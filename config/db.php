<?php
// File: config/db.php
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình CSDL đa môi trường (Local & Hosting)
$host = 'sql100.infinityfree.com';
$port = '3306';
$dbname = 'if0_40738827_khaservice_it';
$username = 'if0_40738827';
$password = 'wun7QKIEJDM20FH';
$charset = 'utf8mb4';

// Tự động phát hiện môi trường Localhost (XAMPP)
$server_name = $_SERVER['SERVER_NAME'] ?? 'localhost';
if ($server_name == 'localhost' || $server_name == '127.0.0.1') {
    $host = 'localhost';
    $dbname = 'khaservice_it';
    $username = 'root';
    $password = '';
}

// Khóa mã hóa bí mật (Dùng cho module Links/Accounts)
define('ENCRYPTION_KEY', 'khaservice_it_secret_key_2024_@#$');

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    error_log("Lỗi kết nối CSDL: " . $e->getMessage());
    die("Lỗi kết nối đến cơ sở dữ liệu. Vui lòng kiểm tra lại cấu hình hoặc liên hệ quản trị viên.");
}

/**
 * Hàm mã hóa dữ liệu (AES-256-CBC)
 */
function encrypt_data($data) {
    $key = hash('sha256', ENCRYPTION_KEY);
    $iv = substr(hash('sha256', 'iv_secret_123'), 0, 16);
    $output = openssl_encrypt($data, "AES-256-CBC", $key, 0, $iv);
    return base64_encode($output);
}

/**
 * Hàm giải mã dữ liệu
 */
function decrypt_data($data) {
    $key = hash('sha256', ENCRYPTION_KEY);
    $iv = substr(hash('sha256', 'iv_secret_123'), 0, 16);
    return openssl_decrypt(base64_decode($data), "AES-256-CBC", $key, 0, $iv);
}
?>