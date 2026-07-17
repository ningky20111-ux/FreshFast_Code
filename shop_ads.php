<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'shop') {
    header("Location: shop_login.php");
    exit;
}

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT *
    FROM shops
    WHERE owner_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$shop = $stmt->get_result()->fetch_assoc();

if (!$shop) {
    die("ไม่พบข้อมูลร้านค้า");
}

// $shopImage = !empty($shop['shop_image']) ? $shop['shop_image'] : "assets/images/sac.png";
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>โฆษณาร้านค้า | FreshFast</title>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{box-sizing:border-box;font-family:sans-serif}

body{
    margin:0;
    background:#fff1b3;
    color:#000;
}

.header{
    height:92px;
    background:#fff1b3;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 28px;
}

.logo{
    display:flex;
    align-items:center;
    gap:10px;
    color:#008c3a;
    font-size:18px;
    font-weight:700;
}

.logo .cart{font-size:48px}

.profile-btn{
    width:52px;
    height:52px;
    border-radius:50%;
    background:#009536;
    color:#fff;
    display:grid;
    place-items:center;
    text-decoration:none;
    font-size:28px;
    box-shadow:0 3px 8px rgba(0,0,0,.25);
}

.nav{
    height:34px;
    background:#f2c300;
    display:flex;
    justify-content:center;
    align-items:center;
    gap:64px;
}

.nav a{
    color:#fff;
    text-decoration:none;
    font-weight:700;
}

.nav a.active{
    color:#008c3a;
}

.container{
    padding:52px 70px 70px;
}

.shop-card{
    background:#f8df73;
    border-radius:20px;
    min-height:300px;
    display:flex;
    align-items:center;
    gap:36px;
    padding:34px 48px;
    box-shadow:0 4px 10px rgba(0,0,0,.18);
    margin-bottom:42px;
}

.shop-img{
    width:235px;
    height:235px;
    border-radius:20px;
    overflow:hidden;
    background:#eee;
    border:1px solid #aaa;
    flex-shrink:0;
}

.shop-img img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.shop-info h2{
    margin:0 0 12px;
    font-size:24px;
}

.shop-info p{
    margin:0 0 8px;
    font-size:20px;
    font-weight:700;
}

.ad-box{
    background:#98efb8;
    border-radius:20px;
    min-height:360px;
    padding:50px 70px;
    box-shadow:0 4px 10px rgba(0,0,0,.18);
    text-align:center;
}

.ad-box h1{
    margin:0 0 34px;
    font-size:24px;
}

.ad-content{
    text-align:left;
    max-width:620px;
    margin:auto;
}

.ad-content h3{
    margin-bottom:24px;
}

.price-row{
    display:flex;
    gap:90px;
    margin-bottom:160px;
}

.price-option{
    display:flex;
    align-items:center;
    gap:8px;
}

.price-option input{
    display:none;
}

.price-option span{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:104px;
    height:28px;
    border-radius:20px;
    background:#fff;
    border:1px solid #555;
    font-weight:700;
    cursor:pointer;
}

.price-option input:checked + span{
    background:#f5c400;
    border-color:#f5c400;
}

.action-row{
    display:flex;
    justify-content:center;
    gap:48px;
}

.cancel-btn,
.confirm-btn{
    width:78px;
    height:28px;
    border:none;
    border-radius:18px;
    font-weight:700;
    cursor:pointer;
    text-decoration:none;
    display:flex;
    align-items:center;
    justify-content:center;
}

.cancel-btn{
    background:#fff;
    color:#000;
    border:1px solid #555;
}

.confirm-btn{
    background:#f5c400;
    color:#000;
}


/* ================= FOOTER ================= */

.footer{
    padding:0 40px 40px;
}

.footer-line{
    border-top:1px solid #ddd;

    text-align:center;

    padding-top:26px;

    color:#444;
    font-weight:600;
}

.footer small{
    display:block;
    margin-top:18px;
    color:#888;
}
.profile{
    width:48px;
    height:48px;

    border-radius:50%;
    overflow:hidden;

    box-shadow:0 4px 12px rgba(0,0,0,.15);
}

.profile img{
    width:100%;
    height:100%;
    object-fit:cover;
}
.empty-shop-image{
    width:100%;
    height:100%;

    background:#f1f5f9;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:80px;

    color:#cbd5e1;

    border-radius:20px;

    position:relative;
}

.empty-shop-image::after{
    content:'';

    position:absolute;

    inset:0;

    background:linear-gradient(
        135deg,
        rgba(255,255,255,.55),
        rgba(255,255,255,0)
    );

    pointer-events:none;
}
.sales-section{
    background:#bbf7d0;
    padding:14px 18px;
    margin-top:-38px;

    position:relative; /* สำคัญ */
}

.sales{
    width:100%;
}
*{
    box-sizing:border-box;
    font-family:Arial,sans-serif;
}

body{
    margin:0;
    background:#eef7f0;
    color:#111;
}

/* ================= HEADER ================= */

.header{
    height:84px;
    background:#fff;

    display:flex;
    align-items:center;
    justify-content:space-between;

    padding:0 28px;

    box-shadow:0 2px 10px rgba(0,0,0,.04);
}

.logo img{
    height:54px;
    display:block;
}

/* PROFILE */

.profile{
    width:42px;
    height:42px;

    border-radius:50%;
    background:#16a34a;

    display:flex;
    align-items:center;
    justify-content:center;

    box-shadow:0 4px 12px rgba(0,0,0,.12);
}
.profile-menu{
    position:relative;
}

.profile-btn{
    width:48px;
    height:48px;

    border:none;
    padding:0;

    border-radius:50%;
    overflow:hidden;

    cursor:pointer;

    background:none;

    box-shadow:0 4px 12px rgba(0,0,0,.15);
}


.profile-btn img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.profile-dropdown{
    position:absolute;

    top:58px;
    right:0;

    width:190px;

    background:#fff;

    border-radius:18px;

    box-shadow:0 14px 35px rgba(0,0,0,.12);

    padding:8px;

    display:none;

    z-index:999;
}

.profile-dropdown.show{
    display:block;
}

.profile-dropdown a{
    display:block;

    padding:12px 14px;

    border-radius:12px;

    text-decoration:none;

    color:#111;

    font-weight:700;

    transition:.2s;
}

.profile-dropdown a:hover{
    background:#f5f5f5;
}

/* ================= NAV ================= */

.nav{
    height:54px;
    background:#ffd400;

    display:flex;
    align-items:center;
    justify-content:center;

    gap:54px;

    box-shadow:0 2px 6px rgba(0,0,0,.04);
}

.nav a{
    text-decoration:none;
    color:#111;
    font-weight:700;
    font-size:15px;

    transition:.2s;
}

.nav a:hover{
    transform:translateY(-1px);
}

.nav a.active{
    color:#008c3a;
}

@media(max-width:900px){
    .container{padding:30px 22px}
    .shop-card{flex-direction:column;align-items:flex-start}
    .price-row{flex-direction:column;gap:18px;margin-bottom:80px}
}
</style>
</head>

<body>

<header class="header">

    <div class="logo">
    <img src="assets/images/logo_ok.png" alt="FreshFast">
    </div>
    <div class="profile-menu">

        <button class="profile-btn" id="profileBtn">
                    <?php if (!empty($shopImage)): ?>
            <img src="<?= e($shopImage) ?>?v=<?= time() ?>" alt="profile">
        <?php else: ?>
            <div style="
                width:100%;
                height:100%;
                background:#16a34a;
                color:white;
                display:flex;
                align-items:center;
                justify-content:center;
                font-size:20px;
                font-weight:700;
            ">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M4 10L5.5 4H18.5L20 10" 
                            stroke="currentColor" 
                            stroke-width="1.7" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"/>

                        <path d="M5 10V18C5 18.5523 5.44772 19 6 19H18C18.5523 19 19 18.5523 19 18V10" 
                            stroke="currentColor" 
                            stroke-width="1.7"/>

                        <path d="M9 19V14H15V19" 
                            stroke="currentColor" 
                            stroke-width="1.7"/>

                        <path d="M3 10H21" 
                            stroke="currentColor" 
                            stroke-width="1.7"/>
                    </svg>
            </div>
        <?php endif; ?>
        </button>

        <div class="profile-dropdown" id="profileDropdown">

            <a href=" shop_profile.php">
                บัญชีของฉัน
            </a>

            <a href="logout.php?redirect=shop_login.php">
                ออกจากระบบ
            </a>

        </div>

    </div>
</header>

<nav class="nav">
    <a href="shop_home.php">หน้าหลัก</a>
    <a href="shop_products.php">สินค้า</a>
    <a href="shop_orders.php">รับคำสั่งซื้อ</a>
    <a href="shop_sales_history.php">ประวัติ</a>
</nav>

<main class="container">

    <section class="shop-card">
        <div class="shop-img">
            <img src="<?= e($shopImage) ?>?v=<?= time() ?>" alt="shop image">
        </div>

        <div class="shop-info">
            <h2><?= e($shop['shop_name'] ?? 'ชื่อร้าน') ?></h2>
  
            <p></p>
        </div>
    </section>

    <section class="ad-box">
        <h1>โฆษณาของฉัน</h1>

        <form action="shop_ads_confirm.php" method="POST" class="ad-content">
            <h3>สร้างโฆษณาใหม่</h3>

            <div class="price-row">
                <label class="price-option">
                    <input type="radio" name="ad_price" value="50" required>
                    <span>50 บาท</span>
                </label>

                <label class="price-option">
                    <input type="radio" name="ad_price" value="70" required>
                    <span>70 บาท</span>
                </label>

                <label class="price-option">
                    <input type="radio" name="ad_price" value="150" required>
                    <span>150 บาท</span>
                </label>
            </div>

            <div class="action-row">
                <a href="shop_home.php" class="cancel-btn">ยกเลิก</a>
                <button type="submit" class="confirm-btn">ยืนยัน</button>
            </div>
        </form>
    </section>

</main>

<footer class="footer">
    <div class="footer-line">
        คำถามที่พบบ่อย&nbsp;&nbsp; ติดต่อเรา&nbsp;&nbsp; ประกาศความเป็นส่วนตัว&nbsp;&nbsp;
        สำหรับลูกค้า&nbsp;&nbsp; นโยบายการใช้คุกกี้&nbsp;&nbsp; การตั้งค่าคุกกี้&nbsp;&nbsp;
        ข้อกำหนดและเงื่อนไขนโยบายการคุ้มครองข้อมูลส่วนบุคคล&nbsp;&nbsp; ลงทะเบียน

        <small>ลิขสิทธิ์ © 2025-2026 FreshFast สงวนลิขสิทธิ์.</small>
    </div>
</footer>

</body>
</html>