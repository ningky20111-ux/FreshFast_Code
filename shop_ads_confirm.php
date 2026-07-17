<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "db.php";

/*
|--------------------------------------------------------------------------
| ตรวจสอบ session ร้านค้า
|--------------------------------------------------------------------------
*/
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['user_role'] ?? '') !== 'shop'
) {
    header("Location: shop_login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| ตรวจสอบ method
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: shop_ads.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| รับข้อมูลราคาโฆษณา
|--------------------------------------------------------------------------
*/
$userId = (int)$_SESSION['user_id'];
$adPrice = (int)($_POST['ad_price'] ?? 0);

if (!in_array($adPrice, [50, 70, 150])) {
    die("ราคาโฆษณาไม่ถูกต้อง");
}

/*
|--------------------------------------------------------------------------
| ดึงข้อมูลร้านค้า
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT shop_id
    FROM shops
    WHERE owner_id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Prepare shops failed : " . $conn->error);
}

$stmt->bind_param("i", $userId);
$stmt->execute();

$shop = $stmt->get_result()->fetch_assoc();

if (!$shop) {
    die("ไม่พบข้อมูลร้านค้า");
}

$shopId = (int)$shop['shop_id'];

/*
|--------------------------------------------------------------------------
| หา promotion_id
|--------------------------------------------------------------------------
*/
$stmtPromo = $conn->prepare("
    SELECT promotion_id
    FROM promotions
    WHERE discount_type = 'ads'
    AND discount_value = ?
    LIMIT 1
");

if (!$stmtPromo) {
    die("Prepare promotions failed : " . $conn->error);
}

$stmtPromo->bind_param("i", $adPrice);
$stmtPromo->execute();

$promotion = $stmtPromo->get_result()->fetch_assoc();

/*
|--------------------------------------------------------------------------
| ถ้ายังไม่มี promotion ให้สร้างใหม่
|--------------------------------------------------------------------------
*/
if (!$promotion) {

    $title = "โฆษณา {$adPrice} บาท";
    $description = "แพ็กเกจโฆษณา {$adPrice} บาท / วัน";
    $discountType = "ads";
    $status = "active";

    $insertPromo = $conn->prepare("
        INSERT INTO promotions
        (
            title,
            description,
            discount_type,
            discount_value,
            start_date,
            end_date,
            status
        )
        VALUES
        (
            ?, ?, ?, ?,
            CURDATE(),
            DATE_ADD(CURDATE(), INTERVAL 30 DAY),
            ?
        )
    ");

    if (!$insertPromo) {
        die("Prepare insert promotions failed : " . $conn->error);
    }

    $insertPromo->bind_param(
        "sssds",
        $title,
        $description,
        $discountType,
        $adPrice,
        $status
    );

    if (!$insertPromo->execute()) {
        die("Insert promotion failed : " . $insertPromo->error);
    }

    $promotionId = (int)$conn->insert_id;

} else {

    $promotionId = (int)$promotion['promotion_id'];
}

/*
|--------------------------------------------------------------------------
| เพิ่มร้านเข้าโปรโมชั่น
|--------------------------------------------------------------------------
*/
$packagePrice = $adPrice;
$durationDays = 1;
$requestStatus = "pending";

$insert = $conn->prepare("
    INSERT INTO shop_promotions
    (
        shop_id,
        promotion_id,
        package_price,
        duration_days,
        status,
        joined_at
    )
    VALUES
    (
        ?, ?, ?, ?, ?, NOW()
    )
");

if (!$insert) {
    die("Prepare shop_promotions failed : " . $conn->error);
}

$insert->bind_param(
    "iidis",
    $shopId,
    $promotionId,
    $packagePrice,
    $durationDays,
    $requestStatus
);

if (!$insert->execute()) {
    die("Insert shop_promotions failed : " . $insert->error);
}

/*
|--------------------------------------------------------------------------
| สำเร็จ
|--------------------------------------------------------------------------
*/
$_SESSION['success'] = "ส่งคำขอโฆษณาสำเร็จแล้ว";

header("Location: shop_home.php");
exit;

?>
