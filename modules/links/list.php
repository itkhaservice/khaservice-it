<?php
// ==================================================
// PAGINATION CONFIG
// ==================================================
$rows_per_page = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : 10;
$current_page  = (isset($_GET['p']) && is_numeric($_GET['p'])) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

// ==================================================
// FILTER INPUT
// ==================================================
$filter_keyword = trim($_GET['filter_keyword'] ?? '');

// ==================================================
// BUILD QUERY
// ==================================================
$where_clauses = ["deleted_at IS NULL"];
$bind_params   = [];

if ($filter_keyword !== '') {
    $where_clauses[] = "(link LIKE :kw1 OR username LIKE :kw2 OR ghi_chu LIKE :kw3)";
    $bind_params[':kw1'] = $bind_params[':kw2'] = $bind_params[':kw3'] = '%' . $filter_keyword . '%';
}

$where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

// Sorting
$sort_by    = $_GET['sort_by'] ?? 'stt';
$sort_order = strtoupper($_GET['sort_order'] ?? 'ASC');
$order_sql = " ORDER BY $sort_by $sort_order, id ASC";

// Count Total
$count_sql = "SELECT COUNT(*) FROM links $where_sql";
$count_stmt = $pdo->prepare($count_sql);
foreach ($bind_params as $key => $value) $count_stmt->bindValue($key, $value);
$count_stmt->execute();
$total_rows  = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $rows_per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $rows_per_page;

// Fetch Data
$data_sql = "SELECT * FROM links $where_sql $order_sql LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($data_sql);
foreach ($bind_params as $key => $value) $stmt->bindValue($key, $value);
$stmt->bindValue(':limit',  $rows_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Column configuration
$all_columns = [
    'stt'        => ['label' => 'STT', 'default' => true],
    'link'       => ['label' => 'Link / URL', 'default' => true],
    'username'   => ['label' => 'Username', 'default' => true],
    'password'   => ['label' => 'Password', 'default' => true],
    'ghi_chu'    => ['label' => 'Ghi chú', 'default' => true]
];

// Hàm kiểm tra Bcrypt hash (để cảnh báo nếu dữ liệu cũ chưa mã hóa)
if (!function_exists('is_bcrypt_hash')) {
    function is_bcrypt_hash($str) {
        return (strlen($str) == 60 && preg_match('/^\$2[ayb]\$.{56}$/', $str));
    }
}
?>

<div class="page-header">
    <div class="header-title">
        <h2><i class="fas fa-link"></i> Quản lý Link / Tài khoản</h2>
    </div>
    <div class="header-actions">
        <?php if (isIT()): ?>
        <a href="index.php?page=links/add" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Thêm mới
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card filter-section">
    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="links/list">
        
        <div class="filter-group">
            <label>Tìm kiếm nhanh</label>
            <input type="text" name="filter_keyword" value="<?php echo htmlspecialchars($filter_keyword); ?>" placeholder="Link, username, ghi chú...">
        </div>

        <div class="filter-buttons-group" style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Lọc</button>
            <a href="index.php?page=links/list" class="btn btn-secondary btn-sm" title="Xóa lọc"><i class="fas fa-undo"></i></a>
            
            <div class="column-selector-container">
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleColumnMenu()"><i class="fas fa-columns"></i> Cột</button>
                <div id="columnMenu" class="dropdown-menu">
                    <div class="dropdown-header">Hiển thị cột</div>
                    <div class="column-list">
                        <?php foreach ($all_columns as $k => $c): ?>
                            <label class="column-item">
                                <input type="checkbox" class="col-checkbox" data-target="<?php echo $k; ?>" checked> 
                                <?php echo htmlspecialchars($c['label']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="table-container card">
    <table class="content-table" id="linksTable">
        <thead>
            <tr>
                <th width="60" class="text-center">STT</th>
                <th data-col="link">Link / URL</th>
                <th data-col="username">Username</th>
                <th data-col="password">Password</th>
                <th data-col="ghi_chu">Ghi chú</th>
                <th width="100" class="text-center">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($links)): ?>
                <tr><td colspan="10" class="text-center" style="padding: 40px !important;">Không tìm thấy dữ liệu</td></tr>
            <?php else: ?>
                <?php foreach ($links as $item): ?>
                    <tr>
                        <td class="text-center"><span class="badge status-default"><?php echo $item['stt']; ?></span></td>
                        <td data-col="link">
                            <div class="flex-align-center gap-10">
                                <a href="<?php echo htmlspecialchars($item['link']); ?>" target="_blank" class="font-medium text-primary link-hover">
                                    <?php echo htmlspecialchars($item['link']); ?>
                                </a>
                                <button class="btn-icon-sm" onclick="copyToClipboard('<?php echo addslashes($item['link']); ?>')" title="Copy Link">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </td>
                        <td data-col="username">
                            <div class="flex-align-center gap-10">
                                <span class="font-semibold"><?php echo htmlspecialchars($item['username']); ?></span>
                                <?php if ($item['username']): ?>
                                <button class="btn-icon-sm" onclick="copyToClipboard('<?php echo addslashes($item['username']); ?>')" title="Copy Username">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td data-col="password">
                            <?php 
                            $is_hashed = is_bcrypt_hash($item['password']);
                            $raw_pass = $is_hashed ? "" : decrypt_data($item['password']);
                            ?>
                            <div class="password-box <?php echo $is_hashed ? 'is-hashed' : ''; ?>">
                                <?php if ($is_hashed): ?>
                                    <span class="password-masked" title="Mật khẩu này ở dạng băm cũ, không xem được">********</span>
                                    <div class="password-btns">
                                        <i class="fas fa-exclamation-triangle text-danger" title="Dữ liệu cũ - Cần cập nhật lại"></i>
                                    </div>
                                <?php else: ?>
                                    <span class="password-masked" data-pass="<?php echo htmlspecialchars($raw_pass); ?>">********</span>
                                    <div class="password-btns">
                                        <button class="btn-icon-sm" onclick="togglePassView(this)" title="Hiện/Ẩn"><i class="fas fa-eye"></i></button>
                                        <button class="btn-icon-sm" onclick="copyToClipboard('<?php echo addslashes($raw_pass); ?>')" title="Copy Pass"><i class="fas fa-copy"></i></button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td data-col="ghi_chu">
                            <div class="text-truncate-2" title="<?php echo htmlspecialchars($item['ghi_chu']); ?>">
                                <?php echo nl2br(htmlspecialchars($item['ghi_chu'])); ?>
                            </div>
                        </td>
                        <td class="actions text-center">
                            <?php if (isIT()): ?>
                                <a href="index.php?page=links/edit&id=<?php echo $item['id']; ?>" class="btn-icon" title="Sửa"><i class="fas fa-edit"></i></a>
                                <?php if (isAdmin()): ?>
                                    <button type="button" class="btn-icon delete-btn" data-delete-url="index.php?page=links/delete&id=<?php echo $item['id']; ?>" title="Xóa"><i class="fas fa-trash-alt"></i></button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="table-footer">
        <div class="footer-left">
            <form action="index.php" method="GET" class="limit-selector" style="display:flex; align-items:center; gap:8px; font-size:0.85rem;">
                <input type="hidden" name="page" value="links/list">
                <input type="hidden" name="filter_keyword" value="<?php echo htmlspecialchars($filter_keyword); ?>">
                <span>Hiển thị</span>
                <select name="limit" onchange="this.form.submit()" style="height:30px; border-radius:4px; border:1px solid #cbd5e1;">
                    <?php foreach ([5, 10, 20, 50, 100] as $l): ?>
                        <option value="<?php echo $l; ?>" <?php echo $rows_per_page == $l ? 'selected' : ''; ?>><?php echo $l; ?></option>
                    <?php endforeach; ?>
                </select>
                <span>dòng</span>
            </form>
        </div>
        <div class="footer-right">
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <a href="index.php?page=links/list&p=1&limit=<?php echo $rows_per_page; ?>&filter_keyword=<?php echo urlencode($filter_keyword); ?>" class="page-link <?php echo $current_page == 1 ? 'disabled' : ''; ?>">&laquo;</a>
                    <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                        <a href="index.php?page=links/list&p=<?php echo $i; ?>&limit=<?php echo $rows_per_page; ?>&filter_keyword=<?php echo urlencode($filter_keyword); ?>" class="page-link <?php echo $current_page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <a href="index.php?page=links/list&p=<?php echo $total_pages; ?>&limit=<?php echo $rows_per_page; ?>&filter_keyword=<?php echo urlencode($filter_keyword); ?>" class="page-link <?php echo $current_page == $total_pages ? 'disabled' : ''; ?>">&raquo;</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleColumnMenu() {
    document.getElementById('columnMenu').classList.toggle('show');
}

window.onclick = function(event) {
    if (!event.target.closest('.column-selector-container')) {
        var dropdowns = document.getElementsByClassName("dropdown-menu");
        for (var i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
            }
        }
    }
}

document.querySelectorAll('.col-checkbox').forEach(cb => {
    cb.addEventListener('change', function() {
        const col = this.getAttribute('data-target');
        const show = this.checked;
        const cells = document.querySelectorAll(`[data-col="${col}"], th[data-col="${col}"]`);
        cells.forEach(c => c.style.display = show ? '' : 'none');
    });
});

function togglePassView(btn) {
    const box = btn.closest('.password-box');
    const span = box.querySelector('.password-masked');
    const icon = btn.querySelector('i');
    const pass = span.getAttribute('data-pass');
    
    if (span.textContent === '********') {
        span.textContent = pass;
        span.classList.add('revealed');
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        span.textContent = '********';
        span.classList.remove('revealed');
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function copyToClipboard(text) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        if (typeof showToast === 'function') showToast('Đã copy vào bộ nhớ tạm', 'success');
        else alert('Đã copy!');
    });
}
</script>

<style>
/* Local Overrides */
.flex-align-center { display: flex; align-items: center; }
.gap-10 { gap: 10px; }
.font-semibold { font-weight: 600; }
.font-medium { font-weight: 500; }
.text-primary { color: var(--primary-color); }
.link-hover:hover { text-decoration: underline; }
.text-truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: 0.85rem; line-height: 1.4; }
.btn-icon-sm { background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; border-radius: 4px; transition: 0.2s; font-size: 0.85rem; }
.btn-icon-sm:hover { color: var(--primary-color); background: #f1f5f9; }
.column-selector-container { position: relative; }
.dropdown-menu { display: none; position: absolute; right: 0; top: 100%; z-index: 100; background: white; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); padding: 12px; min-width: 200px; }
.dropdown-menu.show { display: block; }
.dropdown-header { font-weight: 700; padding: 0 8px 8px 8px; border-bottom: 1px solid #f1f5f9; margin-bottom: 8px; font-size: 0.8rem; color: #64748b; text-transform: uppercase; }
.column-item { display: flex; align-items: center; gap: 10px; padding: 8px; cursor: pointer; font-size: 0.9rem; border-radius: 6px; }
.column-item:hover { background: #f8fafc; color: var(--primary-color); }
.badge { padding: 4px 8px; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }

/* Password Box */
.password-box { display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 4px 10px; border-radius: 8px; border: 1px solid #e2e8f0; min-width: 160px; }
.password-box.is-hashed { background: #fff1f2; border-color: #fecaca; }
.password-masked { font-family: 'Consolas', monospace; color: #64748b; font-size: 0.9rem; }
.password-masked.revealed { color: #0f172a; font-weight: 600; }
.password-btns { display: flex; gap: 4px; align-items: center; }

/* Filter Section Adjustments */
.filter-section { border-left: 5px solid var(--primary-color) !important; padding: 15px 20px !important; margin-bottom: 25px; }
.filter-form { display: flex; align-items: flex-end; gap: 15px; }
.filter-group { display: flex; flex-direction: column; gap: 5px; flex: 1; }
.filter-group label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; }
.filter-group input { height: 34px !important; padding: 0 12px !important; border: 1px solid #cbd5e1 !important; border-radius: 6px !important; }
.table-footer { padding: 15px 20px; background: #fff; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
</style>
