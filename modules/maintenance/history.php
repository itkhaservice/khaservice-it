<?php
$rows_per_page = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : 5;
$current_page  = (isset($_GET['p']) && is_numeric($_GET['p'])) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

$filter_keyword = trim($_GET['filter_keyword'] ?? '');
$filter_project = trim($_GET['filter_project'] ?? '');
$filter_date_from = trim($_GET['filter_date_from'] ?? '');
$filter_date_to   = trim($_GET['filter_date_to'] ?? '');

$where_clauses = [];
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

$data_sql = "SELECT ml.*, d.ma_tai_san, d.ten_thiet_bi, d.nhom_thiet_bi, p.ten_du_an FROM maintenance_logs ml LEFT JOIN devices d ON ml.device_id = d.id LEFT JOIN projects p ON ml.project_id = p.id $where_sql ORDER BY ml.ngay_su_co DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($data_sql);
foreach ($bind_params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $rows_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$projects_list = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an")->fetchAll(PDO::FETCH_ASSOC);

$all_columns = [
    'ten_thiet_bi' => ['label' => 'Thiết bị / Đối tượng', 'default' => true],
    'ma_tai_san'   => ['label' => 'Mã Tài sản', 'default' => true],
    'ten_du_an'    => ['label' => 'Dự án', 'default' => true],
    'ngay_su_co'   => ['label' => 'Ngày yêu cầu', 'default' => true],
    'chi_phi'      => ['label' => 'Chi phí', 'default' => true]
];
?>

<div class="page-header">
    <h2><i class="fas fa-history"></i> Lịch sử Bảo trì</h2>
    <?php if(isIT()): ?><a href="index.php?page=maintenance/add" class="btn btn-primary"><i class="fas fa-plus"></i> Tạo Phiếu</a><?php endif; ?>
</div>

<div class="card filter-section">
    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="maintenance/history">
        <div class="filter-group">
            <label>Dự án</label>
            <select name="filter_project">
                <option value="">-- Tất cả --</option>
                <?php foreach ($projects_list as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo ($filter_project == $p['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['ten_du_an']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group" style="flex: 2;">
            <label>Tìm kiếm</label>
            <input type="text" name="filter_keyword" placeholder="Thiết bị, nội dung, yêu cầu..." value="<?php echo htmlspecialchars($filter_keyword); ?>">
        </div>
        <div class="filter-actions" style="margin-left: auto;">
            <button type="submit" class="btn btn-primary">Lọc</button>
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
            <button type="submit" name="export_selected" class="btn btn-secondary btn-sm"><i class="fas fa-file-export"></i> Xuất file</button>
            <?php if(isAdmin()): ?>
                <button type="button" class="btn btn-danger btn-sm" id="delete-selected-btn">Xóa đã chọn</button>
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
                    $d_name = !empty($log['device_id']) ? $log['ten_thiet_bi'] : ($log['custom_device_name'] ?: "Hỗ trợ chung");
                    $d_code = !empty($log['device_id']) ? $log['ma_tai_san'] : "N/A";
                ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?php echo $log['id']; ?>" class="row-checkbox"></td>
                        <td data-col="ten_thiet_bi" class="font-bold"><?php echo htmlspecialchars($d_name); ?></td>
                        <td data-col="ma_tai_san" class="text-primary"><?php echo htmlspecialchars($d_code); ?></td>
                        <td data-col="ten_du_an"><?php echo htmlspecialchars($log['ten_du_an']); ?></td>
                        <td data-col="ngay_su_co"><?php echo date('d/m/Y', strtotime($log['ngay_su_co'])); ?></td>
                        <td data-col="chi_phi" class="font-bold text-warning"><?php echo number_format($log['chi_phi']); ?> ₫</td>
                        <td class="actions text-center">
                            <a href="index.php?page=maintenance/view&id=<?php echo $log['id']; ?>" class="btn-icon"><i class="fas fa-eye"></i></a>
                            <?php if(isIT()): ?><a href="index.php?page=maintenance/edit&id=<?php echo $log['id']; ?>" class="btn-icon"><i class="fas fa-edit"></i></a><?php endif; ?>
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
            <input type="hidden" name="page" value="maintenance/history">
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
    const saved = JSON.parse(localStorage.getItem('maintenanceColumns'));
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
    const delBtn = document.getElementById('delete-selected-btn');
    if(delBtn) delBtn.addEventListener('click', () => { if(confirm(`Xóa các mục đã chọn?`)) { 
        const f = document.getElementById('maintenance-form'); f.action = 'index.php?page=maintenance/delete_multiple'; f.submit();
    }});
});
</script>