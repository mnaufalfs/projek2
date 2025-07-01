<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/auth.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Hapus pesan 'Order not found' jika ada, khusus di halaman ini
if (isset($_SESSION['message']) && $_SESSION['message'] === 'Order not found') {
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Get user's orders
$query = "
    SELECT ro.*, 
           v.name as vehicle_name, v.plate_number,
           vt.name as vehicle_type,
           d.name as driver_name
    FROM rental_orders ro
    JOIN vehicles v ON ro.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    LEFT JOIN drivers d ON ro.driver_id = d.id
    WHERE ro.user_id = ?
    ORDER BY ro.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$content = '
<div class="container py-4">
    <h2 class="mb-4">My Orders</h2>
    <a href="return_vehicle.php" class="btn btn-warning mb-3">
        <i class="fas fa-car"></i> Pengembalian Kendaraan
        </a>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Vehicle</th>
                            <th>Driver</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Total</th>
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
                            <td>' . ($order['driver_name'] ? htmlspecialchars($order['driver_name']) : '<span class="text-muted">No driver</span>') . '</td>
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
                                        <li><a class="dropdown-item" href="download_receipt.php?type=booking&id=' . $order['id'] . '" target="_blank">Download Receipt</a></li>';
    
    if ($order['status'] === 'completed') {
        $content .= '
                                        <li><a class="dropdown-item" href="download_receipt.php?type=return&id=' . $order['id'] . '" target="_blank">Download Return Receipt</a></li>';
    }
    
    if ($order['status'] === 'pending') {
        $content .= '
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="cancel_order.php" class="dropdown-item" onsubmit="return confirm(\'Are you sure you want to cancel this order?\')">
                                                <input type="hidden" name="order_id" value="' . $order['id'] . '">
                                                <button type="submit" class="btn btn-link text-danger p-0">Cancel Order</button>
                                            </form>
                                        </li>';
    }
    
    $content .= '
                                    </ul>
                                </div>
                            </td>
                        </tr>';
}

// Tambahkan pesan jika kosong
if (empty($orders)) {
    $content .= '
        <div class="alert alert-info mt-4">
            <i class="fas fa-info-circle me-2"></i>
            You have no orders yet.
        </div>';
}

$content .= '
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>';

require_once '../includes/layout.php';
?> 