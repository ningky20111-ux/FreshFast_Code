<?php
session_start();

require_once "db.php";
$conn->set_charset("utf8mb4");

$q = $_GET['q'] ?? '';
$q = trim($q);

$email = $_SESSION['user_email'] ?? null;
$avatar = $email
  ? "https://www.gravatar.com/avatar/" . md5(strtolower(trim($email))) . "?d=identicon&s=96"
  : null;
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ค้นหา | FreshFast</title>
<link rel="stylesheet" href="assets/css/layout.css">

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
          <img class="avatar" src="<?= htmlspecialchars($avatar) ?>">
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
<style>

body{
  font-family: 'textarea', sans-serif;
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
  border:1px solid rgba(0,0,0,.06);
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
  width:100%;
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

/* ====== MOBILE ====== */
@media(max-width:768px){
  .desktop-menu,
  .desktop-search{
    display:none;
  }
}
.page-wrap{
  max-width:1200px;
  margin:0 auto;
  padding:30px 20px;
}

.section-title{
  font-size:22px;
  font-weight:800;
  margin:30px 0 16px;
}
.product-card{
  background:#fff;
  border-radius:22px;
  padding:12px; /* 👈 ทำให้รูปไม่ติดขอบ */
  box-shadow:0 8px 20px rgba(0,0,0,.06);
  transition:.2s;
}

.product-card:hover{
  transform:translateY(-4px);
}

/* 👇 หัวใจสำคัญ */
.product-thumb{
  width:100%;
  aspect-ratio:1/1;
  border-radius:18px;   /* 👈 ทำให้รูปมน */
  overflow:hidden;      /* 👈 ตัดขอบให้โค้ง */
  background:#f4f4f4;
}

.product-thumb img{
  width:100%;
  height:100%;
  object-fit:cover;
}

/* เนื้อด้านใน */
.product-info{
  padding-top:10px;
}

.product-name{
  font-weight:700;
  font-size:15px;
}

.product-shop{
  font-size:13px;
  color:#777;
  margin-top:2px;
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

/* ปุ่ม */
.plus-btn{
  width:34px;
  height:34px;
  border:none;
  border-radius:50%;
  background:#ffd400;
  font-size:18px;
  cursor:pointer;
}
/* ===== SHOP CARD (สไตล์เดียวกับสินค้า) ===== */
.shop-list{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(200px,1fr));
  gap:24px;
}

/* ตัวการ์ด */
.shop-card{
  background:#fff;
  border-radius:22px;
  padding:12px; /* 👈 สำคัญ (ทำให้รูปไม่ติดขอบ) */
  box-shadow:0 8px 20px rgba(0,0,0,.06);
  transition:.2s;
}

.shop-card:hover{
  transform:translateY(-4px);
}

/* 👇 รูป (เหมือน product) */
.shop-thumb{
  width:100%;
  aspect-ratio:1/1;
  border-radius:18px;
  overflow:hidden;
  background:#f4f4f4;
}

.shop-thumb img{
  width:100%;
  height:100%;
  object-fit:cover;
}

/* ข้อมูล */
.shop-info{
  padding-top:10px;
}

/* ฟอนต์เหมือนสินค้า */
.shop-name{
  font-weight:700;
  font-size:15px;
  color:#111;
}

.shop-meta{
  font-size:13px;
  color:#777;
  margin-top:2px;
}

.shop-meta.muted{
  color:#aaa;
}

/* ปุ่ม */
.shop-btn{
  display:inline-block;
  margin-top:10px;
  padding:8px 14px;
  border-radius:999px;
  background:#ffd400;
  font-weight:700;
  text-decoration:none;
  color:#111;
  font-size:13px;
}
@media (max-width:768px){

  /* ===== PAGE ===== */
  .page-wrap{
    padding:20px 14px;
  }

  h2{
    font-size:18px;
    margin-bottom:10px;
  }

  .section-title{
    font-size:18px;
    margin:20px 0 12px;
  }

  /* ===== PRODUCT ===== */
  .product-grid{
    display:flex;
    overflow-x:auto;
    gap:12px;
    scroll-snap-type:x mandatory;
  }

  .product-card{
    flex:0 0 70%;
    max-width:300px;
    scroll-snap-align:center;
  }

  /* ===== SHOP ===== */
  .shop-list{
    display:flex;
    overflow-x:auto;
    gap:12px;
    scroll-snap-type:x mandatory;
  }

  .shop-card{
    flex:0 0 70%;
    max-width:300px;
    scroll-snap-align:center;
  }

  /* ===== รูป ===== */
  .product-thumb,
  .shop-thumb{
    border-radius:16px;
  }

  /* ===== text ===== */
  .product-name,
  .shop-name{
    font-size:14px;
  }

  .product-price{
    font-size:14px;
  }

  /* ===== ปุ่ม ===== */
  .plus-btn{
    width:32px;
    height:32px;
    font-size:16px;
  }

  .shop-btn{
    font-size:12px;
    padding:6px 12px;
  }

}
</style>
</head>
<body>

<div class="page-wrap">

<h2>ผลการค้นหา: "<?= htmlspecialchars($q) ?>"</h2>

<?php if ($q !== ''): ?>

<?php
$like = "%{$q}%";

/* ===== สินค้า ===== */
$stmt = $conn->prepare("
    SELECT p.*, s.shop_name
    FROM products p
    JOIN shops s ON p.shop_id = s.shop_id
    WHERE p.product_name LIKE ?
");
$stmt->bind_param("s", $like);
$stmt->execute();
$products = $stmt->get_result();

/* ===== ร้านค้า ===== */
$stmt2 = $conn->prepare("
    SELECT *
    FROM shops
    WHERE shop_name LIKE ?
");
$stmt2->bind_param("s", $like);
$stmt2->execute();
$shops = $stmt2->get_result();
?>

<!-- ================= สินค้า ================= -->
<div class="section-title">สินค้า :</div>

<?php if ($products->num_rows > 0): ?>
<div class="product-grid">

<?php while($row = $products->fetch_assoc()): ?>
  
<article class="product-card">

  <div class="product-thumb">
    <img src="<?= htmlspecialchars($row['product_image'] ?: 'assets/images/sac.png') ?>">
  </div>

  <div class="product-info">
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

      <form method="POST" action="add_to_cart.php">
        <input type="hidden" name="product_id" value="<?= $row['product_id'] ?>">
        <button type="submit" class="plus-btn">+</button>
      </form>
    </div>
  </div>

</article>

<?php endwhile; ?>

</div>

<?php else: ?>
  <p>ไม่พบสินค้า</p>
<?php endif; ?>


<!-- ================= ร้านค้า ================= -->
<div class="section-title">ร้านค้า :</div>

<?php if ($shops->num_rows > 0): ?>
<div class="shop-list">

<?php while($shop = $shops->fetch_assoc()): ?>

  <article class="shop-card">

    <div class="shop-thumb">
      <img src="<?= htmlspecialchars($shop['shop_image'] ?: 'assets/images/sac.png') ?>">
    </div>

    <div class="shop-info">
      <div class="shop-name"><?= htmlspecialchars($shop['shop_name']) ?></div>
     
      <div class="shop-meta"><?= htmlspecialchars($shop['shop_type']) ?></div>
      <div class="shop-meta muted">แผง <?= htmlspecialchars($shop['stall_number']) ?></div>

      <a class="shop-btn" href="shops.php?id=<?= $shop['shop_id'] ?>">
        ดูสินค้า
      </a>
    </div>

  </article>

<?php endwhile; ?>

</div>

<?php else: ?>
  <p>ไม่พบร้านค้า</p>
<?php endif; ?>


<?php else: ?>
  <p>กรุณาพิมพ์คำค้นหา</p>
<?php endif; ?>
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