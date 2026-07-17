<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();
$cooldownUntil = $_SESSION['otp_cooldown_until'] ?? 0;
$cooldownLeft  = max(0, $cooldownUntil - time());
$cooldownUntil = $_SESSION['otp_cooldown_until'] ?? 0;
$cooldownLeft  = max(0, $cooldownUntil - time());

require_once __DIR__ . "/db.php";

function flash(string $key): ?string {
  $v = $_SESSION[$key] ?? null;
  unset($_SESSION[$key]);
  return $v;
}

function setFlash(string $key, string $msg): void {
  $_SESSION[$key] = $msg;
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

$msg = flash('flash_msg');
$err = flash('flash_err');

// --- หา email สำหรับหน้านี้: ใช้ session เป็นหลัก ---
$email = '';
if (!empty($_SESSION['reset_email'])) {
  $email = (string)$_SESSION['reset_email'];
} elseif (!empty($_GET['email'])) {
  $email = normalizeEmail((string)$_GET['email']);
}

// ถ้าไม่มี email เลย -> กลับไปขอ OTP ใหม่
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  setFlash('flash_err', 'ไม่พบอีเมลสำหรับยืนยัน OTP กรุณาขอ OTP ใหม่');
  redirect('forgot_password.php');
}

// =======================
// POST = ตรวจ OTP
// =======================
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  $otp = preg_replace('/\D+/', '', (string)($_POST['otp'] ?? ''));

  if (!preg_match('/^\d{6}$/', $otp)) {
    setFlash('flash_err', 'กรุณากรอก OTP ให้ครบ 6 หลัก');
    redirect('verify_reset_otp.php');
  }

  // หา user
  $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$user) {
    setFlash('flash_err', 'OTP ไม่ถูกต้อง หรือหมดอายุ (กรุณาขอใหม่)');
    redirect('forgot_password.php');
  }
  $user_id = (int)$user['user_id'];

  // หา OTP ล่าสุด
  $stmt = $conn->prepare("
    SELECT id, otp_hash, expires_at, attempts
    FROM password_reset_otps
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 1
  ");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$row) {
    setFlash('flash_err', 'ยังไม่ได้ขอ OTP กรุณาขอใหม่');
    redirect('forgot_password.php');
  }

  if ((int)$row['attempts'] >= 5) {
    setFlash('flash_err', 'ลองผิดหลายครั้งเกินไป กรุณาส่ง OTP ใหม่');
    redirect('forgot_password.php');
  }

  // ตรวจหมดอายุจากฝั่ง DB จะชัวร์กว่า DateTime local
  $stmt = $conn->prepare("SELECT (NOW() <= ?) AS not_expired");
  $stmt->bind_param("s", $row['expires_at']);
  $stmt->execute();
  $chk = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (empty($chk) || (int)$chk['not_expired'] !== 1) {
    // ลบ OTP ที่หมดอายุ
    $del = $conn->prepare("DELETE FROM password_reset_otps WHERE id = ?");
    $del->bind_param("i", $row['id']);
    $del->execute();
    $del->close();

    setFlash('flash_err', 'OTP หมดอายุแล้ว กรุณาส่งใหม่');
    redirect('forgot_password.php');
  }

  // verify hash
  $ok = password_verify($otp, $row['otp_hash']);

  if (!$ok) {
    $upd = $conn->prepare("UPDATE password_reset_otps SET attempts = attempts + 1 WHERE id = ?");
    $upd->bind_param("i", $row['id']);
    $upd->execute();
    $upd->close();

    setFlash('flash_err', 'รหัส OTP ไม่ถูกต้อง');
    redirect('verify_reset_otp.php');
  }

  // ผ่าน OTP -> ลบ OTP แล้วอนุญาตตั้งรหัสใหม่
  $del = $conn->prepare("DELETE FROM password_reset_otps WHERE id = ?");
  $del->bind_param("i", $row['id']);
  $del->execute();
  $del->close();

  $_SESSION['reset_user_id'] = $user_id;
  $_SESSION['reset_email']   = $email;
  $_SESSION['reset_verified'] = true;

  redirect('reset_password.php');
}

// =======================
// GET = แสดงฟอร์ม
// =======================
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ยืนยัน OTP | FreshFast</title>

<style>
*{
  margin:0;
  padding:0;
  box-sizing:border-box;
  font-family:'Prompt',sans-serif;
}

body{
  margin:0;
  font-family:'Prompt',sans-serif;
  background:linear-gradient(135deg,#f4f6f5 0%,#edf1ee 100%);
}

.page{
  min-height:100vh;
  display:flex;
  justify-content:center;
  align-items:center;
  padding:24px;
}

.shell{
  width:100%;
  max-width:500px;
}

.card{
  width:100%;
  background:rgba(255,255,255,0.82);
  backdrop-filter:blur(12px);
  border:1px solid rgba(255,255,255,0.55);
  border-radius:28px;
  padding:42px 34px;
  box-shadow:
    0 20px 50px rgba(0,0,0,.08),
    0 4px 10px rgba(0,0,0,.03);
}

.brand{
  text-align:center;
  margin-bottom:26px;
}

.logo{
  width:10px;

  height:auto;
  display:block;
  margin:0 auto 14px;
  object-fit:contain;
}

.h1{
  font-size:26px;
  font-weight:700;
  margin:0;
  color:#111;
}

.subtitle{
  margin-top:10px;
  color:#666;
  font-size:14px;
  line-height:1.6;
}

.email-show{
  width:100%;
  margin-top:14px;
  padding:14px 16px;
  background:#f8faf8;
  border:1px solid #e5ebe6;
  border-radius:16px;
  font-size:13px;
  color:#444;
  font-weight:500;
  text-align:center;
}

.form{
  width:100%;
}

.label{
  font-size:14px;
  font-weight:600;
  margin-bottom:8px;
}

.input{
  width:100%;
  display:block;
  padding:16px 18px;
  border-radius:18px;
  font-size:22px;
  font-weight:700;
  text-align:center;
  letter-spacing:10px;
  background:#f8faf8;
  border:1px solid #dfe6e1;
  outline:none;
  transition:.25s;
}

.input:focus{
  border-color:#f5c542;
  background:#fff;
  box-shadow:0 0 0 4px rgba(245,197,66,.18);
}

.flash{
  width:100%;
  padding:14px 16px;
  border-radius:16px;
  margin-bottom:18px;
  font-size:14px;
  font-weight:500;
}

.flash.success{
  background:#eaf8ee;
  color:#1b4332;
}

.flash.error{
  background:#ffecec;
  color:#b00020;
}

.actions{
  display:flex;
  gap:12px;
  margin-top:20px;
  width:100%;
}

.btn{
  flex:1;
  height:54px;
  padding:0 18px;
  border:none;
  border-radius:18px;
  font-size:15px;
  font-weight:700;
  cursor:pointer;
  text-decoration:none;
  display:flex;
  justify-content:center;
  align-items:center;
  transition:.25s;
}

.btn-primary{
  background:#f5c542;
  color:#111;
  box-shadow:0 8px 18px rgba(245,197,66,.35);
}

.btn-primary:hover{
  transform:translateY(-2px);
  box-shadow:0 12px 24px rgba(245,197,66,.4);
}

.btn-secondary{
  background:#fff;
  border:1.5px solid #d7e2d9;
  color:#2e7d32;
}

.btn-secondary:hover{
  background:#f3faf4;
  border-color:#2e7d32;
}
.otp-group{
  display:flex;
  gap:10px;
  justify-content:center;
  width:100%;
  margin-top:4px;
}

.otp-box{
  width:58px;
  height:62px;
  border:none;
  border-radius:18px;
  background:#f8faf8;
  border:1px solid #dfe6e1;
  text-align:center;
  font-size:24px;
  font-weight:700;
  outline:none;
  transition:.25s;
}

.otp-box:focus{
  border-color:#f5c542;
  background:#fff;
  box-shadow:0 0 0 4px rgba(245,197,66,.18);
}

  

@media (max-width:480px){
    .otp-group{
    gap:8px;
  }
  .otp-box{
    width:46px;
    height:54px;
    font-size:20px;
    border-radius:14px;
  }
  .card{
    padding:28px 20px;
    border-radius:22px;
  }
  .input{
    font-size:18px;
    letter-spacing:6px;
  }
  .actions{
    flex-direction:row;
    gap:8px;
  }

  .btn{
    flex:1;
    height:50px;
    font-size:14px;
    border-radius:14px;
    padding:0 12px;
  }
}
</style>
</head>
<body>

<div class="page">
  <div class="shell">
    <div class="card">

      <div class="brand">
      <div class="logo-item">
        <img src="assets/images/logo_ok.png" style="height:40px;">
      </div>
        <h1 class="h1">ยืนยัน OTP</h1>
        <div class="subtitle">
          กรอกรหัส 6 หลักที่ส่งไปยังอีเมลของคุณ
        </div>

        <div class="email-show">
          <?= htmlspecialchars($email) ?>
        </div>
      </div>

      <?php if ($msg): ?>
        <div class="flash success">
          <?= htmlspecialchars($msg) ?>
        </div>
      <?php endif; ?>

      <?php if ($err): ?>
        <div class="flash error">
          <?= htmlspecialchars($err) ?>
        </div>
      <?php endif; ?>

<form class="form" action="verify_reset_otp.php" method="post">

  <div class="label">รหัส OTP</div>

  <div class="otp-group">
    <input type="text" maxlength="1" class="otp-box" inputmode="numeric">
    <input type="text" maxlength="1" class="otp-box" inputmode="numeric">
    <input type="text" maxlength="1" class="otp-box" inputmode="numeric">
    <input type="text" maxlength="1" class="otp-box" inputmode="numeric">
    <input type="text" maxlength="1" class="otp-box" inputmode="numeric">
    <input type="text" maxlength="1" class="otp-box" inputmode="numeric">
  </div>

  <input type="hidden" name="otp" id="otpHidden">

  <div class="actions">
    <button class="btn btn-primary" type="submit">
      ยืนยัน OTP
    </button>
  </div>

</form>

<form action="send_reset_otp.php" method="POST" style="margin-top:12px;">
  <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

  <button
    id="resendBtn"
    class="btn btn-secondary"
    type="submit"
    style="width:100%;"
    <?= $cooldownLeft > 0 ? 'disabled' : '' ?>
  >
    ส่งใหม่
  </button>
</form>
<div id="cooldownText" style="
  margin-top:10px;
  text-align:center;
  font-size:14px;
  color:#666;
"></div>

      </form>
    </div>
  </div>
</div>
<script>
let cooldown = <?= $cooldownLeft ?>;

const resendBtn = document.getElementById('resendBtn');
const cooldownText = document.getElementById('cooldownText');

function tickCooldown() {
  if (cooldown > 0) {
    resendBtn.disabled = true;
    resendBtn.style.opacity = '.55';
    resendBtn.style.cursor = 'not-allowed';

    cooldownText.textContent =
      `คุณสามารถส่งรหัสใหม่ได้อีกใน ${cooldown} วินาที`;

    cooldown--;
  } else {
    resendBtn.disabled = false;
    resendBtn.style.opacity = '1';
    resendBtn.style.cursor = 'pointer';

    cooldownText.textContent = '';
  }
}

tickCooldown();
setInterval(tickCooldown, 1000);
</script>
</body>
</html>