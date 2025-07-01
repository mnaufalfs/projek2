<?php
// Application configuration
define('APP_NAME', 'Vehicle Rental System');
define('APP_URL', 'http://localhost/rental_kendaraan_new');
define('APP_VERSION', '1.0.0');

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 1);
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone
date_default_timezone_set('Asia/Jakarta');

// File upload configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png']);

// Color Scheme
define('PRIMARY_COLOR', '#0288D1');    // Biru laut
define('SECONDARY_COLOR', '#424242');  // Abu gelap
define('ACCENT_COLOR', '#FFFFFF');     // Putih

// Rental Settings
define('MIN_RENTAL_HOURS', 3);
define('OUT_OF_TOWN_SURCHARGE', 0.20); // 20% surcharge for out of town rentals

// Base URL
define('BASE_URL', 'http://localhost/rental_kendaraan_new');

// Helper functions
function formatPrice($price) {
    if ($price === null) {
        return 'Rp 0';
    }
    return 'Rp ' . number_format((float)$price, 0, ',', '.');
}

function formatDate($date) {
    return date('d F Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d F Y H:i', strtotime($datetime));
}

function generateReceiptNumber($type, $id, $created_at = null) {
    // $type: 'booking' atau 'return' (bisa diabaikan jika tidak dipakai)
    $date = $created_at ? date('Ymd', strtotime($created_at)) : date('Ymd');
    return 'RENT-' . $date . '-' . str_pad($id, 5, '0', STR_PAD_LEFT);
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function validateDateTime($datetime) {
    $d = DateTime::createFromFormat('Y-m-d H:i', $datetime);
    return $d && $d->format('Y-m-d H:i') === $datetime;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^[0-9]{10,15}$/', $phone);
}

function validatePassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
}

function validateFile($file) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return false;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    $allowed_types = [
        'image/jpeg',
        'image/png'
    ];

    if (!in_array($mime_type, $allowed_types)) {
        return false;
    }

    return true;
}

function uploadFile($file, $directory = '') {
    if (!validateFile($file)) {
        return false;
    }

    $upload_dir = UPLOAD_DIR . $directory;
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $upload_dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return false;
    }

    return $directory . '/' . $filename;
}

function deleteFile($filepath) {
    $fullpath = UPLOAD_DIR . $filepath;
    if (file_exists($fullpath)) {
        return unlink($fullpath);
    }
    return false;
}

function getVehicleTypes() {
    global $db;
    $stmt = $db->query("SELECT * FROM vehicle_types ORDER BY name");
    return $stmt->fetchAll();
}

function getVehicleStatus() {
    return [
        'available' => 'Available',
        'rented' => 'Rented',
        'maintenance' => 'Maintenance'
    ];
}

function getOrderStatus() {
    return [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'ongoing' => 'Ongoing',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled'
    ];
}

function getPaymentStatus() {
    return [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'refunded' => 'Refunded'
    ];
}

function getPaymentMethods() {
    return [
        'cash' => 'Cash',
        'transfer' => 'Bank Transfer',
        'credit_card' => 'Credit Card'
    ];
}

function getNoteTypes() {
    return [
        'general' => 'General',
        'issue' => 'Issue',
        'payment' => 'Payment',
        'vehicle' => 'Vehicle',
        'driver' => 'Driver',
        'customer' => 'Customer'
    ];
}

// Check if user is logged in for protected pages
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please login first');
        redirect('login.php');
    }
}

// Check if user is admin for admin pages
function requireAdmin() {
    if (!isAdmin()) {
        setFlashMessage('error', 'Access denied');
        redirect('index.php');
    }
}
?> 