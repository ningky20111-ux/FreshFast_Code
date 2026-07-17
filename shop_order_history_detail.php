<?php
session_start();
require_once "db.php";

if (!isset($conn)) {
    
    $conn->set_charset("utf8mb4");
}

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function statusThai($status) {
    switch ($status) {
        case 'pending': return 'รอรับคำสั่งซื้อ';
        case 'accepted': return 'กำลังจัดเตรียม';
        case 'delivering': return 'กำลังจัดส่ง';
        case 'completed': return 'จัดส่งสำเร็จ';
        case 'cancelled': return 'ยกเลิกคำสั่งซื้อ';
        case 'waiting_rider': return 'รอไรเดอร์รับงาน';
        default: return $status;
    }
}
$shopId = $_SESSION['shop_id'] ?? 0;

if ($shopId <= 0) {
    header("Location: shop_login.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT *
    FROM shops
    WHERE shop_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $shopId);
$stmt->execute();
$shop = $stmt->get_result()->fetch_assoc();

if (!$shop) {
    die("ไม่พบร้านค้า");
}

$shopName = $shop['shop_name'];
$shopImage = "";

if (
    !empty($shop['shop_image']) &&
    file_exists($shop['shop_image'])
) {
    $shopImage = $shop['shop_image'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($orderId > 0) {
        $check = $conn->prepare("
            SELECT o.order_id
            FROM orders o
            INNER JOIN order_items oi ON o.order_id = oi.order_id
            WHERE o.order_id = ?
            AND oi.shop_name = ?
            LIMIT 1
        ");
        $check->bind_param("is", $orderId, $shopName);
        $check->execute();
        $found = $check->get_result()->fetch_assoc();

        if ($found) {
            if ($action === 'prepare') {
                $newStatus = 'accepted';
            } elseif ($action === 'cancel') {
                $newStatus = 'cancelled';
            } elseif ($action === 'ready') {
                $newStatus = 'waiting_rider';
            } else {
                $newStatus = null;
            }

            if ($newStatus) {
                $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
                $stmt->bind_param("si", $newStatus, $orderId);
                $stmt->execute();
            }
        }
    }

    header("Location: shop_orders.php?order_id=" . $orderId);
    exit;
}

$selectedOrderId = (int)($_GET['order_id'] ?? 0);

$stmt = $conn->prepare("
    SELECT 
        o.order_id,
        o.receiver_name,
        o.phone,
        o.delivery_address,
        o.postal_code,
        o.note,
        o.status,
        o.subtotal,
        o.delivery_fee,
        o.discount,
        o.total_amount,
        o.created_at,
        SUM(oi.line_total) AS shop_total,
        COUNT(oi.order_item_id) AS item_count
    FROM orders o
    INNER JOIN order_items oi ON o.order_id = oi.order_id
    WHERE oi.shop_name = ?
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
");
$stmt->bind_param("s", $shopName);
$stmt->execute();
$orders = $stmt->get_result();

if ($selectedOrderId <= 0 && $orders->num_rows > 0) {
    $first = $orders->fetch_assoc();
    $selectedOrderId = (int)$first['order_id'];
    $orders->data_seek(0);
}

$selectedOrder = null;
$items = null;

if ($selectedOrderId > 0) {
    $stmt = $conn->prepare("
        SELECT 
            o.order_id,
            o.receiver_name,
            o.phone,
            o.delivery_address,
            o.postal_code,
            o.note,
            o.status,
            o.subtotal,
            o.delivery_fee,
            o.discount,
            o.total_amount,
            o.created_at,
            SUM(oi.line_total) AS shop_total,
            COUNT(oi.order_item_id) AS item_count
        FROM orders o
        INNER JOIN order_items oi ON o.order_id = oi.order_id
        WHERE o.order_id = ?
        AND oi.shop_name = ?
        GROUP BY o.order_id
        LIMIT 1
    ");
    $stmt->bind_param("is", $selectedOrderId, $shopName);
    $stmt->execute();
    $selectedOrder = $stmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare("
        SELECT *
        FROM order_items
        WHERE order_id = ?
        AND shop_name = ?
        ORDER BY order_item_id ASC
    ");
    $stmt->bind_param("is", $selectedOrderId, $shopName);
    $stmt->execute();
    $items = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>รับคำสั่งซื้อ | FreshFast</title>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{
    box-sizing:border-box;
    font-family:Arial,sans-serif;
}

body{
    margin:0;
    background:#eef7f0;
    color:#000;
}
.header{
    height:84px;
    background:#fff;

    display:flex;
    align-items:center;
    justify-content:space-between;

    padding:0 28px;

    box-shadow:0 2px 10px rgba(0,0,0,.04);
}

.logo img{
    height:54px;
    display:block;
}

/* PROFILE */

.profile-menu{
    position:relative;
}

.profile-btn{
    width:48px;
    height:48px;

    border:none;
    padding:0;

    border-radius:50%;
    overflow:hidden;

    cursor:pointer;

    background:none;

    box-shadow:0 4px 12px rgba(0,0,0,.15);
}

.profile-btn img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.profile-dropdown{
    position:absolute;

    top:58px;
    right:0;

    width:190px;

    background:#fff;

    border-radius:18px;

    box-shadow:0 14px 35px rgba(0,0,0,.12);

    padding:8px;

    display:none;

    z-index:999;
}

.profile-dropdown.show{
    display:block;
}

.profile-dropdown a{
    display:block;

    padding:12px 14px;

    border-radius:12px;

    text-decoration:none;

    color:#111;

    font-weight:700;

    transition:.2s;
}

.profile-dropdown a:hover{
    background:#f5f5f5;
}

/* NAV */

.nav{
    height:54px;
    background:#ffd400;

    display:flex;
    align-items:center;
    justify-content:center;

    gap:54px;

    box-shadow:0 2px 6px rgba(0,0,0,.04);
}

.nav a{
    text-decoration:none;
    color:#111;
    font-weight:700;
    font-size:15px;

    transition:.2s;
}

.nav a:hover{
    transform:translateY(-1px);
}

.nav a.active{
    color:#008c3a;
}
.logo{display:flex;align-items:center;gap:10px;color:#008c3a;font-size:18px;font-weight:700}
.logo .cart{font-size:48px}
.profile-btn{width:48px;height:48px;border-radius:50%;background:#009536;color:white;display:grid;place-items:center;font-size:28px;text-decoration:none;box-shadow:0 3px 7px rgba(0,0,0,.25)}

.nav a.active{color:#008c3a}
.page-title{text-align:center;font-size:22px;font-weight:700;margin:46px 0}
.main-wrap{display:grid;grid-template-columns:1.6fr 1fr;gap:72px;padding:0 46px 52px}
.order-card{background:#fff;border-radius:18px;overflow:hidden}
.order-head{background:#f2c91b;padding:24px 50px}
.order-head p{margin:0 0 8px;font-weight:500}
.order-head h2{margin:0;font-size:17px}
.receiver{padding:18px 26px 8px;font-weight:700;line-height:1.9}
.order-table{width:100%;border-collapse:collapse;margin-top:12px}
.order-table th,.order-table td{padding:18px 42px;border-top:1px solid #999;text-align:left;font-weight:700}
.order-table th{font-size:14px;font-weight:500}
.order-table .qty{color:#009536;text-align:center}
.payment-card{background:#fff;border-radius:18px;overflow:hidden;height:max-content}
.payment-head{background:#009536;color:white;padding:20px 48px;font-weight:700}
.payment-body{padding:28px 28px}
.pay-row{display:flex;justify-content:space-between;margin-bottom:18px;font-weight:700}
.line{border-top:1px solid #aaa;margin:20px 0}
.actions{display:flex;justify-content:center;gap:18px;margin-top:28px}
.btn{border:none;border-radius:16px;padding:9px 18px;font-weight:700;cursor:pointer}
.btn-red{background:red;color:white}
.btn-yellow{background:#f2c300;color:#000}
.btn-green{background:#009536;color:white}
.list-zone{padding:0 46px 50px}
.list-title{text-align:center;font-size:22px;font-weight:700;margin-bottom:20px}
.order-list{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
.order-mini{background:#f8df73;border-radius:16px;padding:16px;text-decoration:none;color:#000;box-shadow:0 3px 8px rgba(0,0,0,.16)}
.order-mini.active{outline:4px solid #009536}
.order-mini h3{margin:0 0 8px}
.order-mini p{margin:4px 0;font-weight:600}
.empty{text-align:center;font-size:22px;font-weight:700;padding:80px}
.footer{padding:20px 40px 40px;text-align:center;font-weight:700}
.footer-line{border-top:1px solid #111;padding-top:22px}
.footer small{display:block;margin-top:18px;color:#777}
@media(max-width:900px){
    .main-wrap{grid-template-columns:1fr;padding:0 20px 40px;gap:28px}
    .order-list{grid-template-columns:1fr}
}
</style>
</head>

<body>

<header class="header">

    <div class="logo">
    <img src="assets/images/logo_ok.png" alt="FreshFast">
    </div>
    <div class="profile-menu">

        <button class="profile-btn" id="profileBtn">
        <?php if (!empty($shopImage)): ?>
            <img src="<?= e($shopImage) ?>?v=<?= time() ?>" alt="profile">
        <?php else: ?>
            <div style="
                width:100%;
                height:100%;
                background:#16a34a;
                color:white;
                display:flex;
                align-items:center;
                justify-content:center;
                font-size:20px;
                font-weight:700;
            ">

                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M4 10L5.5 4H18.5L20 10" 
                            stroke="currentColor" 
                            stroke-width="1.7" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"/>

                        <path d="M5 10V18C5 18.5523 5.44772 19 6 19H18C18.5523 19 19 18.5523 19 18V10" 
                            stroke="currentColor" 
                            stroke-width="1.7"/>

                        <path d="M9 19V14H15V19" 
                            stroke="currentColor" 
                            stroke-width="1.7"/>

                        <path d="M3 10H21" 
                            stroke="currentColor" 
                            stroke-width="1.7"/>
                    </svg>

            
            </div>
        <?php endif; ?>
        </button>

        <div class="profile-dropdown" id="profileDropdown">

            <a href=" shop_profile.php">
                บัญชีของฉัน
            </a>

            <a href="logout.php">
                ออกจากระบบ
            </a>

        </div>

    </div>
</header>

<nav class="nav">
    <a href="shop_home.php">หน้าหลัก</a>
    <a href="shop_products.php">สินค้า</a>
    <a href="shop_orders.php" class="active">รับคำสั่งซื้อ</a>
    <a href="shop_sales_history.php">ประวัติ</a>
</nav>

<div class="page-title">ข้อมูลคำสั่งซื้อ</div>

<?php if (!$selectedOrder): ?>

<div class="empty">ยังไม่มีคำสั่งซื้อของร้านนี้</div>

<?php else: ?>

<section class="main-wrap">

    <div class="order-card">
        <div class="order-head">
            <p>หมายเลขคำสั่งซื้อ</p>
            <h2>#<?= e($selectedOrder['order_id']) ?></h2>
        </div>

        <div class="receiver">
            ชื่อผู้รับ: <?= e($selectedOrder['receiver_name']) ?><br>
            เบอร์โทรศัพท์: <?= e($selectedOrder['phone']) ?><br>
            ที่อยู่: <?= e($selectedOrder['delivery_address']) ?><br>
            หมายเหตุ: <?= e($selectedOrder['note'] ?: '-') ?><br>
            รหัสไปรษณีย์: <?= e($selectedOrder['postal_code'] ?: '-') ?><br>
            สถานะ: <?= e(statusThai($selectedOrder['status'])) ?>
        </div>

        <table class="order-table">
            <thead>
                <tr>
                    <th>รายการสั่งซื้อ</th>
                    <th>ราคา</th>
                    <th>จำนวน</th>
                    <th>รวม</th>
                </tr>
            </thead>
            <tbody>
            <?php while($item = $items->fetch_assoc()): ?>
                <tr>
                    <td><?= e($item['product_name']) ?></td>
                    <td><?= number_format((float)$item['price'], 2) ?> บ.</td>
                    <td class="qty"><?= number_format((int)$item['quantity']) ?></td>
                    <td><?= number_format((float)$item['line_total'], 2) ?> บ.</td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div>
        <div class="payment-card">
            <div class="payment-head">ข้อมูลการชำระเงิน</div>

            <div class="payment-body">
                <div class="pay-row">
                    <span>รายการสั่งซื้อรวม</span>
                    <span><?= number_format((int)$selectedOrder['item_count']) ?> รายการ</span>
                </div>

                <div class="pay-row">
                    <span>ราคาร้านนี้</span>
                    <span><?= number_format((float)$selectedOrder['shop_total'], 2) ?> บ.</span>
                </div>

                <div class="pay-row">
                    <span>ค่าส่ง</span>
                    <span><?= number_format((float)$selectedOrder['delivery_fee'], 2) ?> บ.</span>
                </div>

                <div class="pay-row">
                    <span>ส่วนลด</span>
                    <span>-<?= number_format((float)$selectedOrder['discount'], 2) ?> บ.</span>
                </div>

                <div class="line"></div>

                <div class="pay-row">
                    <span>รวมทั้งหมด</span>
                    <span><?= number_format((float)$selectedOrder['total_amount'], 2) ?> บ.</span>
                </div>
            </div>
        </div>

    <form method="POST" class="actions">
        <input type="hidden" name="order_id" value="<?= e($selectedOrder['order_id']) ?>">

        <?php if ($selectedOrder['status'] === 'pending'): ?>

            <button class="btn btn-red" name="action" value="cancel" type="submit">
                ยกเลิกคำสั่งซื้อ
            </button>

            <button class="btn btn-yellow" name="action" value="prepare" type="submit">
                เริ่มจัดเตรียม
            </button>

        <?php elseif ($selectedOrder['status'] === 'accepted'): ?>

            <button class="btn btn-green" name="action" value="ready" type="submit">
                พร้อมจัดส่ง
            </button>

        <?php endif; ?>
    </form>
    </div>

</section>

<section class="list-zone">
    <div class="list-title">คำสั่งซื้อของร้าน</div>

    <div class="order-list">
        <?php while($o = $orders->fetch_assoc()): ?>
            <a class="order-mini <?= (int)$o['order_id'] === $selectedOrderId ? 'active' : '' ?>"
               href="shop_orders.php?order_id=<?= e($o['order_id']) ?>">
                <h3>#<?= e($o['order_id']) ?></h3>
                <p><?= e(statusThai($o['status'])) ?></p>
                <p><?= number_format((float)$o['shop_total'], 2) ?> บาท</p>
                <p><?= date("d/m/Y H:i", strtotime($o['created_at'])) ?></p>
            </a>
        <?php endwhile; ?>
    </div>
</section>

<?php endif; ?>

<footer class="footer">
    <div class="footer-line">
        คำถามที่พบบ่อย ติดต่อเรา ประกาศความเป็นส่วนตัว สำหรับลูกค้า นโยบายการใช้คุกกี้
        การตั้งค่าคุกกี้ ข้อกำหนดและเงื่อนไขนโยบายการคุ้มครองข้อมูลส่วนบุคคล ลงทะเบียน
        <small>ลิขสิทธิ์ © 2025-2026 FreshFast สงวนลิขสิทธิ์.</small>
    </div>
</footer>
<script>
const shopImageInput = document.getElementById("shop_image");
const shopImagePreview = document.getElementById("shopImagePreview");
const emptyProfile = document.getElementById("emptyProfile");

shopImageInput.addEventListener("change", function () {

    const file = this.files[0];

    if (file) {

        shopImagePreview.src = URL.createObjectURL(file);
        shopImagePreview.style.display = "block";

        if (emptyProfile) {
            emptyProfile.style.display = "none";
        }
    }
});
</script>
</body>
</html>