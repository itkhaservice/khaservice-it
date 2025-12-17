<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    echo "<p class='error'>Bạn không có quyền truy cập chức năng này.</p>";
    exit;
}

// Fetch users from the database
$stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY username");
$users = $stmt->fetchAll();
?>

<h2>Danh sách Người dùng Hệ thống</h2>

<a href="index.php?page=users/add" class="add-button btn btn-primary">Thêm người dùng mới</a>

<div class="content-table-wrapper">
    <table class="content-table">
        <thead>
            <tr>
                <th>Username</th>
                <th>Role</th>
                <th>Ngày tạo</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="4" style="text-align: center;">Chưa có người dùng nào.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                        <td class="actions">
                            <a href="index.php?page=users/edit&id=<?php echo $user['id']; ?>" class="btn edit-btn">Sửa</a>
                            <a href="index.php?page=users/delete&id=<?php echo $user['id']; ?>" class="btn delete-btn" onclick="return confirm('Bạn có chắc muốn xóa người dùng này?');">Xóa</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
