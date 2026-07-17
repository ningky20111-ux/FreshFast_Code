<?php // index.php ?>
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
<body class="auth auth--index">
  <div class="page">
    <div class="shell">
    <div class="hero">
    <img src="assets/images/hero-butcher.jpg" alt="">
    <div class="overlay"></div>
    <div class="headline">
        แพลตฟอร์มที่ส่งเสริมการอุดหนุนคนแม่สอด<br>
        และการพัฒนาชุมชนอย่างยั่งยืน
      </div>
    </div>


      <div class="card">
        <div class="brand">
        <img src="assets/images/logo.png" alt="FreshFast" class="logo">
          <p class="sub">โปรดเข้าสู่ระบบหรือลงทะเบียนเพื่อใช้บริการ FreshFast</p>
      </div>

        <div class="actions">
          <a class="btn green" href="register.php" style="text-decoration:none;display:inline-flex;justify-content:center;align-items:center;">ลงทะเบียน</a>
          <a class="btn yellow" href="login.php" style="text-decoration:none;display:inline-flex;justify-content:center;align-items:center;">เข้าสู่ระบบ</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
