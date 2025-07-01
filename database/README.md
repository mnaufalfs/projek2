# Database Documentation

This directory contains the database files for the Vehicle Rental System.

## Files

- `schema.sql`: Database schema and initial data
- `seeder.sql`: Additional sample data for testing
- `backup.sql`: Database backup and restore instructions

## Database Structure

### Tables

1. **users**
   - Stores user information (admin and regular users)
   - Fields: id, name, email, password, phone, role, created_at, updated_at

2. **vehicle_types**
   - Stores vehicle categories
   - Fields: id, name, description, icon, created_at, updated_at

3. **vehicles**
   - Stores vehicle information
   - Fields: id, type_id, name, plate_number, description, price_per_day, status, image, created_at, updated_at
   - Foreign key: type_id references vehicle_types(id)

4. **drivers**
   - Stores driver information
   - Fields: id, name, phone, license_number, status, created_at, updated_at

5. **rental_orders**
   - Stores rental transaction information
   - Fields: id, user_id, vehicle_id, driver_id, start_date, end_date, total_price, status, payment_status, payment_method, created_at, updated_at
   - Foreign keys: user_id references users(id), vehicle_id references vehicles(id), driver_id references drivers(id)

6. **order_notes**
   - Stores notes for rental orders
   - Fields: id, order_id, admin_id, note, note_type, is_private, created_at
   - Foreign keys: order_id references rental_orders(id), admin_id references users(id)

7. **contact_messages**
   - Stores customer contact messages
   - Fields: id, name, email, subject, message, is_read, created_at

## Enums

### User Roles
- admin
- user

### Vehicle Status
- available
- rented
- maintenance

### Driver Status
- available
- assigned
- off

### Order Status
- pending
- confirmed
- ongoing
- completed
- cancelled

### Payment Status
- pending
- paid
- refunded

### Payment Methods
- cash
- transfer
- credit_card

### Note Types
- general
- issue
- payment
- vehicle
- driver
- customer

## Backup and Restore

### Backup
To create a backup of the database, run:
```bash
mysqldump -u [username] -p rental_kendaraan > backup.sql
```

### Restore
To restore the database from a backup, run:
```bash
mysql -u [username] -p rental_kendaraan < backup.sql
```

## Security

1. **Password Storage**
   - Passwords are hashed using PHP's password_hash() function
   - Default password for all sample users: 'password'

2. **Data Validation**
   - Email addresses must be unique
   - Phone numbers must be in valid format
   - License numbers must be unique
   - Plate numbers must be unique

3. **Foreign Key Constraints**
   - CASCADE delete for vehicle_types and vehicles
   - SET NULL for drivers in rental_orders
   - CASCADE delete for rental_orders and order_notes

## Sample Data

The database includes sample data for testing:

1. **Users**
   - Admin user (admin@example.com)
   - 3 regular users

2. **Vehicle Types**
   - 5 types (Sedan, SUV, Minivan, Sports Car, Luxury Car)

3. **Vehicles**
   - 15 vehicles across all types
   - Various prices and statuses

4. **Drivers**
   - 8 drivers with different statuses

5. **Rental Orders**
   - 4 orders with different statuses and payment methods

6. **Order Notes**
   - 5 notes with different types and privacy settings

7. **Contact Messages**
   - 3 messages with different read statuses

## Maintenance

1. **Regular Backups**
   - Create daily backups
   - Store backups in a secure location
   - Test restore process periodically

2. **Data Cleanup**
   - Archive old rental orders
   - Clean up unused vehicle images
   - Remove expired contact messages

3. **Performance**
   - Index frequently queried fields
   - Monitor query performance
   - Optimize table structure as needed 