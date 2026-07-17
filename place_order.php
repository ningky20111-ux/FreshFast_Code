<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function redirectWithError(string $message): void
{
    $_SESSION['checkout_error'] = $message;
    header('Location: checkout.php');
    exit;
}

function normalizeText(?string $value): string
{
    return trim((string)$value);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}

if (empty($_SESSION['cart'])) {
    redirectWithError('ไม่พบสินค้าในตะกร้า กรุณาเลือกสินค้าก่อนสั่งซื้อ');
}

/*
|--------------------------------------------------------------------------
| รับข้อมูลจากฟอร์ม
|--------------------------------------------------------------------------
*/
$receiver_name    = normalizeText($_POST['receiver_name'] ?? '');
$delivery_address = normalizeText($_POST['delivery_address'] ?? '');
$postal_code      = normalizeText($_POST['postal_code'] ?? '');
$phone            = normalizeText($_POST['phone'] ?? '');
$note             = normalizeText($_POST['note'] ?? '');

/*
|--------------------------------------------------------------------------
| validation
|--------------------------------------------------------------------------
*/
if ($receiver_name === '' || $delivery_address === '' || $phone === '') {
    redirectWithError('กรุณากรอกชื่อผู้รับ ที่อยู่ และเบอร์โทรศัพท์ให้ครบ');
}

if ($postal_code !== '' && !preg_match('/^\d{5}$/', $postal_code)) {
    redirectWithError('รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก');
}

/*
|--------------------------------------------------------------------------
| หา user ปัจจุบัน
|--------------------------------------------------------------------------
*/
$user_id = null;

if (!empty($_SESSION['user_email'])) {
    $user_email = trim((string)$_SESSION['user_email']);
    $stmtUser = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
    if ($stmtUser) {
        $stmtUser->bind_param("s", $user_email);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();
        if ($userRow = $resultUser->fetch_assoc()) {
            $user_id = (int)$userRow['user_id'];
        }
        $stmtUser->close();
    }
}

if (($_POST['postal_code'] ?? '') !== '63110') {
    $_SESSION['checkout_error'] = 'ขออภัย ขณะนี้ให้บริการเฉพาะพื้นที่ 63110 เท่านั้น';
    header('Location: checkout.php');
    exit;
}

if (($_POST['postal_code'] ?? '') !== '63110') {
    $_SESSION['checkout_error'] = 'ขออภัย ให้บริการเฉพาะพื้นที่ 63110 เท่านั้น';
    header('Location: checkout.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| ถ้าไม่มี session login ให้ fallback เป็น user ล่าสุดที่ role customer
|--------------------------------------------------------------------------
*/
if (!$user_id) {
    $fallbackSql = "SELECT user_id FROM users WHERE role = 'customer' ORDER BY user_id DESC LIMIT 1";
    $fallbackRes = $conn->query($fallbackSql);
    if ($fallbackRes && $fallbackRes->num_rows > 0) {
        $fallbackRow = $fallbackRes->fetch_assoc();
        $user_id = (int)$fallbackRow['user_id'];
    }
}

if (!$user_id) {
    redirectWithError('ไม่พบข้อมูลผู้ใช้งานสำหรับสร้างคำสั่งซื้อ');
}

/*
|--------------------------------------------------------------------------
| ดึงข้อมูลสินค้าในตะกร้า
|--------------------------------------------------------------------------
*/
$productIds = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($productIds), '?'));
$types = str_repeat('i', count($productIds));

$sql = "
    SELECT 
        p.product_id,
        p.product_name,
        p.price,
        p.shop_id,
        s.shop_name
    FROM products p
    LEFT JOIN shops s ON p.shop_id = s.shop_id
    WHERE p.product_id IN ($placeholders)
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    redirectWithError('เกิดข้อผิดพลาดในการเตรียมข้อมูลสินค้า');
}

$stmt->bind_param($types, ...$productIds);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$subtotal = 0.00;
$total_qty = 0;

while ($row = $result->fetch_assoc()) {
    $product_id = (int)$row['product_id'];
    $qty = (int)($_SESSION['cart'][$product_id] ?? 0);

    if ($qty <= 0) {
        continue;
    }

    $price = (float)$row['price'];
    $line_total = $price * $qty;
    $subtotal += $line_total;
    $total_qty += $qty;

    $items[] = [
        'product_id'   => $product_id,
        'product_name' => $row['product_name'],
        'quantity'     => $qty,
        'price'        => $price,
        'line_total'   => $line_total,
        'shop_name'    => $row['shop_name'] ?? null,
    ];
}
$stmt->close();

if (empty($items)) {
    redirectWithError('ไม่พบรายการสินค้าที่พร้อมสั่งซื้อ');
}

$delivery_fee = 60.00;
$discount = 0.00;
$total_amount = $subtotal + $delivery_fee - $discount;

/*
|--------------------------------------------------------------------------
| บันทึกข้อมูล
|--------------------------------------------------------------------------
*/
$conn->begin_transaction();

try {
    $default_rider_id = 1;

    $sqlOrder = "
        INSERT INTO orders (
            user_id, rider_id, status,
            receiver_name, phone, delivery_address, postal_code, note,
            subtotal, delivery_fee, discount, total_amount
        ) VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmtOrder = $conn->prepare($sqlOrder);
    if (!$stmtOrder) {
        throw new Exception('เตรียมคำสั่งบันทึกออเดอร์ไม่สำเร็จ');
    }

    $stmtOrder->bind_param(
        "iisssssdddd",
        $user_id,
        $default_rider_id,
        $receiver_name,
        $phone,
        $delivery_address,
        $postal_code,
        $note,
        $subtotal,
        $delivery_fee,
        $discount,
        $total_amount
    );

    if (!$stmtOrder->execute()) {
        throw new Exception('บันทึกคำสั่งซื้อไม่สำเร็จ');
    }

    $order_id = (int)$stmtOrder->insert_id;
    $stmtOrder->close();

    $sqlItem = "
        INSERT INTO order_items (
            order_id, product_id, product_name, quantity, price, line_total, shop_name
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ";

    $stmtItem = $conn->prepare($sqlItem);
    if (!$stmtItem) {
        throw new Exception('เตรียมคำสั่งบันทึกรายการสินค้าไม่สำเร็จ');
    }

    foreach ($items as $item) {
        $stmtItem->bind_param(
            "iisidds",
            $order_id,
            $item['product_id'],
            $item['product_name'],
            $item['quantity'],
            $item['price'],
            $item['line_total'],
            $item['shop_name']
        );

        if (!$stmtItem->execute()) {
            throw new Exception('บันทึกรายการสินค้าในคำสั่งซื้อไม่สำเร็จ');
        }
    }

    $stmtItem->close();
    $conn->commit();

    /*
    |--------------------------------------------------------------------------
    | เก็บข้อมูลไว้ใช้ในหน้าถัดไป
    |--------------------------------------------------------------------------
    */
    $_SESSION['last_order_id'] = $order_id;
    $_SESSION['last_order_total'] = $total_amount;
    $_SESSION['last_order_receiver'] = $receiver_name;

    $_SESSION['checkout'] = [
        'receiver_name'    => $receiver_name,
        'delivery_address' => $delivery_address,
        'postal_code'      => $postal_code,
        'phone'            => $phone,
        'note'             => $note,
    ];

    $_SESSION['cart'] = [];


    header("Location: order_success.php?order_id=" . $order_id);
    exit;

} catch (Throwable $e) {
    $conn->rollback();
    redirectWithError('เกิดข้อผิดพลาดระหว่างสร้างคำสั่งซื้อ: ' . $e->getMessage());
}
?>
