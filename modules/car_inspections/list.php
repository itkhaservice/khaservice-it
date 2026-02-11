<?php
// modules/car_inspections/list.php
$pageTitle = "Lịch kiểm tra xe - Audit";

// Check permissions
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'it')) {
    set_message("Bạn không có quyền truy cập trang này!", "error");
    echo '<script>window.location.href = "index.php";</script>';
    exit;
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

// Fetch inspections with details
$stmt = $pdo->prepare("SELECT ci.*, p.ten_du_an, u.fullname as inspector_name 
                      FROM car_inspections ci
                      JOIN projects p ON ci.project_id = p.id
                      JOIN users u ON ci.inspector_id = u.id
                      WHERE ci.inspection_date BETWEEN ? AND ?
                      ORDER BY ci.inspection_time ASC");
$stmt->execute(["$year-$month-01", "$year-$month-$days_in_month"]);
$inspections_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$inspections = [];
foreach($inspections_raw as $item) {
    $inspections[$item['inspection_date']][] = $item;
}

$prev_month = date('Y-m', strtotime("-1 month", $first_day_ts));
$next_month = date('Y-m', strtotime("+1 month", $first_day_ts));

$projects_list = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h2><i class="fas fa-calendar-check"></i> Lịch Kiểm Tra Xe - <?= date('m/Y', $first_day_ts) ?></h2>
    <div class="header-actions">
        <div class="btn-group">
            <a href="index.php?page=car_inspections/list&month=<?= $prev_month ?>" class="btn btn-secondary btn-sm"><i class="fas fa-chevron-left"></i></a>
            <a href="index.php?page=car_inspections/list&month=<?= date('Y-m') ?>" class="btn btn-light btn-sm">Hiện tại</a>
            <a href="index.php?page=car_inspections/list&month=<?= $next_month ?>" class="btn btn-secondary btn-sm"><i class="fas fa-chevron-right"></i></a>
        </div>
    </div>
</div>

<div class="calendar-wrapper">
    <div class="card calendar-card shadow-sm">
        <div class="calendar-grid">
            <div class="cal-day-header">Thứ 2</div>
            <div class="cal-day-header">Thứ 3</div>
            <div class="cal-day-header">Thứ 4</div>
            <div class="cal-day-header">Thứ 5</div>
            <div class="cal-day-header">Thứ 6</div>
            <div class="cal-day-header cal-weekend">Thứ 7</div>
            <div class="cal-day-header cal-weekend">CN</div>

            <?php for($i = 1; $i < $start_day_of_week; $i++): ?>
                <div class="cal-cell cal-empty"></div>
            <?php endfor; ?>

            <?php for($d = 1; $d <= $days_in_month; $d++): 
                $current_date = sprintf("%s-%s-%02d", $year, $month, $d);
                $is_today = ($current_date == date('Y-m-d'));
                $day_inspections = $inspections[$current_date] ?? [];
                $is_weekend = (date('N', strtotime($current_date)) >= 6);
            ?>
                <div class="cal-cell <?= $is_today ? 'cal-today' : '' ?> <?= $is_weekend ? 'cal-weekend-bg' : '' ?>" onclick="openAddModal('<?= $current_date ?>')">
                    <div class="cal-date-num"><?= $d ?></div>
                    <div class="cal-events">
                                                <?php foreach($day_inspections as $ins): 
                                                    $status_label = "";
                                                    $status_class = "event-pending";
                                                    if($ins['status'] == 'completed') {
                                                        if($ins['violation_count'] == 0) {
                                                            $status_label = "ĐẠT";
                                                            $status_class = "event-success";
                                                        } else {
                                                            $status_label = "CHƯA ĐẠT (" . $ins['violation_count'] . ")";
                                                            $status_class = "event-danger";
                                                        }
                                                    }
                                                ?>                        <div class="cal-event <?= $status_class ?>" onclick="event.stopPropagation(); openDetailModal(<?= htmlspecialchars(json_encode($ins)) ?>)">
                            <div class="event-title"><?= htmlspecialchars($ins['ten_du_an']) ?></div>
                            <?php if($status_label): ?><div class="event-status-line"><?= $status_label ?></div><?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endfor; ?>

            <?php 
                $total_slots = $start_day_of_week - 1 + $days_in_month;
                $remaining = (7 - ($total_slots % 7)) % 7;
                for($i = 0; $i < $remaining; $i++): 
            ?>
                <div class="cal-cell cal-empty"></div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- MODAL: ADD INSPECTION -->
<div id="addModal" class="f-modal">
    <div class="f-modal-content animated zoomIn">
        <div class="f-modal-header">
            <h3><i class="fas fa-plus-circle"></i> Đặt lịch kiểm tra</h3>
            <span class="f-close" onclick="closeModal('addModal')">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="quick_add" value="1">
            <input type="hidden" name="inspection_date" id="modal_date">
            <div class="f-modal-body">
                <div class="form-group-f mb-3">
                    <label>Ngày đã chọn</label>
                    <input type="text" id="modal_date_display" class="form-control" readonly style="background: #f8fafc; font-weight: bold;">
                </div>
                <div class="form-group-f mb-3">
                    <label>Dự án kiểm tra <span class="text-danger">*</span></label>
                    <select name="project_id" class="form-control" required>
                        <option value="">-- Chọn dự án --</option>
                        <?php foreach($projects_list as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['ten_du_an']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group-f mb-3">
                    <label>Giờ kiểm tra <span class="text-danger">*</span></label>
                    <input type="time" name="inspection_time" class="form-control" value="09:00" required>
                </div>
            </div>
            <div class="f-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu lịch</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: VIEW DETAILS -->
<div id="detailModal" class="f-modal">
    <div class="f-modal-content animated zoomIn">
        <div class="f-modal-header">
            <h3><i class="fas fa-info-circle"></i> Chi tiết kiểm tra</h3>
            <span class="f-close" onclick="closeModal('detailModal')">&times;</span>
        </div>
        <div class="f-modal-body">
            <div id="detail_content">
                <!-- Content injected by JS -->
            </div>
        </div>
        <div class="f-modal-footer" id="detail_footer">
            <!-- Buttons injected by JS -->
        </div>
    </div>
</div>

<style>
    .calendar-wrapper {
        margin: 0 auto;
    }
    .calendar-card { 
        padding: 0 !important; 
        border: 1px solid #e2e8f0; 
        border-radius: 12px; 
        overflow: hidden;
        box-shadow: 0 4px 20px -5px rgba(0,0,0,0.05) !important;
    }
    .calendar-grid { 
        display: grid; 
        grid-template-columns: repeat(7, 1fr); 
        background: #e2e8f0; 
        gap: 1px; 
    }
    .cal-day-header { 
        background: #f8fafc; 
        padding: 12px 10px; 
        text-align: center; 
        font-weight: 800; 
        font-size: 0.75rem; 
        color: #64748b; 
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .cal-cell { 
        min-height: 100px; 
        background: #fff; 
        padding: 8px; 
        cursor: pointer; 
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); 
        position: relative;
    }
    .cal-cell:hover { 
        background: #fdfdfd; 
        z-index: 2;
        box-shadow: inset 0 0 0 2px var(--primary-color);
    }
    .cal-empty { background: #f8fafc; cursor: default; }
    .cal-weekend-bg { background: #fffcfc; }
    .cal-weekend { color: #ef4444; }
    
    .cal-date-num { 
        font-weight: 800; 
        margin-bottom: 8px; 
        color: #94a3b8; 
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 50%;
    }

    /* TODAY HIGHLIGHT */
    .cal-today {
        background: #f0fdf4;
    }
    .cal-today .cal-date-num {
        background: var(--primary-color);
        color: white;
        box-shadow: 0 4px 10px rgba(36, 162, 92, 0.3);
    }
    .cal-today:hover {
        background: #e7f9ee;
    }

    .cal-events { display: flex; flex-direction: column; gap: 4px; }
    
    .cal-event { 
        font-size: 0.72rem; 
        padding: 5px 8px; 
        border-radius: 6px; 
        display: flex; 
        flex-direction: column;
        align-items: flex-start;
        gap: 2px;
        border-left: 3px solid transparent; 
        transition: transform 0.1s;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .cal-event:hover { transform: translateY(-1px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    
    .event-pending { background: #f1f5f9; color: #475569; border-color: #94a3b8; }
    .event-success { background: #dcfce7; color: #166534; border-color: #108042; }
    .event-danger { background: #fee2e2; color: #991b1b; border-color: #ef4444; }
    
    .event-title { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 700; width: 100%; }
    .event-status-line { font-size: 0.6rem; font-weight: 900; text-transform: uppercase; opacity: 0.8; }

    /* Modal Premium Styles */
    .f-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
    .f-modal-content { background: white; margin: 5% auto; padding: 0; width: 90%; max-width: 500px; border-radius: 16px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
    .f-modal-header { padding: 20px 25px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .f-modal-header h3 { margin: 0; font-size: 1.2rem; color: #1e293b; font-weight: 800; display: flex; align-items: center; gap: 10px; }
    .f-modal-header h3 i { color: var(--primary-color); }
    .f-close { cursor: pointer; font-size: 1.5rem; color: #94a3b8; transition: color 0.2s; }
    .f-close:hover { color: #ef4444; }
    .f-modal-body { padding: 25px; }
    .f-modal-footer { padding: 15px 25px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 12px; }

    .form-group-f { display: flex; flex-direction: column; gap: 8px; margin-bottom: 18px; }
    .form-group-f label { font-weight: 700; font-size: 0.8rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
    .form-group-f input, .form-group-f select { 
        height: 40px; padding: 0 15px; border: 1.5px solid #e2e8f0; border-radius: 10px; 
        font-size: 0.95rem; width: 100%; transition: all 0.2s; font-family: inherit;
    }
    .form-group-f input:focus, .form-group-f select:focus { border-color: var(--primary-color); outline: none; box-shadow: 0 0 0 4px rgba(36, 162, 92, 0.1); }
    .form-group-f input[readonly] { background-color: #f1f5f9; border-style: dashed; }

    .detail-item { margin-bottom: 18px; }
    .detail-label { font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px; }
    .detail-value { font-size: 1.05rem; color: #1e293b; font-weight: 700; }
    
    @media (max-width: 768px) {
        .cal-day-header { font-size: 0.65rem; padding: 10px 5px; }
        .cal-cell { min-height: 80px; }
        .event-badge { display: none; }
    }
</style>

<script>
function openAddModal(date) {
    document.getElementById('modal_date').value = date;
    const d = new Date(date);
    document.getElementById('modal_date_display').value = d.toLocaleDateString('vi-VN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    document.getElementById('addModal').style.display = 'block';
}

function openDetailModal(ins) {
    const isCompleted = (ins.status === 'completed');
    const resultText = isCompleted ? (ins.violation_count == 0 ? '<span class="text-success">ĐẠT</span>' : '<span class="text-danger">CHƯA ĐẠT (' + ins.violation_count + ')</span>') : '<span class="text-muted">Chưa kiểm tra</span>';
    
    let html = `
        <div class="detail-item">
            <div class="detail-label">Dự án</div>
            <div class="detail-value text-primary">Chung cư ${ins.ten_du_an}</div>
        </div>
        <div class="row">
            <div class="col-6 detail-item">
                <div class="detail-label">Thời gian</div>
                <div class="detail-value">${ins.inspection_time.substring(0,5)} - ${ins.inspection_date.split('-').reverse().join('/')}</div>
            </div>
            <div class="col-6 detail-item">
                <div class="detail-label">Người phụ trách</div>
                <div class="detail-value">${ins.inspector_name}</div>
            </div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Kết quả Audit</div>
            <div class="detail-value">${resultText}</div>
        </div>
    `;

    document.getElementById('detail_content').innerHTML = html;
    
    let footerHtml = `
        <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('detailModal')">Đóng</button>
        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteInspection(${ins.id})"><i class="fas fa-trash-alt"></i> Xóa lịch</button>
        <a href="index.php?page=car_inspections/edit&id=${ins.id}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Chỉnh sửa</a>
    `;
    
    if(isCompleted) {
        footerHtml = `
            <a href="index.php?page=car_inspections/print&id=${ins.id}" target="_blank" class="btn btn-success btn-sm"><i class="fas fa-print"></i> In Biên bản</a>
            ` + footerHtml;
    }

    document.getElementById('detail_footer').innerHTML = footerHtml;
    document.getElementById('detailModal').style.display = 'block';
}

function confirmDeleteInspection(id) {
    window.showCustomConfirm(
        'Bạn có chắc chắn muốn xóa (gỡ) dự án này khỏi lịch kiểm tra không? Hành động này không thể hoàn tác.',
        'Xác nhận gỡ lịch',
        function() {
            window.location.href = `index.php?page=car_inspections/delete&id=${id}`;
        }
    );
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

window.onclick = function(event) {
    if (event.target.className === 'f-modal') {
        event.target.style.display = "none";
    }
}
</script>
