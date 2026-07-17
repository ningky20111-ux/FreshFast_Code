<?php
$conn = new mysqli("localhost", "root", "", "freshfast");
$conn->set_charset("utf8mb4");

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit  = isset($_GET['limit'])  ? (int)$_GET['limit']  : 3;

$sql = "SELECT shop_id, shop_name, shop_type, stall_number, shop_image
        FROM shops
        ORDER BY shop_id
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

while ($shop = $result->fetch_assoc()) {
  $img = $shop['shop_image'] ?? '';
  $src = ($img && file_exists(__DIR__ . '/' . $img)) ? $img : 'assets/images/sac.png';
  ?>
  <article class="shop-card">
    <div class="shop-thumb">
      <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($shop['shop_name']) ?>">
    </div>

    <div class="shop-info">
      <div class="shop-name"><?= htmlspecialchars($shop['shop_name']) ?></div>
      <div class="shop-line"></div>

      <div class="shop-meta"> เปิดอยู่ | <?= htmlspecialchars($shop['shop_type']) ?></div>
      <div class="shop-meta muted"> แผง <?= htmlspecialchars($shop['stall_number']) ?></div>

      <a class="shop-btn" href="shop.php?id=<?= (int)$shop['shop_id'] ?>">ดูสินค้า</a>
    </div>
  </article>
  <?php
}

$conn->close();
