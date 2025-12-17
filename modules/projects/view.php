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

<div class="view-container">
    <div class="view-header">
        <h2>Chi tiết Dự án: <?php echo htmlspecialchars($project['ten_du_an']); ?></h2>
        <a href="index.php?page=projects/edit&id=<?php echo $project['id']; ?>" class="btn btn-primary">Sửa Dự án</a>
    </div>

    <dl class="view-grid">
        <dt>Mã Dự án:</dt>
        <dd><?php echo htmlspecialchars($project['ma_du_an']); ?></dd>

        <dt>Tên Dự án:</dt>
        <dd><?php echo htmlspecialchars($project['ten_du_an']); ?></dd>

        <dt>Địa chỉ:</dt>
        <dd><?php echo nl2br(htmlspecialchars($project['dia_chi'])); ?></dd>

        <dt>Loại Dự án:</dt>
        <dd><?php echo htmlspecialchars($project['loai_du_an']); ?></dd>

        <dt>Ghi chú:</dt>
        <dd><?php echo nl2br(htmlspecialchars($project['ghi_chu'])); ?></dd>
    </dl>

    <div class="view-actions">
        <a href="index.php?page=projects/list" class="btn btn-secondary">Quay lại danh sách</a>
    </div>
</div>
