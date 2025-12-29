<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['manager', 'clerk']);

$id = $_GET['id'] ?? 0;
$delivery = queryOne("
    SELECT d.*, f.name as farmer_name, f.farmer_id as f_code, f.phone as farmer_phone, f.location as farmer_location,
           u.name as recorded_by_name
    FROM deliveries d
    JOIN farmers f ON d.farmer_id = f.id
    LEFT JOIN users u ON d.recorded_by = u.id
    WHERE d.id = ?
", [$id]);

if (!$delivery) {
    setFlashMessage('error', 'Delivery not found.');
    redirectBase('/deliveries/index.php');
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $quality_score = $_POST['quality_score'] ?? null;
    $notes = sanitize($_POST['notes'] ?? '');
    
    if ($action === 'quality_check') {
        execute("UPDATE deliveries SET status = 'quality_check', processed_date = NOW() WHERE id = ?", [$id]);
        setFlashMessage('success', 'Delivery moved to quality check.');
    } elseif ($action === 'approve') {
        if ($quality_score) {
            // Approve delivery
            execute("UPDATE deliveries SET status = 'approved', quality_score = ?, processed_date = NOW() WHERE id = ?", 
                   [$quality_score, $id]);
            
            // Update farmer statistics
            execute("UPDATE farmers SET 
                    total_deliveries = total_deliveries + 1, 
                    total_quantity = total_quantity + ? 
                    WHERE id = ?", 
                    [$delivery['quantity'], $delivery['farmer_id']]);
            
            // Create inventory batch
            $sql = "INSERT INTO inventory_batches (batch_number, grade, quantity, original_quantity, moisture_content, status, quality_score)
                    VALUES (?, ?, ?, ?, ?, 'received', ?)";
            execute($sql, [
                $delivery['batch_number'],
                $delivery['grade'],
                $delivery['quantity'],
                $delivery['quantity'],
                $delivery['moisture_content'],
                $quality_score
            ]);
            
            setFlashMessage('success', 'Delivery approved and added to inventory.');
        } else {
            setFlashMessage('error', 'Quality score is required for approval.');
        }
    } elseif ($action === 'reject') {
        execute("UPDATE deliveries SET status = 'rejected', quality_score = ?, processed_date = NOW(), notes = ? WHERE id = ?", 
               [$quality_score, $notes, $id]);
        setFlashMessage('warning', 'Delivery rejected.');
    }
    
    redirectBase('/deliveries/view.php?id=' . $id);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="welcome-section" style="margin-bottom: 32px;">
    <div>
        <h1 class="welcome-title">Delivery Details</h1>
        <p class="welcome-subtitle">Batch: <?php echo e($delivery['batch_number']); ?></p>
    </div>
    <div style="text-align: right;">
        <a href="<?php echo baseUrl('/clerk-dashboard.php'); ?>" class="btn" style="background: #1B5E20; color: white; border-radius: 8px; padding: 10px 24px; font-weight: 600;">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <div style="margin-top: 12px;">
            <span class="badge <?php echo getDeliveryStatusColor($delivery['status']); ?>" style="font-size: 14px; padding: 10px 24px; border-radius: 20px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">
                <?php echo str_replace('_', ' ', $delivery['status']); ?>
            </span>
        </div>
    </div>
</div>

<div class="detail-section" style="background: white; border-radius: 12px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 32px; border: 1px solid #FAF9F7;">
    <h3 style="font-size: 18px; font-weight: 700; color: #3E2723; margin-bottom: 24px;">Delivery Information</h3>
    
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 32px;">
        <div class="info-group">
            <div style="font-size: 13px; color: #8D6E63; margin-bottom: 8px; font-weight: 500;">Batch Number</div>
            <div style="font-size: 16px; font-weight: 700; color: #3E2723; overflow: hidden; text-overflow: ellipsis;"><?php echo e($delivery['batch_number']); ?></div>
        </div>
        <div class="info-group">
            <div style="font-size: 13px; color: #8D6E63; margin-bottom: 8px; font-weight: 500;">Quantity</div>
            <div style="font-size: 16px; font-weight: 700; color: #3E2723;"><?php echo formatWeight($delivery['quantity']); ?></div>
        </div>
        <div class="info-group">
            <div style="font-size: 13px; color: #8D6E63; margin-bottom: 8px; font-weight: 500;">Coffee Grade</div>
            <div style="display: flex;"><span style="background: #E3F2FD; color: #1976D2; padding: 4px 12px; border-radius: 12px; font-weight: 700; font-size: 13px;"><?php echo e($delivery['grade']); ?></span></div>
        </div>
        <div class="info-group">
            <div style="font-size: 13px; color: #8D6E63; margin-bottom: 8px; font-weight: 500;">Moisture Content</div>
            <div style="font-size: 16px; font-weight: 700; color: #3E2723;"><?php echo number_format($delivery['moisture_content'], 1); ?>%</div>
        </div>
        
        <div class="info-group">
            <div style="font-size: 13px; color: #8D6E63; margin-bottom: 8px; font-weight: 500;">Delivery Date</div>
            <div style="font-size: 15px; font-weight: 600; color: #3E2723;"><?php echo formatDateTime($delivery['delivery_date'], 'M d, Y H:i'); ?></div>
        </div>
        <div class="info-group">
            <div style="font-size: 13px; color: #8D6E63; margin-bottom: 8px; font-weight: 500;">Recorded By</div>
            <div style="font-size: 15px; font-weight: 600; color: #3E2723;"><?php echo e($delivery['recorded_by_name'] ?? 'System'); ?></div>
        </div>
        <?php if ($delivery['processed_date']): ?>
            <div class="info-group">
                <div style="font-size: 13px; color: #8D6E63; margin-bottom: 8px; font-weight: 500;">Processed Date</div>
                <div style="font-size: 15px; font-weight: 600; color: #3E2723;"><?php echo formatDateTime($delivery['processed_date'], 'M d, Y H:i'); ?></div>
            </div>
        <?php endif; ?>
        <?php if ($delivery['quality_score']): ?>
            <div class="info-group">
                <div style="font-size: 13px; color: #8D6E63; margin-bottom: 8px; font-weight: 500;">Quality Score</div>
                <div style="font-size: 16px; font-weight: 700; color: #3E2723;"><?php echo $delivery['quality_score']; ?>/100</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="detail-section" style="background: white; border-radius: 12px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 32px; border: 1px solid #FAF9F7;">
    <h3 style="font-size: 18px; font-weight: 700; color: #3E2723; margin-bottom: 24px;">Farmer Information</h3>
    
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 32px;">
        <div class="info-group">
            <div style="font-size: 13px; color: #8D6E63; margin-bottom: 8px; font-weight: 500;">Farmer Name</div>
            <div style="font-size: 16px; font-weight: 600; color: #3E2723;"><?php echo e($delivery['farmer_name']); ?></div>
        </div>
        <div class="info-group">
            <div style="font-size: 13px; color: #8D6E63; margin-bottom: 8px; font-weight: 500;">Farmer ID</div>
            <div style="font-size: 16px; font-weight: 600; color: #3E2723;"><?php echo e($delivery['f_code']); ?></div>
        </div>
        <div class="info-group">
            <div style="font-size: 13px; color: #8D6E63; margin-bottom: 8px; font-weight: 500;">Phone</div>
            <div style="font-size: 16px; font-weight: 600; color: #3E2723;"><?php echo e($delivery['farmer_phone']); ?></div>
        </div>
        <div class="info-group">
            <div style="font-size: 13px; color: #8D6E63; margin-bottom: 8px; font-weight: 500;">Location</div>
            <div style="font-size: 16px; font-weight: 600; color: #3E2723;"><?php echo e($delivery['farmer_location']); ?></div>
        </div>
    </div>
</div>

<?php if ($delivery['status'] === 'pending' || $delivery['status'] === 'quality_check'): ?>
    <div class="detail-section" style="background: white; border-radius: 12px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 32px; border: 1px solid #FAF9F7;">
        <h3 style="font-size: 18px; font-weight: 700; color: #3E2723; margin-bottom: 8px;">Process Delivery</h3>
        <p style="color: #8D6E63; font-size: 14px; margin-bottom: 24px;">Update delivery status and quality assessment</p>
        
        <form method="POST" action="">
            <?php if ($delivery['status'] === 'pending'): ?>
                <button type="submit" name="action" value="quality_check" class="btn" style="background: #5D4037; color: white; border-radius: 8px; padding: 12px 32px; font-weight: 600;">
                    <i class="fas fa-check-square"></i> Move to Quality Check
                </button>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 32px; margin-bottom: 24px;">
                    <div class="form-group">
                        <label class="form-label" for="quality_score">Quality Score (0-100) *</label>
                        <input 
                            type="number" 
                            id="quality_score" 
                            name="quality_score" 
                            class="form-control-styled" 
                            min="0"
                            max="100"
                            placeholder="e.g., 85"
                            required
                        >
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="notes">Additional Notes</label>
                    <textarea 
                        id="notes" 
                        name="notes" 
                        class="form-control-styled" 
                        rows="3"
                        placeholder="Any quality assessment notes..."
                    ></textarea>
                </div>
                
                <div class="flex gap-2">
                    <button type="submit" name="action" value="approve" class="btn" style="background: #2E7D32; color: white; border-radius: 8px; padding: 12px 32px; font-weight: 600;">
                        <i class="fas fa-check"></i> Approve & Add to Inventory
                    </button>
                    <button type="submit" name="action" value="reject" class="btn" style="background: #C62828; color: white; border-radius: 8px; padding: 12px 32px; font-weight: 600;">
                        <i class="fas fa-times"></i> Reject Delivery
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
