<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/db.php'; // $conn = mysqli

// ให้ timezone ใน session ของ MySQL ตรงกับไทยเสมอ
$conn->query("SET time_zone = '+07:00'");

function flash(string $key, ?string $value = null): ?string {
  if ($value !== null) {
    $_SESSION[$key] = $value;
    return null;
  }
  $v = $_SESSION[$key] ?? null;
  unset($_SESSION[$key]);
  return $v;
}

function redirect(string $to): never {
  header("Location: {$to}");
  exit;
}

function normalizeEmail(string $email): string {
  $email = trim($email);
  $email = filter_var($email, FILTER_SANITIZE_EMAIL);
  return strtolower($email);
}

// ====== 1) หา email สำหรับ verify (ใช้ session เป็นหลัก + รองรับ POST/GET) ======
$email = '';
if (!empty($_SESSION['reset_email'])) {
  $email = (string)$_SESSION['reset_email'];
} elseif (!empty($_POST['email'])) {
  $email = normalizeEmail((string)$_POST['email']);
} elseif (!empty($_GET['email'])) {
  $email = normalizeEmail((string)$_GET['email']);
}

$msg = flash('flash_msg');
$err = flash('flash_err');

// ถ้าไม่มีอีเมลใน session/POST/GET -> กลับไปเริ่มใหม่
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  flash('flash_err', 'ไม่พบอีเมลสำหรับยืนยัน OTP กรุณาขอ OTP ใหม่');
  redirect('forgot_password.php');
}

// ✅ ล็อก email ไว้ใน session เสมอ (กัน redirect แล้วหลุด)
$_SESSION['reset_email'] = $email;

// ====== 2) ถ้าเป็น POST = ตรวจ OTP ======
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  $otp = preg_replace('/\D+/', '', (string)($_POST['otp'] ?? ''));
  if (strlen($otp) !== 6) {
    flash('flash_err', 'กรุณากรอก OTP ให้ครบ 6 หลัก');
    redirect('verify_reset_otp.php'); // ใช้ session reset_email อยู่แล้ว
  }

  try {
    // หา user_id จาก email
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
      // ไม่บอกว่ามี/ไม่มี user
      flash('flash_err', 'OTP ไม่ถูกต้อง หรือหมดอายุ (กรุณาขอใหม่)');
      redirect('forgot_password.php');
    }

    $userId = (int)$user['user_id'];

    // เอา OTP ล่าสุดของ user
    $stmt = $conn->prepare("
      SELECT id, otp_hash, expires_at, attempts
      FROM password_reset_otps
      WHERE user_id = ?
      ORDER BY created_at DESC
      LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
      flash('flash_err', 'ไม่พบ OTP สำหรับอีเมลนี้ กรุณาขอใหม่');
      redirect('forgot_password.php');
    }

    $otpId    = (int)$row['id'];
    $otpHash  = (string)$row['otp_hash'];
    $expires  = (string)$row['expires_at'];
    $attempts = (int)$row['attempts'];

    // จำกัดจำนวนครั้ง
    $maxAttempts = 5;
    if ($attempts >= $maxAttempts) {
      flash('flash_err', 'คุณลองผิดหลายครั้งเกินไป กรุณาขอ OTP ใหม่');
      redirect('forgot_password.php');
    }

    // หมดอายุไหม (เทียบจากฝั่ง DB)
    $stmt = $conn->prepare("SELECT (NOW() <= ?) AS not_expired");
    $stmt->bind_param("s", $expires);
    $stmt->execute();
    $chk = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (empty($chk) || (int)$chk['not_expired'] !== 1) {
      // ลบ OTP ทิ้ง
      $stmt = $conn->prepare("DELETE FROM password_reset_otps WHERE id = ?");
      $stmt->bind_param("i", $otpId);
      $stmt->execute();
      $stmt->close();

      flash('flash_err', 'OTP หมดอายุแล้ว กรุณาขอใหม่');
      redirect('forgot_password.php');
    }

    // ตรวจ OTP
    if (!password_verify($otp, $otpHash)) {
      // เพิ่ม attempts
      $stmt = $conn->prepare("UPDATE password_reset_otps SET attempts = attempts + 1 WHERE id = ?");
      $stmt->bind_param("i", $otpId);
      $stmt->execute();
      $stmt->close();

      flash('flash_err', 'OTP ไม่ถูกต้อง');
      redirect('verify_reset_otp.php');
    }

    // ✅ OTP ถูกต้อง -> ลบ OTP ทิ้ง แล้วไปหน้าตั้งรหัสผ่านใหม่
    $stmt = $conn->prepare("DELETE FROM password_reset_otps WHERE id = ?");
    $stmt->bind_param("i", $otpId);
    $stmt->execute();
    $stmt->close();

    // สร้าง session ว่าผ่านการยืนยันแล้ว
    $_SESSION['reset_verified'] = true;
    $_SESSION['reset_user_id']  = $userId;
    $_SESSION['reset_email']    = $email;

    flash('flash_msg', 'ยืนยัน OTP สำเร็จ กรุณาตั้งรหัสผ่านใหม่');
    redirect('reset_password.php'); // ถ้าคุณใช้ชื่อไฟล์อื่น เปลี่ยนตรงนี้ได้

  } catch (Throwable $e) {
    error_log("verify_reset_otp.php error: " . $e->getMessage());
    flash('flash_err', 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่');
    redirect('forgot_password.php');
  }
}

// ====== 3) GET = แสดงฟอร์ม ======
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/auth.css?v=2">
  <title>ยืนยัน OTP | FreshFast</title>
</head>
<body>
  <div class="page">
    <div class="shell">

      <!-- ✅ เพิ่ม Hero ให้ layout เหมือนหน้า login/register -->
      <div class="hero">
        <img src="assets/images/hero-market.png" alt="">
        <div class="overlay"></div>
        <div class="headline">
          ยืนยันรหัส OTP เพื่อดำเนินการต่อ
        </div>
      </div>

      <div class="card">
        <div class="brand">
          <img src="assets/images/logo.png" alt="FreshFast" class="logo">
          <h1 class="h1">ยืนยัน OTP</h1>
          <p class="sub">กรอกรหัส 6 หลักที่ได้รับทางอีเมล</p>
          <p style="margin:6px 0 0;color:#666;font-size:13px;">
            อีเมล: <b><?= htmlspecialchars($email) ?></b>
          </p>
        </div>

        <?php if ($msg): ?>
          <div style="background:#e7f7ed;padding:10px;border-radius:10px;margin-bottom:10px;">
            <?= htmlspecialchars($msg) ?>
          </div>
        <?php endif; ?>

        <?php if ($err): ?>
          <div style="background:#ffe8e8;padding:10px;border-radius:10px;margin-bottom:10px;">
            <?= htmlspecialchars($err) ?>
          </div>
        <?php endif; ?>

        <form class="form" action="verify_reset_otp.php" method="post" autocomplete="off">
          <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

          <div class="label">OTP</div>
          <input class="input"
                 name="otp"
                 inputmode="numeric"
                 pattern="[0-9]{6}"
                 maxlength="6"
                 placeholder="เช่น 123456"
                 required>

          <div style="display:flex;justify-content:center;margin-top:14px;gap:10px;flex-wrap:wrap;">
            <button class="btn yellow" type="submit">ยืนยัน</button>

            <!-- ส่งใหม่: กลับไปหน้าขอ OTP ใหม่ -->
            <a class="btn green"
               style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;"
               href="forgot_password.php">
              ส่งใหม่
            </a>
          </div>

          <div class="bottom-links" style="margin-top:14px;">
            <a href="forgot_password.php" class="link-forgot">ย้อนกลับ</a>
          </div>
        </form>

      </div>
    </div>
  </div>
</body>
</html>