-- Insert sample users
INSERT INTO users (name, email, password, phone, role) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567891', 'user'),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567892', 'user'),
('Mike Johnson', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567893', 'user');

-- Insert sample vehicles
INSERT INTO vehicles (vehicle_type_id, name, plate_number, description, price_per_day, price_per_hour, status, image) VALUES
(1, 'Toyota Camry', 'B 1234 ABC', 'Comfortable sedan with excellent fuel efficiency', 500000, 50000, 'available', 'camry.jpg'),
(2, 'Toyota Innova', 'B 2345 DEF', 'Spacious MPV perfect for family trips', 700000, 70000, 'available', 'innova.jpg'),
(3, 'Suzuki Carry', 'B 3456 GHI', 'Efficient box truck for deliveries', 400000, 40000, 'available', 'carry.jpg'),
(4, 'Mitsubishi Colt Diesel', 'B 4567 JKL', 'Powerful open truck for heavy loads', 600000, 60000, 'available', 'colt.jpg'),
(5, 'Toyota Hiace', 'B 5678 MNO', 'Comfortable minibus for group travel', 800000, 80000, 'available', 'hiace.jpg');

-- Insert sample drivers
INSERT INTO drivers (name, phone, license_number, status) VALUES
('Budi Santoso', '081234567891', 'SIM-123456', 'available'),
('Ahmad Rizki', '081234567892', 'SIM-234567', 'available'),
('Dewi Lestari', '081234567893', 'SIM-345678', 'available'),
('Rudi Hartono', '081234567894', 'SIM-456789', 'available'),
('Siti Aminah', '081234567895', 'SIM-567890', 'available');

-- Insert sample rental orders
INSERT INTO rental_orders (
    user_id, vehicle_id, driver_id, start_date, end_date, 
    rental_type, is_out_of_town, total_price, status, 
    payment_status, payment_method, delivery_fee, pickup_fee
) VALUES
(2, 1, 1, '2024-03-01 08:00:00', '2024-03-03 17:00:00', 'daily', false, 1500000, 'completed', 'paid', 'transfer', 0, 0),
(2, 3, 2, '2024-03-05 09:00:00', '2024-03-07 18:00:00', 'daily', true, 3000000, 'completed', 'paid', 'credit_card', 100000, 100000),
(3, 2, 3, '2024-03-10 10:00:00', '2024-03-12 19:00:00', 'daily', false, 2100000, 'ongoing', 'paid', 'cash', 0, 0),
(4, 4, 4, '2024-03-15 11:00:00', '2024-03-17 20:00:00', 'daily', true, 2400000, 'pending', 'pending', 'transfer', 150000, 150000);

-- Insert sample rental returns
INSERT INTO rental_returns (
    rental_order_id, return_date, late_hours, late_fee, 
    damage_fee, total_additional_fee, notes, status
) VALUES
(1, '2024-03-03 17:30:00', 0.5, 25000, 0, 25000, 'Returned in good condition', 'completed'),
(2, '2024-03-07 18:15:00', 0.25, 17500, 50000, 67500, 'Minor scratch on rear bumper', 'completed');

-- Insert sample order notes
INSERT INTO order_notes (order_id, admin_id, note, note_type, is_private) VALUES
(1, 1, 'Customer requested early pickup', 'general', false),
(1, 1, 'Vehicle returned with minor scratches', 'vehicle', true),
(2, 1, 'Customer paid in full', 'payment', false),
(3, 1, 'Driver assigned: Budi Santoso', 'driver', false),
(4, 1, 'Waiting for payment confirmation', 'payment', true);

-- Insert sample contact messages
INSERT INTO contact_messages (name, email, subject, message, is_read) VALUES
('Alice Cooper', 'alice@example.com', 'Vehicle Availability', 'I would like to know if the Toyota Camry is available next week.', false),
('Bob Wilson', 'bob@example.com', 'Pricing Inquiry', 'What are your rates for long-term rental?', false),
('Carol Davis', 'carol@example.com', 'Driver Request', 'Do you provide drivers for all vehicles?', true); 