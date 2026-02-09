<?php
// File: modules/forms/list.php
// Trang liệt kê tất cả các biểu mẫu mà người dùng đã tạo.

$user_id = $_SESSION['user_id'];
$forms = [];

// Query to get forms for the logged-in user
$sql = "SELECT 
            f.id, f.title, f.status, f.slug, f.created_at,
            (SELECT COUNT(*) FROM form_submissions fs WHERE fs.form_id = f.id) as submission_count
        FROM forms f
        WHERE f.user_id = :user_id
        ORDER BY f.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$forms = $stmt->fetchAll();

?>

<div class="page-header">
    <h2><i class="fas fa-clipboard-list"></i> Biểu mẫu của tôi</h2>
    <a href="user_forms_dashboard.php?page=forms/create" class="btn btn-primary"><i class="fas fa-plus"></i> Tạo Biểu mẫu mới</a>
</div>

<div class="card table-container">
    <?php if (empty($forms)): ?>
        <div class="empty-state">
            <i class="fas fa-file-alt empty-icon"></i>
            <h3>Bạn chưa tạo biểu mẫu nào</h3>
            <p>Hãy bắt đầu tạo biểu mẫu đầu tiên của bạn để thu thập thông tin.</p>
            <a href="user_forms_dashboard.php?page=forms/create" class="btn btn-primary"><i class="fas fa-plus"></i> Tạo ngay</a>
        </div>
    <?php else: ?>
        <table class="content-table">
            <thead>
                <tr>
                    <th>Tiêu đề Biểu mẫu</th>
                    <th class="text-center">Trạng thái</th>
                    <th class="text-center">Lượt trả lời</th>
                    <th>Ngày tạo</th>
                    <th class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($forms as $form): ?>
                    <tr>
                        <td class="font-medium"><?php echo htmlspecialchars($form['title']); ?></td>
                        <td class="text-center">
                            <?php if ($form['status'] == 'published'): ?>
                                <span class="badge status-published">Phát hành</span>
                            <?php else: ?>
                                <span class="badge status-draft">Bản nháp</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a href="index.php?page=forms/view_results&id=<?php echo $form['id']; ?>" class="submission-count">
                                <?php echo $form['submission_count']; ?>
                            </a>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($form['created_at'])); ?></td>
                        <td class="actions text-center">
                            <a href="index.php?page=forms/view_results&id=<?php echo $form['id']; ?>" class="btn-icon" title="Xem kết quả"><i class="fas fa-chart-bar"></i></a>
                            <a href="index.php?page=forms/edit&id=<?php echo $form['id']; ?>" class="btn-icon" title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
                            <a href="public/form.php?slug=<?php echo $form['slug']; ?>" target="_blank" class="btn-icon" title="Xem link công khai"><i class="fas fa-external-link-alt"></i></a>
                            <a href="user_forms_dashboard.php?page=forms/delete&id=<?php echo $form['id']; ?>" class="btn-icon delete-btn" title="Xóa"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
.empty-state {
    text-align: center;
    padding: 60px 40px;
}
.empty-state .empty-icon {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 20px;
}
.empty-state h3 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 10px;
}
.empty-state p {
    color: #64748b;
    margin-bottom: 25px;
}

.badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.status-published {
    background-color: #dcfce7;
    color: #166534;
}
.status-draft {
    background-color: #e2e8f0;
    color: #475569;
}
.submission-count {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--primary-color);
    text-decoration: none;
}
.submission-count:hover {
    text-decoration: underline;
}
</style>