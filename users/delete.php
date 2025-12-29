<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('manager');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectBase('/users/index.php');
}

requireCsrf();

$id = $_POST['id'] ?? 0;

// Prevent deleting System Admin (ID 1)
if ($id == 1) {
    setFlashMessage('error', 'The System Administrator account cannot be deleted.');
    redirectBase('/users/index.php');
}

// Prevent deleting own account
if ($id == getCurrentUserId()) {
    setFlashMessage('error', 'You cannot delete your own account.');
    redirectBase('/users/index.php');
}

$user = queryOne("SELECT * FROM users WHERE id = ?", [$id]);

if (!$user) {
    setFlashMessage('error', 'User not found.');
    redirectBase('/users/index.php');
}

execute("DELETE FROM users WHERE id = ?", [$id]);
setFlashMessage('success', 'User deleted successfully.');
redirectBase('/users/index.php');
