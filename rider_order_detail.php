<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$order_id = (int)($_GET['order_id'] ?? 0);
if ($order_id <= 0) {
    die("ไม่พบหมายเลขคำสั่งซื้อ");
}

/*
|--------------------------------------------------------------------------
| ดึงข้อมูลออเดอร์จริง
|--------------------------------------------------------------------------
| users ไม่มี phone ใน schema ล่าสุด
| จึงใช้เฉพาะ name จาก users และข้อมูลอื่นจาก orders
|--------------------------------------------------------------------------
*/
$sqlOrder = "
    SELECT 
        o.order_id,
        o.user_id,
        o.rider_id,
        o.status,
        o.delivery_address,
        o.delivery_lat,
        o.delivery_lng,
        o.created_at,
        u.name AS customer_name,
        r.full_name AS rider_name,
        r.phone AS rider_phone,
        r.vehicle_type,
        r.license_plate
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    LEFT JOIN riders r ON o.rider_id = r.rider_id
    WHERE o.order_id = ?
    LIMIT 1
";

$stmtOrder = $conn->prepare($sqlOrder);
if (!$stmtOrder) {
    die("เตรียมคำสั่ง SQL ไม่สำเร็จ: " . $conn->error);
}
$stmtOrder->bind_param("i", $order_id);
$stmtOrder->execute();
$orderResult = $stmtOrder->get_result();
$order = $orderResult->fetch_assoc();
$stmtOrder->close();

if (!$order) {
    die("ไม่พบข้อมูลออเดอร์");
}

/*
|--------------------------------------------------------------------------
| ดึงรายการสินค้า
|--------------------------------------------------------------------------
*/
$sqlItems = "
    SELECT 
        oi.order_item_id,
        oi.product_id,
        oi.quantity,
        oi.price,
        p.product_name
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
    ORDER BY oi.order_item_id ASC
";

$stmtItems = $conn->prepare($sqlItems);
if (!$stmtItems) {
    die("เตรียมคำสั่งรายการสินค้าไม่สำเร็จ: " . $conn->error);
}
$stmtItems->bind_param("i", $order_id);
$stmtItems->execute();
$itemsResult = $stmtItems->get_result();

$items = [];
$subtotal = 0;

while ($row = $itemsResult->fetch_assoc()) {
    $lineTotal = ((float)$row['price']) * ((int)$row['quantity']);
    $row['line_total'] = $lineTotal;
    $subtotal += $lineTotal;
    $items[] = $row;
}
$stmtItems->close();

$delivery_fee = 60;
$discount = 0;
$grand_total = $subtotal + $delivery_fee - $discount;

/*
|--------------------------------------------------------------------------
| map fallback
|--------------------------------------------------------------------------
*/
$deliveryLat = $order['delivery_lat'] !== null ? (float)$order['delivery_lat'] : 16.7167;
$deliveryLng = $order['delivery_lng'] !== null ? (float)$order['delivery_lng'] : 98.5747;

$statusLabel = match ($order['status']) {
    'waiting_rider' => 'งานใหม่',
    'accepted'       => 'กำลังเตรียม',
    'delivering'     => 'กำลังจัดส่ง',
    'completed'      => 'จัดส่งสำเร็จ',
    default          => 'ไม่ทราบสถานะ'
};
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>ข้อมูลคำสั่งซื้อ | FreshFast</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>

    <style>
        :root{
            --bg:#efefef;
            --green:#0a9838;
            --green-soft:#97e8af;
            --yellow:#f5c400;
            --text:#111111;
            --muted:#666666;
            --white:#ffffff;
            --line:#d7d7d7;
            --danger:#ff2b2b;
            --shadow:0 4px 16px rgba(0,0,0,.10);
        }
        *{ box-sizing:border-box; }
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
            padding:20px 10px 40px;
        }
        .brand{
            display:flex;
            justify-content:center;
            align-items:center;
            margin:30px 0 18px;
        }
        .brand img{
            width:200px;
            max-width:100%;
            height:auto;
            display:block;
        }
        .page-title{
            text-align:center;
            font-size:22px;
            font-weight:900;
            margin:0 0 20px;
        }
        .status-wrap{
            display:flex;
            justify-content:center;
            margin-bottom:16px;
        }
        .status-badge{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:150px;
            padding:12px 22px;
            border-radius:999px;
            background:var(--green);
            color:#fff;
            font-weight:900;
            font-size:18px;
        }
        .card{
            background:#fff;
            border-radius:22px;
            box-shadow:var(--shadow);
            overflow:hidden;
            margin-bottom:18px;
            border:1px solid rgba(0,0,0,.04);
        }
        .card-head{
            background:var(--green-soft);
            padding:14px 16px;
        }
        .card-head-title{
            font-size:14px;
            color:#4d6a57;
            margin-bottom:4px;
        }
        .card-head-code{
            font-size:28px;
            font-weight:900;
            line-height:1.1;
            word-break:break-word;
        }
        .card-body{
            padding:14px 16px 18px;
        }
        .info-block{
            margin-bottom:16px;
        }
        .info-label{
            font-size:13px;
            font-weight:800;
            margin-bottom:4px;
        }
        .info-value{
            font-size:14px;
            color:#222;
            line-height:1.5;
        }
        .items-head,
        .item-row{
            display:grid;
            grid-template-columns: 1.5fr .6fr .8fr;
            gap:10px;
            align-items:center;
        }
        .items-head{
            font-size:13px;
            font-weight:800;
            margin-top:12px;
            margin-bottom:10px;
        }
        .items-divider{
            border:none;
            border-top:1px solid #111;
            margin:0 0 4px;
        }
        .item-row{
            padding:14px 0;
            border-bottom:1px solid var(--line);
            font-size:14px;
        }
        .item-row:last-child{
            border-bottom:none;
        }
        .item-name{
            font-weight:800;
        }
        .item-qty{
            text-align:center;
            color:var(--green);
            font-weight:800;
        }
        .item-price{
            text-align:right;
            font-weight:800;
        }
        .summary-row{
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:12px 0;
            font-size:15px;
        }
        .summary-divider{
            border:none;
            border-top:1px solid var(--line);
            margin:8px 0;
        }
        .summary-total{
            color:var(--danger);
            font-weight:900;
            font-size:24px;
        }
        .map-title{
            text-align:center;
            font-size:16px;
            font-weight:900;
            margin:24px 0 12px;
        }
        .map-box{
            width:100%;
            height:260px;
            border-radius:18px;
            overflow:hidden;
            background:#ddd;
            box-shadow:var(--shadow);
            margin-bottom:12px;
            border:1px solid rgba(0,0,0,.08);
        }
        #map{
            width:100%;
            height:100%;
        }
        .gps-status{
            background:#fff;
            border:1px solid rgba(0,0,0,.08);
            border-radius:14px;
            padding:12px 14px;
            margin-bottom:18px;
            box-shadow:var(--shadow);
            font-size:14px;
            line-height:1.5;
        }
        .gps-status strong{
            display:block;
            margin-bottom:4px;
        }
        .gps-meta{
            color:var(--muted);
            font-size:13px;
            margin-top:4px;
            word-break:break-word;
        }
        .action-group{
            display:flex;
            flex-direction:column;
            gap:12px;
            margin:0 0 20px;
        }
        .btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:100%;
            border:none;
            border-radius:999px;
            padding:14px 20px;
            font-size:17px;
            font-weight:900;
            cursor:pointer;
            text-decoration:none;
        }
        .btn-yellow{
            background:var(--yellow);
            color:#111;
        }
        .btn-green{
            background:var(--green);
            color:#fff;
        }
        .btn-outline{
            background:#fff;
            color:var(--green);
            border:2px solid var(--green);
        }
        .top-actions{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:14px;
            gap:10px;
        }
        .back-link,
        .mini-link{
            text-decoration:none;
            font-size:13px;
            font-weight:800;
        }
        .back-btn{
            display:inline-flex;
            align-items:center;
            gap:8px;
            background:#ffffff;
            color:#111;
            text-decoration:none;
            font-size:14px;
            font-weight:900;
            padding:10px 18px;
            border-radius:999px;
            box-shadow:0 3px 10px rgba(0,0,0,.12);
            border:1px solid #e5e5e5;
            transition:.2s;
        }

        .back-btn span{
            font-size:20px;
            line-height:1;
        }

        .back-btn:hover{
            transform:translateX(-3px);
        }
        .mini-link{ color:#b00020; }
    </style>
</head>
<body>
    <div class="app">

        <div class="top-actions">

            <a href="rider_home.php?tab=new" class="back-btn">
                <span></span> กลับ
            </a>

        <a href="logout.php" class="mini-link">ออกจากระบบ</a>
        </div>

        <div class="brand">
            <img src="assets/images/logo.png" alt="FreshFast">
        </div>

        <h1 class="page-title">ข้อมูลคำสั่งซื้อ</h1>

        <div class="status-wrap">
            <div class="status-badge"><?= e($statusLabel) ?></div>
        </div>

        <section class="card">
            <div class="card-head">
                <div class="card-head-title">หมายเลขคำสั่งซื้อ</div>
                <div class="card-head-code">#FF-2026-<?= str_pad((string)$order['order_id'], 4, '0', STR_PAD_LEFT) ?></div>
            </div>

            <div class="card-body">
                <div class="info-block">
                    <div class="info-label">ชื่อผู้รับ</div>
                    <div class="info-value"><?= e($order['customer_name'] ?: '-') ?></div>
                </div>

                <div class="info-block">
                    <div class="info-label">เบอร์ไรเดอร์</div>
                    <div class="info-value"><?= e($order['rider_phone'] ?: '-') ?></div>
                </div>

                <div class="info-block">
                    <div class="info-label">📍 ที่อยู่จัดส่ง</div>
                    <div class="info-value"><?= e($order['delivery_address'] ?: '-') ?></div>
                </div>

                <div class="items-head">
                    <div>รายการสั่งซื้อ</div>
                    <div style="text-align:center;">จำนวน</div>
                    <div style="text-align:right;">ราคา</div>
                </div>

                <hr class="items-divider">

                <?php foreach ($items as $item): ?>
                    <div class="item-row">
                        <div class="item-name"><?= e($item['product_name'] ?: 'สินค้า') ?></div>
                        <div class="item-qty"><?= (int)$item['quantity'] ?></div>
                        <div class="item-price"><?= number_format((float)$item['price']) ?> บ.</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card">
            <div class="card-head">
                <div class="card-head-code" style="font-size:18px;">ข้อมูลการชำระเงิน</div>
            </div>

            <div class="card-body">
                <div class="summary-row">
                    <span>รายการสั่งซื้อรวม</span>
                    <strong><?= count($items) ?> รายการ</strong>
                </div>

                <div class="summary-row">
                    <span>ราคา</span>
                    <strong><?= number_format($subtotal) ?> บ.</strong>
                </div>

                <div class="summary-row">
                    <span>ค่าส่ง</span>
                    <strong><?= number_format($delivery_fee) ?> บ.</strong>
                </div>

                <div class="summary-row">
                    <span>ส่วนลด</span>
                    <strong>-<?= number_format($discount) ?> บ.</strong>
                </div>

                <hr class="summary-divider">

                <div class="summary-row">
                    <strong>รวมทั้งหมด</strong>
                    <div class="summary-total"><?= number_format($grand_total) ?> บ.</div>
                </div>
            </div>
        </section>

        <div class="map-title">ที่อยู่ในการจัดส่ง</div>

        <div class="map-box">
            <div id="map"></div>
        </div>

        <div class="gps-status" id="gpsStatusBox">
            <strong id="gpsStatusText">สถานะ GPS: ยังไม่ได้เริ่มติดตาม</strong>
            <div class="gps-meta" id="gpsCoords">ตำแหน่งไรเดอร์: -</div>
            <div class="gps-meta" id="gpsAccuracy">ความแม่นยำ: -</div>
        </div>

        <div class="action-group">

        <?php if ($order['status'] == 'waiting_rider'): ?>

        <button 
            class="btn btn-yellow" 
            type="button" 
            id="orderActionBtn"
            data-action="accept">
            รับงาน
        </button>


        <?php elseif ($order['status'] == 'accepted'): ?>

        <button 
            class="btn btn-green" 
            type="button" 
            id="orderActionBtn"
            data-action="start">
            เริ่มจัดส่ง
        </button>


        <?php elseif ($order['status'] == 'delivering'): ?>

        <button 
            class="btn btn-outline" 
            type="button" 
            id="orderActionBtn"
            data-action="finish">
            จัดส่งสำเร็จ
        </button>


        <?php endif; ?>

        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        const orderId = <?= (int)$order['order_id'] ?>;
        const riderId = <?= (int)($order['rider_id'] ?? 1) ?>;
        const deliveryLat = <?= json_encode($deliveryLat) ?>;
        const deliveryLng = <?= json_encode($deliveryLng) ?>;
        const deliveryAddress = <?= json_encode($order['delivery_address'] ?: '-') ?>;

        const map = L.map('map').setView([deliveryLat, deliveryLng], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const deliveryMarker = L.marker([deliveryLat, deliveryLng]).addTo(map);
        deliveryMarker.bindPopup('จุดจัดส่ง: ' + deliveryAddress);

        let riderMarker = null;
        let riderWatchId = null;

        const startBtn = document.getElementById('startTrackingBtn');
        const finishBtn = document.getElementById('finishDeliveryBtn');
        const gpsStatusText = document.getElementById('gpsStatusText');
        const gpsCoords = document.getElementById('gpsCoords');
        const gpsAccuracy = document.getElementById('gpsAccuracy');

        function setStatus(text) {
            gpsStatusText.textContent = text;
        }

        function setCoords(lat, lng) {
            gpsCoords.textContent = `ตำแหน่งไรเดอร์: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        }

        function setAccuracy(acc) {
            gpsAccuracy.textContent = `ความแม่นยำ: ± ${Math.round(acc)} เมตร`;
        }

        function updateRiderMarker(lat, lng) {
            if (!riderMarker) {
                riderMarker = L.marker([lat, lng]).addTo(map);
                riderMarker.bindPopup('ตำแหน่งไรเดอร์');
            } else {
                riderMarker.setLatLng([lat, lng]);
            }
        }

        function saveLocation(lat, lng) {
            fetch("update_location.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `rider_id=${riderId}&order_id=${orderId}&lat=${lat}&lng=${lng}`
            });
        }

        function updateOrderStatus(status) {
            return fetch("update_order_status.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `order_id=${orderId}&status=${status}`
            });
        }
const actionBtn = document.getElementById("orderActionBtn");


if(actionBtn){

    actionBtn.addEventListener("click", async ()=>{

        const action = actionBtn.dataset.action;


        // รับงาน
        if(action === "accept"){

            await updateOrderStatus("accepted");

            alert("รับงานเรียบร้อย");

            location.reload();

        }


        // เริ่มจัดส่ง
        if(action === "start"){

            if (!navigator.geolocation) {
                alert("อุปกรณ์ไม่รองรับ GPS");
                return;
            }


            if(riderWatchId !== null){
                alert("กำลังติดตามตำแหน่งอยู่");
                return;
            }


            setStatus('สถานะ GPS: กำลังติดตามตำแหน่ง');


            riderWatchId = navigator.geolocation.watchPosition(

                (position)=>{

                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const acc = position.coords.accuracy;


                    updateRiderMarker(lat,lng);

                    saveLocation(lat,lng);

                    setCoords(lat,lng);

                    setAccuracy(acc);

                    setStatus(
                    "สถานะ GPS: กำลังส่งตำแหน่ง"
                    );


                },

                ()=>{

                    alert("ไม่สามารถรับตำแหน่ง GPS ได้");

                },

                {
                    enableHighAccuracy:true,
                    timeout:15000,
                    maximumAge:0
                }

            );


            await updateOrderStatus("delivering");


            alert("เริ่มจัดส่งแล้ว");

            location.reload();

        }



        // จัดส่งสำเร็จ
        if(action === "finish"){


            if(riderWatchId !== null){

                navigator.geolocation.clearWatch(riderWatchId);

                riderWatchId=null;

            }


            await updateOrderStatus("completed");


            alert("จัดส่งสำเร็จ");


            window.location.href =
            "rider_home.php?tab=history";

        }


    });

}

        // startBtn.addEventListener('click', async () => {


        //     if (!navigator.geolocation) {
        //         alert('อุปกรณ์นี้ไม่รองรับ GPS');
        //         return;
        //     }

        //     if (riderWatchId !== null) {
        //         alert('กำลังติดตามอยู่แล้ว');
        //         return;
        //     }

        //     setStatus('สถานะ GPS: กำลังเริ่มติดตาม...');

        //     riderWatchId = navigator.geolocation.watchPosition(
        //         (position) => {
        //             const lat = position.coords.latitude;
        //             const lng = position.coords.longitude;
        //             const acc = position.coords.accuracy;

        //             updateRiderMarker(lat, lng);
        //             saveLocation(lat, lng);
        //             setCoords(lat, lng);
        //             setAccuracy(acc);
        //             setStatus('สถานะ GPS: กำลังติดตามตำแหน่งแบบเรียลไทม์');

        //             const bounds = L.latLngBounds([
        //                 [deliveryLat, deliveryLng],
        //                 [lat, lng]
        //             ]);
        //             map.fitBounds(bounds, { padding: [40, 40] });
        //         },
        //         (error) => {
        //             let msg = 'ไม่สามารถดึงตำแหน่งได้';
        //             if (error.code === 1) msg = 'ผู้ใช้ปฏิเสธการเข้าถึงตำแหน่ง';
        //             if (error.code === 2) msg = 'ไม่สามารถระบุตำแหน่งได้';
        //             if (error.code === 3) msg = 'การดึงตำแหน่งใช้เวลานานเกินไป';
        //             setStatus('สถานะ GPS: ' + msg);
        //             alert(msg);
        //         },
        //         {
        //             enableHighAccuracy: true,
        //             timeout: 15000,
        //             maximumAge: 0
        //         }
        //     );
        // });

        // finishBtn.addEventListener('click', async () => {
        //     if (riderWatchId !== null) {
        //         navigator.geolocation.clearWatch(riderWatchId);
        //         riderWatchId = null;
        //     }

        //     try {
        //         await updateOrderStatus('completed');
        //         alert('จัดส่งสำเร็จ');
        //         window.location.href = 'rider_home.php?tab=history';
        //     } catch (e) {
        //         alert('อัปเดตสถานะไม่สำเร็จ');
        //     }
        // });

        setTimeout(() => {
            map.invalidateSize();
        }, 300);
    </script>
</body>
</html>