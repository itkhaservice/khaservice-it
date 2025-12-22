<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['ten_npp'])) {
        set_message('error', 'Tên nhà phân phối là bắt buộc.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO suppliers (ten_npp, nguoi_lien_he, dien_thoai, email, ghi_chu) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['ten_npp'],
                $_POST['nguoi_lien_he'],
                $_POST['dien_thoai'],
                $_POST['email'],
                $_POST['ghi_chu']
            ]);
            set_message('success', 'Thêm nhà cung cấp thành công!');
            header("Location: index.php?page=suppliers/list");
            exit;
        } catch (PDOException $e) {
            set_message('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-plus-circle"></i> Thêm Nhà cung cấp</h2>
    <div class="header-actions">
        <a href="index.php?page=suppliers/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <button type="submit" form="add-sup-form" class="btn btn-primary"><i class="fas fa-save"></i> Lưu</button>
    </div>
</div>

<form action="index.php?page=suppliers/add" method="POST" id="add-sup-form" class="edit-layout">
    <div class="left-panel">
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-info-circle"></i> Thông tin Cơ bản</h3>
            </div>
            <div class="card-body-custom">
                <div class="form-group">
                    <label>Tên Nhà cung cấp <span class="required">*</span></label>
                    <input type="text" name="ten_npp" required value="<?php echo htmlspecialchars($_POST['ten_npp'] ?? ''); ?>" class="input-highlight">
                </div>
                <div class="form-group">
                    <label>Người liên hệ</label>
                    <input type="text" name="nguoi_lien_he" value="<?php echo htmlspecialchars($_POST['nguoi_lien_he'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea name="ghi_chu" rows="5"><?php echo htmlspecialchars($_POST['ghi_chu'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="right-panel">
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-address-book"></i> Thông tin Liên hệ</h3>
            </div>
            <div class="card-body-custom">
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="text" name="dien_thoai" value="<?php echo htmlspecialchars($_POST['dien_thoai'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>
</form>