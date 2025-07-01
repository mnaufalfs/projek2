<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get driver ID from URL
$driver_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get driver details
$driver = $conn->query("SELECT * FROM drivers WHERE id = $driver_id")->fetch_assoc();

if (!$driver) {
    setFlashMessage('error', 'Driver not found');
    redirect('drivers.php');
}

// Get driver statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN status = 'ongoing' THEN 1 END) as ongoing_orders,
        SUM(total_price) as total_revenue
    FROM rental_orders
    WHERE driver_id = $driver_id
")->fetch_assoc();

// Get rental history
$rental_history = $conn->query("
    SELECT ro.*, v.name as vehicle_name, v.plate_number, vt.name as vehicle_type,
           u.name as user_name, u.phone as user_phone
    FROM rental_orders ro
    JOIN vehicles v ON ro.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    JOIN users u ON ro.user_id = u.id
    WHERE ro.driver_id = $driver_id
    ORDER BY ro.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$content = '
<div class="container">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="card-title">Driver Details</h3>
                    <table class="table">
                        <tr>
                            <th>Name</th>
                            <td>' . htmlspecialchars($driver['name']) . '</td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td>' . htmlspecialchars($driver['phone']) . '</td>
                        </tr>
                        <tr>
                            <th>License Number</th>
                            <td>' . htmlspecialchars($driver['license_number']) . '</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><span class="badge bg-' . ($driver['status'] === 'available' ? 'success' : ($driver['status'] === 'assigned' ? 'warning' : 'danger')) . '">' . ucfirst($driver['status']) . '</span></td>
                        </tr>
                    </table>
                    
                    <div class="d-flex justify-content-between">
                        <a href="drivers.php" class="btn btn-secondary">Back to List</a>
                        <a href="edit_driver.php?id=' . $driver['id'] . '" class="btn btn-primary">Edit Driver</a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Statistics</h4>
                    <table class="table">
                        <tr>
                            <th>Total Orders</th>
                            <td>' . $stats['total_orders'] . '</td>
                        </tr>
                        <tr>
                            <th>Completed Orders</th>
                            <td>' . $stats['completed_orders'] . '</td>
                        </tr>
                        <tr>
                            <th>Ongoing Orders</th>
                            <td>' . $stats['ongoing_orders'] . '</td>
                        </tr>
                        <tr>
                            <th>Total Revenue</th>
                            <td>' . formatPrice($stats['total_revenue']) . '</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Rental History</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Vehicle</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>';

foreach ($rental_history as $order) {
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
                                        ' . htmlspecialchars($order['user_name']) . '<br>
                                        <small class="text-muted">' . htmlspecialchars($order['user_phone']) . '</small>
                                    </td>
                                    <td>
                                        ' . htmlspecialchars($order['vehicle_type']) . ' - ' . htmlspecialchars($order['vehicle_name']) . '<br>
                                        <small class="text-muted">' . htmlspecialchars($order['plate_number']) . '</small>
                                    </td>
                                    <td>' . date('d M Y H:i', strtotime($order['start_date'])) . '</td>
                                    <td>' . date('d M Y H:i', strtotime($order['end_date'])) . '</td>
                                    <td>' . formatPrice($order['total_price']) . '</td>
                                    <td><span class="badge bg-' . $status_class . '">' . ucfirst($order['status']) . '</span></td>
                                    <td>
                                        <a href="view_order.php?id=' . $order['id'] . '" class="btn btn-sm btn-info">View Details</a>
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