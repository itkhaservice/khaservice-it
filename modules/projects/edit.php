<?php
// XỬ LÝ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_GET['id'] ?? null;
    try {
        $stmt = $pdo->prepare("UPDATE projects SET ma_du_an=?, ten_du_an=?, dia_chi_duong=?, dia_chi_phuong_xa=?, dia_chi_tinh_tp=?, loai_du_an=?, ghi_chu=? WHERE id=?");
        $stmt->execute([
            $_POST['ma_du_an'],
            $_POST['ten_du_an'],
            $_POST['dia_chi_duong'],
            $_POST['dia_chi_phuong_xa'],
            $_POST['dia_chi_tinh_tp'],
            $_POST['loai_du_an'],
            $_POST['ghi_chu'],
            $id
        ]);
        set_message('success', 'Cập nhật dự án thành công!');
        echo '<script>window.location.href="index.php?page=projects/list";</script>';
        exit;
    } catch (PDOException $e) {
        set_message('error', 'Lỗi: ' . $e->getMessage());
    }
}

// LẤY DỮ LIỆU
$id = $_GET['id'] ?? null;
if (!$id) { echo '<script>window.location.href="index.php?page=projects/list";</script>'; exit; }

$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$id]);
$project = $stmt->fetch();

if (!$project) {
    set_message('error', 'Dự án không tìm thấy.');
    echo '<script>window.location.href="index.php?page=projects/list";</script>';
    exit;
}
?>

<div class="page-header">
    <h2><i class="fas fa-edit"></i> Sửa Dự án: <?php echo htmlspecialchars($project['ten_du_an']); ?></h2>
    <div class="header-actions">
        <a href="index.php?page=projects/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <button type="submit" form="edit-project-form" class="btn btn-primary"><i class="fas fa-save"></i> Cập nhật</button>
    </div>
</div>

<div class="form-container">
    <form action="index.php?page=projects/edit&id=<?php echo $id; ?>" method="POST" id="edit-project-form">
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-building"></i> Thông tin Dự án</h3>
            </div>
            
            <div class="card-body-custom" style="padding: 20px;">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="ma_du_an">Mã Dự án <span class="required">*</span></label>
                        <input type="text" id="ma_du_an" name="ma_du_an" required value="<?php echo htmlspecialchars($project['ma_du_an']); ?>" class="input-highlight">
                    </div>
                    
                    <div class="form-group">
                        <label for="ten_du_an">Tên Dự án <span class="required">*</span></label>
                        <input type="text" id="ten_du_an" name="ten_du_an" required value="<?php echo htmlspecialchars($project['ten_du_an']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="loai_du_an">Loại Dự án</label>
                        <select name="loai_du_an" id="loai_du_an">
                            <?php 
                            $types = ['Chung cư', 'Văn phòng', 'Khu dân cư', 'Nhà kho', 'Khác'];
                            $current_type = $project['loai_du_an'];
                            foreach($types as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo $current_type == $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label style="font-weight: 700; color: var(--primary-color); margin-bottom: 10px; display: block;">Địa chỉ chi tiết</label>
                        <div class="address-layout">
                            <div class="address-row full-width-row">
                                <div class="address-item">
                                    <label for="dia_chi_duong">Số nhà / Đường</label>
                                    <input type="text" id="dia_chi_duong" name="dia_chi_duong" placeholder="VD: 123 Lê Lợi" value="<?php echo htmlspecialchars($project['dia_chi_duong']); ?>">
                                </div>
                            </div>
                            <div class="address-row split-row">
                                <div class="address-item">
                                    <label for="dia_chi_phuong_xa">Phường / Xã</label>
                                    <input type="text" id="dia_chi_phuong_xa" name="dia_chi_phuong_xa" placeholder="VD: Phường 1" value="<?php echo htmlspecialchars($project['dia_chi_phuong_xa']); ?>">
                                </div>
                                <div class="address-item">
                                    <label for="dia_chi_tinh_tp">Quận / Huyện / Tỉnh / TP</label>
                                    <input type="text" id="dia_chi_tinh_tp" name="dia_chi_tinh_tp" placeholder="VD: Quận 1, TP.HCM" value="<?php echo htmlspecialchars($project['dia_chi_tinh_tp']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="ghi_chu">Ghi chú</label>
                        <textarea id="ghi_chu" name="ghi_chu" rows="4"><?php echo htmlspecialchars($project['ghi_chu']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.address-layout { display: flex; flex-direction: column; gap: 15px; }
.address-row { display: flex; gap: 20px; }
.address-item { flex: 1; }
.address-item label { font-size: 0.8rem; text-transform: uppercase; color: #94a3b8; font-weight: 600; margin-bottom: 6px; display: block; }
.card-header-custom { padding-bottom: 15px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); }
.card-header-custom h3 { margin: 0; font-size: 1.1rem; color: var(--text-color); display: flex; align-items: center; gap: 10px; }
.input-highlight { background-color: #f8fafc; border-color: #cbd5e1; font-weight: 600; color: var(--primary-dark-color); }
@media (max-width: 768px) { .address-row { flex-direction: column; gap: 15px; } }
</style>