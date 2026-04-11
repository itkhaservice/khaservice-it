<?php
// public/api/generate_asset_code.php
require_once '../../config/db.php';

header('Content-Type: application/json');

$project_id = $_GET['project_id'] ?? '';
$group_name = $_GET['group_name'] ?? '';
$type_name = $_GET['type_name'] ?? '';

if (!$project_id || !$group_name || !$type_name) {
    echo json_encode(['code' => '']);
    exit;
}

try {
    // 1. Lấy Mã Dự Án
    $stmt = $pdo->prepare("SELECT ma_du_an FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
    $project_code = strtoupper($project['ma_du_an'] ?? 'DA');

    // 2. Map Mã Phân Nhóm theo chuẩn yêu cầu
    $group_map = [
        'Bãi xe' => 'BX',
        'Hệ thống xe' => 'HTX',
        'Tòa nhà' => 'TN',
        'Văn phòng' => 'VP'
    ];
    $group_code = $group_map[$group_name] ?? strtoupper(substr($group_name, 0, 2));

    // 3. Lấy Mã Loại Thiết Bị từ bảng settings_device_types
    $stmt = $pdo->prepare("SELECT type_code FROM settings_device_types WHERE type_name = ?");
    $stmt->execute([$type_name]);
    $type_row = $stmt->fetch();
    $type_code = strtoupper($type_row['type_code'] ?? 'XXX');

    // 4. Lấy STT tiếp theo (Dựa trên tiền tố mã đã tồn tại để tránh trùng lặp)
    // Quy tắc: KHAS-[Mã DA]-[Mã nhóm]-[Mã Loại]-[STT]
    $prefix = "KHAS-{$project_code}-{$group_code}-{$type_code}-";
    
    // Tìm mã lớn nhất có cùng tiền tố, tập trung vào phần số cuối cùng
    $stmt = $pdo->prepare("SELECT ma_tai_san FROM devices WHERE ma_tai_san LIKE ? ORDER BY LENGTH(ma_tai_san) DESC, ma_tai_san DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last_device = $stmt->fetch();

    $next_num = 1;
    if ($last_device) {
        $last_code = $last_device['ma_tai_san'];
        // Tách lấy phần số cuối cùng (STT)
        $parts = explode('-', $last_code);
        $last_stt_str = end($parts);
        if (is_numeric($last_stt_str)) {
            $next_num = intval($last_stt_str) + 1;
        } else {
            // Nếu phần cuối không phải là số (trường hợp mã đặc biệt), đếm tổng số lượng cùng prefix để an toàn
            $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM devices WHERE ma_tai_san LIKE ?");
            $stmt_count->execute([$prefix . '%']);
            $next_num = $stmt_count->fetchColumn() + 1;
        }
    }
    
    $next_stt = str_pad($next_num, 3, '0', STR_PAD_LEFT);
    $final_code = $prefix . $next_stt;

    echo json_encode(['code' => $final_code]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>