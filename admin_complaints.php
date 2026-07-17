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

$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'latest';

$where = "WHERE 1=1";
$params = [];
$types = "";

if ($search !== '') {
    $where .= " AND (c.order_id LIKE ? OR c.complaint_text LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

$orderBy = "c.created_at DESC";
if ($sort === 'oldest') {
    $orderBy = "c.created_at ASC";
}

$sql = "
    SELECT 
        c.*,
        u.name AS user_name,
        u.email AS user_email
    FROM complaints c
    LEFT JOIN users u ON c.user_id = u.user_id
    $where
    ORDER BY $orderBy
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$complaints = $stmt->get_result();

$todayResult = $conn->query("
    SELECT COUNT(*) AS total 
    FROM complaints 
    WHERE DATE(created_at) = CURDATE()
");
$today = $todayResult ? $todayResult->fetch_assoc()['total'] : 0;

$pendingResult = $conn->query("
    SELECT COUNT(*) AS total 
    FROM complaints 
    WHERE status = 'pending'
");
$pending = $pendingResult ? $pendingResult->fetch_assoc()['total'] : 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>คำร้องเรียน | FreshFast</title>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{box-sizing:border-box;font-family:sans-serif}
body{margin:0;background:#fff;color:#000}
.layout{display:flex;min-height:100vh}
.sidebar{width:252px;background:#98efb8;border-right:1px solid #111;padding:28px 15px}
.logo{display:flex;align-items:center;gap:10px;font-weight:700;color:#008c3a;font-size:20px;margin-bottom:80px}
.logo-icon{font-size:54px}
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

.main{flex:1;background:#fff}
.topbar{height:103px;background:#98efb8;border-bottom:1px solid #111;display:flex;align-items:center;justify-content:space-between;padding:0 42px}
.topbar h1{font-size:26px;margin:0}
.top-icons{display:flex;gap:38px}
.circle-btn{width:62px;height:62px;border-radius:50%;background:#fff;display:grid;place-items:center;font-size:30px;box-shadow:0 3px 8px rgba(0,0,0,.18)}
.content{padding:24px 28px}
.stats{display:grid;grid-template-columns:1fr 1fr;gap:90px;margin-bottom:32px}
.card{height:154px;border-radius:18px;padding:40px 42px;box-shadow:0 3px 8px rgba(0,0,0,.2)}
.card h2{margin:0 0 10px;font-size:25px}
.card p{margin:0;font-size:20px;font-weight:700}
.yellow{background:#fff1b3}
.red{background:#ff0000;color:#fff}
.tools{display:flex;justify-content:flex-end;gap:26px;margin-bottom:14px}
.search-box{width:340px;height:45px;background:#fff1b3;border-radius:24px;display:flex;align-items:center;padding:0 18px;gap:12px;box-shadow:0 2px 5px rgba(0,0,0,.25)}
.search-box span{font-size:30px}
.search-box input{border:none;outline:none;background:transparent;width:100%;font-size:18px;color:#777}
.sort-select{height:42px;min-width:116px;border:none;border-radius:14px;background:#ffc400;font-size:18px;font-weight:700;padding:0 14px;cursor:pointer;box-shadow:0 3px 7px rgba(0,0,0,.2)}
.panel{background:#fff1b3;border-radius:18px;padding:28px 24px 10px;box-shadow:0 2px 8px rgba(0,0,0,.22)}
.table{width:100%;border-collapse:collapse;font-size:17px}
.table th{text-align:left;padding:14px 26px;border-bottom:1px solid rgba(0,0,0,.45)}
.table td{padding:19px 26px;border-bottom:1px solid rgba(0,0,0,.35);font-weight:600}
.pending{color:red}
.done{color:#000}
.message{max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.empty{text-align:center;padding:40px;font-weight:800;font-size:20px}
.dots{text-align:center;font-size:36px;color:#ffc400;letter-spacing:4px}


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
*{
    box-sizing:border-box;
    font-family:sans-serif;
}
.select-wrap{
    position:relative;
    display:flex;
    align-items:center;
}

.sort-select{
    height:42px;
    min-width:140px;
    border:none;
    border-radius:14px;
    background:#ffc400;
    font-size:18px;
    font-weight:700;
    padding:0 42px 0 16px;
    cursor:pointer;
    box-shadow:0 3px 7px rgba(0,0,0,.2);

    appearance:none;
    -webkit-appearance:none;
    -moz-appearance:none;
}

.select-icon{
    position:absolute;
    right:14px;
    width:20px;
    height:20px;
    pointer-events:none;
    color:#000;
}
.notification-alert{
    color:red;
    animation: shake .8s infinite;
}

@keyframes shake{
    0%,100%{
        transform:rotate(0);
    }
    25%{
        transform:rotate(-15deg);
    }
    75%{
        transform:rotate(15deg);
    }
}
</style>
</head>

<body>
<div class="layout">
<?php include "includes/admin_sideber.php"; ?>

<main class="main">
    <header class="topbar">
        <h1>คำร้องเรียน</h1>
            <div class="top-icons">
                <a href="admin_complaints.php" class="top-icon">
                    <i data-lucide="bell" 
                    class="<?= $pending > 0 ? 'notification-alert' : '' ?>">
                    </i>
                </a>

                <div class="top-icon">
                    <i data-lucide="settings"></i>
                </div>
            </div>
    </header>

    <section class="content">

        <div class="stats">
            <div class="card yellow">
                <h2>จำนวนออเดอร์</h2>
                <p>คำร้องเรียนวันนี้ <?= number_format((int)$today) ?> รายการ</p>
            </div>

            <div class="card red">
                <h2><?= number_format((int)$pending) ?></h2>
                <p>กำลังรอการตอบกลับ</p>
            </div>
        </div>

        <form class="tools" method="GET" action="admin_complaints.php">
            <div class="search-box">
                <span>⌕</span>
                <input type="text" name="search" placeholder="ค้นหาหมายเลขออเดอร์" value="<?= e($search) ?>">
            </div>

            <div class="select-wrap">
                <select name="sort" class="sort-select" onchange="this.form.submit()">
                    <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>ล่าสุด</option>
                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>เก่าสุด</option>
                </select>

                <i data-lucide="chevron-down" class="select-icon"></i>
            </div>
        </form>

        <div class="panel">
            <table class="table">
                <thead>
                    <tr>
                        <th>หมายเลขคำสั่งซื้อ</th>
                        <th>วัน / เวลา</th>
                        <th>ข้อความ</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($complaints && $complaints->num_rows > 0): ?>
                    <?php while ($row = $complaints->fetch_assoc()): ?>
                        <tr>
                            <td class="<?= $row['status'] === 'pending' ? 'pending' : 'done' ?>">
                                #<?= e($row['order_id']) ?>
                            </td>

                            <td>
                                <?= date("d/m/Y H:i", strtotime($row['created_at'])) ?>
                            </td>

                            <td class="message">
                                <?= e($row['complaint_text']) ?>
                            </td>

                            <td class="<?= $row['status'] === 'pending' ? 'pending' : 'done' ?>">
                                <?php if ($row['status'] === 'pending'): ?>
                                    <a href="admin_complaint_reply.php?id=<?= (int)$row['complaint_id'] ?>" style="color:red;text-decoration:none;font-weight:700;">
                                        กำลังรอการตอบกลับ
                                    </a>
                                <?php else: ?>
                                    สำเร็จ
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="empty">ยังไม่มีคำร้องเรียน</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>

            <!-- <div class="dots"></div> -->
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