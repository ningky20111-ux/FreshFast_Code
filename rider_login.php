<?php
session_start();
require_once "db.php";

if (isset($_SESSION['rider_id'])) {
    header("Location: rider_home.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if ($email == "" || $password == "") {
        $error = "กรุณากรอกอีเมลและรหัสผ่าน";
    } else {

        $stmt = $conn->prepare("SELECT * FROM riders WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $rider = $result->fetch_assoc();

            // ตรวจสอบสถานะไรเดอร์
            if ($rider['status'] != "active") {
                $error = "บัญชีไรเดอร์ยังไม่ได้รับการอนุมัติ";
            }
            // ตรวจสอบรหัสผ่าน
            elseif ($password == $rider['password']) {

                $_SESSION['rider_id'] = $rider['rider_id'];
                $_SESSION['rider_email'] = $rider['email'];
                $_SESSION['rider_name'] = $rider['full_name'];

                header("Location: rider_home.php");
                exit;

            } else {
                $error = "รหัสผ่านไม่ถูกต้อง";
            }

        } else {
            $error = "ไม่พบบัญชีไรเดอร์";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Rider Login | FreshFast</title>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>

body{
    margin:0;
    min-height:100vh;

    background:
    linear-gradient(
        135deg,
        #dfffe9 0%,
        #f7fff1 50%,
        #fffbe2 100%
    );

    display:flex;
    justify-content:center;
    align-items:center;

    overflow:hidden;
    padding:20px;
}

.box{

    width:100%;
    max-width:460px;

    background:rgba(255,255,255,.75);

    backdrop-filter:blur(15px);

    padding:42px;

    border-radius:34px;

    box-shadow:
    0 20px 50px rgba(0,0,0,.08);

    position:relative;
    z-index:5;

}

.logo{
    width:160px;
    display:block;
    margin:auto;
    margin-bottom:10px;
}

input{
    width:100%;
    padding:14px;
    margin-bottom:15px;
    border:1px solid #ccc;
    border-radius:10px;
    box-sizing:border-box;
    font-size:15px;
}

button{
    width:100%;
    padding:14px;
    background:#0C9638;
    color:#fff;
    border:none;
    border-radius:10px;
    cursor:pointer;
    font-size:16px;
    font-weight:bold;
}

button:hover{
    background:#08742b;
}

.error{
    color:red;
    text-align:center;
    margin-bottom:15px;
}
.bg-circle{
    position:absolute;
    border-radius:50%;
    filter:blur(40px);
    opacity:.4;
}

.one{
    width:420px;
    height:420px;
    background:#7cffb2;

    top:-120px;
    left:-120px;
}

.two{
    width:320px;
    height:320px;
    background:#ffe45b;

    bottom:-100px;
    right:-100px;
}
.welcome{

    text-align:center;

    font-size:32px;

    margin:15px 0 8px;

    font-weight:800;

}

.desc{

    text-align:center;

    color:#666;

    line-height:1.7;

    margin-bottom:28px;

}
</style>

</head>

<body>
<div class="bg-circle one"></div>
<div class="bg-circle two"></div>
<div class="box">

<img src="assets/images/logo_ok.png" class="logo">
<h1 class="welcome">
เข้าสู่ระบบไรเดอร์
</h1>

<p class="desc">
รับงานจัดส่ง ตรวจสอบรายการ และติดตามสถานะการจัดส่งได้ในที่เดียว
</p>
<?php if($error!=""){ ?>
<div class="error"><?= $error ?></div>
<?php } ?>

<form method="POST">

<input
type="email"
name="email"
placeholder="Email"
required>

<input
type="password"
name="password"
placeholder="Password"
required>

<button type="submit">
เข้าสู่ระบบไรเดอร์
</button>

</form>

</div>

</body>
</html>