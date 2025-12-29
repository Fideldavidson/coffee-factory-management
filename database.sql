-- Coffee Factory Management System Database Schema
-- MySQL Database for XAMPP

-- Create database
CREATE DATABASE IF NOT EXISTS coffee_factory_cms;
USE coffee_factory_cms;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('manager', 'clerk', 'farmer') NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Farmers table
CREATE TABLE IF NOT EXISTS farmers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    farmer_id VARCHAR(50) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    location VARCHAR(100) NOT NULL,
    registration_date DATE NOT NULL,
    total_deliveries INT DEFAULT 0,
    total_quantity DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_farmer_id (farmer_id),
    INDEX idx_status (status),
    INDEX idx_location (location)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Deliveries table
CREATE TABLE IF NOT EXISTS deliveries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    grade ENUM('AA', 'AB', 'PB', 'C', 'TT', 'T') NOT NULL,
    moisture_content DECIMAL(5,2) NOT NULL,
    delivery_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    recorded_by INT,
    batch_number VARCHAR(50),
    status ENUM('pending', 'processed', 'quality_check', 'approved', 'rejected') DEFAULT 'pending',
    quality_score INT,
    processed_date TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_farmer_id (farmer_id),
    INDEX idx_status (status),
    INDEX idx_delivery_date (delivery_date),
    INDEX idx_batch_number (batch_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inventory Batches table
CREATE TABLE IF NOT EXISTS inventory_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_number VARCHAR(50) NOT NULL UNIQUE,
    grade VARCHAR(10) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    original_quantity DECIMAL(10,2) NOT NULL,
    status ENUM('received', 'processing', 'dried', 'milled', 'ready_export', 'exported') DEFAULT 'received',
    storage_location VARCHAR(100),
    quality_score INT,
    moisture_content DECIMAL(5,2) NOT NULL,
    received_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_date TIMESTAMP NULL,
    export_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_batch_number (batch_number),
    INDEX idx_status (status),
    INDEX idx_grade (grade),
    INDEX idx_received_date (received_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- System logs table
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user
-- Password: admin123 (hashed with password_hash)
INSERT INTO users (name, email, password, role, phone) VALUES
('System Administrator', 'admin@coffee.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', '+254700000000'),
('John Kamau', 'clerk@coffee.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'clerk', '+254700000001'),
('Mary Wanjiku', 'manager@coffee.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', '+254700000002');

-- Insert sample farmers
INSERT INTO farmers (name, farmer_id, phone, location, registration_date, total_deliveries, total_quantity, status) VALUES
('Samuel Kiprotich', 'MRC001', '+254712345001', 'Meru North', '2023-06-15', 24, 2450.00, 'active'),
('Grace Nyawira', 'MRC002', '+254712345002', 'Meru Central', '2023-08-20', 18, 1890.00, 'active'),
('David Mwenda', 'MRC003', '+254712345003', 'Meru South', '2023-04-10', 32, 3200.00, 'active'),
('Janet Mukami', 'MRC004', '+254712345004', 'Meru East', '2023-09-05', 15, 1450.00, 'inactive'),
('Peter Gitonga', 'MRC005', '+254712345005', 'Meru North', '2023-07-12', 28, 2800.00, 'active');

-- Insert sample deliveries
INSERT INTO deliveries (farmer_id, quantity, grade, moisture_content, delivery_date, recorded_by, batch_number, status, quality_score, processed_date) VALUES
(1, 120.00, 'AA', 11.5, '2024-09-22 09:30:00', 2, 'BTH240922001', 'approved', 85, '2024-09-22 16:00:00'),
(2, 85.00, 'AB', 12.0, '2024-09-22 10:15:00', 2, 'BTH240922002', 'pending', NULL, NULL),
(3, 95.00, 'AA', 11.2, '2024-09-21 14:20:00', 2, 'BTH240921001', 'quality_check', 82, '2024-09-21 18:00:00'),
(1, 110.00, 'AB', 11.8, '2024-09-20 08:45:00', 2, 'BTH240920001', 'approved', 88, '2024-09-20 15:30:00'),
(4, 75.00, 'PB', 12.5, '2024-09-19 11:00:00', 2, 'BTH240919001', 'rejected', 65, '2024-09-19 17:00:00');

-- Insert sample inventory batches
INSERT INTO inventory_batches (batch_number, grade, quantity, original_quantity, status, storage_location, quality_score, moisture_content, received_date, processed_date) VALUES
('BTH240922001', 'AA', 120.00, 120.00, 'ready_export', 'Warehouse A - Section 1', 85, 11.5, '2024-09-22 09:30:00', '2024-09-22 16:00:00'),
('BTH240922002', 'AB', 85.00, 85.00, 'received', 'Warehouse A - Section 2', NULL, 12.0, '2024-09-22 10:15:00', NULL),
('BTH240921001', 'AA', 95.00, 95.00, 'dried', 'Warehouse B - Section 1', 82, 11.2, '2024-09-21 14:20:00', '2024-09-21 18:00:00'),
('BTH240920001', 'AB', 108.00, 110.00, 'milled', 'Warehouse A - Section 3', 88, 11.8, '2024-09-20 08:45:00', '2024-09-20 15:30:00');
