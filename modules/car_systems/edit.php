<?php
// Check permissions
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'it')) {
    set_message("Bạn không có quyền chỉnh sửa!", "error");
    echo '<script>window.location.href = "index.php?page=car_systems/list";</script>';
    exit;
}

$id = $_GET['id'] ?? 0;
if (!$id) {
    echo '<script>window.location.href = "index.php?page=car_systems/list";</script>';
    exit;
}

// Fetch Existing Data
try {
    $stmt = $pdo->prepare("SELECT * FROM car_system_configs WHERE id = ?");
    $stmt->execute([$id]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config) {
        set_message("Không tìm thấy cấu hình này.", "error");
        echo '<script>window.location.href = "index.php?page=car_systems/list";</script>';
        exit;
    }
} catch (PDOException $e) {
    set_message("Lỗi tải dữ liệu.", "error");
    echo '<script>window.location.href = "index.php?page=car_systems/list";</script>';
    exit;
}

// Fetch Projects
try {
    $projects = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $projects = [];
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'] ?? '';
    $server_ip = $_POST['server_ip'] ?? '';
    $db_name = $_POST['db_name'] ?? '';
    $folder_path = $_POST['folder_path'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $errors = [];
    if (empty($project_id)) $errors[] = "Vui lòng chọn dự án.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE car_system_configs SET project_id=?, server_ip=?, db_name=?, folder_path=?, username=?, password=? WHERE id=?");
            $stmt->execute([$project_id, $server_ip, $db_name, $folder_path, $username, $password, $id]);
            
            set_message("Cập nhật thành công!", "success");
            echo '<script>window.location.href = "index.php?page=car_systems/list";</script>';
            exit;
        } catch (PDOException $e) {
            $errors[] = "Lỗi cập nhật: " . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        foreach ($errors as $error) set_message($error, "error");
    }
}
?>

<div class="page-header">
    <h2><i class="fas fa-edit"></i> Chỉnh sửa Cấu hình</h2>
    <a href="index.php?page=car_systems/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="" class="form-grid">
            <div class="form-group full-width">
                <label for="project_id" class="form-label">Dự án <span class="required">*</span></label>
                <select name="project_id" id="project_id" class="form-control" required>
                    <option value="">-- Chọn Dự án --</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($config['project_id'] == $p['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['ten_du_an']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="server_ip" class="form-label">Máy chủ (IP/Domain)</label>
                <input type="text" name="server_ip" id="server_ip" class="form-control" value="<?= htmlspecialchars($config['server_ip']) ?>">
            </div>

            <div class="form-group">
                <label for="db_name" class="form-label">Tên Cơ sở dữ liệu (Database)</label>
                <input type="text" name="db_name" id="db_name" class="form-control" value="<?= htmlspecialchars($config['db_name']) ?>">
            </div>

            <div class="form-group full-width">
                <label for="folder_path" class="form-label">Đường dẫn thư mục cài đặt</label>
                <input type="text" name="folder_path" id="folder_path" class="form-control" value="<?= htmlspecialchars($config['folder_path']) ?>">
            </div>

            <div class="form-group">
                <label for="username" class="form-label">Tài khoản quản trị</label>
                <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($config['username']) ?>">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Mật khẩu</label>
                <input type="text" name="password" id="password" class="form-control" value="<?= htmlspecialchars($config['password']) ?>">
            </div>

            <div class="form-actions full-width">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Cập nhật</button>
            </div>
        </form>
    </div>
</div>