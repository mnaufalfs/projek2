<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/auth.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Get filter parameters
$where_conditions = ["v.status = 'available'"];
$params = [];
$types = '';

if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where_conditions[] = "(v.name LIKE ? OR v.plate_number LIKE ? OR vt.name LIKE ?)";
    $params = array_merge($params, [$search, $search, $search]);
    $types .= 'sss';
}

if (!empty($_GET['vehicle_type'])) {
    $where_conditions[] = "vt.id = ?";
    $params[] = $_GET['vehicle_type'];
    $types .= 'i';
}

if (!empty($_GET['price_min'])) {
    $where_conditions[] = "v.price_per_hour >= ?";
    $params[] = $_GET['price_min'];
    $types .= 'i';
}

if (!empty($_GET['price_max'])) {
    $where_conditions[] = "v.price_per_hour <= ?";
    $params[] = $_GET['price_max'];
    $types .= 'i';
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get vehicles
$query = "
    SELECT v.*, vt.name as vehicle_type
    FROM vehicles v
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    $where_clause
    ORDER BY v.name ASC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$vehicles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get vehicle types for filter
$vehicle_types = $conn->query("SELECT * FROM vehicle_types ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$content = '
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Available Vehicles</h2>
        <a href="my_orders.php" class="btn btn-primary">
            <i class="fas fa-list"></i> My Orders
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Search vehicles..." value="' . htmlspecialchars($_GET['search'] ?? '') . '">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="vehicle_type">
                        <option value="">All Types</option>';
foreach ($vehicle_types as $type) {
    $content .= '
                        <option value="' . $type['id'] . '" ' . (isset($_GET['vehicle_type']) && $_GET['vehicle_type'] == $type['id'] ? 'selected' : '') . '>' . htmlspecialchars($type['name']) . '</option>';
}
$content .= '
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control" name="price_min" placeholder="Min Price" value="' . htmlspecialchars($_GET['price_min'] ?? '') . '">
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control" name="price_max" placeholder="Max Price" value="' . htmlspecialchars($_GET['price_max'] ?? '') . '">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">';

foreach ($vehicles as $vehicle) {
    $content .= '
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <img src="../assets/img/vehicles/' . htmlspecialchars($vehicle['image']) . '" class="card-img-top" alt="' . htmlspecialchars($vehicle['name']) . '" style="height: 200px; object-fit: cover;">
                <div class="card-body">
                    <h5 class="card-title">' . htmlspecialchars($vehicle['name']) . '</h5>
                    <p class="card-text">
                        <span class="badge bg-primary">' . htmlspecialchars($vehicle['vehicle_type']) . '</span>
                        <br>
                        <strong>Plate Number:</strong> ' . htmlspecialchars($vehicle['plate_number']) . '<br>
                        <strong>Price:</strong> ' . formatPrice($vehicle['price_per_hour']) . ' / hour
                    </p>
                    <p class="card-text">' . htmlspecialchars($vehicle['description']) . '</p>
                    <a href="rent_vehicle.php?id=' . $vehicle['id'] . '" class="btn btn-primary">Rent Now</a>
                </div>
            </div>
        </div>';
}

// Tambahkan pesan jika kosong
if (empty($vehicles)) {
    $content .= '
        <div class="alert alert-warning mt-4">
            <i class="fas fa-exclamation-circle me-2"></i>
            Vehicle not found or not available.
        </div>';
}

$content .= '
    </div>
</div>';

require_once '../includes/layout.php';
?> 