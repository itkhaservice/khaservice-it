<?php
if (!isset($device['id'])) {
    return;
}
$device_id = $device['id'];

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_file'])) {
    $loai_file = $_POST['loai_file'] ?? 'Khác';
    $target_dir_base = __DIR__ . "/../../uploads/";
    $target_sub_dir = '';

    switch ($loai_file) {
        case 'HoaDon': $target_sub_dir = 'invoices/'; break;
        case 'BienBan': $target_sub_dir = 'reports/'; break;
        case 'HinhAnh': $target_sub_dir = 'photos/'; break;
        default: $target_sub_dir = 'others/';
    }

    $target_dir = $target_dir_base . $target_sub_dir;

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
        $file_name = basename($_FILES['file_upload']['name']);
        $file_path = $target_dir . uniqid() . '_' . $file_name;

        if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $file_path)) {
            $relative_file_path = "uploads/" . $target_sub_dir . basename($file_path);
            $stmt = $pdo->prepare("INSERT INTO device_files (device_id, loai_file, file_path) VALUES (?, ?, ?)");
            $stmt->execute([$device_id, $loai_file, $relative_file_path]);
            set_message('success', 'Tải lên thành công.');
        }
    }
}

// Handle deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_file' && isset($_GET['file_id'])) {
    $stmt = $pdo->prepare("SELECT file_path FROM device_files WHERE id = ? AND device_id = ?");
    $stmt->execute([$_GET['file_id'], $device_id]);
    $file = $stmt->fetch();
    if ($file) {
        $full_path = __DIR__ . "/../../" . $file['file_path'];
        if (file_exists($full_path)) unlink($full_path);
        $pdo->prepare("DELETE FROM device_files WHERE id = ?")->execute([$_GET['file_id']]);
        set_message('success', 'Đã xóa file.');
    }
    header("Location: index.php?page=devices/view&id=" . $device_id);
    exit;
}

$files_stmt = $pdo->prepare("SELECT * FROM device_files WHERE device_id = ? ORDER BY uploaded_at DESC");
$files_stmt->execute([$device_id]);
$device_files = $files_stmt->fetchAll();

function getFileIconInfo($filePath) {
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
?>

<div class="file-manager-container">
    
    <!-- Upload Area -->
    <div class="upload-zone">
        <form action="index.php?page=devices/view&id=<?php echo $device_id; ?>" method="POST" enctype="multipart/form-data">
            <div class="upload-wrapper">
                <div class="select-group">
                    <label>Loại file</label>
                    <select name="loai_file">
                        <option value="HinhAnh">Hình ảnh</option>
                        <option value="HoaDon">Hóa đơn</option>
                        <option value="BienBan">Biên bản</option>
                        <option value="Khác">Khác</option>
                    </select>
                </div>
                <div class="file-group">
                    <label>Chọn tài liệu</label>
                    <input type="file" name="file_upload" required>
                </div>
                <button type="submit" name="upload_file" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Tải lên
                </button>
            </div>
        </form>
    </div>

    <!-- Files Grid -->
    <?php if (empty($device_files)): ?>
        <div class="empty-files-state">
            <i class="fas fa-folder-open"></i>
            <p>Chưa có tài liệu đính kèm.</p>
        </div>
    <?php else: ?>
        <div class="files-modern-grid">
            <?php foreach ($device_files as $file): 
                $fileInfo = getFileIconInfo($file['file_path']);
                $fileName = htmlspecialchars(basename($file['file_path']));
                $fileUrl = "../" . htmlspecialchars($file['file_path']);
            ?>
                <div class="file-modern-card">
                    <div class="file-top">
                        <?php if ($fileInfo['type'] === 'image'): ?>
                            <div class="file-img-preview" style="background-image: url('<?php echo $fileUrl; ?>')">
                                <a href="<?php echo $fileUrl; ?>" target="_blank" class="zoom-icon"><i class="fas fa-search-plus"></i></a>
                            </div>
                        <?php else: ?>
                            <div class="file-icon-preview">
                                <i class="fas <?php echo $fileInfo['icon']; ?>" style="color: <?php echo $fileInfo['color']; ?>"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="file-badge"><?php echo htmlspecialchars($file['loai_file']); ?></div>
                    </div>
                    
                    <div class="file-bottom">
                        <div class="file-name-container">
                            <a href="<?php echo $fileUrl; ?>" target="_blank" class="file-name" title="<?php echo $fileName; ?>">
                                <?php echo $fileName; ?>
                            </a>
                            <span class="file-date"><?php echo date('d/m/y', strtotime($file['uploaded_at'])); ?></span>
                        </div>
                        <div class="file-actions">
                            <a href="<?php echo $fileUrl; ?>" download class="act-btn" title="Tải xuống"><i class="fas fa-download"></i></a>
                            <a href="#" data-url="index.php?page=devices/view&id=<?php echo $device_id; ?>&action=delete_file&file_id=<?php echo $file['id']; ?>" 
                               class="act-btn text-danger delete-btn" title="Xóa"><i class="fas fa-trash-alt"></i></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.upload-zone {
    background: #f8fafc;
    border: 2px dashed #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
}
.upload-wrapper {
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}
.upload-wrapper .select-group, .upload-wrapper .file-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.upload-wrapper label {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--text-light-color);
    text-transform: uppercase;
}
.upload-wrapper select, .upload-wrapper input {
    height: 38px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 0 10px;
    background: #fff;
}
.upload-wrapper .btn { height: 38px; }

/* Modern Grid */
.files-modern-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 20px;
}
.file-modern-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
}
.file-modern-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
}
.file-top {
    height: 120px;
    position: relative;
    background: #f1f5f9;
}
.file-img-preview {
    width: 100%; height: 100%;
    background-size: cover;
    background-position: center;
    display: flex; align-items: center; justify-content: center;
}
.zoom-icon {
    background: rgba(0,0,0,0.4);
    color: #fff;
    width: 32px; height: 32px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    opacity: 0; transition: 0.3s;
}
.file-img-preview:hover .zoom-icon { opacity: 1; }
.file-icon-preview {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-size: 3rem;
}
.file-badge {
    position: absolute; top: 8px; left: 8px;
    background: rgba(255,255,255,0.9);
    padding: 2px 8px; border-radius: 4px;
    font-size: 0.65rem; font-weight: 700; text-transform: uppercase;
    color: var(--secondary-color);
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.file-bottom {
    padding: 12px;
    display: flex; justify-content: space-between; align-items: flex-start;
    gap: 10px;
}
.file-name-container {
    display: flex; flex-direction: column; gap: 2px;
    overflow: hidden;
}
.file-name {
    font-size: 0.85rem; font-weight: 600; color: var(--text-color);
    text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.file-date { font-size: 0.7rem; color: #94a3b8; }
.file-actions { display: flex; flex-direction: column; gap: 5px; }
.act-btn {
    color: #94a3b8; font-size: 0.85rem; transition: 0.2s;
}
.act-btn:hover { color: var(--primary-color); }
.act-btn.del:hover { color: #ef4444; }

@media (max-width: 768px) {
    .upload-zone { padding: 15px; }
    .upload-wrapper { flex-direction: column; align-items: stretch; gap: 15px; }
    .upload-wrapper .select-group, .upload-wrapper .file-group { width: 100%; }
    .upload-wrapper select, .upload-wrapper input { width: 100%; height: 44px; }
    .upload-wrapper .btn { width: 100%; height: 44px; justify-content: center; }
    
    .files-modern-grid { grid-template-columns: 1fr; }
    .file-modern-card { margin-bottom: 10px; }
    .file-top { height: 160px; } /* Cao hơn chút trên mobile để ảnh rõ hơn */
}
</style>
