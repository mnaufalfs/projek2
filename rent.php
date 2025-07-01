<?php
require_once 'config/config.php';
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$vehicle_id = (int)$_GET['id'];

// Get vehicle details
$stmt = $conn->prepare("
    SELECT v.*, vt.name as type_name 
    FROM vehicles v 
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id 
    WHERE v.id = ? AND v.status = 'available'
");
$stmt->bind_param("i", $vehicle_id);
$stmt->execute();
$vehicle = $stmt->get_result()->fetch_assoc();

if (!$vehicle) {
    $_SESSION['message'] = "Vehicle not found or not available.";
    $_SESSION['message_type'] = "danger";
    redirect('index.php');
}

// Get available drivers
$drivers = [];
$result = $conn->query("SELECT * FROM drivers WHERE status = 'available' ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $drivers[] = $row;
}

// Get delivery fees
$delivery_fees = [];
$result = $conn->query("SELECT * FROM delivery_fees ORDER BY distance_km");
while ($row = $result->fetch_assoc()) {
    $delivery_fees[] = $row;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $rental_type = $_POST['rental_type'];
    $is_out_of_town = isset($_POST['is_out_of_town']) ? 1 : 0;
    $driver_id = $_POST['driver_id'];
    $needs_delivery = isset($_POST['needs_delivery']) ? 1 : 0;
    $delivery_distance = $needs_delivery ? (int)$_POST['delivery_distance'] : 0;
    $needs_pickup = isset($_POST['needs_pickup']) ? 1 : 0;
    $pickup_distance = $needs_pickup ? (int)$_POST['pickup_distance'] : 0;
    $payment_method = $_POST['payment_method'];
    $payment_proof_path = null;

    // Validation
    if (empty($start_date)) {
        $errors[] = "Start date is required";
    }

    if (empty($end_date)) {
        $errors[] = "End date is required";
    }

    if (strtotime($start_date) >= strtotime($end_date)) {
        $errors[] = "End date must be after start date";
    }

    if (empty($driver_id)) {
        $errors[] = "Please select a driver";
    }

    if ($needs_delivery && empty($delivery_distance)) {
        $errors[] = "Please select delivery distance";
    }

    if ($needs_pickup && empty($pickup_distance)) {
        $errors[] = "Please select pickup distance";
    }

    if (empty($payment_method)) {
        $errors[] = "Please select payment method";
    }

    // Validasi upload bukti transfer jika transfer
    if ($payment_method === 'transfer') {
        if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Please upload payment proof for bank transfer.";
        } else {
            $allowed_ext = ['jpg', 'jpeg', 'png'];
            $file_info = pathinfo($_FILES['payment_proof']['name']);
            $ext = strtolower($file_info['extension']);
            if (!in_array($ext, $allowed_ext)) {
                $errors[] = "Payment proof must be an image (JPG, JPEG, PNG).";
            } elseif ($_FILES['payment_proof']['size'] > 5 * 1024 * 1024) {
                $errors[] = "Payment proof file size max 5MB.";
            } else {
                $upload_dir = 'uploads/payment_proof/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $filename = uniqid('proof_') . '.' . $ext;
                $target_path = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target_path)) {
                    $payment_proof_path = $target_path;
                } else {
                    $errors[] = "Failed to upload payment proof.";
                }
            }
        }
    }

    if (empty($errors)) {
        // Calculate rental duration and price
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        
        $hours = $interval->h + ($interval->days * 24);
        if ($hours < MIN_RENTAL_HOURS) {
            $errors[] = "Minimum rental duration is " . MIN_RENTAL_HOURS . " hours";
        } else {
            // Calculate base price
            $base_price = $vehicle['base_price'];
            if ($rental_type === 'hourly') {
                $base_price = $base_price / 24;
            } elseif ($rental_type === 'weekly') {
                $base_price = $base_price * 7 * 0.9; // 10% discount
            } elseif ($rental_type === 'monthly') {
                $base_price = $base_price * 30 * 0.8; // 20% discount
            }

            // Apply out of town surcharge
            if ($is_out_of_town) {
                $base_price *= (1 + OUT_OF_TOWN_SURCHARGE);
            }

            // Calculate total price
            $total_price = $base_price;
            if ($needs_delivery) {
                $delivery_fee = array_filter($delivery_fees, function($fee) use ($delivery_distance) {
                    return $fee['distance_km'] >= $delivery_distance;
                });
                $delivery_fee = reset($delivery_fee);
                $total_price += $delivery_fee['fee'];
            }
            if ($needs_pickup) {
                $pickup_fee = array_filter($delivery_fees, function($fee) use ($pickup_distance) {
                    return $fee['distance_km'] >= $pickup_distance;
                });
                $pickup_fee = reset($pickup_fee);
                $total_price += $pickup_fee['fee'];
            }

            // Create rental order
            if ($payment_method === 'transfer') {
                $stmt = $conn->prepare("
                    INSERT INTO rental_orders (
                        user_id, vehicle_id, driver_id, start_date, end_date, 
                        rental_type, is_out_of_town, total_price, payment_method, payment_proof,
                        delivery_fee, pickup_fee
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "iiissssdsdds",
                    $_SESSION['user_id'],
                    $vehicle_id,
                    $driver_id,
                    $start_date,
                    $end_date,
                    $rental_type,
                    $is_out_of_town,
                    $total_price,
                    $payment_method,
                    $payment_proof_path,
                    $delivery_fee['fee'] ?? 0,
                    $pickup_fee['fee'] ?? 0
                );
            } else {
            $stmt = $conn->prepare("
                INSERT INTO rental_orders (
                    user_id, vehicle_id, driver_id, start_date, end_date, 
                    rental_type, is_out_of_town, total_price, payment_method,
                    delivery_fee, pickup_fee
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "iiissssdsdd",
                $_SESSION['user_id'],
                $vehicle_id,
                $driver_id,
                $start_date,
                $end_date,
                $rental_type,
                $is_out_of_town,
                $total_price,
                $payment_method,
                $delivery_fee['fee'] ?? 0,
                $pickup_fee['fee'] ?? 0
            );
            }

            if ($stmt->execute()) {
                $_SESSION['message'] = "Rental order created successfully!";
                $_SESSION['message_type'] = "success";
                redirect('orders.php');
            } else {
                $errors[] = "Failed to create rental order. Please try again.";
            }
        }
    }
}

$content = '
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Rent Vehicle</h3>
                </div>
                <div class="card-body">
                    ' . (!empty($errors) ? '<div class="alert alert-danger"><ul class="mb-0"><li>' . implode('</li><li>', $errors) . '</li></ul></div>' : '') . '
                    
                    <div class="mb-4">
                        <h4>Vehicle Details</h4>
                        <p>
                            <strong>Type:</strong> ' . htmlspecialchars($vehicle['type_name']) . '<br>
                            <strong>Name:</strong> ' . htmlspecialchars($vehicle['name']) . '<br>
                            <strong>Plate Number:</strong> ' . htmlspecialchars($vehicle['plate_number']) . '<br>
                            <strong>Base Price:</strong> ' . formatPrice($vehicle['base_price']) . '/day
                        </p>
                    </div>

                    <form method="POST" action="" id="rentalForm" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Start Date & Time</label>
                                <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">End Date & Time</label>
                                <input type="datetime-local" class="form-control" id="end_date" name="end_date" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="rental_type" class="form-label">Rental Type</label>
                            <select class="form-select" id="rental_type" name="rental_type" required>
                                <option value="hourly">Hourly</option>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_out_of_town" name="is_out_of_town">
                                <label class="form-check-label" for="is_out_of_town">
                                    Out of Town (+20% surcharge)
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="driver_id" class="form-label">Select Driver</label>
                            <select class="form-select" id="driver_id" name="driver_id" required>
                                <option value="">Choose a driver...</option>';
                                foreach ($drivers as $driver) {
                                    $content .= '<option value="' . $driver['id'] . '">' . htmlspecialchars($driver['name']) . ' - ' . htmlspecialchars($driver['phone']) . '</option>';
                                }
                            $content .= '
                            </select>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="needs_delivery" name="needs_delivery">
                                <label class="form-check-label" for="needs_delivery">
                                    Need Vehicle Delivery
                                </label>
                            </div>
                            <div id="delivery_distance_div" class="mt-2" style="display: none;">
                                <label for="delivery_distance" class="form-label">Delivery Distance (KM)</label>
                                <select class="form-select" id="delivery_distance" name="delivery_distance">
                                    <option value="">Select distance...</option>';
                                    foreach ($delivery_fees as $fee) {
                                        $content .= '<option value="' . $fee['distance_km'] . '">' . $fee['distance_km'] . ' KM - ' . formatPrice($fee['fee']) . '</option>';
                                    }
                                $content .= '
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="needs_pickup" name="needs_pickup">
                                <label class="form-check-label" for="needs_pickup">
                                    Need Vehicle Pickup
                                </label>
                            </div>
                            <div id="pickup_distance_div" class="mt-2" style="display: none;">
                                <label for="pickup_distance" class="form-label">Pickup Distance (KM)</label>
                                <select class="form-select" id="pickup_distance" name="pickup_distance">
                                    <option value="">Select distance...</option>';
                                    foreach ($delivery_fees as $fee) {
                                        $content .= '<option value="' . $fee['distance_km'] . '">' . $fee['distance_km'] . ' KM - ' . formatPrice($fee['fee']) . '</option>';
                                    }
                                $content .= '
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="payment_cash" value="cash" required>
                                <label class="form-check-label" for="payment_cash">
                                    Cash
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="payment_transfer" value="transfer" required>
                                <label class="form-check-label" for="payment_transfer">
                                    Bank Transfer
                                </label>
                            </div>
                        </div>

                        <div class="mb-3" id="payment_proof_group" style="display:none;">
                            <label for="payment_proof" class="form-label">Upload Payment Proof <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="payment_proof" name="payment_proof" accept="image/jpeg,image/png">
                            <div class="form-text">Allowed formats: JPG, JPEG, PNG. Max 5MB.</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Book Now</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
document.getElementById("needs_delivery").addEventListener("change", function() {
    document.getElementById("delivery_distance_div").style.display = this.checked ? "block" : "none";
});

document.getElementById("needs_pickup").addEventListener("change", function() {
    document.getElementById("pickup_distance_div").style.display = this.checked ? "block" : "none";
    });

    // Show/hide payment proof input
    const paymentCash = document.getElementById('payment_cash');
    const paymentTransfer = document.getElementById('payment_transfer');
    const paymentProofGroup = document.getElementById('payment_proof_group');
    function togglePaymentProof() {
        if (paymentTransfer.checked) {
            paymentProofGroup.style.display = 'block';
            document.getElementById('payment_proof').required = true;
        } else {
            paymentProofGroup.style.display = 'none';
            document.getElementById('payment_proof').required = false;
        }
    }
    paymentCash.addEventListener('change', togglePaymentProof);
    paymentTransfer.addEventListener('change', togglePaymentProof);
    togglePaymentProof();
});
</script>';

require_once 'includes/layout.php';
?> 