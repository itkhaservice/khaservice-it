<?php
// modules/projects/list.php
$rows_per_page = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : 5;
$current_page  = (isset($_GET['p']) && is_numeric($_GET['p'])) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

$filter_keyword = trim($_GET['filter_keyword'] ?? '');
$filter_type    = trim($_GET['filter_type'] ?? '');

$where_clauses = ["deleted_at IS NULL"];
$bind_params   = [];

if ($filter_keyword !== '') {
    $where_clauses[] = "(ma_du_an LIKE :kw1 OR ten_du_an LIKE :kw2 OR dia_chi_duong LIKE :kw3 OR dia_chi_phuong_xa LIKE :kw4 OR dia_chi_tinh_tp LIKE :kw5)";
    $bind_params[':kw1'] = $bind_params[':kw2'] = $bind_params[':kw3'] = $bind_params[':kw4'] = $bind_params[':kw5'] = '%' . $filter_keyword . '%';
}
if ($filter_type !== '') { $where_clauses[] = "loai_du_an = :loai"; $bind_params[':loai'] = $filter_type; }

$where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM projects $where_sql");
foreach ($bind_params as $k => $v) $count_stmt->bindValue($k, $v);
$count_stmt->execute();
$total_rows  = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $rows_per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $rows_per_page;

$stmt = $pdo->prepare("SELECT * FROM projects $where_sql ORDER BY ten_du_an ASC LIMIT :limit OFFSET :offset");
foreach ($bind_params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $rows_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$projects_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$project_types = $pdo->query("SELECT DISTINCT loai_du_an FROM projects WHERE loai_du_an != '' ORDER BY loai_du_an")->fetchAll(PDO::FETCH_COLUMN);

$all_columns = [
    'ma_du_an'   => ['label' => 'Mã Dự án', 'default' => true],
    'ten_du_an'  => ['label' => 'Tên Dự án', 'default' => true],
    'dia_chi'    => ['label' => 'Địa chỉ', 'default' => true],
    'loai_du_an' => ['label' => 'Loại', 'default' => true],
];
?>

<div class="page-header">
    <h2><i class="fas fa-building"></i> Danh sách Dự án</h2>
    <?php if(isIT()): ?><a href="index.php?page=projects/add" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm mới</a><?php endif; ?>
</div>

<div class="card filter-section">
    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="projects/list">
        <div class="filter-group">
            <label>Loại Dự án</label>
            <select name="filter_type" class="form-select-sm">
                <option value="">-- Tất cả --</option>
                <?php foreach ($project_types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($filter_type == $type) ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group" style="flex: 2;">
            <label>Tìm kiếm</label>
            <input type="text" name="filter_keyword" placeholder="Mã, tên, địa chỉ..." value="<?php echo htmlspecialchars($filter_keyword); ?>" class="form-control-sm">
        </div>
        <div class="filter-actions" style="margin-left: auto;">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Lọc</button>
            <a href="index.php?page=projects/list" class="btn btn-secondary btn-sm"><i class="fas fa-undo"></i></a>
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
    </form>
</div>

<form action="index.php?page=projects/export" method="POST" id="projects-form">
    <div class="batch-actions" id="batch-actions" style="display: none;">
        <span class="selected-count-label">Đã chọn <strong id="selected-count">0</strong> mục</span>
        <div class="action-buttons">
            <button type="button" class="btn btn-secondary btn-sm" id="clear-selection-btn"><i class="fas fa-times"></i> Bỏ chọn</button>
            <input type="hidden" name="visible_columns" id="visible_columns_input">
            <button type="submit" name="export_selected" class="btn btn-secondary btn-sm" onclick="prepareExport()"><i class="fas fa-file-export"></i> Xuất Excel</button>
            <?php if(isAdmin()): ?>
                <button type="button" class="btn btn-danger btn-sm" id="delete-selected-btn" data-action="index.php?page=projects/delete_multiple">Xóa đã chọn</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-container card">
        <table class="content-table" id="projectsTable">
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
                <?php foreach ($projects_list as $p): ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?php echo $p['id']; ?>" class="row-checkbox"></td>
                        <td data-col="ma_du_an" class="font-medium text-primary"><?php echo htmlspecialchars($p['ma_du_an']); ?></td>
                        <td data-col="ten_du_an" class="font-bold"><?php echo htmlspecialchars($p['ten_du_an']); ?></td>
                        <td data-col="dia_chi">
                            <?php 
                                $addr = array_filter([$p['dia_chi_duong'], $p['dia_chi_phuong_xa'], $p['dia_chi_tinh_tp']]);
                                echo htmlspecialchars(!empty($addr) ? implode(', ', $addr) : '');
                            ?>
                        </td>
                        <td data-col="loai_du_an"><span class="badge status-info"><?php echo htmlspecialchars($p['loai_du_an']); ?></span></td>
                        <td class="actions text-center">
                            <a href="index.php?page=projects/view&id=<?php echo $p['id']; ?>" class="btn-icon"><i class="fas fa-eye"></i></a>
                            <?php if(isIT()): ?>
                                <a href="index.php?page=projects/edit&id=<?php echo $p['id']; ?>" class="btn-icon"><i class="fas fa-edit"></i></a>
                                <?php if(isAdmin()): ?>
                                    <a href="index.php?page=projects/delete&id=<?php echo $p['id']; ?>" class="btn-icon delete-btn"><i class="fas fa-trash-alt"></i></a>
                                <?php endif; ?>
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
            <input type="hidden" name="page" value="projects/list">
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
function toggleColumnMenu() { const m = document.getElementById('columnMenu'); if(m) m.classList.toggle('show'); }
const colCbs = document.querySelectorAll('.col-checkbox');
function updateCols() {
    const s = {};
    colCbs.forEach(cb => {
        const t = cb.dataset.target; s[t] = cb.checked;
        document.querySelectorAll(`[data-col="${t}"]`).forEach(el => el.style.display = cb.checked ? '' : 'none');
    });
    localStorage.setItem('projectColumns', JSON.stringify(s));
}
colCbs.forEach(cb => cb.addEventListener('change', updateCols));

document.addEventListener('DOMContentLoaded', () => {
    const saved = JSON.parse(localStorage.getItem('projectColumns'));
    if(saved) colCbs.forEach(cb => { if(saved.hasOwnProperty(cb.dataset.target)) cb.checked = saved[cb.dataset.target]; });
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
    if(clearBtn) clearBtn.addEventListener('click', () => { if(selectAll) selectAll.checked = false; rowCbs.forEach(cb => cb.checked = false); updateBatch(); });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.column-selector-container')) {
            const m = document.getElementById('columnMenu'); if(m) m.classList.remove('show');
        }
    });
});

function prepareExport() {
    const activeColumns = [];
    document.querySelectorAll('.col-checkbox:checked').forEach(cb => {
        activeColumns.push({ key: cb.dataset.target, label: cb.parentElement.textContent.trim() });
    });
    document.getElementById('visible_columns_input').value = JSON.stringify(activeColumns);
}
</script>