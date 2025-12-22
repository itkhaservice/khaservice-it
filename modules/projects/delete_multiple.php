<?php
// modules/projects/delete_multiple.php

if (!isAdmin()) {
    set_message('error', 'Bạn không có quyền thực hiện thao tác này.');
    header("Location: index.php?page=projects/list");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = $_POST['ids'];
    $deleted_count = 0;
    $skipped_count = 0;
    $skipped_details = [];

    try {
        $pdo->beginTransaction();
        
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM devices WHERE project_id = ?");
        $stmt_del = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt_name = $pdo->prepare("SELECT ten_du_an FROM projects WHERE id = ?");

        foreach ($ids as $id) {
            // Check dependencies
            $stmt_check->execute([$id]);
            $count = $stmt_check->fetchColumn();

            if ($count > 0) {
                $skipped_count++;
                $stmt_name->execute([$id]);
                $name = $stmt_name->fetchColumn() ?: "ID $id";
                $skipped_details[] = "$name (còn $count thiết bị)";
            } else {
                $stmt_del->execute([$id]);
                $deleted_count += $stmt_del->rowCount();
            }
        }

        $pdo->commit();

        if ($deleted_count > 0) {
            $msg = "Đã xóa thành công $deleted_count dự án.";
            if ($skipped_count > 0) {
                $msg .= " Có $skipped_count dự án không thể xóa do còn dữ liệu liên quan."; // Simple message
                set_message('warning', $msg . " Chi tiết: " . implode(', ', $skipped_details));
            } else {
                set_message('success', $msg);
            }
        } else {
            if ($skipped_count > 0) {
                 set_message('error', "Không thể xóa $skipped_count dự án đã chọn vì chúng vẫn còn chứa thiết bị. Vui lòng kiểm tra lại.");
            } else {
                set_message('warning', 'Không có dự án nào được xóa.');
            }
        }

    } catch (PDOException $e) {
        $pdo->rollBack();
        set_message('error', 'Lỗi khi xóa dữ liệu: ' . $e->getMessage());
    }
} else {
    set_message('error', 'Yêu cầu không hợp lệ.');
}

header("Location: index.php?page=projects/list");
exit;
?>
