<?php
require_once __DIR__ . '/../includes/functions.php';
// Role check
if (!isLoggedIn()) {
    http_response_code(403);
    die('Unauthorized');
}

$currentUserRole = getCurrentUserRole();
$currentUserId = getCurrentUserId();

// Get parameters
$format = $_GET['format'] ?? 'json'; // csv or json
$timeframe = $_GET['timeframe'] ?? 'today';
$customStart = $_GET['start_date'] ?? '';
$customEnd = $_GET['end_date'] ?? '';
$farmerIdParam = $_GET['farmer_id'] ?? null;
$filterGrade = $_GET['grade'] ?? '';
$filterStatus = $_GET['status'] ?? '';

// Enforce Data Access Logic
// ... (existing farmer check logic) ...

// (Adding this block to match the original file's logic flow but with new variables)
$targetFarmerId = null;

if ($currentUserRole === 'farmer') {
    // Farmers can ONLY export their own data
    $user = queryOne("SELECT phone FROM users WHERE id = ?", [$currentUserId]);
    $farmer = queryOne("SELECT id FROM farmers WHERE phone = ?", [$user['phone']]);
    
    if (!$farmer) {
        die("Farmer profile not found.");
    }
    $targetFarmerId = $farmer['id'];
} else {
    // Managers/Clerks can export all or specific farmer
    if ($farmerIdParam) {
        $targetFarmerId = $farmerIdParam;
    }
}

// Calculate Date Range
// ... (existing switch case logic for timeframe) ...
$startDate = date('Y-m-d');
$endDate = date('Y-m-d');

switch ($timeframe) {
    case 'today':
        $startDate = date('Y-m-d 00:00:00');
        $endDate = date('Y-m-d 23:59:59');
        break;
    case 'week':
        $startDate = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $endDate = date('Y-m-d 23:59:59');
        break;
    case 'month':
        $startDate = date('Y-m-01 00:00:00');
        $endDate = date('Y-m-t 23:59:59');
        break;
    case 'year':
        $startDate = date('Y-01-01 00:00:00');
        $endDate = date('Y-12-31 23:59:59');
        break;
    case 'custom':
        if (!empty($customStart) && !empty($customEnd)) {
            $startDate = date('Y-m-d 00:00:00', strtotime($customStart));
            $endDate = date('Y-m-d 23:59:59', strtotime($customEnd));
        }
        break;
    case 'all':
        $startDate = '2000-01-01 00:00:00';
        $endDate = date('Y-12-31 23:59:59');
        break;
}

// Query Data
$sql = "
    SELECT 
        d.delivery_date,
        f.farmer_id,
        f.name as farmer_name,
        d.quantity,
        d.grade,
        d.status,
        d.batch_number,
        d.moisture_content,
        d.quality_score
    FROM deliveries d
    JOIN farmers f ON d.farmer_id = f.id
    WHERE d.delivery_date BETWEEN ? AND ?
";

$params = [$startDate, $endDate];

if ($targetFarmerId) {
    $sql .= " AND d.farmer_id = ?";
    $params[] = $targetFarmerId;
}

if ($filterGrade) {
    $sql .= " AND d.grade = ?";
    $params[] = $filterGrade;
}

if ($filterStatus) {
    $sql .= " AND d.status = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY d.delivery_date DESC";

try {
    $data = queryAll($sql, $params);
} catch (Exception $e) {
    if ($format === 'json') {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    } else {
        die("Error fetching data");
    }
    exit;
}

// Handle Export
if ($format === 'csv') {
    // Determine filename
    $filename = 'delivery_report_' . date('Y-m-d', strtotime($startDate)) . '_to_' . date('Y-m-d', strtotime($endDate)) . '.csv';
    
    // Set Headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Header Row
    fputcsv($output, ['Date', 'Farmer ID', 'Farmer Name', 'Quantity (kg)', 'Grade', 'Status']);
    
    // Data Rows
    foreach ($data as $row) {
        fputcsv($output, [
            $row['delivery_date'],
            $row['farmer_id'],
            $row['farmer_name'],
            $row['quantity'],
            $row['grade'],
            $row['status']
        ]);
    }
    
    fclose($output);
    exit;
} elseif ($format === 'json') {
    header('Content-Type: application/json');
    echo json_encode(['data' => $data, 'meta' => ['start' => $startDate, 'end' => $endDate]]);
    exit;
}
?>
