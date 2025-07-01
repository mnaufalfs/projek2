<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get statistics
$stats = [
    'total_vehicles' => $conn->query("SELECT COUNT(*) as count FROM vehicles")->fetch_assoc()['count'],
    'available_vehicles' => $conn->query("SELECT COUNT(*) as count FROM vehicles WHERE status = 'available'")->fetch_assoc()['count'],
    'total_drivers' => $conn->query("SELECT COUNT(*) as count FROM drivers")->fetch_assoc()['count'],
    'available_drivers' => $conn->query("SELECT COUNT(*) as count FROM drivers WHERE status = 'available'")->fetch_assoc()['count'],
    'total_orders' => $conn->query("SELECT COUNT(*) as count FROM rental_orders")->fetch_assoc()['count'],
    'pending_orders' => $conn->query("SELECT COUNT(*) as count FROM rental_orders WHERE status = 'pending'")->fetch_assoc()['count'],
    'ongoing_orders' => $conn->query("SELECT COUNT(*) as count FROM rental_orders WHERE status = 'ongoing'")->fetch_assoc()['count'],
    'total_revenue' => $conn->query("SELECT SUM(total_price) as total FROM rental_orders WHERE status IN ('completed', 'ongoing')")->fetch_assoc()['total'] ?? 0
];

// Get recent orders
$recent_orders = $conn->query("
    SELECT ro.*, v.name as vehicle_name, v.plate_number, d.name as driver_name,
           u.name as user_name, vt.name as vehicle_type
    FROM rental_orders ro
    JOIN vehicles v ON ro.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    JOIN drivers d ON ro.driver_id = d.id
    JOIN users u ON ro.user_id = u.id
    ORDER BY ro.created_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$content = '
<div class="container">
    <h2 class="mb-4">Admin Dashboard</h2>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Vehicles</h5>
                    <p class="card-text">
                        Total: ' . $stats['total_vehicles'] . '<br>
                        Available: ' . $stats['available_vehicles'] . '
                    </p>
                    <a href="vehicles.php" class="btn btn-light btn-sm">Manage Vehicles</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Drivers</h5>
                    <p class="card-text">
                        Total: ' . $stats['total_drivers'] . '<br>
                        Available: ' . $stats['available_drivers'] . '
                    </p>
                    <a href="drivers.php" class="btn btn-light btn-sm">Manage Drivers</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Orders</h5>
                    <p class="card-text">
                        Total: ' . $stats['total_orders'] . '<br>
                        Pending: ' . $stats['pending_orders'] . '<br>
                        Ongoing: ' . $stats['ongoing_orders'] . '
                    </p>
                    <a href="orders.php" class="btn btn-light btn-sm">Manage Orders</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Revenue</h5>
                    <p class="card-text">
                        Total: ' . formatPrice($stats['total_revenue']) . '
                    </p>
                    <a href="reports.php" class="btn btn-light btn-sm">View Reports</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Orders</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
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
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>';

foreach ($recent_orders as $order) {
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
                                    <td>' . htmlspecialchars($order['user_name']) . '</td>
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
                                                <li><a class="dropdown-item" href="view_order.php?id=' . $order['id'] . '">View Details</a></li>';
                                                
                                                if ($order['status'] === 'pending') {
                                                    $content .= '
                                                <li><a class="dropdown-item" href="verify_payment.php?id=' . $order['id'] . '">Verify Payment</a></li>';
                                                }
                                                
                                                if ($order['status'] === 'confirmed') {
                                                    $content .= '
                                                <li><a class="dropdown-item" href="start_rental.php?id=' . $order['id'] . '">Start Rental</a></li>';
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
                </div>
            </div>
        </div>
    </div>
</div>';

require_once '../includes/layout.php';
?> 