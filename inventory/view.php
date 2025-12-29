<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['manager', 'clerk']);

$id = $_GET['id'] ?? 0;
$batch = queryOne("SELECT * FROM inventory_batches WHERE id = ?", [$id]);

if (!$batch) {
    setFlashMessage('error', 'Batch not found.');
    redirectBase('/inventory/index.php');
}

// Handle status and quantity updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    if (isset($_POST['update_status'])) {
        $newStatus = $_POST['status'];
        $storage_location = sanitize($_POST['storage_location'] ?? '');
        
        $sql = "UPDATE inventory_batches SET status = ?, storage_location = ?, processed_date = NOW() WHERE id = ?";
        execute($sql, [$newStatus, $storage_location, $id]);
        
        if ($newStatus === 'exported') {
            execute("UPDATE inventory_batches SET export_date = NOW() WHERE id = ?", [$id]);
        }
        
        setFlashMessage('success', 'Batch status updated successfully.');
        redirectBase('/inventory/view.php?id=' . $id);
    } elseif (isset($_POST['update_quantity'])) {
        $newQuantity = $_POST['quantity'];
        
        if ($newQuantity > 0 && $newQuantity <= $batch['original_quantity']) {
            execute("UPDATE inventory_batches SET quantity = ? WHERE id = ?", [$newQuantity, $id]);
            setFlashMessage('success', 'Quantity updated successfully.');
            redirectBase('/inventory/view.php?id=' . $id);
        } else {
            setFlashMessage('error', 'Invalid quantity value.');
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="flex-between mb-3">
    <div>
        <h2>Batch Details</h2>
        <p style="color: var(--gray-500);"><?php echo e($batch['batch_number']); ?></p>
    </div>
    <div class="flex gap-2">
        <a href="<?php echo baseUrl('/inventory/index.php'); ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Back
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="flex-between">
            <h3 class="card-title">Batch Information</h3>
            <span class="badge <?php echo getInventoryStatusColor($batch['status']); ?>" style="font-size: 14px; padding: 8px 16px;">
                <?php echo ucfirst(str_replace('_', ' ', $batch['status'])); ?>
            </span>
        </div>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px;">
            <div>
                <div style="font-size: 13px; color: var(--gray-500); margin-bottom: 4px;">Batch Number</div>
                <div style="font-weight: 600; font-size: 16px;"><?php echo e($batch['batch_number']); ?></div>
            </div>
            <div>
                <div style="font-size: 13px; color: var(--gray-500); margin-bottom: 4px;">Coffee Grade</div>
                <div><span class="badge badge-info" style="font-size: 14px;"><?php echo e($batch['grade']); ?></span></div>
            </div>
            <div>
                <div style="font-size: 13px; color: var(--gray-500); margin-bottom: 4px;">Current Quantity</div>
                <div style="font-weight: 600; font-size: 16px;"><?php echo formatWeight($batch['quantity']); ?></div>
            </div>
            <div>
                <div style="font-size: 13px; color: var(--gray-500); margin-bottom: 4px;">Original Quantity</div>
                <div style="font-weight: 600;"><?php echo formatWeight($batch['original_quantity']); ?></div>
            </div>
            <div>
                <div style="font-size: 13px; color: var(--gray-500); margin-bottom: 4px;">Moisture Content</div>
                <div style="font-weight: 600;"><?php echo number_format($batch['moisture_content'], 1); ?>%</div>
            </div>
            <?php if ($batch['quality_score']): ?>
                <div>
                    <div style="font-size: 13px; color: var(--gray-500); margin-bottom: 4px;">Quality Score</div>
                    <div style="font-weight: 600; font-size: 16px;"><?php echo e($batch['quality_score']); ?>/100</div>
                </div>
            <?php endif; ?>
            <div>
                <div style="font-size: 13px; color: var(--gray-500); margin-bottom: 4px;">Storage Location</div>
                <div style="font-weight: 600;"><?php echo e($batch['storage_location'] ?? 'Not assigned'); ?></div>
            </div>
            <div>
                <div style="font-size: 13px; color: var(--gray-500); margin-bottom: 4px;">Received Date</div>
                <div style="font-weight: 600;"><?php echo formatDateTime($batch['received_date'], 'M d, Y H:i'); ?></div>
            </div>
            <?php if ($batch['processed_date']): ?>
                <div>
                    <div style="font-size: 13px; color: var(--gray-500); margin-bottom: 4px;">Processed Date</div>
                    <div style="font-weight: 600;"><?php echo formatDateTime($batch['processed_date'], 'M d, Y H:i'); ?></div>
                </div>
            <?php endif; ?>
            <?php if ($batch['export_date']): ?>
                <div>
                    <div style="font-size: 13px; color: var(--gray-500); margin-bottom: 4px;">Export Date</div>
                    <div style="font-weight: 600;"><?php echo formatDateTime($batch['export_date'], 'M d, Y H:i'); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($batch['status'] !== 'exported'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Update Batch Status</h3>
            <p class="card-description">Move batch through processing stages</p>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="status">Status *</label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="received" <?php echo $batch['status'] === 'received' ? 'selected' : ''; ?>>Received</option>
                            <option value="processing" <?php echo $batch['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="dried" <?php echo $batch['status'] === 'dried' ? 'selected' : ''; ?>>Dried</option>
                            <option value="milled" <?php echo $batch['status'] === 'milled' ? 'selected' : ''; ?>>Milled</option>
                            <option value="ready_export" <?php echo $batch['status'] === 'ready_export' ? 'selected' : ''; ?>>Ready for Export</option>
                            <option value="exported" <?php echo $batch['status'] === 'exported' ? 'selected' : ''; ?>>Exported</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="storage_location">Storage Location</label>
                        <input 
                            type="text" 
                            id="storage_location" 
                            name="storage_location" 
                            class="form-control" 
                            value="<?php echo e($batch['storage_location'] ?? ''); ?>"
                            placeholder="e.g., Warehouse A - Section 1"
                        >
                    </div>
                </div>
                
                <button type="submit" name="update_status" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Update Status
                </button>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Adjust Quantity</h3>
            <p class="card-description">Update quantity (e.g., due to processing loss)</p>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="form-group">
                    <label class="form-label" for="quantity">New Quantity (kg) *</label>
                    <input 
                        type="number" 
                        id="quantity" 
                        name="quantity" 
                        class="form-control" 
                        step="0.01"
                        min="0"
                        max="<?php echo e($batch['original_quantity']); ?>"
                        value="<?php echo e($batch['quantity']); ?>"
                        required
                    >
                    <small style="color: var(--gray-500);">Cannot exceed original quantity: <?php echo formatWeight($batch['original_quantity']); ?></small>
                </div>
                
                <button type="submit" name="update_quantity" class="btn btn-warning">
                    <i class="fas fa-edit"></i>
                    Update Quantity
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
