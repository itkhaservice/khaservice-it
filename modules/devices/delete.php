<?php
if (!isset($_GET['id'])) {
    set_message('error', 'Không có ID thiết bị được cung cấp.');
    header("Location: index.php?page=devices/list");
    exit;
}

$device_id = $_GET['id'];

// Fetch device info for confirmation message
$stmt = $pdo->prepare("SELECT ma_tai_san, ten_thiet_bi FROM devices WHERE id = ?");
$stmt->execute([$device_id]);
$device = $stmt->fetch();

if (!$device) {
    set_message('error', 'Thiết bị không tồn tại.');
    header("Location: index.php?page=devices/list");
    exit;
}

// Check for confirmation
if (isset($_POST['confirm_delete'])) {
    try {
        $pdo->beginTransaction();

        // 1. Find and delete files
        $stmt_files = $pdo->prepare("SELECT file_path FROM device_files WHERE device_id = ?");
        $stmt_files->execute([$device_id]);
        $files_to_delete = $stmt_files->fetchAll(PDO::FETCH_COLUMN);

        foreach ($files_to_delete as $file_path) {
            $real_file_path = realpath(__DIR__ . '/../../' . $file_path);
            $base_path = realpath(__DIR__ . '/../../uploads');
            if ($real_file_path && strpos($real_file_path, $base_path) === 0 && file_exists($real_file_path)) {
                unlink($real_file_path);
            }
        }

        // 2. Delete related records
        $pdo->prepare("DELETE FROM device_files WHERE device_id = ?")->execute([$device_id]);
        $pdo->prepare("DELETE FROM maintenance_logs WHERE device_id = ?")->execute([$device_id]);
        $pdo->prepare("DELETE FROM devices WHERE id = ?")->execute([$device_id]);

        $pdo->commit();
        set_message('success', 'Đã xóa thiết bị ' . $device['ma_tai_san'] . ' thành công!');
        header("Location: index.php?page=devices/list");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        set_message('error', 'Lỗi: ' . $e->getMessage());
        header("Location: index.php?page=devices/view&id=" . $device_id);
        exit;
    }
}
?>

<div class="delete-confirmation-container">
    <div class="card delete-card">
        <div class="warning-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2>Xác nhận xóa thiết bị?</h2>
        <p>Bạn đang yêu cầu xóa thiết bị <strong><?php echo htmlspecialchars($device['ten_thiet_bi']); ?></strong> (<?php echo htmlspecialchars($device['ma_tai_san']); ?>).</p>
        <div class="alert-box danger">
            <i class="fas fa-info-circle"></i> 
            Hành động này sẽ xóa vĩnh viễn thiết bị và <strong>tất cả lịch sử bảo trì, tài liệu đính kèm</strong> liên quan. Không thể hoàn tác!
        </div>
        
        <form action="index.php?page=devices/delete&id=<?php echo $device_id; ?>" method="POST" class="delete-actions">
            <input type="hidden" name="confirm_delete" value="1">
            <a href="index.php?page=devices/view&id=<?php echo $device_id; ?>" class="btn btn-secondary">Hủy bỏ</a>
            <button type="submit" class="btn btn-danger">Xác nhận Xóa vĩnh viễn</button>
        </form>
    </div>
</div>

<style>
.delete-confirmation-container {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 60px 20px;
}
.delete-card {
    max-width: 500px;
    width: 100%;
    text-align: center;
    padding: 40px !important;
}
.warning-icon {
    width: 80px;
    height: 80px;
    background: #fee2e2;
    color: #ef4444;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin: 0 auto 25px auto;
}
.delete-card h2 {
    font-size: 1.5rem;
    margin-bottom: 15px;
    color: var(--text-color);
}
.delete-card p {
    color: var(--text-light-color);
    margin-bottom: 25px;
    line-height: 1.6;
}
.alert-box.danger {
    background: #fef2f2;
    color: #991b1b;
    padding: 15px;
    border-radius: 8px;
    font-size: 0.9rem;
    text-align: left;
    margin-bottom: 30px;
    border-left: 4px solid #ef4444;
}
.delete-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
}
.delete-actions .btn {
    padding: 0 25px;
    height: 42px;
}
</style>