<?php
$projects = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an")->fetchAll();
$suppliers = $pdo->query("SELECT id, ten_npp FROM suppliers ORDER BY ten_npp")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['ten_dich_vu']) || empty($_POST['ngay_het_han'])) {
        set_message('error', 'Vui lòng điền tên dịch vụ và ngày hết hạn.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO services (ten_dich_vu, loai_dich_vu, supplier_id, project_id, ngay_dang_ky, ngay_het_han, chi_phi_gia_han, nhac_truoc_ngay, ghi_chu) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['ten_dich_vu'], $_POST['loai_dich_vu'],
                $_POST['supplier_id'] ?: null, $_POST['project_id'] ?: null,
                $_POST['ngay_dang_ky'] ?: null, $_POST['ngay_het_han'],
                $_POST['chi_phi_gia_han'] ?: 0, $_POST['nhac_truoc_ngay'] ?: 30,
                $_POST['ghi_chu']
            ]);
            set_message('success', 'Đã thêm dịch vụ thành công!');
            header("Location: index.php?page=services/list");
            exit;
        } catch (PDOException $e) { set_message('error', 'Lỗi: ' . $e->getMessage()); }
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-plus-circle"></i> Thêm Dịch vụ mới</h2>
    <div class="header-actions">
        <a href="index.php?page=services/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <button type="submit" form="add-service-form" class="btn btn-primary">Lưu dịch vụ</button>
    </div>
</div>

<form action="index.php?page=services/add" method="POST" id="add-service-form" class="edit-layout">
    <div class="left-panel">
        <div class="card">
            <div class="card-header-custom"><h3><i class="fas fa-info-circle"></i> Thông tin chung</h3></div>
            <div class="card-body-custom">
                <div class="form-group"><label>Tên dịch vụ <span class="required">*</span></label><input type="text" name="ten_dich_vu" required placeholder="VD: Google Workspace, Domain khaservice.vn"></div>
                <div class="form-group"><label>Loại dịch vụ</label><input type="text" name="loai_dich_vu" placeholder="VD: Cloud, Mail, Domain, Hosting..."></div>
                <div class="form-group"><label>Nhà cung cấp</label>
                    <select name="supplier_id">
                        <option value="">-- Chọn nhà cung cấp --</option>
                        <?php foreach($suppliers as $sup): ?><option value="<?php echo $sup['id']; ?>"><?php echo htmlspecialchars($sup['ten_npp']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Dự án sử dụng</label>
                    <div class="searchable-select-container">
                        <input type="text" id="project_search" class="search-input" placeholder="Gõ tên dự án hoặc để trống nếu dùng chung..." autocomplete="off">
                        <input type="hidden" name="project_id" id="project_id">
                        <div id="project_dropdown" class="searchable-dropdown"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="right-panel">
        <div class="card">
            <div class="card-header-custom"><h3><i class="fas fa-calendar-alt"></i> Thời hạn & Chi phí</h3></div>
            <div class="card-body-custom">
                <div class="form-group">
                    <label>Ngày hết hạn <span class="required">*</span></label>
                    <input type="date" name="ngay_het_han" required value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                    <small class="text-muted" style="display: block; margin-top: 5px;"><i class="fas fa-info-circle"></i> Hệ thống tự động lặp lại hàng năm (chỉ cần chọn đúng Ngày/Tháng).</small>
                </div>
                <div class="form-group"><label>Chi phí gia hạn (VNĐ)</label>
                    <input type="text" id="chi_phi_format" class="input-highlight" placeholder="0" value="0">
                    <input type="hidden" name="chi_phi_gia_han" id="chi_phi_gia_han" value="0">
                </div>
                <div class="form-group"><label>Nhắc trước (ngày)</label><input type="number" name="nhac_truoc_ngay" value="30"></div>
                <div class="form-group"><label>Ghi chú</label><textarea name="ghi_chu" rows="4"></textarea></div>
            </div>
        </div>
    </div>
</form>

<style>
.input-highlight { font-weight: 700; color: #108042; font-size: 1.1rem; }
/* Layout Styles */
.edit-layout { display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; align-items: start; }
.left-panel, .right-panel { display: flex; flex-direction: column; gap: 20px; }
.card-header-custom { padding-bottom: 20px; margin-bottom: 25px; border-bottom: 1px solid #f1f5f9; }
.card-header-custom h3 { margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 12px; }
.card-header-custom h3 i { color: #fff; background: var(--gradient-primary); padding: 8px; border-radius: 8px; font-size: 1rem; }

/* Searchable Select */
.searchable-select-container { position: relative; width: 100%; }
.search-input { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; }
.search-input:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); outline: none; }
.searchable-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #cbd5e1; border-radius: 8px; margin-top: 5px; max-height: 250px; overflow-y: auto; z-index: 1000; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); display: none; }
.dropdown-item { padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #f1f5f9; text-align: left; }
.dropdown-item:hover { background: #f8fafc; color: var(--primary-color); }
.dropdown-item.active { background: #f8fafc; color: var(--primary-color); }
.item-title { font-weight: 600; font-size: 0.9rem; }
.no-results { padding: 15px; text-align: center; color: #94a3b8; font-size: 0.9rem; }

@media (max-width: 992px) { .edit-layout { grid-template-columns: 1fr; } }
</style>

<script>
let localProjects = <?php echo json_encode($projects); ?>;
let activeIndex = -1;

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('project_search');
    const dropdown = document.getElementById('project_dropdown');
    const hiddenInput = document.getElementById('project_id');

    searchInput.addEventListener('input', function() {
        renderDropdown(this.value.toLowerCase().trim());
    });
    searchInput.addEventListener('focus', function() {
        renderDropdown(this.value.toLowerCase().trim());
    });

    searchInput.addEventListener('keydown', function(e) {
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
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    // Currency Formatting
    const chiPhiFormat = document.getElementById('chi_phi_format');
    const chiPhiHidden = document.getElementById('chi_phi_gia_han');

    if (chiPhiFormat) {
        chiPhiFormat.addEventListener('input', function(e) {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value === '') value = '0';
            chiPhiHidden.value = value;
            this.value = parseInt(value).toLocaleString('vi-VN');
        });
    }
});

function renderDropdown(filter) {
    const dropdown = document.getElementById('project_dropdown');
    const filtered = localProjects.filter(p => p.ten_du_an.toLowerCase().includes(filter));

    if (filtered.length === 0) {
        dropdown.innerHTML = '<div class="no-results">Không tìm thấy dự án</div>';
    } else {
        dropdown.innerHTML = filtered.map(p => `
            <div class="dropdown-item" onclick="selectProject(${p.id}, '${p.ten_du_an.replace(/'/g, "\\'")}')">
                <span class="item-title">${p.ten_du_an}</span>
            </div>
        `).join('');
    }
    dropdown.style.display = 'block';
    activeIndex = -1;
}

function selectProject(id, name) {
    document.getElementById('project_search').value = name;
    document.getElementById('project_id').value = id;
    document.getElementById('project_dropdown').style.display = 'none';
}

function updateActiveItem(items) {
    items.forEach((item, index) => {
        item.classList.toggle('active', index === activeIndex);
        if (index === activeIndex) item.scrollIntoView({ block: 'nearest' });
    });
}
</script>
