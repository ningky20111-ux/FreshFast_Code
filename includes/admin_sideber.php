<?php
$currentPage = basename($_SERVER['PHP_SELF']);

require_once "db.php";

$pendingComplaint = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM complaints
    WHERE status = 'pending'
");

if($result){
    $row = $result->fetch_assoc();
    $pendingComplaint = (int)$row['total'];
}

?>

<aside class="sidebar">
    <img src="assets/images/logo_ok.png" class="logo" alt="FreshFast">

    <nav class="menu">

        <a href="admin_dashboard.php"
           class="<?= $currentPage == 'admin_dashboard.php' ? 'active' : '' ?>">
            <i data-lucide="layout-dashboard"></i>
            Dashboard
        </a>

        <a href="admin_users.php"
           class="<?= $currentPage == 'admin_users.php' ? 'active' : '' ?>">
            <i data-lucide="users"></i>
            จัดการผู้ใช้งาน
        </a>

        <a href="admin_orders.php"
           class="<?= $currentPage == 'admin_orders.php' ? 'active' : '' ?>">
            <i data-lucide="shopping-bag"></i>
            จัดการคำสั่งซื้อ
        </a>

        <a href="admin_finance.php"
           class="<?= $currentPage == 'admin_finance.php' ? 'active' : '' ?>">
            <i data-lucide="dollar-sign"></i>
            การเงิน
        </a>

        <a href="admin_complaints.php"
           class="<?= $currentPage == 'admin_complaints.php' ? 'active' : '' ?>">
            <i data-lucide="alert-triangle"></i>
            คำร้องเรียน
        </a>

        <a href="admin_promotions.php"
           class="<?= $currentPage == 'admin_promotions.php' ? 'active' : '' ?>">
            <i data-lucide="gift"></i>
            ระบบโปรโมชั่น
        </a>

        <a href="admin_settings.php"
           class="<?= $currentPage == 'admin_settings.php' ? 'active' : '' ?>">
            <i data-lucide="settings"></i>
            ตั้งค่าระบบ
        </a>

    </nav>
</aside>

<script src="https://unpkg.com/lucide@latest"></script>

<script>
lucide.createIcons();
</script>