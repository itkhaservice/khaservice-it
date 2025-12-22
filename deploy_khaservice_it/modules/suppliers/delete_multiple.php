<?php
// modules/suppliers/delete_multiple.php

if (!isAdmin()) {
    set_message('error', 'Bạn không có quyền thực hiện thao tác này.');
    header("Location: index.php?page=suppliers/list");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = $_POST['ids'];
    $deleted_count = 0;
    $skipped_count = 0;
    $skipped_details = [];

    try {
        $pdo->beginTransaction();
        
        $stmt_check_devices = $pdo->prepare("SELECT COUNT(*) FROM devices WHERE supplier_id = ?");
        $stmt_check_services = $pdo->prepare("SELECT COUNT(*) FROM services WHERE supplier_id = ?");
        $stmt_del = $pdo->prepare("UPDATE suppliers SET deleted_at = NOW() WHERE id = ?");
        $stmt_name = $pdo->prepare("SELECT ten_npp FROM suppliers WHERE id = ?");

        foreach ($ids as $id) {
            // Check dependencies
            $stmt_check_devices->execute([$id]);
            $device_count = $stmt_check_devices->fetchColumn();
            
            $stmt_check_services->execute([$id]);
            $service_count = $stmt_check_services->fetchColumn();

            if ($device_count > 0 || $service_count > 0) {
                $skipped_count++;
                $stmt_name->execute([$id]);
                $name = $stmt_name->fetchColumn() ?: "ID $id";
                $reasons = [];
                if($device_count > 0) $reasons[] = "$device_count thiết bị";
                if($service_count > 0) $reasons[] = "$service_count dịch vụ";
                $skipped_details[] = "$name (" . implode(', ', $reasons) . ")";
            } else {
                $stmt_del->execute([$id]);
                $deleted_count += $stmt_del->rowCount();
            }
        }

        $pdo->commit();

        if ($deleted_count > 0) {
            $msg = "Đã chuyển thành công $deleted_count nhà cung cấp vào thùng rác.";
            if ($skipped_count > 0) {
                set_message('warning', $msg . " Có $skipped_count mục bị bỏ qua do còn dữ liệu liên quan: " . implode(', ', $skipped_details));
            } else {
                set_message('success', $msg);
            }
        } else {
            if ($skipped_count > 0) {
                 set_message('error', "Không thể xử lý các nhà cung cấp đã chọn vì họ đang liên kết với thiết bị hoặc dịch vụ. Chi tiết: " . implode(', ', $skipped_details));
            } else {
                set_message('warning', 'Không có dữ liệu nào được xử lý.');
            }
        }

    } catch (PDOException $e) {
        $pdo->rollBack();
        set_message('error', 'Lỗi khi xóa dữ liệu: ' . $e->getMessage());
    }
} else {
    set_message('error', 'Yêu cầu không hợp lệ.');
}

header("Location: index.php?page=suppliers/list");
exit;
?>
