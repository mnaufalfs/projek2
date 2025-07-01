<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/auth.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

// Ambil order ongoing milik user yang belum retur
$orders = $conn->query("
    SELECT ro.*, v.name as vehicle_name, v.plate_number, v.price_per_hour
    FROM rental_orders ro
    JOIN vehicles v ON ro.vehicle_id = v.id
    WHERE ro.user_id = $user_id AND ro.status = 'ongoing' AND NOT EXISTS (
        SELECT 1 FROM rental_returns rr WHERE rr.order_id = ro.id
    )
    ORDER BY ro.end_date ASC
")->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'];
    $return_date = $_POST['return_date'];
    $pickup_option = $_POST['pickup_option'];
    $return_payment_method = $_POST['return_payment_method'];
    
    // Hitung denda
    $order = $conn->query("SELECT * FROM rental_orders WHERE id = $order_id")->fetch_assoc();
    $vehicle = $conn->query("SELECT price_per_hour FROM vehicles WHERE id = {$order['vehicle_id']}")->fetch_assoc();
    $price_per_hour = $vehicle ? (float)$vehicle['price_per_hour'] : 0;
    $end_date = new DateTime($order['end_date']);
    $return = new DateTime($return_date);
    $late_hours = max(0, ($return->getTimestamp() - $end_date->getTimestamp()) / 3600);
    $late_fee = $late_hours * $price_per_hour;
    $pickup_fee = $pickup_option === 'jemput' ? 20000 : 0;
    
    // Hitung biaya jemput
    $total_additional_fee = $late_fee + $pickup_fee;
    
    // Upload bukti pembayaran jika transfer
    $return_payment_proof = null;
    if ($return_payment_method === 'transfer' && isset($_FILES['return_payment_proof'])) {
        $file = $_FILES['return_payment_proof'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'return_proof_' . time() . '.' . $ext;
        $target = '../uploads/payment_proof/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $return_payment_proof = 'uploads/payment_proof/' . $filename;
        }
    }
    
    // Insert ke rental_returns
    $stmt = $conn->prepare("
        INSERT INTO rental_returns (
            order_id, return_date, late_hours, late_fee,
            pickup_option, pickup_fee, total_additional_fee,
            return_payment_method, return_payment_proof, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    $stmt->bind_param(
        "isddsdsss",
        $order_id,
        $return_date,
        $late_hours,
        $late_fee,
        $pickup_option,
        $pickup_fee,
        $total_additional_fee,
        $return_payment_method,
        $return_payment_proof
    );
    
    if ($stmt->execute()) {
        setFlashMessage('success', 'Pengembalian kendaraan berhasil diajukan');
        redirect('my_orders.php');
    } else {
        setFlashMessage('error', 'Gagal mengajukan pengembalian kendaraan');
    }
}

$now = date('Y-m-d\TH:i');

$content = '
<div class="container py-4">
    <h2 class="mb-4">Pengembalian Kendaraan</h2>';
    
if (empty($orders)) {
    $content .= '
    <div class="alert alert-info">
        Tidak ada kendaraan yang perlu dikembalikan.
    </div>';
} else {
    foreach ($orders as $order) {
        $content .= '
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">'.$order['vehicle_name'].'</h5>
                <p class="card-text">
                    <strong>Plat Nomor:</strong> '.$order['plate_number'].'<br>
                    <strong>Tanggal Selesai:</strong> '.date('d M Y H:i', strtotime($order['end_date'])).'
                </p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#returnModal'.$order['id'].'">
                    Kembalikan Kendaraan
                </button>
            </div>
        </div>
        
        <!-- Return Modal -->
        <div class="modal fade" id="returnModal'.$order['id'].'" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="order_id" value="'.$order['id'].'">
                        <div class="modal-header">
                            <h5 class="modal-title">Form Pengembalian Kendaraan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Tanggal & Waktu Pengembalian</label>
                                <input type="datetime-local" class="form-control" name="return_date" id="return_date_'.$order['id'].'" required value="'.$now.'" onchange="updateDenda('.$order['id'].', \''.$order['end_date'].'\', '.$order['price_per_hour'].')">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Opsi Pengembalian</label>
                                <select class="form-select" name="pickup_option" id="pickup_option_'.$order['id'].'" required onchange="updateDenda('.$order['id'].', \''.$order['end_date'].'\', '.$order['price_per_hour'].')">
                                    <option value="antar_sendiri">Antar Sendiri</option>
                                    <option value="jemput">Jemput (Rp 20.000)</option>
                                </select>
                            </div>
                            
                            <div id="denda_info_'.$order['id'].'" class="alert alert-info mb-3"></div>
                            
                            <div id="bayar_retur_group_'.$order['id'].'" class="mb-3">
                                <label class="form-label">Metode Pembayaran Retur</label>
                                <select class="form-select" name="return_payment_method" id="return_payment_method_'.$order['id'].'" required onchange="toggleReturnProof('.$order['id'].')">
                                    <option value="">Pilih metode pembayaran</option>
                                    <option value="cash">Cash</option>
                                    <option value="transfer">Transfer</option>
                                </select>
                            </div>
                            
                            <div id="return_payment_proof_group_'.$order['id'].'" class="mb-3" style="display:none">
                                <label class="form-label">Bukti Transfer</label>
                                <input type="file" class="form-control" name="return_payment_proof" id="return_payment_proof_'.$order['id'].'" accept="image/*">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Ajukan Pengembalian</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';
    }
}

$content .= '
</div>

<script>
function updateDenda(orderId, endDate, pricePerHour) {
    var returnDate = document.getElementById("return_date_" + orderId).value;
    var pickupOption = document.getElementById("pickup_option_" + orderId).value;
    var dendaInfo = document.getElementById("denda_info_" + orderId);
    var bayarReturGroup = document.getElementById("bayar_retur_group_" + orderId);
    
    if (!endDate || !returnDate) {
        dendaInfo.innerHTML = "";
        return;
    }
    var lateHours = Math.max(0, Math.ceil((new Date(returnDate) - new Date(endDate)) / (1000 * 60 * 60)));
    var lateFee = lateHours * pricePerHour;
    var pickupFee = pickupOption === "jemput" ? 20000 : 0;
    var totalFee = lateFee + pickupFee;
    var html = "";
    if (lateHours > 0) {
        html += "Keterlambatan: " + lateHours + " jam<br>";
        html += "Denda keterlambatan: Rp " + lateFee.toLocaleString("id-ID") + "<br>";
    }
    if (pickupFee > 0) {
        html += "Biaya jemput: Rp " + pickupFee.toLocaleString("id-ID") + "<br>";
    }
    if (totalFee > 0) {
        html += "<strong>Total biaya tambahan: Rp " + totalFee.toLocaleString("id-ID") + "</strong>";
    }
    dendaInfo.innerHTML = html;
    // Opsi pembayaran selalu tampil
    bayarReturGroup.style.display = "block";
}

function toggleReturnProof(orderId) {
    var paymentMethod = document.getElementById("return_payment_method_" + orderId).value;
    var proofGroup = document.getElementById("return_payment_proof_group_" + orderId);
    var proofInput = document.getElementById("return_payment_proof_" + orderId);
    if (paymentMethod === "transfer") {
        proofGroup.style.display = "block";
        proofInput.required = true;
    } else {
        proofGroup.style.display = "none";
        proofInput.required = false;
    }
}
</script>';

require_once '../includes/layout.php'; 