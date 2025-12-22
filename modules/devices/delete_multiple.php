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

    try {
        $pdo->beginTransaction();
        
        $stmt_del = $pdo->prepare("UPDATE devices SET deleted_at = NOW() WHERE id = ?");

        foreach ($ids as $id) {
            $stmt_del->execute([$id]);
            $deleted_count += $stmt_del->rowCount();
        }

        $pdo->commit();

        if ($deleted_count > 0) {
            set_message('success', "Đã chuyển thành công $deleted_count thiết bị vào thùng rác.");
        } else {
            set_message('warning', 'Không có thiết bị nào được xử lý.');
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