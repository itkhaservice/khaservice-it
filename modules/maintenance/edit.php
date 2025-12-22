<?php
// modules/maintenance/edit.php

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: index.php?page=maintenance/history"); exit; }

$stmt = $pdo->prepare("SELECT * FROM maintenance_logs WHERE id = ?");
$stmt->execute([$id]);
$log = $stmt->fetch();

if (!$log) { header("Location: index.php?page=maintenance/history"); exit; }

$projects = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an")->fetchAll();
$current_project_id = $log['project_id'];

function getFastDateTime($h, $m, $d, $mon, $y) {
    if (empty($h) || empty($m) || empty($d) || empty($mon) || empty($y)) return null;
    return sprintf("%04d-%02d-%02d %02d:%02d:00", $y, $mon, $d, $h, $m);
}

function parseFastDateTime($dt) {
    if (!$dt) return ['h'=>'', 'm'=>'', 'd'=>'', 'mon'=>'', 'y'=>''];
    $ts = strtotime($dt);
    return [
        'h' => date('H', $ts), 'm' => date('i', $ts),
        'd' => date('d', $ts), 'mon' => date('m', $ts), 'y' => date('Y', $ts)
    ];
}

$arr = parseFastDateTime($log['arrival_time']);
$comp = parseFastDateTime($log['completion_time']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $arrival_time = getFastDateTime($_POST['arr_h'], $_POST['arr_m'], $_POST['arr_d'], $_POST['arr_mon'], $_POST['arr_y']);
        $completion_time = getFastDateTime($_POST['comp_h'], $_POST['comp_m'], $_POST['comp_d'], $_POST['comp_mon'], $_POST['comp_y']);

        $stmt = $pdo->prepare("UPDATE maintenance_logs SET 
            project_id=?, device_id=?, custom_device_name=?, usage_time_manual=?, ngay_su_co=?, noi_dung=?, hu_hong=?, xu_ly=?, chi_phi=?, 
            client_name=?, client_phone=?, arrival_time=?, completion_time=?, work_type=? WHERE id=?");
        $stmt->execute([
            $_POST['project_id'], !empty($_POST['device_id']) ? $_POST['device_id'] : null,
            !empty($_POST['custom_device_name']) ? $_POST['custom_device_name'] : null,
            !empty($_POST['usage_time_manual']) ? $_POST['usage_time_manual'] : null,
            $_POST['ngay_su_co'] ?: date('Y-m-d'), 
            $_POST['noi_dung'] ?: null, 
            $_POST['hu_hong'], $_POST['xu_ly'], $_POST['chi_phi'] ?: 0,
            $_POST['client_name'], $_POST['client_phone'], $arrival_time, $completion_time, 
            $_POST['work_type'] ?: 'Bảo trì / Sửa chữa', $id
        ]);
        set_message('success', 'Cập nhật phiếu thành công!');
        header("Location: index.php?page=maintenance/view&id=" . $id);
        exit;
    } catch (PDOException $e) { set_message('error', 'Lỗi: ' . $e->getMessage()); }
}
?>

<div class="page-header">
    <h2><i class="fas fa-edit"></i> Chỉnh sửa Phiếu Công tác #<?php echo $id; ?></h2>
    <div class="header-actions">
        <a href="index.php?page=maintenance/view&id=<?php echo $id; ?>" class="btn btn-secondary"><i class="fas fa-times"></i> Hủy</a>
        <button type="submit" form="edit-maintenance-form" class="btn btn-primary"><i class="fas fa-save"></i> Cập nhật</button>
    </div>
</div>

<form action="index.php?page=maintenance/edit&id=<?php echo $id; ?>" method="POST" id="edit-maintenance-form">
    <div class="form-grid">
        <!-- CỘT TRÁI: NỘI DUNG -->
        <div class="form-column">
            <div class="card">
                <div class="dashboard-card-header"><h3><i class="fas fa-tools"></i> Nội dung Công việc</h3></div>
                
                <div class="form-group">
                    <label>Loại công việc <span class="required">*</span></label>
                    <div class="clearable-input-wrapper">
                        <input type="text" name="work_type" list="work_type_list" value="<?php echo htmlspecialchars($log['work_type']); ?>" required>
                        <i class="fas fa-times-circle clear-input"></i>
                    </div>
                    <datalist id="work_type_list">
                        <option value="Kiểm tra thực tế bãi giữ xe">
                        <option value="Kiểm tra máy tính kế toán">
                        <option value="Kiểm tra máy tính hệ thống xe">
                        <option value="Kiểm tra bảo trì hệ thống xe">
                    </datalist>
                </div>

                <div class="form-group">
                    <label>Mô tả sự cố / Yêu cầu</label>
                    <div class="clearable-input-wrapper">
                        <textarea name="noi_dung" rows="3"><?php echo htmlspecialchars($log['noi_dung']); ?></textarea>
                        <i class="fas fa-times-circle clear-input"></i>
                    </div>
                </div>

                <!-- Tách Nguyên nhân và Giải pháp -->
                <div class="form-group">
                    <label>Xác định Nguyên nhân</label>
                    <div class="clearable-input-wrapper">
                        <textarea name="hu_hong" rows="4"><?php echo htmlspecialchars($log['hu_hong']); ?></textarea>
                        <i class="fas fa-times-circle clear-input"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label>Giải pháp / Kết quả</label>
                    <div class="clearable-input-wrapper">
                        <textarea name="xu_ly" rows="4"><?php echo htmlspecialchars($log['xu_ly']); ?></textarea>
                        <i class="fas fa-times-circle clear-input"></i>
                    </div>
                </div>
            </div>

            <div class="card mt-20">
                <div class="dashboard-card-header"><h3><i class="fas fa-user-clock"></i> Thời gian Thực hiện</h3></div>
                
                <div class="form-group">
                    <label>Thời điểm có mặt</label>
                    <div class="fast-time-container">
                        <div class="fast-time-group">
                            <input type="number" name="arr_h" class="input-h auto-tab" maxlength="2" placeholder="HH" value="<?php echo $arr['h']; ?>">
                            <span class="sep">:</span>
                            <input type="number" name="arr_m" class="input-m auto-tab" maxlength="2" placeholder="mm" value="<?php echo $arr['m']; ?>">
                            <span class="sep" style="margin: 0 8px;">&nbsp;</span>
                            <input type="number" name="arr_d" class="input-d auto-tab" maxlength="2" placeholder="DD" value="<?php echo $arr['d']; ?>">
                            <span class="sep">/</span>
                            <input type="number" name="arr_mon" class="input-mon auto-tab" maxlength="2" placeholder="MM" value="<?php echo $arr['mon']; ?>">
                            <span class="sep">/</span>
                            <input type="number" name="arr_y" class="input-y auto-tab" maxlength="4" placeholder="YYYY" value="<?php echo $arr['y']; ?>">
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary btn-now" onclick="fillNow('arr')">Nay</button>
                    </div>
                </div>

                <div class="form-group mt-20">
                    <label>Thời điểm hoàn thành</label>
                    <div class="fast-time-container">
                        <div class="fast-time-group">
                            <input type="number" name="comp_h" class="input-h auto-tab" maxlength="2" placeholder="HH" value="<?php echo $comp['h']; ?>">
                            <span class="sep">:</span>
                            <input type="number" name="comp_m" class="input-m auto-tab" maxlength="2" placeholder="mm" value="<?php echo $comp['m']; ?>">
                            <span class="sep" style="margin: 0 8px;">&nbsp;</span>
                            <input type="number" name="comp_d" class="input-d auto-tab" maxlength="2" placeholder="DD" value="<?php echo $comp['d']; ?>">
                            <span class="sep">/</span>
                            <input type="number" name="comp_mon" class="input-mon auto-tab" maxlength="2" placeholder="MM" value="<?php echo $comp['mon']; ?>">
                            <span class="sep">/</span>
                            <input type="number" name="comp_y" class="input-y auto-tab" maxlength="4" placeholder="YYYY" value="<?php echo $comp['y']; ?>">
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary btn-now" onclick="fillNow('comp')">Nay</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- CỘT PHẢI -->
        <div class="form-column">
            <div class="card">
                <div class="dashboard-card-header"><h3><i class="fas fa-map-marker-alt"></i> Đối tượng</h3></div>
                <div class="form-group">
                    <label>Dự án <span class="required">*</span></label>
                    <select name="project_id" id="project_id" required onchange="loadDevices(this.value)">
                        <?php foreach ($projects as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($current_project_id == $p['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['ten_du_an']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Phân loại</label>
                    <div class="radio-group-modern">
                        <label class="radio-item"><input type="radio" name="target_mode" value="device" <?php echo !empty($log['device_id']) ? 'checked' : ''; ?> onclick="toggleTargetMode('device')"><span class="radio-label"><i class="fas fa-laptop"></i> Thiết bị</span></label>
                        <label class="radio-item"><input type="radio" name="target_mode" value="custom" <?php echo empty($log['device_id']) ? 'checked' : ''; ?> onclick="toggleTargetMode('custom')"><span class="radio-label"><i class="fas fa-keyboard"></i> Khác</span></label>
                    </div>
                </div>
                <div id="device-selection-area" style="<?php echo !empty($log['device_id']) ? 'display:block' : 'display:none'; ?>">
                    <div class="form-group"><label>Thiết bị</label><select id="device_id" name="device_id"><option value="">-- Đang tải... --</option></select></div>
                </div>
                <div id="custom-name-area" style="<?php echo empty($log['device_id']) ? 'display:block' : 'display:none'; ?>">
                    <div class="form-group"><label>Tên Đối tượng</label><div class="clearable-input-wrapper"><input type="text" name="custom_device_name" value="<?php echo htmlspecialchars($log['custom_device_name']); ?>"><i class="fas fa-times-circle clear-input"></i></div></div>
                </div>
                <div class="form-group"><label>Ngày yêu cầu</label><input type="date" name="ngay_su_co" value="<?php echo $log['ngay_su_co']; ?>"></div>
                <div class="form-group"><label>Sử dụng (Ghi chú)</label><div class="clearable-input-wrapper"><input type="text" name="usage_time_manual" value="<?php echo htmlspecialchars($log['usage_time_manual']); ?>"><i class="fas fa-times-circle clear-input"></i></div></div>
            </div>

            <div class="card mt-20">
                <div class="dashboard-card-header"><h3><i class="fas fa-id-card"></i> Khách hàng</h3></div>
                <div class="form-group"><label>Người liên hệ</label><div class="clearable-input-wrapper"><input type="text" name="client_name" value="<?php echo htmlspecialchars($log['client_name']); ?>"><i class="fas fa-times-circle clear-input"></i></div></div>
                <div class="form-group"><label>Số điện thoại</label><div class="clearable-input-wrapper"><input type="text" name="client_phone" value="<?php echo htmlspecialchars($log['client_phone']); ?>"><i class="fas fa-times-circle clear-input"></i></div></div>
                <div class="form-group"><label>Chi phí</label><div class="input-icon-wrapper"><input type="number" name="chi_phi" value="<?php echo $log['chi_phi'] ?: 0; ?>"><span class="input-icon">VNĐ</span></div></div>
            </div>
        </div>
    </div>
</form>

<style>
* { box-sizing: border-box; }
.form-grid { max-width: 100%; overflow-x: hidden; }

/* Clearable Input */
.clearable-input-wrapper { position: relative; display: flex; align-items: center; width: 100%; }
.clearable-input-wrapper input, .clearable-input-wrapper textarea { padding-right: 35px !important; width: 100%; }
.clear-input { position: absolute; right: 10px; color: #cbd5e1; cursor: pointer; display: none; z-index: 10; font-size: 1.1rem; }
.clearable-input-wrapper:hover .clear-input { color: #94a3b8; }
.clear-input:hover { color: #ef4444 !important; }
.clearable-input-wrapper textarea + .clear-input { top: 12px; }

/* Fast Time Styles */
.fast-time-container { display: flex; align-items: center; gap: 10px; width: 100%; }
.fast-time-group { display: flex; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; flex: 1; padding: 2px 8px; justify-content: center; overflow: hidden; }
.fast-time-group input { border: none; background: transparent; text-align: center; font-size: 0.95rem; outline: none; padding: 8px 0; }
.input-h, .input-m, .input-d, .input-mon { width: 32px; }
.input-y { width: 50px; }
.sep { font-weight: 700; color: #94a3b8; margin: 0 1px; }
.btn-now { height: 42px; min-width: 60px; }

/* Radio Group */
.radio-group-modern { display: flex; gap: 10px; margin: 5px 0; }
.radio-item { flex: 1; cursor: pointer; }
.radio-item input { display: none; }
.radio-label { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.85rem; font-weight: 600; color: #64748b; }
.radio-item input:checked + .radio-label { background: #ecfdf5; border-color: var(--primary-color); color: var(--primary-color); }

@media (max-width: 768px) {
    .fast-time-container { flex-direction: row; align-items: center; } 
    .fast-time-group { padding: 2px 4px; }
    .btn-now { width: auto; margin-top: 0; min-width: 50px; }
    .form-row-responsive { grid-template-columns: 1fr; gap: 15px; }
    .radio-group-modern { flex-direction: column; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    loadDevices(document.getElementById('project_id').value, "<?php echo $log['device_id']; ?>");
    
    // Clear Logic
    document.querySelectorAll('.clearable-input-wrapper').forEach(wrapper => {
        const input = wrapper.querySelector('input, textarea');
        const btn = wrapper.querySelector('.clear-input');
        const toggle = () => btn.style.display = input.value.length > 0 ? 'block' : 'none';
        input.addEventListener('input', toggle);
        btn.addEventListener('click', () => { input.value = ''; toggle(); input.focus(); });
        toggle();
    });
});

function fillNow(prefix) {
    const now = new Date();
    document.querySelector(`input[name="${prefix}_h"]`).value = String(now.getHours()).padStart(2, '0');
    document.querySelector(`input[name="${prefix}_m"]`).value = String(now.getMinutes()).padStart(2, '0');
    document.querySelector(`input[name="${prefix}_d"]`).value = String(now.getDate()).padStart(2, '0');
    document.querySelector(`input[name="${prefix}_mon"]`).value = String(now.getMonth() + 1).padStart(2, '0');
    document.querySelector(`input[name="${prefix}_y"]`).value = now.getFullYear();
    document.querySelector(`input[name="${prefix}_h"]`).dispatchEvent(new Event('input'));
}

function toggleTargetMode(mode) {
    document.getElementById('device-selection-area').style.display = (mode === 'device' ? 'block' : 'none');
    document.getElementById('custom-name-area').style.display = (mode === 'custom' ? 'block' : 'none');
}

function loadDevices(projectId, selectedId = "") {
    const ds = document.getElementById('device_id');
    if (!projectId) return;
    ds.innerHTML = '<option value="">Đang tải...</option>';
    fetch(`api/get_devices_by_project.php?project_id=${projectId}`)
        .then(r => r.json()).then(data => {
            ds.innerHTML = '<option value="">-- Chọn thiết bị --</option>';
            data.forEach(d => { 
                if (!d.parent_id) {
                    const sel = (d.id == selectedId) ? 'selected' : '';
                    ds.innerHTML += `<option value="${d.id}" ${sel}>${d.ten_thiet_bi} (${d.ma_tai_san})</option>`; 
                }
            });
        });
}

document.querySelectorAll('.auto-tab').forEach(input => {
    input.addEventListener('input', function() {
        if (this.value.length >= this.maxLength) {
            let next = this.nextElementSibling;
            while (next && next.tagName !== 'INPUT') next = next.nextElementSibling;
            if (next) next.focus();
        }
    });
});
</script>