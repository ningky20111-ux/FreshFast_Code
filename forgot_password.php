<?php
session_start();

$msg = $_SESSION['flash_msg'] ?? null;
$err = $_SESSION['flash_err'] ?? null;
$old_email = $_SESSION['old_email'] ?? '';
$otpCooldown = $_SESSION['otp_cooldown'] ?? 0;
unset($_SESSION['otp_cooldown']);

// DEBUG values (อ่านก่อน แล้วค่อย unset ทีหลัง)
$debug_redirect = $_SESSION['debug_redirect'] ?? null;
$debug_step     = $_SESSION['debug_step'] ?? null;
$debug_file     = $_SESSION['debug_file'] ?? null;

$debug_error      = $_SESSION['debug_error'] ?? null;
$debug_error_file = $_SESSION['debug_error_file'] ?? null;

$debug_mysql_time       = $_SESSION['debug_mysql_time'] ?? null;
$debug_cooldown_row     = $_SESSION['debug_cooldown_row'] ?? null;
$debug_user_id          = $_SESSION['debug_user_id'] ?? null;
$debug_delete_affected  = $_SESSION['debug_delete_affected'] ?? null;
$debug_insert_id        = $_SESSION['debug_insert_id'] ?? null;
$debug_expires_at       = $_SESSION['debug_expires_at'] ?? null;
$debug_otp_plain        = $_SESSION['debug_otp_plain'] ?? null;
$debug_mysql_time_error = $_SESSION['debug_mysql_time_error'] ?? null;

// ✅ เพิ่ม 3 ตัวนี้สำหรับ SendGrid
$debug_sendgrid_ok     = $_SESSION['debug_sendgrid_ok'] ?? null;
$debug_sendgrid_status = $_SESSION['debug_sendgrid_status'] ?? null;
$debug_sendgrid_body   = $_SESSION['debug_sendgrid_body'] ?? null;

// เคลียร์ flash
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

// เคลียร์ debug หลังอ่านแล้ว (กันค้างหน้าถัดไป)
unset(
  $_SESSION['debug_redirect'], $_SESSION['debug_step'], $_SESSION['debug_file'],
  $_SESSION['debug_error'], $_SESSION['debug_error_file'],
  $_SESSION['debug_mysql_time'], $_SESSION['debug_cooldown_row'],
  $_SESSION['debug_user_id'], $_SESSION['debug_delete_affected'], $_SESSION['debug_insert_id'],
  $_SESSION['debug_expires_at'], $_SESSION['debug_otp_plain'], $_SESSION['debug_mysql_time_error'],
  $_SESSION['debug_sendgrid_ok'], $_SESSION['debug_sendgrid_status'], $_SESSION['debug_sendgrid_body']
);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ลืมรหัสผ่าน | FreshFast</title>

<style>
*{ margin:0; padding:0; box-sizing:border-box; font-family:"Prompt",sans-serif; }
body{
  margin:0; background:#f3f3f3; font-family:'Prompt',sans-serif;
  display:flex; justify-content:center; align-items:center; height:100vh;
}
.container{
  width:1100px; height:650px; display:flex; border-radius:24px; overflow:hidden;
  box-shadow:0 20px 40px rgba(0,0,0,0.08); background:white;
}
.btn,
.link-btn{
  width:100%;
  display:flex;
  justify-content:center;
  align-items:center;
  padding:12px;
  border-radius:20px;
  font-size:16px;
  font-weight:600;
  text-decoration:none;
  box-sizing:border-box;
  transition:.25s;
}

/* ปุ่มหลัก */
.btn{
  background:#f2c94c;
  border:none;
  cursor:pointer;
}

/* ปุ่มรอง */
.link-btn{
  margin-top:12px;
  border:2px solid #2e7d32;
  color:#2e7d32;
  background:transparent;
}

.link-btn:hover{
  background:#2e7d32;
  color:#fff;
}


.left{ flex:1; position:relative; overflow:hidden; }
.left img{ width:100%; height:100%; object-fit:cover; }
.left::after{ content:""; position:absolute; width:100%; height:100%; background:rgba(0,0,0,0.35); }
.overlay-text{
  position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
  color:white; font-size:26px; font-weight:600; text-align:center; width:70%; z-index:2;
}
.right{ flex:1; display:flex; justify-content:center; align-items:center; }
.card{
  background:#dfe7e2; padding:50px; border-radius:20px; width:420px; text-align:center;
  box-shadow:0 10px 30px rgba(0,0,0,0.1);
}
.logo{ width:120px; margin-bottom:20px; }
h2{ margin-bottom:10px; }
p{ margin-bottom:18px; }
.input{
  width:100%; padding:12px; border-radius:20px; border:none; outline:none; margin-bottom:20px;
}
.btn{
  background:#f2c94c; border:none; padding:10px 25px; border-radius:20px;
  cursor:pointer; font-weight:bold;
}
.link{
  display:inline-block;
  margin-top:18px;
  padding:10px 18px;
  border:1px solid #2e7d32;
  border-radius:999px;
  font-size:16px;   /* เท่าปุ่ม */
  color:#2e7d32;
  text-decoration:none;
  transition:.25s;
}

.link:hover{
  background:#2e7d32;
  color:#fff;
}

.flash{
  width:100%;
  padding:12px 14px;
  border-radius:14px;
  margin:12px 0 18px 0;
  font-size:14px;
  line-height:1.4;
  text-align:left;
  box-shadow:0 6px 18px rgba(0,0,0,0.08);
}
.flash-success{ background:#e9f7ef; border:1px solid #b7e4c7; color:#1b4332; }
.flash-error{ background:#fdecec; border:1px solid #f5c2c7; color:#842029; }

.debug{
  width:100%;
  background:#0b1220;
  color:#dbeafe;
  border-radius:14px;
  padding:12px 14px;
  margin:12px 0 18px 0;
  font-size:12px;
  line-height:1.4;
  text-align:left;
  overflow:auto;
  max-height:260px;
  box-shadow:0 6px 18px rgba(0,0,0,0.12);
}
.debug b{ color:#fff; }
.debug .warn{ color:#fbbf24; }
.debug .err{ color:#fb7185; }
.debug pre{ white-space:pre-wrap; margin-top:6px; }

/* ===== Responsive: Mobile ===== */
@media (max-width: 768px) {
  body{
    height:auto;
    padding:16px;
    align-items:flex-start;
  }

  .container{
    width:100%;
    height:auto;
    flex-direction:column;
    border-radius:18px;
  }

  .left{
    height:220px;               /* ทำเป็น header รูป */
    flex:none;
  }
  .left img{
    height:220px;
  }

  .overlay-text{
    font-size:18px;
    width:90%;
    line-height:1.4;
  }

  .right{
    padding:16px;
  }

  .card{
    width:100%;
    max-width:420px;
    padding:28px 20px;
    border-radius:18px;
  }

  .logo{
    width:96px;
  }

  .input{
    padding:12px 14px;
  }
.btn,
.link-btn{
  width:100%;
  display:flex;
  justify-content:center;
  align-items:center;
  padding:12px;
  border-radius:20px;
  font-size:16px;
  font-weight:600;
  text-decoration:none;
  box-sizing:border-box;
  transition:.25s;
}

/* ปุ่มหลัก */
.btn{
  background:#f2c94c;
  border:none;
  cursor:pointer;
}

/* ปุ่มรอง */
.link-btn{
  margin-top:12px;
  border:2px solid #2e7d32;
  color:#2e7d32;
  background:transparent;
}

.link-btn:hover{
  background:#2e7d32;
  color:#fff;
}

}

/* ===== Extra small phones ===== */
@media (max-width: 420px) {
  .left{ height:180px; }
  .left img{ height:180px; }
  .overlay-text{ font-size:16px; }
  .card{ padding:24px 16px; }
}

</style>
</head>

<body>
<div class="container">

  <div class="left">
    <img src="assets/images/forgot.jpg" alt="Forgot Password">
    <div class="overlay-text">
      แพลตฟอร์มที่ส่งเสริมการอุดหนุนคนแม่สอด <br>
      และการพัฒนาชุมชนอย่างยั่งยืน
    </div>
  </div>

  <div class="right">
    <div class="card">
      <img src="assets/images/logo_ok.png" class="logo" alt="FreshFast Logo">
      <h2>ลืมรหัสผ่าน?</h2>
      <p>กรอกอีเมลเพื่อรับรหัส OTP</p>

      <?php if ($msg): ?>
        <div class="flash flash-success"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <?php if ($err): ?>
        <div class="flash flash-error" id="cooldownFlash"><?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <?php if ($debug_redirect || $debug_step || $debug_error || $debug_sendgrid_status): ?>
        <div class="debug">
          <div><b>debug_file:</b> <?= htmlspecialchars((string)$debug_file) ?></div>
          <div><b>debug_redirect:</b> <span class="warn"><?= htmlspecialchars((string)$debug_redirect) ?></span></div>
          <div><b>debug_step:</b> <?= htmlspecialchars((string)$debug_step) ?></div>

          <?php if ($debug_error): ?>
            <div><b>debug_error:</b> <span class="err"><?= htmlspecialchars((string)$debug_error) ?></span></div>
            <div><b>debug_error_file:</b> <?= htmlspecialchars((string)$debug_error_file) ?></div>
          <?php endif; ?>

          <hr style="border:none;border-top:1px solid rgba(255,255,255,0.12);margin:10px 0;">

          <div><b>user_id:</b> <?= htmlspecialchars((string)$debug_user_id) ?></div>
          <div><b>cooldown_row:</b> <pre><?= htmlspecialchars(print_r($debug_cooldown_row, true)) ?></pre></div>
          <div><b>delete_affected:</b> <?= htmlspecialchars((string)$debug_delete_affected) ?></div>
          <div><b>insert_id:</b> <?= htmlspecialchars((string)$debug_insert_id) ?></div>
          <div><b>otp_plain:</b> <?= htmlspecialchars((string)$debug_otp_plain) ?></div>

          <hr style="border:none;border-top:1px solid rgba(255,255,255,0.12);margin:10px 0;">

          <div><b>sendgrid_ok:</b> <?= htmlspecialchars((string)$debug_sendgrid_ok) ?></div>
          <div><b>sendgrid_status:</b> <?= htmlspecialchars((string)$debug_sendgrid_status) ?></div>
          <div><b>sendgrid_body:</b> <pre><?= htmlspecialchars((string)$debug_sendgrid_body) ?></pre></div>
        </div>
      <?php endif; ?>

      <form action="send_reset_otp.php" method="POST">
        <input
          id="email"
          type="email"
          name="email"
          class="input"
          placeholder="เช่น freshket@gmail.com"
          autocomplete="email"
          required
          value="<?= htmlspecialchars($old_email) ?>"
        >
        <button type="submit" class="btn">ส่งรหัสยืนยัน</button>
      </form>

      <a href="login.php" class="link-btn">กลับไปหน้าเข้าสู่ระบบ</a>
    </div>
  </div>

</div>
</body>
<script>
let cooldown = <?= (int)$otpCooldown ?>;

if (cooldown > 0) {
  const flash = document.getElementById('cooldownFlash');

  const timer = setInterval(() => {
    cooldown--;

    if (cooldown <= 0) {
      clearInterval(timer);
      flash.textContent = "คุณสามารถขอ OTP ใหม่ได้แล้ว";
      flash.style.background = "#e9f7ef";
      flash.style.color = "#1b4332";
      return;
    }

    flash.textContent = `กรุณารอ ${cooldown} วินาทีก่อนขอ OTP ใหม่`;
  }, 1000);
}
</script>
</html>