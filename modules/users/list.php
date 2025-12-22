<?php
if ($_SESSION['role'] !== 'admin') {
    echo "<div class='card text-center' style='padding: 50px;'><i class='fas fa-lock' style='font-size: 3rem; color: #ef4444; margin-bottom: 20px;'></i><p>Bạn không có quyền truy cập chức năng này.</p></div>";
    return;
}

// PAGINATION CONFIG
$rows_per_page = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : 5;
$current_page  = (isset($_GET['p']) && is_numeric($_GET['p'])) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

$filter_keyword = trim($_GET['filter_keyword'] ?? '');
$filter_role    = trim($_GET['filter_role'] ?? '');

$where_clauses = [];
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

<div class="card filter-section">
    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="users/list">
        <div class="filter-group">
            <label>Vai trò</label>
            <select name="filter_role">
                <option value="">-- Tất cả --</option>
                <option value="admin" <?php echo ($filter_role === 'admin') ? 'selected' : ''; ?>>Admin</option>
                <option value="it" <?php echo ($filter_role === 'it') ? 'selected' : ''; ?>>IT Staff</option>
                <option value="xem" <?php echo ($filter_role === 'xem') ? 'selected' : ''; ?>>Viewer</option>
            </select>
        </div>
        <div class="filter-group" style="flex: 2;">
            <label>Tìm kiếm</label>
            <input type="text" name="filter_keyword" placeholder="Username..." value="<?php echo htmlspecialchars($filter_keyword); ?>">
        </div>
        <div class="filter-actions" style="margin-left: auto;">
            <button type="submit" class="btn btn-primary">Lọc</button>
            <a href="index.php?page=users/list" class="btn btn-secondary"><i class="fas fa-undo"></i></a>
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
                            <a href="index.php?page=users/edit&id=<?php echo $u['id']; ?>" class="btn-icon"><i class="fas fa-user-edit"></i></a>
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
            <input type="hidden" name="page" value="users/list">
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
        <a href="<?php echo $base . '&p=1'; ?>" class="page-link <?php echo $current_page <= 1 ? 'disabled' : ''; ?>"><i class="fas fa-angle-double-left"></i></a>
        <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
            <a href="<?php echo $base . '&p=' . $i; ?>" class="page-link <?php echo $i == $current_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <a href="<?php echo $base . '&p=' . $total_pages; ?>" class="page-link <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>"><i class="fas fa-angle-double-right"></i></a>
    </div>
</div>

<script>
const selectAll = document.getElementById('select-all');
const rowCbs = document.querySelectorAll('.row-checkbox');
const batchActions = document.getElementById('batch-actions');
const selectedCount = document.getElementById('selected-count');
function updateBatch() {
    const n = document.querySelectorAll('.row-checkbox:checked').length;
    batchActions.style.display = n > 0 ? 'flex' : 'none';
    selectedCount.textContent = n;
}
if(selectAll) selectAll.addEventListener('change', function() { rowCbs.forEach(cb => cb.checked = this.checked); updateBatch(); });
rowCbs.forEach(cb => cb.addEventListener('change', updateBatch));
</script>
