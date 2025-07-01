<?php
require_once 'config/config.php';
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['type']) || !isset($_GET['id'])) {
    redirect('orders.php');
}

$type = $_GET['type'];
$id = (int)$_GET['id'];

if ($type === 'booking') {
    // Get booking details
    $stmt = $conn->prepare("
        SELECT ro.*, v.name as vehicle_name, v.plate_number, d.name as driver_name,
               vt.name as vehicle_type, u.name as user_name, u.phone as user_phone
        FROM rental_orders ro
        JOIN vehicles v ON ro.vehicle_id = v.id
        JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
        JOIN drivers d ON ro.driver_id = d.id
        JOIN users u ON ro.user_id = u.id
        WHERE ro.id = ? AND ro.user_id = ?
    ");
    $stmt->bind_param("ii", $id, $_SESSION['user_id']);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        redirect('orders.php');
    }

    // Generate PDF
    require_once 'vendor/autoload.php';
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor(APP_NAME);
    $pdf->SetTitle('Booking Receipt');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 12);

    // Company header
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, APP_NAME, 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Booking Receipt', 0, 1, 'C');
    $pdf->Ln(10);

    // Receipt details
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(40, 10, 'Receipt No:', 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, generateReceiptNumber('booking', $order['id']), 0, 1);

    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(40, 10, 'Date:', 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, date('d M Y H:i', strtotime($order['created_at'])), 0, 1);

    $pdf->Ln(5);

    // Customer details
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Customer Details:', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(40, 10, 'Name:', 0);
    $pdf->Cell(0, 10, $order['user_name'], 0, 1);
    $pdf->Cell(40, 10, 'Phone:', 0);
    $pdf->Cell(0, 10, $order['user_phone'], 0, 1);

    $pdf->Ln(5);

    // Rental details
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Rental Details:', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(40, 10, 'Vehicle:', 0);
    $pdf->Cell(0, 10, $order['vehicle_type'] . ' - ' . $order['vehicle_name'] . ' (' . $order['plate_number'] . ')', 0, 1);
    $pdf->Cell(40, 10, 'Driver:', 0);
    $pdf->Cell(0, 10, $order['driver_name'], 0, 1);
    $pdf->Cell(40, 10, 'Start Date:', 0);
    $pdf->Cell(0, 10, date('d M Y H:i', strtotime($order['start_date'])), 0, 1);
    $pdf->Cell(40, 10, 'End Date:', 0);
    $pdf->Cell(0, 10, date('d M Y H:i', strtotime($order['end_date'])), 0, 1);
    $pdf->Cell(40, 10, 'Rental Type:', 0);
    $pdf->Cell(0, 10, ucfirst($order['rental_type']), 0, 1);
    $pdf->Cell(40, 10, 'Out of Town:', 0);
    $pdf->Cell(0, 10, $order['is_out_of_town'] ? 'Yes' : 'No', 0, 1);

    $pdf->Ln(5);

    // Payment details
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Payment Details:', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(40, 10, 'Method:', 0);
    $pdf->Cell(0, 10, ucfirst($order['payment_method']), 0, 1);
    $pdf->Cell(40, 10, 'Status:', 0);
    $pdf->Cell(0, 10, ucfirst($order['payment_status']), 0, 1);

    $pdf->Ln(5);

    // Price breakdown
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Price Breakdown:', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(40, 10, 'Base Price:', 0);
    $pdf->Cell(0, 10, formatPrice($order['total_price'] - $order['delivery_fee'] - $order['pickup_fee']), 0, 1);
    if ($order['delivery_fee'] > 0) {
        $pdf->Cell(40, 10, 'Delivery Fee:', 0);
        $pdf->Cell(0, 10, formatPrice($order['delivery_fee']), 0, 1);
    }
    if ($order['pickup_fee'] > 0) {
        $pdf->Cell(40, 10, 'Pickup Fee:', 0);
        $pdf->Cell(0, 10, formatPrice($order['pickup_fee']), 0, 1);
    }
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(40, 10, 'Total:', 0);
    $pdf->Cell(0, 10, formatPrice($order['total_price']), 0, 1);

    // Output PDF
    $pdf->Output('booking_receipt_' . $order['id'] . '.pdf', 'D');
} else {
    // Get return details
    $stmt = $conn->prepare("
        SELECT rr.*, ro.*, v.name as vehicle_name, v.plate_number, d.name as driver_name,
               vt.name as vehicle_type, u.name as user_name, u.phone as user_phone
        FROM rental_returns rr
        JOIN rental_orders ro ON rr.rental_order_id = ro.id
        JOIN vehicles v ON ro.vehicle_id = v.id
        JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
        JOIN drivers d ON ro.driver_id = d.id
        JOIN users u ON ro.user_id = u.id
        WHERE rr.id = ? AND ro.user_id = ?
    ");
    $stmt->bind_param("ii", $id, $_SESSION['user_id']);
    $stmt->execute();
    $return = $stmt->get_result()->fetch_assoc();

    if (!$return) {
        redirect('orders.php');
    }

    // Generate PDF
    require_once 'vendor/autoload.php';
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor(APP_NAME);
    $pdf->SetTitle('Return Receipt');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 12);

    // Company header
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, APP_NAME, 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Return Receipt', 0, 1, 'C');
    $pdf->Ln(10);

    // Receipt details
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(40, 10, 'Receipt No:', 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, generateReceiptNumber('return', $return['id']), 0, 1);

    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(40, 10, 'Date:', 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, date('d M Y H:i', strtotime($return['created_at'])), 0, 1);

    $pdf->Ln(5);

    // Customer details
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Customer Details:', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(40, 10, 'Name:', 0);
    $pdf->Cell(0, 10, $return['user_name'], 0, 1);
    $pdf->Cell(40, 10, 'Phone:', 0);
    $pdf->Cell(0, 10, $return['user_phone'], 0, 1);

    $pdf->Ln(5);

    // Vehicle details
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Vehicle Details:', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(40, 10, 'Vehicle:', 0);
    $pdf->Cell(0, 10, $return['vehicle_type'] . ' - ' . $return['vehicle_name'] . ' (' . $return['plate_number'] . ')', 0, 1);
    $pdf->Cell(40, 10, 'Driver:', 0);
    $pdf->Cell(0, 10, $return['driver_name'], 0, 1);

    $pdf->Ln(5);

    // Return details
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Return Details:', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(40, 10, 'Return Date:', 0);
    $pdf->Cell(0, 10, date('d M Y H:i', strtotime($return['return_date'])), 0, 1);
    $pdf->Cell(40, 10, 'Late Hours:', 0);
    $pdf->Cell(0, 10, $return['late_hours'] . ' hours', 0, 1);

    $pdf->Ln(5);

    // Additional charges
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Additional Charges:', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    if ($return['late_hours'] > 0) {
        $pdf->Cell(40, 10, 'Late Fee:', 0);
        $pdf->Cell(0, 10, formatPrice($return['late_fee']), 0, 1);
    }
    if ($return['damage_fee'] > 0) {
        $pdf->Cell(40, 10, 'Damage Fee:', 0);
        $pdf->Cell(0, 10, formatPrice($return['damage_fee']), 0, 1);
    }
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(40, 10, 'Total Additional:', 0);
    $pdf->Cell(0, 10, formatPrice($return['total_additional_fee']), 0, 1);

    // Output PDF
    $pdf->Output('return_receipt_' . $return['id'] . '.pdf', 'D');
}
?> 