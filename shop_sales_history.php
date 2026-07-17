<?php
session_start();
require_once "db.php";

if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "freshfast");
    $conn->set_charset("utf8mb4");
}

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
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
$month = $_GET['month'] ?? date("Y-m");

$whereMonth = "";
$params = [$shopName];
$types = "s";

if ($month !== '') {
    $whereMonth = "AND DATE_FORMAT(o.created_at, '%Y-%m') = ?";
    $params[] = $month;
    $types .= "s";
}

$sql = "
    SELECT 
        o.order_id,
        o.created_at,
        o.status,
        SUM(oi.line_total) AS shop_total,
        COUNT(oi.order_item_id) AS item_count
    FROM orders o
    INNER JOIN order_items oi ON o.order_id = oi.order_id
    WHERE oi.shop_name = ?
    AND o.status IN ('accepted','delivering','completed')
    $whereMonth
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();

$totalSales = 0;
$topProducts = [];

$sqlTopProducts = "
SELECT
    oi.product_name,
    SUM(oi.quantity) AS total_qty
FROM order_items oi
INNER JOIN orders o ON oi.order_id = o.order_id
WHERE oi.shop_name = ?
AND o.status IN ('accepted','delivering','completed')
GROUP BY oi.product_name
ORDER BY total_qty DESC
LIMIT 3
";

$stmtTop = $conn->prepare($sqlTopProducts);
$stmtTop->bind_param("s", $shopName);
$stmtTop->execute();

$resTop = $stmtTop->get_result();

while($row = $resTop->fetch_assoc()){
    $topProducts[] = $row;
}
$totalOrders = 0;
$rows = [];

while ($row = $orders->fetch_assoc()) {
    $rows[] = $row;
    $totalSales += (float)$row['shop_total'];
    $totalOrders++;
}

function statusThai($status) {
    switch ($status) {
        case 'accepted':
            return 'กำลังจัดเตรียม';
        case 'delivering':
            return 'กำลังจัดส่ง';
        case 'completed':
            return 'จัดส่งสำเร็จ';
        case 'cancelled':
            return 'ยกเลิก';
        default:
            return 'รอดำเนินการ';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ประวัติการขาย | FreshFast</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;font-family:Arial,sans-serif;}
body{margin:0;background:#eef7f0;color:#000}
.header{height:92px;background:#fff1b3;display:flex;align-items:center;justify-content:space-between;padding:0 28px}
.logo{display:flex;align-items:center;gap:10px;color:#008c3a;font-size:18px;font-weight:700}
.logo .cart{font-size:48px}
.profile-btn{width:48px;height:48px;border-radius:50%;background:#009536;color:white;display:grid;place-items:center;font-size:28px;text-decoration:none;box-shadow:0 3px 7px rgba(0,0,0,.25)}
.nav{height:30px;background:#f2c300;display:flex;align-items:center;justify-content:center;gap:64px}
.nav a{text-decoration:none;color:#fff;font-weight:700}
.nav a.active{color:#008c3a}
.title-zone{text-align:center;padding:42px 0 20px}
.title-zone h1{font-size:22px;margin:0 0 14px}
.filter-form input{width:320px;height:24px;border:none;border-radius:12px;padding:0 14px;box-shadow:0 2px 5px rgba(0,0,0,.18)}
.summary{display:flex;justify-content:center;gap:24px;margin:0 0 24px}
.summary-card{background:#f8df73;border-radius:16px;padding:14px 28px;font-weight:700;box-shadow:0 3px 8px rgba(0,0,0,.14)}

.history-section{background:#98efb8;padding:20px 50px 64px;}
.history-row{display:grid;grid-template-columns:1fr 220px;align-items:center;padding:28px 36px;border-bottom:1px solid rgba(0,0,0,.35)}
.order-no{font-weight:700}
.order-no.cancel{color:red}
.date{margin-top:8px;font-weight:500}
.amount{text-align:right;font-size:20px;font-weight:700}
.status{font-size:14px;margin-top:6px;color:#006b2d;font-weight:700}
.empty{text-align:center;font-size:24px;font-weight:700;padding:120px 0}
.footer{background:#eef7f0;;padding:55px 40px 40px;text-align:center;font-weight:700}
.footer-line{border-top:1px solid #111;padding-top:22px}
.footer small{display:block;margin-top:18px;color:#777}
@media(max-width:900px){
    .history-section{padding:20px}
    .history-row{grid-template-columns:1fr;padding:22px 8px}
    .amount{text-align:left;margin-top:14px}
    .summary{flex-direction:column;align-items:center}
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


.history-row{
    display:grid;
    grid-template-columns:1fr 220px;
    align-items:center;
    padding:28px 36px;
    border-bottom:1px solid rgba(0,0,0,.35);
    text-decoration:none;
    color:#000;
}

.history-row:hover{
    background:#b8f3cb;
}
/* ==========================
   RESPONSIVE FIX
========================== */

@media(max-width:768px){
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

    body{
        overflow-x:hidden;
    }

    /* HEADER */
    .header{
        height:70px;
        padding:0 16px;
    }

    .logo img{
        height:42px;
    }

    .profile-btn{
        width:42px;
        height:42px;
    }


    /* NAV */
    /* .nav{
        height:54px;
        display:flex;
        justify-content:flex-start;
        gap:24px;

        overflow-x:auto;
        padding:0 18px;

        white-space:nowrap;
    }

    .nav::-webkit-scrollbar{
        display:none;
    }

    .nav a{
        font-size:14px;
    } */


    /* TITLE */
    .title-zone{
        padding:28px 16px 20px;
    }

    .title-zone h1{
        font-size:20px;
    }


    /* DATE FILTER */
    .filter-form input{

        width:100%;
        max-width:350px;

        height:42px;

        border-radius:14px;

        font-size:15px;
    }



    /* SUMMARY */

    .summary{

        flex-direction:column;

        align-items:center;

        gap:14px;

        padding:0 16px;
    }


    .summary-card{

        width:100%;
        max-width:350px;

        text-align:center;

        padding:16px;

    }



    /* HISTORY */

    .history-section{

        padding:20px 16px 40px;

    }


    .history-row{

        display:flex;

        flex-direction:column;

        align-items:flex-start;

        gap:12px;


        padding:20px;

        border-radius:18px;

        margin-bottom:14px;

        background:#98efb8;

    }


    .order-no{

        font-size:15px;

    }


    .date{

        font-size:14px;

    }


    .status{

        font-size:14px;

    }


    .amount{

        width:100%;

        text-align:left;

        font-size:18px;

        padding-top:10px;

        border-top:1px solid rgba(0,0,0,.2);

    }



    /* EMPTY */

    .empty{

        font-size:20px;

        padding:80px 20px;

    }



    /* FOOTER */

    .footer{

        padding:40px 16px 30px;

        font-size:12px;

        line-height:1.8;

    }

}




@media(max-width:480px){

    .history-row{

        padding:18px;

    }


    .order-no{

        font-size:14px;

    }


    .amount{

        font-size:16px;

    }


    .summary-card{

        font-size:14px;

    }

    /* NAV */
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
    <a href="shop_orders.php">รับคำสั่งซื้อ</a>
    <a href="shop_sales_history.php" class="active">ประวัติ</a>
</nav>

<section class="title-zone">
    <h1>ประวัติการขาย</h1>

    <form method="GET" class="filter-form">
        <input type="month" name="month" value="<?= e($month) ?>" onchange="this.form.submit()">
    </form>
</section>

<!-- <div class="summary">
    <div class="summary-card">จำนวนออเดอร์ <?= number_format($totalOrders) ?> รายการ</div>
    <div class="summary-card">ยอดขายรวม <?= number_format($totalSales, 2) ?> บาท</div>
</div> -->
<div class="summary">

    <div class="summary-card">
        จำนวนออเดอร์ <?= number_format($totalOrders) ?> รายการ
    </div>

    <div class="summary-card">
        ยอดขายรวม <?= number_format($totalSales, 2) ?> บาท
    </div>

    <?php foreach($topProducts as $index => $product): ?>

        <div class="summary-card">

            <?php
            $medals = ['🥇','🥈','🥉'];
            echo $medals[$index];
            ?>

            <?= e($product['product_name']) ?>

            <br>

            <small>
                ขายได้ <?= number_format($product['total_qty']) ?> ชิ้น
            </small>

        </div>

    <?php endforeach; ?>

</div>
<section class="history-section">

<?php if (count($rows) > 0): ?>

        <?php foreach ($rows as $row): ?>
        <a class="history-row"
        href="shop_order_history_detail.php?order_id=<?= $row['order_id'] ?>">
            <div>
                <div class="order-no <?= $row['status'] === 'cancelled' ? 'cancel' : '' ?>">
                    #หมายเลขคำสั่งซื้อ <?= e($row['order_id']) ?>
                </div>

                <div class="date">
                    <?= date("d / m / Y เวลา H:i น.", strtotime($row['created_at'])) ?>
                </div>

                <div class="status">
                    <?= e(statusThai($row['status'])) ?> · <?= number_format((int)$row['item_count']) ?> รายการ
                </div>
            </div>

            <div class="amount">
                ราคารวม <?= number_format((float)$row['shop_total'], 2) ?> บาท
            </div>
        <a>
    <?php endforeach; ?>

<?php else: ?>

    <div class="empty">ยังไม่มีประวัติการขายในเดือนนี้</div>

<?php endif; ?>

</section>

<footer class="footer">
    <div class="footer-line">
        คำถามที่พบบ่อย ติดต่อเรา ประกาศความเป็นส่วนตัว สำหรับลูกค้า นโยบายการใช้คุกกี้
        การตั้งค่าคุกกี้ ข้อกำหนดและเงื่อนไขนโยบายการคุ้มครองข้อมูลส่วนบุคคล ลงทะเบียน
        <small>ลิขสิทธิ์ © 2025-2026 FreshFast สงวนลิขสิทธิ์.</small>
    </div>
</footer>
<script>
const profileBtn = document.getElementById("profileBtn");
const profileDropdown = document.getElementById("profileDropdown");

profileBtn?.addEventListener("click", (e) => {
    e.stopPropagation();
    profileDropdown.classList.toggle("show");
});

document.addEventListener("click", () => {
    profileDropdown?.classList.remove("show");
});

profileDropdown?.addEventListener("click", (e) => {
    e.stopPropagation();
});
</script>
</body>
</html>