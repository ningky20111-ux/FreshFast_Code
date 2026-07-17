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

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $startDate = trim($_POST['start_date'] ?? '');
    $endDate = trim($_POST['end_date'] ?? '');
    $targetScope = trim($_POST['target_scope'] ?? 'all_shops');
    $customerLimit = trim($_POST['customer_limit'] ?? 'all');
    $minOrderAmount = (float)($_POST['min_order_amount'] ?? 0);
    $discountValue = (float)($_POST['discount_value'] ?? 0);
    $systemCommissionLimit = (float)($_POST['system_commission_limit'] ?? 0);
    $shopCreditLimit = (float)($_POST['shop_credit_limit'] ?? 0);
    $allowStack = trim($_POST['allow_stack'] ?? 'no');
    $gpExtraPercent = (float)($_POST['gp_extra_percent'] ?? 0);
    $campaignBudget = (float)($_POST['campaign_budget'] ?? 0);
    $dailyBudget = (float)($_POST['daily_budget'] ?? 0);
    $displayPositions = $_POST['display_positions'] ?? [];

    if ($title === '') {
        $error = "กรุณากรอกชื่อโปรโมชั่น";
    } elseif ($startDate === '' || $endDate === '') {
        $error = "กรุณาเลือกวันที่เริ่มต้นและวันที่สิ้นสุด";
    } elseif ($discountValue <= 0) {
        $error = "กรุณากรอกส่วนลดสูงสุด";
    } else {
        $description = "ขอบเขต: {$targetScope}, ลูกค้า: {$customerLimit}, ขั้นต่ำ: {$minOrderAmount}, ค่าธรรมเนียมระบบ: {$systemCommissionLimit}, เครดิตร้าน: {$shopCreditLimit}, ใช้ร่วมโปรอื่น: {$allowStack}, GP เพิ่ม: {$gpExtraPercent}, งบแคมเปญ: {$campaignBudget}, งบต่อวัน: {$dailyBudget}, ตำแหน่งแสดง: " . implode(", ", $displayPositions);

        $discountType = "promotion";
        $status = "active";

        $stmt = $conn->prepare("
            INSERT INTO promotions
            (
                title,
                description,
                discount_type,
                discount_value,
                start_date,
                end_date,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            die("SQL Error: " . $conn->error);
        }

        $stmt->bind_param(
            "sssdsss",
            $title,
            $description,
            $discountType,
            $discountValue,
            $startDate,
            $endDate,
            $status
        );

        if ($stmt->execute()) {
            header("Location: admin_promotions.php");
            exit;
        } else {
            $error = "บันทึกโปรโมชั่นไม่สำเร็จ: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>สร้างโปรโมชั่น | FreshFast</title>
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
    padding:34px 28px;
}

.form-card{
    background:#fff1b3;
    border-radius:18px;
    padding:38px 34px;
    min-height:860px;
    box-shadow:0 2px 8px rgba(0,0,0,.2);
    position:relative;
}

.calendar-icon{
    position:absolute;
    top:26px;
    right:28px;
    font-size:34px;
    color:#009b39;
}

.calendar-icon{
    position:absolute;
    top:26px;
    right:28px;
    color:#009b39;
}

.calendar-icon i{
    width:34px;
    height:34px;
    stroke-width:2.2;
}


.form-title{
    font-size:17px;
    font-weight:700;
    margin-bottom:30px;
}

.form-row{
    display:grid;
    grid-template-columns:150px 1fr;
    align-items:start;
    gap:18px;
    margin-bottom:18px;
}

.form-row label{
    font-weight:700;
    padding-top:6px;
}

input[type="text"],
input[type="number"],
input[type="date"]{
    width:650px;
    height:31px;
    border:1px solid #111;
    border-radius:18px;
    padding:0 16px;
    font-size:15px;
    outline:none;
    background:#fff;
}

.option-group{
    display:flex;
    flex-direction:column;
    gap:8px;
}

.pill{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:max-content;
    min-width:108px;
    height:31px;
    border:1px solid #111;
    border-radius:18px;
    background:#fff;
    padding:0 20px;
    font-weight:600;
    cursor:pointer;
}

.pill input{
    display:none;
}

.pill:has(input:checked){
    background:#ffc400;
}

.conditions{
    display:grid;
    grid-template-columns:150px 1fr;
    gap:18px;
    margin-top:35px;
}

.conditions-title{
    font-weight:700;
}

.condition-list{
    display:flex;
    flex-direction:column;
    gap:9px;
}

.condition-line{
    display:flex;
    align-items:center;
    gap:10px;
    font-weight:600;
}

.condition-line input{
    width:160px;
}

.small-input{
    width:170px!important;
}

.display-section{
    display:grid;
    grid-template-columns:150px 1fr;
    gap:18px;
    margin-top:45px;
}

.display-buttons{
    display:flex;
    flex-direction:column;
    gap:9px;
    margin-top:18px;
}

.display-pill{
    width:118px;
    height:28px;
    border:1px solid #111;
    border-radius:18px;
    background:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:600;
    cursor:pointer;
}

.display-pill input{
    display:none;
}

.display-pill:has(input:checked){
    background:#ffc400;
}

.actions{
    display:flex;
    justify-content:center;
    gap:12px;
    margin-top:58px;
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
.cancel-btn,
.save-btn{
    border:none;
    border-radius:13px;
    height:37px;
    padding:0 35px;
    color:#fff;
    font-size:17px;
    font-weight:700;
    text-decoration:none;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    box-shadow:0 3px 7px rgba(0,0,0,.22);
}

.cancel-btn{
    background:#ff0000;
}

.save-btn{
    background:#009b39;
}

.alert{
    padding:12px 18px;
    border-radius:12px;
    margin-bottom:18px;
    font-weight:700;
}

.alert.error{
    background:#ffb3b3;
}
.logo{
    width:190px;
    margin-bottom:80px;
}
@media(max-width:1000px){
    input[type="text"],
    input[type="number"],
    input[type="date"]{
        width:100%;
    }

    .form-row,
    .conditions,
    .display-section{
        grid-template-columns:1fr;
    }
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

        <section class="content">

            <form class="form-card" method="POST" action="admin_add_promotion.php">
                <div class="calendar-icon">
                    <i data-lucide="calendar-days"></i>
                </div>

                <div class="form-title">สร้างโปรโมชั่น (Create Promotion – Admin)</div>

                <?php if ($error): ?>
                    <div class="alert error"><?= e($error) ?></div>
                <?php endif; ?>

                <div class="form-row">
                    <label>ชื่อโปรโมชั่น :</label>
                    <input type="text" name="title" placeholder="ชื่อโปรโมชั่น" required>
                </div>

                <div class="form-row">
                    <label>วันที่เริ่มต้น-สิ้นสุด :</label>
                    <div style="display:flex;gap:12px;width:650px;">
                        <input type="date" name="start_date" required>
                        <input type="date" name="end_date" required>
                    </div>
                </div>

                <div class="form-row">
                    <label>ขอบเขตการใช้งาน :</label>
                    <div class="option-group">
                        <label class="pill">
                            <input type="radio" name="target_scope" value="all_shops" checked>
                            ทุกร้านค้าที่ต้องการเข้าร่วม
                        </label>

                        <label class="pill">
                            <input type="radio" name="target_scope" value="category_only">
                            เฉพาะหมวดหมู่ของสินค้า
                        </label>

                        <label class="pill">
                            <input type="radio" name="target_scope" value="low_sales_shop">
                            ร้านค้าที่มียอดขายขั้นต่ำ
                        </label>
                    </div>
                </div>

                <div class="form-row" style="margin-top:28px;">
                    <label>จำกัดเฉพาะลูกค้า :</label>
                    <div class="option-group">
                        <label class="pill">
                            <input type="radio" name="customer_limit" value="new_customer" checked>
                            ลูกค้าใหม่
                        </label>

                        <label class="pill">
                            <input type="radio" name="customer_limit" value="old_customer">
                            ลูกค้าเก่า
                        </label>
                    </div>
                </div>

                <div class="conditions">
                    <div class="conditions-title">เงื่อนไขโปรโมชั่น :</div>

                    <div class="condition-list">
                        <div class="condition-line">
                            <span>• ยอดสั่งซื้อขั้นต่ำ :</span>
                            <input type="number" name="min_order_amount" placeholder="เช่น 200" step="0.01">
                            <span>บาท</span>
                        </div>

                        <div class="condition-line">
                            <span>• ส่วนลดสูงสุด :</span>
                            <input type="number" name="discount_value" placeholder="เช่น 15" step="0.01" required>
                            <span>%</span>
                        </div>

                        <div class="condition-line">
                            <span>• จำนวนสิทธิรวมทั้งระบบ :</span>
                            <input type="number" name="system_commission_limit" placeholder="เช่น 2000" step="0.01">
                            <span>สิทธิ์</span>
                        </div>

                        <div class="condition-line">
                            <span>• จำกัดกี่สิทธิ์ต่อ 1 บัญชี :</span>
                            <input type="number" name="shop_credit_limit" placeholder="เช่น 20" step="0.01">
                            <span>สิทธิ์</span>
                        </div>

                        <div class="condition-line">
                            <span>• ใช้ร่วมกับโปรอื่นได้หรือไม่ :</span>

                            <label class="pill" style="min-width:42px;width:42px;padding:0;">
                                <input type="radio" name="allow_stack" value="yes" checked>
                                ✓
                            </label>

                            <label class="pill" style="min-width:42px;width:42px;padding:0;background:#ffc400;">
                                <input type="radio" name="allow_stack" value="no">
                                ✕
                            </label>
                        </div>

                        <div class="condition-line">
                            <span>• ค่าธรรมเนียมเพิ่มเติมจากร้าน (GP เพิ่ม %) :</span>
                            <input type="number" name="gp_extra_percent" placeholder="เช่น 20" step="0.01">
                            <span>%</span>
                        </div>
                    </div>
                </div>

                <div class="conditions">
                    <div class="conditions-title">การควบคุมงบประมาณ :</div>

                    <div class="condition-list">
                        <div class="condition-line">
                            <span>• งบประมาณรวมของแคมเปญ :</span>
                            <input type="number" name="campaign_budget" placeholder="เช่น 2000000" step="0.01">
                            <span>บาท</span>
                        </div>

                        <div class="condition-line">
                            <span>• งบต่อวัน :</span>
                            <input type="number" name="daily_budget" placeholder="เช่น 2000000" step="0.01">
                            <span>บาท</span>
                        </div>
                    </div>
                </div>

                <div class="display-section">
                    <div class="conditions-title">การแสดงผล :</div>

                    <div>
                        <div class="condition-line">• ตำแหน่งแสดง :</div>

                        <div class="display-buttons">
                            <label class="display-pill">
                                <input type="checkbox" name="display_positions[]" value="home" checked>
                                หน้าแรก
                            </label>

                            <label class="display-pill">
                                <input type="checkbox" name="display_positions[]" value="promotion" checked>
                                หน้าโปรโมชั่น
                            </label>

                            <label class="display-pill">
                                <input type="checkbox" name="display_positions[]" value="category">
                                หน้า Category
                            </label>
                        </div>
                    </div>
                </div>

                <div class="actions">
                    <a href="admin_promotions.php" class="cancel-btn">ยกเลิก</a>
                    <button type="submit" class="save-btn">บันทึกโปรโมชั่น</button>
                </div>

            </form>

        </section>

    </main>

</div>
<script src="https://unpkg.com/lucide@latest"></script>

<script>
lucide.createIcons();
</script>

<script src="https://unpkg.com/lucide@latest"></script>

<script>
lucide.createIcons();
</script>

</body>
</html>