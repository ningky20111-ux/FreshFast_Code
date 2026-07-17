<?php
session_start();

require_once "db.php";
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("DB error");
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

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

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$successMessage = "";

/* เพิ่มสินค้า */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {

    $product_id = (int)($_POST['product_id'] ?? 0);

    if ($product_id > 0) {

        // ดึง shop_id ของสินค้าที่กด
        $stmtCheck = $conn->prepare("
            SELECT shop_id 
            FROM products 
            WHERE product_id = ?
        ");
        $stmtCheck->bind_param("i", $product_id);
        $stmtCheck->execute();
        $newProduct = $stmtCheck->get_result()->fetch_assoc();
        $stmtCheck->close();

        $newShopId = $newProduct['shop_id'] ?? 0;

        // ✅ ถ้าตะกร้าว่าง → เพิ่มได้เลย
        if (empty($_SESSION['cart'])) {

            $_SESSION['cart'][$product_id] = 1;
            $_SESSION['cart_success'] = "เพิ่มสินค้าลงตะกร้าแล้ว";

        } else {

            // ✅ เช็คร้านเดิม
            $cartIds = array_keys($_SESSION['cart']);
            $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
            $types = str_repeat('i', count($cartIds));

            $stmtCart = $conn->prepare("
                SELECT DISTINCT shop_id 
                FROM products 
                WHERE product_id IN ($placeholders)
            ");

            $stmtCart->bind_param($types, ...$cartIds);
            $stmtCart->execute();
            $cartShop = $stmtCart->get_result()->fetch_assoc();
            $stmtCart->close();

            $existingShopId = $cartShop['shop_id'] ?? 0;

            // ❌ คนละร้าน
            if ($existingShopId != $newShopId) {

                $_SESSION['cart_error'] = "ไม่สามารถเพิ่มสินค้าได้ เพราะตะกร้ารับได้จากร้านเดียวเท่านั้น";

            } else {

                // ✅ ร้านเดียวกัน เพิ่มได้
                $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + 1;
                $_SESSION['cart_success'] = "เพิ่มสินค้าลงตะกร้าแล้ว";
            }
        }
    }

    // 🔥 สำคัญมาก → reload เพื่อให้ toast แสดง
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

/* ชื่อหมวด */
$stmtCat = $conn->prepare("SELECT category_name FROM categories WHERE category_id=?");
$stmtCat->bind_param("i", $category_id);
$stmtCat->execute();
$category = $stmtCat->get_result()->fetch_assoc();
$stmtCat->close();

/* แปลงไทย */
$thaiNames = [
    'Meat' => 'เนื้อสัตว์',
    'Fruits & Vegetables' => 'ผักผลไม้',
    'Seasoning' => 'เครื่องปรุง',
    'Beverages' => 'เครื่องดื่ม',
    'Frozen' => 'แช่แข็ง',
    'Kitchen Supplies' => 'ของใช้ในครัว',
    'Desserts' => 'ของหวาน'
];

$displayCategoryName = $thaiNames[$category['category_name'] ?? ''] ?? 'หมวดสินค้า';

/* ดึงสินค้า */
$stmt = $conn->prepare("
    SELECT p.*, s.shop_name 
    FROM products p
    JOIN shops s ON p.shop_id = s.shop_id
    WHERE p.category_id=?
    ORDER BY p.product_id DESC
");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

$cartCount = array_sum($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($displayCategoryName) ?> | FreshFast</title>

<link rel="stylesheet" href="assets/css/layout.css">

<style>
body{
    margin:0;
    background:#eef7f0;
    font-family:Arial, Helvetica, sans-serif;
}
.page-wrap{
    max-width:1200px;
    margin:0 auto;
    padding:30px 20px 60px;
}
.hero-image{
    width:100%;
    height:250px;
    object-fit:cover;
    border-radius:24px;
    margin-bottom:30px;
}
.category-title{
    text-align:center;
    font-size:42px;
    font-weight:800;
    margin:0 0 8px;
}
.category-sub{
    text-align:center;
    color:#666;
    margin-bottom:30px;
}
.alert-success{
    background:#e7f8ea;
    color:#127a2f;
    padding:14px;
    border-radius:14px;
    text-align:center;
    margin-bottom:20px;
}
.product-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));
    gap:24px;
}
.product-card{
    background:#fff;
    border-radius:22px;
    padding:14px;
    box-shadow:0 8px 20px rgba(0,0,0,.06);
}
.product-img{
    width:100%;
    aspect-ratio:1/1;
    border-radius:18px;
    overflow:hidden;
    background:#f4f4f4;
}
.product-img img{
    width:100%;
    height:100%;
    object-fit:cover;
}
.product-name{
    font-weight:700;
    margin-top:14px;
    min-height:44px;
}
.product-bottom{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:10px;
}
.product-price{
    font-weight:700;
}
.plus-btn{
    width:38px;
    height:38px;
    border:none;
    border-radius:50%;
    background:#ffd400;
    font-size:22px;
    font-weight:700;
    cursor:pointer;
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

.toast-success,
.toast-error{
  position:fixed;
  top:50%;
  left:50%;
  transform:translate(-50%, -50%);
  background:#c62828;
  color:#fff;
  padding:14px 20px;
  border-radius:18px;
  font-size:15px;
  font-weight:600;
  box-shadow:0 18px 40px rgba(0,0,0,.22);
  z-index:9999;

  max-width:min(90vw, 420px);
  width:max-content;

  text-align:center;
  line-height:1.45;
  word-break:break-word;

  opacity:1;
  transition:all .35s ease;
}

.toast-success{
  background:#1b5e20;
  color:#fff;
}

.toast-error{
  background:#c62828;
  color:#fff;
}

.toast-success.hide,
.toast-error.hide{
  opacity:0;
  transform:translate(-50%, -55%) scale(.95);
}
.product-shop{
    font-size:15px;
    color:#777;
    margin-top:0px;
    font-weight:400;
    line-height:1.2;
}

@media(max-width:768px){
    .desktop-menu{
        display:none;
    }
    .product-shop{
    font-size:15px;
    color:#777;
    margin-top:0px;
    font-weight:400;
    line-height:1.2;
}
}
.empty-box{
    grid-column:1/-1;
    background:#fff;
    padding:30px;
    text-align:center;
    border-radius:18px;
}
@media(max-width:992px){
    .product-grid{grid-template-columns:repeat(3,1fr);}
}
@media(max-width:768px){
    .product-grid{grid-template-columns:repeat(2,1fr);gap:16px;}
    .hero-image{height:180px;}
    .category-title{font-size:30px;}

    .toast-error{
        position:fixed;
        top:50%;
        left:50%;
        transform:translate(-50%, -50%);
        background:#c62828;
        color:#fff;
        padding:16px 26px;
        border-radius:18px;
        font-size:14px;
        font-weight:600;
        box-shadow:0 18px 40px rgba(0,0,0,.22);
        z-index:9999;

        max-width:min(90vw, 420px);
        width:max-content;

        text-align:center;
        line-height:1.45;
        word-break:break-word;
        white-space:normal;

        opacity:1;
        transition:all .35s ease;
        }

        .toast-error.hide{
        opacity:0;
        transform:translate(-50%, -55%) scale(.95);
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
          <div class="menu-title">หมวดหมู่</div>
          <a href="category.php?id=1">เนื้อสัตว์</a>
          <a href="category.php?id=2">ผักผลไม้</a>
          <a href="category.php?id=3">เครื่องปรุง</a>
          <a href="category.php?id=4">เครื่องดื่ม</a>
          <a href="category.php?id=5">แช่แข็ง</a>
          <a href="category.php?id=6">ของใช้ในครัว</a>
          <a href="category.php?id=7">ของหวาน</a>
          <a href="shop_all.php">ร้านค้าทั้งหมด</a>
        <div class="menu-title">ผู้ใช้งาน</div>
         <a href="account.php">บัญชีของฉัน</a>
          <a href="orders.php">คำสั่งซื้อของฉัน</a>
          <a href="favorites.php">รายการโปรด</a>
          <a href="cart.php">ตะกร้าสินค้า</a>
          <a href="logout.php">ออกจากระบบ</a>
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
</div>

<div class="mobile-overlay" id="mobileOverlay"></div>

<?php if(!empty($_SESSION['cart_success'])): ?>
<div id="toast-success" class="toast-success">
  <?= $_SESSION['cart_success']; unset($_SESSION['cart_success']); ?>
</div>
<?php endif; ?>

<?php if(!empty($_SESSION['cart_error'])): ?>
<div id="toast-error" class="toast-error">
  <?= $_SESSION['cart_error']; unset($_SESSION['cart_error']); ?>
</div>
<?php endif; ?>

<!-- CONTENT -->
<div class="page-wrap">

    <img src="assets/images/pic3.png" class="hero-image">

    <h1 class="category-title"><?= htmlspecialchars($displayCategoryName) ?></h1>
    <div class="category-sub">คุ้มค่า คุ้มราคา การันตีโดย FreshFast</div>

    <div class="product-grid">
        <?php if($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                
                <div class="product-card">
                    <div class="product-img">
                        <img src="<?= htmlspecialchars($row['product_image'] ?: 'assets/images/sac.png') ?>">
                    </div>

                    <div class="product-name">
                        <?= htmlspecialchars($row['product_name']) ?>
                    </div>
                    <div class="product-shop">
                        <?= htmlspecialchars($row['shop_name']) ?>
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
        <?php else: ?>
            <div class="empty-box">ยังไม่มีสินค้าในหมวดนี้</div>
        <?php endif; ?>
    </div>

</div>

<!-- FOOTER -->
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
<!-- SCRIPT -->
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

<script>
const successToast = document.getElementById('toast-success');
if (successToast) {
  setTimeout(() => {
    successToast.classList.add('hide');
    setTimeout(() => successToast.remove(), 350);
  }, 3000);
}

const errorToast = document.getElementById('toast-error');
if (errorToast) {
  setTimeout(() => {
    errorToast.classList.add('hide');
    setTimeout(() => errorToast.remove(), 350);
  }, 3000);
}
</script>

</body>
</html>