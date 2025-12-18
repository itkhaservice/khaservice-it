<?php
// Fetch suppliers from the database
$stmt = $pdo->query("SELECT * FROM suppliers ORDER BY ten_npp");
$suppliers = $stmt->fetchAll();
?>

<h2>Danh sách Nhà cung cấp</h2>

<a href="index.php?page=suppliers/add" class="add-button btn btn-primary">Thêm nhà cung cấp mới</a>

<div class="content-table-wrapper">
    <table class="content-table">
        <thead>
            <tr>
                <th>Tên Nhà phân phối</th>
                <th>Người liên hệ</th>
                <th>Điện thoại</th>
                <th>Email</th>
                <th>Ghi chú</th>
                <th>Thao tác</th>
            </tr>
        </thead>
    <tbody>
        <?php if (empty($suppliers)): ?>
            <tr>
                <td colspan="6" style="text-align: center;">Chưa có nhà cung cấp nào.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($suppliers as $supplier): ?>
                <tr>
                    <td><?php echo htmlspecialchars($supplier['ten_npp']); ?></td>
                    <td><?php echo htmlspecialchars($supplier['nguoi_lien_he']); ?></td>
                    <td><?php echo htmlspecialchars($supplier['dien_thoai']); ?></td>
                    <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                    <td><?php echo htmlspecialchars($supplier['ghi_chu']); ?></td>
                    <td class="actions">
                        <a href="index.php?page=suppliers/edit&id=<?php echo $supplier['id']; ?>" class="btn edit-btn">Sửa</a>
                        <a href="index.php?page=suppliers/delete&id=<?php echo $supplier['id']; ?>" class="btn delete-btn">Xóa</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
</div>