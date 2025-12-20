<?php
// ==================================================
// PAGINATION
// ==================================================
$rows_per_page = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : 5;
$current_page  = (isset($_GET['p']) && is_numeric($_GET['p'])) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

// ==================================================
// FILTER INPUT (SANITIZE)
// ==================================================
$filter_keyword = trim($_GET['filter_keyword'] ?? '');
$filter_project = trim($_GET['filter_project'] ?? '');
$filter_status  = trim($_GET['filter_status'] ?? '');

// ==================================================
// BUILD WHERE CLAUSE DYNAMICALLY
// ==================================================
$where_clauses = [];
$bind_params   = [];

// Keyword
if ($filter_keyword !== '') {
    $where_clauses[] = "(d.ma_tai_san LIKE :kw_ma OR d.ten_thiet_bi LIKE :kw_ten)";
    $bind_params[':kw_ma']  = '%' . $filter_keyword . '%';
    $bind_params[':kw_ten'] = '%' . $filter_keyword . '%';
}


// Project
if ($filter_project !== '' && is_numeric($filter_project)) {
    $where_clauses[] = "d.project_id = :project_id";
    $bind_params[':project_id'] = (int)$filter_project;
}

// Status
if ($filter_status !== '') {
    $where_clauses[] = "d.trang_thai = :trang_thai";
    $bind_params[':trang_thai'] = $filter_status;
}

$where_sql = !empty($where_clauses)
    ? ' WHERE ' . implode(' AND ', $where_clauses)
    : '';

// ==================================================
// SORTING (ANTI SQL INJECTION)
// ==================================================
$allowed_sort_columns = [
    'ma_tai_san'   => 'd.ma_tai_san',
    'ten_thiet_bi' => 'd.ten_thiet_bi',
    'ten_du_an'    => 'p.ten_du_an',
    'ten_npp'      => 's.ten_npp',
    'trang_thai'   => 'd.trang_thai',
    'created_at'   => 'd.created_at'
];

$sort_by    = $_GET['sort_by'] ?? 'created_at';
$sort_order = strtoupper($_GET['sort_order'] ?? 'DESC');

if (!array_key_exists($sort_by, $allowed_sort_columns)) {
    $sort_by = 'created_at';
}
if (!in_array($sort_order, ['ASC', 'DESC'])) {
    $sort_order = 'DESC';
}

$order_sql = " ORDER BY {$allowed_sort_columns[$sort_by]} $sort_order";

// ==================================================
// COUNT TOTAL ROWS
// ==================================================
$count_sql = "
    SELECT COUNT(*)
    FROM devices d
    LEFT JOIN projects p ON d.project_id = p.id
    LEFT JOIN suppliers s ON d.supplier_id = s.id
    $where_sql
";

$count_stmt = $pdo->prepare($count_sql);
foreach ($bind_params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();

$total_rows  = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $rows_per_page));

// Fix current page
if ($current_page > $total_pages) {
    $current_page = $total_pages;
}

// ==================================================
// PAGINATION OFFSET
// ==================================================
$offset = ($current_page - 1) * $rows_per_page;
if ($offset < 0) $offset = 0;

// ==================================================
// FETCH DEVICES DATA
// ==================================================
$data_sql = "
    SELECT
        d.id,
        d.ma_tai_san,
        d.ten_thiet_bi,
        d.trang_thai,
        p.ten_du_an,
        s.ten_npp
    FROM devices d
    LEFT JOIN projects p ON d.project_id = p.id
    LEFT JOIN suppliers s ON d.supplier_id = s.id
    $where_sql
    $order_sql
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($data_sql);

// Bind filter params
foreach ($bind_params as $key => $value) {
    $stmt->bindValue($key, $value);
}

// Bind pagination params (BẮT BUỘC INT)
$stmt->bindValue(':limit',  $rows_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==================================================
// DATA FOR FILTER DROPDOWNS
// ==================================================
$projects = $pdo->query("
    SELECT id, ten_du_an
    FROM projects
    ORDER BY ten_du_an
")->fetchAll(PDO::FETCH_ASSOC);

$statuses = $pdo->query("
    SELECT DISTINCT trang_thai
    FROM devices
    ORDER BY trang_thai
")->fetchAll(PDO::FETCH_ASSOC);
?>


<div class="page-header">
    <h2><i class="fas fa-server"></i> Danh sách Thiết bị</h2>
    <a href="index.php?page=devices/add" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm mới</a>
</div>

<!-- Filter Section -->
<div class="card filter-section">
    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="devices/list">
        
        <div class="filter-group">
            <label><i class="fas fa-building"></i> Dự án</label>
            <select name="filter_project">
                <option value="">-- Tất cả --</option>
                <?php foreach ($projects as $project): ?>
                    <option value="<?php echo $project['id']; ?>" <?php echo ($filter_project == $project['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($project['ten_du_an']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label><i class="fas fa-info-circle"></i> Trạng thái</label>
            <select name="filter_status">
                <option value="">-- Tất cả --</option>
                 <?php foreach ($statuses as $status): ?>
                    <option value="<?php echo htmlspecialchars($status['trang_thai']); ?>" <?php echo ($filter_status == $status['trang_thai']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($status['trang_thai']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label><i class="fas fa-search"></i> Tìm kiếm</label>
            <input type="text" name="filter_keyword" placeholder="Mã TS, Tên thiết bị..." value="<?php echo htmlspecialchars($filter_keyword); ?>">
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Lọc</button>
            <a href="index.php?page=devices/list" class="btn btn-secondary" title="Xóa bộ lọc"><i class="fas fa-undo"></i></a>
        </div>
    </form>
</div>

<!-- Main form for actions like export -->
<form action="index.php?page=devices/export" method="POST" id="devices-form">
    
    <!-- Batch Actions Toolbar -->
    <div class="batch-actions" id="batch-actions" style="display: none;">
        <span class="selected-count-label">Đã chọn <strong id="selected-count">0</strong> thiết bị</span>
        <div class="action-buttons">
            <button type="submit" name="export_selected" class="btn btn-secondary btn-sm" formaction="index.php?page=devices/export">
                <i class="fas fa-file-export"></i> Xuất CSV
            </button>
            <button type="button" name="delete_selected" class="btn btn-danger btn-sm" id="delete-selected-btn">
                <i class="fas fa-trash-alt"></i> Xóa
            </button>
        </div>
    </div>

    <div class="table-container card">
        <table class="content-table">
            <thead>
                <tr>
                    <th width="40"><input type="checkbox" id="select-all"></th>
                    <?php
                    $columns = [
                        'ma_tai_san' => 'Mã Tài sản',
                        'ten_thiet_bi' => 'Tên Thiết bị',
                        'ten_du_an' => 'Dự án',
                        'ten_npp' => 'Nhà cung cấp',
                        'trang_thai' => 'Trạng thái'
                    ];

                    foreach ($columns as $col_name => $col_label) {
                        $new_sort_order = 'ASC';
                        $sort_icon = '<i class="fas fa-sort" style="color: #ccc;"></i>';
                        
                        if ($sort_by == $col_name) {
                            $new_sort_order = ($sort_order == 'ASC') ? 'DESC' : 'ASC';
                            $sort_icon = ($sort_order == 'ASC') ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
                        }
                        
                        // Preserve existing GET parameters
                        $current_query_params = $_GET;
                        $current_query_params['sort_by'] = $col_name;
                        $current_query_params['sort_order'] = $new_sort_order;
                        $sort_link = 'index.php?' . http_build_query($current_query_params);
                        
                        echo '<th><a href="' . $sort_link . '" class="sort-link">' . htmlspecialchars($col_label) . ' ' . $sort_icon . '</a></th>';
                    }
                    ?>
                    <th width="120" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($devices)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="fas fa-search" style="font-size: 3rem; color: #e2e8f0; margin-bottom: 10px;"></i>
                            <p>Không tìm thấy thiết bị nào phù hợp.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($devices as $device): ?>
                        <tr>
                            <td><input type="checkbox" name="selected_devices[]" value="<?php echo $device['id']; ?>" class="row-checkbox"></td>
                            <td class="font-medium text-primary"><?php echo htmlspecialchars($device['ma_tai_san']); ?></td>
                            <td><?php echo htmlspecialchars($device['ten_thiet_bi']); ?></td>
                            <td><?php echo htmlspecialchars($device['ten_du_an']); ?></td>
                            <td><?php echo htmlspecialchars($device['ten_npp']); ?></td>
                            <td>
                                <?php 
                                    $statusClass = 'status-default';
                                    if ($device['trang_thai'] === 'Đang sử dụng') $statusClass = 'status-active';
                                    elseif ($device['trang_thai'] === 'Hỏng') $statusClass = 'status-error';
                                    elseif ($device['trang_thai'] === 'Thanh lý') $statusClass = 'status-warning';
                                ?>
                                <span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($device['trang_thai']); ?></span>
                            </td>
                            <td class="actions text-center">
                                <a href="index.php?page=devices/view&id=<?php echo $device['id']; ?>" class="btn-icon" title="Xem"><i class="fas fa-eye"></i></a>
                                <a href="index.php?page=devices/edit&id=<?php echo $device['id']; ?>" class="btn-icon" title="Sửa"><i class="fas fa-edit"></i></a>
                                <button type="button" 
                                        class="btn-icon text-danger" 
                                        title="Xóa"
                                        onclick="openDeleteModal(<?php echo $device['id']; ?>, '<?php echo htmlspecialchars($device['ten_thiet_bi']); ?>', '<?php echo htmlspecialchars($device['ma_tai_san']); ?>')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</form>

<!-- Pagination Section -->
<div class="pagination-container">
    <div class="rows-per-page">
        <form action="index.php" method="GET" class="rows-per-page-form">
            <input type="hidden" name="page" value="devices/list">
            <?php foreach ($_GET as $key => $value): ?>
                <?php if ($key !== 'limit' && $key !== 'page'): ?>
                    <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <label>Hiển thị</label>
            <select name="limit" onchange="this.form.submit()" class="form-select-sm">
                <option value="5" <?php echo $rows_per_page == 5 ? 'selected' : ''; ?>>5</option>
                <option value="10" <?php echo $rows_per_page == 10 ? 'selected' : ''; ?>>10</option>
                <option value="25" <?php echo $rows_per_page == 25 ? 'selected' : ''; ?>>25</option>
                <option value="50" <?php echo $rows_per_page == 50 ? 'selected' : ''; ?>>50</option>
            </select>
        </form>
    </div>

    <div class="pagination-links">
        <?php
        $query_params = $_GET;
        unset($query_params['p']); 
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

<!-- BEAUTIFUL DELETE MODAL -->
<div id="deleteDeviceModal" class="modal">
    <div class="modal-content delete-modal-content">
        <div class="delete-modal-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2 class="delete-modal-title">Xác nhận xóa thiết bị?</h2>
        <p class="delete-modal-text">
            Bạn đang yêu cầu xóa thiết bị <strong id="modal-device-name"></strong> (<span id="modal-device-code"></span>).
        </p>
        <div class="delete-alert-box">
            <i class="fas fa-info-circle"></i> 
            <span>Hành động này sẽ xóa vĩnh viễn thiết bị và <strong>tất cả dữ liệu liên quan</strong>. Không thể hoàn tác!</span>
        </div>
        
        <form id="delete-device-form" action="" method="POST" class="delete-modal-actions">
            <input type="hidden" name="confirm_delete" value="1">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Hủy bỏ</button>
            <button type="submit" class="btn btn-danger">Xác nhận Xóa</button>
        </form>
    </div>
</div>

<script>
// Main List Functionality
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const batchActions = document.getElementById('batch-actions');
    const selectedCountSpan = document.getElementById('selected-count');

    function updateActionButtons() {
        const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
        
        if (checkedCount > 0) {
            batchActions.style.display = 'flex';
            selectedCountSpan.textContent = checkedCount;
        } else {
            batchActions.style.display = 'none';
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateActionButtons();
        });
    }

    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            }
            updateActionButtons();
        });
    });

    updateActionButtons();
});

// Modal Logic
function openDeleteModal(id, name, code) {
    document.getElementById('modal-device-name').textContent = name;
    document.getElementById('modal-device-code').textContent = code;
    document.getElementById('delete-device-form').action = 'index.php?page=devices/delete&id=' + id;
    
    document.getElementById('deleteDeviceModal').classList.add('show');
}

function closeDeleteModal() {
    document.getElementById('deleteDeviceModal').classList.remove('show');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('deleteDeviceModal');
    if (event.target == modal) {
        closeDeleteModal();
    }
}
</script>
