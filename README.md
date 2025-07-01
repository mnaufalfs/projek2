# Vehicle Rental System

A web-based vehicle rental system built with PHP and MySQL.

## Features

### User Features
- User registration and login
- Browse available vehicles
- Filter vehicles by type, price, and search
- View vehicle details
- Rent vehicles with optional driver
- View rental history
- Download booking and return receipts
- Update profile information

### Admin Features
- Manage vehicles (add, edit, delete)
- Manage drivers (add, edit, delete)
- Manage rental orders
- View statistics and reports
- Export data to Excel/PDF

## Requirements

- PHP >= 7.4
- MySQL >= 5.7
- Apache/Nginx web server
- Composer

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/rental_kendaraan.git
cd rental_kendaraan
```

2. Install dependencies:
```bash
composer install
```

3. Create database and import schema:
```bash
mysql -u root -p < database/schema.sql
```

4. Configure database connection:
- Copy `config/database.example.php` to `config/database.php`
- Update database credentials in `config/database.php`

5. Configure web server:
- Set document root to project directory
- Enable mod_rewrite for Apache
- Set proper permissions for uploads directory

6. Default admin credentials:
- Email: admin@example.com
- Password: password

## Directory Structure

```
rental_kendaraan/
├── admin/              # Admin panel files
├── assets/            # Static assets (CSS, JS, images)
├── config/            # Configuration files
├── database/          # Database schema and migrations
├── includes/          # Common includes
├── uploads/           # Uploaded files
├── user/              # User panel files
├── vendor/            # Composer dependencies
├── .htaccess          # Apache configuration
├── composer.json      # Composer configuration
└── README.md          # Documentation
```

## Security

- Password hashing using PHP's password_hash()
- Prepared statements for all database queries
- Input validation and sanitization
- XSS protection
- CSRF protection
- Session security

## License

This project is licensed under the MIT License - see the LICENSE file for details. 