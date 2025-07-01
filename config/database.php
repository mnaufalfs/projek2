<?php

// Mengambil nilai dari environment variables
// Jika environment variable tidak ditemukan, gunakan nilai default (untuk pengembangan lokal)
$host = getenv('DB_HOST') ?: 'localhost'; // Akan menjadi 'db' di lingkungan Docker
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'rental_kendaraan'; // Perhatikan spasi di nama database

// Create connection
$conn = new mysqli(hostname: $host, username: $username, password: $password, database: $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset(charset: "utf8");

// Export connection to global variable
global $db;
$db = $conn;

?>