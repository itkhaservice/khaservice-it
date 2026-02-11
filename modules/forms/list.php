<?php
// modules/forms/list.php
$user_id = $_SESSION['user_id'];
$sql = "SELECT f.*, (SELECT COUNT(*) FROM form_submissions fs WHERE fs.form_id = f.id) as submission_count
        FROM forms f WHERE f.user_id = :user_id ORDER BY f.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$forms = $stmt->fetchAll();
?>
<link rel="stylesheet" href="<?php echo $final_base; ?>assets/css/form_builder.css?v=<?php echo time(); ?>">

<div class="form-module-container">
    <div class="form-page-header">
        <h2><i class="fas fa-folders"></i> Danh sách biểu mẫu</h2>
        <div class="header-actions">
            <a href="user_forms_dashboard.php?page=forms/create" class="btn-f btn-f-primary"><i class="fas fa-plus"></i> Thiết kế form mới</a>
        </div>
    </div>

    <div class="form-card" style="padding: 0 !important; overflow: hidden;">
        <table class="form-table">
            <thead>
                <tr>
                    <th width="60" class="text-center">STT</th>
                    <th>Tên Biểu mẫu / Định danh</th>
                    <th class="text-center" width="130">Trạng thái</th>
                    <th class="text-center" width="110">Lượt nộp</th>
                    <th width="140">Ngày khởi tạo</th>
                    <th class="text-center" width="180">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($forms)): ?>
                    <tr><td colspan="6" class="text-center text-muted" style="padding: 50px;">Bạn chưa có dữ liệu biểu mẫu nào.</td></tr>
                <?php endif; ?>
                <?php foreach ($forms as $idx => $form): ?>
                    <tr>
                        <td class="text-center text-muted" data-label="STT"><?php echo $idx + 1; ?></td>
                        <td data-label="Tên biểu mẫu">
                            <div style="font-weight: 700; color: var(--f-text); font-size: 0.95rem;"><?php echo htmlspecialchars($form['title']); ?></div>
                            <div style="font-size: 0.75rem; color: var(--f-text-light);">Slug: /<?php echo $form['slug']; ?></div>
                        </td>
                        <td class="text-center" data-label="Trạng thái">
                            <span class="f-badge <?php echo $form['status'] == 'published' ? 'f-badge-success' : 'f-badge-draft'; ?>">
                                <?php echo $form['status'] == 'published' ? 'ĐANG CHẠY' : 'BẢN NHÁP'; ?>
                            </span>
                        </td>
                        <td class="text-center" data-label="Lượt nộp">
                            <a href="user_forms_dashboard.php?page=forms/view_results&id=<?php echo $form['id']; ?>" style="font-weight: 800; color: var(--f-primary); text-decoration: none; font-size: 1rem;">
                                <?php echo $form['submission_count']; ?>
                            </a>
                        </td>
                        <td data-label="Ngày tạo">
                            <div style="font-weight: 600; color: var(--f-text-light); font-size: 0.8rem;"><?php echo date('d/m/Y', strtotime($form['created_at'])); ?></div>
                        </td>
                        <td class="text-center" data-label="Thao tác">
                            <div style="display: flex; justify-content: center; gap: 5px;">
                                <a href="user_forms_dashboard.php?page=forms/view_results&id=<?php echo $form['id']; ?>" class="btn-icon-sm" title="Xem kết quả"><i class="fas fa-chart-line"></i></a>
                                <a href="user_forms_dashboard.php?page=forms/edit&id=<?php echo $form['id']; ?>" class="btn-icon-sm" title="Sửa thiết kế"><i class="fas fa-edit"></i></a>
                                <a href="<?php echo $final_base; ?>public/form.php?slug=<?php echo $form['slug']; ?>" target="_blank" class="btn-icon-sm" title="Xem trước biểu mẫu"><i class="fas fa-external-link-alt"></i></a>
                                <a href="user_forms_dashboard.php?page=forms/delete&id=<?php echo $form['id']; ?>" class="btn-icon-sm delete-btn" title="Xóa"><i class="far fa-trash-alt" style="color: #ef4444;"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>