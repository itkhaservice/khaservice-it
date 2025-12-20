<?php
if (!isset($_GET['id'])) {
    set_message('error', 'Không có ID dự án.');
    header("Location: index.php?page=projects/list");
    exit;
}

$project_id = $_GET['id'];

// Check if project exists
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) {
    set_message('error', 'Dự án không tồn tại.');
    header("Location: index.php?page=projects/list");
    exit;
}

// Check for dependencies (devices)
$stmt_check = $pdo->prepare("SELECT COUNT(*) FROM devices WHERE project_id = ?");
$stmt_check->execute([$project_id]);
$device_count = $stmt_check->fetchColumn();

// Handle Confirmation
if (isset($_POST['confirm_delete'])) {
    if ($device_count > 0) {
        set_message('error', "Không thể xóa dự án này vì đang có $device_count thiết bị liên quan. Vui lòng chuyển hoặc xóa thiết bị trước.");
        header("Location: index.php?page=projects/view&id=$project_id");
        exit;
    }

    try {
        $stmt_del = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt_del->execute([$project_id]);
        set_message('success', 'Đã xóa dự án ' . $project['ma_du_an'] . ' thành công!');
        header("Location: index.php?page=projects/list");
        exit;
    } catch (PDOException $e) {
        set_message('error', 'Lỗi khi xóa: ' . $e->getMessage());
        header("Location: index.php?page=projects/view&id=$project_id");
        exit;
    }
}
?>

<div class="delete-confirmation-container">
    <div class="card delete-card">
        <div class="delete-modal-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2 class="delete-modal-title">Xác nhận xóa dự án?</h2>
        <p class="delete-modal-text">
            Bạn đang yêu cầu xóa dự án <strong><?php echo htmlspecialchars($project['ten_du_an']); ?></strong> (<?php echo htmlspecialchars($project['ma_du_an']); ?>).
        </p>
        
        <?php if ($device_count > 0): ?>
            <div class="delete-alert-box" style="border-left-color: #f59e0b; background: #fffbeb; color: #92400e;">
                <i class="fas fa-exclamation-circle"></i> 
                <span><strong>Cảnh báo:</strong> Dự án này đang chứa <strong><?php echo $device_count; ?></strong> thiết bị. Bạn không thể xóa cho đến khi di chuyển hoặc xóa hết thiết bị khỏi dự án.</span>
            </div>
            <div class="delete-modal-actions">
                <a href="index.php?page=projects/view&id=<?php echo $project_id; ?>" class="btn btn-secondary">Quay lại</a>
                <a href="index.php?page=devices/list&filter_project=<?php echo $project_id; ?>" class="btn btn-primary">Xem thiết bị</a>
            </div>
        <?php else: ?>
            <div class="delete-alert-box">
                <i class="fas fa-info-circle"></i> 
                <span>Hành động này sẽ xóa vĩnh viễn dự án khỏi hệ thống. Không thể hoàn tác!</span>
            </div>
            <form action="index.php?page=projects/delete&id=<?php echo $project_id; ?>" method="POST" class="delete-modal-actions">
                <input type="hidden" name="confirm_delete" value="1">
                <a href="index.php?page=projects/list" class="btn btn-secondary">Hủy bỏ</a>
                <button type="submit" class="btn btn-danger">Xác nhận Xóa vĩnh viễn</button>
            </form>
        <?php endif; ?>
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