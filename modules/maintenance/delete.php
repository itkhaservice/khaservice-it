<?php
if (!isset($_GET['id'])) {
    set_message('error', 'Không có ID phiếu bảo trì.');
    header("Location: index.php?page=maintenance/history");
    exit;
}

$id = $_GET['id'];

// Check permissions
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'it') {
    set_message('error', 'Bạn không có quyền thực hiện thao tác này.');
    header("Location: index.php?page=maintenance/history");
    exit;
}

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

// Check for dependencies (attached files)
$stmt_files = $pdo->prepare("SELECT COUNT(*) FROM maintenance_files WHERE maintenance_id = ?");
$stmt_files->execute([$id]);
$file_count = $stmt_files->fetchColumn();

$display_name = $log['ma_tai_san'] ?? $log['custom_device_name'] ?? 'Không xác định';

if (isset($_REQUEST['confirm_delete'])) {
    try {
        $pdo->beginTransaction();

        // 1. Delete physical files
        $stmt_get_files = $pdo->prepare("SELECT file_path FROM maintenance_files WHERE maintenance_id = ?");
        $stmt_get_files->execute([$id]);
        $files = $stmt_get_files->fetchAll(PDO::FETCH_COLUMN);

        foreach ($files as $file_path) {
            $full_path = __DIR__ . "/../../" . $file_path;
            if (file_exists($full_path)) {
                unlink($full_path);
            }
        }

        // 2. Delete database records
        $pdo->prepare("DELETE FROM maintenance_files WHERE maintenance_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM maintenance_logs WHERE id = ?")->execute([$id]);

        $pdo->commit();
        set_message('success', 'Đã xóa lịch sử bảo trì thành công!');
        header("Location: index.php?page=maintenance/history");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
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
        
        <?php if ($file_count > 0): ?>
            <div class="delete-alert-box" style="border-left-color: #f59e0b; background: #fffbeb; color: #92400e;">
                <i class="fas fa-paperclip" style="font-size: 1.2rem;"></i> 
                <span><strong>Cảnh báo:</strong> Phiếu này đang có <strong><?php echo $file_count; ?></strong> tài liệu đính kèm. Hành động này sẽ xóa vĩnh viễn phiếu và toàn bộ tài liệu liên quan.</span>
            </div>
        <?php else: ?>
            <div class="delete-alert-box">
                <i class="fas fa-info-circle"></i> 
                <span>Hành động này sẽ xóa vĩnh viễn phiếu bảo trì này khỏi hệ thống. Không thể hoàn tác!</span>
            </div>
        <?php endif; ?>
        
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