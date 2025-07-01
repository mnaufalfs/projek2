<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get vehicle types for dropdown
$vehicle_types = $conn->query("SELECT * FROM vehicle_types ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_type_id = (int)$_POST['vehicle_type_id'];
    $name = $conn->real_escape_string($_POST['name']);
    $plate_number = $conn->real_escape_string($_POST['plate_number']);
    $price_per_hour = (float)$_POST['price_per_hour'];
    $description = $conn->real_escape_string($_POST['description']);
    $status = $conn->real_escape_string($_POST['status']);

    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/img/vehicles/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png'];

        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image = $new_filename;
            }
        }
    }

    $sql = "INSERT INTO vehicles (vehicle_type_id, name, plate_number, price_per_hour, description, image, status) 
            VALUES ($vehicle_type_id, '$name', '$plate_number', $price_per_hour, '$description', '$image', '$status')";

    if ($conn->query($sql)) {
        setFlashMessage('success', 'Vehicle added successfully');
        redirect('vehicles.php');
    } else {
        setFlashMessage('error', 'Failed to add vehicle: ' . $conn->error);
    }
}

$content = '
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Add New Vehicle</h3>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="vehicle_type_id" class="form-label">Vehicle Type</label>
                            <select class="form-select" id="vehicle_type_id" name="vehicle_type_id" required>
                                <option value="">Select Vehicle Type</option>';

foreach ($vehicle_types as $type) {
    $content .= '
                                <option value="' . $type['id'] . '">' . htmlspecialchars($type['name']) . '</option>';
}

$content .= '
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Vehicle Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="plate_number" class="form-label">Plate Number</label>
                            <input type="text" class="form-control" id="plate_number" name="plate_number" required>
                        </div>

                        <div class="mb-3">
                            <label for="price_per_hour" class="form-label">Price per Hour</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="price_per_hour" name="price_per_hour" min="0" step="1000" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">Vehicle Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png">
                            <div class="form-text">Allowed formats: JPG, JPEG, PNG</div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="available">Available</option>
                                <option value="unavailable">Unavailable</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="vehicles.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Add Vehicle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>';

require_once '../includes/layout.php';
?> 