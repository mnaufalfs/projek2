<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/auth.php';
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$admin_id = $_SESSION['user_id'];
$admin = $conn->query("SELECT * FROM users WHERE id=$admin_id")->fetch_assoc();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $update = $conn->prepare("UPDATE users SET name=?, email=?, phone=?".($password ? ", password=?" : "")." WHERE id=?");
    if ($password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $update->bind_param('ssssi', $name, $email, $phone, $hash, $admin_id);
    } else {
        $update->bind_param('sssi', $name, $email, $phone, $admin_id);
    }
    $update->execute();
    setFlashMessage('success', 'Profile updated successfully');
    redirect('profile.php');
}

$content = '<div class="container py-4">
<h2 class="mb-4">Admin Profile</h2>';
if (isset($_SESSION['message'])) {
    $content .= '<div class="alert alert-'.$_SESSION['message_type'].'">'.$_SESSION['message'].'</div>';
    unset($_SESSION['message'], $_SESSION['message_type']);
}
$content .= '<div class="card mb-4"><div class="card-body">
<form method="POST">
    <div class="mb-3">
        <label class="form-label">Name</label>
        <input type="text" class="form-control" name="name" value="'.htmlspecialchars($admin['name']).'" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" name="email" value="'.htmlspecialchars($admin['email']).'" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Phone</label>
        <input type="text" class="form-control" name="phone" value="'.htmlspecialchars($admin['phone']).'" required>
    </div>
    <div class="mb-3">
        <label class="form-label">New Password <small class="text-muted">(leave blank if not changing)</small></label>
        <input type="password" class="form-control" name="password" autocomplete="new-password">
    </div>
    <button type="submit" class="btn btn-primary">Update Profile</button>
</form>
</div></div></div>';
require_once '../includes/layout.php'; 