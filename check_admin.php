<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Check admin user
$stmt = $db->query("SELECT * FROM users WHERE role = 'admin'");
$admin = $stmt->fetch_assoc();

if ($admin) {
    echo "Admin found:\n";
    echo "ID: " . $admin['id'] . "\n";
    echo "Name: " . $admin['name'] . "\n";
    echo "Email: " . $admin['email'] . "\n";
    echo "Role: " . $admin['role'] . "\n";
    echo "Password hash: " . $admin['password'] . "\n";
} else {
    echo "No admin user found!\n";
    
    // Create admin user
    $name = 'Admin';
    $email = 'admin@rental.com';
    $password = password_hash('password', PASSWORD_DEFAULT);
    $phone = '081234567890';
    $role = 'admin';
    
    $stmt = $db->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $password, $phone, $role);
    
    if ($stmt->execute()) {
        echo "Admin user created successfully!\n";
        echo "Email: admin@rental.com\n";
        echo "Password: password\n";
    } else {
        echo "Failed to create admin user: " . $stmt->error . "\n";
    }
}
?> 