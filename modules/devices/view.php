<?php
$device = null;
if (isset($_GET['id'])) {
    $device_id = $_GET['id'];

    $stmt = $pdo->prepare("
        SELECT
            d.*,
            p.ten_du_an,
            s.ten_npp
        FROM devices d
        LEFT JOIN projects p ON d.project_id = p.id
        LEFT JOIN suppliers s ON d.supplier_id = s.id
        WHERE d.id = ?
    ");
    $stmt->execute([$device_id]);
    $device = $stmt->fetch();
}

if (!$device) {
    set_message('error', 'Thiết bị không tìm thấy!');
    header("Location: index.php?page=devices/list");
    exit;
}
?>

<div class="view-container">
    <div class="view-header">
        <h2>Chi tiết Thiết bị: <?php echo htmlspecialchars($device['ten_thiet_bi']); ?></h2>
        <a href="index.php?page=devices/edit&id=<?php echo $device['id']; ?>" class="btn btn-primary">Sửa Thiết bị</a>
    </div>

    <dl class="view-grid">
        <dt>Mã Tài sản:</dt>
        <dd><?php echo htmlspecialchars($device['ma_tai_san']); ?></dd>

        <dt>Tên Thiết bị:</dt>
        <dd><?php echo htmlspecialchars($device['ten_thiet_bi']); ?></dd>

        <dt>Nhóm Thiết bị:</dt>
        <dd><?php echo htmlspecialchars($device['nhom_thiet_bi']); ?></dd>

        <dt>Loại Thiết bị:</dt>
        <dd><?php echo htmlspecialchars($device['loai_thiet_bi']); ?></dd>

        <dt>Model:</dt>
        <dd><?php echo htmlspecialchars($device['model']); ?></dd>

        <dt>Serial Number:</dt>
        <dd><?php echo htmlspecialchars($device['serial']); ?></dd>

        <dt>Dự án:</dt>
        <dd><?php echo htmlspecialchars($device['ten_du_an'] ?? 'N/A'); ?></dd>

        <dt>Nhà cung cấp:</dt>
        <dd><?php echo htmlspecialchars($device['ten_npp'] ?? 'N/A'); ?></dd>

        <dt>Ngày mua:</dt>
        <dd><?php echo htmlspecialchars($device['ngay_mua']); ?></dd>

        <dt>Giá mua (VNĐ):</dt>
        <dd><?php echo htmlspecialchars(number_format($device['gia_mua'], 0, ',', '.')); ?></dd>

        <dt>Bảo hành đến:</dt>
        <dd><?php echo htmlspecialchars($device['bao_hanh_den']); ?></dd>

        <dt>Trạng thái:</dt>
        <dd><?php echo htmlspecialchars($device['trang_thai']); ?></dd>

        <dt>Ghi chú:</dt>
        <dd><?php echo nl2br(htmlspecialchars($device['ghi_chu'])); ?></dd>

        <dt>Ngày tạo hồ sơ:</dt>
        <dd><?php echo htmlspecialchars($device['created_at']); ?></dd>
    </dl>

    <div class="view-actions">
        <a href="index.php?page=devices/list" class="btn btn-secondary">Quay lại danh sách</a>
    </div>
</div>

<?php
// Include the file management section
include_once __DIR__ . '/../device_files/manage.php';

// Fetch maintenance logs for this device
$maintenance_stmt = $pdo->prepare("SELECT * FROM maintenance_logs WHERE device_id = ? ORDER BY ngay_su_co DESC");
$maintenance_stmt->execute([$device_id]);
$maintenance_logs = $maintenance_stmt->fetchAll();
?>

<div class="maintenance-history-section">
    <h3>Lịch sử Bảo trì</h3>
    <?php if (empty($maintenance_logs)): ?>
        <p>Chưa có lịch sử bảo trì cho thiết bị này.</p>
    <?php else: ?>
        <table class="content-table">
            <thead>
                <tr>
                    <th>Ngày sự cố</th>
                    <th>Mô tả</th>
                    <th>Hư hỏng</th>
                    <th>Xử lý</th>
                    <th>Chi phí</th>
                    <th>Ngày tạo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($maintenance_logs as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['ngay_su_co']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($log['noi_dung'])); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($log['hu_hong'])); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($log['xu_ly'])); ?></td>
                        <td><?php echo htmlspecialchars(number_format($log['chi_phi'], 0, ',', '.')); ?></td>
                        <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
