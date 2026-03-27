<?php
require 'db.php';

$name_filter = $_GET['name'] ?? '';
$cat_filter = $_GET['cat'] ?? '';

// Truy vấn lấy sản phẩm chưa bị xóa hẳn
$sql = "SELECT p.*, c.name as cat_name FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.is_deleted = 0";

if ($name_filter) $sql .= " AND p.name LIKE :name";
if ($cat_filter) $sql .= " AND p.category_id = :cat";

$stmt = $pdo->prepare($sql);
if ($name_filter) $stmt->bindValue(':name', "%$name_filter%");
if ($cat_filter) $stmt->bindValue(':cat', $cat_filter);
$stmt->execute();
$products = $stmt->fetchAll();
?>

<h2>Quản lý sản phẩm</h2>
<form method="GET" style="margin-bottom: 20px;">
    <input type="text" name="name" placeholder="Tìm tên..." value="<?= htmlspecialchars($name_filter) ?>">
    <select name="cat">
        <option value="">-- Tất cả loại --</option>
        </select>
    <button type="submit">Lọc</button>
</form>

<table border="1" width="100%">
    <tr>
        <th>Hình</th>
        <th>Tên SP</th>
        <th>Lợi nhuận (%)</th>
        <th>Trạng thái</th>
        <th>Thao tác</th>
    </tr>
    <?php foreach ($products as $p): ?>
    <tr>
        <td><img src="uploads/<?= $p['image'] ?>" width="50"></td>
        <td><?= htmlspecialchars($p['name']) ?></td>
        <td><?= $p['ty_le_loi_nhuan'] ?>%</td>
        <td><?= $p['status'] == 1 ? 'Hiển thị' : 'Ẩn' ?></td>
        <td>
            <a href="product_edit.php?id=<?= $p['id'] ?>">Sửa</a> | 
            <a href="product_delete.php?id=<?= $p['id'] ?>" onclick="return confirm('Xác nhận xóa?')">Xóa</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
