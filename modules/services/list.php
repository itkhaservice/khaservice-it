<?php
// modules/services/list.php
$rows_per_page = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : 10;
$current_page  = (isset($_GET['p']) && is_numeric($_GET['p'])) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

$filter_keyword = trim($_GET['filter_keyword'] ?? '');
$filter_project = trim($_GET['filter_project'] ?? '');

$where_clauses = ["s.deleted_at IS NULL"];
$bind_params   = [];

if ($filter_keyword !== '') {
    $where_clauses[] = "(s.ten_dich_vu LIKE :kw OR s.loai_dich_vu LIKE :kw OR p.ten_du_an LIKE :kw)";
    $bind_params[':kw'] = '%' . $filter_keyword . '%';
}
if ($filter_project !== '' && is_numeric($filter_project)) {
    $where_clauses[] = "s.project_id = :project_id";
    $bind_params[':project_id'] = (int)$filter_project;
}

$where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM services s LEFT JOIN projects p ON s.project_id = p.id $where_sql");
foreach ($bind_params as $k => $v) $count_stmt->bindValue($k, $v);
$count_stmt->execute();
$total_rows  = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $rows_per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $rows_per_page;

$stmt = $pdo->prepare("SELECT s.*, p.ten_du_an, sup.ten_npp FROM services s LEFT JOIN projects p ON s.project_id = p.id LEFT JOIN suppliers sup ON s.supplier_id = sup.id $where_sql ORDER BY s.ngay_het_han ASC LIMIT :limit OFFSET :offset");
foreach ($bind_params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $rows_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

$projects_list = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an")->fetchAll(PDO::FETCH_ASSOC);

$all_columns = [
    'ten_dich_vu'   => ['label' => 'Tên Dịch vụ', 'default' => true],
    'ten_du_an'     => ['label' => 'Dự án', 'default' => true],
    'ngay_het_han'  => ['label' => 'Ngày hết hạn', 'default' => true],
    'ngay_nhan_de_nghi' => ['label' => 'Ngày nhận đề nghị', 'default' => false],
    'trang_thai'    => ['label' => 'Trạng thái', 'default' => true],
];
?>

<div class="page-header">
    <h2><i class="fas fa-cloud"></i> Quản lý Dịch vụ</h2>
    <?php if(isIT()): ?><a href="index.php?page=services/add" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm dịch vụ</a><?php endif; ?>
</div>

<div class="card filter-section-modern">
    <form action="index.php" method="GET" class="filter-form-modern">
        <input type="hidden" name="page" value="services/list">
        <div class="filter-main-grid">
            <div class="filter-item">
                <label>Dự án</label>
                <div class="searchable-select-container">
                    <input type="text" id="project_search" class="form-control-sm" placeholder="Tất cả dự án..." value="<?php if ($filter_project) { foreach($projects_list as $p) { if($p['id'] == $filter_project) { echo htmlspecialchars($p['ten_du_an']); break; } } } ?>" autocomplete="off">
                    <button type="button" class="btn-clear-inline" id="btn-clear-project" style="<?php echo $filter_project ? 'display:block' : 'display:none'; ?>"><i class="fas fa-times"></i></button>
                    <input type="hidden" name="filter_project" id="filter_project" value="<?php echo htmlspecialchars($filter_project); ?>">
                    <div id="project_dropdown" class="searchable-dropdown"></div>
                </div>
            </div>
            <div class="filter-item" style="flex: 2;">
                <label>Từ khóa</label>
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="filter_keyword" placeholder="Tên dịch vụ, loại..." value="<?php echo htmlspecialchars($filter_keyword); ?>" class="form-control-sm">
                </div>
            </div>
            <div class="filter-item" style="flex: 0 0 auto; flex-direction: row; gap: 8px;">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Lọc</button>
                <a href="index.php?page=services/list" class="btn btn-secondary btn-sm"><i class="fas fa-undo"></i></a>
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

<form action="index.php?page=services/export" method="POST" id="services-form">
    <div class="batch-actions" id="batch-actions" style="display: none;">
        <span class="selected-count-label">Đã chọn <strong id="selected-count">0</strong> mục</span>
        <div class="action-buttons">
            <button type="button" class="btn btn-secondary btn-sm" id="clear-selection-btn"><i class="fas fa-times"></i> Bỏ chọn</button>
            <input type="hidden" name="visible_columns" id="visible_columns_input">
            <button type="submit" name="export_selected" class="btn btn-secondary btn-sm" onclick="prepareExport()">Xuất Excel</button>
            <?php if(isIT()): ?>
                <button type="button" class="btn btn-danger btn-sm" id="delete-selected-btn" data-action="index.php?page=services/delete_multiple">Xóa đã chọn</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-container card">
        <table class="content-table" id="servicesTable">
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
                <?php foreach ($services as $s):
                    // Check if $s['ngay_het_han'] is valid before strtotime
                    $ngay_het_han_formatted = isset($s['ngay_het_han']) && $s['ngay_het_han'] ? date('d/m/Y', strtotime($s['ngay_het_han'])) : 'N/A';
                    // Check if $s['ngay_nhan_de_nghi'] is valid before strtotime
                    $ngay_nhan_de_nghi_formatted = isset($s['ngay_nhan_de_nghi']) && $s['ngay_nhan_de_nghi'] ? date('d/m/Y', strtotime($s['ngay_nhan_de_nghi'])) : 'Chưa nhận';
                ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?php echo $s['id']; ?>" class="row-checkbox"></td>
                        <td data-col="ten_dich_vu">
                            <div class="font-bold"><?php echo htmlspecialchars($s['ten_dich_vu']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($s['loai_dich_vu']); ?> - <?php echo htmlspecialchars($s['ten_npp'] ?? 'N/A'); ?></small>
                        </td>
                        <td data-col="ten_du_an"><?php echo htmlspecialchars($s['ten_du_an'] ?: "Dùng chung"); ?></td>
                        <td data-col="ngay_het_han"><?php echo $ngay_het_han_formatted; ?></td>
                        <td data-col="ngay_nhan_de_nghi"><?php echo $ngay_nhan_de_nghi_formatted; ?></td>
                        <td data-col="trang_thai"><span class="badge"><?php echo htmlspecialchars($s['trang_thai']); ?></span></td>
                        <td class="actions text-center">
                            <a href="index.php?page=services/view&id=<?php echo $s['id']; ?>" class="btn-icon"><i class="fas fa-eye"></i></a>
                            <?php if(isIT()): ?>
                                <a href="index.php?page=services/edit&id=<?php echo $s['id']; ?>" class="btn-icon"><i class="fas fa-edit"></i></a>
                                <a href="index.php?page=services/delete&id=<?php echo $s['id']; ?>" class="btn-icon delete-btn"><i class="fas fa-trash-alt"></i></a>
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
            <input type="hidden" name="page" value="services/list">
            <?php foreach ($_GET as $key => $value): if(!in_array($key, ['limit','page','p'])) { ?><input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>"><?php } endforeach; ?>
            <label style="font-size: 0.85rem; color: #64748b;">Hiển thị</label>
            <select name="limit" onchange="this.form.submit()" class="form-select-sm" style="width: auto;">
                <?php foreach([10,25,50,100] as $lim): ?><option value="<?php echo $lim; ?>" <?php echo $rows_per_page == $lim ? 'selected' : ''; ?>><?php echo $lim; ?></option><?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="pagination-links" style="display: flex; gap: 5px; margin-left: auto;">
        <?php $q = $_GET; unset($q['p']); $base = 'index.php?' . http_build_query($q); ?>
        <a href="<?php echo $base . '&p=1'; ?>" class="page-link <?php echo $current_page <= 1 ? 'disabled' : ''; ?>"><i class="fas fa-angle-double-left"></i></a>
        <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?><a href="<?php echo $base . '&p=' . $i; ?>" class="page-link <?php echo $i == $current_page ? 'active' : ''; ?>"><?php echo $i; ?></a><?php endfor; ?>
        <a href="<?php echo $base . '&p=' . $total_pages; ?>" class="page-link <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>"><i class="fas fa-angle-double-right"></i></a>
    </div>
</div>

<script>
let localProjects = <?php echo json_encode($projects_list); ?>;
function toggleColumnMenu() { const m = document.getElementById('columnMenu'); if(m) m.classList.toggle('show'); }
const colCbs = document.querySelectorAll('.col-checkbox');
function updateCols() {
    const s = {};
    colCbs.forEach(cb => {
        const t = cb.dataset.target; s[t] = cb.checked;
        document.querySelectorAll(`[data-col="${t}"]`).forEach(el => el.style.display = cb.checked ? '' : 'none');
    });
    localStorage.setItem('serviceColumns', JSON.stringify(s));
}
colCbs.forEach(cb => cb.addEventListener('change', updateCols));

document.addEventListener('DOMContentLoaded', () => {
    const saved = JSON.parse(localStorage.getItem('serviceColumns'));
    if(saved) colCbs.forEach(cb => { if(saved.hasOwnProperty(cb.dataset.target)) cb.checked = saved[cb.dataset.target]; });
    updateCols();

    const selectAll = document.getElementById('select-all');
    const rowCbs = document.querySelectorAll('.row-checkbox');
    const batch = document.getElementById('batch-actions');
    const clearBtn = document.getElementById('clear-selection-btn');

    function updateBatch() {
        const n = document.querySelectorAll('.row-checkbox:checked').length;
        if(batch) batch.style.display = n > 0 ? 'flex' : 'none';
        if(document.getElementById('selected-count')) document.getElementById('selected-count').textContent = n;
    }
    if(selectAll) selectAll.addEventListener('change', () => { rowCbs.forEach(cb => cb.checked = selectAll.checked); updateBatch(); });
    rowCbs.forEach(cb => cb.addEventListener('change', updateBatch));
    if(clearBtn) clearBtn.addEventListener('click', () => { if(selectAll) selectAll.checked = false; rowCbs.forEach(cb => cb.checked = false); updateBatch(); });

    // Project Search
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
        if (!e.target.closest('.column-selector-container')) { const m = document.getElementById('columnMenu'); if(m) m.classList.remove('show'); }
    });
});

function renderProjectDropdown(filter = '') {
    const dropdown = document.getElementById('project_dropdown');
    const filtered = localProjects.filter(p => p.ten_du_an.toLowerCase().includes(filter));
    let html = '<div class="dropdown-item" onclick="selectProject(\'\', \'\')">-- Tất cả dự án --</div>';
    html += filtered.map(p => `<div class="dropdown-item" onclick="selectProject(${p.id}, '${p.ten_du_an.replace(/'/g, "\'")}')"><span class="item-title">${p.ten_du_an}</span></div>`).join('');
    dropdown.innerHTML = html; dropdown.style.display = 'block';
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
.filter-section-modern { 
    padding: 15px; 
    margin-bottom: 20px; 
    background: #fff; 
    border-radius: 12px; 
    box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
    border-left: 5px solid var(--primary-color) !important;
}
.filter-form-modern { display: flex; flex-direction: column; gap: 12px; }
.filter-main-grid { display: flex; gap: 12px; align-items: flex-end; }
.filter-item { display: flex; flex-direction: column; gap: 4px; flex: 1; }
.filter-item label { font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; }
.search-input-wrapper { position: relative; width: 100%; }
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem; }
.search-input-wrapper input { padding-left: 35px !important; width: 100%; }
.form-control-sm { height: 36px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 0.85rem; width: 100%; transition: 0.2s; }

.searchable-select-container { position: relative; width: 100%; min-width: 200px; }
.searchable-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #cbd5e1; border-radius: 8px; margin-top: 5px; max-height: 250px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); display: none; }
.dropdown-item { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; text-align: left; }
.dropdown-item:hover { background: #f8fafc; color: var(--primary-color); }
.btn-clear-inline { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; z-index: 5; }

@media (max-width: 768px) {
    .filter-main-grid { flex-direction: column; align-items: stretch; }
}
</style>