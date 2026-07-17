<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
$cooldownUntil = $_SESSION['otp_cooldown_until'] ?? 0;
$cooldownLeft = max(0, $cooldownUntil - time());

session_start();
ob_start();

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/db.php';

use SendGrid\Mail\Mail;

const DEBUG = false; // live server ปิดไว้เสมอ// ปิดทีหลังได้

/* ------------------------------
   Load .env
------------------------------ */
$dotenvPath = __DIR__ . '/.env';
if (file_exists($dotenvPath)) {
  $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
  $dotenv->load();
}

/* ------------------------------
   Helpers
------------------------------ */
function dbg_set(string $key, $value): void {
  if (!DEBUG) return;
  $_SESSION[$key] = $value;
}

function safeRedirect(string $to): never {
  if (ob_get_length()) { ob_end_clean(); }

  if (headers_sent($file, $line)) {
    die("HEADERS ALREADY SENT at {$file}:{$line}");
  }

  header("Location: {$to}");
  exit;
}

function redirectWithFlash(string $msg, string $to = 'forgot_password.php', bool $isError = true, string $tag = ''): never {
  if ($isError) $_SESSION['flash_err'] = $msg;
  else $_SESSION['flash_msg'] = $msg;

  if (DEBUG) {
    $_SESSION['debug_redirect'] = $tag !== '' ? $tag : 'REDIRECT';
    $_SESSION['debug_step'] = $_SESSION['debug_step'] ?? '(unknown)';
  }

  safeRedirect($to);
}

function normalizeEmail(string $email): string {
  $email = trim($email);
  $email = filter_var($email, FILTER_SANITIZE_EMAIL);
  return strtolower($email);
}

function envval(string $key, string $default = ''): string {
  if (isset($_ENV[$key]) && $_ENV[$key] !== '') return (string)$_ENV[$key];
  if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return (string)$_SERVER[$key];
  $v = getenv($key);
  return ($v !== false && $v !== '') ? (string)$v : $default;
}

function generateOtp(int $length = 6): string {
  $min = 10 ** ($length - 1);
  $max = (10 ** $length) - 1;
  return (string) random_int($min, $max);
}

/**
 * @return array{0:bool,1:int,2:string} [ok, status, bodyOrError]
 */
function sendOtpEmailSendGrid(string $toEmail, string $otp): array {
  $apiKey    = trim(envval('SENDGRID_API_KEY'));
  $fromEmail = trim(envval('SENDGRID_FROM_EMAIL'));
  $fromName  = envval('SENDGRID_FROM_NAME', 'FreshFast');

  if ($apiKey === '' || $fromEmail === '') {
    return [false, 0, 'Missing SENDGRID_API_KEY or SENDGRID_FROM_EMAIL'];
  }

  $subject = 'FreshFast: รหัส OTP สำหรับรีเซ็ตรหัสผ่าน';
  $plain = "รหัส OTP ของคุณคือ: {$otp}\nรหัสนี้จะหมดอายุภายใน 10 นาที\nหากคุณไม่ได้ทำรายการนี้ กรุณาเพิกเฉย";
  $html  = "
    <div style='font-family: Arial, sans-serif; line-height:1.6'>
      <h2>FreshFast</h2>
      <p>รหัส OTP สำหรับรีเซ็ตรหัสผ่านของคุณคือ:</p>
      <p style='font-size: 28px; font-weight: 700; letter-spacing: 6px;'>{$otp}</p>
      <p>รหัสนี้จะหมดอายุภายใน <b>10 นาที</b></p>
      <p style='color:#666'>หากคุณไม่ได้ทำรายการนี้ กรุณาเพิกเฉย</p>
    </div>
  ";

  $mail = new Mail();
  $mail->setFrom($fromEmail, $fromName);
  $mail->setSubject($subject);
  $mail->addTo($toEmail);
  $mail->addContent('text/plain', $plain);
  $mail->addContent('text/html', $html);

  try {
    $sg = new \SendGrid($apiKey);
    $res = $sg->send($mail);
    $status = (int)$res->statusCode();
    $body   = (string)$res->body();
    return [$status === 202, $status, $body];
  } catch (Throwable $e) {
    return [false, 0, 'EXCEPTION: ' . $e->getMessage()];
  }
}

/* ------------------------------
   Validate request
------------------------------ */
dbg_set('debug_step', 'START');
dbg_set('debug_file', __FILE__);

// เคลียร์ค่า sendgrid เก่า (กันดูผิดรอบ)
dbg_set('debug_sendgrid_ok', '');
dbg_set('debug_sendgrid_status', '');
dbg_set('debug_sendgrid_body', '');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  redirectWithFlash('วิธีเข้าถึงไม่ถูกต้อง', 'forgot_password.php', true, 'NOT_POST');
}

$email = normalizeEmail($_POST['email'] ?? '');
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $_SESSION['old_email'] = $email;
  redirectWithFlash('กรุณากรอกอีเมลให้ถูกต้อง', 'forgot_password.php', true, 'INVALID_EMAIL');
}
$_SESSION['old_email'] = $email;

/* ------------------------------
   Business logic
------------------------------ */
$cooldownSeconds  = 30;
$otpExpireMinutes = 10;

try {
  dbg_set('debug_step', 'BEGIN_TX');
  $conn->begin_transaction();

  dbg_set('debug_step', 'FIND_USER');
  $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$user) {
    $conn->rollback();

    redirectWithFlash(
      'ไม่พบบัญชีผู้ใช้งานที่ใช้อีเมลนี้ในระบบ',
      'forgot_password.php',
      true,
      'USER_NOT_FOUND'
    );
  }

  $userId = (int)$user['user_id'];
  dbg_set('debug_user_id', $userId);

  dbg_set('debug_step', 'CHECK_COOLDOWN');
  $stmt = $conn->prepare("
    SELECT created_at,
           GREATEST(TIMESTAMPDIFF(SECOND, created_at, NOW()), 0) AS diff_sec
    FROM password_reset_otps
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 1
  ");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  dbg_set('debug_cooldown_row', $row ?? null);

  if ($row && isset($row['diff_sec'])) {
    $since = (int)$row['diff_sec'];
    if ($since < $cooldownSeconds) {
      $remain = $cooldownSeconds - $since;
      $conn->rollback();

    redirectWithFlash(
      'ส่ง OTP ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง',
      'verify_reset_otp.php',
      true,
      'SENDGRID_FAIL'
    );
    }
  }

  dbg_set('debug_step', 'DELETE_OLD');
  $stmt = $conn->prepare("DELETE FROM password_reset_otps WHERE user_id = ?");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  dbg_set('debug_delete_affected', $stmt->affected_rows);
  $stmt->close();

  dbg_set('debug_step', 'INSERT_NEW');
  $otp     = generateOtp(6);
  $otpHash = password_hash($otp, PASSWORD_DEFAULT);

  $stmt = $conn->prepare("
    INSERT INTO password_reset_otps (user_id, otp_hash, expires_at, attempts)
    VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), 0)
  ");
  $stmt->bind_param("isi", $userId, $otpHash, $otpExpireMinutes);
  $stmt->execute();

  if ($stmt->affected_rows !== 1) {
    throw new RuntimeException('Insert OTP failed');
  }
  dbg_set('debug_insert_id', $conn->insert_id);
  dbg_set('debug_otp_plain', $otp); // ✅ เทสอยู่ได้ / ใช้งานจริงให้ลบทิ้ง
  $stmt->close();

  // ✅ ส่ง OTP จริงผ่าน SendGrid
  dbg_set('debug_step', 'SEND_EMAIL');
  [$ok, $status, $body] = sendOtpEmailSendGrid($email, $otp);

  // ✅ เซฟไว้ให้ดูทุกครั้ง
  dbg_set('debug_sendgrid_ok', $ok ? 'true' : 'false');
  dbg_set('debug_sendgrid_status', $status);
  dbg_set('debug_sendgrid_body', substr((string)$body, 0, 1200));

  if (!$ok) {
    $conn->rollback();
    redirectWithFlash('ส่ง OTP ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 'forgot_password.php', true, 'SENDGRID_FAIL');
  }

  dbg_set('debug_step', 'COMMIT');
  $conn->commit();

  $_SESSION['reset_email'] = $email;
  $_SESSION['otp_cooldown_until'] = time() + $cooldownSeconds;

  dbg_set('debug_step', 'REDIRECT_TO_VERIFY');
  safeRedirect('verify_reset_otp.php');

} catch (Throwable $e) {
  try { $conn->rollback(); } catch (Throwable $ignore) {}

  dbg_set('debug_error', $e->getMessage());
  dbg_set('debug_error_file', $e->getFile() . ':' . $e->getLine());

  redirectWithFlash('เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่', 'forgot_password.php', true, 'EXCEPTION');
}