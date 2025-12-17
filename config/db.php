<?php
// File: config/db.php
// Mục đích: Thiết lập kết nối đến Cơ sở dữ liệu (CSDL)

$host = 'localhost';        // Host của MySQL Server (thường là localhost)
$dbname = 'khaservice_it';  // Tên CSDL đã tạo
$username = 'root';         // Tên người dùng của MySQL
$password = '';             // Mật khẩu của người dùng MySQL (để trống nếu dùng XAMPP mặc định)
$charset = 'utf8mb4';       // Bảng mã ký tự

// Tạo chuỗi Data Source Name (DSN)
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

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
