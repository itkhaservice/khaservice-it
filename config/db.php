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
$server_addr = $_SERVER['SERVER_ADDR'] ?? '';

function is_private_ip($ip) {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) return false;
    $long = ip2long($ip);
    $private_ranges = [
        [ip2long('10.0.0.0'), ip2long('10.255.255.255')],
        [ip2long('172.16.0.0'), ip2long('172.31.255.255')],
        [ip2long('192.168.0.0'), ip2long('192.168.255.255')],
        [ip2long('127.0.0.0'), ip2long('127.255.255.255')]
    ];
    foreach ($private_ranges as $range) {
        if ($long >= $range[0] && $long <= $range[1]) return true;
    }
    return false;
}

// Treat localhost or private LAN IPs as local development environment
if ($server_name == 'localhost' || $server_name == '127.0.0.1' || is_private_ip($server_name) || ($server_addr && is_private_ip($server_addr))) {
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