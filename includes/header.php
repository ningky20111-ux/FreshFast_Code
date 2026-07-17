<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$email = $_SESSION['user_email'] ?? null;

$avatar = $email
  ? "https://www.gravatar.com/avatar/" . md5(strtolower(trim($email))) . "?d=identicon&s=96"
  : null;
?>

<header class="topbar">
  <div class="topbar__inner">

    <div class="left-group">

      <button class="menu-btn" id="menuBtn">☰</button>

      <a class="brand desktop-logo" href="home.php">
        <img src="assets/images/logo_ok.png" alt="FreshFast">
      </a>

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

    <!-- SEARCH -->
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


      <a href="favorites.php" class="iconbtn">
          <svg viewBox="0 0 24 24" class="icon" fill="none" stroke="currentColor"
          stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20.5 6.5c-1.5-1.5-4-1.5-5.5 0L12 9.5l-3-3c-1.5-1.5-4-1.5-5.5 0s-1.5 4 0 5.5L12 21l8.5-9c1.5-1.5 1.5-4 0-5.5z"/>
        </svg>
      </a>
      <a href="cart.php" class="iconbtn">
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
            <a href="logout.php" class="dropdown-item">ออกจากระบบ</a>
          </div>
        </div>
      <?php else: ?>
        <a href="login.php" class="login-btn">เข้าสู่ระบบ</a>
      <?php endif; ?>

    </div>

  </div>
</header>

<!-- MOBILE MENU -->
<div class="mobile-menu" id="mobileMenu">
  <a href="home.php" class="menu-item">หน้าหลัก</a>
</div>

<div class="mobile-overlay" id="mobileOverlay"></div>



<script>
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