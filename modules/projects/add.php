<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['ma_du_an']) || empty($_POST['ten_du_an'])) {
        set_message('error', 'Vui lòng nhập Mã và Tên dự án.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO projects (ma_du_an, ten_du_an, dia_chi, loai_du_an, ghi_chu) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['ma_du_an'],
                $_POST['ten_du_an'],
                $_POST['dia_chi'],
                $_POST['loai_du_an'],
                $_POST['ghi_chu']
            ]);
            set_message('success', 'Thêm dự án thành công!');
            header("Location: index.php?page=projects/list");
            exit;
        } catch (PDOException $e) {
            set_message('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-plus-circle"></i> Thêm Dự án mới</h2>
    <div class="header-actions">
        <a href="index.php?page=projects/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <button type="submit" form="add-project-form" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Dự án</button>
    </div>
</div>

<div class="form-container">
    <form action="index.php?page=projects/add" method="POST" id="add-project-form">
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-building"></i> Thông tin Dự án</h3>
            </div>
            
            <div class="card-body-custom" style="padding: 20px;">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="ma_du_an">Mã Dự án <span class="required">*</span></label>
                        <input type="text" id="ma_du_an" name="ma_du_an" required placeholder="VD: DA01" value="<?php echo htmlspecialchars($_POST['ma_du_an'] ?? ''); ?>" class="input-highlight">
                    </div>
                    
                    <div class="form-group">
                        <label for="ten_du_an">Tên Dự án <span class="required">*</span></label>
                        <input type="text" id="ten_du_an" name="ten_du_an" required placeholder="VD: Chung cư Sóng Vàng" value="<?php echo htmlspecialchars($_POST['ten_du_an'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="loai_du_an">Loại Dự án</label>
                        <select name="loai_du_an" id="loai_du_an">
                            <option value="Chung cư">Chung cư</option>
                            <option value="Văn phòng">Văn phòng</option>
                            <option value="Khu dân cư">Khu dân cư</option>
                            <option value="Nhà kho">Nhà kho</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="dia_chi">Địa chỉ</label>
                        <input type="text" id="dia_chi" name="dia_chi" value="<?php echo htmlspecialchars($_POST['dia_chi'] ?? ''); ?>">
                    </div>

                    <div class="form-group full-width">
                        <label for="ghi_chu">Ghi chú</label>
                        <textarea id="ghi_chu" name="ghi_chu" rows="4"><?php echo htmlspecialchars($_POST['ghi_chu'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.card-header-custom {
    padding-bottom: 15px;
    margin-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
}
.card-header-custom h3 {
    margin: 0;
    font-size: 1.1rem;
    color: var(--text-color);
    display: flex; align-items: center; gap: 10px;
}
.input-highlight {
    background-color: #f8fafc;
    border-color: #cbd5e1;
    font-weight: 600;
    color: var(--primary-dark-color);
}
</style>