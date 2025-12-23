<?php
// modules/maintenance/export.php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/messages.php';

if (ob_get_length()) ob_clean();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_selected'])) {
    $selected_ids = $_POST['ids'] ?? [];
    $visible_columns_json = $_POST['visible_columns'] ?? '[]';
    $visible_columns = json_decode($visible_columns_json, true);

    if (empty($selected_ids)) {
        set_message('warning', 'Vui lòng chọn ít nhất một phiếu để xuất.');
        header('Location: index.php?page=maintenance/history'); exit;
    }

    $ids = array_map('intval', $selected_ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $sql = "SELECT ml.*, d.ma_tai_san, d.ten_thiet_bi, p.ten_du_an, u.fullname as nguoi_thuc_hien 
            FROM maintenance_logs ml 
            LEFT JOIN devices d ON ml.device_id = d.id 
            LEFT JOIN projects p ON ml.project_id = p.id 
            LEFT JOIN users u ON ml.user_id = u.id
            WHERE ml.id IN ($placeholders) ORDER BY ml.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "Export_Maintenance_" . date('Ymd_His') . ".xls";
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><table border="1">';
    
    echo '<tr style="background-color: #10b981; color: #ffffff; font-weight: bold;"><th>STT</th>';
    foreach ($visible_columns as $col) echo '<th>' . htmlspecialchars($col['label']) . '</th>';
    echo '<th>Yêu cầu</th><th>Nguyên nhân</th><th>Xử lý</th></tr>';

    $stt = 1;
    foreach ($data as $row) {
        echo '<tr><td>' . $stt++ . '</td>';
        foreach ($visible_columns as $col) {
            $key = $col['key'];
            $val = $row[$key] ?? '';
            if ($key === 'ngay_su_co') $val = date('d/m/Y', strtotime($val));
            if ($key === 'ten_thiet_bi' && empty($row['device_id'])) $val = $row['custom_device_name'] ?: "Công tác chung";
            if ($key === 'ma_tai_san' && empty($row['device_id'])) $val = $row['work_type'];
            echo '<td>' . htmlspecialchars($val) . '</td>';
        }
        echo '<td>' . htmlspecialchars($row['noi_dung']) . '</td>';
        echo '<td>' . htmlspecialchars($row['hu_hong']) . '</td>';
        echo '<td>' . htmlspecialchars($row['xu_ly']) . '</td>';
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}
?>