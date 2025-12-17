<?php
if (!isset($_GET['id'])) {
    set_message('error', 'Không có ID thiết bị được cung cấp.');
    header("Location: index.php?page=devices/list");
    exit;
}

$device_id = $_GET['id'];

try {
    // Start a transaction
    $pdo->beginTransaction();

    // 1. Find and delete associated files from the filesystem
    $stmt_files = $pdo->prepare("SELECT file_path FROM device_files WHERE device_id = ?");
    $stmt_files->execute([$device_id]);
    $files_to_delete = $stmt_files->fetchAll(PDO::FETCH_COLUMN);

    foreach ($files_to_delete as $file_path) {
        // Make sure the file path is within the uploads directory for security
        $real_file_path = realpath(__DIR__ . '/../../' . $file_path);
        $base_path = realpath(__DIR__ . '/../../uploads');

        if ($real_file_path && strpos($real_file_path, $base_path) === 0 && file_exists($real_file_path)) {
            unlink($real_file_path);
        }
    }

    // 2. Delete records from device_files table
    $stmt_df = $pdo->prepare("DELETE FROM device_files WHERE device_id = ?");
    $stmt_df->execute([$device_id]);

    // 3. Delete records from maintenance_logs table
    $stmt_ml = $pdo->prepare("DELETE FROM maintenance_logs WHERE device_id = ?");
    $stmt_ml->execute([$device_id]);

    // 4. Delete the device itself
    $stmt_d = $pdo->prepare("DELETE FROM devices WHERE id = ?");
    $stmt_d->execute([$device_id]);

    // If all queries were successful, commit the transaction
    $pdo->commit();

    set_message('success', 'Thiết bị và tất cả dữ liệu liên quan đã được xóa thành công!');

} catch (PDOException $e) {
    // If any query fails, roll back the transaction
    $pdo->rollBack();
    set_message('error', 'Lỗi khi xóa thiết bị: ' . $e->getMessage());
} catch (Exception $e) {
    // Catch other general exceptions (e.g., from file operations)
    $pdo->rollBack();
    set_message('error', 'Đã xảy ra lỗi: ' . $e->getMessage());
header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php?page=devices/list'));
exit;
