<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $license_number = trim($_POST['license_number']);
    $status = $_POST['status'];

    // Validation
    if (empty($name)) {
        $errors[] = "Name is required";
    }

    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }

    if (empty($license_number)) {
        $errors[] = "License number is required";
    } else {
        // Check if license number already exists
        $stmt = $conn->prepare("SELECT id FROM drivers WHERE license_number = ?");
        $stmt->bind_param("s", $license_number);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "License number already exists";
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO drivers (name, phone, license_number, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $phone, $license_number, $status);

        if ($stmt->execute()) {
            setFlashMessage('success', 'Driver added successfully');
            redirect('drivers.php');
        } else {
            $errors[] = "Failed to add driver: " . $conn->error;
        }
    }
}

$content = '
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Add New Driver</h3>
                </div>
                <div class="card-body">
                    ' . (!empty($errors) ? '<div class="alert alert-danger"><ul class="mb-0"><li>' . implode('</li><li>', $errors) . '</li></ul></div>' : '') . '
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Driver Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="' . (isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '') . '" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="' . (isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '') . '" required>
                        </div>

                        <div class="mb-3">
                            <label for="license_number" class="form-label">License Number</label>
                            <input type="text" class="form-control" id="license_number" name="license_number" value="' . (isset($_POST['license_number']) ? htmlspecialchars($_POST['license_number']) : '') . '" required>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="available" ' . (isset($_POST['status']) && $_POST['status'] === 'available' ? 'selected' : '') . '>Available</option>
                                <option value="assigned" ' . (isset($_POST['status']) && $_POST['status'] === 'assigned' ? 'selected' : '') . '>Assigned</option>
                                <option value="off" ' . (isset($_POST['status']) && $_POST['status'] === 'off' ? 'selected' : '') . '>Off</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="drivers.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Add Driver</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>';

require_once '../includes/layout.php';
?> 