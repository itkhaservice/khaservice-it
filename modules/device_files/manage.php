<?php
// This file is expected to be included within modules/devices/view.php
// and therefore expects $device_id to be available.

if (!isset($device['id'])) {
    set_message('error', 'Không thể quản lý file: ID thiết bị không xác định.');
    return;
}

$device_id = $device['id'];

// Constants for file upload validation
const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
const ALLOWED_MIME_TYPES = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword', // .doc
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
    'application/vnd.ms-excel', // .xls
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
];

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_file'])) {
    $loai_file = $_POST['loai_file'] ?? 'Khác';
    $target_dir_base = __DIR__ . "/../../uploads/";
    $target_sub_dir = '';

    switch ($loai_file) {
        case 'HoaDon': $target_sub_dir = 'invoices/'; break;
        case 'BienBan': $target_sub_dir = 'reports/'; break;
        case 'HinhAnh': $target_sub_dir = 'photos/'; break;
        default: $target_sub_dir = 'others/'; // Fallback for 'Khác' or new types
    }

    $target_dir = $target_dir_base . $target_sub_dir;

    // Ensure the target directory exists
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            set_message('error', 'Không thể tạo thư mục tải lên: ' . $target_dir);
            // Early exit if directory creation fails
            return;
        }
    }

    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['file_upload']['tmp_name'];
        $file_name = basename($_FILES['file_upload']['name']);
        $file_size = $_FILES['file_upload']['size'];
        $file_type = mime_content_type($file_tmp_name); // Get MIME type from content

        // Validate file size
        if ($file_size > MAX_FILE_SIZE) {
            set_message('error', "File '$file_name' vượt quá kích thước tối đa " . (MAX_FILE_SIZE / 1024 / 1024) . "MB.");
            // Prevent further processing
            return;
        }

        // Validate file type
        if (!in_array($file_type, ALLOWED_MIME_TYPES)) {
            set_message('error', "File '$file_name' có định dạng không được phép. Chỉ chấp nhận JPG, PNG, GIF, PDF, DOC, DOCX, XLS, XLSX.");
            // Prevent further processing
            return;
        }

        $file_path = $target_dir . uniqid() . '_' . $file_name; // Add unique ID to prevent overwrites

        if (move_uploaded_file($file_tmp_name, $file_path)) {
            // Save file info to database
            $relative_file_path = "uploads/" . $target_sub_dir . basename($file_path);
            $stmt = $pdo->prepare("INSERT INTO device_files (device_id, loai_file, file_path) VALUES (?, ?, ?)");
            $stmt->execute([$device_id, $loai_file, $relative_file_path]);
            set_message('success', "File '$file_name' đã được tải lên thành công.");
        } else {
            set_message('error', "Có lỗi khi di chuyển file '$file_name'.");
        }
    } else {
        if ($_FILES['file_upload']['error'] !== UPLOAD_ERR_NO_FILE) {
            set_message('error', "Lỗi tải lên file: " . $_FILES['file_upload']['error']);
        }
    }
}

// Handle file deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_file' && isset($_GET['file_id'])) {
    $file_id_to_delete = $_GET['file_id'];

    $stmt = $pdo->prepare("SELECT file_path FROM device_files WHERE id = ? AND device_id = ?");
    $stmt->execute([$file_id_to_delete, $device_id]);
    $file_to_delete = $stmt->fetch();

    if ($file_to_delete) {
        $full_path = __DIR__ . "/../../" . $file_to_delete['file_path'];
        if (file_exists($full_path) && unlink($full_path)) {
            $delete_stmt = $pdo->prepare("DELETE FROM device_files WHERE id = ?");
            $delete_stmt->execute([$file_id_to_delete]);
            set_message('success', "File đã được xóa thành công.");
        } else {
            // If file doesn't exist on disk but in DB, delete from DB anyway.
            if (!file_exists($full_path)) {
                 $delete_stmt = $pdo->prepare("DELETE FROM device_files WHERE id = ?");
                 $delete_stmt->execute([$file_id_to_delete]);
                 set_message('success', "File không tồn tại trên server nhưng đã được xóa khỏi DB.");
            } else {
                set_message('error', "Không thể xóa file khỏi server.");
            }
        }
    } else {
        set_message('error', "File không tìm thấy trong database.");
    }
    // Redirect to clean up URL
    header("Location: index.php?page=devices/view&id=" . $device_id);
    exit;
}

// Fetch existing files for this device
$files_stmt = $pdo->prepare("SELECT * FROM device_files WHERE device_id = ? ORDER BY uploaded_at DESC");
$files_stmt->execute([$device_id]);
$device_files = $files_stmt->fetchAll();
?>

<div class="file-management-section">
    <h3>Quản lý File đính kèm</h3>

    <div class="form-container">
        <form action="index.php?page=devices/view&id=<?php echo $device_id; ?>" method="POST" enctype="multipart/form-data" class="form-grid">
            <div class="form-group">
                <label for="file_upload">Chọn File</label>
                <input type="file" id="file_upload" name="file_upload" required>
            </div>
            <div class="form-group">
                <label for="loai_file">Loại File</label>
                <select id="loai_file" name="loai_file">
                    <option value="HoaDon">Hóa đơn</option>
                    <option value="BienBan">Biên bản</option>
                    <option value="HinhAnh">Hình ảnh</option>
                    <option value="Khác">Khác</option>
                </select>
            </div>
            <div class="form-actions full-width" style="text-align: left;">
                <button type="submit" name="upload_file" class="btn btn-primary">Tải lên</button>
            </div>
        </form>
    </div>

    <h4>Các File đã tải lên:</h4>
    <?php if (empty($device_files)): ?>
        <p>Chưa có file đính kèm nào.</p>
    <?php else: ?>
        <table class="content-table compact-table">
            <thead>
                <tr>
                    <th>Loại File</th>
                    <th>Tên File</th>
                    <th>Ngày tải lên</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($device_files as $file): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($file['loai_file']); ?></td>
                        <td>
                            <a href="../<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank">
                                <?php echo htmlspecialchars(basename($file['file_path'])); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($file['uploaded_at']); ?></td>
                        <td class="actions">
                            <a href="index.php?page=devices/view&id=<?php echo $device_id; ?>&action=delete_file&file_id=<?php echo $file['id']; ?>" class="delete">Xóa</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
    .file-management-section {
        margin-top: 30px;
        border-top: 1px solid #eee;
        padding-top: 20px;
    }
    .file-management-section h3 {
        margin-bottom: 20px;
    }
    .compact-table th, .compact-table td {
        padding: 8px 10px;
        font-size: 0.85em;
    }
    .success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 15px;
    }
    .error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 15px;
    }
</style>
