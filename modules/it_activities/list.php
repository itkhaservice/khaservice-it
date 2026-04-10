<?php
// modules/it_activities/list.php
$pageTitle = "Kế hoạch Hoạt động IT";

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'it')) {
    set_message("error", "Bạn không có quyền truy cập trang này!");
    echo '<script>window.location.href = "index.php";</script>';
    exit;
}

// ==================================================
// HANDLE QUICK BATCH SCHEDULING
// ==================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_schedule'])) {
    $project_id = $_POST['project_id'];
    $selected_days = $_POST['days'] ?? []; // Array of day numbers
    $month_str = $_POST['schedule_month']; // Y-m format
    $checked_by = $_SESSION['user_id'];

    if ($project_id && !empty($selected_days)) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO it_system_health_checks (project_id, check_date, checked_by, overall_health) VALUES (?, ?, ?, 'good')");
            
            foreach ($selected_days as $day) {
                $check_date = $month_str . "-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                // Check if already exists to avoid duplicates
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM it_system_health_checks WHERE project_id = ? AND check_date = ?");
                $check_stmt->execute([$project_id, $check_date]);
                if ($check_stmt->fetchColumn() == 0) {
                    $stmt->execute([$project_id, $check_date, $checked_by]);
                }
            }
            $pdo->commit();
            set_message("success", "Đã lên lịch thành công cho " . count($selected_days) . " ngày!");
        } catch (Exception $e) {
            $pdo->rollBack();
            set_message("error", "Lỗi: " . $e->getMessage());
        }
    }
}

// ==================================================
// CALENDAR CALCULATION
// ==================================================
$filter_month = $_GET['month'] ?? date('Y-m');
$year = date('Y', strtotime($filter_month));
$month = date('m', strtotime($filter_month));

$first_day_ts = strtotime("$year-$month-01");
$days_in_month = date('t', $first_day_ts);
$start_day_of_week = date('N', $first_day_ts);
$end_date = "$year-$month-$days_in_month";
$start_date = "$year-$month-01";

// Fetch Activities
$stmt = $pdo->prepare("SELECT ci.id, ci.project_id, ci.inspection_date, p.ten_du_an, 'inspection' as type
                      FROM car_inspections ci
                      JOIN projects p ON ci.project_id = p.id
                      WHERE ci.inspection_date BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$inspections = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT m.id, m.project_id, m.ngay_lap_phieu as activity_date, p.ten_du_an, 'maintenance' as type
                      FROM maintenance_logs m
                      JOIN projects p ON m.project_id = p.id
                      WHERE m.ngay_lap_phieu BETWEEN ? AND ?
                      GROUP BY activity_date, m.project_id");
$stmt->execute([$start_date, $end_date]);
$maintenances = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT h.id, h.project_id, h.check_date as activity_date, p.ten_du_an, 'health_check' as type
                      FROM it_system_health_checks h
                      JOIN projects p ON h.project_id = p.id
                      WHERE h.check_date BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$health_checks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$activities = [];
foreach($inspections as $item) { $activities[$item['inspection_date']][] = $item; }
foreach($maintenances as $item) { $activities[$item['activity_date']][] = $item; }
foreach($health_checks as $item) { $activities[$item['activity_date']][] = $item; }

$prev_month = date('Y-m', strtotime("-1 month", $first_day_ts));
$next_month = date('Y-m', strtotime("+1 month", $first_day_ts));
$projects_list = $pdo->query("SELECT id, ten_du_an FROM projects WHERE deleted_at IS NULL ORDER BY ten_du_an")->fetchAll();
?>

<style>
.tag-item { display: block; padding: 2px 6px; margin-bottom: 3px; border-radius: 4px; font-size: 0.72rem; color: #fff; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: 0.2s; }
.tag-item:hover { transform: scale(1.02); color: #fff; filter: brightness(1.1); }
.tag-inspection { background-color: #0ea5e9; } 
.tag-maintenance { background-color: #f59e0b; }
.tag-health_check { background-color: #108042; }

.calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
.cal-day-header { background: #f8fafc; padding: 12px; text-align: center; font-weight: 700; font-size: 0.8rem; color: #64748b; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; }
.cal-cell { min-height: 110px; padding: 8px; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; background: #fff; transition: background 0.2s; }
.cal-cell:hover { background: #fdfdfd; }
.cal-cell.cal-weekend { background: #fcfdfd; }
.cal-cell.cal-today { background: #fffbeb; border: 2px solid #108042; }
.cal-day-num { font-weight: 800; margin-bottom: 6px; display: block; font-size: 0.9rem; color: #1e293b; }
.cal-empty { background: #f1f5f9; }

/* Batch Schedule Styles */
.days-selector { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; margin-top: 10px; }
.day-pill { border: 1px solid #e2e8f0; padding: 8px; text-align: center; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: 0.2s; }
.day-pill:hover { border-color: var(--primary-color); background: #f0fdf4; }
.day-pill.active { background: var(--primary-color); color: #fff; border-color: var(--primary-color); }
.day-pill input { display: none; }

.header-actions { display: flex; gap: 10px; align-items: center; }
.legend { display: flex; gap: 15px; margin-top: 15px; font-size: 0.8rem; font-weight: 600; }
.legend-item { display: flex; align-items: center; gap: 6px; color: #64748b; }
.dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
</style>

<div class="page-header">
    <h2><i class="fas fa-tasks"></i> Kế hoạch Hoạt động IT - <?= date('m/Y', $first_day_ts) ?></h2>
    <div class="header-actions">
        <button class="btn btn-primary btn-sm" onclick="openScheduleModal()">
            <i class="fas fa-calendar-plus"></i> Lên lịch nhanh
        </button>
        <div class="btn-group">
            <a href="index.php?page=it_activities/list&month=<?= $prev_month ?>" class="btn btn-secondary btn-sm"><i class="fas fa-chevron-left"></i></a>
            <a href="index.php?page=it_activities/list&month=<?= $next_month ?>" class="btn btn-secondary btn-sm"><i class="fas fa-chevron-right"></i></a>
        </div>
        <a href="index.php?page=it_activities/export_monthly&month=<?= $filter_month ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-file-export"></i> Xuất Excel Tháng
        </a>
    </div>
</div>

<div class="calendar-wrapper">
    <div class="card shadow-sm mb-3">
        <div class="calendar-grid">
            <div class="cal-day-header">Thứ 2</div><div class="cal-day-header">Thứ 3</div><div class="cal-day-header">Thứ 4</div>
            <div class="cal-day-header">Thứ 5</div><div class="cal-day-header">Thứ 6</div><div class="cal-day-header">Thứ 7</div><div class="cal-day-header">CN</div>

            <?php for($i = 1; $i < $start_day_of_week; $i++): ?><div class="cal-cell cal-empty"></div><?php endfor; ?>

            <?php for($d = 1; $d <= $days_in_month; $d++): 
                $current_date = sprintf("%s-%s-%02d", $year, $month, $d);
                $is_today = ($current_date == date('Y-m-d'));
                $day_activities = $activities[$current_date] ?? [];
                $day_of_week = date('N', strtotime($current_date));
            ?>
                <div class="cal-cell <?= ($day_of_week >= 6) ? 'cal-weekend' : '' ?> <?= $is_today ? 'cal-today' : '' ?>">
                    <span class="cal-day-num"><?= $d ?></span>
                    <div class="day-tags">
                        <?php foreach($day_activities as $act): ?>
                            <?php if($act['type'] == 'inspection'): ?>
                                <a href="index.php?page=car_inspections/list&date=<?= $current_date ?>" class="tag-item tag-inspection" title="Kiểm xe: <?= $act['ten_du_an'] ?>"><i class="fas fa-car"></i> KX: <?= $act['ten_du_an'] ?></a>
                            <?php elseif($act['type'] == 'maintenance'): ?>
                                <a href="index.php?page=maintenance/history&project_id=<?= $act['project_id'] ?>&date=<?= $current_date ?>" class="tag-item tag-maintenance" title="Bảo trì: <?= $act['ten_du_an'] ?>"><i class="fas fa-tools"></i> BT: <?= $act['ten_du_an'] ?></a>
                            <?php elseif($act['type'] == 'health_check'): ?>
                                <a href="index.php?page=it_activities/view_health_check&id=<?= $act['id'] ?>" class="tag-item tag-health_check" title="Kiểm tra: <?= $act['ten_du_an'] ?>"><i class="fas fa-heartbeat"></i> KT: <?= $act['ten_du_an'] ?></a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <a href="index.php?page=it_activities/add_health_check&date=<?= $current_date ?>" class="btn btn-outline-success btn-xs mt-1" style="font-size: 9px; padding: 0 4px;" title="Thêm Kiểm tra">
                            <i class="fas fa-plus"></i> Kiểm tra
                        </a>
                    </div>
                </div>
            <?php endfor; ?>
            
            <?php 
            $last_cell_count = ($start_day_of_week + $days_in_month - 1) % 7;
            if($last_cell_count > 0): for($i = $last_cell_count; $i < 7; $i++): echo '<div class="cal-cell cal-empty"></div>'; endfor; endif; ?>
        </div>
    </div>
    
    <div class="legend">
        <div class="legend-item"><span class="dot tag-inspection"></span> Kiểm xe</div>
        <div class="legend-item"><span class="dot tag-maintenance"></span> Bảo trì</div>
        <div class="legend-item"><span class="dot tag-health_check"></span> Kiểm tra hệ thống</div>
    </div>
</div>

<!-- MODAL: QUICK SCHEDULE -->
<div id="scheduleModal" class="modal-custom">
    <div class="modal-content-custom" style="width: 500px;">
        <div class="modal-header-custom">
            <h3><i class="fas fa-calendar-alt"></i> Lên lịch hoạt động nhanh</h3>
            <span class="close-modal" onclick="closeScheduleModal()">&times;</span>
        </div>
        <form action="" method="POST" id="quick-schedule-form">
            <input type="hidden" name="quick_schedule" value="1">
            <input type="hidden" name="schedule_month" value="<?= $filter_month ?>">
            <div class="modal-body-custom">
                <div class="form-group mb-3">
                    <label style="font-weight:700;">1. Chọn Dự án</label>
                    <select name="project_id" class="form-control" required>
                        <option value="">-- Chọn dự án --</option>
                        <?php foreach($projects_list as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['ten_du_an']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <label style="font-weight:700;">2. Chọn các ngày đi kiểm tra (Tháng <?= date('m/Y', $first_day_ts) ?>)</label>
                <div class="days-selector">
                    <?php for($d = 1; $d <= $days_in_month; $d++): 
                        $dw = date('N', strtotime("$year-$month-$d"));
                        $is_we = ($dw >= 6);
                    ?>
                        <label class="day-pill <?= $is_we ? 'bg-light' : '' ?>" id="pill-<?= $d ?>">
                            <?= $d ?>
                            <input type="checkbox" name="days[]" value="<?= $d ?>" onchange="togglePill(<?= $d ?>, this)">
                        </label>
                    <?php endfor; ?>
                </div>
                <small class="text-muted mt-2 d-block">* Click vào các số ngày để chọn nhanh.</small>
            </div>
            <div class="modal-footer-custom">
                <button type="button" class="btn btn-secondary" onclick="closeScheduleModal()">Hủy</button>
                <button type="submit" class="btn btn-primary">TẠO LỊCH NGAY</button>
            </div>
        </form>
    </div>
</div>

<script>
function openScheduleModal() { document.getElementById('scheduleModal').style.display = 'block'; }
function closeScheduleModal() { document.getElementById('scheduleModal').style.display = 'none'; }
function togglePill(day, cb) {
    const pill = document.getElementById('pill-' + day);
    if(cb.checked) pill.classList.add('active');
    else pill.classList.remove('active');
}
window.onclick = (e) => { if (e.target == document.getElementById('scheduleModal')) closeScheduleModal(); }
</script>

<style>
.modal-custom { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
.modal-content-custom { background: #fff; margin: 5vh auto; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; animation: modalPop 0.25s; }
@keyframes modalPop { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.modal-header-custom { background: var(--primary-color); color: #fff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
.modal-header-custom h3 { margin: 0; font-size: 1.1rem; font-weight: 700; }
.close-modal { cursor: pointer; font-size: 24px; opacity: 0.8; }
.modal-body-custom { padding: 20px; }
.modal-footer-custom { padding: 15px 20px; border-top: 1px solid #e2e8f0; text-align: right; background: #f8fafc; }
</style>