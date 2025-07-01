<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('my_orders.php');
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

// Check if order exists and belongs to user
$stmt = $conn->prepare("
    SELECT status 
    FROM rental_orders 
    WHERE id = ? AND user_id = ? AND status = 'pending'
");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    setFlashMessage('error', 'Order not found or cannot be cancelled');
    redirect('my_orders.php');
}

// Cancel order
$stmt = $conn->prepare("UPDATE rental_orders SET status = 'cancelled' WHERE id = ?");
$stmt->bind_param("i", $order_id);

if ($stmt->execute()) {
    setFlashMessage('success', 'Order cancelled successfully');
} else {
    setFlashMessage('error', 'Failed to cancel order');
}

redirect('my_orders.php');
?> 