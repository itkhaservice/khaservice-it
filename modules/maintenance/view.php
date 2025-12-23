<?php
// modules/maintenance/view.php

$log = null;
if (isset($_GET['id'])) {
    $log_id = $_GET['id'];
    $stmt = $pdo->prepare("
        SELECT ml.*, d.ma_tai_san, d.ten_thiet_bi, d.loai_thiet_bi, d.model, d.ngay_mua,
               COALESCE(p_log.ten_du_an, p_dev.ten_du_an) as ten_du_an, 
               COALESCE(p_log.dia_chi_duong, p_dev.dia_chi_duong) as dia_chi_duong,
               COALESCE(p_log.dia_chi_phuong_xa, p_dev.dia_chi_phuong_xa) as dia_chi_phuong_xa,
               COALESCE(p_log.dia_chi_tinh_tp, p_dev.dia_chi_tinh_tp) as dia_chi_tinh_tp,
               d.trang_thai as trang_thai_tb,
               u.fullname as nguoi_thuc_hien
        FROM maintenance_logs ml
        LEFT JOIN devices d ON ml.device_id = d.id
        LEFT JOIN projects p_log ON ml.project_id = p_log.id
        LEFT JOIN projects p_dev ON d.project_id = p_dev.id
        LEFT JOIN users u ON ml.user_id = u.id
        WHERE ml.id = ?
    ");
    $stmt->execute([$log_id]);
    $log = $stmt->fetch();
}

if (!$log) {
    set_message('error', 'Nhật ký bảo trì không tìm thấy!');
    header("Location: index.php?page=maintenance/history");
    exit;
}

// Xử lý logic hiển thị chung cho Web
$is_custom_device = empty($log['device_id']);
$web_display_name = $is_custom_device ? ($log['custom_device_name'] ?: "") : $log['ten_thiet_bi'];
$web_display_code = $is_custom_device ? ($log['work_type'] ?: "Công tác") : $log['ma_tai_san'];
$print_device_name = $web_display_name; // Dùng cho cả print logic bên dưới

$addr_parts = [];
if(!empty($log['dia_chi_duong'])) $addr_parts[] = $log['dia_chi_duong'];
if(!empty($log['dia_chi_phuong_xa'])) $addr_parts[] = $log['dia_chi_phuong_xa'];
if(!empty($log['dia_chi_tinh_tp'])) $addr_parts[] = $log['dia_chi_tinh_tp'];

$display_address = !empty($addr_parts) ? implode(', ', $addr_parts) : "";
$display_city = !empty($log['dia_chi_tinh_tp']) ? $log['dia_chi_tinh_tp'] : "TP.HCM";
$display_project_name = !empty($log['ten_du_an']) ? $log['ten_du_an'] : "Khác / Không xác định";

$current_user_name = $_SESSION['fullname'] ?? 'IT Support';

// --- ATTACHMENTS LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_file'])) {
    $loai_file = $_POST['loai_file'] ?? 'Khác';
    $target_dir = __DIR__ . "/../../uploads/maintenance/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
        $file_name = basename($_FILES['file_upload']['name']);
        $file_path = $target_dir . uniqid() . '_' . $file_name;
        if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $file_path)) {
            $relative_path = "uploads/maintenance/" . basename($file_path);
            $stmt = $pdo->prepare("INSERT INTO maintenance_files (maintenance_id, loai_file, file_path) VALUES (?, ?, ?)");
            $stmt->execute([$log_id, $loai_file, $relative_path]);
            set_message('success', 'Tải tệp lên thành công.');
            header("Location: index.php?page=maintenance/view&id=$log_id");
            exit;
        }
    }
}
if (isset($_GET['action']) && $_GET['action'] === 'delete_file' && isset($_GET['file_id'])) {
    $file_id = $_GET['file_id'];
    $stmt = $pdo->prepare("SELECT file_path FROM maintenance_files WHERE id = ? AND maintenance_id = ?"); 
    $stmt->execute([$file_id, $log_id]);
    $file = $stmt->fetch();
    if ($file) {
        $full_path = __DIR__ . "/../../" . $file['file_path'];
        if (file_exists($full_path)) unlink($full_path);
        $pdo->prepare("DELETE FROM maintenance_files WHERE id = ?")->execute([$file_id]);
        set_message('success', 'Đã xóa tệp.');
    }
    header("Location: index.php?page=maintenance/view&id=$log_id");
    exit;
}
$files = $pdo->prepare("SELECT * FROM maintenance_files WHERE maintenance_id = ? ORDER BY uploaded_at DESC");
$files->execute([$log_id]);
$attachments = $files->fetchAll();

function getFileIconInfo($filePath) {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'jpg': case 'jpeg': case 'png': case 'gif': case 'webp': return ['type' => 'image', 'icon' => 'fa-file-image', 'color' => '#3b82f6'];
        case 'pdf': return ['type' => 'icon', 'icon' => 'fa-file-pdf', 'color' => '#ef4444'];
        default: return ['type' => 'icon', 'icon' => 'fa-file', 'color' => '#94a3b8'];
    }
}
?>

<div class="web-view">
    <div class="page-header">
        <h2><i class="fas fa-file-invoice"></i> Chi tiết Phiếu #<?php echo $log['id']; ?></h2>   
        <div class="header-actions">
            <a href="index.php?page=maintenance/history" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
            <button class="btn btn-warning" onclick="togglePrintDebug()"><i class="fas fa-eye"></i> Soi mẫu in</button>
            <button class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> In phiếu A4</button>
            <?php if(isIT()): ?>
                <a href="index.php?page=maintenance/edit&id=<?php echo $log['id']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Chỉnh sửa</a>
                <a href="index.php?page=maintenance/delete&id=<?php echo $log['id']; ?>" data-url="index.php?page=maintenance/delete&id=<?php echo $log['id']; ?>&confirm_delete=1" class="btn btn-danger delete-btn"><i class="fas fa-trash-alt"></i> Xóa phiếu</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="view-grid-layout maintenance-view">
        <div class="main-content">
            <div class="card ticket-card">
                <div class="ticket-header">
                    <div class="ticket-status">
                        <span class="label">Ngày sự cố:</span>
                        <span class="value date"><i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($log['ngay_su_co'])); ?></span>
                    </div>
                </div>
                <div class="ticket-body">
                    <div class="content-block problem"><h4 class="block-title"><i class="fas fa-exclamation-circle"></i> Hiện tượng / Yêu cầu</h4><div class="block-content"><?php echo nl2br(htmlspecialchars($log['noi_dung'])); ?></div></div>
                    <div class="content-block diagnosis"><h4 class="block-title"><i class="fas fa-microscope"></i> Nguyên nhân / Hư hỏng</h4><div class="block-content"><?php echo !empty($log['hu_hong']) ? nl2br(htmlspecialchars($log['hu_hong'])) : '<em>Chưa ghi nhận</em>'; ?></div></div>
                    <div class="content-block solution"><h4 class="block-title"><i class="fas fa-check-circle"></i> Biện pháp Xử lý</h4><div class="block-content"><?php echo !empty($log['xu_ly']) ? nl2br(htmlspecialchars($log['xu_ly'])) : '<em>Chưa ghi nhận</em>'; ?></div></div>
                </div>
            </div>

            <div class="card mt-20 attachment-section">
                <div class="card-header-custom"><h3><i class="fas fa-paperclip"></i> Tài liệu đính kèm</h3></div>
                <div class="card-body-custom">
                    <div class="upload-zone">
                        <form action="index.php?page=maintenance/view&id=<?php echo $log['id']; ?>" method="POST" enctype="multipart/form-data" class="upload-form">
                            <select name="loai_file" class="form-select"><option value="HinhAnh">Hình ảnh</option><option value="BienBan">Biên bản</option><option value="Khác">Khác</option></select>
                            <input type="file" name="file_upload" required>
                            <button type="submit" name="upload_file" class="btn btn-primary"><i class="fas fa-upload"></i> Tải lên</button>
                        </form>
                    </div>
                    <?php if (empty($attachments)): ?>
                        <div class="text-center" style="padding: 20px; color: #94a3b8;"><i class="far fa-folder-open" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i> Chưa có tài liệu.</div>
                    <?php else: ?>
                        <div class="files-grid-simple">
                            <?php foreach ($attachments as $file): 
                                $info = getFileIconInfo($file['file_path']); $url = "../" . $file['file_path'];
                            ?>
                                <div class="file-item-card">
                                    <div class="file-thumb">
                                        <?php if ($info['type'] === 'image'): ?><img src="<?php echo $url; ?>">
                                        <?php else: ?><i class="fas <?php echo $info['icon']; ?> icon-file"></i><?php endif; ?>
                                    </div>
                                    <div class="file-meta">
                                        <span class="name"><?php echo basename($file['file_path']); ?></span>
                                        <div class="actions">
                                            <a href="<?php echo $url; ?>" download><i class="fas fa-download"></i></a>
                                            <a href="index.php?page=maintenance/view&id=<?php echo $log_id; ?>&action=delete_file&file_id=<?php echo $file['id']; ?>" class="text-danger delete-btn" data-url="index.php?page=maintenance/view&id=<?php echo $log_id; ?>&action=delete_file&file_id=<?php echo $file['id']; ?>"><i class="fas fa-trash-alt"></i></a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="side-content">
            <div class="card device-profile-card">
                <div class="profile-header">
                    <div class="device-icon-large"><i class="fas fa-<?php echo $is_custom_device ? 'cube' : 'server'; ?>"></i></div>
                    <div class="profile-title"><h3><?php echo htmlspecialchars($web_display_name); ?></h3><span class="code"><?php echo htmlspecialchars($web_display_code); ?></span></div>
                </div>
                <div class="profile-details">
                    <div class="detail-row"><span class="d-label">Dự án</span><span class="d-value"><?php echo htmlspecialchars($display_project_name); ?></span></div>
                    <div class="detail-row"><span class="d-label">Đại diện</span><span class="d-value"><?php echo htmlspecialchars($log['client_name'] ?? '---'); ?></span></div>
                    <div class="detail-row"><span class="d-label">TG Có mặt</span><span class="d-value"><?php echo $log['arrival_time'] ? date('H:i d/m', strtotime($log['arrival_time'])) : '-'; ?></span></div>
                    <div class="detail-row"><span class="d-label">Hoàn thành</span><span class="d-value"><?php echo $log['completion_time'] ? date('H:i d/m', strtotime($log['completion_time'])) : '-'; ?></span></div>
                    <div class="detail-row"><span class="d-label">Thực hiện</span><span class="d-value"><?php echo htmlspecialchars($log['nguoi_thuc_hien'] ?? 'N/A'); ?></span></div>
                </div>
                <?php if (!$is_custom_device): ?>
                <div style="padding: 0 20px 20px 20px;"><a href="index.php?page=devices/view&id=<?php echo $log['device_id']; ?>" class="btn btn-primary" style="display: flex; width: auto; justify-content: center;"><i class="fas fa-external-link-alt"></i> Xem hồ sơ thiết bị</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>function togglePrintDebug() { document.body.classList.toggle('debug-print-mode'); }</script>

<style>
/* WEB STYLES - Chỉ tập trung vào giao diện trình duyệt */
.maintenance-view { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start; }
.ticket-card, .device-profile-card { padding: 0; overflow: hidden; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.ticket-header, .profile-header { background: #f8fafc; padding: 15px; border-bottom: 1px solid #e2e8f0; }
.ticket-status { display: flex; align-items: center; gap: 10px; }
.ticket-status .label { font-size: 0.85rem; color: #64748b; font-weight: 600; text-transform: uppercase; }
.ticket-status .value { font-size: 1rem; font-weight: 700; color: #1e293b; }
.ticket-status .value i { color: var(--primary-color); margin-right: 5px; }
.ticket-body, .profile-details { padding: 20px; }
.content-block { margin-bottom: 20px; padding-left: 15px; border-left: 4px solid #e2e8f0; }
.block-title { font-size: 0.95rem; font-weight: 700; margin-bottom: 10px; color: #334155; }
.block-content { font-size: 0.95rem; color: #1e293b; line-height: 1.6; background: #f8fafc; padding: 12px; border-radius: 6px; }
.device-icon-large { font-size: 2rem; color: var(--primary-color); margin-bottom: 10px; text-align: center; }
.profile-title { text-align: center; }
.detail-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.9rem; }
.d-value { font-weight: 600; }

/* ATTACHMENTS UI */
.upload-zone { background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 10px; padding: 15px; }
.upload-form { display: flex; gap: 10px; align-items: center; }
.files-grid-simple { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; margin-top: 20px; }
.file-item-card { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
.file-thumb { height: 80px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.file-thumb img { width: 100%; height: 100%; object-fit: cover; }
.icon-file { font-size: 2rem; color: #94a3b8; }
.file-meta { padding: 8px; display: flex; justify-content: space-between; align-items: center; background: #fff; }
.file-meta .name { font-size: 0.75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 80px; }

@media (max-width: 992px) {
    .maintenance-view { grid-template-columns: 1fr; }
    .side-content { order: -1; }
    .header-actions { display: flex; flex-wrap: wrap; gap: 8px; }
    .header-actions .btn, .header-actions a { flex: 1 1 calc(50% - 8px); justify-content: center; height: 44px; font-size: 0.85rem; }
}
</style>

<?php 
// ĐƯA PHẦN IN ẤN VÀO TỆP RIÊNG ĐỂ KHÔNG BAO GIỜ THAY ĐỔI
include 'print_template.inc.php'; 
?>
