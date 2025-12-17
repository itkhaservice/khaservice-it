<?php
// This file is expected to be included within modules/devices/view.php
// and therefore expects $device_id to be available.

if (!isset($device['id'])) {
    echo "<p class='error'>Không thể quản lý file: ID thiết bị không xác định.</p>";
    return;
}

$device_id = $device['id'];
$upload_errors = [];
$upload_messages = [];

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
        mkdir($target_dir, 0777, true);
    }

    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['file_upload']['tmp_name'];
        $file_name = basename($_FILES['file_upload']['name']);
        $file_path = $target_dir . uniqid() . '_' . $file_name; // Add unique ID to prevent overwrites

        if (move_uploaded_file($file_tmp_name, $file_path)) {
            // Save file info to database
            $relative_file_path = "uploads/" . $target_sub_dir . basename($file_path);
            $stmt = $pdo->prepare("INSERT INTO device_files (device_id, loai_file, file_path) VALUES (?, ?, ?)");
            $stmt->execute([$device_id, $loai_file, $relative_file_path]);
            $upload_messages[] = "File '$file_name' đã được tải lên thành công.";
        } else {
            $upload_errors[] = "Có lỗi khi di chuyển file '$file_name'.";
        }
    } else {
        if ($_FILES['file_upload']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_errors[] = "Lỗi tải lên file: " . $_FILES['file_upload']['error'];
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
            $upload_messages[] = "File đã được xóa thành công.";
        } else {
            // If file doesn't exist on disk but in DB, delete from DB anyway.
            if (!file_exists($full_path)) {
                 $delete_stmt = $pdo->prepare("DELETE FROM device_files WHERE id = ?");
                 $delete_stmt->execute([$file_id_to_delete]);
                 $upload_messages[] = "File không tồn tại trên server nhưng đã được xóa khỏi DB.";
            } else {
                $upload_errors[] = "Không thể xóa file khỏi server.";
            }
        }
    } else {
        $upload_errors[] = "File không tìm thấy trong database.";
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

    <?php if (!empty($upload_errors)): ?>
        <div class="error">
            <?php foreach ($upload_errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($upload_messages)): ?>
        <div class="success">
            <?php foreach ($upload_messages as $message): ?>
                <p><?php echo $message; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

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
                            <a href="index.php?page=devices/view&id=<?php echo $device_id; ?>&action=delete_file&file_id=<?php echo $file['id']; ?>" class="delete" onclick="return confirm('Bạn có chắc muốn xóa file này?');">Xóa</a>
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
