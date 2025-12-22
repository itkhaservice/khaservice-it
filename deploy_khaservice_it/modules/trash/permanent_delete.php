<?php
// modules/trash/permanent_delete.php

if (!isAdmin()) {
    set_message('error', 'Chỉ Admin mới có quyền xóa vĩnh viễn dữ liệu.');
    header("Location: index.php?page=trash/list");
    exit;
}

$id = $_GET['id'] ?? null;
$type = $_GET['type'] ?? null;
$allowed_types = ['maintenance', 'devices', 'projects', 'services', 'suppliers', 'users'];

if (!$id || !in_array($type, $allowed_types)) {
    set_message('error', 'Yêu cầu không hợp lệ.');
    header("Location: index.php?page=trash/list");
    exit;
}

$table_map = [
    'maintenance' => 'maintenance_logs',
    'devices'     => 'devices',
    'projects'    => 'projects',
    'services'    => 'services',
    'suppliers'   => 'suppliers',
    'users'       => 'users'
];

$table = $table_map[$type];

try {
    $pdo->beginTransaction();

    // Đặc thù cho từng bảng (Xóa file, dữ liệu liên quan vĩnh viễn)
    if ($type === 'maintenance') {
        // Xóa file đính kèm vật lý
        $stmt_files = $pdo->prepare("SELECT file_path FROM maintenance_files WHERE maintenance_id = ?");
        $stmt_files->execute([$id]);
        $files = $stmt_files->fetchAll(PDO::FETCH_COLUMN);
        foreach ($files as $file_path) {
            $full_path = __DIR__ . "/../../" . $file_path;
            if (file_exists($full_path)) unlink($full_path);
        }
        $pdo->prepare("DELETE FROM maintenance_files WHERE maintenance_id = ?")->execute([$id]);
    } 
    elseif ($type === 'devices') {
        // Xóa file thiết bị vật lý
        $stmt_files = $pdo->prepare("SELECT file_path FROM device_files WHERE device_id = ?");
        $stmt_files->execute([$id]);
        $files = $stmt_files->fetchAll(PDO::FETCH_COLUMN);
        foreach ($files as $file_path) {
            $full_path = __DIR__ . "/../../" . $file_path;
            if (file_exists($full_path)) unlink($full_path);
        }
        $pdo->prepare("DELETE FROM device_files WHERE device_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM maintenance_logs WHERE device_id = ?")->execute([$id]);
    }

    // Xóa bản ghi thực sự
    $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id = ?");
    $stmt->execute([$id]);
    
    $pdo->commit();
    set_message('success', 'Đã xóa vĩnh viễn dữ liệu khỏi hệ thống!');
} catch (Exception $e) {
    $pdo->rollBack();
    set_message('error', 'Lỗi xóa vĩnh viễn: ' . $e->getMessage());
}

header("Location: index.php?page=trash/list&type=$type");
exit;
?>
