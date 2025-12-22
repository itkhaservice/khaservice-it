<?php
// modules/maintenance/delete_multiple.php

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'it') {
    set_message('error', 'Bạn không có quyền thực hiện thao tác này.');
    header("Location: index.php?page=maintenance/history");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = $_POST['ids'];
    $count = 0;

    try {
        $pdo->beginTransaction();
        
        // Prepare statement for efficiency and security
        $stmt = $pdo->prepare("DELETE FROM maintenance_logs WHERE id = ?");

        foreach ($ids as $id) {
            $stmt->execute([$id]);
            $count += $stmt->rowCount();
        }

        $pdo->commit();

        if ($count > 0) {
            set_message('success', "Đã xóa thành công $count phiếu bảo trì.");
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
