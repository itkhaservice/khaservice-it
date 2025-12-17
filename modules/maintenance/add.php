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

<h2>Thêm Nhật ký Bảo trì mới</h2>


<div class="form-container">
    <form action="index.php?page=maintenance/add" method="POST" class="form-grid">
        <div class="form-group">
            <label for="device_id">Thiết bị (*)</label>
            <select id="device_id" name="device_id" required>
                <option value="">-- Chọn thiết bị --</option>
                <?php foreach ($devices as $device): ?>
                    <option value="<?php echo $device['id']; ?>" <?php echo (($_POST['device_id'] ?? '') == $device['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($device['ma_tai_san'] . ' - ' . $device['ten_thiet_bi']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="ngay_su_co">Ngày sự cố (*)</label>
            <input type="date" id="ngay_su_co" name="ngay_su_co" required value="<?php echo htmlspecialchars($_POST['ngay_su_co'] ?? ''); ?>">
        </div>

        <div class="form-group full-width">
            <label for="noi_dung">Mô tả sự cố</label>
            <textarea id="noi_dung" name="noi_dung"><?php echo htmlspecialchars($_POST['noi_dung'] ?? ''); ?></textarea>
        </div>

        <div class="form-group full-width">
            <label for="hu_hong">Hư hỏng</label>
            <textarea id="hu_hong" name="hu_hong"><?php echo htmlspecialchars($_POST['hu_hong'] ?? ''); ?></textarea>
        </div>

        <div class="form-group full-width">
            <label for="xu_ly">Xử lý</label>
            <textarea id="xu_ly" name="xu_ly"><?php echo htmlspecialchars($_POST['xu_ly'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label for="chi_phi">Chi phí (VNĐ)</label>
            <input type="number" id="chi_phi" name="chi_phi" step="1000" value="<?php echo htmlspecialchars($_POST['chi_phi'] ?? ''); ?>">
        </div>

        <div class="form-actions">
            <a href="index.php?page=maintenance/history" class="btn btn-secondary">Hủy</a>
            <button type="submit" class="btn btn-primary">Lưu Nhật ký</button>
        </div>
    </form>
</div>
