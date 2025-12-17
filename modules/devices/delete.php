<?php
if (isset($_GET['id'])) {
    $device_id = $_GET['id'];

    // Optional: Check if the device exists before attempting to delete
    $check_stmt = $pdo->prepare("SELECT id FROM devices WHERE id = ?");
    $check_stmt->execute([$device_id]);
    if ($check_stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM devices WHERE id = ?");
        $stmt->execute([$device_id]);
        $_SESSION['message'] = 'Thiết bị đã được xóa thành công!'; // Not implemented yet, but good for future
    } else {
        $_SESSION['error'] = 'Thiết bị không tìm thấy để xóa.'; // Not implemented yet
    }
}

header("Location: index.php?page=devices/list");
exit;
