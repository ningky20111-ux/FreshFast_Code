
<?php
session_start();

$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Shop Register | FreshFast</title>

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

    overflow-x:hidden;
    position:relative;
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

.register-wrapper{
    width:100%;

    display:flex;
    justify-content:center;
    align-items:center;

    position:relative;
    z-index:5;
}

.register-card{
    width:100%;
    max-width:500px;

    background:rgba(255,255,255,.75);

    backdrop-filter:blur(14px);

    border-radius:34px;

    padding:42px;

    box-shadow:
    0 20px 50px rgba(0,0,0,.08);
}

/* BRAND */

.brand{
    display:flex;
    flex-direction:column;

    align-items:center;
    justify-content:center;

    gap:14px;

    margin-bottom:28px;

    text-align:center;
}

.brand-logo img{
    width:130px;
    object-fit:contain;
    display:block;
}

.brand-sub{
    color:#666;
    margin-top:4px;
    font-size:14px;
    text-align:center;
}

/* TEXT */

.welcome{
    font-size:32px;
    font-weight:800;

    text-align:center;

    margin-bottom:10px;
}

.desc{
    color:#666;
    line-height:1.7;

    text-align:center;

    margin-bottom:30px;
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

/* CHECKBOX */

.checkbox{
    margin:22px 0;

    display:flex;
    align-items:flex-start;

    gap:12px;

    font-size:14px;
    line-height:1.6;

    color:#444;
}

.checkbox input{
    margin-top:2px;

    width:18px;
    height:18px;
}

.checkbox a{
    color:#16a34a;
    font-weight:700;
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

.register-btn{
    width:100%;
    height:58px;

    border:none;
    border-radius:20px;

    background:#ffd400;

    font-size:18px;
    font-weight:800;

    cursor:pointer;

    transition:.2s;

    box-shadow:
    0 12px 24px rgba(255,212,0,.35);
}

.register-btn:hover{
    transform:translateY(-2px) scale(1.01);
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

/* MODAL */

.modal{
    display:none;

    position:fixed;
    inset:0;

    background:rgba(0,0,0,.4);

    z-index:9999;

    justify-content:center;
    align-items:center;

    padding:20px;
}

.modal-box{
    width:100%;
    max-width:850px;

    max-height:90vh;
    overflow:auto;

    background:white;

    border-radius:28px;

    padding:34px;

    position:relative;
}

.close-btn{
    position:absolute;

    top:18px;
    right:18px;

    width:42px;
    height:42px;

    border:none;
    border-radius:50%;

    background:#f1f1f1;

    font-size:26px;
    cursor:pointer;
}

.terms-text{
    color:#444;
    line-height:1.8;
}


.form-group select{
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

    appearance:none;

    cursor:pointer;
}
.form-group select{
    appearance:auto;
}
.form-group select:focus{
    transform:translateY(-1px);

    box-shadow:
    0 0 0 3px rgba(22,163,74,.15),
    0 8px 20px rgba(0,0,0,.08);
}

/* MOBILE */

@media(max-width:768px){

    .register-card{
        max-width:390px;

        padding:32px 24px;

        border-radius:28px;
    }

    .brand-logo img{
        width:115px;
    }

    .welcome{
        font-size:26px;
    }

    .desc{
        font-size:14px;
    }

    .form-group input{
        height:52px;
        font-size:15px;
    }

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

<div class="register-wrapper">

    <div class="register-card">

        <div class="brand">

            <div class="brand-logo">
                <img src="assets/images/logo_ok.png" alt="FreshFast">
            </div>

            <div class="brand-sub">
                ระบบสมัครร้านค้า FreshFast
            </div>

        </div>

        <div class="welcome">
            สมัครร้านค้า
        </div>

        <div class="desc">
            เริ่มต้นขายสินค้า จัดการร้านค้า
            และเข้าถึงลูกค้าได้ง่ายขึ้น
        </div>

        <?php if($error != ''): ?>
            <div class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="do_shop_register.php" method="POST">

            <div class="form-group">
                <label>ชื่อร้านค้า</label>

                <input
                    name="shop_name"
                    required
                    placeholder="กรอกชื่อร้านค้า"
                >
            </div>
            <div class="form-group">
                <label>ประเภทร้านค้า</label>

                <select
                    name="shop_type"
                    required
                    class="shop-select"
                >
                    <option value="">
                        -- เลือกประเภทร้านค้า --
                    </option>

                    <option value="Meat">เนื้อสัตว์</option>
                    <option value="Fruits & Vegetables">ผักผลไม้</option>
                    <option value="Seasoning">เครื่องปรุง</option>
                    <option value="Beverages">เครื่องดื่ม</option>
                    <option value="Frozen">แช่แข็ง</option>
                    <option value="Kitchen Supplies">ของใช้ในครัว</option>
                    <option value="Desserts">ของหวาน</option>
                </select>
            </div>
            <div class="form-group">
                <label>อีเมล</label>

                <input
                    type="email"
                    name="email"
                    required
                    placeholder="กรอกอีเมล"
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

            <label class="checkbox">

                <input
                    type="checkbox"
                    name="accept_terms"
                    value="1"
                    required
                >

                <span>
                    ฉันยอมรับ
                    <a href="#" onclick="openTerms(event)">
                        ข้อกำหนดการใช้งาน
                    </a>
                    และนโยบายความเป็นส่วนตัว
                </span>

            </label>

            <button type="submit" class="register-btn">
                สมัครร้านค้า
            </button>

        </form>

        <a href="shop_login.php" class="back-link">
            กลับไปหน้าเข้าสู่ระบบ
        </a>

    </div>

</div>

<!-- MODAL -->

<div class="modal" id="termsModal">

    <div class="modal-box">

        <button
            class="close-btn"
            type="button"
            onclick="closeTerms()"
        >
            ×
        </button>

        <h2>ข้อกำหนดและเงื่อนไข</h2>

        <div class="terms-text">

            วางข้อความข้อกำหนดทั้งหมดของเธอไว้ตรงนี้ได้เลย

        </div>

    </div>

</div>

<script>

function openTerms(event){
    event.preventDefault();

    document.getElementById("termsModal").style.display = "flex";
}

function closeTerms(){
    document.getElementById("termsModal").style.display = "none";
}

</script>

</body>
</html>