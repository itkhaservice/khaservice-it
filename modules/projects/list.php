<?php
// Fetch projects from the database
$stmt = $pdo->query("SELECT * FROM projects ORDER BY ten_du_an");
$projects = $stmt->fetchAll();
?>

<h2>Danh sách Dự án</h2>

<a href="index.php?page=projects/add" class="add-button btn btn-primary">Thêm dự án mới</a>

<div class="content-table-wrapper">
    <table class="content-table">
        <thead>
            <tr>
                <th>Mã Dự án</th>
                <th>Tên Dự án</th>
                <th>Địa chỉ</th>
                <th>Loại Dự án</th>
                <th>Ghi chú</th>
                <th>Thao tác</th>
            </tr>
        </thead>
    <tbody>
        <?php if (empty($projects)): ?>
            <tr>
                <td colspan="6" style="text-align: center;">Chưa có dự án nào.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($projects as $project): ?>
                <tr>
                    <td><?php echo htmlspecialchars($project['ma_du_an']); ?></td>
                    <td><?php echo htmlspecialchars($project['ten_du_an']); ?></td>
                    <td><?php echo htmlspecialchars($project['dia_chi']); ?></td>
                    <td><?php echo htmlspecialchars($project['loai_du_an']); ?></td>
                    <td><?php echo htmlspecialchars($project['ghi_chu']); ?></td>
                    <td class="actions">
                        <a href="index.php?page=projects/edit&id=<?php echo $project['id']; ?>" class="btn edit-btn">Sửa</a>
                        <a href="index.php?page=projects/delete&id=<?php echo $project['id']; ?>" class="btn delete-btn" onclick="return confirm('Bạn có chắc muốn xóa dự án này?');">Xóa</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
</div>