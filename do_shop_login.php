<?php
session_start();
require_once "db.php";

if (!isset($conn)) {
    $conn->set_charset("utf8mb4");
}

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header("Location: shop_login.php?error=" . urlencode("กรุณากรอกอีเมลและรหัสผ่าน"));
    exit;
}

$stmt = $conn->prepare("
    SELECT user_id, 
    name, 
    email, 
    password,
    role, 
    user_status

    FROM users
    WHERE email = ?
    LIMIT 1
");
if (($user['user_status'] ?? 'active') != 'active'){
    header("Location: shop_login.php?error=" . urlencode("บัญชีนี้ถูกระงับหรือปิดใช้งาน"));
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: shop_login.php?error=" . urlencode("ไม่พบบัญชีผู้ใช้นี้"));
    exit;
}

if (!password_verify($password, $user['password'])) {
    header("Location: shop_login.php?error=" . urlencode("รหัสผ่านไม่ถูกต้อง"));
    exit;
}

if (($user['role'] ?? '') !== 'shop') {
    header("Location: shop_login.php?error=" . urlencode("บัญชีนี้ไม่ใช่บัญชีร้านค้า"));
    exit;
}

if (($user['user_status'] ?? 'active') !== 'active'){
    header("Location: shop_login.php?error=" . urlencode("บัญชีนี้ถูกระงับหรือปิดใช้งาน"));
    exit;
}


$stmt = $conn->prepare("
    SELECT shop_id
    FROM shops
    WHERE owner_id = ?
    LIMIT 1
");

$stmt->bind_param("i",$user['user_id']);
$stmt->execute();

$shop = $stmt->get_result()->fetch_assoc();
$_SESSION['shop_id']=$shop['shop_id'];

if (!$shop) {
    header("Location: shop_login.php?error=" . urlencode("ยังไม่พบข้อมูลร้านค้าของบัญชีนี้"));
    exit;
}

$_SESSION['shop_user_id']=$user['user_id']; // เจ้าของร้าน
$_SESSION['shop_name'] = $user['name'];
$_SESSION['shop_email'] = $user['email'];
$_SESSION['shop_role'] = $user['role'];
$_SESSION['shop_id'] = $shop['shop_id'];

header("Location: shop_home.php");
exit;