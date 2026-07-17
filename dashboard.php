<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}
?>

<h1>ยินดีต้อนรับสู่ FreshFast</h1>
<a href="logout.php">ออกจากระบบ</a>
