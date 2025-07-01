<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/auth.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get order details
$query = "
    SELECT ro.*, 
           u.name as customer_name, u.phone as customer_phone,
           v.name as vehicle_name, v.plate_number, v.image as vehicle_image,
           vt.name as vehicle_type,
           d.name as driver_name, d.phone as driver_phone, d.license_number
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

$content = '
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Order Details</h4>
                    <span class="badge bg-' . ($order['status'] === 'pending' ? 'warning' : 
                        ($order['status'] === 'confirmed' ? 'info' : 
                        ($order['status'] === 'ongoing' ? 'primary' : 
                        ($order['status'] === 'completed' ? 'success' : 'danger')))) . '">
                        ' . ucfirst($order['status']) . '
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <img src="../assets/img/vehicles/' . htmlspecialchars($order['vehicle_image']) . '" class="img-fluid rounded" alt="' . htmlspecialchars($order['vehicle_name']) . '">
                        </div>
                        <div class="col-md-8">
                            <h5>' . htmlspecialchars($order['vehicle_type']) . ' - ' . htmlspecialchars($order['vehicle_name']) . '</h5>
                            <p>
                                <strong>Plate Number:</strong> ' . htmlspecialchars($order['plate_number']) . '<br>
                                <strong>Order ID:</strong> ' . generateReceiptNumber('booking', $order['id']) . '<br>
                                <strong>Order Date:</strong> ' . date('d M Y H:i', strtotime($order['created_at'])) . '
                            </p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h5>Rental Details</h5>
                            <table class="table">
                                <tr>
                                    <th>Start Date</th>
                                    <td>' . date('d M Y H:i', strtotime($order['start_date'])) . '</td>
                                </tr>
                                <tr>
                                    <th>End Date</th>
                                    <td>' . date('d M Y H:i', strtotime($order['end_date'])) . '</td>
                                </tr>
                                <tr>
                                    <th>Duration</th>
                                    <td>' . ceil((strtotime($order['end_date']) - strtotime($order['start_date'])) / 3600) . ' hours</td>
                                </tr>
                                <tr>
                                    <th>Total Price</th>
                                    <td>' . formatPrice($order['total_price']) . '</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Customer Details</h5>
                            <table class="table">
                                <tr>
                                    <th>Name</th>
                                    <td>' . htmlspecialchars($order['customer_name']) . '</td>
                                </tr>
                                <tr>
                                    <th>Phone</th>
                                    <td>' . htmlspecialchars($order['customer_phone']) . '</td>
                                </tr>
                            </table>';

if ($order['driver_id']) {
    $content .= '
                            <h5>Driver Details</h5>
                            <table class="table">
                                <tr>
                                    <th>Name</th>
                                    <td>' . htmlspecialchars($order['driver_name']) . '</td>
                                </tr>
                                <tr>
                                    <th>Phone</th>
                                    <td>' . htmlspecialchars($order['driver_phone']) . '</td>
                                </tr>
                                <tr>
                                    <th>License Number</th>
                                    <td>' . htmlspecialchars($order['license_number']) . '</td>
                                </tr>
                            </table>';
}

$content .= '
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <h5 class="alert-heading">Important Notes:</h5>
                        <ul class="mb-0">
                            <li>Please arrive 15 minutes before the start time</li>
                            <li>Bring your ID card and driver\'s license</li>
                            <li>Payment will be made when picking up the vehicle</li>';
if ($order['status'] === 'pending') {
    $content .= '
                            <li>You can cancel this order if it hasn\'t been confirmed yet</li>';
}
$content .= '
                        </ul>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="my_orders.php" class="btn btn-secondary">Back to Orders</a>
                        <div>
                            <a href="download_receipt.php?type=booking&id=' . $order['id'] . '" class="btn btn-primary" target="_blank">
                                <i class="fas fa-download"></i> Download Receipt
                            </a>';
if ($order['status'] === 'completed') {
    $content .= '
                            <a href="download_receipt.php?type=return&id=' . $order['id'] . '" class="btn btn-success" target="_blank">
                                <i class="fas fa-download"></i> Download Return Receipt
                            </a>';
}
$content .= '
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

require_once '../includes/layout.php';
?> 