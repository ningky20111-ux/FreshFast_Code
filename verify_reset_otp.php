<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(0);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/db.php';

function flash(string $key): ?string
{
    $value = $_SESSION[$key] ?? null;
    unset($_SESSION[$key]);

    return is_string($value) ? $value : null;
}

function setFlash(string $key, string $message): void
{
    $_SESSION[$key] = $message;
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function normalizeEmail(string $email): string
{
    $email = trim($email);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    return strtolower((string)$email);
}

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$message = flash('flash_msg');
$error = flash('flash_err');

$email = '';

if (!empty($_SESSION['reset_email'])) {
    $email = normalizeEmail((string)$_SESSION['reset_email']);
} elseif (!empty($_GET['email'])) {
    $email = normalizeEmail((string)$_GET['email']);
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash(
        'flash_err',
        'ไม่พบอีเมลสำหรับยืนยัน OTP กรุณาขอ OTP ใหม่'
    );

    redirect('forgot_password.php');
}

/* =========================
   POST: VERIFY OTP
========================= */

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $otp = preg_replace(
        '/\D+/',
        '',
        (string)($_POST['otp'] ?? '')
    );

    if (!preg_match('/^\d{6}$/', $otp)) {
        setFlash(
            'flash_err',
            'กรุณากรอก OTP ให้ครบ 6 หลัก'
        );

        redirect('verify_reset_otp.php');
    }

    $stmt = $conn->prepare(
        'SELECT user_id
         FROM users
         WHERE email = ?
         LIMIT 1'
    );

    if (!$stmt) {
        setFlash('flash_err', 'ระบบขัดข้อง กรุณาลองใหม่');
        redirect('forgot_password.php');
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        setFlash(
            'flash_err',
            'OTP ไม่ถูกต้องหรือหมดอายุ กรุณาขอใหม่'
        );

        redirect('forgot_password.php');
    }

    $userId = (int)$user['user_id'];

    $stmt = $conn->prepare(
        'SELECT id, otp_hash, expires_at, attempts
         FROM password_reset_otps
         WHERE user_id = ?
         ORDER BY created_at DESC
         LIMIT 1'
    );

    if (!$stmt) {
        setFlash('flash_err', 'ระบบขัดข้อง กรุณาลองใหม่');
        redirect('forgot_password.php');
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $otpRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$otpRow) {
        setFlash(
            'flash_err',
            'ยังไม่ได้ขอ OTP กรุณาขอใหม่'
        );

        redirect('forgot_password.php');
    }

    if ((int)$otpRow['attempts'] >= 5) {
        setFlash(
            'flash_err',
            'ลองผิดหลายครั้งเกินไป กรุณาส่ง OTP ใหม่'
        );

        redirect('forgot_password.php');
    }

    $stmt = $conn->prepare(
        'SELECT (NOW() <= ?) AS not_expired'
    );

    if (!$stmt) {
        setFlash('flash_err', 'ระบบขัดข้อง กรุณาลองใหม่');
        redirect('forgot_password.php');
    }

    $expiresAt = (string)$otpRow['expires_at'];

    $stmt->bind_param('s', $expiresAt);
    $stmt->execute();
    $expiryCheck = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (
        empty($expiryCheck)
        || (int)$expiryCheck['not_expired'] !== 1
    ) {
        $deleteStmt = $conn->prepare(
            'DELETE FROM password_reset_otps
             WHERE id = ?'
        );

        if ($deleteStmt) {
            $otpId = (int)$otpRow['id'];

            $deleteStmt->bind_param('i', $otpId);
            $deleteStmt->execute();
            $deleteStmt->close();
        }

        setFlash(
            'flash_err',
            'OTP หมดอายุแล้ว กรุณาส่งใหม่'
        );

        redirect('forgot_password.php');
    }

    $isValid = password_verify(
        $otp,
        (string)$otpRow['otp_hash']
    );

    if (!$isValid) {
        $updateStmt = $conn->prepare(
            'UPDATE password_reset_otps
             SET attempts = attempts + 1
             WHERE id = ?'
        );

        if ($updateStmt) {
            $otpId = (int)$otpRow['id'];

            $updateStmt->bind_param('i', $otpId);
            $updateStmt->execute();
            $updateStmt->close();
        }

        setFlash(
            'flash_err',
            'รหัส OTP ไม่ถูกต้อง'
        );

        redirect('verify_reset_otp.php');
    }

    $deleteStmt = $conn->prepare(
        'DELETE FROM password_reset_otps
         WHERE id = ?'
    );

    if ($deleteStmt) {
        $otpId = (int)$otpRow['id'];

        $deleteStmt->bind_param('i', $otpId);
        $deleteStmt->execute();
        $deleteStmt->close();
    }

    $_SESSION['reset_user_id'] = $userId;
    $_SESSION['reset_email'] = $email;
    $_SESSION['reset_verified'] = true;

    redirect('reset_password.php');
}
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta name="theme-color" content="#07933a">

    <title>ยืนยัน OTP | FreshFast</title>

    <style>
        :root{
            --green:#07933a;
            --green-dark:#056f2c;
            --green-soft:#a6efbf;
            --yellow:#f5c400;
            --page:#fff8d9;
            --white:#ffffff;
            --text:#172019;
            --muted:#68736b;
            --border:#dce5df;
            --danger:#a72525;
            --danger-bg:#fff0ef;
            --success:#176b38;
            --success-bg:#ecf9f0;
            --shadow:0 20px 55px rgba(25,70,40,.15);
        }

        *{
            box-sizing:border-box;
        }

        html,
        body{
            width:100%;
            min-height:100%;
            margin:0;
        }

        body{
            min-height:100vh;
            background:
                radial-gradient(
                    circle at top left,
                    rgba(166,239,191,.72),
                    transparent 34%
                ),
                linear-gradient(
                    145deg,
                    var(--page),
                    #fffdf3
                );
            color:var(--text);
            font-family:Arial,Helvetica,sans-serif;
        }

        button,
        input{
            font:inherit;
        }

        a{
            color:inherit;
            text-decoration:none;
        }

        .page{
            min-height:100vh;
            padding:24px;
            display:grid;
            place-items:center;
        }

        .shell{
            width:min(960px,100%);
            min-height:570px;
            display:grid;
            grid-template-columns:1fr 1fr;
            overflow:hidden;
            border:1px solid rgba(7,147,58,.13);
            border-radius:28px;
            background:var(--white);
            box-shadow:var(--shadow);
        }

        .visual{
            position:relative;
            min-height:570px;
            padding:46px;
            display:flex;
            flex-direction:column;
            justify-content:flex-end;
            overflow:hidden;
            background:
                linear-gradient(
                    145deg,
                    rgba(7,147,58,.97),
                    rgba(5,111,44,.94)
                );
            color:#fff;
        }

        .visual::before,
        .visual::after{
            content:"";
            position:absolute;
            border-radius:50%;
            background:rgba(255,255,255,.10);
        }

        .visual::before{
            width:330px;
            height:330px;
            top:-120px;
            right:-100px;
        }

        .visual::after{
            width:230px;
            height:230px;
            left:-85px;
            bottom:-80px;
        }

        .visual-content{
            position:relative;
            z-index:1;
        }

        .visual-icon{
            width:66px;
            height:66px;
            margin-bottom:22px;
            display:grid;
            place-items:center;
            border-radius:20px;
            background:var(--yellow);
            color:#1b241d;
            font-size:34px;
            font-weight:900;
            box-shadow:0 12px 30px rgba(0,0,0,.16);
        }

        .visual h2{
            margin:0;
            max-width:420px;
            font-size:clamp(34px,4vw,48px);
            line-height:1.12;
        }

        .visual p{
            margin:18px 0 0;
            max-width:420px;
            color:rgba(255,255,255,.88);
            font-size:16px;
            line-height:1.75;
        }

        .card{
            padding:44px 42px;
            display:flex;
            flex-direction:column;
            justify-content:center;
        }

        .brand{
            margin-bottom:24px;
            text-align:center;
        }

        .logo-wrap{
            min-height:62px;
            margin-bottom:15px;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .logo{
            display:block;
            width:auto;
            height:58px;
            max-width:215px;
            object-fit:contain;
        }

        .logo-fallback{
            display:none;
            color:var(--green);
            font-size:31px;
            font-weight:900;
        }

        .title{
            margin:0;
            font-size:clamp(30px,5vw,40px);
            line-height:1.2;
        }

        .subtitle{
            margin:10px 0 0;
            color:var(--muted);
            font-size:15px;
            line-height:1.6;
        }

        .email{
            margin-top:14px;
            padding:12px 14px;
            border:1px solid #dcebe1;
            border-radius:12px;
            background:#f4fbf6;
            color:#435248;
            font-size:13px;
            overflow-wrap:anywhere;
        }

        .alert{
            margin-bottom:14px;
            padding:12px 14px;
            border-radius:12px;
            font-size:14px;
            line-height:1.55;
        }

        .alert-success{
            border:1px solid #b9e4c8;
            background:var(--success-bg);
            color:var(--success);
        }

        .alert-error{
            border:1px solid #efc0bc;
            background:var(--danger-bg);
            color:var(--danger);
        }

        .form{
            width:100%;
        }

        .label{
            margin-bottom:8px;
            display:block;
            font-size:14px;
            font-weight:800;
        }

        .input{
            width:100%;
            height:58px;
            padding:0 18px;
            border:1px solid #bcc8bf;
            border-radius:14px;
            outline:none;
            background:#fff;
            color:var(--text);
            font-size:24px;
            font-weight:800;
            letter-spacing:8px;
            text-align:center;
            transition:.2s ease;
        }

        .input::placeholder{
            color:#a3aba5;
            font-size:15px;
            font-weight:400;
            letter-spacing:0;
        }

        .input:focus{
            border-color:var(--green);
            box-shadow:0 0 0 4px rgba(7,147,58,.13);
        }

        .actions{
            margin-top:18px;
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:12px;
        }

        .button{
            min-height:50px;
            padding:12px 18px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border:0;
            border-radius:14px;
            font-weight:900;
            cursor:pointer;
            transition:.15s ease;
        }

        .button:active{
            transform:scale(.98);
        }

        .button-yellow{
            background:var(--yellow);
            color:#222;
        }

        .button-green{
            background:var(--green);
            color:#fff;
        }

        .button-green:hover{
            background:var(--green-dark);
        }

        .back{
            margin-top:20px;
            text-align:center;
        }

        .back a{
            color:var(--green-dark);
            font-size:14px;
            font-weight:800;
        }

        .back a:hover{
            text-decoration:underline;
        }

        @media(max-width:760px){
            .page{
                padding:14px;
                align-items:start;
            }

            .shell{
                min-height:0;
                grid-template-columns:1fr;
                border-radius:22px;
            }

            .visual{
                min-height:190px;
                padding:28px 24px;
                justify-content:center;
            }

            .visual-icon{
                width:52px;
                height:52px;
                margin-bottom:14px;
                border-radius:15px;
                font-size:27px;
            }

            .visual h2{
                font-size:28px;
            }

            .visual p{
                margin-top:10px;
                font-size:14px;
            }

            .card{
                padding:30px 22px 34px;
            }

            .logo{
                height:48px;
                max-width:180px;
            }
        }

        @media(max-width:430px){
            .page{
                padding:8px;
            }

            .shell{
                border-radius:18px;
            }

            .visual{
                min-height:150px;
                padding:22px 18px;
            }

            .visual p{
                display:none;
            }

            .card{
                padding:26px 16px 30px;
            }

            .actions{
                grid-template-columns:1fr;
            }

            .input{
                height:54px;
                font-size:22px;
            }
        }
    </style>
</head>

<body>

<main class="page">
    <section class="shell">

        <div class="visual">
            <div class="visual-content">
                <div class="visual-icon">✓</div>

                <h2>
                    ยืนยันตัวตนด้วยรหัส OTP
                </h2>

                <p>
                    กรอกรหัส 6 หลักที่ระบบส่งไปยังอีเมลของคุณ
                    เพื่อดำเนินการตั้งรหัสผ่านใหม่
                </p>
            </div>
        </div>

        <div class="card">
            <div class="brand">
                <div class="logo-wrap">
                    <img
                        src="/assets/images/logo_ok.png"
                        alt="FreshFast"
                        class="logo"
                        onerror="
                            this.style.display='none';
                            this.nextElementSibling.style.display='block';
                        "
                    >

                    <div class="logo-fallback">
                        FreshFast
                    </div>
                </div>

                <h1 class="title">
                    ยืนยัน OTP
                </h1>

                <p class="subtitle">
                    กรอกรหัส 6 หลักที่ได้รับทางอีเมล
                </p>

                <div class="email">
                    อีเมล:
                    <strong><?= e($email) ?></strong>
                </div>
            </div>

            <?php if ($message !== null): ?>
                <div class="alert alert-success">
                    <?= e($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== null): ?>
                <div class="alert alert-error">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form
                class="form"
                action="verify_reset_otp.php"
                method="post"
                autocomplete="off"
            >
                <label class="label" for="otp">
                    รหัส OTP
                </label>

                <input
                    id="otp"
                    class="input"
                    name="otp"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    maxlength="6"
                    placeholder="กรอกรหัส 6 หลัก"
                    autocomplete="one-time-code"
                    required
                    autofocus
                >

                <div class="actions">
                    <button
                        class="button button-yellow"
                        type="submit"
                    >
                        ยืนยัน
                    </button>

                    <a
                        class="button button-green"
                        href="forgot_password.php"
                    >
                        ส่งรหัสใหม่
                    </a>
                </div>

                <div class="back">
                    <a href="forgot_password.php">
                        ย้อนกลับ
                    </a>
                </div>
            </form>
        </div>

    </section>
</main>

<script>
const otpInput = document.getElementById('otp');

if (otpInput) {
    otpInput.addEventListener('input', function () {
        this.value = this.value
            .replace(/\D/g, '')
            .slice(0, 6);
    });
}
</script>

</body>
</html>