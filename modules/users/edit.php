<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    set_message('error', 'Bạn không có quyền truy cập chức năng này.');
    header("Location: index.php?page=home"); // Redirect to home or a suitable page
    exit;
}

$user_id = $_GET['id'] ?? null;
$user = null;

if ($user_id) {
    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}

if (!$user) {
    set_message('error', 'Người dùng không tìm thấy!');
    header("Location: index.php?page=users/list");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'] ?? ''; // New password, can be empty
    $role = $_POST['role'];

    // Basic validation
    if (empty($username)) {
        set_message('error', 'Tên đăng nhập là bắt buộc.');
    }

    // Check if username already exists for another user
    if (!isset($_SESSION['messages']) || empty(array_filter($_SESSION['messages'], function($msg) { return $msg['type'] === 'error'; }))) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetch()) {
            set_message('error', 'Tên đăng nhập đã tồn tại cho người dùng khác.');
        }
    }

    // If a new password is provided, validate it
    if (!empty($password)) {
        if (strlen($password) < 6) {
            set_message('error', 'Mật khẩu mới phải có ít nhất 6 ký tự.');
        }
    }

    if (!isset($_SESSION['messages']) || empty(array_filter($_SESSION['messages'], function($msg) { return $msg['type'] === 'error'; }))) {
        try {
            $sql = "UPDATE users SET username = ?, role = ? WHERE id = ?";
            $params = [$username, $role, $user_id];

            // If a new password is provided, hash it and update
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?";
                $params = [$username, $hashed_password, $role, $user_id];
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // If the current logged-in user changed their own role, update session
            if ($user_id == $_SESSION['user_id']) {
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
            }
            set_message('success', 'Người dùng đã được cập nhật thành công!');
            header("Location: index.php?page=users/list");
            exit;
        } catch (PDOException $e) {
            set_message('error', 'Lỗi khi cập nhật người dùng: ' . $e->getMessage());
        }
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-user-edit"></i> Sửa Người dùng: <?php echo htmlspecialchars($user['username']); ?></h2>
    <a href="index.php?page=users/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
</div>

<div class="card form-container">
    <form action="index.php?page=users/edit&id=<?php echo $user_id; ?>" method="POST" class="form-grid">
        <div class="form-group full-width">
            <label for="username">Tên đăng nhập <span class="required">*</span></label>
            <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? $user['username']); ?>">
        </div>
        <div class="form-group">
            <label for="password">Mật khẩu mới</label>
            <input type="password" id="password" name="password" placeholder="Để trống nếu không muốn đổi">
            <small class="text-muted"><i class="fas fa-info-circle"></i> Chỉ nhập nếu bạn muốn thay đổi mật khẩu hiện tại.</small>
        </div>

        <div class="form-group">
            <label for="role">Vai trò</label>
            <select id="role" name="role">
                <option value="xem" <?php echo (($_POST['role'] ?? $user['role']) == 'xem') ? 'selected' : ''; ?>>Xem (Chỉ xem)</option>
                <option value="it" <?php echo (($_POST['role'] ?? $user['role']) == 'it') ? 'selected' : ''; ?>>IT (Quản lý thiết bị)</option>
                <option value="admin" <?php echo (($_POST['role'] ?? $user['role']) == 'admin') ? 'selected' : ''; ?>>Admin (Toàn quyền)</option>
            </select>
        </div>

        <div class="form-actions full-width">
            <a href="index.php?page=users/list" class="btn btn-secondary">Hủy</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Cập nhật</button>
        </div>
    </form>
</div>
