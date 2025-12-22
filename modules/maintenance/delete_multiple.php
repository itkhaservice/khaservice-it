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
    $files_deleted_count = 0;

    try {
        $pdo->beginTransaction();
        
        $stmt_get_files = $pdo->prepare("SELECT file_path FROM maintenance_files WHERE maintenance_id = ?");
        $stmt_del_files = $pdo->prepare("DELETE FROM maintenance_files WHERE maintenance_id = ?");
        $stmt_del_log = $pdo->prepare("DELETE FROM maintenance_logs WHERE id = ?");

        foreach ($ids as $id) {
            // 1. Handle physical files
            $stmt_get_files->execute([$id]);
            $files = $stmt_get_files->fetchAll(PDO::FETCH_COLUMN);
            foreach ($files as $file_path) {
                $full_path = __DIR__ . "/../../" . $file_path;
                if (file_exists($full_path)) {
                    unlink($full_path);
                    $files_deleted_count++;
                }
            }

            // 2. Delete database records
            $stmt_del_files->execute([$id]);
            $stmt_del_log->execute([$id]);
            $deleted_count += $stmt_del_log->rowCount();
        }

        $pdo->commit();

        if ($deleted_count > 0) {
            $msg = "Đã xóa thành công $deleted_count phiếu bảo trì.";
            if ($files_deleted_count > 0) {
                $msg .= " Đã gỡ bỏ $files_deleted_count tài liệu đính kèm liên quan.";
            }
            set_message('success', $msg);
        } else {
            set_message('warning', 'Không có phiếu nào được xóa.');
        }

    } catch (PDOException $e) {
        $pdo->rollBack();
        set_message('error', 'Lỗi khi xóa dữ liệu: ' . $e->getMessage());
    }
} else {
    set_message('error', 'Yêu cầu không hợp lệ.');
}

header("Location: index.php?page=maintenance/history");
exit;
?>
