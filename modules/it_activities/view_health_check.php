<?php
// modules/it_activities/view_health_check.php
$pageTitle = "Chi tiết Kiểm tra Hệ thống";

$id = $_GET['id'] ?? null;
if (!$id) {
    set_message("error", "Không tìm thấy mã báo cáo!");
    echo '<script>window.location.href = "index.php?page=it_activities/list";</script>';
    exit;
}

// Fetch main check info with project address
$stmt = $pdo->prepare("SELECT h.*, p.ten_du_an, p.dia_chi, u.fullname as checker_name 
                      FROM it_system_health_checks h
                      JOIN projects p ON h.project_id = p.id
                      JOIN users u ON h.checked_by = u.id
                      WHERE h.id = ?");
$stmt->execute([$id]);
$check = $stmt->fetch();

if (!$check) {
    set_message("error", "Báo cáo không tồn tại!");
    echo '<script>window.location.href = "index.php?page=it_activities/list";</script>';
    exit;
}

// Auto-generate signing token if missing
if (empty($check['signing_token'])) {
    $token = bin2hex(random_bytes(16));
    $pdo->prepare("UPDATE it_system_health_checks SET signing_token = ? WHERE id = ?")->execute([$token, $id]);
    $check['signing_token'] = $token;
}

// Fetch ALL devices for this project and JOIN with check details if they exist
$stmt = $pdo->prepare("SELECT 
                        dev.id as device_id, dev.ten_thiet_bi, dev.ma_tai_san, dev.nhom_thiet_bi, dev.parent_id,
                        d.status, d.health_status, d.quantity, d.cause, d.notes, d.id as detail_id
                      FROM devices dev
                      LEFT JOIN it_system_health_check_details d ON dev.id = d.device_id AND d.check_id = ?
                      WHERE dev.project_id = ? AND dev.deleted_at IS NULL
                      ORDER BY dev.nhom_thiet_bi, dev.parent_id ASC, dev.ten_thiet_bi");
$stmt->execute([$id, $check['project_id']]);
$details = $stmt->fetchAll();

// Handle Quick Add Device
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_add_device'])) {
    try {
        if (empty($_POST['q_ma_tai_san'])) throw new Exception("Mã tài sản không được để trống!");
        if (empty($_POST['q_ten_thiet_bi'])) throw new Exception("Tên thiết bị không được để trống!");

        $pdo->beginTransaction();

        // 1. Lấy trạng thái mặc định đầu tiên nếu có, nếu không dùng 'Tốt'
        $default_status_row = $pdo->query("SELECT status_name FROM settings_device_statuses ORDER BY id ASC LIMIT 1")->fetch();
        $default_status = $default_status_row ? $default_status_row['status_name'] : 'Tốt';

        // 2. Thêm vào bảng devices
        $stmt = $pdo->prepare("INSERT INTO devices (ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, ma_tai_san, project_id, parent_id, model, serial, trang_thai) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['q_ten_thiet_bi'], 
            $_POST['q_nhom_thiet_bi'], 
            $_POST['q_loai_thiet_bi'], 
            $_POST['q_ma_tai_san'], 
            $check['project_id'], 
            $_POST['q_parent_id'] ?: null, 
            $_POST['q_model'] ?: null, 
            $_POST['q_serial'] ?: null,
            $default_status
        ]);
        $new_device_id = $pdo->lastInsertId();

        // 3. Tự động thêm vào chi tiết báo cáo hiện tại (it_system_health_check_details)
        $stmt_detail = $pdo->prepare("INSERT INTO it_system_health_check_details (check_id, device_id, status, health_status, quantity, cause, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_detail->execute([
            $id,               // ID của báo cáo hiện tại
            $new_device_id,    // ID thiết bị vừa tạo
            'Đang sử dụng',    // Trạng thái sử dụng mặc định
            'good',            // Tình trạng sức khỏe mặc định (Tốt)
            1,                 // Số lượng mặc định
            '',                // Nguyên nhân (trống)
            'Vừa thêm mới'     // Ghi chú
        ]);

        $pdo->commit();

        set_message("success", "Đã thêm nhanh thiết bị và cập nhật vào báo cáo!");
        echo "<script>window.location.href = 'index.php?page=it_activities/view_health_check&id=$id';</script>";
        exit;
    } catch (Exception $e) { 
        if ($pdo->inTransaction()) $pdo->rollBack();
        set_message("error", "Lỗi: " . $e->getMessage()); 
    }
}

$db_groups = $pdo->query("SELECT group_name FROM settings_device_groups ORDER BY group_name ASC")->fetchAll(PDO::FETCH_COLUMN);
$db_types = $pdo->query("SELECT * FROM settings_device_types ORDER BY group_name, type_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$potential_parents = [];
if ($check['project_id']) {
    $stmt_p = $pdo->prepare("SELECT id, ten_thiet_bi, ma_tai_san FROM devices WHERE project_id = ? AND parent_id IS NULL AND deleted_at IS NULL ORDER BY ten_thiet_bi ASC");
    $stmt_p->execute([$check['project_id']]);
    $potential_parents = $stmt_p->fetchAll();
}

$tree_by_group = []; $roots = []; $children = [];
foreach ($details as $row) {
    if (!$row['parent_id']) $roots[$row['nhom_thiet_bi'] ?: 'Khác'][] = $row;
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

$health_labels = [
    'good' => '<span class="status-badge status-success">Tốt</span>', 
    'warning' => '<span class="status-badge status-warning">Cảnh báo</span>', 
    'broken' => '<span class="status-badge status-danger">Hỏng</span>',
    '' => '<span class="status-badge" style="background:#f1f5f9; color:#94a3b8;">Chưa kiểm tra</span>'
];
$overall_health_labels = ['good' => '<span class="health-pill health-good"><i class="fas fa-check-circle"></i> TỐT</span>', 'warning' => '<span class="health-pill health-warning"><i class="fas fa-exclamation-triangle"></i> CẢNH BÁO</span>', 'critical' => '<span class="health-pill health-critical"><i class="fas fa-radiation"></i> KHẨN CẤP</span>'];
?>

<style>
.view-container { margin-top: 20px; }
.summary-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; position: sticky; top: 20px; }
.summary-header { background: #f8fafc; padding: 15px 20px; border-bottom: 1px solid #e2e8f0; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 10px; }
.summary-body { padding: 20px; }
.info-item { margin-bottom: 15px; display: flex; flex-direction: column; gap: 4px; }
.info-item label { font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; }
.info-item span { font-size: 0.95rem; color: #1e293b; font-weight: 500; }
.summary-notes { background: #f1f5f9; padding: 12px; border-radius: 8px; font-size: 0.85rem; color: #475569; line-height: 1.5; margin-top: 10px; }
.group-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-bottom: 25px; overflow: hidden; }
.group-card-header { background: #f8fafc; padding: 12px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.group-card-header h3 { margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: 10px; }
.device-table { width: 100%; border-collapse: collapse; }
.device-table th { background: #f1f5f9; padding: 12px 15px; text-align: left; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; }
.device-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.row-child { background-color: #fcfdfd; }
.device-info { display: flex; align-items: flex-start; gap: 10px; }
.device-info.level-1 { padding-left: 20px; }
.tree-branch { color: #cbd5e1; font-size: 1rem; margin-top: 2px; }
.device-text strong { display: block; color: #1e293b; font-size: 0.9rem; margin-bottom: 2px; }
.device-text small { color: #94a3b8; font-size: 0.7rem; font-style: italic; display: block; }
.row-child .device-text strong { color: #475569; font-weight: 500; }
.status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
.status-success { background: #dcfce7; color: #166534; }
.status-danger { background: #fee2e2; color: #991b1b; }
.status-warning { background: #fffbeb; color: #92400e; }
.health-pill { padding: 6px 12px; border-radius: 6px; font-weight: 800; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 6px; }
.health-good { background: #10b981; color: #fff; }
.health-warning { background: #f59e0b; color: #fff; }
.health-critical { background: #ef4444; color: #fff; }
.text-note { color: #64748b; font-size: 0.85rem; line-height: 1.4; }
.text-cause { color: #ef4444; font-weight: 600; font-size: 0.8rem; display: block; margin-top: 4px; }
.signature-area { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px; }
.signature-box { border: 2px dashed #e2e8f0; border-radius: 12px; padding: 20px; text-align: center; background: #fff; }
.signature-title { font-weight: 800; font-size: 0.8rem; color: #64748b; text-transform: uppercase; margin-bottom: 15px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; }
.signature-img { max-width: 200px; max-height: 100px; margin: 10px auto; display: block; }
.no-signature { padding: 20px; color: #cbd5e1; font-style: italic; font-size: 0.85rem; }
.signer-name { font-weight: 700; margin-top: 10px; color: #1e293b; }

/* Modal Styles */
.modal-custom { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
.modal-content-custom { background: #fff; margin: 5vh auto; width: 650px; max-width: 95%; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; animation: modalPop 0.25s; }
@keyframes modalPop { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.modal-header-custom { background: var(--primary-color); color: #fff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
.modal-body-custom { padding: 25px; }
.modal-footer-custom { padding: 15px 25px; border-top: 1px solid #e2e8f0; text-align: right; background: #f8fafc; }
.form-group-custom label { display: block; margin-bottom: 6px; font-weight: 600; color: var(--text-color); font-size: 0.85rem; }
.form-control-custom { width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.9rem; }
.type-picker-wrapper { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px; max-height: 140px; overflow-y: auto; }
.type-list { display: flex; flex-wrap: wrap; gap: 8px; }
.type-pill { padding: 5px 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; font-size: 0.75rem; font-weight: 500; color: #475569; cursor: pointer; transition: 0.2s; }
.type-pill:hover { border-color: var(--primary-color); color: var(--primary-color); background: #f0fdf4; }
.type-pill.active { background: var(--primary-color); color: #fff; border-color: var(--primary-color); }
</style>

<div class="page-header">
    <h2><i class="fas fa-file-invoice"></i> Báo cáo Kiểm tra Hệ thống</h2>
    <div class="header-actions">
        <a href="index.php?page=it_activities/list" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <button type="button" class="btn btn-primary btn-sm" onclick="openQuickAddModal()"><i class="fas fa-bolt"></i> THÊM NHANH</button>
        <a href="confirm_health_check.php?token=<?= $check['signing_token'] ?>" class="btn btn-success btn-sm" target="_blank"><i class="fas fa-signature"></i> KÝ XÁC NHẬN</a>
        <a href="index.php?page=it_activities/edit_health_check&id=<?= $id ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> CHỈNH SỬA</a>
        <a href="index.php?page=it_activities/print&id=<?= $id ?>" class="btn btn-dark btn-sm" target="_blank"><i class="fas fa-print"></i> IN BÁO CÁO</a>
        <a href="index.php?page=it_activities/export_daily&id=<?= $id ?>" class="btn btn-secondary btn-sm" target="_blank"><i class="fas fa-file-export"></i> XUẤT EXCEL</a>
        <a href="#" data-url="index.php?page=it_activities/delete_health_check&id=<?= $id ?>" class="btn btn-danger btn-sm delete-btn"><i class="fas fa-trash-alt"></i> XÓA BÁO CÁO</a>
    </div>
</div>

<div class="row view-container">
    <div class="col-md-3">
        <div class="summary-card">
            <div class="summary-header"><i class="fas fa-info-circle"></i> THÔNG TIN CHUNG</div>
            <div class="summary-body">
                <div class="info-item"><label>Dự án</label><span><?= htmlspecialchars($check['ten_du_an'] ?? '') ?></span></div>
                <div class="info-item"><label>Địa chỉ</label><span><?= htmlspecialchars($check['dia_chi'] ?? '-') ?></span></div>
                <div class="info-item"><label>Ngày thực hiện</label><span><?= date('d/m/Y', strtotime($check['check_date'])) ?></span></div>
                <div class="info-item"><label>Người kiểm tra</label><span><?= htmlspecialchars($check['checker_name'] ?? '') ?></span></div>
                <div class="info-item"><label>Đánh giá tổng quát</label><div><?= $overall_health_labels[$check['overall_health']] ?? $check['overall_health'] ?></div></div>
                <?php if($check['summary_notes']): ?><div class="info-item"><label>Ghi chú hệ thống</label><div class="summary-notes"><?= nl2br(htmlspecialchars($check['summary_notes'] ?? '')) ?></div></div><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <?php foreach ($tree_by_group as $group_name => $nodes): ?>
            <div class="group-card">
                <div class="group-card-header"><h3><i class="fas fa-layer-group"></i> NHÓM: <?= htmlspecialchars($group_name ?? '') ?></h3></div>
                <table class="device-table">
                    <thead><tr><th width="35%">Thiết bị / Mã TS</th><th width="15%">Sử dụng</th><th width="15%">Sức khỏe</th><th width="8%">S.Lượng</th><th width="27%">Nguyên nhân & Ghi chú</th></tr></thead>
                    <tbody>
                        <?php foreach ($nodes as $node): 
                            $item = $node['item']; $lvl = $node['level'];
                        ?>
                            <tr class="<?= $lvl > 0 ? 'row-child' : '' ?>">
                                <td>
                                    <div class="device-info level-<?= $lvl ?>"><?php if($lvl > 0): ?><span class="tree-branch">↳</span><?php endif; ?><div class="device-text"><strong><?= htmlspecialchars($item['ten_thiet_bi'] ?? '') ?></strong><small><?= htmlspecialchars($item['ma_tai_san'] ?? '') ?></small></div></div>
                                </td>
                                <td><span class="status-badge <?= ($item['status'] === 'Đang sử dụng') ? 'status-success' : 'status-danger' ?>"><?= htmlspecialchars($item['status'] ?? 'Chưa kiểm tra') ?></span></td>
                                <td><?= $health_labels[$item['health_status'] ?? ''] ?></td>
                                <td style="font-weight: 700; color: #1e293b;"><?= $item['quantity'] ?? '-' ?></td>
                                <td>
                                    <?php if($item['cause'] || $item['notes']): ?>
                                        <?php if($item['cause']): ?><span class="text-cause"><i class="fas fa-bug"></i> <?= htmlspecialchars($item['cause'] ?? '') ?></span><?php endif; ?>
                                        <?php if($item['notes']): ?><div class="text-note"><?= htmlspecialchars($item['notes'] ?? '') ?></div><?php endif; ?>
                                    <?php else: ?><span class="text-muted" style="font-size: 0.75rem; font-style: italic;">Bình thường</span><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
        <div class="signature-area">
            <div class="signature-box">
                <div class="signature-title">NHÂN VIÊN KIỂM TRA (IT)</div>
                <?php if($check['it_signature']): ?><img src="<?= $check['it_signature'] ?>" class="signature-img" alt="IT Signature"><div class="signer-name"><?= htmlspecialchars($check['checker_name'] ?? '') ?></div><?php else: ?><div class="no-signature">Chưa ký xác nhận</div><?php endif; ?>
            </div>
            <div class="signature-box">
                <div class="signature-title">CÁN BỘ TẠI DỰ ÁN</div>
                <?php if($check['client_signature']): ?><img src="<?= $check['client_signature'] ?>" class="signature-img" alt="Client Signature"><div class="signer-name"><?= htmlspecialchars($check['client_name'] ?? '') ?></div><?php else: ?><div class="no-signature">Chưa ký xác nhận</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- QUICK ADD DEVICE MODAL -->
<div id="quickAddModal" class="modal-custom">
    <div class="modal-content-custom">
        <div class="modal-header-custom"><h3><i class="fas fa-bolt"></i> Thêm nhanh Thiết bị</h3><span class="close-modal" style="cursor:pointer; font-size:1.5rem;" onclick="closeQuickAddModal()">&times;</span></div>
        <form action="" method="POST" id="quick-add-form">
            <input type="hidden" name="quick_add_device" value="1">
            <input type="hidden" name="project_id" id="q_project_id" value="<?= $check['project_id'] ?>">
            <div class="modal-body-custom">
                <div class="row">
                    <div class="col-md-5 mb-3"><div class="form-group-custom"><label>Nhóm</label><select name="q_nhom_thiet_bi" id="q_nhom_thiet_bi" class="form-control-custom" onchange="filterQuickTypes();"><?php foreach ($db_groups as $group): ?><option value="<?= htmlspecialchars($group) ?>"><?= htmlspecialchars($group) ?></option><?php endforeach; ?></select></div></div>
                    <div class="col-md-7 mb-3"><div class="form-group-custom"><label>Loại thiết bị <span class="text-danger">*</span></label><select name="q_loai_thiet_bi" id="q_loai_thiet_bi" class="form-control-custom" required onchange="callGenerateAssetCodeAPI();"></select></div></div>
                </div>
                <div class="form-group-custom mb-3"><label>Tên thiết bị <span class="text-danger">*</span></label><input type="text" name="q_ten_thiet_bi" id="q_ten_thiet_bi" class="form-control-custom" required placeholder="VD: Máy tính chị Yến"></div>
                <div class="form-group-custom mb-3"><label>Mã Tài sản (Tự sinh)</label><div style="display: flex; gap: 5px;"><input type="text" name="q_ma_tai_san" id="q_ma_tai_san" class="form-control-custom" style="background:#f1f5f9;" readonly><button type="button" class="btn btn-secondary btn-sm" onclick="callGenerateAssetCodeAPI()"><i class="fas fa-sync"></i></button></div></div>
                <div class="form-group-custom mb-3"><label>Thiết bị gốc (Cha)</label><select name="q_parent_id" class="form-control-custom"><option value="">-- Là thiết bị gốc --</option><?php foreach ($potential_parents as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['ma_tai_san']) ?> - <?= htmlspecialchars($p['ten_thiet_bi']) ?></option><?php endforeach; ?></select></div>
                <div class="row"><div class="col-md-6 mb-3"><div class="form-group-custom"><label>Model</label><input type="text" name="q_model" class="form-control-custom"></div></div><div class="col-md-6 mb-3"><div class="form-group-custom"><label>Serial</label><input type="text" name="q_serial" class="form-control-custom"></div></div></div>
            </div>
            <div class="modal-footer-custom"><button type="button" class="btn btn-secondary" onclick="closeQuickAddModal()">Hủy</button><button type="submit" class="btn btn-primary">LƯU THIẾT BỊ</button></div>
        </form>
    </div>
</div>

<script>
const allTypes = <?= json_encode($db_types) ?>;
function openQuickAddModal() { document.getElementById('quickAddModal').style.display = 'block'; filterQuickTypes(); }
function closeQuickAddModal() { document.getElementById('quickAddModal').style.display = 'none'; }
function filterQuickTypes() {
    const group = document.getElementById('q_nhom_thiet_bi').value;
    const typeSelect = document.getElementById('q_loai_thiet_bi');
    const assetInput = document.getElementById('q_ma_tai_san');
    
    typeSelect.innerHTML = '<option value="">-- Chọn loại --</option>';
    assetInput.value = '';
    
    const filtered = allTypes.filter(t => t.group_name === group);
    filtered.forEach(t => {
        const opt = document.createElement('option');
        opt.value = t.type_name;
        opt.innerText = t.type_name;
        typeSelect.appendChild(opt);
    });
}
function callGenerateAssetCodeAPI() {
    const projectId = document.getElementById('q_project_id').value; const groupName = document.getElementById('q_nhom_thiet_bi').value; const typeName = document.getElementById('q_loai_thiet_bi').value; const assetInput = document.getElementById('q_ma_tai_san');
    if (!projectId || !groupName || !typeName) return;
    assetInput.value = 'Đang tạo...';
    fetch(`api/generate_asset_code.php?project_id=${projectId}&group_name=${encodeURIComponent(groupName)}&type_name=${encodeURIComponent(typeName)}`)
        .then(response => response.json()).then(data => { assetInput.value = data.code || 'Lỗi!'; })
        .catch(() => { assetInput.value = 'Lỗi kết nối!'; });
}
window.onclick = (e) => { if (e.target == document.getElementById('quickAddModal')) closeQuickAddModal(); }
</script>