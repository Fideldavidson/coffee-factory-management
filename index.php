<?php
require_once __DIR__ . '/includes/functions.php';

// Redirect to appropriate dashboard based on role
if (!isLoggedIn()) {
    redirectBase('/login.php');
}

$role = getCurrentUserRole();

if ($role === 'manager') {
    redirectBase('/manager-dashboard.php');
} elseif ($role === 'clerk') {
    redirectBase('/clerk-dashboard.php');
} else {
    redirectBase('/farmer-portal.php');
}
