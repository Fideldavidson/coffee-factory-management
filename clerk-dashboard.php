<?php
require_once __DIR__ . '/includes/functions.php';
requireRole('clerk');

// Get today's statistics
$stats = [
    'today_deliveries' => queryOne("SELECT COUNT(*) as count FROM deliveries WHERE DATE(delivery_date) = CURDATE()")['count'],
    'pending_deliveries' => queryOne("SELECT COUNT(*) as count FROM deliveries WHERE status = 'pending'")['count'],
    'today_quantity' => queryOne("SELECT COALESCE(SUM(quantity), 0) as total FROM deliveries WHERE DATE(delivery_date) = CURDATE()")['total'],
    'active_farmers' => queryOne("SELECT COUNT(*) as count FROM farmers WHERE status = 'active'")['count'],
];

// Get today's deliveries
$todayDeliveries = queryAll("
    SELECT d.*, f.name as farmer_name 
    FROM deliveries d
    JOIN farmers f ON d.farmer_id = f.id
    WHERE DATE(d.delivery_date) = CURDATE()
    ORDER BY d.delivery_date DESC
");

// Get pending deliveries
$pendingDeliveries = queryAll("
    SELECT d.*, f.name as farmer_name 
    FROM deliveries d
    JOIN farmers f ON d.farmer_id = f.id
    WHERE d.status = 'pending'
    ORDER BY d.delivery_date DESC
    LIMIT 5
");

include __DIR__ . '/includes/header.php';
?>

<div class="welcome-section">
    <div>
        <h1 class="welcome-title">Day Operations, <?php echo e(ucfirst(getCurrentUserRole())); ?></h1>
        <p class="welcome-subtitle">Managing deliveries and farmer intake for today</p>
    </div>
    <div class="date-display">
        <div style="font-size: var(--font-size-sm); color: var(--text-muted);">Today</div>
        <div style="font-weight: 600; color: var(--foreground); font-size: var(--font-size-base);"><?php echo date('l, d F Y'); ?></div>
    </div>
</div>

<div class="inventory-stats-row">
    <a href="<?php echo baseUrl('/deliveries/index.php'); ?>" class="stat-card">
        <div class="stat-info">
            <h3>Today's Deliveries</h3>
            <div class="stat-value"><?php echo number_format($stats['today_deliveries']); ?></div>
            <div class="stat-subtext"><?php echo number_format($stats['pending_deliveries']); ?> pending review</div>
        </div>
        <div class="stat-icon green">
            <i class="fas fa-truck"></i>
        </div>
    </a>
    
    <a href="<?php echo baseUrl('/deliveries/index.php?status=pending'); ?>" class="stat-card">
        <div class="stat-info">
            <h3>Pending Deliveries</h3>
            <div class="stat-value"><?php echo number_format($stats['pending_deliveries']); ?></div>
            <div class="stat-subtext">Awaiting processing</div>
        </div>
        <div class="stat-icon orange">
            <i class="fas fa-clock"></i>
        </div>
    </a>
    
    <div class="stat-card">
        <div class="stat-info">
            <h3>Today's Quantity</h3>
            <div class="stat-value"><?php echo formatWeight($stats['today_quantity']); ?></div>
            <div class="stat-subtext">Total weight received</div>
        </div>
        <div class="stat-icon purple">
            <i class="fas fa-weight"></i>
        </div>
    </div>
    
    <a href="<?php echo baseUrl('/farmers/index.php'); ?>" class="stat-card">
        <div class="stat-info">
            <h3>Active Farmers</h3>
            <div class="stat-value"><?php echo number_format($stats['active_farmers']); ?></div>
            <div class="stat-subtext">Registered in system</div>
        </div>
        <div class="stat-icon blue">
            <i class="fas fa-users"></i>
        </div>
    </a>
</div>

<div class="quick-actions">
    <h2 class="section-title">Quick Actions</h2>
    <p class="section-subtitle">Common tasks for daily operations</p>
    
    <div class="action-list">
        <a href="<?php echo baseUrl('/deliveries/index.php'); ?>" class="action-btn">
            <i class="fas fa-plus"></i>
            <span>Record Delivery</span>
        </a>
        <a href="<?php echo baseUrl('/farmers/index.php'); ?>" class="action-btn">
            <i class="fas fa-user-plus"></i>
            <span>Add Farmer</span>
        </a>
        <a href="<?php echo baseUrl('/inventory/index.php'); ?>" class="action-btn">
            <i class="fas fa-boxes"></i>
            <span>View Inventory</span>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="flex-between">
            <div>
                <h3 class="card-title">Pending Processing</h3>
                <p class="card-description">Deliveries awaiting intake verification and grading</p>
            </div>
            <a href="<?php echo baseUrl('/deliveries/index.php?status=pending'); ?>" class="btn btn-secondary btn-sm">
                View Registry
            </a>
        </div>
    </div>
    <div class="card-body" style="padding: 10px 24px 24px;">
        <?php if (empty($pendingDeliveries)): ?>
            <div style="text-align: center; color: var(--text-muted); padding: 40px;">
                <i class="fas fa-check-double" style="font-size: 32px; opacity: 0.3; margin-bottom: 12px; display: block;"></i>
                All caught up! No pending deliveries.
            </div>
        <?php else: ?>
            <div class="task-list">
                <?php foreach ($pendingDeliveries as $delivery): 
                    $initials = strtoupper(substr($delivery['farmer_name'], 0, 1));
                    if (strpos($delivery['farmer_name'], ' ') !== false) {
                        $parts = explode(' ', $delivery['farmer_name']);
                        $initials .= strtoupper(substr($parts[1], 0, 1));
                    }
                ?>
                    <div class="task-card" style="display: flex; align-items: center; justify-content: space-between; padding: 16px; background: #FAF9F7; border: 1px solid #E0D5CE; border-radius: 12px; margin-bottom: 12px; transition: all 0.2s ease;">
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <div style="width: 44px; height: 44px; background: #8D6E63; color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
                                <?php echo $initials; ?>
                            </div>
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <h4 style="font-size: 15px; font-weight: 600; color: #3E2723;"><?php echo e($delivery['farmer_name']); ?></h4>
                                    <span style="font-size: 11px; padding: 2px 8px; background: white; border: 1px solid #E0D5CE; border-radius: 4px; color: #8D6E63; font-weight: 600;"><?php echo e($delivery['batch_number']); ?></span>
                                </div>
                                <p style="font-size: 13px; color: #8D6E63; margin-top: 2px;">
                                    <i class="far fa-clock" style="font-size: 11px; margin-right: 4px;"></i> 
                                    Received <?php echo formatDateTime($delivery['delivery_date'], 'H:i'); ?> • moisture: <?php echo e($delivery['moisture_content']); ?>%
                                </p>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 24px;">
                            <div style="text-align: right;">
                                <div style="font-size: 18px; font-weight: 700; color: #3E2723;"><?php echo formatWeight($delivery['quantity']); ?></div>
                                <div style="font-size: 11px; color: #8D6E63; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Grade: <?php echo e($delivery['grade']); ?></div>
                            </div>
                            <a href="<?php echo baseUrl('/deliveries/view.php?id=' . $delivery['id']); ?>" class="btn btn-action-primary">
                                Process <i class="fas fa-arrow-right" style="margin-left: 8px; font-size: 11px;"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="activity-section" style="padding: 32px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #F5F5F5; margin-bottom: 32px;">
    <h3 class="section-title">Today's Activity Feed</h3>
    <p class="section-subtitle">Real-time log of deliveries recorded today</p>
    
    <div class="activity-list" style="margin-top: 24px;">
        <?php if (empty($todayDeliveries)): ?>
            <div class="activity-item">
                <div class="activity-content">
                    <p style="color: var(--text-muted); text-align: center;">No deliveries recorded today</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($todayDeliveries as $delivery): ?>
                <div class="activity-item" style="padding: 20px 0; border-bottom: 1px solid #FAF9F7;">
                    <div class="activity-dot <?php 
                        echo $delivery['status'] === 'pending' ? 'dot-orange' : 
                            ($delivery['status'] === 'received' ? 'dot-green' : 'dot-blue'); 
                    ?>"></div>
                    <div class="activity-content" style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
                        <div>
                            <h4 style="font-size: 15px; font-weight: 600;"><?php echo e($delivery['farmer_name']); ?> recorded a delivery</h4>
                            <p style="margin-top: 4px; color: var(--text-muted);">
                                Grade <?php echo e($delivery['grade']); ?> • Batch <?php echo e($delivery['batch_number']); ?>
                            </p>
                            <small style="color: #A67C52; font-weight: 600; font-size: 11px;">
                                <i class="far fa-clock"></i> <?php echo formatDateTime($delivery['delivery_date'], 'H:i'); ?>
                            </small>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 16px; font-weight: 700; color: #3E2723;"><?php echo formatWeight($delivery['quantity']); ?></div>
                            <span class="badge <?php echo getDeliveryStatusColor($delivery['status']); ?>" style="margin-top: 4px;">
                                <?php echo ucfirst(str_replace('_', ' ', $delivery['status'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
