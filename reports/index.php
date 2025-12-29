<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['manager', 'clerk']);

// Get Timeframe from URL or default to 'month'
$timeframe = $_GET['timeframe'] ?? 'month';
$customStart = $_GET['start_date'] ?? '';
$customEnd = $_GET['end_date'] ?? '';
$filterGrade = $_GET['grade'] ?? '';
$filterStatus = $_GET['status'] ?? '';

// Calculate Date Range
$currentMonthStart = date('Y-m-01');
$currentMonthEnd = date('Y-m-t');
$periodLabel = "This Month";

switch ($timeframe) {
    case 'today':
        $currentMonthStart = date('Y-m-d 00:00:00');
        $currentMonthEnd = date('Y-m-d 23:59:59');
        $periodLabel = "Today";
        break;
    case 'week':
        $currentMonthStart = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $currentMonthEnd = date('Y-m-d 23:59:59');
        $periodLabel = "This Week";
        break;
    case 'month':
        $currentMonthStart = date('Y-m-01 00:00:00');
        $currentMonthEnd = date('Y-m-t 23:59:59');
        $periodLabel = "This Month";
        break;
    case 'year':
        $currentMonthStart = date('Y-01-01 00:00:00');
        $currentMonthEnd = date('Y-12-31 23:59:59');
        $periodLabel = "This Year";
        break;
    case 'custom':
        if (!empty($customStart)) {
            $currentMonthStart = date('Y-m-d 00:00:00', strtotime($customStart));
            $periodLabel = "Custom Range";
        }
        if (!empty($customEnd)) {
            $currentMonthEnd = date('Y-m-d 23:59:59', strtotime($customEnd));
        }
        break;
}

// "Last Period" logic is tricky with dynamic ranges, so we'll simplify:
// For trends, we'll just compare to the immediate previous equivalent period
$duration = strtotime($currentMonthEnd) - strtotime($currentMonthStart);
$lastMonthStart = date('Y-m-d H:i:s', strtotime($currentMonthStart) - $duration - 1);
$lastMonthEnd = date('Y-m-d H:i:s', strtotime($currentMonthStart) - 1);

// Grade Distribution (for Doughnut Chart)
$gradeDist = queryAll("
    SELECT grade, COUNT(*) as count, SUM(quantity) as qty 
    FROM deliveries 
    WHERE delivery_date BETWEEN ? AND ? 
    GROUP BY grade
", [$currentMonthStart, $currentMonthEnd]);

// Top 5 Farmers (for Horizontal Bar Chart)
$topFarmers = queryAll("
    SELECT f.name, SUM(d.quantity) as total_qty 
    FROM deliveries d
    JOIN farmers f ON d.farmer_id = f.id
    WHERE d.delivery_date BETWEEN ? AND ?
    GROUP BY d.farmer_id
    ORDER BY total_qty DESC
    LIMIT 5
", [$currentMonthStart, $currentMonthEnd]);

// Mock Data for "Monthly Performance Trends" (Past 6 months)
$months = [];
$deliveriesTrend = [];
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i months"));
    $monthName = date('F', strtotime($date));
    // ... rest of loop logic is unchanged ...
    $months[] = $monthName;
    
    $start = date('Y-m-01', strtotime("-$i months"));
    $end = date('Y-m-t', strtotime("-$i months"));
    $qty = queryOne("SELECT COALESCE(SUM(quantity), 0) as qty FROM deliveries WHERE delivery_date BETWEEN ? AND ?", [$start, $end])['qty'];
    
    $deliveriesTrend[$monthName] = $qty;
}

// Current Stats
$totalDeliveries = queryOne("SELECT COUNT(*) as count FROM deliveries WHERE delivery_date BETWEEN ? AND ?", [$currentMonthStart, $currentMonthEnd])['count'];
$totalQuantity = queryOne("SELECT COALESCE(SUM(quantity), 0) as qty FROM deliveries WHERE delivery_date BETWEEN ? AND ?", [$currentMonthStart, $currentMonthEnd])['qty'];
$activeFarmers = queryOne("SELECT COUNT(*) as count FROM farmers WHERE status = 'active'")['count'];

// Last Month Stats (for trends)
$lastTotalDeliveries = queryOne("SELECT COUNT(*) as count FROM deliveries WHERE delivery_date BETWEEN ? AND ?", [$lastMonthStart, $lastMonthEnd])['count'];
$lastTotalQuantity = queryOne("SELECT COALESCE(SUM(quantity), 0) as qty FROM deliveries WHERE delivery_date BETWEEN ? AND ?", [$lastMonthStart, $lastMonthEnd])['qty'];

// Calculate Trends
$delTrend = $lastTotalDeliveries > 0 ? (($totalDeliveries - $lastTotalDeliveries) / $lastTotalDeliveries) * 100 : 0;
$qtyTrend = $lastTotalQuantity > 0 ? (($totalQuantity - $lastTotalQuantity) / $lastTotalQuantity) * 100 : 0;

// Quality and Moisture Stats
$avgQuality = $extraStats['avg_quality'] ?? 0;

// Recent Deliveries Table
$recentDeliveries = queryAll("
    SELECT d.*, f.name as farmer_name 
    FROM deliveries d 
    JOIN farmers f ON d.farmer_id = f.id 
    WHERE d.delivery_date BETWEEN ? AND ? 
    ORDER BY d.delivery_date DESC 
    LIMIT 10
", [$currentMonthStart, $currentMonthEnd]);

$pageTitle = "Reports & Analytics";
include __DIR__ . '/../includes/header.php';
?>

<style>
    .analytics-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 24px; }
    .insight-card { background: white; padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); display: flex; flex-direction: column; }
    .insight-card h3 { font-size: 14px; font-weight: 700; color: #5D4037; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8; }
    .chart-box { position: relative; height: 180px; width: 100%; }
    .trend-full-width { grid-column: span 2; }
    .trend-full-width .chart-box { height: 220px; }

    @media (max-width: 1200px) {
        .analytics-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .analytics-grid { grid-template-columns: 1fr; }
        .inventory-stats-row { grid-template-columns: repeat(2, 1fr) !important; }
    }
    
    @media (min-width: 1400px) {
        .inventory-stats-row { grid-template-columns: repeat(4, 1fr) !important; }
    }
</style>

<div class="welcome-section">
    <div>
        <h1 class="welcome-title">Reports & Analytics</h1>
        <p class="welcome-subtitle">Generate comprehensive reports and view performance analytics</p>
    </div>
    <div class="date-display flex gap-2" id="exportButtons" data-html2canvas-ignore="true">
        <button class="btn btn-light" style="background: white; border: 1px solid var(--border-color); height: 50px;" onclick="exportCSV()">
            <i class="fas fa-file-csv"></i> Export CSV
        </button>
        <button class="btn btn-primary" style="background: var(--primary-dark); height: 50px;" onclick="exportPDF()">
            <i class="fas fa-file-pdf"></i> Export PDF
        </button>
    </div>
</div>

<!-- Report Configuration -->
<div class="report-config-card">
    <div class="report-config-header">
        <i class="far fa-file-alt"></i> Report Configuration
    </div>
    <div class="form-row">
        <div class="form-col">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Time Frame</label>
                <select id="timeFrame" class="form-select no-icon" onchange="toggleCustomDates()">
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month" selected>This Month</option>
                    <option value="year">This Year</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>
        </div>
        <div class="form-col custom-date-group" style="display: none;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Start Date</label>
                <input type="date" id="startDate" class="form-control-styled">
            </div>
        </div>
        <div class="form-col custom-date-group" style="display: none;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">End Date</label>
                <input type="date" id="endDate" class="form-control-styled">
            </div>
        </div>
        <div class="form-col">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Coffee Grade</label>
                <select id="filterGrade" class="form-select no-icon">
                    <option value="">All Grades</option>
                    <option value="AA" <?php echo $filterGrade === 'AA' ? 'selected' : ''; ?>>AA</option>
                    <option value="AB" <?php echo $filterGrade === 'AB' ? 'selected' : ''; ?>>AB</option>
                    <option value="PB" <?php echo $filterGrade === 'PB' ? 'selected' : ''; ?>>PB</option>
                    <option value="C" <?php echo $filterGrade === 'C' ? 'selected' : ''; ?>>C</option>
                </select>
            </div>
        </div>
        <div class="form-col">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Status</label>
                <select id="filterStatus" class="form-select no-icon">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="quality_check" <?php echo $filterStatus === 'quality_check' ? 'selected' : ''; ?>>Quality Check</option>
                    <option value="approved" <?php echo $filterStatus === 'approved' ? 'selected' : ''; ?>>Approved</option>
                </select>
            </div>
        </div>
    </div>
    <div class="form-row" style="margin-top: 15px;">
        <div class="form-col">
            <button class="btn btn-primary" onclick="generateReportPreview()">
                <i class="fas fa-sync-alt"></i> Update Preview
            </button>
        </div>
    </div>
</div>

<!-- Stats Grid -->
<div class="inventory-stats-row" style="margin-bottom: 24px;">
    <div class="stat-card">
        <div class="stat-info">
            <h3>Total Deliveries</h3>
            <div class="stat-value"><?php echo number_format($totalDeliveries); ?></div>
            <div class="stat-subtext <?php echo $delTrend >= 0 ? 'text-success' : 'text-danger'; ?>">
                <i class="fas fa-arrow-<?php echo $delTrend >= 0 ? 'up' : 'down'; ?>"></i>
                <?php echo abs(round($delTrend)); ?>% vs last period
            </div>
        </div>
        <div class="stat-icon blue">
            <i class="fas fa-truck"></i>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-info">
            <h3>Total Quantity</h3>
            <div class="stat-value"><?php echo formatWeight($totalQuantity); ?></div>
            <div class="stat-subtext <?php echo $qtyTrend >= 0 ? 'text-success' : 'text-danger'; ?>">
                <i class="fas fa-arrow-<?php echo $qtyTrend >= 0 ? 'up' : 'down'; ?>"></i>
                <?php echo abs(round($qtyTrend)); ?>% vs last period
            </div>
        </div>
        <div class="stat-icon orange">
            <i class="fas fa-weight"></i>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-info">
            <h3>Active Farmers</h3>
            <div class="stat-value"><?php echo number_format($activeFarmers); ?></div>
            <div class="stat-subtext">Contributing currently</div>
        </div>
        <div class="stat-icon green">
            <i class="fas fa-users"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <h3>Avg. Quality</h3>
            <div class="stat-value"><?php echo round($avgQuality); ?>/100</div>
            <div class="stat-subtext">Batch score</div>
        </div>
        <div class="stat-icon yellow">
            <i class="fas fa-star"></i>
        </div>
    </div>
</div>

<div class="analytics-grid">
    <div class="insight-card trend-full-width">
        <h3>Delivery Trend</h3>
        <div class="chart-box">
            <canvas id="trendChart"></canvas>
        </div>
    </div>
    
    <div class="insight-card">
        <h3>Grade Mix</h3>
        <div class="chart-box">
            <canvas id="gradeChart"></canvas>
        </div>
    </div>
    
    <div class="insight-card">
        <h3>Top Contributors</h3>
        <div class="chart-box">
            <canvas id="farmersChart"></canvas>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- Export Functionality Scripts               -->
<!-- ========================================== -->

<!-- Export Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Initialize date inputs and dropdown state from URL params
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const timeframe = urlParams.get('timeframe') || 'month';
        const start = urlParams.get('start_date');
        const end = urlParams.get('end_date');
        const grade = urlParams.get('grade');
        const status = urlParams.get('status');

        const timeFrameSelect = document.getElementById('timeFrame');
        if(timeFrameSelect) {
            timeFrameSelect.value = timeframe;
            toggleCustomDates(); 
        }

        const today = new Date().toISOString().split('T')[0];
        document.getElementById('startDate').value = start || today;
        document.getElementById('endDate').value = end || today;
        
        if (grade) document.getElementById('filterGrade').value = grade;
        if (status) document.getElementById('filterStatus').value = status;

        initCharts();
    });

    function initCharts() {
        // Trend Chart
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($deliveriesTrend)); ?>,
                datasets: [{
                    label: 'Coffee Quantity (kg)',
                    data: <?php echo json_encode(array_values($deliveriesTrend)); ?>,
                    borderColor: '#4E342E',
                    backgroundColor: 'rgba(78, 52, 46, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // Grade Chart
        new Chart(document.getElementById('gradeChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($gradeDist, 'grade')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($gradeDist, 'qty')); ?>,
                    backgroundColor: ['#6D4C41', '#8D6E63', '#A1887F', '#BCAAA4', '#D7CCC8']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
            }
        });

        // Top Farmers Chart
        new Chart(document.getElementById('farmersChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($topFarmers, 'name')); ?>,
                datasets: [{
                    label: 'Total kg',
                    data: <?php echo json_encode(array_column($topFarmers, 'total_qty')); ?>,
                    backgroundColor: '#1B5E20'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    }

    function toggleCustomDates() {
        const timeFrame = document.getElementById('timeFrame').value;
        const customGroups = document.querySelectorAll('.custom-date-group');
        customGroups.forEach(group => {
            group.style.display = timeFrame === 'custom' ? 'block' : 'none';
        });
    }

    function generateReportPreview() {
        const params = getReportParams();
        window.location.href = `index.php?${params}`;
    }

    function getReportParams() {
        const timeFrame = document.getElementById('timeFrame').value;
        const grade = document.getElementById('filterGrade').value;
        const status = document.getElementById('filterStatus').value;
        
        let params = `timeframe=${timeFrame}`;
        if (grade) params += `&grade=${grade}`;
        if (status) params += `&status=${status}`;
        
        if (timeFrame === 'custom') {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            if (start) params += `&start_date=${start}`;
            if (end) params += `&end_date=${end}`;
        }
        return params;
    }

    function getExportParams() {
        return getReportParams();
    }

    function exportCSV() {
        const params = getExportParams();
        window.location.href = `export_handler.php?format=csv&${params}`;
    }

    async function exportPDF() {
        const params = getExportParams();
        try {
            // Show loading state could be nice here
            const btn = event.currentTarget;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            
            // Fetch data
            const response = await fetch(`export_handler.php?format=json&${params}`);
            if (!response.ok) throw new Error("Server error");
            
            const result = await response.json();
            
            if (!result.data || result.data.length === 0) {
                alert("No data found for the selected time range.");
                btn.innerHTML = originalText;
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Add Header
            doc.setFontSize(22);
            doc.setTextColor(44, 62, 80); // Dark blue-grey
            doc.text("Coffee Factory Delivery Report", 14, 20);
            
            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 28);
            
            if (result.meta) {
                 doc.text(`Period: ${result.meta.start.split(' ')[0]} to ${result.meta.end.split(' ')[0]}`, 14, 34);
            }

            // Define Table Columns
            const tableColumn = ["Date", "Farmer Name", "ID", "Qty (kg)", "Grade", "Status"];
            const tableRows = [];

            result.data.forEach(row => {
                const deliveryData = [
                    row.delivery_date.split(' ')[0], // Just date part
                    row.farmer_name,
                    row.farmer_id,
                    parseFloat(row.quantity).toFixed(2),
                    row.grade,
                    row.status
                ];
                tableRows.push(deliveryData);
            });

            // Calculate Total Quantity
            const totalQty = result.data.reduce((sum, row) => sum + parseFloat(row.quantity), 0);

            doc.autoTable({
                head: [tableColumn],
                body: tableRows,
                startY: 40,
                theme: 'grid',
                styles: { fontSize: 9, cellPadding: 3 },
                headStyles: { fillColor: [41, 128, 185], textColor: 255 }, // Blue header
                alternateRowStyles: { fillColor: [245, 245, 245] },
                didDrawPage: function (data) {
                    // Footer
                    doc.setFontSize(8);
                    doc.text('Coffee Factory Management System', data.settings.margin.left, doc.internal.pageSize.height - 10);
                }
            });

            // Add Summary at bottom
            const finalY = doc.lastAutoTable.finalY || 40;
            doc.setFontSize(12);
            doc.setTextColor(0);
            doc.text(`Total Quantity: ${totalQty.toFixed(2)} kg`, 14, finalY + 10);
            doc.text(`Total Records: ${result.data.length}`, 14, finalY + 16);

            doc.save(`kagaene_factory_report_${new Date().toISOString().slice(0,10)}.pdf`);

            btn.innerHTML = originalText;

        } catch (error) {
            console.error(error);
            alert("Error generating PDF: " + error.message);
            // Restore button text if it was changed
            const icons = document.querySelectorAll('.fa-spinner');
            if(icons.length > 0) icons[0].parentElement.innerHTML = '<i class="fas fa-file-pdf"></i> Export PDF'; 
        }
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
