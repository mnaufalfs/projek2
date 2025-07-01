USE rental_vehicle_system;
 
ALTER TABLE rental_returns 
ADD COLUMN return_payment_method ENUM('cash', 'transfer') DEFAULT NULL AFTER total_additional_fee,
ADD COLUMN return_payment_proof VARCHAR(255) DEFAULT NULL AFTER return_payment_method; 