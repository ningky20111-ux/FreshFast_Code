<?php
session_start();

require_once "db.php";

$email = $_SESSION['user_email'] ?? null;

$avatar = "assets/images/default-user.png";

if($email){

    $stmt = $conn->prepare("
        SELECT profile_image
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->bind_param("s",$email);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    if(!empty($user['profile_image']) && file_exists($user['profile_image'])){
        $avatar = $user['profile_image'];
    }

}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Home | FreshFast</title>
<link rel="stylesheet" href="assets/css/home.css?v=2">

<style>
.toast-success{
  position:fixed;
  top:50%;
  left:50%;
  transform:translate(-50%, -50%);
  background:#1b5e20;
  color:#fff;
  padding:16px 26px;
  border-radius:18px;
  font-size:15px;
  font-weight:600;
  box-shadow:0 18px 40px rgba(0,0,0,.22);
  z-index:9999;

  opacity:1;
  transition:all .35s ease;
}

.toast-success.hide{
  opacity:0;
  transform:translate(-50%, -55%) scale(.95);
}

@keyframes toastPop{
  from{
    opacity:0;
    transform:translate(-50%, -45%) scale(.92);
  }
  to{
    opacity:1;
    transform:translate(-50%, -50%) scale(1);
  }
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
.toast-error{
  position:fixed;
  top:50%;
  left:50%;
  transform:translate(-50%, -50%);
  background:#c62828;
  color:#fff;
  padding:16px 26px;
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
  white-space:normal;

  opacity:1;
  transition:all .35s ease;
}

.toast-error.hide{
  opacity:0;
  transform:translate(-50%, -55%) scale(.95);
}

.card__name{
  font-weight:700;
  margin-bottom:2px; /* 👈 ชิดลง */
}

.card__shop{
  font-size:12.5px;
  color:#777;
  margin-top:0;       /* 👈 ไม่ให้มันห่าง */
  line-height:1.2;
}

/* ถ้ายังห่างอีก ใช้อันนี้ */
.card__shop{
  margin-top:-2px;
}

.product-card{
  background:#fff;
  border-radius:22px;
  padding:14px;
  box-shadow:0 8px 20px rgba(0,0,0,.06);
  min-width:180px; /* 👈 สำคัญสำหรับ scroll แนวนอน */
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
  margin-bottom:2px;
}

.product-shop{
  font-size:13px;
  color:#777;
  margin-top:0;
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

.product-card {
  color: #111; /* 👈 บังคับให้กลับมาเป็นสีดำ */
}

.product-name {
  color: #111;
}

.product-price {
  color: #111;
}

.product-shop {
  color: #777;
}

.plus-btn.disabled{
  background:#ccc;
  cursor:not-allowed;
}


.product-card{
    flex: 0 0 180px;
    min-width:180px;
}


.no-shop-message{
    width:100%;
    text-align:center;
    padding:40px 20px;
    color:#fff;
    font-size:18px;
    font-weight:600;
}



/* 📱 MOBILE */

/* ================= RESPONSIVE ================= */
@media (max-width:768px){
  .no-shop-message{
    width:100%;
    text-align:center;
    padding:40px 20px;
    color:#fff;
    font-size:12px;
    font-weight:600;
}
  .cards{
      gap:0;
      scroll-snap-type:x mandatory;
    }
  .product-card{
    flex: 0 0 50%;
    max-width: 100%;
    scroll-snap-align:center;
    margin:0 auto;
  }
  .product-card{
    max-width: 340px;
  }
.card__shop{
  width:150px;              /* 👈 ลดขนาดรูป */
  height:90px;
}
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
  .login-btn{
    width:38px;
    height:38px;
    padding:0;
    justify-content:center;
  }

  .login-text{
    display:none; /* ซ่อนข้อความ */
  }

  .login-btn .icon{
    width:22px;
    height:22px;
  }


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


<main class="wrap">

  <!-- HERO -->
  <section class="hero" aria-label="โปรโมชั่น">
    <div class="hero__frame">
      <div class="hero__slides" id="slides">
        <img class="hero__img is-active" src="assets/images/sul.png" alt="">
      </div>
    </div>
  </section>

  <?php
  require_once "db.php";
  $conn->set_charset("utf8mb4");
$sql = "
SELECT 
    c.category_id,
    c.category_name,
    COUNT(p.product_id) AS total_items
FROM categories c
LEFT JOIN products p
ON c.category_id = p.category_id
GROUP BY c.category_id, c.category_name
ORDER BY c.category_id
";
  $result = $conn->query($sql);

  $thaiNames = [
    'Meat' => 'เนื้อสัตว์',
    'Fruits & Vegetables' => 'ผักผลไม้',
    'Seasoning' => 'เครื่องปรุง',
    'Beverages' => 'เครื่องดื่ม',
    'Frozen' => 'แช่แข็ง',
    'Kitchen Supplies' => 'ของใช้ในครัว',
    'Desserts' => 'ของหวาน'
  ];
  ?>

  <!-- CATEGORY -->
  <section class="cats" aria-label="หมวดหมู่">
    <?php while($row = $result->fetch_assoc()): ?>
      <a class="cat" href="category.php?id=<?= (int)$row['category_id'] ?>">
        <div class="cat__title">
          <?= htmlspecialchars($thaiNames[$row['category_name']] ?? $row['category_name']) ?>
        </div>
        <div class="cat__sub"><?= (int)$row['total_items'] ?> รายการ</div>
      </a>
    <?php endwhile; ?>
  </section>

  <!-- DEALS -->
  <section class="deals" aria-label="กำลังลดแรง">
    <div class="deals__head">
      <div class="deals__title">
        <div class="deals__big">สินค้ามาใหม่!</div>
        <div class="deals__small">คุ้มค่า คุ้มราคา</div>
      </div>
    </div>

    <div class="deals__row">
      <button class="navbtn prev" type="button">‹</button>
      <?php


$deals = $conn->query("
SELECT 
    p.product_id,
    p.product_name,
    p.price,
    p.product_image,
    s.shop_name
FROM products p
JOIN shops s 
ON p.shop_id = s.shop_id
WHERE s.status = 'open'
AND CURTIME() BETWEEN s.opening_time AND s.closing_time
ORDER BY p.product_id DESC
LIMIT 10
");
?>
<div class="cards" id="dealsTrack">

<?php if ($deals->num_rows == 0): ?>

    <div class="no-shop-message">
        ยังไม่มีร้านค้าเปิด ณ ขณะนี้
    </div>

<?php else: ?>

    <?php while($row = $deals->fetch_assoc()): ?>

    <article class="product-card">

        <div class="product-img">
            <img src="<?= htmlspecialchars($row['product_image']) ?>" alt="">
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

            <?php if ($email): ?>
                <form method="POST" action="add_to_cart.php">
                    <input type="hidden" name="product_id" value="<?= $row['product_id'] ?>">
                    <button type="submit" class="plus-btn">+</button>
                </form>

            <?php else: ?>

                <button class="plus-btn disabled" onclick="showLoginToast()">+</button>

            <?php endif; ?>

        </div>

    </article>

    <?php endwhile; ?>

<?php endif; ?>

</div>
      



      <button class="navbtn next" type="button">›</button>
    </div>
  </section>

  <!-- SHOPS -->
  <section class="home-section2">

    <div class="promo-wrap">
      <img class="promo-banner" src="assets/images/saa.png" alt="">
    </div>

    <h2 class="open-title">กำลังเปิดอยู่ตอนนี้</h2>


  <?php
  $limit = 3;

  // ✅ ถ้ากด "ดูเพิ่มเติม"
  if (isset($_GET['load']) && $_GET['load'] === 'more') {
      $limit = 100; // หรือจะใช้ 999 ก็ได้
  }

$shops = $conn->query("
SELECT
    shop_id,
    shop_name,
    shop_type,
    stall_number,
    shop_image
FROM shops
WHERE status = 'open'
AND CURTIME() BETWEEN opening_time AND closing_time
ORDER BY shop_id
LIMIT $limit
");
    ?>

    <div class="shop-list" id="shopList">
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

      <a class="shop-btn" href="shops.php?id=<?= (int)$shop['shop_id'] ?>">
        ดูสินค้า
      </a>
          </div>
      </article>

<?php endwhile; ?>

</div>

    <?php $conn->close(); ?>

    <div class="footer-more">
<a class="more-btn" href="home.php?load=more">
  ดูเพิ่มเติม
</a>
    </div>

  </section>

</main>


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
// ================= DEALS SLIDER =================
const dealsTrack = document.getElementById("dealsTrack");
const prevBtn = document.querySelector(".navbtn.prev");
const nextBtn = document.querySelector(".navbtn.next");

if (dealsTrack && prevBtn && nextBtn) {

  const scrollAmount = 320;

  nextBtn.addEventListener("click", () => {
    dealsTrack.scrollBy({
      left: scrollAmount,
      behavior: "smooth"
    });
  });

  prevBtn.addEventListener("click", () => {
    dealsTrack.scrollBy({
      left: -scrollAmount,
      behavior: "smooth"
    });
  });

}
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

document.addEventListener("click", () => {
  profileDropdown?.classList.remove("show");
});
</script> 

<?php if (!empty($_SESSION['cart_success'])): ?>
  <div id="toast-success" class="toast-success">
    <?= htmlspecialchars($_SESSION['cart_success']) ?>
  </div>
  <?php unset($_SESSION['cart_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['cart_error'])): ?>
  <div id="toast-error" class="toast-error">
    <?= htmlspecialchars($_SESSION['cart_error']) ?>
  </div>
  <?php unset($_SESSION['cart_error']); ?>
<?php endif; ?>

<script>
const errorToast = document.getElementById('toast-error');

if (errorToast) {
  setTimeout(() => {
    errorToast.style.opacity = '0';
    errorToast.style.transform = 'translate(-50%,-55%) scale(.95)';

    setTimeout(() => {
      errorToast.remove();
    }, 300);
  }, 3000);
}
</script>

<script>
const toast = document.getElementById('toast-success');
if (toast) {
  setTimeout(() => {
    toast.classList.add('hide');

    setTimeout(() => {
      toast.remove();
    }, 350);

  }, 3000);
}
</script>
<script>
function showLoginToast(){
  alert("กรุณาเข้าสู่ระบบก่อนเพิ่มสินค้า");
}
</script>
</body>
</html>