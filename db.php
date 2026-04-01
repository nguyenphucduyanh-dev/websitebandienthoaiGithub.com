<?php
// config/db.php  —  Kết nối CSDL duy nhất toàn dự án
// Dùng PDO để hỗ trợ Prepared Statements, tránh SQL Injection

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'phone_shop');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Không lộ chi tiết lỗi ra ngoài môi trường production
            error_log('[DB Error] ' . $e->getMessage());
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Lỗi kết nối CSDL.']));
        }
    }
    return $pdo;
}
