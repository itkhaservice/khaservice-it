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

<div class="page-header">
    <h2><i class="fas fa-tools"></i> Lịch sử Bảo trì</h2>
    <a href="index.php?page=maintenance/add" class="btn btn-primary"><i class="fas fa-plus"></i> Tạo Phiếu Bảo trì</a>
</div>

<!-- Filter Section -->
<div class="card filter-section">
    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="maintenance/history">
        
        <div class="filter-group">
            <label><i class="fas fa-server"></i> Thiết bị</label>
            <select name="filter_device">
                <option value="">-- Tất cả thiết bị --</option>
                <?php foreach ($devices_filter as $device): ?>
                    <option value="<?php echo $device['id']; ?>" <?php echo ($filter_device == $device['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($device['ten_thiet_bi']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label><i class="fas fa-search"></i> Tìm kiếm</label>
            <input type="text" name="filter_keyword" placeholder="Mô tả, mã TS, hư hỏng..." value="<?php echo htmlspecialchars($filter_keyword); ?>">
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Lọc</button>
            <a href="index.php?page=maintenance/history" class="btn btn-secondary" title="Xóa bộ lọc"><i class="fas fa-undo"></i></a>
        </div>
    </form>
</div>

<div class="table-container card">
    <table class="content-table">
        <thead>
            <tr>
                <?php
                $columns = [
                    'ten_thiet_bi' => 'Thiết bị',
                    'ma_tai_san' => 'Mã Tài sản',
                    'ngay_su_co' => 'Ngày sự cố',
                    'noi_dung' => 'Mô tả sự cố',
                    'hu_hong' => 'Hư hỏng',
                    'xu_ly' => 'Xử lý',
                    'chi_phi' => 'Chi phí (VNĐ)'
                ];

                $sortable_columns = [
                    'ten_thiet_bi' => 'Thiết bị',
                    'ma_tai_san' => 'Mã Tài sản',
                    'ngay_su_co' => 'Ngày sự cố',
                    'chi_phi' => 'Chi phí (VNĐ)'
                ];

                foreach ($columns as $col_name => $col_label) {
                    $new_sort_order = 'ASC';
                    $sort_icon = '';
                    $sort_link = '';

                    if (array_key_exists($col_name, $sortable_columns)) {
                        $sort_icon = '<i class="fas fa-sort" style="color: #ccc;"></i>';
                        if ($sort_by == $col_name) {
                            $new_sort_order = ($sort_order == 'ASC') ? 'DESC' : 'ASC';
                            $sort_icon = ($sort_order == 'ASC') ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
                        }
                        $current_query_params = $_GET;
                        $current_query_params['sort_by'] = $col_name;
                        $current_query_params['sort_order'] = $new_sort_order;
                        $sort_link = 'index.php?' . http_build_query($current_query_params);
                        echo '<th><a href="' . $sort_link . '" class="sort-link">' . htmlspecialchars($col_label) . ' ' . $sort_icon . '</a></th>';
                    } else {
                        echo '<th>' . htmlspecialchars($col_label) . '</th>';
                    }
                }
                ?>
                <th width="100" class="text-center">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="8" class="empty-state">
                        <i class="fas fa-clipboard-list" style="font-size: 3rem; color: #e2e8f0; margin-bottom: 10px;"></i>
                        <p>Chưa có dữ liệu bảo trì nào.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="font-medium text-primary"><?php echo htmlspecialchars($log['ten_thiet_bi']); ?></td>
                        <td><?php echo htmlspecialchars($log['ma_tai_san']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($log['ngay_su_co'])); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($log['noi_dung'])); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($log['hu_hong'])); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($log['xu_ly'])); ?></td>
                        <td class="text-right font-bold text-warning"><?php echo number_format($log['chi_phi'], 0, ',', '.'); ?></td>
                        <td class="actions text-center">
                            <a href="index.php?page=maintenance/view&id=<?php echo $log['id']; ?>" class="btn-icon" title="Xem chi tiết"><i class="fas fa-eye"></i></a>
                            <a href="index.php?page=maintenance/edit&id=<?php echo $log['id']; ?>" class="btn-icon" title="Sửa"><i class="fas fa-edit"></i></a>
                            <a href="index.php?page=maintenance/delete&id=<?php echo $log['id']; ?>" class="btn-icon delete-btn" title="Xóa"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination Section -->
<div class="pagination-container">
    <div class="rows-per-page">
        <form action="index.php" method="GET" class="rows-per-page-form">
            <input type="hidden" name="page" value="maintenance/history">
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