<?php
require_once 'db.php';

$order_id = (int)($_POST['order_id'] ?? 0);
$status = trim($_POST['status'] ?? '');

$allowed = ['pending','accepted','delivering','completed'];

if ($order_id <= 0 || !in_array($status, $allowed, true)) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
$stmt->bind_param("si", $status, $order_id);
$stmt->execute();

echo json_encode(['success' => true]);