<?php
$project = null;
if (isset($_GET['id'])) {
    $project_id = $_GET['id'];

    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
}

if (!$project) {
    set_message('error', 'Dự án không tìm thấy!');
    header("Location: index.php?page=projects/list");
    exit;
}
?>

<div class="page-header">
    <h2><i class="fas fa-building"></i> Chi tiết Dự án</h2>
    <div class="header-actions">
        <a href="index.php?page=projects/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <a href="index.php?page=projects/edit&id=<?php echo $project['id']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Chỉnh sửa</a>
    </div>
</div>

<div class="card view-container" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h3><?php echo htmlspecialchars($project['ten_du_an']); ?></h3>
        <span class="badge status-info"><?php echo htmlspecialchars($project['loai_du_an']); ?></span>
    </div>

    <div class="card-body">
        <dl class="detail-list" style="grid-template-columns: 1fr;">
            <div class="detail-item">
                <dt>Mã Dự án</dt>
                <dd class="highlight text-primary"><?php echo htmlspecialchars($project['ma_du_an']); ?></dd>
            </div>

            <div class="detail-item">
                <dt>Tên Dự án</dt>
                <dd><strong><?php echo htmlspecialchars($project['ten_du_an']); ?></strong></dd>
            </div>

            <div class="detail-item mt-10">
                <dt>Địa chỉ</dt>
                <dd><i class="fas fa-map-marker-alt text-muted"></i> <?php echo nl2br(htmlspecialchars($project['dia_chi'])); ?></dd>
            </div>

            <div class="detail-item mt-10">
                <dt>Ghi chú</dt>
                <dd class="text-block"><?php echo nl2br(htmlspecialchars($project['ghi_chu'] ?? 'Không có ghi chú')); ?></dd>
            </div>
        </dl>
    </div>
</div>

<style>
.text-block {
    background: #f8fafc;
    padding: 10px 15px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    color: #64748b;
}
.mt-10 { margin-top: 15px; }
.text-muted { color: #94a3b8; }
</style>
