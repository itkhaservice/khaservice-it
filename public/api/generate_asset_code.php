<?php
// public/api/generate_asset_code.php
require_once '../../config/db.php';

header('Content-Type: application/json');

$project_id = $_GET['project_id'] ?? '';
$group_name = $_GET['group_name'] ?? ''; // Thêm tham số Nhóm
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
    $project_code = $project['ma_du_an'] ?? 'DA';

    // 2. Lấy Mã Phân Nhóm (group_code)
    $stmt = $pdo->prepare("SELECT group_code FROM settings_device_groups WHERE group_name = ?");
    $stmt->execute([$group_name]);
    $group = $stmt->fetch();
    $group_code = $group['group_code'] ?? 'XX';

    // 3. Lấy Mã Loại Thiết Bị (type_code)
    $stmt = $pdo->prepare("SELECT type_code FROM settings_device_types WHERE type_name = ?");
    $stmt->execute([$type_name]);
    $type = $stmt->fetch();
    $type_code = $type['type_code'] ?? 'DEV';

    // 4. Đếm số lượng thiết bị hiện tại để tính STT
    // Quy tắc đếm: Cùng dự án, cùng nhóm, cùng loại
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM devices WHERE project_id = ? AND nhom_thiet_bi = ? AND loai_thiet_bi = ?");
    $stmt->execute([$project_id, $group_name, $type_name]);
    $count = $stmt->fetchColumn();
    
    $next_stt = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

    // 5. Tạo mã theo định dạng mới
    // Format: KHAS-[DA]-[GROUP]-[TYPE]-[001]
    $final_code = "KHAS-{$project_code}-{$group_code}-{$type_code}-{$next_stt}";

    echo json_encode(['code' => strtoupper($final_code)]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>