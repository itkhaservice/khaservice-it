<?php
$id = $_GET['id'] ?? null;
if (!$id) { header("Location: index.php?page=suppliers/list"); exit; }

$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$id]);
$s = $stmt->fetch();

if (!$s) { set_message('error', 'Không tìm thấy.'); header("Location: index.php?page=suppliers/list"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['ten_npp'])) {
        set_message('error', 'Tên nhà phân phối là bắt buộc.');
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE suppliers SET ten_npp=?, nguoi_lien_he=?, dien_thoai=?, email=?, ghi_chu=? WHERE id=?");
            $stmt->execute([$_POST['ten_npp'], $_POST['nguoi_lien_he'], $_POST['dien_thoai'], $_POST['email'], $_POST['ghi_chu'], $id]);
            set_message('success', 'Cập nhật thành công!');
            header("Location: index.php?page=suppliers/list");
            exit;
        } catch (PDOException $e) {
            set_message('error', 'Lỗi: ' . $e->getMessage());
        }
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-edit"></i> Sửa Nhà cung cấp</h2>
    <div class="header-actions">
        <a href="index.php?page=suppliers/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <button type="submit" form="edit-sup-form" class="btn btn-primary"><i class="fas fa-save"></i> Cập nhật</button>
    </div>
</div>

<form action="index.php?page=suppliers/edit&id=<?php echo $id; ?>" method="POST" id="edit-sup-form" class="edit-layout">
    <div class="left-panel">
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-info-circle"></i> Thông tin Cơ bản</h3>
            </div>
            <div class="card-body-custom">
                <div class="form-group">
                    <label>Tên Nhà cung cấp <span class="required">*</span></label>
                    <input type="text" name="ten_npp" required value="<?php echo htmlspecialchars($s['ten_npp']); ?>" class="input-highlight">
                </div>
                <div class="form-group">
                    <label>Người liên hệ</label>
                    <input type="text" name="nguoi_lien_he" value="<?php echo htmlspecialchars($s['nguoi_lien_he']); ?>">
                </div>
                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea name="ghi_chu" rows="5"><?php echo htmlspecialchars($s['ghi_chu']); ?></textarea>
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
                    <input type="text" name="dien_thoai" value="<?php echo htmlspecialchars($s['dien_thoai']); ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($s['email']); ?>">
                </div>
            </div>
        </div>
    </div>
</form>