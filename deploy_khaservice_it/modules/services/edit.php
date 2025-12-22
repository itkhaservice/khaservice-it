<?php
$id = $_GET['id'] ?? null;
if (!$id) { header("Location: index.php?page=services/list"); exit; }

$stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
$stmt->execute([$id]);
$service = $stmt->fetch();

if (!$service) { header("Location: index.php?page=services/list"); exit; }

$projects = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an")->fetchAll();
$suppliers = $pdo->query("SELECT id, ten_npp FROM suppliers ORDER BY ten_npp")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("UPDATE services SET ten_dich_vu=?, loai_dich_vu=?, supplier_id=?, project_id=?, ngay_dang_ky=?, ngay_het_han=?, chi_phi_gia_han=?, nhac_truoc_ngay=?, ghi_chu=? WHERE id=?");
        $stmt->execute([
            $_POST['ten_dich_vu'], $_POST['loai_dich_vu'],
            $_POST['supplier_id'] ?: null, $_POST['project_id'] ?: null,
            $_POST['ngay_dang_ky'] ?: null, $_POST['ngay_het_han'],
            $_POST['chi_phi_gia_han'] ?: 0, $_POST['nhac_truoc_ngay'] ?: 30,
            $_POST['ghi_chu'], $id
        ]);
        set_message('success', 'Đã cập nhật dịch vụ!');
        header("Location: index.php?page=services/list");
        exit;
    } catch (PDOException $e) { set_message('error', 'Lỗi: ' . $e->getMessage()); }
}
?>

<div class="page-header">
    <h2><i class="fas fa-edit"></i> Sửa Dịch vụ: <?php echo htmlspecialchars($service['ten_dich_vu']); ?></h2>
    <div class="header-actions">
        <a href="index.php?page=services/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <button type="submit" form="edit-service-form" class="btn btn-primary">Cập nhật</button>
    </div>
</div>

<form action="index.php?page=services/edit&id=<?php echo $id; ?>" method="POST" id="edit-service-form" class="edit-layout">
    <div class="left-panel">
        <div class="card">
            <div class="card-header-custom"><h3>Thông tin chung</h3></div>
            <div class="card-body-custom">
                <div class="form-group"><label>Tên dịch vụ</label><input type="text" name="ten_dich_vu" required value="<?php echo htmlspecialchars($service['ten_dich_vu']); ?>"></div>
                <div class="form-group"><label>Loại dịch vụ</label><input type="text" name="loai_dich_vu" value="<?php echo htmlspecialchars($service['loai_dich_vu']); ?>"></div>
                <div class="form-group"><label>Nhà cung cấp</label>
                    <select name="supplier_id">
                        <option value="">-- Chọn nhà cung cấp --</option>
                        <?php foreach($suppliers as $sup): ?><option value="<?php echo $sup['id']; ?>" <?php echo ($service['supplier_id'] == $sup['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($sup['ten_npp']); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Dự án sử dụng</label>
                    <select name="project_id">
                        <option value="">-- Dùng chung --</option>
                        <?php foreach($projects as $p): ?><option value="<?php echo $p['id']; ?>" <?php echo ($service['project_id'] == $p['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['ten_du_an']); ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="right-panel">
        <div class="card">
            <div class="card-header-custom"><h3>Thời hạn</h3></div>
            <div class="card-body-custom">
                <div class="form-group"><label>Ngày hết hạn</label><input type="date" name="ngay_het_han" required value="<?php echo $service['ngay_het_han']; ?>"></div>
                <div class="form-group"><label>Chi phí gia hạn</label><input type="number" name="chi_phi_gia_han" value="<?php echo $service['chi_phi_gia_han']; ?>"></div>
                <div class="form-group"><label>Nhắc trước (ngày)</label><input type="number" name="nhac_truoc_ngay" value="<?php echo $service['nhac_truoc_ngay']; ?>"></div>
                <div class="form-group"><label>Ghi chú</label><textarea name="ghi_chu" rows="4"><?php echo htmlspecialchars($service['ghi_chu']); ?></textarea></div>
            </div>
        </div>
    </div>
</form>
