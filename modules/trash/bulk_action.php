<?php
// modules/trash/bulk_action.php

if (!isIT()) {
    set_message('error', 'Bạn không có quyền thực hiện thao tác này.');
    header("Location: index.php?page=trash/list");
    exit;
}

$type = $_GET['type'] ?? '';
$action = $_GET['action'] ?? '';
$allowed_types = ['maintenance', 'devices', 'projects', 'services', 'suppliers', 'users'];

if (!in_array($type, $allowed_types) || !in_array($action, ['restore_all', 'empty_trash'])) {
    set_message('error', 'Yêu cầu không hợp lệ.');
    header("Location: index.php?page=trash/list");
    exit;
}

// Map types to tables
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
    if ($action === 'restore_all') {
        $stmt = $pdo->prepare("UPDATE $table SET deleted_at = NULL WHERE deleted_at IS NOT NULL");
        $stmt->execute();
        set_message('success', 'Đã khôi phục toàn bộ dữ liệu thành công!');
    } elseif ($action === 'empty_trash') {
        // Vĩnh viễn xóa - Cần cẩn trọng
        $stmt = $pdo->prepare("DELETE FROM $table WHERE deleted_at IS NOT NULL");
        $stmt->execute();
        set_message('success', 'Đã dọn sạch thùng rác thành công!');
    }
} catch (PDOException $e) {
    set_message('error', 'Lỗi thực thi: ' . $e->getMessage());
}

header("Location: index.php?page=trash/list&type=$type");
exit;
