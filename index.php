<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/auth.php';

// Get featured vehicles
$stmt = $db->query("
    SELECT v.*, vt.name as type_name, vt.icon
    FROM vehicles v
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    WHERE v.status = 'available'
    ORDER BY v.created_at DESC
    LIMIT 6
");
$featured_vehicles = [];
while ($row = $stmt->fetch_assoc()) {
    $featured_vehicles[] = $row;
}

// Get vehicle types
$stmt = $db->query("SELECT * FROM vehicle_types ORDER BY name");
$vehicle_types = [];
while ($row = $stmt->fetch_assoc()) {
    $vehicle_types[] = $row;
}

// Get statistics
$stmt = $db->query("SELECT COUNT(*) as total FROM vehicles WHERE status = 'available'");
$available_vehicles = $stmt->fetch_assoc()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM drivers WHERE status = 'available'");
$available_drivers = $stmt->fetch_assoc()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM rental_orders WHERE status = 'completed'");
$completed_orders = $stmt->fetch_assoc()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$total_customers = $stmt->fetch_assoc()['total'];

// Start output buffering
ob_start();
?>

<!-- Hero Section -->
<div class="hero bg-primary text-white py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4 fw-bold mb-4">Rent Your Perfect Vehicle </h1>
                <p class="lead mb-4">Choose from our wide selection of vehicles for your transportation needs. We offer competitive prices and excellent service.</p>
                <a href="vehicles.php" class="btn btn-light btn-lg">
                    <i class="fas fa-car me-2"></i> Browse Vehicles
                </a>
            </div>
            <div class="col-md-6">
                <img src="assets/images/hero-car.png" alt="Hero Car" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<!-- Statistics Section -->
<div class="container mb-5">
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-car fa-3x text-primary mb-3"></i>
                    <h3 class="card-title"><?= $available_vehicles ?></h3>
                    <p class="card-text">Available Vehicles</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-user-tie fa-3x text-primary mb-3"></i>
                    <h3 class="card-title"><?= $available_drivers ?></h3>
                    <p class="card-text">Available Drivers</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-check-circle fa-3x text-primary mb-3"></i>
                    <h3 class="card-title"><?= $completed_orders ?></h3>
                    <p class="card-text">Completed Orders</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                    <h3 class="card-title"><?= $total_customers ?></h3>
                    <p class="card-text">Happy Customers</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vehicle Types Section -->
<div class="container mb-5">
    <h2 class="text-center mb-4">Our Vehicle Types</h2>
    <div class="row g-4">
        <?php foreach ($vehicle_types as $type): ?>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="<?= $type['icon'] ?> fa-3x text-primary mb-3"></i>
                        <h3 class="card-title"><?= htmlspecialchars($type['name']) ?></h3>
                        <p class="card-text"><?= htmlspecialchars($type['description']) ?></p>
                        <a href="vehicles.php?type=<?= $type['id'] ?>" class="btn btn-primary">
                            View Vehicles
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Featured Vehicles Section -->
<div class="container mb-5">
    <h2 class="text-center mb-4">Featured Vehicles</h2>
    <div class="row g-4">
        <?php foreach ($featured_vehicles as $vehicle): ?>
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
                        <a href="user/rent_vehicle.php?id=<?= $vehicle['id'] ?>" class="btn btn-primary">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
        <a href="user/index.php" class="btn btn-outline-primary btn-lg">
            View All Vehicles
        </a>
    </div>
</div>

<!-- How It Works Section -->
<div class="container mb-5">
    <h2 class="text-center mb-4">How It Works</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="display-4 text-primary mb-3">1</div>
                    <h3 class="card-title">Choose Your Vehicle</h3>
                    <p class="card-text">Browse our selection of vehicles and choose the one that suits your needs.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="display-4 text-primary mb-3">2</div>
                    <h3 class="card-title">Book Online</h3>
                    <p class="card-text">Select your rental dates and complete the booking process online.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="display-4 text-primary mb-3">3</div>
                    <h3 class="card-title">Pick Up & Enjoy</h3>
                    <p class="card-text">Pick up your vehicle at the scheduled time and enjoy your journey.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Testimonials Section -->
<div class="container mb-5">
    <h2 class="text-center mb-4">What Our Customers Say</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <img src="assets/images/testimonial-1.jpg" alt="Customer" class="rounded-circle me-3" width="60">
                        <div>
                            <h5 class="card-title mb-0">John Doe</h5>
                            <small class="text-muted">Business Traveler</small>
                        </div>
                    </div>
                    <p class="card-text">"Great service and excellent vehicles. The booking process was smooth and the staff was very helpful."</p>
                    <div class="text-warning">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <img src="assets/images/testimonial-2.jpg" alt="Customer" class="rounded-circle me-3" width="60">
                        <div>
                            <h5 class="card-title mb-0">Jane Smith</h5>
                            <small class="text-muted">Family Vacation</small>
                        </div>
                    </div>
                    <p class="card-text">"We rented a minivan for our family vacation and it was perfect. The vehicle was clean and well-maintained."</p>
                    <div class="text-warning">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <img src="assets/images/testimonial-3.jpg" alt="Customer" class="rounded-circle me-3" width="60">
                        <div>
                            <h5 class="card-title mb-0">Mike Johnson</h5>
                            <small class="text-muted">Weekend Getaway</small>
                        </div>
                    </div>
                    <p class="card-text">"The prices are competitive and the service is top-notch. I'll definitely rent from them again."</p>
                    <div class="text-warning">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Call to Action Section -->
<div class="bg-primary text-white py-5 mb-5">
    <div class="container text-center">
        <h2 class="mb-4">Ready to Rent?</h2>
        <p class="lead mb-4">Join thousands of satisfied customers who have chosen our service.</p>
        <?php if (!isLoggedIn()): ?>
            <a href="register.php" class="btn btn-light btn-lg me-3">Register Now</a>
            <a href="login.php" class="btn btn-outline-light btn-lg">Login</a>
        <?php else: ?>
            <a href="user/index.php" class="btn btn-light btn-lg">Browse Vehicles</a>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 