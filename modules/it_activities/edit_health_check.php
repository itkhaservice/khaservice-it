<?php
// modules/it_activities/edit_health_check.php
$pageTitle = "Chỉnh sửa Kiểm tra Hệ thống";

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'it')) {
    set_message("error", "Bạn không có quyền truy cập trang này!");
    echo '<script>window.location.href = "index.php";</script>';
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    set_message("error", "Không tìm thấy mã báo cáo!");
    echo '<script>window.location.href = "index.php?page=it_activities/list";</script>';
    exit;
}

$status_sync_map = ['good' => 'Tốt', 'warning' => 'Cảnh báo', 'broken' => 'Hỏng'];

$stmt = $pdo->prepare("SELECT * FROM it_system_health_checks WHERE id = ?");
$stmt->execute([$id]);
$check = $stmt->fetch();

if (!$check) {
    set_message("error", "Báo cáo không tồn tại!");
    echo '<script>window.location.href = "index.php?page=it_activities/list";</script>';
    exit;
}

$project_id = $check['project_id'];
$check_date = $check['check_date'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_health_check'])) {
    $overall_health = $_POST['overall_health'];
    $summary_notes = $_POST['summary_notes'];
    $check_date = $_POST['check_date'];
    $checked_by = $_POST['checked_by'];
    $client_name = $_POST['client_name'];
    $client_position = $_POST['client_position'];
    $checked_position = $_POST['checked_position'];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE it_system_health_checks SET check_date = ?, overall_health = ?, summary_notes = ?, checked_by = ?, client_name = ?, client_position = ?, checked_position = ? WHERE id = ?");
        $stmt->execute([$check_date, $overall_health, $summary_notes, $checked_by, $client_name, $client_position, $checked_position, $id]);

        if (isset($_POST['device_ids']) && is_array($_POST['device_ids'])) {
            $stmt_sync = $pdo->prepare("UPDATE devices SET trang_thai = ? WHERE id = ?");
            foreach ($_POST['device_ids'] as $index => $device_id) {
                $status = $_POST['status'][$index] ?? 'Đang sử dụng';
                $health = $_POST['health_status'][$index] ?? 'good';
                $qty = $_POST['quantity'][$index] ?? 1;
                $cause = $_POST['cause'][$index] ?? '';
                $notes = $_POST['notes'][$index] ?? '';
                
                // Kiểm tra xem thiết bị này đã có trong báo cáo chưa
                $stmt_check = $pdo->prepare("SELECT id FROM it_system_health_check_details WHERE check_id = ? AND device_id = ?");
                $stmt_check->execute([$id, $device_id]);
                $exists = $stmt_check->fetch();

                if ($exists) {
                    $stmt_upd = $pdo->prepare("UPDATE it_system_health_check_details 
                                              SET status = ?, health_status = ?, quantity = ?, cause = ?, notes = ? 
                                              WHERE id = ?");
                    $stmt_upd->execute([$status, $health, $qty, $cause, $notes, $exists['id']]);
                } else {
                    $stmt_ins = $pdo->prepare("INSERT INTO it_system_health_check_details 
                                              (check_id, device_id, status, health_status, quantity, cause, notes) 
                                              VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt_ins->execute([$id, $device_id, $status, $health, $qty, $cause, $notes]);
                }

                if (isset($status_sync_map[$health])) {
                    $stmt_sync->execute([$status_sync_map[$health], $device_id]);
                }
            }
        }
        $pdo->commit();
        set_message("success", "Đã cập nhật báo cáo thành công!");
        echo "<script>window.location.href = 'index.php?page=it_activities/view_health_check&id=$id';</script>";
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        set_message("error", "Lỗi: " . $e->getMessage());
    }
}

$projects = $pdo->query("SELECT id, ten_du_an, ma_du_an FROM projects WHERE deleted_at IS NULL ORDER BY ten_du_an ASC")->fetchAll();
$it_staff = $pdo->query("SELECT id, fullname FROM users WHERE role IN ('it', 'admin') AND deleted_at IS NULL ORDER BY fullname ASC")->fetchAll();

// Fetch ALL devices for this project and JOIN with existing check details if they exist
$stmt = $pdo->prepare("SELECT 
                        dev.id as device_id, dev.ten_thiet_bi, dev.ma_tai_san, dev.nhom_thiet_bi, dev.parent_id,
                        d.status, d.health_status, d.quantity, d.cause, d.notes, d.id as detail_id
                      FROM devices dev
                      LEFT JOIN it_system_health_check_details d ON dev.id = d.device_id AND d.check_id = ?
                      WHERE dev.project_id = ? AND dev.deleted_at IS NULL
                      ORDER BY dev.nhom_thiet_bi, dev.parent_id ASC, dev.ten_thiet_bi");
$stmt->execute([$id, $project_id]);
$existing_details = $stmt->fetchAll();

$tree_by_group = []; $roots = []; $children = [];
foreach ($existing_details as $row) {
    if (!$row['parent_id']) $roots[$row['nhom_thiet_bi']][] = $row;
    else $children[$row['parent_id']][] = $row;
}
foreach ($roots as $group => $root_list) {
    foreach ($root_list as $root) {
        $tree_by_group[$group][] = ['item' => $root, 'level' => 0];
        if (isset($children[$root['device_id']])) {
            foreach ($children[$root['device_id']] as $child) $tree_by_group[$group][] = ['item' => $child, 'level' => 1];
        }
    }
}
?>

<style>
.health-check-container { margin-top: 20px; }
.group-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-bottom: 25px; overflow: hidden; }
.group-card-header { background: #f8fafc; padding: 12px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.group-card-header h3 { margin: 0; font-size: 1rem; font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: 10px; }
.device-table { width: 100%; border-collapse: collapse; }
.device-table th { background: #f1f5f9; padding: 12px 15px; text-align: left; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; border-bottom: 1px solid #e2e8f0; }
.device-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.device-info { display: flex; align-items: flex-start; gap: 10px; }
.device-info.level-1 { padding-left: 20px; }
.tree-branch { color: #cbd5e1; font-size: 1rem; margin-top: 2px; }
.device-text strong { display: block; color: #1e293b; font-size: 0.9rem; margin-bottom: 2px; }
.device-text small { color: #94a3b8; font-size: 0.7rem; font-style: italic; display: block; }
.select-styled { width: 100%; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; background-color: #fff; transition: 0.2s; cursor: pointer; }
.status-inuse { color: #10b981; font-weight: 600; }
.status-notinuse { color: #ef4444; font-weight: 600; }
.input-styled { width: 100%; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; }
.input-styled:focus, .select-styled:focus { border-color: var(--primary-color); outline: none; box-shadow: 0 0 0 3px rgba(36, 162, 92, 0.1); }
</style>

<div class="page-header">
    <h2><i class="fas fa-edit"></i> Chỉnh sửa Phiếu Kiểm tra</h2>
    <div class="header-actions">
        <a href="index.php?page=it_activities/view_health_check&id=<?= $id ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <button type="submit" form="edit-health-check-form" name="update_health_check" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Cập nhật báo cáo</button>
    </div>
</div>

<form action="" method="POST" id="edit-health-check-form">
    <input type="hidden" name="update_health_check" value="1">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-light"><strong>Thông tin chung</strong></div>
                <div class="card-body">
                    <div class="form-group mb-3"><label>Dự án</label><select class="form-control bg-light" disabled><?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['ten_du_an']) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group mb-3"><label>Ngày kiểm tra <span class="text-danger">*</span></label><input type="date" name="check_date" value="<?= $check_date ?>" class="form-control" required></div>
                    
                    <hr>
                    <div class="form-group mb-3"><label>Đại diện dự án</label><input type="text" name="client_name" value="<?= htmlspecialchars($check['client_name'] ?? '') ?>" class="form-control" placeholder="Họ và tên người đại diện"></div>
                    <div class="form-group mb-3"><label>Chức vụ đại diện</label><input type="text" name="client_position" value="<?= htmlspecialchars($check['client_position'] ?? '') ?>" class="form-control" placeholder="VD: Trưởng BQL, Kế toán..."></div>
                    
                    <hr>
                    <div class="form-group mb-3">
                        <label>Người kiểm tra <span class="text-danger">*</span></label>
                        <select name="checked_by" class="form-control" required>
                            <?php foreach ($it_staff as $staff): ?>
                                <option value="<?= $staff['id'] ?>" <?= $check['checked_by'] == $staff['id'] ? 'selected' : '' ?>><?= htmlspecialchars($staff['fullname']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-3"><label>Chức vụ người kiểm tra</label><input type="text" name="checked_position" value="<?= htmlspecialchars($check['checked_position'] ?? 'IT') ?>" class="form-control" placeholder="VD: Nhân viên IT, Team Lead..."></div>
                    
                    <hr>
                    <div class="form-group mb-3"><label>Đánh giá tổng quát</label><input type="text" name="overall_health" value="<?= htmlspecialchars($check['overall_health'] ?? '') ?>" class="form-control" placeholder="VD: Tốt, ổn định / Cần lưu ý thay thế..."></div>
                    <div class="form-group"><label>Ghi chú tổng quan</label><textarea name="summary_notes" class="form-control" rows="4"><?= htmlspecialchars($check['summary_notes']) ?></textarea></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="health-check-container">
                <?php foreach ($tree_by_group as $group_name => $nodes): ?>
                    <div class="group-card">
                        <div class="group-card-header"><h3><i class="fas fa-layer-group"></i> Nhóm: <?= htmlspecialchars($group_name) ?></h3></div>
                        <table class="device-table">
                            <thead><tr><th width="35%">Thiết bị / Mã TS</th><th width="15%">Sử dụng</th><th width="15%">Sức khỏe</th><th width="8%">S.Lượng</th><th width="27%">Nguyên nhân & Ghi chú</th></tr></thead>
                            <tbody>
                                <?php foreach ($nodes as $node): 
                                    $d = $node['item']; $lvl = $node['level'];
                                ?>
                                    <tr class="<?= $lvl == 0 ? 'row-root' : 'row-child' ?>">
                                        <td>
                                            <div class="device-info level-<?= $lvl ?>"><?php if($lvl > 0): ?><span class="tree-branch">↳</span><?php endif; ?><div class="device-text"><strong><?= htmlspecialchars($d['ten_thiet_bi'] ?? '') ?></strong><small><?= htmlspecialchars($d['ma_tai_san'] ?? '') ?></small></div></div>
                                            <input type="hidden" name="device_ids[]" value="<?= $d['device_id'] ?>">
                                        </td>
                                        <td><select name="status[]" class="select-styled" onchange="updateStatusColor(this)"><option value="Đang sử dụng" <?= ($d['status'] ?? '') == 'Đang sử dụng' ? 'selected' : '' ?> class="status-inuse">Đang dùng</option><option value="Không sử dụng" <?= ($d['status'] ?? '') == 'Không sử dụng' ? 'selected' : '' ?> class="status-notinuse">Không dùng</option></select></td>
                                        <td><select name="health_status[]" class="select-styled"><option value="good" <?= ($d['health_status'] ?? '') == 'good' ? 'selected' : '' ?>>Tốt</option><option value="warning" <?= ($d['health_status'] ?? '') == 'warning' ? 'selected' : '' ?>>Cảnh báo</option><option value="broken" <?= ($d['health_status'] ?? '') == 'broken' ? 'selected' : '' ?>>Hỏng</option></select></td>
                                        <td><input type="number" name="quantity[]" value="<?= $d['quantity'] ?? 1 ?>" min="0" class="input-styled text-center"></td>
                                        <td><input type="text" name="cause[]" value="<?= htmlspecialchars($d['cause'] ?? '') ?>" class="input-styled mb-1" placeholder="Nguyên nhân..."><input type="text" name="notes[]" value="<?= htmlspecialchars($d['notes'] ?? '') ?>" class="input-styled" placeholder="Ghi chú..."></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</form>

<script>
function updateStatusColor(select) {
    select.classList.remove('status-inuse', 'status-notinuse');
    if (select.value === 'Đang sử dụng') select.classList.add('status-inuse');
    else if (select.value === 'Không sử dụng') select.classList.add('status-notinuse');
}
document.addEventListener('DOMContentLoaded', () => { document.querySelectorAll('select[name="status[]"]').forEach(updateStatusColor); });
</script>