<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $errors = [];

    // Validate input
    if (empty($name)) {
        $errors[] = 'Name is required';
    }

    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Invalid email format';
    }

    if (empty($subject)) {
        $errors[] = 'Subject is required';
    }

    if (empty($message)) {
        $errors[] = 'Message is required';
    }

    if (empty($errors)) {
        // Insert message into database
        $stmt = $db->prepare("
            INSERT INTO contact_messages (name, email, subject, message)
            VALUES (?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$name, $email, $subject, $message])) {
            setFlashMessage('success', 'Your message has been sent successfully. We will get back to you soon.');
            redirect(APP_URL . '/contact.php');
        } else {
            $errors[] = 'Failed to send message. Please try again.';
        }
    }
}

// Start output buffering
ob_start();
?>

<!-- Page Header -->
<div class="bg-primary text-white py-5 mb-5">
    <div class="container">
        <h1 class="display-4">Contact Us</h1>
        <p class="lead">Get in touch with us for any inquiries</p>
    </div>
</div>

<!-- Contact Information -->
<div class="container mb-5">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-map-marker-alt fa-3x text-primary mb-3"></i>
                    <h3 class="card-title">Our Location</h3>
                    <p class="card-text">
                        123 Rental Street<br>
                        Jakarta, Indonesia<br>
                        12345
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-phone fa-3x text-primary mb-3"></i>
                    <h3 class="card-title">Phone Numbers</h3>
                    <p class="card-text">
                        Customer Service: +62 123 4567 890<br>
                        Support: +62 123 4567 891<br>
                        Emergency: +62 123 4567 892
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                    <h3 class="card-title">Email Addresses</h3>
                    <p class="card-text">
                        info@rental.com<br>
                        support@rental.com<br>
                        emergency@rental.com
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Form -->
<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h2 class="text-center mb-4">Send Us a Message</h2>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= isset($name) ? htmlspecialchars($name) : '' ?>" required>
                            <div class="invalid-feedback">
                                Please enter your name.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>
                            <div class="invalid-feedback">
                                Please enter a valid email address.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" value="<?= isset($subject) ? htmlspecialchars($subject) : '' ?>" required>
                            <div class="invalid-feedback">
                                Please enter a subject.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required><?= isset($message) ? htmlspecialchars($message) : '' ?></textarea>
                            <div class="invalid-feedback">
                                Please enter your message.
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i> Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Map -->
<div class="container mb-5">
    <div class="card">
        <div class="card-body p-0">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126932.82765732763!2d106.6894292!3d-6.2295715!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f3e945e34b9d%3A0x5371bf0fdad786a2!2sJakarta%2C%20Indonesia!5e0!3m2!1sen!2sus!4v1647881234567!5m2!1sen!2sus"
                width="100%"
                height="450"
                style="border:0;"
                allowfullscreen=""
                loading="lazy">
            </iframe>
        </div>
    </div>
</div>

<!-- Business Hours -->
<div class="container mb-5">
    <div class="card">
        <div class="card-body">
            <h2 class="text-center mb-4">Business Hours</h2>
            <div class="row">
                <div class="col-md-6 offset-md-3">
                    <table class="table">
                        <tbody>
                            <tr>
                                <td>Monday - Friday</td>
                                <td>8:00 AM - 8:00 PM</td>
                            </tr>
                            <tr>
                                <td>Saturday</td>
                                <td>9:00 AM - 6:00 PM</td>
                            </tr>
                            <tr>
                                <td>Sunday</td>
                                <td>10:00 AM - 4:00 PM</td>
                            </tr>
                            <tr>
                                <td>Public Holidays</td>
                                <td>10:00 AM - 4:00 PM</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Form Validation Script -->
<script>
(function() {
    'use strict';
    
    // Fetch all forms we want to apply validation to
    var forms = document.querySelectorAll('.needs-validation');
    
    // Loop over them and prevent submission
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 