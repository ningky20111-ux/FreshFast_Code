<?php
session_start();

// กัน cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// ล้าง session
$_SESSION = [];

// ลบ cookie session
if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

/* redirect แบบยืดหยุ่น */
$redirect = $_GET['redirect'] ?? 'home.php';

header("Location: " . $redirect);
exit;
?>