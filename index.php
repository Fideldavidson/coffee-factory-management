<?php
require_once __DIR__ . '/includes/functions.php';

// If user is logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    $role = getCurrentUserRole();
    
    if ($role === 'manager') {
        redirectBase('/manager-dashboard.php');
    } elseif ($role === 'clerk') {
        redirectBase('/clerk-dashboard.php');
    } else {
        redirectBase('/farmer-portal.php');
    }
}

// If not logged in, show the home page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meru Coffee Co-op - Factory Management System</title>
    <meta name="description" content="Professional coffee factory management system for Meru Coffee Cooperative. Empowering farmers, one bean at a time.">
    <link rel="stylesheet" href="<?php echo baseUrl('/assets/css/home.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="home-page">
    <!-- Header -->
    <header class="home-header" id="header">
        <div class="header-container">
            <a href="<?php echo baseUrl('/index.php'); ?>" class="home-logo">
                <img src="<?php echo baseUrl('/assets/images/coffee-icon.png'); ?>" alt="Coffee Icon">
                <span>Meru Coffee Co-op</span>
            </a>
            <a href="<?php echo baseUrl('/login.php'); ?>" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Login
            </a>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-container">
            <div class="hero-badge">
                <i class="fas fa-seedling"></i>
                Empowering Coffee Farmers Since 2005
            </div>
            <h1 class="hero-title">
                Welcome to <span class="highlight">Meru Coffee</span><br>
                Factory Management
            </h1>
            <p class="hero-subtitle">
                A comprehensive platform designed to streamline coffee production, 
                track deliveries, manage inventory, and empower our farming community 
                with transparency and efficiency.
            </p>
            <div class="hero-cta">
                <a href="<?php echo baseUrl('/login.php'); ?>" class="btn-primary">
                    <i class="fas fa-rocket"></i>
                    Get Started
                </a>
                <a href="#features" class="btn-secondary">
                    <i class="fas fa-info-circle"></i>
                    Learn More
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="features-container">
            <div class="section-header">
                <span class="section-badge">Our Features</span>
                <h2 class="section-title">Built for Excellence</h2>
                <p class="section-description">
                    Our system provides powerful tools to manage every aspect of coffee production, 
                    from farm to factory.
                </p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Farmer Management</h3>
                    <p class="feature-description">
                        Comprehensive farmer profiles with delivery history, 
                        and performance analytics to support our cooperative members.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h3 class="feature-title">Delivery Tracking</h3>
                    <p class="feature-description">
                        Real-time tracking of coffee deliveries with quality assessments
                       and weight verification.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <h3 class="feature-title">Inventory Control</h3>
                    <p class="feature-description">
                        Advanced inventory management with batch tracking, quality grading, 
                        and stock level monitoring for optimal production planning.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">Analytics & Reports</h3>
                    <p class="feature-description">
                        Powerful reporting tools with customizable timeframes, data exports, 
                        and visual dashboards for informed decision-making.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="feature-title">Quality Assurance</h3>
                    <p class="feature-description">
                        Rigorous quality control with moisture testing, grade classification, 
                        and compliance tracking to maintain premium standards.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="feature-title">Farmer Portal</h3>
                    <p class="feature-description">
                        Dedicated portal for farmers to view their delivery history, 
                     and access important cooperative information.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="home-footer">
        <div class="footer-container">
            <div class="footer-logo">
                <img src="<?php echo baseUrl('/assets/images/coffee-icon.png'); ?>" alt="Coffee Icon">
                <span>Meru Coffee Co-op</span>
            </div>
            <p class="footer-description">
                Empowering farmers, one bean at a time. Building a sustainable future 
                for our coffee farming community through innovation and transparency.
            </p>
            <div class="footer-divider"></div>
            <div class="footer-bottom">
                &copy; <?php echo date('Y'); ?> Meru Coffee Cooperative. All rights reserved.
            </div>
        </div>
    </footer>

    <script>
        // Header scroll effect
        const header = document.getElementById('header');
        
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
