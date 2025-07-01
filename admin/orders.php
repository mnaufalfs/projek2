<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Handle order status update
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    
    // Validate status
    $valid_statuses = ['pending', 'confirmed', 'ongoing', 'completed', 'cancelled'];
    if (!in_array($new_status, $valid_statuses)) {
        setFlashMessage('error', 'Invalid status');
        redirect('orders.php');
    }
    
    // Update order status
    $stmt = $conn->prepare("UPDATE rental_orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        // Update vehicle and driver status based on order status
        if ($new_status === 'confirmed' || $new_status === 'ongoing') {
            $conn->query("UPDATE vehicles v 
                         JOIN rental_orders ro ON v.id = ro.vehicle_id 
                         SET v.status = 'rented' 
                         WHERE ro.id = $order_id");
            
            $conn->query("UPDATE drivers d 
                         JOIN rental_orders ro ON d.id = ro.driver_id 
                         SET d.status = 'assigned' 
                         WHERE ro.id = $order_id");
        } elseif ($new_status === 'completed' || $new_status === 'cancelled') {
            $conn->query("UPDATE vehicles v 
                         JOIN rental_orders ro ON v.id = ro.vehicle_id 
                         SET v.status = 'available' 
                         WHERE ro.id = $order_id");
            
            $conn->query("UPDATE drivers d 
                         JOIN rental_orders ro ON d.id = ro.driver_id 
                         SET d.status = 'available' 
                         WHERE ro.id = $order_id");
        }
        
        setFlashMessage('success', 'Order status updated successfully');
    } else {
        setFlashMessage('error', 'Failed to update order status');
    }
    
    redirect('orders.php');
}

// Build query with filters
$where_conditions = [];
$params = [];
$types = '';

// Search by order ID, customer name, or vehicle
if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where_conditions[] = "(ro.id LIKE ? OR u.name LIKE ? OR v.name LIKE ? OR v.plate_number LIKE ?)";
    $params = array_merge($params, [$search, $search, $search, $search]);
    $types .= 'ssss';
}

// Filter by status
if (!empty($_GET['status'])) {
    $where_conditions[] = "ro.status = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}

// Filter by date range
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

// Filter by vehicle type
if (!empty($_GET['vehicle_type'])) {
    $where_conditions[] = "vt.id = ?";
    $params[] = $_GET['vehicle_type'];
    $types .= 'i';
}

// Build the WHERE clause
$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get all orders with related information
$query = "
    SELECT ro.*, 
           u.name as user_name, u.phone as user_phone,
           v.name as vehicle_name, v.plate_number,
           vt.name as vehicle_type,
           d.name as driver_name,
           rr.return_date, rr.late_hours, 
           rr.late_fee, rr.pickup_option, rr.pickup_fee, 
           rr.total_additional_fee, rr.return_payment_method, 
           rr.return_payment_proof, rr.status as return_status
    FROM rental_orders ro
    JOIN users u ON ro.user_id = u.id
    JOIN vehicles v ON ro.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    LEFT JOIN drivers d ON ro.driver_id = d.id
    LEFT JOIN rental_returns rr ON ro.id = rr.order_id
    $where_clause
    ORDER BY ro.created_at DESC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get vehicle types for filter
$vehicle_types = $conn->query("SELECT * FROM vehicle_types ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$content = '
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Order Management</h2>
        <div>
            <a href="export_orders.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '') . '" class="btn btn-success me-2">
                <i class="fas fa-file-excel"></i> Export to Excel
            </a>
            <a href="export_orders_pdf.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '') . '" class="btn btn-danger me-2">
                <i class="fas fa-file-pdf"></i> Export to PDF
            </a>
            <a href="export_orders_csv.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '') . '" class="btn btn-secondary me-2">
                <i class="fas fa-file-csv"></i> Export to CSV
            </a>
            <a href="order_statistics.php" class="btn btn-info">
                <i class="fas fa-chart-bar"></i> View Statistics
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" placeholder="Search orders..." value="' . htmlspecialchars($_GET['search'] ?? '') . '">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="pending"' . (isset($_GET['status']) && $_GET['status'] === 'pending' ? ' selected' : '') . '>Pending</option>
                        <option value="confirmed"' . (isset($_GET['status']) && $_GET['status'] === 'confirmed' ? ' selected' : '') . '>Confirmed</option>
                        <option value="ongoing"' . (isset($_GET['status']) && $_GET['status'] === 'ongoing' ? ' selected' : '') . '>Ongoing</option>
                        <option value="completed"' . (isset($_GET['status']) && $_GET['status'] === 'completed' ? ' selected' : '') . '>Completed</option>
                        <option value="cancelled"' . (isset($_GET['status']) && $_GET['status'] === 'cancelled' ? ' selected' : '') . '>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="vehicle_type">
                        <option value="">All Vehicle Types</option>';

foreach ($vehicle_types as $type) {
    $content .= '
                        <option value="' . $type['id'] . '"' . (isset($_GET['vehicle_type']) && $_GET['vehicle_type'] == $type['id'] ? ' selected' : '') . '>' . htmlspecialchars($type['name']) . '</option>';
}

$content .= '
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" name="start_date" placeholder="Start Date" value="' . htmlspecialchars($_GET['start_date'] ?? '') . '">
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" name="end_date" placeholder="End Date" value="' . htmlspecialchars($_GET['end_date'] ?? '') . '">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
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
                            <th>Return Date</th>
                            <th>Booking Fee</th>
                            <th>Additional Fee</th>
                            <th>Total Fee</th>
                            <th>Status</th>
                            <th>Payment Booking</th>
                            <th>Payment Retur</th>
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
    // Payment Booking
    $payment_method_badge = '';
    if ($order['payment_method'] === 'cash') {
        $payment_method_badge = '<span class="badge bg-success">Cash</span>';
    } elseif ($order['payment_method'] === 'transfer') {
        $payment_method_badge = '<span class="badge bg-info text-dark">Transfer</span>';
        if (!empty($order['payment_proof'])) {
            $payment_method_badge .= '<br><button type="button" class="btn btn-link p-0 mt-1" data-bs-toggle="modal" data-bs-target="#proofModal' . $order['id'] . '">Lihat Bukti</button>';
        }
    }
    // Payment Retur
    $payment_retur_badge = '-';
    if ($order['return_payment_method'] === 'cash') {
        $payment_retur_badge = '<span class="badge bg-success">Cash</span>';
    } elseif ($order['return_payment_method'] === 'transfer') {
        $payment_retur_badge = '<span class="badge bg-info text-dark">Transfer</span>';
        $payment_retur_badge .= '<br><button type="button" class="btn btn-link p-0 mt-1" data-bs-toggle="modal" data-bs-target="#returnProofModal' . $order['id'] . '">Lihat Bukti</button>';
    }
    $content .= '
                        <tr>
                            <td>' . generateReceiptNumber('booking', $order['id'], $order['created_at']) . '</td>
                            <td>
                                ' . htmlspecialchars($order['user_name']) . '<br>
                                <small class="text-muted">' . htmlspecialchars($order['user_phone']) . '</small>
                            </td>
                            <td>
                                ' . htmlspecialchars($order['vehicle_type']) . ' - ' . htmlspecialchars($order['vehicle_name']) . '<br>
                                <small class="text-muted">' . htmlspecialchars($order['plate_number']) . '</small>
                            </td>
                            <td>' . ($order['driver_name'] ? htmlspecialchars($order['driver_name']) : '<span class="text-muted">No driver</span>') . '</td>
                            <td>' . date('d M Y H:i', strtotime($order['start_date'])) . '</td>
                            <td>' . date('d M Y H:i', strtotime($order['end_date'])) . '</td>
                            <td>' . ($order['return_date'] ? date('d M Y H:i', strtotime($order['return_date'])) : '<span class="text-muted">-</span>') . '</td>
                            <td>' . formatPrice($order['total_price']) . '</td>
                            <td>' . (
                                (isset($order['late_fee']) || isset($order['pickup_fee']))
                                    ? formatPrice((float)($order['late_fee'] ?? 0) + (float)($order['pickup_fee'] ?? 0))
                                    : '<span class="text-muted">-</span>'
                            ) . '</td>
                            <td>' . formatPrice($order['total_price'] + ($order['total_additional_fee'] ?? 0)) . '</td>
                            <td><span class="badge bg-' . $status_class . '">' . ucfirst($order['status']) . '</span></td>
                            <td>' . $payment_method_badge . '</td>
                            <td>' . $payment_retur_badge . '</td>
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
    
    $content .= '
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" class="dropdown-item">
                                                <input type="hidden" name="order_id" value="' . $order['id'] . '">
                                                <select name="status" class="form-select form-select-sm mb-2" onchange="this.form.submit()">
                                                    <option value="">Update Status</option>
                                                    <option value="pending"' . ($order['status'] === 'pending' ? ' selected' : '') . '>Pending</option>
                                                    <option value="confirmed"' . ($order['status'] === 'confirmed' ? ' selected' : '') . '>Confirmed</option>
                                                    <option value="ongoing"' . ($order['status'] === 'ongoing' ? ' selected' : '') . '>Ongoing</option>
                                                    <option value="completed"' . ($order['status'] === 'completed' ? ' selected' : '') . '>Completed</option>
                                                    <option value="cancelled"' . ($order['status'] === 'cancelled' ? ' selected' : '') . '>Cancelled</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                        </li>
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
</div>';

// Payment Proof Modal
foreach ($orders as $order) {
    if ($order['payment_method'] === 'transfer' && $order['payment_proof']) {
        $img_path = realpath(__DIR__ . '/../' . $order['payment_proof']);
        $img_url = APP_URL . '/' . $order['payment_proof'];
        $img_exists = $img_path && file_exists($img_path);
        $content .= '
        <div class="modal fade" id="proofModal'.$order['id'].'" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Payment Proof</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">';
        if ($img_exists) {
            $content .= '<img src="'.$img_url.'" class="img-fluid" alt="Payment Proof">';
        } else {
            $content .= '<div class="alert alert-danger">File tidak ditemukan!</div>';
        }
        $content .= '</div></div></div></div>';
    }

    if ($order['return_payment_method'] === 'transfer' && $order['return_payment_proof']) {
        $img_path = realpath(__DIR__ . '/../' . $order['return_payment_proof']);
        $img_url = APP_URL . '/' . $order['return_payment_proof'];
        $img_exists = $img_path && file_exists($img_path);
        $content .= '
        <div class="modal fade" id="returnProofModal'.$order['id'].'" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Return Payment Proof</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">';
        if ($img_exists) {
            $content .= '<img src="'.$img_url.'" class="img-fluid" alt="Return Payment Proof">';
        } else {
            $content .= '<div class="alert alert-danger">Tidak ada bukti transfer retur!</div>';
        }
        $content .= '</div></div></div></div>';
    }
}

require_once '../includes/layout.php';
?> 