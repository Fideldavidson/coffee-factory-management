<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['manager', 'clerk']);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $name = sanitize($_POST['name'] ?? '');
    // Auto-generate farmer_id
    $farmer_id = getNextFarmerId();
    $phone = sanitize($_POST['phone'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $registration_date = $_POST['registration_date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($phone)) $errors[] = 'Phone number is required';
    if (empty($location)) $errors[] = 'Location is required';
    
    if (empty($errors)) {
        $sql = "INSERT INTO farmers (name, farmer_id, phone, location, registration_date, status) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        if (execute($sql, [$name, $farmer_id, $phone, $location, $registration_date, $status])) {
            setFlashMessage('success', 'Farmer added successfully with ID: ' . $farmer_id . '. Now create their user account.');
            $redirectUrl = baseUrl('/users/index.php?open_modal=true&name=' . urlencode($name) . '&phone=' . urlencode($phone) . '&role=farmer');
            redirect($redirectUrl);
        } else {
            $errors[] = 'Failed to add farmer. Please try again.';
        }
    }
    
    if (!empty($errors)) {
        setFlashMessage('error', implode('<br>', $errors));
        redirectBase('/farmers/index.php');
    }
} else {
    redirectBase('/farmers/index.php');
}
