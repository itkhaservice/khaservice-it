<?php
$device_id = $_GET['id'] ?? null;
$device = null;

if ($device_id) {
    $stmt = $pdo->prepare("SELECT * FROM devices WHERE id = ?");
    $stmt->execute([$device_id]);
    $device = $stmt->fetch();
}

if (!$device) {
    set_message('error', 'Thiết bị không tìm thấy!');
    header("Location: index.php?page=devices/list");
    exit;
}

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
            $sql = "UPDATE devices SET
                        ma_tai_san = ?, ten_thiet_bi = ?, nhom_thiet_bi = ?, loai_thiet_bi = ?, model = ?, serial = ?,
                        project_id = ?, parent_id = ?, supplier_id = ?, ngay_mua = ?, gia_mua = ?, bao_hanh_den = ?, trang_thai = ?, ghi_chu = ?
                    WHERE id = ?";
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
                $_POST['ghi_chu'],
                $device_id
            ]);
            set_message('success', 'Thiết bị đã được cập nhật thành công!');
            header("Location: index.php?page=devices/view&id=" . $device_id);
            exit;
        } catch (PDOException $e) {
            set_message('error', 'Lỗi khi cập nhật thiết bị: ' . $e->getMessage());
        }
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-edit"></i> Sửa Thiết bị: <?php echo htmlspecialchars($device['ten_thiet_bi']); ?></h2>
    <div class="header-actions">
        <a href="index.php?page=devices/view&id=<?php echo $device_id; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <button type="submit" form="edit-device-form" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Thay đổi</button>
    </div>
</div>

<form action="index.php?page=devices/edit&id=<?php echo $device_id; ?>" method="POST" id="edit-device-form" class="edit-layout">
    
    <!-- Left Column: Device Identity -->
    <div class="left-panel">
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-microchip"></i> Thông tin Thiết bị</h3>
            </div>
            <div class="card-body-custom">
                <div class="form-group">
                    <label for="ma_tai_san">Mã Tài sản <span class="required">*</span></label>
                    <input type="text" id="ma_tai_san" name="ma_tai_san" value="<?php echo htmlspecialchars($_POST['ma_tai_san'] ?? $device['ma_tai_san']); ?>" required class="input-highlight">
                </div>
                
                <div class="form-group">
                    <label for="ten_thiet_bi">Tên Thiết bị <span class="required">*</span></label>
                    <input type="text" id="ten_thiet_bi" name="ten_thiet_bi" value="<?php echo htmlspecialchars($_POST['ten_thiet_bi'] ?? $device['ten_thiet_bi']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="parent_id">Thuộc thiết bị (Nếu là linh kiện con)</label>
                    <div class="searchable-select-container">
                        <input type="text" id="parent_search" class="search-input" placeholder="Chọn dự án trước..." disabled autocomplete="off">
                        <button type="button" class="btn-clear" onclick="clearSearch('parent')" title="Xóa chọn"><i class="fas fa-times"></i></button>
                        <input type="hidden" name="parent_id" id="parent_id" value="<?php echo htmlspecialchars($device['parent_id'] ?? ''); ?>">
                        <div id="parent_dropdown" class="searchable-dropdown"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group half">
                        <label for="nhom_thiet_bi">Nhóm Thiết bị</label>
                        <select id="nhom_thiet_bi" name="nhom_thiet_bi">
                            <?php foreach ($db_groups as $group): ?>
                                <option value="<?php echo htmlspecialchars($group); ?>" <?php echo (($_POST['nhom_thiet_bi'] ?? $device['nhom_thiet_bi']) == $group) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($group); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group half">
                        <label for="loai_thiet_bi">Loại Thiết bị</label>
                        <input type="text" id="loai_thiet_bi" name="loai_thiet_bi" list="common_types" value="<?php echo htmlspecialchars($_POST['loai_thiet_bi'] ?? $device['loai_thiet_bi']); ?>" placeholder="Chọn hoặc gõ loại mới...">
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
                        <input type="text" id="model" name="model" value="<?php echo htmlspecialchars($_POST['model'] ?? $device['model']); ?>">
                    </div>
                    <div class="form-group half">
                        <label for="serial">Serial Number</label>
                        <input type="text" id="serial" name="serial" value="<?php echo htmlspecialchars($_POST['serial'] ?? $device['serial']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                     <label for="ghi_chu">Ghi chú</label>
                     <textarea id="ghi_chu" name="ghi_chu" rows="5" placeholder="Ghi chú thêm..."><?php echo htmlspecialchars($_POST['ghi_chu'] ?? $device['ghi_chu']); ?></textarea>
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
                            <option value="<?php echo htmlspecialchars($status['status_name']); ?>" <?php echo (($_POST['trang_thai'] ?? $device['trang_thai']) == $status['status_name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status['status_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="project_id">Dự án</label>
                    <div class="searchable-select-container">
                        <input type="text" id="project_search" class="search-input" placeholder="Gõ tên hoặc mã dự án..." value="<?php 
                            if ($device['project_id']) {
                                foreach($projects as $p) {
                                    if($p['id'] == $device['project_id']) {
                                        echo htmlspecialchars($p['ten_du_an']);
                                        break;
                                    }
                                }
                            }
                        ?>" autocomplete="off">
                        <button type="button" class="btn-clear" onclick="clearSearch('project')" title="Xóa chọn"><i class="fas fa-times"></i></button>
                        <input type="hidden" name="project_id" id="project_id" value="<?php echo htmlspecialchars($device['project_id'] ?? ''); ?>">
                        <div id="project_dropdown" class="searchable-dropdown"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="supplier_id">Nhà cung cấp</label>
                    <select id="supplier_id" name="supplier_id">
                        <option value="">-- Chọn nhà cung cấp --</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['id']; ?>" <?php echo (($_POST['supplier_id'] ?? $device['supplier_id']) == $supplier['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($supplier['ten_npp']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group half">
                        <label for="ngay_mua">Ngày mua</label>
                        <input type="date" id="ngay_mua" name="ngay_mua" value="<?php echo htmlspecialchars($_POST['ngay_mua'] ?? $device['ngay_mua']); ?>">
                    </div>
                    <div class="form-group half">
                        <label for="bao_hanh_den">Bảo hành đến</label>
                        <input type="date" id="bao_hanh_den" name="bao_hanh_den" value="<?php echo htmlspecialchars($_POST['bao_hanh_den'] ?? $device['bao_hanh_den']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="gia_mua">Giá mua (VNĐ)</label>
                    <div class="input-icon-wrapper">
                        <input type="number" id="gia_mua" name="gia_mua" step="1000" value="<?php echo htmlspecialchars($_POST['gia_mua'] ?? $device['gia_mua']); ?>">
                        <i class="fas fa-tag input-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
let localProjects = <?php echo json_encode($projects); ?>;
let localParents = [];
let activeIndex = -1;
const currentDeviceId = "<?php echo $device_id; ?>";
const initialParentId = "<?php echo $device['parent_id']; ?>";

document.addEventListener('DOMContentLoaded', () => {
    const projectSearch = document.getElementById('project_search');
    const projectDropdown = document.getElementById('project_dropdown');
    const projectIdInput = document.getElementById('project_id');

    const parentSearch = document.getElementById('parent_search');
    const parentDropdown = document.getElementById('parent_dropdown');
    const parentIdInput = document.getElementById('parent_id');

    // Khởi tạo nếu đã có project_id
    if (projectIdInput.value) {
        loadParentDevices(projectIdInput.value);
    }

    // Event listeners cho Dự án
    projectSearch.addEventListener('input', function() {
        renderDropdown(this.value.toLowerCase().trim(), localProjects, projectDropdown, (item) => {
            selectProject(item.id, item.ten_du_an);
        });
    });
    projectSearch.addEventListener('focus', function() {
        renderDropdown(this.value.toLowerCase().trim(), localProjects, projectDropdown, (item) => {
            selectProject(item.id, item.ten_du_an);
        });
    });
    projectSearch.addEventListener('keydown', (e) => handleKeydown(e, projectDropdown));

    // Event listeners cho Thiết bị cha
    parentSearch.addEventListener('input', function() {
        renderDropdown(this.value.toLowerCase().trim(), localParents, parentDropdown, (item) => {
            selectParent(item.id, item.ten_thiet_bi, item.ma_tai_san);
        });
    });
    parentSearch.addEventListener('focus', function() {
        if (localParents.length > 0) {
            renderDropdown(this.value.toLowerCase().trim(), localParents, parentDropdown, (item) => {
                selectParent(item.id, item.ten_thiet_bi, item.ma_tai_san);
            });
        }
    });
    parentSearch.addEventListener('keydown', (e) => handleKeydown(e, parentDropdown));

    document.addEventListener('click', function(e) {
        if (!projectSearch.contains(e.target) && !projectDropdown.contains(e.target)) projectDropdown.style.display = 'none';
        if (!parentSearch.contains(e.target) && !parentDropdown.contains(e.target)) parentDropdown.style.display = 'none';
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
        if (activeIndex > -1 && items[activeIndex]) {
            e.preventDefault();
            items[activeIndex].click();
        }
    }
}

function updateActiveItem(items) {
    items.forEach((item, index) => {
        item.classList.toggle('active', index === activeIndex);
        if (index === activeIndex) item.scrollIntoView({ block: 'nearest' });
    });
}

function renderDropdown(filter, data, dropdown, onSelect) {
    const filtered = data.filter(item => {
        const title = (item.ten_du_an || item.ten_thiet_bi || '').toLowerCase();
        const sub = (item.ma_tai_san || '').toLowerCase();
        return title.includes(filter) || sub.includes(filter);
    });

    if (filtered.length === 0) {
        dropdown.innerHTML = '<div class="no-results">Không tìm thấy kết quả</div>';
    } else {
        dropdown.innerHTML = filtered.map(item => `
            <div class="dropdown-item" data-id="${item.id}">
                <span class="item-title">${item.ten_du_an || item.ten_thiet_bi}</span>
                ${item.ma_tai_san ? `<span class="item-sub">${item.ma_tai_san}</span>` : ''}
            </div>
        `).join('');

        dropdown.querySelectorAll('.dropdown-item').forEach((div, idx) => {
            div.onclick = () => onSelect(filtered[idx]);
        });
    }
    dropdown.style.display = 'block';
    activeIndex = -1;
}

function selectProject(id, name) {
    document.getElementById('project_search').value = name;
    document.getElementById('project_id').value = id;
    document.getElementById('project_dropdown').style.display = 'none';
    
    // Reset và tải parent devices
    document.getElementById('parent_search').value = '';
    document.getElementById('parent_id').value = '';
    loadParentDevices(id);
}

function selectParent(id, name, code) {
    document.getElementById('parent_search').value = `${name} (${code})`;
    document.getElementById('parent_id').value = id;
    document.getElementById('parent_dropdown').style.display = 'none';
}

function loadParentDevices(projectId) {
    const parentSearch = document.getElementById('parent_search');
    const parentIdInput = document.getElementById('parent_id');
    
    parentSearch.disabled = false;
    parentSearch.placeholder = 'Đang tải...';
    
    fetch(`api/get_devices_by_project.php?project_id=${projectId}`)
        .then(r => r.json())
        .then(data => {
            // Loại bỏ chính thiết bị đang sửa khỏi danh sách cha và chỉ lấy thiết bị chính
            localParents = data.filter(d => d.id != currentDeviceId && !d.parent_id);
            parentSearch.placeholder = 'Gõ tên hoặc mã thiết bị cha...';
            
            // Nếu có initial parent, hiển thị tên nó
            if (initialParentId && parentIdInput.value == initialParentId) {
                const p = localParents.find(d => d.id == initialParentId);
                if (p) parentSearch.value = `${p.ten_thiet_bi} (${p.ma_tai_san})`;
            }
        });
}

function clearSearch(type) {
    const searchInput = document.getElementById(type + '_search');
    const idInput = document.getElementById(type + '_id');
    const dropdown = document.getElementById(type + '_dropdown');
    
    searchInput.value = '';
    idInput.value = '';
    dropdown.style.display = 'none';
    
    if (type === 'project') {
        const parentSearch = document.getElementById('parent_search');
        const parentIdInput = document.getElementById('parent_id');
        parentSearch.value = '';
        parentIdInput.value = '';
        parentSearch.disabled = true;
        parentSearch.placeholder = 'Chọn dự án trước...';
        localParents = [];
    }
}
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

/* Searchable Select */
.searchable-select-container { position: relative; width: 100%; }
.search-input { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; transition: all 0.2s; }
.search-input:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); outline: none; }
.search-input:disabled { background-color: #f1f5f9; cursor: not-allowed; opacity: 0.7; }

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
    .page-header { flex-direction: column; align-items: flex-start; gap: 15px; }
    .header-actions { 
        width: 100%; 
        display: flex; 
        flex-direction: row !important; 
        flex-wrap: wrap; 
        gap: 8px; 
    }
    .header-actions .btn, .header-actions a { 
        flex: 1 1 auto;
        min-width: calc(50% - 8px); 
        justify-content: center;
        height: 40px;
        font-size: 0.85rem;
    }
    
    .form-row { flex-direction: column; gap: 0; }
    .form-group.half { width: 100%; }
    
    .card { padding: 15px; }
    .card-header-custom { margin-bottom: 15px; padding-bottom: 10px; }
    .card-header-custom h3 { font-size: 1.1rem; }
}
</style>