<?php
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectBase('/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        logAction('security_alert', 'Failed CSRF verification on login');
        $error = 'Security validation failed. Please try again.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Get user from database
        $user = queryOne("SELECT * FROM users WHERE email = ?", [$email]);
        
        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
            
            // Set session
            setUserSession($user['id'], $user['name'], $user['role'], $user['email'], $user['phone']);
            
            // Redirect based on role
            if ($user['role'] === 'manager') {
                redirectBase('/manager-dashboard.php');
            } elseif ($user['role'] === 'clerk') {
                redirectBase('/clerk-dashboard.php');
            } else {
                redirectBase('/farmer-portal.php');
            }
        } else {
            logAction('login_failed', "Failed login attempt for email: $email");
            $error = 'Invalid email or password.';
        }
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Meru Coffee Co-op</title>
    <!-- Use separate login CSS for isolation -->
    <link rel="stylesheet" href="<?php echo baseUrl('/assets/css/login.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="login-page">
    <?php if ($error): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: '<?php echo e($error); ?>',
                confirmButtonColor: '#3E2723'
            });
        });
    </script>
    <?php endif; ?>
    
    <div class="login-brand">
        <div class="login-brand-logo">
            <img src="<?php echo baseUrl('/assets/images/coffee-icon.png'); ?>" alt="Coffee Icon" class="brand-icon">
            <h1>Meru Coffee Co-op</h1>
        </div>
        <div class="login-brand-subtitle">Factory Management System</div>
    </div>

    <div class="login-card">
        <div class="login-title">
            <h2>Welcome Back</h2>
            <p>Sign in to access your dashboard</p>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="your.email@coffee.com"
                    value="<?php echo e($_POST['email'] ?? ''); ?>"
                    required
                >
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="password-input-group">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Enter your password"
                        required
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        <i class="fas fa-eye-slash" id="toggleIcon"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
            </button>
        </form>
    </div>
    
    <div class="login-footer">
        &copy; <?php echo date('Y'); ?> Meru Coffee Cooperative. Empowering farmers, one bean at a time.
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            }
        }
    </script>
</body>
</html>
