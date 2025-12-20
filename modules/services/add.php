<?php
$projects = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an")->fetchAll();
$suppliers = $pdo->query("SELECT id, ten_npp FROM suppliers ORDER BY ten_npp")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['ten_dich_vu']) || empty($_POST['ngay_het_han'])) {
        set_message('error', 'Vui lòng điền tên dịch vụ và ngày hết hạn.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO services (ten_dich_vu, loai_dich_vu, supplier_id, project_id, ngay_dang_ky, ngay_het_han, chi_phi_gia_han, nhac_truoc_ngay, ghi_chu) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['ten_dich_vu'], $_POST['loai_dich_vu'],
                $_POST['supplier_id'] ?: null, $_POST['project_id'] ?: null,
                $_POST['ngay_dang_ky'] ?: null, $_POST['ngay_het_han'],
                $_POST['chi_phi_gia_han'] ?: 0, $_POST['nhac_truoc_ngay'] ?: 30,
                $_POST['ghi_chu']
            ]);
            set_message('success', 'Đã thêm dịch vụ thành công!');
            header("Location: index.php?page=services/list");
            exit;
        } catch (PDOException $e) { set_message('error', 'Lỗi: ' . $e->getMessage()); }
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-plus-circle"></i> Thêm Dịch vụ mới</h2>
    <div class="header-actions">
        <a href="index.php?page=services/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <button type="submit" form="add-service-form" class="btn btn-primary">Lưu dịch vụ</button>
    </div>
</div>

<form action="index.php?page=services/add" method="POST" id="add-service-form" class="edit-layout">
    <div class="left-panel">
        <div class="card">
            <div class="card-header-custom"><h3><i class="fas fa-info-circle"></i> Thông tin chung</h3></div>
            <div class="card-body-custom">
                <div class="form-group"><label>Tên dịch vụ <span class="required">*</span></label><input type="text" name="ten_dich_vu" required placeholder="VD: Google Workspace, Domain khaservice.vn"></div>
                <div class="form-group"><label>Loại dịch vụ</label><input type="text" name="loai_dich_vu" placeholder="VD: Cloud, Mail, Domain, Hosting..."></div>
                <div class="form-group"><label>Nhà cung cấp</label>
                    <select name="supplier_id">
                        <option value="">-- Chọn nhà cung cấp --</option>
                        <?php foreach($suppliers as $sup): ?><option value="<?php echo $sup['id']; ?>"><?php echo htmlspecialchars($sup['ten_npp']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Dự án sử dụng</label>
                    <select name="project_id">
                        <option value="">-- Dùng chung / Toàn công ty --</option>
                        <?php foreach($projects as $p): ?><option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['ten_du_an']); ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="right-panel">
        <div class="card">
            <div class="card-header-custom"><h3><i class="fas fa-calendar-alt"></i> Thời hạn & Chi phí</h3></div>
            <div class="card-body-custom">
                <div class="form-group"><label>Ngày hết hạn <span class="required">*</span></label><input type="date" name="ngay_het_han" required></div>
                <div class="form-group"><label>Chi phí gia hạn (VNĐ)</label><input type="number" name="chi_phi_gia_han" value="0"></div>
                <div class="form-group"><label>Nhắc trước (ngày)</label><input type="number" name="nhac_truoc_ngay" value="30"></div>
                <div class="form-group"><label>Ghi chú</label><textarea name="ghi_chu" rows="4"></textarea></div>
            </div>
        </div>
    </div>
</form>
