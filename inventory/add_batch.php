<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole(['manager', 'clerk']);

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    // 1. Cherry Reception, Weighing & Batch Creation
    // In the Kenyan system, this happens after cherries are sorted (removing unripe/diseased ones)
    // Only "Grade A" ripe cherries are weighed and assigned this Batch ID.
    $batch_number = sanitize($_POST['batch_number']);
    $grade = sanitize($_POST['grade']);
    
    // 2. Intake Metrics
    // The primary metric at intake is Weight (kg), determining payment to farmers.
    $quantity = floatval($_POST['quantity']);
    $original_quantity = $quantity; // Establish baseline for loss calculation later
    
    $storage_location = sanitize($_POST['storage_location'] ?? '');
    
    // 3. Processing & Quality Data
    // Moisture content is critical. Target for dried parchment is 10.5% - 12%.
    // If this is a new "wet" batch, moisture might be higher/irrelevant until drying.
    $moisture_content = floatval($_POST['moisture_content']);
    $quality_score = !empty($_POST['quality_score']) ? intval($_POST['quality_score']) : null;
    $status = 'received'; // Default start status

    // Validate Required Fields
    $errors = [];
    if (empty($batch_number)) $errors[] = "Batch number is required.";
    if (empty($grade)) $errors[] = "Grade is required.";
    if ($quantity <= 0) $errors[] = "Quantity must be greater than 0.";

    // Check for duplicate batch number
    $existing = queryOne("SELECT id FROM inventory_batches WHERE batch_number = ?", [$batch_number]);
    if ($existing) {
        $errors[] = "Batch number already exists.";
    }

    if (empty($errors)) {
        // --- Technical Flow: Batch Lifecycle Injection ---
        
        // Stage 1: Reception (Done via Form Input)
        
        // Stage 2: Pulping (Primary Separation) would separate Lights/Heavies.
        // This record implies the main batch (likely Heavies/P1).
        
        // Stage 3: Fermentation (12-48hrs) -> Washing -> Soaking (12-24hrs distinct Kenyan step)
        // These steps happen physically. In the system, we initialize the batch record here.
        
        $sql = "INSERT INTO inventory_batches 
                (batch_number, grade, quantity, original_quantity, status, storage_location, moisture_content, quality_score, received_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        if (execute($sql, [$batch_number, $grade, $quantity, $original_quantity, $status, $storage_location, $moisture_content, $quality_score])) {
            setFlashMessage('success', "Batch <strong>$batch_number</strong> created successfully. Ready for processing steps (Fermentation -> Washing -> Drying).");
            redirectBase('/inventory/index.php');
        } else {
            setFlashMessage('danger', 'Failed to create batch. Database error.');
            redirectBase('/inventory/index.php');
        }
    } else {
        setFlashMessage('danger', implode('<br>', $errors));
        redirectBase('/inventory/index.php');
    }
} else {
    // If accessed directly without POST
    redirectBase('/inventory/index.php');
}
