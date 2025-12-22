<?php
// modules/services/delete_multiple.php

if (!isIT()) {
    set_message('error', 'Bạn không có quyền thực hiện thao tác này.');
    header("Location: index.php?page=services/list");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = $_POST['ids'];
    $deleted_count = 0;

    try {
        $pdo->beginTransaction();
        
        $stmt_del = $pdo->prepare("UPDATE services SET deleted_at = NOW() WHERE id = ?");

        foreach ($ids as $id) {
            $stmt_del->execute([$id]);
            $deleted_count += $stmt_del->rowCount();
        }

        $pdo->commit();

        if ($deleted_count > 0) {
            set_message('success', "Đã chuyển thành công $deleted_count dịch vụ vào thùng rác.");
        } else {
            set_message('warning', 'Không có dịch vụ nào được xử lý.');
        }

    } catch (PDOException $e) {
        $pdo->rollBack();
        set_message('error', 'Lỗi khi xóa dữ liệu: ' . $e->getMessage());
    }
} else {
    set_message('error', 'Yêu cầu không hợp lệ.');
}

header("Location: index.php?page=services/list");
exit;
?>
