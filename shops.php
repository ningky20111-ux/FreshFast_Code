<?php
session_start();
$email = $_SESSION['user_email'] ?? null;

// รูปโปรไฟล์จากอีเมล (Gravatar)
$avatar = "assets/images/default-user.png";
require_once "db.php";
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("DB error");
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* USER */
$email = $_SESSION['user_email'] ?? null;

/* รับ shop id */
$shop_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* ดึงข้อมูลร้าน */
$stmtShop = $conn->prepare("
SELECT *
FROM shops
WHERE shop_id = ?
");

$stmtShop->bind_param("i", $shop_id);
$stmtShop->execute();
$shop = $stmtShop->get_result()->fetch_assoc();
$stmtShop->close();

if (!$shop) {
    die("ไม่พบร้าน");
}
/* เพิ่มสินค้า */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {

    $product_id = (int)$_POST['product_id'];

    if (empty($_SESSION['cart'])) {
        $_SESSION['cart'][$product_id] = 1;
        $_SESSION['cart_success'] = "เพิ่มสินค้าแล้ว";
    } else {

        $cartIds = array_keys($_SESSION['cart']);
        $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
        $types = str_repeat('i', count($cartIds));

        $stmt = $conn->prepare("
            SELECT DISTINCT shop_id FROM products
            WHERE product_id IN ($placeholders)
        ");
        $stmt->bind_param($types, ...$cartIds);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing['shop_id'] != $shop_id) {
            $_SESSION['cart_error'] = "ไม่สามารถเพิ่มสินค้าจากคนละร้านได้";
        } else {
            $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + 1;
            $_SESSION['cart_success'] = "เพิ่มสินค้าแล้ว";
        }
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

/* ดึงสินค้า */
$stmt = $conn->prepare("
SELECT *
FROM products
WHERE shop_id = ?
ORDER BY product_id DESC
");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($shop['shop_name']) ?> | FreshFast</title>

<!-- ✅ โหลด CSS กลาง -->
<link rel="stylesheet" href="assets/css/layout.css">
<link rel="stylesheet" href="assets/css/header.css?v=2">
<style>
body{margin:0;background:#eef7f0;font-family:Arial;}

.page-wrap{
  max-width:1200px;
  margin:0 auto;
  padding:30px 20px;
}

.hero-image{
  width:30%;
  height:250px;
  object-fit:cover;
  border-radius:24px;
  margin:0 auto 20px;   /* อยู่ตรงกลาง */
  display:block;        /* สำคัญ */
}

.shop-title{
  text-align:center;
  font-size:40px;
  font-weight:800;
}

.shop-sub{
  text-align:center;
  color:#777;
  font-size:20px;
  font-weight:500;
  margin:10px 0 30px;
}
.product-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(200px,1fr));
  gap:24px;
}

.product-card{
  background:#fff;
  border-radius:20px;
  padding:14px;
  box-shadow:0 8px 20px rgba(0,0,0,.06);
}

.product-img{
  width:100%;
  aspect-ratio:1/1;
  border-radius:16px;
  overflow:hidden;

  border:1px solid #e0e0e0;  /* 👈 กรอบเทา */
}

.product-img img{
  width:100%;
  height:100%;
  object-fit:cover;
}

.product-name{
  font-weight:700;
  margin-top:10px;
}

.product-bottom{
  display:flex;
  justify-content:space-between;
  margin-top:10px;
}

.product-price{
  font-weight:700;
}

.plus-btn{
  width:36px;
  height:36px;
  border:none;
  border-radius:50%;
  background:#ffd400;
  font-size:20px;
}

/* toast */
.toast-success,
.toast-error{
  position:fixed;
  top:50%;
  left:50%;
  transform:translate(-50%,-50%);
  padding:14px 20px;
  border-radius:16px;
  color:#fff;
  font-weight:600;
  z-index:9999;
}

.toast-success{background:#2e7d32;}
.toast-error{background:#c62828;}

.toast-success.hide,
.toast-error.hide{
  opacity:0;
  transform:translate(-50%, -55%) scale(.95);
  transition:.3s;
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
.desktop-menu-dropdown{
  max-height: 600px;
  overflow-y: auto;
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
  max-width:none;

  display:flex;              /* 👈 สำคัญ */
  align-items:center;        /* 👈 จัดให้อยู่กลางแนวตั้ง */
  height:46px;
  border-radius:999px;
  border:1px solid #ddd;
  padding:0 14px;
  background:#fff;
}
.desktop-search{
  flex:1;
  max-width:520px;
}
.desktop-search input{
  width:50%;
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
.topbar__inner{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
}

.left-group{
    display:flex;
    align-items:center;
    gap:10px;
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
.login-btn{
  padding:8px 14px;
  border-radius:999px;
  background:green;
  color:white;
  font-weight:700;
  text-decoration:none;
  font-size:14px;
  transition:.2s;
}

.login-btn:hover{
  background:green;
  transform:scale(1.03);
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
.cards{
    display:flex;
    gap:20px;
    overflow-x:auto;
    scroll-behavior:smooth;
    scrollbar-width:none;
}

.cards::-webkit-scrollbar{
    display:none;
}
.shop-status{
    margin-top:8px;
    text-align:center;

    color:#d32f2f;
    font-weight:700;
    font-size:15px;
}
.shop-title{
    text-align:center;
    font-size:40px;
    font-weight:800;
    margin-bottom:10px;
}

.shop-sub{
    text-align:center;
    color:#777;
    font-size:20px;
    font-weight:500;
    margin:0 0 20px;
}

.shop-status{
    margin-top:10px;
    margin-bottom:40px; /* เพิ่มระยะก่อนถึงสินค้า */
    text-align:center;

    color:#d32f2f;
    font-weight:700;
    font-size:15px;
    line-height:1.8; /* เพิ่มระยะระหว่างบรรทัด */
}

.shop-time{
    display:inline-block;
    margin-top:8px;
    font-size:14px;
    color:#555;
    font-weight:500;
}
/* ================= MOBILE MENU ================= */

@media(max-width:768px){

    .desktop-menu{
        display:none;
    }

    .desktop-search{
        display:none;
    }

    .desktop-logo{
        display:none;
    }

    .profile-menu{
        display:none;
    }

    .menu-btn{
        display:flex !important;
        align-items:center;
        justify-content:center;
        width:42px;
        height:42px;
        flex-shrink:0;
    }

    .mobile-search{
        display:flex;
        align-items:center;
        flex:1;
        height:44px;

        background:#f3f3f3;
        border-radius:999px;

        padding:0 14px;
        border:none; /* สำคัญ */
    }

    .mobile-search input{
        flex:1;
        border:none;
        outline:none;
        background:transparent; /* สำคัญมาก */
        font-size:14px;
        padding:0 10px;
    }

    .topbar__inner{
        display:flex;
        align-items:center;
        gap:10px;
    }

}
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
.login-btn{
  display:flex;
  align-items:center;
  gap:6px;
  padding:8px 12px;
  border-radius:999px;
  background:green;
  color:white;
  text-decoration:none;
  font-weight:700;
  transition:.2s;
}

.login-btn .icon{
  width:20px;
  height:20px;
  stroke:#fff;
  fill:none;
  stroke-width:2;
}

/* desktop = show text */
.login-text{
  display:inline;
}

/* hover */
.login-btn:hover{
  background:#333;
}

.shop-time{
    display:inline-block;
    margin-top:6px;
    font-size:14px;
    color:#555;
    font-weight:500;
}

@media(max-width:768px){

  .page-wrap{
    padding:20px 14px;
  }

  .hero-image{
    height:160px;
    border-radius:18px;
  }

  .shop-title{
    font-size:26px;
  }

  .product-grid{
    grid-template-columns:repeat(2,1fr);
    gap:12px;
  }


  .product-card{
    padding:10px;
    border-radius:16px;
  }


  .product-img{
    border-radius:12px;
  }


  .product-name{
    font-size:14px;
    margin-top:8px;
  }


  .product-price{
    font-size:14px;
  }


  .plus-btn{
    width:30px;
    height:30px;
    font-size:18px;
  }

}
</style>

</head>

<body>

<!-- ✅ เรียก header -->

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

        <?php if ($email): ?>
          <a href="account.php" class="menu-item">บัญชีของฉัน</a>
          <a href="orders.php" class="menu-item">คำสั่งซื้อของฉัน</a>
          <a href="favorites.php" class="menu-item">รายการโปรด</a>
          <a href="cart.php" class="menu-item">ตะกร้าสินค้า</a>
          <a href="logout.php" class="menu-item">ออกจากระบบ</a>
        <?php else: ?>
          <a href="login.php" class="menu-item">เข้าสู่ระบบ</a>
        <?php endif; ?>
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

      <?php if ($email): ?>
        <div class="profile-menu">
          <button class="avatar-btn" id="avatarBtn">
            <img class="avatar" src="<?= htmlspecialchars($avatar) ?>">
          </button>

          <div class="dropdown" id="profileDropdown">
            <a href="account.php" class="dropdown-item">บัญชีของฉัน</a>
            <div class="dropdown-sep"></div>
            <a href="logout.php" class="dropdown-item">ออกจากระบบ</a>
          </div>
        </div>

      <?php else: ?>
        <a href="login.php" class="login-btn" aria-label="เข้าสู่ระบบ">
          <svg viewBox="0 0 24 24" class="icon">
            <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5z"/>
            <path d="M4 22c0-4 3.6-7 8-7s8 3 8 7"/>
          </svg>
          <span class="login-text">เข้าสู่ระบบ</span>
        </a>
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


  <div class="menu-title">ผู้ใช้งาน</div>

  <?php if ($email): ?>
    <a href="account.php" class="menu-item">บัญชีของฉัน</a>
    <a href="orders.php" class="menu-item">คำสั่งซื้อของฉัน</a>
    <a href="favorites.php" class="menu-item">รายการโปรด</a>
    <a href="cart.php" class="menu-item">ตะกร้าสินค้า</a>
    <a href="logout.php" class="menu-item">ออกจากระบบ</a>
  <?php else: ?>
    <a href="login.php" class="menu-item">เข้าสู่ระบบ</a>
  <?php endif; ?>
</div>

<div class="mobile-overlay" id="mobileOverlay"></div>

<div class="page-wrap">

  <img src="<?= htmlspecialchars($shop['shop_image'] ?: 'assets/images/pic.jpg') ?>" class="hero-image">

<h1 class="shop-title">
    <?= htmlspecialchars($shop['shop_name']) ?>
</h1>
<h2 class="shop-sub">
    <?= !empty($shop['description']) 
        ? htmlspecialchars($shop['description']) 
        : 'ยังไม่มีคำบรรยาย'
    ?>
</h2>

<?php 
date_default_timezone_set('Asia/Bangkok');

$currentTime = date("H:i");

$openTime = date("H:i", strtotime($shop['opening_time']));
$closeTime = date("H:i", strtotime($shop['closing_time']));

$isOpen = (
    $shop['status'] == 'open' &&
    $currentTime >= $openTime &&
    $currentTime <= $closeTime
);
// คำนวณเวลาที่เหลือก่อนร้านปิด
$closingDateTime = strtotime(date("Y-m-d") . ' ' . $shop['closing_time']);
$currentDateTime = strtotime(date("Y-m-d H:i:s"));

$minutesLeft = floor(($closingDateTime - $currentDateTime) / 60);


?>
<?php
date_default_timezone_set('Asia/Bangkok');

$currentDateTime = strtotime(date("Y-m-d H:i:s"));
$closeDateTime = strtotime(date("Y-m-d") . ' ' . $shop['closing_time']);

$minutesLeft = floor(($closeDateTime - $currentDateTime) / 60);

$isOpen = (
    $shop['status'] == 'open' &&
    $currentDateTime >= strtotime(date("Y-m-d") . ' ' . $shop['opening_time']) &&
    $currentDateTime <= $closeDateTime
);
?>


<?php if($isOpen && $minutesLeft <= 60 && $minutesLeft > 0): ?>

<div class="shop-status" style="color:#e67e22;">
    ร้านจะปิดในอีก <?= $minutesLeft ?> นาที
</div>


<?php elseif(!$isOpen): ?>

<div class="shop-status">
    ร้านค้าปิด

    <?php if(!empty($shop['opening_time']) && !empty($shop['closing_time'])): ?>
        <div class="shop-time">
            เปิดให้บริการเวลา 
            <?= date("H:i", strtotime($shop['opening_time'])) ?>
            -
            <?= date("H:i", strtotime($shop['closing_time'])) ?>
            น.
        </div>
    <?php endif; ?>

</div>

<?php endif; ?>

  <!-- toast -->
  <?php if(!empty($_SESSION['cart_success'])): ?>
    <div class="toast-success"><?= $_SESSION['cart_success']; unset($_SESSION['cart_success']); ?></div>
  <?php endif; ?>

  <?php if(!empty($_SESSION['cart_error'])): ?>
    <div class="toast-error"><?= $_SESSION['cart_error']; unset($_SESSION['cart_error']); ?></div>
  <?php endif; ?>

  <div class="product-grid">
    <?php while($row = $result->fetch_assoc()): ?>
      <div class="product-card">

        <div class="product-img">
          <!-- <img src="<?= htmlspecialchars($row['product_image'] ?: 'assets/images/sac.png') ?>"> -->
                  <?php if (!empty($row['product_image'])): ?>
            <img src="<?= htmlspecialchars($row['product_image']) ?>">
          <?php endif; ?>
        </div>

        <div class="product-name">
          <?= htmlspecialchars($row['product_name']) ?>
        </div>

        <div class="product-bottom">
          <div class="product-price">
            <?= number_format($row['price'],2) ?> บ.
          </div>

          <form method="post">
            <input type="hidden" name="product_id" value="<?= $row['product_id'] ?>">
            <button type="submit" name="add_to_cart" class="plus-btn">+</button>
          </form>
        </div>

      </div>
    <?php endwhile; ?>
  </div>

</div>

<script>
const successToast = document.querySelector('.toast-success');
if (successToast) {
  setTimeout(() => {
    successToast.classList.add('hide');
    setTimeout(() => successToast.remove(), 300);
  }, 3000);
}

const errorToast = document.querySelector('.toast-error');
if (errorToast) {
  setTimeout(() => {
    errorToast.classList.add('hide');
    setTimeout(() => errorToast.remove(), 300);
  }, 3000);
}
</script>

<footer class="site-footer">
  <div class="footer-divider"></div>

  <div class="footer-links">
    <a href="#">คำถามที่พบบ่อย</a>
    <a href="#">ติดต่อเรา</a>
    <a href="#">ประกาศความเป็นส่วนตัว</a>
    <a href="#">ข้อกำหนดและเงื่อนไข</a>
  </div>

  <div class="footer-copy">
    ลิขสิทธิ์ © 2025–2026 FreshFast สงวนสิทธิ์.
  </div>
</footer>
<script>
// MOBILE MENU
const menuBtn = document.getElementById("menuBtn");
const mobileMenu = document.getElementById("mobileMenu");
const overlay = document.getElementById("mobileOverlay");

menuBtn?.addEventListener("click", () => {
  mobileMenu.classList.add("show");
  overlay.classList.add("show");
});

overlay?.addEventListener("click", () => {
  mobileMenu.classList.remove("show");
  overlay.classList.remove("show");
});

document.querySelectorAll("#mobileMenu a").forEach(link => {
  link.addEventListener("click", () => {
    mobileMenu.classList.remove("show");
    overlay.classList.remove("show");
  });
});

// DESKTOP DROPDOWN
const desktopMenuBtn = document.getElementById("desktopMenuBtn");
const desktopDropdown = document.getElementById("desktopMenuDropdown");

desktopMenuBtn?.addEventListener("click", (e) => {
  e.stopPropagation();
  desktopDropdown.classList.toggle("show");
});

desktopDropdown?.addEventListener("click", (e) => {
  e.stopPropagation();
});

document.addEventListener("click", () => {
  desktopDropdown?.classList.remove("show");
});

// PROFILE DROPDOWN
const avatarBtn = document.getElementById("avatarBtn");
const profileDropdown = document.getElementById("profileDropdown");

avatarBtn?.addEventListener("click", (e) => {
  e.stopPropagation();
  profileDropdown.classList.toggle("show");
});

profileDropdown?.addEventListener("click", (e) => {
  e.stopPropagation();
});

document.addEventListener("click", () => {
  profileDropdown?.classList.remove("show");
});
</script>
</body>
</html>