<?php
// modules/car_inspections/add.php
$pageTitle = "Đặt lịch kiểm tra xe";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'];
    $inspector_id = $_POST['inspector_id'];
    $inspection_date = $_POST['inspection_date'];
    $inspection_time = $_POST['inspection_time'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO car_inspections (project_id, inspector_id, inspection_date, inspection_time, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$project_id, $inspector_id, $inspection_date, $inspection_time]);
        set_message("Đã đặt lịch kiểm tra thành công!", "success");
        echo '<script>window.location.href = "index.php?page=car_inspections/list";</script>';
        exit;
    } catch (PDOException $e) {
        set_message("Lỗi: " . $e->getMessage(), "error");
    }
}

$projects_list = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an")->fetchAll(PDO::FETCH_ASSOC);
$inspectors_list = $pdo->query("SELECT id, fullname FROM users WHERE role IN ('admin', 'it') ORDER BY fullname")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h2><i class="fas fa-plus-circle"></i> Đặt lịch kiểm tra mới</h2>
    <a href="index.php?page=car_inspections/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
</div>

<div class="card form-card-modern">
    <div class="card-header-f">
        <i class="fas fa-calendar-alt"></i> Thông tin chi tiết lịch hẹn
    </div>
    <form action="" method="POST" class="standard-form">
        <div class="form-grid">
            <div class="form-group-f">
                <label>Dự án cần kiểm tra <span class="text-danger">*</span></label>
                <select name="project_id" required>
                    <option value="">-- Chọn dự án --</option>
                    <?php foreach($projects_list as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['ten_du_an']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group-f">
                <label>Người phụ trách <span class="text-danger">*</span></label>
                <select name="inspector_id" required>
                    <option value="">-- Chọn nhân viên --</option>
                    <?php foreach($inspectors_list as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $_SESSION['user_id'] == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['fullname']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group-f">
                <label>Ngày kiểm tra <span class="text-danger">*</span></label>
                <input type="date" name="inspection_date" value="<?= $_GET['date'] ?? date('Y-m-d') ?>" required>
            </div>
            <div class="form-group-f">
                <label>Giờ kiểm tra <span class="text-danger">*</span></label>
                <input type="time" name="inspection_time" value="<?= date('H:i') ?>" required>
            </div>
        </div>

        <div class="form-actions-f">
            <button type="reset" class="btn btn-secondary">Hủy nhập</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu lịch hẹn</button>
        </div>
    </form>
</div>

<style>
    .form-card-modern { padding: 0 !important; overflow: hidden; border: none; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    .card-header-f { background: #f8fafc; padding: 15px 25px; font-weight: 700; border-bottom: 1px solid #e2e8f0; color: #1e293b; }
    .standard-form { padding: 25px; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-group-f { display: flex; flex-direction: column; gap: 8px; }
    .form-group-f label { font-weight: 700; font-size: 0.8rem; color: #64748b; text-transform: uppercase; }
    .form-group-f input, .form-group-f select { height: 42px; padding: 0 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; width: 100%; transition: border-color 0.2s; }
    .form-group-f input:focus, .form-group-f select:focus { border-color: var(--primary-color); outline: none; }
    .form-actions-f { margin-top: 30px; padding-top: 20px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 10px; }
    .form-actions-f .btn { height: 42px; padding: 0 25px; }

    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
    }
</style>
