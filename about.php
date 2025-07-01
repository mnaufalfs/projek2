<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Start output buffering
ob_start();
?>

<!-- Page Header -->
<div class="bg-primary text-white py-5 mb-5">
    <div class="container">
        <h1 class="display-4">About Us</h1>
        <p class="lead">Learn more about our company and services</p>
    </div>
</div>

<!-- Company Overview -->
<div class="container mb-5">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h2 class="mb-4">Our Story</h2>
            <p class="lead">We are a leading vehicle rental company committed to providing excellent service and quality vehicles to our customers.</p>
            <p>Founded in 2024, we have grown to become one of the most trusted names in the vehicle rental industry. Our mission is to make transportation accessible and convenient for everyone.</p>
            <p>We take pride in our extensive fleet of well-maintained vehicles and our team of professional drivers who ensure a safe and comfortable journey for our customers.</p>
        </div>
        <div class="col-md-6">
            <img src="assets/images/about-company.jpg" alt="Our Company" class="img-fluid rounded shadow">
        </div>
    </div>
</div>

<!-- Our Values -->
<div class="container mb-5">
    <h2 class="text-center mb-4">Our Values</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-star fa-3x text-primary mb-3"></i>
                    <h3 class="card-title">Quality Service</h3>
                    <p class="card-text">We are committed to providing the highest quality service to our customers, ensuring their satisfaction and comfort.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                    <h3 class="card-title">Safety First</h3>
                    <p class="card-text">Safety is our top priority. We maintain our vehicles regularly and ensure our drivers are well-trained and experienced.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-handshake fa-3x text-primary mb-3"></i>
                    <h3 class="card-title">Customer Trust</h3>
                    <p class="card-text">We value the trust of our customers and work hard to maintain it through transparency and reliable service.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Our Team -->
<div class="container mb-5">
    <h2 class="text-center mb-4">Our Team</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100">
                <img src="assets/images/team-1.jpg" class="card-img-top" alt="Team Member">
                <div class="card-body text-center">
                    <h3 class="card-title">John Smith</h3>
                    <p class="text-muted">CEO & Founder</p>
                    <p class="card-text">With over 20 years of experience in the automotive industry, John leads our company with vision and expertise.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <img src="assets/images/team-2.jpg" class="card-img-top" alt="Team Member">
                <div class="card-body text-center">
                    <h3 class="card-title">Sarah Johnson</h3>
                    <p class="text-muted">Operations Manager</p>
                    <p class="card-text">Sarah ensures smooth operations and maintains the highest standards of service quality.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <img src="assets/images/team-3.jpg" class="card-img-top" alt="Team Member">
                <div class="card-body text-center">
                    <h3 class="card-title">Michael Brown</h3>
                    <p class="text-muted">Customer Service Head</p>
                    <p class="card-text">Michael and his team are dedicated to providing exceptional customer service and support.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Why Choose Us -->
<div class="container mb-5">
    <h2 class="text-center mb-4">Why Choose Us</h2>
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-car fa-3x text-primary mb-3"></i>
                    <h3 class="card-title">Wide Selection</h3>
                    <p class="card-text">Choose from our extensive fleet of vehicles to suit your needs.</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-dollar-sign fa-3x text-primary mb-3"></i>
                    <h3 class="card-title">Competitive Prices</h3>
                    <p class="card-text">Enjoy affordable rates with no hidden charges.</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-3x text-primary mb-3"></i>
                    <h3 class="card-title">24/7 Support</h3>
                    <p class="card-text">Our customer support team is available round the clock.</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-map-marker-alt fa-3x text-primary mb-3"></i>
                    <h3 class="card-title">Nationwide Service</h3>
                    <p class="card-text">We provide service across multiple locations.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Call to Action -->
<div class="bg-primary text-white py-5 mb-5">
    <div class="container text-center">
        <h2 class="mb-4">Ready to Experience Our Service?</h2>
        <p class="lead mb-4">Join thousands of satisfied customers who have chosen our service.</p>
        <?php if (!isLoggedIn()): ?>
            <a href="register.php" class="btn btn-light btn-lg me-3">Register Now</a>
            <a href="login.php" class="btn btn-outline-light btn-lg">Login</a>
        <?php else: ?>
            <a href="vehicles.php" class="btn btn-light btn-lg">Browse Vehicles</a>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 