<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['manager', 'clerk']);

// Get filter parameters
$status = $_GET['status'] ?? '';
$grade = $_GET['grade'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM inventory_batches WHERE 1=1";
$params = [];

if ($status) {
    if ($status === 'all') {
        // do nothing, show all
    } else {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
}

if ($grade) {
    if ($grade === 'all') {
        // distinct from "All Grades" option which sends empty string usually, 
        // but if 'all' value is sent handle it.
    } else {
        $sql .= " AND grade = ?";
        $params[] = $grade;
    }
}

if ($search) {
    $sql .= " AND (batch_number LIKE ? OR storage_location LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY received_date DESC";

$batches = queryAll($sql, $params);

// Get summary stats
$totalInventory = queryOne("SELECT COALESCE(SUM(quantity), 0) as total FROM inventory_batches WHERE status != 'exported'")['total'];
$totalBatches = queryOne("SELECT COUNT(*) as count FROM inventory_batches WHERE status != 'exported'")['count'];
$lowStockCount = queryOne("SELECT COUNT(*) as count FROM inventory_batches WHERE quantity < 50 AND status != 'exported'")['count'];
$lowStockPercent = $totalBatches > 0 ? round(($lowStockCount / $totalBatches) * 100) : 0;
$readyExportCount = queryOne("SELECT COUNT(*) as count FROM inventory_batches WHERE status = 'ready_export'")['count'];
$processingCount = queryOne("SELECT COUNT(*) as count FROM inventory_batches WHERE status IN ('received', 'processing', 'dried', 'milled')")['count'];

$pageTitle = "Inventory Management";
include __DIR__ . '/../includes/header.php';
?>

<div class="welcome-section">
    <div>
        <h1 class="welcome-title">Inventory Management</h1>
        <p class="welcome-subtitle">Track coffee batches, stock levels, and processing status</p>
    </div>
    <div class="date-display">
        <div style="font-size: var(--font-size-sm); color: var(--text-muted);">Current Inventory</div>
        <div style="font-weight: 600; color: var(--primary-dark); font-size: var(--font-size-lg);"><?php echo formatWeight($totalInventory); ?></div>
    </div>
</div>

<!-- Stats Grid -->
<div class="inventory-stats-row">
    <div class="inv-stat-card">
        <div class="inv-stat-label">Total Inventory</div>
        <div class="inv-stat-value brown"><?php echo formatWeight($totalInventory); ?></div>
        <div class="inv-stat-sub"><?php echo number_format($totalBatches); ?> total batches</div>
    </div>
    
    <div class="inv-stat-card">
        <div class="inv-stat-label">Low Stock Alert</div>
        <div class="inv-stat-value warning"><?php echo number_format($lowStockCount); ?></div>
        <div class="inv-stat-sub">Batches below 20%</div>
    </div>
    
    <div class="inv-stat-card">
        <div class="inv-stat-label">Ready for Export</div>
        <div class="inv-stat-value success"><?php echo number_format($readyExportCount); ?></div>
        <div class="inv-stat-sub">Processed batches</div>
    </div>
    
    <div class="inv-stat-card">
        <div class="inv-stat-label">Processing</div>
        <div class="inv-stat-value warning"><?php echo number_format($processingCount); ?></div>
        <div class="inv-stat-sub">In progress</div>
    </div>
</div>

<!-- Filters & Search -->
<div>
    <div class="filter-section-title">
        <i class="fas fa-filter"></i> Filters & Search
    </div>
    
    <div class="filter-bar">
        <div class="filter-search">
            <div class="input-with-icon">
                <i class="fas fa-search"></i>
                <input 
                    type="text" 
                    id="searchInput"
                    class="form-control-styled" 
                    placeholder="Search batches..."
                    value="<?php echo e($search); ?>"
                    onkeyup="debouncedSearch()"
                >
            </div>
        </div>
        
        <div class="filter-select">
            <select id="statusFilter" class="form-select no-icon" onchange="filterInventory()">
                <option value="">All Status</option>
                <option value="received" <?php echo $status === 'received' ? 'selected' : ''; ?>>Received</option>
                <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                <option value="dried" <?php echo $status === 'dried' ? 'selected' : ''; ?>>Dried</option>
                <option value="milled" <?php echo $status === 'milled' ? 'selected' : ''; ?>>Milled</option>
                <option value="ready_export" <?php echo $status === 'ready_export' ? 'selected' : ''; ?>>Ready Export</option>
                <option value="exported" <?php echo $status === 'exported' ? 'selected' : ''; ?>>Exported</option>
            </select>
        </div>
        
        <div class="filter-select">
            <select id="gradeFilter" class="form-select no-icon" onchange="filterInventory()">
                <option value="">All Grades</option>
                <option value="AA" <?php echo $grade === 'AA' ? 'selected' : ''; ?>>AA</option>
                <option value="AB" <?php echo $grade === 'AB' ? 'selected' : ''; ?>>AB</option>
                <option value="PB" <?php echo $grade === 'PB' ? 'selected' : ''; ?>>PB</option>
                <option value="C" <?php echo $grade === 'C' ? 'selected' : ''; ?>>C</option>
            </select>
        </div>
        
        <div class="filter-action">
            <button onclick="openAddBatchModal()" class="btn btn-primary">
                <i class="fas fa-box-open" style="margin-right: 8px;"></i> Add New Batch
            </button>
        </div>
    </div>
</div>

<!-- Inventory Card Grid -->
<?php if (empty($batches)): ?>
    <div class="empty-state-card">
        <div class="empty-state-icon">
            <i class="fas fa-box-open"></i>
        </div>
        <p class="empty-state-text">No inventory batches found matching your criteria.</p>
    </div>
<?php else: ?>
    <div class="inventory-horizontal-list">
        <?php foreach ($batches as $batch): 
            $stockLevel = ($batch['original_quantity'] > 0) ? round(($batch['quantity'] / $batch['original_quantity']) * 100, 1) : 0;
            $isProcessing = in_array($batch['status'], ['processing', 'dried', 'milled']);
            $processingText = $isProcessing ? 'In progress' : ($batch['status'] === 'received' ? 'Not started' : 'Completed');
        ?>
            <!-- Horizontal Batch Card -->
            <div class="batch-card-horizontal" 
                 data-batch='<?php echo htmlspecialchars(json_encode($batch), ENT_QUOTES, 'UTF-8'); ?>'
                 data-stock="<?php echo e($stockLevel); ?>"
                 data-processing="<?php echo e($processingText); ?>">
                
                <div class="batch-top-content">
                    <div class="batch-identity-section">
                        <div class="batch-icon-box">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="batch-info-details">
                            <h4><?php echo e($batch['batch_number']); ?></h4>
                            <p><?php echo e($batch['grade']); ?> Grade &bull; <?php echo e($batch['storage_location'] ?? 'Not Assigned'); ?></p>
                            <div class="batch-meta-info">
                                <span>Received: <?php echo formatDate($batch['received_date'], 'd/m/Y'); ?></span>
                                <span class="meta-sep"></span>
                                <span>Quality: <?php echo $batch['quality_score'] ? e($batch['quality_score']).'/100' : 'N/A'; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="batch-status-weight-section">
                        <span class="status-badge-pill <?php echo e($batch['status']); ?>">
                            <i class="far fa-clock"></i> <?php echo str_replace('_', ' ', $batch['status']); ?>
                        </span>
                        <div class="weight-display">
                            <span class="weight-current"><?php echo number_format($batch['quantity'], 2); ?> kg</span>
                            <span class="weight-original">of <?php echo number_format($batch['original_quantity'], 2); ?> kg original</span>
                        </div>
                    </div>
                </div>

                <div class="batch-bottom-content">
                    <div class="batch-detailed-metrics">
                        <div class="metric-block">
                            <span class="metric-label">Moisture Content</span>
                            <span class="metric-value"><?php echo e($batch['moisture_content']); ?>%</span>
                        </div>
                        <div class="metric-block">
                            <span class="metric-label">Current Stock</span>
                            <span class="metric-value"><?php echo e($stockLevel); ?>%</span>
                        </div>
                        <div class="metric-block">
                            <span class="metric-label">Processing Time</span>
                            <span class="metric-value"><?php echo e($processingText); ?></span>
                        </div>
                    </div>
                    
                    <div class="batch-card-actions">
                        <button class="btn btn-action-ghost" onclick='openViewBatchModal(this)'>
                            <i class="far fa-eye"></i> View
                        </button>
                        <button class="btn btn-action-primary" onclick='openProgressBatchModal(this)'>
                            <i class="fas fa-arrow-right"></i> Progress
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>

<script>
let searchTimeout;

function debouncedSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(filterInventory, 500); 
}

function filterInventory() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const grade = document.getElementById('gradeFilter').value;
    
    let url = '<?php echo baseUrl('/inventory/index.php'); ?>?';
    if (search) url += 'search=' + encodeURIComponent(search) + '&';
    if (status) url += 'status=' + status + '&';
    if (grade) url += 'grade=' + grade;
    
    window.location.href = url;
}

// === Modal Logic ===

// Data Helpers
function getBatchFromBtn(btn) {
    const card = btn.closest('.batch-card-horizontal');
    const batch = JSON.parse(card.dataset.batch);
    batch.stockLevel = card.dataset.stock;
    batch.processingText = card.dataset.processing;
    return batch;
}

// 1. Add Batch Modal
function openAddBatchModal() {
    document.getElementById('addBatchModal').classList.add('show');
    document.body.classList.add('modal-open');
}
function closeAddBatchModal() {
    document.getElementById('addBatchModal').classList.remove('show');
    document.body.classList.remove('modal-open');
}

// 2. View Batch Modal
function openViewBatchModal(btn) {
    const batch = getBatchFromBtn(btn);
    
    document.getElementById('viewSubtitle').textContent = 'Detailed tracking for ' + batch.batch_number;
    
    // Header Data
    document.getElementById('viewBatchDisplay').textContent = batch.batch_number;
    document.getElementById('viewGrade').textContent = batch.grade;
    document.getElementById('viewStatus').innerHTML = `<span class="badge ${getStatusBadgeClass(batch.status)}">${batch.status.replace('_', ' ')}</span>`;
    document.getElementById('viewLocation').textContent = batch.storage_location || 'Not Assigned';
    
    // Metrics
    document.getElementById('viewCurrentQty').textContent = batch.quantity + ' kg';
    document.getElementById('viewOriginalQty').textContent = batch.original_quantity + ' kg';
    document.getElementById('viewStockLevel').textContent = batch.stockLevel + '%';
    document.getElementById('viewMoisture').textContent = batch.moisture_content + '%';
    
    // Processing Info
    document.getElementById('viewQuality').textContent = batch.quality_score ? batch.quality_score + '/100' : 'N/A';
    document.getElementById('viewReceived').textContent = new Date(batch.received_date).toLocaleDateString('en-GB');
    document.getElementById('viewProcessing').textContent = batch.processingText;
    
    // Timeline Highlight
    updateViewTimeline(batch.status);
    
    document.getElementById('viewBatchModal').classList.add('show');
    document.body.classList.add('modal-open');
}

function updateViewTimeline(status) {
    const stages = ['received', 'processing', 'dried', 'milled', 'ready_export'];
    const currentIdx = stages.indexOf(status);
    
    // Implementation of visual timeline logic if we added one, otherwise just display for now
}

function closeViewBatchModal() {
    document.getElementById('viewBatchModal').classList.remove('show');
    document.body.classList.remove('modal-open');
}

// 3. Progress Batch Modal
function openProgressBatchModal(btn) {
    const batch = getBatchFromBtn(btn);
    
    // Set Header Info
    document.getElementById('progBatchNumber').textContent = batch.batch_number;
    
    // Set Info Banner
    document.getElementById('progCurrentStatus').innerHTML = `<span class="badge ${getStatusBadgeClass(batch.status)}">${batch.status.replace('_', ' ')}</span>`;
    
    // Set Form Values
    document.getElementById('progBatchId').value = batch.id;
    document.getElementById('progAdjustedQty').value = batch.quantity; 
    document.getElementById('progNewMoisture').value = batch.moisture_content;
    document.getElementById('progNewLocation').value = batch.storage_location || '';
    
    // Set Status Select
    const statusSelect = document.getElementById('progNewStatusSelect');
    statusSelect.value = getNextStatus(batch.status);
    
    // Reset Loss Info
    document.getElementById('progLossInput').value = '';
    document.getElementById('progKgLoss').textContent = '0.00 kg';
    
    // Store original quantity for calculation
    document.getElementById('progLossInput').dataset.originalQty = batch.quantity;
    
    document.getElementById('progressBatchModal').classList.add('show');
    document.body.classList.add('modal-open');
}

function closeProgressBatchModal() {
    document.getElementById('progressBatchModal').classList.remove('show');
    document.body.classList.remove('modal-open');
}

function calculateAdjustedQty() {
    const originalQty = parseFloat(document.getElementById('progLossInput').dataset.originalQty);
    const lossPercent = parseFloat(document.getElementById('progLossInput').value) || 0;
    
    const lossAmount = originalQty * (lossPercent / 100);
    const adjustedQty = (originalQty - lossAmount).toFixed(2);
    
    document.getElementById('progKgLoss').textContent = lossAmount.toFixed(2) + ' kg';
    document.getElementById('progAdjustedQty').value = adjustedQty;
}

// Helpers
function getStatusBadgeClass(status) {
    const map = {
        'received': 'badge-primary',
        'processing': 'badge-warning',
        'dried': 'badge-info',
        'milled': 'badge-success',
        'ready_export': 'badge-success',
        'exported': 'badge-dark'
    };
    return map[status] || 'badge-light';
}

function getNextStatus(current) {
    const flow = ['received', 'processing', 'dried', 'milled', 'ready_export', 'exported'];
    const idx = flow.indexOf(current);
    if (idx >= 0 && idx < flow.length - 1) {
        return flow[idx + 1];
    }
    return current; // No next status
}

// Close modals on overlay click
window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.parentElement.classList.remove('show');
    }
}
</script>

<!-- Add Batch Modal -->
<div id="addBatchModal" class="modal">
    <div class="modal-overlay" onclick="closeAddBatchModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Batch</h3>
            <button class="modal-close" onclick="closeAddBatchModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p class="modal-subtitle">Enter the details for the new inventory batch.</p>
            <form action="add_batch.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="form-group">
                    <label class="form-label">Batch Number *</label>
                    <input type="text" name="batch_number" class="form-input" placeholder="e.g., BATCH-2024-001" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Grade *</label>
                    <select name="grade" class="form-input" required>
                        <option value="AA">AA Grade</option>
                        <option value="AB">AB Grade</option>
                        <option value="PB">PB Grade</option>
                        <option value="C">C Grade</option>
                        <option value="TT">TT</option>
                        <option value="T">T</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Quantity (kg) *</label>
                    <input type="number" step="0.01" name="quantity" class="form-input" placeholder="e.g., 5000" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Storage Location *</label>
                    <input type="text" name="storage_location" class="form-input" placeholder="e.g., Warehouse A-1" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Moisture Content (%) *</label>
                    <input type="number" step="0.01" name="moisture_content" class="form-input" placeholder="e.g., 11.5" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Quality Score (0-100)</label>
                    <input type="number" name="quality_score" class="form-input" placeholder="e.g., 85" min="0" max="100">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeAddBatchModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Batch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Batch Modal -->
<div id="viewBatchModal" class="modal">
    <div class="modal-overlay" onclick="closeViewBatchModal()"></div>
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3>Batch Details</h3>
            <button class="modal-close" onclick="closeViewBatchModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                <div>
                    <div id="viewBatchDisplay" style="font-size: 24px; font-weight: 700; color: var(--text-main);"></div>
                    <p class="modal-subtitle" id="viewSubtitle" style="margin-bottom: 0;">Tracking Information</p>
                </div>
                <div id="viewStatus"></div>
            </div>
            
            <div class="form-group" style="background: #F8F5F2; padding: 20px; border-radius: 12px; margin-bottom: 24px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label class="detail-label" style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                            <i class="fas fa-layer-group" style="color: var(--primary);"></i> Grade
                        </label>
                        <div id="viewGrade" style="font-size: 16px; font-weight: 600; color: var(--text-main);"></div>
                    </div>
                    <div>
                        <label class="detail-label" style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                            <i class="fas fa-map-marker-alt" style="color: var(--primary);"></i> Location
                        </label>
                        <div id="viewLocation" style="font-size: 16px; font-weight: 600; color: var(--text-main);"></div>
                    </div>
                </div>
            </div>

            <h4 style="font-size: 14px; font-weight: 600; color: var(--text-muted); margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.5px;">metrics & quality</h4>
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px;">
                <div class="detail-item" style="background: white; border: 1px solid var(--border-color); padding: 12px; border-radius: 8px;">
                    <span class="detail-label"><i class="fas fa-weight-hanging" style="color: var(--text-muted); margin-right: 6px;"></i> Current Qty</span>
                    <span class="detail-value" id="viewCurrentQty" style="font-size: 16px;"></span>
                </div>
                <div class="detail-item" style="background: white; border: 1px solid var(--border-color); padding: 12px; border-radius: 8px;">
                    <span class="detail-label"><i class="fas fa-box" style="color: var(--text-muted); margin-right: 6px;"></i> Original Qty</span>
                    <span class="detail-value" id="viewOriginalQty" style="font-size: 16px;"></span>
                </div>
                <div class="detail-item" style="background: white; border: 1px solid var(--border-color); padding: 12px; border-radius: 8px;">
                    <span class="detail-label"><i class="fas fa-tint" style="color: var(--info); margin-right: 6px;"></i> Moisture</span>
                    <span class="detail-value" id="viewMoisture" style="font-size: 16px;"></span>
                </div>
                <div class="detail-item" style="background: white; border: 1px solid var(--border-color); padding: 12px; border-radius: 8px;">
                    <span class="detail-label"><i class="fas fa-star" style="color: var(--warning); margin-right: 6px;"></i> Quality Score</span>
                    <span class="detail-value" id="viewQuality" style="font-size: 16px;"></span>
                </div>
                <div class="detail-item" style="background: white; border: 1px solid var(--border-color); padding: 12px; border-radius: 8px;">
                     <span class="detail-label"><i class="fas fa-chart-pie" style="color: var(--primary); margin-right: 6px;"></i> Stock Level</span>
                     <span class="detail-value" id="viewStockLevel" style="font-size: 16px;"></span>
                </div>
            </div>

            <div style="background: #F5F5F5; padding: 16px; border-radius: 8px; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <span class="detail-label" style="display: block; margin-bottom: 4px;">Received Date</span>
                    <span class="detail-value" id="viewReceived" style="font-weight: 600;"></span>
                </div>
                <div style="text-align: right;">
                    <span class="detail-label" style="display: block; margin-bottom: 4px;">Processing Stage</span>
                    <span class="detail-value" id="viewProcessing" style="font-weight: 600; color: var(--primary);"></span>
                </div>
            </div>

            <div class="modal-actions" style="margin-top: 24px;">
                <button type="button" class="btn btn-primary" style="width: 100%;" onclick="closeViewBatchModal()">Close Details</button>
            </div>
        </div>
    </div>
</div>

<!-- Progress Batch Modal -->
<div id="progressBatchModal" class="modal">
    <div class="modal-overlay" onclick="closeProgressBatchModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Progress Batch Stage</h3>
            <button class="modal-close" onclick="closeProgressBatchModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p class="modal-subtitle">Update processing status and details for batch <span id="progBatchNumber" style="font-weight: 600;"></span></p>
            
            <div class="modal-info-banner">
                <div class="info-row">
                    <div class="info-label">Current Status:</div>
                    <div class="info-value" id="progCurrentStatus"></div>
                </div>
            </div>

            <form action="progress_batch.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="batch_id" id="progBatchId">
                
                <div class="form-group">
                    <label class="form-label" style="font-weight: 600;">Select New Status *</label>
                    <div class="select-wrapper">
                        <select name="new_status" id="progNewStatusSelect" class="form-select highlight-field" required>
                            <option value="received">Received</option>
                            <option value="processing">Pulping & Fermentation</option>
                            <option value="dried">Drying Stage Complete</option>
                            <option value="milled">Milling (Hulling) Complete</option>
                            <option value="ready_export">Ready for Export/Sale</option>
                            <option value="exported">Exported / Dispatched</option>
                        </select>
                    </div>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label">Loss (%)</label>
                        <input type="number" step="0.1" id="progLossInput" class="form-input" placeholder="0.0" oninput="calculateAdjustedQty()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Loss in KG</label>
                        <div id="progKgLoss" style="padding: 10px; background: #f8f9fa; border-radius: 4px; font-weight: 600; color: var(--danger);">0.00 kg</div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">New Net Weight (kg)</label>
                    <input type="number" step="0.01" name="adjusted_quantity" id="progAdjustedQty" class="form-input" style="font-weight: bold; color: var(--primary-dark);">
                    <small style="color: var(--text-muted);">Adjusted amount after processing loss</small>
                </div>

                <div class="form-group">
                    <label class="form-label">New Moisture %</label>
                    <input type="number" step="0.1" name="new_moisture" id="progNewMoisture" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Update Storage Location</label>
                    <input type="text" name="storage_location" id="progNewLocation" class="form-input" placeholder="e.g., Bin-04, Warehouse C">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeProgressBatchModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save" style="margin-right: 8px;"></i> Save Progress</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
