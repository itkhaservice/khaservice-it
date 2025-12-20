<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    if (empty($_POST['ma_du_an'])) {
        set_message('error', 'Mã dự án là bắt buộc.');
    }
    if (empty($_POST['ten_du_an'])) {
        set_message('error', 'Tên dự án là bắt buộc.');
    }

    // Only proceed if no errors have been set
    if (!isset($_SESSION['messages']) || empty(array_filter($_SESSION['messages'], function($msg) { return $msg['type'] === 'error'; }))) {
        try {
            $sql = "INSERT INTO projects (ma_du_an, ten_du_an, dia_chi, loai_du_an, ghi_chu) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['ma_du_an'],
                $_POST['ten_du_an'],
                $_POST['dia_chi'],
                $_POST['loai_du_an'],
                $_POST['ghi_chu']
            ]);
            set_message('success', 'Dự án đã được thêm mới thành công!');
            header("Location: index.php?page=projects/list");
            exit;
        } catch (PDOException $e) {
            set_message('error', 'Lỗi khi thêm dự án: ' . $e->getMessage());
        }
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-plus-circle"></i> Thêm Dự án mới</h2>
    <a href="index.php?page=projects/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
</div>

<div class="card form-container">
    <form action="index.php?page=projects/add" method="POST" class="form-grid">
        <div class="form-group">
            <label for="ma_du_an">Mã Dự án <span class="required">*</span></label>
            <input type="text" id="ma_du_an" name="ma_du_an" required value="<?php echo htmlspecialchars($_POST['ma_du_an'] ?? ''); ?>" placeholder="VD: DA-01">
        </div>
        <div class="form-group">
            <label for="ten_du_an">Tên Dự án <span class="required">*</span></label>
            <input type="text" id="ten_du_an" name="ten_du_an" required value="<?php echo htmlspecialchars($_POST['ten_du_an'] ?? ''); ?>" placeholder="VD: Tòa nhà A">
        </div>

        <div class="form-group full-width">
            <label for="dia_chi">Địa chỉ</label>
            <textarea id="dia_chi" name="dia_chi" rows="2"><?php echo htmlspecialchars($_POST['dia_chi'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label for="loai_du_an">Loại Dự án</label>
            <input type="text" id="loai_du_an" name="loai_du_an" placeholder="Chung cư, Văn phòng..." value="<?php echo htmlspecialchars($_POST['loai_du_an'] ?? ''); ?>">
        </div>

        <div class="form-group full-width">
            <label for="ghi_chu">Ghi chú</label>
            <textarea id="ghi_chu" name="ghi_chu" rows="3"><?php echo htmlspecialchars($_POST['ghi_chu'] ?? ''); ?></textarea>
        </div>

        <div class="form-actions">
            <a href="index.php?page=projects/list" class="btn btn-secondary">Hủy</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Dự án</button>
        </div>
    </form>
</div>
