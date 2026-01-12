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
            $ngay_lap_phieu = (isAdmin() && !empty($_POST['ngay_lap_phieu'])) ? $_POST['ngay_lap_phieu'] : date('Y-m-d');

            $final_device_id = !empty($_POST['component_id']) ? $_POST['component_id'] : (!empty($_POST['device_id']) ? $_POST['device_id'] : null);

            $stmt = $pdo->prepare("INSERT INTO maintenance_logs 
                (user_id, project_id, device_id, custom_device_name, usage_time_manual, ngay_su_co, ngay_lap_phieu, noi_dung, hu_hong, xu_ly, chi_phi, client_name, client_phone, arrival_time, completion_time, work_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'], 
                $_POST['project_id'],
                $final_device_id,
                !empty($_POST['custom_device_name']) ? $_POST['custom_device_name'] : null,
                !empty($_POST['usage_time_manual']) ? $_POST['usage_time_manual'] : null,
                $ngay_su_co,
                $ngay_lap_phieu,
                $_POST['noi_dung'] ?: null, 
                $_POST['hu_hong'],
                $_POST['xu_ly'],
                $_POST['client_name'],
                $_POST['client_phone'],
                $arrival_time,
                $completion_time,
                $_POST['work_type'] ?: 'Bảo trì / Sửa chữa'
            ]);
            set_message('success', 'Đã tạo phiếu công tác thành công!');
            echo "<script>window.location.href = 'index.php?page=maintenance/history';</script>";
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
                
                <div class="form-group">
                    <label>Thời điểm có mặt</label>
                    <div class="fast-time-container">
                        <div class="fast-time-group">
                            <input type="number" name="arr_h" class="input-h auto-tab" maxlength="2" placeholder="HH">
                            <span class="sep">:</span>
                            <input type="number" name="arr_m" class="input-m auto-tab" maxlength="2" placeholder="mm">
                            <span class="sep" style="margin: 0 8px;">&nbsp;</span>
                            <input type="number" name="arr_d" class="input-d auto-tab" maxlength="2" placeholder="DD">
                            <span class="sep">/</span>
                            <input type="number" name="arr_mon" class="input-mon auto-tab" maxlength="2" placeholder="MM">
                            <span class="sep">/</span>
                            <input type="number" name="arr_y" class="input-y auto-tab" maxlength="4" placeholder="YYYY">
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary btn-now" onclick="fillNow('arr')">Nay</button>
                    </div>
                </div>

                <div class="form-group mt-20">
                    <label>Thời điểm hoàn thành</label>
                    <div class="fast-time-container">
                        <div class="fast-time-group">
                            <input type="number" name="comp_h" class="input-h auto-tab" maxlength="2" placeholder="HH">
                            <span class="sep">:</span>
                            <input type="number" name="comp_m" class="input-m auto-tab" maxlength="2" placeholder="mm">
                            <span class="sep" style="margin: 0 8px;">&nbsp;</span>
                            <input type="number" name="comp_d" class="input-d auto-tab" maxlength="2" placeholder="DD">
                            <span class="sep">/</span>
                            <input type="number" name="comp_mon" class="input-mon auto-tab" maxlength="2" placeholder="MM">
                            <span class="sep">/</span>
                            <input type="number" name="comp_y" class="input-y auto-tab" maxlength="4" placeholder="YYYY">
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary btn-now" onclick="fillNow('comp')">Nay</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- CỘT PHẢI -->
        <div class="form-column">
            <?php if (isAdmin()): ?>
                <div class="card mb-20" style="border: 2px solid var(--primary-color); background: #f0fdf4;">
                    <div class="dashboard-card-header"><h3><i class="fas fa-user-shield"></i> Quyền Quản trị</h3></div>
                    <div class="form-group">
                        <label>Ngày lập phiếu (In trên đầu phiếu)</label>
                        <input type="date" name="ngay_lap_phieu" class="search-input" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <small class="text-muted">Admin có quyền đặt ngày in trên phiếu khác với ngày hiện tại.</small>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="dashboard-card-header"><h3><i class="fas fa-map-marker-alt"></i> Đối tượng</h3></div>
                <div class="form-group">
                    <label>Dự án <span class="required">*</span></label>
                    <div class="searchable-select-container">
                        <input type="text" id="project_search" class="search-input" placeholder="Gõ tên hoặc mã dự án..." required value="<?php 
                            if ($preselected_project_id) {
                                foreach($projects as $p) {
                                    if($p['id'] == $preselected_project_id) {
                                        echo htmlspecialchars($p['ten_du_an']);
                                        break;
                                    }
                                }
                            }
                        ?>" autocomplete="off">
                        <button type="button" class="btn-clear" onclick="clearSearch('project')" title="Xóa chọn"><i class="fas fa-times"></i></button>
                        <input type="hidden" name="project_id" id="project_id" value="<?php echo $preselected_project_id; ?>">
                        <div id="project_dropdown" class="searchable-dropdown"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Phân loại</label>
                    <div class="radio-group-modern">
                        <label class="radio-item"><input type="radio" name="target_mode" value="device" checked onclick="toggleTargetMode('device')"><span class="radio-label"><i class="fas fa-laptop"></i> Thiết bị</span></label>
                        <label class="radio-item"><input type="radio" name="target_mode" value="custom" onclick="toggleTargetMode('custom')"><span class="radio-label"><i class="fas fa-keyboard"></i> Khác</span></label>
                    </div>
                </div>
                <div id="device-selection-area">
                    <div class="form-group">
                        <label>Thiết bị</label>
                        <div class="searchable-select-container">
                            <input type="text" id="device_search" class="search-input" placeholder="Gõ tên hoặc mã tài sản..." <?php echo !$preselected_device_id ? 'disabled' : ''; ?> value="<?php echo $preselected_device_data ? htmlspecialchars($preselected_device_data['ten_thiet_bi'] . ' (' . $preselected_device_data['ma_tai_san'] . ')') : ''; ?>" autocomplete="off">
                            <button type="button" class="btn-clear" onclick="clearSearch('device')" title="Xóa chọn"><i class="fas fa-times"></i></button>
                            <input type="hidden" id="device_id" name="device_id" value="<?php echo $preselected_device_id; ?>">
                            <div id="device_dropdown" class="searchable-dropdown"></div>
                        </div>
                    </div>
                    <div class="form-group" id="component-group" style="display: none;">
                        <label>Linh kiện</label>
                        <select id="component_id" name="component_id"></select>
                    </div>
                </div>
                <div id="custom-name-area" style="display: none;"><div class="form-group"><label>Tên Đối tượng</label><div class="clearable-input-wrapper"><input type="text" name="custom_device_name" placeholder="VD: Hệ thống mạng..."><i class="fas fa-times-circle clear-input"></i></div></div></div>
                <div class="form-group"><label>Ngày yêu cầu</label><input type="date" name="ngay_su_co" value="<?php echo date('Y-m-d'); ?>"></div>
                <div class="form-group"><label>Sử dụng (Ghi chú)</label><div class="clearable-input-wrapper"><input type="text" name="usage_time_manual" placeholder="VD: 2 năm..."><i class="fas fa-times-circle clear-input"></i></div></div>
            </div>
            <div class="card mt-20">
                <div class="dashboard-card-header"><h3><i class="fas fa-id-card"></i> Khách hàng</h3></div>
                <div class="form-group"><label>Người liên hệ</label><div class="clearable-input-wrapper"><input type="text" name="client_name" placeholder="Tên khách hàng..."><i class="fas fa-times-circle clear-input"></i></div></div>
                <div class="form-group"><label>Chức vụ</label><div class="clearable-input-wrapper"><input type="text" name="client_phone" placeholder="Chức vụ..."><i class="fas fa-times-circle clear-input"></i></div></div>
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
.fast-time-group { display: flex; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; flex: 1; padding: 2px 8px; justify-content: center; overflow: hidden; }
.fast-time-group input { border: none; background: transparent; text-align: center; font-size: 0.95rem; outline: none; padding: 8px 0; }
.input-h, .input-m, .input-d, .input-mon { width: 32px; }
.input-y { width: 50px; }
.sep { font-weight: bold; color: #94a3b8; margin: 0 1px; }
.btn-now { height: 42px; min-width: 60px; }

/* Radio Group */
.radio-group-modern { display: flex; gap: 10px; margin: 5px 0; }
.radio-item { flex: 1; cursor: pointer; }
.radio-item input { display: none; }
.radio-label { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.85rem; font-weight: 600; color: #64748b; }
.radio-item input:checked + .radio-label { background: #ecfdf5; border-color: var(--primary-color); color: var(--primary-color); }

/* Searchable Select */
.searchable-select-container { position: relative; width: 100%; }
.search-input { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; }
.search-input:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); outline: none; }

/* Clear Button Inside Search */
.btn-clear {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 5px;
    display: none;
    transition: color 0.2s;
    z-index: 5;
}
.searchable-select-container:hover .btn-clear,
.search-input:focus + .btn-clear {
    display: block;
}
.btn-clear:hover {
    color: #ef4444;
}

.searchable-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #cbd5e1; border-radius: 8px; margin-top: 5px; max-height: 250px; overflow-y: auto; z-index: 1000; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); display: none; }
.dropdown-item { padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #f1f5f9; text-align: left; transition: all 0.2s; }
.dropdown-item:last-child { border-bottom: none; }
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
let localDevices = [];
let activeIndex = -1;

document.addEventListener('DOMContentLoaded', () => {
    const pid = document.getElementById('project_id').value;
    if (pid) loadDevices(pid); 
    
    const preDeviceId = "<?php echo $preselected_device_id; ?>";
    if (preDeviceId) loadComponents(preDeviceId);

    const projectSearch = document.getElementById('project_search');
    const projectDropdown = document.getElementById('project_dropdown');
    const projectIdInput = document.getElementById('project_id');

    if (projectSearch) {
        projectSearch.addEventListener('input', function() { renderProjectDropdown(this.value.toLowerCase().trim()); });
        projectSearch.addEventListener('focus', function() { renderProjectDropdown(this.value.toLowerCase().trim()); });
        projectSearch.addEventListener('keydown', (e) => handleKeydown(e, projectDropdown));
    }

    const searchInput = document.getElementById('device_search');
    const dropdown = document.getElementById('device_dropdown');
    const hiddenInput = document.getElementById('device_id');

    if (searchInput) {
        searchInput.addEventListener('input', function() { renderDropdown(this.value.toLowerCase().trim()); });
        searchInput.addEventListener('focus', function() { if (localDevices.length > 0) renderDropdown(this.value.toLowerCase().trim()); });
        searchInput.addEventListener('keydown', (e) => handleKeydown(e, dropdown));
    }

    document.addEventListener('click', function(e) {
        if (projectSearch && !projectSearch.contains(e.target) && !projectDropdown.contains(e.target)) projectDropdown.style.display = 'none';
        if (searchInput && !searchInput.contains(e.target) && !dropdown.contains(e.target)) dropdown.style.display = 'none';
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
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        activeIndex = Math.min(activeIndex + 1, items.length - 1);
        updateActiveItem(items);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        activeIndex = Math.max(activeIndex - 1, -1);
        updateActiveItem(items);
    } else if (e.key === 'Enter') {
        if (activeIndex > -1 && items[activeIndex]) { e.preventDefault(); items[activeIndex].click(); }
    }
}

function renderProjectDropdown(filter = '') {
    const dropdown = document.getElementById('project_dropdown');
    const filtered = localProjects.filter(p => p.ten_du_an.toLowerCase().includes(filter));
    if (filtered.length === 0) {
        dropdown.innerHTML = '<div class="no-results">Không tìm thấy dự án</div>';
    } else {
        dropdown.innerHTML = filtered.map(p => `<div class="dropdown-item" onclick="selectProject(${p.id}, '${p.ten_du_an.replace(/'/g, "'")}')"><span class="item-title">${p.ten_du_an}</span></div>`).join('');
    }
    dropdown.style.display = 'block';
    activeIndex = -1;
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

function loadDevices(projectId) {
    const searchInput = document.getElementById('device_search');
    if (!projectId) { if(searchInput) { searchInput.disabled = true; searchInput.value = ''; } return; }
    if(searchInput) searchInput.placeholder = 'Đang tải...';
    fetch(`api/get_devices_by_project.php?project_id=${projectId}`).then(r => r.json()).then(data => {
        localDevices = data.filter(d => !d.parent_id);
        if(searchInput) { searchInput.disabled = false; searchInput.placeholder = 'Gõ tên hoặc mã tài sản...'; }
    });
}

function renderDropdown(filter = '') {
    const dropdown = document.getElementById('device_dropdown');
    if (!dropdown) return;
    const filtered = localDevices.filter(d => (d.ten_thiet_bi && d.ten_thiet_bi.toLowerCase().includes(filter)) || (d.ma_tai_san && d.ma_tai_san.toLowerCase().includes(filter)));
    if (filtered.length === 0) {
        dropdown.innerHTML = '<div class="no-results">Không tìm thấy thiết bị nào</div>';
    } else {
        dropdown.innerHTML = filtered.map((d, index) => `<div class="dropdown-item" onclick="selectDevice(${d.id}, '${d.ten_thiet_bi.replace(/'/g, "'")}', '${d.ma_tai_san}')"><span class="item-title">${d.ten_thiet_bi}</span><span class="item-sub">${d.ma_tai_san}</span></div>`).join('');
    }
    dropdown.style.display = 'block';
    activeIndex = -1;
}

function selectDevice(id, name, code) {
    const searchInput = document.getElementById('device_search');
    const hiddenInput = document.getElementById('device_id');
    if(searchInput) searchInput.value = `${name} (${code})`;
    if(hiddenInput) hiddenInput.value = id;
    document.getElementById('device_dropdown').style.display = 'none';
    loadComponents(id);
}

function clearSearch(type) {
    const searchInput = document.getElementById(type + '_search');
    const idInput = document.getElementById(type + '_id');
    const dropdown = document.getElementById(type + '_dropdown');
    
    searchInput.value = '';
    idInput.value = '';
    if(dropdown) dropdown.style.display = 'none';
    
    if (type === 'project') {
        const deviceSearch = document.getElementById('device_search');
        const deviceIdInput = document.getElementById('device_id');
        const compGroup = document.getElementById('component-group');
        
        if(deviceSearch) {
            deviceSearch.value = '';
            deviceSearch.disabled = true;
            deviceSearch.placeholder = 'Chọn dự án trước...';
        }
        if(deviceIdInput) deviceIdInput.value = '';
        if(compGroup) compGroup.style.display = 'none';
        localDevices = [];
    } else if (type === 'device') {
        const compGroup = document.getElementById('component-group');
        if(compGroup) compGroup.style.display = 'none';
    }
}

function loadComponents(deviceId) {
    const compGroup = document.getElementById('component-group');
    const compSelect = document.getElementById('component_id');
    if (!deviceId) { compGroup.style.display = 'none'; return; }
    fetch(`api/get_devices_by_project.php?parent_id=${deviceId}`).then(r => r.json()).then(data => {
        if (data && data.length > 0) {
            compSelect.innerHTML = '<option value="">-- Kiểm tra tổng thể --</option>';
            data.forEach(c => { compSelect.innerHTML += `<option value="${c.id}">${c.ten_thiet_bi} (${c.ma_tai_san})</option>`; });
            compGroup.style.display = 'block';
        } else { compGroup.style.display = 'none'; }
    });
}

function toggleTargetMode(mode) {
    const deviceArea = document.getElementById('device-selection-area');
    const customArea = document.getElementById('custom-name-area');
    const deviceIdInput = document.getElementById('device_id');
    const deviceSearchInput = document.getElementById('device_search');
    const customNameInput = document.querySelector('input[name="custom_device_name"]');

    if (mode === 'device') {
        deviceArea.style.display = 'block';
        customArea.style.display = 'none';
        if (customNameInput) customNameInput.value = ''; 
    } else {
        deviceArea.style.display = 'none';
        customArea.style.display = 'block';
        if (deviceIdInput) deviceIdInput.value = ''; 
        if (deviceSearchInput) deviceSearchInput.value = '';
    }
}

function updateActiveItem(items) {
    items.forEach((item, index) => {
        if (index === activeIndex) { item.classList.add('active'); item.scrollIntoView({ block: 'nearest' }); } 
        else { item.classList.remove('active'); }
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