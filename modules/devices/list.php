<?php
// Fetch devices from the database
$stmt = $pdo->query("
    SELECT
        d.id,
        d.ma_tai_san,
        d.ten_thiet_bi,
        d.trang_thai,
        p.ten_du_an,
        s.ten_npp
    FROM devices d
    LEFT JOIN projects p ON d.project_id = p.id
    LEFT JOIN suppliers s ON d.supplier_id = s.id
    ORDER BY d.created_at DESC
");
$devices = $stmt->fetchAll();
?>

<h2>Danh sách Thiết bị</h2>

<a href="index.php?page=devices/add" class="add-button btn btn-primary">Thêm thiết bị mới</a>

<div class="content-table-wrapper">
    <table class="content-table">
        <thead>
            <tr>
                <th>Mã Tài sản</th>
                <th>Tên Thiết bị</th>
                <th>Dự án</th>
                <th>Nhà cung cấp</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
    <tbody>
        <?php if (empty($devices)): ?>
            <tr>
                <td colspan="6" style="text-align: center;">Chưa có thiết bị nào.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($devices as $device): ?>
                <tr>
                    <td><?php echo htmlspecialchars($device['ma_tai_san']); ?></td>
                    <td><?php echo htmlspecialchars($device['ten_thiet_bi']); ?></td>
                    <td><?php echo htmlspecialchars($device['ten_du_an']); ?></td>
                    <td><?php echo htmlspecialchars($device['ten_npp']); ?></td>
                    <td><?php echo htmlspecialchars($device['trang_thai']); ?></td>
                    <td class="actions">
                        <a href="index.php?page=devices/view&id=<?php echo $device['id']; ?>" class="btn view-btn">Xem</a>
                        <a href="index.php?page=devices/edit&id=<?php echo $device['id']; ?>" class="btn edit-btn">Sửa</a>
                        <a href="index.php?page=devices/delete&id=<?php echo $device['id']; ?>" class="btn delete-btn" onclick="return confirm('Bạn có chắc muốn xóa thiết bị này?');">Xóa</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
</div>