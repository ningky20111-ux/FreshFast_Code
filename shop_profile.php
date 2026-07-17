<?php
session_start();
require_once "db.php";

if (!isset($conn)) {
    require_once "db.php";
    $conn->set_charset("utf8mb4");
}

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function columnExists($conn, $table, $column) {
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

/* CHECK COLUMNS */
if (!columnExists($conn, "shops", "shop_id")) { $conn->query("ALTER TABLE shops ADD COLUMN shop_id INT AUTO_INCREMENT PRIMARY KEY"); }
if (!columnExists($conn, "shops", "owner_id")) { $conn->query("ALTER TABLE shops ADD COLUMN owner_id INT NULL"); }
if (!columnExists($conn, "shops", "status")) { $conn->query("ALTER TABLE shops ADD COLUMN status VARCHAR(20) DEFAULT 'open'"); }
if (!columnExists($conn, "shops", "shop_image")) { $conn->query("ALTER TABLE shops ADD COLUMN shop_image VARCHAR(255) NULL"); }
if (!columnExists($conn, "shops", "shop_banner")) { $conn->query("ALTER TABLE shops ADD COLUMN shop_banner VARCHAR(255) NULL"); }
if (!columnExists($conn, "shops", "shop_address")) { $conn->query("ALTER TABLE shops ADD COLUMN shop_address TEXT NULL"); }
if (!columnExists($conn, "shops", "shop_phone")) { $conn->query("ALTER TABLE shops ADD COLUMN shop_phone VARCHAR(30) NULL"); }
if (!columnExists($conn, "shops", "shop_email")) { $conn->query("ALTER TABLE shops ADD COLUMN shop_email VARCHAR(150) NULL"); }

/* GET CURRENT USER */
$currentUserId = $_SESSION['shop_id'] ?? 0;
if (!$currentUserId && isset($_SESSION['shop_email'])) {
    $email = $_SESSION['shop_email'];
    $stmt = $conn->prepare("SELECT shop_id FROM shops WHERE shop_email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $currentUserId = $user['shop_id'] ?? 0;
}

if ($currentUserId <= 0) {
    die("กรุณาเข้าสู่ระบบก่อนแก้ไขร้านค้า");
}

/* GET SHOP */
$stmt = $conn->prepare("SELECT * FROM shops WHERE shop_id = ? LIMIT 1");
$stmt->bind_param("i", $currentUserId);
$stmt->execute();

$shop = $stmt->get_result()->fetch_assoc();

if (!$shop) {
    $defaultShopName = "";
    $defaultShopType = "ยังไม่ได้ระบุ";
    $defaultStatus = "open";
    $stmt = $conn->prepare("INSERT INTO shops (shop_id, shop_name, shop_type, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $currentUserId, $defaultShopName, $defaultShopType, $defaultStatus);
    $stmt->execute();
    
    $newShopId = $conn->insert_id;
    $stmt = $conn->prepare("SELECT * FROM shops WHERE shop_id = ? LIMIT 1");
    $stmt->bind_param("i", $newShopId);
    $stmt->execute();
    $shop = $stmt->get_result()->fetch_assoc();
}

$shopId = (int)$shop['shop_id'];

/* UPLOAD FUNCTION */
function uploadImage($fieldName, $shopId, $prefix) {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) return null;
    $folder = "assets/shoping/";
    if (!is_dir($folder)) mkdir($folder, 0777, true);
    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed)) return null;
    $fileName = $prefix . "_" . $shopId . "_" . time() . "." . $ext;
    $savePath = $folder . $fileName;
    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $savePath)) return $savePath;
    return null;
}

/* UPDATE SHOP */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_shop'])) {
    $shopName = trim($_POST['shop_name'] ?? '');
    $shopDescription = trim($_POST['description'] ?? '');
    $shopAddress = trim($_POST['shop_address'] ?? '');
    $shopType = trim($_POST['shop_type'] ?? '');
    $shopEmail = trim($_POST['shop_email'] ?? '');
    $shopPhone = trim($_POST['shop_phone'] ?? '');
    $newShopImage = uploadImage("shop_image", $shopId, "shop");
    $shopImageSave = $newShopImage ?: ($shop['shop_image'] ?? null);
    $stmt = $conn->prepare("
        UPDATE shops 
        SET 
            shop_name = ?,
            description = ?,
            shop_type = ?,
            shop_address = ?,
            shop_email = ?,
            shop_phone = ?,
            shop_image = ?
        WHERE shop_id = ?
    ");

    $stmt->bind_param(
        "sssssssi",
        $shopName,
        $shopDescription,
        $shopType,
        $shopAddress,
        $shopEmail,
        $shopPhone,
        $shopImageSave,
        $shopId
    );

 $stmt->execute();
$_SESSION['success'] = "อัปเดตข้อมูลร้านค้าสำเร็จแล้ว";

    header("Location: shop_profile.php?v=" . time());
    exit;
}

$shopImage = null;
if (!empty($shop['shop_image']) && file_exists($shop['shop_image'])) {
    $shopImage = $shop['shop_image'];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>แก้ไขร้านค้า | FreshFast</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">


<style>
/* ================= CSS RESET & BASE ================= */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family:sans-serif;
}

body {
    background: #ffffff;
    color: #111111;
    overflow-x: hidden;
    width: 100%;
    -webkit-font-smoothing: antialiased;
}

img {
    max-width: 100%;
    height: auto;
    display: block;
}

.hidden-file {
    display: none;
}

/* ================= HEADER & NAVIGATION ================= */
.header {
    height: 84px;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,.05);
}

.logo img {
    height: 50px;
}

.profile-menu {
    position: relative;
}

.profile-btn {
    width: 46px;
    height: 46px;
    border: none;
    border-radius: 50%;
    overflow: hidden;
    cursor: pointer;
    background: none;
    box-shadow: 0 4px 10px rgba(0,0,0,.12);
}

.profile-btn img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-dropdown {
    position: absolute;
    top: 56px;
    right: 0;
    width: 180px;
    background: #ffffff;
    border-radius: 14px;
    box-shadow: 0 10px 30px rgba(0,0,0,.15);
    padding: 6px;
    display: none;
    z-index: 999;
}

.profile-dropdown.show {
    display: block;
}

.profile-dropdown a {
    display: block;
    padding: 10px 14px;
    border-radius: 10px;
    text-decoration: none;
    color: #111;
    font-weight: 600;
    font-size: 14px;
}

.profile-dropdown a:hover {
    background: #f5f5f5;
}

.nav {
    height: 54px;
    background: #ffd400;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 40px;
    box-shadow: 0 2px 6px rgba(0,0,0,.04);
}

.nav a {
    text-decoration: none;
    color: #111111;
    font-weight: 700;
    font-size: 15px;
    transition: 0.2s;
}

.nav a:hover, .nav a.active {
    color: #008c3a;
}

/* ================= MAIN CONTENT (DESKTOP) ================= */
.title-zone {
    text-align: center;
    padding: 30px 20px;
}

.title-zone h1 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 15px;
}

.shop-name-input {
    border: none;
    background: transparent;
    border-bottom: 2px solid #000;
    outline: none;
    text-align: center;
    font-size: 22px;
    font-weight: 700;
    width: 100%;
    max-width: 300px;
    padding: 5px;
}

.form-section {
    background: #f8f2df;
    padding: 50px 20px;
    width: 100%;
}

.form-grid {
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: flex-start;
    gap: 40px;
    max-width: 1100px;
    margin: 0 auto;
    width: 100%;
}

.side-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 160px;
    flex-shrink: 0;
}

.side-actions button {
    padding: 10px;
    border-radius: 12px;
    border: 1px solid #999;
    font-weight: 700;
    background: #fff;
    cursor: pointer;
}

.side-actions .active {
    background: #009536;
    color: #fff;
    border: none;
}

.input-panel {
    flex: 1;
    max-width: 450px;
    width: 100%;
}

.input-panel label {
    display: block;
    font-weight: 700;
    margin: 15px 0 6px;
}

.input-panel input,
.input-panel textarea {
    width: 100%;
    border: 1px solid #ddd;
    border-radius: 12px;
    padding: 10px 14px;
    outline: none;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,.05);
    font-size: 15px;
}

.input-panel textarea {
    height: 80px;
    resize: none;
}

.update-row {
    margin-top: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.update-btn {
    border: none;
    background: #009536;
    color: #fff;
    border-radius: 12px;
    padding: 10px 24px;
    font-weight: 700;
    cursor: pointer;
    font-size: 16px;
}

.delete-link {
    color: red;
    font-weight: 700;
    text-decoration: none;
}

.profile-image-wrap {
    position: relative;
    width: 260px;
    height: 260px;
    flex-shrink: 0;
}

.shop-image-box {
    width: 100%;
    height: 100%;
    border-radius: 24px;
    overflow: hidden;
    background: #e5e7eb;
    box-shadow: 0 4px 15px rgba(0,0,0,.1);
}

.shop-image-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-upload-label {
    position: absolute;
    right: -5px;
    bottom: -5px;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #16a34a;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(0,0,0,.2);
}

.empty-profile {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e5e7eb;
}

.empty-profile svg {
    width: 60px;
    height: 60px;
    stroke: #9ca3af;
}

.footer {
    padding: 40px 20px;
    text-align: center;
}

.footer-line {
    border-top: 1px solid #ddd;
    padding-top: 20px;
    font-size: 13px;
    color: #555;
    line-height: 1.6;
}

.footer small {
    display: block;
    margin-top: 10px;
    color: #888;
}
.success-message{

    max-width:500px;

    margin:0 auto 20px;

    background:#dcfce7;

    color:#166534;

    border-left:5px solid #16a34a;

    padding:15px 20px;

    border-radius:14px;

    font-weight:700;

    text-align:center;

    animation:fade .3s ease;

}


@keyframes fade{

from{
    opacity:0;
    transform:translateY(-10px);
}

to{
    opacity:1;
    transform:translateY(0);
}

}
/* ========================================================= */
/* ==================== MOBILE RESPONSIVE ==================== */
/* ========================================================= */
/* 2. ใช้ @media บังคับถมทับคลาสจาก style.css ทั้งหมด */
@media screen and (max-width: 768px) {
    .header {
        height: 65px !important;
        padding: 0 16px !important;
        display: flex !important;
    }
    
    .logo img{
        height:42px;
    }

    .nav{
        height:54px;

        background:#ffd400;

        display:flex;
        align-items:center;

        gap:18px;

        overflow-x:auto;

        padding:0 18px;

        white-space:nowrap;
    }

    .nav::-webkit-scrollbar{
        display:none;
    }

    .nav a{
        text-decoration:none;

        color:#111;

        font-size:14px;
        font-weight:700;
    }

    .nav a.active{
        color:#008c3a;
    }


    .title-zone {
        padding: 20px 16px !important;
    }

    .title-zone h1 {
        font-size: 24px !important;
    }

    .shop-name-input {
        max-width: 100% !important;
        background: #f4f4f5 !important;
        border: 1px solid #e4e4e7 !important;
        border-radius: 12px !important;
        padding: 12px !important;
        font-size: 18px !important;
        display: block !important;
    }

    .form-section {
        background: #ffffff !important; 
        padding: 10px 16px 30px !important;
    }

    .form-grid {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        gap: 24px !important;
        width: 100% !important;
    }

    .profile-image-wrap {
        order: 1 !important; 
        width: 180px !important;
        height: 180px !important;
        display: block !important;
    }

    .side-actions {
        order: 2 !important;
        flex-direction: row !important;
        width: 100% !important;
        display: flex !important;
    }

    .side-actions button {
        flex: 1 !important;
        font-size: 13px !important;
        padding: 12px !important;
    }

    .input-panel {
        order: 3 !important;
        max-width: 100% !important;
        width: 100% !important;
        display: block !important;
    }

    .input-panel input,
    .input-panel textarea {
        background: #f4f4f5 !important;
        border: none !important;
        padding: 14px !important;
        font-size: 16px !important; 
        border-radius: 14px !important;
    }

    .input-panel textarea {
        height: 100px !important;
    }

    .update-row {
        flex-direction: column !important;
        gap: 12px !important;
        width: 100% !important;
        display: flex !important;
    }

    .update-btn {
        width: 100% !important;
        padding: 14px !important;
        border-radius: 14px !important;
        font-size: 16px !important;
        text-align: center !important;
    }

    .delete-link {
        display: block !important;
        width: 100% !important;
        text-align: center !important;
        padding: 12px !important;
        background: #fef2f2 !important;
        border-radius: 14px !important;
    }

    .footer-line {
        font-size: 11px !important;
    }
}
</style>
</head>

<body>

<form method="POST" enctype="multipart/form-data">

<header class="header">
    <div class="logo">
        <img src="assets/images/logo_ok.png" alt="FreshFast">
    </div>

    <div class="profile-menu">
        <button class="profile-btn" id="profileBtn" type="button">
            <?php if (!empty($shopImage)): ?>
                <img src="<?= e($shopImage) ?>?v=<?= time() ?>" alt="profile">
            <?php else: ?>
                <div style="width:100%;height:100%;background:#16a34a;color:white;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;">
                    F
                </div>
            <?php endif; ?>
        </button>
        <div class="profile-dropdown" id="profileDropdown">
            <a href="shop_profile.php">บัญชีของฉัน</a>
            <a href="logout.php">ออกจากระบบ</a>
        </div>
    </div>
</header>

<nav class="nav">
    <a href="shop_home.php">หน้าหลัก</a>
    <a href="shop_products.php">สินค้า</a>
    <a href="shop_orders.php">รับคำสั่งซื้อ</a>
    <a href="shop_sales_history.php">ประวัติ</a>
</nav>

<section class="title-zone">
    <h1>แก้ไขร้านค้า</h1>
    <?php if(isset($_SESSION['success'])): ?>

<div class="success-message">
    <?= e($_SESSION['success']); ?>
</div>

<?php unset($_SESSION['success']); ?>

<?php endif; ?>
    <input class="shop-name-input" type="text" name="shop_name" value="<?= e($shop['shop_name'] ?? '') ?>" placeholder="กรอกชื่อร้าน" required>
</section>

<section class="form-section">
<div class="form-grid">

    <div class="side-actions">
        <button type="button" class="active">แก้ไขร้านค้า</button>
            <button
        type="button"
        onclick="location.href='shop_settings.php'">เวลาปิด-เปิดร้าน
    </button>
        <button type="button">เปลี่ยนรหัส</button>
    </div>

    <div class="input-panel">
        <label>ที่อยู่</label>
        <textarea name="shop_address" placeholder="กรอกที่อยู่ร้าน"><?= e($shop['shop_address'] ?? '') ?></textarea>
        <label>คำบรรยายร้าน</label>

        <textarea 
        name="description"
        placeholder="เช่น ร้านจำหน่ายวัตถุดิบสดใหม่ ส่งตรงจากแหล่งผลิต มีบริการจัดส่งทุกวัน"
        ><?= e($shop['description'] ?? '') ?></textarea>
        <label>ประเภทร้านค้า</label>

<select name="shop_type" required
style="
width:100%;
border:1px solid #ddd;
border-radius:12px;
padding:10px 14px;
background:white;
font-size:15px;
">

    <?php
    $types = [
        'Meat',
        'Fruits & Vegetables',
        'Seasoning',
        'Beverages',
        'Frozen',
        'Kitchen Supplies',
        'Desserts'
    ];

    foreach ($types as $type):
    ?>

        <option value="<?= e($type) ?>"
            <?= ($shop['shop_type'] ?? '') === $type ? 'selected' : '' ?>>
            <?= e($type) ?>
        </option>

    <?php endforeach; ?>

</select>
        <label>อีเมล</label>
        <input type="email" name="shop_email" value="<?= e($shop['shop_email'] ?? '') ?>" placeholder="กรอกอีเมลร้าน">

        <label>เบอร์โทร</label>
        <input type="text" name="shop_phone" value="<?= e($shop['shop_phone'] ?? '') ?>" placeholder="0xx-xxxx-xxx">

        <div class="update-row">
            <button type="submit" name="update_shop" class="update-btn">
                อัปเดตการแก้ไข
            </button>
            <a href="#" class="delete-link">
                ลบบัญชี
            </a>
        </div>
    </div>

    <div class="profile-image-wrap">
        <div class="shop-image-box">
            <?php if (!empty($shopImage)): ?>
                <img id="shopImagePreview" src="<?= e($shopImage) ?>?v=<?= time() ?>" alt="shop image">
            <?php else: ?>
                <div class="empty-profile" id="emptyProfile">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                </div>
                <img id="shopImagePreview" style="display:none;" alt="shop image">
            <?php endif; ?>
        </div>
        <label for="shop_image" class="image-upload-label">✎</label>
        <input class="hidden-file" type="file" id="shop_image" name="shop_image" accept="image/*">
    </div>

</div>
</section>

<footer class="footer">
    <div class="footer-line">
        คำถามที่พบบ่อย&nbsp;&nbsp; ติดต่อเรา&nbsp;&nbsp; ประกาศความเป็นส่วนตัว&nbsp;&nbsp;
        นโยบายการใช้คุกกี้&nbsp;&nbsp; ข้อกำหนดและเงื่อนไข
        <small>ลิขสิทธิ์ © 2025-2026 FreshFast สงวนลิขสิทธิ์.</small>
    </div>
</footer>

</form>

<script>
const shopImageInput = document.getElementById("shop_image");
const shopImagePreview = document.getElementById("shopImagePreview");
const emptyProfile = document.getElementById("emptyProfile");

shopImageInput.addEventListener("change", function () {
    const file = this.files[0];
    if (file) {
        shopImagePreview.src = URL.createObjectURL(file);
        shopImagePreview.style.display = "block";
        if(emptyProfile){
            emptyProfile.style.display = "none";
        }
    }
});

const profileBtn = document.getElementById("profileBtn");
const profileDropdown = document.getElementById("profileDropdown");

profileBtn?.addEventListener("click", (e) => {
    e.stopPropagation();
    profileDropdown.classList.toggle("show");
});

document.addEventListener("click", () => {
    profileDropdown?.classList.remove("show");
});
</script>
<script>
window.addEventListener('load', function() {
    // ฟังก์ชันเช็กและบังคับ
    function checkAndFixLayout() {
        if (window.innerWidth <= 768) {
            const grid = document.querySelector('.form-grid');
            if (grid) {
                grid.style.display = 'flex';
                grid.style.flexDirection = 'column';
                console.log("Layout Fixed after fully loaded");
            }
        }
    }

    // รันทันทีที่โหลดเสร็จ
    checkAndFixLayout();
    
    // เผื่อไว้อีก 500ms เผื่อมี CSS อื่นโหลดมาช้ากว่า
    setTimeout(checkAndFixLayout, 500);
});
</script>
</body>
</html>