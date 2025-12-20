<?php
// Fetch projects for dropdown
$projects = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an")->fetchAll();

$preselected_device_id = $_GET['device_id'] ?? null;
$preselected_project_id = null;

if ($preselected_device_id) {
    $stmt = $pdo->prepare("SELECT project_id FROM devices WHERE id = ?");
    $stmt->execute([$preselected_device_id]);
    $preselected_project_id = $stmt->fetchColumn();
}

// Hàm hỗ trợ gộp thời gian từ các ô nhập lẻ
function getFastDateTime($h, $m, $d, $mon, $y) {
    if (empty($h) || empty($m) || empty($d) || empty($mon) || empty($y)) return null;
    return sprintf("%04d-%02d-%02d %02d:%02d:00", $y, $mon, $d, $h, $m);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $has_device = !empty($_POST['device_id']);
    $has_custom_name = !empty($_POST['custom_device_name']);

    if ((!$has_device && !$has_custom_name) || empty($_POST['project_id']) || empty($_POST['ngay_su_co']) || empty($_POST['noi_dung'])) {
        set_message('error', 'Vui lòng chọn Dự án, Thiết bị (hoặc nhập tên), và điền đầy đủ thông tin bắt buộc (*).');
    } else {
        try {
            // Gộp thời gian từ các ô nhập nhanh
            $arrival_time = getFastDateTime($_POST['arr_h'], $_POST['arr_m'], $_POST['arr_d'], $_POST['arr_mon'], $_POST['arr_y']);
            $completion_time = getFastDateTime($_POST['comp_h'], $_POST['comp_m'], $_POST['comp_d'], $_POST['comp_mon'], $_POST['comp_y']);

            $stmt = $pdo->prepare("INSERT INTO maintenance_logs 
                (project_id, device_id, custom_device_name, ngay_su_co, noi_dung, hu_hong, xu_ly, chi_phi, client_name, client_phone, arrival_time, completion_time, work_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['project_id'],
                !empty($_POST['device_id']) ? $_POST['device_id'] : null,
                !empty($_POST['custom_device_name']) ? $_POST['custom_device_name'] : null,
                $_POST['ngay_su_co'],
                $_POST['noi_dung'],
                $_POST['hu_hong'],
                $_POST['xu_ly'],
                $_POST['chi_phi'] ?: 0,
                $_POST['client_name'],
                $_POST['client_phone'],
                $arrival_time,
                $completion_time,
                $_POST['work_type'] ?: 'Bảo trì / Sửa chữa'
            ]);
            set_message('success', 'Đã tạo phiếu bảo trì thành công!');
            header("Location: index.php?page=maintenance/history");
            exit;
        } catch (PDOException $e) {
            set_message('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}
?>

<style>
    /* Style cho bộ nhập nhanh thời gian */
    .fast-time-group { display: flex; align-items: center; gap: 2px; background: #fff; border: 1px solid #ddd; padding: 2px 5px; border-radius: 4px; width: fit-content; }
    .fast-time-group input { border: none; padding: 5px 2px; text-align: center; font-size: 14px; outline: none; }
    .fast-time-group input:focus { background: #e0f2fe; }
    .fast-time-group .sep { font-weight: bold; color: #999; margin: 0 1px; }
    .input-h, .input-m, .input-d, .input-mon { width: 25px; }
    .input-y { width: 45px; }
    /* Ẩn mũi tên input number */
    input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
</style>

<div class="page-header">
    <h2><i class="fas fa-plus-circle"></i> Tạo Phiếu Bảo trì mới</h2>
    <div class="header-actions">
        <a href="index.php?page=maintenance/history" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <button type="submit" form="add-maintenance-form" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Phiếu</button>
    </div>
</div>

<form action="index.php?page=maintenance/add" method="POST" id="add-maintenance-form" class="edit-layout">
    <div class="left-panel">
        <div class="card">
            <div class="card-header-custom"><h3><i class="fas fa-edit"></i> Nội dung Sửa chữa</h3></div>
            <div class="card-body-custom">
                <div class="form-group">
                    <label>Loại công việc</label>
                    <input type="text" name="work_type" value="Bảo trì / Sửa chữa">
                </div>
                <div class="form-group">
                    <label>Mô tả sự cố / Yêu cầu <span class="required">*</span></label>
                    <textarea name="noi_dung" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label>Xác định Hư hỏng</label>
                    <textarea name="hu_hong" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Giải pháp / Kết quả xử lý</label>
                    <textarea name="xu_ly" rows="3"></textarea>
                </div>
            </div>
        </div>

        <div class="card mt-20">
            <div class="card-header-custom"><h3><i class="fas fa-clock"></i> Thời gian thực hiện (Giờ:Phút Ngày/Tháng/Năm)</h3></div>
            <div class="card-body-custom">
                <div class="form-row">
                    <!-- NHẬP NHANH CÓ MẶT -->
                    <div class="form-group half">
                        <label>Thời điểm có mặt</label>
                        <div style="display: flex; align-items: center; gap: 8px;">
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

                    <!-- NHẬP NHANH HOÀN THÀNH -->
                    <div class="form-group half">
                        <label>Thời điểm hoàn thành</label>
                        <div style="display: flex; align-items: center; gap: 8px;">
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
            </div>
        </div>
    </div>

    <div class="right-panel">
        <div class="card">
            <div class="card-header-custom"><h3><i class="fas fa-cog"></i> Thông tin Đối tượng</h3></div>
            <div class="card-body-custom">
                <div class="form-group">
                    <label>Chọn Dự án <span class="required">*</span></label>
                    <select name="project_id" required class="input-highlight" onchange="loadDevices(this.value)">
                        <option value="">-- Chọn dự án --</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($preselected_project_id == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['ten_du_an']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Loại đối tượng:</label>
                    <div style="display: flex; gap: 15px; margin-top: 5px;">
                        <label><input type="radio" name="target_mode" value="device" checked onclick="toggleTargetMode('device')"> Thiết bị</label>
                        <label><input type="radio" name="target_mode" value="custom" onclick="toggleTargetMode('custom')"> Nhập tay</label>
                    </div>
                </div>
                <div id="device-selection-area">
                    <div class="form-group">
                        <label>Thiết bị</label>
                        <select id="device_id" name="device_id" class="input-highlight" disabled>
                            <option value="">-- Chọn dự án trước --</option>
                        </select>
                    </div>
                </div>
                <div id="custom-name-area" style="display: none;">
                    <div class="form-group">
                        <label>Tên Đối tượng <span class="required">*</span></label>
                        <input type="text" name="custom_device_name" placeholder="VD: Phần mềm...">
                    </div>
                </div>
                <div class="form-group">
                    <label>Ngày yêu cầu <span class="required">*</span></label>
                    <input type="date" name="ngay_su_co" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Chi phí</label>
                    <input type="number" name="chi_phi" value="0" step="1000">
                </div>
            </div>
        </div>
        <div class="card mt-20">
            <div class="card-header-custom"><h3><i class="fas fa-user-tag"></i> Khách hàng</h3></div>
            <div class="card-body-custom">
                <div class="form-group"><label>Đại diện</label><input type="text" name="client_name"></div>
                <div class="form-group"><label>SĐT</label><input type="text" name="client_phone"></div>
            </div>
        </div>
    </div>
</form>

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
