<?php
// modules/users/delete_multiple.php

if ($_SESSION['role'] !== 'admin') {
    set_message('error', 'Bạn không có quyền thực hiện thao tác này.');
    header("Location: index.php?page=users/list");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = $_POST['ids'];
    $deleted_count = 0;
    $skipped_count = 0;

    try {
        $pdo->beginTransaction();
        
        $stmt_del = $pdo->prepare("UPDATE users SET deleted_at = NOW() WHERE id = ? AND id != ?");

        foreach ($ids as $id) {
            if ($id == $_SESSION['user_id']) {
                $skipped_count++;
                continue;
            }
            $stmt_del->execute([$id, $_SESSION['user_id']]);
            $deleted_count += $stmt_del->rowCount();
        }

        $pdo->commit();

        if ($deleted_count > 0) {
            $msg = "Đã chuyển thành công $deleted_count người dùng vào thùng rác.";
            if ($skipped_count > 0) {
                $msg .= " Bỏ qua $skipped_count tài khoản (tài khoản đang đăng nhập).";
            }
            set_message('success', $msg);
        } else {
            if ($skipped_count > 0) {
                set_message('warning', 'Không thể xóa tài khoản của chính bạn.');
            } else {
                set_message('warning', 'Không có người dùng nào được xử lý.');
            }
        }

    } catch (PDOException $e) {
        $pdo->rollBack();
        set_message('error', 'Lỗi khi xóa dữ liệu: ' . $e->getMessage());
    }
} else {
    set_message('error', 'Yêu cầu không hợp lệ.');
}

header("Location: index.php?page=users/list");
exit;
?>
