<?php
require_once __DIR__ . '/../includes/functions.php';
setSecurityHeaders();
requireLogin();

$pageTitle = getPageTitle();
$currentUser = getCurrentUserName();
$currentRole = getCurrentUserRole();
$flashMessage = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?> - Coffee Factory CMS</title>
    <meta name="csrf-token" content="<?php echo generateCsrfToken(); ?>">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Styles -->
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-minimal/minimal.css">
    
    <link rel="stylesheet" href="<?php echo baseUrl('/assets/css/style.css?v=' . time()); ?>">
    <link rel="stylesheet" href="<?php echo baseUrl('/assets/css/sidebar-expansion.css?v=' . time()); ?>">
    <link rel="stylesheet" href="<?php echo baseUrl('/assets/css/utility.css?v=' . time()); ?>">
</head>
<body>
    <script>
        // Immediately apply sidebar state to prevent layout shift
        if (localStorage.getItem('sidebarExpanded') === 'true' && window.innerWidth > 768) {
            document.body.classList.add('sidebar-expanded');
            // Disable transitions temporarily
            document.body.classList.add('no-transition');
            setTimeout(() => {
                document.body.classList.remove('no-transition');
            }, 100);
        }
    </script>
    <?php if ($flashMessage): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?php echo $flashMessage['type'] === 'error' || $flashMessage['type'] === 'danger' ? 'error' : ($flashMessage['type'] === 'warning' ? 'warning' : 'success'); ?>',
                title: '<?php echo $flashMessage['type'] === 'error' || $flashMessage['type'] === 'danger' ? 'Error!' : ($flashMessage['type'] === 'warning' ? 'Notice' : 'Success!'); ?>',
                text: '<?php echo addslashes(strip_tags($flashMessage['message'])); ?>',
                confirmButtonColor: '#3E2723',
                timer: 4000,
                timerProgressBar: true
            });
        });
    </script>
    <?php endif; ?>
    
    <div class="wrapper">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
        
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo" onclick="toggleSidebar()" style="cursor: pointer;">
                    <i class="fas fa-bars logo-icon" style="color: #3E2723;"></i>
                    <span><?php 
                        if ($currentRole === 'clerk') {
                            echo 'Operations Dashboard';
                        } elseif ($currentRole === 'farmer') {
                            echo 'Farmer Portal';
                        } else {
                            echo 'Management Dashboard';
                        }
                    ?></span>
                </div>
            </div>
            
            <div class="nav-section-title">Navigation</div>
            
            <nav class="sidebar-nav">
                <?php if ($currentRole === 'manager'): ?>
                    <a href="<?php echo baseUrl('/manager-dashboard.php'); ?>" class="nav-item <?php echo isActivePage('manager-dashboard'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                            <path d="M520-600v-240h320v240H520ZM120-440v-400h320v400H120Zm400 320v-400h320v400H520Zm-400 0v-240h320v240H120Zm80-400h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Zm160-320Zm240-160Zm0 240ZM360-280Z"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo baseUrl('/farmers/index.php'); ?>" class="nav-item <?php echo isActivePage('farmers'); ?>">
                        <i class="fas fa-user-friends"></i>
                        <span>Farmers</span>
                    </a>
                    <a href="<?php echo baseUrl('/deliveries/index.php'); ?>" class="nav-item <?php echo isActivePage('deliveries'); ?>">
                        <i class="fas fa-truck-loading"></i>
                        <span>Deliveries</span>
                    </a>
                    <a href="<?php echo baseUrl('/inventory/index.php'); ?>" class="nav-item <?php echo isActivePage('inventory'); ?>">
                        <i class="fas fa-cubes"></i>
                        <span>Inventory</span>
                    </a>
                    <a href="<?php echo baseUrl('/reports/index.php'); ?>" class="nav-item <?php echo isActivePage('reports'); ?>">
                        <i class="fas fa-file-alt"></i>
                        <span>Reports</span>
                    </a>
                    <a href="<?php echo baseUrl('/users/index.php'); ?>" class="nav-item <?php echo isActivePage('users'); ?>">
                        <i class="fas fa-cog"></i>
                        <span>User Management</span>
                    </a>
                <?php elseif ($currentRole === 'clerk'): ?>
                    <a href="<?php echo baseUrl('/clerk-dashboard.php'); ?>" class="nav-item <?php echo isActivePage('clerk-dashboard'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                            <path d="M520-600v-240h320v240H520ZM120-440v-400h320v400H120Zm400 320v-400h320v400H520Zm-400 0v-240h320v240H120Zm80-400h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Zm160-320Zm240-160Zm0 240ZM360-280Z"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo baseUrl('/farmers/index.php'); ?>" class="nav-item <?php echo isActivePage('farmers'); ?>">
                        <i class="fas fa-users"></i>
                        <span>Farmers</span>
                    </a>
                    <a href="<?php echo baseUrl('/deliveries/index.php'); ?>" class="nav-item <?php echo isActivePage('deliveries'); ?>">
                        <i class="fas fa-truck"></i>
                        <span>Deliveries</span>
                    </a>
                    <a href="<?php echo baseUrl('/inventory/index.php'); ?>" class="nav-item <?php echo isActivePage('inventory'); ?>">
                        <i class="fas fa-boxes"></i>
                        <span>Inventory</span>
                    </a>
                <?php elseif ($currentRole === 'farmer'): ?>
                    <a href="<?php echo baseUrl('/farmer-portal.php'); ?>" class="nav-item <?php echo isActivePage('farmer-portal'); ?>">
                        <i class="fas fa-home"></i>
                        <span>My Portal</span>
                    </a>
                <?php endif; ?>
            </nav>
            
            <div class="sidebar-user">
                <!-- Logout is handled in profile dropdown now, but keeping sidebar footer empty or used for something else -->
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                        <img src="<?php echo baseUrl('/assets/images/coffee-icon.png'); ?>" alt="Coffee Icon" class="toggle-coffee-icon">
                    </button>
                    <div class="header-title">
                        <span><?php 
                            if ($currentRole === 'clerk') {
                                echo 'Operations Dashboard';
                            } elseif ($currentRole === 'farmer') {
                                echo 'Farmer Portal';
                            } else {
                                echo 'Management Dashboard';
                            }
                        ?></span>
                    </div>
                </div>
                
                <div class="header-right">
                    <div class="user-profile" onclick="toggleProfileDropdown()">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr(getCurrentUserName(), 0, 1)); ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo e(getCurrentUserName()); ?></div>
                            <div class="user-role"><?php echo ucfirst(e(getCurrentUserRole())); ?></div>
                        </div>
                    </div>
                    
                    <div class="profile-dropdown" id="profileDropdown">
                        <a href="#" class="dropdown-item" onclick="openProfileSettings(event)">
                            <i class="far fa-user"></i>
                            <span>Profile Settings</span>
                        </a>
                        <a href="#" class="dropdown-item" onclick="openChangePassword(event)">
                            <i class="fa-solid fa-key"></i>
                            <span>Change Password</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo baseUrl('/logout.php'); ?>" class="dropdown-item logout-item">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </header>
            
            <div class="content-body">
