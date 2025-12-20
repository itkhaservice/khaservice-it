<?php
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php?page=projects/list");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$id]);
$project = $stmt->fetch();

if (!$project) {
    set_message('error', 'Dự án không tìm thấy.');
    header("Location: index.php?page=projects/list");
    exit;
}

// Fetch devices in this project
$stmt_devices = $pdo->prepare("SELECT id, ma_tai_san, ten_thiet_bi, loai_thiet_bi, trang_thai FROM devices WHERE project_id = ? LIMIT 10");
$stmt_devices->execute([$id]);
$devices = $stmt_devices->fetchAll();

// Count total devices
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM devices WHERE project_id = ?");
$stmt_count->execute([$id]);
$total_devices = $stmt_count->fetchColumn();
?>

<div class="page-header">
    <h2><i class="fas fa-building"></i> Chi tiết Dự án</h2>
    <div class="header-actions">
        <a href="index.php?page=projects/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <a href="index.php?page=projects/edit&id=<?php echo $id; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Chỉnh sửa</a>
    </div>
</div>

<div class="view-grid-layout">
    <!-- Left Column: Project Info -->
    <div class="info-column">
        <div class="card info-card">
            <div class="card-header-custom">
                <h3><i class="fas fa-info-circle"></i> Thông tin Chung</h3>
                <span class="badge status-info"><?php echo htmlspecialchars($project['loai_du_an']); ?></span>
            </div>
            
            <div class="card-body-custom">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-barcode"></i> Mã Dự án</span>
                        <span class="info-value highlight"><?php echo htmlspecialchars($project['ma_du_an']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-signature"></i> Tên Dự án</span>
                        <span class="info-value strong"><?php echo htmlspecialchars($project['ten_du_an']); ?></span>
                    </div>

                    <div class="info-item full-width">
                        <span class="info-label"><i class="fas fa-map-marker-alt"></i> Địa chỉ</span>
                        <span class="info-value"><?php echo htmlspecialchars($project['dia_chi']); ?></span>
                    </div>
                    
                    <div class="info-item full-width">
                        <span class="info-label"><i class="fas fa-sticky-note"></i> Ghi chú</span>
                        <div class="note-box">
                            <?php echo nl2br(htmlspecialchars($project['ghi_chu'] ?? 'Không có ghi chú')); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Related Devices -->
    <div class="history-column">
        <div class="card history-card">
            <div class="card-header-custom">
                <h3><i class="fas fa-server"></i> Thiết bị thuộc Dự án</h3>
                <span class="badge status-active"><?php echo $total_devices; ?> thiết bị</span>
            </div>
            <div class="card-body-custom">
                <?php if (empty($devices)): ?>
                    <div class="empty-state-small">
                        <div class="icon-circle"><i class="fas fa-box-open"></i></div>
                        <p>Chưa có thiết bị nào được gán vào dự án này.</p>
                        <a href="index.php?page=devices/add&project_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">Thêm thiết bị</a>
                    </div>
                <?php else: ?>
                    <ul class="device-list-simple">
                        <?php foreach ($devices as $d): ?>
                            <li>
                                <a href="index.php?page=devices/view&id=<?php echo $d['id']; ?>" class="device-item-link">
                                    <div class="d-info">
                                        <span class="d-code"><?php echo htmlspecialchars($d['ma_tai_san']); ?></span>
                                        <span class="d-name"><?php echo htmlspecialchars($d['ten_thiet_bi']); ?></span>
                                    </div>
                                    <span class="d-status badge-sm <?php echo $d['trang_thai'] == 'Hỏng' ? 'err' : 'ok'; ?>">
                                        <?php echo htmlspecialchars($d['trang_thai']); ?>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if($total_devices > 10): ?>
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="index.php?page=devices/list&filter_project=<?php echo $id; ?>" class="btn btn-sm btn-secondary">Xem tất cả <?php echo $total_devices; ?> thiết bị</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Reusing view styles from devices/view.php */
.view-grid-layout {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 30px;
    align-items: start;
}
.card-header-custom {
    display: flex; justify-content: space-between; align-items: center;
    padding-bottom: 20px; margin-bottom: 25px; border-bottom: 1px solid #f1f5f9;
}
.card-header-custom h3 {
    margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-color);
    display: flex; align-items: center; gap: 10px;
}
.info-grid {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px 30px;
}
.info-item { display: flex; flex-direction: column; gap: 5px; }
.info-item.full-width { grid-column: 1 / -1; }
.info-label {
    font-size: 0.75rem; text-transform: uppercase; color: var(--text-light-color);
    font-weight: 600; letter-spacing: 0.5px;
}
.info-value { font-size: 0.95rem; color: var(--text-color); font-weight: 500; }
.info-value.highlight {
    font-family: 'Consolas', monospace; color: var(--primary-dark-color);
    background: #f0fdf4; padding: 2px 8px; border-radius: 4px;
}
.info-value.strong { font-weight: 700; font-size: 1.05rem; }
.note-box {
    background: #f8fafc; padding: 12px; border-radius: 8px;
    border: 1px dashed #cbd5e1; font-size: 0.9rem; color: var(--secondary-color);
}

/* Device List Simple */
.device-list-simple {
    list-style: none; padding: 0; margin: 0;
}
.device-list-simple li {
    border-bottom: 1px dashed #e2e8f0;
}
.device-list-simple li:last-child { border-bottom: none; }
.device-item-link {
    display: flex; justify-content: space-between; align-items: center;
    padding: 10px 0; text-decoration: none; color: var(--text-color);
    transition: 0.2s;
}
.device-item-link:hover { padding-left: 5px; color: var(--primary-color); }
.d-info { display: flex; flex-direction: column; }
.d-code { font-weight: 700; font-size: 0.85rem; }
.d-name { font-size: 0.8rem; color: var(--text-light-color); }
.badge-sm { font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; background: #f1f5f9; color: #64748b; }
.badge-sm.ok { background: #dcfce7; color: #166534; }
.badge-sm.err { background: #fef2f2; color: #991b1b; }

@media (max-width: 992px) {
    .view-grid-layout { grid-template-columns: 1fr; }
}
</style>