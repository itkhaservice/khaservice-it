<?php
// --- Pagination Logic ---
$rows_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$current_page = isset($_GET['p']) ? (int)$_GET['p'] : 1;

// --- Filtering Logic ---
$filter_keyword = $_GET['filter_keyword'] ?? '';
$filter_device = $_GET['filter_device'] ?? '';

$where_clauses = [];
$bind_params = [];

if (!empty($filter_keyword)) {
    $where_clauses[] = "(d.ten_thiet_bi LIKE :keyword OR d.ma_tai_san LIKE :keyword OR ml.noi_dung LIKE :keyword OR ml.hu_hong LIKE :keyword OR ml.xu_ly LIKE :keyword)";
    $bind_params[':keyword'] = '%' . $filter_keyword . '%';
}
if (!empty($filter_device)) {
    $where_clauses[] = "ml.device_id = :device_id";
    $bind_params[':device_id'] = $filter_device;
}

$where_sql = count($where_clauses) > 0 ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

// --- Sorting Logic ---
$allowed_sort_columns = [
    'ten_thiet_bi' => 'd.ten_thiet_bi',
    'ma_tai_san' => 'd.ma_tai_san',
    'ngay_su_co' => 'ml.ngay_su_co',
    'chi_phi' => 'ml.chi_phi',
    'created_at' => 'ml.created_at'
];
$sort_by = $_GET['sort_by'] ?? 'ngay_su_co';
$sort_order = $_GET['sort_order'] ?? 'DESC';

// Validate sort_by column
if (!array_key_exists($sort_by, $allowed_sort_columns)) {
    $sort_by = 'ngay_su_co'; // Default if invalid
}
// Validate sort_order
if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) {
    $sort_order = 'DESC'; // Default if invalid
}

$order_sql = " ORDER BY " . $allowed_sort_columns[$sort_by] . " " . $sort_order;

// --- Total Records Calculation ---
$total_rows_stmt = $pdo->prepare("SELECT COUNT(ml.id) FROM maintenance_logs ml LEFT JOIN devices d ON ml.device_id = d.id" . $where_sql);
$total_rows_stmt->execute($bind_params);
$total_rows = $total_rows_stmt->fetchColumn();
$total_pages = ceil($total_rows / $rows_per_page);

// Ensure current page is within valid range
if ($current_page < 1) $current_page = 1;
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;

$offset = ($current_page - 1) * $rows_per_page;
if ($offset < 0) $offset = 0; // Ensure offset is not negative

// --- Fetch Maintenance Logs for the current page ---
$sql = "
    SELECT
        ml.*,
        d.ma_tai_san,
        d.ten_thiet_bi
    FROM maintenance_logs ml
    LEFT JOIN devices d ON ml.device_id = d.id
    " . $where_sql .
    $order_sql . "
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
$final_bind_params = array_merge($bind_params, [
    ':limit' => $rows_per_page,
    ':offset' => $offset
]);
$stmt->execute($final_bind_params);
$logs = $stmt->fetchAll();

// --- Fetch devices for dropdown filter ---
$devices_filter_stmt = $pdo->query("SELECT id, ten_thiet_bi FROM devices ORDER BY ten_thiet_bi");
$devices_filter = $devices_filter_stmt->fetchAll();

?>

<h2>Lịch sử Bảo trì Thiết bị</h2>

<!-- Filter Section -->
<div class="filter-section">
    <form action="index.php" method="GET">
        <input type="hidden" name="page" value="maintenance/history">
        <select name="filter_device">
            <option value="">-- Lọc theo thiết bị --</option>
            <?php foreach ($devices_filter as $device): ?>
                <option value="<?php echo $device['id']; ?>" <?php echo ($filter_device == $device['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($device['ten_thiet_bi']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="filter_keyword" placeholder="Lọc theo tên, mã, mô tả..." value="<?php echo htmlspecialchars($filter_keyword); ?>">
        <button type="submit" class="btn btn-secondary">Lọc</button>
        <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php?page=maintenance/history'">Xóa bộ lọc</button>
    </form>
</div>

<a href="index.php?page=maintenance/add" class="add-button btn btn-primary">Thêm Nhật ký Bảo trì mới</a>

<div class="content-table-wrapper">
    <table class="content-table">
        <thead>
            <tr>
                <?php
                $columns = [
                    'ten_thiet_bi' => 'Thiết bị',
                    'ma_tai_san' => 'Mã Tài sản',
                    'ngay_su_co' => 'Ngày sự cố',
                    'noi_dung' => 'Mô tả sự cố', // This column is displayed, but we sort by 'ngay_su_co' or 'ten_thiet_bi' etc.
                    'hu_hong' => 'Hư hỏng',
                    'xu_ly' => 'Xử lý',
                    'chi_phi' => 'Chi phí (VNĐ)'
                ];

                // Manually define sortable columns and their corresponding database fields
                $sortable_columns = [
                    'ten_thiet_bi' => 'Thiết bị',
                    'ma_tai_san' => 'Mã Tài sản',
                    'ngay_su_co' => 'Ngày sự cố',
                    'chi_phi' => 'Chi phí (VNĐ)'
                ];

                foreach ($columns as $col_name => $col_label) {
                    $new_sort_order = 'ASC';
                    $sort_indicator = '';
                    $sort_link = '';

                    // Only create sort link if the column is sortable
                    if (array_key_exists($col_name, $sortable_columns)) {
                        if ($sort_by == $col_name) {
                            $new_sort_order = ($sort_order == 'ASC') ? 'DESC' : 'ASC';
                            $sort_indicator = ($sort_order == 'ASC') ? ' &#x25B2;' : ' &#x25BC;';
                        }
                        $current_query_params = $_GET;
                        $current_query_params['sort_by'] = $col_name;
                        $current_query_params['sort_order'] = $new_sort_order;
                        $sort_link = 'index.php?' . http_build_query($current_query_params);
                        echo '<th><a href="' . $sort_link . '">' . htmlspecialchars($col_label) . $sort_indicator . '</a></th>';
                    } else {
                        echo '<th>' . htmlspecialchars($col_label) . '</th>';
                    }
                }
                ?>
                <th>Thao tác</th>
            </tr>
        </thead>
    <tbody>
        <?php if (empty($logs)): ?>
            <tr>
                <td colspan="8" style="text-align: center;">Chưa có nhật ký bảo trì nào.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo htmlspecialchars($log['ten_thiet_bi']); ?></td>
                    <td><?php echo htmlspecialchars($log['ma_tai_san']); ?></td>
                    <td><?php echo htmlspecialchars($log['ngay_su_co']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($log['noi_dung'])); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($log['hu_hong'])); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($log['xu_ly'])); ?></td>
                    <td><?php echo htmlspecialchars(number_format($log['chi_phi'], 0, ',', '.')); ?></td>
                    <td class="actions">
                        <a href="index.php?page=maintenance/edit&id=<?php echo $log['id']; ?>" class="btn edit-btn">Sửa</a>
                        <a href="index.php?page=maintenance/delete&id=<?php echo $log['id']; ?>" class="btn delete-btn" onclick="return confirm('Bạn có chắc muốn xóa nhật ký này?');">Xóa</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
</div>

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
            <input type="hidden" name="page" value="maintenance/history">
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
                    <option value="10" <?php echo $rows_per_page == 10 ? 'selected' : ''; ?>>10</option>
                    <option value="25" <?php echo $rows_per_page == 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?php echo $rows_per_page == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $rows_per_page == 100 ? 'selected' : ''; ?>>100</option>
                </select>
                <span>dòng mỗi trang.</span>
            </span>
        </form>
    </div>
</div>