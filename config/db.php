<?php
// File: config/db.php
// Cấu hình CSDL cho Hosting 123Host

$host = 'localhost'; // Mặc định của hosting
$port = '3306';
$dbname = 'kblbccwr_khaservice_it';
$username = 'kblbccwr_khaservice_it';
$password = 'jRM7k7HTzH6zjLDV88PZ';
$charset = 'utf8mb4';

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
    die("Lỗi kết nối đến cơ sở dữ liệu. Vui lòng kiểm tra lại thông tin cấu hình.");
}
?>