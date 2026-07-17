
<?php
session_start();
require_once "db.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($conn)) {
    require_once "db.php";
    $conn->set_charset("utf8mb4");
}

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function columnExists($conn, $table, $column) {
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

/* =========================
   FIX / CHECK COLUMNS
========================= */



if (!columnExists($conn, "products", "product_description")) {
    $conn->query("ALTER TABLE products ADD COLUMN product_description TEXT NULL");
}

if (!columnExists($conn, "products", "product_status")) {
    $conn->query("ALTER TABLE products ADD COLUMN product_status VARCHAR(30) DEFAULT 'active'");
}

if (!columnExists($conn, "products", "unit")) {
    $conn->query("ALTER TABLE products ADD COLUMN unit VARCHAR(30) DEFAULT 'kg'");
}

if (!columnExists($conn, "products", "updated_at")) {
    $conn->query("ALTER TABLE products ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
}

if (!columnExists($conn, "products", "product_image")) {
    $conn->query("ALTER TABLE products ADD COLUMN product_image VARCHAR(255) NULL");
} else {
    $conn->query("ALTER TABLE products MODIFY product_image VARCHAR(255) NULL");
}

if (!columnExists($conn, "shops", "owner_id")) {
    $conn->query("ALTER TABLE shops ADD COLUMN owner_id INT NULL");
}

/* =========================
   CURRENT USER
========================= */
$shopId = $_SESSION['shop_id'] ?? 0;

if (!$shopId) {
    header("Location: shop_login.php");
    exit;
}

$stmt = $conn->prepare("
SELECT *
FROM shops
WHERE shop_id = ?
LIMIT 1
");

$stmt->bind_param("i", $shopId);
$stmt->execute();

$shop = $stmt->get_result()->fetch_assoc();

if (!$shop) {
    session_destroy();
    header("Location: shop_login.php");
    exit;
}

/* =========================
   GET SHOP
========================= */

/* =========================
   UPLOAD PRODUCT IMAGE
========================= */

function uploadProductImage($shopId) {
    if (!isset($_FILES['product_image']) || $_FILES['product_image']['error'] !== 0) {
        return null;
    }

    $folder = "assets/producting/";

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed)) {
        return null;
    }

    $fileName = "product_" . $shopId . "_" . time() . "." . $ext;
    $savePath = $folder . $fileName;

    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $savePath)) {
        return $savePath;
    }

    return null;
}

/* =========================
   EDIT PRODUCT
========================= */

$editId = (int)($_GET['edit'] ?? 0);
$product = null;

if ($editId > 0) {
    $stmt = $conn->prepare("
        SELECT *
        FROM products
        WHERE product_id = ?
        AND shop_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $editId, $shopId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
}

/* =========================
   SAVE PRODUCT
========================= */

if(isset($_GET['disable'])){

    $id = (int)$_GET['disable'];

    $stmt = $conn->prepare("
        UPDATE products
        SET product_status='inactive'
        WHERE product_id=?
        AND shop_id=?
    ");

    $stmt->bind_param("ii",$id,$shopId);
    $stmt->execute();

    header("Location: shop_products.php");
    exit;
}

if(isset($_GET['enable'])){

    $id = (int)$_GET['enable'];

    $stmt = $conn->prepare("
        UPDATE products
        SET product_status='active'
        WHERE product_id=?
        AND shop_id=?
    ");

    $stmt->bind_param("ii",$id,$shopId);
    $stmt->execute();

    header("Location: shop_products.php");
    exit;
}

if(isset($_GET['delete'])){

    $id = (int)$_GET['delete'];

    $stmt = $conn->prepare("
        DELETE FROM products
        WHERE product_id=?
        AND shop_id=?
    ");

    $stmt->bind_param("ii",$id,$shopId);
    $stmt->execute();

    header("Location: shop_products.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $productId = (int)($_POST['product_id'] ?? 0);
    $productName = trim($_POST['product_name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['product_description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $unit = trim($_POST['unit'] ?? 'kg');

    if ($productName === '') {
        $productName = "สินค้าใหม่";
    }

    $newImage = uploadProductImage($shopId);

    if ($productId > 0) {
        $stmt = $conn->prepare("
            SELECT product_image 
            FROM products 
            WHERE product_id = ? 
            AND shop_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $productId, $shopId);
        $stmt->execute();
        $old = $stmt->get_result()->fetch_assoc();

        $productImage = $newImage ? $newImage : ($old['product_image'] ?? null);
        $stock = 0;
        $stmt = $conn->prepare("
            UPDATE products
            SET 
                product_name = ?,
                category_id = ?,
                product_description = ?,
                price = ?,
                unit = ?,
                product_image = ?
            WHERE product_id = ?
            AND shop_id = ?
        ");

            $stmt->bind_param(
                "sisdssii",
                $productName,
                $categoryId,
                $description,
                $price,
                $unit,
                $productImage,
                $productId,
                $shopId
            );

        $stmt->execute();

        header("Location: shop_products.php?edit=" . $productId . "&success=1");
        exit;

    } else {
        $productImage = $newImage;

        $stmt = $conn->prepare("
            INSERT INTO products
                (
                    product_name,
                    price,
                    unit,
                    shop_id,
                    category_id,
                    product_image,
                    product_status,
                    product_description,
                    stock
                )
            VALUES
                (?, ?, ?, ?, ?, ?, 'active', ?, ?)
        ");
        $stock = 0;
        $stmt->bind_param(
            "sdsiissi",
            $productName,
            $price,
            $unit,
            $shopId,
            $categoryId,
            $productImage,
            $description,
            $stock
        );

        $stmt->execute();

$newProductId = $conn->insert_id;

header("Location: shop_products.php?success=1");
exit;
    }
}

/* =========================
   LOAD DATA
========================= */

$categories = $conn->query("SELECT * FROM categories ORDER BY category_id ASC");

$stmt = $conn->prepare("
    SELECT p.*, c.category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.shop_id = ?
    ORDER BY p.updated_at DESC, p.product_id DESC
");
$stmt->bind_param("i", $shopId);
$stmt->execute();
$myProducts = $stmt->get_result();

$productImage = "";

if (!empty($product) && !empty($product['product_image']) && file_exists($product['product_image'])) {
    $productImage = $product['product_image'];
}

$success = isset($_GET['success']);
if(isset($_GET['success'])){
    $product = null;
    $productImage = "";
}
/* =========================
   SHOP PROFILE IMAGE
========================= */

$shopImage = "";

if (
    !empty($shop['shop_image']) &&
    file_exists($shop['shop_image'])
) {
    $shopImage = $shop['shop_image'];
}
?>


<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>สินค้า | FreshFast</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{
    box-sizing:border-box;
    font-family:sans-serif;
}

body{
    margin:0;
    background:#eef7f0;
    color:#111;
}

/* ================= HEADER ================= */

.header{
    height:84px;
    background:#fff;

    display:flex;
    align-items:center;
    justify-content:space-between;

    padding:0 28px;

    box-shadow:0 2px 10px rgba(0,0,0,.04);
}

.logo img{
    height:54px;
    display:block;
}

/* ================= PROFILE ================= */

.profile-menu{
    position:relative;
}

.profile-btn{
    width:48px;
    height:48px;

    border:none;
    padding:0;

    border-radius:50%;
    overflow:hidden;

    cursor:pointer;

    background:none;

    box-shadow:0 4px 12px rgba(0,0,0,.15);
}

.profile-btn img{
    width:100%;
    height:100%;
    object-fit:cover;
}

/* ================= DROPDOWN ================= */

.profile-dropdown{
    position:absolute;

    top:58px;
    right:0;

    width:190px;

    background:#fff;

    border-radius:18px;

    box-shadow:0 14px 35px rgba(0,0,0,.12);

    padding:8px;

    display:none;

    z-index:999;
}

.profile-dropdown.show{
    display:block;
}

.profile-dropdown a{
    display:block;

    padding:12px 14px;

    border-radius:12px;

    text-decoration:none;

    color:#111;

    font-weight:700;

    transition:.2s;
}

.profile-dropdown a:hover{
    background:#f5f5f5;
}

/* ================= NAV ================= */

.nav{
    height:54px;
    background:#ffd400;

    display:flex;
    align-items:center;
    justify-content:center;

    gap:54px;

    box-shadow:0 2px 6px rgba(0,0,0,.04);
}

.nav a{
    text-decoration:none;
    color:#111;
    font-weight:700;
    font-size:15px;

    transition:.2s;
}

.nav a:hover{
    transform:translateY(-1px);
}

.nav a.active{
    color:#008c3a;
}

/* ================= TITLE ================= */


.page-title{text-align:center;font-size:22px;font-weight:700;margin:46px 0}

/* ================= SUCCESS ================= */

.success{
    background:#dcfce7;

    border-radius:18px;

    padding:14px 20px;

    text-align:center;

    font-weight:700;

    margin:0 auto 24px;

    width:340px;

    color:#166534;

    box-shadow:0 6px 18px rgba(0,0,0,.06);
}

/* ================= FORM ================= */

.form-section{
    padding:0 40px 40px;
}

.form-grid{
    background:#fff;

    border-radius:28px;

    display:grid;
    grid-template-columns:300px 1fr;

    gap:60px;

    padding:40px;

    box-shadow:0 10px 30px rgba(0,0,0,.08);
}

.image-wrap{
    position:relative;
}

.image-box{
    width:300px;
    height:300px;

    border-radius:24px;

    overflow:hidden;

    background:#f5f5f5;
}

.image-box img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.upload-label{
    position:absolute;

    right:12px;
    bottom:12px;

    width:58px;
    height:58px;

    border-radius:50%;

    background:#16a34a;
    color:#fff;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:28px;

    cursor:pointer;

    box-shadow:0 8px 20px rgba(0,0,0,.15);

    z-index:5;
}
.image-wrap{
    position:relative;
    width:300px;
    height:300px;
}

.hidden-file{
    display:none;
}

.input-panel label{
    display:block;

    font-size:16px;
    font-weight:700;

    margin:14px 0 8px;
}
.input-panel select{
    width:100%;

    border:none;

    border-radius:18px;

    background:#f5f5f5;

    padding:14px 48px 14px 16px;

    outline:none;

    font-size:15px;
    font-weight:600;

    appearance:none;
    -webkit-appearance:none;
    -moz-appearance:none;

    cursor:pointer;

    transition:.2s;

    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");

    background-repeat:no-repeat;

    background-position:right 16px center;

    background-size:16px;
}
.input-panel select:focus{
    background-color:#fff;

    box-shadow:
    0 0 0 4px rgba(22,163,74,.12);

    transform:translateY(-1px);
}
.input-panel input,
.input-panel textarea{
    width:100%;

    border:none;

    border-radius:16px;

    background:#f5f5f5;

    padding:14px 16px;

    outline:none;

    font-size:15px;
}

.input-panel textarea{
    resize:none;
    min-height:90px;
}

.price-row{
    display:flex;
    align-items:center;
    gap:10px;
}

.price-row input{
    width:150px;
}

.price-row select{
    width:160px;
}

.promo-pill{
    display:inline-flex;
    align-items:center;
    gap:8px;

    background:#f3f4f6;

    border-radius:999px;

    padding:10px 16px;

    font-weight:700;

    margin-top:6px;
}

/* ================= BUTTONS ================= */

.button-row{
    margin-top:30px;

    display:flex;
    gap:14px;
}

.cancel-btn,
.save-btn{
    border:none;

    border-radius:16px;

    padding:14px 28px;

    font-size:15px;
    font-weight:700;

    cursor:pointer;

    text-decoration:none;

    transition:.2s;
}

.cancel-btn{
    background:#f3f4f6;
    color:#111;
}

.save-btn{
    background:#16a34a;
    color:#fff;
}

.cancel-btn:hover,
.save-btn:hover{
    transform:translateY(-2px);
}

/* ================= PRODUCT LIST ================= */

.product-list{
    padding:20px 40px 70px;
}

.product-list h2{
    text-align:center;

    font-size:32px;
    font-weight:800;

    margin-bottom:30px;
}

.products-grid{
    display:grid;

    grid-template-columns:
    repeat(auto-fill,minmax(170px,1fr));

    gap:18px;
}

.product-card{
    background:#fff;
    border-radius:28px;
    padding:14px;
    text-decoration:none;
    color:#111;
    box-shadow:0 8px 20px rgba(0,0,0,.08);

    display:flex;
    flex-direction:column;

    height:100%;          /* เพิ่ม */
    min-height:320px;     /* เพิ่ม */
}
.product-card img,
.product-placeholder{
    width:100%;
    aspect-ratio:1/1;
    border-radius:18px;
    object-fit:cover;
}
.product-placeholder{
    width:100%;
    aspect-ratio:1/1;

    border-radius:20px;

    background:
    linear-gradient(
        145deg,
        #f7f7f7,
        #ececec
    );

    border:1px solid #e5e5e5;

    display:flex;
    align-items:center;
    justify-content:center;

    overflow:hidden;
}

.product-placeholder svg{
    width:64px;
    height:64px;

    stroke:#c9c9c9;

    stroke-width:1.6;

    opacity:.9;
}
.product-info{
    padding-top:12px;
}

.product-name{
    font-size:18px;
    font-weight:800;

    line-height:1.3;

    margin-bottom:4px;
}

.product-category{
    color:#777;

    font-size:13px;
    font-weight:600;

    margin-bottom:14px;
}

.product-price{
    font-size:16px;
    font-weight:800;
}
.product-card h3{
    margin:0 0 10px;

    font-size:20px;
    font-weight:800;
}


/* /////////////////////// */

.product-card p{
    margin:4px 0;

    color:#555;

    font-size:15px;
}
/* ปุ่มด้านล่างของการ์ด */
.product-actions{
    display:flex;
    gap:8px;
    margin-top:14px;
}

/* ปุ่มทั่วไป */
.action-btn{
    flex:1;
    border:none;
    border-radius:12px;
    padding:10px 0;
    font-size:14px;
    font-weight:700;
    cursor:pointer;
    transition:.25s;
    color:#fff;

    display:flex;
    align-items:center;
    justify-content:center;
    text-decoration:none;
}

/* ปุ่มปิดสินค้า */
.btn-toggle{
    background:#f59e0b;
}

.btn-toggle:hover{
    background:#d97706;
    transform:translateY(-2px);
}

/* ถ้าสินค้าปิดแล้ว */
.btn-toggle.closed{
    background:#16a34a;
}

.btn-toggle.closed:hover{
    background:#15803d;
}

/* ปุ่มลบ */
.btn-delete{
    background:#ef4444;
}

.btn-delete:hover{
    background:#dc2626;
    transform:translateY(-2px);
}
.product-info{
    display:flex;
    flex-direction:column;
    flex:1;
}
/* มือถือ */
@media(max-width:768px){

    .product-actions{
        flex-direction:column;
        gap:10px;
    }

    .action-btn{
        width:100%;
        font-size:15px;
        padding:12px;
    }

}
/* ================= FOOTER ================= */

.footer{
    padding:0 40px 40px;
}

.footer-line{
    border-top:1px solid #ddd;

    text-align:center;

    padding-top:26px;

    color:#444;
    font-weight:600;
}

.footer small{
    display:block;
    margin-top:18px;
    color:#888;
}
.product-card{
    cursor:pointer;
}

/* ================= MOBILE ================= */
@media (max-width: 768px){

    body{
        margin:0;
        background:#eef7f0;
        font-family:sans-serif;
        color:#111;
    }

    /* HEADER */

    .header{
        height:70px;

        background:#fff;

        display:flex;
        align-items:center;
        justify-content:space-between;

        padding:0 18px;

        position:sticky;
        top:0;

        z-index:1000;

        box-shadow:
        0 2px 12px rgba(0,0,0,.05);
    }

    .logo img{
        height:42px;
    }

    /* PROFILE */

    .profile-btn{
        width:42px;
        height:42px;

        border:none;
        border-radius:50%;

        overflow:hidden;

        background:none;

        padding:0;
    }

    .profile-btn img{
        width:100%;
        height:100%;
        object-fit:cover;
    }

    /* NAVBAR */

    .nav{
        height:54px;

        background:#ffd400;

        display:flex;
        align-items:center;

        gap:18px;

        overflow-x:auto;

        padding:0 18px;

        white-space:nowrap;
    }

    .nav::-webkit-scrollbar{
        display:none;
    }

    .nav a{
        text-decoration:none;

        color:#111;

        font-size:14px;
        font-weight:700;
    }

    .nav a.active{
        color:#008c3a;
    }

    /* TITLE */

    .page-title{
        text-align:center;

        font-size:24px;
        font-weight:800;

        margin:26px 0 22px;
    }

    /* SUCCESS */

    .success{
        width:calc(100% - 32px);

        margin:auto auto 18px;

        background:#dcfce7;

        color:#166534;

        padding:14px;

        border-radius:18px;

        text-align:center;

        font-weight:700;
    }

    /* FORM */

    .form-section{
        padding:0 16px 24px;
    }

    .form-grid{
        background:#fff;

        border-radius:28px;

        padding:22px;

        display:flex;
        flex-direction:column;

        gap:24px;

        box-shadow:
        0 8px 24px rgba(0,0,0,.06);
    }

    /* IMAGE */

    .image-wrap{
        position:relative;

        width:100%;
        height: auto;
        max-width:180px;

        aspect-ratio:1/1;

        margin:auto;
    }

    .image-box{
        width:100%;
        height:100%;

        border-radius:24px;

        overflow:hidden;

        background:#f2f2f2;
    }

    .image-box img{
        width:100%;
        height:100%;

        object-fit:cover;
    }

    .upload-label{
        position:absolute;

        right:8px;
        bottom:8px;

        width:44px;
        height:44px;

        border-radius:50%;

        background:#16a34a;

        color:#fff;

        display:flex;
        align-items:center;
        justify-content:center;

        font-size:24px;

        cursor:pointer;

        box-shadow:
        0 6px 18px rgba(0,0,0,.15);
    }

    .hidden-file{
        display:none;
    }

    /* FORM INPUT */

    .input-panel label{
        display:block;

        font-size:15px;
        font-weight:700;

        margin:14px 0 8px;
    }

    .input-panel input,
    .input-panel textarea,
    .input-panel select{
        width:100%;

        border:none;

        border-radius:18px;

        background:#f5f5f5;

        padding:16px;

        font-size:16px;

        outline:none;

        transition:.2s;

        appearance:none;
        -webkit-appearance:none;
    }

    .input-panel textarea{
        resize:none;
        min-height:90px;
    }

    /* SELECT */

    .input-panel select{
        padding-right:48px;

        background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%23777' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");

        background-repeat:no-repeat;

        background-position:right 16px center;

        background-size:16px;
    }

    .input-panel input:focus,
    .input-panel textarea:focus,
    .input-panel select:focus{
        background:#fff;

        box-shadow:
        0 0 0 4px rgba(22,163,74,.12);
    }

    /* PRICE */

.price-row{
    display:flex;
    align-items:center;

    gap:10px;
}

.price-input-wrap{
    position:relative;

    flex:1;
}

.price-input-wrap input{
    width:100%;

    padding-right:55px;
}

.price-unit-text{
    position:absolute;

    right:16px;
    top:50%;

    transform:translateY(-50%);

    font-size:15px;
    font-weight:700;

    color:#666;

    pointer-events:none;
}

.price-row select{
    width:130px;

    flex-shrink:0;
}
    .price-row input{
        flex:1;
    }

    .price-row select{
        width:120px;
        flex-shrink:0;
    }

    .price-row span{
        white-space:nowrap;

        font-size:15px;
        font-weight:700;

        color:#555;
    }

    /* PROMO */

    .promo-pill{
        display:inline-flex;
        align-items:center;
        gap:8px;

        background:#f4f4f4;

        border-radius:999px;

        padding:12px 16px;

        font-size:14px;
        font-weight:700;
    }

    /* BUTTON */

    .button-row{
        margin-top:22px;

        display:flex;
        flex-direction:column;

        gap:12px;
    }

    .cancel-btn,
    .save-btn{
        width:100%;

        border:none;

        border-radius:18px;

        padding:16px;

        font-size:16px;
        font-weight:700;

        cursor:pointer;

        text-align:center;

        text-decoration:none;
    }

    .cancel-btn{
        background:#f3f4f6;
        color:#111;
    }

    .save-btn{
        background:#16a34a;
        color:#fff;
    }

    /* PRODUCT LIST */

    .product-list{
        padding:8px 16px 90px;
    }

    .product-list h2{
        text-align:center;

        font-size:24px;
        font-weight:800;

        margin-bottom:20px;
    }

    .products-grid{
        display:grid;

        grid-template-columns:
        repeat(2,1fr);

        gap:14px;
    }

    /* PRODUCT CARD */

    .product-card{
        background:#fff;

        border-radius:26px;

        padding:12px;

        text-decoration:none;

        color:#111;

        box-shadow:
        0 6px 18px rgba(0,0,0,.06);
    }

    .product-card img{
        width:100%;

        aspect-ratio:1/1;

        object-fit:cover;

        border-radius:18px;

        margin-bottom:12px;
    }

    .product-name{
        font-size:16px;
        font-weight:800;

        line-height:1.35;

        margin-bottom:4px;
    }

    .product-category{
        color:#777;

        font-size:13px;
        font-weight:600;

        margin-bottom:10px;
    }

    .product-price{
        font-size:16px;
        font-weight:800;
    }

    /* FOOTER */

    .footer{
        padding:0 20px 30px;
    }

    .footer-line{
        border-top:1px solid #ddd;

        padding-top:20px;

        text-align:center;

        color:#666;

        font-size:13px;

        line-height:1.7;
    }

    .footer small{
        display:block;
        margin-top:14px;
    }
}

</style>
</head>

<body>


<header class="header">

    <div class="logo">
    <img src="assets/images/logo_ok.png" alt="FreshFast">
    </div>
    <div class="profile-menu">

        <button class="profile-btn" id="profileBtn">
                    <?php if (!empty($shopImage)): ?>
            <img src="<?= e($shopImage) ?>?v=<?= time() ?>" alt="profile">
        <?php else: ?>
            <div style="
                width:100%;
                height:100%;
                background:#16a34a;
                color:white;
                display:flex;
                align-items:center;
                justify-content:center;
                font-size:20px;
                font-weight:700;
            ">
                                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M4 10L5.5 4H18.5L20 10" 
                            stroke="currentColor" 
                            stroke-width="1.7" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"/>

                        <path d="M5 10V18C5 18.5523 5.44772 19 6 19H18C18.5523 19 19 18.5523 19 18V10" 
                            stroke="currentColor" 
                            stroke-width="1.7"/>

                        <path d="M9 19V14H15V19" 
                            stroke="currentColor" 
                            stroke-width="1.7"/>

                        <path d="M3 10H21" 
                            stroke="currentColor" 
                            stroke-width="1.7"/>
                    </svg>
            </div>
        <?php endif; ?>
        </button>

        <div class="profile-dropdown" id="profileDropdown">

            <a href=" shop_profile.php">
                บัญชีของฉัน
            </a>

            <a href="logout.php">
                ออกจากระบบ
            </a>

        </div>

    </div>
</header>

<nav class="nav">
    <a href="shop_home.php">หน้าหลัก</a>
    <a href="shop_products.php" class="active">สินค้า</a>
    <a href="shop_orders.php">รับคำสั่งซื้อ</a>
    <a href="shop_sales_history.php">ประวัติ</a>
</nav>

<!-- <section class="banner-zone">
    <div class="banner-box">
        <img src="assets/images/saa.png" alt="โปรโมชั่น">
    </div>
</section> -->

<div class="page-title">
    <?= $product ? 'แก้ไขข้อมูลสินค้า' : 'เพิ่มข้อมูลสินค้า' ?>
</div>

<?php if ($success): ?>
    <div class="success">บันทึกข้อมูลสินค้าเรียบร้อยแล้ว</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<section class="form-section">
    <div class="form-grid">

        <div class="image-wrap">
            <div class="image-box">
                <img
                    id="productPreview"
                    src="<?= !empty($productImage) ? e($productImage) : '' ?>"
                    style="<?= empty($productImage) ? 'display:none;' : '' ?>"
                    alt="product image"
                />
            </div>

            <label for="product_image" class="upload-label">✎</label>
            <input class="hidden-file" type="file" id="product_image" name="product_image" accept="image/*">
        </div>

        <div class="input-panel">
            <input type="hidden" name="product_id" value="<?= e($product['product_id'] ?? 0) ?>">

            <label>ชื่อสินค้า</label>
            <input
                type="text"
                name="product_name"
                value="<?= e($product['product_name'] ?? '') ?>"
                placeholder="กรอกชื่อสินค้า"
                required
            >

            <label>หมวดหมู่สินค้า</label>
            <select name="category_id" required>
                <option value="">เลือกหมวดหมู่สินค้า</option>
                <?php while($cat = $categories->fetch_assoc()): ?>
                    <option value="<?= e($cat['category_id']) ?>"
                        <?= isset($product['category_id']) && (int)$product['category_id'] === (int)$cat['category_id'] ? 'selected' : '' ?>>
                        <?= e($cat['category_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>คำบรรยายสินค้า</label>
            <textarea name="product_description" placeholder="กรอกคำบรรยายสินค้า"><?= e($product['product_description'] ?? '') ?></textarea>

            <label>ราคา</label>
                <div class="price-row">

                    <div class="price-input-wrap">
                        <input
                            type="number"
                            step="1"
                            min="0"
                            name="price"
                            value="<?= e($product['price'] ?? '') ?>"
                            placeholder="ราคา"
                            required
                        >

                        <span class="price-unit-text">บาท</span>
                    </div>

                    <select name="unit">
                        <?php
                        $unit = $product['unit'] ?? 'kg';
                        $units = [
                            'kg' => 'กิโลกรัม',
                            'piece' => 'ชิ้น',
                            'pack' => 'แพ็ค',
                            'bottle' => 'ขวด',
                            'bundle' => 'มัด',
                            'can' => 'กระป๋อง'
                        ];
                        foreach ($units as $key => $label):
                        ?>
                            <option value="<?= e($key) ?>" <?= $unit === $key ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

            </div>


            <label>โปรโมชั่น</label>
            <span class="promo-pill">🏪 กำลังลดแรง!</span>

            <div class="button-row">
                <a href="shop_products.php" class="cancel-btn">ยกเลิก</a>
                <button type="submit" name="save_product" class="save-btn">บันทึก</button>
            </div>
        </div>

    </div>
</section>

</form>

<section class="product-list">
    <h2>สินค้าของร้าน</h2>

    <div class="products-grid">
        <?php if ($myProducts->num_rows > 0): ?>
            <?php while($p = $myProducts->fetch_assoc()): ?>
                <?php
                if (!empty($p['product_image']) && $p['product_image'] !== '0' && file_exists($p['product_image'])) {
                    $img = $p['product_image'];
                }
                ?>
                <div class="product-card" onclick="window.location='shop_products.php?edit=<?= $p['product_id'] ?>'">
                <?php if (!empty($p['product_image']) && file_exists($p['product_image'])): ?>

                        <img src="<?= e($p['product_image']) ?>?v=<?= time() ?>" alt="">

                    <?php else: ?>

                        <div class="product-placeholder">
                            
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M3 7H21" stroke-linecap="round"/>
                                <path d="M6 7L7 4H17L18 7" stroke-linecap="round" stroke-linejoin="round"/>
                                <rect x="4" y="7" width="16" height="13" rx="2"/>
                                <path d="M9 11C9 11 10 13 12 13C14 13 15 11 15 11" stroke-linecap="round"/>
                            </svg>
                        </div>

                    <?php endif; ?>

<div class="product-info">

    <div class="product-name">
        <?= e($p['product_name']) ?>
    </div>

    <div class="product-category">
        <?= e($p['category_name'] ?? '-') ?>
    </div>

    <div class="product-price">
        <?= number_format((float)$p['price'], 2) ?> ฿
    </div>


    <div class="product-actions">

        <?php if($p['product_status']=="active"): ?>

    <a class="action-btn btn-toggle"
    href="shop_products.php?disable=<?= $p['product_id'] ?>"
    onclick="event.stopPropagation(); return confirm('ปิดการขายสินค้านี้ใช่หรือไม่')">
    สินค้าหมด
    </a>

        <?php else: ?>

        <a class="action-btn btn-toggle closed"
        href="shop_products.php?enable=<?= $p['product_id'] ?>"
        onclick="event.stopPropagation(); return confirm('เปิดขายสินค้านี้ใช่หรือไม่')">
        มีสินค้า
        </a>

        <?php endif; ?>

<!-- 
        <a class="action-btn btn-delete"
        href="shop_products.php?delete=<?= $p['product_id'] ?>"
        onclick="return confirm('ลบสินค้านี้ใช่หรือไม่')">
        ลบ
        </a> -->

    </div>

</div> <!-- ปิด product-info -->

</div> <!-- เพิ่มอันนี้ ปิด product-card -->



            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center;font-weight:700;grid-column:1/-1;">ยังไม่มีสินค้าในร้าน</p>
        <?php endif; ?>
    </div>
</section>

<footer class="footer">
    <div class="footer-line">
        คำถามที่พบบ่อย ติดต่อเรา ประกาศความเป็นส่วนตัว สำหรับลูกค้า นโยบายการใช้คุกกี้
        การตั้งค่าคุกกี้ ข้อกำหนดและเงื่อนไขนโยบายการคุ้มครองข้อมูลส่วนบุคคล ลงทะเบียน
        <small>ลิขสิทธิ์ © 2025-2026 FreshFast สงวนลิขสิทธิ์.</small>
    </div>
</footer>
<script>
const profileBtn = document.getElementById("profileBtn");
const profileDropdown = document.getElementById("profileDropdown");

profileBtn?.addEventListener("click", (e) => {
    e.stopPropagation();
    profileDropdown.classList.toggle("show");
});

document.addEventListener("click", () => {
    profileDropdown?.classList.remove("show");
});

profileDropdown?.addEventListener("click", (e) => {
    e.stopPropagation();
});
</script>
<script>
const input = document.getElementById("product_image");
const preview = document.getElementById("productPreview");

input.addEventListener("change", function () {
    const file = this.files[0];

    if (file) {
        const url = URL.createObjectURL(file);

        preview.src = url;
        preview.style.display = "block";
    }
});
</script>

</body>
</html>