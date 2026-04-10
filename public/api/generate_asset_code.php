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

    // 3. Tạo Mã Loại Thiết Bị (Lấy 3 chữ cái đầu không dấu)
    function getTypeCode($str) {
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", "a", $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", "e", $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", "i", $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", "o", $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ỡ)/", "u", $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", "y", $str);
        $str = preg_replace("/(đ)/", "d", $str);
        $str = strtoupper(preg_replace("/[^A-Z0-9]/i", "", $str));
        return substr($str, 0, 3);
    }
    $type_code = getTypeCode($type_name);

    // 4. Lấy STT tiếp theo (Cùng dự án, cùng nhóm, cùng loại)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM devices WHERE project_id = ? AND nhom_thiet_bi = ? AND loai_thiet_bi = ?");
    $stmt->execute([$project_id, $group_name, $type_name]);
    $count = $stmt->fetchColumn();
    $next_stt = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

    $final_code = "KHAS-{$project_code}-{$group_code}-{$type_code}-{$next_stt}";

    echo json_encode(['code' => $final_code]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>