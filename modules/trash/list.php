<?php
// modules/trash/list.php

$type = $_GET['type'] ?? 'maintenance';
$allowed_types = ['maintenance', 'devices', 'projects', 'services', 'suppliers', 'users'];
if (!in_array($type, $allowed_types)) $type = 'maintenance';

// Pagination Config
$rows_per_page = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : 10;
$current_page  = (isset($_GET['p']) && is_numeric($_GET['p'])) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

$data = [];
$title = "";
$icon = "";
$total_rows = 0;
$total_pages = 0;

try {
    // Define base queries based on type
    switch ($type) {
        case 'maintenance':
            $title = "Phiếu Công tác"; $icon = "fa-history";
            $count_sql = "SELECT COUNT(*) FROM maintenance_logs WHERE deleted_at IS NOT NULL";
            $data_sql = "SELECT ml.id, ml.ngay_su_co as label, d.ma_tai_san as sublabel, ml.deleted_at 
                         FROM maintenance_logs ml 
                         LEFT JOIN devices d ON ml.device_id = d.id 
                         WHERE ml.deleted_at IS NOT NULL 
                         ORDER BY ml.deleted_at DESC";
            break;
        case 'devices':
            $title = "Thiết bị"; $icon = "fa-server";
            $count_sql = "SELECT COUNT(*) FROM devices WHERE deleted_at IS NOT NULL";
            $data_sql = "SELECT id, ten_thiet_bi as label, ma_tai_san as sublabel, deleted_at 
                         FROM devices 
                         WHERE deleted_at IS NOT NULL 
                         ORDER BY deleted_at DESC";
            break;
        case 'projects':
            $title = "Dự án"; $icon = "fa-building";
            $count_sql = "SELECT COUNT(*) FROM projects WHERE deleted_at IS NOT NULL";
            $data_sql = "SELECT id, ten_du_an as label, ma_du_an as sublabel, deleted_at 
                         FROM projects 
                         WHERE deleted_at IS NOT NULL 
                         ORDER BY deleted_at DESC";
            break;
        case 'services':
            $title = "Dịch vụ"; $icon = "fa-cloud";
            $count_sql = "SELECT COUNT(*) FROM services WHERE deleted_at IS NOT NULL";
            $data_sql = "SELECT id, ten_dich_vu as label, loai_dich_vu as sublabel, deleted_at 
                         FROM services 
                         WHERE deleted_at IS NOT NULL 
                         ORDER BY deleted_at DESC";
            break;
        case 'suppliers':
            $title = "Nhà cung cấp"; $icon = "fa-truck";
            $count_sql = "SELECT COUNT(*) FROM suppliers WHERE deleted_at IS NOT NULL";
            $data_sql = "SELECT id, ten_npp as label, nguoi_lien_he as sublabel, deleted_at 
                         FROM suppliers 
                         WHERE deleted_at IS NOT NULL 
                         ORDER BY deleted_at DESC";
            break;
        case 'users':
            $title = "Người dùng"; $icon = "fa-users";
            $count_sql = "SELECT COUNT(*) FROM users WHERE deleted_at IS NOT NULL";
            $data_sql = "SELECT id, fullname as label, username as sublabel, deleted_at 
                         FROM users 
                         WHERE deleted_at IS NOT NULL 
                         ORDER BY deleted_at DESC";
            break;
    }

    // Execute Count Query
    $total_rows = $pdo->query($count_sql)->fetchColumn();
    $total_pages = max(1, ceil($total_rows / $rows_per_page));
    if ($current_page > $total_pages) $current_page = $total_pages;
    $offset = ($current_page - 1) * $rows_per_page;

    // Execute Data Query with Limit
    $data_sql .= " LIMIT $rows_per_page OFFSET $offset";
    $data = $pdo->query($data_sql)->fetchAll();

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
        <form action="index.php?page=trash/bulk_action&type=<?php echo $type; ?>" method="POST" id="trash-bulk-form">
            <!-- Batch Actions Toolbar -->
            <div class="batch-actions" id="batch-actions" style="display: none; margin-bottom: 15px;">
                <span class="selected-count-label">Đã chọn <strong id="selected-count">0</strong> mục</span>
                <div class="action-buttons">
                    <button type="button" class="btn btn-secondary btn-sm" id="clear-selection-btn"><i class="fas fa-times"></i> Bỏ chọn</button>
                    <button type="submit" name="action" value="restore_selected" class="btn btn-primary btn-sm" style="background: var(--gradient-primary);"><i class="fas fa-undo"></i> Khôi phục đã chọn</button>
                    <button type="button" class="btn btn-danger btn-sm" id="delete-selected-trash-btn"><i class="fas fa-trash-alt"></i> Xóa vĩnh viễn</button>
                    <input type="hidden" name="bulk_delete_confirm" id="bulk_delete_confirm" value="0">
                </div>
            </div>

            <div class="table-container" style="border:none; box-shadow:none;">
                <table class="content-table">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="select-all"></th>
                            <th>Thông tin mục đã xóa</th>
                            <th>Thời gian xóa</th>
                            <th width="200" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $item): ?>
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="<?php echo $item['id']; ?>" class="row-checkbox"></td>
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
        </form>

        <!-- Pagination Control -->
        <div class="pagination-container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-top: 20px;">
            <div class="rows-per-page">
                <form action="index.php" method="GET" class="rows-per-page-form" style="display: flex; align-items: center; gap: 8px;">
                    <input type="hidden" name="page" value="trash/list">
                    <input type="hidden" name="type" value="<?php echo $type; ?>">
                    <label style="font-size: 0.85rem; color: #64748b;">Hiển thị</label>
                    <select name="limit" onchange="this.form.submit()" class="form-select-sm" style="width: auto;">
                        <?php foreach([5,10,25,50,100] as $lim): 
                            $selected = ($rows_per_page == $lim) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $lim; ?>" <?php echo $selected; ?>><?php echo $lim; ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="pagination-links" style="display: flex; gap: 5px; margin-left: auto;">
                <?php 
                    $q = $_GET; unset($q['p']); 
                    $base = 'index.php?' . http_build_query($q); 
                ?>
                <a href="<?php echo $base . '&p=1'; ?>" class="page-link <?php echo $current_page <= 1 ? 'disabled' : ''; ?>"><i class="fas fa-angle-double-left"></i></a>
                <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): 
                    $active = ($i == $current_page) ? 'active' : '';
                ?>
                    <a href="<?php echo $base . '&p=' . $i; ?>" class="page-link <?php echo $active; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <a href="<?php echo $base . '&p=' . $total_pages; ?>" class="page-link <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>"><i class="fas fa-angle-double-right"></i></a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const rowCbs = document.querySelectorAll('.row-checkbox');
    const batchBar = document.getElementById('batch-actions');
    const countLabel = document.getElementById('selected-count');
    const clearBtn = document.getElementById('clear-selection-btn');
    const bulkForm = document.getElementById('trash-bulk-form');
    const deleteBtn = document.getElementById('delete-selected-trash-btn');

    function updateBatchUI() {
        const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
        if(batchBar) batchBar.style.display = checkedCount > 0 ? 'flex' : 'none';
        if(countLabel) countLabel.textContent = checkedCount;
    }

    if(selectAll) {
        selectAll.addEventListener('change', () => {
            rowCbs.forEach(cb => cb.checked = selectAll.checked);
            updateBatchUI();
        });
    }

    rowCbs.forEach(cb => cb.addEventListener('change', updateBatchUI));

    if(clearBtn) {
        clearBtn.addEventListener('click', () => {
            if(selectAll) selectAll.checked = false;
            rowCbs.forEach(cb => cb.checked = false);
            updateBatchUI();
        });
    }

    if(deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
            showCustomConfirm(`Bạn có chắc chắn muốn xóa VĨNH VIỄN ${checkedCount} mục đã chọn? Hành động này KHÔNG THỂ khôi phục.`, 'Xác nhận xóa vĩnh viễn', () => {
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_selected';
                bulkForm.appendChild(actionInput);
                bulkForm.submit();
            });
        });
    }
});
</script>

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