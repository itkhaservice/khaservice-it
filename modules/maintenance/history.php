<?php
// ==================================================
// PAGINATION CONFIG
// ==================================================
$rows_per_page = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : 10;
$current_page  = (isset($_GET['p']) && is_numeric($_GET['p'])) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

// ==================================================
// FILTER INPUT
// ==================================================
$filter_keyword = trim($_GET['filter_keyword'] ?? '');
$filter_project = trim($_GET['filter_project'] ?? '');
$filter_group   = trim($_GET['filter_group'] ?? '');
$filter_date_from = trim($_GET['filter_date_from'] ?? '');
$filter_date_to   = trim($_GET['filter_date_to'] ?? '');

// ==================================================
// BUILD QUERY
// ==================================================
$where_clauses = [];
$bind_params   = [];

// Keyword Search (Tìm trong tên TB, mã tài sản, tên tùy chỉnh, nội dung...)
if ($filter_keyword !== '') {
    $where_clauses[] = "(d.ten_thiet_bi LIKE :kw OR d.ma_tai_san LIKE :kw OR ml.custom_device_name LIKE :kw OR ml.noi_dung LIKE :kw OR ml.hu_hong LIKE :kw OR ml.xu_ly LIKE :kw)";
    $bind_params[':kw'] = '%' . $filter_keyword . '%';
}

// Filter by Project (Lấy trực tiếp từ maintenance_logs.project_id)
if ($filter_project !== '' && is_numeric($filter_project)) {
    $where_clauses[] = "ml.project_id = :project_id";
    $bind_params[':project_id'] = (int)$filter_project;
}

// Filter by Device Group
if ($filter_group !== '') {
    $where_clauses[] = "d.nhom_thiet_bi = :group";
    $bind_params[':group'] = $filter_group;
}

// Filter by Date Range
if ($filter_date_from !== '') {
    $where_clauses[] = "ml.ngay_su_co >= :date_from";
    $bind_params[':date_from'] = $filter_date_from;
}
if ($filter_date_to !== '') {
    $where_clauses[] = "ml.ngay_su_co <= :date_to";
    $bind_params[':date_to'] = $filter_date_to;
}

$where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

// Sorting
$allowed_sort_columns = [
    'ten_thiet_bi' => 'd.ten_thiet_bi',
    'ma_tai_san'   => 'd.ma_tai_san',
    'ngay_su_co'   => 'ml.ngay_su_co',
    'chi_phi'      => 'ml.chi_phi',
    'ten_du_an'    => 'p.ten_du_an'
];
$sort_by    = $_GET['sort_by'] ?? 'ngay_su_co';
$sort_order = strtoupper($_GET['sort_order'] ?? 'DESC');
if (!array_key_exists($sort_by, $allowed_sort_columns)) $sort_by = 'ngay_su_co';
if (!in_array($sort_order, ['ASC', 'DESC'])) $sort_order = 'DESC';
$order_sql = " ORDER BY {$allowed_sort_columns[$sort_by]} $sort_order";

// Count Total
$count_sql = "
    SELECT COUNT(ml.id) 
    FROM maintenance_logs ml 
    LEFT JOIN devices d ON ml.device_id = d.id 
    LEFT JOIN projects p ON ml.project_id = p.id
    $where_sql
";
$count_stmt = $pdo->prepare($count_sql);
foreach ($bind_params as $key => $value) $count_stmt->bindValue($key, $value);
$count_stmt->execute();
$total_rows  = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $rows_per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $rows_per_page;
if ($offset < 0) $offset = 0;

// Fetch Data (Ưu tiên lấy dự án từ log, tên đối tượng từ custom_name)
$data_sql = "
    SELECT ml.*, d.ma_tai_san, d.ten_thiet_bi, d.nhom_thiet_bi, p.ten_du_an 
    FROM maintenance_logs ml 
    LEFT JOIN devices d ON ml.device_id = d.id 
    LEFT JOIN projects p ON ml.project_id = p.id
    $where_sql 
    $order_sql 
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($data_sql);
foreach ($bind_params as $key => $value) $stmt->bindValue($key, $value);
$stmt->bindValue(':limit',  $rows_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dropdown Data
$projects_filter = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an")->fetchAll(PDO::FETCH_ASSOC);

// Column Config
$all_columns = [
    'ten_thiet_bi' => ['label' => 'Thiết bị / Đối tượng', 'default' => true],
    'ma_tai_san'   => ['label' => 'Mã Tài sản', 'default' => true],
    'ten_du_an'    => ['label' => 'Dự án', 'default' => true],
    'nhom_thiet_bi'=> ['label' => 'Nhóm', 'default' => false],
    'ngay_su_co'   => ['label' => 'Ngày yêu cầu', 'default' => true],
    'noi_dung'     => ['label' => 'Mô tả', 'default' => true],
    'chi_phi'      => ['label' => 'Chi phí', 'default' => true]
];
?>

<div class="page-header">
    <h2><i class="fas fa-history"></i> Lịch sử Hỗ trợ & Bảo trì</h2>
    <a href="index.php?page=maintenance/add" class="btn btn-primary"><i class="fas fa-plus"></i> Tạo Phiếu mới</a>
</div>

<!-- Filter & Toolbar -->
<div class="card filter-section">
    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="maintenance/history">
        
        <div class="filter-group">
            <label><i class="fas fa-building"></i> Dự án</label>
            <select name="filter_project">
                <option value="">-- Tất cả --</option>
                <?php foreach ($projects_filter as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo ($filter_project == $p['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['ten_du_an']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label><i class="far fa-calendar-alt"></i> Từ ngày</label>
            <input type="date" name="filter_date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
        </div>

        <div class="filter-group">
            <label><i class="far fa-calendar-alt"></i> Đến ngày</label>
            <input type="date" name="filter_date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
        </div>

        <div class="filter-group" style="flex: 2; min-width: 250px;">
            <label><i class="fas fa-search"></i> Tìm kiếm</label>
            <input type="text" name="filter_keyword" placeholder="Thiết bị, nội dung, yêu cầu..." value="<?php echo htmlspecialchars($filter_keyword); ?>">
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Lọc</button>
            <a href="index.php?page=maintenance/history" class="btn btn-secondary" title="Reset"><i class="fas fa-undo"></i></a>
        </div>
    </form>
</div>

<!-- Data Table -->
<div class="table-container card">
    <table class="content-table" id="maintenanceTable">
        <thead>
            <tr>
                <?php foreach ($all_columns as $colKey => $colConfig): ?>
                    <th class="col-header" data-col="<?php echo $colKey; ?>">
                        <?php echo htmlspecialchars($colConfig['label']); ?>
                    </th>
                <?php endforeach; ?>
                <th width="100" class="text-center">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="<?php echo count($all_columns) + 1; ?>" class="empty-state">
                        <i class="fas fa-clipboard-list" style="font-size: 3rem; color: #e2e8f0; margin-bottom: 10px;"></i>
                        <p>Không tìm thấy dữ liệu.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <?php 
                        $display_name = !empty($log['device_id']) ? $log['ten_thiet_bi'] : ($log['custom_device_name'] ?: "Hỗ trợ chung");
                        $display_code = !empty($log['device_id']) ? $log['ma_tai_san'] : "N/A";
                    ?>
                    <tr>
                        <td data-col="ten_thiet_bi" class="font-bold"><?php echo htmlspecialchars($display_name); ?></td>
                        <td data-col="ma_tai_san" class="font-medium text-primary"><?php echo htmlspecialchars($display_code); ?></td>
                        <td data-col="ten_du_an" class="text-muted"><?php echo htmlspecialchars($log['ten_du_an'] ?: "N/A"); ?></td>
                        <td data-col="nhom_thiet_bi"><?php echo htmlspecialchars($log['nhom_thiet_bi'] ?: "---"); ?></td>
                        <td data-col="ngay_su_co"><?php echo date('d/m/Y', strtotime($log['ngay_su_co'])); ?></td>
                        <td data-col="noi_dung" class="text-muted small"><?php echo htmlspecialchars($log['noi_dung']); ?></td>
                        <td data-col="chi_phi" class="font-bold text-warning"><?php echo number_format($log['chi_phi']); ?> ₫</td>
                        
                        <td class="actions text-center">
                            <a href="index.php?page=maintenance/view&id=<?php echo $log['id']; ?>" class="btn-icon" title="Xem"><i class="fas fa-eye"></i></a>
                            <a href="index.php?page=maintenance/edit&id=<?php echo $log['id']; ?>" class="btn-icon" title="Sửa"><i class="fas fa-edit"></i></a>
                            <button type="button" class="btn-icon text-danger" onclick="openDeleteModal(<?php echo $log['id']; ?>, '<?php echo htmlspecialchars($display_code); ?>', '<?php echo htmlspecialchars($log['ngay_su_co']); ?>')" title="Xóa">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="pagination-container">
    <div class="pagination-links">
        <?php
        $query_params = $_GET; unset($query_params['p']); 
        $base_url = 'index.php?' . http_build_query($query_params);
        ?>
        <a href="<?php echo $base_url . '&p=1'; ?>" class="page-link <?php echo $current_page <= 1 ? 'disabled' : ''; ?>"><i class="fas fa-angle-double-left"></i></a>
        <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
            <a href="<?php echo $base_url . '&p=' . $i; ?>" class="page-link <?php echo $i == $current_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <a href="<?php echo $base_url . '&p=' . $total_pages; ?>" class="page-link <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>"><i class="fas fa-angle-double-right"></i></a>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteLogModal" class="modal">
    <div class="modal-content delete-modal-content">
        <div class="delete-modal-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <h2 class="delete-modal-title">Xác nhận xóa phiếu?</h2>
        <p class="delete-modal-text">Bạn đang yêu cầu xóa phiếu <strong id="modal-log-code"></strong> ngày <span id="modal-log-date"></span>.</p>
        <div class="delete-modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Hủy bỏ</button>
            <form id="delete-log-form" action="" method="POST" style="display:inline;">
                <input type="hidden" name="confirm_delete" value="1">
                <button type="submit" class="btn btn-danger">Xác nhận Xóa</button>
            </form>
        </div>
    </div>
</div>

<script>
function openDeleteModal(id, code, date) {
    document.getElementById('modal-log-code').textContent = code;
    document.getElementById('modal-log-date').textContent = date;
    document.getElementById('delete-log-form').action = 'index.php?page=maintenance/delete&id=' + id;
    document.getElementById('deleteLogModal').classList.add('show');
}
function closeDeleteModal() {
    document.getElementById('deleteLogModal').classList.remove('show');
}
window.onclick = function(event) {
    if (event.target == document.getElementById('deleteLogModal')) closeDeleteModal();
}
</script>
