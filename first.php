<?php
session_start();

if (isset($_SESSION['user_email'])) {
  header("Location: home.php");
  exit;
}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>FreshFast</title>

  <style>
    body {
      margin: 0;
      font-family: sans-serif;
      background: #efefef;
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
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    /* ===== LEFT IMAGE ===== */
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

    /* ===== RIGHT CARD ===== */
    .card {
      gap: 15px; /* 🔥 คุมระยะห่างทั้งหมด */
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      background: #eef0ee;
      text-align: center;
      padding: 20px;
    }

    .logo {
      width: 250px;
      margin-bottom: 10px;
      margin-bottom: 5px;
    }

    .brand {
      font-size: 22px;
      font-weight: bold;
      color: #1b7f3a;
      margin-bottom: 15px;
    }

    .text {
      font-size: 14px;
      color: #333;
      margin-bottom: 20px;
    }

    .btn {
      display: inline-block;
      width: 140px;
      padding: 10px;
      border-radius: 999px;
      text-decoration: none;
      font-weight: bold;
      margin: 5px;
    }

    .btn-register {
      background: #1b7f3a;
      color: #fff;
    }

    .btn-login {
      background: #f5c542;
      color: #000;
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
      justify-content: center;  /* 🔥 จัดกลางแนวตั้ง */
      align-items: center;      /* 🔥 จัดกลางแนวนอน */
      text-align: center;
      padding: 30px 20px;       /* 🔥 ลดช่องว่าง */
    }

    .logo {
      width: 220px; /* 🔥 ลดขนาดให้พอดอ */
    }
  }

  </style>
</head>

<body>
  <div class="page">
    <div class="shell">

      <!-- LEFT -->
      <div class="hero">
        <img src="assets/images/hero-market.png">
        <div class="headline">
          แพลตฟอร์มที่สร้างพื้นที่เพื่อคนแม่สอด<br>
          เชื่อมต่อการซื้อขายและบริการในชุมชนเดียวกัน
        </div>
      </div>

      <!-- RIGHT -->
      <div class="card">
        <img src="assets/images/logo.png" class="logo">

        <div class="brand"></div>

        <div class="text">
          โปรดเข้าสู่ระบบหรือลงทะเบียนเพื่อใช้บริการ FreshFast
        </div>

        <a href="register.php" class="btn btn-register">ลงทะเบียน</a>
        <a href="login.php" class="btn btn-login">เข้าสู่ระบบ</a>
      </div>

    </div>
  </div>
</body>
</html>