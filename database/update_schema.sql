-- Update vehicles table to rename type_id to vehicle_type_id
ALTER TABLE vehicles CHANGE COLUMN type_id vehicle_type_id INT NOT NULL;

-- Update foreign key constraint
ALTER TABLE vehicles DROP FOREIGN KEY vehicles_ibfk_1;
ALTER TABLE vehicles ADD CONSTRAINT vehicles_ibfk_1 FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_types(id) ON DELETE CASCADE;

-- Add price_per_hour column to vehicles table
ALTER TABLE vehicles ADD COLUMN price_per_hour DECIMAL(10,2) DEFAULT 0.00 AFTER plate_number;

-- Update existing vehicles with default prices based on vehicle type
UPDATE vehicles v
JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
SET v.price_per_hour = 
    CASE 
        WHEN vt.name = 'Motor' THEN 50000
        WHEN vt.name = 'Mobil' THEN 150000
        WHEN vt.name = 'Bus' THEN 300000
        ELSE 100000
    END;

-- Add rental_returns table
CREATE TABLE IF NOT EXISTS rental_returns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    return_date DATETIME NOT NULL,
    condition_notes TEXT,
    additional_charges DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES rental_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes
CREATE INDEX idx_rental_returns_order_id ON rental_returns(order_id);
CREATE INDEX idx_rental_returns_return_date ON rental_returns(return_date); 