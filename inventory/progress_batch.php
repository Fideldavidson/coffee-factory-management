<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['manager', 'clerk']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $batch_id = sanitize($_POST['batch_id']);
    $new_status = sanitize($_POST['new_status']);
    
    // Validate inputs
    if (empty($batch_id) || empty($new_status)) {
        setFlashMessage('danger', 'Invalid request data.');
        redirect('/inventory/index.php');
    }

    // Get current batch data to verify
    $currentBatch = queryOne("SELECT * FROM inventory_batches WHERE id = ?", [$batch_id]);
    if (!$currentBatch) {
        setFlashMessage('danger', 'Batch not found.');
        redirect('/inventory/index.php');
    }

    // Prepare update parameters
    $quantity = $currentBatch['quantity']; 
    $moisture = $currentBatch['moisture_content']; 
    $location = $currentBatch['storage_location'];
    
    // If quantity is being adjusted
    if (isset($_POST['adjusted_quantity']) && is_numeric($_POST['adjusted_quantity'])) {
        $quantity = floatval($_POST['adjusted_quantity']);
    }

    // If moisture is updated
    if (isset($_POST['new_moisture']) && is_numeric($_POST['new_moisture'])) {
        $moisture = floatval($_POST['new_moisture']);
    }

    // If location is updated
    if (isset($_POST['storage_location'])) {
        $location = sanitize($_POST['storage_location']);
    }

    // Determine Logic based on Status transition
    $processed_date = $currentBatch['processed_date'];
    
    if ($new_status === 'processing' && $currentBatch['status'] === 'received') {
        // Start processing
    } elseif ($new_status === 'dried' || $new_status === 'milled') {
        $processed_date = date('Y-m-d H:i:s');
    }

    $sql = "UPDATE inventory_batches 
            SET status = ?, quantity = ?, moisture_content = ?, storage_location = ?, processed_date = ? 
            WHERE id = ?";
    
    if (execute($sql, [$new_status, $quantity, $moisture, $location, $processed_date, $batch_id])) {
        setFlashMessage('success', "Batch updated to stage: <strong>" . ucfirst(str_replace('_', ' ', $new_status)) . "</strong>");
    } else {
        setFlashMessage('danger', 'Failed to update batch.');
    }

    redirectBase('/inventory/index.php');

} else {
    redirectBase('/inventory/index.php');
}
