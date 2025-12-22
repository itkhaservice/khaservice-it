<?php
// modules/maintenance/add.php

// Fetch projects for dropdown
$projects = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an")->fetchAll();

$preselected_device_id = $_GET['device_id'] ?? null;
$preselected_project_id = null;
$preselected_device_data = null;

if ($preselected_device_id) {
    $stmt = $pdo->prepare("SELECT id, project_id, ten_thiet_bi, ma_tai_san FROM devices WHERE id = ?");
    $stmt->execute([$preselected_device_id]);
    $preselected_device_data = $stmt->fetch();
    if ($preselected_device_data) {
        $preselected_project_id = $preselected_device_data['project_id'];
    }
}

// Hàm hỗ trợ gộp thời gian từ các ô nhập lẻ
function getFastDateTime($h, $m, $d, $mon, $y) {
    if (empty($h) || empty($m) || empty($d) || empty($mon) || empty($y)) return null;
    return sprintf("%04d-%02d-%02d %02d:%02d:00", $y, $mon, $d, $h, $m);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['project_id'])) {
        set_message('error', 'Vui lòng chọn Dự án.');
    } else {
        try {
            $arrival_time = getFastDateTime($_POST['arr_h'], $_POST['arr_m'], $_POST['arr_d'], $_POST['arr_mon'], $_POST['arr_y']);
            $completion_time = getFastDateTime($_POST['comp_h'], $_POST['comp_m'], $_POST['comp_d'], $_POST['comp_mon'], $_POST['comp_y']);
            $ngay_su_co = !empty($_POST['ngay_su_co']) ? $_POST['ngay_su_co'] : date('Y-m-d');

            $final_device_id = !empty($_POST['component_id']) ? $_POST['component_id'] : (!empty($_POST['device_id']) ? $_POST['device_id'] : null);

            $stmt = $pdo->prepare("INSERT INTO maintenance_logs 
                (user_id, project_id, device_id, custom_device_name, usage_time_manual, ngay_su_co, noi_dung, hu_hong, xu_ly, chi_phi, client_name, client_phone, arrival_time, completion_time, work_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'], 
                $_POST['project_id'],
                $final_device_id,
                !empty($_POST['custom_device_name']) ? $_POST['custom_device_name'] : null,
                !empty($_POST['usage_time_manual']) ? $_POST['usage_time_manual'] : null,
                $ngay_su_co,
                $_POST['noi_dung'] ?: null, 
                $_POST['hu_hong'],
                $_POST['xu_ly'],
                $_POST['chi_phi'] ?: 0,
                $_POST['client_name'],
                $_POST['client_phone'],
                $arrival_time,
                $completion_time,
                $_POST['work_type'] ?: 'Bảo trì / Sửa chữa'
            ]);
            set_message('success', 'Đã tạo phiếu công tác thành công!');
            header("Location: index.php?page=maintenance/history");
            exit;
        } catch (PDOException $e) {
            set_message('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-file-medical"></i> Tạo Phiếu Công tác</h2>
    <div class="header-actions">
        <a href="index.php?page=maintenance/history" class="btn btn-secondary"><i class="fas fa-times"></i> Hủy</a>
        <button type="submit" form="add-maintenance-form" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Phiếu</button>
    </div>
</div>

<form action="index.php?page=maintenance/add" method="POST" id="add-maintenance-form">
    <div class="form-grid">
        <!-- CỘT TRÁI -->
        <div class="form-column">
            <div class="card">
                <div class="dashboard-card-header"><h3><i class="fas fa-tools"></i> Nội dung Công việc</h3></div>
                
                <div class="form-group">
                    <label>Loại công việc <span class="required">*</span></label>
                    <div class="clearable-input-wrapper">
                        <input type="text" name="work_type" list="work_type_list" value="Bảo trì / Sửa chữa" required placeholder="Chọn hoặc nhập...">
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
                    <div class="clearable-input-wrapper"><textarea name="noi_dung" rows="3" placeholder="Yêu cầu của khách hàng..."></textarea><i class="fas fa-times-circle clear-input"></i></div>
                </div>

                <!-- Tách Nguyên nhân và Giải pháp -->
                <div class="form-group">
                    <label>Xác định Nguyên nhân</label>
                    <div class="clearable-input-wrapper"><textarea name="hu_hong" rows="4" placeholder="Nguyên nhân hư hỏng..."></textarea><i class="fas fa-times-circle clear-input"></i></div>
                </div>
                <div class="form-group">
                    <label>Giải pháp / Kết quả</label>
                    <div class="clearable-input-wrapper"><textarea name="xu_ly" rows="4" placeholder="Các bước xử lý..."></textarea><i class="fas fa-times-circle clear-input"></i></div>
                </div>
            </div>

            <div class="card mt-20">
                <div class="dashboard-card-header"><h3><i class="fas fa-user-clock"></i> Thời gian Thực hiện</h3></div>
                
                <!-- Tách Thời gian -->
                <div class="form-group">
                    <label>Thời điểm có mặt</label>
                    <div class="fast-time-container">
                        <div class="fast-time-group">
                            <div class="time-inputs">
                                <input type="number" name="arr_h" class="input-h auto-tab" maxlength="2" placeholder="HH">
                                <span class="sep">:</span>
                                <input type="number" name="arr_m" class="input-m auto-tab" maxlength="2" placeholder="mm">
                            </div>
                            <div class="date-inputs">
                                <input type="number" name="arr_d" class="input-d auto-tab" maxlength="2" placeholder="DD">
                                <span class="sep">/</span>
                                <input type="number" name="arr_mon" class="input-mon auto-tab" maxlength="2" placeholder="MM">
                                <span class="sep">/</span>
                                <input type="number" name="arr_y" class="input-y auto-tab" maxlength="4" placeholder="YYYY">
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary btn-now" onclick="fillNow('arr')">Nay</button>
                    </div>
                </div>

                <div class="form-group mt-20">
                    <label>Thời điểm hoàn thành</label>
                    <div class="fast-time-container">
                        <div class="fast-time-group">
                            <div class="time-inputs">
                                <input type="number" name="comp_h" class="input-h auto-tab" maxlength="2" placeholder="HH">
                                <span class="sep">:</span>
                                <input type="number" name="comp_m" class="input-m auto-tab" maxlength="2" placeholder="mm">
                            </div>
                            <div class="date-inputs">
                                <input type="number" name="comp_d" class="input-d auto-tab" maxlength="2" placeholder="DD">
                                <span class="sep">/</span>
                                <input type="number" name="comp_mon" class="input-mon auto-tab" maxlength="2" placeholder="MM">
                                <span class="sep">/</span>
                                <input type="number" name="comp_y" class="input-y auto-tab" maxlength="4" placeholder="YYYY">
                            </div>
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
                        <option value="">-- Chọn dự án --</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($preselected_project_id == $p['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['ten_du_an']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Phân loại</label>
                    <div class="radio-group-modern">
                        <label class="radio-item"><input type="radio" name="target_mode" value="device" checked onclick="toggleTargetMode('device')"><span class="radio-label"><i class="fas fa-laptop"></i> Thiết bị</span></label>
                        <label class="radio-item"><input type="radio" name="target_mode" value="custom" onclick="toggleTargetMode('custom')"><span class="radio-label"><i class="fas fa-keyboard"></i> Khác</span></label>
                    </div>
                </div>
                <div id="device-selection-area">
                    <div class="form-group"><label>Thiết bị</label><select id="device_id" name="device_id" onchange="loadComponents(this.value)" <?php echo !$preselected_device_id ? 'disabled' : ''; ?>><?php if($preselected_device_data): ?><option value="<?php echo $preselected_device_data['id']; ?>"><?php echo htmlspecialchars($preselected_device_data['ten_thiet_bi'] . ' (' . $preselected_device_data['ma_tai_san'] . ')'); ?></option><?php else: ?><option value="">-- Chọn dự án trước --</option><?php endif; ?></select></div>
                    <div class="form-group" id="component-group" style="display: none;"><label>Linh kiện</label><select id="component_id" name="component_id"></select></div>
                </div>
                <div id="custom-name-area" style="display: none;"><div class="form-group"><label>Tên Đối tượng</label><div class="clearable-input-wrapper"><input type="text" name="custom_device_name" placeholder="VD: Hệ thống mạng..."><i class="fas fa-times-circle clear-input"></i></div></div></div>
                <div class="form-group"><label>Ngày yêu cầu</label><input type="date" name="ngay_su_co" value="<?php echo date('Y-m-d'); ?>"></div>
                <div class="form-group"><label>Sử dụng (Ghi chú)</label><div class="clearable-input-wrapper"><input type="text" name="usage_time_manual" placeholder="VD: 2 năm..."><i class="fas fa-times-circle clear-input"></i></div></div>
            </div>
            <div class="card mt-20">
                <div class="dashboard-card-header"><h3><i class="fas fa-id-card"></i> Khách hàng</h3></div>
                <div class="form-group"><label>Người liên hệ</label><div class="clearable-input-wrapper"><input type="text" name="client_name" placeholder="Tên khách hàng..."><i class="fas fa-times-circle clear-input"></i></div></div>
                <div class="form-group"><label>Số điện thoại</label><div class="clearable-input-wrapper"><input type="text" name="client_phone" placeholder="SĐT..."><i class="fas fa-times-circle clear-input"></i></div></div>
                <div class="form-group"><label>Chi phí</label><div class="input-icon-wrapper"><input type="number" name="chi_phi" value="0"><span class="input-icon">VNĐ</span></div></div>
            </div>
        </div>
    </div>
</form>

<style>
/* Reset & Base */
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
.fast-time-group { display: flex; flex-direction: column; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; flex: 1; padding: 5px; gap: 5px; }
.time-inputs, .date-inputs { display: flex; align-items: center; justify-content: center; }
.fast-time-group input { border: none; background: transparent; text-align: center; font-size: 0.95rem; outline: none; padding: 5px 0; }
.input-h, .input-m, .input-d, .input-mon { width: 35px; }
.input-y { width: 55px; }
.sep { font-weight: bold; color: #94a3b8; margin: 0 2px; }
.btn-now { height: 42px; min-width: 60px; }

/* Radio Group */
.radio-group-modern { display: flex; gap: 10px; margin: 5px 0; }
.radio-item { flex: 1; cursor: pointer; }
.radio-item input { display: none; }
.radio-label { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.85rem; font-weight: 600; color: #64748b; }
.radio-item input:checked + .radio-label { background: #ecfdf5; border-color: var(--primary-color); color: var(--primary-color); }

@media (max-width: 768px) {
    .fast-time-container { flex-direction: column; align-items: stretch; }
    .btn-now { width: 100%; margin-top: 5px; }
    .radio-group-modern { flex-direction: column; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const pid = document.getElementById('project_id').value;
    if (pid && !document.getElementById('device_id').value) loadDevices(pid);
    const preDeviceId = "<?php echo $preselected_device_id; ?>";
    if (preDeviceId) loadComponents(preDeviceId);

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

function loadComponents(deviceId) {
    const compGroup = document.getElementById('component-group');
    const compSelect = document.getElementById('component_id');
    if (!deviceId) { compGroup.style.display = 'none'; return; }
    fetch(`api/get_devices_by_project.php?parent_id=${deviceId}`)
        .then(r => r.json()).then(data => {
            if (data && data.length > 0) {
                compSelect.innerHTML = '<option value="">-- Kiểm tra tổng thể --</option>';
                data.forEach(c => { compSelect.innerHTML += `<option value="${c.id}">${c.ten_thiet_bi} (${c.ma_tai_san})</option>`; });
                compGroup.style.display = 'block';
            } else { compGroup.style.display = 'none'; }
        });
}

function toggleTargetMode(mode) {
    document.getElementById('device-selection-area').style.display = (mode === 'device' ? 'block' : 'none');
    document.getElementById('custom-name-area').style.display = (mode === 'custom' ? 'block' : 'none');
}

function loadDevices(projectId) {
    const ds = document.getElementById('device_id');
    if (!projectId) { ds.disabled = true; ds.innerHTML = '<option value="">-- Chọn dự án trước --</option>'; return; }
    ds.innerHTML = '<option value="">Đang tải...</option>';
    fetch(`api/get_devices_by_project.php?project_id=${projectId}`).then(r => r.json()).then(data => {
        ds.innerHTML = '<option value="">-- Chọn thiết bị --</option>';
        data.forEach(d => { if (!d.parent_id) ds.innerHTML += `<option value="${d.id}">${d.ten_thiet_bi} (${d.ma_tai_san})</option>`; });
        ds.disabled = false;
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