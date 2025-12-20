<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    if (empty($_POST['ten_npp'])) {
        set_message('error', 'Tên nhà phân phối là bắt buộc.');
    }

    // Only proceed if no errors have been set
    if (!isset($_SESSION['messages']) || empty(array_filter($_SESSION['messages'], function($msg) { return $msg['type'] === 'error'; }))) {
        try {
            $sql = "INSERT INTO suppliers (ten_npp, nguoi_lien_he, dien_thoai, email, ghi_chu) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['ten_npp'],
                $_POST['nguoi_lien_he'],
                $_POST['dien_thoai'],
                $_POST['email'],
                $_POST['ghi_chu']
            ]);
            set_message('success', 'Nhà cung cấp đã được thêm mới thành công!');
            header("Location: index.php?page=suppliers/list");
            exit;
        } catch (PDOException $e) {
            set_message('error', 'Lỗi khi thêm nhà cung cấp: ' . $e->getMessage());
        }
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-plus-circle"></i> Thêm Nhà cung cấp</h2>
    <a href="index.php?page=suppliers/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
</div>

<div class="card form-container">
    <form action="index.php?page=suppliers/add" method="POST" class="form-grid">
        <div class="form-group full-width">
            <label for="ten_npp">Tên Nhà phân phối <span class="required">*</span></label>
            <input type="text" id="ten_npp" name="ten_npp" required placeholder="VD: Công ty TNHH ABC">
        </div>
        <div class="form-group">
            <label for="nguoi_lien_he">Người liên hệ</label>
            <input type="text" id="nguoi_lien_he" name="nguoi_lien_he" placeholder="Họ tên người đại diện">
        </div>

        <div class="form-group">
            <label for="dien_thoai">Điện thoại</label>
            <input type="text" id="dien_thoai" name="dien_thoai" placeholder="Số điện thoại liên hệ">
        </div>

        <div class="form-group full-width">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="contact@company.com">
        </div>

        <div class="form-group full-width">
            <label for="ghi_chu">Ghi chú</label>
            <textarea id="ghi_chu" name="ghi_chu" rows="3"></textarea>
        </div>

        <div class="form-actions">
            <a href="index.php?page=suppliers/list" class="btn btn-secondary">Hủy</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Nhà cung cấp</button>
        </div>
    </form>
</div>
