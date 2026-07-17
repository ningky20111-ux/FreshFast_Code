<?php
session_start();
require_once 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
// เช็คล็อกอิน
if (!isset($_SESSION['rider_id'])) {
    header("Location: rider_login.php");
    exit;
}

$tab = $_GET['tab'] ?? 'new';
$allowed_tabs = ['new', 'shipping', 'history'];
if (!in_array($tab, $allowed_tabs, true)) {
    $tab = 'new';
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function cardClass(string $status): string
{
    return match ($status) {
        'completed' => 'job-card job-card--history',
        'cancelled' => 'job-card job-card--cancelled',
        default => 'job-card job-card--active',
    };
}

// map tab -> status ในฐานข้อมูล
$whereStatus = "o.status = 'waiting_rider'";
if ($tab === 'shipping') {
    $whereStatus = "o.status = 'delivering'";
} elseif ($tab === 'history') {
    $whereStatus = "o.status = 'completed'";
}

/*
|--------------------------------------------------------------------------
| ดึงออเดอร์จริงจากฐานข้อมูล
|--------------------------------------------------------------------------
| ใช้ users + orders
| ชื่อลูกค้าจะดึงจาก users.name
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        o.order_id,
        o.status,
        o.delivery_address,
        o.created_at,
        u.name AS customer_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    WHERE {$whereStatus}
    ORDER BY o.order_id DESC
";

$result = $conn->query($sql);
$current_jobs = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $current_jobs[] = [
            'id' => (int)$row['order_id'],
            'order_code' => '#FF-2026-' . str_pad((string)$row['order_id'], 4, '0', STR_PAD_LEFT),
            'customer_name' => $row['customer_name'] ?: 'ไม่ระบุชื่อ',
            'address' => $row['delivery_address'] ?: '-',
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }
}
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Rider Home | FreshFast</title>
    <style>
        :root{
            --bg:#efefef;
            --green:#0c9638;
            --green-soft:#95e4ab;
            --yellow-soft:#f3e19d;
            --yellow:#f6c60c;
            --text:#111111;
            --muted:#666666;
            --gray-card:#d7d7d7;
            --red-soft:#f3aaaa;
            --white:#ffffff;
            --shadow:0 4px 14px rgba(0,0,0,.08);
            --radius:16px;
        }

        *{
            box-sizing:border-box;
        }

        body{
            margin:0;
            background:var(--bg);
            font-family:Arial, Helvetica, sans-serif;
            color:var(--text);
        }

        .app{
            max-width:430px;
            margin:0 auto;
            min-height:100vh;
            padding:20px 12px 32px;
        }

        .brand{
            display:flex;
            justify-content:center;
            align-items:center;
            margin:34px 0 26px;
        }

        .brand img{
            width:200px;
            max-width:100%;
            height:auto;
            display:block;
        }

        .tabs{
            background:var(--yellow-soft);
            border-radius:18px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            padding:8px;
            gap:8px;
            margin-bottom:16px;
        }

        .tab{
            flex:1;
            text-align:center;
            padding:14px 8px;
            border-radius:14px;
            text-decoration:none;
            color:var(--text);
            font-weight:800;
            font-size:15px;
            transition:.2s ease;
        }

        .tab.active{
            color:var(--green);
            text-decoration:underline;
            text-underline-offset:4px;
        }

        .jobs{
            display:flex;
            flex-direction:column;
            gap:14px;
        }

        .job-card{
            display:block;
            border-radius:16px;
            text-decoration:none;
            padding:18px 16px;
            box-shadow:var(--shadow);
            color:var(--text);
        }

        .job-card--active{
            background:var(--green-soft);
        }

        .job-card--history{
            background:var(--gray-card);
        }

        .job-card--cancelled{
            background:var(--red-soft);
        }

        .job-code{
            font-size:14px;
            font-weight:900;
            margin-bottom:8px;
        }

        .job-name{
            font-size:14px;
            font-weight:700;
            margin-bottom:4px;
        }

        .job-address{
            font-size:13px;
            color:rgba(0,0,0,.65);
            margin-bottom:4px;
        }

        .job-date{
            font-size:12px;
            color:rgba(0,0,0,.55);
        }

        .empty{
            background:#fff;
            border-radius:16px;
            padding:24px 18px;
            text-align:center;
            color:var(--muted);
            box-shadow:var(--shadow);
        }

        .top-mini{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:12px;
        }

        .email{
            font-size:12px;
            color:var(--muted);
            word-break:break-all;
        }

        .logout-link{
            font-size:12px;
            color:#b00020;
            text-decoration:none;
            font-weight:700;
        }
    </style>
</head>
<body>
    <div class="app">
        <div class="top-mini">
            <div class="email"><?= e($_SESSION['rider_email']) ?></div>
            <a class="logout-link" href="rider_logout.php">ออกจากระบบ</a>
        </div>

        <div class="brand">
            <img src="assets/images/logo_ok.png" alt="FreshFast">
        </div>

        <div class="tabs">
            <a class="tab <?= $tab === 'new' ? 'active' : '' ?>" href="rider_home.php?tab=new">ใหม่</a>
            <a class="tab <?= $tab === 'shipping' ? 'active' : '' ?>" href="rider_home.php?tab=shipping">กำลังจัดส่ง</a>
            <a class="tab <?= $tab === 'history' ? 'active' : '' ?>" href="rider_home.php?tab=history">ประวัติงาน</a>
        </div>

        <div class="jobs">
            <?php if (!empty($current_jobs)): ?>
                <?php foreach ($current_jobs as $job): ?>
                    <a class="<?= cardClass($job['status']) ?>" href="rider_order_detail.php?order_id=<?= (int)$job['id'] ?>">
                        <div class="job-code"><?= e($job['order_code']) ?></div>
                        <div class="job-name"><?= e($job['customer_name']) ?></div>
                        <div class="job-address">📍 <?= e($job['address']) ?></div>
                        <div class="job-date"><?= e($job['created_at']) ?></div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty">ยังไม่มีรายการในหมวดนี้</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>