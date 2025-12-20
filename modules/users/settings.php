<?php
// module: modules/users/settings.php
// Cho phép bất kỳ ai đã đăng nhập đổi mật khẩu của mình

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    // 1. Kiểm tra dữ liệu
    if (empty($current_pass) || empty($new_pass)) {
        set_message('error', 'Vui lòng nhập đầy đủ thông tin.');
    } elseif ($new_pass !== $confirm_pass) {
        set_message('error', 'Mật khẩu mới không khớp.');
    } elseif (strlen($new_pass) < 6) {
        set_message('error', 'Mật khẩu mới phải từ 6 ký tự trở lên.');
    } else {
        // 2. Kiểm tra mật khẩu hiện tại
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_pass, $user['password'])) {
            // 3. Cập nhật mật khẩu mới
            $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->execute([$hashed, $user_id]);
            set_message('success', 'Đã đổi mật khẩu thành công!');
        } else {
            set_message('error', 'Mật khẩu hiện tại không chính xác.');
        }
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-user-cog"></i> Cài đặt Tài khoản</h2>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
    </div>
</div>

<div class="form-container" style="max-width: 500px; margin: 0 auto;">
    <form action="index.php?page=users/settings" method="POST" class="card">
        <div class="card-header-custom" style="padding: 20px; border-bottom: 1px solid #eee;">
            <h3 style="margin:0;"><i class="fas fa-key"></i> Đổi mật khẩu</h3>
        </div>
        <div class="card-body-custom" style="padding: 25px;">
            <div class="form-group">
                <label>Mật khẩu hiện tại</label>
                <div class="password-wrapper">
                    <input type="password" name="current_password" required placeholder="Nhập mật khẩu đang dùng" class="password-input">
                    <i class="fas fa-eye toggle-password"></i>
                </div>
            </div>
            <hr style="margin: 20px 0; border: none; border-top: 1px dashed #eee;">
            <div class="form-group">
                <label>Mật khẩu mới</label>
                <div class="password-wrapper">
                    <input type="password" name="new_password" required placeholder="Tối thiểu 6 ký tự" class="password-input">
                    <i class="fas fa-eye toggle-password"></i>
                </div>
            </div>
            <div class="form-group">
                <label>Xác nhận mật khẩu mới</label>
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" required placeholder="Nhập lại mật khẩu mới" class="password-input">
                    <i class="fas fa-eye toggle-password"></i>
                </div>
            </div>
            <div style="margin-top: 25px;">
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-check-circle"></i> Cập nhật Mật khẩu
                </button>
            </div>
        </div>
    </form>
</div>

<style>
.card-header-custom h3 { font-size: 1rem; color: #1e293b; display: flex; align-items: center; gap: 10px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #64748b; font-size: 0.9rem; }

/* Style cho phần xem mật khẩu */
.password-wrapper { position: relative; display: flex; align-items: center; }
.password-wrapper input { width: 100%; padding: 10px 40px 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; }
.password-wrapper input:focus { border-color: var(--primary-color); outline: none; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.toggle-password { position: absolute; right: 12px; color: #94a3b8; cursor: pointer; transition: color 0.2s; padding: 5px; }
.toggle-password:hover { color: var(--primary-color); }
.toggle-password.fa-eye-slash { color: var(--primary-color); }
</style>

<script>
document.querySelectorAll('.toggle-password').forEach(icon => {
    icon.addEventListener('click', function() {
        const input = this.parentElement.querySelector('input');
        if (input.type === 'password') {
            input.type = 'text';
            this.classList.remove('fa-eye');
            this.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            this.classList.remove('fa-eye-slash');
            this.classList.add('fa-eye');
        }
    });
});
</script>