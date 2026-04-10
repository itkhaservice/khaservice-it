<?php
// modules/it_activities/add_health_check.php
$pageTitle = "Kiểm tra Tình trạng Hệ thống";

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'it')) {
    set_message("error", "Bạn không có quyền truy cập trang này!");
    echo '<script>window.location.href = "index.php";</script>';
    exit;
}

$selected_date = $_GET['date'] ?? date('Y-m-d');
$project_id = $_GET['project_id'] ?? null;

$status_sync_map = [
    'good'    => 'Tốt',
    'warning' => 'Cảnh báo',
    'broken'  => 'Hỏng'
];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_health_check'])) {
    $project_id = $_POST['project_id'];
    $check_date = $_POST['check_date'];
    $overall_health = $_POST['overall_health'];
    $summary_notes = $_POST['summary_notes'];
    $checked_by = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO it_system_health_checks (project_id, check_date, checked_by, overall_health, summary_notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$project_id, $check_date, $checked_by, $overall_health, $summary_notes]);
        $check_id = $pdo->lastInsertId();

        if (isset($_POST['device_ids']) && is_array($_POST['device_ids'])) {
            $stmt_detail = $pdo->prepare("INSERT INTO it_system_health_check_details (check_id, device_id, status, health_status, quantity, cause, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_sync = $pdo->prepare("UPDATE devices SET trang_thai = ? WHERE id = ?");

            foreach ($_POST['device_ids'] as $index => $device_id) {
                $status = $_POST['status'][$index] ?? 'Đang sử dụng';
                $health = $_POST['health_status'][$index] ?? 'good';
                $qty = $_POST['quantity'][$index] ?? 1;
                $cause = $_POST['cause'][$index] ?? '';
                $notes = $_POST['notes'][$index] ?? '';
                
                $stmt_detail->execute([$check_id, $device_id, $status, $health, $qty, $cause, $notes]);

                if (isset($status_sync_map[$health])) {
                    $stmt_sync->execute([$status_sync_map[$health], $device_id]);
                }
            }
        }

        $pdo->commit();
        set_message("success", "Đã lưu báo cáo và đồng bộ trạng thái thiết bị thành công!");
        echo "<script>window.location.href = 'index.php?page=it_activities/list';</script>";
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        set_message("error", "Lỗi: " . $e->getMessage());
    }
}

// Handle Quick Add Device
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_add_device'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO devices (ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, ma_tai_san, project_id, parent_id, model, serial, trang_thai) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Tốt')");
        $stmt->execute([
            $_POST['q_ten_thiet_bi'],
            $_POST['q_nhom_thiet_bi'],
            $_POST['q_loai_thiet_bi'],
            $_POST['q_ma_tai_san'],
            $_POST['project_id'],
            $_POST['q_parent_id'] ?: null,
            $_POST['q_model'] ?: null,
            $_POST['q_serial'] ?: null
        ]);
        set_message("success", "Đã thêm nhanh thiết bị: " . $_POST['q_ten_thiet_bi']);
        echo "<script>window.location.href = 'index.php?page=it_activities/add_health_check&date=$selected_date&project_id=$project_id';</script>";
        exit;
    } catch (PDOException $e) {
        set_message("error", "Lỗi khi thêm nhanh: " . $e->getMessage());
    }
}

// Fetch dynamic settings
$db_types = $pdo->query("SELECT * FROM settings_device_types ORDER BY group_name, type_name")->fetchAll(PDO::FETCH_ASSOC);
$db_groups = array_unique(array_column($db_types, 'group_name'));
$projects = $pdo->query("SELECT id, ten_du_an, ma_du_an FROM projects WHERE deleted_at IS NULL ORDER BY ten_du_an ASC")->fetchAll();

// Fetch Devices and Build Tree
$tree_by_group = [];
$potential_parents = [];
if ($project_id) {
    $stmt = $pdo->prepare("SELECT id, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, ma_tai_san, parent_id 
                          FROM devices 
                          WHERE project_id = ? AND deleted_at IS NULL 
                          ORDER BY nhom_thiet_bi, parent_id ASC, ten_thiet_bi ASC");
    $stmt->execute([$project_id]);
    $raw_devices = $stmt->fetchAll();
    
    $roots = [];
    $children = [];
    foreach ($raw_devices as $d) {
        if (!$d['parent_id']) {
            $roots[$d['nhom_thiet_bi']][] = $d;
        } else {
            $children[$d['parent_id']][] = $d;
        }
        $potential_parents[] = $d;
    }
    
    // Combine into a display tree
    foreach ($roots as $group => $root_list) {
        foreach ($root_list as $root) {
            $tree_by_group[$group][] = ['item' => $root, 'level' => 0];
            if (isset($children[$root['id']])) {
                foreach ($children[$root['id']] as $child) {
                    $tree_by_group[$group][] = ['item' => $child, 'level' => 1];
                }
            }
        }
    }
}
?>

<style>
/* --- Table & UI Refinement --- */
.health-check-container { margin-top: 20px; }
.group-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-bottom: 25px; overflow: hidden; }
.group-card-header { background: #f8fafc; padding: 12px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.group-card-header h3 { margin: 0; font-size: 1rem; font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: 10px; }
.device-table { width: 100%; border-collapse: collapse; }
.device-table th { background: #f1f5f9; padding: 12px 15px; text-align: left; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; border-bottom: 1px solid #e2e8f0; }
.device-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }

/* Tree Styling */
.row-root { background-color: #fff; }
.row-child { background-color: #fcfdfd; }
.device-info { display: flex; align-items: flex-start; gap: 10px; }
.device-info.level-1 { padding-left: 20px; }
.tree-branch { color: #cbd5e1; font-size: 1rem; margin-top: 2px; }
.device-text strong { display: block; color: #1e293b; font-size: 0.9rem; margin-bottom: 2px; }
.device-text small { color: #94a3b8; font-size: 0.7rem; font-style: italic; display: block; }
.row-child .device-text strong { color: #475569; font-weight: 500; }

.select-styled { width: 100%; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; background-color: #fff; transition: 0.2s; cursor: pointer; }
.status-inuse { color: #10b981; font-weight: 600; }
.status-notinuse { color: #ef4444; font-weight: 600; }
.input-styled { width: 100%; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; }
.input-styled:focus, .select-styled:focus { border-color: var(--primary-color); outline: none; box-shadow: 0 0 0 3px rgba(36, 162, 92, 0.1); }

/* Modal CSS */
.modal-custom { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
.modal-content-custom { background: #fff; margin: 5vh auto; width: 650px; max-width: 95%; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; animation: modalPop 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
@keyframes modalPop { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.modal-header-custom { background: var(--primary-color); color: #fff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
.modal-header-custom h3 { margin: 0; font-size: 1.1rem; font-weight: 700; }
.close-modal { cursor: pointer; font-size: 24px; opacity: 0.8; transition: 0.2s; }
.close-modal:hover { opacity: 1; }
.modal-body-custom { padding: 25px; }
.modal-footer-custom { padding: 15px 25px; border-top: 1px solid #e2e8f0; text-align: right; background: #f8fafc; }
.form-group-custom label { display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-color); font-size: 0.85rem; }
.form-control-custom { width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.9rem; transition: border-color 0.2s; }

/* Type Picker */
.type-picker-wrapper { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px; max-height: 140px; overflow-y: auto; }
.type-list { display: flex; flex-wrap: wrap; gap: 8px; }
.type-pill { padding: 5px 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; font-size: 0.75rem; font-weight: 500; color: #475569; cursor: pointer; transition: 0.2s; }
.type-pill:hover { border-color: var(--primary-color); color: var(--primary-color); background: #f0fdf4; }
.type-pill.active { background: var(--primary-color); color: #fff; border-color: var(--primary-color); box-shadow: 0 2px 4px rgba(36, 162, 92, 0.2); }
</style>

<div class="page-header">
    <h2><i class="fas fa-file-medical"></i> Phiếu Kiểm tra Tình trạng Hệ thống</h2>
    <div class="header-actions">
        <a href="index.php?page=it_activities/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <button type="submit" form="health-check-form" name="save_health_check" class="btn btn-primary"><i class="fas fa-save"></i> Lưu báo cáo</button>
    </div>
</div>

<form action="" method="POST" id="health-check-form">
    <input type="hidden" name="save_health_check" value="1">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-4">
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-light"><strong>Thông tin chung</strong></div>
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label>Dự án <span class="text-danger">*</span></label>
                        <select name="project_id" id="main_project_id" class="form-control" required onchange="window.location.href='index.php?page=it_activities/add_health_check&date=<?= $selected_date ?>&project_id=' + this.value">
                            <option value="">-- Chọn dự án --</option>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>" data-code="<?= $p['ma_du_an'] ?>" <?= $project_id == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['ten_du_an']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label>Ngày kiểm tra <span class="text-danger">*</span></label>
                        <input type="date" name="check_date" value="<?= $selected_date ?>" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label>Đánh giá tổng quát</label>
                        <select name="overall_health" class="form-control">
                            <option value="good">Tốt (Hệ thống ổn định)</option>
                            <option value="warning">Cảnh báo (Có thiết bị lỗi/cần thay thế)</option>
                            <option value="critical">Khẩn cấp (Hệ thống ngưng trệ)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ghi chú tổng quan</label>
                        <textarea name="summary_notes" class="form-control" rows="4" placeholder="Nhận xét chung về hạ tầng IT tại dự án..."></textarea>
                    </div>
                </div>
            </div>
            
            <?php if ($project_id): ?>
            <div class="card shadow-sm border-success text-center p-3">
                <p class="mb-2 small text-muted">Thiết bị còn thiếu?</p>
                <button type="button" class="btn btn-primary btn-sm w-100" onclick="openQuickAddModal()">
                    <i class="fas fa-bolt"></i> THÊM NHANH THIẾT BỊ
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Device Tables -->
        <div class="col-md-8">
            <div class="health-check-container">
                <?php if (!$project_id): ?>
                    <div class="alert alert-info">Vui lòng chọn <strong>Dự án</strong> để liệt kê danh sách thiết bị.</div>
                <?php elseif (empty($tree_by_group)): ?>
                    <div class="alert alert-warning">Chưa có thiết bị nào được quản lý.</div>
                <?php else: ?>
                    <?php foreach ($tree_by_group as $group_name => $nodes): ?>
                        <div class="group-card">
                            <div class="group-card-header">
                                <h3><i class="fas fa-layer-group"></i> Nhóm: <?= htmlspecialchars($group_name) ?></h3>
                            </div>
                            <table class="device-table">
                                <thead>
                                    <tr>
                                        <th width="35%">Thiết bị / Mã TS</th>
                                        <th width="15%">Sử dụng</th>
                                        <th width="15%">Sức khỏe</th>
                                        <th width="8%">S.Lượng</th>
                                        <th width="27%">Nguyên nhân & Ghi chú</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($nodes as $node): 
                                        $d = $node['item'];
                                        $lvl = $node['level'];
                                    ?>
                                        <tr class="<?= $lvl == 0 ? 'row-root' : 'row-child' ?>">
                                            <td>
                                                <div class="device-info level-<?= $lvl ?>">
                                                    <?php if($lvl > 0): ?><span class="tree-branch">↳</span><?php endif; ?>
                                                    <div class="device-text">
                                                        <strong><?= htmlspecialchars($d['ten_thiet_bi']) ?></strong>
                                                        <small><?= htmlspecialchars($d['ma_tai_san']) ?></small>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="device_ids[]" value="<?= $d['id'] ?>">
                                            </td>
                                            <td>
                                                <select name="status[]" class="select-styled" onchange="updateStatusColor(this)">
                                                    <option value="Đang sử dụng" class="status-inuse">Đang dùng</option>
                                                    <option value="Không sử dụng" class="status-notinuse">Không dùng</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select name="health_status[]" class="select-styled">
                                                    <option value="good">Tốt</option>
                                                    <option value="warning">Cảnh báo</option>
                                                    <option value="broken">Hỏng</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" name="quantity[]" value="1" min="0" class="input-styled text-center">
                                            </td>
                                            <td>
                                                <input type="text" name="cause[]" class="input-styled mb-1" placeholder="Nguyên nhân...">
                                                <input type="text" name="notes[]" class="input-styled" placeholder="Ghi chú...">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>

<!-- QUICK ADD DEVICE MODAL -->
<div id="quickAddModal" class="modal-custom">
    <div class="modal-content-custom">
        <div class="modal-header-custom">
            <h3><i class="fas fa-bolt"></i> Thêm nhanh Thiết bị</h3>
            <span class="close-modal" onclick="closeQuickAddModal()">&times;</span>
        </div>
        <form action="" method="POST" id="quick-add-form">
            <input type="hidden" name="quick_add_device" value="1">
            <input type="hidden" name="project_id" id="q_project_id" value="<?= $project_id ?>">
            
            <div class="modal-body-custom">
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <div class="form-group-custom">
                            <label>Nhóm thiết bị</label>
                            <select name="q_nhom_thiet_bi" id="q_nhom_thiet_bi" class="form-control-custom" onchange="filterQuickTypes();">
                                <?php foreach ($db_groups as $group): ?>
                                    <option value="<?= htmlspecialchars($group) ?>"><?= htmlspecialchars($group) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-7 mb-3">
                        <div class="form-group-custom">
                            <label>Loại thiết bị (Chọn bên dưới)</label>
                            <input type="text" name="q_loai_thiet_bi" id="q_loai_thiet_bi" class="form-control-custom" required readonly placeholder="Chọn một loại...">
                        </div>
                    </div>
                </div>
                
                <div class="form-group-custom mb-3">
                    <div class="type-picker-wrapper">
                        <div class="type-list" id="quick-type-list"></div>
                    </div>
                </div>
                
                <div class="form-group-custom mb-3">
                    <label>Tên thiết bị <span class="text-danger">*</span></label>
                    <input type="text" name="q_ten_thiet_bi" id="q_ten_thiet_bi" class="form-control-custom" required placeholder="VD: Máy tính chị Yến">
                </div>

                <div class="form-group-custom mb-3">
                    <label>Mã Tài sản (Tự sinh)</label>
                    <div style="display: flex; gap: 5px;">
                        <input type="text" name="q_ma_tai_san" id="q_ma_tai_san" class="form-control-custom" style="background:#f1f5f9;" readonly>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="callGenerateAssetCodeAPI()"><i class="fas fa-sync"></i></button>
                    </div>
                </div>

                <div class="form-group-custom mb-3">
                    <label>Thiết bị gốc (Cha)</label>
                    <select name="q_parent_id" class="form-control-custom">
                        <option value="">-- Là thiết bị gốc --</option>
                        <?php foreach ($potential_parents as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['ma_tai_san']) ?> - <?= htmlspecialchars($p['ten_thiet_bi']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-group-custom"><label>Model</label><input type="text" name="q_model" class="form-control-custom"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-group-custom"><label>Serial</label><input type="text" name="q_serial" class="form-control-custom"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer-custom">
                <button type="button" class="btn btn-secondary" onclick="closeQuickAddModal()">Hủy</button>
                <button type="submit" class="btn btn-primary">LƯU THIẾT BỊ</button>
            </div>
        </form>
    </div>
</div>

<script>
const allTypes = <?= json_encode($db_types) ?>;
function openQuickAddModal() { document.getElementById('quickAddModal').style.display = 'block'; filterQuickTypes(); }
function closeQuickAddModal() { document.getElementById('quickAddModal').style.display = 'none'; }

function filterQuickTypes() {
    const group = document.getElementById('q_nhom_thiet_bi').value;
    const listContainer = document.getElementById('quick-type-list');
    const typeInput = document.getElementById('q_loai_thiet_bi');
    const assetInput = document.getElementById('q_ma_tai_san');
    listContainer.innerHTML = ''; typeInput.value = ''; assetInput.value = '';
    const filtered = allTypes.filter(t => t.group_name === group);
    filtered.forEach(t => {
        const pill = document.createElement('div');
        pill.className = 'type-pill'; pill.innerText = t.type_name;
        pill.onclick = function() {
            typeInput.value = t.type_name;
            document.querySelectorAll('.type-pill').forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            callGenerateAssetCodeAPI();
        };
        listContainer.appendChild(pill);
    });
}

function callGenerateAssetCodeAPI() {
    const projectId = document.getElementById('q_project_id').value;
    const groupName = document.getElementById('q_nhom_thiet_bi').value;
    const typeName = document.getElementById('q_loai_thiet_bi').value;
    const assetInput = document.getElementById('q_ma_tai_san');
    if (!projectId || !groupName || !typeName) return;
    assetInput.value = 'Đang tạo...';
    fetch(`api/generate_asset_code.php?project_id=${projectId}&group_name=${encodeURIComponent(groupName)}&type_name=${encodeURIComponent(typeName)}`)
        .then(response => response.json()).then(data => { assetInput.value = data.code || 'Lỗi!'; })
        .catch(() => { assetInput.value = 'Lỗi kết nối!'; });
}

function updateStatusColor(select) {
    select.classList.remove('status-inuse', 'status-notinuse');
    if (select.value === 'Đang sử dụng') select.classList.add('status-inuse');
    else if (select.value === 'Không sử dụng') select.classList.add('status-notinuse');
}
document.addEventListener('DOMContentLoaded', () => { document.querySelectorAll('select[name="status[]"]').forEach(updateStatusColor); });
window.onclick = (e) => { if (e.target == document.getElementById('quickAddModal')) closeQuickAddModal(); }
</script>