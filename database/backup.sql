-- Database Backup Instructions
-- To backup the database, run the following command in your terminal:
-- mysqldump -u [username] -p rental_kendaraan > backup.sql

-- To restore the database from backup, run:
-- mysql -u [username] -p rental_kendaraan < backup.sql

-- Note: Replace [username] with your MySQL username
-- You will be prompted for your MySQL password

-- Backup Date: 2024-03-20
-- Backup Version: 1.0.0

-- Drop existing database if exists
DROP DATABASE IF EXISTS rental_kendaraan;

-- Create database
CREATE DATABASE rental_kendaraan;
USE rental_kendaraan;

-- Create users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create vehicle_types table
CREATE TABLE vehicle_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    icon VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create vehicles table
CREATE TABLE vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    plate_number VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    price_per_day DECIMAL(10,2) NOT NULL,
    status ENUM('available', 'rented', 'maintenance') NOT NULL DEFAULT 'available',
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (type_id) REFERENCES vehicle_types(id) ON DELETE CASCADE
);

-- Create drivers table
CREATE TABLE drivers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    license_number VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('available', 'assigned', 'off') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create rental_orders table
CREATE TABLE rental_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    driver_id INT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'refunded') NOT NULL DEFAULT 'pending',
    payment_method ENUM('cash', 'transfer', 'credit_card') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL
);

-- Create order_notes table
CREATE TABLE order_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    admin_id INT NOT NULL,
    note TEXT NOT NULL,
    note_type ENUM('general', 'issue', 'payment', 'vehicle', 'driver', 'customer') NOT NULL DEFAULT 'general',
    is_private BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES rental_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create contact_messages table
CREATE TABLE contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user
INSERT INTO users (name, email, password, phone, role) VALUES
('Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567890', 'admin');

-- Insert vehicle types
INSERT INTO vehicle_types (name, description, icon) VALUES
('Sedan', 'Comfortable and fuel-efficient vehicles for city driving', 'fas fa-car'),
('SUV', 'Spacious vehicles perfect for family trips', 'fas fa-truck-monster'),
('Minivan', 'Large vehicles ideal for group transportation', 'fas fa-van-shuttle'),
('Sports Car', 'High-performance vehicles for an exciting driving experience', 'fas fa-car-side'),
('Luxury Car', 'Premium vehicles with advanced features and comfort', 'fas fa-car-alt');

-- Insert sample vehicles
INSERT INTO vehicles (type_id, name, plate_number, description, price_per_day, status, image) VALUES
(1, 'Toyota Camry', 'B 1234 ABC', 'Comfortable sedan with excellent fuel efficiency', 500000, 'available', 'camry.jpg'),
(2, 'Honda CR-V', 'B 2345 DEF', 'Spacious SUV with modern features', 700000, 'available', 'crv.jpg'),
(3, 'Toyota Alphard', 'B 3456 GHI', 'Luxurious minivan perfect for family trips', 1000000, 'available', 'alphard.jpg'),
(4, 'Mazda MX-5', 'B 4567 JKL', 'Sporty convertible for an exciting drive', 800000, 'available', 'mx5.jpg'),
(5, 'Mercedes-Benz S-Class', 'B 5678 MNO', 'Premium luxury sedan with advanced features', 1500000, 'available', 'sclass.jpg');

-- Insert sample drivers
INSERT INTO drivers (name, phone, license_number, status) VALUES
('John Doe', '081234567891', 'SIM-123456', 'available'),
('Jane Smith', '081234567892', 'SIM-234567', 'available'),
('Mike Johnson', '081234567893', 'SIM-345678', 'available');

-- Insert sample users
INSERT INTO users (name, email, password, phone, role) VALUES
('John Smith', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567891', 'user'),
('Sarah Johnson', 'sarah@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567892', 'user'),
('Michael Brown', 'michael@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567893', 'user');

-- Insert more sample vehicles
INSERT INTO vehicles (type_id, name, plate_number, description, price_per_day, status, image) VALUES
(1, 'Honda City', 'B 6789 PQR', 'Efficient sedan perfect for daily commute', 400000, 'available', 'city.jpg'),
(1, 'Toyota Corolla', 'B 7890 STU', 'Reliable sedan with great fuel economy', 450000, 'available', 'corolla.jpg'),
(2, 'Toyota Fortuner', 'B 8901 VWX', 'Powerful SUV for adventurous trips', 800000, 'available', 'fortuner.jpg'),
(2, 'Mitsubishi Pajero', 'B 9012 YZ', 'Rugged SUV for off-road adventures', 900000, 'available', 'pajero.jpg'),
(3, 'Toyota Hiace', 'B 0123 ABC', 'Versatile van for group transportation', 750000, 'available', 'hiace.jpg'),
(3, 'Mercedes-Benz Sprinter', 'B 1234 DEF', 'Premium van for executive transport', 1200000, 'available', 'sprinter.jpg'),
(4, 'Porsche 911', 'B 2345 GHI', 'Iconic sports car with unmatched performance', 2000000, 'available', '911.jpg'),
(4, 'Ferrari F8', 'B 3456 JKL', 'Exotic supercar for the ultimate driving experience', 3000000, 'available', 'f8.jpg'),
(5, 'BMW 7 Series', 'B 4567 MNO', 'Luxury sedan with cutting-edge technology', 1800000, 'available', '7series.jpg'),
(5, 'Audi A8', 'B 5678 PQR', 'Premium sedan with quattro all-wheel drive', 1700000, 'available', 'a8.jpg');

-- Insert more sample drivers
INSERT INTO drivers (name, phone, license_number, status) VALUES
('David Wilson', '081234567894', 'SIM-456789', 'available'),
('Lisa Anderson', '081234567895', 'SIM-567890', 'available'),
('Robert Taylor', '081234567896', 'SIM-678901', 'available'),
('Emily Martinez', '081234567897', 'SIM-789012', 'available'),
('James Thompson', '081234567898', 'SIM-890123', 'available');

-- Insert sample rental orders
INSERT INTO rental_orders (user_id, vehicle_id, driver_id, start_date, end_date, total_price, status, payment_status, payment_method) VALUES
(2, 1, 1, '2024-03-01 08:00:00', '2024-03-03 17:00:00', 1500000, 'completed', 'paid', 'transfer'),
(2, 3, 2, '2024-03-05 09:00:00', '2024-03-07 18:00:00', 3000000, 'completed', 'paid', 'credit_card'),
(3, 2, 3, '2024-03-10 10:00:00', '2024-03-12 19:00:00', 2100000, 'ongoing', 'paid', 'cash'),
(4, 4, 4, '2024-03-15 11:00:00', '2024-03-17 20:00:00', 2400000, 'pending', 'pending', 'transfer');

-- Insert sample order notes
INSERT INTO order_notes (order_id, admin_id, note, note_type, is_private) VALUES
(1, 1, 'Customer requested early pickup', 'general', false),
(1, 1, 'Vehicle returned with minor scratches', 'vehicle', true),
(2, 1, 'Customer paid in full', 'payment', false),
(3, 1, 'Driver assigned: John Doe', 'driver', false),
(4, 1, 'Waiting for payment confirmation', 'payment', true);

-- Insert sample contact messages
INSERT INTO contact_messages (name, email, subject, message, is_read) VALUES
('Alice Cooper', 'alice@example.com', 'Vehicle Availability', 'I would like to know if the Toyota Camry is available next week.', false),
('Bob Wilson', 'bob@example.com', 'Pricing Inquiry', 'What are your rates for long-term rental?', false),
('Carol Davis', 'carol@example.com', 'Driver Request', 'Do you provide drivers for all vehicles?', true); 