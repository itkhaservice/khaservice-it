<?php
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

            // Logic chọn đối tượng: Ưu tiên linh kiện con nếu có chọn
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
    <h2><i class="fas fa-file-medical"></i> Tạo Phiếu Công tác Mới</h2>
    <div class="header-actions">
        <a href="index.php?page=maintenance/history" class="btn btn-secondary"><i class="fas fa-times"></i> Hủy</a>
        <button type="submit" form="add-maintenance-form" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Phiếu</button>
    </div>
</div>

<form action="index.php?page=maintenance/add" method="POST" id="add-maintenance-form">
    <div class="form-grid">
        <!-- CỘT TRÁI: THÔNG TIN CHÍNH -->
        <div class="form-column">
            <div class="card">
                <div class="dashboard-card-header">
                    <h3><i class="fas fa-tools"></i> Nội dung Công việc</h3>
                </div>
                
                <div class="form-group">
                    <label>Loại công việc <span class="required">*</span></label>
                    <input type="text" name="work_type" value="Bảo trì / Sửa chữa" required placeholder="VD: Bảo trì định kỳ, Sửa chữa đột xuất...">
                </div>

                <div class="form-group">
                    <label>Mô tả sự cố / Yêu cầu của khách hàng</label>
                    <textarea name="noi_dung" rows="4" placeholder="Nhập nội dung yêu cầu hoặc hiện tượng hư hỏng..."></textarea>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Xác định Nguyên nhân / Hư hỏng</label>
                        <textarea name="hu_hong" rows="4" placeholder="Kết quả kiểm tra thực tế..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Giải pháp / Kết quả xử lý</label>
                        <textarea name="xu_ly" rows="4" placeholder="Các bước đã thực hiện và kết quả..."></textarea>
                    </div>
                </div>
            </div>

            <div class="card mt-20">
                <div class="dashboard-card-header">
                    <h3><i class="fas fa-user-clock"></i> Thời gian Thực hiện</h3>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Thời điểm có mặt</label>
                        <div class="fast-time-container">
                            <div class="fast-time-group" id="group-arr">
                                <input type="number" name="arr_h" class="input-h auto-tab" maxlength="2" placeholder="HH">
                                <span class="sep">:</span>
                                <input type="number" name="arr_m" class="input-m auto-tab" maxlength="2" placeholder="mm">
                                <span class="sep">&nbsp;</span>
                                <input type="number" name="arr_d" class="input-d auto-tab" maxlength="2" placeholder="DD">
                                <span class="sep">/</span>
                                <input type="number" name="arr_mon" class="input-mon auto-tab" maxlength="2" placeholder="MM">
                                <span class="sep">/</span>
                                <input type="number" name="arr_y" class="input-y auto-tab" maxlength="4" placeholder="YYYY">
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="fillNow('arr')">Nay</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Thời điểm hoàn thành</label>
                        <div class="fast-time-container">
                            <div class="fast-time-group" id="group-comp">
                                <input type="number" name="comp_h" class="input-h auto-tab" maxlength="2" placeholder="HH">
                                <span class="sep">:</span>
                                <input type="number" name="comp_m" class="input-m auto-tab" maxlength="2" placeholder="mm">
                                <span class="sep">&nbsp;</span>
                                <input type="number" name="comp_d" class="input-d auto-tab" maxlength="2" placeholder="DD">
                                <span class="sep">/</span>
                                <input type="number" name="comp_mon" class="input-mon auto-tab" maxlength="2" placeholder="MM">
                                <span class="sep">/</span>
                                <input type="number" name="comp_y" class="input-y auto-tab" maxlength="4" placeholder="YYYY">
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="fillNow('comp')">Nay</button>
                        </div>
                    </div>
                </div>
                <p style="font-size: 0.8rem; color: #64748b; margin-top: 10px;"><i class="fas fa-info-circle"></i> Nhập theo thứ tự Giờ:Phút Ngày/Tháng/Năm. Bấm "Nay" để lấy thời gian hiện tại.</p>
            </div>
        </div>

        <!-- CỘT PHẢI: ĐỐI TƯỢNG & KHÁCH HÀNG -->
        <div class="form-column">
            <div class="card">
                <div class="dashboard-card-header">
                    <h3><i class="fas fa-map-marker-alt"></i> Thông tin Đối tượng</h3>
                </div>

                <div class="form-group">
                    <label>Dự án <span class="required">*</span></label>
                    <select name="project_id" id="project_id" required onchange="loadDevices(this.value)">
                        <option value="">-- Chọn dự án --</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($preselected_project_id == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['ten_du_an']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Phân loại đối tượng</label>
                    <div class="radio-group-modern">
                        <label class="radio-item">
                            <input type="radio" name="target_mode" value="device" checked onclick="toggleTargetMode('device')">
                            <span class="radio-label"><i class="fas fa-laptop"></i> Thiết bị hệ thống</span>
                        </label>
                        <label class="radio-item">
                            <input type="radio" name="target_mode" value="custom" onclick="toggleTargetMode('custom')">
                            <span class="radio-label"><i class="fas fa-keyboard"></i> Nhập tay đối tượng khác</span>
                        </label>
                    </div>
                </div>

                <div id="device-selection-area">
                    <div class="form-group">
                        <label>Thiết bị chính</label>
                        <select id="device_id" name="device_id" onchange="loadComponents(this.value)" <?php echo !$preselected_device_id ? 'disabled' : ''; ?>>
                            <?php if($preselected_device_data): ?>
                                <option value="<?php echo $preselected_device_data['id']; ?>">
                                    <?php echo htmlspecialchars($preselected_device_data['ten_thiet_bi'] . ' (' . $preselected_device_data['ma_tai_san'] . ')'); ?>
                                </option>
                            <?php else: ?>
                                <option value="">-- Chọn dự án trước --</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Component Selection -->
                    <div class="form-group" id="component-group" style="display: none;">
                        <label>Linh kiện cụ thể (Nếu có)</label>
                        <select id="component_id" name="component_id">
                            <option value="">-- Kiểm tra tổng thể thiết bị --</option>
                        </select>
                        <small class="text-info"><i class="fas fa-info-circle"></i> Để trống nếu kiểm tra toàn bộ CPU. Chọn cụ thể nếu chỉ kiểm tra RAM/SSD.</small>
                    </div>
                </div>

                <div id="custom-name-area" style="display: none;">
                    <div class="form-group">
                        <label>Tên Đối tượng / Hạng mục</label>
                        <input type="text" name="custom_device_name" placeholder="VD: Hệ thống mạng, Cài đặt phần mềm...">
                    </div>
                </div>

                <div class="form-group">
                    <label>Ngày yêu cầu xử lý</label>
                    <input type="date" name="ngay_su_co" value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label>Thời gian sử dụng (Ghi chú)</label>
                    <input type="text" name="usage_time_manual" placeholder="VD: 2 năm, Mới mua... (Để trống để lấy từ hồ sơ thiết bị)">
                </div>
            </div>

            <div class="card mt-20">
                <div class="dashboard-card-header">
                    <h3><i class="fas fa-id-card"></i> Thông tin Khách hàng</h3>
                </div>
                <div class="form-group">
                    <label>Người đại diện / Người liên hệ</label>
                    <input type="text" name="client_name" placeholder="Tên người báo sự cố hoặc người nghiệm thu">
                </div>
                <div class="form-group">
                    <label>Số điện thoại liên hệ</label>
                    <input type="text" name="client_phone" placeholder="SĐT khách hàng">
                </div>
                <div class="form-group">
                    <label>Chi phí xử lý (nếu có)</label>
                    <div class="input-icon-wrapper">
                        <input type="number" name="chi_phi" value="0" step="1000">
                        <span class="input-icon">VNĐ</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
/* Modern Radio Group */
.radio-group-modern { display: flex; gap: 10px; margin: 5px 0; }
.radio-item { flex: 1; cursor: pointer; }
.radio-item input { display: none; }
.radio-label { 
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;
    font-size: 0.85rem; font-weight: 600; color: #64748b; transition: all 0.2s;
}
.radio-item input:checked + .radio-label {
    background: #ecfdf5; border-color: var(--primary-color); color: var(--primary-color);
}

/* Fast Time Input Styles */
.fast-time-container { display: flex; align-items: center; gap: 8px; }
.fast-time-group { 
    display: flex; align-items: center; gap: 0; background: #fff; 
    border: 1px solid #cbd5e1; padding: 2px 8px; border-radius: 8px; flex: 1;
}
.fast-time-group input { 
    border: none; padding: 8px 2px; text-align: center; font-size: 0.9rem; 
    outline: none; background: transparent; box-shadow: none !important;
}
.fast-time-group input:focus { background: #f0fdf4; color: var(--primary-color); }
.fast-time-group .sep { font-weight: 700; color: #94a3b8; margin: 0 2px; }
.input-h, .input-m, .input-d, .input-mon { width: 30px; }
.input-y { width: 50px; }
input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

.mt-20 { margin-top: 20px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const pid = document.getElementById('project_id').value;
    if (pid && !document.getElementById('device_id').value) {
        loadDevices(pid);
    }
    
    // Nếu có preselected device, load linh kiện cho nó luôn
    const preDeviceId = "<?php echo $preselected_device_id; ?>";
    if (preDeviceId) {
        loadComponents(preDeviceId);
    }
});

function loadComponents(deviceId) {
    const compGroup = document.getElementById('component-group');
    const compSelect = document.getElementById('component_id');
    
    if (!deviceId) {
        compGroup.style.display = 'none';
        return;
    }

    // Tận dụng API có sẵn (vì bản chất linh kiện cũng là thiết bị có parent_id)
    fetch(`api/get_devices_by_project.php?parent_id=${deviceId}`)
        .then(r => r.json()).then(data => {
            if (data && data.length > 0) {
                compSelect.innerHTML = '<option value="">-- Kiểm tra tổng thể thiết bị --</option>';
                data.forEach(c => {
                    compSelect.innerHTML += `<option value="${c.id}">${c.ten_thiet_bi} (${c.ma_tai_san})</option>`;
                });
                compGroup.style.display = 'block';
            } else {
                compGroup.style.display = 'none';
                compSelect.innerHTML = '<option value="">-- Không có linh kiện con --</option>';
            }
        }).catch(err => {
            console.error(err);
            compGroup.style.display = 'none';
        });
}

// Tự động chuyển ô khi gõ đủ số
document.querySelectorAll('.auto-tab').forEach(input => {
    input.addEventListener('input', function() {
        const maxLength = parseInt(this.getAttribute('maxlength'));
        if (this.value.length >= maxLength) {
            let next = this.nextElementSibling;
            while (next && next.tagName !== 'INPUT') { next = next.nextElementSibling; }
            if (next) next.focus();
        }
    });
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && this.value.length === 0) {
            let prev = this.previousElementSibling;
            while (prev && prev.tagName !== 'INPUT') { prev = prev.previousElementSibling; }
            if (prev) prev.focus();
        }
    });
});

function fillNow(prefix) {
    const now = new Date();
    document.querySelector(`input[name="${prefix}_h"]`).value = String(now.getHours()).padStart(2, '0');
    document.querySelector(`input[name="${prefix}_m"]`).value = String(now.getMinutes()).padStart(2, '0');
    document.querySelector(`input[name="${prefix}_d"]`).value = String(now.getDate()).padStart(2, '0');
    document.querySelector(`input[name="${prefix}_mon"]`).value = String(now.getMonth() + 1).padStart(2, '0');
    document.querySelector(`input[name="${prefix}_y"]`).value = now.getFullYear();
}

function toggleTargetMode(mode) {
    document.getElementById('device-selection-area').style.display = (mode === 'device' ? 'block' : 'none');
    document.getElementById('custom-name-area').style.display = (mode === 'custom' ? 'block' : 'none');
}

function loadDevices(projectId) {
    const ds = document.getElementById('device_id');
    const currentDeviceId = "<?php echo $preselected_device_id; ?>";
    
    if (!projectId) { 
        ds.disabled = true; 
        ds.innerHTML = '<option value="">-- Chọn dự án trước --</option>';
        return; 
    }
    
    ds.innerHTML = '<option value="">Đang tải...</option>';
    ds.disabled = true;

    fetch(`api/get_devices_by_project.php?project_id=${projectId}`)
        .then(r => r.json()).then(data => {
            ds.innerHTML = '<option value="">-- Chọn thiết bị --</option>';
            data.forEach(d => { 
                // Chỉ hiện các thiết bị chính (không có parent_id) trong danh sách chọn thiết bị chính
                if (!d.parent_id) {
                    const selected = (d.id == currentDeviceId) ? 'selected' : '';
                    ds.innerHTML += `<option value="${d.id}" ${selected}>${d.ten_thiet_bi} (${d.ma_tai_san})</option>`; 
                }
            });
            ds.disabled = false;
            // Nếu có thiết bị được chọn mặc định, load components luôn
            if (ds.value) loadComponents(ds.value);
        })
        .catch(err => {
            console.error(err);
            ds.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
        });
}
</script>

<style>
/* Modern Radio Group */
.radio-group-modern { display: flex; gap: 10px; margin: 5px 0; }
.radio-item { flex: 1; cursor: pointer; }
.radio-item input { display: none; }
.radio-label { 
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;
    font-size: 0.85rem; font-weight: 600; color: #64748b; transition: all 0.2s;
}
.radio-item input:checked + .radio-label {
    background: #ecfdf5; border-color: var(--primary-color); color: var(--primary-color);
}

/* Fast Time Input Styles */
.fast-time-container { display: flex; align-items: center; gap: 8px; }
.fast-time-group { 
    display: flex; align-items: center; gap: 0; background: #fff; 
    border: 1px solid #cbd5e1; padding: 2px 8px; border-radius: 8px; flex: 1;
}
.fast-time-group input { 
    border: none; padding: 8px 2px; text-align: center; font-size: 0.9rem; 
    outline: none; background: transparent; box-shadow: none !important;
}
.fast-time-group input:focus { background: #f0fdf4; color: var(--primary-color); }
.fast-time-group .sep { font-weight: 700; color: #94a3b8; margin: 0 2px; }
.input-h, .input-m, .input-d, .input-mon { width: 30px; }
.input-y { width: 50px; }
input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

.mt-20 { margin-top: 20px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Tự động load thiết bị nếu đã có dự án được chọn (trường hợp quay lại từ URL)
    const pid = document.getElementById('project_id').value;
    if (pid && !document.getElementById('device_id').value) {
        loadDevices(pid);
    }
});

// Tự động chuyển ô khi gõ đủ số
document.querySelectorAll('.auto-tab').forEach(input => {
    input.addEventListener('input', function() {
        const maxLength = parseInt(this.getAttribute('maxlength'));
        if (this.value.length >= maxLength) {
            let next = this.nextElementSibling;
            while (next && next.tagName !== 'INPUT') { next = next.nextElementSibling; }
            if (next) next.focus();
        }
    });
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && this.value.length === 0) {
            let prev = this.previousElementSibling;
            while (prev && prev.tagName !== 'INPUT') { prev = prev.previousElementSibling; }
            if (prev) prev.focus();
        }
    });
});

function fillNow(prefix) {
    const now = new Date();
    document.querySelector(`input[name="${prefix}_h"]`).value = String(now.getHours()).padStart(2, '0');
    document.querySelector(`input[name="${prefix}_m"]`).value = String(now.getMinutes()).padStart(2, '0');
    document.querySelector(`input[name="${prefix}_d"]`).value = String(now.getDate()).padStart(2, '0');
    document.querySelector(`input[name="${prefix}_mon"]`).value = String(now.getMonth() + 1).padStart(2, '0');
    document.querySelector(`input[name="${prefix}_y"]`).value = now.getFullYear();
}

function toggleTargetMode(mode) {
    document.getElementById('device-selection-area').style.display = (mode === 'device' ? 'block' : 'none');
    document.getElementById('custom-name-area').style.display = (mode === 'custom' ? 'block' : 'none');
}

function loadDevices(projectId) {
    const ds = document.getElementById('device_id');
    const currentDeviceId = "<?php echo $preselected_device_id; ?>";
    
    if (!projectId) { 
        ds.disabled = true; 
        ds.innerHTML = '<option value="">-- Chọn dự án trước --</option>';
        return; 
    }
    
    ds.innerHTML = '<option value="">Đang tải...</option>';
    ds.disabled = true;

    fetch(`api/get_devices_by_project.php?project_id=${projectId}`)
        .then(r => r.json()).then(data => {
            ds.innerHTML = '<option value="">-- Chọn thiết bị --</option>';
            data.forEach(d => { 
                const selected = (d.id == currentDeviceId) ? 'selected' : '';
                ds.innerHTML += `<option value="${d.id}" ${selected}>${d.ten_thiet_bi} (${d.ma_tai_san})</option>`; 
            });
            ds.disabled = false;
        })
        .catch(err => {
            console.error(err);
            ds.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
        });
}
</script>

<script>
// Tự động chuyển ô khi gõ đủ số
document.querySelectorAll('.auto-tab').forEach(input => {
    input.addEventListener('input', function() {
        const maxLength = parseInt(this.getAttribute('maxlength'));
        if (this.value.length >= maxLength) {
            let next = this.nextElementSibling;
            while (next && next.tagName !== 'INPUT') { next = next.nextElementSibling; }
            if (next) next.focus();
        }
    });
    // Hỗ trợ xóa ngược (backspace) quay lại ô trước
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && this.value.length === 0) {
            let prev = this.previousElementSibling;
            while (prev && prev.tagName !== 'INPUT') { prev = prev.previousElementSibling; }
            if (prev) prev.focus();
        }
    });
});

function fillNow(prefix) {
    const now = new Date();
    document.querySelector(`input[name="${prefix}_h"]`).value = String(now.getHours()).padStart(2, '0');
    document.querySelector(`input[name="${prefix}_m"]`).value = String(now.getMinutes()).padStart(2, '0');
    document.querySelector(`input[name="${prefix}_d"]`).value = String(now.getDate()).padStart(2, '0');
    document.querySelector(`input[name="${prefix}_mon"]`).value = String(now.getMonth() + 1).padStart(2, '0');
    document.querySelector(`input[name="${prefix}_y"]`).value = now.getFullYear();
}

function toggleTargetMode(mode) {
    document.getElementById('device-selection-area').style.display = (mode === 'device' ? 'block' : 'none');
    document.getElementById('custom-name-area').style.display = (mode === 'custom' ? 'block' : 'none');
}

function loadDevices(projectId) {
    const ds = document.getElementById('device_id');
    if (!projectId) { ds.disabled = true; return; }
    fetch(`api/get_devices_by_project.php?project_id=${projectId}`)
        .then(r => r.json()).then(data => {
            ds.innerHTML = '<option value="">-- Chọn thiết bị --</option>';
            data.forEach(d => { ds.innerHTML += `<option value="${d.id}">${d.ten_thiet_bi} (${d.ma_tai_san})</option>`; });
            ds.disabled = false;
        });
}
</script>
