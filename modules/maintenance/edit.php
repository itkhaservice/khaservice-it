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

$staff_list = [];
if (isAdmin()) {
    $staff_list = $pdo->query("SELECT id, fullname, username FROM users WHERE deleted_at IS NULL ORDER BY fullname ASC")->fetchAll();
}

function getFastDateTime($h, $m, $d, $mon, $y) {
    if (empty($h) || empty($m) || empty($d) || empty($mon) || empty($y)) return null;
    return sprintf("%04d-%02d-%02d %02d:%02d:00", $y, $mon, $d, $h, $m);
}

function parseFastDateTime($dt) {
    if (!$dt) return ['h'=>'', 'm'=>'', 'd'=>'', 'mon'=>'', 'y'=>''];
    $ts = strtotime($dt);
    return ['h' => date('H', $ts), 'm' => date('i', $ts), 'd' => date('d', $ts), 'mon' => date('m', $ts), 'y' => date('Y', $ts)];
}

$arr = parseFastDateTime($log['arrival_time']);
$comp = parseFastDateTime($log['completion_time']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $arrival_time = getFastDateTime($_POST['arr_h'], $_POST['arr_m'], $_POST['arr_d'], $_POST['arr_mon'], $_POST['arr_y']);
        $completion_time = getFastDateTime($_POST['comp_h'], $_POST['comp_m'], $_POST['comp_d'], $_POST['comp_mon'], $_POST['comp_y']);
        $assigned_user_id = (isAdmin() && !empty($_POST['assigned_user_id'])) ? $_POST['assigned_user_id'] : $log['user_id'];
        $ngay_lap_phieu = (isAdmin() && !empty($_POST['ngay_lap_phieu'])) ? $_POST['ngay_lap_phieu'] : ($log['ngay_lap_phieu'] ?: date('Y-m-d'));

        $stmt = $pdo->prepare("UPDATE maintenance_logs SET 
            project_id=?, device_id=?, custom_device_name=?, usage_time_manual=?, ngay_su_co=?, ngay_lap_phieu=?, noi_dung=?, hu_hong=?, xu_ly=?, 
            client_name=?, client_phone=?, arrival_time=?, completion_time=?, work_type=?, user_id=? WHERE id=?");
        $stmt->execute([
            $_POST['project_id'], !empty($_POST['device_id']) ? $_POST['device_id'] : null,
            !empty($_POST['custom_device_name']) ? $_POST['custom_device_name'] : null,
            !empty($_POST['usage_time_manual']) ? $_POST['usage_time_manual'] : null,
            $_POST['ngay_su_co'] ?: date('Y-m-d'),
            $ngay_lap_phieu,
            $_POST['noi_dung'] ?: null, 
            $_POST['hu_hong'], $_POST['xu_ly'],
            $_POST['client_name'], $_POST['client_phone'], $arrival_time, $completion_time, 
            $_POST['work_type'] ?: 'Bảo trì / Sửa chữa', $assigned_user_id, $id
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
        <div class="form-column">
            <div class="card">
                <div class="dashboard-card-header"><h3><i class="fas fa-tools"></i> Nội dung Công việc</h3></div>
                <div class="form-group">
                    <label>Loại công việc <span class="required">*</span></label>
                    <div class="clearable-input-wrapper"><input type="text" name="work_type" list="work_type_list" value="<?php echo htmlspecialchars($log['work_type']); ?>" required><i class="fas fa-times-circle clear-input"></i></div>
                    <datalist id="work_type_list">
                        <option value="Kiểm tra thực tế bãi giữ xe">
                        <option value="Kiểm tra máy tính kế toán">
                        <option value="Kiểm tra máy tính hệ thống xe">
                        <option value="Kiểm tra bảo trì hệ thống xe">
                    </datalist>
                </div>
                <div class="form-group"><label>Mô tả sự cố / Yêu cầu</label><div class="clearable-input-wrapper"><textarea name="noi_dung" rows="3"><?php echo htmlspecialchars($log['noi_dung']); ?></textarea><i class="fas fa-times-circle clear-input"></i></div></div>
                <div class="form-group"><label>Xác định Nguyên nhân</label><div class="clearable-input-wrapper"><textarea name="hu_hong" rows="4"><?php echo htmlspecialchars($log['hu_hong']); ?></textarea><i class="fas fa-times-circle clear-input"></i></div></div>
                <div class="form-group"><label>Giải pháp / Kết quả</label><div class="clearable-input-wrapper"><textarea name="xu_ly" rows="4"><?php echo htmlspecialchars($log['xu_ly']); ?></textarea><i class="fas fa-times-circle clear-input"></i></div></div>
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
        <div class="form-column">
            <?php if (isAdmin()): ?>
                <div class="card mb-20" style="border: 2px solid var(--primary-color); background: #f0fdf4;">
                    <div class="dashboard-card-header"><h3><i class="fas fa-user-shield"></i> Quyền Quản trị</h3></div>
                    <div class="form-group">
                        <label>Nhân viên phụ trách <span class="required">*</span></label>
                        <div class="searchable-select-container">
                            <input type="text" id="staff_search" class="search-input" placeholder="Gõ tên nhân viên..." required value="<?php foreach($staff_list as $s) { if($s['id'] == $log['user_id']) { echo htmlspecialchars($s['fullname'] ?: $s['username']); break; } } ?>" autocomplete="off">
                            <input type="hidden" name="assigned_user_id" id="assigned_user_id" value="<?php echo $log['user_id']; ?>">
                            <div id="staff_dropdown" class="searchable-dropdown"></div>
                        </div>
                    </div>
                    <div class="form-group mt-15">
                        <label>Ngày lập phiếu (In trên đầu phiếu)</label>
                        <input type="date" name="ngay_lap_phieu" class="search-input" value="<?php echo $log['ngay_lap_phieu'] ?: date('Y-m-d', strtotime($log['created_at'])); ?>">
                    </div>
                    <small class="text-muted">Admin có quyền thay đổi người thực hiện và ngày in trên phiếu.</small>
                </div>
            <?php endif; ?>
            <div class="card">
                <div class="dashboard-card-header"><h3><i class="fas fa-map-marker-alt"></i> Đối tượng</h3></div>
                <div class="form-group">
                    <label>Dự án <span class="required">*</span></label>
                    <div class="searchable-select-container">
                        <input type="text" id="project_search" class="search-input" placeholder="Gõ tên hoặc mã dự án..." required value="<?php foreach($projects as $p) { if($p['id'] == $current_project_id) { echo htmlspecialchars($p['ten_du_an']); break; } } ?>" autocomplete="off">
                        <input type="hidden" name="project_id" id="project_id" value="<?php echo $current_project_id; ?>">
                        <div id="project_dropdown" class="searchable-dropdown"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Phân loại</label>
                    <div class="radio-group-modern">
                        <label class="radio-item"><input type="radio" name="target_mode" value="device" <?php echo !empty($log['device_id']) ? 'checked' : ''; ?> onclick="toggleTargetMode('device')"><span class="radio-label"><i class="fas fa-laptop"></i> Thiết bị</span></label>
                        <label class="radio-item"><input type="radio" name="target_mode" value="custom" <?php echo empty($log['device_id']) ? 'checked' : ''; ?> onclick="toggleTargetMode('custom')"><span class="radio-label"><i class="fas fa-keyboard"></i> Khác</span></label>
                    </div>
                </div>
                <div id="device-selection-area" style="<?php echo !empty($log['device_id']) ? 'display:block' : 'display:none'; ?>">
                    <div class="form-group">
                        <label>Thiết bị</label>
                        <div class="searchable-select-container">
                            <input type="text" id="device_search" class="search-input" placeholder="Gõ tên hoặc mã tài sản..." autocomplete="off" value="<?php if (!empty($log['device_id'])) echo htmlspecialchars($log['ten_thiet_bi'] . ' (' . $log['ma_tai_san'] . ')'); ?>">
                            <input type="hidden" id="device_id" name="device_id" value="<?php echo $log['device_id']; ?>">
                            <div id="device_dropdown" class="searchable-dropdown"></div>
                        </div>
                    </div>
                </div>
                <div id="custom-name-area" style="<?php echo empty($log['device_id']) ? 'display:block' : 'display:none'; ?>"><div class="form-group"><label>Tên Đối tượng</label><div class="clearable-input-wrapper"><input type="text" name="custom_device_name" value="<?php echo htmlspecialchars($log['custom_device_name']); ?>"><i class="fas fa-times-circle clear-input"></i></div></div></div>
                <div class="form-group"><label>Ngày yêu cầu</label><input type="date" name="ngay_su_co" value="<?php echo $log['ngay_su_co']; ?>"></div>
                <div class="form-group"><label>Sử dụng (Ghi chú)</label><div class="clearable-input-wrapper"><input type="text" name="usage_time_manual" value="<?php echo htmlspecialchars($log['usage_time_manual']); ?>"><i class="fas fa-times-circle clear-input"></i></div></div>
            </div>
            <div class="card mt-20">
                <div class="dashboard-card-header"><h3><i class="fas fa-id-card"></i> Khách hàng</h3></div>
                <div class="form-group"><label>Người liên hệ</label><div class="clearable-input-wrapper"><input type="text" name="client_name" value="<?php echo htmlspecialchars($log['client_name']); ?>"><i class="fas fa-times-circle clear-input"></i></div></div>
                <div class="form-group"><label>Số điện thoại</label><div class="clearable-input-wrapper"><input type="text" name="client_phone" value="<?php echo htmlspecialchars($log['client_phone']); ?>"><i class="fas fa-times-circle clear-input"></i></div></div>
            </div>
        </div>
    </div>
</form>

<style>
* { box-sizing: border-box; }
.form-grid { max-width: 100%; overflow-x: hidden; }
.clearable-input-wrapper { position: relative; display: flex; align-items: center; width: 100%; }
.clearable-input-wrapper input, .clearable-input-wrapper textarea { padding-right: 35px !important; width: 100%; }
.clear-input { position: absolute; right: 10px; color: #cbd5e1; cursor: pointer; display: none; z-index: 10; font-size: 1.1rem; }
.clearable-input-wrapper:hover .clear-input { color: #94a3b8; }
.clear-input:hover { color: #ef4444 !important; }
.fast-time-container { display: flex; align-items: center; gap: 10px; width: 100%; }
.fast-time-group { display: flex; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; flex: 1; padding: 2px 8px; justify-content: center; overflow: hidden; }
.fast-time-group input { border: none; background: transparent; text-align: center; font-size: 0.95rem; outline: none; padding: 8px 0; }
.input-h, .input-m, .input-d, .input-mon { width: 32px; }
.input-y { width: 50px; }
.sep { font-weight: 700; color: #94a3b8; margin: 0 1px; }
.btn-now { height: 42px; min-width: 60px; }
.radio-group-modern { display: flex; gap: 10px; margin: 5px 0; }
.radio-item { flex: 1; cursor: pointer; }
.radio-item input { display: none; }
.radio-label { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.85rem; font-weight: 600; color: #64748b; }
.radio-item input:checked + .radio-label { background: #ecfdf5; border-color: var(--primary-color); color: var(--primary-color); }
.searchable-select-container { position: relative; width: 100%; }
.search-input { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; }
.search-input:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); outline: none; }
.searchable-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #cbd5e1; border-radius: 8px; margin-top: 5px; max-height: 250px; overflow-y: auto; z-index: 1000; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); display: none; }
.dropdown-item { padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #f1f5f9; text-align: left; }
.dropdown-item:hover, .dropdown-item.active { background: #f8fafc; color: var(--primary-color); }
.dropdown-item .item-title { display: block; font-weight: 600; font-size: 0.9rem; }
.dropdown-item .item-sub { display: block; font-size: 0.75rem; color: #94a3b8; }
.no-results { padding: 15px; text-align: center; color: #94a3b8; font-style: italic; font-size: 0.9rem; }
@media (max-width: 768px) {
    .fast-time-container { flex-direction: row; align-items: center; } 
    .fast-time-group { padding: 2px 4px; }
    .btn-now { width: auto; margin-top: 0; min-width: 50px; }
    .radio-group-modern { flex-direction: column; }
}
</style>

<script>
let localProjects = <?php echo json_encode($projects); ?>;
let localStaff = <?php echo json_encode($staff_list); ?>;
let localDevices = [];
let activeIndex = -1;
const initialDeviceId = "<?php echo $log['device_id']; ?>";

document.addEventListener('DOMContentLoaded', () => {
    const projectSearch = document.getElementById('project_search');
    const projectDropdown = document.getElementById('project_dropdown');
    const projectIdInput = document.getElementById('project_id');
    const deviceSearch = document.getElementById('device_search');
    const deviceDropdown = document.getElementById('device_dropdown');
    const deviceIdInput = document.getElementById('device_id');
    const staffSearch = document.getElementById('staff_search');
    const staffDropdown = document.getElementById('staff_dropdown');
    const staffIdInput = document.getElementById('assigned_user_id');

    if (projectIdInput.value) loadDevices(projectIdInput.value);

    if (projectSearch) {
        projectSearch.addEventListener('input', function() { renderDropdown(this.value.toLowerCase().trim(), localProjects, projectDropdown, (item) => selectProject(item.id, item.ten_du_an)); });
        projectSearch.addEventListener('focus', function() { renderDropdown(this.value.toLowerCase().trim(), localProjects, projectDropdown, (item) => selectProject(item.id, item.ten_du_an)); });
        projectSearch.addEventListener('keydown', (e) => handleKeydown(e, projectDropdown));
    }
    if (staffSearch) {
        staffSearch.addEventListener('input', function() { renderDropdown(this.value.toLowerCase().trim(), localStaff, staffDropdown, (item) => selectStaff(item.id, item.fullname || item.username), 'fullname', 'username'); });
        staffSearch.addEventListener('focus', function() { renderDropdown(this.value.toLowerCase().trim(), localStaff, staffDropdown, (item) => selectStaff(item.id, item.fullname || item.username), 'fullname', 'username'); });
        staffSearch.addEventListener('keydown', (e) => handleKeydown(e, staffDropdown));
    }
    if (deviceSearch) {
        deviceSearch.addEventListener('input', function() { renderDropdown(this.value.toLowerCase().trim(), localDevices, deviceDropdown, (item) => selectDevice(item.id, item.ten_thiet_bi, item.ma_tai_san)); });
        deviceSearch.addEventListener('focus', function() { if (localDevices.length > 0) renderDropdown(this.value.toLowerCase().trim(), localDevices, deviceDropdown, (item) => selectDevice(item.id, item.ten_thiet_bi, item.ma_tai_san)); });
        deviceSearch.addEventListener('keydown', (e) => handleKeydown(e, deviceDropdown));
    }

    document.addEventListener('click', function(e) {
        if (projectSearch && !projectSearch.contains(e.target) && !projectDropdown.contains(e.target)) projectDropdown.style.display = 'none';
        if (deviceSearch && !deviceSearch.contains(e.target) && !deviceDropdown.contains(e.target)) deviceDropdown.style.display = 'none';
        if (staffSearch && !staffSearch.contains(e.target) && !staffDropdown.contains(e.target)) staffDropdown.style.display = 'none';
    });

    document.querySelectorAll('.clearable-input-wrapper').forEach(wrapper => {
        const input = wrapper.querySelector('input, textarea');
        const btn = wrapper.querySelector('.clear-input');
        const toggle = () => btn.style.display = input.value.length > 0 ? 'block' : 'none';
        input.addEventListener('input', toggle);
        btn.addEventListener('click', () => { input.value = ''; toggle(); input.focus(); });
        toggle();
    });
});

function handleKeydown(e, dropdown) {
    const items = dropdown.querySelectorAll('.dropdown-item');
    if (e.key === 'ArrowDown') { e.preventDefault(); activeIndex = Math.min(activeIndex + 1, items.length - 1); updateActiveItem(items); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); activeIndex = Math.max(activeIndex - 1, -1); updateActiveItem(items); }
    else if (e.key === 'Enter') { if (activeIndex > -1 && items[activeIndex]) { e.preventDefault(); items[activeIndex].click(); } }
}

function selectProject(id, name) {
    document.getElementById('project_search').value = name;
    document.getElementById('project_id').value = id;
    document.getElementById('project_dropdown').style.display = 'none';
    const ds = document.getElementById('device_search');
    const di = document.getElementById('device_id');
    if (ds) ds.value = '';
    if (di) di.value = '';
    loadDevices(id);
}

function selectStaff(id, name) {
    document.getElementById('staff_search').value = name;
    document.getElementById('assigned_user_id').value = id;
    document.getElementById('staff_dropdown').style.display = 'none';
}

function selectDevice(id, name, code) {
    document.getElementById('device_search').value = `${name} (${code})`;
    document.getElementById('device_id').value = id;
    document.getElementById('device_dropdown').style.display = 'none';
}

function loadDevices(projectId) {
    const ds = document.getElementById('device_search');
    if (!projectId) { if(ds) { ds.disabled = true; ds.value = ''; } return; }
    if(ds) ds.placeholder = 'Đang tải...';
    fetch(`api/get_devices_by_project.php?project_id=${projectId}`).then(r => r.json()).then(data => {
        localDevices = data.filter(d => !d.parent_id);
        if(ds) {
            ds.disabled = false; ds.placeholder = 'Gõ tên hoặc mã thiết bị...';
            if (initialDeviceId && document.getElementById('device_id').value == initialDeviceId) {
                const d = localDevices.find(item => item.id == initialDeviceId);
                if (d) ds.value = `${d.ten_thiet_bi} (${d.ma_tai_san})`;
            }
        }
    });
}

function renderDropdown(filter, data, dropdown, onSelect, field1 = 'ten_du_an', field2 = 'ma_tai_san') {
    const filtered = data.filter(item => {
        const title = (item[field1] || item['ten_thiet_bi'] || '').toLowerCase();
        const sub = (item[field2] || '').toLowerCase();
        return title.includes(filter) || sub.includes(filter);
    });
    if (filtered.length === 0) { dropdown.innerHTML = '<div class="no-results">Không tìm thấy kết quả</div>'; }
    else {
        dropdown.innerHTML = filtered.map(item => `
            <div class="dropdown-item">
                <span class="item-title">${item[field1] || item['ten_thiet_bi'] || item['fullname']}</span>
                ${item[field2] ? `<span class="item-sub">${item[field2]}</span>` : ''}
            </div>
        `).join('');
        dropdown.querySelectorAll('.dropdown-item').forEach((div, idx) => { div.onclick = () => onSelect(filtered[idx]); });
    }
    dropdown.style.display = 'block'; activeIndex = -1;
}

function toggleTargetMode(mode) {
    const deviceArea = document.getElementById('device-selection-area');
    const customArea = document.getElementById('custom-name-area');
    const di = document.getElementById('device_id');
    const ds = document.getElementById('device_search');
    const cn = document.querySelector('input[name="custom_device_name"]');
    if (mode === 'device') { deviceArea.style.display = 'block'; customArea.style.display = 'none'; if (cn) cn.value = ''; }
    else { deviceArea.style.display = 'none'; customArea.style.display = 'block'; if (di) di.value = ''; if (ds) ds.value = ''; }
}

function updateActiveItem(items) {
    items.forEach((item, index) => {
        item.classList.toggle('active', index === activeIndex);
        if (index === activeIndex) item.scrollIntoView({ block: 'nearest' });
    });
}

function fillNow(prefix) {
    const now = new Date();
    document.querySelector(`input[name="${prefix}_h"]`).value = String(now.getHours()).padStart(2, '0');
    document.querySelector(`input[name="${prefix}_m"]`).value = String(now.getMinutes()).padStart(2, '0');
    document.querySelector(`input[name="${prefix}_d"]`).value = String(now.getDate()).padStart(2, '0');
    document.querySelector(`input[name="${prefix}_mon"]`).value = String(now.getMonth() + 1).padStart(2, '0');
    document.querySelector(`input[name="${prefix}_y"]`).value = now.getFullYear();
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