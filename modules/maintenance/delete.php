<?php
if (isset($_GET['id'])) {
    $log_id = $_GET['id'];

    $check_stmt = $pdo->prepare("SELECT id FROM maintenance_logs WHERE id = ?");
    $check_stmt->execute([$log_id]);
    if ($check_stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM maintenance_logs WHERE id = ?");
        $stmt->execute([$log_id]);
        $_SESSION['message'] = 'Nhật ký bảo trì đã được xóa thành công!'; // For future use
    } else {
        $_SESSION['error'] = 'Nhật ký bảo trì không tìm thấy để xóa.'; // For future use
    }
}

header("Location: index.php?page=maintenance/history");
exit;
