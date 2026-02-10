<?php
/**
 * File debug: Kiểm tra kết nối API cho biểu mẫu
 * Truy cập từ: http://your-domain.com/public/api/debug_form_submission.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

session_start();

$debug_info = [
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
        'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'N/A',
    ],
    'session_info' => [
        'session_id' => session_id() ?? 'N/A',
        'user_id' => $_SESSION['user_id'] ?? 'NOT SET - Bạn chưa đăng nhập',
        'role' => $_SESSION['role'] ?? 'N/A',
        'fullname' => $_SESSION['fullname'] ?? 'N/A',
        'session_status' => session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE',
    ],
    'request_info' => [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'N/A',
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
        'http_host' => $_SERVER['HTTP_HOST'] ?? 'N/A',
    ],
    'database_info' => [
        'status' => 'Đang kiểm tra...',
    ],
];

// Kiểm tra kết nối database
try {
    require_once __DIR__ . '/../../config/db.php';
    
    // Test database connection
    $result = $pdo->query("SELECT 1");
    $debug_info['database_info'] = [
        'status' => 'KẾT NỐI THÀNH CÔNG',
        'message' => 'Database có thể được truy cập từ API'
    ];
} catch (Exception $e) {
    $debug_info['database_info'] = [
        'status' => 'LỖI KẾT NỐI',
        'error' => $e->getMessage()
    ];
}

// Kiểm tra các file include
$debug_info['files_check'] = [
    'db.php' => file_exists(__DIR__ . '/../../config/db.php') ? 'CÓ' : 'KHÔNG',
    'audit_helper.php' => file_exists(__DIR__ . '/../../includes/audit_helper.php') ? 'CÓ' : 'KHÔNG',
];

// Kiểm tra các table cần thiết
if (isset($pdo)) {
    try {
        $tables_check = [
            'forms' => false,
            'form_questions' => false,
            'question_options' => false,
            'users' => false,
        ];
        
        foreach (array_keys($tables_check) as $table) {
            $result = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '$table'");
            $tables_check[$table] = $result->rowCount() > 0;
        }
        $debug_info['tables_check'] = $tables_check;
    } catch (Exception $e) {
        $debug_info['tables_check'] = ['error' => $e->getMessage()];
    }
}

// Nếu là POST request, kiểm tra dữ liệu được gửi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents('php://input');
    $json_data = json_decode($raw_input, true);
    
    $debug_info['post_data'] = [
        'raw_length' => strlen($raw_input),
        'json_valid' => json_last_error() === JSON_ERROR_NONE,
        'json_error' => json_last_error_msg(),
        'data_keys' => $json_data ? array_keys($json_data) : 'Invalid JSON',
    ];
}

echo json_encode($debug_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
