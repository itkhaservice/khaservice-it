<?php
if ($_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['username']) || empty($_POST['password'])) {
        set_message('error', 'Username và Password là bắt buộc.');
    } else {
        try {
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['username'], $hashed_password, $_POST['role']]);
            set_message('success', 'Tạo tài khoản thành công!');
            header("Location: index.php?page=users/list");
            exit;
        } catch (PDOException $e) {
            set_message('error', 'Lỗi: Tên đăng nhập có thể đã tồn tại.');
        }
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-user-plus"></i> Thêm Người dùng</h2>
    <div class="header-actions">
        <a href="index.php?page=users/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <button type="submit" form="add-user-form" class="btn btn-primary"><i class="fas fa-save"></i> Tạo tài khoản</button>
    </div>
</div>

<div class="form-container" style="max-width: 600px;">
    <form action="index.php?page=users/add" method="POST" id="add-user-form">
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-user-shield"></i> Thông tin Tài khoản</h3>
            </div>
            <div class="card-body-custom" style="padding: 20px;">
                <div class="form-group">
                    <label>Tên đăng nhập <span class="required">*</span></label>
                    <input type="text" name="username" required class="input-highlight">
                </div>
                <div class="form-group">
                    <label>Mật khẩu <span class="required">*</span></label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Vai trò</label>
                    <select name="role">
                        <option value="xem">Xem (Chỉ xem)</option>
                        <option value="it">IT (Quản lý thiết bị)</option>
                        <option value="admin">Admin (Toàn quyền)</option>
                    </select>
                </div>
            </div>
        </div>
    </form>
</div>
<style>
.card-header-custom { padding-bottom: 15px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); }
.card-header-custom h3 { margin: 0; font-size: 1.1rem; color: var(--text-color); display: flex; align-items: center; gap: 10px; }
</style>