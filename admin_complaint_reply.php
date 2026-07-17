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

$complaintId = (int)($_GET['id'] ?? 0);

if ($complaintId <= 0) {
    die("ไม่พบคำร้องเรียน");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reply = trim($_POST['admin_reply'] ?? '');

    if ($reply === '') {
        $error = "กรุณากรอกข้อความตอบกลับ";
    } else {
        $adminId = (int)$_SESSION['user_id'];

        $stmt = $conn->prepare("
            UPDATE complaints
            SET 
                admin_reply = ?,
                replied_at = NOW(),
                replied_by = ?,
                status = 'done'
            WHERE complaint_id = ?
        ");

        if (!$stmt) {
            die("SQL Error: " . $conn->error);
        }

        $stmt->bind_param("sii", $reply, $adminId, $complaintId);

        if ($stmt->execute()) {
            header("Location: admin_complaints.php");
            exit;
        } else {
            $error = "ตอบกลับไม่สำเร็จ";
        }
    }
}

$stmt = $conn->prepare("
    SELECT 
        c.*,
        o.total_amount,
        o.created_at AS order_created_at,
        u.name AS user_name,
        u.email AS user_email
    FROM complaints c
    LEFT JOIN orders o ON c.order_id = o.order_id
    LEFT JOIN users u ON c.user_id = u.user_id
    WHERE c.complaint_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $complaintId);
$stmt->execute();
$complaint = $stmt->get_result()->fetch_assoc();

if (!$complaint) {
    die("ไม่พบข้อมูลคำร้องเรียน");
}

$orderId = (int)$complaint['order_id'];

$itemStmt = $conn->prepare("
    SELECT *
    FROM order_items
    WHERE order_id = ?
");

$itemStmt->bind_param("i", $orderId);
$itemStmt->execute();
$items = $itemStmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ตอบกลับคำร้องเรียน | FreshFast</title>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{box-sizing:border-box;font-family:sans-serif}
body{margin:0;background:#fff1b3;color:#000}
a{text-decoration:none;color:inherit}

.topbar{
    height:103px;
    background:#98efb8;
    border-bottom:1px solid #111;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 42px;
}

.logo{
    display:flex;
    align-items:center;
    gap:10px;
    color:#008c3a;
    font-size:20px;
    font-weight:700;
}

.logo-icon{font-size:54px}

.top-title{
    font-size:26px;
    font-weight:700;
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

.page-title{
    text-align:center;
    margin:46px 0 28px;
    font-size:30px;
}

.complaint-box{
    max-width:850px;
    background:#fff;
    margin:0 auto 28px;
    padding:28px 40px;
    border-radius:18px;
    box-shadow:0 4px 10px rgba(0,0,0,.18);
    font-size:17px;
    font-weight:600;
}

.time{
    text-align:center;
    font-size:18px;
    font-weight:700;
    margin-bottom:32px;
}

.content-wrap{
    max-width:1250px;
    margin:0 auto;
    display:grid;
    grid-template-columns:1.5fr .95fr;
    gap:80px;
    padding:0 54px 50px;
}

.order-card{
    background:#fff;
    border-radius:18px;
    overflow:hidden;
}

.order-head{
    background:#f5c400;
    padding:28px 54px;
    font-weight:700;
    line-height:1.8;
}

.order-body{
    padding:28px 34px;
}

.info{
    font-weight:700;
    line-height:1.8;
    margin-bottom:30px;
}

.item-row{
    display:grid;
    grid-template-columns:1fr 120px 80px 120px;
    border-top:1px solid #aaa;
    padding:22px 26px;
    font-weight:700;
}

.side-card{
    background:#fff;
    border-radius:18px;
    overflow:hidden;
    margin-bottom:28px;
}

.side-head{
    background:#009b39;
    color:#fff;
    padding:24px 34px;
    font-size:18px;
    font-weight:700;
}

.side-body{
    padding:28px 34px;
    font-weight:700;
    line-height:2.2;
}

.reply-box{
    background:#fff;
    border-radius:18px;
    padding:26px 34px;
    margin-top:28px;
}

.reply-box textarea{
    width:100%;
    min-height:140px;
    border:2px solid #222;
    border-radius:16px;
    padding:16px;
    font-size:17px;
    resize:none;
    outline:none;
}

.actions{
    display:flex;
    justify-content:center;
    gap:18px;
    margin-top:24px;
}

.btn{
    border:none;
    border-radius:14px;
    padding:10px 32px;
    font-size:18px;
    font-weight:700;
    color:#fff;
    cursor:pointer;
}

.green{background:#009b39}
.red{background:#ff0000}

.error{
    background:#ffd1d1;
    color:#b00000;
    padding:12px 18px;
    border-radius:12px;
    margin-bottom:16px;
    font-weight:700;
}
</style>
</head>

<body>

<header class="topbar">
    <a href="admin_complaints.php" class="logo">
        <div class="logo-icon">🛒</div>
        <div>FRESHFAST</div>
    </a>

    <div class="top-title">คำร้องเรียน</div>

    <div class="top-icons">
        <div class="circle-btn">🔔</div>
        <div class="circle-btn">⚙️</div>
    </div>
</header>

<h1 class="page-title">คำร้องเรียน</h1>

<div class="complaint-box">
    <?= e($complaint['complaint_text']) ?>
</div>

<div class="time">
    <?= date("d / m / Y เวลา H:i น.", strtotime($complaint['created_at'])) ?>
</div>

<div class="content-wrap">

    <div>
        <div class="order-card">
            <div class="order-head">
                หมายเลขคำสั่งซื้อ<br>
                #<?= e($orderId) ?>
            </div>

            <div class="order-body">
                <div class="info">
                    ชื่อลูกค้า <?= e($complaint['user_name'] ?? '-') ?><br>
                    อีเมล <?= e($complaint['user_email'] ?? '-') ?><br>
                    วันที่สั่งซื้อ <?= !empty($complaint['order_created_at']) ? e(date("d/m/Y H:i", strtotime($complaint['order_created_at']))) : '-' ?>
                </div>

                <?php if ($items && $items->num_rows > 0): ?>
                    <?php while ($item = $items->fetch_assoc()): ?>
                        <?php
                            $qty = (int)($item['quantity'] ?? 1);
                            $price = (float)($item['price'] ?? $item['unit_price'] ?? 0);
                            $subtotal = (float)($item['line_total'] ?? $item['subtotal'] ?? ($price * $qty));
                        ?>
                        <div class="item-row">
                            <div><?= e($item['product_name'] ?? 'ชื่อสินค้า') ?></div>
                            <div><?= number_format($price, 2) ?> บ.</div>
                            <div>x<?= $qty ?></div>
                            <div><?= number_format($subtotal, 2) ?> บ.</div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="item-row">
                        <div>ไม่มีรายการสินค้า</div>
                        <div>-</div>
                        <div>-</div>
                        <div>-</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" class="reply-box">
            <?php if (!empty($error)): ?>
                <div class="error"><?= e($error) ?></div>
            <?php endif; ?>

            <textarea name="admin_reply" placeholder="พิมพ์ข้อความตอบกลับลูกค้า"><?= e($complaint['admin_reply'] ?? '') ?></textarea>

            <div class="actions">
                <a href="admin_complaints.php" class="btn red">ยกเลิก</a>
                <button type="submit" class="btn green">ตอบกลับแล้ว</button>
            </div>
        </form>
    </div>

    <div>
        <div class="side-card">
            <div class="side-head">ข้อมูลการชำระเงิน</div>
            <div class="side-body">
                รายการสั่งซื้อรวม <?= $items ? $items->num_rows : 0 ?> รายการ<br>
                ราคา <?= number_format((float)($complaint['total_amount'] ?? 0), 2) ?> บ.<br>
                ค่าจัดส่ง -<br>
                ส่วนลด -<br>
                <hr>
                รวมทั้งหมด <?= number_format((float)($complaint['total_amount'] ?? 0), 2) ?> บ.
            </div>
        </div>

        <div class="side-card">
            <div class="side-head">ผู้จัดส่ง</div>
            <div class="side-body">
                ID ไรเดอร์ -
            </div>
        </div>
    </div>

</div>

</body>
</html>