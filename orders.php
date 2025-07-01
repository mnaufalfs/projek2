<?php
require_once 'config/config.php';
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user's orders
$stmt = $conn->prepare("
    SELECT ro.*, v.name as vehicle_name, v.plate_number, d.name as driver_name,
           vt.name as vehicle_type, rr.id as return_id, rr.return_date
    FROM rental_orders ro
    JOIN vehicles v ON ro.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    JOIN drivers d ON ro.driver_id = d.id
    LEFT JOIN rental_returns rr ON ro.id = rr.order_id
    WHERE ro.user_id = ?
    ORDER BY ro.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$content = '
<div class="container">
    <h2 class="mb-4">My Orders</h2>
    
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Vehicle</th>
                    <th>Driver</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Total Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';

foreach ($orders as $order) {
    $status_class = '';
    switch ($order['status']) {
        case 'pending':
            $status_class = 'warning';
            break;
        case 'confirmed':
            $status_class = 'info';
            break;
        case 'ongoing':
            $status_class = 'primary';
            break;
        case 'completed':
            $status_class = 'success';
            break;
        case 'cancelled':
            $status_class = 'danger';
            break;
    }

    $content .= '
                <tr>
                    <td>' . generateReceiptNumber('booking', $order['id']) . '</td>
                    <td>
                        ' . htmlspecialchars($order['vehicle_type']) . ' - ' . htmlspecialchars($order['vehicle_name']) . '<br>
                        <small class="text-muted">' . htmlspecialchars($order['plate_number']) . '</small>
                    </td>
                    <td>' . htmlspecialchars($order['driver_name']) . '</td>
                    <td>' . date('d M Y H:i', strtotime($order['start_date'])) . '</td>
                    <td>' . date('d M Y H:i', strtotime($order['end_date'])) . '</td>
                    <td>' . formatPrice($order['total_price']) . '</td>
                    <td><span class="badge bg-' . $status_class . '">' . ucfirst($order['status']) . '</span></td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                Actions
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="view_order.php?id=' . $order['id'] . '">View Details</a></li>
                                <li><a class="dropdown-item" href="download_receipt.php?type=booking&id=' . $order['id'] . '">Download Booking Receipt</a></li>';
                                
                                if ($order['status'] === 'completed' && $order['return_id']) {
                                    $content .= '<li><a class="dropdown-item" href="download_receipt.php?type=return&id=' . $order['return_id'] . '">Download Return Receipt</a></li>';
                                }
                                
                                if ($order['status'] === 'ongoing') {
                                    $content .= '<li><a class="dropdown-item" href="return_vehicle.php?id=' . $order['id'] . '">Return Vehicle</a></li>';
                                }
                                
                                $content .= '
                            </ul>
                        </div>
                    </td>
                </tr>';
}

$content .= '
            </tbody>
        </table>
    </div>
</div>';

require_once 'includes/layout.php';
?> 