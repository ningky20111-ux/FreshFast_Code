<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: login.php");
  exit;
}

$email = trim($_POST['email']);
$password = $_POST['password'];

require_once "db.php";
$conn->set_charset("utf8mb4");

$stmt = $conn->prepare("SELECT user_id, email, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {

  // ล็อกอินสำเร็จ
  $_SESSION['user_email'] = $user['email'];
  $_SESSION['user_id'] = $user['user_id'];

  header("Location: home.php");
  exit;

} else {

  // ❌ รหัสผิด
  $_SESSION['login_error'] = "รหัสผ่านของคุณไม่ถูกต้อง!!!";
  header("Location: login.php");
  exit;
}
