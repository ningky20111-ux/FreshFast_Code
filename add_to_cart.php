<?php
session_start();
require_once 'db.php';

$productId = (int)($_POST['product_id'] ?? 0);

if ($productId <= 0) {
    header("Location: home.php");
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* หา shop_id ของสินค้าที่กำลังจะเพิ่ม */
$stmt = $conn->prepare("
    SELECT shop_id, product_name
    FROM products
    WHERE product_id = ?
");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: home.php");
    exit;
}

$newShopId = (int)$product['shop_id'];

/* ถ้ามีสินค้าใน cart อยู่แล้ว */
if (!empty($_SESSION['cart'])) {

    $existingIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($existingIds), '?'));
    $types = str_repeat('i', count($existingIds));

    $sql = "
        SELECT DISTINCT shop_id
        FROM products
        WHERE product_id IN ($placeholders)
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$existingIds);
    $stmt->execute();

    $existingShop = $stmt->get_result()->fetch_assoc();
    $cartShopId = (int)$existingShop['shop_id'];

    if ($cartShopId !== $newShopId) {
        $_SESSION['cart_error'] = "ไม่สามารถเพิ่มสินค้าจากคนละร้านในตะกร้าเดียวกันได้";
        unset($_SESSION['cart_success']); // สำคัญมาก ล้างข้อความสำเร็จเก่า
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

/* เพิ่มสินค้า */
$_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + 1;

unset($_SESSION['cart_error']); // ล้าง error เก่า
$_SESSION['cart_success'] = "เพิ่มสินค้าเรียบร้อยแล้ว";

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;

