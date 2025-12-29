<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('manager');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $phone = sanitize($_POST['phone'] ?? '');
    
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email) || !isValidEmail($email)) $errors[] = 'Valid email is required';
    if (empty($password) || strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
    if (empty($role)) $errors[] = 'Role is required';
    
    // Check if email exists
    if (!empty($email)) {
        $existing = queryOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) $errors[] = 'Email already exists';
    }
    
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)";
        
        if (execute($sql, [$name, $email, $hashedPassword, $role, $phone])) {
            setFlashMessage('success', 'User added successfully!');
            redirectBase('/users/index.php');
        } else {
            $errors[] = 'Failed to add user.';
        }
    }
    if (!empty($errors)) {
        setFlashMessage('error', implode('<br>', $errors));
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Add New User</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div class="form-group">
                <label class="form-label" for="name">Full Name *</label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo e($_POST['name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password *</label>
                <input type="password" id="password" name="password" class="form-control" minlength="6" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="role">Role *</label>
                <select id="role" name="role" class="form-select" required>
                    <option value="">Select role</option>
                    <option value="manager">Manager</option>
                    <option value="clerk">Clerk</option>
                    <option value="farmer">Farmer</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo e($_POST['phone'] ?? ''); ?>">
            </div>
            
            <div class="flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Add User
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
