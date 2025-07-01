<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get vehicle ID from URL
$vehicle_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get vehicle details with type information
$vehicle = $conn->query("
    SELECT v.*, vt.name as vehicle_type
    FROM vehicles v
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    WHERE v.id = $vehicle_id
")->fetch_assoc();

if (!$vehicle) {
    setFlashMessage('error', 'Vehicle not found');
    redirect('vehicles.php');
}

// Get rental history
$rental_history = $conn->query("
    SELECT ro.*, u.name as user_name, d.name as driver_name
    FROM rental_orders ro
    JOIN users u ON ro.user_id = u.id
    JOIN drivers d ON ro.driver_id = d.id
    WHERE ro.vehicle_id = $vehicle_id
    ORDER BY ro.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$content = '
<div class="container">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="card-title">Vehicle Details</h3>';
                    
if ($vehicle['image']) {
    $content .= '
                    <img src="../' . $vehicle['image'] . '" alt="Vehicle Image" class="img-fluid rounded mb-3">';
}

$content .= '
                    <table class="table">
                        <tr>
                            <th>Type</th>
                            <td>' . htmlspecialchars($vehicle['vehicle_type']) . '</td>
                        </tr>
                        <tr>
                            <th>Name</th>
                            <td>' . htmlspecialchars($vehicle['name']) . '</td>
                        </tr>
                        <tr>
                            <th>Plate Number</th>
                            <td>' . htmlspecialchars($vehicle['plate_number']) . '</td>
                        </tr>
                        <tr>
                            <th>Price/Hour</th>
                            <td>' . formatPrice($vehicle['price_per_hour']) . '</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><span class="badge bg-' . ($vehicle['status'] === 'available' ? 'success' : 'danger') . '">' . ucfirst($vehicle['status']) . '</span></td>
                        </tr>
                        <tr>
                            <th>Description</th>
                            <td>' . nl2br(htmlspecialchars($vehicle['description'])) . '</td>
                        </tr>
                    </table>
                    
                    <div class="d-flex justify-content-between">
                        <a href="vehicles.php" class="btn btn-secondary">Back to List</a>
                        <a href="edit_vehicle.php?id=' . $vehicle['id'] . '" class="btn btn-primary">Edit Vehicle</a>
                    </div>
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
                                    <th>Driver</th>
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
                                    <td>' . htmlspecialchars($order['user_name']) . '</td>
                                    <td>' . htmlspecialchars($order['driver_name']) . '</td>
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