<?php
session_start();

require_once "db.php";

if (!isset($conn)) {
    require_once "db.php";
    $conn->set_charset("utf8mb4");
}

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

function e($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function statusThai($status) {
    return match ($status) {
        'pending' => 'รอรับออเดอร์',
        'accepted' => 'รับออเดอร์แล้ว',
        'delivering' => 'กำลังจัดส่ง',
        'completed' => 'จัดส่งสำเร็จ',
        'cancelled' => 'ยกเลิก/จัดส่งไม่สำเร็จ',
        default => 'ไม่ทราบสถานะ'
    };
}

function statusClass($status) {
    return match ($status) {
        'pending' => 'st-pending',
        'accepted' => 'st-accepted',
        'delivering' => 'st-delivering',
        'completed' => 'st-completed',
        'cancelled' => 'st-cancelled',
        default => ''
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];

    $allowed = ['pending', 'accepted', 'delivering', 'completed', 'cancelled'];

    if (in_array($new_status, $allowed, true)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin_orders.php");
    exit;
}

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$dateFilter = $_GET['date'] ?? 'all';

$where = [];
$params = [];
$types = "";

if ($search !== '') {
    $where[] = "(o.order_id LIKE ? OR o.receiver_name LIKE ? OR o.phone LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

if ($statusFilter !== 'all') {
    $where[] = "o.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if ($dateFilter === 'today') {
    $where[] = "DATE(o.created_at) = CURDATE()";
} elseif ($dateFilter === 'week') {
    $where[] = "YEARWEEK(o.created_at, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($dateFilter === 'month') {
    $where[] = "MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
}

$whereSql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$sql = "
    SELECT 
        o.*,
        u.name AS customer_name,
        r.full_name AS rider_name,
        COUNT(oi.order_item_id) AS item_count
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    LEFT JOIN riders r ON o.rider_id = r.rider_id
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    $whereSql
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$orders = $stmt->get_result();

$itemSql = "
    SELECT * 
    FROM order_items 
    WHERE order_id = ? 
    ORDER BY order_item_id ASC
";
$itemStmt = $conn->prepare($itemSql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการคำสั่งซื้อ | FreshFast</title>
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

        .menu a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 24px;
            margin-bottom: 18px;
            border-radius: 20px;
            color: #000;
            text-decoration: none;
            font-weight: 600;
            font-size: 17px;
        }

        .menu a.active {
            background: #fff1b3;
            box-shadow: 0 4px 8px rgba(0,0,0,.16);
        }

        .menu .icon {
            font-size: 26px;
            width: 30px;
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
            padding: 28px 28px 34px;
        }

        .filter-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 26px;
            margin-bottom: 14px;
        }

        .search-box {
            width: 340px;
            height: 52px;
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

.date-select{
    height:52px;
    background:#f5c400;
    border:none;
    border-radius:16px;
    padding:0 52px 0 18px;
    font-size:17px;
    font-weight:700;
    box-shadow:0 3px 8px rgba(0,0,0,.18);

    appearance:none;
    -webkit-appearance:none;
    -moz-appearance:none;

    cursor:pointer;

    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='22' height='22' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.7' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");

    background-repeat:no-repeat;
    background-position:right 16px center;
    background-size:18px;
}

        .tabs {
            background: #fff1b3;
            border-radius: 20px;
            padding: 6px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 52px;
            margin: 0 0 26px;
            box-shadow: 0 2px 8px rgba(0,0,0,.22);
        }

        .tab {
            height: 50px;
            border-radius: 15px;
            border: none;
            background: white;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 3px 7px rgba(0,0,0,.18);
            text-decoration: none;
            color: #000;
            display: grid;
            place-items: center;
        }

        .tab.active {
            background: #f5c400;
        }

        .table-card {
            background: #fff1b3;
            border-radius: 18px 18px 0 0;
            padding: 18px 26px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,.18);
            min-height: 660px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 0 24px 16px;
            font-size: 15px;
        }

        td {
            padding: 18px 24px;
            border-top: 1px solid rgba(0,0,0,.35);
            font-size: 16px;
            font-weight: 600;
            vertical-align: middle;
        }

        .order-no {
            font-weight: 700;
        }

        .status {
            font-weight: 700;
        }

        .st-pending {
            color: #9a6a00;
        }

        .st-accepted {
            color: #0b7a36;
        }

        .st-delivering {
            color: #0057c2;
        }

        .st-completed {
            color: #038b35;
        }

        .st-cancelled {
            color: red;
        }

        .detail-btn {
            border: none;
            background: white;
            border-radius: 15px;
            padding: 9px 20px;
            font-size: 15px;
            font-weight: 700;
            box-shadow: 0 3px 8px rgba(0,0,0,.22);
            cursor: pointer;
        }

        .detail-box {
            display: none;
            background: rgba(255,255,255,.65);
        }

        .detail-box td {
            padding: 20px 26px;
            border-top: none;
        }

        .detail-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }

        .detail-panel {
            background: white;
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,.12);
        }

        .detail-panel h3 {
            margin: 0 0 12px;
            font-size: 18px;
        }

        .small-table td,
        .small-table th {
            padding: 8px;
            border-top: 1px solid #ddd;
            font-size: 14px;
        }

        .status-form {
            display: flex;
            gap: 10px;
            margin-top: 14px;
        }

        .status-form select {
            flex: 1;
            padding: 10px;
            border-radius: 12px;
            border: 1px solid #ccc;
            font-weight: 600;
        }

        .save-btn {
            border: none;
            background: #f5c400;
            border-radius: 12px;
            padding: 10px 16px;
            font-weight: 700;
            cursor: pointer;
        }

        .empty {
            text-align: center;
            padding: 80px;
            font-size: 22px;
            font-weight: 700;
            color: #777;
        }
        .logo{
                width:190px;
                margin-bottom:80px;
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
.tabs{
    display:flex;
    gap:18px;
    flex-wrap:wrap;
}

.tab{
    height:48px;
    padding:0 22px;
    border-radius:16px;
    background:#fff1b3;
    text-decoration:none;
    color:#111;
    font-weight:700;
    display:flex;
    align-items:center;
    gap:10px;
    box-shadow:0 3px 8px rgba(0,0,0,.12);
    transition:.2s;
}

.tab:hover{
    transform:translateY(-2px);
}

.tab.active{
    background:#ffc400;
    color:#000;
}

.tab i{
    width:20px;
    height:20px;
    stroke-width:2.4;
}
        @media (max-width: 1100px) {
            .sidebar {
                width: 210px;
            }

            .tabs {
                gap: 12px;
            }

            .detail-content {
                grid-template-columns: 1fr;
            }

            th, td {
                padding-left: 10px;
                padding-right: 10px;
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
            <h1>จัดการคำสั่งซื้อ</h1>
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
                    <option value="all" <?= $dateFilter === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                    <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>วันนี้</option>
                    <option value="week" <?= $dateFilter === 'week' ? 'selected' : '' ?>>สัปดาห์นี้</option>
                    <option value="month" <?= $dateFilter === 'month' ? 'selected' : '' ?>>เดือนนี้</option>
                </select>

                <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
            </form>

                <div class="tabs">

                    <a class="tab <?= $statusFilter === 'all' ? 'active' : '' ?>" 
                    href="?status=all&date=<?= e($dateFilter) ?>&search=<?= e($search) ?>">
                        <i data-lucide="layout-grid"></i>
                        ทั้งหมด
                    </a>

                    <a class="tab <?= $statusFilter === 'delivering' ? 'active' : '' ?>" 
                    href="?status=delivering&date=<?= e($dateFilter) ?>&search=<?= e($search) ?>">
                        <i data-lucide="truck"></i>
                        กำลังจัดส่ง
                    </a>

                    <a class="tab <?= $statusFilter === 'completed' ? 'active' : '' ?>" 
                    href="?status=completed&date=<?= e($dateFilter) ?>&search=<?= e($search) ?>">
                        <i data-lucide="circle-check-big"></i>
                        จัดส่งสำเร็จ
                    </a>

                    <a class="tab <?= $statusFilter === 'cancelled' ? 'active' : '' ?>" 
                    href="?status=cancelled&date=<?= e($dateFilter) ?>&search=<?= e($search) ?>">
                        <i data-lucide="circle-x"></i>
                        ยกเลิก/จัดส่งไม่สำเร็จ
                    </a>

                </div>

            <div class="table-card">
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
                    <?php if ($orders->num_rows > 0): ?>
                        <?php while ($order = $orders->fetch_assoc()): ?>
                            <tr>
                                <td class="order-no">#<?= e($order['order_id']) ?></td>
                                <td><?= number_format((float)$order['total_amount'], 2) ?> บาท</td>
                                <td><?= date("d/m/Y H:i", strtotime($order['created_at'])) ?></td>
                                <td>
                                    <span class="status <?= statusClass($order['status']) ?>">
                                        <?= statusThai($order['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button 
                                        type="button" 
                                        class="detail-btn" 
                                        onclick="toggleDetail('detail-<?= e($order['order_id']) ?>')"
                                    >
                                        ดูเพิ่มเติม
                                    </button>
                                </td>
                            </tr>

                            <tr id="detail-<?= e($order['order_id']) ?>" class="detail-box">
                                <td colspan="5">
                                    <div class="detail-content">
                                        <div class="detail-panel">
                                            <h3>ข้อมูลลูกค้า</h3>
                                            <p><b>ชื่อลูกค้า:</b> <?= e($order['customer_name'] ?? '-') ?></p>
                                            <p><b>ชื่อผู้รับ:</b> <?= e($order['receiver_name']) ?></p>
                                            <p><b>เบอร์โทร:</b> <?= e($order['phone']) ?></p>
                                            <p><b>ที่อยู่:</b> <?= e($order['delivery_address'] ?? '-') ?></p>
                                            <p><b>หมายเหตุ:</b> <?= e($order['note'] ?: '-') ?></p>
                                            <p><b>ไรเดอร์:</b> <?= e($order['rider_name'] ?? 'ยังไม่ได้มอบหมาย') ?></p>

                                            <form method="POST" class="status-form">
                                                <input type="hidden" name="order_id" value="<?= e($order['order_id']) ?>">
                                                <select name="status">
                                                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>รอรับออเดอร์</option>
                                                    <option value="accepted" <?= $order['status'] === 'accepted' ? 'selected' : '' ?>>รับออเดอร์แล้ว</option>
                                                    <option value="delivering" <?= $order['status'] === 'delivering' ? 'selected' : '' ?>>กำลังจัดส่ง</option>
                                                    <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>จัดส่งสำเร็จ</option>
                                                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>ยกเลิก/จัดส่งไม่สำเร็จ</option>
                                                </select>
                                                <button class="save-btn" name="update_status" value="1">บันทึก</button>
                                            </form>
                                        </div>

                                        <div class="detail-panel">
                                            <h3>รายการสินค้า</h3>
                                            <table class="small-table">
                                                <thead>
                                                    <tr>
                                                        <th>สินค้า</th>
                                                        <th>ร้าน</th>
                                                        <th>จำนวน</th>
                                                        <th>รวม</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php
                                                    $itemStmt->bind_param("i", $order['order_id']);
                                                    $itemStmt->execute();
                                                    $items = $itemStmt->get_result();
                                                ?>
                                                <?php while ($item = $items->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?= e($item['product_name']) ?></td>
                                                        <td><?= e($item['shop_name']) ?></td>
                                                        <td><?= e($item['quantity']) ?></td>
                                                        <td><?= number_format((float)$item['line_total'], 2) ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                                </tbody>
                                            </table>

                                            <p><b>ค่าสินค้า:</b> <?= number_format((float)$order['subtotal'], 2) ?> บาท</p>
                                            <p><b>ค่าส่ง:</b> <?= number_format((float)$order['delivery_fee'], 2) ?> บาท</p>
                                            <p><b>ส่วนลด:</b> <?= number_format((float)$order['discount'], 2) ?> บาท</p>
                                            <p><b>ยอดสุทธิ:</b> <?= number_format((float)$order['total_amount'], 2) ?> บาท</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty">ไม่พบข้อมูลคำสั่งซื้อ</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </section>
    </main>
</div>

<script>
function toggleDetail(id) {
    const row = document.getElementById(id);
    row.style.display = row.style.display === "table-row" ? "none" : "table-row";
}
</script>
<script>
lucide.createIcons();
</script>
</body>
</html>