<?php
require 'db.php';
$id = $_GET['id'];

// 1. Lấy thông tin đơn hàng và thông tin khách hàng (JOIN users)
$stmt = $pdo->prepare("SELECT o.*, u.fullname, u.phone, u.email 
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       WHERE o.id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

// 2. Lấy danh sách sản phẩm trong đơn hàng
$details_stmt = $pdo->prepare("SELECT od.*, p.name, p.image 
                               FROM order_details od 
                               JOIN products p ON od.product_id = p.id 
                               WHERE od.order_id = ?");
$details_stmt->execute([$id]);
$items = $details_stmt->fetchAll();
?>

<h3>Chi tiết đơn hàng #<?= $order['id'] ?></h3>
<div style="display: flex; gap: 50px;">
    <div class="customer-info">
        <h4>Thông tin khách hàng</h4>
        <p>Họ tên: <?= htmlspecialchars($order['fullname']) ?></p>
        <p>SĐT: <?= $order['phone'] ?></p>
        <p>Địa chỉ nhận hàng: <?= htmlspecialchars($order['shipping_address']) ?></p>
    </div>
    <div class="order-info">
        <h4>Thông tin đơn hàng</h4>
        <p>Ngày đặt: <?= $order['order_date'] ?></p>
        <p>Phương thức: <?= $order['payment_method'] ?></p>
        <p>Trạng thái: <strong><?= $order['status'] ?></strong></p>
    </div>
</div>

<table border="1" width="100%">
    <thead>
        <tr>
            <th>Hình ảnh</th>
            <th>Tên sản phẩm</th>
            <th>Số lượng</th>
            <th>Giá lúc mua</th>
            <th>Thành tiền</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
            <td><img src="uploads/<?= $item['image'] ?>" width="50"></td>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td><?= $item['quantity'] ?></td>
            <td><?= number_format($item['price_at_purchase']) ?>đ</td>
            <td><?= number_format($item['price_at_purchase'] * $item['quantity']) ?>đ</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <th colspan="4" align="right">TỔNG CỘNG:</th>
            <th><?= number_format($order['total_price']) ?>đ</th>
        </tr>
    </tfoot>
</table>
<br>
<a href="admin_orders.php"> Quay lại danh sách</a>
