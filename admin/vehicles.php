<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Handle vehicle deletion
if (isset($_POST['delete_vehicle'])) {
    $vehicle_id = (int)$_POST['vehicle_id'];
    $conn->query("DELETE FROM vehicles WHERE id = $vehicle_id");
    setFlashMessage('success', 'Vehicle deleted successfully');
    redirect('vehicles.php');
}

// Get all vehicles with their types
$vehicles = $conn->query("
    SELECT v.*, vt.name as vehicle_type
    FROM vehicles v
    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
    ORDER BY v.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$content = '
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Vehicle Management</h2>
        <a href="add_vehicle.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Vehicle
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Plate Number</th>
                            <th>Price/Hour</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';

foreach ($vehicles as $vehicle) {
    $status_class = $vehicle['status'] === 'available' ? 'success' : 'danger';
    
    $content .= '
                        <tr>
                            <td>' . $vehicle['id'] . '</td>
                            <td>' . htmlspecialchars($vehicle['vehicle_type']) . '</td>
                            <td>' . htmlspecialchars($vehicle['name']) . '</td>
                            <td>' . htmlspecialchars($vehicle['plate_number']) . '</td>
                            <td>' . formatPrice($vehicle['price_per_hour']) . '</td>
                            <td><span class="badge bg-' . $status_class . '">' . ucfirst($vehicle['status']) . '</span></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="edit_vehicle.php?id=' . $vehicle['id'] . '">Edit</a></li>
                                        <li><a class="dropdown-item" href="view_vehicle.php?id=' . $vehicle['id'] . '">View Details</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" class="dropdown-item" onsubmit="return confirm(\'Are you sure you want to delete this vehicle?\');">
                                                <input type="hidden" name="vehicle_id" value="' . $vehicle['id'] . '">
                                                <button type="submit" name="delete_vehicle" class="btn btn-link text-danger p-0">Delete</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>';
}

$content .= '
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>';

require_once '../includes/layout.php';
?> 