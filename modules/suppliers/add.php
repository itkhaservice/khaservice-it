<?php
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    if (empty($_POST['ten_npp'])) {
        $errors[] = 'Tên nhà phân phối là bắt buộc.';
    }

    if (empty($errors)) {
        $sql = "INSERT INTO suppliers (ten_npp, nguoi_lien_he, dien_thoai, email, ghi_chu) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['ten_npp'],
            $_POST['nguoi_lien_he'],
            $_POST['dien_thoai'],
            $_POST['email'],
            $_POST['ghi_chu']
        ]);

        header("Location: index.php?page=suppliers/list");
        exit;
    }
}
?>

<h2>Thêm Nhà cung cấp mới</h2>

<?php if (!empty($errors)): ?>
    <div class="error">
        <?php foreach ($errors as $error): ?>
            <p><?php echo $error; ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="form-container">
    <form action="index.php?page=suppliers/add" method="POST" class="form-grid">
        <div class="form-group">
            <label for="ten_npp">Tên Nhà phân phối (*)</label>
            <input type="text" id="ten_npp" name="ten_npp" required>
        </div>
        <div class="form-group">
            <label for="nguoi_lien_he">Người liên hệ</label>
            <input type="text" id="nguoi_lien_he" name="nguoi_lien_he">
        </div>

        <div class="form-group">
            <label for="dien_thoai">Điện thoại</label>
            <input type="text" id="dien_thoai" name="dien_thoai">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email">
        </div>

        <div class="form-group full-width">
            <label for="ghi_chu">Ghi chú</label>
            <textarea id="ghi_chu" name="ghi_chu"></textarea>
        </div>

        <div class="form-actions">
            <a href="index.php?page=suppliers/list" class="btn btn-secondary">Hủy</a>
            <button type="submit" class="btn btn-primary">Lưu Nhà cung cấp</button>
        </div>
    </form>
</div>
