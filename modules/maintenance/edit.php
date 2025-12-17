<?php
$log_id = $_GET['id'] ?? null;
$log = null;

if ($log_id) {
    $stmt = $pdo->prepare("SELECT * FROM maintenance_logs WHERE id = ?");
    $stmt->execute([$log_id]);
    $log = $stmt->fetch();
}

if (!$log) {
    echo "<p class='error'>Nhật ký bảo trì không tìm thấy!</p>";
    echo "<a href='index.php?page=maintenance/history' class='btn btn-secondary'>Quay lại lịch sử</a>";
    exit;
}

// Fetch devices for dropdown
$devices_stmt = $pdo->query("SELECT id, ma_tai_san, ten_thiet_bi FROM devices ORDER BY ten_thiet_bi");
$devices = $devices_stmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    if (empty($_POST['device_id'])) {
        $errors[] = 'Thiết bị là bắt buộc.';
    }
    if (empty($_POST['ngay_su_co'])) {
        $errors[] = 'Ngày sự cố là bắt buộc.';
    }

    if (empty($errors)) {
        $sql = "UPDATE maintenance_logs SET
                    device_id = ?, ngay_su_co = ?, noi_dung = ?, hu_hong = ?, xu_ly = ?, chi_phi = ?
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['device_id'],
            $_POST['ngay_su_co'],
            $_POST['noi_dung'],
            $_POST['hu_hong'],
            $_POST['xu_ly'],
            $_POST['chi_phi'] ?: null,
            $log_id
        ]);

        header("Location: index.php?page=maintenance/history");
        exit;
    }
}
?>

<h2>Sửa Nhật ký Bảo trì</h2>

<?php if (!empty($errors)): ?>
    <div class="error">
        <?php foreach ($errors as $error): ?>
            <p><?php echo $error; ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="form-container">
    <form action="index.php?page=maintenance/edit&id=<?php echo $log_id; ?>" method="POST" class="form-grid">
        <div class="form-group">
            <label for="device_id">Thiết bị (*)</label>
            <select id="device_id" name="device_id" required>
                <option value="">-- Chọn thiết bị --</option>
                <?php foreach ($devices as $device): ?>
                    <option value="<?php echo $device['id']; ?>" <?php echo ($log['device_id'] == $device['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($device['ma_tai_san'] . ' - ' . $device['ten_thiet_bi']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="ngay_su_co">Ngày sự cố (*)</label>
            <input type="date" id="ngay_su_co" name="ngay_su_co" value="<?php echo htmlspecialchars($log['ngay_su_co']); ?>" required>
        </div>

        <div class="form-group full-width">
            <label for="noi_dung">Mô tả sự cố</label>
            <textarea id="noi_dung" name="noi_dung"><?php echo htmlspecialchars($log['noi_dung']); ?></textarea>
        </div>

        <div class="form-group full-width">
            <label for="hu_hong">Hư hỏng</label>
            <textarea id="hu_hong" name="hu_hong"><?php echo htmlspecialchars($log['hu_hong']); ?></textarea>
        </div>

        <div class="form-group full-width">
            <label for="xu_ly">Xử lý</label>
            <textarea id="xu_ly" name="xu_ly"><?php echo htmlspecialchars($log['xu_ly']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="chi_phi">Chi phí (VNĐ)</label>
            <input type="number" id="chi_phi" name="chi_phi" step="1000" value="<?php echo htmlspecialchars($log['chi_phi']); ?>">
        </div>

        <div class="form-actions">
            <a href="index.php?page=maintenance/history" class="btn btn-secondary">Hủy</a>
            <button type="submit" class="btn btn-primary">Cập nhật Nhật ký</button>
        </div>
    </form>
</div>
