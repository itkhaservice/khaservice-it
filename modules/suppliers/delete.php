<?php
if (!isset($_GET['id'])) {
    set_message('error', 'Không có ID nhà cung cấp.');
    header("Location: index.php?page=suppliers/list");
    exit;
}

$id = $_GET['id'];

// Check permissions
if (!isAdmin()) {
    set_message('error', 'Bạn không có quyền thực hiện thao tác này.');
    header("Location: index.php?page=suppliers/list");
    exit;
}

// Check if exists
$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    set_message('error', 'Nhà cung cấp không tồn tại.');
    header("Location: index.php?page=suppliers/list");
    exit;
}

// Check for dependencies
$stmt_dev = $pdo->prepare("SELECT COUNT(*) FROM devices WHERE supplier_id = ?");
$stmt_dev->execute([$id]);
$device_count = $stmt_dev->fetchColumn();

$stmt_svc = $pdo->prepare("SELECT COUNT(*) FROM services WHERE supplier_id = ?");
$stmt_svc->execute([$id]);
$service_count = $stmt_svc->fetchColumn();

// Handle Confirmation
if (isset($_REQUEST['confirm_delete'])) {
    if ($device_count > 0 || $service_count > 0) {
        set_message('error', "Không thể xóa nhà cung cấp vì còn dữ liệu liên quan ($device_count thiết bị, $service_count dịch vụ).");
        header("Location: index.php?page=suppliers/list");
        exit;
    }

    try {
        $stmt_del = $pdo->prepare("UPDATE suppliers SET deleted_at = NOW() WHERE id = ?");
        $stmt_del->execute([$id]);
        set_message('success', 'Đã chuyển nhà cung cấp vào thùng rác thành công!');
        header("Location: index.php?page=suppliers/list");
        exit;
    } catch (PDOException $e) {
        set_message('error', 'Lỗi: ' . $e->getMessage());
        header("Location: index.php?page=suppliers/list");
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
            Bạn đang yêu cầu bỏ nhà cung cấp <strong><?php echo htmlspecialchars($supplier['ten_npp']); ?></strong> vào thùng rác.
        </p>
        
        <?php if ($device_count > 0 || $service_count > 0): ?>
            <div class="delete-alert-box" style="border-left-color: #f59e0b; background: #fffbeb; color: #92400e;">
                <i class="fas fa-exclamation-circle"></i> 
                <span>
                    <strong>Cảnh báo:</strong> Nhà cung cấp này đang liên kết với:
                    <ul style="margin: 5px 0 0 20px; text-align: left;">
                        <?php if($device_count > 0) echo "<li><strong>$device_count</strong> thiết bị</li>"; ?>
                        <?php if($service_count > 0) echo "<li><strong>$service_count</strong> dịch vụ</li>"; ?>
                    </ul>
                    Bạn không thể xóa cho đến khi gỡ bỏ các liên kết này.
                </span>
            </div>
            <div class="delete-modal-actions">
                <a href="index.php?page=suppliers/view&id=<?php echo $id; ?>" class="btn btn-secondary">Quay lại</a>
            </div>
        <?php else: ?>
            <div class="delete-alert-box" style="border-left-color: #f59e0b; background: #fffbeb; color: #92400e;">
                <i class="fas fa-info-circle"></i> 
                <span>Bạn có thể khôi phục nhà cung cấp này từ mục Thùng rác.</span>
            </div>
            <form action="index.php?page=suppliers/delete&id=<?php echo $id; ?>" method="POST" class="delete-modal-actions">
                <input type="hidden" name="confirm_delete" value="1">
                <a href="index.php?page=suppliers/list" class="btn btn-secondary">Hủy bỏ</a>
                <button type="submit" class="btn btn-warning" style="background: var(--gradient-warning); color:white; border:none; padding: 0 25px; height:42px;">Xác nhận</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<style>
.delete-confirmation-container { display: flex; justify-content: center; align-items: center; padding: 60px 20px; }
.delete-card { max-width: 500px; width: 100%; text-align: center; padding: 40px !important; }
</style>
