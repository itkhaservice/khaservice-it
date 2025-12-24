<?php
// modules/suppliers/list.php
$rows_per_page = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : 10;
$current_page  = (isset($_GET['p']) && is_numeric($_GET['p'])) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

$filter_keyword = trim($_GET['filter_keyword'] ?? '');

$where_clauses = ["deleted_at IS NULL"];
$bind_params   = [];

if ($filter_keyword !== '') {
    $where_clauses[] = "(ten_npp LIKE :kw1 OR nguoi_lien_he LIKE :kw2 OR email LIKE :kw3 OR dien_thoai LIKE :kw4 OR ghi_chu LIKE :kw5)";
    $bind_params[':kw1'] = $bind_params[':kw2'] = $bind_params[':kw3'] = $bind_params[':kw4'] = $bind_params[':kw5'] = '%'.$filter_keyword.'%';
}

$where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM suppliers $where_sql");
foreach ($bind_params as $k => $v) $count_stmt->bindValue($k, $v);
$count_stmt->execute();
$total_rows  = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $rows_per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $rows_per_page;

$stmt = $pdo->prepare("SELECT * FROM suppliers $where_sql ORDER BY ten_npp ASC LIMIT :limit OFFSET :offset");
foreach ($bind_params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $rows_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$suppliers_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$all_columns = [
    'ten_npp'      => ['label' => 'Nhà phân phối', 'default' => true],
    'nguoi_lien_he'=> ['label' => 'Liên hệ', 'default' => true],
    'dien_thoai'   => ['label' => 'Điện thoại', 'default' => true],
    'email'        => ['label' => 'Email', 'default' => true],
    'ghi_chu'      => ['label' => 'Ghi chú', 'default' => false]
];
?>

<div class="page-header">
    <h2><i class="fas fa-truck"></i> Danh sách Nhà cung cấp</h2>
    <?php if(isIT()): ?><a href="index.php?page=suppliers/add" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm mới</a><?php endif; ?>
</div>

<div class="card filter-section-modern">
    <form action="index.php" method="GET" class="filter-form-modern">
        <input type="hidden" name="page" value="suppliers/list">
        <div class="filter-main-grid">
            <div class="filter-item" style="flex: 2;">
                <label>Từ khóa Tìm kiếm</label>
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="filter_keyword" placeholder="Tên, liên hệ, email..." value="<?php echo htmlspecialchars($filter_keyword); ?>" class="form-control-sm">
                </div>
            </div>
            <div class="filter-item" style="flex: 0 0 auto; flex-direction: row; gap: 8px;">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Lọc</button>
                <a href="index.php?page=suppliers/list" class="btn btn-secondary btn-sm"><i class="fas fa-undo"></i></a>
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

<form action="index.php?page=suppliers/export" method="POST" id="suppliers-form">
    <div class="batch-actions" id="batch-actions" style="display: none;">
        <span class="selected-count-label">Đã chọn <strong id="selected-count">0</strong> mục</span>
        <div class="action-buttons">
            <button type="button" class="btn btn-secondary btn-sm" id="clear-selection-btn"><i class="fas fa-times"></i> Bỏ chọn</button>
            <input type="hidden" name="visible_columns" id="visible_columns_input">
            <button type="submit" name="export_selected" class="btn btn-secondary btn-sm" onclick="prepareExport()"><i class="fas fa-file-export"></i> Xuất Excel</button>
            <?php if(isAdmin()): ?>
                <button type="button" class="btn btn-danger btn-sm" id="delete-selected-btn" data-action="index.php?page=suppliers/delete_multiple">Xóa đã chọn</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-container card">
        <table class="content-table" id="suppliersTable">
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
                <?php foreach ($suppliers_list as $s): ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?php echo $s['id']; ?>" class="row-checkbox"></td>
                        <td data-col="ten_npp" class="font-bold text-primary"><?php echo htmlspecialchars($s['ten_npp']); ?></td>
                        <td data-col="nguoi_lien_he"><?php echo htmlspecialchars($s['nguoi_lien_he']); ?></td>
                        <td data-col="dien_thoai"><?php echo htmlspecialchars($s['dien_thoai']); ?></td>
                        <td data-col="email"><?php echo htmlspecialchars($s['email']); ?></td>
                        <td data-col="ghi_chu"><?php echo htmlspecialchars($s['ghi_chu']); ?></td>
                        <td class="actions text-center">
                            <a href="index.php?page=suppliers/view&id=<?php echo $s['id']; ?>" class="btn-icon"><i class="fas fa-eye"></i></a>
                            <?php if(isIT()): ?>
                                <a href="index.php?page=suppliers/edit&id=<?php echo $s['id']; ?>" class="btn-icon"><i class="fas fa-edit"></i></a>
                                <?php if(isAdmin()): ?>
                                    <a href="index.php?page=suppliers/delete&id=<?php echo $s['id']; ?>" class="btn-icon delete-btn"><i class="fas fa-trash-alt"></i></a>
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
            <input type="hidden" name="page" value="suppliers/list">
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

@media (max-width: 768px) {
    .filter-main-grid { flex-direction: column; align-items: stretch; }
}
</style>

<script>
function toggleColumnMenu() { const m = document.getElementById('columnMenu'); if(m) m.classList.toggle('show'); }
const colCbs = document.querySelectorAll('.col-checkbox');
function updateCols() {
    const s = {};
    colCbs.forEach(cb => {
        const t = cb.dataset.target; s[t] = cb.checked;
        document.querySelectorAll(`[data-col="${t}"]`).forEach(el => el.style.display = cb.checked ? '' : 'none');
    });
    localStorage.setItem('supplierColumns', JSON.stringify(s));
}
colCbs.forEach(cb => cb.addEventListener('change', updateCols));

document.addEventListener('DOMContentLoaded', () => {
    const saved = JSON.parse(localStorage.getItem('supplierColumns'));
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

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.column-selector-container')) { const m = document.getElementById('columnMenu'); if(m) m.classList.remove('show'); }
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