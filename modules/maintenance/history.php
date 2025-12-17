<?php
// Fetch maintenance logs from the database
$stmt = $pdo->query("
    SELECT
        ml.*,
        d.ma_tai_san,
        d.ten_thiet_bi
    FROM maintenance_logs ml
    LEFT JOIN devices d ON ml.device_id = d.id
    ORDER BY ml.ngay_su_co DESC
");
$logs = $stmt->fetchAll();
?>

<h2>Lịch sử Bảo trì Thiết bị</h2>

<a href="index.php?page=maintenance/add" class="add-button btn btn-primary">Thêm Nhật ký Bảo trì mới</a>

<div class="content-table-wrapper">
    <table class="content-table">
        <thead>
            <tr>
                <th>Thiết bị</th>
                <th>Mã Tài sản</th>
                <th>Ngày sự cố</th>
                <th>Mô tả sự cố</th>
                <th>Hư hỏng</th>
                <th>Xử lý</th>
                <th>Chi phí (VNĐ)</th>
                <th>Thao tác</th>
            </tr>
        </thead>
    <tbody>
        <?php if (empty($logs)): ?>
            <tr>
                <td colspan="8" style="text-align: center;">Chưa có nhật ký bảo trì nào.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo htmlspecialchars($log['ten_thiet_bi']); ?></td>
                    <td><?php echo htmlspecialchars($log['ma_tai_san']); ?></td>
                    <td><?php echo htmlspecialchars($log['ngay_su_co']); ?></td>
                    <td><?php echo htmlspecialchars($log['noi_dung']); ?></td>
                    <td><?php echo htmlspecialchars($log['hu_hong']); ?></td>
                    <td><?php echo htmlspecialchars($log['xu_ly']); ?></td>
                    <td><?php echo htmlspecialchars(number_format($log['chi_phi'], 0, ',', '.')); ?></td>
                    <td class="actions">
                        <a href="index.php?page=maintenance/edit&id=<?php echo $log['id']; ?>" class="btn edit-btn">Sửa</a>
                        <a href="index.php?page=maintenance/delete&id=<?php echo $log['id']; ?>" class="btn delete-btn" onclick="return confirm('Bạn có chắc muốn xóa nhật ký này?');">Xóa</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
</div>