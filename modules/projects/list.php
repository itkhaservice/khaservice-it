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
$filter_type    = trim($_GET['filter_type'] ?? '');

// ==================================================
// BUILD QUERY
// ==================================================
$where_clauses = [];
$bind_params   = [];

if ($filter_keyword !== '') {
    $where_clauses[] = "(ma_du_an LIKE :kw OR ten_du_an LIKE :kw OR dia_chi LIKE :kw)";
    $bind_params[':kw'] = '%' . $filter_keyword . '%';
}
if ($filter_type !== '') {
    $where_clauses[] = "loai_du_an = :loai";
    $bind_params[':loai'] = $filter_type;
}

$where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

// Sorting
$allowed_sort_columns = [
    'ma_du_an'   => 'ma_du_an',
    'ten_du_an'  => 'ten_du_an',
    'dia_chi'    => 'dia_chi',
    'loai_du_an' => 'loai_du_an',
    'ghi_chu'    => 'ghi_chu'
];
$sort_by    = $_GET['sort_by'] ?? 'ten_du_an';
$sort_order = strtoupper($_GET['sort_order'] ?? 'ASC');
if (!array_key_exists($sort_by, $allowed_sort_columns)) $sort_by = 'ten_du_an';
if (!in_array($sort_order, ['ASC', 'DESC'])) $sort_order = 'ASC';
$order_sql = " ORDER BY {$allowed_sort_columns[$sort_by]} $sort_order";

// Count Total
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM projects $where_sql");
foreach ($bind_params as $key => $value) $count_stmt->bindValue($key, $value);
$count_stmt->execute();
$total_rows  = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $rows_per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $rows_per_page;
if ($offset < 0) $offset = 0;

// Fetch Data
$stmt = $pdo->prepare("SELECT * FROM projects $where_sql $order_sql LIMIT :limit OFFSET :offset");
foreach ($bind_params as $key => $value) $stmt->bindValue($key, $value);
$stmt->bindValue(':limit',  $rows_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dropdown Data
$project_types_stmt = $pdo->query("SELECT DISTINCT loai_du_an FROM projects WHERE loai_du_an IS NOT NULL AND loai_du_an != '' ORDER BY loai_du_an");
$project_types = $project_types_stmt->fetchAll(PDO::FETCH_COLUMN);

// ==================================================
// COLUMN CONFIGURATION
// ==================================================
$all_columns = [
    'ma_du_an'   => ['label' => 'Mã Dự án', 'default' => true],
    'ten_du_an'  => ['label' => 'Tên Dự án', 'default' => true],
    'dia_chi'    => ['label' => 'Địa chỉ', 'default' => true],
    'loai_du_an' => ['label' => 'Loại', 'default' => true],
    'ghi_chu'    => ['label' => 'Ghi chú', 'default' => false]
];
?>

<div class="page-header">
    <h2><i class="fas fa-building"></i> Danh sách Dự án</h2>
    <a href="index.php?page=projects/add" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm mới</a>
</div>

<!-- Filter & Toolbar -->
<div class="card filter-section">
    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="projects/list">
        
        <div class="filter-group">
            <label><i class="fas fa-layer-group"></i> Loại Dự án</label>
            <select name="filter_type">
                <option value="">-- Tất cả --</option>
                <?php foreach ($project_types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($filter_type == $type) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label><i class="fas fa-search"></i> Tìm kiếm</label>
            <input type="text" name="filter_keyword" placeholder="Mã, tên, địa chỉ..." value="<?php echo htmlspecialchars($filter_keyword); ?>">
        </div>

        <div class="filter-actions" style="margin-left: auto;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Lọc</button>
            <a href="index.php?page=projects/list" class="btn btn-secondary" title="Reset"><i class="fas fa-undo"></i></a>
            
            <!-- Column Selector Dropdown -->
            <div class="column-selector-container">
                <button type="button" class="btn btn-secondary" onclick="toggleColumnMenu()" title="Tùy chọn cột">
                    <i class="fas fa-columns"></i> Cột
                </button>
                <div id="columnMenu" class="dropdown-menu">
                    <div class="dropdown-header">Hiển thị cột</div>
                    <div class="column-list">
                        <?php foreach ($all_columns as $colKey => $colConfig): ?>
                            <label class="column-item">
                                <input type="checkbox" 
                                       class="col-checkbox" 
                                       data-target="<?php echo $colKey; ?>" 
                                       <?php echo $colConfig['default'] ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($colConfig['label']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Data Table -->
<div class="table-container card">
    <table class="content-table" id="projectsTable">
        <thead>
            <tr>
                <?php foreach ($all_columns as $colKey => $colConfig): ?>
                    <th class="col-header" data-col="<?php echo $colKey; ?>">
                        <?php 
                            $new_sort_order = 'ASC';
                            $sort_icon = '<i class="fas fa-sort" style="opacity:0.3"></i>';
                            if ($sort_by == $colKey) {
                                $new_sort_order = ($sort_order == 'ASC') ? 'DESC' : 'ASC';
                                $sort_icon = ($sort_order == 'ASC') ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
                            }
                            $current_query = $_GET;
                            $current_query['sort_by'] = $colKey;
                            $current_query['sort_order'] = $new_sort_order;
                            $sort_link = 'index.php?' . http_build_query($current_query);
                        ?>
                        <a href="<?php echo $sort_link; ?>" class="sort-link">
                            <?php echo htmlspecialchars($colConfig['label']); ?> <?php echo $sort_icon; ?>
                        </a>
                    </th>
                <?php endforeach; ?>
                <th width="120" class="text-center">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($projects)): ?>
                <tr>
                    <td colspan="<?php echo count($all_columns) + 1; ?>" class="empty-state">
                        <i class="fas fa-search" style="font-size: 3rem; color: #e2e8f0; margin-bottom: 10px;"></i>
                        <p>Không tìm thấy dự án nào.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($projects as $project): ?>
                    <tr>
                        <td data-col="ma_du_an" class="font-medium text-primary"><?php echo htmlspecialchars($project['ma_du_an']); ?></td>
                        <td data-col="ten_du_an" class="font-bold"><?php echo htmlspecialchars($project['ten_du_an']); ?></td>
                        <td data-col="dia_chi"><?php echo htmlspecialchars($project['dia_chi']); ?></td>
                        <td data-col="loai_du_an"><span class="badge status-info"><?php echo htmlspecialchars($project['loai_du_an']); ?></span></td>
                        <td data-col="ghi_chu" class="text-muted small"><?php echo htmlspecialchars($project['ghi_chu']); ?></td>
                        
                        <td class="actions text-center">
                            <a href="index.php?page=projects/view&id=<?php echo $project['id']; ?>" class="btn-icon" title="Xem"><i class="fas fa-eye"></i></a>
                            <a href="index.php?page=projects/edit&id=<?php echo $project['id']; ?>" class="btn-icon" title="Sửa"><i class="fas fa-edit"></i></a>
                            <button type="button" 
                                    class="btn-icon text-danger" 
                                    onclick="openDeleteModal(<?php echo $project['id']; ?>, '<?php echo htmlspecialchars($project['ten_du_an']); ?>', '<?php echo htmlspecialchars($project['ma_du_an']); ?>')"
                                    title="Xóa">
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
    <div class="rows-per-page">
        <form action="index.php" method="GET" class="rows-per-page-form">
            <input type="hidden" name="page" value="projects/list">
            <?php foreach ($_GET as $key => $value): if(!in_array($key, ['limit','page'])) { ?>
                <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
            <?php } endforeach; ?>
            <label>Hiển thị</label>
            <select name="limit" onchange="this.form.submit()" class="form-select-sm">
                <?php foreach([5,10,25,50] as $lim): ?>
                    <option value="<?php echo $lim; ?>" <?php echo $rows_per_page == $lim ? 'selected' : ''; ?>><?php echo $lim; ?></option>
                <?php endforeach; ?>
            </select>
            <span>dòng / trang</span>
        </form>
    </div>
    <div class="pagination-links">
        <?php
        $query_params = $_GET; unset($query_params['p']); 
        $base_url = 'index.php?' . http_build_query($query_params);
        ?>
        <a href="<?php echo $base_url . '&p=1'; ?>" class="page-link <?php echo $current_page <= 1 ? 'disabled' : ''; ?>"><i class="fas fa-angle-double-left"></i></a>
        <a href="<?php echo $base_url . '&p=' . ($current_page - 1); ?>" class="page-link <?php echo $current_page <= 1 ? 'disabled' : ''; ?>"><i class="fas fa-angle-left"></i></a>
        <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
            <a href="<?php echo $base_url . '&p=' . $i; ?>" class="page-link <?php echo $i == $current_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <a href="<?php echo $base_url . '&p=' . ($current_page + 1); ?>" class="page-link <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>"><i class="fas fa-angle-right"></i></a>
        <a href="<?php echo $base_url . '&p=' . $total_pages; ?>" class="page-link <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>"><i class="fas fa-angle-double-right"></i></a>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteProjectModal" class="modal">
    <div class="modal-content delete-modal-content">
        <div class="delete-modal-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <h2 class="delete-modal-title">Xác nhận xóa dự án?</h2>
        <p class="delete-modal-text">Bạn đang yêu cầu xóa dự án <strong id="modal-project-name"></strong> (<span id="modal-project-code"></span>).</p>
        <div class="delete-alert-box"><i class="fas fa-info-circle"></i> <span>Dự án sẽ bị xóa vĩnh viễn. <strong>Cần đảm bảo không còn thiết bị nào thuộc dự án này</strong> trước khi xóa.</span></div>
        <form id="delete-project-form" action="" method="POST" class="delete-modal-actions">
            <input type="hidden" name="confirm_delete" value="1">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Hủy bỏ</button>
            <button type="submit" class="btn btn-danger">Xác nhận Xóa</button>
        </form>
    </div>
</div>

<script>
// Column Logic
function toggleColumnMenu() {
    document.getElementById('columnMenu').classList.toggle('show');
}
document.addEventListener('click', function(e) {
    if (!document.querySelector('.column-selector-container').contains(e.target)) {
        document.getElementById('columnMenu').classList.remove('show');
    }
});
const checkboxes = document.querySelectorAll('.col-checkbox');
function updateColumns() {
    const visibleCols = {};
    checkboxes.forEach(cb => {
        const target = cb.getAttribute('data-target');
        const isVisible = cb.checked;
        visibleCols[target] = isVisible;
        const th = document.querySelector(`th[data-col="${target}"]`);
        if(th) th.style.display = isVisible ? '' : 'none';
        const cells = document.querySelectorAll(`td[data-col="${target}"]`);
        cells.forEach(cell => cell.style.display = isVisible ? '' : 'none');
    });
    localStorage.setItem('projectColumns', JSON.stringify(visibleCols));
}
document.addEventListener('DOMContentLoaded', function() {
    const savedCols = JSON.parse(localStorage.getItem('projectColumns'));
    if (savedCols) {
        checkboxes.forEach(cb => {
            const target = cb.getAttribute('data-target');
            if (savedCols.hasOwnProperty(target)) cb.checked = savedCols[target];
        });
    }
    updateColumns();
    checkboxes.forEach(cb => cb.addEventListener('change', updateColumns));
});

// Modal Logic
function openDeleteModal(id, name, code) {
    document.getElementById('modal-project-name').textContent = name;
    document.getElementById('modal-project-code').textContent = code;
    document.getElementById('delete-project-form').action = 'index.php?page=projects/delete&id=' + id;
    document.getElementById('deleteProjectModal').classList.add('show');
}
function closeDeleteModal() {
    document.getElementById('deleteProjectModal').classList.remove('show');
}
window.onclick = function(event) {
    if (event.target == document.getElementById('deleteProjectModal')) closeDeleteModal();
}
</script>
