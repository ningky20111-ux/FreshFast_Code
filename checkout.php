<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function e($str)
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| ถ้าไม่มีสินค้าในตะกร้า ให้กลับไปหน้าตะกร้า
|--------------------------------------------------------------------------
*/
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| ดึงข้อมูลสินค้าในตะกร้า
|--------------------------------------------------------------------------
*/
$cartItems = [];
$subtotal = 0;
$deliveryFee = 60;
$discount = 0;
$totalItems = 0;
$shopNames = [];

$productIds = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($productIds), '?'));
$types = str_repeat('i', count($productIds));

$sql = "
    SELECT 
        p.product_id,
        p.product_name,
        p.price,
        p.unit,
        p.product_image,
        p.shop_id,
        s.shop_name
    FROM products p
    LEFT JOIN shops s ON p.shop_id = s.shop_id
    WHERE p.product_id IN ($placeholders)
    ORDER BY s.shop_name ASC, p.product_name ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$productIds);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $pid = (int)$row['product_id'];
    $qty = (int)($_SESSION['cart'][$pid] ?? 0);
    if ($qty <= 0) {
        continue;
    }

    $row['quantity'] = $qty;
    $row['line_total'] = $qty * (float)$row['price'];

    $subtotal += $row['line_total'];
    $totalItems += $qty;
    $cartItems[] = $row;

    if (!empty($row['shop_name'])) {
        $shopNames[$row['shop_name']] = $row['shop_name'];
    }
}
$stmt->close();

if (empty($cartItems)) {
    header("Location: cart.php");
    exit;
}

$grandTotal = max(0, $subtotal + $deliveryFee - $discount);

/*
|--------------------------------------------------------------------------
| ข้อมูล default ของฟอร์ม
|--------------------------------------------------------------------------
*/
$receiver_name = $_SESSION['checkout']['receiver_name'] ?? '';
$delivery_address = $_SESSION['checkout']['delivery_address'] ?? '';
$postal_code = $_SESSION['checkout']['postal_code'] ?? '63110';
$phone = $_SESSION['checkout']['phone'] ?? '';
$note = $_SESSION['checkout']['note'] ?? '';

/*
|--------------------------------------------------------------------------
| ถ้าล็อกอินอยู่ ลองดึงข้อมูลผู้ใช้มาใส่ default
|--------------------------------------------------------------------------
*/
if (empty($receiver_name) && !empty($_SESSION['user_email'])) {
    $email = trim((string)$_SESSION['user_email']);

    $sqlUser = "SELECT name, email FROM users WHERE email = ? LIMIT 1";
    $stmtUser = $conn->prepare($sqlUser);
    if ($stmtUser) {
        $stmtUser->bind_param("s", $email);
        $stmtUser->execute();
        $resUser = $stmtUser->get_result();
        if ($user = $resUser->fetch_assoc()) {
            $receiver_name = $user['name'] ?? '';
        }
        $stmtUser->close();
    }
}

/*
|--------------------------------------------------------------------------
| restaurant default
|--------------------------------------------------------------------------
*/
$restaurantName = 'ร้านของคุณ';
$restaurantAddress = 'แม่สอด จังหวัดตาก';

$sqlRestaurant = "SELECT restaurant_name, address FROM restaurants ORDER BY restaurant_id ASC LIMIT 1";
$resRestaurant = $conn->query($sqlRestaurant);
if ($resRestaurant && $resRestaurant->num_rows > 0) {
    $restaurant = $resRestaurant->fetch_assoc();
    if (!empty($restaurant['restaurant_name'])) {
        $restaurantName = $restaurant['restaurant_name'];
    }
    if (!empty($restaurant['address'])) {
        $restaurantAddress = $restaurant['address'];
    }
}

/*
|--------------------------------------------------------------------------
| validation message
|--------------------------------------------------------------------------
*/
$error = $_SESSION['checkout_error'] ?? '';
unset($_SESSION['checkout_error']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ที่อยู่ในการจัดส่ง - FreshFast</title>
    <link rel="stylesheet" href="assets/css/layout.css">
    <style>
        .map-search{
            width:100%;
            height:48px;
            border-radius:999px;
            border:1px solid #ccc;
            padding:0 16px;
            font-size:15px;
            margin-bottom:12px;
        }
        * { box-sizing: border-box; } 

        body {
            margin: 0;
            font-family: "textarea";
            background: #dceee0;
            color: #111;
        }
        body,
        input,
        textarea,
        button,
        select,
        .summary-row,
        .summary-row span,
        .summary-row strong,
        .summary-body,
        .card,
        .card * {
            font-family: "textarea", sans-serif !important;
        }

        a {
            text-decoration: none;
            color: inherit;
        }


        .search-box {
            flex: 1;
            max-width: 620px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            height: 48px;
            border-radius: 999px;
            border: none;
            outline: none;
            background: #fff;
            padding: 0 20px 0 52px;
            font-size: 15px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08) inset;
        }

        .search-box span {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 24px;
            opacity: 0.75;
        }


        .page-title {
            text-align: center;
            font-size: 52px;
            font-weight: 800;
            margin: 64px 0 40px;
            color: #111;
        }

        .main-wrap {
            background: #d9dc8f;
            padding: 50px 24px 80px;
        }

        .content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.45fr 0.95fr;
            gap: 36px;
            align-items: start;
        }

        .card {
            background: #f3f3f3;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 24px rgba(0,0,0,0.06);
        }
        .map-search{
            width:100%;
            height:48px;
            border-radius:999px;
            border:1px solid #ccc;
            padding:0 16px;
            font-size:15px;
            margin-bottom:12px;
        }

        .card-header-yellow {
            background: #efcb19;
            color: #111;
            font-weight: 800;
            padding: 18px 22px;
        }

        .card-header-green {
            background: #08992a;
            color: #fff;
            font-weight: 800;
            padding: 18px 22px;
        }

        .form-body {
            padding: 22px;
        }

        .readonly-box {
            background: #fff;
            border: 1px solid #d1d1d1;
            border-radius: 20px;
            padding: 16px 18px;
            margin-bottom: 14px;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.04);
        }

        .readonly-box label {
            display: block;
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .readonly-box .value {
            color: #666;
            font-size: 15px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 18px;
            font-weight: 800;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            height: 48px;
            border-radius: 999px;
            border: 1px solid #cfcfcf;
            background: #fff;
            padding: 0 16px;
            font-size: 15px;
            outline: none;
        }

        .form-group textarea {
            min-height: 90px;
            border-radius: 20px;
            padding: 14px 16px;
            resize: vertical;
            font-family: inherit;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .error-box {
            margin-bottom: 16px;
            background: #ffe6e6;
            color: #b00020;
            border: 1px solid #f0b5b5;
            padding: 14px 16px;
            border-radius: 16px;
            font-weight: 700;
        }

        .summary-body {
            padding: 22px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            padding: 12px 0;
            font-size: 18px;
        }

        .summary-row strong {
            font-weight: 800;
        }

        .summary-divider {
            border-top: 1px solid #c9c9c9;
            margin: 10px 0;
        }

        .place-order-btn {
            width: 100%;
            margin-top: 20px;
            height: 48px;
            border-radius: 999px;
            border: none;
            background: #0a9d2d;
            color: #fff;
            font-size: 17px;
            font-weight: 800;
            cursor: pointer;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 14px;
            width: 100%;
            height: 44px;
            border-radius: 999px;
            border: 2px solid #0a9d2d;
            color: #0a9d2d;
            font-weight: 800;
            background: #fff;
        }

        .footer {
            background: #dceee0;
            padding: 50px 24px 80px;
        }

        .footer-inner {
            max-width: 1400px;
            margin: 0 auto;
            border-top: 2px solid #8e8e8e;
            padding-top: 24px;
            text-align: center;
            color: #333;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 18px;
            margin-bottom: 18px;
            font-weight: 700;
        }

        .footer-copy {
            font-size: 14px;
            color: #666;
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
        @media (max-width: 1100px) {
            .content {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 860px) {
            .topbar {
                flex-wrap: wrap;
                padding: 16px;
            }

            .search-box {
                order: 3;
                width: 100%;
                max-width: 100%;
            }

            .hero {
                height: 220px;
            }

            .page-title {
                font-size: 40px;
                margin: 42px 0 28px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

        }
    @media (max-width:768px){

    .desktop-logo,
    .desktop-menu,
    .desktop-search,
    .profile-menu{
        display:none !important;
    }

    .mobile-search{
        display:flex !important;
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

    .menu-btn{
        display:flex !important;
        align-items:center;
        justify-content:center;
    }

    .topbar__inner{
        padding:10px 14px;
        gap:10px;
    }
}
    </style>
</head>
<body>

<?php
$email = $_SESSION['user_email'] ?? null;

$avatar = null;

if($email){

    $userStmt = $conn->prepare("
        SELECT profile_image
        FROM users
        WHERE email = ?
    ");

    $userStmt->bind_param("s",$email);
    $userStmt->execute();

    $userData = $userStmt->get_result()->fetch_assoc();

    if($userData){
        $avatar = $userData['profile_image'];
    }

    $userStmt->close();
}
?>

<header class="topbar">
  <div class="topbar__inner">

    <div class="left-group">

      <!-- MOBILE HAMBURGER -->
      <button class="menu-btn" id="menuBtn">☰</button>

      <!-- LOGO -->
      <a class="brand desktop-logo" href="home.php">
        <img src="assets/images/logo_ok.png" alt="FreshFast">
      </a>

      <!-- DESKTOP DROPDOWN MENU -->
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

    <!-- Desktop Search -->
    <form class="desktop-search" action="search.php" method="GET">
    <input type="text" name="q" placeholder="ค้นหาสินค้า / ร้านค้า">
    </form>

    <!-- Mobile Search -->
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
<img class="avatar" 
src="<?= htmlspecialchars($avatar ?: 'assets/images/default-avatar.png') ?>">
    </button>

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

<div class="mobile-overlay" id="mobileOverlay"></div>

    <h1 class="page-title">ที่อยู่ในการจัดส่ง</h1>

    <section class="main-wrap">
        <div class="content">

            <div class="card">
                <div class="card-header-yellow">ข้อมูลที่อยู่</div>

                <form action="place_order.php" method="post" class="form-body">
                    <?php if (!empty($error)): ?>
                        <div class="error-box"><?= e($error) ?></div>
                    <?php endif; ?>


                        <div class="form-group">
                            <label>ชื่อผู้รับ*</label>
                            <input 
                                type="text"
                                name="receiver_name"
                                value="<?= e($receiver_name) ?>"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="delivery_address">ที่อยู่*</label>

                            <!-- ช่องค้นหาสถานที่ -->
                            <input
                                type="text"
                                id="addressSearch"
                                placeholder="ค้นหาที่อยู่ / สถานที่"
                                class="map-search"
                            >

                            <!-- แผนที่ -->
                            <div id="map" style="width:100%;height:320px;border-radius:18px;margin-top:14px;"></div>

                            <!-- แสดงที่อยู่ที่เลือก -->
                            <input 
                                type="text"
                                id="delivery_address"
                                name="delivery_address"
                                placeholder="ที่อยู่จัดส่ง"
                                readonly
                                required
                                value="<?= e($delivery_address) ?>"
                                style="margin-top:14px;"
                            >

                            <!-- hidden lat/lng -->
                            <input type="hidden" name="lat" id="lat">
                            <input type="hidden" name="lng" id="lng">
                        </div>

                    <div class="form-row">
                        
                        <div class="form-group">
                            <label for="postal_code">รหัสไปรษณีย์</label>
                            <input 
                                type="text" 
                                id="postal_code" 
                                name="postal_code" 
                                maxlength="5"
                                value="63110"
                                readonly
                            >
                        </div>

                        <div class="form-group">
                            <label for="phone">เบอร์โทรศัพท์*</label>
                            <input 
                                type="text" 
                                id="phone" 
                                name="phone" 
                                maxlength="20"
                                placeholder="เช่น 08XX-XXX-XXX"
                                value="<?= e($phone) ?>"
                                required
                            >
                        </div>
                    </div>

                        <div class="form-group">
                            <label for="note">หมายเหตุ(ถ้ามี)</label>
                            <textarea 
                                id="note" 
                                name="note" 
                                placeholder="เช่น ส่งหน้าร้านก่อน10โมงหรือโทรก่อนถึง"><?= e($note) ?></textarea>
                        </div>
                        
            </div>

                <div class="card">
                    <div class="card-header-green">ข้อมูลการชำระเงิน</div>
                    <div class="summary-body">
                        <div class="summary-row">
                            <span>รายการสั่งซื้อรวม</span>
                            <strong><?= (int)$totalItems ?> รายการ</strong>
                        </div>

                        <div class="summary-row">
                            <span>ราคา</span>
                            <strong><?= number_format($subtotal, 2) ?> บ.</strong>
                        </div>

                        <div class="summary-row">
                            <span>ค่าส่ง</span>
                            <strong><?= number_format($deliveryFee, 2) ?> บ.</strong>
                        </div>

                        <div class="summary-row">
                            <span>ส่วนลด</span>
                            <strong>-<?= number_format($discount, 2) ?> บ.</strong>
                        </div>

                        <div class="summary-divider"></div>

                        <div class="summary-row">
                            <span><strong>รวมทั้งหมด</strong></span>
                            <strong><?= number_format($grandTotal, 2) ?> บ.</strong>
                        </div>
                        <button type="submit" class="place-order-btn">สั่งซื้อสินค้า</button>
                        <a href="cart.php" class="back-btn">กลับไปยังตะกร้า</a>
                    </div>
                </div>
        </form>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-inner">
            <div class="footer-links">
                <a href="#">คำถามที่พบบ่อย</a>
                <a href="#">ติดต่อเรา</a>
                <a href="#">ประกาศความเป็นส่วนตัว</a>
                <a href="#">สำหรับลูกค้า</a>
                <a href="#">นโยบายการใช้คุกกี้</a>
                <a href="#">ลงทะเบียน</a>
            </div>
            <div class="footer-copy">
                ลิขสิทธิ์ © 2025-2026 FreshFast สงวนลิขสิทธิ์
            </div>
        </div>
    </footer>

<script>
const menuBtn = document.getElementById("menuBtn");
const mobileMenu = document.getElementById("mobileMenu");
const overlay = document.getElementById("mobileOverlay");

if (menuBtn && mobileMenu && overlay) {
    menuBtn.addEventListener("click", () => {
        mobileMenu.classList.add("show");
        overlay.classList.add("show");
    });

    overlay.addEventListener("click", () => {
        mobileMenu.classList.remove("show");
        overlay.classList.remove("show");
    });
}

const desktopMenuBtn = document.getElementById("desktopMenuBtn");
const desktopMenuDropdown = document.getElementById("desktopMenuDropdown");

if (desktopMenuBtn && desktopMenuDropdown) {
    desktopMenuBtn.addEventListener("click", function(e){
        e.stopPropagation();
        desktopMenuDropdown.classList.toggle("show");
    });

    document.addEventListener("click", function(){
        desktopMenuDropdown.classList.remove("show");
    });
}

const avatarBtn = document.getElementById("avatarBtn");
const profileDropdown = document.getElementById("profileDropdown");

if (avatarBtn && profileDropdown) {
    avatarBtn.addEventListener("click", function(e){
        e.stopPropagation();
        profileDropdown.classList.toggle("show");
    });

    document.addEventListener("click", function(){
        profileDropdown.classList.remove("show");
    });
}

</script>
 <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
const allowedPostalCode = "63110";

const map = L.map('map').setView([16.7163, 98.5733], 14); // แม่สอด

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

const marker = L.marker([16.7163, 98.5733], {
    draggable: true
}).addTo(map);

function reverseGeocode(lat, lng){
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
    .then(res => res.json())
    .then(data => {

        const postcode = data.address.postcode || "";

        if (postcode !== allowedPostalCode){
            alert("ขออภัย ให้บริการเฉพาะพื้นที่ 63110 เท่านั้น");
            document.getElementById("delivery_address").value = "";
            document.getElementById("lat").value = "";
            document.getElementById("lng").value = "";
            return;
        }

        document.getElementById("delivery_address").value = data.display_name;
        document.getElementById("postal_code").value = postcode;
        document.getElementById("lat").value = lat;
        document.getElementById("lng").value = lng;
    });
}

marker.on('dragend', function(e){
    const pos = marker.getLatLng();
    reverseGeocode(pos.lat, pos.lng);
});

map.on('click', function(e){
    marker.setLatLng(e.latlng);
    reverseGeocode(e.latlng.lat, e.latlng.lng);
});

document.getElementById("addressSearch").addEventListener("change", function(){
    const query = this.value;

    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
    .then(res => res.json())
    .then(results => {
        if (!results.length){
            alert("ไม่พบที่อยู่");
            return;
        }

        const place = results[0];
        const lat = parseFloat(place.lat);
        const lng = parseFloat(place.lon);

        map.setView([lat, lng], 16);
        marker.setLatLng([lat, lng]);

        reverseGeocode(lat, lng);
    });
});
</script>
</body>
</html>