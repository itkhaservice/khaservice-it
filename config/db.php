<?php
// File: config/db.php
// Cấu hình CSDL đa môi trường (Local & Hosting)

// Thông tin mặc định cho Hosting (InfinityFree)
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