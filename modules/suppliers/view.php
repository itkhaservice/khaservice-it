<?php
if (!isset($_GET['id'])) {
    echo '<script>window.location.href = "index.php?page=suppliers/list";</script>';
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    set_message('error', 'Nhà cung cấp không tồn tại.');
    echo '<script>window.location.href = "index.php?page=suppliers/list";</script>';
    exit;
}

// --- LOGIC XỬ LÝ FILE ---
$upload_base_dir = dirname(__DIR__, 2) . "/uploads/suppliers/";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_supplier_file'])) {
    $loai_file = $_POST['loai_file'] ?? 'Khác';
    $target_sub_dir = '';

    switch ($loai_file) {
        case 'Hợp đồng': $target_sub_dir = 'contracts/'; break;
        case 'Đề nghị': $target_sub_dir = 'requests/'; break;
        default: $target_sub_dir = 'others/';
    }

    $target_dir = $upload_base_dir . $target_sub_dir;
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
        $file_name = basename($_FILES['file_upload']['name']);
        $new_file_name = uniqid() . '_' . $file_name;
        $file_path = $target_dir . $new_file_name;

        if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $file_path)) {
            $db_file_path = "uploads/suppliers/" . $target_sub_dir . $new_file_name;
            $stmt_in = $pdo->prepare("INSERT INTO supplier_files (supplier_id, loai_file, file_path) VALUES (?, ?, ?)");
            $stmt_in->execute([$id, $loai_file, $db_file_path]);
            set_message('success', 'Tải lên tài liệu thành công.');
            safe_redirect("index.php?page=suppliers/view&id=" . $id);
        }
    }
}

// Xử lý xóa file
if (isset($_GET['action']) && $_GET['action'] === 'delete_file' && isset($_GET['file_id'])) {
    $stmt_f = $pdo->prepare("SELECT file_path FROM supplier_files WHERE id = ? AND supplier_id = ?");
    $stmt_f->execute([$_GET['file_id'], $id]);
    $file_to_del = $stmt_f->fetch();
    if ($file_to_del) {
        $full_physical_path = dirname(__DIR__, 2) . "/" . $file_to_del['file_path'];
        if (file_exists($full_physical_path)) unlink($full_physical_path);
        $pdo->prepare("DELETE FROM supplier_files WHERE id = ?")->execute([$_GET['file_id']]);
        set_message('success', 'Đã xóa tài liệu.');
    }
    safe_redirect("index.php?page=suppliers/view&id=" . $id);
}

// Fetch supplier files
$files_stmt = $pdo->prepare("SELECT * FROM supplier_files WHERE supplier_id = ? ORDER BY uploaded_at DESC");
$files_stmt->execute([$id]);
$supplier_files = $files_stmt->fetchAll();

function getSupplierFileIconInfo($filePath) {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'jpg': case 'jpeg': case 'png': case 'gif': case 'webp': return ['icon' => 'fa-file-image', 'color' => '#3b82f6'];
        case 'pdf': return ['icon' => 'fa-file-pdf', 'color' => '#ef4444'];
        case 'doc': case 'docx': return ['icon' => 'fa-file-word', 'color' => '#2563eb'];
        case 'xls': case 'xlsx': return ['icon' => 'fa-file-excel', 'color' => '#10b981'];
        case 'zip': case 'rar': return ['icon' => 'fa-file-archive', 'color' => '#f59e0b'];
        default: return ['icon' => 'fa-file-alt', 'color' => '#94a3b8'];
    }
}

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
    <!-- SIDEBAR -->
    <div class="side-content">
        <div class="card profile-card">
            <div class="profile-header-bg"></div>
            <div class="profile-avatar-wrapper">
                <div class="profile-avatar">
                    <i class="fas fa-building"></i>
                </div>
            </div>
            
            <div class="profile-main-info">
                <h3 class="supplier-title"><?php echo htmlspecialchars($supplier['ten_npp']); ?></h3>
                <div class="role-badge"><i class="fas fa-shield-alt"></i> Nhà cung cấp đối tác</div>
            </div>
            
            <div class="profile-details-section">
                <div class="section-divider"><span>Thông tin liên hệ</span></div>
                <?php 
                $contacts_json = isset($supplier['thong_tin_lien_he']) ? $supplier['thong_tin_lien_he'] : '';
                $contacts = !empty($contacts_json) ? json_decode($contacts_json, true) : [];
                if (!empty($contacts)): 
                    foreach ($contacts as $c): ?>
                        <div class="contact-pill">
                            <div class="cp-header">
                                <span class="cp-name"><?php echo htmlspecialchars($c['name']); ?></span>
                                <?php if(!empty($c['role'])): ?><span class="cp-role"><?php echo htmlspecialchars($c['role']); ?></span><?php endif; ?>
                            </div>
                            <a href="tel:<?php echo $c['phone']; ?>" class="cp-phone"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($c['phone']); ?></a>
                        </div>
                    <?php endforeach;
                else: ?>
                    <div class="legacy-detail">
                        <div class="ld-row"><i class="fas fa-user"></i> <span><?php echo htmlspecialchars($supplier['nguoi_lien_he'] ?? '---'); ?></span></div>
                        <div class="ld-row"><i class="fas fa-phone-alt"></i> <span><?php echo htmlspecialchars($supplier['dien_thoai'] ?? '---'); ?></span></div>
                        <div class="ld-row"><i class="fas fa-envelope"></i> <span><?php echo htmlspecialchars($supplier['email'] ?? '---'); ?></span></div>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if(!empty($supplier['ghi_chu'])): ?>
                <div class="profile-note-section">
                    <div class="note-label">Ghi chú hệ thống</div>
                    <div class="note-box"><?php echo nl2br(htmlspecialchars($supplier['ghi_chu'])); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- TÀI LIỆU NHÀ CUNG CẤP (STABLE DESIGN) -->
        <div class="card explorer-card">
            <div class="explorer-header sticky-header">
                <div class="explorer-title">
                    <i class="fas fa-folder-open"></i>
                    <h3>Tài liệu liên quan <span class="count-badge"><?= count($supplier_files) ?></span></h3>
                </div>
                <?php if(isIT()): ?>
                    <button class="btn btn-sm btn-upload-toggle" onclick="toggleUploadArea()">
                        <i class="fas fa-cloud-upload-alt"></i> <span>Tải lên</span>
                    </button>
                <?php endif; ?>
            </div>

            <?php if(isIT()): ?>
            <div id="upload-area" class="explorer-upload-zone" style="display: none;">
                <form action="index.php?page=suppliers/view&id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
                    <div class="upload-bar">
                        <select name="loai_file" class="form-select-sm">
                            <option value="Hợp đồng">Hợp đồng</option>
                            <option value="Đề nghị">Đề nghị</option>
                            <option value="Khác">Tài liệu khác</option>
                        </select>
                        <div class="custom-file-input-wrapper">
                            <input type="file" name="file_upload" id="file_upload" required onchange="updateFileName(this)">
                            <label for="file_upload" id="file-label"><i class="fas fa-link"></i> <span>Chọn tệp tin...</span></label>
                        </div>
                        <button type="submit" name="upload_supplier_file" class="btn btn-primary btn-sm">Lưu ngay</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="explorer-body-scroll">
                <?php if(empty($supplier_files)): ?>
                    <div class="explorer-empty">
                        <i class="far fa-folder-open"></i>
                        <p>Danh sách tài liệu đang trống.</p>
                    </div>
                <?php else: ?>
                    <div class="explorer-list">
                        <?php foreach($supplier_files as $f): 
                            $info = getSupplierFileIconInfo($f['file_path']);
                            $fName = basename($f['file_path']);
                            $fNameClean = substr($fName, strpos($fName, '_') + 1);
                            $fUrl = $final_base . htmlspecialchars($f['file_path']);
                            $ext = strtolower(pathinfo($f['file_path'], PATHINFO_EXTENSION));
                            
                            // Define viewing URL
                            $viewUrl = $fUrl;
                            // For Office files, use Google Docs Viewer proxy
                            if (in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'])) {
                                $viewUrl = "https://docs.google.com/viewer?url=" . urlencode($fUrl) . "&embedded=true";
                            }
                        ?>
                            <div class="explorer-row">
                                <div class="row-icon" style="color: <?= $info['color'] ?>; background: <?= $info['color'] ?>10;">
                                    <i class="fas <?= $info['icon'] ?>"></i>
                                </div>
                                <div class="row-content">
                                    <a href="<?= $viewUrl ?>" target="_blank" class="file-link" title="Xem trực tuyến: <?= $fNameClean ?>"><?= $fNameClean ?></a>
                                    <div class="row-meta">
                                        <span class="meta-tag" style="border: 1px solid <?= $info['color'] ?>40; color: <?= $info['color'] ?>;"><?= $f['loai_file'] ?></span>
                                        <span class="meta-date"><?= date('d/m/Y', strtotime($f['uploaded_at'])) ?></span>
                                    </div>
                                </div>
                                <div class="row-actions">
                                    <a href="<?= $viewUrl ?>" target="_blank" class="action-icon view" title="Xem trực tuyến"><i class="fas fa-eye"></i></a>
                                    <a href="<?= $fUrl ?>" download class="action-icon download" title="Tải về máy"><i class="fas fa-download"></i></a>
                                    <?php if(isIT()): ?>
                                        <a href="#" data-url="index.php?page=suppliers/view&id=<?= $id ?>&action=delete_file&file_id=<?= $f['id'] ?>" class="action-icon delete delete-btn" title="Xóa tài liệu"><i class="fas fa-trash-alt"></i></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Related Devices -->
        <div class="card mt-20">
            <div class="dashboard-card-header"><h3><i class="fas fa-server"></i> Thiết bị đã cung cấp (<?= count($devices) ?>)</h3></div>
            <?php if(empty($devices)): ?><p class="text-muted" style="padding:20px;">Trống.</p><?php else: ?>
                <div class="table-container" style="border:none; box-shadow:none;"><table class="content-table"><thead><tr><th>Mã tài sản</th><th>Tên thiết bị</th><th>Trạng thái</th></tr></thead><tbody>
                    <?php foreach($devices as $d): ?><tr>
                        <td><a href="index.php?page=devices/view&id=<?= $d['id'] ?>" class="text-primary font-medium"><?= htmlspecialchars($d['ma_tai_san']) ?></a></td>
                        <td><?= htmlspecialchars($d['ten_thiet_bi']) ?></td>
                        <td><span class="badge"><?= htmlspecialchars($d['trang_thai']) ?></span></td>
                    </tr><?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        </div>

        <!-- Related Services -->
        <div class="card mt-20">
            <div class="dashboard-card-header"><h3><i class="fas fa-cloud"></i> Dịch vụ / Phần mềm (<?= count($services) ?>)</h3></div>
            <?php if(empty($services)): ?><p class="text-muted" style="padding:20px;">Trống.</p><?php else: ?>
                <div class="table-container" style="border:none; box-shadow:none;"><table class="content-table"><thead><tr><th>Tên dịch vụ</th><th>Ngày hết hạn</th><th>Trạng thái</th></tr></thead><tbody>
                    <?php foreach($services as $s): ?><tr>
                        <td><a href="index.php?page=services/view&id=<?= $s['id'] ?>" class="font-bold"><?= htmlspecialchars($s['ten_dich_vu']) ?></a></td>
                        <td><?= date('d/m/Y', strtotime($s['ngay_het_han'])) ?></td>
                        <td><span class="badge"><?= htmlspecialchars($s['trang_thai']) ?></span></td>
                    </tr><?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Grid Layout */
.suppliers-view-grid { display: grid; grid-template-columns: 300px 1fr; gap: 25px; align-items: start; }

/* Modern Profile Card Design */
.profile-card { border: none; overflow: hidden; padding: 0 !important; display: flex; flex-direction: column; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
.profile-header-bg { height: 80px; background: linear-gradient(135deg, var(--primary-color), #166534); width: 100%; }
.profile-avatar-wrapper { margin-top: -40px; display: flex; justify-content: center; position: relative; z-index: 2; }
.profile-avatar { width: 80px; height: 80px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--primary-color); box-shadow: 0 4px 10px rgba(0,0,0,0.1); border: 4px solid #fff; }

.profile-main-info { padding: 15px 20px; text-align: center; }
.supplier-title { font-size: 1.2rem; font-weight: 800; color: #1e293b; margin-bottom: 8px; line-height: 1.3; }
.role-badge { display: inline-flex; align-items: center; gap: 6px; background: #f0fdf4; color: #166534; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; border: 1px solid #dcfce7; }

.profile-details-section { padding: 10px 20px 20px 20px; }
.section-divider { display: flex; align-items: center; text-align: center; margin: 15px 0; }
.section-divider::before, .section-divider::after { content: ''; flex: 1; border-bottom: 1px solid #f1f5f9; }
.section-divider span { padding: 0 10px; color: #94a3b8; font-size: 0.6rem; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; }

.contact-pill { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px; margin-bottom: 8px; transition: all 0.2s; text-align: left; }
.contact-pill:hover { border-color: var(--primary-color); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.cp-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px; }
.cp-name { font-weight: 700; color: #334155; font-size: 0.85rem; }
.cp-role { font-size: 0.6rem; color: #108042; background: #ecfdf5; padding: 1px 5px; border-radius: 4px; font-weight: 700; }
.cp-phone { display: flex; align-items: center; gap: 6px; color: #64748b; text-decoration: none; font-size: 0.75rem; font-weight: 500; }
.cp-phone:hover { color: var(--primary-color); }
.cp-phone i { color: var(--primary-color); font-size: 0.7rem; }

.legacy-detail { display: flex; flex-direction: column; gap: 8px; }
.ld-row { display: flex; align-items: center; gap: 10px; color: #475569; font-size: 0.8rem; padding: 6px 0; border-bottom: 1px dashed #f1f5f9; text-align: left; }
.ld-row i { color: #94a3b8; width: 14px; text-align: center; }

.profile-note-section { padding: 0 20px 25px 20px; }
.note-label { font-size: 0.6rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 6px; padding-left: 5px; text-align: left; }
.note-box { background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; padding: 10px; border-radius: 8px; font-size: 0.8rem; line-height: 1.5; font-style: italic; text-align: left; }

/* EXPLORER CARD STABLE */
.explorer-card { border: none; overflow: hidden; display: flex; flex-direction: column; }
.sticky-header { position: sticky; top: 0; z-index: 10; background: #fff; border-bottom: 1px solid #f1f5f9; }
.explorer-header { padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; }
.explorer-title { display: flex; align-items: center; gap: 10px; }
.explorer-title i { color: var(--primary-color); font-size: 1.1rem; }
.explorer-title h3 { margin: 0; font-size: 0.95rem; font-weight: 700; }
.count-badge { background: #f1f5f9; color: #64748b; font-size: 0.7rem; padding: 1px 6px; border-radius: 10px; }

.btn-upload-toggle { background: #f0fdf4; color: #108042; border: 1px solid #dcfce7; padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 0.8rem; }
.btn-upload-toggle:hover { background: #108042; color: #fff; }

.explorer-upload-zone { padding: 15px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.upload-bar { display: flex; gap: 10px; align-items: center; }
.custom-file-input-wrapper { flex: 1; position: relative; }
.custom-file-input-wrapper input { position: absolute; width: 0; height: 0; opacity: 0; }
.custom-file-input-wrapper label { display: block; background: #fff; border: 1px dashed #cbd5e1; padding: 7px 12px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: 0.2s; }
.custom-file-input-wrapper:hover label { border-color: var(--primary-color); color: var(--primary-color); }

/* Scrollable Workspace */
.explorer-body-scroll { max-height: 320px; overflow-y: auto; background: #fff; }
.explorer-body-scroll::-webkit-scrollbar { width: 5px; }
.explorer-body-scroll::-webkit-scrollbar-track { background: #f8fafc; }
.explorer-body-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

.explorer-row { display: flex; align-items: center; gap: 15px; padding: 10px 20px; border-bottom: 1px solid #f8fafc; transition: 0.2s; }
.explorer-row:hover { background: #f8fbff; }
.explorer-row:last-child { border-bottom: none; }

.row-icon { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
.row-content { flex: 1; min-width: 0; }
.file-link { display: block; font-weight: 600; font-size: 0.85rem; color: #334155; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.file-link:hover { color: var(--primary-color); }

.row-meta { display: flex; align-items: center; gap: 10px; margin-top: 2px; }
.meta-tag { font-size: 0.6rem; font-weight: 800; text-transform: uppercase; padding: 0px 5px; border-radius: 3px; }
.meta-date { font-size: 0.65rem; color: #94a3b8; }

.row-actions { display: flex; gap: 4px; }
.action-icon { width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; color: #94a3b8; border: 1px solid #e2e8f0; background: #fff; transition: 0.2s; }
.action-icon:hover { transform: scale(1.1); color: var(--primary-color); border-color: var(--primary-color); }
.action-icon.delete:hover { color: #ef4444; border-color: #ef4444; background: #fef2f2; }

.explorer-empty { padding: 40px; text-align: center; color: #cbd5e1; font-size: 0.85rem; }
.explorer-empty i { font-size: 2.5rem; margin-bottom: 10px; opacity: 0.4; }

@media (max-width: 992px) {
    .suppliers-view-grid { grid-template-columns: 1fr; }
    .mobile-hide { display: none; }
}
</style>

<script>
function toggleUploadArea() {
    const area = document.getElementById('upload-area');
    area.style.display = area.style.display === 'none' ? 'block' : 'none';
}
function updateFileName(input) {
    const label = document.getElementById('file-label').querySelector('span');
    if(input.files.length > 0) {
        label.textContent = input.files[0].name;
        label.parentElement.style.borderColor = '#108042';
        label.parentElement.style.color = '#108042';
    }
}
</script>