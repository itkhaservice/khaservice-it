<?php
// ==================================================
// PAGINATION & FILTER LOGIC
// ==================================================
$rows_per_page = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : 5;
$current_page  = (isset($_GET['p']) && is_numeric($_GET['p'])) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

$filter_keyword = trim($_GET['filter_keyword'] ?? '');
$where_sql = $filter_keyword !== '' ? " WHERE (ten_npp LIKE :kw OR nguoi_lien_he LIKE :kw OR email LIKE :kw OR dien_thoai LIKE :kw)" : "";
$bind_params = $filter_keyword !== '' ? [':kw' => '%' . $filter_keyword . '%'] : [];

// Count Total
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM suppliers $where_sql");
foreach ($bind_params as $key => $value) $count_stmt->bindValue($key, $value);
$count_stmt->execute();
$total_rows  = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $rows_per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $rows_per_page;

// Fetch Data
$stmt = $pdo->prepare("SELECT * FROM suppliers $where_sql ORDER BY ten_npp ASC LIMIT :limit OFFSET :offset");
foreach ($bind_params as $key => $value) $stmt->bindValue($key, $value);
$stmt->bindValue(':limit',  $rows_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Column Config
$all_columns = [
    'ten_npp'      => ['label' => 'Nhà phân phối', 'default' => true],
    'nguoi_lien_he'=> ['label' => 'Liên hệ', 'default' => true],
    'dien_thoai'   => ['label' => 'Điện thoại', 'default' => true],
    'email'        => ['label' => 'Email', 'default' => true],
    'ghi_chu'      => ['label' => 'Ghi chú', 'default' => false]
];
?>

<div class="page-header">
    <h2><i class="fas fa-truck"></i> Danh sách Nhà cung cấp</h2>
    <a href="index.php?page=suppliers/add" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm mới</a>
</div>

<!-- Filter Section -->
<div class="card filter-section">
    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="suppliers/list">
        <div class="filter-group">
            <label><i class="fas fa-search"></i> Tìm kiếm</label>
            <input type="text" name="filter_keyword" placeholder="Tên, người liên hệ, email..." value="<?php echo htmlspecialchars($filter_keyword); ?>">
        </div>
        <div class="filter-actions" style="margin-left: auto;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Lọc</button>
            <a href="index.php?page=suppliers/list" class="btn btn-secondary" title="Reset"><i class="fas fa-undo"></i></a>
            
            <div class="column-selector-container">
                <button type="button" class="btn btn-secondary" onclick="toggleColumnMenu()"><i class="fas fa-columns"></i> Cột</button>
                <div id="columnMenu" class="dropdown-menu">
                    <div class="dropdown-header">Hiển thị cột</div>
                    <div class="column-list">
                        <?php foreach ($all_columns as $colKey => $colConfig): ?>
                            <label class="column-item">
                                <input type="checkbox" class="col-checkbox" data-target="<?php echo $colKey; ?>" <?php echo $colConfig['default'] ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($colConfig['label']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Table -->
<div class="table-container card">
    <table class="content-table" id="suppliersTable">
        <thead>
            <tr>
                <?php foreach ($all_columns as $colKey => $colConfig): ?>
                    <th data-col="<?php echo $colKey; ?>"><?php echo htmlspecialchars($colConfig['label']); ?></th>
                <?php endforeach; ?>
                <th width="100" class="text-center">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($suppliers)): ?>
                <tr>
                    <td colspan="<?php echo count($all_columns) + 1; ?>" class="empty-state">
                        <i class="fas fa-boxes" style="font-size: 3rem; color: #e2e8f0; margin-bottom: 10px;"></i>
                        <p>Không tìm thấy nhà cung cấp nào.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($suppliers as $s): ?>
                    <tr>
                        <td data-col="ten_npp" class="font-bold text-primary"><?php echo htmlspecialchars($s['ten_npp']); ?></td>
                        <td data-col="nguoi_lien_he"><?php echo htmlspecialchars($s['nguoi_lien_he']); ?></td>
                        <td data-col="dien_thoai"><?php echo htmlspecialchars($s['dien_thoai']); ?></td>
                        <td data-col="email">
                            <?php if($s['email']): ?>
                                <a href="mailto:<?php echo htmlspecialchars($s['email']); ?>" class="link-primary"><?php echo htmlspecialchars($s['email']); ?></a>
                            <?php endif; ?>
                        </td>
                        <td data-col="ghi_chu" class="text-muted small"><?php echo htmlspecialchars($s['ghi_chu']); ?></td>
                        <td class="actions text-center">
                            <a href="index.php?page=suppliers/edit&id=<?php echo $s['id']; ?>" class="btn-icon" title="Sửa"><i class="fas fa-edit"></i></a>
                            <button type="button" class="btn-icon text-danger" onclick="openDeleteModal(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['ten_npp']); ?>')"><i class="fas fa-trash-alt"></i></button>
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
            <input type="hidden" name="page" value="suppliers/list">
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
        <?php $base_url = 'index.php?page=suppliers/list&limit=' . $rows_per_page . '&filter_keyword=' . urlencode($filter_keyword); ?>
        <a href="<?php echo $base_url . '&p=1'; ?>" class="page-link <?php echo $current_page <= 1 ? 'disabled' : ''; ?>"><i class="fas fa-angle-double-left"></i></a>
        <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
            <a href="<?php echo $base_url . '&p=' . $i; ?>" class="page-link <?php echo $i == $current_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <a href="<?php echo $base_url . '&p=' . $total_pages; ?>" class="page-link <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>"><i class="fas fa-angle-double-right"></i></a>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteSupplierModal" class="modal">
    <div class="modal-content delete-modal-content">
        <div class="delete-modal-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <h2 class="delete-modal-title">Xác nhận xóa?</h2>
        <p class="delete-modal-text">Bạn muốn xóa nhà cung cấp <strong id="modal-sup-name"></strong>?</p>
        <div class="delete-alert-box"><i class="fas fa-info-circle"></i> <span>Dữ liệu nhà cung cấp sẽ bị xóa vĩnh viễn. Cần đảm bảo không có thiết bị nào đang tham chiếu tới đây.</span></div>
        <form id="delete-sup-form" action="" method="POST" class="delete-modal-actions">
            <input type="hidden" name="confirm_delete" value="1">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Hủy</button>
            <button type="submit" class="btn btn-danger">Xác nhận Xóa</button>
        </form>
    </div>
</div>

<script>
function toggleColumnMenu() { document.getElementById('columnMenu').classList.toggle('show'); }
const checkboxes = document.querySelectorAll('.col-checkbox');
function updateColumns() {
    const visibleCols = {};
    checkboxes.forEach(cb => {
        const target = cb.getAttribute('data-target');
        const isVisible = cb.checked;
        visibleCols[target] = isVisible;
        document.querySelectorAll(`[data-col="${target}"]`).forEach(el => el.style.display = isVisible ? '' : 'none');
    });
    localStorage.setItem('supplierColumns', JSON.stringify(visibleCols));
}
document.addEventListener('DOMContentLoaded', function() {
    const savedCols = JSON.parse(localStorage.getItem('supplierColumns'));
    if (savedCols) checkboxes.forEach(cb => { if(savedCols.hasOwnProperty(cb.dataset.target)) cb.checked = savedCols[cb.dataset.target]; });
    updateColumns();
    checkboxes.forEach(cb => cb.addEventListener('change', updateColumns));
});
function openDeleteModal(id, name) {
    document.getElementById('modal-sup-name').textContent = name;
    document.getElementById('delete-sup-form').action = 'index.php?page=suppliers/delete&id=' + id;
    document.getElementById('deleteSupplierModal').classList.add('show');
}
function closeDeleteModal() { document.getElementById('deleteSupplierModal').classList.remove('show'); }
</script>
