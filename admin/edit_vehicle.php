<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get vehicle ID from URL
$vehicle_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get vehicle details
$vehicle = $conn->query("
    SELECT v.*, vt.name as vehicle_type
    FROM vehicles v
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    WHERE v.id = $vehicle_id
")->fetch_assoc();

if (!$vehicle) {
    setFlashMessage('error', 'Vehicle not found');
    redirect('vehicles.php');
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
    $image = $vehicle['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/vehicles/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png'];

        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Delete old image if exists
                if ($vehicle['image'] && file_exists('../' . $vehicle['image'])) {
                    unlink('../' . $vehicle['image']);
                }
                $image = 'uploads/vehicles/' . $new_filename;
            }
        }
    }

    $sql = "UPDATE vehicles SET 
            vehicle_type_id = $vehicle_type_id,
            name = '$name',
            plate_number = '$plate_number',
            price_per_hour = $price_per_hour,
            description = '$description',
            image = '$image',
            status = '$status'
            WHERE id = $vehicle_id";

    if ($conn->query($sql)) {
        setFlashMessage('success', 'Vehicle updated successfully');
        redirect('vehicles.php');
    } else {
        setFlashMessage('error', 'Failed to update vehicle: ' . $conn->error);
    }
}

$content = '
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Edit Vehicle</h3>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="vehicle_type_id" class="form-label">Vehicle Type</label>
                            <select class="form-select" id="vehicle_type_id" name="vehicle_type_id" required>';

foreach ($vehicle_types as $type) {
    $selected = $type['id'] === $vehicle['vehicle_type_id'] ? 'selected' : '';
    $content .= '
                                <option value="' . $type['id'] . '" ' . $selected . '>' . htmlspecialchars($type['name']) . '</option>';
}

$content .= '
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Vehicle Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="' . htmlspecialchars($vehicle['name']) . '" required>
                        </div>

                        <div class="mb-3">
                            <label for="plate_number" class="form-label">Plate Number</label>
                            <input type="text" class="form-control" id="plate_number" name="plate_number" value="' . htmlspecialchars($vehicle['plate_number']) . '" required>
                        </div>

                        <div class="mb-3">
                            <label for="price_per_hour" class="form-label">Price per Hour</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="price_per_hour" name="price_per_hour" min="0" step="1000" value="' . $vehicle['price_per_hour'] . '" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3">' . htmlspecialchars($vehicle['description']) . '</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">Vehicle Image</label>';
                            
if ($vehicle['image']) {
    $content .= '
                            <div class="mb-2">
                                <img src="../' . $vehicle['image'] . '" alt="Current Vehicle Image" class="img-thumbnail" style="max-height: 200px;">
                            </div>';
}

$content .= '
                            <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png">
                            <div class="form-text">Allowed formats: JPG, JPEG, PNG. Leave empty to keep current image.</div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="available" ' . ($vehicle['status'] === 'available' ? 'selected' : '') . '>Available</option>
                                <option value="unavailable" ' . ($vehicle['status'] === 'unavailable' ? 'selected' : '') . '>Unavailable</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="vehicles.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Vehicle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>';

require_once '../includes/layout.php';
?> 