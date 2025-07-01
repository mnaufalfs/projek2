<?php
require_once '../config/auth.php';
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$type = isset($_GET['type']) ? $_GET['type'] : '';

if (!in_array($type, ['booking', 'return'])) {
    setFlashMessage('error', 'Invalid receipt type');
    redirect('my_orders.php');
}

// Get order details
$query = "
    SELECT ro.*, 
           u.name as customer_name, u.phone as customer_phone,
           v.name as vehicle_name, v.plate_number,
           vt.name as vehicle_type,
           d.name as driver_name, d.license_number
    FROM rental_orders ro
    JOIN users u ON ro.user_id = u.id
    JOIN vehicles v ON ro.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    LEFT JOIN drivers d ON ro.driver_id = d.id
    WHERE ro.id = ? AND ro.user_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    setFlashMessage('error', 'Order not found');
    redirect('my_orders.php');
}

// Generate receipt number
$receipt_number = generateReceiptNumber($type, $order['id']);

// Create PDF
require_once '../vendor/autoload.php';

class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 20);
        $this->Cell(0, 15, 'Rental Vehicle System', 0, false, 'C', 0, '', 0, false, 'M', 'M');
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Rental Vehicle System');
$pdf->SetTitle($type === 'booking' ? 'Booking Receipt' : 'Return Receipt');

$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Add content
$pdf->Cell(0, 10, $type === 'booking' ? 'Booking Receipt' : 'Return Receipt', 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(40, 7, 'Receipt Number:', 0);
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 7, $receipt_number, 0, 1);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(40, 7, 'Date:', 0);
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 7, date('d M Y H:i', strtotime($order['created_at'])), 0, 1);

$pdf->Ln(5);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 7, 'Customer Details', 0, 1);
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(40, 7, 'Name:', 0);
$pdf->Cell(0, 7, $order['customer_name'], 0, 1);
$pdf->Cell(40, 7, 'Phone:', 0);
$pdf->Cell(0, 7, $order['customer_phone'], 0, 1);

$pdf->Ln(5);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 7, 'Vehicle Details', 0, 1);
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(40, 7, 'Type:', 0);
$pdf->Cell(0, 7, $order['vehicle_type'], 0, 1);
$pdf->Cell(40, 7, 'Name:', 0);
$pdf->Cell(0, 7, $order['vehicle_name'], 0, 1);
$pdf->Cell(40, 7, 'Plate Number:', 0);
$pdf->Cell(0, 7, $order['plate_number'], 0, 1);

if ($order['driver_id']) {
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Driver Details', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(40, 7, 'Name:', 0);
    $pdf->Cell(0, 7, $order['driver_name'], 0, 1);
    $pdf->Cell(40, 7, 'License:', 0);
    $pdf->Cell(0, 7, $order['license_number'], 0, 1);
}

$pdf->Ln(5);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 7, 'Rental Details', 0, 1);
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(40, 7, 'Start Date:', 0);
$pdf->Cell(0, 7, date('d M Y H:i', strtotime($order['start_date'])), 0, 1);
$pdf->Cell(40, 7, 'End Date:', 0);
$pdf->Cell(0, 7, date('d M Y H:i', strtotime($order['end_date'])), 0, 1);
$pdf->Cell(40, 7, 'Duration:', 0);
$pdf->Cell(0, 7, ceil((strtotime($order['end_date']) - strtotime($order['start_date'])) / 3600) . ' hours', 0, 1);
$pdf->Cell(40, 7, 'Lokasi Sewa:', 0);
$pdf->Cell(0, 7, $order['is_out_of_town'] ? 'Luar Kota' : 'Dalam Kota', 0, 1);
if ($order['is_out_of_town']) {
    $base_price = $order['total_price'] / 1.2;
    $surcharge = $order['total_price'] - $base_price;
    $pdf->Cell(40, 7, 'Biaya Luar Kota:', 0);
    $pdf->Cell(0, 7, formatPrice($surcharge), 0, 1);
}
$pdf->Cell(40, 7, 'Total Price:', 0);
$pdf->Cell(0, 7, formatPrice($order['total_price']), 0, 1);

$pdf->Ln(10);

$pdf->SetFont('helvetica', 'I', 10);
$pdf->Cell(0, 7, 'This is a computer-generated receipt. No signature is required.', 0, 1, 'C');

// Output PDF
if (ob_get_length()) ob_end_clean();
$pdf->Output($receipt_number . '.pdf', 'D');
?> 