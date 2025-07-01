<?php
require_once '../config/auth.php';
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get filter parameters
$where_conditions = [];
$params = [];
$types = '';

if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where_conditions[] = "(ro.id LIKE ? OR u.name LIKE ? OR v.name LIKE ? OR v.plate_number LIKE ?)";
    $params = array_merge($params, [$search, $search, $search, $search]);
    $types .= 'ssss';
}

if (!empty($_GET['status'])) {
    $where_conditions[] = "ro.status = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}

if (!empty($_GET['start_date'])) {
    $where_conditions[] = "ro.start_date >= ?";
    $params[] = $_GET['start_date'];
    $types .= 's';
}

if (!empty($_GET['end_date'])) {
    $where_conditions[] = "ro.end_date <= ?";
    $params[] = $_GET['end_date'];
    $types .= 's';
}

if (!empty($_GET['vehicle_type'])) {
    $where_conditions[] = "vt.id = ?";
    $params[] = $_GET['vehicle_type'];
    $types .= 'i';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get orders data
$query = "
    SELECT 
        ro.id,
        ro.created_at,
        u.name as customer_name,
        u.phone as customer_phone,
        v.name as vehicle_name,
        v.plate_number,
        vt.name as vehicle_type,
        d.name as driver_name,
        ro.start_date,
        ro.end_date,
        ro.total_price,
        ro.status
    FROM rental_orders ro
    JOIN users u ON ro.user_id = u.id
    JOIN vehicles v ON ro.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    LEFT JOIN drivers d ON ro.driver_id = d.id
    $where_clause
    ORDER BY ro.created_at DESC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="orders_report_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, [
    'Order ID',
    'Date',
    'Customer',
    'Phone',
    'Vehicle Type',
    'Vehicle',
    'Plate Number',
    'Driver',
    'Start Date',
    'End Date',
    'Total Price',
    'Status'
]);

// Add data
foreach ($orders as $order) {
    fputcsv($output, [
        generateReceiptNumber('booking', $order['id']),
        date('d M Y H:i', strtotime($order['created_at'])),
        $order['customer_name'],
        $order['customer_phone'],
        $order['vehicle_type'],
        $order['vehicle_name'],
        $order['plate_number'],
        $order['driver_name'] ?? 'No driver',
        date('d M Y H:i', strtotime($order['start_date'])),
        date('d M Y H:i', strtotime($order['end_date'])),
        $order['total_price'],
        ucfirst($order['status'])
    ]);
}

fclose($output);
exit;
?> 