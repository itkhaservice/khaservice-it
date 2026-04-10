<?php
// modules/it_activities/delete_health_check.php

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'it')) {
    set_message("error", "Bạn không có quyền thực hiện thao tác này!");
    echo '<script>window.location.href = "index.php";</script>';
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        $pdo->beginTransaction();

        // 1. Delete details first (due to foreign key or just to be clean)
        $stmt = $pdo->prepare("DELETE FROM it_system_health_check_details WHERE check_id = ?");
        $stmt->execute([$id]);

        // 2. Delete the main record
        $stmt = $pdo->prepare("DELETE FROM it_system_health_checks WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();
        set_message("success", "Đã xóa kế hoạch kiểm tra hệ thống thành công!");
    } catch (PDOException $e) {
        $pdo->rollBack();
        set_message("error", "Lỗi khi xóa: " . $e->getMessage());
    }
} else {
    set_message("error", "Mã bản ghi không hợp lệ!");
}

echo '<script>window.location.href = "index.php?page=it_activities/list";</script>';
exit;
?>