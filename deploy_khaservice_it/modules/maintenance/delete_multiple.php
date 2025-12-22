<?php
// modules/maintenance/delete_multiple.php

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'it') {
    set_message('error', 'Bạn không có quyền thực hiện thao tác này.');
    header("Location: index.php?page=maintenance/history");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = $_POST['ids'];
    $deleted_count = 0;

    try {
        $pdo->beginTransaction();
        
        $stmt_del_log = $pdo->prepare("UPDATE maintenance_logs SET deleted_at = NOW() WHERE id = ?");

        foreach ($ids as $id) {
            $stmt_del_log->execute([$id]);
            $deleted_count += $stmt_del_log->rowCount();
        }

        $pdo->commit();

        if ($deleted_count > 0) {
            set_message('success', "Đã chuyển thành công $deleted_count phiếu công tác vào thùng rác.");
        } else {
            set_message('warning', 'Không có phiếu nào được xử lý.');
        }

    } catch (PDOException $e) {
        $pdo->rollBack();
        set_message('error', 'Lỗi khi xử lý dữ liệu: ' . $e->getMessage());
    }
} else {
    set_message('error', 'Yêu cầu không hợp lệ.');
}

header("Location: index.php?page=maintenance/history");
exit;
?>
