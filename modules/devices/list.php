<?php
// ==================================================
// PAGINATION CONFIG
// ==================================================
$rows_per_page = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : 5;
$current_page  = (isset($_GET['p']) && is_numeric($_GET['p'])) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

// ==================================================
// FILTER INPUT
// ==================================================
$filter_keyword = trim($_GET['filter_keyword'] ?? '');
$filter_project = trim($_GET['filter_project'] ?? '');
$filter_status  = trim($_GET['filter_status'] ?? '');
$filter_group   = trim($_GET['filter_group'] ?? '');
$filter_type    = trim($_GET['filter_type'] ?? '');

// ==================================================
// BUILD QUERY
// ==================================================
$where_clauses = ["d.deleted_at IS NULL"];
$bind_params   = [];

if ($filter_keyword !== '') {
    $where_clauses[] = "(d.ma_tai_san LIKE :kw1 OR d.ten_thiet_bi LIKE :kw2 OR d.serial LIKE :kw3 OR d.model LIKE :kw4)";
    $bind_params[':kw1'] = $bind_params[':kw2'] = $bind_params[':kw3'] = $bind_params[':kw4'] = '%' . $filter_keyword . '%';
}
if ($filter_project !== '' && is_numeric($filter_project)) {
    $where_clauses[] = "d.project_id = :project_id";
    $bind_params[':project_id'] = (int)$filter_project;
}
if ($filter_status !== '') {
    $where_clauses[] = "d.trang_thai = :trang_thai";
    $bind_params[':trang_thai'] = $filter_status;
}
if ($filter_group !== '') {
    $where_clauses[] = "d.nhom_thiet_bi = :nhom_thiet_bi";
    $bind_params[':nhom_thiet_bi'] = $filter_group;
}
if ($filter_type !== '') {
    $where_clauses[] = "d.loai_thiet_bi = :loai_thiet_bi";
    $bind_params[':loai_thiet_bi'] = $filter_type;
}

$where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

// Sorting
$allowed_sort_columns = ['ma_tai_san' => 'd.ma_tai_san', 'ten_thiet_bi' => 'd.ten_thiet_bi', 'created_at' => 'd.created_at'];
$sort_by    = $_GET['sort_by'] ?? 'created_at';
$sort_order = strtoupper($_GET['sort_order'] ?? 'DESC');
if (!array_key_exists($sort_by, $allowed_sort_columns)) $sort_by = 'created_at';
$order_sql = " ORDER BY {$allowed_sort_columns[$sort_by]} $sort_order";

// Count Total
$count_sql = "SELECT COUNT(*) FROM devices d $where_sql";
$count_stmt = $pdo->prepare($count_sql);
foreach ($bind_params as $key => $value) $count_stmt->bindValue($key, $value);
$count_stmt->execute();
$total_rows  = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $rows_per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $rows_per_page;

// Fetch Data (Cập nhật SQL JOIN thêm bảng parent)
$data_sql = "SELECT 
                d.*, 
                p.ten_du_an, 
                s.ten_npp,
                parent.ten_thiet_bi as parent_name,
                parent.ma_tai_san as parent_code
             FROM devices d 
             LEFT JOIN projects p ON d.project_id = p.id 
             LEFT JOIN suppliers s ON d.supplier_id = s.id 
             LEFT JOIN devices parent ON d.parent_id = parent.id
             $where_sql $order_sql LIMIT :limit OFFSET :offset";
             
$stmt = $pdo->prepare($data_sql);
foreach ($bind_params as $key => $value) $stmt->bindValue($key, $value);
$stmt->bindValue(':limit',  $rows_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

$projects_list = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an")->fetchAll(PDO::FETCH_ASSOC);
$statuses_config = $pdo->query("SELECT status_name, color_class FROM settings_device_statuses")->fetchAll(PDO::FETCH_KEY_PAIR);

$groups_list = $pdo->query("SELECT group_name FROM settings_device_groups ORDER BY group_name ASC")->fetchAll(PDO::FETCH_COLUMN);
$all_types = $pdo->query("SELECT type_name, group_name FROM settings_device_types ORDER BY type_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// DANH SÁCH CỘT (Đã thêm parent_name)
$all_columns = [
    'ma_tai_san'    => ['label' => 'Mã Tài sản', 'default' => true],
    'ten_thiet_bi'  => ['label' => 'Tên Thiết bị', 'default' => true],
    'parent_name'   => ['label' => 'Thuộc thiết bị', 'default' => true], // Mặc định hiện để phân biệt
    'loai_thiet_bi' => ['label' => 'Loại', 'default' => false],
    'nhom_thiet_bi' => ['label' => 'Nhóm', 'default' => false],
    'model'         => ['label' => 'Model', 'default' => false],
    'serial'        => ['label' => 'Serial', 'default' => false],
    'ten_du_an'     => ['label' => 'Dự án', 'default' => true],
    'ten_npp'       => ['label' => 'Nhà cung cấp', 'default' => false],
    'ngay_mua'      => ['label' => 'Ngày mua', 'default' => false],
    'bao_hanh_den'  => ['label' => 'Hạn BH', 'default' => false],
    'gia_mua'       => ['label' => 'Giá mua', 'default' => false],
    'trang_thai'    => ['label' => 'Trạng thái', 'default' => true],
];
?>

<div class="page-header">
    <h2><i class="fas fa-server"></i> Danh sách Thiết bị</h2>
    <?php if(isIT()): ?><a href="index.php?page=devices/add" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm mới</a><?php endif; ?>
</div>

<div class="card filter-section-modern">
    <form action="index.php" method="GET" class="filter-form-modern" id="filter-form">
        <input type="hidden" name="page" value="devices/list">
        <div class="filter-main-grid">
            <div class="filter-item">
                <label>Dự án</label>
                <div class="searchable-select-container">
                    <input type="text" id="project_search" class="form-control-sm" placeholder="Tất cả dự án..." value="<?php 
                        if ($filter_project) {
                            foreach($projects_list as $p) {
                                if($p['id'] == $filter_project) { echo htmlspecialchars($p['ten_du_an']); break; }
                            }
                        }
                    ?>" autocomplete="off">
                    <button type="button" class="btn-clear-inline" id="btn-clear-project" style="<?php echo $filter_project ? 'display:block' : 'display:none'; ?>"><i class="fas fa-times"></i></button>
                    <input type="hidden" name="filter_project" id="filter_project" value="<?php echo htmlspecialchars($filter_project); ?>">
                    <div id="project_dropdown" class="searchable-dropdown"></div>
                </div>
            </div>
            <div class="filter-item">
                <label>Nhóm</label>
                <select name="filter_group" id="filter_group" class="form-select-sm auto-submit-filter" onchange="updateTypeFilter(); this.form.submit()">
                    <option value="">-- Tất cả nhóm --</option>
                    <?php foreach ($groups_list as $g): ?>
                        <option value="<?php echo htmlspecialchars($g); ?>" <?php echo $filter_group === $g ? 'selected' : ''; ?>><?php echo htmlspecialchars($g); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <label>Loại</label>
                <select name="filter_type" id="filter_type" class="form-select-sm auto-submit-filter" onchange="this.form.submit()"><option value="">-- Tất cả loại --</option></select>
            </div>
            <div class="filter-item">
                <label>Trạng thái</label>
                <select name="filter_status" class="form-select-sm auto-submit-filter" onchange="this.form.submit()">
                    <option value="">-- Tất cả trạng thái --</option>
                    <?php foreach ($statuses_config as $name => $cls): ?>
                        <option value="<?php echo htmlspecialchars($name); ?>" <?php echo $filter_status === $name ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="filter-bottom-flex">
            <div class="search-input-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="filter_keyword" placeholder="Tìm theo Mã tài sản, Tên, Model hoặc Serial..." value="<?php echo htmlspecialchars($filter_keyword); ?>" class="form-control-sm">
            </div>
            <div class="filter-buttons">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Lọc</button>
                <a href="index.php?page=devices/list" class="btn btn-secondary btn-sm"><i class="fas fa-undo"></i></a>
                <div class="column-selector-container">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleColumnMenu()"><i class="fas fa-columns"></i> Cột</button>
                    <div id="columnMenu" class="dropdown-menu">
                        <div class="dropdown-header">Hiển thị cột</div>
                        <div class="column-list">
                            <?php foreach ($all_columns as $k => $c): ?>
                                <label class="column-item"><input type="checkbox" class="col-checkbox" data-target="<?php echo $k; ?>" <?php echo $c['default'] ? 'checked' : ''; ?>> <?php echo htmlspecialchars($c['label']); ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<form action="index.php?page=devices/export" method="POST" id="devices-form">
    <div class="batch-actions" id="batch-actions" style="display: none;">
        <span class="selected-count-label">Đã chọn <strong id="selected-count">0</strong> mục</span>
        <div class="action-buttons">
            <button type="button" class="btn btn-secondary btn-sm" id="clear-selection-btn"><i class="fas fa-times"></i> Bỏ chọn</button>
            <input type="hidden" name="visible_columns" id="visible_columns_input">
            <button type="submit" name="export_selected" class="btn btn-secondary btn-sm" onclick="prepareExport()"><i class="fas fa-file-export"></i> Xuất Excel</button>
            <?php if(isAdmin()): ?>
                <button type="button" class="btn btn-danger btn-sm" id="delete-selected-btn" data-action="index.php?page=devices/delete_multiple"><i class="fas fa-trash-alt"></i> Xóa đã chọn</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-container card">
        <table class="content-table" id="devicesTable">
            <thead>
                <tr>
                    <th width="40"><input type="checkbox" id="select-all"></th>
                    <?php foreach ($all_columns as $k => $c): ?>
                        <th data-col="<?php echo $k; ?>"><?php echo htmlspecialchars($c['label']); ?></th>
                    <?php endforeach; ?>
                    <th width="100" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($devices as $d): ?>
                    <tr>
                        <td><input type="checkbox" name="selected_devices[]" value="<?php echo $d['id']; ?>" class="row-checkbox"></td>
                        <td data-col="ma_tai_san" class="font-medium text-primary"><?php echo htmlspecialchars($d['ma_tai_san']); ?></td>
                        <td data-col="ten_thiet_bi"><?php echo htmlspecialchars($d['ten_thiet_bi']); ?></td>
                        
                        <!-- CỘT THUỘC THIẾT BỊ MỚI -->
                        <td data-col="parent_name">
                            <?php if ($d['parent_id']): ?>
                                <a href="index.php?page=devices/view&id=<?php echo $d['parent_id']; ?>" class="parent-link">
                                    <i class="fas fa-level-up-alt fa-rotate-90"></i> <?php echo htmlspecialchars($d['parent_name']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted" style="font-size: 0.8rem;">[Thiết bị gốc]</span>
                            <?php endif; ?>
                        </td>

                        <td data-col="loai_thiet_bi"><?php echo htmlspecialchars($d['loai_thiet_bi']); ?></td>
                        <td data-col="nhom_thiet_bi"><?php echo htmlspecialchars($d['nhom_thiet_bi']); ?></td>
                        <td data-col="model"><?php echo htmlspecialchars($d['model']); ?></td>
                        <td data-col="serial"><?php echo htmlspecialchars($d['serial']); ?></td>
                        <td data-col="ten_du_an"><?php echo htmlspecialchars($d['ten_du_an']); ?></td>
                        <td data-col="ten_npp"><?php echo htmlspecialchars($d['ten_npp'] ?? 'N/A'); ?></td>
                        <td data-col="ngay_mua"><?php echo $d['ngay_mua'] ? date('d/m/Y', strtotime($d['ngay_mua'])) : ''; ?></td>
                        <td data-col="bao_hanh_den">
                            <?php if ($d['bao_hanh_den']): ?>
                                <span class="<?php echo (strtotime($d['bao_hanh_den']) < time()) ? 'text-danger' : ''; ?>"><?php echo date('d/m/Y', strtotime($d['bao_hanh_den'])); ?></span>
                            <?php endif; ?>
                        </td>
                        <td data-col="gia_mua"><?php echo $d['gia_mua'] ? number_format($d['gia_mua'], 0, ",", ".") . ' ₫' : ''; ?></td>
                        <td data-col="trang_thai">
                            <?php $cls = $statuses_config[$d['trang_thai']] ?? 'status-default'; ?>
                            <span class="badge <?php echo $cls; ?>"><?php echo htmlspecialchars($d['trang_thai']); ?></span>
                        </td>
                        <td class="actions text-center">
                            <a href="index.php?page=devices/view&id=<?php echo $d['id']; ?>" class="btn-icon"><i class="fas fa-eye"></i></a>
                            <?php if(isIT()): ?>
                                <a href="index.php?page=devices/edit&id=<?php echo $d['id']; ?>" class="btn-icon"><i class="fas fa-edit"></i></a>
                                <?php if(isAdmin()): ?>
                                    <a href="index.php?page=devices/delete&id=<?php echo $d['id']; ?>" class="btn-icon delete-btn"><i class="fas fa-trash-alt"></i></a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</form>

<div class="pagination-container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <div class="rows-per-page">
        <form action="index.php" method="GET" class="rows-per-page-form" style="display: flex; align-items: center; gap: 8px;">
            <input type="hidden" name="page" value="devices/list">
            <?php foreach ($_GET as $key => $value): if(!in_array($key, ['limit','page','p'])) { ?>
                <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
            <?php } endforeach; ?>
            <label style="font-size: 0.85rem; color: #64748b;">Hiển thị</label>
            <select name="limit" onchange="this.form.submit()" class="form-select-sm" style="width: auto;">
                <?php foreach([5,10,25,50,100] as $lim): ?>
                    <option value="<?php echo $lim; ?>" <?php echo $rows_per_page == $lim ? 'selected' : ''; ?>><?php echo $lim; ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="pagination-links" style="display: flex; gap: 5px; margin-left: auto;">
        <?php $q = $_GET; unset($q['p']); $base = 'index.php?' . http_build_query($q); ?>
        <a href="<?php echo $base . '&p=1'; ?>" class="page-link <?php echo $current_page <= 1 ? 'disabled' : ''; ?>"><i class="fas fa-angle-double-left"></i></a>
        <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
            <a href="<?php echo $base . '&p=' . $i; ?>" class="page-link <?php echo $i == $current_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <a href="<?php echo $base . '&p=' . $total_pages; ?>" class="page-link <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>"><i class="fas fa-angle-double-right"></i></a>
    </div>
</div>

<script>
let localProjects = <?php echo json_encode($projects_list); ?>;
let allTypes = <?php echo json_encode($all_types); ?>;
let currentFilterType = "<?php echo $filter_type; ?>";
let activeIndex = -1;

function updateTypeFilter() {
    const gs = document.getElementById('filter_group');
    const ts = document.getElementById('filter_type');
    const sel = gs.value;
    ts.innerHTML = '<option value="">-- Tất cả loại --</option>';
    const filtered = sel ? allTypes.filter(t => t.group_name === sel) : allTypes;
    filtered.forEach(t => {
        const opt = document.createElement('option');
        opt.value = t.type_name; opt.textContent = t.type_name;
        if(t.type_name === currentFilterType) opt.selected = true;
        ts.appendChild(opt);
    });
}

function toggleColumnMenu() { document.getElementById('columnMenu').classList.toggle('show'); }
const colCbs = document.querySelectorAll('.col-checkbox');
function updateCols() {
    const s = {};
    colCbs.forEach(cb => {
        const t = cb.dataset.target; s[t] = cb.checked;
        document.querySelectorAll(`[data-col="${t}"]`).forEach(el => el.style.display = cb.checked ? '' : 'none');
    });
    localStorage.setItem('deviceColumns', JSON.stringify(s));
}
colCbs.forEach(cb => cb.addEventListener('change', updateCols));

document.addEventListener('DOMContentLoaded', () => {
    updateTypeFilter();
    const ps = document.getElementById('project_search');
    const pd = document.getElementById('project_dropdown');
    const pi = document.getElementById('filter_project');
    const cl = document.getElementById('btn-clear-project');

    if (ps) {
        ps.addEventListener('input', function() { renderProjectDropdown(this.value.toLowerCase().trim()); cl.style.display = this.value.length > 0 ? 'block' : 'none'; });
        ps.addEventListener('focus', function() { renderProjectDropdown(this.value.toLowerCase().trim()); });
    }
    if (cl) cl.addEventListener('click', () => { ps.value = ''; pi.value = ''; cl.style.display = 'none'; pd.style.display = 'none'; });
    document.addEventListener('click', (e) => {
        if (ps && !ps.contains(e.target) && !pd.contains(e.target)) pd.style.display = 'none';
        if (!e.target.closest('.column-selector-container')) document.getElementById('columnMenu').classList.remove('show');
    });

    const saved = JSON.parse(localStorage.getItem('deviceColumns'));
    if(saved) colCbs.forEach(cb => { const t = cb.dataset.target; if(saved.hasOwnProperty(t)) cb.checked = saved[t]; });
    updateCols();

    const selectAll = document.getElementById('select-all');
    const rowCbs = document.querySelectorAll('.row-checkbox');
    const batch = document.getElementById('batch-actions');
    const count = document.getElementById('selected-count');
    const clearBtn = document.getElementById('clear-selection-btn');

    function updateBatch() { 
        const n = document.querySelectorAll('.row-checkbox:checked').length; 
        if(batch) batch.style.display = n > 0 ? 'flex' : 'none'; 
        if(count) count.textContent = n; 
    }

    if(selectAll) selectAll.addEventListener('change', () => { rowCbs.forEach(cb => cb.checked = selectAll.checked); updateBatch(); });
    rowCbs.forEach(cb => cb.addEventListener('change', updateBatch));
    
    if(clearBtn) clearBtn.addEventListener('click', () => { 
        if(selectAll) selectAll.checked = false; 
        rowCbs.forEach(cb => cb.checked = false); 
        updateBatch(); 
    });

    // Auto-highlight active filters
    document.querySelectorAll('.auto-submit-filter').forEach(select => {
        if (select.value !== '') select.classList.add('active-filter');
    });
});

function renderProjectDropdown(filter = '') {
    const dropdown = document.getElementById('project_dropdown');
    const filtered = localProjects.filter(p => p.ten_du_an.toLowerCase().includes(filter));
    let html = '<div class="dropdown-item" onclick="selectProject(\'\', \'\')">-- Tất cả dự án --</div>';
    html += filtered.map(p => `<div class="dropdown-item" onclick="selectProject(${p.id}, '${p.ten_du_an.replace(/'/g, "\\'")}')"><span class="item-title">${p.ten_du_an}</span></div>`).join('');
    dropdown.innerHTML = html; dropdown.style.display = 'block'; activeIndex = -1;
}
function selectProject(id, name) { document.getElementById('project_search').value = name; document.getElementById('filter_project').value = id; document.getElementById('project_dropdown').style.display = 'none'; document.getElementById('btn-clear-project').style.display = name ? 'block' : 'none'; }

function prepareExport() {
    const activeColumns = [];
    document.querySelectorAll('.col-checkbox:checked').forEach(cb => {
        activeColumns.push({
            key: cb.dataset.target,
            label: cb.parentElement.textContent.trim()
        });
    });
    document.getElementById('visible_columns_input').value = JSON.stringify(activeColumns);
}
</script>

<style>
/* CSS CẢI TIẾN */
.filter-section-modern { 
    padding: 15px; 
    margin-bottom: 20px; 
    background: #fff; 
    border-radius: 12px; 
    box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
    border-left: 5px solid var(--primary-color) !important;
}
.filter-form-modern { display: flex; flex-direction: column; gap: 12px; }
.filter-main-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
.filter-item { display: flex; flex-direction: column; gap: 4px; }
.filter-item label { font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; }
.filter-bottom-flex { display: flex; gap: 12px; align-items: flex-end; border-top: 1px solid #f1f5f9; padding-top: 12px; }
.search-input-wrapper { position: relative; flex: 1; }
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem; }
.search-input-wrapper input { padding-left: 35px !important; width: 100%; }
.filter-buttons { display: flex; gap: 8px; }
.form-control-sm, .form-select-sm { height: 36px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 0.85rem; width: 100%; transition: 0.2s; }
.form-control-sm:focus { border-color: var(--primary-color); outline: none; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }

/* Highlight Active Filter */
.form-select-sm.active-filter {
    border: 2px solid var(--primary-color) !important;
    background-color: #f0fdf4 !important;
    color: var(--primary-dark-color) !important;
    font-weight: 600;
}

/* Link thiết bị cha */
.parent-link { color: #64748b; text-decoration: none; font-size: 0.85rem; font-weight: 500; transition: 0.2s; }
.parent-link:hover { color: var(--primary-color); }
.parent-link i { font-size: 0.75rem; margin-right: 4px; color: #cbd5e1; }

.searchable-select-container { position: relative; width: 100%; }
.btn-clear-inline { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; z-index: 5; }
.searchable-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #cbd5e1; border-radius: 8px; margin-top: 5px; max-height: 250px; overflow-y: auto; z-index: 1000; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); display: none; }
.dropdown-item { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; }
.dropdown-item:hover { background: #f8fafc; color: var(--primary-color); }

@media (max-width: 768px) {
    .filter-main-grid { grid-template-columns: 1fr; }
    .filter-bottom-flex { flex-direction: column; align-items: stretch; }
    .filter-buttons { width: 100%; display: grid; grid-template-columns: 1fr 40px auto; }
}
.content-table td { white-space: nowrap; }
.content-table td[data-col="ten_thiet_bi"], .content-table td[data-col="parent_name"] { white-space: normal; min-width: 150px; }
</style>