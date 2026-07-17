<?php
session_start();
require_once "db.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit;
// }

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* สถิติ */
$totalAdsSql = "
    SELECT 
        COUNT(*) AS total_ads,
        COUNT(DISTINCT shop_id) AS total_shops
    FROM shop_promotions
    WHERE status IN ('pending','approved','joined')
";
$totalAds = $conn->query($totalAdsSql)->fetch_assoc();

$totalPromoSql = "
    SELECT COUNT(*) AS total_promotions
    FROM promotions
    WHERE status = 'active'
";
$totalPromo = $conn->query($totalPromoSql)->fetch_assoc();

/* ปฏิทินโปรโมชั่น */
$calendarSql = "
    SELECT title, start_date, end_date
    FROM promotions
    WHERE status = 'active'
    ORDER BY start_date ASC
    LIMIT 5
";
$calendarResult = $conn->query($calendarSql);

/* โปรโมชั่นใกล้สิ้นสุด */
$endingSql = "
    SELECT title, end_date, discount_value
    FROM promotions
    WHERE status = 'active'
    AND end_date IS NOT NULL
    ORDER BY end_date ASC
    LIMIT 1
";
$endingPromo = $conn->query($endingSql)->fetch_assoc();

/* จำนวนโฆษณาแต่ละแพ็กเกจ */
$packageSql = "
    SELECT 
        package_price,
        COUNT(*) AS total_shops
    FROM shop_promotions
    WHERE status IN ('pending','approved','joined')
    GROUP BY package_price
";
$packageResult = $conn->query($packageSql);

$packages = [
    50 => 0,
    70 => 0,
    150 => 0
];

$maxCount = 1;

if ($packageResult) {
    while ($row = $packageResult->fetch_assoc()) {
        $price = (int)$row['package_price'];
        $count = (int)$row['total_shops'];

        if (isset($packages[$price])) {
            $packages[$price] = $count;
        }

        if ($count > $maxCount) {
            $maxCount = $count;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ระบบโปรโมชั่น | FreshFast</title>

<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{
    box-sizing:border-box;
    font-family:sans-serif;
}

body{
    margin:0;
    background:#fff;
    color:#000;
}

.layout{
    display:flex;
    min-height:100vh;
}

.sidebar{
    width:252px;
    background:#98efb8;
    border-right:1px solid #111;
    padding:28px 15px;
}

.logo{
    display:flex;
    align-items:center;
    gap:10px;
    font-weight:700;
    color:#008c3a;
    font-size:20px;
    margin-bottom:80px;
}

.logo-icon{
    font-size:54px;
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
    height:103px;
    background:#98efb8;
    border-bottom:1px solid #111;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 42px;
}

.topbar h1{
    font-size:26px;
    margin:0;
}

.top-icons{
    display:flex;
    gap:38px;
}

.circle-btn{
    width:62px;
    height:62px;
    border-radius:50%;
    background:#fff;
    display:grid;
    place-items:center;
    font-size:30px;
    box-shadow:0 3px 8px rgba(0,0,0,.18);
}

.content{
    padding:24px 28px;
}

.top-cards{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:90px;
    margin-bottom:24px;
}

.stat-card{
    min-height:153px;
    border-radius:18px;
    padding:38px 42px;
    color:#000;
    text-decoration:none;
    box-shadow:0 3px 8px rgba(0,0,0,.2);
    display:block;
}

.stat-card h2{
    margin:0 0 8px;
    font-size:25px;
}

.stat-card p{
    margin:0;
    font-size:20px;
    font-weight:600;
}

.yellow{
    background:#ffc400;
}

.green{
    background:#009b39;
    color:#fff;
}

.calendar-box{
    background:#fff1b3;
    border-radius:18px;
    padding:26px 36px 20px;
    margin-bottom:22px;
    box-shadow:0 2px 8px rgba(0,0,0,.2);
    position:relative;
}

.calendar-box h2{
    margin:0 0 20px;
    font-size:25px;
}

.calendar-icon{
    position:absolute;
    top:26px;
    right:28px;
    color:black;
}

.calendar-icon i{
    width:54px;
    height:54px;
    stroke-width:2.2;
}
.calendar-list{
    display:grid;
    grid-template-columns:repeat(5, 1fr);
    gap:38px;
}

.date-card{
    background:#98efb8;
    border-radius:18px;
    min-height:157px;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    box-shadow:0 3px 7px rgba(0,0,0,.18);
}

.date-card .day{
    font-size:34px;
    font-weight:700;
    line-height:1;
}

.date-card .month{
    font-size:15px;
    margin-top:8px;
}

.date-card .name{
    margin-top:14px;
    font-size:17px;
    font-weight:700;
    text-decoration:underline;
    text-align:center;
}

.middle-row{
    display:grid;
    grid-template-columns:1fr 228px;
    gap:20px;
    margin-bottom:20px;
}

.ending-card{
    background:#ff0000;
    color:#fff;
    border-radius:18px;
    padding:20px 40px;
    min-height:126px;
    box-shadow:0 3px 8px rgba(0,0,0,.2);
}

.ending-card h2{
    margin:0 0 16px;
    font-size:26px;
}

.ending-card p{
    margin:0;
    font-size:17px;
}

.add-card{
    background:#009b39;
    color:#fff;
    border-radius:18px;
    text-decoration:none;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:20px;
    font-size:25px;
    font-weight:700;
    box-shadow:0 3px 8px rgba(0,0,0,.2);
}

.plus{
    font-size:58px;
    font-weight:300;
}

.chart-box{
    background:#fff1b3;
    border-radius:18px;
    padding:26px 40px;
    box-shadow:0 2px 8px rgba(0,0,0,.2);
}

.chart-box h2{
    margin:0 0 24px;
    font-size:25px;
}

.bar-row{
    display:grid;
    grid-template-columns:90px 1fr 70px;
    gap:14px;
    align-items:center;
    margin:22px 0;
    font-size:17px;
}

.bar-bg{
    height:34px;
    border-radius:20px;
    overflow:hidden;
}

.bar{
    height:34px;
    background:#009b39;
    border-radius:20px;
    min-width:18px;
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
@media(max-width:1000px){
    .top-cards,
    .middle-row{
        grid-template-columns:1fr;
        gap:20px;
    }

    .calendar-list{
        grid-template-columns:repeat(2, 1fr);
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
</head>

<body>

<div class="layout">


<?php include "includes/admin_sideber.php"; ?>


    <main class="main">

        <header class="topbar">
            <h1>ระบบโปรโมชั่น</h1>

            <div class="top-icons">
                <a href="admin_complaints.php" class="top-icon <?= $pendingComplaint > 0 ? 'has-noti' : '' ?>">
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

            <div class="top-cards">
                <a href="admin_active_ads.php" class="stat-card yellow">
                    <h2>โฆษณาที่กำลังเปิดอยู่</h2>
                    <p>
                        <?= number_format((int)$totalAds['total_shops']) ?> ร้านค้าที่เข้าร่วม
                        &nbsp;|&nbsp;
                        <?= number_format((int)$totalAds['total_ads']) ?> รายการ
                    </p>
                </a>

                <div class="stat-card green">
                    <h2>โปรโมชั่น</h2>
                    <p>
                        <?= number_format((int)$totalPromo['total_promotions']) ?> โปรโมชั่นที่เปิดใช้งาน
                    </p>
                </div>
            </div>

            <div class="calendar-box">
                <h2>ปฏิทินโปรโมชั่น</h2>
                <div class="calendar-icon">
                    <i data-lucide="calendar-days"></i>
                </div>

                <div class="calendar-list">
                    <?php if ($calendarResult && $calendarResult->num_rows > 0): ?>
                        <?php while ($promo = $calendarResult->fetch_assoc()): ?>
                            <?php
                                $date = !empty($promo['start_date']) ? strtotime($promo['start_date']) : time();
                                $day = date('d', $date);
                                $month = date('m', $date);
                            ?>
                            <div class="date-card">
                                <div class="day"><?= e($day) ?></div>
                                <div class="month">เดือน <?= e($month) ?></div>
                                <div class="name"><?= e($promo['title']) ?></div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <div class="date-card">
                                <div class="day">00</div>
                                <div class="month">มกราคม</div>
                                <div class="name">ชื่อโปรโมชั่น</div>
                            </div>
                        <?php endfor; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="middle-row">

                <div class="ending-card">
                    <h2>โปรโมชั่นที่จะสิ้นสุดในเร็วๆนี้</h2>

                    <?php if ($endingPromo): ?>
                        <p>
                            โปรโมชั่น <?= e($endingPromo['title']) ?>
                            สิ้นสุดวันที่ <?= date("d/m/Y", strtotime($endingPromo['end_date'])) ?>
                            ยอดขายรวม ณ ปัจจุบันอยู่ที่ <?= number_format((float)$endingPromo['discount_value'], 2) ?> บาท
                        </p>
                    <?php else: ?>
                        <p>ยังไม่มีโปรโมชั่นที่กำลังจะสิ้นสุด</p>
                    <?php endif; ?>
                </div>

                <a href="admin_add_promotion.php" class="add-card">
                    <span class="plus">＋</span>
                    <span>เพิ่มโฆษณา</span>
                </a>

            </div>

            <div class="chart-box">
                <h2>จำนวนโฆษณา</h2>

                <?php foreach ($packages as $price => $count): ?>
                    <?php
                        $width = ($count / $maxCount) * 100;
                    ?>
                    <div class="bar-row">
                        <div><?= number_format($price) ?> บาท / วัน</div>

                        <div class="bar-bg">
                            <div class="bar" style="width:<?= $width ?>%;"></div>
                        </div>

                        <div><?= number_format($count) ?> ร้าน</div>
                    </div>
                <?php endforeach; ?>
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