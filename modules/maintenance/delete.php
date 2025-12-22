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
        $stmt_del = $pdo->prepare("UPDATE maintenance_logs SET deleted_at = NOW() WHERE id = ?");
        $stmt_del->execute([$id]);
        set_message('success', 'Đã chuyển phiếu công tác vào thùng rác!');
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
        <div class="delete-modal-icon" style="background: #fef3c7; color: #d97706;">
            <i class="fas fa-trash-alt"></i>
        </div>
        <h2 class="delete-modal-title">Bỏ vào Thùng rác?</h2>
        <p class="delete-modal-text">
            Bạn đang yêu cầu bỏ phiếu bảo trì ngày <strong><?php echo date('d/m/Y', strtotime($log['ngay_su_co'])); ?></strong> của đối tượng <strong><?php echo htmlspecialchars($display_name); ?></strong> vào thùng rác.
        </p>
        
        <div class="delete-alert-box" style="border-left-color: #f59e0b; background: #fffbeb; color: #92400e;">
            <i class="fas fa-info-circle"></i> 
            <span>Bạn có thể khôi phục phiếu này sau từ mục Thùng rác.</span>
        </div>
        
        <form action="index.php?page=maintenance/delete&id=<?php echo $id; ?>" method="POST" class="delete-modal-actions">
            <input type="hidden" name="confirm_delete" value="1">
            <a href="index.php?page=maintenance/history" class="btn btn-secondary">Hủy bỏ</a>
            <button type="submit" class="btn btn-warning" style="background: var(--gradient-warning); color:white; border:none;">Xác nhận Bỏ vào Thùng rác</button>
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