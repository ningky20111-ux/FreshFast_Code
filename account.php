<?php
// account.php (UI-first version: ยังไม่ผูก DB)
session_start();
$conn = new mysqli("localhost","root","","freshfast");
$conn->set_charset("utf8mb4");

if($conn->connect_error){
    die("Database Error");
}
$email = $_SESSION['user_email'] ?? null;

$avatar = $email
  ? "https://www.gravatar.com/avatar/" . md5(strtolower(trim($email))) . "?d=identicon&s=96"
  : null;
// ถ้าคุณยังไม่มีระบบ session user_id ให้ใช้แบบนี้ชั่วคราว
// ถ้ามีแล้ว ให้เปลี่ยนเป็นเช็ค user_id ทีหลัง
if (!isset($_SESSION['user_email'])) {
  header("Location: login.php");
  exit;
}

/**
 * Mock user data (แทน DB ชั่วคราว)
 * ตอนผูก DB ทีหลัง: เปลี่ยนให้ดึงจาก SELECT users WHERE user_id = session
 */
$stmt = $conn->prepare("
SELECT *
FROM users
WHERE email = ?
LIMIT 1
");

$stmt->bind_param("s",$email);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();
$success = false;


function uploadProfileImage(){

    if(
        !isset($_FILES['profile_image']) ||
        $_FILES['profile_image']['error'] != 0
    ){
        return null;
    }

    $folder = "assets/profile/";

    if(!is_dir($folder)){
        mkdir($folder,0777,true);
    }

    $ext = strtolower(pathinfo(
        $_FILES['profile_image']['name'],
        PATHINFO_EXTENSION
    ));

    $allow = ['jpg','jpeg','png','webp'];

    if(!in_array($ext,$allow)){
        return null;
    }

    $filename = "user_".time().".".$ext;

    $path = $folder.$filename;

    if(move_uploaded_file(
        $_FILES['profile_image']['tmp_name'],
        $path
    )){
        return $path;
    }

    return null;
}
// Mock update (ยังไม่อัปเดต DB)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name  = trim($_POST['name']);
    $mail  = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    $newImage = uploadProfileImage();

    if($newImage){
        $profileImage = $newImage;
    }else{
        $profileImage = $user['profile_image'];
    }

    $stmt = $conn->prepare("
    UPDATE users
    SET
        name=?,
        email=?,
        phone=?,
        profile_image=?
    WHERE user_id=?
    ");

    $stmt->bind_param(
        "ssssi",
        $name,
        $mail,
        $phone,
        $profileImage,
        $user['user_id']
    );

    $stmt->execute();

    header("Location: account.php?success=1");
    exit;

}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>บัญชีของฉัน | FreshFast</title>
  <link rel="stylesheet" href="assets/css/account.css?v=1">


<style>
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

/* ================= RESPONSIVE ================= */
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

<!-- TOPBAR -->
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
          <a href="home.php">หน้าหลัก</a>
          <a href="category.php?id=1">เนื้อสัตว์</a>
          <a href="category.php?id=2">ผักผลไม้</a>
          <a href="category.php?id=3">เครื่องปรุง</a>
          <a href="category.php?id=4">เครื่องดื่ม</a>
          <a href="category.php?id=5">แช่แข็ง</a>
          <a href="category.php?id=6">ของใช้ในครัว</a>
          <a href="category.php?id=7">ของหวาน</a>
        </div>
      </div>

    </div>

    <div class="desktop-search">
      <input type="text" placeholder="ค้นหาสินค้า / ร้านค้า">
    </div>

    <form class="mobile-search" action="search.php" method="GET">
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

      <div class="profile-menu">
        <button class="avatar-btn" id="avatarBtn">
         <img class="avatar"
src="<?= !empty($user['profile_image'])
        ? htmlspecialchars($user['profile_image'])
        : htmlspecialchars($avatar) ?>">
        </button>

        <div class="dropdown" id="profileDropdown">

          <a href="account.php" class="dropdown-item">
            บัญชีของฉัน
          </a>

          <a href="my_order.php" class="dropdown-item">
            คำสั่งซื้อของฉัน
          </a>

          <div class="dropdown-sep"></div>

          <a href="logout.php" class="dropdown-item">
            ออกจากระบบ
          </a>

        </div>

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

  <div class="menu-title">ผู้ใช้งาน</div>
  <a href="account.php" class="menu-item">บัญชีของฉัน</a>
  <a href="orders.php" class="menu-item">คำสั่งซื้อของฉัน</a>
  <a href="favorites.php" class="menu-item">รายการโปรด</a>
  <a href="cart.php" class="menu-item">ตะกร้าสินค้า</a>
  <a href="logout.php" class="menu-item">ออกจากระบบ</a>
</div>

<div class="mobile-overlay" id="mobileOverlay"></div>

<div class="wrap">
  <div class="hero">
    <h1>บัญชีของฉัน</h1>
    <p>แก้ไขโปรไฟล์</p>
  </div>

  <div class="panel">
    <!-- SIDEBAR -->
    <div class="side">
      <button class="navbtn active">แก้ไขโปรไฟล์</button>
      <button class="navbtn" type="button" onclick="alert('หน้า ที่อยู่ของฉัน (ยังไม่ทำ)')">ที่อยู่ของฉัน</button>
      <button class="navbtn" type="button" onclick="window.location.href='orders.php'"> คำสั่งซื้อของฉัน
</button>
      <button class="navbtn" type="button" onclick="alert('หน้า เปลี่ยนรหัส (ยังไม่ทำ)')">เปลี่ยนรหัส</button>
    </div>

    <!-- CONTENT -->
    <div class="content">
      <form method="post" action="account.php" enctype="multipart/form-data">
        <div class="grid">
          <!-- photo -->
          <div>
            <div class="photo" id="photoBox">
              <span style="color:#9bb3a5;font-weight:900;">รูปโปรไฟล์</span>

              <!-- image preview -->
             <img
                id="photoPreview"
                src="<?= htmlspecialchars($user['profile_image']) ?>"
                style="<?= empty($user['profile_image']) ? 'display:none;' : '' ?>"
                >

              <button class="editfab" type="button" id="editPhotoBtn" title="เปลี่ยนรูป">✎</button>
            </div>
            <input id="photoInput" type="file" name="profile_image" accept="image/*" style="display:none;">
          </div>

          <!-- form -->
          <div>
            <div class="formrow">
              <label>ชื่อผู้ใช้งาน</label>
              <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>

            <div class="formrow">
              <label>อีเมล</label>
              <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <div class="formrow">
              <label>เบอร์โทร</label>
              <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
            </div>

            <button class="btn green" type="submit">อัปเดตการแก้ไข</button>

            <?php if ($success): ?>
              <div class="toast">บันทึกสำเร็จ (ตอนนี้เป็นโหมด UI ยังไม่ผูกฐานข้อมูล)</div>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Dropdown menu
  const accountMenu = document.getElementById('accountMenu');

  avatarBtn.addEventListener('click', () => {
    accountMenu.classList.toggle('show');
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.dropdown')) {
      accountMenu.classList.remove('show');
    }
  });

  // Photo preview (UI only)
  const editBtn = document.getElementById('editPhotoBtn');
  const fileInput = document.getElementById('photoInput');
  const previewImg = document.getElementById('photoPreview');
  const photoBox = document.getElementById('photoBox');

  editBtn.addEventListener('click', () => fileInput.click());

  fileInput.addEventListener('change', () => {
    const file = fileInput.files && fileInput.files[0];
    if (!file) return;

    // basic type check
    if (!file.type.startsWith('image/')) {
      alert('กรุณาเลือกไฟล์รูปภาพเท่านั้น');
      fileInput.value = '';
      return;
    }

    const url = URL.createObjectURL(file);
    previewImg.src = url;
    previewImg.style.display = 'block';

    // hide placeholder text
    const spans = photoBox.querySelectorAll('span');
    spans.forEach(s => s.style.display = 'none');
  });
</script>
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

document.querySelectorAll("#mobileMenu a").forEach(link => {
  link.addEventListener("click", () => {
    mobileMenu.classList.remove("show");
    overlay.classList.remove("show");
  });
});

const desktopMenuBtn = document.getElementById("desktopMenuBtn");
const desktopDropdown = document.getElementById("desktopMenuDropdown");

desktopMenuBtn?.addEventListener("click", (e) => {
  e.stopPropagation();
  desktopDropdown.classList.toggle("show");
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

document.addEventListener("click", () => {
  profileDropdown?.classList.remove("show");
});
</script>
</body>
</html>