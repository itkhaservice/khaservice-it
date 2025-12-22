<?php
$id = $_GET['id'] ?? null;
if (!$id) { header("Location: index.php?page=services/list"); exit; }

$stmt = $pdo->prepare("
    SELECT s.*, p.ten_du_an, sup.ten_npp, sup.nguoi_lien_he, sup.dien_thoai as sup_phone, sup.email as sup_email
    FROM services s
    LEFT JOIN projects p ON s.project_id = p.id
    LEFT JOIN suppliers sup ON s.supplier_id = sup.id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$service = $stmt->fetch();

if (!$service) { header("Location: index.php?page=services/list"); exit; }

// Tính toán ngày còn lại
$today = new DateTime();
$expiry = new DateTime($service['ngay_het_han']);
$diff = $today->diff($expiry);
$days_left = (int)$diff->format("%r%a");

$status_class = "text-success";
if ($days_left <= 0) $status_class = "text-danger";
elseif ($days_left <= 30) $status_class = "text-warning";
?>

<div class="page-header">
    <h2><i class="fas fa-info-circle"></i> Chi tiết Dịch vụ</h2>
    <div class="header-actions">
        <a href="index.php?page=services/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <?php if(isIT()): ?>
            <a href="index.php?page=services/edit&id=<?php echo $id; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Chỉnh sửa</a>
        <?php endif; ?>
    </div>
</div>

<div class="view-grid-layout">
    <div class="left-panel">
        <div class="card">
            <div class="card-header-custom"><h3>Thông tin Dịch vụ</h3></div>
            <div class="card-body-custom" style="padding: 20px;">
                <div class="info-row"><strong>Tên dịch vụ:</strong> <span><?php echo htmlspecialchars($service['ten_dich_vu']); ?></span></div>
                <div class="info-row"><strong>Loại:</strong> <span><?php echo htmlspecialchars($service['loai_dich_vu']); ?></span></div>
                <div class="info-row"><strong>Dự án:</strong> <span><?php echo htmlspecialchars($service['ten_du_an'] ?: "Dùng chung"); ?></span></div>
                <hr>
                <div class="info-row"><strong>Ngày bắt đầu:</strong> <span><?php echo $service['ngay_dang_ky'] ? date('d/m/Y', strtotime($service['ngay_dang_ky'])) : '---'; ?></span></div>
                <div class="info-row"><strong>Ngày hết hạn:</strong> <span class="<?php echo $status_class; ?> font-bold"><?php echo date('d/m/Y', strtotime($service['ngay_het_han'])); ?></span></div>
                <div class="info-row"><strong>Còn lại:</strong> <span class="<?php echo $status_class; ?> font-bold"><?php echo ($days_left > 0) ? $days_left . " ngày" : "Đã hết hạn"; ?></span></div>
                <hr>
                <div class="info-row"><strong>Ngày nhận Đề nghị TT:</strong> <span><?php echo $service['ngay_nhan_de_nghi'] ? date('d/m/Y', strtotime($service['ngay_nhan_de_nghi'])) : '<em>Chưa nhận</em>'; ?></span></div>
                <div class="info-row"><strong>Chi phí gia hạn:</strong> <span><?php echo number_format($service['chi_phi_gia_han']); ?> ₫</span></div>
            </div>
        </div>
    </div>

    <div class="right-panel">
        <div class="card">
            <div class="card-header-custom"><h3>Nhà cung cấp</h3></div>
            <div class="card-body-custom" style="padding: 20px;">
                <div class="info-row"><strong>Đơn vị:</strong> <span><?php echo htmlspecialchars($service['ten_npp'] ?: "N/A"); ?></span></div>
                <div class="info-row"><strong>Liên hệ:</strong> <span><?php echo htmlspecialchars($service['nguoi_lien_he'] ?: "N/A"); ?></span></div>
                <div class="info-row"><strong>SĐT:</strong> <span><?php echo htmlspecialchars($service['sup_phone'] ?: "N/A"); ?></span></div>
                <div class="info-row"><strong>Email:</strong> <span><?php echo htmlspecialchars($service['sup_email'] ?: "N/A"); ?></span></div>
            </div>
        </div>
        
        <div class="card mt-20">
            <div class="card-header-custom"><h3>Ghi chú</h3></div>
            <div class="card-body-custom" style="padding: 20px;">
                <?php echo nl2br(htmlspecialchars($service['ghi_chu'] ?: "Không có ghi chú")); ?>
            </div>
        </div>
    </div>
</div>

<style>
.info-row { display: flex; justify-content: space-between; margin-bottom: 12px; }
.view-grid-layout { display: grid; grid-template-columns: 1.5fr 1fr; gap: 25px; }
.font-bold { font-weight: 700; }
@media (max-width: 768px) { .view-grid-layout { grid-template-columns: 1fr; } }
</style>
