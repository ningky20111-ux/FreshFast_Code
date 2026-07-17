<?php
session_start();
if (!isset($_SESSION['reset_user_id'])) {
  header("Location: forgot_password.php");
  exit;
}
$err = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_err']);
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/reset.css?v=1">
  <title>ตั้งรหัสใหม่ | FreshFast</title>
</head>
<body>
  <div class="page">
    <div class="shell">
      <div class="card">
        <div class="brand">
          <img src="assets/images/logo.png" alt="FreshFast" class="logo">
          <h1 class="h1">ตั้งรหัสผ่านใหม่</h1>
          <p style="margin-top:6px;"><?= htmlspecialchars($_SESSION['reset_email']) ?></p>
        </div>

        <?php if ($err): ?>
          <div style="background:#ffe8e8;padding:10px;border-radius:10px;margin-bottom:10px;">
            <?= htmlspecialchars($err) ?>
          </div>
        <?php endif; ?>

        <form class="form" action="do_reset_password.php" method="post">
          <div class="label">รหัสผ่านใหม่</div>
          <input class="input" name="new_password" type="password" required>

          <div class="label">ยืนยันรหัสผ่านใหม่</div>
          <input class="input" name="confirm_password" type="password" required>

          <div style="display:flex;justify-content:center;margin-top:14px;">
            <button class="btn yellow" type="submit">บันทึกรหัสใหม่</button>
          </div>
        </form>

      </div>
    </div>
  </div>
</body>
</html>
