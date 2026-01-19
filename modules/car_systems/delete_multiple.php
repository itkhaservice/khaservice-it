<?php
// modules/car_systems/delete_multiple.php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/messages.php';

if (!isAdmin()) {
    set_message('error', 'Bạn không có quyền thực hiện hành động này.');
        echo "<script>window.location.href = 'index.php?page=car_systems/list';</script>";    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_items = $_POST['selected_items'] ?? [];

    if (empty($selected_items)) {
        set_message('warning', 'Vui lòng chọn ít nhất một mục để xóa.');
    } else {
        $ids = array_map('intval', $selected_items);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        try {
            $stmt = $pdo->prepare("DELETE FROM car_system_configs WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            set_message('success', 'Đã xóa ' . count($ids) . ' cấu hình thành công.');
        } catch (PDOException $e) {
            set_message('error', 'Lỗi khi xóa: ' . $e->getMessage());
        }
    }
}

    echo "<script>window.location.href = 'index.php?page=car_systems/list';</script>";exit;
?>