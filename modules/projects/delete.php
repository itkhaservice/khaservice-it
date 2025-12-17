<?php
if (isset($_GET['id'])) {
    $project_id = $_GET['id'];

    $check_stmt = $pdo->prepare("SELECT id FROM projects WHERE id = ?");
    $check_stmt->execute([$project_id]);
    if ($check_stmt->fetch()) {
        // Before deleting a project, consider handling dependent devices.
        // For simplicity, we'll just delete the project. In a real app,
        // you might want to set device.project_id to NULL, or prevent deletion
        // if devices are linked.
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $_SESSION['message'] = 'Dự án đã được xóa thành công!'; // For future use
    } else {
        $_SESSION['error'] = 'Dự án không tìm thấy để xóa.'; // For future use
    }
}

header("Location: index.php?page=projects/list");
exit;
