<?php
// modules/devices/delete_multiple.php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/messages.php';

// Check if the user is an admin or has the right permissions
if ($_SESSION['role'] !== 'admin') {
    set_message('error', 'Bạn không có quyền thực hiện hành động này.');
    header('Location: index.php?page=devices/list');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_selected'])) {
    $selected_devices = $_POST['selected_devices'] ?? [];

    if (empty($selected_devices)) {
        set_message('warning', 'Vui lòng chọn ít nhất một thiết bị để xóa.');
        header('Location: index.php?page=devices/list');
        exit;
    }

    // Sanitize the array of IDs to ensure they are integers
    $ids_to_delete = array_map('intval', $selected_devices);
    
    // Create placeholders for the IN clause (e.g., "?,?,?")
    $placeholders = implode(',', array_fill(0, count($ids_to_delete), '?'));

    try {
        // Start a transaction
        $pdo->beginTransaction();

        // 1. Find and delete associated files from the filesystem
        $stmt_files = $pdo->prepare("SELECT file_path FROM device_files WHERE device_id IN ($placeholders)");
        $stmt_files->execute($ids_to_delete);
        $files_to_delete = $stmt_files->fetchAll(PDO::FETCH_COLUMN);

        foreach ($files_to_delete as $file_path) {
            $real_file_path = realpath(__DIR__ . '/../../' . $file_path);
            $base_path = realpath(__DIR__ . '/../../uploads');
            if ($real_file_path && strpos($real_file_path, $base_path) === 0 && file_exists($real_file_path)) {
                unlink($real_file_path);
            }
        }

        // 2. Delete records from device_files table
        $stmt_df = $pdo->prepare("DELETE FROM device_files WHERE device_id IN ($placeholders)");
        $stmt_df->execute($ids_to_delete);

        // 3. Delete records from maintenance_logs table
        $stmt_ml = $pdo->prepare("DELETE FROM maintenance_logs WHERE device_id IN ($placeholders)");
        $stmt_ml->execute($ids_to_delete);

        // 4. Delete the devices themselves
        $stmt_d = $pdo->prepare("DELETE FROM devices WHERE id IN ($placeholders)");
        $stmt_d->execute($ids_to_delete);
        $count = $stmt_d->rowCount();

        // If all queries were successful, commit the transaction
        $pdo->commit();

        set_message('success', "Đã xóa thành công {$count} thiết bị và tất cả dữ liệu liên quan.");

    } catch (PDOException $e) {
        $pdo->rollBack();
        set_message('error', 'Lỗi cơ sở dữ liệu khi xóa thiết bị: ' . $e->getMessage());
    } catch (Exception $e) {
        $pdo->rollBack();
        set_message('error', 'Đã xảy ra lỗi: ' . $e->getMessage());
    }

    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?page=devices/list'));
    exit;

} else {
    // If not a POST request or delete_selected is not set, redirect
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?page=devices/list'));
    exit;
}
