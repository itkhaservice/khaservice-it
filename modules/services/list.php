<?php
$rows_per_page = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : 10;
$current_page  = (isset($_GET['p']) && is_numeric($_GET['p'])) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

$filter_keyword = trim($_GET['filter_keyword'] ?? '');
$filter_project = trim($_GET['filter_project'] ?? '');

$where_clauses = ["s.deleted_at IS NULL"];
$bind_params   = [];

if ($filter_keyword !== '') {
    $where_clauses[] = "(s.ten_dich_vu LIKE :kw OR s.loai_dich_vu LIKE :kw OR p.ten_du_an LIKE :kw)";
    $bind_params[':kw'] = '%' . $filter_keyword . '%';
}

if ($filter_project !== '' && is_numeric($filter_project)) {
    $where_clauses[] = "s.project_id = :project_id";
    $bind_params[':project_id'] = (int)$filter_project;
}

$where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM services s LEFT JOIN projects p ON s.project_id = p.id $where_sql");
foreach ($bind_params as $k => $v) $count_stmt->bindValue($k, $v);
$count_stmt->execute();
$total_rows  = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $rows_per_page));
$offset = ($current_page - 1) * $rows_per_page;

$data_sql = "
    SELECT s.*, p.ten_du_an, sup.ten_npp 
    FROM services s 
    LEFT JOIN projects p ON s.project_id = p.id 
    LEFT JOIN suppliers sup ON s.supplier_id = sup.id
    $where_sql 
    ORDER BY s.ngay_het_han ASC 
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($data_sql);
foreach ($bind_params as $k => $v) $bind_params_list[$k] = $v; // Temporary store
foreach ($bind_params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $rows_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

$projects_list = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h2><i class="fas fa-cloud"></i> Quản lý Dịch vụ & Gia hạn</h2>
    <?php if(isIT()): ?><a href="index.php?page=services/add" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm dịch vụ</a><?php endif; ?>
</div>

<div class="card filter-section">
    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="services/list">
        <div class="filter-group">
            <label>Dự án</label>
            <div class="searchable-select-container">
                <input type="text" id="project_search" class="search-input" placeholder="Tất cả dự án..." value="<?php 
                    if ($filter_project) {
                        foreach($projects_list as $p) {
                            if($p['id'] == $filter_project) {
                                echo htmlspecialchars($p['ten_du_an']);
                                break;
                            }
                        }
                    }
                ?>" autocomplete="off">
                <input type="hidden" name="filter_project" id="filter_project" value="<?php echo htmlspecialchars($filter_project); ?>">
                <div id="project_dropdown" class="searchable-dropdown"></div>
            </div>
        </div>
        <div class="filter-group">
            <label>Tìm kiếm</label>
            <input type="text" name="filter_keyword" placeholder="Tên dịch vụ, nhà cung cấp..." value="<?php echo htmlspecialchars($filter_keyword); ?>">
        </div>
        <div class="filter-actions" style="margin-left: auto;">
            <button type="submit" class="btn btn-primary">Lọc</button>
            <a href="index.php?page=services/list" class="btn btn-secondary"><i class="fas fa-undo"></i></a>
        </div>
    </form>
</div>

<form action="index.php?page=services/export" method="POST" id="services-form">
    <div class="batch-actions" id="batch-actions" style="display: none;">
        <span class="selected-count-label">Đã chọn <strong id="selected-count">0</strong> mục</span>
        <div class="action-buttons">
            <button type="button" class="btn btn-secondary btn-sm" id="clear-selection-btn"><i class="fas fa-times"></i> Bỏ chọn</button>
            <?php if(isIT()): ?>
                <button type="button" class="btn btn-danger btn-sm" id="delete-selected-btn" data-action="index.php?page=services/delete_multiple">Xóa đã chọn</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-container card">
        <table class="content-table" id="servicesTable">
            <thead>
                <tr>
                    <th width="40"><input type="checkbox" id="select-all"></th>
                    <th>Tên Dịch vụ</th>
                    <th>Dự án</th>
                    <th>Ngày hết hạn</th>
                    <th>Còn lại</th>
                    <th>Đề nghị TT</th>
                    <th>Trạng thái</th>
                    <th width="100" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($services)): ?>
                    <tr><td colspan="8" class="empty-state">Chưa có dịch vụ nào</td></tr>
                <?php else: ?>
                    <?php foreach ($services as $s): 
                        $today = new DateTime();
                        $expiry = new DateTime($s['ngay_het_han']);
                        $diff = $today->diff($expiry);
                        $days_left = (int)$diff->format("%r%a");
                        
                        $status_class = "text-success";
                        if ($days_left <= 0) $status_class = "text-danger font-bold";
                        elseif ($days_left <= 30) $status_class = "text-warning font-bold";
                    ?>
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="<?php echo $s['id']; ?>" class="row-checkbox"></td>
                            <td>
                                <div class="font-bold"><?php echo htmlspecialchars($s['ten_dich_vu']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($s['loai_dich_vu']); ?> - <?php echo htmlspecialchars($s['ten_npp'] ?? 'N/A'); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($s['ten_du_an'] ?: "Dùng chung"); ?></td>
                            <td class="<?php echo $status_class; ?>"><?php echo date('d/m/Y', strtotime($s['ngay_het_han'])); ?></td>
                            <td class="<?php echo $status_class; ?>">
                                <?php 
                                    if($days_left < 0) echo "Quá hạn " . abs($days_left) . " ngày";
                                    elseif($days_left == 0) echo "Hết hạn hôm nay";
                                    else echo $days_left . " ngày";
                                ?>
                            </td>
                            <td>
                                <?php if($s['ngay_nhan_de_nghi']): ?>
                                    <span class="text-info"><i class="fas fa-envelope-open-text"></i> <?php echo date('d/m/Y', strtotime($s['ngay_nhan_de_nghi'])); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Chưa nhận</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    $trang_thai = $s['trang_thai'];
                                    $badge_class = "status-info";
                                    if($trang_thai === 'Chờ thanh toán') $badge_class = "status-warning";
                                    if($trang_thai === 'Đang hoạt động') $badge_class = "status-active";
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($trang_thai); ?></span>
                            </td>
                            <td class="actions text-center">
                                <a href="index.php?page=services/view&id=<?php echo $s['id']; ?>" class="btn-icon" title="Chi tiết"><i class="fas fa-eye"></i></a>
                                <?php if(isIT()): ?>
                                    <a href="index.php?page=services/edit&id=<?php echo $s['id']; ?>" class="btn-icon" title="Sửa"><i class="fas fa-edit"></i></a>
                                    <a href="index.php?page=services/delete&id=<?php echo $s['id']; ?>" data-url="index.php?page=services/delete&id=<?php echo $s['id']; ?>&confirm_delete=1" class="btn-icon delete-btn" title="Xóa"><i class="fas fa-trash-alt"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</form>

<div class="pagination-container">
    <!-- Keep existing pagination code -->
</div>

<script>
let localProjects = <?php echo json_encode($projects_list); ?>;
let activeIndex = -1;

document.addEventListener('DOMContentLoaded', () => {
    // Searchable Select Logic
    const projectSearch = document.getElementById('project_search');
    const projectDropdown = document.getElementById('project_dropdown');
    const projectIdInput = document.getElementById('filter_project');

    if (projectSearch) {
        projectSearch.addEventListener('input', function() {
            renderProjectDropdown(this.value.toLowerCase().trim());
        });
        projectSearch.addEventListener('focus', function() {
            renderProjectDropdown(this.value.toLowerCase().trim());
        });
        projectSearch.addEventListener('keydown', function(e) {
            const items = projectDropdown.querySelectorAll('.dropdown-item');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = Math.min(activeIndex + 1, items.length - 1);
                updateActiveItem(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = Math.max(activeIndex - 1, -1);
                updateActiveItem(items);
            } else if (e.key === 'Enter') {
                if (activeIndex > -1 && items[activeIndex]) {
                    e.preventDefault();
                    items[activeIndex].click();
                }
            }
        });
    }

    document.addEventListener('click', function(e) {
        if (projectSearch && !projectSearch.contains(e.target) && !projectDropdown.contains(e.target)) {
            projectDropdown.style.display = 'none';
        }
    });

    const selectAll = document.getElementById('select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const batchActions = document.getElementById('batch-actions');
    const selectedCountDisplay = document.getElementById('selected-count');
    const clearBtn = document.getElementById('clear-selection-btn');

    function updateBatchUI() {
        const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
        const totalCount = rowCheckboxes.length;
        
        if (batchActions) {
            batchActions.style.display = (checkedCount > 0) ? 'flex' : 'none';
        }
        
        if (selectedCountDisplay) {
            selectedCountDisplay.textContent = checkedCount;
        }

        if (selectAll) {
            selectAll.checked = (totalCount > 0 && checkedCount === totalCount);
            selectAll.indeterminate = (checkedCount > 0 && checkedCount < totalCount);
        }
    }

    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBatchUI);
    });

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const isChecked = this.checked;
            rowCheckboxes.forEach(cb => cb.checked = isChecked);
            updateBatchUI();
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            if (selectAll) selectAll.checked = false;
            rowCheckboxes.forEach(cb => cb.checked = false);
            updateBatchUI();
        });
    }
});

function renderProjectDropdown(filter = '') {
    const dropdown = document.getElementById('project_dropdown');
    const filtered = localProjects.filter(p => p.ten_du_an.toLowerCase().includes(filter));

    let html = '<div class="dropdown-item" onclick="selectProject(\'\', \'\')">-- Tất cả dự án --</div>';
    if (filtered.length === 0) {
        html += '<div class="no-results">Không tìm thấy dự án</div>';
    } else {
        html += filtered.map(p => `
            <div class="dropdown-item" onclick="selectProject(${p.id}, '${p.ten_du_an.replace(/'/g, "\\'")}')">
                <span class="item-title">${p.ten_du_an}</span>
            </div>
        `).join('');
    }
    dropdown.innerHTML = html;
    dropdown.style.display = 'block';
    activeIndex = -1;
}

function selectProject(id, name) {
    document.getElementById('project_search').value = name;
    document.getElementById('filter_project').value = id;
    document.getElementById('project_dropdown').style.display = 'none';
}

function updateActiveItem(items) {
    items.forEach((item, index) => {
        item.classList.toggle('active', index === activeIndex);
        if (index === activeIndex) item.scrollIntoView({ block: 'nearest' });
    });
}
</script>

<style>
/* Searchable Select */
.searchable-select-container { position: relative; width: 100%; min-width: 200px; }
.search-input { width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; }
.searchable-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #cbd5e1; border-radius: 6px; margin-top: 5px; max-height: 200px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); display: none; }
.dropdown-item { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; text-align: left; }
.dropdown-item:hover, .dropdown-item.active { background: #f8fafc; color: var(--primary-color); }
.no-results { padding: 10px; text-align: center; color: #94a3b8; font-size: 0.85rem; }

/* Responsive Filter */
@media (max-width: 768px) {
    .filter-form { flex-direction: column; align-items: stretch; gap: 15px; }
    .filter-group { width: 100%; }
    .searchable-select-container { min-width: 100%; }
    .filter-actions { margin-left: 0 !important; width: 100%; display: flex; gap: 10px; }
    .filter-actions .btn { flex: 1; justify-content: center; }
}
</style>