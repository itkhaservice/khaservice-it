<?php
if (!isset($_GET['id'])) {
    set_message('error', 'Không có ID dịch vụ.');
    header("Location: index.php?page=services/list");
    exit;
}

$service_id = $_GET['id'];

// Check permissions
if (!isIT()) {
    set_message('error', 'Bạn không có quyền thực hiện thao tác này.');
    header("Location: index.php?page=services/list");
    exit;
}

// Fetch service info for confirmation message
$stmt = $pdo->prepare("SELECT ten_dich_vu FROM services WHERE id = ?");
$stmt->execute([$service_id]);
$service = $stmt->fetch();

if (!$service) {
    set_message('error', 'Dịch vụ không tồn tại.');
    header("Location: index.php?page=services/list");
    exit;
}

// Handle Confirmation
if (isset($_REQUEST['confirm_delete'])) {
    try {
        $stmt_del = $pdo->prepare("UPDATE services SET deleted_at = NOW() WHERE id = ?");
        $stmt_del->execute([$service_id]);
        set_message('success', 'Đã chuyển dịch vụ ' . $service['ten_dich_vu'] . ' vào thùng rác!');
        header("Location: index.php?page=services/list");
        exit;
    } catch (PDOException $e) {
        set_message('error', 'Lỗi khi xóa: ' . $e->getMessage());
        header("Location: index.php?page=services/list");
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
        <p class="delete-modal-text">
            Bạn đang yêu cầu bỏ dịch vụ <strong><?php echo htmlspecialchars($service['ten_dich_vu']); ?></strong> vào thùng rác.
        </p>
        
        <div class="delete-alert-box" style="border-left-color: #f59e0b; background: #fffbeb; color: #92400e;">
            <i class="fas fa-info-circle"></i> 
            <span>Dữ liệu dịch vụ sẽ bị ẩn. Bạn có thể khôi phục lại từ mục Thùng rác.</span>
        </div>
        
        <form action="index.php?page=services/delete&id=<?php echo $service_id; ?>" method="POST" class="delete-modal-actions">
            <input type="hidden" name="confirm_delete" value="1">
            <a href="index.php?page=services/list" class="btn btn-secondary">Hủy bỏ</a>
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
</style>
