<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "กรุณาเข้าสู่ระบบ"
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

$orderId = intval($_POST['order_id'] ?? 0);
$text = trim($_POST['complaint_text'] ?? '');

if ($orderId <= 0 || $text === '') {
    echo json_encode([
        "success" => false,
        "message" => "ข้อมูลไม่ครบ"
    ]);
    exit;
}


/* =========================
   UPLOAD IMAGE
========================= */

$imageName = null;

if(
    isset($_FILES['complaint_image']) &&
    $_FILES['complaint_image']['error'] == 0
){

    $uploadDir = "uploads/complaints/";

    if(!is_dir($uploadDir)){
        mkdir($uploadDir,0777,true);
    }

    $ext = strtolower(pathinfo(
        $_FILES['complaint_image']['name'],
        PATHINFO_EXTENSION
    ));

    $imageName = uniqid() . "." . $ext;

    move_uploaded_file(
        $_FILES['complaint_image']['tmp_name'],
        $uploadDir . $imageName
    );
}

$stmt = $conn->prepare("
    INSERT INTO complaints
    (
        order_id,
        user_id,
        complaint_text,
        complaint_image
    )
    VALUES
    (?, ?, ?, ?)
");

if(!$stmt){
    echo json_encode([
        "success"=>false,
        "message"=>"SQL Prepare Error: ".$conn->error
    ]);
    exit;
}

$stmt->bind_param(
    "iiss",
    $orderId,
    $userId,
    $text,
    $imageName
);

if ($stmt->execute()) {

    echo json_encode([
        "success" => true
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => $conn->error
    ]);
}
?>