<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/db.php';

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    die('ไม่สามารถเชื่อมต่อฐานข้อมูลได้');
}

$conn->set_charset('utf8mb4');

if (empty($_SESSION['user_id']) && empty($_SESSION['user_email'])) {
    header('Location: /login.php');
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function hasColumn(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare(
        'SELECT COUNT(*) AS total
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?'
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int)($row['total'] ?? 0) > 0;
}

function publicImageUrl(string $storedPath, string $fallback): string
{
    $cleanPath = ltrim(trim($storedPath), '/');

    if ($cleanPath !== '' && is_file(__DIR__ . '/' . $cleanPath)) {
        return '/' . $cleanPath;
    }

    return $fallback;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$sessionEmail = trim((string)($_SESSION['user_email'] ?? ''));

$hasProfileImage = hasColumn($conn, 'users', 'profile_image');
$profileSelect = $hasProfileImage ? ', profile_image' : '';

if ($userId > 0) {
    $userStmt = $conn->prepare(
        'SELECT user_id, name, email, password' . $profileSelect . '
         FROM users
         WHERE user_id = ?
         LIMIT 1'
    );

    if (!$userStmt) {
        die('ไม่สามารถโหลดข้อมูลผู้ใช้ได้');
    }

    $userStmt->bind_param('i', $userId);
} else {
    $userStmt = $conn->prepare(
        'SELECT user_id, name, email, password' . $profileSelect . '
         FROM users
         WHERE email = ?
         LIMIT 1'
    );

    if (!$userStmt) {
        die('ไม่สามารถโหลดข้อมูลผู้ใช้ได้');
    }

    $userStmt->bind_param('s', $sessionEmail);
}

$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$user) {
    session_destroy();
    header('Location: /login.php');
    exit;
}

$userId = (int)$user['user_id'];
$_SESSION['user_id'] = $userId;
$_SESSION['user_email'] = (string)$user['email'];

$fallbackAvatar = 'https://www.gravatar.com/avatar/'
    . md5(strtolower(trim((string)$user['email'])))
    . '?d=identicon&s=160';

$profileImage = publicImageUrl(
    (string)($user['profile_image'] ?? ''),
    $fallbackAvatar
);


if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cartCount = array_sum(
    array_map('intval', $_SESSION['cart'])
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $_SESSION['password_error'] = 'กรุณากรอกรหัสผ่านให้ครบทุกช่อง';
    } elseif (strlen($newPassword) < 8) {
        $_SESSION['password_error'] = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร';
    } elseif ($newPassword !== $confirmPassword) {
        $_SESSION['password_error'] = 'รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน';
    } elseif (hash_equals($currentPassword, $newPassword)) {
        $_SESSION['password_error'] = 'รหัสผ่านใหม่ต้องไม่เหมือนรหัสผ่านปัจจุบัน';
    } else {
        $storedPassword = (string)($user['password'] ?? '');

        /*
         * รองรับทั้งบัญชีที่เก็บด้วย password_hash()
         * และข้อมูลเก่าที่อาจยังเป็นข้อความธรรมดา เพื่อย้ายเข้าสู่ระบบ Hash
         */
        $currentPasswordIsCorrect = password_verify(
            $currentPassword,
            $storedPassword
        );

        if (
            !$currentPasswordIsCorrect
            && $storedPassword !== ''
            && hash_equals($storedPassword, $currentPassword)
        ) {
            $currentPasswordIsCorrect = true;
        }

        if (!$currentPasswordIsCorrect) {
            $_SESSION['password_error'] = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
        } else {
            $newPasswordHash = password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

            if ($newPasswordHash === false) {
                $_SESSION['password_error'] = 'ไม่สามารถเข้ารหัสรหัสผ่านใหม่ได้';
            } else {
                $updateStmt = $conn->prepare(
                    'UPDATE users SET password = ? WHERE user_id = ?'
                );

                if (!$updateStmt) {
                    $_SESSION['password_error'] = 'เตรียมบันทึกรหัสผ่านไม่สำเร็จ';
                } else {
                    $updateStmt->bind_param(
                        'si',
                        $newPasswordHash,
                        $userId
                    );

                    if ($updateStmt->execute()) {
                        $_SESSION['password_success'] = 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว';
                    } else {
                        $_SESSION['password_error'] = 'เปลี่ยนรหัสผ่านไม่สำเร็จ';
                    }

                    $updateStmt->close();
                }
            }
        }
    }

    header('Location: /change_password.php', true, 303);
    exit;
}

$success = (string)($_SESSION['password_success'] ?? '');
$error = (string)($_SESSION['password_error'] ?? '');

unset($_SESSION['password_success'], $_SESSION['password_error']);
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, viewport-fit=cover"
    >

    <title>เปลี่ยนรหัสผ่าน | FreshFast</title>

    <?php $cssPath = __DIR__ . '/assets/css/change_password.css'; ?>
    <?php if (is_file($cssPath)): ?>
        <style><?= file_get_contents($cssPath) ?></style>
    <?php endif; ?>

    <style>
    .actions{
        display:flex;
        align-items:center;
        gap:14px;
    }

    .cart-icon-btn{
        position:relative;
        width:42px;
        height:42px;
        display:flex;
        align-items:center;
        justify-content:center;
        border-radius:50%;
        color:#222;
        text-decoration:none;
        transition:.2s ease;
    }

    .cart-icon-btn:hover{
        background:#f5f5f5;
        color:#16a34a;
        transform:scale(1.05);
    }

    .cart-icon{
        width:26px;
        height:26px;
        display:block;
    }

    .cart-count{
        position:absolute;
        top:-5px;
        right:-6px;
        min-width:19px;
        height:19px;
        padding:0 5px;
        display:flex;
        align-items:center;
        justify-content:center;
        border:2px solid #fff;
        border-radius:999px;
        background:#e5252a;
        color:#fff;
        font-size:10px;
        font-weight:800;
        line-height:1;
        pointer-events:none;
    }

    .profile-menu summary{
        list-style:none;
        cursor:pointer;
    }

    .profile-menu summary::-webkit-details-marker{
        display:none;
    }

    .profile-menu summary img{
        width:40px;
        height:40px;
        display:block;
        border-radius:50%;
        object-fit:cover;
    }
    </style>

</head>

<body>

<header class="topbar">
    <div class="topbar__inner">

        <button
            type="button"
            class="menu-btn"
            id="menuBtn"
            aria-label="เปิดเมนู"
        >
            ☰
        </button>

        <a
            class="brand"
            href="/home.php"
            aria-label="FreshFast"
        >
            <img
                src="/assets/images/logo_ok.png?v=<?= time() ?>"
                alt="FreshFast"
            >
        </a>

        <details class="desktop-menu">
            <summary>เมนู ▼</summary>

            <div>
                <a href="/home.php">หน้าหลัก</a>
                <a href="/category.php?id=1">เนื้อสัตว์</a>
                <a href="/category.php?id=2">ผักผลไม้</a>
                <a href="/category.php?id=3">เครื่องปรุง</a>
                <a href="/category.php?id=4">เครื่องดื่ม</a>
                <a href="/category.php?id=5">แช่แข็ง</a>
                <a href="/category.php?id=6">ของใช้ในครัว</a>
                <a href="/category.php?id=7">ของหวาน</a>
                <a href="/shop_all.php">ร้านค้าทั้งหมด</a>
            </div>
        </details>

        <form
            class="search"
            action="/search.php"
            method="get"
        >
            <input
                type="search"
                name="q"
                placeholder="ค้นหาสินค้า / ร้านค้า"
            >
        </form>

        <div class="actions">
            <a
                href="/cart.php"
                class="cart-icon-btn"
                aria-label="ตะกร้าสินค้า"
            >
                <svg
                    viewBox="0 0 24 24"
                    class="cart-icon"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <circle cx="9" cy="20" r="1.5"/>
                    <circle cx="17" cy="20" r="1.5"/>
                    <path d="M3 3h2l2.5 11h11l2-7H6.5"/>
                </svg>

                <?php if ($cartCount > 0): ?>
                    <span class="cart-count">
                        <?= (int)$cartCount ?>
                    </span>
                <?php endif; ?>
            </a>

            <details class="profile-menu">
                <summary>
                    <img
                        src="<?= e($profileImage) ?>?v=<?= time() ?>"
                        alt="รูปโปรไฟล์"
                    >
                </summary>

                <div>
                    <a href="/account.php">บัญชีของฉัน</a>
                    <a href="/my_address.php">ที่อยู่ของฉัน</a>
                    <a href="/my_orders.php">คำสั่งซื้อของฉัน</a>
                    <a href="/change_password.php">เปลี่ยนรหัสผ่าน</a>
                    <a href="/logout.php">ออกจากระบบ</a>
                </div>
            </details>
        </div>

    </div>
</header>

<aside
    class="mobile-menu"
    id="mobileMenu"
>
    <button
        type="button"
        id="closeMenu"
        class="mobile-close"
        aria-label="ปิดเมนู"
    >
        ×
    </button>

    <img
        src="/assets/images/logo_ok.png?v=<?= time() ?>"
        alt="FreshFast"
    >

    <a href="/home.php">หน้าหลัก</a>
    <a href="/account.php">แก้ไขโปรไฟล์</a>
    <a href="/my_address.php">ที่อยู่ของฉัน</a>
    <a href="/my_orders.php">คำสั่งซื้อของฉัน</a>
    <a href="/change_password.php">เปลี่ยนรหัสผ่าน</a>
    <a href="/cart.php">ตะกร้าสินค้า</a>
    <a href="/logout.php">ออกจากระบบ</a>
</aside>

<div
    class="overlay"
    id="overlay"
></div>

<section class="hero">
    <h1>บัญชีของฉัน</h1>
    <p>เปลี่ยนรหัสผ่าน</p>
</section>

<main class="layout">

    <nav class="side">
        <a href="/account.php">แก้ไขโปรไฟล์</a>
        <a href="/my_address.php">ที่อยู่ของฉัน</a>
        <a href="/my_orders.php">คำสั่งซื้อของฉัน</a>
        <a href="/change_password.php" class="active">เปลี่ยนรหัสผ่าน</a>
    </nav>

    <section class="card">
        <div class="card-heading">
            <h2>เปลี่ยนรหัสผ่าน</h2>
            <p>กรอกรหัสผ่านปัจจุบันก่อนตั้งรหัสผ่านใหม่</p>
        </div>

        <?php if ($success !== ''): ?>
            <div class="alert ok"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert err"><?= e($error) ?></div>
        <?php endif; ?>

        <form
            method="post"
            action="/change_password.php"
            class="password-form"
            autocomplete="off"
        >
            <div class="field">
                <label for="current_password">
                    รหัสผ่านปัจจุบัน
                </label>

                <div class="password-input">
                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        data-target="current_password"
                        aria-label="แสดงหรือซ่อนรหัสผ่าน"
                    >
                        แสดง
                    </button>
                </div>
            </div>

            <div class="field">
                <label for="new_password">
                    รหัสผ่านใหม่
                </label>

                <div class="password-input">
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        data-target="new_password"
                        aria-label="แสดงหรือซ่อนรหัสผ่าน"
                    >
                        แสดง
                    </button>
                </div>

                <small>
                    รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร
                </small>
            </div>

            <div class="field">
                <label for="confirm_password">
                    ยืนยันรหัสผ่านใหม่
                </label>

                <div class="password-input">
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        data-target="confirm_password"
                        aria-label="แสดงหรือซ่อนรหัสผ่าน"
                    >
                        แสดง
                    </button>
                </div>
            </div>

            <div class="password-policy">
                <strong>คำแนะนำเพื่อความปลอดภัย</strong>
                <ul>
                    <li>ใช้รหัสผ่านอย่างน้อย 8 ตัวอักษร</li>
                    <li>ควรมีตัวอักษร ตัวเลข และอักขระพิเศษร่วมกัน</li>
                    <li>ไม่ควรใช้ชื่อ อีเมล หรือรหัสเดิมเป็นรหัสผ่าน</li>
                    <li>อย่าเปิดเผยรหัสผ่านให้บุคคลอื่น</li>
                </ul>
            </div>

            <button type="submit" class="save-button">
                บันทึกรหัสผ่านใหม่
            </button>
        </form>
    </section>

</main>

<script>
const mobileMenu = document.getElementById('mobileMenu');
const overlay = document.getElementById('overlay');

function openMenu() {
    mobileMenu?.classList.add('show');
    overlay?.classList.add('show');
}

function closeMenu() {
    mobileMenu?.classList.remove('show');
    overlay?.classList.remove('show');
}

document.getElementById('menuBtn')?.addEventListener('click', openMenu);
document.getElementById('closeMenu')?.addEventListener('click', closeMenu);
overlay?.addEventListener('click', closeMenu);

document.querySelectorAll('.toggle-password').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.target);

        if (!input) return;

        const shouldShow = input.type === 'password';

        input.type = shouldShow ? 'text' : 'password';
        button.textContent = shouldShow ? 'ซ่อน' : 'แสดง';
    });
});
</script>

</body>
</html>