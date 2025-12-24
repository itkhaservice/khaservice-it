<?php
$pageTitle = "Hệ thống xe - Danh sách cấu hình";

// Check permissions
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'it')) {
    set_message("Bạn không có quyền truy cập trang này!", "error");
    echo '<script>window.location.href = "index.php";</script>';
    exit;
}

// ==================================================
// PAGINATION CONFIG
// ==================================================
$rows_per_page = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : 10;
$current_page  = (isset($_GET['p']) && is_numeric($_GET['p'])) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

// ==================================================
// FILTER INPUT
// ==================================================
$search = trim($_GET['search'] ?? '');
$filter_project = trim($_GET['filter_project'] ?? '');

$params = [];
$where_clauses = ["1=1"];

if ($search) {
    $where_clauses[] = "(c.server_ip LIKE ? OR c.db_name LIKE ? OR c.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_project !== '' && is_numeric($filter_project)) {
    $where_clauses[] = "c.project_id = ?";
    $params[] = (int)$filter_project;
}

$where_sql = implode(" AND ", $where_clauses);

// ==================================================
// FETCH DATA
// ==================================================
// 1. Fetch Projects for filter dropdown
$projects_list = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an ASC")->fetchAll(PDO::FETCH_ASSOC);

// 2. Count Total Rows
$count_sql = "SELECT COUNT(*) FROM car_system_configs c WHERE $where_sql";
try {
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_rows = (int)$count_stmt->fetchColumn();
} catch (PDOException $e) { $total_rows = 0; }

$total_pages = max(1, ceil($total_rows / $rows_per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $rows_per_page;

// 3. Fetch Data with Join
$sql = "SELECT c.*, p.ten_du_an 
        FROM car_system_configs c
        JOIN projects p ON c.project_id = p.id
        WHERE $where_sql
        ORDER BY p.ten_du_an ASC
        LIMIT $rows_per_page OFFSET $offset";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $configs = [];
}

// Column Config
$all_columns = [
    'ten_du_an'   => ['label' => 'Dự án', 'default' => true],
    'server_ip'   => ['label' => 'Máy chủ', 'default' => true],
    'db_name'     => ['label' => 'Database', 'default' => true],
    'folder_path' => ['label' => 'Thư mục', 'default' => true],
    'username'    => ['label' => 'Tài khoản', 'default' => true],
    'password'    => ['label' => 'Mật khẩu', 'default' => true],
];
?>

<div class="page-header">
    <h2><i class="fas fa-car-battery"></i> Cấu hình Hệ thống Xe</h2>
    <a href="index.php?page=car_systems/add" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm mới</a>
</div>

<!-- Modern Filter Section -->
<div class="card filter-section-modern">
    <form action="index.php" method="GET" class="filter-form-modern">
        <input type="hidden" name="page" value="car_systems/list">
        <input type="hidden" name="limit" value="<?= $rows_per_page ?>">
        
        <div class="filter-main-grid">
            <!-- Searchable Select for Project -->
            <div class="filter-item">
                <label>Dự án</label>
                <div class="searchable-select-container">
                    <input type="text" id="project_search" class="form-control-sm" placeholder="Tất cả dự án..." value="<?php 
                        if ($filter_project) {
                            foreach($projects_list as $p) {
                                if($p['id'] == $filter_project) { echo htmlspecialchars($p['ten_du_an']); break; }
                            }
                        }
                    ?>" autocomplete="off">
                    <button type="button" class="btn-clear-inline" id="btn-clear-project" style="<?php echo $filter_project ? 'display:block' : 'display:none'; ?>"><i class="fas fa-times"></i></button>
                    <input type="hidden" name="filter_project" id="filter_project" value="<?php echo htmlspecialchars($filter_project); ?>">
                    <div id="project_dropdown" class="searchable-dropdown"></div>
                </div>
            </div>

            <div class="filter-item" style="grid-column: span 2;">
                <label>Từ khóa</label>
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" placeholder="Máy chủ, Database, Tài khoản..." value="<?= htmlspecialchars($search) ?>" class="form-control-sm">
                </div>
            </div>
            <div class="filter-item" style="justify-content: flex-end; flex-direction: row; gap: 8px;">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Lọc</button>
                <a href="index.php?page=car_systems/list" class="btn btn-secondary btn-sm" title="Xóa lọc"><i class="fas fa-undo"></i></a>
                <div class="column-selector-container">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleColumnMenu()"><i class="fas fa-columns"></i> Cột</button>
                    <div id="columnMenu" class="dropdown-menu">
                        <div class="dropdown-header">Hiển thị cột</div>
                        <div class="column-list">
                            <?php foreach ($all_columns as $k => $c): ?>
                                <label class="column-item">
                                    <input type="checkbox" class="col-checkbox" data-target="<?= $k; ?>" <?= $c['default'] ? 'checked' : ''; ?>> 
                                    <?= htmlspecialchars($c['label']); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<form action="index.php?page=car_systems/export" method="POST" id="car-systems-form">
    <!-- Batch Actions -->
    <div class="batch-actions" id="batch-actions" style="display: none;">
        <span class="selected-count-label">Đã chọn <strong id="selected-count">0</strong> mục</span>
        <div class="action-buttons">
            <button type="button" class="btn btn-secondary btn-sm" id="clear-selection-btn"><i class="fas fa-times"></i> Bỏ chọn</button>
            <input type="hidden" name="visible_columns" id="visible_columns_input">
            <button type="submit" name="export_selected" class="btn btn-secondary btn-sm" onclick="prepareExport()"><i class="fas fa-file-export"></i> Xuất Excel</button>
            <?php if(isAdmin()): ?>
                <button type="button" class="btn btn-danger btn-sm" id="delete-selected-btn" data-action="index.php?page=car_systems/delete_multiple"><i class="fas fa-trash-alt"></i> Xóa đã chọn</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Table Container -->
    <div class="card table-container">
        <table class="content-table" id="config-table">
            <thead>
                <tr>
                    <th width="40"><input type="checkbox" id="select-all"></th>
                    <?php foreach ($all_columns as $k => $c): ?>
                        <th data-col="<?= $k; ?>"><?= htmlspecialchars($c['label']); ?></th>
                    <?php endforeach; ?>
                    <th width="80" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($configs)): ?>
                    <tr><td colspan="<?= count($all_columns) + 2 ?>" class="text-center py-4 text-muted">Không tìm thấy dữ liệu.</td></tr>
                <?php else: ?>
                    <?php foreach ($configs as $config):
                        // Ensure password is not directly outputted if it's sensitive and not intended to be visible by default
                        $password_display = isset($config['password']) ? htmlspecialchars($config['password']) : '';
                    ?>
                        <tr>
                            <td><input type="checkbox" name="selected_items[]" value="<?= $config['id']; ?>" class="row-checkbox"></td>
                            <td data-col="ten_du_an" class="font-medium text-primary"><?= htmlspecialchars($config['ten_du_an']) ?></td>
                            <td data-col="server_ip">
                                <span class="badge bg-label-info"><i class="fas fa-server me-1"></i> <?= htmlspecialchars($config['server_ip']) ?></span>
                            </td>
                            <td data-col="db_name" class="text-secondary fw-medium">
                                <?= htmlspecialchars($config['db_name']) ?>
                            </td>
                            <td data-col="folder_path">
                                <div class="folder-path" title="<?= htmlspecialchars($config['folder_path']) ?>">
                                    <i class="far fa-folder text-warning me-1"></i> <?= htmlspecialchars($config['folder_path']) ?>
                                </div>
                            </td>
                            <td data-col="username">
                                <div class="user-badge">
                                    <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($config['username']) ?>
                                </div>
                            </td>
                            <td data-col="password">
                                <div class="password-wrapper">
                                    <input type="password" value="<?= $password_display ?>" readonly class="password-input">
                                    <button type="button" class="btn-toggle-pass toggle-password" title="Hiện/Ẩn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="actions text-center">
                                <a href="index.php?page=car_systems/edit&id=<?= $config['id'] ?>" class="btn-icon"><i class="fas fa-edit"></i></a>
                                <a href="#" class="btn-icon delete-btn" data-url="index.php?page=car_systems/delete&id=<?= $config['id'] ?>"><i class="fas fa-trash-alt"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</form>

<!-- Pagination Control -->
<div class="pagination-container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-top: 20px;">
    <div class="rows-per-page">
        <form action="index.php" method="GET" class="rows-per-page-form" style="display: flex; align-items: center; gap: 8px;">
            <input type="hidden" name="page" value="car_systems/list">
            <?php if($search): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
            <?php if($filter_project): ?><input type="hidden" name="filter_project" id="hidden_filter_project" value="<?= htmlspecialchars($filter_project) ?>"><?php endif; ?>
            
            <label style="font-size: 0.85rem; color: #64748b;">Hiển thị</label>
            <select name="limit" onchange="this.form.submit()" class="form-select-sm" style="width: auto;">
                <?php foreach([5,10,25,50,100] as $lim):
                    $selected = ($rows_per_page == $lim) ? 'selected' : '';
                ?>
                    <option value="<?= $lim ?>" <?= $selected ?>><?= $lim ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="pagination-links" style="display: flex; gap: 5px; margin-left: auto;">
        <?php 
            $q = $_GET; unset($q['p']); 
            $base = 'index.php?' . http_build_query($q); 
        ?>
        <a href="<?= $base . '&p=1'; ?>" class="page-link <?= $current_page <= 1 ? 'disabled' : ''; ?>"><i class="fas fa-angle-double-left"></i></a>
        <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++):
            $active = ($i == $current_page) ? 'active' : '';
        ?>
            <a href="<?= $base . '&p=' . $i; ?>" class="page-link <?= $active ?>"><?= $i; ?></a>
        <?php endfor; ?>
        <a href="<?= $base . '&p=' . $total_pages; ?>" class="page-link <?= $current_page >= $total_pages ? 'disabled' : ''; ?>"><i class="fas fa-angle-double-right"></i></a>
    </div>
</div>

<script>
    // PROJECT DATA FROM PHP
    let localProjects = <?= json_encode($projects_list) ?>;

    // Searchable Select Logic
    function renderProjectDropdown(filter = '') {
        const dropdown = document.getElementById('project_dropdown');
        const filtered = localProjects.filter(p => p.ten_du_an.toLowerCase().includes(filter));
        let html = '<div class="dropdown-item" onclick="selectProject(\'\', \'\')">-- Tất cả dự án --</div>';
        
        if (filtered.length > 0) {
            html += filtered.map(p => 
                `<div class="dropdown-item" onclick="selectProject(${p.id}, '${p.ten_du_an.replace(/'/g, "\'")}')">
                    <span class="item-title">${p.ten_du_an}</span>
                </div>`
            ).join('');
        } else {
            html += '<div class="dropdown-item text-muted">Không tìm thấy</div>';
        }
        
        dropdown.innerHTML = html;
        dropdown.style.display = 'block';
    }

    function selectProject(id, name) {
        document.getElementById('project_search').value = name;
        document.getElementById('filter_project').value = id;
        
        // Update hidden input in pagination form as well
        const hiddenPageFilter = document.getElementById('hidden_filter_project');
        if(hiddenPageFilter) hiddenPageFilter.value = id;

        document.getElementById('project_dropdown').style.display = 'none';
        document.getElementById('btn-clear-project').style.display = name ? 'block' : 'none';
    }

    // Toggle Column Menu
    function toggleColumnMenu() { document.getElementById('columnMenu').classList.toggle('show'); }

    // Export Logic
    function prepareExport() {
        const activeColumns = [];
        document.querySelectorAll('.col-checkbox:checked').forEach(cb => {
            activeColumns.push({ key: cb.dataset.target, label: cb.parentElement.textContent.trim() });
        });
        document.getElementById('visible_columns_input').value = JSON.stringify(activeColumns);
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Project Search Events
        const ps = document.getElementById('project_search');
        const pd = document.getElementById('project_dropdown');
        const pi = document.getElementById('filter_project');
        const cl = document.getElementById('btn-clear-project');
        const hiddenPageFilter = document.getElementById('hidden_filter_project');

        if (ps) {
            ps.addEventListener('input', function() { 
                renderProjectDropdown(this.value.toLowerCase().trim()); 
                cl.style.display = this.value.length > 0 ? 'block' : 'none'; 
            });
            ps.addEventListener('focus', function() { 
                renderProjectDropdown(this.value.toLowerCase().trim()); 
            });
        }
        
        if (cl) cl.addEventListener('click', () => { 
            ps.value = ''; 
            pi.value = ''; 
            if(hiddenPageFilter) hiddenPageFilter.value = '';
            cl.style.display = 'none'; 
            pd.style.display = 'none'; 
        });

        // Toggle Password
        document.querySelectorAll('.toggle-password').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                    input.classList.add('visible');
                } else {
                    input.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                    input.classList.remove('visible');
                }
            });
        });

        // Column Visibility Logic
        const colCbs = document.querySelectorAll('.col-checkbox');
        function updateCols() {
            const state = {};
            colCbs.forEach(cb => {
                const target = cb.dataset.target; state[target] = cb.checked;
                document.querySelectorAll(`[data-col="${target}"]`).forEach(el => el.style.display = cb.checked ? '' : 'none');
            });
            localStorage.setItem('carSystemColumns', JSON.stringify(state));
        }
        colCbs.forEach(cb => cb.addEventListener('change', updateCols));
        const savedCols = JSON.parse(localStorage.getItem('carSystemColumns'));
        if(savedCols) {
            colCbs.forEach(cb => { const t = cb.dataset.target; if(savedCols.hasOwnProperty(t)) cb.checked = savedCols[t]; });
            updateCols();
        }

        // Batch Selection Logic
        const selectAll = document.getElementById('select-all');
        const rowCbs = document.querySelectorAll('.row-checkbox');
        const batchBar = document.getElementById('batch-actions');
        const countLabel = document.getElementById('selected-count');
        const clearBtn = document.getElementById('clear-selection-btn');
        function updateBatchUI() {
            const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
            batchBar.style.display = checkedCount > 0 ? 'flex' : 'none';
            countLabel.textContent = checkedCount;
        }
        if(selectAll) selectAll.addEventListener('change', () => { rowCbs.forEach(cb => cb.checked = selectAll.checked); updateBatchUI(); });
        rowCbs.forEach(cb => cb.addEventListener('change', updateBatchUI));
        if(clearBtn) clearBtn.addEventListener('click', () => { if(selectAll) selectAll.checked = false; rowCbs.forEach(cb => cb.checked = false); updateBatchUI(); });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => { 
            if (!e.target.closest('.column-selector-container')) document.getElementById('columnMenu').classList.remove('show'); 
            if (ps && !ps.contains(e.target) && !pd.contains(e.target) && e.target !== cl) pd.style.display = 'none';
        });
    });
</script>

<style>
    .filter-section-modern { 
        padding: 15px; 
        margin-bottom: 20px; 
        background: #fff; 
        border-radius: 12px; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
        border-left: 5px solid var(--primary-color) !important;
    }
    .filter-main-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: flex-end; }
    .filter-item { display: flex; flex-direction: column; gap: 5px; }
    .filter-item label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; }
    
    /* Searchable Select Styles */
    .searchable-select-container { position: relative; width: 100%; }
    .btn-clear-inline { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; z-index: 5; }
    .searchable-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #cbd5e1; border-radius: 8px; margin-top: 5px; max-height: 250px; overflow-y: auto; z-index: 1000; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); display: none; }
    .dropdown-item { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; }
    .dropdown-item:hover { background: #f8fafc; color: var(--primary-color); }
    .dropdown-item.text-muted { cursor: default; }
    
    .search-input-wrapper { position: relative; width: 100%; }
    .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem; }
    .search-input-wrapper input { padding-left: 35px !important; }
    .form-control-sm, .form-select-sm { height: 36px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 0.85rem; width: 100%; }
    
    .folder-path { max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-family: 'Consolas', monospace; font-size: 0.85rem; color: #475569; background: #f1f5f9; padding: 4px 8px; border-radius: 4px; display: inline-block; }
    .badge.bg-label-info { background-color: #e0f2fe; color: #0284c7; font-weight: 500; padding: 5px 10px; border-radius: 6px; font-size: 0.85rem; }
    .user-badge { display: flex; align-items: center; color: #334155; font-weight: 500; }
    .user-badge i { margin-right: 8px; color: #94a3b8; font-size: 1.1rem; }
    .password-wrapper { display: flex; align-items: center; background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px; width: 100%; max-width: 130px; transition: border-color 0.2s; }
    .password-input { border: none; background: transparent; width: 100%; outline: none; font-size: 0.85rem; font-family: 'Consolas', monospace; color: #64748b; letter-spacing: 1px; }
    .password-input.visible { color: #0f172a; letter-spacing: normal; }
    .btn-toggle-pass { border: none; background: none; color: #94a3b8; cursor: pointer; padding: 0; font-size: 0.85rem; }
    
    .pagination-container .page-link { display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; color: #64748b; text-decoration: none; font-size: 0.9rem; font-weight: 500; }
    .pagination-container .page-link.active { background-color: var(--primary-color); color: #fff; border-color: var(--primary-color); }
    .pagination-container .page-link.disabled { color: #cbd5e1; pointer-events: none; }

    @media (max-width: 992px) { .filter-main-grid { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 768px) { .filter-main-grid { grid-template-columns: 1fr; } .folder-path { max-width: 120px; } }
</style>