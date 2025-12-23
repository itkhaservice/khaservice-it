<?php
// modules/suppliers/export.php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/messages.php';

if (ob_get_length()) ob_clean();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_selected'])) {
    $selected_ids = $_POST['ids'] ?? [];
    $visible_columns_json = $_POST['visible_columns'] ?? '[]';
    $visible_columns = json_decode($visible_columns_json, true);

    if (empty($selected_ids)) {
        set_message('warning', 'Vui lòng chọn ít nhất một nhà cung cấp.');
        header('Location: index.php?page=suppliers/list'); exit;
    }

    $ids = array_map('intval', $selected_ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id IN ($placeholders) ORDER BY ten_npp ASC");
    $stmt->execute($ids);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "Export_Suppliers_" . date('Ymd_His') . ".xls";
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
            echo '<td>' . htmlspecialchars($row[$key] ?? '') . '</td>';
        }
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}
?>
