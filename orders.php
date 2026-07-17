<?php
session_start();
require_once "db.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) && !isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| USER + AVATAR
|--------------------------------------------------------------------------
*/
$email = $_SESSION['user_email'] ?? null;

$avatar = $email
  ? "https://www.gravatar.com/avatar/" . md5(strtolower(trim($email))) . "?d=identicon&s=96"
  : null;

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| หา user_id
|--------------------------------------------------------------------------
*/
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId <= 0 && !empty($_SESSION['user_email'])) {

    $email = $_SESSION['user_email'];

    $userStmt = $conn->prepare("
        SELECT user_id
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    if ($userStmt) {

        $userStmt->bind_param("s", $email);
        $userStmt->execute();

        $userRow = $userStmt->get_result()->fetch_assoc();

        if ($userRow) {
            $userId = (int)$userRow['user_id'];
            $_SESSION['user_id'] = $userId;
        }
    }
}

/*
|--------------------------------------------------------------------------
| ดึง orders
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT *
    FROM orders
    WHERE user_id = ?
    ORDER BY order_id DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Error orders: " . $conn->error);
}

$stmt->bind_param("i", $userId);
$stmt->execute();

$orders = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<title>คำสั่งซื้อของฉัน | FreshFast</title>

<style>
*{
    box-sizing:border-box;
}

body{
    margin:0;
    background:#edf8f1;
    font-family:Arial, Helvetica, sans-serif;
    color:#111;
}

a{
    text-decoration:none;
    color:inherit;
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
  display:flex;
  align-items:center;
  height:46px;
  border-radius:999px;
  border:1px solid #ddd;
  padding:0 14px;
  background:#fff;
}

.desktop-search input{
  width:100%;
  height:40px;
  border:none;
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

.logo-item{
  margin-bottom:18px;
}

.menu-title{
  font-size:13px;
  font-weight:700;
  color:#888;
  margin:14px 0 8px;
}

.menu-item{
  display:block;
  text-decoration:none;
  color:#111;
  padding:12px;
  border-radius:10px;
  font-weight:500;
}

.menu-item:hover{
  background:#f6f6f6;
}

/* ================= PAGE ================= */

.hero{
  text-align:center;
  padding:40px 20px;
  background:#f2f7f3;
}

.hero h1{
  font-size:34px;
  margin:0;
}

.hero p{
  margin-top:6px;
  font-weight:700;
  color:#555;
}

.panel{
    display:flex;
    gap:24px;
    align-items:flex-start;
}


.content{
    flex:1;
}


.side{
    width:200px;
    flex-shrink:0;
}


.content-title{
    font-size:18px;
    font-weight:900;
    margin-bottom:24px;
}


.info{
    line-height:1.8;
    font-weight:700;
    margin-bottom:24px;
}


.empty{
    background:#fff;
    border-radius:20px;
    padding:40px;
    text-align:center;
    font-weight:800;
    box-shadow:0 4px 12px rgba(0,0,0,.08);
}
.panel{
  display:flex;
  gap:24px;
  max-width:1200px;
  margin:40px auto;
  padding:0 24px;
}

/* sidebar */
.side{
  width:200px;
  display:flex;
  flex-direction:column;
  gap:10px;
}

.navbtn{
  padding:10px 14px;
  border-radius:12px;
  border:1px solid #ddd;
  background:#fff;
  font-weight:700;
  cursor:pointer;
}

.navbtn.active{
  background:#009b39;
  color:#fff;
  border-color:#009b39;
}

/* content */
.content{
  flex:1;
}

/* ORDER CARD (ใหม่) */
.order-card{
  background:#fff;
  border-radius:20px;
  box-shadow:0 8px 24px rgba(0,0,0,.06);
  overflow:hidden;
  margin-bottom:20px;
  border:1px solid #eee;
}

/* header ให้ดู soft เหมือน account */
.order-head{
  display:grid;
  grid-template-columns:1fr 1fr 1fr;
  gap:10px;
  padding:18px 22px;
  background:#f7faf8;
  font-weight:800;
  font-size:14px;
  color:#333;
  border-bottom:1px solid #eee;
}

/* body */
.order-body{
  padding:18px 22px;
}

.info{
  font-weight:600;
  line-height:1.7;
  color:#333;
  margin-bottom:16px;
}

.shop-name{
  font-weight:800;
  margin:18px 0 10px;
}

/* item row ให้ดู clean */
.item-row{
  display:grid;
  grid-template-columns:1fr 120px 80px 120px;
  gap:10px;
  padding:10px 0;
  border-top:1px solid #f0f0f0;
  font-size:14px;
}

/* buttons */
.order-actions{
display:flex;
gap:12px;
margin-top:22px;
}

.btn{

height:42px;

padding:0 22px;

border-radius:999px;

font-weight:800;

}


.green{

background:#2ECC71;

}


.red{

background:#ff6b6b;

}
.debug{
    background:#fff3cd;
    border:1px solid #ffd966;
    border-radius:12px;
    padding:12px 16px;
    margin-bottom:18px;
    font-size:13px;
    font-weight:700;
}

/* ================= MODAL ================= */

.modal{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.35);
    display:none;
    justify-content:center;
    align-items:center;
    z-index:9999;
}


.icon-red,
.icon-green{
    width:90px;
    height:90px;
    border-radius:50%;
    margin:0 auto 25px;
    display:flex;
    justify-content:center;
    align-items:center;
    font-size:42px;
    color:#fff;
}

.icon-red{
    background:#ff0000;
}

.icon-green{
    background:#009b39;
}

.modal-box p{
    font-size:28px;
    margin-bottom:40px;
}

textarea{
    width:100%;
    height:90px;
    border-radius:30px;
    border:2px solid #333;
    padding:20px;
    font-size:22px;
    resize:none;
}

.modal-actions{
    display:flex;
    justify-content:center;
    gap:20px;
    margin-top:40px;
}

.cancel-btn,
.submit-btn{
    border:none;
    border-radius:999px;
    padding:14px 34px;
    font-size:24px;
    font-weight:800;
    color:#fff;
    cursor:pointer;
}

.cancel-btn{
    background:#009b39;
}

.submit-btn{
    background:#009b39;
}

.modal-box{

width:92%;
max-width:520px;

background:#fff;

border-radius:24px;

padding:32px;

box-shadow:0 20px 60px rgba(0,0,0,.15);

}
/* ================= RESPONSIVE ================= */
@media(max-width:768px){

.panel{
    flex-direction:column;
}


.side{
    width:100%;
    display:flex;
    overflow-x:auto;
}


.navbtn{
    white-space:nowrap;
}


.content{
    width:100%;
}


.order-card{
    width:100%;
}


.order-head{
    grid-template-columns:1fr;
    gap:15px;
}


.product-item{
    flex-direction:column;
    gap:8px;
}



.actions .btn{
    width:100%;
    text-align:center;
}

}

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

  .order-body,
  .order-head{
    padding:18px;
  }


.modal-box h2{
font-size:22px;
}


.modal-box p{
font-size:14px;
}

  textarea{
    font-size:18px;
  }

  .cancel-btn,
  .submit-btn{
    font-size:18px;
  }
}

</style>
</head>

<body>

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
      </div>

    </div>

    <!-- Desktop Search -->
    <form class="desktop-search" action="search.php" method="GET">

      <span class="search__icon">
        <svg viewBox="0 0 24 24" class="search-svg" fill="none" stroke="currentColor"
          stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="7"></circle>
          <line x1="20" y1="20" x2="16.65" y2="16.65"></line>
        </svg>
      </span>

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

    <div class="order-actions">

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

      <div class="profile-menu">

        <button class="avatar-btn" id="avatarBtn">
          <img class="avatar" src="<?= htmlspecialchars($avatar) ?>">
        </button>

        <div class="dropdown" id="profileDropdown">

          <a href="account.php" class="dropdown-item">
            บัญชีของฉัน
          </a>

          <div class="dropdown-sep"></div>

          <a href="logout.php" class="dropdown-item">
            ออกจากระบบ
          </a>

        </div>
      </div>

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

  <div class="menu-title">ผู้ใช้งาน</div>

  <a href="account.php" class="menu-item">บัญชีของฉัน</a>
  <a href="orders.php" class="menu-item">คำสั่งซื้อของฉัน</a>
  <a href="favorites.php" class="menu-item">รายการโปรด</a>
  <a href="cart.php" class="menu-item">ตะกร้าสินค้า</a>
  <a href="logout.php" class="menu-item">ออกจากระบบ</a>

</div>

<div class="mobile-overlay" id="mobileOverlay"></div>

<section class="page-header">

<h1>คำสั่งซื้อของฉัน</h1>

<div class="tabs">

</div>

</section>

<!-- ตรงส่วน orders ใช้ของเดิมต่อได้เลย -->
<main class="wrap">
    
  <div class="panel">
      <div class="side">

      <button class="navbtn" onclick="location.href='account.php'">
      แก้ไขโปรไฟล์
      </button>
      <button class="navbtn active">
      คำสั่งซื้อของฉัน
      </button>

      <button class="navbtn">
      เปลี่ยนรหัส
      </button>

      </div>
    <section>

        <!-- <div class="debug">
            user_id ปัจจุบัน: <?= e($userId) ?>
        </div> -->

        <div class="content-title">
            <?= $orders ? (int)$orders->num_rows : 0 ?> คำสั่งซื้อ
        </div>

        <?php if ($orders && $orders->num_rows > 0): ?>

            <?php while ($order = $orders->fetch_assoc()): ?>

                <?php
                    $orderId = (int)$order['order_id'];

                    $total = $order['total_amount']
                        ?? $order['total_price']
                        ?? $order['grand_total']
                        ?? 0;

                    $date = $order['created_at']
                        ?? $order['order_date']
                        ?? '';

                    $status = $order['status']
                        ?? $order['order_status']
                        ?? '-';

                    $receiverName = $order['receiver_name']
                        ?? $order['customer_name']
                        ?? $order['name']
                        ?? '-';

                    $phone = $order['phone']
                        ?? $order['receiver_phone']
                        ?? $order['customer_phone']
                        ?? '-';

                    $address = $order['address']
                        ?? $order['delivery_address']
                        ?? $order['shipping_address']
                        ?? '-';

                    $note = $order['note']
                        ?? $order['order_note']
                        ?? '-';

                    /*
                    |--------------------------------------------------------------------------
                    | ดึงรายการสินค้า
                    |--------------------------------------------------------------------------
                    */
                    $itemSql = "
                        SELECT *
                        FROM order_items
                        WHERE order_id = ?
                    ";

                    $itemStmt = $conn->prepare($itemSql);
                    $items = null;

                    if ($itemStmt) {

                        $itemStmt->bind_param("i", $orderId);
                        $itemStmt->execute();

                        $items = $itemStmt->get_result();
                    }
                ?>

                <div class="order-card">

                    <div class="order-head">

                        <div>
                            หมายเลขคำสั่งซื้อ<br>
                            #<?= e($orderId) ?>
                        </div>

                        <div>
                            ราคารวม<br>
                            <?= number_format((float)$total, 2) ?> บาท
                        </div>

                        <div>
                            วันที่ทำการสั่งซื้อ<br>

                            <?= $date ? e(date("d/m/Y", strtotime($date))) : '-' ?>

                            <br>

                            สถานะ: <?= e($status) ?>
                        </div>

                    </div>

                    <div class="order-body">

                        <div class="info">
                            ชื่อผู้รับ <?= e($receiverName) ?><br>
                            เบอร์โทรศัพท์ <?= e($phone) ?><br>
                            ที่อยู่ <?= e($address) ?><br>
                            หมายเหตุ <?= e($note) ?>
                        </div>

                        <div class="shop-name">
                            รายการสินค้า
                        </div>

                        <?php if ($items && $items->num_rows > 0): ?>

                            <?php while ($item = $items->fetch_assoc()): ?>

                                <?php
                                    $qty = (int)($item['quantity'] ?? $item['qty'] ?? 1);

                                    $price = $item['unit_price']
                                        ?? $item['price']
                                        ?? 0;

                                    $subtotal = $item['line_total']
                                        ?? $item['subtotal']
                                        ?? ((float)$price * $qty);

                                    $productName = $item['product_name']
                                        ?? ("สินค้า #" . ($item['product_id'] ?? '-'));
                                ?>
                                <div class="product-item">


                                <div>

                                <b>
                                <?=e($productName)?>
                                </b>

                                <br>

                                <span>
                                จำนวน x<?=$qty?>
                                </span>


                                </div>


                                <strong>

                                <?=number_format($subtotal,2)?> บาท

                                </strong>


                                </div>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <div class="item-row">
                                <div>ไม่มีรายการสินค้า</div>
                                <div>-</div>
                                <div>-</div>
                                <div>-</div>
                            </div>

                        <?php endif; ?>

            <div class="order-actions">

                <a class="btn green"
                href="order_detail.php?order_id=<?= $orderId ?>">
                    สั่งซื้ออีกครั้ง
                </a>

                <?php if (!empty($order['complaint_status']) && $order['complaint_status'] == 1): ?>

                    <button class="btn red" disabled style="opacity:.5;cursor:not-allowed;">
                        ร้องเรียนแล้ว
                    </button>

                <?php else: ?>

                    <button class="btn red"
                        onclick="openComplaintModal(<?= $orderId ?>)">
                        
                        ร้องเรียน
                    </button>

                <?php endif; ?>

            </div>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">
                ยังไม่มีคำสั่งซื้อ
            </div>

        <?php endif; ?>

    </section>
  </div>
</main>
<!-- Modal -->
<!-- Modal -->
<div id="complaintModal" class="modal">

  <div class="modal-box">

    <!-- FORM -->
    <div id="complaintFormBox">

      <div class="icon-circle red">
        <svg viewBox="0 0 24 24" class="icon-svg">
          <path d="M3 11v2a2 2 0 0 0 2 2h1l5 4V5L6 9H5a2 2 0 0 0-2 2z"/>
          <path d="M16 7a4 4 0 0 1 0 10"/>
        </svg>
      </div>

      <h2>ส่งคำร้องเรียน</h2>

      <p>
        กรุณาระบุรายละเอียดปัญหาที่พบ พร้อมแนบหลักฐาน (ถ้ามี)
        ทีมงาน FreshFast จะตรวจสอบข้อมูลและดำเนินการช่วยเหลือโดยเร็วที่สุด
      </p>

      <textarea id="complaintText"
      placeholder="พิมพ์รายละเอียดคำร้องเรียน..."></textarea>


      <div class="upload-box">

        <label for="complaintImage" class="upload-btn">
          เพิ่มรูปหลักฐาน
        </label>

        <input 
        type="file"
        id="complaintImage"
        accept="image/*"
        hidden>

        <div id="imageName">
          ยังไม่ได้เลือกรูป
        </div>

      </div>


      <div class="modal-actions">

        <button class="btn ghost"
        onclick="closeComplaintModal()">
          ยกเลิก
        </button>


        <button class="btn primary"
        onclick="submitComplaint()">
          ส่งคำร้อง
        </button>

      </div>


    </div>
    <!-- END FORM -->


    <!-- SUCCESS -->
    <div id="complaintSuccessBox"
    style="display:none;"
    class="success-box">


      <div class="success-icon">
        ✓
      </div>


      <h2>
        เราได้รับเรื่องที่คุณแจ้งแล้ว
      </h2>


      <p>
        ทีมงาน FreshFast ได้รับคำร้องเรียนของคุณเรียบร้อยแล้ว
        <br>
        และจะดำเนินการตรวจสอบโดยเร็วที่สุด
      </p>


      <div class="modal-actions">

        <button 
        class="btn primary"
        onclick="location.href='home.php'">

          กลับสู่หน้าหลัก

        </button>

      </div>


    </div>
    <!-- END SUCCESS -->


  </div>

</div>

<style>
.navbtn{
    text-align:center;
}
.modal{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.35);
    display:none;
    justify-content:center;
    align-items:center;
    z-index:9999;
}
.modal{
  position:fixed;
  inset:0;
  display:none;
  justify-content:center;
  align-items:center;
  background:rgba(0,0,0,.45);
  backdrop-filter: blur(6px);
  z-index:9999;
}
.success-box{
    text-align:center;
    padding:20px;
}


.success-icon{

    width:80px;
    height:80px;

    margin:0 auto 20px;

    border-radius:50%;

    background:#e9f9ef;

    color:#16a34a;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:45px;

    font-weight:900;

}


.success-box h2{

    font-size:24px;
    margin-bottom:12px;

}


.success-box p{

    color:#666;
    line-height:1.7;
    font-size:15px;

}

@keyframes pop{
  from{transform:scale(.95); opacity:0;}
  to{transform:scale(1); opacity:1;}
}

.modal-content{
  text-align:center;
}

.icon-circle{
  width:72px;
  height:72px;
  border-radius:50%;
  display:flex;
  justify-content:center;
  align-items:center;
  margin:0 auto 14px;
}

.icon-circle.red{
  background:#ffeded;
  color:#ff3b30;
}

.icon-circle.green{
  background:#e9f9ef;
  color:#16a34a;
}

.icon-svg{
  width:34px;
  height:34px;
  stroke:currentColor;
  fill:none;
  stroke-width:2.5;
  stroke-linecap:round;
  stroke-linejoin:round;
}

.modal h2{
  font-size:22px;
  margin:10px 0 6px;
  font-weight:800;
  color:#111;
}

.modal p{
  font-size:14px;
  color:#666;
  line-height:1.6;
  margin-bottom:16px;
}

textarea:focus{
  border-color:#16a34a;
  box-shadow:0 0 0 3px rgba(22,163,74,.15);
}

.modal-actions{
  display:flex;
  gap:10px;
  margin-top:16px;
  justify-content:center;
}

.btn{
  padding:10px 18px;
  border-radius:999px;
  font-weight:700;
  cursor:pointer;
  border:none;
  font-size:14px;
}

.btn.primary{
  background:#16a34a;
  color:#fff;
}

.btn.primary:hover{
  background:#12813a;
}

.btn.ghost{
  background:#f3f3f3;
  color:#333;
}

.btn.ghost:hover{
  background:#e9e9e9;
}

.icon-red,
.icon-green{
    width:90px;
    height:90px;
    border-radius:50%;
    margin:0 auto 25px;
    display:flex;
    justify-content:center;
    align-items:center;
    font-size:42px;
    color:#fff;
}

.icon-red{
    background:#ff0000;
}

.icon-green{
    background:#009b39;
}

.modal-box h2{
    font-size:24px;
}

.modal-box p{
    font-size:15px;
}

.modal-actions{
    display:flex;
    justify-content:center;
    gap:20px;
    margin-top:40px;
}
/* ===== Complaint Textarea ===== */

#complaintText{

    width:100%;
    height:140px;

    background:#f8faf9;

    border:1.5px solid #d8e8dc;

    border-radius:20px;

    padding:18px 20px;

    font-size:15px;

    font-family:inherit;

    color:#333;

    resize:none;

    outline:none;

    transition:.25s;

}


#complaintText::placeholder{

    color:#999;

}


#complaintText:focus{

    background:#fff;

    border-color:#2ECC71;

    box-shadow:
    0 0 0 4px rgba(46,204,113,.12);

}


/* ===== Upload Box ===== */

.upload-box{

    margin-top:16px;

    background:#f8faf9;

    border:1.5px solid #d8e8dc;

    border-radius:20px;

    padding:16px;

    text-align:center;

}


.upload-btn{

    width:100%;

    display:flex;

    align-items:center;

    justify-content:center;

    gap:8px;

    padding:13px;

    border-radius:14px;

    background:#eaf8ef;

    color:#009b39;

    font-weight:800;

    cursor:pointer;

    border:1px dashed #2ECC71;

    transition:.2s;

}


.upload-btn:hover{

    background:#dff4e6;

}


#imageName{

    margin-top:12px;

    font-size:13px;

    color:#777;

}
.cancel-btn,
.submit-btn{
    border:none;
    border-radius:999px;
    padding:14px 34px;
    font-size:24px;
    font-weight:800;
    color:#fff;
    cursor:pointer;
}

.cancel-btn{
    background:#009b39;
}

.submit-btn{
    background:#009b39;
}


.upload-box{
    margin-top:18px;
    text-align:left;
}

.upload-btn{

    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;

    padding:12px 20px;

    border-radius:999px;

    background:#f1f8f3;

    color:#009b39;

    font-weight:800;

    cursor:pointer;

    border:1px dashed #009b39;

    transition:.2s;
}


.upload-btn:hover{
    background:#e3f5e8;
}


.upload-btn svg{

    width:20px;
    height:20px;

    fill:none;
    stroke:#009b39;

    stroke-width:2;
}


#imageName{

    margin-top:10px;

    font-size:13px;

    color:#777;

    text-align:center;
}

.order-card{

background:#fff;

border-radius:24px;

padding:0;

margin-bottom:20px;

box-shadow:
0 6px 20px rgba(0,0,0,.08);

}

.order-head{

    background:#f8fbf9;

    padding:20px;

}


.order-head div{

    color:#555;

}


.order-body{

    padding:24px;

}


.item-row{

    background:#fafafa;

    border-radius:12px;

    padding:14px;

    margin-top:10px;

    border:none;

}


.order-actions{
display:flex;
gap:12px;
margin-top:22px;
}


.actions .btn{

    padding:12px 22px;

    border-radius:999px;

}


.actions .green{

    background:#009b39;

}


.actions .red{

    background:#ff5757;

}

.page-header{
    max-width:1200px;
    margin:30px auto;
    padding:0 24px;
    text-align:center;
}


.page-header h1{
    font-size:32px;
    font-weight:900;
    margin-bottom:20px;
}


.tabs{

display:flex;
gap:12px;

}


.tabs button{

border:none;
background:white;

padding:10px 22px;

border-radius:999px;

font-weight:700;

cursor:pointer;

box-shadow:
0 3px 10px rgba(0,0,0,.08);

}


.tabs .active{

background:#2ECC71;
color:white;

}
.status{

display:inline-block;

padding:6px 14px;

border-radius:999px;

background:#e8f8ef;

color:#009b39;

font-size:13px;

font-weight:800;

margin-bottom:10px;

}
.product-item{

display:flex;

justify-content:space-between;

padding:14px;

background:#f8faf9;

border-radius:16px;

margin-top:10px;

}


.product-item span{

color:#777;

font-size:14px;

}
#complaintSuccessBox{
    text-align:center;
}

#complaintSuccessBox h2{
    color:#111;
    display:block;
}

#complaintSuccessBox p{
    display:block;
    color:#666;
}
</style>

<script>
let currentOrderId = 0;

function openComplaintModal(orderId){

    currentOrderId = orderId;

    document.getElementById('complaintModal').style.display = 'flex';

    document.getElementById('complaintFormBox').style.display = 'block';
    document.getElementById('complaintSuccessBox').style.display = 'none';

    document.getElementById('complaintText').value = '';
}

function closeComplaintModal(){
    document.getElementById('complaintModal').style.display = 'none';
}

function submitComplaint(){

    const text = document.getElementById('complaintText').value.trim();

    const image =
        document.getElementById('complaintImage').files[0];

    if(text === ''){
        alert('กรุณากรอกคำร้องเรียน');
        return;
    }

    const formData = new FormData();

    formData.append('order_id', currentOrderId);
    formData.append('complaint_text', text);

    // ถ้ามีรูปก็ส่งไปด้วย
    if(image){
        formData.append('complaint_image', image);
    }
    fetch('submit_complaint.php', {
        method:'POST',
        body:formData
    })
    .then(res => res.text())
    .then(result => {

        console.log(result);

        let data = JSON.parse(result);

        if(data.success){
          setTimeout(()=>{

              document.getElementById('complaintFormBox').style.display = 'none';

              document.getElementById('complaintSuccessBox').style.display = 'block';

          },100);

        }else{

            alert(data.message || 'เกิดข้อผิดพลาด');

        }

    })
    .catch(err=>{
        console.log(err);
        alert("ระบบผิดพลาด");
    });
}
document
.getElementById('complaintImage')
.addEventListener('change',function(){

    if(this.files.length){

        document.getElementById('imageName').innerText =
        this.files[0].name;

    }else{

        document.getElementById('imageName').innerText =
        "ยังไม่ได้เลือกรูป";

    }

});
</script>

</body>
</html>