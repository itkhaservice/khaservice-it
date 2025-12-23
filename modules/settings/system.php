<?php
// modules/settings/system.php

if (!isAdmin()) {
    set_message('error', 'Chỉ Admin mới có quyền truy cập trang này.');
    header("Location: index.php");
    exit;
}

// 1. AUTO-MIGRATE TABLE
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings_device_groups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_name VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $cols_g = $pdo->query("SHOW COLUMNS FROM settings_device_groups LIKE 'group_code'")->fetchAll();
    if (empty($cols_g)) { $pdo->exec("ALTER TABLE settings_device_groups ADD COLUMN group_code VARCHAR(20) DEFAULT NULL AFTER group_name"); }

    $cols_t = $pdo->query("SHOW COLUMNS FROM settings_device_types LIKE 'type_code'")->fetchAll();
    if (empty($cols_t)) { $pdo->exec("ALTER TABLE settings_device_types ADD COLUMN type_code VARCHAR(20) DEFAULT NULL AFTER type_name"); }
} catch (PDOException $e) { /* Silent fail */ }

// 2. HELPER FUNCTIONS
function get_pagination_data($pdo, $table, $page_param, $limit = 5, $order_by = 'id DESC', $where_sql = '', $params = []) {
    $page = isset($_GET[$page_param]) ? max(1, (int)$_GET[$page_param]) : 1;
    $offset = ($page - 1) * $limit;
    $count_sql = "SELECT COUNT(*) FROM $table $where_sql";
    $total_stmt = $pdo->prepare($count_sql);
    $total_stmt->execute($params);
    $total_rows = $total_stmt->fetchColumn();
    $total_pages = ceil($total_rows / $limit);
    $sql = "SELECT * FROM $table $where_sql ORDER BY $order_by LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $param_index = 1;
    foreach ($params as $v) { $stmt->bindValue($param_index++, $v); }
    $stmt->bindValue($param_index++, (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue($param_index++, (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    return [ 'data' => $stmt->fetchAll(), 'total_pages' => $total_pages, 'current_page' => $page, 'param' => $page_param ];
}

// 3. HANDLE ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add_group') {
            $name = trim($_POST['group_name']); $code = strtoupper(trim($_POST['group_code'] ?? ''));
            if ($name) { $pdo->prepare("INSERT IGNORE INTO settings_device_groups (group_name, group_code) VALUES (?, ?)")->execute([$name, $code]); set_message('success', 'Đã thêm phân nhóm.'); }
        } elseif ($action === 'delete_group') {
            $pdo->prepare("DELETE FROM settings_device_groups WHERE id = ?")->execute([$_POST['id']]); set_message('success', 'Đã xóa phân nhóm.');
        } elseif ($action === 'delete_multiple_groups') {
            if (!empty($_POST['ids'])) { $ids = implode(',', array_map('intval', $_POST['ids'])); $pdo->exec("DELETE FROM settings_device_groups WHERE id IN ($ids)"); set_message('success', 'Đã xóa các phân nhóm.'); }
        } elseif ($action === 'add_type') {
            $name = trim($_POST['type_name']); $code = strtoupper(trim($_POST['type_code'] ?? '')); $group = $_POST['group_name'];
            if ($name && $group) { $pdo->prepare("INSERT IGNORE INTO settings_device_types (type_name, type_code, group_name) VALUES (?, ?, ?)")->execute([$name, $code, $group]); set_message('success', 'Đã thêm loại thiết bị.'); }
        } elseif ($action === 'delete_type') {
            $pdo->prepare("DELETE FROM settings_device_types WHERE id = ?")->execute([$_POST['id']]); set_message('success', 'Đã xóa loại thiết bị.');
        } elseif ($action === 'delete_multiple_types') {
            if (!empty($_POST['ids'])) { $ids = implode(',', array_map('intval', $_POST['ids'])); $pdo->exec("DELETE FROM settings_device_types WHERE id IN ($ids)"); set_message('success', 'Đã xóa các loại thiết bị.'); }
        } elseif ($action === 'add_status') {
            $name = trim($_POST['status_name']); $color = $_POST['color_class'];
            if ($name) { $pdo->prepare("INSERT IGNORE INTO settings_device_statuses (status_name, color_class) VALUES (?, ?)")->execute([$name, $color]); set_message('success', 'Đã thêm trạng thái.'); }
        } elseif ($action === 'delete_status') {
            $pdo->prepare("DELETE FROM settings_device_statuses WHERE id = ?")->execute([$_POST['id']]); set_message('success', 'Đã xóa trạng thái.');
        } elseif ($action === 'delete_multiple_statuses') {
            if (!empty($_POST['ids'])) { $ids = implode(',', array_map('intval', $_POST['ids'])); $pdo->exec("DELETE FROM settings_device_statuses WHERE id IN ($ids)"); set_message('success', 'Đã xóa các trạng thái.'); }
        }
        $redirectUrl = "index.php?page=settings/system";
        $keepParams = ['page_g', 'page_t', 'page_s', 'filter_group'];
        foreach ($keepParams as $p) { if (isset($_GET[$p]) && $_GET[$p] !== '') $redirectUrl .= "&$p=" . urlencode($_GET[$p]); }
        header("Location: $redirectUrl"); exit;
    } catch (Exception $e) { set_message('error', 'Lỗi: ' . $e->getMessage()); }
}

// 4. FETCH DATA
$filter_group = $_GET['filter_group'] ?? '';
$where_type = ''; $params_type = [];
if ($filter_group) { $where_type = "WHERE group_name = ?"; $params_type = [$filter_group]; }

$groupsData   = get_pagination_data($pdo, 'settings_device_groups', 'page_g', 10, 'group_name ASC');
$typesData    = get_pagination_data($pdo, 'settings_device_types', 'page_t', 10, 'group_name, type_name ASC', $where_type, $params_type);
$statusesData = get_pagination_data($pdo, 'settings_device_statuses', 'page_s', 10, 'status_name ASC');
$allGroups = $pdo->query("SELECT group_name, group_code FROM settings_device_groups ORDER BY group_name ASC")->fetchAll();
?>

<div class="page-header">
    <h2><i class="fas fa-cogs"></i> Cấu hình Hệ thống</h2>
</div>

<div class="settings-layout-grid">
    
    <!-- === SECTION 1: PHÂN NHÓM === -->
    <div class="settings-card">
        <div class="card-header">
            <h3><i class="fas fa-layer-group"></i> Phân nhóm</h3>
            <div class="header-right-actions">
                <div class="bulk-actions" id="bulk-actions-group" style="display:none;">
                    <button type="button" class="btn-bulk-delete" onclick="submitBulkDelete('form-list-group')"><i class="fas fa-trash"></i> (<span class="count">0</span>)</button>
                </div>
            </div>
        </div>
        <div class="card-content">
            <form action="" method="POST" class="settings-form-stacked">
                <input type="hidden" name="action" value="add_group">
                <div class="form-row-split">
                    <div class="form-col flex-2"><label>Tên Nhóm</label><input type="text" name="group_name" placeholder="Bãi xe..." required></div>
                    <div class="form-col flex-1"><label>Mã (BX)</label><input type="text" name="group_code" placeholder="Mã..." class="uppercase-input"></div>
                    <div class="form-col-btn"><label>&nbsp;</label><button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i></button></div>
                </div>
            </form>
            <form id="form-list-group" action="" method="POST">
                <input type="hidden" name="action" value="delete_multiple_groups">
                <div class="settings-table-wrapper">
                    <table class="settings-table">
                        <thead><tr><th width="30"><input type="checkbox" onchange="toggleSelectAll(this, 'form-list-group')"></th><th>Phân nhóm</th><th>Mã</th><th width="40"></th></tr></thead>
                        <tbody>
                            <?php foreach ($groupsData['data'] as $g): ?>
                                <tr>
                                    <td><input type="checkbox" name="ids[]" value="<?php echo $g['id']; ?>" onchange="updateBulkState('form-list-group', 'bulk-actions-group')"></td>
                                    <td class="font-bold"><?php echo htmlspecialchars($g['group_name']); ?></td>
                                    <td><?php if($g['group_code']): ?><span class="badge-code"><?php echo htmlspecialchars($g['group_code']); ?></span><?php endif; ?></td>
                                    <td class="text-right">
                                        <button type="button" class="btn-delete-tiny" onclick="confirmDeleteSingle('delete_group', <?php echo $g['id']; ?>)"><i class="fas fa-times"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
            <?php render_pagination($groupsData); ?>
        </div>
    </div>

    <!-- === SECTION 2: LOẠI THIẾT BỊ === -->
    <div class="settings-card">
        <div class="card-header">
            <h3><i class="fas fa-microchip"></i> Loại thiết bị</h3>
            <div class="header-right-actions">
                <div class="bulk-actions" id="bulk-actions-type" style="display:none;">
                    <button type="button" class="btn-bulk-delete" onclick="submitBulkDelete('form-list-type')"><i class="fas fa-trash"></i> (<span class="count">0</span>)</button>
                </div>
                <select class="form-select-tiny" onchange="filterTypes(this.value)">
                    <option value="">Tất cả Nhóm</option>
                    <?php foreach ($allGroups as $g): ?>
                        <option value="<?php echo htmlspecialchars($g['group_name']); ?>" <?php echo $filter_group === $g['group_name'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($g['group_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="card-content">
            <form action="" method="POST" class="settings-form-stacked">
                <input type="hidden" name="action" value="add_type">
                <div class="form-group-custom">
                    <div class="form-row-full"><label>Tên Loại thiết bị</label><input type="text" name="type_name" placeholder="Camera..." required></div>
                    <div class="form-row-split">
                        <div class="form-col flex-1"><label>Mã (PC)</label><input type="text" name="type_code" placeholder="Mã..." class="uppercase-input"></div>
                        <div class="form-col flex-2"><label>Thuộc nhóm</label>
                            <select name="group_name">
                                <?php if (empty($allGroups)): ?><option value="">-- Trống --</option><?php else: ?>
                                    <?php foreach ($allGroups as $g): ?><option value="<?php echo htmlspecialchars($g['group_name']); ?>"><?php echo htmlspecialchars($g['group_name']); ?></option><?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-col-btn"><label>&nbsp;</label><button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i></button></div>
                    </div>
                </div>
            </form>
            <form id="form-list-type" action="" method="POST">
                <input type="hidden" name="action" value="delete_multiple_types">
                <div class="settings-table-wrapper">
                    <table class="settings-table">
                        <thead><tr><th width="30"><input type="checkbox" onchange="toggleSelectAll(this, 'form-list-type')"></th><th>Tên loại</th><th>Mã</th><th>Nhóm</th><th width="40"></th></tr></thead>
                        <tbody>
                            <?php foreach ($typesData['data'] as $t): ?>
                                <tr>
                                    <td><input type="checkbox" name="ids[]" value="<?php echo $t['id']; ?>" onchange="updateBulkState('form-list-type', 'bulk-actions-type')"></td>
                                    <td class="font-bold"><?php echo htmlspecialchars($t['type_name']); ?></td>
                                    <td><?php if($t['type_code']): ?><span class="badge-code"><?php echo htmlspecialchars($t['type_code']); ?></span><?php endif; ?></td>
                                    <td><span class="badge-soft"><?php echo htmlspecialchars($t['group_name']); ?></span></td>
                                    <td class="text-right">
                                        <button type="button" class="btn-delete-tiny" onclick="confirmDeleteSingle('delete_type', <?php echo $t['id']; ?>)"><i class="fas fa-times"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
             <?php render_pagination($typesData); ?>
        </div>
    </div>

    <!-- === SECTION 3: TRẠNG THÁI === -->
    <div class="settings-card">
        <div class="card-header">
            <h3><i class="fas fa-info-circle"></i> Trạng thái</h3>
            <div class="header-right-actions">
                <div class="bulk-actions" id="bulk-actions-status" style="display:none;">
                    <button type="button" class="btn-bulk-delete" onclick="submitBulkDelete('form-list-status')"><i class="fas fa-trash"></i> (<span class="count">0</span>)</button>
                </div>
            </div>
        </div>
        <div class="card-content">
            <form action="" method="POST" class="settings-form-stacked">
                <input type="hidden" name="action" value="add_status">
                <div class="form-row-split">
                    <div class="form-col flex-2"><label>Tên Trạng thái</label><input type="text" name="status_name" placeholder="Đang sửa..." required></div>
                    <div class="form-col flex-1"><label>Màu</label>
                        <select name="color_class">
                            <option value="status-active">Xanh lá</option><option value="status-error">Đỏ</option><option value="status-warning">Vàng</option><option value="status-info">Xanh</option><option value="status-default">Xám</option>
                        </select>
                    </div>
                    <div class="form-col-btn"><label>&nbsp;</label><button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i></button></div>
                </div>
            </form>
            <form id="form-list-status" action="" method="POST">
                <input type="hidden" name="action" value="delete_multiple_statuses">
                <div class="settings-table-wrapper">
                    <table class="settings-table">
                        <thead><tr><th width="30"><input type="checkbox" onchange="toggleSelectAll(this, 'form-list-status')"></th><th>Tên trạng thái</th><th width="40"></th></tr></thead>
                        <tbody>
                            <?php foreach ($statusesData['data'] as $s): ?>
                                <tr>
                                    <td><input type="checkbox" name="ids[]" value="<?php echo $s['id']; ?>" onchange="updateBulkState('form-list-status', 'bulk-actions-status')"></td>
                                    <td><span class="badge <?php echo $s['color_class']; ?>"><?php echo htmlspecialchars($s['status_name']); ?></span></td>
                                    <td class="text-right">
                                        <button type="button" class="btn-delete-tiny" onclick="confirmDeleteSingle('delete_status', <?php echo $s['id']; ?>)"><i class="fas fa-times"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
            <?php render_pagination($statusesData); ?>
        </div>
    </div>
</div>

<!-- HIDDEN FORMS & MODALS -->
<form id="single-delete-form" action="" method="POST" style="display:none;"><input type="hidden" name="action" id="single-delete-action"><input type="hidden" name="id" id="single-delete-id"></form>
<div id="customConfirmModal" class="custom-modal-overlay">
    <div class="custom-modal-container">
        <div class="modal-icon-wrapper"><i class="fas fa-exclamation-triangle"></i></div>
        <h3 class="modal-title">Xác nhận xóa</h3>
        <p class="modal-message" id="modalMessageText">Bạn có chắc chắn muốn xóa mục này không?</p>
        <div class="modal-actions">
            <button type="button" class="btn-modal btn-cancel" onclick="closeConfirmModal()">Hủy bỏ</button>
            <button type="button" class="btn-modal btn-confirm" id="btnConfirmAction">Xóa ngay</button>
        </div>
    </div>
</div>

<?php
function render_pagination($data) {
    if ($data['total_pages'] <= 1) return;
    $param = $data['param']; $current = $data['current_page']; $total = $data['total_pages'];
    $query = $_GET;
    echo '<div class="pagination-styled">';
    if ($current > 1) { $query[$param] = $current - 1; echo '<a href="index.php?'.http_build_query($query).'" class="page-link"><i class="fas fa-chevron-left"></i></a>'; }
    else { echo '<span class="page-link disabled"><i class="fas fa-chevron-left"></i></span>'; }
    echo '<span class="page-info"><strong>'.$current.'</strong> / '.$total.'</span>';
    if ($current < $total) { $query[$param] = $current + 1; echo '<a href="index.php?'.http_build_query($query).'" class="page-link"><i class="fas fa-chevron-right"></i></a>'; }
    else { echo '<span class="page-link disabled"><i class="fas fa-chevron-right"></i></span>'; }
    echo '</div>';
}
?>

<script>
function filterTypes(groupName) {
    const url = new URL(window.location.href);
    if (groupName) url.searchParams.set('filter_group', groupName); else url.searchParams.delete('filter_group');
    url.searchParams.set('page_t', 1); window.location.href = url.toString();
}
let pendingAction = null;
function showModal(message, callback) { document.getElementById('modalMessageText').textContent = message; document.getElementById('customConfirmModal').classList.add('active'); pendingAction = callback; }
function closeConfirmModal() { document.getElementById('customConfirmModal').classList.remove('active'); pendingAction = null; }
document.getElementById('btnConfirmAction').addEventListener('click', function() { if (pendingAction) pendingAction(); closeConfirmModal(); });
function confirmDeleteSingle(action, id) { showModal('Bạn có chắc chắn muốn xóa mục này không?', function() { document.getElementById('single-delete-action').value = action; document.getElementById('single-delete-id').value = id; document.getElementById('single-delete-form').submit(); }); }
function toggleSelectAll(source, formId) { const checkboxes = document.querySelectorAll(`#${formId} input[name="ids[]"]`); checkboxes.forEach(cb => cb.checked = source.checked); updateBulkState(formId, formId.replace('form-list-', 'bulk-actions-')); }
function updateBulkState(formId, wrapperId) { const n = document.querySelectorAll(`#${formId} input[name="ids[]"]:checked`).length; const w = document.getElementById(wrapperId); w.style.display = n > 0 ? 'flex' : 'none'; if(n > 0) w.querySelector('.count').textContent = n; }
function submitBulkDelete(formId) { const n = document.querySelectorAll(`#${formId} input[name="ids[]"]:checked`).length; if (n === 0) return; showModal(`Bạn có chắc muốn xóa ${n} mục đã chọn?`, function() { document.getElementById(formId).submit(); }); }
</script>

<style>
/* CORE LAYOUT */
.settings-layout-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; align-items: start; margin-bottom: 40px; }
.settings-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow: hidden; display: flex; flex-direction: column; }
.settings-card .card-header { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; justify-content: space-between; align-items: center; min-height: 50px; flex-wrap: wrap; gap: 10px; }
.settings-card .card-header h3 { margin: 0; font-size: 0.95rem; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 8px; }
.header-right-actions { display: flex; align-items: center; gap: 8px; }
.card-content { padding: 15px; }
.form-group-custom { display: flex; flex-direction: column; gap: 10px; margin-bottom: 15px; }
.form-row-full label, .form-row-split label { display: block; font-size: 0.7rem; font-weight: 700; color: #64748b; margin-bottom: 4px; text-transform: uppercase; }
.form-row-full input, .form-row-split input, .form-row-split select { width: 100%; height: 36px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 10px; font-size: 0.85rem; transition: 0.2s; }
.form-row-full input:focus, .form-row-split input:focus, .form-row-split select:focus { border-color: var(--primary-color); outline: none; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }
.form-row-split { display: flex; gap: 8px; align-items: flex-end; margin-bottom: 10px; }
.flex-1 { flex: 1; } .flex-2 { flex: 2; }
.form-col-btn button { height: 36px; width: 38px; border-radius: 6px; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer; }
.uppercase-input { text-transform: uppercase; }
.form-select-tiny { height: 28px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.75rem; padding: 0 5px; background: #fff; max-width: 120px; }
.badge-code { background: #e0f2fe; color: #0284c7; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 0.75rem; font-weight: 700; }
.badge-soft { background: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: 600; }
.settings-table-wrapper { border: 1px solid #f1f5f9; border-radius: 8px; overflow-x: auto; margin-bottom: 10px; }
.settings-table { width: 100%; border-collapse: collapse; min-width: 300px; }
.settings-table th, .settings-table td { padding: 10px; text-align: left; font-size: 0.85rem; border-bottom: 1px solid #f8fafc; }
.settings-table th { background: #f8fafc; font-size: 0.7rem; color: #64748b; text-transform: uppercase; font-weight: 700; }
.btn-delete-tiny { background: none; border: none; color: #cbd5e1; cursor: pointer; padding: 5px; font-size: 0.9rem; transition: 0.2s; }
.btn-delete-tiny:hover { color: #ef4444; transform: scale(1.2); }
.text-right { text-align: right; }
.pagination-styled { display: flex; justify-content: center; align-items: center; gap: 5px; margin-top: 5px; }
.page-link { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0; border-radius: 4px; color: #64748b; text-decoration: none; font-size: 0.8rem; }
.btn-bulk-delete { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; cursor: pointer; }

/* MODAL STYLES */
.custom-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center; opacity: 0; visibility: hidden; transition: 0.3s; backdrop-filter: blur(2px); }
.custom-modal-overlay.active { opacity: 1; visibility: visible; }
.custom-modal-container { background: #fff; width: 90%; max-width: 400px; border-radius: 16px; padding: 25px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.2); transform: translateY(20px); transition: 0.3s; }
.custom-modal-overlay.active .custom-modal-container { transform: translateY(0); }
.modal-icon-wrapper { width: 60px; height: 60px; background: #fef2f2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 15px; }
.modal-title { font-size: 1.25rem; font-weight: 700; color: #1e293b; margin-bottom: 10px; }
.modal-message { font-size: 0.95rem; color: #64748b; margin-bottom: 25px; line-height: 1.5; }
.modal-actions { display: flex; gap: 10px; justify-content: center; }
.btn-modal { flex: 1; padding: 10px; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; }
.btn-cancel { background: #f1f5f9; color: #475569; }
.btn-confirm { background: #ef4444; color: #fff; }

@media (max-width: 768px) { .settings-layout-grid { grid-template-columns: 1fr; } .form-row-split { flex-direction: column; align-items: stretch; gap: 10px; } .form-col-btn button { width: 100%; height: 40px; } }
</style>