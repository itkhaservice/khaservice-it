<?php
$id = $_GET['id'] ?? null;
if (!$id) { header("Location: index.php?page=maintenance/history"); exit; }

$stmt = $pdo->prepare("SELECT * FROM maintenance_logs WHERE id = ?");
$stmt->execute([$id]);
$log = $stmt->fetch();

if (!$log) { set_message('error', 'Không tìm thấy phiếu.'); header("Location: index.php?page=maintenance/history"); exit; }

$projects = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an")->fetchAll();
$stmt_current_proj = $pdo->prepare("SELECT project_id FROM devices WHERE id = ?");
$stmt_current_proj->execute([$log['device_id']]);
$current_project_id = $stmt_current_proj->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['device_id']) || empty($_POST['ngay_su_co']) || empty($_POST['noi_dung'])) {
        set_message('error', 'Vui lòng điền thông tin bắt buộc.');
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE maintenance_logs SET device_id=?, ngay_su_co=?, noi_dung=?, hu_hong=?, xu_ly=?, chi_phi=?, client_name=?, client_phone=?, arrival_time=?, completion_time=?, work_type=? WHERE id=?");
            $stmt->execute([
                $_POST['device_id'], $_POST['ngay_su_co'], $_POST['noi_dung'], $_POST['hu_hong'], $_POST['xu_ly'], $_POST['chi_phi'] ?: 0,
                $_POST['client_name'], $_POST['client_phone'], $_POST['arrival_time'] ?: null, $_POST['completion_time'] ?: null, 
                $_POST['work_type'] ?: 'Bảo trì / Sửa chữa', $id
            ]);
            set_message('success', 'Cập nhật thành công!');
            header("Location: index.php?page=maintenance/view&id=" . $id);
            exit;
        } catch (PDOException $e) { set_message('error', 'Lỗi: ' . $e->getMessage()); }
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-edit"></i> Sửa Phiếu Bảo trì #<?php echo $id; ?></h2>
    <div class="header-actions">
        <a href="index.php?page=maintenance/view&id=<?php echo $id; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <button type="submit" form="edit-maintenance-form" class="btn btn-primary"><i class="fas fa-save"></i> Cập nhật</button>
    </div>
</div>

<form action="index.php?page=maintenance/edit&id=<?php echo $id; ?>" method="POST" id="edit-maintenance-form" class="edit-layout">
    <div class="left-panel">
        <div class="card">
            <div class="card-header-custom"><h3><i class="fas fa-edit"></i> Nội dung Sửa chữa</h3></div>
            <div class="card-body-custom">
                <div class="form-group">
                    <label for="work_type">Loại công việc (Hiển thị trên phiếu)</label>
                    <input type="text" id="work_type" name="work_type" value="<?php echo htmlspecialchars($log['work_type'] ?? 'Bảo trì / Sửa chữa'); ?>">
                </div>

                <div class="form-group">
                    <label>Mô tả sự cố / Yêu cầu <span class="required">*</span></label>
                    <textarea name="noi_dung" rows="4" required><?php echo htmlspecialchars($_POST['noi_dung'] ?? $log['noi_dung']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Xác định Hư hỏng</label>
                    <textarea name="hu_hong" rows="3"><?php echo htmlspecialchars($_POST['hu_hong'] ?? $log['hu_hong']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Giải pháp / Kết quả xử lý</label>
                    <textarea name="xu_ly" rows="3"><?php echo htmlspecialchars($_POST['xu_ly'] ?? $log['xu_ly']); ?></textarea>
                </div>
            </div>
        </div>

        <div class="card mt-20">
            <div class="card-header-custom"><h3><i class="fas fa-clock"></i> Thời gian thực hiện</h3></div>
            <div class="card-body-custom">
                <div class="form-row">
                    <div class="form-group half">
                        <label>Thời điểm có mặt</label>
                        <input type="datetime-local" name="arrival_time" value="<?php echo $log['arrival_time'] ? date('Y-m-d\TH:i', strtotime($log['arrival_time'])) : ''; ?>">
                    </div>
                    <div class="form-group half">
                        <label>Thời điểm hoàn thành</label>
                        <input type="datetime-local" name="completion_time" value="<?php echo $log['completion_time'] ? date('Y-m-d\TH:i', strtotime($log['completion_time'])) : ''; ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="right-panel">
        <div class="card">
            <div class="card-header-custom"><h3><i class="fas fa-cog"></i> Thông tin chung</h3></div>
            <div class="card-body-custom">
                <div class="form-group">
                    <label>Chọn Dự án <span class="required">*</span></label>
                    <select id="project_select" class="input-highlight" onchange="loadDevices(this.value)">
                        <option value="">-- Chọn dự án --</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($current_project_id == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['ten_du_an']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Thiết bị bảo trì <span class="required">*</span></label>
                    <select id="device_id" name="device_id" required class="input-highlight">
                        <option value="<?php echo $log['device_id']; ?>">Đang tải...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Thời điểm yêu cầu <span class="required">*</span></label>
                    <input type="date" name="ngay_su_co" required value="<?php echo $log['ngay_su_co']; ?>">
                </div>
                <div class="form-group">
                    <label>Chi phí phát sinh (VNĐ)</label>
                    <input type="number" name="chi_phi" value="<?php echo htmlspecialchars($log['chi_phi']); ?>" step="1000">
                </div>
            </div>
        </div>

        <div class="card mt-20">
            <div class="card-header-custom"><h3><i class="fas fa-user-tag"></i> Đại diện Khách hàng</h3></div>
            <div class="card-body-custom">
                <div class="form-group">
                    <label>Tên đại diện</label>
                    <input type="text" name="client_name" value="<?php echo htmlspecialchars($log['client_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="text" name="client_phone" value="<?php echo htmlspecialchars($log['client_phone'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>
</form>

<script>
const currentProjectId = "<?php echo $current_project_id; ?>";
const currentDeviceId = "<?php echo $log['device_id']; ?>";

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
    if (currentProjectId) {
        loadDevices(currentProjectId, currentDeviceId);
    }
});
</script>
