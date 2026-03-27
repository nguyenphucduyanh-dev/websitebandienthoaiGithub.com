<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) header("Location: login.php");

$user_id = $_SESSION['user']['id'];
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>

<h2>Lịch sử mua hàng của bạn</h2>
<table border="1" width="100%">
    <tr>
        <th>Mã đơn</th>
        <th>Ngày đặt</th>
        <th>Tổng tiền</th>
        <th>Phương thức</th>
        <th>Trạng thái</th>
    </tr>
    <?php foreach ($orders as $o): ?>
    <tr>
        <td>#<?= $o['id'] ?></td>
        <td><?= date("d/m/Y H:i", strtotime($o['order_date'])) ?></td>
        <td><?= number_format($o['total_price']) ?>đ</td>
        <td><?= $o['payment_method'] ?></td>
        <td><?= $o['status'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>
