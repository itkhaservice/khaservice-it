<?php
if (isset($_GET['id'])) {
    $log_id = $_GET['id'];

    try {
        $check_stmt = $pdo->prepare("SELECT id FROM maintenance_logs WHERE id = ?");
        $check_stmt->execute([$log_id]);
        if ($check_stmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM maintenance_logs WHERE id = ?");
            $stmt->execute([$log_id]);
            set_message('success', 'Nhật ký bảo trì đã được xóa thành công!');
        } else {
            set_message('error', 'Nhật ký bảo trì không tìm thấy để xóa.');
        }
    } catch (PDOException $e) {
        set_message('error', 'Lỗi khi xóa nhật ký bảo trì: ' . $e->getMessage());
    }
} else {
    set_message('error', 'Không có ID nhật ký bảo trì được cung cấp.');
}

header("Location: index.php?page=maintenance/history");
exit;
