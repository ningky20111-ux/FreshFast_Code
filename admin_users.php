<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "db.php";

if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "freshfast");
    $conn->set_charset("utf8mb4");
}

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

function e($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function columnExists($conn, $table, $column)
{
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);

    $sql = "SHOW COLUMNS FROM `$table` LIKE '$column'";
    $result = $conn->query($sql);

    return $result && $result->num_rows > 0;
}



function roleThai($role)
{
    switch ($role) {
        case 'customer':
            return 'ผู้ซื้อ';
        case 'shops':
            return 'ผู้ขาย';
        case 'riders':
            return 'ไรเดอร์';
        case 'admin':
            return 'แอดมิน';
        default:
            return 'ไม่ทราบ';
    }
}


function statusThai($status)
{
    switch ($status) {
        case 'active':
            return 'กำลังใช้งาน';
        case 'suspended':
            return 'ระงับชั่วคราว';
        case 'banned':
            return 'แบนถาวร';
        case 'closed':
            return 'ปิดบัญชี';
        default:
            return 'กำลังใช้งาน';
    }
}

/* เพิ่ม column สถานะ ถ้ายังไม่มี */
if (!columnExists($conn, "users", "user_status")) {
    $conn->query("
        ALTER TABLE users 
        ADD COLUMN user_status VARCHAR(30) DEFAULT 'active'
    ");
}

/* ตารางบันทึกการกระทำ */
$conn->query("
    CREATE TABLE IF NOT EXISTS user_activity_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action_text VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

/* อัปเดตสถานะ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $map = [
        'active' => 'เปิดใช้งาน',
        'suspended' => 'ระงับชั่วคราว',
        'banned' => 'แบนถาวร',
        'closed' => 'ปิดบัญชีตามคำขอผู้ใช้งาน'
    ];

    if ($userId > 0 && isset($map[$action])) {
        $stmt = $conn->prepare("UPDATE users SET user_status = ? WHERE user_id = ?");
        $stmt->bind_param("si", $action, $userId);
        $stmt->execute();

        $logText = $map[$action];

        $stmt = $conn->prepare("
            INSERT INTO user_activity_logs (user_id, action_text)
            VALUES (?, ?)
        ");
        $stmt->bind_param("is", $userId, $logText);
        $stmt->execute();
    }

    $backRole = $_GET['role'] ?? 'customer';
    $backTab = $_POST['tab'] ?? 'profile';

    header(
        "Location: admin_users.php?role="
        . urlencode($backRole)
        . "&user_id=" . $userId
        . "&tab=" . urlencode($backTab)
    );
    exit;
}

$role = $_GET['role'] ?? 'customer';
$search = trim($_GET['search'] ?? '');
$selectedId = (int)($_GET['user_id'] ?? 0);
$tab = $_GET['tab'] ?? 'profile';

$allowedRoles = ['customer', 'shops', 'riders'];

if (!in_array($role, $allowedRoles)) {
    $role = 'customer';
}

$where = "";
$params = [];
$types = "";

if ($search !== '') {

    // ถ้าพิมพ์ #28 ให้ตัด # ออก
    $searchClean = ltrim($search, '#');

    $where .= "
        AND (
            name LIKE ?
            OR email LIKE ?
            OR CAST(user_id AS CHAR) LIKE ?
        )
    ";

    $like = "%{$searchClean}%";

    $params[] = $like;
    $params[] = $like;
    $params[] = $like;

    $types .= "sss";
}


if($role === 'customer'){

$stmt = $conn->prepare("
SELECT *
FROM users
WHERE role = ?
$where
ORDER BY created_at DESC
");

$stmt->bind_param(
    "s".$types,
    $role,
    ...$params
);

}

else if($role === 'shops'){

$stmt = $conn->prepare("
        SELECT

        shop_id AS user_id,

        shop_name AS name,

        shop_email AS email,

        shop_phone AS phone,

        status AS user_status,

        owner_id,

        shop_address,

        shop_type,

        description,

        shop_image AS profile_image,

        shop_banner,

        'shops' AS role,

        NULL AS created_at

        FROM shops

        ORDER BY shop_id DESC
        ");

}

else if($role === 'riders'){


$stmt = $conn->prepare("
            SELECT

            rider_id AS user_id,
            profile_image,

            full_name AS name,

            email,

            phone,

            status AS user_status,

            vehicle_type,

            license_plate,

            'riders' AS role,

            NULL AS created_at


            FROM riders

            ORDER BY rider_id DESC

            ");


}

$stmt->execute();
$users = $stmt->get_result();

if ($selectedId <= 0 && $users->num_rows > 0) {
    $first = $users->fetch_assoc();
    $selectedId = (int)$first['user_id'];
    $users->data_seek(0);
}

$selected = null;

// if ($selectedId > 0) {
//     $stmt = $conn->prepare("
//         SELECT 
//             u.*,
//             COUNT(o.order_id) AS total_orders,
//             COALESCE(SUM(o.total_amount), 0) AS total_spent,
//             MAX(o.created_at) AS last_order_date
//         FROM users u
//         LEFT JOIN orders o ON u.user_id = o.user_id
//         WHERE u.user_id = ?
//         GROUP BY u.user_id
//     ");

//     $stmt->bind_param("i", $selectedId);
//     $stmt->execute();
//     $selected = $stmt->get_result()->fetch_assoc();
// }

$orderStmt = $conn->prepare("
    SELECT order_id, total_amount, created_at, status
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");

$logsStmt = $conn->prepare("
    SELECT action_text, created_at
    FROM user_activity_logs
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");


if ($selectedId > 0) {


if($role == 'customer'){


$stmt = $conn->prepare("
SELECT 
u.*,
COUNT(o.order_id) AS total_orders,
COALESCE(SUM(o.total_amount),0) AS total_spent,
MAX(o.created_at) AS last_order_date

FROM users u

LEFT JOIN orders o 
ON u.user_id = o.user_id

WHERE u.user_id = ?

GROUP BY u.user_id
");


}

else if($role == 'shops'){


$stmt = $conn->prepare("
SELECT
shop_id AS user_id,
shop_name AS name,
shop_email AS email,
shop_phone AS phone,
status AS user_status,
'ผู้ขาย' AS role,
NULL AS created_at,
shop_address,
shop_type,
description,
shop_image AS profile_image,
shop_banner

FROM shops

WHERE shop_id = ?
");


}

else if($role == 'riders'){

$stmt = $conn->prepare("
SELECT
rider_id AS user_id,
full_name AS name,
profile_image,
email,
phone,
status AS user_status,
'riders' AS role,
NULL AS created_at,
vehicle_type,
license_plate

FROM riders

WHERE rider_id = ?
");


}


$stmt->bind_param("i",$selectedId);

$stmt->execute();

$selected = $stmt->get_result()->fetch_assoc();


}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>จัดการผู้ใช้งาน | FreshFast</title>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{box-sizing:border-box;font-family:sans-serif}
body{margin:0;background:#f7f7f7;color:#000}
.layout{display:flex;min-height:100vh}
.sidebar{width:252px;background:#98efb8;border-right:1px solid #111;padding:28px 14px}
.logo{display:flex;align-items:center;gap:12px;font-size:22px;font-weight:700;color:#07883b;margin-bottom:90px}
.logo .cart{font-size:48px}
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

.main{flex:1}
.topbar{height:103px;background:#98efb8;border-bottom:1px solid #111;display:flex;align-items:center;justify-content:space-between;padding:0 48px 0 42px}
.topbar h1{font-size:25px;margin:0}
.top-icons{display:flex;gap:38px}
.circle-btn{width:62px;height:62px;background:#fff;border-radius:50%;display:grid;place-items:center;font-size:28px;box-shadow:0 3px 8px rgba(0,0,0,.2)}
.content{padding:28px 26px}
.top-filter{display:grid;grid-template-columns:225px 225px 225px 1fr;gap:24px;margin-bottom:50px}
.role-btn,.search-box{height:74px;border-radius:18px;border:none;background:#fff1b3;font-size:20px;font-weight:700;box-shadow:0 3px 8px rgba(0,0,0,.16);display:flex;align-items:center;justify-content:center;text-decoration:none;color:#000}
.role-btn.active{background:#009536;color:#fff}
.search-box{justify-content:flex-start;padding:0 24px}
.search-box span{font-size:42px;margin-right:18px}
.search-box input{border:none;outline:none;background:transparent;width:100%;font-size:18px;font-weight:600}
.grid{display:grid;grid-template-columns:1fr 305px;gap:28px}
.profile-head{background:#fff1b3;border-radius:18px;padding:8px;display:grid;grid-template-columns:170px 1fr;gap:18px;box-shadow:0 2px 8px rgba(0,0,0,.18);margin-bottom:38px}
.avatar{height:155px;border-radius:18px;background:#eaeaea}
.profile-info{padding:20px 6px;position:relative}
.profile-info h2{margin:0 0 14px;font-size:22px}
.badge{position:absolute;right:18px;top:12px;background:#f5c400;border-radius:15px;padding:3px 14px;font-size:13px;font-weight:700}
.tabs{background:#fff1b3;border-radius:18px;padding:6px;display:grid;grid-template-columns:repeat(4,1fr);gap:34px;box-shadow:0 2px 8px rgba(0,0,0,.18);margin-bottom:34px}
.tab{height:50px;border-radius:14px;background:#fff;border:none;font-size:19px;font-weight:700;box-shadow:0 3px 7px rgba(0,0,0,.16)}
.tab.active{background:#f5c400}
.info-row{display:grid;grid-template-columns:1fr 1fr;gap:34px;margin-bottom:20px}
.card{background:#fff1b3;border-radius:18px;padding:22px 28px;box-shadow:0 2px 8px rgba(0,0,0,.16)}
.card h3{margin:0 0 14px;font-size:20px;border-bottom:1px solid rgba(0,0,0,.35);padding-bottom:12px}
.card p{margin:7px 0;font-size:16px;font-weight:500}
.order-card{background:#fff1b3;border-radius:18px;padding:24px 30px;box-shadow:0 2px 8px rgba(0,0,0,.16)}
.order-title{font-size:22px;font-weight:700;margin-bottom:14px}
table{width:100%;border-collapse:collapse}
th,td{text-align:left;padding:13px 14px;border-top:1px solid rgba(0,0,0,.35);font-size:15px;font-weight:600}
.detail-btn{background:#fff;border:none;border-radius:14px;padding:8px 18px;font-weight:700;box-shadow:0 3px 7px rgba(0,0,0,.18);text-decoration:none;color:#000}
.side-card{background:#fff1b3;border-radius:18px;padding:18px 10px;box-shadow:0 2px 8px rgba(0,0,0,.18);margin-bottom:20px}
.side-card h3{font-size:20px;margin:6px 12px 14px;border-bottom:1px solid rgba(0,0,0,.35);padding-bottom:10px}
.log-item{padding:14px 34px;border-bottom:1px solid rgba(0,0,0,.28);font-weight:600}
.action-form{display:flex;flex-direction:column;gap:14px;padding:0 0 2px}
.action-form button{height:52px;border:none;border-radius:13px;background:#fff;font-size:16px;font-weight:700;box-shadow:0 3px 7px rgba(0,0,0,.16);cursor:pointer}

.user-list{margin-top:18px;display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.user-mini{background:#fff1b3;border-radius:14px;padding:12px;text-decoration:none;color:#000;font-weight:700;box-shadow:0 2px 6px rgba(0,0,0,.12)}
.user-mini.active{outline:3px solid #009536}
.empty{text-align:center;padding:40px;font-size:20px;font-weight:700;color:#777}
        .logo{
    width:190px;
    margin-bottom:80px;
}
.circle-btn i{
    width:34px;
    height:34px;
    stroke-width:2.2;
}
.search-box button{
    border:none;
    background:#009536;
    color:#fff;
    border-radius:10px;
    padding:10px 16px;
    font-weight:700;
    cursor:pointer;
}
.tab-content{
    display:none;
}

.tab-content.active{
    display:block;
}
.tab-content{
    display:none;
}

.tab-content.active{
    display:block;
}

.avatar{
    height:155px;
    width:155px;
    border-radius:18px;
    overflow:hidden;
    background:#eaeaea;
}

.avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
}
.user-table{
    margin-top:20px;
    background:#fff1b3;
    border-radius:18px;
    padding:20px;
    box-shadow:0 2px 8px rgba(0,0,0,.15);
}

.user-table table{
    width:100%;
    border-collapse:collapse;
}

.user-table th{
    padding:15px;
    border-bottom:1px solid #999;
    font-size:18px;
    text-align:left;
}

.user-table td{
    padding:15px;
    border-bottom:1px solid #ddd;
}

.user-table tr{
    cursor:pointer;
    transition:.2s;
}

.user-table tr:hover{
    background:#fff7cf;
}

.active-row{
    background:#ffe36d;
}

@media(max-width:1100px){.sidebar{width:210px}.top-filter{grid-template-columns:1fr 1fr}.grid{grid-template-columns:1fr}.info-row{grid-template-columns:1fr}}
.top-icon,
.circle-btn{
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
    width:15px;
    height:15px;
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
    <h1>จัดการผู้ใช้งาน</h1>
        <div class="top-icons">
            <div class="circle-btn">
                <a href="admin_complaints.php" 
                class="top-icon <?= $pendingComplaint > 0 ? 'has-noti':'' ?>">

                    <i data-lucide="bell"></i>

                    <?php if($pendingComplaint > 0): ?>
                        <span class="noti-dot"></span>
                    <?php endif; ?>

                </a>
            </div>
            <div class="circle-btn">
                <i data-lucide="settings"></i>
            </div>
        </div>
</header>

<section class="content">

<form method="GET" class="top-filter">
    <a class="role-btn <?= $role === 'customer' ? 'active' : '' ?>" href="admin_users.php?role=customer">ผู้ซื้อ</a>
    <a class="role-btn <?= $role === 'shops' ? 'active' : '' ?>" href="admin_users.php?role=shops">ผู้ขาย</a>
    <a class="role-btn <?= $role === 'riders' ? 'active' : '' ?>" href="admin_users.php?role=riders">ไรเดอร์</a>

    <div class="search-box">
        <span>⌕</span>

        <input type="hidden" name="role" value="<?= e($role) ?>">

        <input
            type="text"
            name="search"
            value="<?= e($search) ?>"
            placeholder="ค้นหาผู้ใช้งาน..."
        >

        <button type="submit">ค้นหา</button>
    </div>
</form>

<?php if (!$selected): ?>

<div class="empty">ไม่พบข้อมูลผู้ใช้งาน</div>

<?php else: ?>

<div class="grid">

<div>

    <div class="profile-head">
        <div class="avatar">
        <img
        src="<?= !empty($selected['profile_image'])
        ? e($selected['profile_image'])
        : 'images/default.png' ?>"
        >
        </div>

        <div class="profile-info">
            <div class="badge">
                <?= e(statusThai($selected['user_status'] ?? 'active')) ?>
            </div>

            <h2><?= e($selected['name'] ?? '-') ?></h2>

            <p>
                ลงทะเบียนตั้งแต่
                <?= !empty($selected['created_at']) ? date("d/m/Y", strtotime($selected['created_at'])) : '-' ?>
            </p>

            <p>
                ยอดคำสั่งซื้อทั้งหมด
                <?= number_format((int)($selected['total_orders'] ?? 0)) ?>
            </p>
        </div>
    </div>

        <div class="tabs">
            <button type="button" class="tab <?= $tab === 'profile' ? 'active' : '' ?>" data-tab="profile">ข้อมูลผู้ใช้งาน</button>
            <button type="button" class="tab <?= $tab === 'orders' ? 'active' : '' ?>" data-tab="orders">ประวัติคำสั่งซื้อ</button>
            <button type="button" class="tab <?= $tab === 'address' ? 'active' : '' ?>" data-tab="address">ข้อมูลที่อยู่</button>
            <button type="button" class="tab <?= $tab === 'logs' ? 'active' : '' ?>" data-tab="logs">บันทึกการกระทำ</button>

        </div>

<div class="tab-content <?= $tab === 'profile' ? 'active' : '' ?>" id="profile">
    <div class="info-row">

        <div class="card">
            <h3>ข้อมูลผู้ใช้งาน</h3>
            <p><b>ชื่อผู้ใช้งาน :</b> <?= e($selected['name'] ?? '-') ?></p>
            <p><b>E-mail :</b> <?= e($selected['email'] ?? '-') ?></p>
            <p><b>เบอร์โทร :</b> <?= e($selected['phone'] ?? '-') ?></p>
            <p><b>ประเภท :</b> <?= e(roleThai($selected['role'] ?? '-')) ?></p>
            <p><b>วันที่ลงทะเบียน :</b>
                <?= !empty($selected['created_at']) ? date("d/m/Y H:i", strtotime($selected['created_at'])) : '-' ?>
            </p>
        </div>

        <div class="card">
            <h3>ข้อมูลการใช้งาน</h3>

            <p><b>จำนวนคำสั่งซื้อทั้งหมด :</b>
                <?= number_format((int)($selected['total_orders'] ?? 0)) ?>
            </p>

            <p><b>ยอดค่าใช้จ่ายทั้งหมด :</b>
                <?= number_format((float)($selected['total_spent'] ?? 0), 2) ?> บาท
            </p>

            <p><b>ยอดเฉลี่ยแต่ละคำสั่งซื้อ :</b>
                <?php
                $totalOrders = (int)($selected['total_orders'] ?? 0);
                $totalSpent = (float)($selected['total_spent'] ?? 0);

                if ($totalOrders > 0) {
                    echo number_format($totalSpent / $totalOrders, 2);
                } else {
                    echo "0.00";
                }
                ?>
                บาท
            </p>

            <p><b>วันที่สั่งซื้อล่าสุด :</b>
                <?= !empty($selected['last_order_date']) ? date("d/m/Y H:i", strtotime($selected['last_order_date'])) : '-' ?>
            </p>
        </div>

    </div>
</div>

<div class="tab-content <?= $tab === 'orders' ? 'active' : '' ?>" id="orders">
    <div class="order-card">
        <div class="order-title">ประวัติคำสั่งซื้อ <i data-lucide="chevron-right"></i></div>
        

        <table>
            <thead>
            <tr>
                <th>หมายเลขคำสั่งซื้อ</th>
                <th>ราคารวม</th>
                <th>วันที่ทำการสั่งซื้อ</th>
                <th>สถานะ</th>
                <th>การกระทำ</th>
            </tr>
            </thead>

            <tbody>
            <?php
            $orderStmt->bind_param("i", $selectedId);
            $orderStmt->execute();
            $orderRows = $orderStmt->get_result();
            ?>

            <?php if ($orderRows->num_rows > 0): ?>
                <?php while ($o = $orderRows->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= e($o['order_id']) ?></td>
                        <td><?= number_format((float)$o['total_amount'], 2) ?> บาท</td>
                        <td><?= date("d/m/Y", strtotime($o['created_at'])) ?></td>
                        <td><?= e($o['status']) ?></td>
                        <td>
                            <a class="detail-btn" href="admin_orders.php?search=<?= e($o['order_id']) ?>">
                                ดูเพิ่มเติม
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="empty">ยังไม่มีประวัติคำสั่งซื้อ</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="tab-content <?= $tab === 'address' ? 'active' : '' ?>" id="address">

    <div class="card">
        <h3>ข้อมูลที่อยู่</h3>

        <p>ยังไม่มีข้อมูลที่อยู่</p>
    </div>

</div>

<div class="user-table">

<table>

<thead>
<tr>
    <th>ID</th>
    <th>ชื่อ</th>
    <th>Email</th>
</tr>
</thead>

<tbody>

<?php while($u = $users->fetch_assoc()): ?>

<tr
onclick="location.href='admin_users.php?role=<?=e($role)?>&user_id=<?=$u['user_id']?>&tab=<?=$tab?>'"
class="<?= (int)$u['user_id']==$selectedId ? 'active-row':'' ?>"
>

<td>#<?=e($u['user_id'])?></td>

<td><?=e($u['name'])?></td>

<td><?=e($u['email'])?></td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

</div>

</div>

<aside>

<div class="tab-content <?= $tab === 'logs' ? 'active' : '' ?>" id="logs">
    <div class="side-card">
        <h3>บันทึกการกระทำ</h3>

        <?php
        $logsStmt->bind_param("i", $selectedId);
        $logsStmt->execute();
        $logs = $logsStmt->get_result();
        ?>

        <?php if ($logs->num_rows > 0): ?>
            <?php while ($log = $logs->fetch_assoc()): ?>
                <div class="log-item">
                    <?= date("d/m/Y", strtotime($log['created_at'])) ?><br>
                    <?= e($log['action_text']) ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="log-item">ยังไม่มีบันทึกการกระทำ</div>
        <?php endif; ?>
    </div>
</div>

    <div class="side-card">
        <h3>จัดการสถานะผู้ใช้งาน</h3>

        <form method="POST" class="action-form">
            <input type="hidden" id="currentTabInput" name="tab" value="<?= e($tab) ?>">
            <input type="hidden" name="user_id" value="<?= e($selectedId) ?>">

            <button name="action" value="active">เปิดใช้งาน</button>
            <button name="action" value="suspended">ระงับชั่วคราว</button>
            <button name="action" value="banned">แบนถาวร</button>
            <button name="action" value="closed">ปิดบัญชีตามคำขอผู้ใช้งาน</button>
        </form>
    </div>

</aside>

</div>

<?php endif; ?>

</section>

</main>
<script src="https://unpkg.com/lucide@latest"></script>

<script>
lucide.createIcons();
</script>
</div>

<script>

const tabs = document.querySelectorAll('.tab');
const contents = document.querySelectorAll('.tab-content');
const currentTabInput = document.getElementById('currentTabInput');

tabs.forEach(tab => {

    tab.addEventListener('click', () => {

        tabs.forEach(t => t.classList.remove('active'));
        contents.forEach(c => c.classList.remove('active'));

        tab.classList.add('active');

        const target = tab.dataset.tab;

        document
            .getElementById(target)
            .classList.add('active');

        // อัปเดต hidden input
        currentTabInput.value = target;

    });

});

</script>

</body>
</html>