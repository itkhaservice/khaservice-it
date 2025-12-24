<?php
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'it')) {
    set_message("Bạn không có quyền xóa!", "error");
    echo '<script>window.location.href = "index.php?page=car_systems/list";</script>';
    exit;
}

$id = $_GET['id'] ?? 0;
if ($id) {
    // Check confirmation (though main.js usually handles prompt, this is server-side check)
    if (isset($_GET['confirm_delete']) && $_GET['confirm_delete'] == 1) {
        try {
            $stmt = $pdo->prepare("DELETE FROM car_system_configs WHERE id = ?");
            $stmt->execute([$id]);
            set_message("Đã xóa cấu hình thành công!", "success");
        } catch (PDOException $e) {
            set_message("Lỗi xóa: " . $e->getMessage(), "error");
        }
    }
}

echo '<script>window.location.href = "index.php?page=car_systems/list";</script>';
exit;
