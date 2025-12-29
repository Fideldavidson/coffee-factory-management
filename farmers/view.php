<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['manager', 'clerk']);

$id = $_GET['id'] ?? 0;
$farmer = queryOne("SELECT * FROM farmers WHERE id = ?", [$id]);

if (!$farmer) {
    setFlashMessage('error', 'Farmer not found.');
    redirectBase('/farmers/index.php');
}

// Get farmer's delivery history
$deliveries = queryAll("
    SELECT * FROM deliveries 
    WHERE farmer_id = ? 
    ORDER BY delivery_date DESC
", [$id]);

include __DIR__ . '/../includes/header.php';
?>

<div class="flex-between mb-3">
    <div>
        <h2><?php echo e($farmer['name']); ?></h2>
        <p style="color: var(--gray-500);">Farmer ID: <?php echo e($farmer['farmer_id']); ?></p>
    </div>
    <div class="flex gap-2">
        <a href="<?php echo baseUrl('/farmers/edit.php?id=' . $id); ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i>
            Edit
        </a>
        <a href="<?php echo baseUrl('/farmers/index.php'); ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Back
        </a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-truck"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">Total Deliveries</div>
            <div class="stat-value"><?php echo number_format($farmer['total_deliveries']); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-weight"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">Total Quantity</div>
            <div class="stat-value"><?php echo formatWeight($farmer['total_quantity']); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon info">
            <i class="fas fa-calendar"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">Member Since</div>
            <div class="stat-value" style="font-size: 20px;"><?php echo formatDate($farmer['registration_date'], 'M Y'); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon <?php echo $farmer['status'] === 'active' ? 'success' : 'danger'; ?>">
            <i class="fas fa-<?php echo $farmer['status'] === 'active' ? 'check-circle' : 'times-circle'; ?>"></i>
        </div>
        <div class="stat-content">
            <div class="stat-label">Status</div>
            <div class="stat-value" style="font-size: 20px;"><?php echo ucfirst($farmer['status']); ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Farmer Information</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px;">
            <div>
                <div style="font-size: 13px; color: var(--gray-500); margin-bottom: 4px;">Phone Number</div>
                <div style="font-weight: 600;"><?php echo e($farmer['phone']); ?></div>
            </div>
            <div>
                <div style="font-size: 13px; color: var(--gray-500); margin-bottom: 4px;">Location</div>
                <div style="font-weight: 600;"><?php echo e($farmer['location']); ?></div>
            </div>
            <div>
                <div style="font-size: 13px; color: var(--gray-500); margin-bottom: 4px;">Registration Date</div>
                <div style="font-weight: 600;"><?php echo formatDate($farmer['registration_date'], 'F d, Y'); ?></div>
            </div>
            <div>
                <div style="font-size: 13px; color: var(--gray-500); margin-bottom: 4px;">Last Updated</div>
                <div style="font-weight: 600;"><?php echo formatDateTime($farmer['updated_at'], 'M d, Y H:i'); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Delivery History</h3>
        <p class="card-description">All deliveries from this farmer</p>
    </div>
    <div class="card-body">
        <?php if (empty($deliveries)): ?>
            <p style="text-align: center; color: var(--gray-500); padding: 40px;">No deliveries recorded yet.</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Batch Number</th>
                            <th>Quantity</th>
                            <th>Grade</th>
                            <th>Moisture %</th>
                            <th>Delivery Date</th>
                            <th>Status</th>
                            <th>Quality Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deliveries as $delivery): ?>
                            <tr>
                                <td><strong><?php echo e($delivery['batch_number']); ?></strong></td>
                                <td><?php echo formatWeight($delivery['quantity']); ?></td>
                                <td><span class="badge badge-info"><?php echo e($delivery['grade']); ?></span></td>
                                <td><?php echo number_format($delivery['moisture_content'], 1); ?>%</td>
                                <td><?php echo formatDateTime($delivery['delivery_date'], 'M d, Y H:i'); ?></td>
                                <td>
                                    <span class="badge <?php echo getDeliveryStatusColor($delivery['status']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $delivery['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($delivery['quality_score']): ?>
                                        <strong><?php echo e($delivery['quality_score']); ?>/100</strong>
                                    <?php else: ?>
                                        <span style="color: var(--gray-400);">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
