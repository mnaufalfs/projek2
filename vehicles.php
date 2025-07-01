<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/auth.php';

// Get filter parameters
$type_id = isset($_GET['type']) ? (int)$_GET['type'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

// Build query
$query = "
    SELECT v.*, vt.name as type_name, vt.icon
    FROM vehicles v
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    WHERE v.status = 'available'";
$params = [];
$types = '';

// Kumpulkan kondisi filter selain status
$or_filters = [];

if ($type_id) {
    $or_filters[] = "v.vehicle_type_id = ?";
    $params[] = $type_id;
    $types .= 'i';
}

if ($search) {
    $or_filters[] = "(v.name LIKE ? OR v.plate_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

if ($min_price !== null && $min_price > 0) {
    $or_filters[] = "v.price_per_hour >= ?";
    $params[] = $min_price;
    $types .= 'i';
}

if ($max_price !== null && $max_price > 0) {
    $or_filters[] = "v.price_per_hour <= ?";
    $params[] = $max_price;
    $types .= 'i';
}

// Gabungkan filter dengan OR jika ada
if (!empty($or_filters)) {
    $query .= " AND (" . implode(' OR ', $or_filters) . ")";
}

// Add sorting
switch ($sort) {
    case 'price_asc':
        $query .= " ORDER BY v.price_per_hour ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY v.price_per_hour DESC";
        break;
    case 'name_desc':
        $query .= " ORDER BY v.name DESC";
        break;
    default:
        $query .= " ORDER BY v.name ASC";
}

// Get vehicles
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$vehicles = $result->fetch_all(MYSQLI_ASSOC);

// Get vehicle types for filter
$result = $conn->query("SELECT * FROM vehicle_types ORDER BY name");
$vehicle_types = $result->fetch_all(MYSQLI_ASSOC);

// Start output buffering
ob_start();
?>

<!-- Page Header -->
<div class="bg-primary text-white py-5 mb-5">
    <div class="container">
        <h1 class="display-4">Our Vehicles</h1>
        <p class="lead">Find the perfect vehicle for your needs</p>
    </div>
</div>

<!-- Filters -->
<div class="container mb-5">
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Vehicle Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <?php foreach ($vehicle_types as $type): ?>
                            <option value="<?= $type['id'] ?>" <?= $type_id == $type['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Search vehicles...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Min Price/Hour</label>
                    <input type="number" name="min_price" class="form-control" value="<?= $min_price ?>" placeholder="Min price">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Max Price/Hour</label>
                    <input type="number" name="max_price" class="form-control" value="<?= $max_price ?>" placeholder="Max price">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sort By</label>
                    <select name="sort" class="form-select">
                        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                        <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price (Low-High)</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price (High-Low)</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i> Apply Filters
                    </button>
                    <a href="vehicles.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i> Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Vehicles List -->
<div class="container mb-5">
    <?php if (empty($vehicles)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> No vehicles found matching your criteria.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($vehicles as $vehicle): ?>
                <div class="col-md-4">
                    <div class="card h-100">
                        <img src="<?= APP_URL ?>/assets/img/vehicles/<?= $vehicle['image'] ?>" class="card-img-top" alt="<?= htmlspecialchars($vehicle['name']) ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($vehicle['name']) ?></h5>
                            <p class="card-text">
                                <i class="<?= $vehicle['icon'] ?> me-2"></i>
                                <?= htmlspecialchars($vehicle['type_name']) ?>
                            </p>
                            <p class="card-text">
                                <i class="fas fa-tag me-2"></i>
                                <?= formatPrice($vehicle['price_per_hour']) ?> / hour
                            </p>
                            <p class="card-text">
                                <i class="fas fa-car me-2"></i>
                                <?= htmlspecialchars($vehicle['plate_number']) ?>
                            </p>
                            <p class="card-text">
                                <i class="fas fa-info-circle me-2"></i>
                                <?= htmlspecialchars($vehicle['description']) ?>
                            </p>
                            <?php if (isLoggedIn()): ?>
                                <a href="user/rent_vehicle.php?id=<?= $vehicle['id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-car me-2"></i> Rent Now
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i> Login to Rent
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 