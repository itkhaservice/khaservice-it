<?php
// File: config/db.php
// Mục đích: Thiết lập kết nối đến Cơ sở dữ liệu (CSDL)

$host = '127.0.0.1';     // Dùng IP để tốc độ nhanh hơn trên local
$port = '3307';            // Cổng mặc định của bộ đóng gói
$dbname = 'khaservice_it';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";

// Tùy chọn cho PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Báo lỗi khi có lỗi
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Trả về mảng kết hợp
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Tắt chế độ mô phỏng prepared statements
];

try {
    // Tạo đối tượng PDO để kết nối
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Nếu kết nối thất bại, hiển thị lỗi và dừng chương trình
    // TRONG MÔI TRƯỢNG THỰC TẾ: Không nên hiển thị chi tiết lỗi cho người dùng cuối
    // Thay vào đó, ghi log lỗi và hiển thị một thông báo chung.
    error_log("Lỗi kết nối CSDL: " . $e->getMessage()); // Ghi lỗi vào log của server
    die("Lỗi kết nối đến cơ sở dữ liệu. Vui lòng thử lại sau.");
}
?>
