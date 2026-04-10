<?php
// modules/it_activities/view_health_check.php
$pageTitle = "Chi tiết Kiểm tra Hệ thống";

$id = $_GET['id'] ?? null;
if (!$id) {
    set_message("error", "Không tìm thấy mã báo cáo!");
    echo '<script>window.location.href = "index.php?page=it_activities/list";</script>';
    exit;
}

// Fetch main check info
$stmt = $pdo->prepare("SELECT h.*, p.ten_du_an, p.ma_du_an, u.fullname as checker_name 
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

// Fetch details and Build Tree
$stmt = $pdo->prepare("SELECT d.*, dev.ten_thiet_bi, dev.ma_tai_san, dev.nhom_thiet_bi, dev.parent_id
                      FROM it_system_health_check_details d
                      JOIN devices dev ON d.device_id = dev.id
                      WHERE d.check_id = ?
                      ORDER BY dev.nhom_thiet_bi, dev.parent_id ASC, dev.ten_thiet_bi");
$stmt->execute([$id]);
$details = $stmt->fetchAll();

$tree_by_group = [];
$roots = [];
$children = [];
foreach ($details as $row) {
    if (!$row['parent_id']) {
        $roots[$row['nhom_thiet_bi'] ?: 'Khác'][] = $row;
    } else {
        $children[$row['parent_id']][] = $row;
    }
}

foreach ($roots as $group => $root_list) {
    foreach ($root_list as $root) {
        $tree_by_group[$group][] = ['item' => $root, 'level' => 0];
        if (isset($children[$root['device_id']])) {
            foreach ($children[$root['device_id']] as $child) {
                $tree_by_group[$group][] = ['item' => $child, 'level' => 1];
            }
        }
    }
}

$health_labels = [
    'good' => '<span class="status-badge status-success">Tốt</span>',
    'warning' => '<span class="status-badge status-warning">Cảnh báo</span>',
    'broken' => '<span class="status-badge status-danger">Hỏng</span>'
];

$overall_health_labels = [
    'good' => '<span class="health-pill health-good"><i class="fas fa-check-circle"></i> TỐT</span>',
    'warning' => '<span class="health-pill health-warning"><i class="fas fa-exclamation-triangle"></i> CẢNH BÁO</span>',
    'critical' => '<span class="health-pill health-critical"><i class="fas fa-radiation"></i> KHẨN CẤP</span>'
];
?>

<style>
/* --- Detailed View UI --- */
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

/* Tree Styling */
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
</style>

<div class="page-header">
    <h2><i class="fas fa-file-invoice"></i> Báo cáo Kiểm tra: <?= htmlspecialchars($check['ten_du_an']) ?></h2>
    <div class="header-actions">
        <a href="index.php?page=it_activities/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <a href="index.php?page=it_activities/edit_health_check&id=<?= $id ?>" class="btn btn-primary"><i class="fas fa-edit"></i> CHỈNH SỬA</a>
        <a href="index.php?page=it_activities/export_daily&id=<?= $id ?>" class="btn btn-secondary btn-sm" target="_blank"><i class="fas fa-file-export"></i> XUẤT EXCEL</a>
        <a href="#" data-url="index.php?page=it_activities/delete_health_check&id=<?= $id ?>" class="btn btn-danger delete-btn"><i class="fas fa-trash-alt"></i> XÓA BÁO CÁO</a>
    </div>
</div>

<div class="row view-container">
    <div class="col-md-3">
        <div class="summary-card">
            <div class="summary-header"><i class="fas fa-info-circle"></i> THÔNG TIN CHUNG</div>
            <div class="summary-body">
                <div class="info-item"><label>Dự án</label><span><?= htmlspecialchars($check['ten_du_an']) ?></span></div>
                <div class="info-item"><label>Ngày thực hiện</label><span><?= date('d/m/Y', strtotime($check['check_date'])) ?></span></div>
                <div class="info-item"><label>Người kiểm tra</label><span><?= htmlspecialchars($check['checker_name']) ?></span></div>
                <div class="info-item"><label>Đánh giá tổng quát</label><div><?= $overall_health_labels[$check['overall_health']] ?? $check['overall_health'] ?></div></div>
                <?php if($check['summary_notes']): ?><div class="info-item"><label>Ghi chú hệ thống</label><div class="summary-notes"><?= nl2br(htmlspecialchars($check['summary_notes'])) ?></div></div><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <?php foreach ($tree_by_group as $group_name => $nodes): ?>
            <div class="group-card">
                <div class="group-card-header"><h3><i class="fas fa-layer-group"></i> NHÓM: <?= htmlspecialchars($group_name) ?></h3></div>
                <table class="device-table">
                    <thead><tr><th width="35%">Thiết bị / Mã TS</th><th width="15%">Sử dụng</th><th width="15%">Sức khỏe</th><th width="8%">S.Lượng</th><th width="27%">Nguyên nhân & Ghi chú</th></tr></thead>
                    <tbody>
                        <?php foreach ($nodes as $node): 
                            $item = $node['item']; $lvl = $node['level'];
                        ?>
                            <tr class="<?= $lvl > 0 ? 'row-child' : '' ?>">
                                <td>
                                    <div class="device-info level-<?= $lvl ?>">
                                        <?php if($lvl > 0): ?><span class="tree-branch">↳</span><?php endif; ?>
                                        <div class="device-text"><strong><?= htmlspecialchars($item['ten_thiet_bi']) ?></strong><small><?= htmlspecialchars($item['ma_tai_san']) ?></small></div>
                                    </div>
                                </td>
                                <td><span class="status-badge <?= ($item['status'] === 'Đang sử dụng') ? 'status-success' : 'status-danger' ?>"><?= htmlspecialchars($item['status']) ?></span></td>
                                <td><?= $health_labels[$item['health_status']] ?? $item['health_status'] ?></td>
                                <td style="font-weight: 700; color: #1e293b;"><?= $item['quantity'] ?></td>
                                <td>
                                    <?php if($item['cause'] || $item['notes']): ?>
                                        <?php if($item['cause']): ?><span class="text-cause"><i class="fas fa-bug"></i> <?= htmlspecialchars($item['cause']) ?></span><?php endif; ?>
                                        <?php if($item['notes']): ?><div class="text-note"><?= htmlspecialchars($item['notes']) ?></div><?php endif; ?>
                                    <?php else: ?><span class="text-muted" style="font-size: 0.75rem; font-style: italic;">Bình thường</span><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
</div>