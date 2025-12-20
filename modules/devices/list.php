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
$filter_project = trim($_GET['filter_project'] ?? '');
$filter_status  = trim($_GET['filter_status'] ?? '');

// ==================================================
// BUILD QUERY
// ==================================================
$where_clauses = [];
$bind_params   = [];

if ($filter_keyword !== '') {
    $where_clauses[] = "(d.ma_tai_san LIKE :kw_ma OR d.ten_thiet_bi LIKE :kw_ten OR d.serial LIKE :kw_serial OR d.model LIKE :kw_model)";
    $bind_params[':kw_ma']  = '%' . $filter_keyword . '%';
    $bind_params[':kw_ten'] = '%' . $filter_keyword . '%';
    $bind_params[':kw_serial'] = '%' . $filter_keyword . '%';
    $bind_params[':kw_model'] = '%' . $filter_keyword . '%';
}

if ($filter_project !== '' && is_numeric($filter_project)) {
    $where_clauses[] = "d.project_id = :project_id";
    $bind_params[':project_id'] = (int)$filter_project;
}

if ($filter_status !== '') {
    $where_clauses[] = "d.trang_thai = :trang_thai";
    $bind_params[':trang_thai'] = $filter_status;
}

$where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

// Sorting
$allowed_sort_columns = [
    'ma_tai_san'   => 'd.ma_tai_san',
    'ten_thiet_bi' => 'd.ten_thiet_bi',
    'ten_du_an'    => 'p.ten_du_an',
    'ten_npp'      => 's.ten_npp',
    'trang_thai'   => 'd.trang_thai',
    'created_at'   => 'd.created_at',
    'gia_mua'      => 'd.gia_mua',
    'ngay_mua'     => 'd.ngay_mua'
];
$sort_by    = $_GET['sort_by'] ?? 'created_at';
$sort_order = strtoupper($_GET['sort_order'] ?? 'DESC');
if (!array_key_exists($sort_by, $allowed_sort_columns)) $sort_by = 'created_at';
if (!in_array($sort_order, ['ASC', 'DESC'])) $sort_order = 'DESC';
$order_sql = " ORDER BY {$allowed_sort_columns[$sort_by]} $sort_order";

// Count Total
$count_sql = "SELECT COUNT(*) FROM devices d LEFT JOIN projects p ON d.project_id = p.id LEFT JOIN suppliers s ON d.supplier_id = s.id $where_sql";
$count_stmt = $pdo->prepare($count_sql);
foreach ($bind_params as $key => $value) $count_stmt->bindValue($key, $value);
$count_stmt->execute();
$total_rows  = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $rows_per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $rows_per_page;
if ($offset < 0) $offset = 0;

// Fetch Data (FULL COLUMNS)
$data_sql = "
    SELECT
        d.*,
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
foreach ($bind_params as $key => $value) $stmt->bindValue($key, $value);
$stmt->bindValue(':limit',  $rows_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dropdown Data
$projects = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an")->fetchAll(PDO::FETCH_ASSOC);
$statuses = $pdo->query("SELECT DISTINCT trang_thai FROM devices ORDER BY trang_thai")->fetchAll(PDO::FETCH_ASSOC);

// ==================================================
// COLUMN CONFIGURATION
// ==================================================
$all_columns = [
    'ma_tai_san'   => ['label' => 'Mã Tài sản', 'default' => true],
    'ten_thiet_bi' => ['label' => 'Tên Thiết bị', 'default' => true],
    'loai_thiet_bi'=> ['label' => 'Loại', 'default' => false],
    'model'        => ['label' => 'Model', 'default' => false],
    'serial'       => ['label' => 'Serial', 'default' => false],
    'ten_du_an'    => ['label' => 'Dự án', 'default' => true],
    'ten_npp'      => ['label' => 'Nhà cung cấp', 'default' => true],
    'ngay_mua'     => ['label' => 'Ngày mua', 'default' => false],
    'gia_mua'      => ['label' => 'Giá mua', 'default' => false],
    'bao_hanh_den' => ['label' => 'Hạn BH', 'default' => false],
    'trang_thai'   => ['label' => 'Trạng thái', 'default' => true],
];
?>

<div class="page-header">
    <h2><i class="fas fa-server"></i> Danh sách Thiết bị</h2>
    <a href="index.php?page=devices/add" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm mới</a>
</div>

<!-- Filter & Toolbar -->
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
            <input type="text" name="filter_keyword" placeholder="Mã TS, Tên, Model..." value="<?php echo htmlspecialchars($filter_keyword); ?>">
        </div>

        <div class="filter-actions" style="margin-left: auto;"> <!-- Push actions to right -->
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Lọc</button>
            <a href="index.php?page=devices/list" class="btn btn-secondary" title="Reset"><i class="fas fa-undo"></i></a>
            
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
<form action="index.php?page=devices/export" method="POST" id="devices-form">
    
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
        <table class="content-table" id="devicesTable">
            <thead>
                <tr>
                    <th width="40"><input type="checkbox" id="select-all"></th>
                    <?php foreach ($all_columns as $colKey => $colConfig): ?>
                        <th class="col-header" data-col="<?php echo $colKey; ?>">
                            <?php 
                                // Sort Link Logic
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
                <?php if (empty($devices)): ?>
                    <tr>
                        <td colspan="<?php echo count($all_columns) + 2; ?>" class="empty-state">
                            <i class="fas fa-search" style="font-size: 3rem; color: #e2e8f0; margin-bottom: 10px;"></i>
                            <p>Không tìm thấy thiết bị nào phù hợp.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($devices as $device): ?>
                        <tr>
                            <td><input type="checkbox" name="selected_devices[]" value="<?php echo $device['id']; ?>" class="row-checkbox"></td>
                            
                            <!-- DATA COLUMNS -->
                            <td data-col="ma_tai_san" class="font-medium text-primary"><?php echo htmlspecialchars($device['ma_tai_san']); ?></td>
                            <td data-col="ten_thiet_bi"><?php echo htmlspecialchars($device['ten_thiet_bi']); ?></td>
                            <td data-col="loai_thiet_bi"><?php echo htmlspecialchars($device['loai_thiet_bi']); ?></td>
                            <td data-col="model"><?php echo htmlspecialchars($device['model']); ?></td>
                            <td data-col="serial"><?php echo htmlspecialchars($device['serial']); ?></td>
                            <td data-col="ten_du_an"><?php echo htmlspecialchars($device['ten_du_an']); ?></td>
                            <td data-col="ten_npp"><?php echo htmlspecialchars($device['ten_npp']); ?></td>
                            <td data-col="ngay_mua"><?php echo $device['ngay_mua'] ? date('d/m/Y', strtotime($device['ngay_mua'])) : '-'; ?></td>
                            <td data-col="gia_mua"><?php echo number_format($device['gia_mua']); ?></td>
                            <td data-col="bao_hanh_den">
                                <?php if($device['bao_hanh_den']): ?>
                                    <span class="<?php echo (strtotime($device['bao_hanh_den']) < time()) ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo date('d/m/Y', strtotime($device['bao_hanh_den'])); ?>
                                    </span>
                                <?php else: echo '-'; endif; ?>
                            </td>
                            <td data-col="trang_thai">
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
                                <button type="button" class="btn-icon text-danger" onclick="openDeleteModal(<?php echo $device['id']; ?>, '<?php echo htmlspecialchars($device['ten_thiet_bi']); ?>', '<?php echo htmlspecialchars($device['ma_tai_san']); ?>')"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</form>

<!-- Pagination -->
<div class="pagination-container">
    <div class="rows-per-page">
        <form action="index.php" method="GET" class="rows-per-page-form">
            <input type="hidden" name="page" value="devices/list">
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

<!-- Delete Modal (Included from previous step) -->
<div id="deleteDeviceModal" class="modal">
    <div class="modal-content delete-modal-content">
        <div class="delete-modal-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <h2 class="delete-modal-title">Xác nhận xóa thiết bị?</h2>
        <p class="delete-modal-text">Bạn đang yêu cầu xóa thiết bị <strong id="modal-device-name"></strong> (<span id="modal-device-code"></span>).</p>
        <div class="delete-alert-box"><i class="fas fa-info-circle"></i> <span>Hành động này sẽ xóa vĩnh viễn thiết bị và <strong>tất cả dữ liệu liên quan</strong>. Không thể hoàn tác!</span></div>
        <form id="delete-device-form" action="" method="POST" class="delete-modal-actions">
            <input type="hidden" name="confirm_delete" value="1">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Hủy bỏ</button>
            <button type="submit" class="btn btn-danger">Xác nhận Xóa</button>
        </form>
    </div>
</div>

<script>
// --- COLUMN VISIBILITY LOGIC ---
function toggleColumnMenu() {
    document.getElementById('columnMenu').classList.toggle('show');
}

// Close menu when clicking outside
document.addEventListener('click', function(e) {
    const container = document.querySelector('.column-selector-container');
    if (container && !container.contains(e.target)) {
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

        // Toggle Header
        const th = document.querySelector(`th[data-col="${target}"]`);
        if(th) th.style.display = isVisible ? '' : 'none';

        // Toggle Cells
        const cells = document.querySelectorAll(`td[data-col="${target}"]`);
        cells.forEach(cell => {
            cell.style.display = isVisible ? '' : 'none';
        });
    });
    
    // Save to LocalStorage
    localStorage.setItem('deviceColumns', JSON.stringify(visibleCols));
}

// Init Columns on Load
document.addEventListener('DOMContentLoaded', function() {
    const savedCols = JSON.parse(localStorage.getItem('deviceColumns'));
    
    if (savedCols) {
        checkboxes.forEach(cb => {
            const target = cb.getAttribute('data-target');
            if (savedCols.hasOwnProperty(target)) {
                cb.checked = savedCols[target];
            }
        });
    }
    
    updateColumns(); // Apply state

    // Attach listeners
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateColumns);
    });
});

// --- EXISTING LIST LOGIC (Select All, etc.) ---
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
            if (!this.checked) selectAllCheckbox.checked = false;
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
window.onclick = function(event) {
    if (event.target == document.getElementById('deleteDeviceModal')) {
        closeDeleteModal();
    }
}
</script>