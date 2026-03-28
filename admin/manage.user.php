<?php
require 'auth.php'; // Bảo vệ trang
require '../db.php';

// 1. Xử lý Thêm nhân viên mới
if (isset($_POST['add_staff'])) {
    $u = $_POST['username'];
    $p = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $f = $_POST['fullname'];
    $stmt = $pdo->prepare("INSERT INTO admins (username, password, fullname, role) VALUES (?, ?, ?, 'staff')");
    $stmt->execute([$u, $p, $f]);
}

// 2. Xử lý Khóa/Mở khóa tài khoản (áp dụng cho khách hàng)
if (isset($_GET['toggle_user'])) {
    $uid = $_GET['toggle_user'];
    $status = $_GET['status'];
    $new_status = ($status == 1) ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmt->execute([$new_status, $uid]);
}

// 3. Xử lý Reset mật khẩu khách hàng
if (isset($_POST['reset_pwd'])) {
    $uid = $_POST['user_id'];
    $new_p = password_hash("123456", PASSWORD_DEFAULT); // Reset về mặc định
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$new_p, $uid]);
    $msg = "Đã reset mật khẩu về: 123456";
}

// Lấy danh sách khách hàng
$customers = $pdo->query("SELECT * FROM users")->fetchAll();
?>

<h3>Thêm nhân viên mới</h3>
<form method="POST">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Mật khẩu" required>
    <input type="text" name="fullname" placeholder="Họ tên">
    <button type="submit" name="add_staff">Thêm nhân viên</button>
</form>

<hr>
<h3>Danh sách Khách hàng</h3>
<table border="1" width="100%">
    <tr>
        <th>Username</th>
        <th>Họ tên</th>
        <th>Trạng thái</th>
        <th>Thao tác</th>
    </tr>
    <?php foreach ($customers as $c): ?>
    <tr>
        <td><?= $c['username'] ?></td>
        <td><?= $c['fullname'] ?></td>
        <td><?= $c['is_active'] == 1 ? 'Đang hoạt động' : '<span style="color:red">Đang bị khóa</span>' ?></td>
        <td>
            <a href="?toggle_user=<?= $c['id'] ?>&status=<?= $c['is_active'] ?>">
                <?= $c['is_active'] == 1 ? 'Khóa' : 'Mở khóa' ?>
            </a> |
            <form method="POST" style="display:inline">
                <input type="hidden" name="user_id" value="<?= $c['id'] ?>">
                <button type="submit" name="reset_pwd" onclick="return confirm('Reset về 123456?')">Reset Pass</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
