<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    set_message('error', 'Bạn không có quyền truy cập chức năng này.');
    header("Location: index.php?page=home"); // Redirect to home or a suitable page
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Basic validation
    if (empty($username)) {
        set_message('error', 'Tên đăng nhập là bắt buộc.');
    }
    if (empty($password)) {
        set_message('error', 'Mật khẩu là bắt buộc.');
    } elseif (strlen($password) < 6) {
        set_message('error', 'Mật khẩu phải có ít nhất 6 ký tự.');
    }

    // Check if username already exists
    if (!isset($_SESSION['messages']) || empty(array_filter($_SESSION['messages'], function($msg) { return $msg['type'] === 'error'; }))) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            set_message('error', 'Tên đăng nhập đã tồn tại.');
        }
    }

    if (!isset($_SESSION['messages']) || empty(array_filter($_SESSION['messages'], function($msg) { return $msg['type'] === 'error'; }))) {
        try {
            // Hash the password before storing
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $hashed_password, $role]);

            set_message('success', 'Người dùng đã được thêm mới thành công!');
            header("Location: index.php?page=users/list");
            exit;
        } catch (PDOException $e) {
            set_message('error', 'Lỗi khi thêm người dùng: ' . $e->getMessage());
        }
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-user-plus"></i> Thêm Người dùng mới</h2>
    <a href="index.php?page=users/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
</div>

<div class="card form-container">
    <form action="index.php?page=users/add" method="POST" class="form-grid">
        <div class="form-group full-width">
            <label for="username">Tên đăng nhập <span class="required">*</span></label>
            <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" placeholder="Nhập tên đăng nhập">
        </div>
        <div class="form-group">
            <label for="password">Mật khẩu <span class="required">*</span></label>
            <input type="password" id="password" name="password" required placeholder="Nhập mật khẩu (tối thiểu 6 ký tự)">
        </div>

        <div class="form-group">
            <label for="role">Vai trò</label>
            <select id="role" name="role">
                <option value="xem" <?php echo (($_POST['role'] ?? '') == 'xem') ? 'selected' : ''; ?>>Xem (Chỉ xem)</option>
                <option value="it" <?php echo (($_POST['role'] ?? '') == 'it') ? 'selected' : ''; ?>>IT (Quản lý thiết bị)</option>
                <option value="admin" <?php echo (($_POST['role'] ?? '') == 'admin') ? 'selected' : ''; ?>>Admin (Toàn quyền)</option>
            </select>
        </div>

        <div class="form-actions full-width">
            <a href="index.php?page=users/list" class="btn btn-secondary">Hủy</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Thêm Người dùng</button>
        </div>
    </form>
</div>
