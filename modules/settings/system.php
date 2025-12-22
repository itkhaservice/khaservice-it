<?php
// modules/settings/system.php

if (!isAdmin()) {
    set_message('error', 'Chỉ Admin mới có quyền truy cập trang này.');
    header("Location: index.php");
    exit;
}

// AUTO-MIGRATE: Tạo bảng settings_device_groups nếu chưa có
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings_device_groups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_name VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) { /* Silent fail */ }

// XỬ LÝ ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add_group') {
            $name = trim($_POST['group_name']);
            if ($name) {
                $pdo->prepare("INSERT IGNORE INTO settings_device_groups (group_name) VALUES (?)")->execute([$name]);
                set_message('success', 'Đã thêm phân nhóm mới.');
            }
        } elseif ($action === 'delete_group') {
            $pdo->prepare("DELETE FROM settings_device_groups WHERE id = ?")->execute([$_POST['id']]);
            set_message('success', 'Đã xóa phân nhóm.');
        } elseif ($action === 'add_type') {
            $name = trim($_POST['type_name']);
            $group = $_POST['group_name'];
            if ($name) {
                $pdo->prepare("INSERT IGNORE INTO settings_device_types (type_name, group_name) VALUES (?, ?)")->execute([$name, $group]);
                set_message('success', 'Đã thêm loại thiết bị mới.');
            }
        } elseif ($action === 'delete_type') {
            $pdo->prepare("DELETE FROM settings_device_types WHERE id = ?")->execute([$_POST['id']]);
            set_message('success', 'Đã xóa loại thiết bị.');
        } elseif ($action === 'add_status') {
            $name = trim($_POST['status_name']);
            $color = $_POST['color_class'];
            if ($name) {
                $pdo->prepare("INSERT IGNORE INTO settings_device_statuses (status_name, color_class) VALUES (?, ?)")->execute([$name, $color]);
                set_message('success', 'Đã thêm tình trạng mới.');
            }
        } elseif ($action === 'delete_status') {
            $pdo->prepare("DELETE FROM settings_device_statuses WHERE id = ?")->execute([$_POST['id']]);
            set_message('success', 'Đã xóa tình trạng.');
        }
        header("Location: index.php?page=settings/system");
        exit;
    } catch (PDOException $e) { set_message('error', 'Lỗi: ' . $e->getMessage()); }
}

// FETCH DATA
$groups = $pdo->query("SELECT * FROM settings_device_groups ORDER BY group_name")->fetchAll();
$types = $pdo->query("SELECT * FROM settings_device_types ORDER BY group_name, type_name")->fetchAll();
$statuses = $pdo->query("SELECT * FROM settings_device_statuses ORDER BY status_name")->fetchAll();
?>

<div class="page-header">
    <h2><i class="fas fa-cogs"></i> Cấu hình Hệ thống</h2>
</div>

<div class="settings-layout-grid">
    <!-- SECTION 1: PHÂN NHÓM -->
    <div class="settings-card">
        <div class="card-header">
            <h3><i class="fas fa-layer-group"></i> Phân nhóm</h3>
        </div>
        <div class="card-content">
            <form action="index.php?page=settings/system" method="POST" class="settings-form-inline">
                <input type="hidden" name="action" value="add_group">
                <div class="input-group">
                    <input type="text" name="group_name" placeholder="Tên nhóm mới..." required>
                    <button type="submit" class="btn btn-primary" title="Thêm nhóm"><i class="fas fa-plus"></i></button>
                </div>
            </form>

            <div class="settings-table-wrapper">
                <table class="settings-table">
                    <thead>
                        <tr>
                            <th>Tên Phân nhóm</th>
                            <th width="40"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groups as $g): ?>
                            <tr>
                                <td class="font-bold"><?php echo htmlspecialchars($g['group_name']); ?></td>
                                <td class="text-right">
                                    <form action="index.php?page=settings/system" method="POST" onsubmit="return confirm('Xóa nhóm này?')">
                                        <input type="hidden" name="action" value="delete_group">
                                        <input type="hidden" name="id" value="<?php echo $g['id']; ?>">
                                        <button type="submit" class="btn-delete-tiny"><i class="fas fa-times"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SECTION 2: LOẠI THIẾT BỊ -->
    <div class="settings-card">
        <div class="card-header">
            <h3><i class="fas fa-microchip"></i> Loại thiết bị</h3>
        </div>
        <div class="card-content">
            <form action="index.php?page=settings/system" method="POST" class="settings-form-stacked">
                <input type="hidden" name="action" value="add_type">
                <div class="form-row">
                    <div class="form-col">
                        <label>Tên Loại</label>
                        <input type="text" name="type_name" placeholder="VD: Laptop..." required>
                    </div>
                    <div class="form-col">
                        <label>Thuộc nhóm</label>
                        <?php if (empty($groups)): ?>
                            <select name="group_name" disabled>
                                <option value="">-- Cần thêm nhóm trước --</option>
                            </select>
                        <?php else: ?>
                            <select name="group_name">
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?php echo htmlspecialchars($g['group_name']); ?>"><?php echo htmlspecialchars($g['group_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="form-col-btn">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-full-height" <?php echo empty($groups) ? 'disabled' : ''; ?>><i class="fas fa-plus"></i></button>
                    </div>
                </div>
            </form>

            <div class="settings-table-wrapper">
                <table class="settings-table">
                    <thead>
                        <tr>
                            <th>Loại thiết bị</th>
                            <th>Nhóm</th>
                            <th width="40"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($types as $t): ?>
                            <tr>
                                <td class="font-bold"><?php echo htmlspecialchars($t['type_name']); ?></td>
                                <td><span class="badge-soft"><?php echo htmlspecialchars($t['group_name']); ?></span></td>
                                <td class="text-right">
                                    <form action="index.php?page=settings/system" method="POST" onsubmit="return confirm('Xóa loại này?')">
                                        <input type="hidden" name="action" value="delete_type">
                                        <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                        <button type="submit" class="btn-delete-tiny"><i class="fas fa-times"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SECTION 3: TRẠNG THÁI -->
    <div class="settings-card">
        <div class="card-header">
            <h3><i class="fas fa-info-circle"></i> Trạng thái</h3>
        </div>
        <div class="card-content">
            <form action="index.php?page=settings/system" method="POST" class="settings-form-stacked">
                <input type="hidden" name="action" value="add_status">
                <div class="form-row">
                    <div class="form-col">
                        <label>Tên Trạng thái</label>
                        <input type="text" name="status_name" placeholder="VD: Đang sửa..." required>
                    </div>
                    <div class="form-col">
                        <label>Màu sắc</label>
                        <select name="color_class">
                            <option value="status-active">Xanh lá</option>
                            <option value="status-error">Đỏ</option>
                            <option value="status-warning">Vàng</option>
                            <option value="status-info">Xanh dương</option>
                            <option value="status-default">Xám</option>
                        </select>
                    </div>
                    <div class="form-col-btn">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-full-height"><i class="fas fa-plus"></i></button>
                    </div>
                </div>
            </form>

            <div class="settings-table-wrapper">
                <table class="settings-table">
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
                                        <button type="submit" class="btn-delete-tiny"><i class="fas fa-times"></i></button>
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
/* INTERNAL SCOPED STYLES FOR SETTINGS PAGE */
.settings-layout-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
    gap: 25px;
    align-items: start;
}

.settings-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.settings-card .card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #f1f5f9;
    background: #f8fafc;
}

.settings-card .card-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 10px;
}

.settings-card .card-header h3 i { color: var(--primary-color); }

.settings-card .card-content { padding: 20px; }

/* FORMS */
.settings-form-inline { margin-bottom: 15px; }
.input-group { display: flex; gap: 8px; }
.input-group input { flex: 1; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 12px; font-size: 0.9rem; }
.input-group .btn { width: 42px; height: 38px; padding: 0; }

.settings-form-stacked { margin-bottom: 15px; }
.form-row { display: flex; gap: 10px; align-items: flex-start; }
.form-col { flex: 1; display: flex; flex-direction: column; gap: 5px; }
.form-col label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; }
.form-col input, .form-col select { height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 10px; font-size: 0.85rem; width: 100%; }
.form-col-btn { display: flex; flex-direction: column; gap: 5px; }
.btn-full-height { height: 38px; min-width: 42px; }

/* TABLES */
.settings-table-wrapper {
    max-height: 350px;
    overflow-y: auto;
    border: 1px solid #f1f5f9;
    border-radius: 8px;
}

.settings-table { width: 100%; border-collapse: collapse; }
.settings-table thead th { 
    position: sticky; top: 0; background: #f8fafc; 
    text-align: left; padding: 10px 15px; font-size: 0.75rem; 
    color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0;
    z-index: 5;
}
.settings-table tbody td { padding: 12px 15px; border-bottom: 1px solid #f8fafc; font-size: 0.9rem; }
.settings-table tbody tr:hover { background: #f1f5f9; }

/* UTILS */
.badge-soft { background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
.btn-delete-tiny { 
    background: none; border: none; color: #94a3b8; cursor: pointer; 
    font-size: 0.9rem; padding: 4px; transition: 0.2s; 
}
.btn-delete-tiny:hover { color: #ef4444; transform: scale(1.2); }
.text-right { text-align: right; }
.font-bold { font-weight: 600; color: #1e293b; }

@media (max-width: 768px) {
    .settings-layout-grid { grid-template-columns: 1fr; }
    .form-row { flex-direction: column; }
    .btn-full-height { width: 100%; }
}
</style>