<?php
// modules/car_systems/export.php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/messages.php';

if (ob_get_length()) ob_clean();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_selected'])) {
    $selected_items = $_POST['selected_items'] ?? [];
    $visible_columns_json = $_POST['visible_columns'] ?? '[]';
    $visible_columns = json_decode($visible_columns_json, true);

    if (empty($selected_items)) {
        set_message('warning', 'Vui lòng chọn ít nhất một mục để xuất file.');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?page=car_systems/list'));
        exit;
    }

    $selected_ids = array_map('intval', $selected_items);
    $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));

    $sql = "
        SELECT c.*, p.ten_du_an 
        FROM car_system_configs c
        JOIN projects p ON c.project_id = p.id
        WHERE c.id IN ($placeholders)
        ORDER BY p.ten_du_an ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($selected_ids);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "Export_CarSystems_" . date('Ymd_His') . ".xls";
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
    echo '<body>';
    echo '<table border="1">';
    
    echo '<tr style="background-color: #3b82f6; color: #ffffff; font-weight: bold;">';
    echo '<th>STT</th>';
    foreach ($visible_columns as $col) {
        echo '<th>' . htmlspecialchars($col['label']) . '</th>';
    }
    echo '</tr>';

    $stt = 1;
    foreach ($data as $row) {
        echo '<tr>';
        echo '<td>' . $stt++ . '</td>';
        foreach ($visible_columns as $col) {
            echo '<td>' . htmlspecialchars($row[$col['key']] ?? '') . '</td>';
        }
        echo '</tr>';
    }

    echo '</table>';
    echo '</body></html>';
    exit;
}
?>