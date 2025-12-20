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

<div class="page-header">
    <h2><i class="fas fa-building"></i> Danh sách Dự án</h2>
    <a href="index.php?page=projects/add" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm Dự án</a>
</div>

<!-- Filter Section -->
<div class="card filter-section">
    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="projects/list">
        
        <div class="filter-group">
            <label><i class="fas fa-layer-group"></i> Loại Dự án</label>
            <select name="filter_type">
                <option value="">-- Tất cả loại --</option>
                <?php foreach ($project_types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($filter_type == $type) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label><i class="fas fa-search"></i> Tìm kiếm</label>
            <input type="text" name="filter_keyword" placeholder="Mã, tên dự án, địa chỉ..." value="<?php echo htmlspecialchars($filter_keyword); ?>">
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Lọc</button>
            <a href="index.php?page=projects/list" class="btn btn-secondary" title="Xóa bộ lọc"><i class="fas fa-undo"></i></a>
        </div>
    </form>
</div>

<div class="table-container card">
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
                }
                ?>
                <th width="120" class="text-center">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($projects)): ?>
                <tr>
                    <td colspan="6" class="empty-state">
                        <i class="fas fa-city" style="font-size: 3rem; color: #e2e8f0; margin-bottom: 10px;"></i>
                        <p>Chưa có dự án nào trong hệ thống.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($projects as $project): ?>
                    <tr>
                        <td class="font-medium text-primary"><?php echo htmlspecialchars($project['ma_du_an']); ?></td>
                        <td class="font-bold"><?php echo htmlspecialchars($project['ten_du_an']); ?></td>
                        <td><?php echo htmlspecialchars($project['dia_chi']); ?></td>
                        <td><span class="badge status-info"><?php echo htmlspecialchars($project['loai_du_an']); ?></span></td>
                        <td class="text-muted small"><?php echo htmlspecialchars($project['ghi_chu']); ?></td>
                        <td class="actions text-center">
                            <a href="index.php?page=projects/view&id=<?php echo $project['id']; ?>" class="btn-icon" title="Xem chi tiết"><i class="fas fa-eye"></i></a>
                            <a href="index.php?page=projects/edit&id=<?php echo $project['id']; ?>" class="btn-icon" title="Sửa"><i class="fas fa-edit"></i></a>
                            <a href="index.php?page=projects/delete&id=<?php echo $project['id']; ?>" class="btn-icon delete-btn" title="Xóa"><i class="fas fa-trash-alt"></i></a>
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
            <input type="hidden" name="page" value="projects/list">
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