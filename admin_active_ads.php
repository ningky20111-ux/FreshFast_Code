<?php
session_start();
require_once "db.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

/*
|--------------------------------------------------------------------------
| เช็กการเข้าใช้งานแบบเดียวกับหน้า admin อื่นแบบผ่อนปรน
| ถ้ายังไม่มีระบบ login admin แยก จะเช็กแค่ว่ามี session user_id
|--------------------------------------------------------------------------
*/
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit;
// }

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$tab = $_GET['tab'] ?? 'summary';
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'latest';

/*
|--------------------------------------------------------------------------
| สรุปแพ็กเกจโฆษณา
|--------------------------------------------------------------------------
*/
$summarySql = "
    SELECT 
        sp.package_price,
        COUNT(*) AS total_shops,
        SUM(sp.package_price) AS total_income
    FROM shop_promotions sp
    GROUP BY sp.package_price
    ORDER BY sp.package_price ASC
";

$summaryResult = $conn->query($summarySql);

/*
|--------------------------------------------------------------------------
| รายการร้านค้าที่เข้าร่วมโฆษณา
|--------------------------------------------------------------------------
*/
$where = "WHERE 1=1";
$params = [];
$types = "";

if ($search !== '') {
    $where .= " AND (s.shop_id LIKE ? OR s.shop_name LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

$orderBy = "sp.joined_at DESC";

if ($sort === 'oldest') {
    $orderBy = "sp.joined_at ASC";
} elseif ($sort === 'price_high') {
    $orderBy = "sp.package_price DESC";
} elseif ($sort === 'price_low') {
    $orderBy = "sp.package_price ASC";
}

$listSql = "
    SELECT
        sp.shop_promotion_id,
        sp.shop_id,
        sp.promotion_id,
        sp.package_price,
        sp.duration_days,
        sp.status,
        sp.joined_at,
        s.shop_name
    FROM shop_promotions sp
    INNER JOIN shops s ON sp.shop_id = s.shop_id
    $where
    ORDER BY $orderBy
";

$stmt = $conn->prepare($listSql);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$listResult = $stmt->get_result();

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
    background:#fff2ad;
    box-shadow:0 4px 10px rgba(0,0,0,.12);
}

.menu .icon{
    font-size:28px;
    width:30px;
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

.menu a{
    display:flex;
    align-items:center;
    gap:16px;
    padding:17px 24px;
    margin-bottom:22px;
    color:#000;
    text-decoration:none;
    font-weight:600;
    border-radius:18px;
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
    padding:22px 30px;
}

.panel{
    background:#fff1b3;
    border-radius:18px;
    padding:34px 38px 10px;
    box-shadow:0 2px 8px rgba(0,0,0,.22);
    min-height:874px;
}

.panel-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
    margin-bottom:28px;
}

.panel h2{
    margin:0;
    font-size:24px;
}

.tools{
    display:flex;
    align-items:center;
    gap:26px;
}

.search-box{
    width:340px;
    height:45px;
    background:#fff;
    border-radius:24px;
    display:flex;
    align-items:center;
    padding:0 18px;
    gap:12px;
    box-shadow:0 2px 5px rgba(0,0,0,.25);
}

.search-box span{
    font-size:30px;
}

.search-box input{
    border:none;
    outline:none;
    width:100%;
    font-size:18px;
    color:#777;
}

.sort-select{
    height:42px;
    min-width:116px;
    border:none;
    border-radius:14px;
    background:#ffc400;
    font-size:18px;
    font-weight:700;
    padding:0 14px;
    cursor:pointer;
    box-shadow:0 3px 7px rgba(0,0,0,.2);
}

.tab-row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:82px;
    max-width:950px;
    margin:0 auto 28px;
}

.tab-btn{
    height:67px;
    border-radius:18px;
    border:none;
    display:flex;
    align-items:center;
    justify-content:center;
    text-decoration:none;
    color:#000;
    font-size:21px;
    font-weight:700;
    background:#98efb8;
    box-shadow:0 4px 8px rgba(0,0,0,.18);
}

.tab-btn.active{
    background:#ffc400;
}

.summary-wrap{
    max-width:955px;
    margin:0 auto;
}

.summary-card{
    background:#98efb8;
    border-radius:18px;
    min-height:204px;
    padding:25px 36px;
    margin-bottom:20px;
    box-shadow:0 3px 8px rgba(0,0,0,.2);
}

.summary-card h3{
    margin:0 0 20px;
    font-size:22px;
}

.summary-card p{
    margin:13px 0;
    font-size:17px;
    font-weight:600;
}

.table{
    width:100%;
    border-collapse:collapse;
    margin-top:44px;
    font-size:17px;
}

.table th{
    text-align:left;
    padding:14px 20px;
    border-bottom:1px solid rgba(0,0,0,.35);
}

.table td{
    padding:18px 20px;
    border-bottom:1px solid rgba(0,0,0,.35);
}

.empty{
    text-align:center;
    padding:50px;
    font-size:20px;
    font-weight:700;
}

.dots{
    text-align:center;
    font-size:38px;
    color:#ffc400;
    letter-spacing:3px;
}

small{
    color:#555;
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
    gap:26px;
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
}

.menu a.active{
    background:#fff2ad;
    box-shadow:0 4px 10px rgba(0,0,0,.12);
}

.menu .icon{
    font-size:28px;
    width:30px;
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
</style>
</head>

<body>

<div class="layout">
<?php include "includes/admin_sideber.php"; ?>
    <main class="main">
        <header class="topbar">
            <h1>ระบบโปรโมชั่น</h1>
            <div class="top-icons">
                <div class="top-icon">
                    <i data-lucide="bell"></i>
                </div>

                <div class="top-icon">
                    <i data-lucide="settings"></i>
                </div>
            </div>
        </header>
        </header>

        <section class="content">

            <div class="panel">

                <div class="panel-header">
                    <h2>โฆษณาที่กำลังเปิดอยู่</h2>

                    <?php if ($tab === 'list'): ?>
                        <form class="tools" method="GET" action="admin_promotions.php">
                            <input type="hidden" name="tab" value="list">

                            <div class="search-box">
                                <span>⌕</span>
                                <input 
                                    type="text" 
                                    name="search" 
                                    placeholder="ค้นหา ID ร้านค้า / ชื่อร้าน" 
                                    value="<?= e($search) ?>"
                                >
                            </div>

                            <select name="sort" class="sort-select" onchange="this.form.submit()">
                                <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>ล่าสุด</option>
                                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>เก่าสุด</option>
                                <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>ราคาสูง</option>
                                <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>ราคาต่ำ</option>
                            </select>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="tab-row">
                    <a 
                        href="admin_promotions.php?tab=summary" 
                        class="tab-btn <?= $tab === 'summary' ? 'active' : '' ?>"
                    >
                        ร้านค้าที่เข้าร่วม
                    </a>

                    <a 
                        href="admin_promotions.php?tab=list" 
                        class="tab-btn <?= $tab === 'list' ? 'active' : '' ?>"
                    >
                        รายการโฆษณา
                    </a>
                </div>

                <?php if ($tab === 'summary'): ?>

                    <div class="summary-wrap">

                        <?php if ($summaryResult && $summaryResult->num_rows > 0): ?>

                            <?php while ($row = $summaryResult->fetch_assoc()): ?>

                                <div class="summary-card">
                                    <h3>
                                        <?= number_format((float)$row['package_price'], 0) ?> บาท / วัน |
                                        <?= number_format((int)$row['total_shops']) ?> ร้านค้าที่เข้าร่วม
                                    </h3>

                                    <p>งบประมาณโฆษณาต่อวัน</p>
                                    <p>ประมาณ <?= number_format((int)$row['total_shops']) ?> ร้านค้าที่สมัครเข้าร่วม</p>
                                    <p>รายได้จากโฆษณารวม <?= number_format((float)$row['total_income'], 2) ?> บาท</p>
                                </div>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <div class="empty">ยังไม่มีร้านค้าที่สมัครโฆษณา</div>

                        <?php endif; ?>

                    </div>

                <?php else: ?>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID ร้านค้า</th>
                                <th>รายได้จากการโฆษณา</th>
                                <th>วันที่เข้าร่วม</th>
                                <th>ประเภทการโฆษณา</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if ($listResult && $listResult->num_rows > 0): ?>

                                <?php while ($row = $listResult->fetch_assoc()): ?>

                                    <tr>
                                        <td>
                                            ID ร้านค้า <?= e($row['shop_id']) ?><br>
                                            <small><?= e($row['shop_name']) ?></small>
                                        </td>

                                        <td>
                                            <?= number_format((float)$row['package_price'], 2) ?> บาท
                                        </td>

                                        <td>
                                            <?= !empty($row['joined_at']) ? date("d/m/Y", strtotime($row['joined_at'])) : '-' ?>
                                        </td>

                                        <td>
                                            <?= number_format((float)$row['package_price'], 0) ?> บาท / วัน
                                        </td>
                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="4" class="empty">ยังไม่มีรายการโฆษณา</td>
                                </tr>

                            <?php endif; ?>

                        </tbody>
                    </table>

                    <div class="dots">●●●</div>

                <?php endif; ?>

            </div>

        </section>

    </main>

</div>
<script>
lucide.createIcons();
</script>

</body>
</html>