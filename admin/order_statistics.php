<?php
require_once '../config/auth.php';
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get date range from request or default to current month
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get overall statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN status = 'ongoing' THEN 1 END) as ongoing_orders,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
        SUM(CASE WHEN status IN ('completed', 'ongoing') THEN total_price ELSE 0 END) as total_revenue,
        AVG(CASE WHEN status = 'completed' THEN total_price ELSE NULL END) as avg_order_value
    FROM rental_orders
    WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
")->fetch_assoc();

// Get revenue by vehicle type
$revenue_by_type = $conn->query("
    SELECT 
        vt.name as vehicle_type,
        COUNT(*) as total_orders,
        SUM(ro.total_price) as total_revenue
    FROM rental_orders ro
    JOIN vehicles v ON ro.vehicle_id = v.id
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    WHERE ro.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
    AND ro.status IN ('completed', 'ongoing')
    GROUP BY vt.id
    ORDER BY total_revenue DESC
")->fetch_all(MYSQLI_ASSOC);

// Get daily revenue for the last 30 days
$daily_revenue = $conn->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as total_orders,
        SUM(total_price) as revenue
    FROM rental_orders
    WHERE created_at BETWEEN DATE_SUB('$end_date', INTERVAL 30 DAY) AND '$end_date 23:59:59'
    AND status IN ('completed', 'ongoing')
    GROUP BY DATE(created_at)
    ORDER BY date
")->fetch_all(MYSQLI_ASSOC);

// Get top customers
$top_customers = $conn->query("
    SELECT 
        u.name as customer_name,
        COUNT(*) as total_orders,
        SUM(ro.total_price) as total_spent
    FROM rental_orders ro
    JOIN users u ON ro.user_id = u.id
    WHERE ro.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
    AND ro.status IN ('completed', 'ongoing')
    GROUP BY u.id
    ORDER BY total_spent DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$content = '
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Order Statistics</h2>
        <form method="GET" class="d-flex gap-2">
            <input type="date" class="form-control" name="start_date" value="' . $start_date . '">
            <input type="date" class="form-control" name="end_date" value="' . $end_date . '">
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>

    <div class="row">
        <!-- Overall Statistics -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Total Orders</h5>
                    <h2 class="mb-0">' . $stats['total_orders'] . '</h2>
                    <small class="text-muted">
                        ' . $stats['completed_orders'] . ' completed, ' . 
                        $stats['ongoing_orders'] . ' ongoing, ' . 
                        $stats['cancelled_orders'] . ' cancelled
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <h2 class="mb-0">' . formatPrice($stats['total_revenue']) . '</h2>
                    <small class="text-muted">
                        Avg. ' . formatPrice($stats['avg_order_value']) . ' per order
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Completion Rate</h5>
                    <h2 class="mb-0">' . round(($stats['completed_orders'] / $stats['total_orders']) * 100, 1) . '%</h2>
                    <small class="text-muted">
                        ' . $stats['completed_orders'] . ' of ' . $stats['total_orders'] . ' orders
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Cancellation Rate</h5>
                    <h2 class="mb-0">' . round(($stats['cancelled_orders'] / $stats['total_orders']) * 100, 1) . '%</h2>
                    <small class="text-muted">
                        ' . $stats['cancelled_orders'] . ' of ' . $stats['total_orders'] . ' orders
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Revenue by Vehicle Type -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Revenue by Vehicle Type</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Vehicle Type</th>
                                    <th>Orders</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>';
foreach ($revenue_by_type as $type) {
    $content .= '
                                <tr>
                                    <td>' . htmlspecialchars($type['vehicle_type']) . '</td>
                                    <td>' . $type['total_orders'] . '</td>
                                    <td>' . formatPrice($type['total_revenue']) . '</td>
                                </tr>';
}
$content .= '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top Customers</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                </tr>
                            </thead>
                            <tbody>';
foreach ($top_customers as $customer) {
    $content .= '
                                <tr>
                                    <td>' . htmlspecialchars($customer['customer_name']) . '</td>
                                    <td>' . $customer['total_orders'] . '</td>
                                    <td>' . formatPrice($customer['total_spent']) . '</td>
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

    <!-- Daily Revenue Chart -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Daily Revenue (Last 30 Days)</h5>
        </div>
        <div class="card-body">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById("revenueChart").getContext("2d");
new Chart(ctx, {
    type: "line",
    data: {
        labels: ' . json_encode(array_column($daily_revenue, "date")) . ',
        datasets: [{
            label: "Revenue",
            data: ' . json_encode(array_column($daily_revenue, "revenue")) . ',
            borderColor: "rgb(75, 192, 192)",
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return "Rp " + value.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>';

require_once '../includes/layout.php';
?> 