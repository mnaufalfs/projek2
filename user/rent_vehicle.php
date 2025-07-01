<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/auth.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$vehicle_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Jika tidak ada id kendaraan, redirect ke index tanpa pesan error
if (!$vehicle_id) {
    redirect('index.php');
}

// Get vehicle details
$stmt = $conn->prepare("
    SELECT v.*, vt.name as vehicle_type
    FROM vehicles v
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    WHERE v.id = ? AND v.status = 'available'
");
$stmt->bind_param("i", $vehicle_id);
$stmt->execute();
$vehicle = $stmt->get_result()->fetch_assoc();

if (!$vehicle) {
    setFlashMessage('error', 'Vehicle not found or not available');
    redirect('index.php');
}

// Get available drivers
$drivers = $conn->query("
    SELECT * FROM drivers 
    WHERE status = 'available' 
    ORDER BY name ASC
")->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $driver_id = !empty($_POST['driver_id']) ? (int)$_POST['driver_id'] : null;
    $payment_method = $_POST['payment_method'] ?? '';
    $payment_proof_path = null;
    $is_out_of_town = isset($_POST['is_out_of_town']) && $_POST['is_out_of_town'] == '1' ? 1 : 0;
    $errors = [];
    
    // Validate dates
    $start = strtotime($start_date);
    $end = strtotime($end_date);
    $now = time();
    
    if ($start < $now) {
        $errors[] = 'Start date must be in the future';
    } elseif ($end <= $start) {
        $errors[] = 'End date must be after start date';
    }

    // Validasi minimal 3 jam hanya jika tanggal valid
    if (empty($errors)) {
        $duration = ceil(($end - $start) / 3600);
        $minRentalHours = 3;
        if ($duration < $minRentalHours) {
            $errors[] = 'Minimum rental duration is 3 hours.';
        }
    }
    if (empty($payment_method)) {
        $errors[] = 'Please select payment method';
    }
    // Validasi upload bukti transfer jika transfer
    if ($payment_method === 'transfer') {
        if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Please upload payment proof for bank transfer.';
        } else {
            $allowed_ext = ['jpg', 'jpeg', 'png'];
            $file_info = pathinfo($_FILES['payment_proof']['name']);
            $ext = strtolower($file_info['extension']);
            if (!in_array($ext, $allowed_ext)) {
                $errors[] = 'Payment proof must be an image (JPG, JPEG, PNG).';
            } elseif ($_FILES['payment_proof']['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Payment proof file size max 5MB.';
            } else {
                $upload_dir = '../uploads/payment_proof/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $filename = uniqid('proof_') . '.' . $ext;
                $target_path = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target_path)) {
                    $payment_proof_path = 'uploads/payment_proof/' . $filename;
                } else {
                    $errors[] = 'Failed to upload payment proof.';
                }
            }
        }
    }
    if (empty($errors)) {
        // Calculate duration in hours
        $duration = ceil(($end - $start) / 3600);
        // Calculate total price
        $total_price = $duration * $vehicle['price_per_hour'];
        if ($is_out_of_town) {
            $total_price = $total_price * 1.2;
        }
        // Create order
        if ($payment_method === 'transfer') {
            $stmt = $conn->prepare("
                INSERT INTO rental_orders (
                    user_id, vehicle_id, driver_id, start_date, end_date, 
                    total_price, status, payment_method, payment_proof, is_out_of_town, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, NOW())
            ");
            $stmt->bind_param(
                "iiissdssi",
                $_SESSION['user_id'],
                $vehicle_id,
                $driver_id,
                $start_date,
                $end_date,
                $total_price,
                $payment_method,
                $payment_proof_path,
                $is_out_of_town
            );
        } else {
            $stmt = $conn->prepare("
                INSERT INTO rental_orders (
                    user_id, vehicle_id, driver_id, start_date, end_date, 
                    total_price, status, payment_method, is_out_of_town, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW())
            ");
            $stmt->bind_param(
                "iiissdsi",
                $_SESSION['user_id'],
                $vehicle_id,
                $driver_id,
                $start_date,
                $end_date,
                $total_price,
                $payment_method,
                $is_out_of_town
            );
        }
        if ($stmt->execute()) {
            setFlashMessage('success', 'Order created successfully. Please wait for admin confirmation.');
            redirect('my_orders.php');
        } else {
            $errors[] = 'Failed to create order';
        }
    } else {
        setFlashMessage('error', implode('<br>', $errors));
    }
}

$content = '
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Rent Vehicle</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <img src="../assets/img/vehicles/' . htmlspecialchars($vehicle['image']) . '" class="img-fluid rounded" alt="' . htmlspecialchars($vehicle['name']) . '">
                        </div>
                        <div class="col-md-8">
                            <h5>' . htmlspecialchars($vehicle['name']) . '</h5>
                            <p>
                                <span class="badge bg-primary">' . htmlspecialchars($vehicle['vehicle_type']) . '</span><br>
                                <strong>Plate Number:</strong> ' . htmlspecialchars($vehicle['plate_number']) . '<br>
                                <strong>Price:</strong> ' . formatPrice($vehicle['price_per_hour']) . ' / hour
                            </p>
                            <p>' . htmlspecialchars($vehicle['description']) . '</p>
                        </div>
                    </div>

                    <form method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Start Date & Time</label>
                                <input type="datetime-local" class="form-control" name="start_date" id="start_date" required 
                                       min="' . date('Y-m-d\TH:i') . '">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Date & Time</label>
                                <input type="datetime-local" class="form-control" name="end_date" id="end_date" required 
                                       min="' . date('Y-m-d\TH:i') . '">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Lokasi Sewa</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_out_of_town" id="dalam_kota" value="0" checked>
                                <label class="form-check-label" for="dalam_kota">Dalam Kota</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_out_of_town" id="luar_kota" value="1">
                                <label class="form-check-label" for="luar_kota">Luar Kota (+20% dari total harga sewa)</label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div id="estimate_box" class="alert alert-info d-flex align-items-center" style="font-size:1.2rem;display:none;">
                                <i class="fas fa-calculator me-2"></i>
                                <span id="estimate_text">Estimated rental cost will appear here.</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Driver (Optional)</label>
                            <select class="form-select" name="driver_id">
                                <option value="">No driver needed</option>';
foreach ($drivers as $driver) {
    $content .= '
                                <option value="' . $driver['id'] . '">' . htmlspecialchars($driver['name']) . ' - ' . htmlspecialchars($driver['license_number']) . '</option>';
}
$content .= '
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Method</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="payment_method" id="payment_cash" value="cash" required>
                                <label class="form-check-label" for="payment_cash">Cash</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="payment_method" id="payment_transfer" value="transfer" required>
                                <label class="form-check-label" for="payment_transfer">Bank Transfer</label>
                            </div>
                        </div>

                        <div class="mb-3" id="payment_proof_group" style="display:none;">
                            <label for="payment_proof" class="form-label">Upload Payment Proof <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="payment_proof" name="payment_proof" accept="image/jpeg,image/png">
                            <div class="form-text">Allowed formats: JPG, JPEG, PNG. Max 5MB.</div>
                        </div>

                        <div class="alert alert-info">
                            <h5 class="alert-heading">Important Notes:</h5>
                            <ul class="mb-0">
                                <li>Rental duration is calculated in hours</li>
                                <li>Minimum rental duration is <b>3 hours</b></li>
                                <li>Driver service is optional</li>
                                <li>Payment will be made when picking up the vehicle</li>
                            </ul>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">Back</a>
                            <button type="submit" class="btn btn-primary">Submit Order</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>';

$content .= '
<script>
function updateEstimate() {
    const start = document.getElementById("start_date").value;
    const end = document.getElementById("end_date").value;
    const pricePerHour = ' . (float)$vehicle['price_per_hour'] . ';
    const isOutOfTown = document.getElementById("luar_kota").checked;
    if (start && end) {
        const startDate = new Date(start);
        const endDate = new Date(end);
        let hours = Math.ceil((endDate - startDate) / 3600000);
        if (hours < 1) hours = 0;
        let total = hours * pricePerHour;
        let surcharge = 0;
        if (isOutOfTown) {
            surcharge = total * 0.2;
            total += surcharge;
        }
        let text = `Durasi: ${hours} jam | Harga: Rp ${total.toLocaleString(\'id-ID\')}`;
        if (isOutOfTown && hours > 0) {
            text += ` (termasuk biaya luar kota: Rp ${surcharge.toLocaleString(\'id-ID\')})`;
        }
        document.getElementById("estimate_box").style.display = hours > 0 ? "flex" : "none";
        document.getElementById("estimate_text").innerText = text;
    } else {
        document.getElementById("estimate_box").style.display = "none";
    }
}
document.getElementById("start_date").addEventListener("change", updateEstimate);
document.getElementById("end_date").addEventListener("change", updateEstimate);
document.getElementById("dalam_kota").addEventListener("change", updateEstimate);
document.getElementById("luar_kota").addEventListener("change", updateEstimate);
</script>';

require_once '../includes/layout.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
// Form validation
(function() {
    "use strict";
    var forms = document.querySelectorAll(".needs-validation");
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener("submit", function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add("was-validated");
        }, false);
    });
})();

// Date validation
document.querySelector("input[name=end_date]").addEventListener("change", function() {
    var start = document.querySelector("input[name=start_date]").value;
    var end = this.value;
    if (start && end && new Date(end) <= new Date(start)) {
        this.setCustomValidity("End date must be after start date");
    } else {
        this.setCustomValidity("");
    }
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

    // Estimasi biaya muncul default
    updateEstimate();
});
</script>
<?php
?> 