<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCsrfToken($token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Security validation failed. Please refresh the page.']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $userId = getCurrentUserId();

    if ($action === 'update_profile') {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');

        if (empty($name) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Name and email are required.']);
            exit;
        }

        // Check email uniqueness (if changed)
        $existing = queryOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $userId]);
        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'Email is already in use.']);
            exit;
        }

        $oldUser = queryOne("SELECT phone FROM users WHERE id = ?", [$userId]);
        $oldPhone = $oldUser['phone'] ?? '';

        // In PDO with MySQL, rowCount() returns 0 if no rows were actually changed (e.g. updating with same values)
        // However, execute() returns true on success. We need to catch that.
        try {
            execute("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?", [$name, $email, $phone, $userId]);
            
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_phone'] = $phone;
            
            // Sync with farmers table if user is a farmer
            if (getCurrentUserRole() === 'farmer') {
                execute("UPDATE farmers SET phone = ? WHERE phone = ?", [$phone, $oldPhone]);
            }
            
            logAction('profile_update', "User updated profile details");
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to update profile: ' . $e->getMessage()]);
        }

    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }

        $user = queryOne("SELECT password FROM users WHERE id = ?", [$userId]);
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Incorrect current password.']);
            exit;
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        try {
            execute("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $userId]);
            logAction('password_change', "User changed password");
            echo json_encode(['success' => true, 'message' => 'Password changed successfully!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to change password.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
}
?>
