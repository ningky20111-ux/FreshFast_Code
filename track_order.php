<?php
session_start();
$email = $_SESSION['user_email'] ?? null;

$avatar = $email
  ? "https://www.gravatar.com/avatar/" . md5(strtolower(trim($email))) . "?d=identicon&s=96"
  : null;

require_once 'db.php';

$userAvatar = null;

if ($email) {

    $stmtUser = $conn->prepare("
        SELECT profile_image
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    $stmtUser->bind_param("s", $email);
    $stmtUser->execute();

    $userData = $stmtUser->get_result()->fetch_assoc();

    $stmtUser->close();

    if (!empty($userData['profile_image'])) {
        $userAvatar = $userData['profile_image'];
    } else {
        $userAvatar = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($email))) . "?d=identicon&s=96";
    }
}

function e($str)
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    header("Location: home.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| ดึงข้อมูลคำสั่งซื้อ + ไรเดอร์
|--------------------------------------------------------------------------
*/
$sqlOrder = "
    SELECT 
        o.order_id,
        o.user_id,
        o.rider_id,
        o.status,
        o.delivery_address,
        o.created_at,
        r.full_name AS rider_name,
        r.phone AS rider_phone,
        r.vehicle_type,
        r.license_plate
    FROM orders o
    LEFT JOIN riders r ON o.rider_id = r.rider_id
    WHERE o.order_id = ?
    LIMIT 1
";

$stmtOrder = $conn->prepare($sqlOrder);
$stmtOrder->bind_param("i", $order_id);
$stmtOrder->execute();
$orderResult = $stmtOrder->get_result();
$order = $orderResult->fetch_assoc();
$stmtOrder->close();

if (!$order) {
    header("Location: home.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| ดึงรายการสินค้าในออเดอร์
|--------------------------------------------------------------------------
*/
$sqlItems = "
    SELECT 
        oi.order_item_id,
        oi.product_id,
        oi.quantity,
        oi.price,
        p.product_name,
        s.shop_name
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.product_id
    LEFT JOIN shops s ON p.shop_id = s.shop_id
    WHERE oi.order_id = ?
    ORDER BY oi.order_item_id ASC
";

$stmtItems = $conn->prepare($sqlItems);
$stmtItems->bind_param("i", $order_id);
$stmtItems->execute();
$itemsResult = $stmtItems->get_result();

$items = [];
$totalAmount = 0;
$totalQty = 0;

while ($row = $itemsResult->fetch_assoc()) {
    $qty = (int)$row['quantity'];
    $price = (float)$row['price'];
    $lineTotal = $qty * $price;
    $totalAmount += $lineTotal;
    $totalQty += $qty;
    $row['line_total'] = $lineTotal;
    $items[] = $row;
}
$stmtItems->close();

/*
|--------------------------------------------------------------------------
| map status
|--------------------------------------------------------------------------
*/
$status = $order['status'] ?? 'pending';

$steps = [
    'pending'    => 1,
    'accepted'   => 2,
    'delivering' => 4,
    'completed'  => 5
];

$currentStep = $steps[$status] ?? 1;

$statusLabels = [
    1 => 'สั่งซื้อสำเร็จ',
    2 => 'ผู้ขายกำลังเตรียมสินค้า',
    3 => 'ไรเดอร์กำลังเข้ารับสินค้า',
    4 => 'กำลังเดินทางไปส่ง',
    5 => 'จัดส่งสำเร็จ'
];

$statusTextMap = [
    'pending'    => 'สั่งซื้อสำเร็จ',
    'accepted'   => 'ผู้ขายกำลังเตรียมสินค้า',
    'delivering' => 'กำลังเดินทางไปส่ง',
    'completed'  => 'จัดส่งสำเร็จ'
];

$currentStatusText = $statusTextMap[$status] ?? 'กำลังดำเนินการ';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดตามคำสั่งซื้อ - FreshFast</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>

    <style>
        * { box-sizing: border-box; }

        body{
            margin:0;
            font-family:'textarea',sans-serif;
            background:#dceee0;
            color:#111;
        }

        a{
            text-decoration:none;
            color:inherit;
        }


        .nav-icons{
            display:flex;
            align-items:center;
            gap:18px;
            font-size:20px;
        }
        /* ================= TOPBAR ================= */
        .topbar{
        position:sticky;
        top:0;
        z-index:100;
        background:#fff;
        border-bottom:1px solid rgba(0,0,0,.06);
        }

        .topbar__inner{
        max-width:1200px;
        margin:0 auto;
        padding:14px 20px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:16px;
        }

        .left-group{
        display:flex;
        align-items:center;
        gap:12px;
        }

        /* ================= DESKTOP MENU ================= */
        .desktop-menu{
        position:relative;
        display:flex;
        align-items:center;
        }

        .desktop-menu-btn{
        height:44px;
        padding:0 18px;
        border:none;
        background:#f6f6f6;
        border-radius:999px;
        font-weight:700;
        cursor:pointer;
        display:flex;
        align-items:center;
        gap:8px;
        transition:.2s;
        }

        .desktop-menu-btn:hover{
        background:#ececec;
        }

        .desktop-menu-dropdown{
        position:absolute;
        top:54px;
        left:0;
        width:240px;
        background:#fff;
        border-radius:16px;
        box-shadow:0 14px 35px rgba(0,0,0,.14);
        border:1px solid rgba(0,0,0,.06);
        padding:10px;
        display:none;
        z-index:200;
        }

        .desktop-menu-dropdown.show{
        display:block;
        }

        .desktop-menu-dropdown a{
        display:block;
        padding:12px 14px;
        border-radius:10px;
        text-decoration:none;
        color:#111;
        font-weight:600;
        }

        .desktop-menu-dropdown a:hover{
        background:#f5f5f5;
        }

        /* ================= SEARCH ================= */
        .desktop-search{
        flex:1;
        max-width:520px;
        }

        .desktop-search input{
        width:100%;
        height:46px;
        border-radius:999px;
        border:1px solid #ddd;
        padding:0 18px;
        font-size:14px;
        outline:none;
        }

        /* mobile search (header) */
        .mobile-search{
        display:none;
        }

        .search__icon{
        display:flex;
        align-items:center;
        justify-content:center;
        color:#666;
        margin-right:8px;
        }

        .search-svg{
        width:18px;
        height:18px;
        stroke:#222;
        opacity:.8;
        }

        /* ================= BRAND ================= */
        .brand img{
        height:54px;
        display:block;
        }

        /* ================= ACTION ICONS ================= */
        .actions{
        display:flex;
        align-items:center;
        gap:8px;
        }

        .iconbtn{
        background:transparent;
        border:none;
        cursor:pointer;
        padding:8px;
        border-radius:50%;
        transition:.2s;
        display:flex;
        align-items:center;
        justify-content:center;
        }

        .iconbtn:hover{
        background:#f7f7f7;
        }

        .icon{
        width:22px;
        height:22px;
        stroke:#222;
        transition:.2s;
        }

        .iconbtn:hover .icon{
        stroke:#f5c542;
        transform:scale(1.08);
        }

        /* ================= PROFILE ================= */
        .profile-menu{
        position:relative;
        display:flex;
        align-items:center;
        }

        .avatar-btn{
        border:none;
        background:none;
        padding:0;
        cursor:pointer;
        }

        .avatar{
        width:40px;
        height:40px;
        border-radius:999px;
        object-fit:cover;
        border:2px solid #eee;
        }

        .dropdown{
        position:absolute;
        top:48px;
        right:0;
        width:190px;
        background:#fff;
        border-radius:14px;
        box-shadow:0 10px 30px rgba(0,0,0,.12);
        border:1px solid rgba(0,0,0,.08);
        padding:8px;
        display:none;
        }

        .dropdown.show{
        display:block;
        }

        .dropdown-item{
        display:block;
        padding:10px 12px;
        border-radius:10px;
        text-decoration:none;
        color:#111;
        font-weight:600;
        }

        .dropdown-item:hover{
        background:#f5f5f5;
        }

        .dropdown-sep{
        height:1px;
        background:#eee;
        margin:6px 0;
        }

        /* ================= MOBILE MENU ================= */
        .menu-btn{
        display:none;
        width:42px;
        height:42px;
        border:none;
        background:none;
        font-size:24px;
        cursor:pointer;
        }

        .mobile-menu{
        position:fixed;
        top:0;
        left:-100%;
        width:270px;
        height:100%;
        background:#fff;
        box-shadow:6px 0 25px rgba(0,0,0,.15);
        padding:20px;
        overflow-y:auto;
        transition:.3s;
        z-index:1001;
        }

        .mobile-menu.show{
        left:0;
        }

        .mobile-overlay{
        position:fixed;
        inset:0;
        background:rgba(0,0,0,.4);
        display:none;
        z-index:1000;
        }

        .mobile-overlay.show{
        display:block;
        }
        .track {
            display: flex;
            justify-content: space-between;
            position: relative;
            padding: 0 22px; /* ครึ่งวงกลม */
        }
        /* เส้นต้องเต็มระหว่าง padding */
        .track::before {
            content: "";
            position: absolute;
            top: 22px;
            left: 22px;
            right: 22px;
            height: 4px;
            background: #e5e5e5;
            z-index: 0;
            border-radius: 999px;
        }
        .track-item {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 2;
        }
        .track-item .circle {
            position: relative;
            z-index: 2;
            width: 44px;
            height: 44px;
            margin: 0 auto;
            border-radius: 50%;
            background: #e5e5e5;
            display: grid;
            place-items: center;
            font-size: 16px;
            font-weight: 900;
            color: #888;
        }   
        .track-item.done .circle {
            background: #16a34a;
            color: #fff;
        }
        .track-item.active .circle {
            background: #22c55e;
            color: #fff;
            box-shadow: 0 0 0 6px rgba(34,197,94,.15);
        }   
        .track-item .label {
            margin-top: 10px;
            font-size: 13px;
            font-weight: 700;
            color: #444;
        }

                /* ================= RESPONSIVE (HEADER ONLY) ================= */
        @media (max-width:768px){
        .desktop-logo,
        .desktop-menu,
        .desktop-search,
        .profile-menu{
            display:none;
        }

        .menu-btn{
            display:flex;
            align-items:center;
            justify-content:center;
            flex-shrink:0;
        }

        .mobile-search{
            display:flex;
            align-items:center;
            flex:1;
            height:46px;
            border-radius:999px;
            background:#f6f6f6;
            border:1px solid #e4e4e4;
            padding:0 14px;
        }

        .mobile-search input{
            flex:1;
            border:none;
            outline:none;
            background:transparent;
            font-size:14px;
        }

        .topbar__inner{
            padding:10px 14px;
            gap:10px;
        }

        .actions{
            gap:2px;
        }

        .iconbtn{
            padding:6px;
        }

        .icon{
            width:20px;
            height:20px;
        }
        }
        .nav-icons a.active{
            background:#f1c40f;
            width:42px;
            height:42px;
            border-radius:50%;
            display:grid;
            place-items:center;
        }

        .category-bar{
            background:#069b2e;
            color:#fff;
            padding:0 24px;
        }

        .category-menu{
            display:flex;
            justify-content:center;
            gap:38px;
            flex-wrap:wrap;
            min-height:48px;
            align-items:center;
            font-size:15px;
            font-weight:600;
        }

        .hero{
            width:100%;
            height:320px;
            overflow:hidden;
            background:#eee;
        }

        .hero img{
            width:100%;
            height:100%;
            object-fit:cover;
            display:block;
        }

        .container{
            max-width:1400px;
            margin:0 auto;
            padding:50px 24px 80px;
        }

        .page-title{
            text-align:center;
            font-size:54px;
            font-weight:800;
            margin:0 0 8px;
        }

        .subtitle{
            text-align:center;
            font-size:22px;
            color:#444;
            margin-bottom:8px;
        }

        .amount{
            text-align:center;
            font-weight:700;
            font-size:22px;
            margin-bottom:24px;
        }

        .status-card,
        .detail-card,
        .map-card{
            background:#f4f4f4;
            border-radius:24px;
            overflow:hidden;
            box-shadow:0 10px 24px rgba(0,0,0,0.06);
            margin-bottom:28px;
        }

        .status-header,
        .detail-header,
        .map-header{
            background:#efcb19;
            padding:18px 24px;
            font-weight:800;
            font-size:22px;
        }

        .status-body{
            padding:26px 20px 30px;
        }

        .steps{
            display:grid;
            grid-template-columns:repeat(5, 1fr);
            gap:12px;
            text-align:center;
            align-items:start;
            margin-bottom:26px;
        }

        .step .icon{
            font-size:28px;
            margin-bottom:10px;
        }

        .step .label{
            font-size:16px;
            font-weight:800;
            line-height:1.4;
        }

        .progress-line{
            display:grid;
            grid-template-columns:repeat(5, 1fr);
            align-items:center;
            gap:0;
            margin-top:12px;
        }

        .progress-dot-wrap{
            position:relative;
            display:flex;
            justify-content:center;
            align-items:center;
        }

        .progress-dot{
            width:34px;
            height:34px;
            border-radius:50%;
            background:#bfbfbf;
            color:white;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:18px;
            font-weight:900;
            z-index:2;
        }

        .progress-dot.active{
            background:#16a34a;
        }

        .progress-dot.current{
            background:#16a34a;
            box-shadow:0 0 0 6px rgba(22,163,74,0.12);
        }

        .progress-segment{
            height:6px;
            background:#bfbfbf;
            margin-top:-20px;
            position:relative;
            top:17px;
        }

        .progress-segment.active{
            background:#16a34a;
        }

        .detail-body{
            padding:0;
        }

        .detail-section{
            padding:20px 24px;
        }

        .detail-grid{
            display:grid;
            grid-template-columns:repeat(2, 1fr);
            gap:14px 30px;
            margin-bottom:10px;
        }

        .detail-grid div{
            font-size:17px;
            line-height:1.7;
        }

        .detail-grid strong{
            display:inline-block;
            min-width:120px;
        }

        .divider{
            border-top:1px solid #cfcfcf;
        }

        .items-header,
        .item-row{
            display:grid;
            grid-template-columns:2fr 1fr 1fr 1fr;
            gap:16px;
            align-items:center;
        }

        .items-header{
            padding:16px 24px;
            font-weight:800;
            color:#333;
        }

        .item-row{
            padding:18px 24px;
            border-top:1px solid #ddd;
        }

        .map-header{
            background:#08992a;
            color:#fff;
        }

        .map-body{
            padding:18px 18px 20px;
        }

        #map{
            width:100%;
            height:420px;
            border-radius:18px;
            overflow:hidden;
        }

        .rider-box{
            margin-top:16px;
            display:grid;
            grid-template-columns:repeat(2, 1fr);
            gap:12px 24px;
            font-size:16px;
        }

        .back-home{
            display:block;
            width:270px;
            max-width:100%;
            margin:28px auto 0;
            background:#0a9d2d;
            color:#fff;
            text-align:center;
            height:48px;
            line-height:48px;
            border-radius:999px;
            font-weight:800;
        }

        .map-status{
            margin-top:14px;
            font-size:15px;
            color:#555;
        }

@media (max-width: 768px) {

    .detail-header {
        font-size: 18px;
        padding: 14px 16px;
    }

    .detail-section {
        padding: 14px 16px;
    }

    /* เปลี่ยนจาก 2 คอลัมน์ → 1 คอลัมน์ + card style */
    .detail-grid {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .detail-grid div {
        background: #fff;
        padding: 12px 14px;
        border-radius: 12px;
        font-size: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }

    .detail-grid strong {
        min-width: auto;
        color: #666;
        font-weight: 600;
    }

    /* items header ซ่อนอยู่แล้วดี แต่ปรับ row ให้สวยขึ้น */
    .item-row {
        display: flex;
        flex-direction: column;
        gap: 6px;
        background: #fff;
        margin: 10px 16px;
        border-radius: 14px;
        padding: 14px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    }

    .item-row > div {
        display: flex;
        justify-content: space-between;
        font-size: 14px;
    }

    .item-row strong {
        font-size: 15px;
    }

    .divider {
        margin: 10px 0;
    }
    @media (max-width: 768px) {

    .detail-grid div {
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }

    .detail-grid strong {
        color: #777;
        font-size: 13px;
    }

    /* ทำเฉพาะที่อยู่ให้เด่นขึ้น */
    .detail-grid div:has(strong:contains("ที่อยู่")) {
        align-items: flex-start;
    }

    /* fallback (เพราะ :contains ใช้ไม่ได้ใน CSS จริง) */

}

        /* ================= MODERN TRACK (GRAB STYLE) ================= */
/* ================= MODERN TRACK FIXED ================= */

        .modern-track {
            padding: 20px 10px;
        }

        /* เส้นหลักเส้นเดียว */
        .track {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            position: relative;
            padding: 0 10px;
        }

        /* เส้นกลาง (เส้นเดียวพอ) */
        .track::before {
            content: "";
            position: absolute;
            top: 22px;
            left: 40px;
            right: 40px;
            height: 4px;
            background: #e5e5e5;
            z-index: 0;
            border-radius: 999px;
        }

        /* item */
        .track-item {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        /* circle */
        .track-item .circle {
            width: 44px;
            height: 44px;
            margin: 0 auto;
            border-radius: 50%;
            background: #e5e5e5;
            display: grid;
            place-items: center;
            font-size: 16px;
            font-weight: 900;
            color: #888;
        }

        /* done */
        .track-item.done .circle {
            background: #16a34a;
            color: #fff;
        }

        /* active */
        .track-item.active .circle {
            background: #22c55e;
            color: #fff;
            box-shadow: 0 0 0 6px rgba(34,197,94,.15);
        }

        /* label */
        .track-item .label {
            margin-top: 10px;
            font-size: 13px;
            font-weight: 700;
            color: #444;
        }

/* ================= MOBILE FIX ================= */
    .track {
        flex-direction: column;
        align-items: flex-start;
        gap: 22px;
        padding-left: 40px;
    }

    /* เส้นแนวตั้งแทนแนวนอน */
    .track::before {
        width: 4px;
        height: calc(100% - 40px);
        left: 57px;
        top: 20px;
        right: auto;
    }

    .track-item {
        display: flex;
        align-items: center;
        gap: 14px;
        text-align: left;
    }

    .track-item .label {
        margin: 0;
        font-size: 14px;
    }

    .track-item .circle {
        width: 38px;
        height: 38px;
        font-size: 14px;
        margin: 0;
    }
}

    </style>
</head>
<body>

<!-- HEADER -->
<header class="topbar">
  <div class="topbar__inner">

    <div class="left-group">

      <!-- MOBILE MENU BTN -->
      <button class="menu-btn" id="menuBtn">☰</button>

      <!-- LOGO -->
      <a class="brand desktop-logo" href="home.php">
        <img src="assets/images/logo_ok.png" alt="FreshFast">
      </a>

      <!-- DESKTOP DROPDOWN -->
      <div class="desktop-menu">
        <button class="desktop-menu-btn" id="desktopMenuBtn">
          เมนู ▼
        </button>

        <div class="desktop-menu-dropdown" id="desktopMenuDropdown">
          <a href="home.php">หน้าหลัก</a>
          <a href="category.php?id=1">เนื้อสัตว์</a>
          <a href="category.php?id=2">ผักผลไม้</a>
          <a href="category.php?id=3">เครื่องปรุง</a>
          <a href="category.php?id=4">เครื่องดื่ม</a>
          <a href="category.php?id=5">แช่แข็ง</a>
          <a href="category.php?id=6">ของใช้ในครัว</a>
          <a href="category.php?id=7">ของหวาน</a>
            <a href="shop_all.php">ร้านค้าทั้งหมด</a>
        </div>
      </div>

    </div>

    <!-- DESKTOP SEARCH -->
    <form class="desktop-search" action="search.php" method="GET">

    <input type="text" name="q" placeholder="ค้นหาสินค้า / ร้านค้า">
    </form>


    <!-- MOBILE SEARCH -->
    <form class="mobile-search" action="search.php" method="GET">
      <span class="search__icon">
        <svg viewBox="0 0 24 24" class="search-svg" fill="none" stroke="currentColor"
          stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="7"></circle>
          <line x1="20" y1="20" x2="16.65" y2="16.65"></line>
        </svg>
      </span>
      <input type="text" name="q" placeholder="ค้นหาสินค้า / ร้านค้า">
    </form>

    <!-- ACTIONS -->
    <div class="actions">

      <a href="favorites.php" class="iconbtn" aria-label="รายการโปรด">
        <svg viewBox="0 0 24 24" class="icon" fill="none" stroke="currentColor"
          stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20.5 6.5c-1.5-1.5-4-1.5-5.5 0L12 9.5l-3-3c-1.5-1.5-4-1.5-5.5 0s-1.5 4 0 5.5L12 21l8.5-9c1.5-1.5 1.5-4 0-5.5z"/>
        </svg>
      </a>

      <a href="cart.php" class="iconbtn" aria-label="ตะกร้า">
        <svg viewBox="0 0 24 24" class="icon" fill="none" stroke="currentColor"
          stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="20" r="1.5"/>
          <circle cx="17" cy="20" r="1.5"/>
          <path d="M3 3h2l2.5 11h11l2-7H6.5"/>
        </svg>
      </a>

      <?php if($email): ?>
      <div class="profile-menu">
        <button class="avatar-btn" id="avatarBtn">
<img class="avatar" src="<?= htmlspecialchars($userAvatar) ?>"></button>

        <div class="dropdown" id="profileDropdown">
          <a href="account.php" class="dropdown-item">บัญชีของฉัน</a>
          <div class="dropdown-sep"></div>
          <a href="logout.php" class="dropdown-item">ออกจากระบบ</a>
        </div>
      </div>
      <?php endif; ?>

    </div>

  </div>
</header>

<!-- MOBILE MENU -->
<div class="mobile-menu" id="mobileMenu">
  <div class="logo-item">
    <img src="assets/images/logo_ok.png" style="height:40px;">
  </div>

  <a href="home.php" class="menu-item">หน้าหลัก</a>

  <div class="menu-title">หมวดหมู่</div>
  <a href="category.php?id=1" class="menu-item">เนื้อสัตว์</a>
  <a href="category.php?id=2" class="menu-item">ผักผลไม้</a>
  <a href="category.php?id=3" class="menu-item">เครื่องปรุง</a>
  <a href="category.php?id=4" class="menu-item">เครื่องดื่ม</a>
  <a href="category.php?id=5" class="menu-item">แช่แข็ง</a>
  <a href="category.php?id=6" class="menu-item">ของใช้ในครัว</a>
  <a href="category.php?id=7" class="menu-item">ของหวาน</a>
  <a href="shop_all.php" class="menu-item">ร้านค้าทั้งหมด</a>

    <div class="menu-title">ผู้ใช้งาน</div>
  <a href="account.php" class="menu-item">บัญชีของฉัน</a>
  <a href="orders.php" class="menu-item">คำสั่งซื้อของฉัน</a>
  <a href="favorites.php" class="menu-item">รายการโปรด</a>
  <a href="cart.php" class="menu-item">ตะกร้าสินค้า</a>
  <a href="logout.php" class="menu-item">ออกจากระบบ</a>
</div>

<div class="mobile-overlay" id="mobileOvexrlay"></div>

    <section class="hero">
        <img src="assets/images/pf5.png" alt="FreshFast Banner">
    </section>

    <div class="container">
        <h1 class="page-title">สถานะคำสั่งซื้อ</h1>
        <div class="subtitle"><?= e($currentStatusText) ?></div>
        <div class="amount"><?= number_format($totalAmount, 2) ?> บ.</div>

        <div class="status-card">
            <div class="status-header">ความคืบหน้าการจัดส่ง</div>
            <div class="status-body modern-track">

                <div class="track">
                    <?php 
                    $icons = ['🧾','🛍️','🏍️','🚚','📦'];
                    for ($i = 1; $i <= 5; $i++): 
                    ?>
                    <div class="track-item <?= $i < $currentStep ? 'done' : '' ?> <?= $i == $currentStep ? 'active' : '' ?>">
                        
                        <div class="circle">
                            <?= $i <= $currentStep ? '✓' : $icons[$i-1] ?>
                        </div>

                      

                        <div class="label">
                            <?= e($statusLabels[$i]) ?>
                        </div>

                    </div>
                    <?php endfor; ?>
                </div>

            </div>
        </div>

        <div class="detail-card">
            <div class="detail-header">หมายเลขคำสั่งซื้อ #<?= (int)$order['order_id'] ?></div>
            <div class="detail-body">

                <div class="detail-section">
                    <div class="detail-grid">
                        <div><strong>สถานะ :</strong> <?= e($currentStatusText) ?></div>
                        <div><strong>วันที่สั่งซื้อ :</strong> <?= e($order['created_at']) ?></div>
                        <div><strong>ชื่อไรเดอร์ :</strong> <?= e($order['rider_name'] ?: '-') ?></div>
                        <div><strong>เบอร์ไรเดอร์ :</strong> <?= e($order['rider_phone'] ?: '-') ?></div>
                        <div><strong>ทะเบียนรถ :</strong> <?= e($order['license_plate'] ?: '-') ?></div>
                        <div><strong>ประเภทยานพาหนะ :</strong> <?= e($order['vehicle_type'] ?: '-') ?></div>
                        <div><strong>ที่อยู่จัดส่ง :</strong> <?= e($order['delivery_address'] ?: '-') ?></div>
                        <div><strong>จำนวนสินค้า </strong> <?= (int)$totalQty ?> รายการ</div>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="items-header">
                    <div>ชื่อสินค้า</div>
                    <div>ราคา</div>
                    <div>จำนวน</div>
                    <div>รวม</div>
                </div>

                <?php foreach ($items as $item): ?>
                    <div class="item-row">
                        <div>
                            <strong><?= e($item['product_name'] ?: 'สินค้า') ?></strong>
                            <div style="font-size:14px;color:#666;margin-top:4px;">
                                ร้าน: <?= e($item['shop_name'] ?: '-') ?>
                            </div>
                        </div>
                        <div><?= number_format((float)$item['price'], 2) ?> บ.</div>
                        <div><?= (int)$item['quantity'] ?></div>
                        <div><?= number_format((float)$item['line_total'], 2) ?> บ.</div>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>

        <div class="map-card">
            <div class="map-header">ติดตามตำแหน่งไรเดอร์แบบเรียลไทม์</div>
            <div class="map-body">
                <div id="map"></div>

                <div class="rider-box">
                <div><strong>ไรเดอร์:</strong> <?= e($order['rider_name'] ?: '-') ?></div>
                <div><strong>เบอร์โทร:</strong> <?= e($order['rider_phone'] ?: '-') ?></div>
                <div><strong>ทะเบียน:</strong> <?= e($order['license_plate'] ?: '-') ?></div>
                <div><strong>ประเภทยานพาหนะ:</strong> <?= e($order['vehicle_type'] ?: '-') ?></div>
                    <div><strong>สถานะล่าสุด:</strong> <span id="liveStatus">กำลังโหลดตำแหน่ง...</span></div>
                </div>

                <div class="map-status" id="updatedAt">ยังไม่มีการอัปเดต</div>
            </div>
        </div>

        <a href="home.php" class="back-home">กลับสู่หน้าหลัก</a>
    </div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        const orderId = <?= (int)$order_id ?>;
        const riderId = <?= (int)($order['rider_id'] ?? 0) ?>;

        const map = L.map('map').setView([16.7167, 98.5667], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        let riderMarker = null;
        let firstLoad = true;

        function updateRiderLocation() {
            if (!riderId) {
                document.getElementById('liveStatus').textContent = 'ยังไม่มีไรเดอร์รับงาน';
                return;
            }

            fetch(`get_rider_location.php?order_id=${orderId}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('liveStatus').textContent = data.message || 'ไม่พบตำแหน่ง';
                        return;
                    }

                    const lat = parseFloat(data.latitude);
                    const lng = parseFloat(data.longitude);

                    if (!riderMarker) {
                        riderMarker = L.marker([lat, lng]).addTo(map)
                            .bindPopup(`ไรเดอร์: ${data.rider_name || 'Rider'}`);
                    } else {
                        riderMarker.setLatLng([lat, lng]);
                    }

                    if (firstLoad) {
                        map.setView([lat, lng], 16);
                        firstLoad = false;
                    }

                    document.getElementById('liveStatus').textContent = 'อัปเดตตำแหน่งแล้ว';
                    document.getElementById('updatedAt').textContent = 'อัปเดตล่าสุด: ' + (data.updated_at || '-');
                })
                .catch(() => {
                    document.getElementById('liveStatus').textContent = 'โหลดตำแหน่งไม่สำเร็จ';
                });
        }

        updateRiderLocation();
        setInterval(updateRiderLocation, 5000);
    </script>
<script>
const btn = document.getElementById('desktopMenuBtn');
const dropdown = document.getElementById('desktopMenuDropdown');

btn.addEventListener('click', function (e) {
    e.stopPropagation();
    dropdown.classList.toggle('show');
});

// คลิกข้างนอกให้ปิด
document.addEventListener('click', function () {
    dropdown.classList.remove('show');
});
</script>
</body>
</html>