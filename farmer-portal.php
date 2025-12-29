<?php
require_once __DIR__ . '/includes/functions.php';
requireRole('farmer');

$userId = getCurrentUserId();
$userEmail = $_SESSION['user_email'];

// Get the user's phone to find the farmer record
$user = queryOne("SELECT phone, name FROM users WHERE id = ?", [$userId]);
$userPhone = $user['phone'];

// Find the farmer record by phone
$farmer = queryOne("SELECT * FROM farmers WHERE phone = ?", [$userPhone]);

if (!$farmer) {
    // If no farmer record found, we show a simplified welcome or error
    include __DIR__ . '/includes/header.php';
    echo '<div class="profile-card" style="text-align: center; padding: 40px;">
            <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #8D6E63; margin-bottom: 20px;"></i>
            <h3>Farmer Record Not Found</h3>
            <p>We couldn\'t find a farmer profile linked to your account phone number (' . e($userPhone) . '). Please contact the factory management.</p>
          </div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$farmerId = $farmer['id'];

// Calculate Statistics
// 1. Total Deliveries
$stats = queryOne("SELECT COUNT(*) as total_count, SUM(quantity) as total_weight FROM deliveries WHERE farmer_id = ?", [$farmerId]);
$totalDeliveries = $stats['total_count'] ?? 0;
$totalWeight = $stats['total_weight'] ?? 0;

// 2. Deliveries this month
$thisMonthDeliveries = queryOne("
    SELECT COUNT(*) as count 
    FROM deliveries 
    WHERE farmer_id = ? AND MONTH(delivery_date) = MONTH(CURRENT_DATE()) AND YEAR(delivery_date) = YEAR(CURRENT_DATE())
", [$farmerId])['count'] ?? 0;

// 3. Get Recent Deliveries
$recentDeliveries = queryAll("
    SELECT * FROM deliveries 
    WHERE farmer_id = ? 
    ORDER BY delivery_date DESC 
    LIMIT 10
", [$farmerId]);

include __DIR__ . '/includes/header.php';
?>

<div class="farmer-portal-wrapper">
    <!-- Welcome Header -->
    <div class="portal-hero">
        <h1>Welcome, <?php echo e($farmer['name']); ?>!</h1>
        <p>Track your coffee deliveries with Meru Coffee Cooperative</p>
    </div>

    <div class="portal-grid-row">
        <!-- Left: My Profile Card -->
        <div class="portal-col-left">
            <div class="portal-card profile-card">
                <div class="card-header-simple">
                    <i class="far fa-user"></i>
                    <h3>My Profile</h3>
                </div>
                
                <div class="profile-main-info">
                    <div class="farmer-avatar-large">
                        <?php 
                            $names = explode(' ', $farmer['name']);
                            $initials = (isset($names[0]) ? substr($names[0], 0, 1) : '') . (isset($names[1]) ? substr($names[1], 0, 1) : '');
                            echo strtoupper($initials);
                         ?>
                    </div>
                    <div class="profile-text-info">
                        <h4><?php echo e($farmer['name']); ?></h4>
                        <div class="id-row">
                            <span>Farmer ID: <?php echo e($farmer['farmer_id']); ?></span>
                        </div>
                        <span class="status-pill active-status"><?php echo e($farmer['status']); ?></span>
                    </div>
                </div>

                <div class="profile-details-list">
                    <div class="p-detail-item">
                        <i class="fas fa-phone-alt"></i>
                        <span><?php echo e($farmer['phone']); ?></span>
                    </div>
                    <div class="p-detail-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo e($farmer['location']); ?></span>
                    </div>
                    <div class="p-detail-item">
                        <i class="far fa-calendar-alt"></i>
                        <span>Registered: <?php echo date('n/j/Y', strtotime($farmer['registration_date'])); ?></span>
                    </div>
                </div>

                <button class="btn-portal-white" onclick="openProfileSettings(event)">
                    <i class="far fa-eye"></i> Edit Profile
                </button>
            </div>
        </div>

        <!-- Right: My Statistics -->
        <div class="portal-col-right">
            <div class="portal-card stats-overview-card">
                <div class="card-header-simple">
                    <h3>My Statistics</h3>
                    <p>Your delivery performance overview</p>
                </div>

                <div class="portal-stats-boxes">
                    <div class="p-stat-box">
                        <div class="p-stat-icon blue">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="p-stat-info">
                            <div class="p-stat-value"><?php echo number_format($totalDeliveries); ?></div>
                            <div class="p-stat-label">Total Deliveries</div>
                        </div>
                    </div>

                    <div class="p-stat-box">
                        <div class="p-stat-icon green">
                            <i class="fas fa-cubes"></i>
                        </div>
                        <div class="p-stat-info">
                            <div class="p-stat-value"><?php echo number_format($totalWeight); ?> kg</div>
                            <div class="p-stat-label">Total Quantity</div>
                        </div>
                    </div>

                    <div class="p-stat-box">
                        <div class="p-stat-icon purple">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="p-stat-info">
                            <div class="p-stat-value"><?php echo $thisMonthDeliveries; ?> deliveries</div>
                            <div class="p-stat-label">This Month</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom: Delivery History -->
    <div class="portal-card full-width-card history-card">
        <div class="card-header-flex">
            <div class="header-text">
                <h3>My Delivery History</h3>
                <p>Track all your coffee deliveries</p>
            </div>
            <div class="header-actions" style="display: flex; gap: 10px; align-items: center;">
                <select id="exportTimeframe" class="form-select" style="height: 38px; font-size: 13px; padding: 0 30px 0 10px; width: auto;">
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month" selected>This Month</option>
                    <option value="year">This Year</option>
                    <option value="all">All Time</option>
                </select>
                <button class="btn-download-report" onclick="exportFarmerCSV()">
                    <i class="fas fa-file-csv"></i> CSV
                </button>
                <button class="btn-download-report" onclick="exportFarmerPDF()">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
            </div>
        </div>

        <div class="history-content">
            <?php if (empty($recentDeliveries)): ?>
                <div class="empty-state">
                    <div class="empty-icon-box">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h4>No deliveries yet</h4>
                    <p>Your delivery history will appear here once you start delivering coffee.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="portal-table">
                        <thead>
                            <tr>
                                <th>Batch No</th>
                                <th>Weight</th>
                                <th>Grade</th>
                                <th>Moisture</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Quality</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentDeliveries as $d): ?>
                                <tr>
                                    <td><strong><?php echo e($d['batch_number']); ?></strong></td>
                                    <td><?php echo number_format($d['quantity'], 2); ?> kg</td>
                                    <td><span class="badge-grade"><?php echo e($d['grade']); ?></span></td>
                                    <td><?php echo number_format($d['moisture_content'], 1); ?>%</td>
                                    <td><?php echo date('d/m/Y', strtotime($d['delivery_date'])); ?></td>
                                    <td><span class="badge <?php echo getDeliveryStatusColor($d['status']); ?>"><?php echo ucfirst($d['status']); ?></span></td>
                                    <td><?php echo $d['quality_score'] ? $d['quality_score'] . '/100' : 'Pending'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Farmer Portal Specific CSS - Added to main style.css eventually */
.farmer-portal-wrapper {
    padding-bottom: 40px;
}

.portal-hero {
    background: #F2EEED;
    border-radius: 16px;
    padding: 48px 24px;
    text-align: center;
    margin-bottom: 32px;
}

.portal-hero h1 {
    font-size: 32px;
    color: #3E2723;
    margin-bottom: 8px;
    font-weight: 700;
}

.portal-hero p {
    color: #8D6E63;
    font-size: 16px;
}

.portal-grid-row {
    display: flex;
    gap: 24px;
    margin-bottom: 24px;
}

.portal-col-left {
    flex: 0 0 340px;
}

.portal-col-right {
    flex: 1;
}

.portal-card {
    background: #FFFFFF;
    border-radius: 12px;
    padding: 24px;
    border: 1px solid #E0E0E0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.02);
}

.card-header-simple h3 {
    font-size: 18px;
    color: #3E2723;
    margin-bottom: 4px;
    font-weight: 700;
}

.card-header-simple i {
    color: #3E2723;
    margin-right: 8px;
}

.card-header-simple p {
    font-size: 14px;
    color: #8D6E63;
}

/* Profile Styles */
.profile-main-info {
    display: flex;
    align-items: center;
    gap: 16px;
    margin: 24px 0;
}

.farmer-avatar-large {
    width: 64px;
    height: 64px;
    background: #8D6E63;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 700;
}

.profile-text-info h4 {
    font-size: 18px;
    color: #3E2723;
    margin-bottom: 4px;
}

.profile-text-info .id-row {
    font-size: 13px;
    color: #8D6E63;
    margin-bottom: 6px;
}

.status-pill {
    padding: 2px 10px;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}

.status-pill.active-status {
    background: #6D4C41;
    color: white;
}

.profile-details-list {
    border-top: 1px solid #F5F5F5;
    padding-top: 16px;
    margin-bottom: 24px;
}

.p-detail-item {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
    color: #5D4037;
    font-size: 14px;
}

.p-detail-item i {
    width: 16px;
    color: #8D6E63;
}

.btn-portal-white {
    width: 100%;
    padding: 10px;
    border: 1px solid #F2EEEB;
    background: #F7F3F0;
    border-radius: 8px;
    color: #3E2723;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

/* Stats Styles */
.portal-stats-boxes {
    display: flex;
    gap: 16px;
    margin-top: 24px;
}

.p-stat-box {
    flex: 1;
    background: #FAF9F7;
    padding: 24px;
    border-radius: 12px;
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.p-stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.p-stat-icon.blue { background: #E3F2FD; color: #2196F3; }
.p-stat-icon.green { background: #E8F5E9; color: #4CAF50; }
.p-stat-icon.purple { background: #F3E5F5; color: #9C27B0; }

.p-stat-value {
    font-size: 22px;
    font-weight: 800;
    color: #3E2723;
    line-height: 1.2;
}

.p-stat-label {
    font-size: 13px;
    color: #8D6E63;
}

/* History Card */
.card-header-flex {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
}

.header-text h3 {
    font-size: 20px;
    color: #3E2723;
    margin-bottom: 4px;
    font-weight: 800;
}

.header-text p {
    color: #8D6E63;
    font-size: 14px;
}

.btn-download-report {
    padding: 8px 16px;
    background: #F9F7F5;
    border: 1.5px solid #E0D5CE;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 700;
    color: #3E2723;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.empty-state {
    text-align: center;
    padding: 60px 0;
}

.empty-icon-box {
    width: 64px;
    height: 64px;
    background: #FAF9F7;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 32px;
    color: #8D6E63;
}

.empty-state h4 {
    font-size: 18px;
    color: #3E2723;
    margin-bottom: 8px;
}

.empty-state p {
    color: #8D6E63;
    max-width: 300px;
    margin: 0 auto;
}

.portal-table {
    width: 100%;
    border-collapse: collapse;
}

.portal-table th {
    text-align: left;
    padding: 12px 16px;
    background: #FCFBFA;
    border-bottom: 1px solid #F0EAE6;
    color: #8D6E63;
    font-size: 13px;
    font-weight: 600;
}

.portal-table td {
    padding: 16px;
    border-bottom: 1px solid #FDFBF9;
    font-size: 14px;
    color: #3E2723;
}

.badge-grade {
    background: #EFEBE9;
    color: #5D4037;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 700;
    font-size: 12px;
}

@media (max-width: 1024px) {
    .portal-grid-row { flex-direction: column; }
    .portal-col-left { flex: 1; }
    .portal-stats-boxes { flex-direction: column; }
}
</style>

<!-- Export Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>

<script>
    function getFarmerExportParams() {
        const timeframe = document.getElementById('exportTimeframe').value;
        return `timeframe=${timeframe}`;
    }

    function exportFarmerCSV() {
        const params = getFarmerExportParams();
        // The backend handles the farmer_id security check automatically
        window.location.href = `reports/export_handler.php?format=csv&${params}`;
    }

    async function exportFarmerPDF() {
        const params = getFarmerExportParams();
        try {
            const btn = event.currentTarget;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            
            // Path relative to farmer-portal.php
            const response = await fetch(`reports/export_handler.php?format=json&${params}`);
            if (!response.ok) throw new Error("Server error");
            
            const result = await response.json();
            
            if (!result.data || result.data.length === 0) {
                alert("No deliveries found for the selected time range.");
                btn.innerHTML = originalText;
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const farmerName = "<?php echo e($farmer['name']); ?>";

            // Add Header
            doc.setFontSize(20);
            doc.setTextColor(62, 39, 35); // Brownish
            doc.text("My Delivery Report", 14, 20);
            
            doc.setFontSize(10);
            doc.setTextColor(141, 110, 99);
            doc.text(`Farmer: ${farmerName}`, 14, 28);
            doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 33);

            // Define Table Columns
            const tableColumn = ["Date", "Batch No", "Quantity (kg)", "Grade", "Moisture", "Qual.", "Status"];
            const tableRows = [];

            result.data.forEach(row => {
                const deliveryData = [
                    row.delivery_date.split(' ')[0], 
                    row.batch_number,
                    parseFloat(row.quantity).toFixed(2),
                    row.grade,
                    row.moisture_content + '%',
                    row.quality_score ? row.quality_score : '-',
                    row.status
                ];
                tableRows.push(deliveryData);
            });

            // Calculate Totals
            const totalQty = result.data.reduce((sum, row) => sum + parseFloat(row.quantity), 0);

            doc.autoTable({
                head: [tableColumn],
                body: tableRows,
                startY: 40,
                theme: 'grid',
                styles: { fontSize: 9, cellPadding: 3 },
                headStyles: { fillColor: [109, 76, 65], textColor: 255 }, // Brown header
                alternateRowStyles: { fillColor: [250, 249, 247] },
                didDrawPage: function (data) {
                    doc.setFontSize(8);
                    doc.text('Meru Coffee Cooperative - Farmer Portal', data.settings.margin.left, doc.internal.pageSize.height - 10);
                }
            });

            const finalY = doc.lastAutoTable.finalY || 40;
            doc.setFontSize(12);
            doc.setTextColor(0);
            doc.text(`Total Quantity in Period: ${totalQty.toFixed(2)} kg`, 14, finalY + 10);

            doc.save(`my_deliveries_${new Date().toISOString().slice(0,10)}.pdf`);
            btn.innerHTML = originalText;

        } catch (error) {
            console.error(error);
            alert("Error generating PDF: " + error.message);
            const icons = document.querySelectorAll('.fa-spinner');
            if(icons.length > 0) icons[0].parentElement.innerHTML = '<i class="fas fa-file-pdf"></i> PDF';
        }
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
