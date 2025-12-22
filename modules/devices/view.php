<?php
$device = null;
if (isset($_GET['id'])) {
    $device_id = $_GET['id'];

    $stmt = $pdo->prepare("
        SELECT
            d.*,
            p.ten_du_an,
            s.ten_npp,
            parent.ten_thiet_bi as parent_name,
            parent.ma_tai_san as parent_code
        FROM devices d
        LEFT JOIN projects p ON d.project_id = p.id
        LEFT JOIN suppliers s ON d.supplier_id = s.id
        LEFT JOIN devices parent ON d.parent_id = parent.id
        WHERE d.id = ?
    ");
    $stmt->execute([$device_id]);
    $device = $stmt->fetch();
}

if (!$device) {
    set_message('error', 'Thiết bị không tìm thấy!');
    header("Location: index.php?page=devices/list");
    exit;
}

// Fetch status color from settings
$status_stmt = $pdo->prepare("SELECT color_class FROM settings_device_statuses WHERE status_name = ?");
$status_stmt->execute([$device['trang_thai']]);
$statusClass = $status_stmt->fetchColumn() ?: 'status-default';

// Fetch child devices (components)
$stmt_children = $pdo->prepare("SELECT id, ten_thiet_bi, ma_tai_san, loai_thiet_bi, trang_thai FROM devices WHERE parent_id = ? AND deleted_at IS NULL");
$stmt_children->execute([$device_id]);
$children = $stmt_children->fetchAll();
?>

<div class="page-header">
    <h2><i class="fas fa-info-circle"></i> Chi tiết Thiết bị</h2>
    <div class="header-actions">
        <a href="index.php?page=devices/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <a href="index.php?page=devices/edit&id=<?php echo $device['id']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Chỉnh sửa</a>
    </div>
</div>

<div class="view-grid-layout">
    <!-- Left Column: Device Info & Files -->
    <div class="info-column">
        <!-- Main Info Card -->
        <div class="card info-card">
            <div class="card-header-custom">
                <h3><i class="fas fa-microchip"></i> Thông tin Chung</h3>
                <span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($device['trang_thai']); ?></span>
            </div>
            
            <div class="card-body-custom">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-barcode"></i> Mã Tài sản</span>
                        <span class="info-value highlight"><?php echo htmlspecialchars($device['ma_tai_san']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-desktop"></i> Tên Thiết bị</span>
                        <span class="info-value strong"><?php echo htmlspecialchars($device['ten_thiet_bi']); ?></span>
                    </div>

                    <?php if ($device['parent_id']): ?>
                    <div class="info-item full-width">
                        <span class="info-label"><i class="fas fa-level-up-alt"></i> Thuộc thiết bị (Cha)</span>
                        <span class="info-value">
                            <a href="index.php?page=devices/view&id=<?php echo $device['parent_id']; ?>" class="link-primary font-bold">
                                <?php echo htmlspecialchars($device['parent_name']); ?> (<?php echo htmlspecialchars($device['parent_code']); ?>)
                            </a>
                        </span>
                    </div>
                    <?php endif; ?>

                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-layer-group"></i> Loại / Nhóm</span>
                        <span class="info-value"><?php echo htmlspecialchars($device['loai_thiet_bi']); ?> <span class="text-muted">/ <?php echo htmlspecialchars($device['nhom_thiet_bi']); ?></span></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-cube"></i> Model</span>
                        <span class="info-value"><?php echo htmlspecialchars($device['model']); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-fingerprint"></i> Serial Number</span>
                        <span class="info-value"><?php echo htmlspecialchars($device['serial']); ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-building"></i> Dự án</span>
                        <span class="info-value"><a href="index.php?page=projects/view&id=<?php echo $device['project_id']; ?>" class="link-primary"><?php echo htmlspecialchars($device['ten_du_an'] ?? 'Chưa phân bổ'); ?></a></span>
                    </div>
                    
                    <div class="info-item full-width">
                        <span class="info-label"><i class="fas fa-sticky-note"></i> Ghi chú</span>
                        <div class="info-value note-box">
                            <?php echo nl2br(htmlspecialchars($device['ghi_chu'] ?? 'Không có ghi chú')); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Components (Children) Card -->
        <?php if (!empty($children)): ?>
        <div class="card children-card mt-20">
            <div class="card-header-custom">
                <h3><i class="fas fa-boxes"></i> Linh kiện bên trong (<?php echo count($children); ?>)</h3>
                <a href="index.php?page=devices/add&project_id=<?php echo $device['project_id']; ?>&parent_id=<?php echo $device['id']; ?>" class="btn btn-sm btn-secondary"><i class="fas fa-plus"></i> Thêm LK</a>
            </div>
            <div class="card-body-custom">
                <div class="table-container" style="border:none; box-shadow:none;">
                    <table class="content-table">
                        <thead>
                            <tr>
                                <th>Mã TS</th>
                                <th>Tên linh kiện</th>
                                <th>Loại</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($children as $child): ?>
                                <tr>
                                    <td><a href="index.php?page=devices/view&id=<?php echo $child['id']; ?>" class="font-bold text-primary"><?php echo htmlspecialchars($child['ma_tai_san']); ?></a></td>
                                    <td><?php echo htmlspecialchars($child['ten_thiet_bi']); ?></td>
                                    <td><?php echo htmlspecialchars($child['loai_thiet_bi']); ?></td>
                                    <td><span class="badge"><?php echo htmlspecialchars($child['trang_thai']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Procurement Info Card -->
        <div class="card procurement-card mt-20">
            <div class="card-header-custom">
                <h3><i class="fas fa-shopping-cart"></i> Thông tin Mua sắm</h3>
            </div>
            <div class="card-body-custom">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-truck"></i> Nhà cung cấp</span>
                        <span class="info-value"><?php echo htmlspecialchars($device['ten_npp'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-calendar-alt"></i> Ngày mua</span>
                        <span class="info-value"><?php echo $device['ngay_mua'] ? date('d/m/Y', strtotime($device['ngay_mua'])) : 'N/A'; ?></span>
                    </div>
                    <div class="info-item">
                         <span class="info-label"><i class="fas fa-tag"></i> Giá mua</span>
                         <span class="info-value price"><?php echo number_format($device['gia_mua'], 0, ',', '.'); ?> ₫</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-shield-alt"></i> Bảo hành đến</span>
                        <span class="info-value <?php echo (strtotime($device['bao_hanh_den']) < time()) ? 'text-danger' : 'text-success'; ?>">
                            <?php echo $device['bao_hanh_den'] ? date('d/m/Y', strtotime($device['bao_hanh_den'])) : 'N/A'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- File Attachments Module -->
        <div class="card mt-20">
            <div class="card-header-custom">
                <h3><i class="fas fa-paperclip"></i> Tài liệu đính kèm</h3>
            </div>
            <div class="card-body-custom">
                <?php include_once __DIR__ . '/../device_files/manage.php'; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Maintenance History -->
    <div class="history-column">
        <div class="card history-card">
            <div class="card-header-custom">
                <h3><i class="fas fa-history"></i> Lịch sử Bảo trì</h3>
                <a href="index.php?page=maintenance/add&device_id=<?php echo $device['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Tạo mới</a>
            </div>
            <div class="card-body-custom">
                <?php
                // Fetch maintenance logs for this device AND its children
                $child_ids = array_column($children, 'id');
                $target_ids = array_merge([$device_id], $child_ids);
                $placeholders = implode(',', array_fill(0, count($target_ids), '?'));

                $maintenance_stmt = $pdo->prepare("
                    SELECT ml.*, d.ten_thiet_bi as target_name, d.id as target_id 
                    FROM maintenance_logs ml 
                    JOIN devices d ON ml.device_id = d.id
                    WHERE ml.device_id IN ($placeholders) AND ml.deleted_at IS NULL
                    ORDER BY ml.ngay_su_co DESC, ml.id DESC
                ");
                $maintenance_stmt->execute($target_ids);
                $maintenance_logs = $maintenance_stmt->fetchAll();
                ?>

                <?php if (empty($maintenance_logs)): ?>
                    <div class="empty-state-small">
                        <div class="icon-circle"><i class="fas fa-clipboard-check"></i></div>
                        <p>Chưa có lịch sử bảo trì.</p>
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($maintenance_logs as $log): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <span class="date"><i class="far fa-calendar"></i> <?php echo date('d/m/Y', strtotime($log['ngay_su_co'])); ?></span>
                                        <?php if($log['target_id'] != $device_id): ?>
                                            <span class="badge status-info" style="font-size: 0.65rem;">Linh kiện: <?php echo htmlspecialchars($log['target_name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h4 class="title"><?php echo htmlspecialchars($log['noi_dung']); ?></h4>
                                    
                                    <div class="log-details">
                                        <?php if ($log['hu_hong']): ?>
                                            <div class="log-row error">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <span><?php echo htmlspecialchars($log['hu_hong']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($log['xu_ly']): ?>
                                            <div class="log-row success">
                                                <i class="fas fa-tools"></i>
                                                <span><?php echo htmlspecialchars($log['xu_ly']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* --- VIEW PAGE SPECIFIC STYLES --- */
.view-grid-layout {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 30px;
    align-items: start;
}

/* Card Header Customization */
.card-header-custom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 20px;
    margin-bottom: 25px;
    border-bottom: 1px solid #f1f5f9;
}

.card-header-custom h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-color);
    display: flex;
    align-items: center;
    gap: 10px;
}
.card-header-custom h3 i {
    color: var(--primary-color);
    background: #ecfdf5;
    padding: 8px;
    border-radius: 8px;
    font-size: 0.9rem;
}

/* Info Grid Styling */
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px 30px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.info-item.full-width {
    grid-column: 1 / -1;
}

.info-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    color: var(--text-light-color);
    font-weight: 600;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.info-label i { color: #94a3b8; }

.info-value {
    font-size: 0.95rem;
    color: var(--text-color);
    font-weight: 500;
}

.info-value.highlight {
    font-family: 'Consolas', monospace;
    color: var(--primary-dark-color);
    background: #f0fdf4;
    padding: 2px 8px;
    border-radius: 4px;
    display: inline-block;
    width: fit-content;
}

.info-value.strong { font-weight: 700; font-size: 1.05rem; }
.info-value.price { font-weight: 700; color: #d97706; font-size: 1.1rem; }
.info-value.text-muted { color: #94a3b8; font-weight: 400; font-size: 0.85rem; }

.note-box {
    background: #f8fafc;
    padding: 12px;
    border-radius: 8px;
    border: 1px dashed #cbd5e1;
    font-size: 0.9rem;
    color: var(--secondary-color);
    line-height: 1.6;
}

/* Timeline Styling - Modern */
.timeline {
    position: relative;
    padding-left: 25px;
    border-left: 2px solid #e2e8f0;
    margin-left: 10px;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -32px;
    top: 0;
    width: 14px;
    height: 14px;
    background: #fff;
    border: 3px solid var(--primary-color);
    border-radius: 50%;
    box-shadow: 0 0 0 3px #ecfdf5;
}

.timeline-content {
    background: #fff;
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    font-size: 0.85rem;
}

.timeline-header .date {
    color: var(--text-light-color);
    font-weight: 600;
}

.timeline-content .title {
    margin: 0 0 10px 0;
    font-size: 1rem;
    color: var(--text-color);
    font-weight: 700;
}

.log-details {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.log-row {
    display: flex;
    gap: 10px;
    font-size: 0.9rem;
    padding: 8px 12px;
    border-radius: 6px;
    align-items: start;
}

.log-row.error { background: #fef2f2; color: #991b1b; }
.log-row.success { background: #f0fdf4; color: #166534; }
.log-row i { margin-top: 3px; }

/* Empty State */
.empty-state-small {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-light-color);
}
.icon-circle {
    width: 60px; height: 60px;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 15px auto;
}
.icon-circle i { font-size: 1.5rem; color: #94a3b8; }

/* Responsive */
@media (max-width: 992px) {
    .view-grid-layout {
        grid-template-columns: 1fr;
    }
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>