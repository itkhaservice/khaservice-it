<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    echo "<p class='error'>Bạn không có quyền truy cập chức năng này.</p>";
    exit;
}

$user_id_to_delete = $_GET['id'] ?? null;

if ($user_id_to_delete) {
    // Prevent admin from deleting their own account
    if ($user_id_to_delete == $_SESSION['user_id']) {
        $_SESSION['error'] = 'Bạn không thể xóa tài khoản của chính mình!'; // For future use
        header("Location: index.php?page=users/list");
        exit;
    }

    $check_stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $check_stmt->execute([$user_id_to_delete]);
    if ($check_stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id_to_delete]);
        $_SESSION['message'] = 'Người dùng đã được xóa thành công!'; // For future use
    } else {
        $_SESSION['error'] = 'Người dùng không tìm thấy để xóa.'; // For future use
    }
}

header("Location: index.php?page=users/list");
exit;
