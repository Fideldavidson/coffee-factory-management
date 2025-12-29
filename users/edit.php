<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('manager');

$id = $_GET['id'] ?? 0;
$user = queryOne("SELECT * FROM users WHERE id = ?", [$id]);

if (!$user) {
    setFlashMessage('error', 'User not found.');
    redirectBase('/users/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email) || !isValidEmail($email)) $errors[] = 'Valid email is required';
    if (empty($role)) $errors[] = 'Role is required';
    
    // Check if email exists elsewhere
    if (!empty($email) && $email !== $user['email']) {
        $existing = queryOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $id]);
        if ($existing) $errors[] = 'Email already exists';
    }
    
    if (empty($errors)) {
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET name = ?, email = ?, role = ?, phone = ?, password = ? WHERE id = ?";
            $params = [$name, $email, $role, $phone, $hashedPassword, $id];
        } else {
            $sql = "UPDATE users SET name = ?, email = ?, role = ?, phone = ? WHERE id = ?";
            $params = [$name, $email, $role, $phone, $id];
        }
        
        try {
            execute($sql, $params);
            setFlashMessage('success', 'User updated successfully!');
            redirectBase('/users/index.php');
        } catch (Exception $e) {
            $errors[] = 'Failed to update user: ' . $e->getMessage();
        }
    }
    if (!empty($errors)) {
        setFlashMessage('error', implode('<br>', $errors));
    }
}

$pageTitle = "Edit User";
include __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Edit User</h3>
        <p class="card-description">Update account information for <?php echo e($user['name']); ?></p>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div class="form-group">
                <label class="form-label" for="name">Full Name *</label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo e($_POST['name'] ?? $user['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo e($_POST['email'] ?? $user['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password (Leave blank to keep current)</label>
                <input type="password" id="password" name="password" class="form-control" minlength="6">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="role">Role *</label>
                <select id="role" name="role" class="form-select" required>
                    <option value="manager" <?php echo ($user['role'] === 'manager') ? 'selected' : ''; ?>>Manager</option>
                    <option value="clerk" <?php echo ($user['role'] === 'clerk') ? 'selected' : ''; ?>>Clerk</option>
                    <option value="farmer" <?php echo ($user['role'] === 'farmer') ? 'selected' : ''; ?>>Farmer</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo e($_POST['phone'] ?? $user['phone']); ?>">
            </div>
            
            <div class="flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Update User
                </button>
                <a href="<?php echo baseUrl('/users/index.php'); ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
