<?php
session_start();

if (isset($_SESSION['shop_id']) && ($_SESSION['shop_role'] ?? '') === 'shop') {
    header("Location: shop_home.php");
    exit;
}

$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shop Login | FreshFast</title>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{
    box-sizing:border-box;
    font-family:sans-serif;
}

body{
    margin:0;
    min-height:100dvh;

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

    padding:20px;

    overflow:hidden;
}

/* BG */

.bg-circle{
    position:absolute;
    border-radius:50%;
    filter:blur(40px);
    opacity:.4;
}

.bg-circle.one{
    width:420px;
    height:420px;
    background:#7cffb2;

    top:-120px;
    left:-100px;
}

.bg-circle.two{
    width:360px;
    height:360px;
    background:#ffe45b;

    bottom:-100px;
    right:-80px;
}

/* CARD */
.login-wrapper{
    width:100%;

    display:flex;
    justify-content:center;
    align-items:center;
}

.login-card{
    width:100%;
    max-width:470px;

    background:rgba(255,255,255,.75);

    backdrop-filter:blur(14px);

    border-radius:34px;

    padding:42px;

    box-shadow:
    0 20px 50px rgba(0,0,0,.08);

    position:relative;
    z-index:5;
}

/* BRAND */

.brand{
    display:flex;
    align-items:center;
    gap:16px;

    margin-bottom:28px;
}

.brand{
    display:flex;
    flex-direction:column;

    align-items:center;
    justify-content:center;

    gap:14px;

    margin-bottom:28px;

    text-align:center;
}

.brand-title,
.brand-sub{
    text-align:center;
}

.brand-logo img{
    width:90%;
    object-fit:contain;
    display:block;
}

.brand-sub{
    color:#666;
    margin-top:4px;
    font-size:14px;
}

/* TEXT */

.welcome{
    font-size:32px;
    font-weight:800;
    margin-bottom:8px;
}

.desc{
    color:#666;
    line-height:1.7;
    margin-bottom:28px;
}

/* FORM */

.form-group{
    margin-bottom:18px;
}

.form-group label{
    display:block;
    margin-bottom:8px;

    font-weight:700;
    font-size:15px;
}

.form-group input{
    width:100%;
    height:56px;

    border:none;
    outline:none;

    border-radius:18px;

    padding:0 18px;

    font-size:16px;

    background:#fff;

    box-shadow:
    inset 0 0 0 1px rgba(0,0,0,.05),
    0 5px 14px rgba(0,0,0,.05);

    transition:.2s;
}

.form-group input:focus{
    transform:translateY(-1px);

    box-shadow:
    0 0 0 3px rgba(22,163,74,.15),
    0 8px 20px rgba(0,0,0,.08);
}

/* ERROR */

.error{
    background:#ffe2e2;
    color:#b00000;

    padding:14px;
    border-radius:16px;

    margin-bottom:18px;

    font-weight:700;
}

/* BUTTON */

.login-btn{
    width:100%;
    height:58px;

    border:none;
    border-radius:20px;

    background:#ffd400;

    font-size:18px;
    font-weight:800;

    cursor:pointer;

    margin-top:8px;

    transition:.2s;

    box-shadow:
    0 12px 24px rgba(255,212,0,.35);
}

.login-btn:hover{
    transform:translateY(-2px) scale(1.01);
}

.register-btn{
    margin-top:18px;

    width:100%;
    height:54px;

    border-radius:18px;

    background:#16a34a;
    color:#fff;

    text-decoration:none;

    display:flex;
    align-items:center;
    justify-content:center;

    font-weight:800;

    transition:.2s;
}

.register-btn:hover{
    transform:translateY(-2px);
}

.back-link{
    display:block;

    margin-top:22px;

    text-align:center;

    color:#555;

    text-decoration:none;

    font-weight:600;
}

.back-link:hover{
    color:#111;
}

.welcome,
.desc{
    text-align:center;
}
/* MOBILE */
@media(max-width:768px){

    .login-card{
        width:100%;
        max-width:380px;


        border-radius:28px;

    }
    .login-wrapper{
        width:100%;
        min-height:100dvh;

        display:flex;
        justify-content:center;
        align-items:center;

        padding:20px;
    }
    .brand-logo img{
        width:120px;
    }

    .welcome{
        font-size:26px;
    }

    .desc{
        font-size:14px;
        line-height:1.6;
    }

    .form-group input{
        height:52px;
        font-size:15px;
    }

    .login-btn,
    .register-btn{
        height:52px;
        font-size:16px;
    }

    .bg-circle.one{
        width:260px;
        height:260px;
    }

    .bg-circle.two{
        width:220px;
        height:220px;
    }
}
</style>
</head>

<body>

<div class="bg-circle one"></div>
<div class="bg-circle two"></div>

<div class="login-wrapper">

    <div class="login-card">

        <div class="brand">

            <div class="brand-logo">
                <img src="assets/images/logo_ok.png" alt="FreshFast">
            </div>

            <div>
                <div class="brand-sub">
                    ระบบสำหรับร้านค้า
                </div>
            </div>

        </div>

        <div class="welcome">
            เข้าสู่ระบบร้านค้า
        </div>

        <div class="desc">
            จัดการร้านค้า สินค้า และคำสั่งซื้อของคุณ
            ได้ในที่เดียว
        </div>

        <?php if ($error !== ''): ?>
            <div class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="do_shop_login.php" method="POST">

            <div class="form-group">
                <label>อีเมล</label>

                <input
                    type="email"
                    name="email"
                    required
                    placeholder="กรอกอีเมลร้านค้า"
                >
            </div>

            <div class="form-group">
                <label>รหัสผ่าน</label>

                <input
                    type="password"
                    name="password"
                    required
                    placeholder="กรอกรหัสผ่าน"
                >
            </div>

            <button type="submit" class="login-btn">
                เข้าสู่ระบบ
            </button>

        </form>

        <a href="shop_register.php" class="register-btn">
            สมัครร้านค้า
        </a>

        <a href="login.php" class="back-link">
            ไปหน้าล็อกอินสำหรับผู้ซื้อ
        </a>

    </div>

</div>

</body>
</body>
</html>