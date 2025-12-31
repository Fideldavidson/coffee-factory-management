<?php
require_once __DIR__ . '/includes/functions.php';
requireRole('manager');

// Get dashboard statistics
$stats = [
    'total_farmers' => queryOne("SELECT COUNT(*) as count FROM farmers WHERE status = 'active'")['count'],
    'today_deliveries' => queryOne("SELECT COUNT(*) as count FROM deliveries WHERE DATE(delivery_date) = CURDATE()")['count'],
    'pending_review' => queryOne("SELECT COUNT(*) as count FROM deliveries WHERE status = 'pending'")['count'],
    'total_inventory' => queryOne("SELECT COALESCE(SUM(quantity), 0) as total FROM inventory_batches WHERE status != 'exported'")['total'],
    'low_stock' => queryOne("SELECT COUNT(*) as count FROM inventory_batches WHERE quantity < 50 AND status != 'exported'")['count'],
];

// Get Recent Activity (Union of Deliveries, Inventory, Farmers)
$recentActivity = queryAll("
    (SELECT 
        'delivery' as type,
        d.delivery_date as activity_date,
        CONCAT('New delivery recorded') as title,
        CONCAT(f.name, ' - ', d.quantity, 'kg ', d.grade) as description,
        'success' as status_color
    FROM deliveries d
    JOIN farmers f ON d.farmer_id = f.id
    ORDER BY d.delivery_date DESC LIMIT 5)
    
    UNION ALL
    
    (SELECT 
        'inventory' as type,
        COALESCE(ib.updated_at, ib.created_at) as activity_date,
        CONCAT('Batch processing updated') as title,
        CONCAT(ib.batch_number, ' - Status: ', ib.status) as description,
        'info' as status_color
    FROM inventory_batches ib
    WHERE ib.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY activity_date DESC LIMIT 5)
    
    UNION ALL
    
    (SELECT 
        'farmer' as type,
        created_at as activity_date,
        'New farmer registered' as title,
        CONCAT(name, ' - ', location) as description,
        'warning' as status_color
    FROM farmers
    ORDER BY created_at DESC LIMIT 5)
    
    ORDER BY activity_date DESC
    LIMIT 10
");

include __DIR__ . '/includes/header.php';
?>

<div class="welcome-section">
    <div>
        <h1 class="welcome-title">Welcome Back, <?php echo e(ucfirst(getCurrentUserRole())); ?></h1>
        <p class="welcome-subtitle">Here's what's happening at Meru Coffee Cooperative today</p>
    </div>
    <div class="date-display">
        <div style="font-size: var(--font-size-sm); color: var(--text-muted);">Today</div>
        <div style="font-weight: 600; color: var(--foreground); font-size: var(--font-size-base);"><?php echo date('l, d F Y'); ?></div>
    </div>
</div>

<!-- Statistics Grid -->
<div class="stats-grid">
    <a href="<?php echo baseUrl('/farmers/index.php'); ?>" class="stat-card">
        <div class="stat-info">
            <h3>Total Farmers</h3>
            <div class="stat-value"><?php echo number_format($stats['total_farmers']); ?></div>
            <div class="stat-subtext"><?php echo number_format($stats['total_farmers']); ?> active farmers</div>
        </div>
        <div class="stat-icon blue">
            <i class="fas fa-users"></i>
        </div>
    </a>

    <a href="<?php echo baseUrl('/deliveries/index.php'); ?>" class="stat-card">
        <div class="stat-info">
            <h3>Today's Deliveries</h3>
            <div class="stat-value"><?php echo number_format($stats['today_deliveries']); ?></div>
            <div class="stat-subtext">0 pending review</div>
        </div>
        <div class="stat-icon green">
            <i class="fas fa-truck"></i>
        </div>
    </a>

    <a href="<?php echo baseUrl('/inventory/index.php'); ?>" class="stat-card">
        <div class="stat-info">
            <h3>Total Inventory</h3>
            <div class="stat-value"><?php echo formatWeight($stats['total_inventory']); ?></div>
            <div class="stat-subtext">0 low stock alerts</div>
        </div>
        <div class="stat-icon orange">
            <i class="fas fa-box"></i>
        </div>
    </a>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <h2 class="section-title">Quick Actions</h2>
    <p class="section-subtitle">Frequently used management functions</p>
    
    <div class="action-list">
        <a href="<?php echo baseUrl('/farmers/index.php'); ?>" class="action-btn">
            <i class="fas fa-plus"></i>
            <span>Register New Farmer</span>
        </a>
        <a href="<?php echo baseUrl('/reports/index.php'); ?>" class="action-btn">
            <i class="fas fa-file-alt"></i>
            <span>View Reports</span>
        </a>
        <a href="<?php echo baseUrl('/inventory/index.php'); ?>" class="action-btn">
            <i class="fas fa-eye"></i>
            <span>Inventory Overview</span>
        </a>
    </div>
</div>

<!-- Recent Activity -->
<div class="activity-section">
    <h2 class="section-title">Recent Activity</h2>
    <p class="section-subtitle">Latest updates from your coffee factory</p>
    
    <div class="activity-list">
        <?php if (!empty($recentActivity)): ?>
            <?php foreach ($recentActivity as $index => $activity): ?>
                <?php if ($index === 5): ?>
                    <div id="extraRecentActivity" style="display: none;">
                <?php endif; ?>

                <div class="activity-item">
                    <div class="activity-dot dot-<?php 
                        echo $activity['status_color'] === 'success' ? 'green' : 
                            ($activity['status_color'] === 'info' ? 'blue' : 'purple'); 
                    ?>"></div>
                    <div class="activity-content">
                        <h4><?php echo e($activity['title']); ?></h4>
                        <p><?php echo e($activity['description']); ?></p>
                        <small style="color: var(--text-muted); font-size: var(--font-size-xs);">
                            <?php echo date('M d, Y H:i', strtotime($activity['activity_date'])); ?>
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (count($recentActivity) > 5): ?>
                    </div> <!-- Close extraRecentActivity -->
                <button id="viewMoreBtn" onclick="toggleRecentActivity()" class="btn btn-secondary" style="margin-top: 16px; width: 100%;">
                    View More
                </button>
            <?php endif; ?>

        <?php else: ?>
            <div class="activity-item">
                <div class="activity-content">
                    <p style="color: var(--text-muted); text-align: center;">No recent activity to display</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleRecentActivity() {
    var extraContent = document.getElementById("extraRecentActivity");
    var btn = document.getElementById("viewMoreBtn");
    
    if (extraContent.style.display === "none") {
        extraContent.style.display = "block";
        btn.textContent = "Show Less";
    } else {
        extraContent.style.display = "none";
        btn.textContent = "View More";
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
