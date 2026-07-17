<?php
session_start();
require_once "db.php";

if (!isset($conn)) {
    require_once "db.php";
    $conn->set_charset("utf8mb4");
}

function e($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

$platformRate = 0.22;
$shopRate = 1 - $platformRate;

$search = trim($_GET['search'] ?? '');
$dateFilter = $_GET['date'] ?? 'today';

$where = ["o.status = 'completed'"];
$params = [];
$types = "";

if ($search !== '') {
    $where[] = "o.order_id LIKE ?";
    $params[] = "%{$search}%";
    $types .= "s";
}

if ($dateFilter === 'today') {
    $where[] = "DATE(o.created_at) = CURDATE()";
} elseif ($dateFilter === 'month') {
    $where[] = "MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
} elseif ($dateFilter === 'all') {
    // ไม่ต้องเพิ่มเงื่อนไข
}

$whereSql = "WHERE " . implode(" AND ", $where);

function getOne($conn, $sql) {
    $result = $conn->query($sql);
    return $result ? $result->fetch_assoc() : [];
}

$today = getOne($conn, "
    SELECT 
        COUNT(*) AS total_orders,
        COALESCE(SUM(total_amount), 0) AS total_sales
    FROM orders
    WHERE status = 'completed'
    AND DATE(created_at) = CURDATE()
");

$month = getOne($conn, "
    SELECT 
        COUNT(*) AS total_orders,
        COALESCE(SUM(total_amount), 0) AS total_sales
    FROM orders
    WHERE status = 'completed'
    AND MONTH(created_at) = MONTH(CURDATE())
    AND YEAR(created_at) = YEAR(CURDATE())
");

$all = getOne($conn, "
    SELECT 
        COALESCE(SUM(total_amount), 0) AS total_sales
    FROM orders
    WHERE status = 'completed'
");

$platformIncome = (float)$all['total_sales'] * $platformRate;
$shopIncome = (float)$all['total_sales'] * $shopRate;

$sql = "
    SELECT 
        o.order_id,
        o.total_amount,
        o.created_at
    FROM orders o
    $whereSql
    ORDER BY o.created_at DESC
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>การเงิน | FreshFast</title>
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
            background: white;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 28px;
            box-shadow: 0 3px 8px rgba(0,0,0,.2);
        }

        .content {
            padding: 24px 28px 34px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px 78px;
            margin-bottom: 18px;
        }

        .card {
            background: #fff1b3;
            border-radius: 18px;
            min-height: 128px;
            padding: 34px 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,.18);
            position: relative;
        }

        .card.green {
            background: #98efb8;
        }

        .card h2 {
            margin: 0 0 8px;
            font-size: 32px;
        }

        .card p {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }

        .card .amount {
            top: 38px;
            position: absolute;
            bottom: 24px;
            font-size: 34px;
            font-weight: 700;
            right:90px;

        }

        .card .icon {
            position: absolute;
            right: 34px;
            top: 20px;
        }

        .card .icon i{
            width:38px;
            height:38px;
            color:#111;
            stroke-width:2.2;
        }

        .filter-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 26px;
            margin: 12px 0;
        }

        .search-box {
            width: 340px;
            height: 46px;
            background: #fff1b3;
            border-radius: 18px;
            display: flex;
            align-items: center;
            padding: 0 18px;
            box-shadow: 0 2px 6px rgba(0,0,0,.18);
        }

        .search-box span {
            font-size: 34px;
            margin-right: 12px;
        }

        .search-box input {
            border: none;
            outline: none;
            background: transparent;
            width: 100%;
            font-size: 17px;
            font-weight: 600;
        }

        .date-select {
            height: 46px;
            background: #f5c400;
            border: none;
            border-radius: 16px;
            padding: 0 16px;
            font-size: 17px;
            font-weight: 700;
            box-shadow: 0 3px 8px rgba(0,0,0,.18);
        }

        .table-card {
            background: #fff1b3;
            border-radius: 18px;
            padding: 24px 26px 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.18);
            min-height: 444px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 8px 24px 18px;
            font-size: 15px;
        }

        td {
            padding: 20px 24px;
            border-top: 1px solid rgba(0,0,0,.35);
            font-size: 16px;
            font-weight: 600;
        }

        .empty {
            text-align: center;
            padding: 70px;
            font-size: 22px;
            color: #777;
            font-weight: 700;
        }

        .dots {
            text-align: center;
            margin-top: 8px;
        }

        .dot {
            display: inline-block;
            width: 14px;
            height: 14px;
            background: #ccc;
            border-radius: 50%;
            margin: 0 2px;
        }

        .dot.active {
            background: #f5c400;
        }
        .logo{
    width:190px;
    margin-bottom:80px;
}
    .card .icon i{
        width:38px;
        height:38px;
        color:black;
        stroke-width:2.2;
    }
    .menu a i{
        width:28px;
        height:28px;
        stroke-width:2.2;
        color:black;
        flex-shrink:0;
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
.date-select{
    height:46px;
    background:#f5c400;
    border:none;
    border-radius:16px;
    padding:0 50px 0 16px;
    font-size:17px;
    font-weight:700;
    box-shadow:0 3px 8px rgba(0,0,0,.18);

    appearance:none;
    -webkit-appearance:none;
    -moz-appearance:none;

    cursor:pointer;

    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='22' height='22' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");

    background-repeat:no-repeat;
    background-position:right 16px center;
    background-size:20px;
}

        @media (max-width: 1100px) {
            .sidebar {
                width: 210px;
            }

            .summary-grid {
                grid-template-columns: 1fr;
                gap: 18px;
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
            <h1>การเงิน</h1>
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

            <div class="summary-grid">
                <div class="card">
                    <h2><?= number_format($platformIncome, 2) ?></h2>
                    <p>รายได้สุทธิของแพลตฟอร์ม <?= $platformRate * 100 ?>%</p>
                    <small>(เดือนนี้)</small>
                </div>

                <div class="card">
                    <h2><?= number_format($shopIncome, 2) ?></h2>
                    <p>รายได้ร้านค้า</p>
                    <small>(เดือนนี้)</small>
                </div>

                <div class="card green">
                    <p>ยอดขายทั้งหมดวันนี้</p>
                    <small>(จำนวนออเดอร์)</small>
                    <div class="amount"><?= number_format((int)$today['total_orders']) ?></div>
                    <div class="icon">
                        <i data-lucide="wallet"></i>
                    </div>
                </div>

                <div class="card">
                    <p>ยอดขายเดือนนี้</p>
                    <small>(จำนวนออเดอร์)</small>
                    <div class="amount"><?= number_format((int)$month['total_orders']) ?></div>
                    <div class="icon">
                        <i data-lucide="wallet"></i>
                    </div>
                </div>
            </div>

            <form method="GET" class="filter-row">
                <div class="search-box">
                    <span>⌕</span>
                    <input 
                        type="text" 
                        name="search" 
                        value="<?= e($search) ?>" 
                        placeholder="ค้นหาหมายเลขออเดอร์"
                    >
                </div>

            <select name="date" class="date-select" onchange="this.form.submit()">
                <option value="today">วันนี้</option>
                <option value="month">เดือนนี้</option>
                <option value="all">ทั้งหมด</option>
            </select>
            </form>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>หมายเลขคำสั่งซื้อ</th>
                            <th>ราคารวม</th>
                            <th>รายได้แพลตฟอร์ม</th>
                            <th>โอนให้ร้านค้า</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($orders->num_rows > 0): ?>
                        <?php while ($order = $orders->fetch_assoc()): ?>
                            <?php
                                $total = (float)$order['total_amount'];
                                $platformFee = $total * $platformRate;
                                $shopTransfer = $total * $shopRate;
                            ?>
                            <tr>
                                <td>#<?= e($order['order_id']) ?></td>
                                <td><?= number_format($total, 2) ?> บาท</td>
                                <td><?= number_format($platformFee, 2) ?> บาท</td>
                                <td><?= number_format($shopTransfer, 2) ?> บาท</td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="empty">ไม่พบข้อมูลการเงิน</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="dots">
                <span class="dot active"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </div>

        </section>
    </main>
</div>
<script>
lucide.createIcons();
</script>

<script src="https://unpkg.com/lucide@latest"></script>
</body>
</html>