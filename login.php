<?php
session_start();
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login | FreshFast</title>

  <style>
    body {
      margin: 0;
      font-family: sans-serif;
      background: #f3f3f3;
    }

    .page {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    /* ===== LAYOUT ===== */
    .shell {
      display: grid;
      grid-template-columns: 1fr 1fr;
      width: 900px;
      height: 550px;
      border-radius: 20px;
      overflow: hidden;
      background: #fff;
    }

    /* ===== LEFT ===== */
    .hero {
      position: relative;
      height: 100%;
    }

    .hero img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .headline {
      position: absolute;
      bottom: 20px;
      left: 20px;
      color: #fff;
      font-size: 20px;
      line-height: 1.4;
    }

    /* ===== RIGHT ===== */
    .card {
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      height: 100%;
      padding: 10px 30px 30px;
      box-sizing: border-box;
      background: #eef0ee;
      overflow-y: auto;
    }

    .brand {
      text-align: center;
      margin-top: -10px;
      margin-bottom: 5px;
    }

    .logo {
      width: 200px;
    }
    .h1 {
      font-size: 20px;
      margin: 5px 0;
      line-height: 1.4;
      font-weight: 700;
    }

    .form {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .label {
      font-size: 14px;
    }

    .input {
      width: 100%;
      padding: 12px 16px;
      border-radius: 25px;
      border: 1px solid #ddd;
      box-sizing: border-box;
    }

    .btn {
      padding: 12px;
      border-radius: 25px;
      border: none;
      background: #f5c542;
      cursor: pointer;
      width: 150px;
    }

    .bottom-links {
      margin-top: 10px;
      text-align: center;
      font-size: 13px;
    }

    .bottom-links a {
      color: #2e7d32;
      text-decoration: none;
      text-decoration: underline; 
    }

    .bottom-links a:hover {
      text-decoration: underline;
    }

    /* ===== MOBILE ===== */
    @media (max-width: 768px) {
      .shell {
        grid-template-columns: 1fr;
        height: auto;
        width: 90%;
      }

      .hero {
        display: none;
      }

      .card {
        height: auto;
      }
    }
  </style>
</head>

<body>
  <div class="page">
    <div class="shell">

      <!-- LEFT -->
      <div class="hero">
        <img src="assets/images/hero-butcher.jpg">
        <div class="headline">
          แพลตฟอร์มที่ส่งเสริมการอุดหนุนคนแม่สอด<br>
          และการพัฒนาชุมชนอย่างยั่งยืน
        </div>
      </div>

      <!-- RIGHT -->
      <div class="card">
        <div class="brand">
          <img src="assets/images/logo_ok.png" class="logo">
          <div class="h1">
            ยินดีต้อนรับ!<br>
            เข้าสู่ระบบเพื่อเริ่มช้อปได้เลย
          </div>
        </div>

<?php
if (!empty($_SESSION['login_error'])) {
            echo '<div style="
              background:#ffe5e5;
              color:#b00020;
              padding:10px;
              border-radius:10px;
              margin-bottom:10px;
              text-align:center;">
              ' . htmlspecialchars($_SESSION['login_error']) . '
              </div>';
            unset($_SESSION['login_error']);
          }
        ?>

        <form class="form" action="do_login.php" method="post">

          <div class="label">อีเมล</div>
          <input class="input" name="email" type="email" required>

          <div class="label">รหัสผ่าน</div>
          <input class="input" name="password" type="password" required>

          <div style="display:flex;justify-content:center;margin-top:10px;">
            <button class="btn">เข้าสู่ระบบ</button>
          </div>

          <div class="bottom-links">
            <a href="forgot_password.php">ลืมรหัสผ่าน?</a><br>
  
            หรือยังไม่มีบัญชี?
            <a href="register.php">ลงทะเบียนเลย</a>
          </div>

        </form>
      </div>

    </div>
  </div>
</body>
</html>