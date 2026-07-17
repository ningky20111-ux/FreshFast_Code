<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function e($str){
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/* ================= USER ================= */
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
/* ================= DEMO ================= */
if (isset($_GET['demo']) && $_GET['demo'] == '1' && empty($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        1 => 2,
        3 => 1,
        17 => 1
    ];
}

/* ================= CART ACTION ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);

    if ($action === 'increase' && $productId > 0) {
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + 1;
    }

    if ($action === 'decrease' && isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]--;
        if ($_SESSION['cart'][$productId] <= 0) {
            unset($_SESSION['cart'][$productId]);
        }
    }

    if ($action === 'remove') {
        unset($_SESSION['cart'][$productId]);
    }

    if ($action === 'clear_cart') {
        $_SESSION['cart'] = [];
    }

    header("Location: cart.php");
    exit;
}

/* ================= LOAD CART ================= */
$cartItems = [];
$subtotal = 0;
$deliveryFee = 0;
$discount = 0;
$totalItems = 0;

if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $sql = "
        SELECT p.*, s.shop_name
        FROM products p
        LEFT JOIN shops s ON p.shop_id=s.shop_id
        WHERE p.product_id IN ($placeholders)
        ORDER BY s.shop_name ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $pid = (int)$row['product_id'];
        $qty = (int)$_SESSION['cart'][$pid];

        $row['quantity'] = $qty;
        $row['line_total'] = $qty * $row['price'];

        $subtotal += $row['line_total'];
        $totalItems += $qty;

        $cartItems[] = $row;
    }
}

$grandTotal = $subtotal + $deliveryFee - $discount;

/* ================= GROUP BY SHOP ================= */
$groupedByShop = [];
foreach ($cartItems as $item) {
    $shop = $item['shop_name'] ?: 'ร้านค้า';
    $groupedByShop[$shop][] = $item;
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ตะกร้าสินค้า | FreshFast</title>

<link rel="stylesheet" href="assets/css/layout.css">

<style>
body{
    margin:0;
    background:#dceee0;
    font-family:Arial,sans-serif;
}
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
    z-index:999;
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
.cart-columns,
.item-row{
    display:grid;
    grid-template-columns:minmax(260px,1.8fr) 120px 120px 120px;
    gap:16px;
    align-items:center;
}
.cart-columns div:not(:first-child),
.item-price,
.item-total,
.qty-box{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;
}

.cart-columns{
    padding:16px 22px;
    background:#f8f8f8;
    border-bottom:1px solid #ececec;
    font-size:14px;
    font-weight:700;
    color:#666;
}

.item-price,
.item-total{
    font-weight:600;
    text-align:center;
}

.qty-box{
    justify-content:center;
}
.card-head-flex{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
}

.clear-cart-btn{
    border:none;
    background:none;
    padding:0;
    margin:0;

    color:#d32f2f;
    font-size:14px;
    font-weight:700;

    text-decoration:underline;
    cursor:pointer;

    transition:.2s;
}

.clear-cart-btn:hover{
    color:#b71c1c;
    transform:none;
    background:none;
}

.cart-page{
    max-width:1400px;
    margin:auto;
    padding:30px 20px 70px;
}
.cart-title{
    text-align:center;
    font-size:46px;
    font-weight:800;
    margin:20px 0 35px;
}
.cart-layout{
    display:grid;
    grid-template-columns:1.6fr .9fr;
    gap:28px;
}
.card{
    background:#fff;
    border-radius:24px;
    overflow:hidden;
    box-shadow:0 10px 24px rgba(0,0,0,.08);
}
.card-head-yellow{
    background:#f3cd1e;
    padding:18px 22px;
    font-weight:800;
}
.card-head-green{
    background:#08a52f;
    color:#fff;
    padding:18px 22px;
    font-weight:800;
}
.shop-row{
    padding:18px 22px;
    font-weight:800;
    border-bottom:1px solid #eee;
}
.item-row{
    display:grid;
    grid-template-columns:1.8fr .7fr .8fr .8fr;
    gap:16px;
    align-items:center;
    padding:22px;
    border-bottom:1px solid #eee;
}
.product-info{
    display:flex;
    gap:14px;
    align-items:center;
}
.product-image{
    width:70px;
    height:70px;
    border-radius:14px;
    overflow:hidden;
    background:#f4f4f4;
}
.product-image img{
    width:100%;
    height:100%;
    object-fit:cover;
}
.qty-box{
    display:flex;
    align-items:center;
    gap:8px;
}
.qty-btn{
    width:30px;
    height:30px;
    border:none;
    border-radius:50%;
    cursor:pointer;
}
.summary-body{
    padding:22px;
}
.summary-row{
    display:flex;
    justify-content:space-between;
    padding:12px 0;
}
.checkout-btn{
    width:100%;
    height:50px;
    border:none;
    border-radius:999px;
    background:#08a52f;
    color:#fff;
    font-weight:800;
    cursor:pointer;
    margin-top:18px;
}
.footer{
    padding:50px 20px;
    text-align:center;
}
.footer-links{
    display:flex;
    flex-wrap:wrap;
    justify-content:center;
    gap:16px;
    margin-bottom:16px;
}
.summary-body{
    width:100%;
    box-sizing:border-box;
}
.shop-row{
    display:flex;
    align-items:center;
    gap:10px;
    padding:18px 22px;
    font-weight:800;
    border-bottom:1px solid #eee;
}

.shop-icon{
    display:flex;
    align-items:center;
    justify-content:center;
    width:32px;
    height:32px;
    border-radius:10px;
    background:#f3f4f6;
    color:#333;
    flex-shrink:0;
}
@media(max-width:980px){
    .desktop-menu{
        display:none !important;
        }
    .cart-layout{
        grid-template-columns:1fr;
    }
}
@media(max-width:768px){
    .desktop-menu{
    display:none !important;
    }
    .cart-columns{
        display:none;
    }

    .item-row{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        padding:14px 16px;
        border-bottom:1px solid #eee;
    }

    .product-info{
        flex:1;
        display:flex;
        align-items:center;
        gap:12px;
        min-width:0;
    }

    .product-image{
        width:56px;
        height:56px;
        border-radius:14px;
        flex-shrink:0;
    }

    .product-info > div{
        min-width:0;
    }

    .product-info strong{
        display:block;
        font-size:14px;
        font-weight:700;
        margin-bottom:4px;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }

    /* ซ่อนราคาคอลัมน์เดิม */
    .item-price{
        display:none !important;
    }

    /* ซ่อน total เดิมบน mobile ถ้าไม่อยากให้ซ้ำ */
    .item-total{
        display:none;
    }

    .qty-box{
        display:flex;
        align-items:center;
        gap:8px;
        background:#f6f6f6;
        padding:6px 10px;
        border-radius:999px;
        flex-shrink:0;
    }

    .qty-btn{
        width:28px;
        height:28px;
        border:none;
        border-radius:50%;
        background:#08a52f;
        color:#fff;
        font-weight:700;
        cursor:pointer;
    }
}
    .checkout-btn{
        width:100%;
        height:50px;
        border:none;
        border-radius:999px;
        background:#08a52f;
        color:#fff;
        font-weight:800;
        cursor:pointer;
        margin-top:18px;

        display:flex;
        align-items:center;
        justify-content:center;
        text-decoration:none;
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

<div class="mobile-overlay" id="mobileOverlay"></div>

<!-- CONTENT -->
<div class="cart-page">

    <h1 class="cart-title">ตะกร้าสินค้า</h1>

    <div class="cart-layout">

        <div class="card">
            <div class="card-head-yellow card-head-flex">
                <span>รายการสินค้า</span>

                <?php if(!empty($groupedByShop)): ?>
                <form method="post" onsubmit="return confirm('ลบสินค้าทั้งหมดออกจากตะกร้า?')">
                    <input type="hidden" name="action" value="clear_cart">
                    <button type="submit" class="clear-cart-btn">
                        ลบทั้งหมด
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <?php if(!empty($groupedByShop)): ?>
                <div class="cart-columns">
                    <div></div>
                    <div>ราคา/ชิ้น</div>
                    <div>จำนวน</div>
                    <div>รวม</div>
                </div>
            <?php endif; ?>

            <?php if(!empty($groupedByShop)): ?>
                <?php foreach($groupedByShop as $shop=>$items): ?>
                    <div class="shop-row">
                        <span class="shop-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none"
                            stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l1.5-5h15L21 9"/>
                        <path d="M5 10v9h14v-9"/>
                        <path d="M9 19v-5h6v5"/>
                        </svg>
                        </span>
                        <?= e($shop) ?>
                    </div>

                    <?php foreach($items as $item): ?>
                    <div class="item-row">

                        <div class="product-info">
                            <div class="product-image">
                                <!-- <img src="<?= e($item['product_image'] ?: 'assets/images/sac.png') ?>"> -->
                            </div>

                            
                                <div class="product-meta">
                                    <strong><?= e($item['product_name']) ?></strong>
                                    <span class="unit-price"><?= number_format($item['price'],2) ?> บ.</span>
                                </div>
                        </div>

                        <div class="item-price">
                            <?= number_format($item['price'],2) ?> บ.
                        </div>

                        <div class="qty-box">
                            <form method="post">
                                <input type="hidden" name="action" value="decrease">
                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                <button class="qty-btn">-</button>
                            </form>

                            <?= $item['quantity'] ?>

                            <form method="post">
                                <input type="hidden" name="action" value="increase">
                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                <button class="qty-btn">+</button>
                            </form>
                        </div>

                        <div class="item-total">
                            <?= number_format($item['line_total'],2) ?> บ.
                        </div>

                    </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="padding:40px;text-align:center;">ยังไม่มีสินค้าในตะกร้า</div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-head-green">ข้อมูลการชำระเงิน</div>

            <div class="summary-body">
                <div class="summary-row">
                    <span>สินค้า</span>
                    <strong><?= $totalItems ?></strong>
                </div>

                <div class="summary-row">
                    <span>ราคา</span>
                    <strong><?= number_format($subtotal,2) ?></strong>
                </div>

                <div class="summary-row">
                    <span>ค่าส่ง</span>
                    <strong><?= number_format($deliveryFee,2) ?></strong>
                </div>

                <div class="summary-row">
                    <span>รวมทั้งหมด</span>
                    <strong><?= number_format($grandTotal,2) ?></strong>
                </div>

                <a href="checkout.php" class="checkout-btn">สั่งซื้อสินค้า</a>
            </div>
        </div>

    </div>
</div>

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
/* MOBILE MENU */
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


/* DESKTOP DROPDOWN MENU */
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


/* PROFILE DROPDOWN */
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

</body>
</html>