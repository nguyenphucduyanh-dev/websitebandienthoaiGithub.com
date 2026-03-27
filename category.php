<?php
require 'db.php';

// 1. Lấy tham số từ URL
$cat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 2. Đếm tổng số sản phẩm để tính số trang
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
$count_stmt->execute([$cat_id]);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

// 3. Lấy danh sách sản phẩm (Kèm tính toán giá bán trực tiếp trong SQL)
$stmt = $pdo->prepare("
    SELECT *, (gia_nhap * (1 + ty_le_loi_nhuan / 100)) AS gia_ban 
    FROM products 
    WHERE category_id = ? 
    LIMIT ? OFFSET ?
");
// Lưu ý: PDO LIMIT/OFFSET cần truyền tham số kiểu INT nếu emulates_prepares = false
$stmt->bindValue(1, $cat_id, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();
?>

<div class="product-grid">
    <?php foreach ($products as $row): ?>
        <div class="product-item">
            <img src="uploads/<?= htmlspecialchars($row['image']) ?>" width="150">
            <h3><?= htmlspecialchars($row['name']) ?></h3>
            <p>Giá: <?= number_format($row['gia_ban'], 0, ',', '.') ?> VNĐ</p>
            <a href="product_detail.php?id=<?= $row['id'] ?>">Xem chi tiết</a>
        </div>
    <?php endforeach; ?>
</div>

<div class="pagination">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?id=<?= $cat_id ?>&page=<?= $i ?>" class="<?= $page == $i ? 'active' : '' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>
</div>
