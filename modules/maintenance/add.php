<?php
// Fetch devices for dropdown
$devices_stmt = $pdo->query("SELECT id, ma_tai_san, ten_thiet_bi FROM devices ORDER BY ten_thiet_bi");
$devices = $devices_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    if (empty($_POST['device_id'])) {
        set_message('error', 'Thiết bị là bắt buộc.');
    }
    if (empty($_POST['ngay_su_co'])) {
        set_message('error', 'Ngày sự cố là bắt buộc.');
    }

    // Only proceed if no errors have been set
    if (!isset($_SESSION['messages']) || empty(array_filter($_SESSION['messages'], function($msg) { return $msg['type'] === 'error'; }))) {
        try {
            $sql = "INSERT INTO maintenance_logs (
                        device_id, ngay_su_co, noi_dung, hu_hong, xu_ly, chi_phi
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?
                    )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['device_id'],
                $_POST['ngay_su_co'],
                $_POST['noi_dung'],
                $_POST['hu_hong'],
                $_POST['xu_ly'],
                $_POST['chi_phi'] ?: null
            ]);
            set_message('success', 'Nhật ký bảo trì đã được thêm mới thành công!');
            header("Location: index.php?page=maintenance/history");
            exit;
        } catch (PDOException $e) {
            set_message('error', 'Lỗi khi thêm nhật ký bảo trì: ' . $e->getMessage());
        }
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-plus-circle"></i> Thêm Nhật ký Bảo trì</h2>
    <a href="index.php?page=maintenance/history" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
</div>

<div class="card form-container">
    <form action="index.php?page=maintenance/add" method="POST" class="form-grid">
        <div class="form-group full-width">
            <label for="device_id">Thiết bị <span class="required">*</span></label>
            <select id="device_id" name="device_id" required class="select-searchable">
                <option value="">-- Chọn thiết bị cần bảo trì --</option>
                <?php 
                $preselected_device = $_GET['device_id'] ?? ($_POST['device_id'] ?? '');
                foreach ($devices as $device): 
                ?>
                    <option value="<?php echo $device['id']; ?>" <?php echo ($preselected_device == $device['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($device['ma_tai_san'] . ' - ' . $device['ten_thiet_bi']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="ngay_su_co">Ngày sự cố <span class="required">*</span></label>
            <input type="date" id="ngay_su_co" name="ngay_su_co" required value="<?php echo htmlspecialchars($_POST['ngay_su_co'] ?? date('Y-m-d')); ?>">
        </div>

        <div class="form-group">
            <label for="chi_phi">Chi phí (VNĐ)</label>
            <input type="number" id="chi_phi" name="chi_phi" step="1000" min="0" placeholder="0" value="<?php echo htmlspecialchars($_POST['chi_phi'] ?? ''); ?>">
        </div>

        <div class="form-group full-width">
            <label for="noi_dung">Mô tả sự cố (Hiện tượng)</label>
            <textarea id="noi_dung" name="noi_dung" rows="3" placeholder="Mô tả chi tiết sự cố..."><?php echo htmlspecialchars($_POST['noi_dung'] ?? ''); ?></textarea>
        </div>

        <div class="form-group full-width">
            <label for="hu_hong">Nguyên nhân / Hư hỏng thực tế</label>
            <textarea id="hu_hong" name="hu_hong" rows="3" placeholder="Xác định nguyên nhân hoặc linh kiện hỏng..."><?php echo htmlspecialchars($_POST['hu_hong'] ?? ''); ?></textarea>
        </div>

        <div class="form-group full-width">
            <label for="xu_ly">Biện pháp Xử lý / Sửa chữa</label>
            <textarea id="xu_ly" name="xu_ly" rows="3" placeholder="Các bước đã thực hiện..."><?php echo htmlspecialchars($_POST['xu_ly'] ?? ''); ?></textarea>
        </div>

        <div class="form-actions">
            <a href="index.php?page=maintenance/history" class="btn btn-secondary">Hủy</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Phiếu</button>
        </div>
    </form>
</div>
