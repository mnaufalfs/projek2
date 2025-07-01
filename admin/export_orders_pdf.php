<?php
require_once '../config/auth.php';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

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
        u.name as customer_name,
        v.name as vehicle_name,
        v.plate_number,
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

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$dompdf = new Dompdf($options);

$html = '<html><head><style>
    body { font-family: Arial, sans-serif; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f5f5f5; }
    h1 { text-align: center; }
</style></head><body>';

$html .= '<h1>Orders Report</h1>';
$html .= '<p>Generated on: ' . date('d M Y H:i') . '</p>';
$html .= '<table>
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Vehicle</th>
            <th>Driver</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Total</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>';

foreach ($orders as $order) {
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars(generateReceiptNumber('booking', $order['id'])) . '</td>';
    $html .= '<td>' . htmlspecialchars($order['customer_name']) . '</td>';
    $html .= '<td>' . htmlspecialchars($order['vehicle_name']) . ' (' . htmlspecialchars($order['plate_number']) . ')</td>';
    $html .= '<td>' . ($order['driver_name'] ? htmlspecialchars($order['driver_name']) : 'No driver') . '</td>';
    $html .= '<td>' . date('d M Y H:i', strtotime($order['start_date'])) . '</td>';
    $html .= '<td>' . date('d M Y H:i', strtotime($order['end_date'])) . '</td>';
    $html .= '<td>' . htmlspecialchars(formatPrice($order['total_price'])) . '</td>';
    $html .= '<td>' . ucfirst(htmlspecialchars($order['status'])) . '</td>';
    $html .= '</tr>';
}

$html .= '</tbody></table></body></html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment;filename="orders_report_' . date('Y-m-d') . '.pdf"');
echo $dompdf->output();
exit;
?> 