<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    set_message('error', 'Bạn không có quyền truy cập chức năng này.');
    header("Location: index.php?page=home"); // Redirect to home or a suitable page
    exit;
}

$user_id_to_delete = $_GET['id'] ?? null;

if ($user_id_to_delete) {
    // Prevent admin from deleting their own account
    if ($user_id_to_delete == $_SESSION['user_id']) {
        set_message('error', 'Bạn không thể xóa tài khoản của chính mình!');
        header("Location: index.php?page=users/list");
        exit;
    }

    try {
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $check_stmt->execute([$user_id_to_delete]);
        if ($check_stmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id_to_delete]);
            set_message('success', 'Người dùng đã được xóa thành công!');
        } else {
            set_message('error', 'Người dùng không tìm thấy để xóa.');
        }
    } catch (PDOException $e) {
        set_message('error', 'Lỗi khi xóa người dùng: ' . $e->getMessage());
    }
} else {
    set_message('error', 'Không có ID người dùng được cung cấp.');
}

header("Location: index.php?page=users/list");
exit;
