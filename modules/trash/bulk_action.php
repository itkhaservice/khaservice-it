<?php
// modules/trash/bulk_action.php

if (!isIT()) {
    set_message('error', 'Bạn không có quyền thực hiện thao tác này.');
    echo "<script>window.location.href = 'index.php?page=trash/list';</script>";
    exit;
}

$type = $_GET['type'] ?? '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$allowed_types = ['maintenance', 'devices', 'projects', 'services', 'suppliers', 'users'];

if (!in_array($type, $allowed_types) || !in_array($action, ['restore_all', 'empty_trash', 'restore_selected', 'delete_selected'])) {
    set_message('error', 'Yêu cầu không hợp lệ.');
    echo "<script>window.location.href = 'index.php?page=trash/list';</script>";
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
        $stmt = $pdo->prepare("DELETE FROM $table WHERE deleted_at IS NOT NULL");
        $stmt->execute();
        set_message('success', 'Đã dọn sạch thùng rác thành công!');
    } elseif ($action === 'restore_selected' && isset($_POST['ids']) && is_array($_POST['ids'])) {
        $ids = array_map('intval', $_POST['ids']);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE $table SET deleted_at = NULL WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        set_message('success', 'Đã khôi phục ' . count($ids) . ' mục thành công!');
    } elseif ($action === 'delete_selected' && isset($_POST['ids']) && is_array($_POST['ids'])) {
        $ids = array_map('intval', $_POST['ids']);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        set_message('success', 'Đã xóa vĩnh viễn ' . count($ids) . ' mục thành công!');
    } else {
        set_message('warning', 'Không có mục nào được chọn.');
    }
} catch (PDOException $e) {
    set_message('error', 'Lỗi thực thi: ' . $e->getMessage());
}

echo "<script>window.location.href = 'index.php?page=trash/list&type=$type';</script>";
exit;
