<?php
// modules/it_activities/export_daily.php
if (!isset($pdo)) {
    require_once __DIR__ . '/../../config/db.php';
}

$id = $_GET['id'] ?? null;
if (!$id) die("Missing ID");

// Fetch main info
$stmt = $pdo->prepare("SELECT h.*, p.ten_du_an, u.fullname as checker_name 
                      FROM it_system_health_checks h
                      JOIN projects p ON h.project_id = p.id
                      JOIN users u ON h.checked_by = u.id
                      WHERE h.id = ?");
$stmt->execute([$id]);
$check = $stmt->fetch();
if (!$check) die("Not found");

// Fetch details and Build Tree
$stmt = $pdo->prepare("SELECT d.*, dev.ten_thiet_bi, dev.ma_tai_san, dev.nhom_thiet_bi, dev.parent_id
                      FROM it_system_health_check_details d
                      JOIN devices dev ON d.device_id = dev.id
                      WHERE d.check_id = ?
                      ORDER BY dev.nhom_thiet_bi, dev.parent_id ASC, dev.ten_thiet_bi");
$stmt->execute([$id]);
$details = $stmt->fetchAll();

$tree = [];
$roots = [];
$children = [];
foreach ($details as $row) {
    if (!$row['parent_id']) {
        $roots[$row['nhom_thiet_bi'] ?: 'Khác'][] = $row;
    } else {
        $children[$row['parent_id']][] = $row;
    }
}

foreach ($roots as $group => $root_list) {
    foreach ($root_list as $root) {
        $tree[] = ['item' => $root, 'level' => 0];
        if (isset($children[$root['device_id']])) {
            foreach ($children[$root['device_id']] as $child) {
                $tree[] = ['item' => $child, 'level' => 1];
            }
        }
    }
}

$health_map = ['good' => 'Tốt', 'warning' => 'Cảnh báo', 'broken' => 'Hỏng'];

$filename = "Bao-cao-kiem-tra-" . str_replace(' ', '-', $check['ten_du_an']) . "-" . date('d-m-Y', strtotime($check['check_date'])) . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head><meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8"></head>';
echo '<body>';

echo '<table border="1">';
echo '<tr><th colspan="7" style="font-size:16pt; background-color:#108042; color:white;">BÁO CÁO KIỂM TRA TÌNH TRẠNG HỆ THỐNG IT</th></tr>';
echo '<tr><td colspan="2"><b>Dự án:</b></td><td colspan="5">' . htmlspecialchars($check['ten_du_an']) . '</td></tr>';
echo '<tr><td colspan="2"><b>Ngày thực hiện:</b></td><td colspan="5">' . date('d/m/Y', strtotime($check['check_date'])) . '</td></tr>';
echo '<tr><td colspan="2"><b>Người kiểm tra:</b></td><td colspan="5">' . htmlspecialchars($check['checker_name']) . '</td></tr>';
echo '<tr><td colspan="2"><b>Đánh giá tổng quát:</b></td><td colspan="5">' . ($health_map[$check['overall_health']] ?? $check['overall_health']) . '</td></tr>';
echo '<tr><td colspan="7"></td></tr>';

echo '<tr style="background-color:#f2f2f2;">
        <th>Nhóm</th>
        <th>Tên thiết bị</th>
        <th>Sử dụng</th>
        <th>Sức khỏe</th>
        <th>S.Lượng</th>
        <th>Nguyên nhân lỗi</th>
        <th>Ghi chú thêm</th>
      </tr>';

foreach ($tree as $node) {
    $d = $node['item']; $lvl = $node['level'];
    $indent = ($lvl > 0) ? "    ↳ " : "";
    echo '<tr>';
    echo '<td>' . ($lvl == 0 ? htmlspecialchars($d['nhom_thiet_bi']) : "") . '</td>';
    echo '<td style="' . ($lvl == 0 ? 'font-weight:bold;' : 'color:#475569;') . '">' . $indent . htmlspecialchars($d['ten_thiet_bi']) . '</td>';
    echo '<td align="center">' . htmlspecialchars($d['status']) . '</td>';
    echo '<td align="center">' . ($health_map[$d['health_status']] ?? $d['health_status']) . '</td>';
    echo '<td align="center">' . $d['quantity'] . '</td>';
    echo '<td>' . htmlspecialchars($d['cause'] ?: "-") . '</td>';
    echo '<td>' . htmlspecialchars($d['notes'] ?: "-") . '</td>';
    echo '</tr>';
}

echo '<tr><td colspan="7"></td></tr>';
echo '<tr><td colspan="7"></td></tr>';

// Signatures Row
echo '<tr>
        <td colspan="3" align="center" style="height:150px; vertical-align:top;">
            <b>NHÂN VIÊN KIỂM TRA</b><br><br>';
            if($check['it_signature']) {
                echo '<img src="' . $check['it_signature'] . '" width="150" height="80"><br>';
            }
            echo '<b>' . htmlspecialchars($check['checker_name']) . '</b>
        </td>
        <td></td>
        <td colspan="3" align="center" style="height:150px; vertical-align:top;">
            <b>CÁN BỘ TẠI DỰ ÁN</b><br><br>';
            if($check['client_signature']) {
                echo '<img src="' . $check['client_signature'] . '" width="150" height="80"><br>';
            }
            echo '<b>' . htmlspecialchars($check['client_name'] ?: "") . '</b>
        </td>
      </tr>';

echo '</table>';
echo '</body></html>';
exit;
?>