<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('manager'); // Only managers can delete farmers

$id = $_GET['id'] ?? 0;
$farmer = queryOne("SELECT * FROM farmers WHERE id = ?", [$id]);

if (!$farmer) {
    setFlashMessage('error', 'Farmer not found.');
    redirectBase('/farmers/index.php');
}

// Check if farmer has deliveries
$deliveryCount = queryOne("SELECT COUNT(*) as count FROM deliveries WHERE farmer_id = ?", [$id])['count'];

if ($deliveryCount > 0) {
    // Soft delete - set status to inactive
    execute("UPDATE farmers SET status = 'inactive' WHERE id = ?", [$id]);
    setFlashMessage('warning', 'Farmer has delivery history and has been set to inactive instead of deleted.');
} else {
    // Hard delete
    execute("DELETE FROM farmers WHERE id = ?", [$id]);
    setFlashMessage('success', 'Farmer deleted successfully.');
}

redirectBase('/farmers/index.php');
