<?php
session_start();

require_once "db.php";
$conn->set_charset("utf8mb4");

/* ดึงหมวดหมู่ทั้งหมด */
$catStmt = $conn->prepare("SELECT * FROM categories ORDER BY category_id ASC");
$catStmt->execute();
$categories = $catStmt->get_result();

if ($conn->connect_error) {
    die("DB error");
}

/* USER */
/* USER */
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

    $userResult = $userStmt->get_result();

    if($user = $userResult->fetch_assoc()){

        $avatar = $user['profile_image'];

    }

}
/* ดึงร้าน */
$stmt = $conn->prepare("
    SELECT 
        s.*, 
        GROUP_CONCAT(DISTINCT c.category_name SEPARATOR ', ') AS categories
    FROM shops s
    LEFT JOIN products p ON s.shop_id = p.shop_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    GROUP BY s.shop_id
    ORDER BY s.shop_id DESC
");
$stmt->execute();
$result = $stmt->get_result();
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ร้านค้าทั้งหมด | FreshFast</title>

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
    transition:.2s;
}
.product-card:hover{
    transform:translateY(-4px);
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
    margin-top:10px;
    min-height:auto; /* ❌ เอาออก */
    line-height:1.3;
}
.product-shop{
    font-size:14px;
    color:#777;
    margin-top:6px;
    line-height:1.4;
}
.empty-box{
    grid-column:1/-1;
    background:#fff;
    padding:30px;
    text-align:center;
    border-radius:18px;
}
/* ====== TOPBAR ====== */
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

/* ====== MENU ====== */
.desktop-menu{
  position:relative;
}
.desktop-menu-btn{
  height:44px;
  padding:0 18px;
  border:none;
  background:#f6f6f6;
  border-radius:999px;
  font-weight:700;
  cursor:pointer;
}
.desktop-menu-dropdown{
  position:absolute;
  top:54px;
  left:0;
  width:220px;
  background:#fff;
  border-radius:16px;
  box-shadow:0 14px 35px rgba(0,0,0,.14);
  padding:10px;
  display:none;
}
.desktop-menu-dropdown.show{
  display:block;
}

/* ====== SEARCH ====== */
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
}

/* ====== ACTIONS ====== */
.actions{
  display:flex;
  align-items:center;
  gap:8px;
}
.iconbtn{
  border:none;
  background:none;
  padding:8px;
  cursor:pointer;
}

/* ====== PROFILE ====== */
.profile-menu{
  position:relative;
}
.avatar{
  width:40px;
  height:40px;
  border-radius:50%;
}
.dropdown{
  position:absolute;
  top:48px;
  right:0;
  width:180px;
  background:#fff;
  border-radius:14px;
  box-shadow:0 10px 30px rgba(0,0,0,.12);
  padding:8px;
  display:none;
}
.dropdown.show{
  display:block;
}

/* ====== SHOP ====== */
.page-wrap{
  max-width:1200px;
  margin:0 auto;
  padding:30px 20px;
}
.hero-image{
  width:100%;
  height:250px;
  object-fit:cover;
  border-radius:24px;
  
}
.shop-title{
  text-align:center;
  font-size:40px;
  font-weight:800;
}
.shop-sub{
  text-align:center;
  color:#666;
  margin-bottom:30px;
}

/* ====== PRODUCT ====== */
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
  width:150px;              /* 👈 ลดขนาดรูป */
  height:90px;
  aspect-ratio:1/1;
  border-radius:16px;
  overflow:hidden;
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
.product-shop{
    font-size:13px;
    color:#777;
    margin-top:4px;
    line-height:1.4;
}
/* ====== TOAST ====== */
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
}
.toast-success{background:#2e7d32;}
.toast-error{background:#c62828;}
.category-block-title{
  font-size:26px;
  font-weight:800;
  margin:40px 0 16px;
}

.shop-list{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(200px,1fr));
  gap:20px;
}

.shop-card{
  background:#fff;
  border-radius:20px;
  padding:10px;
  box-shadow:0 8px 20px rgba(0,0,0,.06);
}

.shop-thumb img{
    margin-top: 3.5px;
  width:100%;
  border-radius:14px;
  aspect-ratio:3/2;
  object-fit:cover;
}

.shop-name{
  font-weight:700;
  margin-top:10px;
}

.shop-meta{
  font-size:13px;
  color:#666;
}

.shop-btn{
  display:inline-block;
  margin-top:10px;
  padding:8px 14px;
  background:#ffd400;
  border-radius:999px;
  text-decoration:none;
  color:#000;
  font-weight:600;
}
/* ====== MOBILE ====== */
@media(max-width:768px){
  .desktop-menu,
  .desktop-search{
    display:none;
  }
}
/* responsive */
@media(max-width:992px){
    .product-grid{grid-template-columns:repeat(3,1fr);}
}
@media(max-width:768px){
    .product-grid{grid-template-columns:repeat(2,1fr);gap:16px;}
    .hero-image{height:180px;}
    .category-title{font-size:30px;}
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
        <!-- <a href="shop_all.php" class="menu-item">ร้านค้าทั้งหมด</a> -->
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
<img class="avatar" 
src="<?= htmlspecialchars($avatar ?: 'assets/images/default-avatar.png') ?>">
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

<div class="mobile-overlay" id="mobileOverlay"></div>


<!-- CONTENT -->
<div class="page-wrap">

    <img src="assets/images/pic.jpg" class="hero-image">

    <h1 class="category-title">ร้านค้าทั้งหมด</h1>
    <div class="category-sub">เลือกร้านที่คุณต้องการได้เลย</div>
<?php while($cat = $categories->fetch_assoc()): ?>

    <h2 class="category-block-title">
        <?= htmlspecialchars($cat['category_name']) ?>
    </h2>
  <div class="shop-list">

  <?php
  $shopStmt = $conn->prepare("
    SELECT DISTINCT s.shop_id, s.shop_name, s.shop_type, s.stall_number, s.shop_image
    FROM shops s
    JOIN products p ON s.shop_id = p.shop_id
    WHERE p.category_id = ?
  ");
  $shopStmt->bind_param("i", $cat['category_id']);
  $shopStmt->execute();
  $shops = $shopStmt->get_result();
  ?>

  <?php if($shops->num_rows > 0): ?>
    <?php while($shop = $shops->fetch_assoc()): ?>

      <article class="shop-card">

        <div class="shop-thumb">
          <img src="<?= htmlspecialchars($shop['shop_image'] ?: 'assets/images/sac.png') ?>">
        </div>

        <div class="shop-info">
          <div class="shop-name"><?= htmlspecialchars($shop['shop_name']) ?></div>
          <div class="shop-line"></div>

          <div class="shop-meta"><?= htmlspecialchars($shop['shop_type']) ?></div>
          <div class="shop-meta muted">แผง <?= htmlspecialchars($shop['stall_number']) ?></div>

          <a class="shop-btn" href="shops.php?id=<?= $shop['shop_id'] ?>">
            ดูสินค้า
          </a>
        </div>

      </article>

    <?php endwhile; ?>
  <?php else: ?>
    <div class="empty-box">ไม่มีร้านในหมวดนี้</div>
  <?php endif; ?>

    </div>

<?php endwhile; ?>


</div>

<script>
/* ===== MOBILE MENU ===== */
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

/* ===== DESKTOP MENU ===== */
const desktopMenuBtn = document.getElementById("desktopMenuBtn");
const desktopDropdown = document.getElementById("desktopMenuDropdown");

desktopMenuBtn?.addEventListener("click", (e) => {
  e.stopPropagation();
  desktopDropdown.classList.toggle("show");
});

document.addEventListener("click", () => {
  desktopDropdown?.classList.remove("show");
});

/* ===== PROFILE ===== */
const avatarBtn = document.getElementById("avatarBtn");
const profileDropdown = document.getElementById("profileDropdown");

avatarBtn?.addEventListener("click", (e) => {
  e.stopPropagation();
  profileDropdown.classList.toggle("show");
});

document.addEventListener("click", () => {
  profileDropdown?.classList.remove("show");
});
</script>
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
</body>
</html>