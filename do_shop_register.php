<?php
session_start();
require_once "db.php";
$shopType = trim($_POST['shop_type'] ?? '');

if (!isset($conn)) {
    require_once "db.php";
    $conn->set_charset("utf8mb4");
}

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

function backWithError($message) {
    header("Location: shop_register.php?error=" . urlencode($message));
    exit;
}

function columnExists($conn, $table, $column) {
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

if (!columnExists($conn, "users", "user_status")) {
    $conn->query("ALTER TABLE users ADD COLUMN user_status VARCHAR(30) DEFAULT 'active'");
}

if (!columnExists($conn, "users", "profile_image")) {
    $conn->query("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL");
}

if (!columnExists($conn, "shops", "owner_id")) {
    $conn->query("ALTER TABLE shops ADD COLUMN owner_id INT NULL");
}

$shopName = trim($_POST['shop_name'] ?? '');
$shopType = trim($_POST['shop_type'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$acceptTerms = $_POST['accept_terms'] ?? '';

if ($shopName === '' || $email === '' || $password === '') {
    backWithError("กรุณากรอกข้อมูลให้ครบ");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    backWithError("รูปแบบอีเมลไม่ถูกต้อง");
}

if (strlen($password) < 6) {
    backWithError("รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร");
}

if ($acceptTerms !== '1') {
    backWithError("กรุณายอมรับข้อกำหนดและเงื่อนไขก่อนสมัครสมาชิก");
}
$shopName = trim($_POST['shop_name'] ?? '');
$shopType = trim($_POST['shop_type'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$acceptTerms = $_POST['accept_terms'] ?? '';

if ($shopName === '' || $email === '' || $password === '') {
    backWithError("กรุณากรอกข้อมูลให้ครบ");
}

if ($shopType === '') {
    backWithError("กรุณาเลือกประเภทร้านค้า");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    backWithError("รูปแบบอีเมลไม่ถูกต้อง");
}
$stmt = $conn->prepare("
    SELECT user_id
    FROM users
    WHERE email = ?
    LIMIT 1
");
$stmt->bind_param("s", $email);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();

if ($exists) {
    backWithError("อีเมลนี้ถูกใช้แล้ว");
}

$conn->begin_transaction();

try {
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        INSERT INTO users
            (name, email, password, role, is_verified, user_status)
        VALUES
            (?, ?, ?, 'shop', 1, 'active')
    ");
    $stmt->bind_param("sss", $shopName, $email, $hash);
    $stmt->execute();

    $userId = $conn->insert_id;

    $stmt = $conn->prepare("
    INSERT INTO shops
    (
        owner_id,
        shop_name,
        shop_type,
        status,
        shop_email,
        shop_password
    )
    VALUES
    (
        ?, ?, ?, 'active', ?, ?
    )
    ");

    $stmt->bind_param(
        "issss",
        $userId,
        $shopName,
        $shopType,
        $email,
        $hash
    );
    $stmt->execute();

    $consentType = "shop_terms";
    $consentVersion = "1.0";
    $consentedAt = date("Y-m-d H:i:s");
    $consentIp = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO user_consents
            (user_id, consent_type, consent_version, consented_at, consent_ip)
        VALUES
            (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "issss",
        $userId,
        $consentType,
        $consentVersion,
        $consentedAt,
        $consentIp
    );
    $stmt->execute();

    $conn->commit();

    header("Location: shop_login.php");
    exit;

} catch (Throwable $e) {
    $conn->rollback();
    backWithError("สมัครสมาชิกไม่สำเร็จ: " . $e->getMessage());
}