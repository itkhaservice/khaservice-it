<?php
// modules/car_inspections/delete.php

// Kiểm tra quyền (Chỉ Admin hoặc IT mới được xóa lịch)
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'it')) {
    set_message("error", "Bạn không có quyền thực hiện hành động này!");
    header("Location: index.php?page=car_inspections/list");
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    set_message("error", "ID không hợp lệ.");
    header("Location: index.php?page=car_inspections/list");
    exit;
}

try {
    // Kiểm tra tồn tại và lấy thông tin để thông báo
    $stmt = $pdo->prepare("SELECT ci.*, p.ten_du_an FROM car_inspections ci JOIN projects p ON ci.project_id = p.id WHERE ci.id = ?");
    $stmt->execute([$id]);
    $inspection = $stmt->fetch();

    if (!$inspection) {
        set_message("error", "Không tìm thấy lịch kiểm tra.");
        header("Location: index.php?page=car_inspections/list");
        exit;
    }

    // Thực hiện xóa vĩnh viễn (vì bảng này không có soft delete)
    $delete_stmt = $pdo->prepare("DELETE FROM car_inspections WHERE id = ?");
    $delete_stmt->execute([$id]);

    // Ghi log hoạt động (nếu có hệ thống audit)
    if (file_exists(__DIR__ . '/../../includes/audit_helper.php')) {
        require_once __DIR__ . '/../../includes/audit_helper.php';
        log_action($pdo, 'DELETE_CAR_INSPECTION', 'car_inspections', $id, "Deleted inspection for project: " . $inspection['ten_du_an'] . " on " . $inspection['inspection_date']);
    }

    set_message("success", "Đã gỡ lịch kiểm tra dự án " . $inspection['ten_du_an'] . " thành công.");

} catch (PDOException $e) {
    set_message("error", "Lỗi khi xóa: " . $e->getMessage());
}

// Chuyển hướng về trang danh sách (kèm tháng của lịch vừa xóa để người dùng dễ theo dõi)
$redirect_month = isset($inspection['inspection_date']) ? date('Y-m', strtotime($inspection['inspection_date'])) : date('Y-m');
header("Location: index.php?page=car_inspections/list&month=" . $redirect_month);
exit;
