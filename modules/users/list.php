<?php
if ($_SESSION['role'] !== 'admin') {
    echo "<div class='card text-center' style='padding: 50px;'><i class='fas fa-lock' style='font-size: 3rem; color: #ef4444; margin-bottom: 20px;'></i><p>Bạn không có quyền truy cập chức năng này.</p></div>";
    return;
}

// PAGINATION CONFIG
$rows_per_page = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : 10;
$current_page  = (isset($_GET['p']) && is_numeric($_GET['p'])) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

$filter_keyword = trim($_GET['filter_keyword'] ?? '');
$filter_role    = trim($_GET['filter_role'] ?? '');

$where_clauses = ["deleted_at IS NULL"];
$bind_params   = [];

if ($filter_keyword !== '') {
    $where_clauses[] = "(username LIKE :kw OR fullname LIKE :kw)";
    $bind_params[':kw'] = '%' . $filter_keyword . '%';
}
if ($filter_role !== '') {
    $where_clauses[] = "role = :role";
    $bind_params[':role'] = $filter_role;
}

$where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

// Count Total
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users $where_sql");
foreach ($bind_params as $k => $v) $count_stmt->bindValue($k, $v);
$count_stmt->execute();
$total_rows  = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $rows_per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $rows_per_page;

// Fetch Data
$stmt = $pdo->prepare("SELECT id, username, fullname, role, created_at FROM users $where_sql ORDER BY username LIMIT :limit OFFSET :offset");
foreach ($bind_params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $rows_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users_list = $stmt->fetchAll();
?>

<div class="page-header">
    <h2><i class="fas fa-users"></i> Quản trị Người dùng</h2>
    <a href="index.php?page=users/add" class="btn btn-primary"><i class="fas fa-user-plus"></i> Thêm mới</a>
</div>

<div class="card filter-section-modern">
    <form action="index.php" method="GET" class="filter-form-modern">
        <input type="hidden" name="page" value="users/list">
        <div class="filter-main-grid">
            <div class="filter-item">
                <label>Vai trò</label>
                <select name="filter_role" class="form-select-sm">
                    <option value="">-- Tất cả --</option>
                    <option value="admin" <?php echo ($filter_role === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="it" <?php echo ($filter_role === 'it') ? 'selected' : ''; ?>>IT Staff</option>
                    <option value="xem" <?php echo ($filter_role === 'xem') ? 'selected' : ''; ?>>Viewer</option>
                </select>
            </div>
            <div class="filter-item" style="flex: 2;">
                <label>Từ khóa Tìm kiếm</label>
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="filter_keyword" placeholder="Username, họ tên..." value="<?php echo htmlspecialchars($filter_keyword); ?>" class="form-control-sm">
                </div>
            </div>
            <div class="filter-item" style="flex: 0 0 auto; flex-direction: row; gap: 8px;">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Lọc</button>
                <a href="index.php?page=users/list" class="btn btn-secondary btn-sm"><i class="fas fa-undo"></i></a>
            </div>
        </div>
    </form>
</div>

<form action="index.php?page=users/delete_multiple" method="POST" id="users-form">
    <div class="batch-actions" id="batch-actions" style="display: none;">
        <span class="selected-count-label">Đã chọn <strong id="selected-count">0</strong> mục</span>
        <button type="button" class="btn btn-danger btn-sm" id="delete-selected-btn" data-action="index.php?page=users/delete_multiple">Xóa đã chọn</button>
    </div>

    <div class="table-container card">
        <table class="content-table">
            <thead>
                <tr>
                    <th width="40"><input type="checkbox" id="select-all"></th>
                    <th>Username</th>
                    <th>Họ và tên</th>
                    <th>Vai trò</th>
                    <th>Ngày khởi tạo</th>
                    <th width="100" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users_list as $u): ?>
                    <tr>
                        <td>
                            <?php if($u['username'] !== $_SESSION['username']): ?>
                                <input type="checkbox" name="ids[]" value="<?php echo $u['id']; ?>" class="row-checkbox">
                            <?php endif; ?>
                        </td>
                        <td class="font-bold text-primary"><?php echo htmlspecialchars($u['username']); ?></td>
                        <td><?php echo htmlspecialchars($u['fullname']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($u['role'])); ?></td>
                        <td class="text-muted"><?php echo date('d/m/Y H:i', strtotime($u['created_at'])); ?></td>
                        <td class="actions text-center">
                            <a href="index.php?page=users/edit&id=<?php echo $u['id']; ?>" class="btn-icon" title="Sửa"><i class="fas fa-user-edit"></i></a>
                            <?php if($u['username'] !== $_SESSION['username']): ?>
                                <a href="index.php?page=users/delete&id=<?php echo $u['id']; ?>" data-url="index.php?page=users/delete&id=<?php echo $u['id']; ?>&confirm_delete=1" class="btn-icon delete-btn" title="Xóa"><i class="fas fa-trash-alt"></i></a>
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
            <input type="hidden" name="page" value="users/list">
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
.form-control-sm, .form-select-sm { height: 36px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 0.85rem; width: 100%; transition: 0.2s; }

.pagination-container .page-link { display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; color: #64748b; text-decoration: none; font-size: 0.9rem; font-weight: 500; }
.pagination-container .page-link.active { background-color: var(--primary-color); color: #fff; border-color: var(--primary-color); }
.pagination-container .page-link.disabled { color: #cbd5e1; pointer-events: none; }

@media (max-width: 768px) {
    .filter-main-grid { flex-direction: column; align-items: stretch; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectAll = document.getElementById('select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const batchActions = document.getElementById('batch-actions');
    const selectedCountDisplay = document.getElementById('selected-count');

    function updateBatchUI() {
        const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
        const totalCount = rowCheckboxes.length;
        if (batchActions) batchActions.style.display = (checkedCount > 0) ? 'flex' : 'none';
        if (selectedCountDisplay) selectedCountDisplay.textContent = checkedCount;
        if (selectAll) {
            selectAll.checked = (totalCount > 0 && checkedCount === totalCount);
            selectAll.indeterminate = (checkedCount > 0 && checkedCount < totalCount);
        }
    }
    rowCheckboxes.forEach(cb => cb.addEventListener('change', updateBatchUI));
    if (selectAll) selectAll.addEventListener('change', function() { rowCheckboxes.forEach(cb => cb.checked = this.checked); updateBatchUI(); });
});
</script>