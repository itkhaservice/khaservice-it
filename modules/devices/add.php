<?php
// Fetch projects and suppliers for dropdowns
$projects_stmt = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an");
$projects = $projects_stmt->fetchAll();

$suppliers_stmt = $pdo->query("SELECT id, ten_npp FROM suppliers ORDER BY ten_npp");
$suppliers = $suppliers_stmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    if (empty($_POST['ma_tai_san'])) {
        $errors[] = 'Mã tài sản là bắt buộc.';
    }
    if (empty($_POST['ten_thiet_bi'])) {
        $errors[] = 'Tên thiết bị là bắt buộc.';
    }

    if (empty($errors)) {
        $sql = "INSERT INTO devices (
                    ma_tai_san, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, model, serial,
                    project_id, supplier_id, ngay_mua, gia_mua, bao_hanh_den, trang_thai, ghi_chu
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['ma_tai_san'],
            $_POST['ten_thiet_bi'],
            $_POST['nhom_thiet_bi'],
            $_POST['loai_thiet_bi'],
            $_POST['model'],
            $_POST['serial'],
            $_POST['project_id'] ?: null,
            $_POST['supplier_id'] ?: null,
            $_POST['ngay_mua'] ?: null,
            $_POST['gia_mua'] ?: null,
            $_POST['bao_hanh_den'] ?: null,
            $_POST['trang_thai'],
            $_POST['ghi_chu']
        ]);

        header("Location: index.php?page=devices/list");
        exit;
    }
}
?>

<h2>Thêm Thiết bị mới</h2>

<?php if (!empty($errors)): ?>
    <div class="error">
        <?php foreach ($errors as $error): ?>
            <p><?php echo $error; ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="form-container">
    <form action="index.php?page=devices/add" method="POST" class="form-grid">
        <div class="form-group">
            <label for="ma_tai_san">Mã Tài sản (*)</label>
            <input type="text" id="ma_tai_san" name="ma_tai_san" required>
        </div>
        <div class="form-group">
            <label for="ten_thiet_bi">Tên Thiết bị (*)</label>
            <input type="text" id="ten_thiet_bi" name="ten_thiet_bi" required>
        </div>

        <div class="form-group">
            <label for="nhom_thiet_bi">Nhóm Thiết bị</label>
            <select id="nhom_thiet_bi" name="nhom_thiet_bi">
                <option value="Văn phòng">Văn phòng</option>
                <option value="Bãi xe">Bãi xe</option>
            </select>
        </div>

        <div class="form-group">
            <label for="loai_thiet_bi">Loại Thiết bị</label>
            <input type="text" id="loai_thiet_bi" name="loai_thiet_bi" placeholder="PC, UPS, Camera...">
        </div>

        <div class="form-group">
            <label for="model">Model</label>
            <input type="text" id="model" name="model">
        </div>

        <div class="form-group">
            <label for="serial">Serial Number</label>
            <input type="text" id="serial" name="serial">
        </div>

        <div class="form-group">
            <label for="project_id">Dự án</label>
            <select id="project_id" name="project_id">
                <option value="">-- Chọn dự án --</option>
                <?php foreach ($projects as $project): ?>
                    <option value="<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['ten_du_an']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="supplier_id">Nhà cung cấp</label>
            <select id="supplier_id" name="supplier_id">
                <option value="">-- Chọn nhà cung cấp --</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?php echo $supplier['id']; ?>"><?php echo htmlspecialchars($supplier['ten_npp']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="ngay_mua">Ngày mua</label>
            <input type="date" id="ngay_mua" name="ngay_mua">
        </div>

        <div class="form-group">
            <label for="gia_mua">Giá mua (VNĐ)</label>
            <input type="number" id="gia_mua" name="gia_mua" step="1000">
        </div>

        <div class="form-group">
            <label for="bao_hanh_den">Bảo hành đến</label>
            <input type="date" id="bao_hanh_den" name="bao_hanh_den">
        </div>

        <div class="form-group">
            <label for="trang_thai">Trạng thái</label>
            <select id="trang_thai" name="trang_thai">
                <option value="Đang sử dụng">Đang sử dụng</option>
                <option value="Hỏng">Hỏng</option>
                <option value="Thanh lý">Thanh lý</option>
            </select>
        </div>

        <div class="form-group full-width">
            <label for="ghi_chu">Ghi chú</label>
            <textarea id="ghi_chu" name="ghi_chu"></textarea>
        </div>

        <div class="form-actions">
            <a href="index.php?page=devices/list" class="btn btn-secondary">Hủy</a>
            <button type="submit" class="btn btn-primary">Lưu Thiết bị</button>
        </div>
    </form>
</div>
