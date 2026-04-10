<?php
// modules/it_activities/export_monthly.php
if (!isset($pdo)) {
    require_once __DIR__ . '/../../config/db.php';
}

$filter_month = $_GET['month'] ?? date('Y-m');
$year = date('Y', strtotime($filter_month));
$month = date('m', strtotime($filter_month));

$days_in_month = date('t', strtotime("$year-$month-01"));
$start_date = "$year-$month-01";
$end_date = "$year-$month-$days_in_month";

// 1. Fetch Car Inspections
$stmt = $pdo->prepare("SELECT ci.inspection_date as activity_date, p.ten_du_an, 'Kiểm xe' as type, u.fullname as checker
                      FROM car_inspections ci
                      JOIN projects p ON ci.project_id = p.id
                      JOIN users u ON ci.inspector_id = u.id
                      WHERE ci.inspection_date BETWEEN ? AND ?
                      ORDER BY activity_date ASC");
$stmt->execute([$start_date, $end_date]);
$inspections = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch Maintenance
$stmt = $pdo->prepare("SELECT m.ngay_lap_phieu as activity_date, p.ten_du_an, 'Bảo trì' as type, u.fullname as checker
                      FROM maintenance_logs m
                      JOIN projects p ON m.project_id = p.id
                      JOIN users u ON m.user_id = u.id
                      WHERE m.ngay_lap_phieu BETWEEN ? AND ?
                      ORDER BY activity_date ASC");
$stmt->execute([$start_date, $end_date]);
$maintenances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch Health Checks
$stmt = $pdo->prepare("SELECT h.check_date as activity_date, p.ten_du_an, 'Kiểm tra hệ thống' as type, u.fullname as checker
                      FROM it_system_health_checks h
                      JOIN projects p ON h.project_id = p.id
                      JOIN users u ON h.checked_by = u.id
                      WHERE h.check_date BETWEEN ? AND ?
                      ORDER BY activity_date ASC");
$stmt->execute([$start_date, $end_date]);
$health_checks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$all_activities = array_merge($inspections, $maintenances, $health_checks);
// Sắp xếp theo ngày thực hiện để xem tiến độ cả tháng
usort($all_activities, function($a, $b) {
    return strtotime($a['activity_date']) - strtotime($b['activity_date']);
});

$filename = "Ke-hoach-Hoat-dong-IT-Thang-" . $month . "-" . $year . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head><meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8"></head>';
echo '<body>';

echo '<table border="1">';
echo '<tr><th colspan="5" style="font-size:16pt; background-color:#108042; color:white;">KẾ HOẠCH HOẠT ĐỘNG IT - THÁNG ' . $month . '/' . $year . '</th></tr>';
echo '<tr><td colspan="5"></td></tr>';
echo '<tr style="background-color:#f2f2f2;">
        <th>STT</th>
        <th>Ngày thực hiện</th>
        <th>Dự án</th>
        <th>Loại công việc</th>
        <th>Người thực hiện</th>
      </tr>';

$stt = 1;
foreach ($all_activities as $act) {
    echo '<tr>';
    echo '<td align="center">' . $stt++ . '</td>';
    echo '<td align="center">' . date('d/m/Y', strtotime($act['activity_date'])) . '</td>';
    echo '<td>' . htmlspecialchars($act['ten_du_an']) . '</td>';
    echo '<td align="center">' . htmlspecialchars($act['type']) . '</td>';
    echo '<td>' . htmlspecialchars($act['checker']) . '</td>';
    echo '</tr>';
}

echo '</table>';
echo '</body></html>';
exit;
?>