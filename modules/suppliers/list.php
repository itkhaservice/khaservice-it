<?php
// Fetch suppliers from the database
$stmt = $pdo->query("SELECT * FROM suppliers ORDER BY ten_npp");
$suppliers = $stmt->fetchAll();
?>

<div class="page-header">
    <h2><i class="fas fa-truck-loading"></i> Danh sách Nhà cung cấp</h2>
    <a href="index.php?page=suppliers/add" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm Nhà cung cấp</a>
</div>

<div class="table-container card">
    <table class="content-table">
        <thead>
            <tr>
                <th>Tên Nhà phân phối</th>
                <th>Người liên hệ</th>
                <th>Điện thoại</th>
                <th>Email</th>
                <th>Ghi chú</th>
                <th width="100" class="text-center">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($suppliers)): ?>
                <tr>
                    <td colspan="6" class="empty-state">
                        <i class="fas fa-boxes" style="font-size: 3rem; color: #e2e8f0; margin-bottom: 10px;"></i>
                        <p>Chưa có nhà cung cấp nào.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($suppliers as $supplier): ?>
                    <tr>
                        <td class="font-medium text-primary"><?php echo htmlspecialchars($supplier['ten_npp']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['nguoi_lien_he']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['dien_thoai']); ?></td>
                        <td>
                            <?php if ($supplier['email']): ?>
                                <a href="mailto:<?php echo htmlspecialchars($supplier['email']); ?>" class="link-primary"><?php echo htmlspecialchars($supplier['email']); ?></a>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?php echo htmlspecialchars($supplier['ghi_chu']); ?></td>
                        <td class="actions text-center">
                            <a href="index.php?page=suppliers/edit&id=<?php echo $supplier['id']; ?>" class="btn-icon" title="Sửa"><i class="fas fa-edit"></i></a>
                            <a href="index.php?page=suppliers/delete&id=<?php echo $supplier['id']; ?>" class="btn-icon delete-btn" title="Xóa"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>