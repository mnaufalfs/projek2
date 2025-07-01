-- Create database
CREATE DATABASE IF NOT EXISTS rental_vehicle_system;
USE rental_vehicle_system;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Vehicle types table
CREATE TABLE vehicle_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT
);

-- Vehicles table
CREATE TABLE vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_type_id INT,
    name VARCHAR(100) NOT NULL,
    plate_number VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    status ENUM('available', 'rented', 'maintenance') DEFAULT 'available',
    base_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_types(id)
);

-- Drivers table
CREATE TABLE drivers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    license_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('available', 'assigned', 'off') DEFAULT 'available'
);

-- Delivery fees table
CREATE TABLE delivery_fees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    distance_km DECIMAL(10,2) NOT NULL,
    fee DECIMAL(10,2) NOT NULL
);

-- Rental orders table
CREATE TABLE rental_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    vehicle_id INT,
    driver_id INT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    rental_type ENUM('hourly', 'daily', 'weekly', 'monthly') NOT NULL,
    is_out_of_town BOOLEAN DEFAULT FALSE,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'ongoing', 'completed', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'verified') DEFAULT 'pending',
    payment_method ENUM('cash', 'transfer') NOT NULL,
    payment_proof VARCHAR(255),
    delivery_fee DECIMAL(10,2) DEFAULT 0,
    pickup_fee DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (driver_id) REFERENCES drivers(id)
);

-- Rental returns table
CREATE TABLE rental_returns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rental_order_id INT,
    return_date DATETIME NOT NULL,
    late_hours INT DEFAULT 0,
    late_fee DECIMAL(10,2) DEFAULT 0,
    damage_fee DECIMAL(10,2) DEFAULT 0,
    total_additional_fee DECIMAL(10,2) DEFAULT 0,
    return_payment_method ENUM('cash', 'transfer') DEFAULT NULL,
    return_payment_proof VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'verified', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rental_order_id) REFERENCES rental_orders(id)
);

-- Insert sample data for vehicle types
INSERT INTO vehicle_types (name, description) VALUES
('Sedan', 'Luxury sedan vehicles'),
('MPV', 'Multi Purpose Vehicle'),
('Mobil Box', 'Box type delivery vehicles'),
('Bak Terbuka', 'Open truck vehicles'),
('Minibis', 'Mini bus vehicles');

-- Insert sample delivery fees
INSERT INTO delivery_fees (distance_km, fee) VALUES
(5, 50000),
(10, 75000),
(15, 100000),
(20, 125000),
(25, 150000),
(30, 175000),
(35, 200000),
(40, 225000),
(45, 250000),
(50, 275000);

-- Insert sample admin user
INSERT INTO users (name, email, phone, password, role) VALUES
('Admin', 'admin@rental.com', '08123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'); 