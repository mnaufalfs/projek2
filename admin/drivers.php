<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Handle driver deletion
if (isset($_POST['delete_driver'])) {
    $driver_id = (int)$_POST['driver_id'];
    $conn->query("DELETE FROM drivers WHERE id = $driver_id");
    setFlashMessage('success', 'Driver deleted successfully');
    redirect('drivers.php');
}

// Get all drivers
$drivers = $conn->query("
    SELECT d.*, 
           COUNT(DISTINCT ro.id) as total_orders,
           COUNT(DISTINCT CASE WHEN ro.status = 'ongoing' THEN ro.id END) as active_orders
    FROM drivers d
    LEFT JOIN rental_orders ro ON d.id = ro.driver_id
    GROUP BY d.id
    ORDER BY d.name ASC
")->fetch_all(MYSQLI_ASSOC);

$content = '
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Driver Management</h2>
        <a href="add_driver.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Driver
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>License Number</th>
                            <th>Status</th>
                            <th>Orders</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';

foreach ($drivers as $driver) {
    $status_class = $driver['status'] === 'available' ? 'success' : 
                   ($driver['status'] === 'assigned' ? 'warning' : 'danger');
    
    $content .= '
                        <tr>
                            <td>' . $driver['id'] . '</td>
                            <td>' . htmlspecialchars($driver['name']) . '</td>
                            <td>' . htmlspecialchars($driver['phone']) . '</td>
                            <td>' . htmlspecialchars($driver['license_number']) . '</td>
                            <td><span class="badge bg-' . $status_class . '">' . ucfirst($driver['status']) . '</span></td>
                            <td>
                                Total: ' . $driver['total_orders'] . '<br>
                                Active: ' . $driver['active_orders'] . '
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="edit_driver.php?id=' . $driver['id'] . '">Edit</a></li>
                                        <li><a class="dropdown-item" href="view_driver.php?id=' . $driver['id'] . '">View Details</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" class="dropdown-item" onsubmit="return confirm(\'Are you sure you want to delete this driver?\');">
                                                <input type="hidden" name="driver_id" value="' . $driver['id'] . '">
                                                <button type="submit" name="delete_driver" class="btn btn-link text-danger p-0">Delete</button>
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