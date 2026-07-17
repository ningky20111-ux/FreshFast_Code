<?php
session_start();
require_once "db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $_SESSION['flash_err'] = "คำขอไม่ถูกต้อง";
  header("Location: forgot_password.php");
  exit;
}

if (!isset($_SESSION['reset_user_id'])) {
  $_SESSION['flash_err'] = "เซสชันหมดอายุ กรุณาขอ OTP ใหม่";
  header("Location: forgot_password.php");
  exit;
}

$new = (string)($_POST['new_password'] ?? '');
$cf  = (string)($_POST['confirm_password'] ?? '');

$new = trim($new);
$cf  = trim($cf);

// ✅ policy ขั้นต่ำ (ปรับได้ตาม requirement)
if (strlen($new) < 8) {
  $_SESSION['flash_err'] = "รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษร";
  header("Location: reset_password.php");
  exit;
}
// มีตัวอักษรและตัวเลขอย่างน้อย 1
if (!preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new)) {
  $_SESSION['flash_err'] = "รหัสผ่านต้องมีตัวอักษรและตัวเลขอย่างน้อยอย่างละ 1";
  header("Location: reset_password.php");
  exit;
}

if ($new !== $cf) {
  $_SESSION['flash_err'] = "รหัสผ่านยืนยันไม่ตรงกัน";
  header("Location: reset_password.php");
  exit;
}

$user_id = (int)$_SESSION['reset_user_id'];
$hash = password_hash($new, PASSWORD_DEFAULT);

try {
  $conn->begin_transaction();

  // อัปเดตรหัส
  $upd = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
  $upd->bind_param("si", $hash, $user_id);
  $upd->execute();
  $upd->close();

  // ลบ OTP ทิ้ง
  $del = $conn->prepare("DELETE FROM password_reset_otps WHERE user_id = ?");
  $del->bind_param("i", $user_id);
  $del->execute();
  $del->close();

  $conn->commit();

} catch (\Throwable $e) {
  try { $conn->rollback(); } catch (\Throwable $t) {}

  // ไม่ต้องโชว์รายละเอียดให้ผู้ใช้ แต่ log ไว้ได้
  error_log("do_reset_password failed: " . $e->getMessage());

  $_SESSION['flash_err'] = "เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง";
  header("Location: reset_password.php");
  exit;
}

// เคลียร์ session สำหรับรีเซ็ต
unset($_SESSION['reset_user_id'], $_SESSION['reset_email']);

$_SESSION['flash_msg'] = "เปลี่ยนรหัสผ่านสำเร็จแล้ว! กรุณาเข้าสู่ระบบ";
header("Location: login.php");
exit;
