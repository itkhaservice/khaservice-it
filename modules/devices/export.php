<?php
// modules/devices/export.php
// Script này xử lý xuất dữ liệu thiết bị ra file Excel (.xls) theo cột đang hiển thị

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/messages.php';

// Xóa mọi nội dung trong bộ đệm để đảm bảo file Excel sạch sẽ
if (ob_get_length()) ob_clean();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_selected'])) {
    $selected_devices = $_POST['selected_devices'] ?? [];
    $visible_columns_json = $_POST['visible_columns'] ?? '[]';
    $visible_columns = json_decode($visible_columns_json, true);

    if (empty($selected_devices)) {
        set_message('warning', 'Vui lòng chọn ít nhất một thiết bị để xuất file.');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?page=devices/list'));
        exit;
    }

    if (empty($visible_columns)) {
        set_message('warning', 'Không có cột nào được chọn để xuất.');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php?page=devices/list'));
        exit;
    }

    // Làm sạch mảng ID
    $selected_ids = array_map('intval', $selected_devices);
    $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));

    // Truy vấn đầy đủ thông tin
    $sql = "
        SELECT 
            d.*, 
            p.ten_du_an, 
            s.ten_npp,
            parent.ten_thiet_bi as parent_name
        FROM devices d
        LEFT JOIN projects p ON d.project_id = p.id
        LEFT JOIN suppliers s ON d.supplier_id = s.id
        LEFT JOIN devices parent ON d.parent_id = parent.id
        WHERE d.id IN ($placeholders)
        ORDER BY p.ten_du_an ASC, d.ma_tai_san ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($selected_ids);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Thiết lập Header Excel
    $filename = "Export_Devices_" . date('Ymd_His') . ".xls";
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
    echo '<body>';
    echo '<table border="1">';
    
    // 1. Dòng tiêu đề cột (Động theo UI)
    echo '<tr style="background-color: #10b981; color: #ffffff; font-weight: bold;">';
    echo '<th>STT</th>';
    foreach ($visible_columns as $col) {
        echo '<th>' . htmlspecialchars($col['label']) . '</th>';
    }
    echo '</tr>';

    // 2. Nội dung dữ liệu
    $stt = 1;
    foreach ($data as $row) {
        echo '<tr>';
        echo '<td>' . $stt++ . '</td>';
        foreach ($visible_columns as $col) {
            $key = $col['key'];
            $val = '';
            
            // Xử lý logic format cho từng loại dữ liệu
            switch ($key) {
                case 'ngay_mua':
                case 'bao_hanh_den':
                    $val = $row[$key] ? date('d/m/Y', strtotime($row[$key])) : '';
                    break;
                case 'gia_mua':
                    $val = number_format($row[$key], 0, ",", ".");
                    break;
                default:
                    $val = $row[$key] ?? '';
                    break;
            }
            
            echo '<td>' . htmlspecialchars($val) . '</td>';
        }
        echo '</tr>';
    }

    echo '</table>';
    echo '</body></html>';
    exit;

} else {
    set_message('error', 'Yêu cầu không hợp lệ.');
    header('Location: index.php?page=devices/list');
    exit;
}
