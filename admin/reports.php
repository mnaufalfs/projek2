<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/auth.php';
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Filter
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';
$vehicle_type = $_GET['vehicle_type'] ?? '';
$payment_method = $_GET['payment_method'] ?? '';

$where = "WHERE DATE(ro.created_at) BETWEEN ? AND ?";
$params = [$start_date, $end_date];
$types = 'ss';
if ($status) { $where .= " AND ro.status = ?"; $params[] = $status; $types .= 's'; }
if ($vehicle_type) { $where .= " AND vt.id = ?"; $params[] = $vehicle_type; $types .= 'i'; }
if ($payment_method) { $where .= " AND ro.payment_method = ?"; $params[] = $payment_method; $types .= 's'; }

// Query data customer
$customer_id = $_GET['customer_id'] ?? '';
if ($customer_id) {
    $where .= " AND ro.user_id = ?";
    $params[] = $customer_id;
    $types .= 'i';
}

// Data statistik
$stat = $conn->prepare("
    SELECT COUNT(*) as total_orders,
           SUM(ro.total_price) as total_booking_fee,
           SUM(COALESCE(rr.total_additional_fee,0)) as total_additional_fee,
           SUM(ro.total_price + COALESCE(rr.total_additional_fee,0)) as total_revenue,
           COUNT(DISTINCT ro.user_id) as unique_customers
    FROM rental_orders ro
    JOIN vehicles v ON ro.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    LEFT JOIN rental_returns rr ON ro.id = rr.order_id
    $where
");
$stat->bind_param($types, ...$params);
$stat->execute();
$rekap = $stat->get_result()->fetch_assoc();

// Data grafik: order per bulan
$chart_orders = $conn->prepare("
    SELECT DATE_FORMAT(ro.created_at, '%Y-%m') as bulan, COUNT(*) as total
    FROM rental_orders ro
    JOIN vehicles v ON ro.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    $where
    GROUP BY bulan ORDER BY bulan
");
$chart_orders->bind_param($types, ...$params);
$chart_orders->execute();
$orders_per_month = $chart_orders->get_result()->fetch_all(MYSQLI_ASSOC);

// Data grafik: revenue per bulan
$chart_revenue = $conn->prepare("
    SELECT DATE_FORMAT(ro.created_at, '%Y-%m') as bulan, SUM(ro.total_price + COALESCE(rr.total_additional_fee,0)) as revenue
    FROM rental_orders ro
    JOIN vehicles v ON ro.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    LEFT JOIN rental_returns rr ON ro.id = rr.order_id
    $where
    GROUP BY bulan ORDER BY bulan
");
$chart_revenue->bind_param($types, ...$params);
$chart_revenue->execute();
$revenue_per_month = $chart_revenue->get_result()->fetch_all(MYSQLI_ASSOC);

// Data grafik: distribusi status
$chart_status = $conn->prepare("
    SELECT ro.status, COUNT(*) as total
    FROM rental_orders ro
    JOIN vehicles v ON ro.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    $where
    GROUP BY ro.status
");
$chart_status->bind_param($types, ...$params);
$chart_status->execute();
$status_dist = $chart_status->get_result()->fetch_all(MYSQLI_ASSOC);

// Data grafik: distribusi payment method
$chart_payment = $conn->prepare("
    SELECT ro.payment_method, COUNT(*) as total
    FROM rental_orders ro
    JOIN vehicles v ON ro.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    $where
    GROUP BY ro.payment_method
");
$chart_payment->bind_param($types, ...$params);
$chart_payment->execute();
$payment_dist = $chart_payment->get_result()->fetch_all(MYSQLI_ASSOC);

// Data grafik: order per vehicle type
$chart_vehicle_orders = $conn->prepare("
    SELECT vt.name as vehicle_type, COUNT(*) as total
    FROM rental_orders ro
    JOIN vehicles v ON ro.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    $where
    GROUP BY vt.id ORDER BY total DESC
");
$chart_vehicle_orders->bind_param($types, ...$params);
$chart_vehicle_orders->execute();
$vehicle_orders = $chart_vehicle_orders->get_result()->fetch_all(MYSQLI_ASSOC);

// Data grafik: revenue per vehicle type
$chart_vehicle_revenue = $conn->prepare("
    SELECT vt.name as vehicle_type, SUM(ro.total_price + COALESCE(rr.total_additional_fee,0)) as revenue
    FROM rental_orders ro
    JOIN vehicles v ON ro.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    LEFT JOIN rental_returns rr ON ro.id = rr.order_id
    $where
    GROUP BY vt.id ORDER BY revenue DESC
");
$chart_vehicle_revenue->bind_param($types, ...$params);
$chart_vehicle_revenue->execute();
$vehicle_revenue = $chart_vehicle_revenue->get_result()->fetch_all(MYSQLI_ASSOC);

// Data grafik: top customer by order
$chart_top_customer = $conn->prepare("
    SELECT u.name as customer, COUNT(*) as total
    FROM rental_orders ro
    JOIN users u ON ro.user_id = u.id
    $where
    GROUP BY ro.user_id ORDER BY total DESC LIMIT 10
");
$chart_top_customer->bind_param($types, ...$params);
$chart_top_customer->execute();
$top_customers = $chart_top_customer->get_result()->fetch_all(MYSQLI_ASSOC);

// Data vehicle types
$vehicle_types = $conn->query("SELECT * FROM vehicle_types ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Data detail order
$detail = $conn->prepare("
    SELECT ro.*, u.name as customer_name, v.name as vehicle_name, v.plate_number, vt.name as vehicle_type,
           d.name as driver_name, rr.return_date, rr.late_fee, rr.pickup_fee, rr.total_additional_fee
    FROM rental_orders ro
    JOIN users u ON ro.user_id = u.id
    JOIN vehicles v ON ro.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    LEFT JOIN drivers d ON ro.driver_id = d.id
    LEFT JOIN rental_returns rr ON ro.id = rr.order_id
    $where
    ORDER BY ro.created_at DESC
");
$detail->bind_param($types, ...$params);
$detail->execute();
$orders = $detail->get_result()->fetch_all(MYSQLI_ASSOC);

$content = '<div class="container py-4">
<h2 class="mb-4">Reports</h2>
<form method="GET" class="row g-3 mb-4 align-items-end">
    <div class="col-md-2">
        <label class="form-label">Start Date</label>
        <input type="date" class="form-control" name="start_date" value="'.htmlspecialchars($start_date).'">
    </div>
    <div class="col-md-2">
        <label class="form-label">End Date</label>
        <input type="date" class="form-control" name="end_date" value="'.htmlspecialchars($end_date).'">
    </div>
    <div class="col-md-2">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
            <option value="">All</option>
            <option value="pending"'.($status==='pending'?' selected':'').'>Pending</option>
            <option value="confirmed"'.($status==='confirmed'?' selected':'').'>Confirmed</option>
            <option value="ongoing"'.($status==='ongoing'?' selected':'').'>Ongoing</option>
            <option value="completed"'.($status==='completed'?' selected':'').'>Completed</option>
            <option value="cancelled"'.($status==='cancelled'?' selected':'').'>Cancelled</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Vehicle Type</label>
        <select class="form-select" name="vehicle_type">
            <option value="">All</option>';
foreach ($vehicle_types as $type) {
    $content .= '<option value="'.$type['id'].'"'.($vehicle_type==$type['id']?' selected':'').'>'.htmlspecialchars($type['name']).'</option>';
}
$content .= '</select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Payment Method</label>
        <select class="form-select" name="payment_method">
            <option value="">All</option>
            <option value="cash"'.($payment_method==='cash'?' selected':'').'>Cash</option>
            <option value="transfer"'.($payment_method==='transfer'?' selected':'').'>Transfer</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Customer</label>
        <select class="form-select select2-customer" name="customer_id" data-placeholder="Search customer...">
            <option value="">All</option>';
if ($customer_id) {
    $cust = $conn->query("SELECT id, name, email, phone FROM users WHERE id=".(int)$customer_id)->fetch_assoc();
    if ($cust) {
        $label = htmlspecialchars($cust['name']) . ' (ID: ' . $cust['id'] . ', ' . htmlspecialchars($cust['email']) . ', ' . htmlspecialchars($cust['phone']) . ')';
        $content .= '<option value="'.$cust['id'].'" selected>'.$label.'</option>';
    }
}
$content .= '</select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">Filter</button>
    </div>
</form>

<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-title">Total Orders</h6>
                <h4>'.($rekap['total_orders']??0).'</h4>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-title">Booking Fee</h6>
                <h4>'.formatPrice($rekap['total_booking_fee']??0).'</h4>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-title">Additional Fee</h6>
                <h4>'.formatPrice($rekap['total_additional_fee']??0).'</h4>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-title">Total Revenue</h6>
                <h4>'.formatPrice($rekap['total_revenue']??0).'</h4>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <h6 class="card-title">Unique Customers</h6>
                <h4>'.($rekap['unique_customers']??0).'</h4>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6 mb-4">
        <canvas id="ordersChart"></canvas>
    </div>
    <div class="col-md-6 mb-4">
        <canvas id="revenueChart"></canvas>
    </div>
    <div class="col-md-6 mb-4">
        <canvas id="statusChart"></canvas>
    </div>
    <div class="col-md-6 mb-4">
        <canvas id="paymentChart"></canvas>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6 mb-4">
        <canvas id="vehicleOrdersChart"></canvas>
    </div>
    <div class="col-md-6 mb-4">
        <canvas id="vehicleRevenueChart"></canvas>
    </div>
    <div class="col-md-12 mb-4">
        <canvas id="topCustomerChart"></canvas>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Order Details</h5>
            <div>
                <a href="export_orders.php?'.http_build_query($_GET).'&format=excel" class="btn btn-success me-2"><i class="fas fa-file-excel"></i> Export Excel</a>
                <a href="export_orders.php?'.http_build_query($_GET).'&format=pdf" class="btn btn-danger"><i class="fas fa-file-pdf"></i> Export PDF</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Vehicle</th>
                        <th>Plate</th>
                        <th>Driver</th>
                        <th>Status</th>
                        <th>Booking Fee</th>
                        <th>Additional Fee</th>
                        <th>Total Fee</th>
                    </tr>
                </thead>
                <tbody>';
foreach ($orders as $order) {
    $content .= '<tr>
        <td>'.generateReceiptNumber('booking', $order['id'], $order['created_at']).'</td>
        <td>'.date('d M Y H:i', strtotime($order['created_at'])).'</td>
        <td>'.htmlspecialchars($order['customer_name']).'</td>
        <td>'.htmlspecialchars($order['vehicle_type']).' - '.htmlspecialchars($order['vehicle_name']).'</td>
        <td>'.htmlspecialchars($order['plate_number']).'</td>
        <td>'.($order['driver_name'] ? htmlspecialchars($order['driver_name']) : '<span class="text-muted">No driver</span>').'</td>
        <td><span class="badge bg-'.($order['status'] === 'pending' ? 'warning' : ($order['status'] === 'confirmed' ? 'info' : ($order['status'] === 'ongoing' ? 'primary' : ($order['status'] === 'completed' ? 'success' : 'danger')))).'">'.ucfirst($order['status']).'</span></td>
        <td>'.formatPrice($order['total_price']).'</td>
        <td>'.formatPrice(($order['late_fee']??0)+($order['pickup_fee']??0)).'</td>
        <td>'.formatPrice($order['total_price']+($order['late_fee']??0)+($order['pickup_fee']??0)).'</td>
    </tr>';
}
$content .= '</tbody></table></div></div></div>';

// Pie chart status: legend warna konsisten
$status_labels = ['completed', 'cancelled', 'ongoing', 'pending', 'confirmed'];
$status_colors = [
    'completed' => '#388E3C', // green
    'cancelled' => '#D32F2F', // red
    'ongoing' => '#0288D1', // blue
    'pending' => '#FBC02D', // yellow
    'confirmed' => '#7B1FA2', // purple
];
$status_data = [];
$status_color_data = [];
foreach ($status_labels as $label) {
    $found = false;
    foreach ($status_dist as $row) {
        if ($row['status'] === $label) {
            $status_data[] = (int)$row['total'];
            $found = true;
            break;
        }
    }
    if (!$found) $status_data[] = 0;
    $status_color_data[] = $status_colors[$label];
}

// Chart.js data
$content .= '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ordersChart = document.getElementById("ordersChart").getContext("2d");
const revenueChart = document.getElementById("revenueChart").getContext("2d");
const statusChart = document.getElementById("statusChart").getContext("2d");
const paymentChart = document.getElementById("paymentChart").getContext("2d");
const vehicleOrdersChart = document.getElementById("vehicleOrdersChart").getContext("2d");
const vehicleRevenueChart = document.getElementById("vehicleRevenueChart").getContext("2d");
const topCustomerChart = document.getElementById("topCustomerChart").getContext("2d");

const ordersData = {
    labels: '.json_encode(array_column($orders_per_month, 'bulan')).',
    datasets: [{
        label: "Orders",
        data: '.json_encode(array_column($orders_per_month, 'total')).',
        backgroundColor: "#0288D1"
    }]
};
const revenueData = {
    labels: '.json_encode(array_column($revenue_per_month, 'bulan')).',
    datasets: [{
        label: "Revenue",
        data: '.json_encode(array_column($revenue_per_month, 'revenue')).',
        backgroundColor: "#388E3C"
    }]
};
const statusData = {
    labels: '.json_encode($status_labels).',
    datasets: [{
        label: "Status",
        data: '.json_encode($status_data).',
        backgroundColor: '.json_encode($status_color_data).'
    }]
};
const paymentData = {
    labels: '.json_encode(array_column($payment_dist, 'payment_method')).',
    datasets: [{
        label: "Payment Method",
        data: '.json_encode(array_column($payment_dist, 'total')).',
        backgroundColor: ["#0288D1", "#388E3C", "#FBC02D"]
    }]
};
new Chart(ordersChart, { type: "bar", data: ordersData });
new Chart(revenueChart, { type: "bar", data: revenueData });
new Chart(statusChart, { type: "pie", data: statusData });
new Chart(paymentChart, { type: "pie", data: paymentData });

const vehicleOrdersData = {
    labels: '.json_encode(array_column($vehicle_orders, 'vehicle_type')).',
    datasets: [{ label: "Orders", data: '.json_encode(array_column($vehicle_orders, 'total')).', backgroundColor: "#7B1FA2" }]
};
const vehicleRevenueData = {
    labels: '.json_encode(array_column($vehicle_revenue, 'vehicle_type')).',
    datasets: [{ label: "Revenue", data: '.json_encode(array_column($vehicle_revenue, 'revenue')).', backgroundColor: "#FBC02D" }]
};
const topCustomerData = {
    labels: '.json_encode(array_column($top_customers, 'customer')).',
    datasets: [{ label: "Orders", data: '.json_encode(array_column($top_customers, 'total')).', backgroundColor: "#0288D1" }]
};
new Chart(vehicleOrdersChart, { type: "bar", data: vehicleOrdersData });
new Chart(vehicleRevenueChart, { type: "bar", data: vehicleRevenueData });
new Chart(topCustomerChart, { type: "bar", data: topCustomerData });
</script>';

// Tambahkan langsung tag <link> dan <script> di $content dengan urutan benar
$content .= '
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    if (typeof $ === "undefined") {
        console.error("jQuery not loaded!");
        return;
    }
    if ($(".select2-customer").length === 0) {
        console.warn("select2-customer not found in DOM");
    }
    if (typeof $.fn.select2 === "undefined") {
        console.error("Select2 plugin not loaded!");
        return;
    }
    $(".select2-customer").select2({
        width: "100%",
        allowClear: true,
        ajax: {
            url: "customer_search.php",
            dataType: "json",
            delay: 250,
            data: function(params) {
                return { term: params.term };
            },
            processResults: function(data) {
                return { results: data.results };
            },
            cache: true
        },
        placeholder: "Search customer by name or ID",
        minimumInputLength: 1
    });
});
</script>
';

require_once '../includes/layout.php'; 