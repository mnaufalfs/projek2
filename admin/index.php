<?php
require_once '../config/database.php';
require_once '../config/auth.php';

// Ensure only admin can access this page
if (!isAdmin()) {
    header('Location: ../index.php');
    exit();
}

// Get counts for dashboard
$stmt = $conn->query("SELECT COUNT(*) as count FROM users");
$user_count = $stmt->fetch_assoc()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM vehicles");
$vehicle_count = $stmt->fetch_assoc()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM drivers");
$driver_count = $stmt->fetch_assoc()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM rental_orders");
$order_count = $stmt->fetch_assoc()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0");
$unread_messages = $stmt->fetch_assoc()['count'];

ob_start();
?>
<div class="container py-4">
    <h2 class="mb-4">Dashboard</h2>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <!-- Users Card -->
        <div class="col">
            <a href="users.php" class="text-decoration-none">
                <div class="card h-100 dashboard-card shadow-sm">
                    <div class="card-body text-center">
                        <div class="card-icon text-primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <h5 class="card-title">Users</h5>
                        <p class="card-text display-6"><?php echo $user_count; ?></p>
                        <p class="text-muted">Manage system users</p>
                    </div>
                </div>
            </a>
        </div>
        <!-- Vehicles Card -->
        <div class="col">
            <a href="vehicles.php" class="text-decoration-none">
                <div class="card h-100 dashboard-card shadow-sm">
                    <div class="card-body text-center">
                        <div class="card-icon text-success">
                            <i class="fas fa-car"></i>
                        </div>
                        <h5 class="card-title">Vehicles</h5>
                        <p class="card-text display-6"><?php echo $vehicle_count; ?></p>
                        <p class="text-muted">Manage rental vehicles</p>
                    </div>
                </div>
            </a>
        </div>
        <!-- Drivers Card -->
        <div class="col">
            <a href="drivers.php" class="text-decoration-none">
                <div class="card h-100 dashboard-card shadow-sm">
                    <div class="card-body text-center">
                        <div class="card-icon text-info">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h5 class="card-title">Drivers</h5>
                        <p class="card-text display-6"><?php echo $driver_count; ?></p>
                        <p class="text-muted">Manage drivers</p>
                    </div>
                </div>
            </a>
        </div>
        <!-- Orders Card -->
        <div class="col">
            <a href="orders.php" class="text-decoration-none">
                <div class="card h-100 dashboard-card shadow-sm">
                    <div class="card-body text-center">
                        <div class="card-icon text-warning">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h5 class="card-title">Orders</h5>
                        <p class="card-text display-6"><?php echo $order_count; ?></p>
                        <p class="text-muted">Manage rental orders</p>
                    </div>
                </div>
            </a>
        </div>
        <!-- Messages Card -->
        <div class="col">
            <a href="messages.php" class="text-decoration-none">
                <div class="card h-100 dashboard-card shadow-sm">
                    <div class="card-body text-center">
                        <div class="card-icon text-danger">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h5 class="card-title">Messages</h5>
                        <p class="card-text display-6"><?php echo $unread_messages; ?></p>
                        <p class="text-muted">View contact messages</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
<style>
.dashboard-card {
    transition: transform 0.2s;
    cursor: pointer;
}
.dashboard-card:hover {
    transform: translateY(-5px);
}
.card-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
}
</style>
<?php
$content = ob_get_clean();
require_once '../includes/layout.php'; 