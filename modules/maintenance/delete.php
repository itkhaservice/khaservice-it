<?php
if (!isset($_GET['id'])) {
    set_message('error', 'Không có ID phiếu bảo trì.');
    header("Location: index.php?page=maintenance/history");
    exit;
}

$id = $_GET['id'];

// Check existence
$stmt = $pdo->prepare("
    SELECT ml.*, d.ma_tai_san 
    FROM maintenance_logs ml 
    LEFT JOIN devices d ON ml.device_id = d.id 
    WHERE ml.id = ?
");
$stmt->execute([$id]);
$log = $stmt->fetch();

if (!$log) {
    set_message('error', 'Phiếu bảo trì không tồn tại.');
    header("Location: index.php?page=maintenance/history");
    exit;
}

$display_name = $log['ma_tai_san'] ?? $log['custom_device_name'] ?? 'Không xác định';

if (isset($_POST['confirm_delete'])) {
    try {
        $stmt_del = $pdo->prepare("DELETE FROM maintenance_logs WHERE id = ?");
        $stmt_del->execute([$id]);
        set_message('success', 'Đã xóa lịch sử bảo trì thành công!');
        header("Location: index.php?page=maintenance/history");
        exit;
    } catch (PDOException $e) {
        set_message('error', 'Lỗi: ' . $e->getMessage());
        header("Location: index.php?page=maintenance/view&id=$id");
        exit;
    }
}
?>

<div class="delete-confirmation-container">
    <div class="card delete-card">
        <div class="delete-modal-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2 class="delete-modal-title">Xác nhận xóa phiếu bảo trì?</h2>
        <p class="delete-modal-text">
            Bạn đang yêu cầu xóa phiếu bảo trì ngày <strong><?php echo date('d/m/Y', strtotime($log['ngay_su_co'])); ?></strong> của đối tượng <strong><?php echo htmlspecialchars($display_name); ?></strong>.
        </p>
        
        <div class="delete-alert-box">
            <i class="fas fa-info-circle"></i> 
            <span>Hành động này sẽ xóa vĩnh viễn phiếu bảo trì này khỏi hệ thống. Không thể hoàn tác!</span>
        </div>
        
        <form action="index.php?page=maintenance/delete&id=<?php echo $id; ?>" method="POST" class="delete-modal-actions">
            <input type="hidden" name="confirm_delete" value="1">
            <a href="index.php?page=maintenance/history" class="btn btn-secondary">Hủy bỏ</a>
            <button type="submit" class="btn btn-danger">Xác nhận Xóa</button>
        </form>
    </div>
</div>

<style>
.delete-confirmation-container {
    display: flex; justify-content: center; align-items: center; padding: 60px 20px;
}
.delete-card {
    max-width: 500px; width: 100%; text-align: center; padding: 40px !important;
}
</style>