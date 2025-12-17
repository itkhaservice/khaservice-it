<?php
// --- Pagination Logic ---
$rows_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$current_page = isset($_GET['p']) ? (int)$_GET['p'] : 1;

// --- Filtering Logic ---
$filter_keyword = $_GET['filter_keyword'] ?? '';
$filter_type = $_GET['filter_type'] ?? '';

$where_clauses = [];
$bind_params = [];

if (!empty($filter_keyword)) {
    $where_clauses[] = "(ma_du_an LIKE :keyword OR ten_du_an LIKE :keyword OR dia_chi LIKE :keyword OR loai_du_an LIKE :keyword)";
    $bind_params[':keyword'] = '%' . $filter_keyword . '%';
}
if (!empty($filter_type)) {
    $where_clauses[] = "loai_du_an = :loai_du_an";
    $bind_params[':loai_du_an'] = $filter_type;
}

$where_sql = count($where_clauses) > 0 ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

// --- Sorting Logic ---
$allowed_sort_columns = [
    'ma_du_an' => 'ma_du_an',
    'ten_du_an' => 'ten_du_an',
    'dia_chi' => 'dia_chi',
    'loai_du_an' => 'loai_du_an',
    'ghi_chu' => 'ghi_chu'
];
$sort_by = $_GET['sort_by'] ?? 'ten_du_an';
$sort_order = $_GET['sort_order'] ?? 'ASC';

// Validate sort_by column
if (!array_key_exists($sort_by, $allowed_sort_columns)) {
    $sort_by = 'ten_du_an'; // Default if invalid
}
// Validate sort_order
if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) {
    $sort_order = 'ASC'; // Default if invalid
}

$order_sql = " ORDER BY " . $allowed_sort_columns[$sort_by] . " " . $sort_order;

// --- Total Records Calculation ---
$total_rows_stmt = $pdo->prepare("SELECT COUNT(*) FROM projects" . $where_sql);
$total_rows_stmt->execute($bind_params);
$total_rows = $total_rows_stmt->fetchColumn();
$total_pages = ceil($total_rows / $rows_per_page);

// Ensure current page is within valid range
if ($current_page < 1) $current_page = 1;
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;

$offset = ($current_page - 1) * $rows_per_page;
if ($offset < 0) $offset = 0; // Ensure offset is not negative

// --- Fetch Projects for the current page ---
$sql = "
    SELECT
        id,
        ma_du_an,
        ten_du_an,
        dia_chi,
        loai_du_an,
        ghi_chu
    FROM projects
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
$projects = $stmt->fetchAll();

// --- Fetch distinct project types for dropdown filter ---
$project_types_stmt = $pdo->query("SELECT DISTINCT loai_du_an FROM projects WHERE loai_du_an IS NOT NULL AND loai_du_an != '' ORDER BY loai_du_an");
$project_types = $project_types_stmt->fetchAll(PDO::FETCH_COLUMN);

?>

<h2>Danh sách Dự án</h2>

<!-- Filter Section -->
<div class="filter-section">
    <form action="index.php" method="GET">
        <input type="hidden" name="page" value="projects/list">
        <select name="filter_type">
            <option value="">-- Lọc theo loại dự án --</option>
            <?php foreach ($project_types as $type): ?>
                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($filter_type == $type) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($type); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="filter_keyword" placeholder="Lọc theo mã, tên, địa chỉ..." value="<?php echo htmlspecialchars($filter_keyword); ?>">
        <button type="submit" class="btn btn-secondary">Lọc</button>
        <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php?page=projects/list'">Xóa bộ lọc</button>
    </form>
</div>

<a href="index.php?page=projects/add" class="add-button btn btn-primary">Thêm dự án mới</a>

<div class="content-table-wrapper">
    <table class="content-table">
        <thead>
            <tr>
                <?php
                $columns = [
                    'ma_du_an' => 'Mã Dự án',
                    'ten_du_an' => 'Tên Dự án',
                    'dia_chi' => 'Địa chỉ',
                    'loai_du_an' => 'Loại Dự án',
                    'ghi_chu' => 'Ghi chú'
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
        <?php if (empty($projects)): ?>
            <tr>
                <td colspan="6" style="text-align: center;">Chưa có dự án nào.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($projects as $project): ?>
                <tr>
                    <td><?php echo htmlspecialchars($project['ma_du_an']); ?></td>
                    <td><?php echo htmlspecialchars($project['ten_du_an']); ?></td>
                    <td><?php echo htmlspecialchars($project['dia_chi']); ?></td>
                    <td><?php echo htmlspecialchars($project['loai_du_an']); ?></td>
                    <td><?php echo htmlspecialchars($project['ghi_chu']); ?></td>
                    <td class="actions">
                        <a href="index.php?page=projects/edit&id=<?php echo $project['id']; ?>" class="btn edit-btn">Sửa</a>
                        <a href="index.php?page=projects/delete&id=<?php echo $project['id']; ?>" class="btn delete-btn" onclick="return confirm('Bạn có chắc muốn xóa dự án này?');">Xóa</a>
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
            <input type="hidden" name="page" value="projects/list">
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