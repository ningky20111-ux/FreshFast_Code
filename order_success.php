<?php
session_start();
require_once 'db.php';

function e($str)
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

$order_id = $_GET['order_id'] ?? $_SESSION['last_order_id'] ?? null;

if (!$order_id) {
    header("Location: home.php");
    exit;
}
// ถ้าออเดอร์ยังไม่มี rider_id ให้กำหนดเป็นไรเดอร์คนแรก
$stmt = $conn->prepare("SELECT rider_id FROM orders WHERE order_id=?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$temp = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (empty($temp['rider_id'])) {

    $rider = $conn->query("
        SELECT rider_id
        FROM riders
        WHERE status='active'
        LIMIT 1
    ")->fetch_assoc();

    if ($rider) {
        $stmt = $conn->prepare("
            UPDATE orders
            SET rider_id=?
            WHERE order_id=?
        ");
        $stmt->bind_param("ii", $rider['rider_id'], $order_id);
        $stmt->execute();
        $stmt->close();
    }
}

$sql = "
SELECT
    o.order_id,
    o.status,
    o.created_at,
    o.rider_id,
    r.full_name AS rider_name,
    r.phone AS rider_phone,
    r.license_plate
FROM orders o
LEFT JOIN riders r ON o.rider_id = r.rider_id
WHERE o.order_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header("Location: home.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>สั่งซื้อสำเร็จ</title>

<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
*{
    box-sizing:border-box;
}

body{
    margin:0;
    font-family:'textarea', sans-serif;
    min-height:100vh;

    display:flex;
    align-items:center;
    justify-content:center;

    background:linear-gradient(135deg,#dff7e5,#f5f7ff);
    overflow-x:hidden;
}

.card{
    width:420px;
    max-width:92%;
    background:#fff;
    border-radius:26px;
    padding:32px 26px;
    text-align:center;

    box-shadow:0 25px 60px rgba(0,0,0,0.12);
    animation:pop .35s ease;
}

@keyframes pop{
    from{transform:scale(.9);opacity:0}
    to{transform:scale(1);opacity:1}
}

.icon{
    width:88px;
    height:88px;
    margin:0 auto 18px;

    border-radius:50%;
    background:linear-gradient(135deg,#22c55e,#16a34a);

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:40px;
    color:#fff;

    box-shadow:0 12px 30px rgba(22,163,74,0.35);
}

h1{
    margin:8px 0 6px;
    font-size:26px;
}

p{
    margin:0 0 10px;
    color:#666;
    font-size:14px;
}

.order-box{
    margin:18px 0;
    padding:14px;
    border-radius:14px;

    background:#f4f7f5;
    border:1px dashed #cbd5c0;

    font-weight:600;
}

.status{
    display:inline-block;
    padding:6px 12px;
    border-radius:999px;

    background:#fff3cd;
    color:#856404;
    font-size:13px;

    margin-bottom:12px;
}

.rider{
    font-size:14px;
    color:#444;
    margin-bottom:10px;
}

.btn{
    display:block;
    width:100%;
    padding:14px;

    border-radius:999px;
    font-weight:600;

    text-decoration:none;
    margin-top:12px;
    transition:.2s;
}

.btn-track{
    background:#16a34a;
    color:#fff;
}

.btn-track:hover{
    background:#12833c;
}

.btn-home{
    background:#fff;
    border:2px solid #16a34a;
    color:#16a34a;
}

.btn-home:hover{
    background:#16a34a;
    color:#fff;
}

.small{
    margin-top:12px;
    font-size:12px;
    color:#888;
}

@media (max-width:480px){
    .card{
        padding:24px 18px;
        border-radius:20px;
    }

    .icon{
        width:78px;
        height:78px;
        font-size:34px;
    }

    h1{
        font-size:22px;
    }
}
</style>
</head>

<body>

<div class="card">

    <div class="icon">
    <svg viewBox="0 0 24 24" width="44" height="49" fill="none">
    <path d="M20 6L9 17l-5-5"
            stroke="white"
            stroke-width="3.5"
            stroke-linecap="round"
            stroke-linejoin="round"/>
    </svg>
    </div>

    <h1>สั่งซื้อสำเร็จ</h1>
    <p>คำสั่งซื้อของคุณถูกบันทึกเรียบร้อยแล้ว</p>

    <div class="order-box">
        Order ID #<?= e($order_id) ?>
    </div>

    <div class="status">
        <?= e($order['status']) ?>
    </div>
<?php if (!empty($order['rider_name'])): ?>

<div class="rider">
    🚴‍♂️ ไรเดอร์ : <?= e($order['rider_name']) ?><br>
    📞 เบอร์ : <?= e($order['rider_phone']) ?><br>
    🛵 ทะเบียน : <?= e($order['license_plate']) ?>
</div>

<?php else: ?>

<div class="rider">
    🚴‍♂️ กำลังรอไรเดอร์รับออเดอร์
</div>

<?php endif; ?>

    <a class="btn btn-track"
       href="track_order.php?order_id=<?= e($order_id) ?>">
        ติดตามออเดอร์
    </a>

    <a class="btn btn-home" href="home.php">
        กลับหน้าหลัก
    </a>

    <div class="small">
        สั่งซื้อเมื่อ <?= date('d M Y H:i', strtotime($order['created_at'])) ?>
    </div>

</div>

</body>
</html>