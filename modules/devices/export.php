<?php
// modules/devices/export.php
// This script requires authentication.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/messages.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_selected'])) {
    $selected_devices = $_POST['selected_devices'] ?? [];

    if (empty($selected_devices)) {
        set_message('warning', 'Vui lòng chọn ít nhất một thiết bị để xuất file.');
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'index.php?page=devices/list');
        exit;
    }

    // Sanitize the array of IDs
    $selected_ids = array_map('intval', $selected_devices);
    
    // Create placeholders for the IN clause
    $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));

    $sql = "
        SELECT 
            d.ma_tai_san,
            d.ten_thiet_bi,
            d.serial_number,
            d.ngay_mua,
            d.gia_mua,
            d.thoi_han_bao_hanh,
            d.trang_thai,
            p.ten_du_an,
            s.ten_npp,
            d.ghi_chu
        FROM devices d
        LEFT JOIN projects p ON d.project_id = p.id
        LEFT JOIN suppliers s ON d.supplier_id = s.id
        WHERE d.id IN ($placeholders)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($selected_ids);
    $devices_to_export = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="devices_export_' . date('Y-m-d') . '.csv"');

    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Write UTF-8 BOM to make Excel open it correctly
    fwrite($output, "\xEF\xBB\xBF");

    // Write header row
    fputcsv($output, [
        'Mã Tài Sản',
        'Tên Thiết Bị',
        'Serial Number',
        'Ngày Mua',
        'Giá Mua',
        'Thời Hạn Bảo Hành (tháng)',
        'Trạng Thái',
        'Tên Dự Án',
        'Nhà Cung Cấp',
        'Ghi Chú'
    ]);

    // Write data rows
    foreach ($devices_to_export as $device) {
        fputcsv($output, $device);
    }

    fclose($output);
    exit;

} else {
    // If accessed directly or without the correct POST data, redirect back
    set_message('error', 'Hành động không hợp lệ.');
    header('Location: index.php?page=devices/list');
    exit;
}
