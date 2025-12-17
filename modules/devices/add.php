<?php
// Fetch projects and suppliers for dropdowns
$projects_stmt = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an");
$projects = $projects_stmt->fetchAll();

$suppliers_stmt = $pdo->query("SELECT id, ten_npp FROM suppliers ORDER BY ten_npp");
$suppliers = $suppliers_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    if (empty($_POST['ma_tai_san'])) {
        set_message('error', 'Mã tài sản là bắt buộc.');
    }
    if (empty($_POST['ten_thiet_bi'])) {
        set_message('error', 'Tên thiết bị là bắt buộc.');
    }

    // Check if there are any errors before proceeding
    if (!isset($_SESSION['messages']) || empty(array_filter($_SESSION['messages'], function($msg) { return $msg['type'] === 'error'; }))) {
        try {
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
            set_message('success', 'Thiết bị đã được thêm mới thành công!');
            header("Location: index.php?page=devices/list");
            exit;
        } catch (PDOException $e) {
            set_message('error', 'Lỗi khi thêm thiết bị: ' . $e->getMessage());
        }
    }
}
?>

<h2>Thêm Thiết bị mới</h2>

<div class="form-container">
    <form action="index.php?page=devices/add" method="POST" class="form-grid">
        <div class="form-group">
            <label for="ma_tai_san">Mã Tài sản (*)</label>
            <input type="text" id="ma_tai_san" name="ma_tai_san" required value="<?php echo htmlspecialchars($_POST['ma_tai_san'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="ten_thiet_bi">Tên Thiết bị (*)</label>
            <input type="text" id="ten_thiet_bi" name="ten_thiet_bi" required value="<?php echo htmlspecialchars($_POST['ten_thiet_bi'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="nhom_thiet_bi">Nhóm Thiết bị</label>
            <select id="nhom_thiet_bi" name="nhom_thiet_bi">
                <option value="Văn phòng" <?php echo (($_POST['nhom_thiet_bi'] ?? '') == 'Văn phòng') ? 'selected' : ''; ?>>Văn phòng</option>
                <option value="Bãi xe" <?php echo (($_POST['nhom_thiet_bi'] ?? '') == 'Bãi xe') ? 'selected' : ''; ?>>Bãi xe</option>
            </select>
        </div>

        <div class="form-group">
            <label for="loai_thiet_bi">Loại Thiết bị</label>
            <input type="text" id="loai_thiet_bi" name="loai_thiet_bi" placeholder="PC, UPS, Camera..." value="<?php echo htmlspecialchars($_POST['loai_thiet_bi'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="model">Model</label>
            <input type="text" id="model" name="model" value="<?php echo htmlspecialchars($_POST['model'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="serial">Serial Number</label>
            <input type="text" id="serial" name="serial" value="<?php echo htmlspecialchars($_POST['serial'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="project_id">Dự án</label>
            <select id="project_id" name="project_id">
                <option value="">-- Chọn dự án --</option>
                <?php foreach ($projects as $project): ?>
                    <option value="<?php echo $project['id']; ?>" <?php echo (($_POST['project_id'] ?? '') == $project['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($project['ten_du_an']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="supplier_id">Nhà cung cấp</label>
            <select id="supplier_id" name="supplier_id">
                <option value="">-- Chọn nhà cung cấp --</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?php echo $supplier['id']; ?>" <?php echo (($_POST['supplier_id'] ?? '') == $supplier['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($supplier['ten_npp']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="ngay_mua">Ngày mua</label>
            <input type="date" id="ngay_mua" name="ngay_mua" value="<?php echo htmlspecialchars($_POST['ngay_mua'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="gia_mua">Giá mua (VNĐ)</label>
            <input type="number" id="gia_mua" name="gia_mua" step="1000" value="<?php echo htmlspecialchars($_POST['gia_mua'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="bao_hanh_den">Bảo hành đến</label>
            <input type="date" id="bao_hanh_den" name="bao_hanh_den" value="<?php echo htmlspecialchars($_POST['bao_hanh_den'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="trang_thai">Trạng thái</label>
            <select id="trang_thai" name="trang_thai">
                <option value="Đang sử dụng" <?php echo (($_POST['trang_thai'] ?? '') == 'Đang sử dụng') ? 'selected' : ''; ?>>Đang sử dụng</option>
                <option value="Hỏng" <?php echo (($_POST['trang_thai'] ?? '') == 'Hỏng') ? 'selected' : ''; ?>>Hỏng</option>
                <option value="Thanh lý" <?php echo (($_POST['trang_thai'] ?? '') == 'Thanh lý') ? 'selected' : ''; ?>>Thanh lý</option>
            </select>
        </div>

        <div class="form-group full-width">
            <label for="ghi_chu">Ghi chú</label>
            <textarea id="ghi_chu" name="ghi_chu"><?php echo htmlspecialchars($_POST['ghi_chu'] ?? ''); ?></textarea>
        </div>

        <div class="form-actions">
            <a href="index.php?page=devices/list" class="btn btn-secondary">Hủy</a>
            <button type="submit" class="btn btn-primary">Lưu Thiết bị</button>
        </div>
    </form>
</div>