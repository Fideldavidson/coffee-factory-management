<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('manager');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    requireCsrf();

    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? '';

    // Validation
    $errors = [];
    if (!$id) $errors[] = "User ID is required.";
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (empty($role) || !in_array($role, ['manager', 'clerk', 'farmer'])) $errors[] = "Valid role is required.";

    // Prevent System Admin Role Change
    if ($id == 1 && $role !== 'manager') {
         $errors[] = "The System Administrator role cannot be changed.";
    }

    // Check email uniqueness (excluding current user)
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        $errors[] = "Email is already in use by another user.";
    }

    if (!empty($errors)) {
        setFlashMessage('error', implode(' ', $errors));
        header("Location: " . baseUrl('/users/index.php'));
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ? WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $role, $id]);

        // If System Admin (ID 1), ensure minimal privileges are kept if logic allowed modification (but we prevented role change above)
        // Strictly speaking, we just updated the user.

        logAction('update_user', "Updated user: $name (ID: $id)");
        setFlashMessage('success', "User updated successfully.");

    } catch (PDOException $e) {
        logAction('update_user_failed', "Failed to update user ID $id: " . $e->getMessage());
        setFlashMessage('error', "Database error: " . $e->getMessage());
    }
    
    header("Location: " . baseUrl('/users/index.php'));
    exit;
}
