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


<h2>Danh sách Thiết bị</h2>

<!-- Filter Section -->
<div class="filter-section">
    <form action="index.php" method="GET">
        <input type="hidden" name="page" value="devices/list">
        <select name="filter_project">
            <option value="">-- Lọc theo dự án --</option>
            <?php foreach ($projects as $project): ?>
                <option value="<?php echo $project['id']; ?>" <?php echo ($filter_project == $project['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($project['ten_du_an']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="filter_status">
            <option value="">-- Lọc theo trạng thái --</option>
             <?php foreach ($statuses as $status): ?>
                <option value="<?php echo htmlspecialchars($status['trang_thai']); ?>" <?php echo ($filter_status == $status['trang_thai']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($status['trang_thai']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="filter_keyword" placeholder="Lọc theo tên, mã..." value="<?php echo htmlspecialchars($filter_keyword); ?>">
        <button type="submit" class="btn btn-secondary">Lọc</button>
        <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php?page=devices/list'">Xóa bộ lọc</button>
    </form>
</div>

<!-- Main form for actions like export -->
<form action="index.php?page=devices/export" method="POST" id="devices-form">
    <div class="table-actions">
        <a href="index.php?page=devices/add" class="add-button btn btn-primary">Thêm thiết bị mới</a>
        <button type="submit" name="export_selected" class="btn btn-secondary" id="export-selected-btn" style="display: none;" formaction="index.php?page=devices/export">Export ra CSV</button>
        <button type="button" name="delete_selected" class="btn btn-danger" id="delete-selected-btn" style="display: none;">Xóa mục đã chọn</button>
        <span id="selected-count" style="margin-left: 15px; margin-right: 15px; font-weight: bold; display: none;"></span>
    </div>

    <div class="content-table-wrapper">
        <table class="content-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all"></th>
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
                        $sort_indicator = '';
                        if ($sort_by == $col_name) {
                            $new_sort_order = ($sort_order == 'ASC') ? 'DESC' : 'ASC';
                            $sort_indicator = ($sort_order == 'ASC') ? ' &#x25B2;' : ' &#x25BC;';
                        }
                        // Preserve existing GET parameters
                        $current_query_params = $_GET;
                        $current_query_params['sort_by'] = $col_name;
                        $current_query_params['sort_order'] = $new_sort_order;
                        $sort_link = 'index.php?' . http_build_query($current_query_params);
                        echo '<th><a href="' . $sort_link . '">' . htmlspecialchars($col_label) . $sort_indicator . '</a></th>';
                    }
                    ?>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($devices)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">Không tìm thấy thiết bị nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($devices as $device): ?>
                        <tr>
                            <td><input type="checkbox" name="selected_devices[]" value="<?php echo $device['id']; ?>" class="row-checkbox"></td>
                            <td><?php echo htmlspecialchars($device['ma_tai_san']); ?></td>
                            <td><?php echo htmlspecialchars($device['ten_thiet_bi']); ?></td>
                            <td><?php echo htmlspecialchars($device['ten_du_an']); ?></td>
                            <td><?php echo htmlspecialchars($device['ten_npp']); ?></td>
                            <td><?php echo htmlspecialchars($device['trang_thai']); ?></td>
                            <td class="actions">
                                <a href="index.php?page=devices/view&id=<?php echo $device['id']; ?>" class="btn view-btn">Xem</a>
                                <a href="index.php?page=devices/edit&id=<?php echo $device['id']; ?>" class="btn edit-btn">Sửa</a>
                                <a href="index.php?page=devices/delete&id=<?php echo $device['id']; ?>" class="btn delete-btn">Xóa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</form>

<!-- Pagination Section -->
<div class="pagination">
    <div class="pagination-links">
        <?php
        // Build URL with existing filters and sort parameters
        $query_params = $_GET;
        unset($query_params['p']); // Unset page number for base URL
        $base_url = 'index.php?' . http_build_query($query_params);
        ?>

        <a href="<?php echo $base_url . '&p=1'; ?>" <?php echo $current_page <= 1 ? 'class="disabled"' : ''; ?>>&laquo; Đầu</a>
        <a href="<?php echo $base_url . '&p=' . ($current_page - 1); ?>" <?php echo $current_page <= 1 ? 'class="disabled"' : ''; ?>>&laquo; Trước</a>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="<?php echo $base_url . '&p=' . $i; ?>" class="<?php echo $i == $current_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <a href="<?php echo $base_url . '&p=' . ($current_page + 1); ?>" <?php echo $current_page >= $total_pages ? 'class="disabled"' : ''; ?>>Sau &raquo;</a>
        <a href="<?php echo $base_url . '&p=' . $total_pages; ?>" <?php echo $current_page >= $total_pages ? 'class="disabled"' : ''; ?>>Cuối &raquo;</a>
    </div>

    <div class="pagination-controls">
        <form action="index.php" method="GET" class="rows-per-page-form">
            <input type="hidden" name="page" value="devices/list">
            <!-- Persist other filters and sort parameters -->
            <?php foreach ($_GET as $key => $value): ?>
                <?php if ($key !== 'limit' && $key !== 'page'): ?>
                    <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <span class="rows-per-page">
                <span>Hiển thị</span>
                <select name="limit" onchange="this.form.submit()">
                    <option value="5" <?php echo $rows_per_page == 5 ? 'selected' : ''; ?>>5</option>
                    <option value="10" <?php echo $rows_per_page == 10 ? '' : ''; ?>>10</option>
                    <option value="25" <?php echo $rows_per_page == 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?php echo $rows_per_page == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $rows_per_page == 100 ? 'selected' : ''; ?>>100</option>
                </select>
                <span>dòng mỗi trang.</span>
            </span>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const deleteSelectedBtn = document.getElementById('delete-selected-btn');
    const exportSelectedBtn = document.getElementById('export-selected-btn');
    const selectedCountSpan = document.getElementById('selected-count'); // Thêm dòng này

    function updateActionButtons() {
        const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
        
        if (checkedCount > 0) {
            deleteSelectedBtn.style.display = 'inline-flex';
            exportSelectedBtn.style.display = 'inline-flex';
            selectedCountSpan.style.display = 'inline'; // Hiển thị span
            selectedCountSpan.textContent = `Đã chọn ${checkedCount} thiết bị`; // Cập nhật văn bản
        } else {
            deleteSelectedBtn.style.display = 'none';
            exportSelectedBtn.style.display = 'none';
            selectedCountSpan.style.display = 'none'; // Ẩn span
            selectedCountSpan.textContent = ''; // Xóa văn bản
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
            // Uncheck 'select all' if a row is manually unchecked
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            }
            updateActionButtons();
        });
    });

    // Initial check on page load
    updateActionButtons();
});
</script>