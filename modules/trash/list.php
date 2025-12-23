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
    <div class="dashboard-card-header trash-header-actions">
        <h3><i class="fas <?php echo $icon; ?>"></i> Danh sách <?php echo $title; ?> đã xóa</h3>
        <?php if (!empty($data)): ?>
            <div class="bulk-trash-btns">
                <a href="index.php?page=trash/bulk_action&type=<?php echo $type; ?>&action=restore_all" class="btn-bulk btn-bulk-restore" data-mobile-text="Khôi phục hết">
                    <i class="fas fa-undo-alt"></i> <span>Khôi phục tất cả</span>
                </a>
                <a href="#" data-url="index.php?page=trash/bulk_action&type=<?php echo $type; ?>&action=empty_trash" class="btn-bulk btn-bulk-delete delete-btn" data-mobile-text="Xóa sạch mục">
                    <i class="fas fa-trash-alt"></i> <span>Dọn sạch mục này</span>
                </a>
            </div>
        <?php endif; ?>
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

<style>
.trash-header-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    padding-bottom: 15px;
}
.bulk-trash-btns {
    display: flex;
    gap: 12px;
}
.btn-bulk {
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    color: white !important;
    text-decoration: none !important;
    cursor: pointer;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
.btn-bulk i {
    font-size: 1rem;
}
.btn-bulk-restore {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}
.btn-bulk-delete {
    background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
}
.btn-bulk:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    filter: brightness(1.1);
    color: white !important;
}
.btn-bulk:active {
    transform: translateY(0);
}

@media (max-width: 768px) {
    .trash-header-actions {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    .bulk-trash-btns {
        width: 100%;
        flex-direction: row;
    }
    .btn-bulk {
        flex: 1;
        justify-content: center;
        padding: 12px 10px;
        font-size: 0.8rem;
    }
    .btn-bulk span {
        display: none;
    }
    .btn-bulk::after {
        content: attr(data-mobile-text);
    }
    .trash-tabs .btn {
        min-width: 120px !important;
    }
}
</style>