<?php
// --- Pagination Logic ---
$rows_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
$current_page = isset($_GET['p']) ? (int)$_GET['p'] : 1;

// --- Filtering Logic ---
$filter_keyword = $_GET['filter_keyword'] ?? '';
$filter_project = $_GET['filter_project'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

$where_clauses = [];
$bind_params = [];

if (!empty($filter_keyword)) {
    $where_clauses[] = "(d.ma_tai_san LIKE :keyword OR d.ten_thiet_bi LIKE :keyword)";
    $bind_params[':keyword'] = '%' . $filter_keyword . '%';
}
if (!empty($filter_project)) {
    $where_clauses[] = "d.project_id = :project_id";
    $bind_params[':project_id'] = $filter_project;
}
if (!empty($filter_status)) {
    $where_clauses[] = "d.trang_thai = :trang_thai";
    $bind_params[':trang_thai'] = $filter_status;
}

$where_sql = count($where_clauses) > 0 ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

// --- Sorting Logic ---
$allowed_sort_columns = [
    'ma_tai_san' => 'd.ma_tai_san',
    'ten_thiet_bi' => 'd.ten_thiet_bi',
    'ten_du_an' => 'p.ten_du_an',
    'ten_npp' => 's.ten_npp',
    'trang_thai' => 'd.trang_thai',
    'created_at' => 'd.created_at' // Assuming 'created_at' is a column in 'devices' table
];
$sort_by = $_GET['sort_by'] ?? 'created_at';
$sort_order = $_GET['sort_order'] ?? 'DESC';

// Validate sort_by column
if (!array_key_exists($sort_by, $allowed_sort_columns)) {
    $sort_by = 'created_at'; // Default if invalid
}
// Validate sort_order
if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) {
    $sort_order = 'DESC'; // Default if invalid
}

$order_sql = " ORDER BY " . $allowed_sort_columns[$sort_by] . " " . $sort_order;


// --- Total Records Calculation ---
$total_rows_stmt = $pdo->prepare("SELECT COUNT(*) FROM devices d" . $where_sql);
$total_rows_stmt->execute($bind_params);
$total_rows = $total_rows_stmt->fetchColumn();
$total_pages = ceil($total_rows / $rows_per_page);

// Ensure current page is within valid range
if ($current_page < 1) $current_page = 1;
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;

$offset = ($current_page - 1) * $rows_per_page;
if ($offset < 0) $offset = 0; // Ensure offset is not negative

// --- Fetch Devices for the current page ---
$sql = "
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
    " . $where_sql .
    $order_sql . "
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':limit', $rows_per_page, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
foreach ($bind_params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$devices = $stmt->fetchAll();

// --- Fetch Projects and Statuses for dropdown filters ---
$projects = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an")->fetchAll();
$statuses = $pdo->query("SELECT DISTINCT trang_thai FROM devices ORDER BY trang_thai")->fetchAll();

?>

<h2>Danh sách Thiết bị</h2>

<!-- Filter Section -->
<div class="filter-section">
    <form action="index.php" method="GET">
        <input type="hidden" name="page" value="devices/list">
        <input type="text" name="filter_keyword" placeholder="Lọc theo tên, mã..." value="<?php echo htmlspecialchars($filter_keyword); ?>">
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
        <button type="submit" class="btn">Lọc</button>
    </form>
</div>

<!-- Main form for actions like export -->
<form action="index.php?page=devices/export" method="POST" id="devices-form">
    <div class="table-actions">
        <a href="index.php?page=devices/add" class="add-button btn btn-primary">Thêm thiết bị mới</a>
        <button type="submit" name="export_selected" class="btn">Export ra CSV</button>
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
                                <a href="index.php?page=devices/delete&id=<?php echo $device['id']; ?>" class="btn delete-btn" onclick="return confirm('Bạn có chắc muốn xóa thiết bị này?');">Xóa</a>
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

    <form action="index.php" method="GET" class="rows-per-page-form">
         <input type="hidden" name="page" value="devices/list">
         <!-- Persist other filters and sort parameters -->
         <?php foreach ($_GET as $key => $value): ?>
            <?php if ($key !== 'limit' && $key !== 'page'): ?>
                <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
            <?php endif; ?>
         <?php endforeach; ?>
        <span class="rows-per-page">
            Hiển thị
            <select name="limit" onchange="this.form.submit()">
                <option value="10" <?php echo $rows_per_page == 10 ? 'selected' : ''; ?>>10</option>
                <option value="25" <?php echo $rows_per_page == 25 ? 'selected' : ''; ?>>25</option>
                <option value="50" <?php echo $rows_per_page == 50 ? 'selected' : ''; ?>>50</option>
                <option value="100" <?php echo $rows_per_page == 100 ? 'selected' : ''; ?>>100</option>
            </select>
             dòng mỗi trang.
        </span>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
});
</script>