<?php
if ($_SESSION['role'] !== 'admin') {
    echo "<div class='card text-center' style='padding: 50px;'><i class='fas fa-lock' style='font-size: 3rem; color: #ef4444; margin-bottom: 20px;'></i><p>Bạn không có quyền truy cập chức năng này.</p></div>";
    return;
}

$stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY username");
$users = $stmt->fetchAll();
?>

<div class="page-header">
    <h2><i class="fas fa-users"></i> Quản trị Người dùng</h2>
    <a href="index.php?page=users/add" class="btn btn-primary"><i class="fas fa-user-plus"></i> Thêm mới</a>
</div>

<div class="table-container card">
    <table class="content-table">
        <thead>
            <tr>
                <th>Username</th>
                <th>Vai trò</th>
                <th>Ngày khởi tạo</th>
                <th width="100" class="text-center">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="4" class="empty-state">
                        <i class="fas fa-user-slash" style="font-size: 3rem; color: #e2e8f0; margin-bottom: 10px;"></i>
                        <p>Không có người dùng nào.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="font-bold text-primary"><?php echo htmlspecialchars($u['username']); ?></td>
                        <td>
                            <?php 
                            $badge = 'status-default';
                            if ($u['role'] === 'admin') $badge = 'status-error';
                            elseif ($u['role'] === 'it') $badge = 'status-active';
                            ?>
                            <span class="badge <?php echo $badge; ?>"><?php echo ucfirst(htmlspecialchars($u['role'])); ?></span>
                        </td>
                        <td class="text-muted"><?php echo date('d/m/Y H:i', strtotime($u['created_at'])); ?></td>
                        <td class="actions text-center">
                            <a href="index.php?page=users/edit&id=<?php echo $u['id']; ?>" class="btn-icon" title="Sửa"><i class="fas fa-user-edit"></i></a>
                            <?php if($u['username'] !== $_SESSION['username']): ?>
                                <button type="button" class="btn-icon text-danger" onclick="openDeleteModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>')"><i class="fas fa-trash-alt"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Delete Modal -->
<div id="deleteUserModal" class="modal">
    <div class="modal-content delete-modal-content">
        <div class="delete-modal-icon"><i class="fas fa-user-times"></i></div>
        <h2 class="delete-modal-title">Xóa tài khoản?</h2>
        <p class="delete-modal-text">Bạn chắc chắn muốn xóa người dùng <strong id="modal-user-name"></strong>?</p>
        <div class="delete-alert-box"><i class="fas fa-info-circle"></i> <span>Tài khoản sẽ bị xóa vĩnh viễn khỏi hệ thống. Người dùng này sẽ không thể đăng nhập được nữa.</span></div>
        <form id="delete-user-form" action="" method="POST" class="delete-modal-actions">
            <input type="hidden" name="confirm_delete" value="1">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Hủy</button>
            <button type="submit" class="btn btn-danger">Xóa tài khoản</button>
        </form>
    </div>
</div>

<script>
function openDeleteModal(id, name) {
    document.getElementById('modal-user-name').textContent = name;
    document.getElementById('delete-user-form').action = 'index.php?page=users/delete&id=' + id;
    document.getElementById('deleteUserModal').classList.add('show');
}
function closeDeleteModal() { document.getElementById('deleteUserModal').classList.remove('show'); }
</script>