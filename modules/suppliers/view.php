<?php
if (!isset($_GET['id'])) {
    header("Location: index.php?page=suppliers/list");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    set_message('error', 'Nhà cung cấp không tồn tại.');
    header("Location: index.php?page=suppliers/list");
    exit;
}

// --- LOGIC XỬ LÝ FILE (Giống device_files) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_supplier_file'])) {
    $loai_file = $_POST['loai_file'] ?? 'Khác';
    $target_dir_base = __DIR__ . "/../../uploads/suppliers/";
    $target_sub_dir = '';

    switch ($loai_file) {
        case 'Hợp đồng': $target_sub_dir = 'contracts/'; break;
        case 'Đề nghị': $target_sub_dir = 'requests/'; break;
        default: $target_sub_dir = 'others/';
    }

    $target_dir = $target_dir_base . $target_sub_dir;
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
        $file_name = basename($_FILES['file_upload']['name']);
        $new_file_name = uniqid() . '_' . $file_name;
        $file_path = $target_dir . $new_file_name;

        if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $file_path)) {
            $relative_file_path = "uploads/suppliers/" . $target_sub_dir . $new_file_name;
            $stmt_in = $pdo->prepare("INSERT INTO supplier_files (supplier_id, loai_file, file_path) VALUES (?, ?, ?)");
            $stmt_in->execute([$id, $loai_file, $relative_file_path]);
            set_message('success', 'Tải lên tài liệu thành công.');
            header("Location: index.php?page=suppliers/view&id=" . $id);
            exit;
        }
    }
}

// Xử lý xóa file
if (isset($_GET['action']) && $_GET['action'] === 'delete_file' && isset($_GET['file_id'])) {
    $stmt_f = $pdo->prepare("SELECT file_path FROM supplier_files WHERE id = ? AND supplier_id = ?");
    $stmt_f->execute([$_GET['file_id'], $id]);
    $file_to_del = $stmt_f->fetch();
    if ($file_to_del) {
        $full_path = __DIR__ . "/../../" . $file_to_del['file_path'];
        if (file_exists($full_path)) unlink($full_path);
        $pdo->prepare("DELETE FROM supplier_files WHERE id = ?")->execute([$_GET['file_id']]);
        set_message('success', 'Đã xóa tài liệu.');
    }
    header("Location: index.php?page=suppliers/view&id=" . $id);
    exit;
}

// Fetch supplier files
$files_stmt = $pdo->prepare("SELECT * FROM supplier_files WHERE supplier_id = ? ORDER BY uploaded_at DESC");
$files_stmt->execute([$id]);
$supplier_files = $files_stmt->fetchAll();

function getSupplierFileIconInfo($filePath) {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'jpg': case 'jpeg': case 'png': case 'gif': case 'webp': return ['type' => 'image', 'icon' => 'fa-file-image', 'color' => '#3b82f6'];
        case 'pdf': return ['type' => 'icon', 'icon' => 'fa-file-pdf', 'color' => '#ef4444'];
        case 'doc': case 'docx': return ['type' => 'icon', 'icon' => 'fa-file-word', 'color' => '#2563eb'];
        case 'xls': case 'xlsx': return ['type' => 'icon', 'icon' => 'fa-file-excel', 'color' => '#10b981'];
        case 'zip': case 'rar': return ['type' => 'icon', 'icon' => 'fa-file-archive', 'color' => '#f59e0b'];
        default: return ['type' => 'icon', 'icon' => 'fa-file', 'color' => '#94a3b8'];
    }
}
// --- END LOGIC XỬ LÝ FILE ---

// Fetch related devices
$stmt_devices = $pdo->prepare("SELECT id, ma_tai_san, ten_thiet_bi, loai_thiet_bi, trang_thai FROM devices WHERE supplier_id = ? ORDER BY ten_thiet_bi");
$stmt_devices->execute([$id]);
$devices = $stmt_devices->fetchAll();

// Fetch related services
$stmt_services = $pdo->prepare("SELECT s.*, p.ten_du_an FROM services s LEFT JOIN projects p ON s.project_id = p.id WHERE s.supplier_id = ? ORDER BY s.ngay_het_han");
$stmt_services->execute([$id]);
$services = $stmt_services->fetchAll();
?>

<div class="page-header">
    <h2><i class="fas fa-truck"></i> Chi tiết Nhà cung cấp</h2>
    <div class="header-actions">
        <a href="index.php?page=suppliers/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <?php if(isIT()): ?>
            <a href="index.php?page=suppliers/edit&id=<?php echo $id; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Chỉnh sửa</a>
            <?php if(isAdmin()): ?>
                <a href="index.php?page=suppliers/delete&id=<?php echo $id; ?>" data-url="index.php?page=suppliers/delete&id=<?php echo $id; ?>&confirm_delete=1" class="btn btn-danger delete-btn"><i class="fas fa-trash-alt"></i> Xóa</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="suppliers-view-grid">
    <!-- SIDEBAR: THÔNG TIN NHÀ CUNG CẤP -->
    <div class="side-content">
        <div class="card profile-card text-center">
            <div class="device-icon-large" style="background: #f0fdf4; color: #166534; margin: 0 auto 20px auto;">
                <i class="fas fa-building"></i>
            </div>
            <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($supplier['ten_npp']); ?></h3>
            <span class="badge status-info">Nhà cung cấp</span>
            
            <div class="profile-details mt-20" style="text-align: left;">
                <?php 
                $contacts_json = isset($supplier['thong_tin_lien_he']) ? $supplier['thong_tin_lien_he'] : '';
                $contacts = !empty($contacts_json) ? json_decode($contacts_json, true) : [];
                if (!empty($contacts)): 
                    foreach ($contacts as $index => $c): ?>
                        <div class="contact-card-mini" style="background: #f8fafc; border-radius: 8px; padding: 10px; margin-bottom: 10px; border-left: 3px solid #108042;">
                            <div style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($c['name']); ?></div>
                            <div style="font-size: 0.85rem; color: #64748b;">
                                <i class="fas fa-phone-alt" style="width: 15px;"></i> <?php echo htmlspecialchars($c['phone']); ?>
                            </div>
                            <?php if(!empty($c['role'])): ?>
                                <div style="font-size: 0.85rem; color: #64748b;">
                                    <i class="fas fa-tag" style="width: 15px;"></i> <?php echo htmlspecialchars($c['role']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach;
                else: ?>
                    <div class="detail-row">
                        <span class="d-label">Người liên hệ</span>
                        <span class="d-value"><?php echo htmlspecialchars(isset($supplier['nguoi_lien_he']) ? $supplier['nguoi_lien_he'] : '---'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="d-label">Điện thoại</span>
                        <span class="d-value"><?php echo htmlspecialchars(isset($supplier['dien_thoai']) ? $supplier['dien_thoai'] : '---'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="d-label">Email</span>
                        <span class="d-value"><?php echo htmlspecialchars(isset($supplier['email']) ? $supplier['email'] : '---'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if(!empty($supplier['ghi_chu'])): ?>
                <div class="mt-20 supplier-note">
                    <div class="note-title">Ghi chú</div>
                    <div class="note-content"><?php echo nl2br(htmlspecialchars($supplier['ghi_chu'])); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MAIN: DANH SÁCH THIẾT BỊ & DỊCH VỤ -->
    <div class="main-content">
        <!-- TÀI LIỆU NHÀ CUNG CẤP -->
        <div class="card">
            <div class="dashboard-card-header">
                <h3><i class="fas fa-folder-open"></i> Tài liệu liên quan (<?php echo count($supplier_files); ?>)</h3>
            </div>
            
            <!-- Upload Form -->
            <?php if(isIT()): ?>
            <div class="upload-zone-simple">
                <form action="index.php?page=suppliers/view&id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
                    <div class="upload-flex">
                        <select name="loai_file" class="form-select-sm">
                            <option value="Hợp đồng">Hợp đồng</option>
                            <option value="Đề nghị">Đề nghị</option>
                            <option value="Khác">Tài liệu khác</option>
                        </select>
                        <input type="file" name="file_upload" required class="form-control-sm">
                        <button type="submit" name="upload_supplier_file" class="btn btn-primary btn-sm"><i class="fas fa-upload"></i> Tải lên</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <?php if(empty($supplier_files)): ?>
                <p class="text-muted mt-10">Chưa có tài liệu đính kèm.</p>
            <?php else: ?>
                <div class="files-grid-compact mt-20">
                    <?php foreach($supplier_files as $f): 
                        $info = getSupplierFileIconInfo($f['file_path']);
                        $fName = basename($f['file_path']);
                        $fNameClean = substr($fName, strpos($fName, '_') + 1);
                        $fUrl = "../" . htmlspecialchars($f['file_path']);
                    ?>
                        <div class="file-item-compact">
                            <div class="file-icon-box">
                                <i class="fas <?php echo $info['icon']; ?>" style="color: <?php echo $info['color']; ?>"></i>
                            </div>
                            <div class="file-info-box">
                                <a href="<?php echo $fUrl; ?>" target="_blank" class="file-title-text" title="<?php echo $fNameClean; ?>"><?php echo $fNameClean; ?></a>
                                <div class="file-meta-text">
                                    <span class="badge status-info" style="font-size: 0.6rem;"><?php echo htmlspecialchars($f['loai_file']); ?></span>
                                    <span><?php echo date('d/m/Y', strtotime($f['uploaded_at'])); ?></span>
                                </div>
                            </div>
                            <div class="file-actions-box">
                                <a href="<?php echo $fUrl; ?>" download class="btn-icon"><i class="fas fa-download"></i></a>
                                <?php if(isIT()): ?>
                                    <a href="#" data-url="index.php?page=suppliers/view&id=<?php echo $id; ?>&action=delete_file&file_id=<?php echo $f['id']; ?>" class="btn-icon delete-btn text-danger"><i class="fas fa-trash-alt"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Related Devices -->
        <div class="card mt-20">
            <div class="dashboard-card-header">
                <h3><i class="fas fa-server"></i> Thiết bị đã cung cấp (<?php echo count($devices); ?>)</h3>
            </div>
            <?php if(empty($devices)): ?>
                <p class="text-muted">Chưa ghi nhận thiết bị nào từ nhà cung cấp này.</p>
            <?php else: ?>
                <div class="table-container" style="border:none; box-shadow:none; margin-bottom: 0;">
                    <table class="content-table">
                        <thead>
                            <tr>
                                <th>Mã tài sản</th>
                                <th>Tên thiết bị</th>
                                <th class="mobile-hide">Loại</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($devices as $d): ?>
                                <tr>
                                    <td><a href="index.php?page=devices/view&id=<?php echo $d['id']; ?>" class="text-primary font-medium"><?php echo htmlspecialchars($d['ma_tai_san']); ?></a></td>
                                    <td><?php echo htmlspecialchars($d['ten_thiet_bi']); ?></td>
                                    <td class="mobile-hide"><?php echo htmlspecialchars($d['loai_thiet_bi']); ?></td>
                                    <td><span class="badge"><?php echo htmlspecialchars($d['trang_thai']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Related Services -->
        <div class="card mt-20">
            <div class="dashboard-card-header">
                <h3><i class="fas fa-cloud"></i> Dịch vụ / Phần mềm (<?php echo count($services); ?>)</h3>
            </div>
            <?php if(empty($services)): ?>
                <p class="text-muted">Chưa ghi nhận dịch vụ nào từ nhà cung cấp này.</p>
            <?php else: ?>
                <div class="table-container" style="border:none; box-shadow:none; margin-bottom: 0;">
                    <table class="content-table">
                        <thead>
                            <tr>
                                <th>Tên dịch vụ</th>
                                <th class="mobile-hide">Dự án</th>
                                <th>Ngày hết hạn</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($services as $s): ?>
                                <tr>
                                    <td><a href="index.php?page=services/view&id=<?php echo $s['id']; ?>" class="font-bold"><?php echo htmlspecialchars($s['ten_dich_vu']); ?></a></td>
                                    <td class="mobile-hide"><?php echo htmlspecialchars($s['ten_du_an'] ?: 'Dùng chung'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($s['ngay_het_han'])); ?></td>
                                    <td><span class="badge"><?php echo htmlspecialchars($s['trang_thai']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.suppliers-view-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 25px; align-items: start; }
.detail-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.9rem; border-bottom: 1px dashed #f1f5f9; padding-bottom: 8px; }
.d-label { color: #64748b; font-weight: 500; }
.d-value { font-weight: 600; color: #334155; text-align: right; max-width: 60%; }
.supplier-note { text-align: left; padding: 15px; background: #f8fafc; border-radius: 8px; }
.note-title { font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: 5px; }
.note-content { font-size: 0.9rem; line-height: 1.5; }

/* Files Styles */
.upload-zone-simple { background: #f8fafc; border-radius: 8px; padding: 15px; border: 1px dashed #cbd5e1; }
.upload-flex { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.upload-flex .form-select-sm { width: 140px; }
.upload-flex .form-control-sm { flex: 1; min-width: 200px; }

.files-grid-compact { display: grid; grid-template-columns: 1fr; gap: 10px; }
@media (min-width: 1200px) { .files-grid-compact { grid-template-columns: 1fr 1fr; } }

.file-item-compact { display: flex; align-items: center; gap: 12px; padding: 10px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; transition: 0.2s; }
.file-item-compact:hover { border-color: var(--primary-color); background: #f0fdf4; }
.file-icon-box { width: 40px; height: 40px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
.file-info-box { flex: 1; overflow: hidden; }
.file-title-text { display: block; font-size: 0.85rem; font-weight: 600; color: #1e293b; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.file-meta-text { font-size: 0.7rem; color: #94a3b8; display: flex; align-items: center; gap: 8px; margin-top: 2px; }
.file-actions-box { display: flex; gap: 5px; }

@media (max-width: 992px) { .suppliers-view-grid { grid-template-columns: 1fr; gap: 20px; } .mobile-hide { display: none; } }
@media (max-width: 576px) { .profile-card { padding: 20px !important; } .detail-row { flex-direction: column; align-items: flex-start; gap: 4px; } .d-value { text-align: left; max-width: 100%; } }
</style>
