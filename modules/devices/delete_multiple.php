<?php
// modules/devices/delete_multiple.php

if (!isAdmin()) {
    set_message('error', 'Bạn không có quyền thực hiện thao tác này.');
    header("Location: index.php?page=devices/list");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_devices']) && is_array($_POST['selected_devices'])) {
    $ids = $_POST['selected_devices'];
    $deleted_count = 0;
    $skipped_count = 0;
    $skipped_details = [];

    try {
        $pdo->beginTransaction();
        
        $stmt_check_child = $pdo->prepare("SELECT COUNT(*) FROM devices WHERE parent_id = ? AND deleted_at IS NULL");
        $stmt_del = $pdo->prepare("UPDATE devices SET deleted_at = NOW() WHERE id = ?");
        $stmt_info = $pdo->prepare("SELECT ma_tai_san FROM devices WHERE id = ?");

        foreach ($ids as $id) {
            // Check for child devices
            $stmt_check_child->execute([$id]);
            $child_count = $stmt_check_child->fetchColumn();

            if ($child_count > 0) {
                $skipped_count++;
                $stmt_info->execute([$id]);
                $ma_ts = $stmt_info->fetchColumn() ?: "ID $id";
                $skipped_details[] = "$ma_ts (có $child_count linh kiện con)";
            } else {
                $stmt_del->execute([$id]);
                $deleted_count += $stmt_del->rowCount();
            }
        }

        $pdo->commit();

        if ($deleted_count > 0) {
            $msg = "Đã chuyển thành công $deleted_count thiết bị vào thùng rác.";
            if ($skipped_count > 0) {
                set_message('warning', $msg . " Bỏ qua $skipped_count thiết bị do có linh kiện con: " . implode(', ', $skipped_details));
            } else {
                set_message('success', $msg);
            }
        } else {
            if ($skipped_count > 0) {
                set_message('error', "Không thể xóa $skipped_count thiết bị đã chọn vì chúng vẫn còn chứa linh kiện con.");
            } else {
                set_message('warning', 'Không có thiết bị nào được xử lý.');
            }
        }

    } catch (PDOException $e) {
        $pdo->rollBack();
        set_message('error', 'Lỗi khi xử lý dữ liệu: ' . $e->getMessage());
    }
} else {
    set_message('error', 'Yêu cầu không hợp lệ.');
}

header("Location: index.php?page=devices/list");
exit;
?>