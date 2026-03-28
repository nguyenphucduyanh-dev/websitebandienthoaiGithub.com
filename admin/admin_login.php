<?php
session_start();
require '../db.php'; // Đường dẫn đến file kết nối database

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND is_active = 1");
    $stmt->execute([$user]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($pass, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_user'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
        header("Location: index.php"); // Vào trang dashboard admin
        exit();
    } else {
        $error = "Tài khoản không chính xác hoặc đã bị khóa!";
    }
}
?>
<form method="POST">
    <h2>Hệ thống Quản trị</h2>
    <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Đăng nhập</button>
</form>
