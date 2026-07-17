<?php
// do_register.php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit;
}

// ===============================
// 1) รับค่า + validate เบื้องต้น
// ===============================
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$consent = $_POST['consent'] ?? null;
$consent_version = $_POST['consent_version'] ?? '1.0';

if ($name === '' || $email === '' || $password === '') {
    header("Location: register.php");
    exit;
}

// 🔒 บังคับ consent ฝั่ง server
if (!$consent) {
    die("คุณต้องยอมรับข้อกำหนดก่อนสมัครสมาชิก");
}

// 🔒 validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("รูปแบบอีเมลไม่ถูกต้อง");
}

// 🔒 password length ขั้นต่ำ
if (strlen($password) < 6) {
    die("รหัสผ่านต้องอย่างน้อย 6 ตัวอักษร");
}

// normalize email
$email_norm = strtolower($email);

// ===============================
// 2) Connect DB
// ===============================
$conn = new mysqli("localhost", "root", "", "freshfast");
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ใช้ transaction
$conn->begin_transaction();

try {

    // ===============================
    // 3) เช็ค email ซ้ำ
    // ===============================
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE LOWER(email) = ?");
    $stmt->bind_param("s", $email_norm);
    $stmt->execute();
    $stmt->bind_result($cnt);
    $stmt->fetch();
    $stmt->close();

    if ($cnt > 0) {
        $conn->rollback();
        $conn->close();
        header("Location: register.php?dup=1");
        exit;
    }

    // ===============================
    // 4) Insert user
    // ===============================
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $role = "customer";
    $is_verified = 1;

    $stmt2 = $conn->prepare("
        INSERT INTO users 
        (name, email, password, role, is_verified, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt2->bind_param("ssssi", $name, $email_norm, $hash, $role, $is_verified);

    if (!$stmt2->execute()) {
        throw new Exception("Insert user failed");
    }

    $user_id = $stmt2->insert_id;
    $stmt2->close();

    // ===============================
    // 5) Insert consent record
    // ===============================
    $ip = $_SERVER['REMOTE_ADDR'];
    $now = date('Y-m-d H:i:s');

    $stmt3 = $conn->prepare("
        INSERT INTO user_consents
        (user_id, consent_type, consent_version, consented_at, consent_ip)
        VALUES (?, 'privacy_policy', ?, ?, ?)
    ");

    $stmt3->bind_param("isss", $user_id, $consent_version, $now, $ip);

    if (!$stmt3->execute()) {
        throw new Exception("Insert consent failed");
    }

    $stmt3->close();

    // ===============================
    // 6) Commit
    // ===============================
    $conn->commit();
    $conn->close();

    // 🔐 regenerate session id กัน fixation
    session_regenerate_id(true);

    $_SESSION['user_email'] = $email_norm;

    header("Location: register_success.php");
    exit;

} catch (Exception $e) {

    $conn->rollback();
    $conn->close();

    die("เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง");
}