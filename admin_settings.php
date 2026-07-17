<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

/* =========================
   CREATE SETTINGS TABLE
========================= */
$conn->query("
    CREATE TABLE IF NOT EXISTS system_settings (
        setting_id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value VARCHAR(50) NOT NULL DEFAULT '1',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$defaultSettings = [
    'dark_mode' => '1',
    'order_notification' => '1',
    'complaint_notification' => '1',
    'new_shop_notification' => '1'
];

foreach ($defaultSettings as $key => $value) {
    $stmt = $conn->prepare("
        INSERT IGNORE INTO system_settings (setting_key, setting_value)
        VALUES (?, ?)
    ");
    $stmt->bind_param("ss", $key, $value);
    $stmt->execute();
}

/* =========================
   UPDATE SETTING
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = $_POST['setting_key'] ?? '';
    $value = $_POST['setting_value'] ?? '0';

    if (array_key_exists($key, $defaultSettings)) {
        $stmt = $conn->prepare("
            UPDATE system_settings
            SET setting_value = ?
            WHERE setting_key = ?
        ");
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();
    }

    header("Location: admin_settings.php");
    exit;
}

/* =========================
   GET SETTINGS
========================= */
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM system_settings");

while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

function isOn($settings, $key) {
    return isset($settings[$key]) && $settings[$key] == '1';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ตั้งค่าระบบ | FreshFast</title>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
* {
    box-sizing: border-box;
    font-family:sans-serif;
}

body {
    margin: 0;
    background: #f7f7f7;
    color: #000;
}

.layout {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 252px;
    background: #98efb8;
    border-right: 1px solid #111;
    padding: 28px 14px;
}

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 22px;
    font-weight: 700;
    color: #07883b;
    margin-bottom: 90px;
}

.logo .cart {
    font-size: 48px;
}

.menu a {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 24px;
    margin-bottom: 18px;
    border-radius: 20px;
    color: #000;
    text-decoration: none;
    font-weight: 600;
    font-size: 17px;
}

.menu a.active {
    background: #fff1b3;
    box-shadow: 0 4px 8px rgba(0,0,0,.16);
}

.menu .icon {
    font-size: 26px;
    width: 30px;
}

.main {
    flex: 1;
}

.topbar {
    height: 103px;
    background: #98efb8;
    border-bottom: 1px solid #111;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 48px 0 42px;
}

.topbar h1 {
    font-size: 25px;
    margin: 0;
}

.top-icons {
    display: flex;
    gap: 38px;
}

.circle-btn {
    width: 62px;
    height: 62px;
    background: #fff;
    border-radius: 50%;
    display: grid;
    place-items: center;
    font-size: 28px;
    box-shadow: 0 3px 8px rgba(0,0,0,.2);
}

.content {
    padding: 22px 30px;
}

.setting-list {
    display: flex;
    flex-direction: column;
    gap: 31px;
}

.setting-card {
    height: 72px;
    background: #fff1b3;
    border-radius: 18px;
    box-shadow: 0 3px 8px rgba(0,0,0,.18);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 50px 0 30px;
}

.setting-card h2 {
    margin: 0;
    font-size: 25px;
    font-weight: 700;
}

.switch-form {
    margin: 0;
}

.switch {
    position: relative;
    display: inline-block;
    width: 56px;
    height: 28px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background: #ccc;
    border-radius: 50px;
    transition: .25s;
}

.slider:before {
    content: "";
    position: absolute;
    height: 26px;
    width: 26px;
    left: 1px;
    top: 1px;
    background: white;
    border-radius: 50%;
    transition: .25s;
    box-shadow: 0 1px 3px rgba(0,0,0,.25);
}

.switch input:checked + .slider {
    background: #f5c400;
}

.switch input:checked + .slider:before {
    transform: translateX(28px);
}
.circle-btn i{
    width:34px;
    height:34px;
    stroke-width:2.2;
}
.sidebar{
    width:250px;
    background:#96efb6;
    padding:28px 15px;
    border-right:1px solid #111;
}

.logo{
    width:190px;
    margin-bottom:80px;
}

.menu{
    display:flex;
    flex-direction:column;
    gap:20px;
}

.menu a{
    display:flex;
    align-items:center;
    gap:16px;
    padding:15px 24px;
    border-radius:14px;
    text-decoration:none;
    color:#111;
    font-size:18px;
    font-weight:600;
    margin-bottom:0;
}

.menu a.active{
    background:#fff1b3;
    box-shadow:0 4px 8px rgba(0,0,0,.12);
}

.menu .icon{
    width:28px;
    text-align:center;
    font-size:24px;
}

@media(max-width: 900px) {
    .sidebar {
        width: 210px;
    }

    .setting-card {
        padding: 0 24px;
    }

    .setting-card h2 {
        font-size: 20px;
    }
}
</style>
</head>

<body>

<div class="layout">
<?php include "includes/admin_sideber.php"; ?>

<main class="main">

<header class="topbar">
    <h1>ตั้งค่าระบบ</h1>
<div class="top-icons">
    <div class="circle-btn">
        <i data-lucide="bell"></i>
    </div>

    <div class="circle-btn">
        <i data-lucide="settings"></i>
    </div>
</div>
</header>

<section class="content">

    <div class="setting-list">

        <div class="setting-card">
            <h2>โหมดมืด</h2>

            <form method="POST" class="switch-form">
                <input type="hidden" name="setting_key" value="dark_mode">
                <input type="hidden" name="setting_value" value="<?= isOn($settings, 'dark_mode') ? '0' : '1' ?>">

                <label class="switch">
                    <input type="checkbox" onchange="this.form.submit()" <?= isOn($settings, 'dark_mode') ? 'checked' : '' ?>>
                    <span class="slider"></span>
                </label>
            </form>
        </div>

        <div class="setting-card">
            <h2>การแจ้งเตือนคำสั่งซื้อ</h2>

            <form method="POST" class="switch-form">
                <input type="hidden" name="setting_key" value="order_notification">
                <input type="hidden" name="setting_value" value="<?= isOn($settings, 'order_notification') ? '0' : '1' ?>">

                <label class="switch">
                    <input type="checkbox" onchange="this.form.submit()" <?= isOn($settings, 'order_notification') ? 'checked' : '' ?>>
                    <span class="slider"></span>
                </label>
            </form>
        </div>

        <div class="setting-card">
            <h2>การแจ้งเตือนการร้องเรียน</h2>

            <form method="POST" class="switch-form">
                <input type="hidden" name="setting_key" value="complaint_notification">
                <input type="hidden" name="setting_value" value="<?= isOn($settings, 'complaint_notification') ? '0' : '1' ?>">

                <label class="switch">
                    <input type="checkbox" onchange="this.form.submit()" <?= isOn($settings, 'complaint_notification') ? 'checked' : '' ?>>
                    <span class="slider"></span>
                </label>
            </form>
        </div>

        <div class="setting-card">
            <h2>การแจ้งเตือนร้านค้าใหม่</h2>

            <form method="POST" class="switch-form">
                <input type="hidden" name="setting_key" value="new_shop_notification">
                <input type="hidden" name="setting_value" value="<?= isOn($settings, 'new_shop_notification') ? '0' : '1' ?>">

                <label class="switch">
                    <input type="checkbox" onchange="this.form.submit()" <?= isOn($settings, 'new_shop_notification') ? 'checked' : '' ?>>
                    <span class="slider"></span>
                </label>
            </form>
        </div>

    </div>

</section>

</main>

</div>
<script src="https://unpkg.com/lucide@latest"></script>

<script>
lucide.createIcons();
</script>
</body>
</html>