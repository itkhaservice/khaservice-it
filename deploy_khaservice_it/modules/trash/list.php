<?php
// modules/trash/list.php

$type = $_GET['type'] ?? 'maintenance';
$allowed_types = ['maintenance', 'devices', 'projects', 'services', 'suppliers', 'users'];
if (!in_array($type, $allowed_types)) $type = 'maintenance';

$data = [];
$title = "";
$icon = "";

try {
    switch ($type) {
        case 'maintenance':
            $title = "Phiếu Công tác"; $icon = "fa-history";
            $stmt = $pdo->query("SELECT ml.id, ml.ngay_su_co as label, d.ma_tai_san as sublabel, ml.deleted_at FROM maintenance_logs ml LEFT JOIN devices d ON ml.device_id = d.id WHERE ml.deleted_at IS NOT NULL ORDER BY ml.deleted_at DESC");
            $data = $stmt->fetchAll();
            break;
        case 'devices':
            $title = "Thiết bị"; $icon = "fa-server";
            $stmt = $pdo->query("SELECT id, ten_thiet_bi as label, ma_tai_san as sublabel, deleted_at FROM devices WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
            $data = $stmt->fetchAll();
            break;
        case 'projects':
            $title = "Dự án"; $icon = "fa-building";
            $stmt = $pdo->query("SELECT id, ten_du_an as label, ma_du_an as sublabel, deleted_at FROM projects WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
            $data = $stmt->fetchAll();
            break;
        case 'services':
            $title = "Dịch vụ"; $icon = "fa-cloud";
            $stmt = $pdo->query("SELECT id, ten_dich_vu as label, loai_dich_vu as sublabel, deleted_at FROM services WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
            $data = $stmt->fetchAll();
            break;
        case 'suppliers':
            $title = "Nhà cung cấp"; $icon = "fa-truck";
            $stmt = $pdo->query("SELECT id, ten_npp as label, nguoi_lien_he as sublabel, deleted_at FROM suppliers WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
            $data = $stmt->fetchAll();
            break;
        case 'users':
            $title = "Người dùng"; $icon = "fa-users";
            $stmt = $pdo->query("SELECT id, fullname as label, username as sublabel, deleted_at FROM users WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
            $data = $stmt->fetchAll();
            break;
    }
} catch (PDOException $e) {
    set_message('error', 'Lỗi tải dữ liệu thùng rác: ' . $e->getMessage());
}
?>

<div class="page-header">
    <h2><i class="fas fa-trash-restore"></i> Thùng rác hệ thống</h2>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-home"></i> Dashboard</a>
    </div>
</div>

<div class="trash-tabs card" style="padding: 10px; display: flex; gap: 10px; margin-bottom: 20px; overflow-x: auto;">
    <?php 
    $tab_labels = [
        'maintenance' => ['label' => 'Công tác', 'icon' => 'fa-history'],
        'devices'     => ['label' => 'Thiết bị', 'icon' => 'fa-server'],
        'projects'    => ['label' => 'Dự án', 'icon' => 'fa-building'],
        'services'    => ['label' => 'Dịch vụ', 'icon' => 'fa-cloud'],
        'suppliers'   => ['label' => 'Nhà cung cấp', 'icon' => 'fa-truck'],
        'users'       => ['label' => 'Người dùng', 'icon' => 'fa-users']
    ];
    foreach ($tab_labels as $key => $tab): 
    ?>
        <a href="index.php?page=trash/list&type=<?php echo $key; ?>" class="btn <?php echo ($type === $key) ? 'btn-primary' : 'btn-secondary'; ?>" style="flex: 1; min-width: 150px;">
            <i class="fas <?php echo $tab['icon']; ?>"></i> <?php echo $tab['label']; ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="dashboard-card-header">
        <h3><i class="fas <?php echo $icon; ?>"></i> Danh sách <?php echo $title; ?> đã xóa</h3>
    </div>

    <?php if (empty($data)): ?>
        <div style="text-align: center; padding: 50px; color: #94a3b8;">
            <i class="fas fa-trash-alt" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
            <p>Thùng rác trống.</p>
        </div>
    <?php else: ?>
        <div class="table-container" style="border:none; box-shadow:none;">
            <table class="content-table">
                <thead>
                    <tr>
                        <th>Thông tin mục đã xóa</th>
                        <th>Thời gian xóa</th>
                        <th width="200" class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $item): ?>
                        <tr>
                            <td>
                                <div class="font-bold"><?php echo htmlspecialchars($item['label']); ?></div>
                                <div class="text-muted small"><?php echo htmlspecialchars($item['sublabel']); ?></div>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($item['deleted_at'])); ?></td>
                            <td class="actions text-center">
                                <a href="index.php?page=trash/restore&type=<?php echo $type; ?>&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-secondary" style="color: #166534; border-color: #bbf7d0; background: #f0fdf4;">
                                    <i class="fas fa-undo"></i> Khôi phục
                                </a>
                                <a href="#" data-url="index.php?page=trash/permanent_delete&type=<?php echo $type; ?>&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger delete-btn">
                                    <i class="fas fa-times-circle"></i> Xóa vĩnh viễn
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
