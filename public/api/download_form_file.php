<?php
// public/api/download_form_file.php
// API trung chuyển tải file an toàn sau khi kiểm tra quyền

session_start();
require_once '../../config/db.php';

// Kiểm tra đăng nhập (Chỉ cho phép nhân viên/admin xem)
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die("Bạn không có quyền truy cập file này.");
}

$file_path = $_GET['file'] ?? '';

// Bảo mật: Chỉ cho phép đọc trong thư mục uploads/forms/
if (strpos($file_path, 'uploads/forms/') !== 0) {
    http_response_code(400);
    die("Đường dẫn file không hợp lệ.");
}

$full_path = '../../' . $file_path;

if (file_exists($full_path)) {
    $mime = mime_content_type($full_path);
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . basename($full_path) . '"');
    readfile($full_path);
    exit;
} else {
    http_response_code(404);
    die("File không tồn tại.");
}
