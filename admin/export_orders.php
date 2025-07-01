<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../config/auth.php';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Ambil filter dari GET
$where_conditions = [];
$params = [];
$types = '';
$format = $_GET['format'] ?? 'excel';

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

// Data detail order
$query = "
    SELECT ro.*, u.name as customer_name, v.name as vehicle_name, v.plate_number, vt.name as vehicle_type,
           d.name as driver_name, rr.return_date, rr.late_fee, rr.pickup_fee, rr.total_additional_fee
    FROM rental_orders ro
    JOIN users u ON ro.user_id = u.id
    JOIN vehicles v ON ro.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    LEFT JOIN drivers d ON ro.driver_id = d.id
    LEFT JOIN rental_returns rr ON ro.id = rr.order_id
    $where_clause
    ORDER BY ro.created_at DESC
";
$detail = $conn->prepare($query);
if (!empty($params)) {
    $detail->bind_param($types, ...$params);
}
$detail->execute();
$orders = $detail->get_result()->fetch_all(MYSQLI_ASSOC);

if ($format === 'excel') {
    if (ob_get_length()) ob_end_clean(); // Pastikan tidak ada output buffer
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="orders_report.xlsx"');
    header('Cache-Control: max-age=0');
    try {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set header
        $sheet->setCellValue('A1', 'Order ID');
        $sheet->setCellValue('B1', 'Date');
        $sheet->setCellValue('C1', 'Customer');
        $sheet->setCellValue('D1', 'Vehicle');
        $sheet->setCellValue('E1', 'Plate');
        $sheet->setCellValue('F1', 'Driver');
        $sheet->setCellValue('G1', 'Status');
        $sheet->setCellValue('H1', 'Booking Fee');
        $sheet->setCellValue('I1', 'Additional Fee');
        $sheet->setCellValue('J1', 'Total Fee');
        
        // Set data
        $row = 2;
        foreach ($orders as $order) {
            $sheet->setCellValue('A'.$row, generateReceiptNumber('booking', $order['id'], $order['created_at']));
            $sheet->setCellValue('B'.$row, date('d M Y H:i', strtotime($order['created_at'])));
            $sheet->setCellValue('C'.$row, $order['customer_name']);
            $sheet->setCellValue('D'.$row, $order['vehicle_type'].' - '.$order['vehicle_name']);
            $sheet->setCellValue('E'.$row, $order['plate_number']);
            $sheet->setCellValue('F'.$row, $order['driver_name'] ?: 'No driver');
            $sheet->setCellValue('G'.$row, ucfirst($order['status']));
            $sheet->setCellValue('H'.$row, $order['total_price']);
            $sheet->setCellValue('I'.$row, ($order['late_fee']??0)+($order['pickup_fee']??0));
            $sheet->setCellValue('J'.$row, $order['total_price']+($order['late_fee']??0)+($order['pickup_fee']??0));
            $row++;
        }
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    } catch (\Throwable $e) {
        file_put_contents(__DIR__ . '/../export_excel_error.log', $e->getMessage() . "\n" . $e->getTraceAsString());
    }
    exit;
} else {
    // Export PDF
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
    $html .= '<table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Vehicle</th>
                <th>Plate</th>
                <th>Driver</th>
                <th>Status</th>
                <th>Booking Fee</th>
                <th>Additional Fee</th>
                <th>Total Fee</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($orders as $order) {
        $html .= '<tr>
            <td>'.generateReceiptNumber('booking', $order['id'], $order['created_at']).'</td>
            <td>'.date('d M Y H:i', strtotime($order['created_at'])).'</td>
            <td>'.htmlspecialchars($order['customer_name']).'</td>
            <td>'.htmlspecialchars($order['vehicle_type']).' - '.htmlspecialchars($order['vehicle_name']).'</td>
            <td>'.htmlspecialchars($order['plate_number']).'</td>
            <td>'.($order['driver_name'] ? htmlspecialchars($order['driver_name']) : 'No driver').'</td>
            <td>'.ucfirst($order['status']).'</td>
            <td>'.formatPrice($order['total_price']).'</td>
            <td>'.formatPrice(($order['late_fee']??0)+($order['pickup_fee']??0)).'</td>
            <td>'.formatPrice($order['total_price']+($order['late_fee']??0)+($order['pickup_fee']??0)).'</td>
        </tr>';
    }
    
    $html .= '</tbody></table></body></html>';
    
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment;filename="orders_report.pdf"');
    
    echo $dompdf->output();
}
?> 