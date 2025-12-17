<?php
$supplier_id = $_GET['id'] ?? null;
$supplier = null;

if ($supplier_id) {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch();
}

if (!$supplier) {
    set_message('error', 'Nhà cung cấp không tìm thấy!');
    header("Location: index.php?page=suppliers/list");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    if (empty($_POST['ten_npp'])) {
        set_message('error', 'Tên nhà phân phối là bắt buộc.');
    }

    // Only proceed if no errors have been set
    if (!isset($_SESSION['messages']) || empty(array_filter($_SESSION['messages'], function($msg) { return $msg['type'] === 'error'; }))) {
        try {
            $sql = "UPDATE suppliers SET
                        ten_npp = ?, nguoi_lien_he = ?, dien_thoai = ?, email = ?, ghi_chu = ?
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['ten_npp'],
                $_POST['nguoi_lien_he'],
                $_POST['dien_thoai'],
                $_POST['email'],
                $_POST['ghi_chu'],
                $supplier_id
            ]);
            set_message('success', 'Nhà cung cấp đã được cập nhật thành công!');
            header("Location: index.php?page=suppliers/list");
            exit;
        } catch (PDOException $e) {
            set_message('error', 'Lỗi khi cập nhật nhà cung cấp: ' . $e->getMessage());
        }
    }
}
?>

<h2>Sửa Nhà cung cấp: <?php echo htmlspecialchars($supplier['ten_npp']); ?></h2>


<div class="form-container">
    <form action="index.php?page=suppliers/edit&id=<?php echo $supplier_id; ?>" method="POST" class="form-grid">
        <div class="form-group">
            <label for="ten_npp">Tên Nhà phân phối (*)</label>
            <input type="text" id="ten_npp" name="ten_npp" value="<?php echo htmlspecialchars($supplier['ten_npp']); ?>" required>
        </div>
        <div class="form-group">
            <label for="nguoi_lien_he">Người liên hệ</label>
            <input type="text" id="nguoi_lien_he" name="nguoi_lien_he" value="<?php echo htmlspecialchars($supplier['nguoi_lien_he']); ?>">
        </div>

        <div class="form-group">
            <label for="dien_thoai">Điện thoại</label>
            <input type="text" id="dien_thoai" name="dien_thoai" value="<?php echo htmlspecialchars($supplier['dien_thoai']); ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($supplier['email']); ?>">
        </div>

        <div class="form-group full-width">
            <label for="ghi_chu">Ghi chú</label>
            <textarea id="ghi_chu" name="ghi_chu"><?php echo htmlspecialchars($supplier['ghi_chu']); ?></textarea>
        </div>

        <div class="form-actions">
            <a href="index.php?page=suppliers/list" class="btn btn-secondary">Hủy</a>
            <button type="submit" class="btn btn-primary">Cập nhật Nhà cung cấp</button>
        </div>
    </form>
</div>
