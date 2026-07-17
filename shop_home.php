<?php
session_start();
require_once "db.php";

if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "freshfast");
    $conn->set_charset("utf8mb4");
}

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function columnExists($conn, $table, $column)
{
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);

    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");

    return $result && $result->num_rows > 0;
}

/* =========================
   CHECK COLUMNS
========================= */
if (!columnExists($conn, "shops", "shop_id")) {
    $conn->query("ALTER TABLE shops ADD COLUMN shop_id INT AUTO_INCREMENT PRIMARY KEY");
}

if (!columnExists($conn, "shops", "owner_id")) {
    $conn->query("ALTER TABLE shops ADD COLUMN owner_id INT NULL");
}

if (!columnExists($conn, "shops", "status")) {
    $conn->query("ALTER TABLE shops ADD COLUMN status VARCHAR(20) DEFAULT 'open'");
}

if (!columnExists($conn, "shops", "shop_image")) {
    $conn->query("ALTER TABLE shops ADD COLUMN shop_image VARCHAR(255) NULL");
}
if (!columnExists($conn, "shops", "shop_type")) {
    $conn->query("
        ALTER TABLE shops 
        ADD COLUMN shop_type VARCHAR(100) NULL
    ");
}
/* =========================
   CURRENT USER
========================= */
// $currentUserId = $_SESSION['shop_user_id'] ?? 0;

// if (!$currentUserId && isset($_SESSION['shop_email'])) {

//     $email = $_SESSION['shop_email'];

//     $stmt = $conn->prepare("
//         SELECT shop_id 
//         FROM shops 
//         WHERE email = ?
//         LIMIT 1
//     ");

//     $stmt->bind_param("s", $email);
//     $stmt->execute();

//     $user = $stmt->get_result()->fetch_assoc();

//     $currentUserId = $user['user_id'] ?? 0;
// }

// /* =========================
//    GET SHOP
// ========================= */
// $shop = null;

// if ($currentUserId > 0) {

//     $stmt = $conn->prepare("
//         SELECT *
//         FROM shops
//         WHERE owner_id = ?
//         LIMIT 1
//     ");

//     $stmt->bind_param("i", $currentUserId);
//     $stmt->execute();

//     $shop = $stmt->get_result()->fetch_assoc();
// }

// /* fallback ใช้ร้านแรก */
// if (!$shop) {
//     session_destroy();
//     header("Location: shop_login.php?error=กรุณาเข้าสู่ระบบใหม่");
//     exit;
// }

// if (!$shop) {
//     die("ยังไม่มีข้อมูลร้านค้า");
// }

// $shopId = (int)$shop['shop_id'];
// $shopName = $shop['shop_name'];

// /* =========================
//    UPDATE SHOP TYPE
// ========================= */
// // if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_shop_type'])) {

// //     $shopType = trim($_POST['shop_type'] ?? '');

// //     if ($shopType !== '') {

// //         $stmt = $conn->prepare("
// //             UPDATE shops
// //             SET shop_type = ?
// //             WHERE shop_id = ?
// //         ");

// //         $stmt->bind_param("si", $shopType, $shopId);
// //         $stmt->execute();
// //     }

// //     header("Location: shop_home.php");
// //     exit;
// // }
// /* =========================
//    TOGGLE SHOP STATUS
// ========================= */
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {

//     $newStatus = ($shop['status'] ?? 'open') === 'open'
//         ? 'closed'
//         : 'open';

//     $stmt = $conn->prepare("
//         UPDATE shops
//         SET status = ?
//         WHERE shop_id = ?
//     ");

//     $stmt->bind_param("si", $newStatus, $shopId);
//     $stmt->execute();

//     header("Location: shop_home.php");
//     exit;
// }
$shopId = $_SESSION['shop_id'] ?? 0;

if (!$shopId) {
    header("Location: shop_login.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT *
    FROM shops
    WHERE shop_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $shopId);
$stmt->execute();

$shop = $stmt->get_result()->fetch_assoc();

/* =========================
   AUTO OPEN / CLOSE
========================= */

date_default_timezone_set("Asia/Bangkok");

if ($shop['auto_open_close']) {

    $now = date("H:i:s");

    if (
        $now >= $shop['opening_time'] &&
        $now < $shop['closing_time']
    ) {

        if ($shop['shop_status'] != "open") {

            $stmt = $conn->prepare("
                UPDATE shops
                SET shop_status='open'
                WHERE shop_id=?
            ");

            $stmt->bind_param("i",$shopId);
            $stmt->execute();

            $shop['shop_status']="open";
        }

    } else {

        if ($shop['shop_status'] != "closed") {

            $stmt = $conn->prepare("
                UPDATE shops
                SET shop_status='closed'
                WHERE shop_id=?
            ");

            $stmt->bind_param("i",$shopId);
            $stmt->execute();

            $shop['shop_status']="closed";
        }

    }

}
// echo "<pre>";
// print_r($shop);
// exit;

$shopName = $shop['shop_name'];
if (!$shop) {
    session_destroy();
    header("Location: shop_login.php");
    exit;
}
/* =========================
   TODAY SALES
========================= */
$stmt = $conn->prepare("
SELECT
    COALESCE(SUM(oi.line_total),0) AS today_sales,
    COUNT(DISTINCT o.order_id) AS today_orders
FROM order_items oi
INNER JOIN orders o
    ON oi.order_id = o.order_id
WHERE
    oi.shop_name = ?
AND DATE(o.created_at)=CURDATE()
");

$stmt->bind_param("s", $shopName);
$stmt->execute();

$today = $stmt->get_result()->fetch_assoc();
$shopName=$shop['shop_name'];
/* =========================
   SHOP IMAGE
========================= */
$shopImage = null;

if (
    !empty($shop['shop_image']) &&
    file_exists($shop['shop_image'])
) {
    $shopImage = $shop['shop_image'];
}

if (
    !empty($shop['shop_image']) &&
    file_exists($shop['shop_image'])
) {
    $shopImage = $shop['shop_image'];
}

$status = $shop['shop_status'];
?>

<!DOCTYPE html>
<html lang="th">

<head>
<meta charset="UTF-8">
<title>หน้าหลักผู้ขาย | FreshFast</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
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

/* ================= HERO ================= */

.hero{
    padding:60px 40px;
}

.shop-card{
    background:#fff;
    border-radius:14px;

    min-height:120px;
     margin-top:-38px;
    display:flex;
    align-items:center;

    gap:18px;

    padding:12px;

    box-shadow:0 10px 20px rgba(0,0,0,.08);
}

.image-box{
    width:220px;
    height:220px;

    border-radius:20px;
    overflow:hidden;

    flex-shrink:0;
}

.image-box img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.shop-info h2{
    font-size:40px;
    font-weight:800;
    margin:0 0 14px;
}

.shop-info p{
    font-size:18px;
    color:#666;
    margin:0 0 24px;
}

/* ================= FORM ================= */

.upload-form{
    display:flex;
    gap:12px;
    align-items:center;
    flex-wrap:wrap;
}

.upload-form input[type=file]{
    background:#f6f6f6;
    border:none;

    padding:12px;
    border-radius:14px;
}

.upload-form button{
    border:none;
    background:#16a34a;
    color:#fff;

    border-radius:14px;

    padding:12px 18px;

    font-weight:700;
    cursor:pointer;

    transition:.2s;
}

.upload-form button:hover{
    transform:scale(1.03);
}

/* ================= SALES ================= */

.sales-section{
    background:#bbf7d0;

    padding:12px 14px;
    margin-top:-38px;
    display:flex;
    align-items:center;
    justify-content:space-between;
}

.sales h3{
    margin:0;
    font-size:24px;
    font-weight:800;
}

.sales .amount{
    font-size:42px;
    font-weight:800;

    margin:18px 0 10px;
}

.sales p{
    margin:0;
    color:#333;
    font-size:18px;
}

.status-form button{
    border:none;

    border-radius:18px;

    padding:18px 34px;

    font-size:24px;
    font-weight:800;

    color:#fff;

    cursor:pointer;

    box-shadow:0 10px 20px rgba(0,0,0,.12);

    transition:.2s;
}

.status-form button:hover{
    transform:translateY(-2px);
}

.status-form .open{
    background:#16a34a;
}

.status-form .closed{
    background:#dc2626;
}

/* ================= PROMO ================= */

.promo-zone{
    padding:34px 40px 90px;

    display:grid;
    grid-template-columns:1fr 1fr;

    gap:28px;
}

.small-card{
    background:#fff;

    border-radius:24px;

    display:flex;
    align-items:center;

    gap:18px;

    padding:20px;

    box-shadow:0 10px 30px rgba(0,0,0,.06);
}

.small-img{
    width:130px;
    height:175px;

    border-radius:11px;
    overflow:hidden;

    flex-shrink:0;
}

.small-img img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.small-card h3{
    margin:0 0 6px;
    font-size:28px;
    font-weight:800;
}

.small-card p{
    margin:0;
    color:#666;
    font-size:16px;
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

.sales-top{
    display:flex;
    align-items:center;
}

.status-form-inline{
    position:absolute;

    top:14px;      /* ระยะจากขอบบน */
    right:18px;    /* ระยะจากขอบขวา */

    margin:0;
}

.status-form-inline button{
    border:none;
    border-radius:20px;

    padding:14px 24px;

    font-size:18px;
    font-weight:700;

    color:#fff;
    cursor:pointer;

    box-shadow:0 4px 10px rgba(0,0,0,.15);
}

.status-form-inline .open{
    background:#16a34a;
}

.status-form-inline .closed{
    background:#dc2626;
}
.small-card-link{
    text-decoration:none;
    color:inherit;
    display:block;
}
/* ================= MOBILE ================= */

@media(max-width:900px){
    body{
        margin:0;
        background:#eef7f0;
        font-family:sans-serif;
        color:#111;
    }

.status-form-inline button{
    border:none;
    border-radius:12px;

    padding:6px 14px;

    font-size:14px;
    font-weight:700;

    color:#fff;
    cursor:pointer;

    box-shadow:0 4px 10px rgba(0,0,0,.15);
}

    /* HEADER */

    .header{
        height:70px;

        background:#fff;

        display:flex;
        align-items:center;
        justify-content:space-between;

        padding:0 18px;

        position:sticky;
        top:0;

        z-index:1000;

        box-shadow:
        0 2px 12px rgba(0,0,0,.05);
    }

    .logo img{
        height:42px;
    }

    .shop-card{
        flex-direction:column;
        align-items:center;
        text-align:center;
        padding: 10px 12px;
        gap:12px;
    }

    .image-box{
        width:65px;
        height:65px;
        border-radius:14px;
    }

    .shop-info h2{
        font-size:24px;
        margin:0;
    }

    .shop-info p{
        font-size:14px;
        color:#666;
        margin-top:6px;
    }
    .sales-section{
        flex-direction:column;
        align-items:flex-start;
        padding: 10px 12px;
        gap:26px;
    }
        .promo-zone{
            padding:12px 14px 20px;
            display:flex;
            justify-content:center;  /* 🔥 ดันเข้ากลาง */
            gap:10px;
            overflow:visible;        /* ❌ ลบ scroll */
            flex-wrap:nowrap;
        }

        .small-card{
            flex:1;                  /* ให้แบ่งพื้นที่เท่ากัน */
            min-width:0;             /* 🔥 สำคัญ ไม่ให้ดันเกินจอ */
            display:flex;
            align-items:center;
            gap:8px;
            padding:12px;
            border-radius:14px;
        }
        .small-img{
            width:50px;
            height:70px;
            border-radius:10px;
        }

        .small-card h3{
            font-size:16px;
            margin:0;
        }

        .small-card p{
            font-size:12px;
        }

    .nav{
        height:54px;

        background:#ffd400;

        display:flex;
        align-items:center;

        gap:18px;

        overflow-x:auto;

        padding:0 18px;

        white-space:nowrap;
    }

    .nav::-webkit-scrollbar{
        display:none;
    }

    .nav a{
        text-decoration:none;

        color:#111;

        font-size:14px;
        font-weight:700;
    }

    .nav a.active{
        color:#008c3a;
    }

    .footer{
        padding:0 20px 30px;
    }

    .footer-line{
        border-top:1px solid #ddd;

        padding-top:20px;

        text-align:center;

        color:#666;

        font-size:13px;

        line-height:1.7;
    }

    .footer small{
        display:block;
        margin-top:14px;
    }
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

    <a href="shop_home.php" class="active">
        หน้าหลัก
    </a>

    <a href="shop_products.php">
        สินค้า
    </a>

    <a href="shop_orders.php">
        รับคำสั่งซื้อ
    </a>

    <a href="shop_sales_history.php">
        ประวัติ
    </a>

</nav>

<section class="hero">

    <div class="shop-card">

        <div class="image-box">

            <?php if (!empty($shopImage)): ?>

                <img src="<?= e($shopImage) ?>?v=<?= time() ?>" alt="shop image">

            <?php else: ?>

                <div class="empty-shop-image">

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

        </div>


<div class="shop-info">

    <h2>
        <?= e($shop['shop_name']) ?>
    </h2>


    <p class="shop-description">
        <?= !empty($shop['description']) 
            ? e($shop['description']) 
            : 'ยังไม่มีคำอธิบายร้าน'
        ?>
    </p>


    <p class="shop-time">

        🕒 เวลาเปิด-ปิดร้าน :

        <?php if(!empty($shop['opening_time']) && !empty($shop['closing_time'])): ?>

            <?= date("H:i", strtotime($shop['opening_time'])) ?>
            -
            <?= date("H:i", strtotime($shop['closing_time'])) ?>
            น.

        <?php else: ?>

            ยังไม่ได้ตั้งเวลา

        <?php endif; ?>

    </p>


</div>
</section>

    <section class="sales-section">

        <div class="sales">

            <div class="sales-top">
                <h3>ยอดขายวันนี้</h3>

                    <div class="status-form-inline">

                    <?php if($status=="open"): ?>

                    <button class="open" type="button">
                    🟢 เปิดอยู่
                    </button>

                    <?php else: ?>

                    <button class="closed" type="button">
                    🔴 ปิดอยู่
                    </button>

                    <?php endif; ?>

                    </div>
            </div>

            <div class="amount">
                <?= number_format((float)$today['today_sales'], 2) ?> บาท
            </div>

            <p>
                <?= number_format((int)$today['today_orders']) ?> คำสั่งซื้อ
            </p>

        </div>

    </section>

<section class="promo-zone">

    <div class="small-card">

        <div class="small-img">
            <img src="assets/images/promo.jpg">
        </div>

        <div>
            <h3>โปรโมชั่น</h3>
            <p>ช่วยให้คุณขายดีขึ้น!</p>
        </div>

    </div>

<a href="shop_ads.php" class="small-card-link">
    <div class="small-card">
        <div class="small-img">
            <img src="assets/images/ads.jpg">
        </div>

        <div>
            <h3>โฆษณา</h3>
            <p>เปิดการมองเห็น!</p>
        </div>
    </div>
</a>

</section>

<footer class="footer">

    <div class="footer-line">

        คำถามที่พบบ่อย ติดต่อเรา ประกาศความเป็นส่วนตัว
        สำหรับลูกค้า นโยบายการใช้คุกกี้
        การตั้งค่าคุกกี้ ข้อกำหนดและเงื่อนไข

        <small>
            ลิขสิทธิ์ © 2025-2026 FreshFast
            สงวนลิขสิทธิ์.
        </small>

    </div>

</footer>
<script>
const profileBtn = document.getElementById("profileBtn");
const profileDropdown = document.getElementById("profileDropdown");

profileBtn?.addEventListener("click", (e) => {
    e.stopPropagation();
    profileDropdown.classList.toggle("show");
});

document.addEventListener("click", () => {
    profileDropdown?.classList.remove("show");
});

profileDropdown?.addEventListener("click", (e) => {
    e.stopPropagation();
});
</script>
</body>
</html>