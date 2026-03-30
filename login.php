<?php
/**
 * login.php - Đăng nhập đơn giản
 * Tác giả: nguyenphucduyanh-dev
 */

session_start();
require_once __DIR__ . '/config/db.php';

$error    = '';
$redirect = $_GET['redirect'] ?? 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ email và mật khẩu.';
    } else {
        $pdo  = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND is_active = 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];

            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Email hoặc mật khẩu không đúng.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Website Bán Điện Thoại</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            background: linear-gradient(135deg, #1a73e8, #4fc3f7);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 20px;
        }
        .login-box {
            background: #fff; border-radius: 12px; padding: 40px; width: 100%; max-width: 420px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }
        .login-box h1 { text-align: center; margin-bottom: 8px; font-size: 24px; }
        .login-box .subtitle { text-align: center; color: #888; margin-bottom: 24px; font-size: 14px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px; }
        .form-group input {
            width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;
            font-size: 15px; transition: border-color 0.2s;
        }
        .form-group input:focus { border-color: #1a73e8; outline: none; }
        .error-msg { background: #ffebee; color: #c62828; padding: 10px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
        .btn-login {
            display: block; width: 100%; padding: 14px; background: #1a73e8; color: #fff; border: none;
            border-radius: 8px; font-size: 16px; font-weight: 700; cursor: pointer;
            transition: background 0.2s;
        }
        .btn-login:hover { background: #1557b0; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>📱 Đăng nhập</h1>
        <p class="subtitle">Website Bán Điện Thoại</p>

        <?php if ($error): ?>
            <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" placeholder="VD: duyanh@gmail.com" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" name="password" id="password" placeholder="Nhập mật khẩu" required>
            </div>
            <button type="submit" class="btn-login">Đăng nhập</button>
        </form>
    </div>
</body>
</html>
