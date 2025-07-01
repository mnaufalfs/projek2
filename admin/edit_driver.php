<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get driver ID from URL
$driver_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get driver details
$driver = $conn->query("SELECT * FROM drivers WHERE id = $driver_id")->fetch_assoc();

if (!$driver) {
    setFlashMessage('error', 'Driver not found');
    redirect('drivers.php');
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
        // Check if license number already exists (excluding current driver)
        $stmt = $conn->prepare("SELECT id FROM drivers WHERE license_number = ? AND id != ?");
        $stmt->bind_param("si", $license_number, $driver_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "License number already exists";
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE drivers SET name = ?, phone = ?, license_number = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $phone, $license_number, $status, $driver_id);

        if ($stmt->execute()) {
            setFlashMessage('success', 'Driver updated successfully');
            redirect('drivers.php');
        } else {
            $errors[] = "Failed to update driver: " . $conn->error;
        }
    }
}

$content = '
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Edit Driver</h3>
                </div>
                <div class="card-body">
                    ' . (!empty($errors) ? '<div class="alert alert-danger"><ul class="mb-0"><li>' . implode('</li><li>', $errors) . '</li></ul></div>' : '') . '
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Driver Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="' . htmlspecialchars($driver['name']) . '" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="' . htmlspecialchars($driver['phone']) . '" required>
                        </div>

                        <div class="mb-3">
                            <label for="license_number" class="form-label">License Number</label>
                            <input type="text" class="form-control" id="license_number" name="license_number" value="' . htmlspecialchars($driver['license_number']) . '" required>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="available" ' . ($driver['status'] === 'available' ? 'selected' : '') . '>Available</option>
                                <option value="assigned" ' . ($driver['status'] === 'assigned' ? 'selected' : '') . '>Assigned</option>
                                <option value="off" ' . ($driver['status'] === 'off' ? 'selected' : '') . '>Off</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="drivers.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Driver</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>';

require_once '../includes/layout.php';
?> 