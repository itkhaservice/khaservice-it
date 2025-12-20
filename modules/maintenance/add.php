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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['device_id']) || empty($_POST['ngay_su_co']) || empty($_POST['noi_dung'])) {
        set_message('error', 'Vui lòng điền đầy đủ các thông tin bắt buộc (*).');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO maintenance_logs 
                (device_id, ngay_su_co, noi_dung, hu_hong, xu_ly, chi_phi, client_name, client_phone, arrival_time, completion_time, work_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['device_id'],
                $_POST['ngay_su_co'],
                $_POST['noi_dung'],
                $_POST['hu_hong'],
                $_POST['xu_ly'],
                $_POST['chi_phi'] ?: 0,
                $_POST['client_name'],
                $_POST['client_phone'],
                $_POST['arrival_time'] ?: null,
                $_POST['completion_time'] ?: null,
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

<div class="page-header">
    <h2><i class="fas fa-plus-circle"></i> Tạo Phiếu Bảo trì mới</h2>
    <div class="header-actions">
        <a href="index.php?page=maintenance/history" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <button type="submit" form="add-maintenance-form" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Phiếu</button>
    </div>
</div>

<form action="index.php?page=maintenance/add" method="POST" id="add-maintenance-form" class="edit-layout">
    <!-- LEFT: Technical Details -->
    <div class="left-panel">
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-edit"></i> Nội dung Sửa chữa</h3>
            </div>
            <div class="card-body-custom">
                <div class="form-group">
                    <label for="work_type">Loại công việc (Hiển thị trên phiếu)</label>
                    <input type="text" id="work_type" name="work_type" value="<?php echo htmlspecialchars($_POST['work_type'] ?? 'Bảo trì / Sửa chữa'); ?>" placeholder="VD: Sửa chữa sự cố, Bảo trì định kỳ...">
                </div>

                <div class="form-group">
                    <label for="noi_dung">Mô tả sự cố / Yêu cầu <span class="required">*</span></label>
                    <textarea id="noi_dung" name="noi_dung" rows="4" required placeholder="Ghi nhận từ dự án báo về..."><?php echo htmlspecialchars($_POST['noi_dung'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="hu_hong">Xác định Hư hỏng</label>
                    <textarea id="hu_hong" name="hu_hong" rows="3" placeholder="Nguyên nhân thực tế sau khi kiểm tra..."><?php echo htmlspecialchars($_POST['hu_hong'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="xu_ly">Giải pháp / Kết quả xử lý</label>
                    <textarea id="xu_ly" name="xu_ly" rows="3" placeholder="Đã thay thế linh kiện gì, sửa như thế nào..."><?php echo htmlspecialchars($_POST['xu_ly'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <div class="card mt-20">
            <div class="card-header-custom">
                <h3><i class="fas fa-clock"></i> Thời gian thực hiện</h3>
            </div>
            <div class="card-body-custom">
                <div class="form-row">
                    <div class="form-group half">
                        <label>Thời điểm có mặt</label>
                        <input type="datetime-local" name="arrival_time" value="<?php echo $_POST['arrival_time'] ?? ''; ?>">
                    </div>
                    <div class="form-group half">
                        <label>Thời điểm hoàn thành</label>
                        <input type="datetime-local" name="completion_time" value="<?php echo $_POST['completion_time'] ?? ''; ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Management -->
    <div class="right-panel">
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-cog"></i> Thông tin chung</h3>
            </div>
            <div class="card-body-custom">
                <!-- Chọn Dự án -->
                <div class="form-group">
                    <label for="project_select">Chọn Dự án <span class="required">*</span></label>
                    <select id="project_select" class="input-highlight" onchange="loadDevices(this.value)">
                        <option value="">-- Chọn dự án --</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($preselected_project_id == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['ten_du_an']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Chọn Thiết bị -->
                <div class="form-group">
                    <label for="device_id">Thiết bị bảo trì <span class="required">*</span></label>
                    <select id="device_id" name="device_id" required class="input-highlight" disabled>
                        <option value="">-- Vui lòng chọn dự án trước --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="ngay_su_co">Thời điểm yêu cầu <span class="required">*</span></label>
                    <input type="date" id="ngay_su_co" name="ngay_su_co" required value="<?php echo $_POST['ngay_su_co'] ?? date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="chi_phi">Chi phí phát sinh (VNĐ)</label>
                    <div class="input-icon-wrapper">
                        <input type="number" id="chi_phi" name="chi_phi" value="<?php echo htmlspecialchars($_POST['chi_phi'] ?? '0'); ?>" step="1000">
                        <i class="fas fa-money-bill-wave input-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-20">
            <div class="card-header-custom">
                <h3><i class="fas fa-user-tag"></i> Đại diện Khách hàng</h3>
            </div>
            <div class="card-body-custom">
                <div class="form-group">
                    <label>Tên đại diện</label>
                    <input type="text" name="client_name" placeholder="Người ký nhận..." value="<?php echo htmlspecialchars($_POST['client_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="text" name="client_phone" placeholder="Liên hệ..." value="<?php echo htmlspecialchars($_POST['client_phone'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>
</form>

<script>
const preselectedDeviceId = "<?php echo $preselected_device_id; ?>";
const preselectedProjectId = "<?php echo $preselected_project_id; ?>";

function loadDevices(projectId, selectedDeviceId = null) {
    const deviceSelect = document.getElementById('device_id');
    
    if (!projectId) {
        deviceSelect.innerHTML = '<option value="">-- Vui lòng chọn dự án trước --</option>';
        deviceSelect.disabled = true;
        return;
    }

    deviceSelect.disabled = true; 
    deviceSelect.innerHTML = '<option value="">Đang tải thiết bị...</option>';

    fetch(`api/get_devices_by_project.php?project_id=${projectId}`)
        .then(response => response.json())
        .then(data => {
            deviceSelect.innerHTML = '<option value="">-- Chọn thiết bị --</option>';
            if (data.length > 0) {
                data.forEach(device => {
                    const option = document.createElement('option');
                    option.value = device.id;
                    option.textContent = `${device.ten_thiet_bi} (${device.ma_tai_san})`;
                    if (selectedDeviceId && selectedDeviceId == device.id) {
                        option.selected = true;
                    }
                    deviceSelect.appendChild(option);
                });
                deviceSelect.disabled = false;
            } else {
                deviceSelect.innerHTML = '<option value="">Dự án này chưa có thiết bị</option>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            deviceSelect.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
        });
}

document.addEventListener('DOMContentLoaded', () => {
    if (preselectedProjectId) {
        loadDevices(preselectedProjectId, preselectedDeviceId);
    }
});
</script>
