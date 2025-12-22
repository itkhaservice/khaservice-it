<?php
// Fetch projects and suppliers for dropdowns
$projects_stmt = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an");
$projects = $projects_stmt->fetchAll();

$suppliers_stmt = $pdo->query("SELECT id, ten_npp FROM suppliers ORDER BY ten_npp");
$suppliers = $suppliers_stmt->fetchAll();

// Fetch dynamic settings
$db_types = $pdo->query("SELECT * FROM settings_device_types ORDER BY group_name, type_name")->fetchAll();
$db_groups = array_unique(array_column($db_types, 'group_name'));
$db_statuses = $pdo->query("SELECT * FROM settings_device_statuses ORDER BY id ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    if (empty($_POST['ma_tai_san'])) {
        set_message('error', 'Mã tài sản là bắt buộc.');
    }
    if (empty($_POST['ten_thiet_bi'])) {
        set_message('error', 'Tên thiết bị là bắt buộc.');
    }

    if (!isset($_SESSION['messages']) || empty(array_filter($_SESSION['messages'], function($msg) { return $msg['type'] === 'error'; }))) {
        try {
            $sql = "INSERT INTO devices (
                        ma_tai_san, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, model, serial,
                        project_id, parent_id, supplier_id, ngay_mua, gia_mua, bao_hanh_den, trang_thai, ghi_chu
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['ma_tai_san'],
                $_POST['ten_thiet_bi'],
                $_POST['nhom_thiet_bi'],
                $_POST['loai_thiet_bi'],
                $_POST['model'],
                $_POST['serial'],
                $_POST['project_id'] ?: null,
                $_POST['parent_id'] ?: null,
                $_POST['supplier_id'] ?: null,
                $_POST['ngay_mua'] ?: null,
                $_POST['gia_mua'] ?: null,
                $_POST['bao_hanh_den'] ?: null,
                $_POST['trang_thai'],
                $_POST['ghi_chu']
            ]);
            set_message('success', 'Thiết bị đã được thêm mới thành công!');
            header("Location: index.php?page=devices/list");
            exit;
        } catch (PDOException $e) {
            set_message('error', 'Lỗi khi thêm thiết bị: ' . $e->getMessage());
        }
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-plus-circle"></i> Thêm Thiết bị mới</h2>
    <div class="header-actions">
        <a href="index.php?page=devices/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <button type="submit" form="add-device-form" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Thiết bị</button>
    </div>
</div>

<form action="index.php?page=devices/add" method="POST" id="add-device-form" class="edit-layout">
    
    <!-- Left Column: Device Identity -->
    <div class="left-panel">
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-microchip"></i> Thông tin Thiết bị</h3>
            </div>
            <div class="card-body-custom">
                <div class="form-group">
                    <label for="ma_tai_san">Mã Tài sản <span class="required">*</span></label>
                    <input type="text" id="ma_tai_san" name="ma_tai_san" value="<?php echo htmlspecialchars($_POST['ma_tai_san'] ?? ''); ?>" required class="input-highlight" placeholder="VD: KHAS-DA01-PC-001">
                </div>
                
                <div class="form-group">
                    <label for="ten_thiet_bi">Tên Thiết bị <span class="required">*</span></label>
                    <input type="text" id="ten_thiet_bi" name="ten_thiet_bi" value="<?php echo htmlspecialchars($_POST['ten_thiet_bi'] ?? ''); ?>" required placeholder="VD: Dell OptiPlex 3050">
                </div>

                <div class="form-group">
                    <label for="parent_id">Thuộc thiết bị (Nếu là linh kiện con)</label>
                    <select id="parent_id" name="parent_id" disabled>
                        <option value="">-- Chọn dự án trước --</option>
                    </select>
                    <small class="text-muted">Chọn nếu thiết bị này là một thành phần bên trong (vd: RAM, SSD của một CPU).</small>
                </div>

                <div class="form-row">
                    <div class="form-group half">
                        <label for="nhom_thiet_bi">Nhóm Thiết bị</label>
                        <select id="nhom_thiet_bi" name="nhom_thiet_bi">
                            <?php foreach ($db_groups as $group): ?>
                                <option value="<?php echo htmlspecialchars($group); ?>" <?php echo (($_POST['nhom_thiet_bi'] ?? '') == $group) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($group); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group half">
                        <label for="loai_thiet_bi">Loại Thiết bị</label>
                        <input type="text" id="loai_thiet_bi" name="loai_thiet_bi" list="common_types" value="<?php echo htmlspecialchars($_POST['loai_thiet_bi'] ?? ''); ?>" placeholder="Chọn hoặc gõ loại mới...">
                        <datalist id="common_types">
                            <?php foreach ($db_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['type_name']); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group half">
                        <label for="model">Model</label>
                        <input type="text" id="model" name="model" value="<?php echo htmlspecialchars($_POST['model'] ?? ''); ?>">
                    </div>
                    <div class="form-group half">
                        <label for="serial">Serial Number</label>
                        <input type="text" id="serial" name="serial" value="<?php echo htmlspecialchars($_POST['serial'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                     <label for="ghi_chu">Ghi chú</label>
                     <textarea id="ghi_chu" name="ghi_chu" rows="5" placeholder="Ghi chú thêm..."><?php echo htmlspecialchars($_POST['ghi_chu'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Management Info -->
    <div class="right-panel">
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-tasks"></i> Quản lý & Mua sắm</h3>
            </div>
            <div class="card-body-custom">
                 <div class="form-group">
                    <label for="trang_thai">Trạng thái</label>
                    <select id="trang_thai" name="trang_thai">
                        <?php foreach ($db_statuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status['status_name']); ?>" <?php echo (($_POST['trang_thai'] ?? '') == $status['status_name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status['status_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="project_id">Dự án</label>
                    <select id="project_id" name="project_id" onchange="loadParentDevices(this.value)">
                        <option value="">-- Chọn dự án --</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>" <?php echo (($_POST['project_id'] ?? '') == $project['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($project['ten_du_an']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="supplier_id">Nhà cung cấp</label>
                    <select id="supplier_id" name="supplier_id">
                        <option value="">-- Chọn nhà cung cấp --</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['id']; ?>" <?php echo (($_POST['supplier_id'] ?? '') == $supplier['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($supplier['ten_npp']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group half">
                        <label for="ngay_mua">Ngày mua</label>
                        <input type="date" id="ngay_mua" name="ngay_mua" value="<?php echo htmlspecialchars($_POST['ngay_mua'] ?? ''); ?>">
                    </div>
                    <div class="form-group half">
                        <label for="bao_hanh_den">Bảo hành đến</label>
                        <input type="date" id="bao_hanh_den" name="bao_hanh_den" value="<?php echo htmlspecialchars($_POST['bao_hanh_den'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="gia_mua">Giá mua (VNĐ)</label>
                    <div class="input-icon-wrapper">
                        <input type="number" id="gia_mua" name="gia_mua" step="1000" value="<?php echo htmlspecialchars($_POST['gia_mua'] ?? ''); ?>">
                        <i class="fas fa-tag input-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function loadParentDevices(projectId) {
    const parentSelect = document.getElementById('parent_id');
    if (!projectId) {
        parentSelect.innerHTML = '<option value="">-- Chọn dự án trước --</option>';
        parentSelect.disabled = true;
        return;
    }

    parentSelect.innerHTML = '<option value="">Đang tải...</option>';
    parentSelect.disabled = true;

    fetch(`api/get_devices_by_project.php?project_id=${projectId}`)
        .then(response => response.json())
        .then(data => {
            parentSelect.innerHTML = '<option value="">-- Là thiết bị chính (Không có cha) --</option>';
            data.forEach(device => {
                // Chỉ hiện các thiết bị chính (không có parent_id) làm cha để tránh lồng nhau quá sâu
                // Lưu ý: Logic này có thể tùy chỉnh nếu muốn lồng nhiều cấp
                parentSelect.innerHTML += `<option value="${device.id}">${device.ten_thiet_bi} (${device.ma_tai_san})</option>`;
            });
            parentSelect.disabled = false;
        })
        .catch(error => {
            console.error('Error loading parent devices:', error);
            parentSelect.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
        });
}

// Tự động load nếu đã có dự án được chọn (trường hợp quay lại do lỗi validation)
document.addEventListener('DOMContentLoaded', () => {
    const projectId = document.getElementById('project_id').value;
    if (projectId) {
        loadParentDevices(projectId);
    }
});
</script>

<style>
/* Layout Styles - TỐI ƯU CHO CẢ DESKTOP VÀ MOBILE */
.edit-layout {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 30px;
    align-items: start;
}

.left-panel, .right-panel {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.card-header-custom {
    padding-bottom: 20px;
    margin-bottom: 25px;
    border-bottom: 1px solid #f1f5f9;
}

.card-header-custom h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-color);
    display: flex; align-items: center; gap: 12px;
}

.card-header-custom h3 i {
    color: #fff; background: var(--gradient-primary); 
    padding: 8px; border-radius: 8px; font-size: 1rem;
    box-shadow: 0 4px 6px -1px rgba(36, 162, 92, 0.3);
}

.form-row {
    display: flex;
    gap: 20px;
}

.form-group.half { flex: 1; }

.input-highlight {
    background-color: #f8fafc;
    border-color: #cbd5e1;
    color: var(--primary-dark-color);
    font-weight: 600;
}

/* RESPONSIVE BREAKPOINTS */
@media (max-width: 992px) {
    .edit-layout { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
    .page-header { flex-direction: column; align-items: flex-start; gap: 15px; }
    .header-actions { width: 100%; display: flex; flex-direction: column; gap: 10px; }
    .header-actions .btn { width: 100%; justify-content: center; height: 44px; }
    
    .form-row { flex-direction: column; gap: 0; }
    .form-group.half { width: 100%; }
    
    .card { padding: 15px; }
    .card-header-custom { margin-bottom: 15px; padding-bottom: 10px; }
    .card-header-custom h3 { font-size: 1.1rem; }
}
</style>