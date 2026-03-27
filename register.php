<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_POST['username'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $fullname = $_POST['fullname'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, phone, address) VALUES (?, ?, ?, ?, ?)");
    try {
        $stmt->execute([$user, $pass, $fullname, $phone, $address]);
        header("Location: login.php?msg=success");
    } catch (Exception $e) {
        $error = "Tên đăng nhập đã tồn tại!";
    }
}
?>
<form method="POST">
    <input type="text" name="username" placeholder="Tên đăng nhập" required>
    <input type="password" name="password" placeholder="Mật khẩu" required>
    <input type="text" name="fullname" placeholder="Họ và tên" required>
    <input type="text" name="phone" placeholder="Số điện thoại" required>
    <textarea name="address" placeholder="Địa chỉ giao hàng"></textarea>
    <button type="submit">Đăng ký</button>
</form>
