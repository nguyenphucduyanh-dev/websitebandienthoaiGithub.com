<?php
require 'db.php';

// --- PHẦN 1: XỬ LÝ TRA CỨU TỒN KHO THEO THỜI ĐIỂM ---
$cat_id = $_GET['cat_id'] ?? '';
$point_time = $_GET['point_time'] ?? date('Y-m-d H:i');
$inventory_data = [];

if ($cat_id && $point_time) {
    $sql_inv = "SELECT p.name, 
        (SELECT IFNULL(SUM(id.quantity), 0) FROM import_details id 
         JOIN import_vouchers iv ON id.voucher_id = iv.id 
         WHERE id.product_id = p.id AND iv.created_at <= :t1 AND iv.status = 'completed') as total_in,
        (SELECT IFNULL(SUM(od.quantity), 0) FROM order_details od 
         JOIN orders o ON od.order_id = o.id 
         WHERE od.product_id = p.id AND o.order_date <= :t2 AND o.status = 'Đã giao thành công') as total_out
        FROM products p WHERE p.category_id = :cat AND p.is_deleted = 0";
    
    $stmt = $pdo->prepare($sql_inv);
    $stmt->execute(['t1' => $point_time, 't2' => $point_time, 'cat' => $cat_id]);
    $inventory_data = $stmt->fetchAll();
}

// --- PHẦN 2: BÁO CÁO NHẬP - XUẤT TRONG KHOẢNG THỜI GIAN ---
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-d');
$report_stmt = $pdo->prepare("SELECT p.name,
    (SELECT SUM(id.quantity) FROM import_details id JOIN import_vouchers iv ON id.voucher_id = iv.id 
     WHERE id.product_id = p.id AND iv.created_at BETWEEN :s1 AND :e1 AND iv.status = 'completed') as qty_in,
    (SELECT SUM(od.quantity) FROM order_details od JOIN orders o ON od.order_id = o.id 
     WHERE od.product_id = p.id AND o.order_date BETWEEN :s2 AND :e2 AND o.status = 'Đã giao thành công') as qty_out
    FROM products p WHERE p.is_deleted = 0");
$report_stmt->execute(['s1' => $start, 'e1' => $end, 's2' => $start, 'e2' => $end]);
$report_data = $report_stmt->fetchAll();

// --- PHẦN 3: CẢNH BÁO HẾT HÀNG ---
$threshold = $_GET['threshold'] ?? 5;
$alert_stmt = $pdo->prepare("SELECT name, so_luong_ton FROM products WHERE so_luong_ton < ? AND is_deleted = 0");
$alert_stmt->execute([$threshold]);
$alerts = $alert_stmt->fetchAll();
?>

<style>
    .alert-red { color: white; background-color: #d9534f; font-weight: bold; padding: 2px 5px; border-radius: 3px; }
    section { margin-bottom: 40px; border: 1px solid #ddd; padding: 20px; }
</style>

<h2>Hệ thống Báo cáo & Thống kê</h2>

<section>
    <h3>1. Tra cứu tồn kho tại thời điểm</h3>
    <form method="GET">
        Loại: <select name="cat_id">
            <?php 
            $cats = $pdo->query("SELECT * FROM categories")->fetchAll();
            foreach($cats as $c) echo "<option value='{$c['id']}'>{$c['name']}</option>";
            ?>
        </select>
        Thời điểm: <input type="datetime-local" name="point_time" value="<?= str_replace(' ', 'T', $point_time) ?>">
        <button type="submit">Kiểm tra</button>
    </form>
    <table border="1" width="100%">
        <tr><th>Tên sản phẩm</th><th>Tổng nhập</th><th>Tổng xuất</th><th>Tồn tại mốc đó</th></tr>
        <?php foreach($inventory_data as $row): ?>
        <tr>
            <td><?= $row['name'] ?></td>
            <td><?= $row['total_in'] ?></td>
            <td><?= $row['total_out'] ?></td>
            <td><strong><?= $row['total_in'] - $row['total_out'] ?></strong></td>
        </tr>
        <?php endforeach; ?>
    </table>
</section>

<section>
    <h3>2. Báo cáo Nhập - Xuất (Từ <?= $start ?> đến <?= $end ?>)</h3>
    <form method="GET">
        Từ: <input type="date" name="start" value="<?= $start ?>">
        Đến: <input type="date" name="end" value="<?= $end ?>">
        <button type="submit">Xem báo cáo</button>
    </form>
    <table border="1" width="100%">
        <tr><th>Tên sản phẩm</th><th>Số lượng nhập thêm</th><th>Số lượng bán ra</th></tr>
        <?php foreach($report_data as $row): ?>
        <tr>
            <td><?= $row['name'] ?></td>
            <td><?= $row['qty_in'] ?? 0 ?></td>
            <td><?= $row['qty_out'] ?? 0 ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</section>

<section>
    <h3>3. Cảnh báo sản phẩm sắp hết hàng</h3>
    <form method="GET">
        Ngưỡng cảnh báo (X): <input type="number" name="threshold" value="<?= $threshold ?>">
        <button type="submit">Cập nhật ngưỡng</button>
    </form>
    <ul>
        <?php foreach($alerts as $a): ?>
        <li><?= $a['name'] ?> - Tồn kho: <span class="alert-red"><?= $a['so_luong_ton'] ?></span></li>
        <?php endforeach; ?>
    </ul>
</section>
