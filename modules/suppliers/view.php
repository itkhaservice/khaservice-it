<?php
if (!isset($_GET['id'])) {
    header("Location: index.php?page=suppliers/list");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    set_message('error', 'Nhà cung cấp không tồn tại.');
    header("Location: index.php?page=suppliers/list");
    exit;
}

// Fetch related devices
$stmt_devices = $pdo->prepare("SELECT id, ma_tai_san, ten_thiet_bi, loai_thiet_bi, trang_thai FROM devices WHERE supplier_id = ? ORDER BY ten_thiet_bi");
$stmt_devices->execute([$id]);
$devices = $stmt_devices->fetchAll();

// Fetch related services
$stmt_services = $pdo->prepare("SELECT s.*, p.ten_du_an FROM services s LEFT JOIN projects p ON s.project_id = p.id WHERE s.supplier_id = ? ORDER BY s.ngay_het_han");
$stmt_services->execute([$id]);
$services = $stmt_services->fetchAll();
?>

<div class="page-header">
    <h2><i class="fas fa-truck"></i> Chi tiết Nhà cung cấp</h2>
    <div class="header-actions">
        <a href="index.php?page=suppliers/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <?php if(isIT()): ?>
            <a href="index.php?page=suppliers/edit&id=<?php echo $id; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Chỉnh sửa</a>
            <?php if(isAdmin()): ?>
                <a href="index.php?page=suppliers/delete&id=<?php echo $id; ?>" data-url="index.php?page=suppliers/delete&id=<?php echo $id; ?>&confirm_delete=1" class="btn btn-danger delete-btn"><i class="fas fa-trash-alt"></i> Xóa</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="suppliers-view-grid">
    <!-- SIDEBAR: THÔNG TIN NHÀ CUNG CẤP -->
    <div class="side-content">
        <div class="card profile-card text-center">
            <div class="device-icon-large" style="background: #f0fdf4; color: #166534; margin: 0 auto 20px auto;">
                <i class="fas fa-building"></i>
            </div>
            <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($supplier['ten_npp']); ?></h3>
            <span class="badge status-info">Nhà cung cấp</span>
            
            <div class="profile-details mt-20" style="text-align: left;">
                <div class="detail-row">
                    <span class="d-label">Người liên hệ</span>
                    <span class="d-value"><?php echo htmlspecialchars($supplier['nguoi_lien_he'] ?: '---'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="d-label">Điện thoại</span>
                    <span class="d-value"><?php echo htmlspecialchars($supplier['dien_thoai'] ?: '---'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="d-label">Email</span>
                    <span class="d-value"><?php echo htmlspecialchars($supplier['email'] ?: '---'); ?></span>
                </div>
            </div>
            
            <?php if(!empty($supplier['ghi_chu'])): ?>
                <div class="mt-20 supplier-note">
                    <div class="note-title">Ghi chú</div>
                    <div class="note-content"><?php echo nl2br(htmlspecialchars($supplier['ghi_chu'])); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MAIN: DANH SÁCH THIẾT BỊ & DỊCH VỤ -->
    <div class="main-content">
        <!-- Related Devices -->
        <div class="card">
            <div class="dashboard-card-header">
                <h3><i class="fas fa-server"></i> Thiết bị đã cung cấp (<?php echo count($devices); ?>)</h3>
            </div>
            <?php if(empty($devices)): ?>
                <p class="text-muted">Chưa ghi nhận thiết bị nào từ nhà cung cấp này.</p>
            <?php else: ?>
                <div class="table-container" style="border:none; box-shadow:none; margin-bottom: 0;">
                    <table class="content-table">
                        <thead>
                            <tr>
                                <th>Mã tài sản</th>
                                <th>Tên thiết bị</th>
                                <th class="mobile-hide">Loại</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($devices as $d): ?>
                                <tr>
                                    <td><a href="index.php?page=devices/view&id=<?php echo $d['id']; ?>" class="text-primary font-medium"><?php echo htmlspecialchars($d['ma_tai_san']); ?></a></td>
                                    <td><?php echo htmlspecialchars($d['ten_thiet_bi']); ?></td>
                                    <td class="mobile-hide"><?php echo htmlspecialchars($d['loai_thiet_bi']); ?></td>
                                    <td><span class="badge"><?php echo htmlspecialchars($d['trang_thai']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Related Services -->
        <div class="card mt-20">
            <div class="dashboard-card-header">
                <h3><i class="fas fa-cloud"></i> Dịch vụ / Phần mềm (<?php echo count($services); ?>)</h3>
            </div>
            <?php if(empty($services)): ?>
                <p class="text-muted">Chưa ghi nhận dịch vụ nào từ nhà cung cấp này.</p>
            <?php else: ?>
                <div class="table-container" style="border:none; box-shadow:none; margin-bottom: 0;">
                    <table class="content-table">
                        <thead>
                            <tr>
                                <th>Tên dịch vụ</th>
                                <th class="mobile-hide">Dự án</th>
                                <th>Ngày hết hạn</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($services as $s): ?>
                                <tr>
                                    <td><a href="index.php?page=services/view&id=<?php echo $s['id']; ?>" class="font-bold"><?php echo htmlspecialchars($s['ten_dich_vu']); ?></a></td>
                                    <td class="mobile-hide"><?php echo htmlspecialchars($s['ten_du_an'] ?: 'Dùng chung'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($s['ngay_het_han'])); ?></td>
                                    <td><span class="badge"><?php echo htmlspecialchars($s['trang_thai']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.suppliers-view-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 25px;
    align-items: start;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    font-size: 0.9rem;
    border-bottom: 1px dashed #f1f5f9;
    padding-bottom: 8px;
}

.d-label { color: #64748b; font-weight: 500; }
.d-value { font-weight: 600; color: #334155; text-align: right; max-width: 60%; }

.supplier-note {
    text-align: left;
    padding: 15px;
    background: #f8fafc;
    border-radius: 8px;
}

.note-title {
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #64748b;
    font-weight: 700;
    margin-bottom: 5px;
}

.note-content {
    font-size: 0.9rem;
    line-height: 1.5;
}

@media (max-width: 992px) {
    .suppliers-view-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .mobile-hide {
        display: none;
    }
}

@media (max-width: 576px) {
    .profile-card {
        padding: 20px !important;
    }
    
    .detail-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
    
    .d-value {
        text-align: left;
        max-width: 100%;
    }
}
</style>
