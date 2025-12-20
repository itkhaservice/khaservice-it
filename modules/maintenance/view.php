<?php
$log = null;
if (isset($_GET['id'])) {
    $log_id = $_GET['id'];

    $stmt = $pdo->prepare("
        SELECT
            ml.*,
            d.ma_tai_san,
            d.ten_thiet_bi
        FROM maintenance_logs ml
        LEFT JOIN devices d ON ml.device_id = d.id
        WHERE ml.id = ?
    ");
    $stmt->execute([$log_id]);
    $log = $stmt->fetch();
}

if (!$log) {
    set_message('error', 'Nhật ký bảo trì không tìm thấy!');
    header("Location: index.php?page=maintenance/history");
    exit;
}
?>

<div class="page-header">
    <h2><i class="fas fa-file-alt"></i> Chi tiết Phiếu Bảo trì #<?php echo $log['id']; ?></h2>
    <div class="header-actions">
        <a href="index.php?page=maintenance/history" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <a href="index.php?page=maintenance/edit&id=<?php echo $log['id']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Chỉnh sửa</a>
    </div>
</div>

<div class="card view-container" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h3>Thông tin Xử lý</h3>
        <span class="badge status-warning"><?php echo number_format($log['chi_phi'], 0, ',', '.'); ?> VNĐ</span>
    </div>
    
    <div class="card-body">
        <dl class="detail-list" style="grid-template-columns: 1fr;">
            <div class="detail-item">
                <dt>Thiết bị</dt>
                <dd>
                    <a href="index.php?page=devices/view&id=<?php echo $log['device_id']; ?>" class="link-primary font-bold">
                        <?php echo htmlspecialchars($log['ten_thiet_bi']); ?>
                    </a>
                </dd>
            </div>

            <div class="detail-item">
                <dt>Mã Tài sản</dt>
                <dd><?php echo htmlspecialchars($log['ma_tai_san']); ?></dd>
            </div>

            <div class="detail-item">
                <dt>Ngày sự cố</dt>
                <dd><i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($log['ngay_su_co'])); ?></dd>
            </div>

            <div class="detail-item mt-10">
                <dt>Mô tả sự cố</dt>
                <dd class="text-block"><?php echo nl2br(htmlspecialchars($log['noi_dung'])); ?></dd>
            </div>

            <?php if (!empty($log['hu_hong'])): ?>
            <div class="detail-item mt-10">
                <dt>Nguyên nhân / Hư hỏng</dt>
                <dd class="text-block text-danger"><?php echo nl2br(htmlspecialchars($log['hu_hong'])); ?></dd>
            </div>
            <?php endif; ?>

            <?php if (!empty($log['xu_ly'])): ?>
            <div class="detail-item mt-10">
                <dt>Biện pháp Xử lý</dt>
                <dd class="text-block text-success"><?php echo nl2br(htmlspecialchars($log['xu_ly'])); ?></dd>
            </div>
            <?php endif; ?>
        </dl>
    </div>
</div>

<style>
.text-block {
    background: #f8fafc;
    padding: 10px 15px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    margin-top: 5px;
}
.mt-10 { margin-top: 15px; }
.text-danger { color: #dc2626; border-left: 3px solid #dc2626; }
.text-success { color: #166534; border-left: 3px solid #166534; }
</style>
