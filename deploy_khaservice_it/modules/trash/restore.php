<?php
// modules/trash/restore.php

if (!isIT()) {
    set_message('error', 'Bạn không có quyền thực hiện thao tác này.');
    header("Location: index.php");
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
    $stmt = $pdo->prepare("UPDATE `$table` SET deleted_at = NULL WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        set_message('success', 'Đã khôi phục dữ liệu thành công!');
    } else {
        set_message('warning', 'Không tìm thấy dữ liệu để khôi phục.');
    }
} catch (PDOException $e) {
    set_message('error', 'Lỗi khôi phục: ' . $e->getMessage());
}

header("Location: index.php?page=trash/list&type=$type");
exit;
?>
