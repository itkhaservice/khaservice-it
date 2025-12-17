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

<div class="view-container">
    <div class="view-header">
        <h2>Chi tiết Nhật ký Bảo trì</h2>
        <a href="index.php?page=maintenance/edit&id=<?php echo $log['id']; ?>" class="btn btn-primary">Sửa Nhật ký</a>
    </div>

    <dl class="view-grid">
        <dt>Thiết bị:</dt>
        <dd><?php echo htmlspecialchars($log['ten_thiet_bi']); ?></dd>

        <dt>Mã Tài sản:</dt>
        <dd><?php echo htmlspecialchars($log['ma_tai_san']); ?></dd>

        <dt>Ngày sự cố:</dt>
        <dd><?php echo htmlspecialchars($log['ngay_su_co']); ?></dd>

        <dt>Mô tả sự cố:</dt>
        <dd><?php echo nl2br(htmlspecialchars($log['noi_dung'])); ?></dd>

        <dt>Hư hỏng:</dt>
        <dd><?php echo nl2br(htmlspecialchars($log['hu_hong'])); ?></dd>

        <dt>Xử lý:</dt>
        <dd><?php echo nl2br(htmlspecialchars($log['xu_ly'])); ?></dd>

        <dt>Chi phí (VNĐ):</dt>
        <dd><?php echo htmlspecialchars(number_format($log['chi_phi'], 0, ',', '.')); ?></dd>
    </dl>

    <div class="view-actions">
        <a href="index.php?page=maintenance/history" class="btn btn-secondary">Quay lại lịch sử</a>
    </div>
</div>
