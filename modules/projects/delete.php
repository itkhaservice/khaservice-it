<?php
if (isset($_GET['id'])) {
    $project_id = $_GET['id'];

    try {
        $check_stmt = $pdo->prepare("SELECT id FROM projects WHERE id = ?");
        $check_stmt->execute([$project_id]);
        if ($check_stmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->execute([$project_id]);
            set_message('success', 'Dự án đã được xóa thành công!');
        } else {
            set_message('error', 'Dự án không tìm thấy để xóa.');
        }
    } catch (PDOException $e) {
        set_message('error', 'Lỗi khi xóa dự án: ' . $e->getMessage());
    }
} else {
    set_message('error', 'Không có ID dự án được cung cấp.');
}

header("Location: index.php?page=projects/list");
exit;
