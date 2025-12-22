<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    set_message('error', 'Bạn không có quyền truy cập chức năng này.');
    header("Location: index.php?page=home"); // Redirect to home or a suitable page
    exit;
}

$user_id_to_delete = $_GET['id'] ?? null;

if (!$user_id_to_delete) {
    set_message('error', 'Không có ID người dùng được cung cấp.');
    header("Location: index.php?page=users/list");
    exit;
}

// Fetch user info for confirmation
$stmt = $pdo->prepare("SELECT id, username, fullname FROM users WHERE id = ?");
$stmt->execute([$user_id_to_delete]);
$user = $stmt->fetch();

if (!$user) {
    set_message('error', 'Người dùng không tồn tại.');
    header("Location: index.php?page=users/list");
    exit;
}

// Prevent admin from deleting their own account
if ($user['id'] == $_SESSION['user_id']) {
    set_message('error', 'Bạn không thể xóa tài khoản của chính mình!');
    header("Location: index.php?page=users/list");
    exit;
}

// Handle Confirmation
if (isset($_REQUEST['confirm_delete'])) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$user_id_to_delete]);
        set_message('success', 'Đã chuyển người dùng ' . htmlspecialchars($user['username']) . ' vào thùng rác!');
        header("Location: index.php?page=users/list");
        exit;
    } catch (PDOException $e) {
        set_message('error', 'Lỗi khi xóa người dùng: ' . $e->getMessage());
        header("Location: index.php?page=users/list");
        exit;
    }
}
?>

<div class="delete-confirmation-container">
    <div class="card delete-card">
        <div class="delete-modal-icon" style="background: #fef3c7; color: #d97706;">
            <i class="fas fa-user-slash"></i>
        </div>
        <h2 class="delete-modal-title">Bỏ người dùng vào Thùng rác?</h2>
        <p class="delete-modal-text">
            Bạn đang yêu cầu bỏ người dùng <strong><?php echo htmlspecialchars($user['fullname']); ?></strong> (<?php echo htmlspecialchars($user['username']); ?>) vào thùng rác.
        </p>
        
        <div class="delete-alert-box" style="border-left-color: #f59e0b; background: #fffbeb; color: #92400e;">
            <i class="fas fa-info-circle"></i> 
            <span>Tài khoản này sẽ bị vô hiệu hóa. Bạn có thể khôi phục lại từ mục Thùng rác.</span>
        </div>
        
        <form action="index.php?page=users/delete&id=<?php echo $user_id_to_delete; ?>" method="POST" class="delete-modal-actions">
            <input type="hidden" name="confirm_delete" value="1">
            <a href="index.php?page=users/list" class="btn btn-secondary">Hủy bỏ</a>
            <button type="submit" class="btn btn-warning" style="background: var(--gradient-warning); color:white; border:none; padding: 0 25px; height:42px;">Xác nhận</button>
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
