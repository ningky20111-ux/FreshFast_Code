<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
require_once "db.php";
$email = $_SESSION['user_email'] ?? null;
$avatar = "assets/images/default-user.png";
if(!$email){
    header("Location: login.php");
    exit;
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


<main class="favorite-page">

    <div class="favorite-empty">

        <div class="favorite-heart">
            <svg viewBox="0 0 24 24">
                <path d="M20.5 6.5c-1.5-1.5-4-1.5-5.5 0L12 9.5l-3-3c-1.5-1.5-4-1.5-5.5 0s-1.5 4 0 5.5L12 21l8.5-9c1.5-1.5 1.5-4 0-5.5z"/>
            </svg>
        </div>


        <h1>
            ยังไม่มีรายการที่ถูกใจ
        </h1>


        <p>
            เพิ่มร้านค้าและสินค้าที่คุณชื่นชอบ<br>
            เพื่อดูได้ง่ายในภายหลัง
        </p>


        <a href="home.php" class="favorite-btn">
            เริ่มเลือกซื้อสินค้า
        </a>

    </div>

</main>


<style>

.favorite-page{
    min-height:75vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:#fafafa;
}


.favorite-empty{
    text-align:center;
    background:#fff;
    padding:50px 40px;
    border-radius:28px;
    box-shadow:0 10px 30px rgba(0,0,0,.06);
}


.favorite-heart{

    width:100px;
    height:100px;
    margin:auto;
    background:#fff1f3;

    border-radius:50%;

    display:flex;
    align-items:center;
    justify-content:center;

}


.favorite-heart svg{

    width:50px;
    height:50px;

    fill:#ff5c7a;

}



.favorite-empty h1{

    margin-top:25px;

    font-size:28px;

    color:#222;

    font-weight:800;

}



.favorite-empty p{

    color:#777;

    line-height:1.7;

    margin:15px 0 30px;

    font-size:16px;

}



.favorite-btn{

    display:inline-block;

    background:#2ECC71;

    color:white;

    padding:14px 35px;

    border-radius:999px;

    text-decoration:none;

    font-weight:700;

    transition:.2s;

}



.favorite-btn:hover{

    background:#27ae60;

    transform:translateY(-2px);

}



@media(max-width:768px){

.favorite-empty{

    margin:20px;

    padding:40px 25px;

}


.favorite-empty h1{

    font-size:22px;

}

}

</style>
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

<?php
?>