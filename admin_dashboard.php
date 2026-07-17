<?php
session_start();
require_once "db.php";

function e($v){
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function oneValue($conn, $sql, $default = 0){
    $res = $conn->query($sql);
    if ($res && $row = $res->fetch_row()) {
        return $row[0] ?? $default;
    }
    return $default;
}

$shopCount = oneValue($conn, "SELECT COUNT(*) FROM shops");
$userCount = oneValue($conn, "SELECT COUNT(*) FROM users");
$orderCount = oneValue($conn, "SELECT COUNT(*) FROM orders");

$todaySales = oneValue($conn, "
    SELECT COALESCE(SUM(total_amount),0)
    FROM orders
    WHERE DATE(created_at) = CURDATE()
");

$deliveringCount = oneValue($conn, "SELECT COUNT(*) FROM orders WHERE status='delivering'");
$completedCount = oneValue($conn, "SELECT COUNT(*) FROM orders WHERE status='completed'");
$cancelledCount = 0;

$topShops = [];
$sqlTopShops = "
    SELECT 
        oi.shop_name,
        SUM(oi.line_total) AS total_sales
    FROM order_items oi
    GROUP BY oi.shop_name
    ORDER BY total_sales DESC
    LIMIT 3
";
$resTop = $conn->query($sqlTopShops);
if ($resTop) {
    while($row = $resTop->fetch_assoc()){
        $topShops[] = $row;
    }
}

$topCategories = [];
$promotions = [];

$sqlPromotion = "
    SELECT 
        promotion_id,
        title,
        start_date,
        end_date,
        status
    FROM promotions
    WHERE status = 'active'
    ORDER BY start_date ASC
    LIMIT 3
";

$resPromotion = $conn->query($sqlPromotion);

if ($resPromotion) {
    while($row = $resPromotion->fetch_assoc()){
        $promotions[] = $row;
    }
}
$sqlTopCats = "
    SELECT 
        c.category_name,
        SUM(oi.quantity) AS total_qty
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    JOIN categories c ON p.category_id = c.category_id
    GROUP BY c.category_id, c.category_name
    ORDER BY total_qty DESC
    LIMIT 5
";
$resCat = $conn->query($sqlTopCats);
if ($resCat) {
    while($row = $resCat->fetch_assoc()){
        $topCategories[] = $row;
    }
}
$thaiMonths = [
    1 => "มกราคม",
    2 => "กุมภาพันธ์",
    3 => "มีนาคม",
    4 => "เมษายน",
    5 => "พฤษภาคม",
    6 => "มิถุนายน",
    7 => "กรกฎาคม",
    8 => "สิงหาคม",
    9 => "กันยายน",
    10 => "ตุลาคม",
    11 => "พฤศจิกายน",
    12 => "ธันวาคม"
];

$currentDay = date("d");
$currentMonth = $thaiMonths[(int)date("m")];
$thaiCat = [
    'Meat' => 'เนื้อสัตว์',
    'Fruits & Vegetables' => 'ผักผลไม้',
    'Seasoning' => 'เครื่องปรุง',
    'Beverages' => 'เครื่องดื่ม',
    'Frozen' => 'แช่แข็ง',
    'Kitchen Supplies' => 'ของใช้ในครัว',
    'Desserts' => 'ของหวาน'
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - FreshFast</title>
<style>
*{
    box-sizing:border-box;
    font-family:sans-serif;
}
.top-icons{
    display:flex;
    gap:38px;
}

.top-icon{
    width:62px;
    height:62px;
    background:#fff;
    border-radius:50%;
    display:grid;
    place-items:center;
    box-shadow:0 3px 8px rgba(0,0,0,.2);
}

.top-icon i{
    width:34px;
    height:34px;
    stroke-width:2.2;
}

body{
    margin: 0;
    background:#fff;
    color:#111;
}

.layout{
    display:flex;
    min-height:100vh;
}

.sidebar{
    width:250px;
    background:#96efb6;
    padding:28px 15px;
    border-right:1px solid #111;
}
/* sidebar */
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


.main{
    flex:1;
    background:#fff;
}

.topbar{
    height:102px;
    background:#96efb6;
    border-bottom:1px solid #111;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 42px;
}

.topbar h1{
    margin:0;
    font-size:28px;
}
/* end of sidbar */


.content{
    padding:24px 28px 40px;
}

.grid-top{
    display:grid;
    grid-template-columns:repeat(3, 1fr);
    gap:58px;
    margin-bottom:28px;
}

.stat-card{
    background:#fff0ad;
    border-radius:18px;
    min-height:155px;
    padding:34px 42px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 4px 10px rgba(0,0,0,.16);
}

.stat-number{
    font-size:32px;
    font-weight:700;
    margin-bottom:10px;
}

.stat-label{
    font-size:20px;
    font-weight:600;
}

.stat-icon{
    width:74px;
    height:74px;
    border-radius:50%;
    background:#96efb6;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:42px;
}
.cat-panel{
    min-height:240px;
}
.dashboard-grid{
    display:grid;
    grid-template-columns:1.05fr 1fr;
    gap:64px;
}

.panel{
    background:#fff0ad;
    border-radius:18px;
    box-shadow:0 4px 10px rgba(0,0,0,.16);
    padding:26px 34px;
    margin-bottom:30px;
}

.sales-card{
    min-height:110px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.sales-title{
    font-size:20px;
    font-weight:700;
}

.sales-sub{
    margin-top:14px;
    font-size:16px;
}

.sales-number{
    font-size:38px;
    font-weight:700;
}

.panel-title{
    display:flex;
    justify-content:space-between;
    align-items:center;
    font-size:22px;
    font-weight:700;
    margin-bottom:22px;
}

.panel-title .arrow{
    font-size:42px;
}

.order-row{
    background:#96efb6;
    border-radius:18px;
    padding:20px 34px;
    margin-bottom:14px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    font-size:20px;
}

.order-row span{
    font-weight:600;
}

.order-row strong{
    font-size:30px;
    font-weight:700;        
}

.cat-icons{
    display:flex;
    gap:24px;
    justify-content:center;
    flex-wrap:nowrap;
    overflow-x:auto;
    padding-bottom:10px;
}

.cat-icon{
    width:95px;
    height:95px;
    border-radius:50%;
    background:#96efb6;
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
    flex-shrink:0;
}
.cat-icons{
    display:flex;
    gap:24px;
    justify-content:space-between;
    align-items:flex-start;
}

.cat-item{
    display:flex;
    flex-direction:column;
    align-items:center;
    width:100px;
}

.cat-icon{
    width:95px;
    height:95px;
    border-radius:50%;
    background:#96efb6;
    display:flex;
    align-items:center;
    justify-content:center;
}

.cat-name{
    margin-top:10px;
    font-size:14px;
    font-weight:600;
    text-align:center;
    line-height:1.3;
}
.cat-icon i{
    width:42px;
    height:42px;
    stroke-width:2.3;
}
.cat-icon i{
    width:48px;
    height:48px;
    stroke-width:2.3;
}
.cat-icons{
    display:flex;
    gap:24px;
    justify-content:space-between;
    align-items:flex-start;
}

.cat-item{
    display:flex;
    flex-direction:column;
    align-items:center;
    width:100px;
}

.cat-icon{
    width:95px;
    height:95px;
    border-radius:50%;
    background:#96efb6;
    display:flex;
    align-items:center;
    justify-content:center;
}

.cat-icon i{
    width:42px;
    height:42px;
    stroke-width:2.3;
}

.cat-text{
    margin-top:10px;
    font-size:14px;
    font-weight:600;
    text-align:center;
    line-height:1.3;
}
.promo-list{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:18px;
}

.promo-card{
    background:#96efb6;
    border-radius:18px;
    padding:30px 16px;
    text-align:center;
    min-height:160px;
}

.promo-date{
    font-size:34px;
    font-weight:900;
}

.promo-month{
    font-size:18px;
    margin:6px 0 14px;
}

.promo-name{
    font-size:16px;
    font-weight:800;
    text-decoration:underline;
}

.shop-row{
    background:#96efb6;
    border-radius:18px;
    padding:14px 16px;
    margin-bottom:14px;
    display:flex;
    align-items:center;
    gap:22px;
}

.rank{
    width:68px;
    height:68px;
    border-radius:18px;
    background:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:38px;
    font-weight:900;
    box-shadow:0 3px 8px rgba(0,0,0,.16);
}

.shop-info{
    font-size:19px;
    font-weight:700;
}

.shop-sales{
    font-size:13px;
    color:#333;
    margin-top:4px;
}
.cat-icon i{
    width:34px;
    height:34px;
    stroke-width:2.2;
}

.card-link{
    text-decoration:none;
    color:inherit;
    display:block;
}

.card-link:hover .stat-card,
.card-link:hover .panel{
    transform:translateY(-3px);
    transition:.2s;
}

@media(max-width:1100px){
    .layout{
        flex-direction:column;
    }

    .sidebar{
        width:100%;
        border-right:none;
        border-bottom:1px solid #111;
    }

    .logo{
        margin-bottom:20px;
    }

    .menu{
        flex-direction:row;
        overflow-x:auto;
        gap:10px;
    }

    .menu a{
        white-space:nowrap;
    }

    .grid-top,
    .dashboard-grid{
        grid-template-columns:1fr;
        gap:24px;
    }
}

.top-icon{
    position:relative;
    text-decoration:none;
    color:#111;
}


.has-noti svg{
    color:red;
    stroke:red;
}


.noti-dot{
    position:absolute;
    top:8px;
    right:10px;
    width:14px;
    height:14px;
    background:red;
    border-radius:50%;
    border:2px solid white;
}
</style>
<script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>

<div class="layout">
<?php include "includes/admin_sideber.php"; ?>

    <main class="main">
        <header class="topbar">
            <h1>Dashboard</h1>
            <div class="top-icons">
        <a href="admin_complaints.php" 
        class="top-icon <?= $pendingComplaint > 0 ? 'has-noti' : '' ?>">

            <i data-lucide="bell"></i>

            <?php if($pendingComplaint > 0): ?>
                <span class="noti-dot"></span>
            <?php endif; ?>

        </a>

                <div class="top-icon">
                    <i data-lucide="settings"></i>
                </div>
            </div>
        </header>

        <section class="content">

            <div class="grid-top">

                <a href="admin_users.php?role=shops" class="card-link">
                <div class="stat-card">
                    <div>
                        <div class="stat-number"><?= number_format($shopCount) ?></div>
                        <div class="stat-label">จำนวนร้านค้า</div>
                    </div>
                    <div class="stat-icon">
                        <i data-lucide="store"></i>
                    </div>
                </div>
                </a>

                <a href="admin_users.php" class="card-link">
                <div class="stat-card">
                    <div>
                        <div class="stat-number"><?= number_format($userCount) ?></div>
                        <div class="stat-label">จำนวนผู้ใช้งาน</div>
                    </div>
                    <div class="stat-icon">
                        <i data-lucide="users"></i>
                    </div>
                </div>
                </a>

                <a href="admin_orders.php" class="card-link">
                    <div class="stat-card">
                        <div>
                            <div class="stat-number"><?= number_format($orderCount) ?></div>
                            <div class="stat-label">จำนวนออเดอร์</div>
                        </div>

                        <div class="stat-icon">
                            <i data-lucide="truck"></i>
                        </div>
                    </div>
                </a>

                </div> <!-- ปิด grid-top ตรงนี้ -->

                <div class="dashboard-grid">

                <div>
                    <a href="admin_finance.php" class="card-link">

                        <div class="panel sales-card">

                            <div>
                                <div class="sales-title">ยอดขายทั้งหมดวันนี้</div>
                                <div class="sales-sub">(จำนวนเงินรวม)</div>
                            </div>

                            <div class="sales-number">
                                <?= number_format((float)$todaySales, 2) ?> บ.
                            </div>

                            <div class="stat-icon">
                                <i data-lucide="wallet"></i>
                            </div>

                        </div>

                    </a>

                    <a href="admin_orders.php" class="card-link">
                    <div class="panel cat-panel">

                        <div class="panel-title">
                            <span>คำสั่งซื้อ</span>
                            <i data-lucide="clipboard-list"></i>
                        </div>

                        <div class="order-row">
                            <span>คำสั่งซื้อที่กำลังจัดส่ง</span>
                            <strong><?= number_format($deliveringCount) ?></strong>
                        </div>

                        <div class="order-row">
                            <span>คำสั่งซื้อที่สำเร็จ</span>
                            <strong><?= number_format($completedCount) ?></strong>
                        </div>

                        <div class="order-row">
                            <span>คำสั่งซื้อที่ถูกยกเลิก</span>
                            <strong><?= number_format($cancelledCount) ?></strong>
                        </div>
                    </div>
                    </a>

                    <div class="panel cat-panel">
                        <div class="panel-title">หมวดหมู่ขายดี</div>
                        <div class="cat-icons">
                            <?php if(!empty($topCategories)): ?>
                                <?php foreach($topCategories as $cat): ?>

                                    <?php
                                    $name = $thaiCat[$cat['category_name']] ?? $cat['category_name'];

                                    $catIcons = [
                                        'Meat' => 'beef',
                                        'Fruits & Vegetables' => 'apple',
                                        'Seasoning' => 'chef-hat',
                                        'Beverages' => 'cup-soda',
                                        'Frozen' => 'snowflake',
                                        'Kitchen Supplies' => 'cooking-pot',
                                        'Desserts' => 'cake'
                                    ];
                                    ?>
                                    <div class="cat-item">

                                        <div class="cat-icon">
                                            <i data-lucide="<?= $catIcons[$cat['category_name']] ?? 'package' ?>"></i>
                                        </div>

                                        <div class="cat-text">
                                            <?= e($name) ?>
                                        </div>

                                    </div>

                                <?php endforeach; ?>
                            <?php else: ?>
                                <div>ยังไม่มีข้อมูล</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div>
                    <a href="admin_promotions.php" class="card-link">
                    <div class="panel">
                        <div class="panel-title">
                            <span>ปฏิทินโปรโมชั่น
                                 <!-- <i data-lucide="chevron-right"></i>  -->
                        </span>
                            <i data-lucide="calendar-days"></i>
                        </div>
                        </a>
                <div class="promo-list">

                <?php if(!empty($promotions)): ?>

                    <?php foreach($promotions as $promo): ?>

                        <?php
                        $date = strtotime($promo['start_date']);
                        $day = date("d", $date);
                        $month = $thaiMonths[(int)date("m", $date)];
                        ?>

                        <div class="promo-card">

                            <div class="promo-date">
                                <?= $day ?>
                            </div>

                            <div class="promo-month">
                                <?= $month ?>
                            </div>

                            <div class="promo-name">
                                <?= e($promo['title']) ?>
                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="promo-card">
                        <div class="promo-date">--</div>
                        <div class="promo-month">ไม่มี</div>
                        <div class="promo-name">
                            ยังไม่มีโปรโมชัน
                        </div>
                    </div>

                <?php endif; ?>

                </div>
                    </div>

                    <div class="panel">
                        <div class="panel-title">
                            <span>ร้านค้าขายดี 
                                <!-- <i data-lucide="chevron-right"></i> -->
                            </span>
                            <i data-lucide="store"></i>
                        </div>

                        <?php if(!empty($topShops)): ?>
                            <?php foreach($topShops as $index => $shop): ?>
                                <div class="shop-row">
                                    <div class="rank"><?= $index + 1 ?></div>
                                    <div>
                                        <div class="shop-info"><?= e($shop['shop_name'] ?: 'ไม่ระบุร้าน') ?></div>
                                        <div class="shop-sales">
                                            ยอดขาย <?= number_format((float)$shop['total_sales'], 2) ?> บ.
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="shop-row">
                                <div class="rank">-</div>
                                <div class="shop-info">ยังไม่มีข้อมูลร้านค้า</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </section>
    </main>

</div>
<script>
lucide.createIcons();
</script>
</body>
</html>