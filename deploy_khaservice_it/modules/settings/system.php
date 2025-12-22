<?php
// modules/settings/system.php

if (!isAdmin()) {
    set_message('error', 'Chỉ Admin mới có quyền truy cập trang này.');
    header("Location: index.php");
    exit;
}

// XỬ LÝ ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // --- LOẠI THIẾT BỊ ---
        if ($action === 'add_type') {
            $name = trim($_POST['type_name']);
            $group = $_POST['group_name'];
            if ($name) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO settings_device_types (type_name, group_name) VALUES (?, ?)");
                $stmt->execute([$name, $group]);
                set_message('success', 'Đã thêm loại thiết bị mới.');
            }
        } elseif ($action === 'delete_type') {
            $id = $_POST['id'];
            $pdo->prepare("DELETE FROM settings_device_types WHERE id = ?")->execute([$id]);
            set_message('success', 'Đã xóa loại thiết bị.');
        }

        // --- TÌNH TRẠNG ---
        elseif ($action === 'add_status') {
            $name = trim($_POST['status_name']);
            $color = $_POST['color_class'];
            if ($name) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO settings_device_statuses (status_name, color_class) VALUES (?, ?)");
                $stmt->execute([$name, $color]);
                set_message('success', 'Đã thêm tình trạng mới.');
            }
        } elseif ($action === 'delete_status') {
            $id = $_POST['id'];
            $pdo->prepare("DELETE FROM settings_device_statuses WHERE id = ?")->execute([$id]);
            set_message('success', 'Đã xóa tình trạng.');
        }
        
        header("Location: index.php?page=settings/system");
        exit;
    } catch (PDOException $e) {
        set_message('error', 'Lỗi: ' . $e->getMessage());
    }
}

// FETCH DATA
$types = $pdo->query("SELECT * FROM settings_device_types ORDER BY group_name, type_name")->fetchAll();
$statuses = $pdo->query("SELECT * FROM settings_device_statuses ORDER BY status_name")->fetchAll();
?>

<div class="page-header">
    <h2><i class="fas fa-cogs"></i> Cấu hình Hệ thống</h2>
</div>

<div class="settings-grid-container">
    <!-- PHẦN 1: QUẢN LÝ LOẠI THIẾT BỊ -->
    <div class="settings-card-wrapper">
        <div class="card h-full">
            <div class="dashboard-card-header">
                <h3><i class="fas fa-microchip"></i> Loại thiết bị & Phân nhóm</h3>
            </div>
            
            <form action="index.php?page=settings/system" method="POST" class="settings-add-form inline">
                <input type="hidden" name="action" value="add_type">
                <div class="form-group flex-2">
                    <label>Tên Loại mới</label>
                    <input type="text" name="type_name" placeholder="VD: Máy Scan..." required>
                </div>
                <div class="form-group flex-1">
                    <label>Phân nhóm</label>
                    <select name="group_name">
                        <option value="Văn phòng">Văn phòng</option>
                        <option value="Bãi xe">Bãi xe</option>
                        <option value="An ninh / Camera">An ninh / Camera</option>
                        <option value="Hạ tầng mạng">Hạ tầng mạng</option>
                        <option value="Linh kiện">Linh kiện</option>
                        <option value="Khác">Khác</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-add-sm"><i class="fas fa-plus"></i></button>
            </form>

            <div class="table-container mt-20">
                <table class="content-table">
                    <thead>
                        <tr>
                            <th>Tên Loại</th>
                            <th>Nhóm</th>
                            <th width="40"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($types as $t): ?>
                            <tr>
                                <td class="font-bold"><?php echo htmlspecialchars($t['type_name']); ?></td>
                                <td><span class="badge status-info"><?php echo htmlspecialchars($t['group_name']); ?></span></td>
                                <td class="text-right">
                                    <form action="index.php?page=settings/system" method="POST" onsubmit="return confirm('Xóa loại này?')">
                                        <input type="hidden" name="action" value="delete_type">
                                        <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                        <button type="submit" class="btn-icon text-danger"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- PHẦN 2: QUẢN LÝ TÌNH TRẠNG -->
    <div class="settings-card-wrapper">
        <div class="card h-full">
            <div class="dashboard-card-header">
                <h3><i class="fas fa-info-circle"></i> Trạng thái thiết bị</h3>
            </div>

            <form action="index.php?page=settings/system" method="POST" class="settings-add-form vertical">
                <input type="hidden" name="action" value="add_status">
                <div class="form-group">
                    <label>Tên Trạng thái</label>
                    <input type="text" name="status_name" placeholder="VD: Chờ sửa..." required>
                </div>
                <div class="form-group">
                    <label>Màu hiển thị</label>
                    <select name="color_class">
                        <option value="status-active">Xanh lá (Tốt)</option>
                        <option value="status-error">Đỏ (Lỗi/Hỏng)</option>
                        <option value="status-warning">Vàng (Cảnh báo)</option>
                        <option value="status-info">Xanh dương (Thông tin)</option>
                        <option value="status-default">Xám (Mặc định)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-full"><i class="fas fa-plus"></i> Thêm trạng thái</button>
            </form>

            <div class="table-container mt-20">
                <table class="content-table">
                    <thead>
                        <tr>
                            <th>Tên trạng thái</th>
                            <th width="40"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($statuses as $s): ?>
                            <tr>
                                <td><span class="badge <?php echo $s['color_class']; ?>"><?php echo htmlspecialchars($s['status_name']); ?></span></td>
                                <td class="text-right">
                                    <form action="index.php?page=settings/system" method="POST" onsubmit="return confirm('Xóa trạng thái này?')">
                                        <input type="hidden" name="action" value="delete_status">
                                        <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                                        <button type="submit" class="btn-icon text-danger"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.settings-grid-container {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    align-items: stretch;
}

.settings-card-wrapper {
    flex: 1;
    min-width: 300px;
    max-width: 100%;
}

.h-full { height: 100%; }
.w-full { width: 100%; height: 42px; }

.settings-add-form.inline {
    display: flex;
    gap: 10px;
    align-items: flex-end;
}

.settings-add-form.vertical {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.form-group.flex-2 { flex: 2; }
.form-group.flex-1 { flex: 1; }
.btn-add-sm { height: 42px; min-width: 42px; }

@media (max-width: 992px) {
    .settings-card-wrapper {
        min-width: 100%;
    }
}

@media (max-width: 768px) {
    .settings-add-form.inline {
        flex-direction: column;
        align-items: stretch;
    }
    .btn-add-sm {
        width: 100%;
    }
}
</style>