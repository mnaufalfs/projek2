<?php
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$term = $_GET['term'] ?? '';
$results = [];

if ($term !== '') {
    $stmt = $conn->prepare("SELECT id, name, email, phone FROM users WHERE role='user' AND (name LIKE ? OR id LIKE ?) ORDER BY name LIMIT 20");
    $like = "%$term%";
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $results[] = [
            'id' => $row['id'],
            'text' => $row['name'] . ' (ID: ' . $row['id'] . ', ' . $row['email'] . ', ' . $row['phone'] . ')'
        ];
    }
}

echo json_encode(['results' => $results]); 