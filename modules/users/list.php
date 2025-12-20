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

<div class="page-header">
    <h2><i class="fas fa-users-cog"></i> Danh sách Người dùng</h2>
    <a href="index.php?page=users/add" class="btn btn-primary"><i class="fas fa-user-plus"></i> Thêm Người dùng</a>
</div>

<div class="table-container card">
    <table class="content-table">
        <thead>
            <tr>
                <th>Username</th>
                <th>Role</th>
                <th>Ngày tạo</th>
                <th width="100" class="text-center">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="4" class="empty-state">
                        <i class="fas fa-user-slash" style="font-size: 3rem; color: #e2e8f0; margin-bottom: 10px;"></i>
                        <p>Chưa có người dùng nào trong hệ thống.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="font-bold text-primary"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td>
                            <?php 
                            $roleClass = 'status-default';
                            if ($user['role'] === 'admin') $roleClass = 'status-error'; // Admin gets red/important color
                            elseif ($user['role'] === 'it') $roleClass = 'status-info';
                            ?>
                            <span class="badge <?php echo $roleClass; ?>"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                        <td class="actions text-center">
                            <a href="index.php?page=users/edit&id=<?php echo $user['id']; ?>" class="btn-icon" title="Sửa"><i class="fas fa-edit"></i></a>
                            <a href="index.php?page=users/delete&id=<?php echo $user['id']; ?>" class="btn-icon delete-btn" title="Xóa"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
