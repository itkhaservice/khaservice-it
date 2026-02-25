<?php
// XỬ LÝ POST (Ưu tiên chạy trước để redirect nếu cần)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_GET['id'] ?? null;
    try {
        if (!empty($_POST['password'])) {
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET fullname = ?, role = ?, password = ? WHERE id = ?");
            $stmt->execute([$_POST['fullname'], $_POST['role'], $hashed_password, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET fullname = ?, role = ? WHERE id = ?");
            $stmt->execute([$_POST['fullname'], $_POST['role'], $id]);
        }
        set_message('success', 'Cập nhật thành công!');
        // Dùng JS redirect an toàn trên hosting
        echo '<script>window.location.href="index.php?page=users/list";</script>';
        exit;
    } catch (PDOException $e) {
        set_message('error', 'Lỗi: ' . $e->getMessage());
    }
}

// LẤY DỮ LIỆU
$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$u = $stmt->fetch();

if (!$u) { 
    set_message('error', 'Không tìm thấy người dùng.'); 
    echo '<script>window.location.href="index.php?page=users/list";</script>';
    exit;
}
?>

<div class="page-header">
    <h2><i class="fas fa-user-edit"></i> Sửa Người dùngg: <?php echo htmlspecialchars($u['username']); ?></h2>
    <div class="header-actions">
        <a href="index.php?page=users/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <button type="submit" form="edit-user-form" class="btn btn-primary"><i class="fas fa-save"></i> Cập nhật</button>
    </div>
</div>

<div class="form-container" style="max-width: 600px;">
    <form action="index.php?page=users/edit&id=<?php echo $id; ?>" method="POST" id="edit-user-form">
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-user-shield"></i> Thông tin Tài khoản</h3>
            </div>
            <div class="card-body-custom" style="padding: 20px;">
                <div class="form-group">
                    <label>Tên đăng nhập</label>
                    <input type="text" value="<?php echo htmlspecialchars($u['username']); ?>" disabled style="background: #f1f5f9; color: #64748b;">
                </div>
                <div class="form-group">
                    <label>Họ và tên</label>
                    <input type="text" name="fullname" value="<?php echo htmlspecialchars($u['fullname']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Mật khẩu mới (Để trống nếu không đổi)</label>
                    <input type="password" name="password" placeholder="********">
                </div>
                <div class="form-group">
                    <label>Vai trò</label>
                    <select name="role">
                        <option value="xem" <?php echo $u['role'] == 'xem' ? 'selected' : ''; ?>>Xem (Chỉ xem)</option>
                        <option value="it" <?php echo $u['role'] == 'it' ? 'selected' : ''; ?>>IT (Quản lý thiết bị)</option>
                        <option value="admin" <?php echo $u['role'] == 'admin' ? 'selected' : ''; ?>>Admin (Toàn quyền)</option>
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