<?php
// modules/services/export.php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/messages.php';

if (ob_get_length()) ob_clean();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_selected'])) {
    $selected_ids = $_POST['ids'] ?? [];
    $visible_columns_json = $_POST['visible_columns'] ?? '[]';
    $visible_columns = json_decode($visible_columns_json, true);

    if (empty($selected_ids)) {
        set_message('warning', 'Vui lòng chọn ít nhất một dịch vụ.');
        header('Location: index.php?page=services/list'); exit;
    }

    $ids = array_map('intval', $selected_ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("SELECT s.*, p.ten_du_an, sup.ten_npp FROM services s LEFT JOIN projects p ON s.project_id = p.id LEFT JOIN suppliers sup ON s.supplier_id = sup.id WHERE s.id IN ($placeholders) ORDER BY s.ngay_het_han ASC");
    $stmt->execute($ids);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "Export_Services_" . date('Ymd_His') . ".xls";
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><table border="1">';
    
    echo '<tr style="background-color: #10b981; color: #ffffff; font-weight: bold;"><th>STT</th>';
    foreach ($visible_columns as $col) echo '<th>' . htmlspecialchars($col['label']) . '</th>';
    echo '</tr>';

    $stt = 1;
    foreach ($data as $row) {
        echo '<tr><td>' . $stt++ . '</td>';
        foreach ($visible_columns as $col) {
            $key = $col['key'];
            $val = $row[$key] ?? '';
            if ($key === 'ngay_het_han' || $key === 'ngay_nhan_de_nghi') $val = $val ? date('d/m/Y', strtotime($val)) : '';
            if ($key === 'ten_du_an' && empty($val)) $val = 'Dùng chung';
            echo '<td>' . htmlspecialchars($val) . '</td>';
        }
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}
?>
