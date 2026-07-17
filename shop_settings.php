<?php
session_start();
require_once "db.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if(!isset($_SESSION['shop_id'])){
    header("Location: shop_login.php");
    exit;
}

$shopId = $_SESSION['shop_id'];

$stmt = $conn->prepare("
SELECT *
FROM shops
WHERE shop_id = ?
");

$stmt->bind_param("i",$shopId);
$stmt->execute();

$shop = $stmt->get_result()->fetch_assoc();


if($_SERVER['REQUEST_METHOD']=="POST"){

    $status = $_POST['status'];

    $open_time = $_POST['open_time'];

    $close_time = $_POST['close_time'];

    $auto_open = isset($_POST['auto_open']) ? 1 : 0;

    $stmt = $conn->prepare("
    UPDATE shops
    SET
        shop_status=?,
        opening_time=?,
        closing_time=?,
        auto_open_close=?
    WHERE shop_id=?
    ");

    $stmt->bind_param(
        "sssii",
        $status,
        $open_time,
        $close_time,
        $auto_open,
        $shopId
    );

    $stmt->execute();
    header("Location: shop_settings.php?success=1");
    exit;
    
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
<meta charset="UTF-8">
<title>ตั้งค่าร้านค้า</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
     font-family:Arial,sans-serif;
}

body{
    background:#eef7f0;
}

.container{

    max-width:700px;
    margin:50px auto;

    padding:30px;
}

.card{

    background:#fff;

    border-radius:22px;

    padding:35px;

    box-shadow:0 10px 30px rgba(0,0,0,.08);

}

h2{

    text-align:center;

    color:#15803d;

    margin-bottom:8px;

    font-size:34px;

}

.subtitle{

    text-align:center;

    color:#777;

    margin-bottom:35px;

}

.section{

    margin-bottom:28px;

}

.section h3{

    margin-bottom:12px;

    color:#111;

    font-size:20px;

}

.radio-group{

    display:flex;

    gap:25px;

}

.radio-group label{

    display:flex;

    align-items:center;

    gap:8px;

    font-size:18px;

}

.time-row{

    display:grid;

    grid-template-columns:1fr 1fr;

    gap:20px;

}

.time-box label{

    display:block;

    margin-bottom:8px;

    font-weight:600;

}

input[type=time]{

    width:100%;

    padding:14px;

    border:2px solid #ddd;

    border-radius:14px;

    font-size:18px;

}

.auto{

    margin-top:25px;

    display:flex;

    align-items:center;

    gap:10px;

    font-size:18px;

}

button{
    border:none;
    background:#16a34a;
    color:#fff;
    padding:16px;
    border-radius:14px;
    font-size:20px;
    font-weight:700;
    cursor:pointer;
    transition:.25s;
}

.button-group{
    display:flex;
    gap:15px;
    margin-top:35px;
}

.back-btn,
.save-btn{
    flex:1;
}
button:hover{

    background:#15803d;

    transform:translateY(-2px);

}

.success{

    background:#dcfce7;

    color:#166534;

    border-left:6px solid #16a34a;

    padding:15px;

    margin-bottom:25px;

    border-radius:12px;

}
.button-group{
    display:flex;
    gap:15px;
    margin-top:35px;
}

.back-btn{
    flex:1;

    text-align:center;
    text-decoration:none;

    background:#fff;
    color:#16a34a;

    border:2px solid #16a34a;

    padding:16px;

    border-radius:14px;

    font-size:18px;
    font-weight:700;

    transition:.25s;
}

.back-btn:hover{
    background:#16a34a;
    color:#fff;
}

.save-btn{
    flex:2;

    border:none;

    background:#16a34a;
    color:#fff;

    padding:16px;

    border-radius:14px;

    font-size:18px;
    font-weight:700;

    cursor:pointer;

    transition:.25s;
}

.save-btn:hover{
    background:#15803d;
}
@media(max-width:700px){

.time-row{

grid-template-columns:1fr;

}

.container{

padding:15px;

}

.card{

padding:20px;

}

}
/* ================= MOBILE RESPONSIVE ================= */

@media(max-width:700px){

    body{
        padding:0;
    }

    .container{
        width:100%;
        margin:0;
        padding:20px 14px;
    }


    .card{
        padding:22px 18px;
        border-radius:22px;
    }


    h2{
        font-size:26px;
        margin-bottom:6px;
    }


    .subtitle{
        font-size:14px;
        margin-bottom:25px;
    }


    .section{
        margin-bottom:22px;
    }


    .section h3{
        font-size:17px;
        margin-bottom:12px;
    }


    /* สถานะร้าน */

    .radio-group{

        flex-direction:column;

        gap:14px;

    }


    .radio-group label{

        font-size:16px;

        background:#f7f7f7;

        padding:14px;

        border-radius:14px;

    }


    input[type="radio"]{

        width:18px;
        height:18px;

    }



    /* เวลาเปิดปิด */

    .time-row{

        grid-template-columns:1fr;

        gap:16px;

    }


    .time-box label{

        font-size:15px;

    }


    input[type=time]{

        padding:13px;

        font-size:16px;

        border-radius:14px;

    }



    /* checkbox */

    .auto{

        font-size:16px;

        margin-top:20px;

    }


    input[type="checkbox"]{

        width:18px;

        height:18px;

    }



    /* ปุ่ม */

    .button-group{

        flex-direction:column-reverse;

        gap:12px;

        margin-top:28px;

    }


    .save-btn,
    .back-btn{

        width:100%;

        flex:none;

        padding:14px;

        font-size:16px;

        border-radius:14px;

    }

}
</style>


</head>
<body>
    <div class="container">

<div class="card">

<h2>ตั้งค่าร้านค้า</h2>

<p class="subtitle">
กำหนดเวลาเปิด-ปิดร้านของคุณ
</p>

<?php if(isset($_GET['success'])): ?>

<div class="success">
บันทึกข้อมูลเรียบร้อยแล้ว
</div>

<?php endif; ?>

<form method="POST">

<div class="section">

<h3>สถานะร้าน</h3>

<div class="radio-group">

<label>

<input
type="radio"
name="status"
value="open"
<?= $shop['shop_status']=="open"?"checked":"" ?>
>

เปิดร้าน

</label>

<label>

<input
type="radio"
name="status"
value="closed"
<?= $shop['shop_status']=="closed"?"checked":"" ?>
>

ปิดร้าน

</label>

</div>

</div>

<div class="time-row">

<div class="time-box">

<label>เวลาเปิดร้าน</label>

<input
type="time"
name="open_time"
value="<?= $shop['opening_time'] ?>"
>

</div>

<div class="time-box">

<label>เวลาปิดร้าน</label>

<input
type="time"
name="close_time"
value="<?= $shop['closing_time'] ?>"
>

</div>

</div>

<label class="auto">

<input
type="checkbox"
name="auto_open"
<?= $shop['auto_open_close']?'checked':'' ?>
>

เปิด-ปิดร้านอัตโนมัติ

</label>

<div class="button-group">

    <a href="shop_home.php" class="back-btn">
        กลับหน้าหลัก
    </a>

    <button type="submit" class="save-btn">
        บันทึกการตั้งค่า
    </button>

</div>

</form>

</div>

</div>
</body>
