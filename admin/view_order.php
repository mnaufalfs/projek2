<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil detail order dan pengembalian
$stmt = $conn->prepare("
    SELECT ro.*, u.name as customer_name, u.phone as customer_phone,
           v.name as vehicle_name, v.plate_number, v.price_per_hour, v.vehicle_type_id,
           vt.name as vehicle_type,
           d.name as driver_name, d.phone as driver_phone, d.license_number,
           rr.return_date, rr.late_hours, rr.late_fee, rr.pickup_option, rr.pickup_fee, rr.total_additional_fee, rr.return_payment_method, rr.return_payment_proof, rr.status as return_status
    FROM rental_orders ro
    JOIN users u ON ro.user_id = u.id
    JOIN vehicles v ON ro.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    LEFT JOIN drivers d ON ro.driver_id = d.id
    LEFT JOIN rental_returns rr ON ro.id = rr.order_id
    WHERE ro.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    setFlashMessage('error', 'Order not found');
    redirect('orders.php');
}

$content = '<div class="container py-4">
    <h2 class="mb-4">Order Details</h2>';

$content .= '<div class="row mb-4">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">Customer</div>
            <div class="card-body">
                <p><strong>Name:</strong> '.htmlspecialchars($order['customer_name']).'</p>
                <p><strong>Phone:</strong> '.htmlspecialchars($order['customer_phone']).'</p>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">Vehicle</div>
            <div class="card-body">
                <p><strong>Type:</strong> '.htmlspecialchars($order['vehicle_type']).'</p>
                <p><strong>Name:</strong> '.htmlspecialchars($order['vehicle_name']).'</p>
                <p><strong>Plate Number:</strong> '.htmlspecialchars($order['plate_number']).'</p>
                <p><strong>Price/Hour:</strong> '.formatPrice($order['price_per_hour']).'</p>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">Driver</div>
            <div class="card-body">
                <p><strong>Name:</strong> '.($order['driver_name'] ? htmlspecialchars($order['driver_name']) : '<span class="text-muted">No driver</span>').'</p>
                <p><strong>Phone:</strong> '.($order['driver_phone'] ? htmlspecialchars($order['driver_phone']) : '-').'</p>
                <p><strong>License:</strong> '.($order['license_number'] ? htmlspecialchars($order['license_number']) : '-').'</p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">Rental Info</div>
            <div class="card-body">
                <p><strong>Order ID:</strong> '.generateReceiptNumber('booking', $order['id'], $order['created_at']).'</p>
                <p><strong>Status:</strong> <span class="badge bg-'.($order['status'] === 'pending' ? 'warning' : ($order['status'] === 'confirmed' ? 'info' : ($order['status'] === 'ongoing' ? 'primary' : ($order['status'] === 'completed' ? 'success' : 'danger')))).'">'.ucfirst($order['status']).'</span></p>
                <p><strong>Start Date:</strong> '.date('d M Y H:i', strtotime($order['start_date'])).'</p>
                <p><strong>End Date:</strong> '.date('d M Y H:i', strtotime($order['end_date'])).'</p>
                <p><strong>Booking Fee:</strong> '.formatPrice($order['total_price']).'</p>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">Payment</div>
            <div class="card-body">
                <p><strong>Method:</strong> '.ucfirst($order['payment_method']).'</p>
                <p><strong>Status:</strong> '.ucfirst($order['payment_status']).'</p>';
if ($order['payment_method'] === 'transfer' && $order['payment_proof']) {
    $content .= '<p><strong>Proof:</strong><br><img src="../'.$order['payment_proof'].'" class="img-fluid rounded border" style="max-width:200px;"></p>';
}
$content .= '</div></div>';

if ($order['return_date']) {
    $content .= '<div class="card mb-3">
        <div class="card-header bg-success text-white">Return Info</div>
        <div class="card-body">
            <p><strong>Return Date:</strong> '.date('d M Y H:i', strtotime($order['return_date'])).'</p>
            <p><strong>Late Hours:</strong> '.($order['late_hours'] ? $order['late_hours'] : 0).' jam</p>
            <p><strong>Late Fee:</strong> '.formatPrice($order['late_fee']).'</p>
            <p><strong>Pickup Option:</strong> '.($order['pickup_option'] ? ucfirst(str_replace('_',' ',$order['pickup_option'])) : '-').'</p>
            <p><strong>Pickup Fee:</strong> '.formatPrice($order['pickup_fee']).'</p>
            <p><strong>Additional Fee:</strong> '.formatPrice($order['total_additional_fee']).'</p>
            <p><strong>Return Payment Method:</strong> '.($order['return_payment_method'] ? ucfirst($order['return_payment_method']) : '-').'</p>';
    if ($order['return_payment_method'] === 'transfer' && $order['return_payment_proof']) {
        $content .= '<p><strong>Return Payment Proof:</strong><br><img src="../'.$order['return_payment_proof'].'" class="img-fluid rounded border" style="max-width:200px;"></p>';
    }
    $content .= '<p><strong>Return Status:</strong> '.ucfirst($order['return_status']).'</p>';
    $content .= '</div></div>';
}

$content .= '</div></div>';

$content .= '<a href="orders.php" class="btn btn-secondary mt-3">Back to Order Management</a>';

require_once '../includes/layout.php'; 