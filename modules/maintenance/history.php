<?php
// modules/maintenance/history.php

$rows_per_page = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : 5;
$current_page  = (isset($_GET['p']) && is_numeric($_GET['p'])) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

$filter_keyword = trim($_GET['filter_keyword'] ?? '');
$filter_project = trim($_GET['filter_project'] ?? '');
$filter_date_from = trim($_GET['filter_date_from'] ?? '');
$filter_date_to   = trim($_GET['filter_date_to'] ?? '');

$where_clauses = ["ml.deleted_at IS NULL"];
$bind_params   = [];

if ($filter_keyword !== '') {
    $where_clauses[] = "(d.ten_thiet_bi LIKE :kw1 OR d.ma_tai_san LIKE :kw2 OR ml.custom_device_name LIKE :kw3 OR ml.noi_dung LIKE :kw4 OR ml.hu_hong LIKE :kw5 OR ml.xu_ly LIKE :kw6)";
    $bind_params[':kw1'] = $bind_params[':kw2'] = $bind_params[':kw3'] = $bind_params[':kw4'] = $bind_params[':kw5'] = $bind_params[':kw6'] = '%' . $filter_keyword . '%';
}
if ($filter_project !== '' && is_numeric($filter_project)) {
    $where_clauses[] = "ml.project_id = :project_id";
    $bind_params[':project_id'] = (int)$filter_project;
}
if ($filter_date_from !== '') { $where_clauses[] = "ml.ngay_su_co >= :date_from"; $bind_params[':date_from'] = $filter_date_from; }
if ($filter_date_to !== '') { $where_clauses[] = "ml.ngay_su_co <= :date_to"; $bind_params[':date_to'] = $filter_date_to; }

$where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

$count_sql = "SELECT COUNT(*) FROM maintenance_logs ml LEFT JOIN devices d ON ml.device_id = d.id $where_sql";
$count_stmt = $pdo->prepare($count_sql);
foreach ($bind_params as $k => $v) $count_stmt->bindValue($k, $v);
$count_stmt->execute();
$total_rows  = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $rows_per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $rows_per_page;

$data_sql = "SELECT ml.*, d.ma_tai_san, d.ten_thiet_bi, d.nhom_thiet_bi, p.ten_du_an, u.fullname as nguoi_thuc_hien 
              FROM maintenance_logs ml 
              LEFT JOIN devices d ON ml.device_id = d.id 
              LEFT JOIN projects p ON ml.project_id = p.id 
              LEFT JOIN users u ON ml.user_id = u.id
              $where_sql ORDER BY ml.id DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($data_sql);
foreach ($bind_params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $rows_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$projects_list = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an")->fetchAll(PDO::FETCH_ASSOC);

$all_columns = [
    'ten_thiet_bi'    => ['label' => 'Thiết bị / Đối tượng', 'default' => true],
    'ma_tai_san'      => ['label' => 'Mã Tài sản', 'default' => true],
    'ten_du_du_an'       => ['label' => 'Dự án', 'default' => true],
    'nguoi_thuc_hien' => ['label' => 'Người thực hiện', 'default' => true],
    'ngay_su_co'      => ['label' => 'Ngày yêu cầu', 'default' => true],
    'work_type'       => ['label' => 'Loại công việc', 'default' => false],
];
?>

<div class="page-header">
    <h2><i class="fas fa-history"></i> Lịch sử Công tác</h2>
    <?php if(isIT()): ?><a href="index.php?page=maintenance/add" class="btn btn-primary"><i class="fas fa-plus"></i> Tạo Phiếu</a><?php endif; ?>
</div>

<div class="card filter-section">
    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="maintenance/history">
        <div class="filter-group">
            <label>Dự án</label>
            <div class="searchable-select-container">
                <input type="text" id="project_search" class="search-input" placeholder="Tất cả dự án..." value="<?php 
                    if ($filter_project) {
                        foreach($projects_list as $p) { if($p['id'] == $filter_project) { echo htmlspecialchars($p['ten_du_an']); break; } }
                    }
                ?>" autocomplete="off">
                <button type="button" class="btn-clear-inline" id="btn-clear-project" style="<?php echo $filter_project ? 'display:block' : 'display:none'; ?>"><i class="fas fa-times"></i></button>
                <input type="hidden" name="filter_project" id="filter_project" value="<?php echo htmlspecialchars($filter_project); ?>">
                <div id="project_dropdown" class="searchable-dropdown"></div>
            </div>
        </div>
        <div class="filter-group" style="flex: 2;">
            <label>Tìm kiếm</label>
            <input type="text" name="filter_keyword" placeholder="Thiết bị, nội dung, yêu cầu..." value="<?php echo htmlspecialchars($filter_keyword); ?>">
        </div>
        <div class="filter-actions" style="margin-left: auto;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Lọc</button>
            <a href="index.php?page=maintenance/history" class="btn btn-secondary"><i class="fas fa-undo"></i></a>
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

<form action="index.php?page=maintenance/export" method="POST" id="maintenance-form">
    <div class="batch-actions" id="batch-actions" style="display: none;">
        <span class="selected-count-label">Đã chọn <strong id="selected-count">0</strong> mục</span>
        <div class="action-buttons">
            <button type="button" class="btn btn-secondary btn-sm" id="clear-selection-btn"><i class="fas fa-times"></i> Bỏ chọn</button>
            <input type="hidden" name="visible_columns" id="visible_columns_input">
            <button type="submit" name="export_selected" class="btn btn-secondary btn-sm" onclick="prepareExport()"><i class="fas fa-file-export"></i> Xuất Excel</button>
            <?php if(isIT()): ?>
                <button type="button" class="btn btn-danger btn-sm" id="delete-selected-btn" data-action="index.php?page=maintenance/delete_multiple"><i class="fas fa-trash-alt"></i> Xóa đã chọn</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-container card">
        <table class="content-table" id="maintenanceTable">
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
                <?php foreach ($logs as $log): 
                    if (!empty($log['device_id'])) {
                        $d_name = $log['ten_thiet_bi']; $d_code = $log['ma_tai_san'];
                    } else {
                        $d_name = $log['custom_device_name'] ?: "Hỗ trợ chung";
                        $d_code = $log['work_type'] ?: "Khác"; 
                    }
                ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?php echo $log['id']; ?>" class="row-checkbox"></td>
                        <td data-col="ten_thiet_bi">
                            <div class="font-bold"><?php echo htmlspecialchars($d_name); ?></div>
                            <?php if(empty($log['device_id'])): ?>
                                <div style="font-size: 0.8rem; color: #64748b;"><?php echo htmlspecialchars($log['noi_dung'] ? mb_strimwidth($log['noi_dung'], 0, 50, "...") : ''); ?></div>
                            <?php endif; ?>
                        </td>
                        <td data-col="ma_tai_san" class="<?php echo !empty($log['device_id']) ? 'text-primary font-medium' : 'text-muted'; ?>">
                            <?php echo htmlspecialchars($d_code); ?>
                        </td>
                        <td data-col="ten_du_an"><?php echo htmlspecialchars($log['ten_du_an']); ?></td>
                        <td data-col="nguoi_thuc_hien"><?php echo htmlspecialchars($log['nguoi_thuc_hien'] ?? 'N/A'); ?></td>
                        <td data-col="ngay_su_co"><?php echo date('d/m/Y', strtotime($log['ngay_su_co'])); ?></td>
                        <td data-col="work_type"><?php echo htmlspecialchars($log['work_type']); ?></td>
                        <td class="actions text-center">
                            <a href="index.php?page=maintenance/view&id=<?php echo $log['id']; ?>" class="btn-icon"><i class="fas fa-eye"></i></a>
                            <?php if(isIT()): ?>
                                <a href="index.php?page=maintenance/edit&id=<?php echo $log['id']; ?>" class="btn-icon"><i class="fas fa-edit"></i></a>
                                <a href="index.php?page=maintenance/delete&id=<?php echo $log['id']; ?>" class="btn-icon delete-btn"><i class="fas fa-trash-alt"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</form>

<div class="pagination-container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-top: 20px;">
    <div class="rows-per-page">
        <form action="index.php" method="GET" class="rows-per-page-form" style="display: flex; align-items: center; gap: 8px;">
            <input type="hidden" name="page" value="maintenance/history">
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
let activeIndex = -1;

function toggleColumnMenu() { document.getElementById('columnMenu').classList.toggle('show'); }
const colCbs = document.querySelectorAll('.col-checkbox');
function updateCols() {
    const s = {};
    colCbs.forEach(cb => {
        const t = cb.dataset.target; s[t] = cb.checked;
        document.querySelectorAll(`[data-col="${t}"]`).forEach(el => el.style.display = cb.checked ? '' : 'none');
    });
    localStorage.setItem('maintenanceColumns', JSON.stringify(s));
}
colCbs.forEach(cb => cb.addEventListener('change', updateCols));

document.addEventListener('DOMContentLoaded', () => {
    const projectSearch = document.getElementById('project_search');
    const projectDropdown = document.getElementById('project_dropdown');
    const projectIdInput = document.getElementById('filter_project');
    const cl = document.getElementById('btn-clear-project');

    if (projectSearch) {
        projectSearch.addEventListener('input', function() { renderProjectDropdown(this.value.toLowerCase().trim()); if(cl) cl.style.display = this.value.length > 0 ? 'block' : 'none'; });
        projectSearch.addEventListener('focus', function() { renderProjectDropdown(this.value.toLowerCase().trim()); });
    }
    if (cl) cl.addEventListener('click', () => { projectSearch.value = ''; projectIdInput.value = ''; cl.style.display = 'none'; projectDropdown.style.display = 'none'; });

    document.addEventListener('click', function(e) {
        if (projectSearch && !projectSearch.contains(e.target) && !projectDropdown.contains(e.target)) projectDropdown.style.display = 'none';
        if (!e.target.closest('.column-selector-container')) document.getElementById('columnMenu').classList.remove('show');
    });

    const saved = JSON.parse(localStorage.getItem('maintenanceColumns'));
    if(saved) colCbs.forEach(cb => { if(saved.hasOwnProperty(cb.dataset.target)) cb.checked = saved[cb.dataset.target]; });
    updateCols();

    const selectAll = document.getElementById('select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const batchActions = document.getElementById('batch-actions');
    const clearBtn = document.getElementById('clear-selection-btn');

    function updateBatchUI() {
        const n = document.querySelectorAll('.row-checkbox:checked').length;
        if (batchActions) batchActions.style.display = (n > 0) ? 'flex' : 'none';
        if (document.getElementById('selected-count')) document.getElementById('selected-count').textContent = n;
    }

    rowCheckboxes.forEach(cb => cb.addEventListener('change', updateBatchUI));
    if (selectAll) selectAll.addEventListener('change', function() { rowCheckboxes.forEach(cb => cb.checked = this.checked); updateBatchUI(); });
    if (clearBtn) clearBtn.addEventListener('click', () => { if (selectAll) selectAll.checked = false; rowCheckboxes.forEach(cb => cb.checked = false); updateBatchUI(); });
});

function renderProjectDropdown(filter = '') {
    const dropdown = document.getElementById('project_dropdown');
    const filtered = localProjects.filter(p => p.ten_du_an.toLowerCase().includes(filter));
    let html = '<div class="dropdown-item" onclick="selectProject(\'\', \'\')">-- Tất cả dự án --</div>';
    html += filtered.map(p => `<div class="dropdown-item" onclick="selectProject(${p.id}, '${p.ten_du_an.replace(/'/g, "\'")}')"><span class="item-title">${p.ten_du_an}</span></div>`).join('');
    dropdown.innerHTML = html; dropdown.style.display = 'block'; activeIndex = -1;
}
function selectProject(id, name) { document.getElementById('project_search').value = name; document.getElementById('filter_project').value = id; document.getElementById('project_dropdown').style.display = 'none'; if(document.getElementById('btn-clear-project')) document.getElementById('btn-clear-project').style.display = name ? 'block' : 'none'; }

function prepareExport() {
    const activeColumns = [];
    document.querySelectorAll('.col-checkbox:checked').forEach(cb => {
        activeColumns.push({ key: cb.dataset.target, label: cb.parentElement.textContent.trim() });
    });
    document.getElementById('visible_columns_input').value = JSON.stringify(activeColumns);
}
</script>

<style>
.searchable-select-container { position: relative; width: 100%; min-width: 200px; }
.search-input { width: 100%; padding: 8px 30px 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; }
.searchable-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #cbd5e1; border-radius: 6px; margin-top: 5px; max-height: 200px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); display: none; }
.dropdown-item { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; text-align: left; }
.dropdown-item:hover { background: #f8fafc; color: var(--primary-color); }
.btn-clear-inline { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; z-index: 5; }
@media (max-width: 768px) {
    .filter-form { flex-direction: column; align-items: stretch; gap: 15px; }
    .filter-group { width: 100%; }
    .filter-actions { margin-left: 0 !important; width: 100%; display: flex; gap: 10px; }
    .filter-actions .btn { flex: 1; justify-content: center; }
}
</style>