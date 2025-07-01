<?php
require_once 'config/config.php';
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    redirect('orders.php');
}

$order_id = (int)$_GET['id'];

// Get order details
$stmt = $conn->prepare("
    SELECT ro.*, v.name as vehicle_name, v.plate_number, d.name as driver_name,
           vt.name as vehicle_type
    FROM rental_orders ro
    JOIN vehicles v ON ro.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    JOIN drivers d ON ro.driver_id = d.id
    WHERE ro.id = ? AND ro.user_id = ? AND ro.status = 'ongoing'
");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['message'] = "Order not found or not eligible for return.";
    $_SESSION['message_type'] = "danger";
    redirect('orders.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $return_date = $_POST['return_date'];
    $late_hours = (int)$_POST['late_hours'];
    $damage_fee = (float)$_POST['damage_fee'];
    $notes = trim($_POST['notes']);

    // Validation
    if (empty($return_date)) {
        $errors[] = "Return date is required";
    }

    if ($late_hours < 0) {
        $errors[] = "Late hours cannot be negative";
    }

    if ($damage_fee < 0) {
        $errors[] = "Damage fee cannot be negative";
    }

    if (empty($errors)) {
        // Calculate late fee
        $late_fee = 0;
        if ($late_hours > 0) {
            $base_price = $order['total_price'] / 24; // Hourly rate
            $late_fee = $base_price * $late_hours;
        }

        $total_additional_fee = $late_fee + $damage_fee;

        // Create return record
        $stmt = $conn->prepare("
            INSERT INTO rental_returns (
                rental_order_id, return_date, late_hours, late_fee,
                damage_fee, total_additional_fee, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "isiddds",
            $order_id,
            $return_date,
            $late_hours,
            $late_fee,
            $damage_fee,
            $total_additional_fee,
            $notes
        );

        if ($stmt->execute()) {
            // Update order status
            $stmt = $conn->prepare("UPDATE rental_orders SET status = 'completed' WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();

            // Update vehicle status
            $stmt = $conn->prepare("UPDATE vehicles SET status = 'available' WHERE id = ?");
            $stmt->bind_param("i", $order['vehicle_id']);
            $stmt->execute();

            // Update driver status
            $stmt = $conn->prepare("UPDATE drivers SET status = 'available' WHERE id = ?");
            $stmt->bind_param("i", $order['driver_id']);
            $stmt->execute();

            $_SESSION['message'] = "Vehicle returned successfully!";
            $_SESSION['message_type'] = "success";
            redirect('orders.php');
        } else {
            $errors[] = "Failed to process return. Please try again.";
        }
    }
}

$content = '
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Return Vehicle</h3>
                </div>
                <div class="card-body">
                    ' . (!empty($errors) ? '<div class="alert alert-danger"><ul class="mb-0"><li>' . implode('</li><li>', $errors) . '</li></ul></div>' : '') . '
                    
                    <div class="mb-4">
                        <h4>Order Details</h4>
                        <p>
                            <strong>Vehicle:</strong> ' . htmlspecialchars($order['vehicle_type']) . ' - ' . htmlspecialchars($order['vehicle_name']) . '<br>
                            <strong>Plate Number:</strong> ' . htmlspecialchars($order['plate_number']) . '<br>
                            <strong>Driver:</strong> ' . htmlspecialchars($order['driver_name']) . '<br>
                            <strong>Start Date:</strong> ' . date('d M Y H:i', strtotime($order['start_date'])) . '<br>
                            <strong>End Date:</strong> ' . date('d M Y H:i', strtotime($order['end_date'])) . '
                        </p>
                    </div>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="return_date" class="form-label">Return Date & Time</label>
                            <input type="datetime-local" class="form-control" id="return_date" name="return_date" required>
                        </div>

                        <div class="mb-3">
                            <label for="late_hours" class="form-label">Late Hours (if any)</label>
                            <input type="number" class="form-control" id="late_hours" name="late_hours" value="0" min="0">
                            <small class="text-muted">Late fee will be calculated based on hourly rate</small>
                        </div>

                        <div class="mb-3">
                            <label for="damage_fee" class="form-label">Damage Fee (if any)</label>
                            <input type="number" class="form-control" id="damage_fee" name="damage_fee" value="0" min="0" step="0.01">
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Submit Return</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>';

require_once 'includes/layout.php';
?> 