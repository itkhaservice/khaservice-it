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
        $stmt_del = $pdo->prepare("UPDATE devices SET deleted_at = NOW() WHERE id = ?");
        $stmt_del->execute([$device_id]);

        set_message('success', 'Đã chuyển thiết bị ' . $device['ma_tai_san'] . ' vào thùng rác!');
        header("Location: index.php?page=devices/list");
        exit;

    } catch (Exception $e) {
        set_message('error', 'Lỗi: ' . $e->getMessage());
        header("Location: index.php?page=devices/view&id=" . $device_id);
        exit;
    }
}
?>

<div class="delete-confirmation-container">
    <div class="card delete-card">
        <div class="delete-modal-icon" style="background: #fef3c7; color: #d97706;">
            <i class="fas fa-trash-alt"></i>
        </div>
        <h2 class="delete-modal-title">Bỏ vào Thùng rác?</h2>
        <p class="delete-modal-text">Bạn đang yêu cầu bỏ thiết bị <strong><?php echo htmlspecialchars($device['ten_thiet_bi']); ?></strong> (<?php echo htmlspecialchars($device['ma_tai_san']); ?>) vào thùng rác.</p>
        <div class="delete-alert-box" style="border-left-color: #f59e0b; background: #fffbeb; color: #92400e;">
            <i class="fas fa-info-circle"></i> 
            Dữ liệu của thiết bị sẽ tạm thời bị ẩn. Bạn có thể khôi phục lại từ mục Thùng rác.
        </div>
        
        <form action="index.php?page=devices/delete&id=<?php echo $device_id; ?>" method="POST" class="delete-actions" style="display:flex; justify-content:center; gap:15px;">
            <input type="hidden" name="confirm_delete" value="1">
            <a href="index.php?page=devices/view&id=<?php echo $device_id; ?>" class="btn btn-secondary">Hủy bỏ</a>
            <button type="submit" class="btn btn-warning" style="background: var(--gradient-warning); color:white; border:none; padding: 0 25px; height:42px;">Xác nhận</button>
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