<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $link = trim($_POST['link'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $ghi_chu = trim($_POST['ghi_chu'] ?? '');
    $stt = (int)($_POST['stt'] ?? 0);

    if (empty($link)) {
        set_message('error', 'Vui lòng nhập Link / URL.');
    }

    if (!isset($_SESSION['messages']) || empty(array_filter($_SESSION['messages'], function($msg) { return $msg['type'] === 'error'; }))) {
        try {
            // Mã hóa mật khẩu thay vì băm (Để có thể xem lại)
            $encrypted_password = encrypt_data($password);
            
            $sql = "INSERT INTO links (link, username, password, ghi_chu, stt) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$link, $username, $encrypted_password, $ghi_chu, $stt]);
            
            set_message('success', 'Đã thêm Link và mã hóa mật khẩu thành công!');
            header("Location: index.php?page=links/list");
            exit;
        } catch (PDOException $e) {
            set_message('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}

// Lấy STT lớn nhất hiện tại
$max_stt = (int)$pdo->query("SELECT MAX(stt) FROM links WHERE deleted_at IS NULL")->fetchColumn();
$next_stt = $max_stt + 1;
?>

<div class="page-header">
    <div class="header-title">
        <h2><i class="fas fa-plus-circle"></i> Thêm Link mới</h2>
    </div>
    <div class="header-actions">
        <a href="index.php?page=links/list" class="btn btn-secondary">
            <i class="fas fa-times"></i> Hủy bỏ
        </a>
        <button type="submit" form="add-link-form" class="btn btn-primary">
            <i class="fas fa-save"></i> Lưu & Mã hóa
        </button>
    </div>
</div>

<div class="edit-container">
    <form action="index.php?page=links/add" method="POST" id="add-link-form" class="card">
        <div class="card-header-custom">
            <h3><i class="fas fa-info-circle"></i> Thông tin tài khoản (Mật khẩu được mã hóa an toàn)</h3>
        </div>
        <div class="card-body-custom">
            <div class="form-grid">
                <div class="form-group span-8">
                    <label for="link">Link / URL <span class="required">*</span></label>
                    <div class="input-with-icon">
                        <i class="fas fa-globe"></i>
                        <input type="url" id="link" name="link" value="<?php echo htmlspecialchars($_POST['link'] ?? ''); ?>" required placeholder="https://example.com" class="form-control">
                    </div>
                </div>
                <div class="form-group span-4">
                    <label for="stt">Thứ tự hiển thị</label>
                    <input type="number" id="stt" name="stt" value="<?php echo htmlspecialchars($_POST['stt'] ?? $next_stt); ?>" class="form-control">
                </div>

                <div class="form-group span-6">
                    <label for="username">Username / Email</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" placeholder="Tên đăng nhập" class="form-control">
                    </div>
                </div>
                <div class="form-group span-6">
                    <label for="password">Password / Khóa <span class="required">*</span></label>
                    <div class="input-password-group">
                        <div class="input-with-icon">
                            <i class="fas fa-key"></i>
                            <input type="password" id="password" name="password" required placeholder="Nhập mật khẩu" class="form-control">
                        </div>
                        <button type="button" class="btn-toggle-input" onclick="toggleInputPassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small class="text-muted"><i class="fas fa-lock"></i> Hệ thống mã hóa AES-256 để bạn có thể xem lại khi cần.</small>
                </div>

                <div class="form-group span-12">
                    <label for="ghi_chu">Ghi chú chi tiết</label>
                    <textarea id="ghi_chu" name="ghi_chu" rows="4" placeholder="Ví dụ: Tài khoản quản trị, Link backup..." class="form-control"><?php echo htmlspecialchars($_POST['ghi_chu'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function toggleInputPassword(id) {
    const input = document.getElementById(id);
    const btn = input.closest('.input-password-group').querySelector('.btn-toggle-input');
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
        btn.classList.add('active');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
        btn.classList.remove('active');
    }
}
</script>

<style>
/* Modern Form Layout */
.form-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 20px; }
.span-4 { grid-column: span 4; }
.span-6 { grid-column: span 6; }
.span-8 { grid-column: span 8; }
.span-12 { grid-column: span 12; }
.card-header-custom { padding: 15px 20px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
.card-header-custom h3 { margin: 0; font-size: 1rem; color: #1e293b; display: flex; align-items: center; gap: 10px; }
.card-header-custom h3 i { color: var(--primary-color); }
.input-with-icon { position: relative; }
.input-with-icon i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem; }
.input-with-icon .form-control { padding-left: 35px; }
.input-password-group { position: relative; display: flex; }
.input-password-group .input-with-icon { flex: 1; }
.btn-toggle-input { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 5px; transition: color 0.2s; z-index: 5; }
.btn-toggle-input:hover, .btn-toggle-input.active { color: var(--primary-color); }
.form-control { width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem; transition: all 0.2s; }
.form-control:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(36, 162, 92, 0.1); outline: none; }
@media (max-width: 768px) { .span-4, .span-6, .span-8 { grid-column: span 12; } }
</style>
