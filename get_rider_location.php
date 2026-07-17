<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

function jsonResponse(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    jsonResponse([
        'success' => false,
        'message' => 'ไม่พบหมายเลขคำสั่งซื้อ'
    ]);
}

/*
|--------------------------------------------------------------------------
| หา order + rider
|--------------------------------------------------------------------------
*/
$sqlOrder = "
    SELECT 
        o.order_id,
        o.rider_id,
        r.full_name AS rider_name
    FROM orders o
    LEFT JOIN riders r ON o.rider_id = r.rider_id
    WHERE o.order_id = ?
    LIMIT 1
";

$stmtOrder = $conn->prepare($sqlOrder);
if (!$stmtOrder) {
    jsonResponse([
        'success' => false,
        'message' => 'เตรียมข้อมูลคำสั่งซื้อไม่สำเร็จ'
    ]);
}

$stmtOrder->bind_param("i", $order_id);
$stmtOrder->execute();
$orderResult = $stmtOrder->get_result();
$order = $orderResult->fetch_assoc();
$stmtOrder->close();

if (!$order) {
    jsonResponse([
        'success' => false,
        'message' => 'ไม่พบคำสั่งซื้อ'
    ]);
}

$rider_id = (int)($order['rider_id'] ?? 0);

if ($rider_id <= 0) {
    jsonResponse([
        'success' => false,
        'message' => 'ยังไม่มีไรเดอร์รับงาน'
    ]);
}

/*
|--------------------------------------------------------------------------
| ดึงตำแหน่งล่าสุดของไรเดอร์
|--------------------------------------------------------------------------
| หมายเหตุ: ตาราง rider_locations ของคุณไม่มี order_id
| ดังนั้นดึงจาก rider_id ล่าสุดแทน
|--------------------------------------------------------------------------
*/
$sqlLocation = "
    SELECT 
        rider_id,
        latitude,
        longitude,
        updated_at
    FROM rider_locations
    WHERE rider_id = ?
    ORDER BY updated_at DESC, id DESC
    LIMIT 1
";

$stmtLocation = $conn->prepare($sqlLocation);
if (!$stmtLocation) {
    jsonResponse([
        'success' => false,
        'message' => 'เตรียมข้อมูลตำแหน่งไม่สำเร็จ'
    ]);
}

$stmtLocation->bind_param("i", $rider_id);
$stmtLocation->execute();
$locationResult = $stmtLocation->get_result();
$location = $locationResult->fetch_assoc();
$stmtLocation->close();

if (!$location) {
    jsonResponse([
        'success' => false,
        'message' => 'ยังไม่มีข้อมูลตำแหน่งไรเดอร์'
    ]);
}

if ($location['latitude'] === null || $location['longitude'] === null) {
    jsonResponse([
        'success' => false,
        'message' => 'ตำแหน่งไรเดอร์ไม่สมบูรณ์'
    ]);
}

jsonResponse([
    'success'    => true,
    'order_id'   => $order_id,
    'rider_id'   => $rider_id,
    'rider_name' => $order['rider_name'] ?? '',
    'latitude'   => (float)$location['latitude'],
    'longitude'  => (float)$location['longitude'],
    'updated_at' => $location['updated_at'] ?? null
]);
?>