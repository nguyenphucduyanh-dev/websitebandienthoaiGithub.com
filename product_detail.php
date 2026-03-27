<?php
require 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin sản phẩm và tên danh mục
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, (p.gia_nhap * (1 + p.ty_le_loi_nhuan / 100)) AS gia_ban
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die("Sản phẩm không tồn tại!");
}
?>

<div class="product-detail">
    <h1><?= htmlspecialchars($product['name']) ?></h1>
    <div class="content">
        <img src="uploads/<?= htmlspecialchars($product['image']) ?>" width="300">
        <div class="info">
            <p><strong>Loại:</strong> <?= htmlspecialchars($product['category_name']) ?></p>
            <p><strong>Giá bán:</strong> <span style="color:red"><?= number_format($product['gia_ban'], 0, ',', '.') ?> VNĐ</span></p>
            <p><strong>Trình trạng:</strong> <?= $product['so_luong_ton'] > 0 ? 'Còn hàng' : 'Hết hàng' ?></p>
            <hr>
            <h4>Thông số kỹ thuật:</h4>
            <div><?= nl2br(htmlspecialchars($product['description'])) ?></div>
            
            <button onclick="addToCart(<?= $product['id'] ?>)">Thêm vào giỏ hàng</button>
        </div>
    </div>
</div>
