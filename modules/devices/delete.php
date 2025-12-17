<?php
if (isset($_GET['id'])) {
    $device_id = $_GET['id'];

    // Optional: Check if the device exists before attempting to delete
    try {
        // Optional: Check if the device exists before attempting to delete
        $check_stmt = $pdo->prepare("SELECT id FROM devices WHERE id = ?");
        $check_stmt->execute([$device_id]);
        if ($check_stmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM devices WHERE id = ?");
            $stmt->execute([$device_id]);
            set_message('success', 'Thiết bị đã được xóa thành công!');
        } else {
            set_message('error', 'Thiết bị không tìm thấy để xóa.');
        }
    } catch (PDOException $e) {
        set_message('error', 'Lỗi khi xóa thiết bị: ' . $e->getMessage());
    }
} else {
    set_message('error', 'Không có ID thiết bị được cung cấp.');
}

header("Location: index.php?page=devices/list");
exit;
