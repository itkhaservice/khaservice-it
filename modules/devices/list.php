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

// Fetch Data
$data_sql = "SELECT d.*, p.ten_du_an, s.ten_npp FROM devices d LEFT JOIN projects p ON d.project_id = p.id LEFT JOIN suppliers s ON d.supplier_id = s.id $where_sql $order_sql LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($data_sql);
foreach ($bind_params as $key => $value) $stmt->bindValue($key, $value);
$stmt->bindValue(':limit',  $rows_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

$projects_list = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an")->fetchAll(PDO::FETCH_ASSOC);
$statuses_config = $pdo->query("SELECT status_name, color_class FROM settings_device_statuses")->fetchAll(PDO::FETCH_KEY_PAIR);

$all_columns = [
    'ma_tai_san'   => ['label' => 'Mã Tài sản', 'default' => true],
    'ten_thiet_bi' => ['label' => 'Tên Thiết bị', 'default' => true],
    'loai_thiet_bi'=> ['label' => 'Loại', 'default' => false],
    'model'        => ['label' => 'Model', 'default' => false],
    'ten_du_an'    => ['label' => 'Dự án', 'default' => true],
    'trang_thai'   => ['label' => 'Trạng thái', 'default' => true],
];
?>

<div class="page-header">
    <h2><i class="fas fa-server"></i> Danh sách Thiết bị</h2>
    <?php if(isIT()): ?><a href="index.php?page=devices/add" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm mới</a><?php endif; ?>
</div>

<div class="card filter-section">
    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="devices/list">
        <div class="filter-group">
            <label>Dự án</label>
            <div class="searchable-select-container">
                <input type="text" id="project_search" class="search-input" placeholder="Tất cả dự án..." value="<?php 
                    if ($filter_project) {
                        foreach($projects_list as $p) {
                            if($p['id'] == $filter_project) {
                                echo htmlspecialchars($p['ten_du_an']);
                                break;
                            }
                        }
                    }
                ?>" autocomplete="off">
                <input type="hidden" name="filter_project" id="filter_project" value="<?php echo htmlspecialchars($filter_project); ?>">
                <div id="project_dropdown" class="searchable-dropdown"></div>
            </div>
        </div>
        <div class="filter-group">
            <label>Tìm kiếm</label>
            <input type="text" name="filter_keyword" placeholder="Mã TS, Tên, Model..." value="<?php echo htmlspecialchars($filter_keyword); ?>">
        </div>
        <div class="filter-actions" style="margin-left: auto;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Lọc</button>
            <a href="index.php?page=devices/list" class="btn btn-secondary" title="Reset"><i class="fas fa-undo"></i></a>
            <div class="column-selector-container">
                <button type="button" class="btn btn-secondary" onclick="toggleColumnMenu()"><i class="fas fa-columns"></i> Cột</button>
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
    </form>
</div>

<form action="index.php?page=devices/export" method="POST" id="devices-form">
    <div class="batch-actions" id="batch-actions" style="display: none;">
        <span class="selected-count-label">Đã chọn <strong id="selected-count">0</strong> mục</span>
        <div class="action-buttons">
            <button type="button" class="btn btn-secondary btn-sm" id="clear-selection-btn"><i class="fas fa-times"></i> Bỏ chọn</button>
            <button type="submit" name="export_selected" class="btn btn-secondary btn-sm"><i class="fas fa-file-export"></i> Xuất CSV</button>
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
                        <td data-col="loai_thiet_bi"><?php echo htmlspecialchars($d['loai_thiet_bi']); ?></td>
                        <td data-col="model"><?php echo htmlspecialchars($d['model']); ?></td>
                        <td data-col="ten_du_an"><?php echo htmlspecialchars($d['ten_du_an']); ?></td>
                        <td data-col="trang_thai">
                            <?php $cls = $statuses_config[$d['trang_thai']] ?? 'status-default'; ?>
                            <span class="badge <?php echo $cls; ?>"><?php echo htmlspecialchars($d['trang_thai']); ?></span>
                        </td>
                        <td class="actions text-center">
                            <a href="index.php?page=devices/view&id=<?php echo $d['id']; ?>" class="btn-icon" title="Xem"><i class="fas fa-eye"></i></a>
                            <?php if(isIT()): ?>
                                <a href="index.php?page=devices/edit&id=<?php echo $d['id']; ?>" class="btn-icon" title="Sửa"><i class="fas fa-edit"></i></a>
                                <?php if(isAdmin()): ?>
                                    <a href="index.php?page=devices/delete&id=<?php echo $d['id']; ?>" data-url="index.php?page=devices/delete&id=<?php echo $d['id']; ?>&confirm_delete=1" class="btn-icon delete-btn" title="Xóa"><i class="fas fa-trash-alt"></i></a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</form>

<div class="pagination-container">
    <div class="rows-per-page">
        <form action="index.php" method="GET" class="rows-per-page-form">
            <input type="hidden" name="page" value="devices/list">
            <?php foreach ($_GET as $key => $value): if(!in_array($key, ['limit','page'])) { ?>
                <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
            <?php } endforeach; ?>
            <label>Hiển thị</label>
            <select name="limit" onchange="this.form.submit()" class="form-select-sm">
                <?php foreach([5,10,25,50,100] as $lim): ?>
                    <option value="<?php echo $lim; ?>" <?php echo $rows_per_page == $lim ? 'selected' : ''; ?>><?php echo $lim; ?></option>
                <?php endforeach; ?>
            </select>
            <span>dòng / trang</span>
        </form>
    </div>
    <div class="pagination-links">
        <?php $q = $_GET; unset($q['p']); $base = 'index.php?' . http_build_query($q); ?>
        <a href="<?php echo $base . '&p=1'; ?>" class="page-link <?php echo $current_page <= 1 ? 'disabled' : ''; ?>" title="Trang đầu"><i class="fas fa-angle-double-left"></i></a>
        <a href="<?php echo $base . '&p=' . max(1, $current_page - 1); ?>" class="page-link <?php echo $current_page <= 1 ? 'disabled' : ''; ?>" title="Trang trước"><i class="fas fa-angle-left"></i></a>
        
        <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
            <a href="<?php echo $base . '&p=' . $i; ?>" class="page-link <?php echo $i == $current_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        
        <a href="<?php echo $base . '&p=' . min($total_pages, $current_page + 1); ?>" class="page-link <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>" title="Trang sau"><i class="fas fa-angle-right"></i></a>
        <a href="<?php echo $base . '&p=' . $total_pages; ?>" class="page-link <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>" title="Trang cuối"><i class="fas fa-angle-double-right"></i></a>
    </div>
</div>

<script>
let localProjects = <?php echo json_encode($projects_list); ?>;
let activeIndex = -1;

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
    // Searchable Select Logic
    const projectSearch = document.getElementById('project_search');
    const projectDropdown = document.getElementById('project_dropdown');
    const projectIdInput = document.getElementById('filter_project');

    if (projectSearch) {
        projectSearch.addEventListener('input', function() {
            renderProjectDropdown(this.value.toLowerCase().trim());
        });
        projectSearch.addEventListener('focus', function() {
            renderProjectDropdown(this.value.toLowerCase().trim());
        });
        projectSearch.addEventListener('keydown', function(e) {
            const items = projectDropdown.querySelectorAll('.dropdown-item');
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
    }

    document.addEventListener('click', function(e) {
        if (projectSearch && !projectSearch.contains(e.target) && !projectDropdown.contains(e.target)) {
            projectDropdown.style.display = 'none';
        }
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
    if(clearBtn) clearBtn.addEventListener('click', () => { if(selectAll) selectAll.checked = false; rowCbs.forEach(cb => false); updateBatch(); });
});

function renderProjectDropdown(filter = '') {
    const dropdown = document.getElementById('project_dropdown');
    const filtered = localProjects.filter(p => p.ten_du_an.toLowerCase().includes(filter));

    let html = '<div class="dropdown-item" onclick="selectProject(\'\', \'\')">-- Tất cả dự án --</div>';
    if (filtered.length === 0) {
        html += '<div class="no-results">Không tìm thấy dự án</div>';
    } else {
        html += filtered.map(p => `
            <div class="dropdown-item" onclick="selectProject(${p.id}, '${p.ten_du_an.replace(/'/g, "\\'")}')">
                <span class="item-title">${p.ten_du_an}</span>
            </div>
        `).join('');
    }
    dropdown.innerHTML = html;
    dropdown.style.display = 'block';
    activeIndex = -1;
}

function selectProject(id, name) {
    document.getElementById('project_search').value = name;
    document.getElementById('filter_project').value = id;
    document.getElementById('project_dropdown').style.display = 'none';
}

function updateActiveItem(items) {
    items.forEach((item, index) => {
        item.classList.toggle('active', index === activeIndex);
        if (index === activeIndex) item.scrollIntoView({ block: 'nearest' });
    });
}
</script>

<style>
/* Searchable Select */
.searchable-select-container { position: relative; width: 100%; min-width: 200px; }
.search-input { width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; }
.searchable-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #cbd5e1; border-radius: 6px; margin-top: 5px; max-height: 200px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); display: none; }
.dropdown-item { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; text-align: left; }
.dropdown-item:hover, .dropdown-item.active { background: #f8fafc; color: var(--primary-color); }
.no-results { padding: 10px; text-align: center; color: #94a3b8; font-size: 0.85rem; }

/* Responsive Filter */
@media (max-width: 768px) {
    .filter-form { flex-direction: column; align-items: stretch; gap: 15px; }
    .filter-group { width: 100%; }
    .searchable-select-container { min-width: 100%; }
    .filter-actions { margin-left: 0 !important; width: 100%; display: flex; gap: 10px; }
    .filter-actions .btn { flex: 1; justify-content: center; }
}
</style>