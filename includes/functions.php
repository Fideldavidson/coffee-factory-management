<?php
/**
 * Common Utility Functions
 */

date_default_timezone_set('Africa/Nairobi');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

/**
 * Redirect to a page
 */
/**
 * Redirect to a page
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Redirect to a path relative to base URL
 */
function redirectBase($path) {
    redirect(baseUrl($path));
}

/**
 * Require login - redirect to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirectBase('/login.php');
    }
}

/**
 * Require specific role
 */
function requireRole($allowedRoles) {
    requireLogin();
    
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    $currentRole = getCurrentUserRole();
    if (!in_array($currentRole, $allowedRoles)) {
        setFlashMessage('error', 'You do not have permission to access this page.');
        redirectBase('/index.php');
    }
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    startSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    startSession();
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Require CSRF token on POST requests
 */
function requireCsrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            logAction('security_alert', 'Failed CSRF verification');
            die('CSRF token validation failed.');
        }
    }
}

/**
 * Set security headers
 */
function setSecurityHeaders() {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data:;");
}

/**
 * Log system action
 */
function logAction($action, $details = '') {
    $userId = getCurrentUserId();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Check if system_logs table exists first (optional safety)
    $sql = "INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
    try {
        execute($sql, [$userId, $action, $details, $ip]);
    } catch (Exception $e) {
        // Fail silently or log to PHP error log if DB fails
        error_log("Logging failed: " . $e->getMessage());
    }
}

/**
 * Format date
 */
function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
    if (empty($datetime)) return '';
    return date($format, strtotime($datetime));
}

/**
 * Format weight (kg)
 */
function formatWeight($weight) {
    return number_format($weight, 2) . ' kg';
}

/**
 * Get delivery status color class
 */
function getDeliveryStatusColor($status) {
    $colors = [
        'pending' => 'badge-warning',
        'processed' => 'badge-info',
        'quality_check' => 'badge-purple',
        'approved' => 'badge-success',
        'rejected' => 'badge-danger'
    ];
    return $colors[$status] ?? 'badge-secondary';
}

/**
 * Get inventory status color class
 */
function getInventoryStatusColor($status) {
    $colors = [
        'received' => 'badge-info',
        'processing' => 'badge-warning',
        'dried' => 'badge-orange',
        'milled' => 'badge-purple',
        'ready_export' => 'badge-success',
        'exported' => 'badge-secondary'
    ];
    return $colors[$status] ?? 'badge-secondary';
}

/**
 * Get farmer status color class
 */
function getFarmerStatusColor($status) {
    return $status === 'active' ? 'badge-success' : 'badge-secondary';
}

/**
 * Get role badge color
 */
function getRoleBadgeColor($role) {
    $colors = [
        'manager' => 'badge-primary',
        'clerk' => 'badge-info',
        'farmer' => 'badge-success'
    ];
    return $colors[$role] ?? 'badge-secondary';
}

/**
 * Generate batch number
 */
function generateBatchNumber() {
    return 'BTH' . date('ymd') . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Kenyan format)
 */
function isValidPhone($phone) {
    return preg_match('/^\+254[0-9]{9}$/', $phone);
}

/**
 * Get page title based on current page
 */
function getPageTitle() {
    $page = basename($_SERVER['PHP_SELF'], '.php');
    $titles = [
        'index' => 'Dashboard',
        'manager-dashboard' => 'Manager Dashboard',
        'clerk-dashboard' => 'Clerk Dashboard',
        'farmer-portal' => 'Farmer Portal',
        'login' => 'Login'
    ];
    return $titles[$page] ?? 'Coffee Factory CMS';
}

/**
 * Check if current page is active (for navigation)
 */
function isActivePage($page) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return strpos($currentPage, $page) !== false ? 'active' : '';
}

/**
 * Escape output
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Get base URL
 */
function baseUrl($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base = '/coffee-factory-management-system';
    return $protocol . '://' . $host . $base . $path;
}
/**
 * Get the next available farmer ID
 * Format: MRC001, MRC002, etc. (auto-increments)
 */
function getNextFarmerId() {
    $sql = "SELECT farmer_id FROM farmers WHERE farmer_id LIKE 'MRC%' ORDER BY farmer_id DESC LIMIT 1";
    $last = queryOne($sql);
    
    if (!$last) {
        return 'MRC001';
    }
    
    $lastId = $last['farmer_id'];
    $number = (int)substr($lastId, 3);
    $nextNumber = $number + 1;
    
    return 'MRC' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
}
