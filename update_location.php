<?php
require_once 'db.php';

$rider_id = (int)($_POST['rider_id'] ?? 0);
$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;

if ($rider_id <= 0 || $lat === null || $lng === null) {
    echo "missing data";
    exit;
}

$sql = "INSERT INTO rider_locations (rider_id, latitude, longitude) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("idd", $rider_id, $lat, $lng);
$stmt->execute();

echo "ok";