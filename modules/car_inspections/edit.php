<?php
// modules/car_inspections/edit.php
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    set_message('error', 'ID không hợp lệ.');
    header('Location: index.php?page=car_inspections/list');
    exit;
}

// ==================================================
// HANDLE UPDATE POST
// ==================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("UPDATE car_inspections SET 
            inspector_id = ?, 
            inspection_date = ?, 
            inspection_time = ?, 
            project_address = ?, 
            inspector_position = ?, 
            bql_name_1 = ?, 
            bql_pos_1 = ?, 
            bql_name_2 = ?, 
            bql_pos_2 = ?, 
            results_summary = ?, 
            violation_count = ?, 
            violation_details = ?, 
            other_opinions = ?, 
            status = ? 
            WHERE id = ?");
        
        $stmt->execute([
            $_POST['inspector_id'],
            $_POST['inspection_date'],
            $_POST['inspection_time'],
            $_POST['project_address'],
            $_POST['inspector_position'],
            $_POST['bql_name_1'],
            $_POST['bql_pos_1'],
            $_POST['bql_name_2'],
            $_POST['bql_pos_2'],
            $_POST['results_summary'],
            $_POST['violation_count'] ?? 0,
            $_POST['violation_details'],
            $_POST['other_opinions'],
            $_POST['status'],
            $id
        ]);
        
        set_message('success', 'Cập nhật kết quả đối soát thành công!');
        // Refresh data or redirect
        header("Location: index.php?page=car_inspections/edit&id=$id");
        exit;
    } catch (PDOException $e) {
        set_message('error', 'Lỗi khi cập nhật: ' . $e->getMessage());
    }
}

// ==================================================
// FETCH CURRENT DATA
// ==================================================
$stmt = $pdo->prepare("SELECT ci.*, p.ten_du_an, p.dia_chi_duong, p.dia_chi_phuong_xa, p.dia_chi_tinh_tp, u.fullname as inspector_name 
                      FROM car_inspections ci 
                      JOIN projects p ON ci.project_id = p.id 
                      JOIN users u ON ci.inspector_id = u.id 
                      WHERE ci.id = ?");
$stmt->execute([$id]);
$ins = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ins) {
    set_message('error', 'Không tìm thấy dữ liệu.');
    header('Location: index.php?page=car_inspections/list');
    exit;
}

// Ghép địa chỉ từ bảng projects làm mặc định nếu project_address trống
if (empty($ins['project_address'])) {
    $ins['project_address'] = implode(', ', array_filter([
        $ins['dia_chi_duong'] ?? '',
        $ins['dia_chi_phuong_xa'] ?? '',
        $ins['dia_chi_tinh_tp'] ?? ''
    ]));
}

$inspectors_list = $pdo->query("SELECT id, fullname FROM users WHERE role IN ('admin', 'it') ORDER BY fullname")->fetchAll(PDO::FETCH_ASSOC);
$pageTitle = "Cập nhật kết quả: " . $ins['ten_du_an'];
?>

<style>
    /* REFINED AUDIT EDIT STYLES */
    .audit-master-wrapper {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 25px;
        margin-top: 5px;
        animation: fadeIn 0.3s ease-out;
    }

    .audit-card {
        background: #ffffff;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        overflow: hidden;
    }

    .side-card-header {
        padding: 12px 18px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        font-weight: 700;
        font-size: 0.8rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .side-card-body { padding: 18px; }

    .meta-info-item { margin-bottom: 15px; }
    .meta-info-item label { display: block; font-size: 0.65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 2px; }
    .meta-info-item .val { font-size: 1rem; font-weight: 700; color: #1e293b; }

    .audit-input-group { margin-bottom: 15px; }
    .audit-input-group label { display: block; font-size: 0.75rem; font-weight: 600; color: #475569; margin-bottom: 6px; }
    
    .audit-control {
        width: 100%;
        height: 38px;
        padding: 0 12px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.9rem;
        color: #334155;
        transition: all 0.2s;
        font-family: inherit;
    }
    .audit-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(36, 162, 92, 0.08);
        outline: none;
    }
    textarea.audit-control { height: auto; padding: 10px 12px; line-height: 1.5; }

    .card-title-bar {
        padding: 15px 20px;
        border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; gap: 10px;
    }
    .card-title-bar h3 { margin: 0; font-size: 0.95rem; font-weight: 700; color: #334155; }
    .bg-blue-tint { background: #f8fbff; color: #0284c7; border-bottom: 1px solid #e0f2fe; }
    .bg-green-tint { background: #f8fff9; color: #108042; border-bottom: 1px solid #dcfce7; }

    /* Participants 6:4 */
    .participants-container { padding: 20px; }
    .p-grid-table { display: flex; flex-direction: column; gap: 10px; }
    .p-grid-header { display: grid; grid-template-columns: 6fr 4fr; gap: 12px; padding: 0 5px; }
    .p-grid-header span { font-size: 0.65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; }
    .p-grid-row { display: grid; grid-template-columns: 6fr 4fr; gap: 12px; align-items: center; }
    
    /* REFINED HEADER BUTTONS */
    .header-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .btn-action {
        height: 36px;
        padding: 0 16px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        border: none;
    }
    .btn-save { background: var(--gradient-primary); color: #fff; box-shadow: 0 4px 6px rgba(36, 162, 92, 0.15); }
    .btn-save:hover { transform: translateY(-1px); box-shadow: 0 6px 12px rgba(36, 162, 92, 0.2); }
    
    .btn-print { background: #fff; color: #108042; border: 1.5px solid #108042; }
    .btn-print:hover { background: #f0fdf4; }
    
    .btn-cancel { background: #fff; color: #64748b; border: 1.5px solid #cbd5e1; }
    .btn-cancel:hover { background: #f8fafc; color: #475569; }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    @media (max-width: 992px) {
        .audit-master-wrapper { grid-template-columns: 1fr; }
        .page-header { flex-direction: column; align-items: flex-start; gap: 15px; }
        .header-actions { width: 100%; justify-content: flex-start; flex-wrap: wrap; }
        .btn-action { flex: 1; min-width: 120px; justify-content: center; }
    }
</style>

<div class="page-header">
    <h2><i class="fas fa-edit"></i> Cập nhật kết quả đối soát</h2>
    <div class="header-actions">
        <a href="index.php?page=car_inspections/list" class="btn-action btn-cancel"><i class="fas fa-times"></i> Hủy</a>
        
        <?php if($ins['status'] == 'completed'): ?>
            <a href="index.php?page=car_inspections/print&id=<?= $ins['id'] ?>" target="_blank" class="btn-action btn-print">
                <i class="fas fa-print"></i> In biên bản
            </a>
        <?php endif; ?>
        
        <button type="submit" form="audit-edit-form" class="btn-action btn-save">
            <i class="fas fa-save"></i> Lưu dữ liệu
        </button>
    </div>
</div>

<form action="" method="POST" id="audit-edit-form">
    <div class="audit-master-wrapper">
        
        <!-- SIDEBAR -->
        <aside class="audit-side-panel">
            <div class="audit-card">
                <div class="side-card-header">Lịch trình</div>
                <div class="side-card-body">
                    <div class="meta-info-item">
                        <label>Dự án</label>
                        <div class="val text-primary">Chung cư <?= htmlspecialchars($ins['ten_du_an'] ?? '') ?></div>
                    </div>
                    
                    <div class="audit-input-group">
                        <label>Ngày kiểm tra</label>
                        <input type="date" name="inspection_date" class="audit-control" value="<?= $ins['inspection_date'] ?>">
                    </div>
                    
                    <div class="audit-input-group">
                        <label>Giờ thực hiện</label>
                        <input type="time" name="inspection_time" class="audit-control" value="<?= $ins['inspection_time'] ?>">
                    </div>
                    
                    <div class="audit-input-group">
                        <label>Trạng thái</label>
                        <select name="status" class="audit-control">
                            <option value="pending" <?= $ins['status'] == 'pending' ? 'selected' : '' ?>>Chờ kiểm tra</option>
                            <option value="completed" <?= $ins['status'] == 'completed' ? 'selected' : '' ?>>Đã hoàn thành</option>
                        </select>
                    </div>
                </div>
            </div>
        </aside>

        <!-- MAIN -->
        <main class="audit-main-panel">
            
            <!-- SECTION I -->
            <div class="audit-card mb-4">
                <div class="card-title-bar bg-blue-tint">
                    <i class="fas fa-users"></i>
                    <h3>I. THÀNH PHẦN THAM DỰ & ĐỊA ĐIỂM</h3>
                </div>
                <div class="participants-container">
                    <div class="audit-input-group mb-4">
                        <label>Địa chỉ bãi xe</label>
                        <input type="text" name="project_address" class="audit-control" value="<?= htmlspecialchars($ins['project_address'] ?? '') ?>" placeholder="Địa chỉ hiển thị trên biên bản...">
                    </div>

                    <div class="p-grid-table">
                        <div class="p-grid-header">
                            <span>Họ và tên thành viên</span>
                            <span>Chức danh</span>
                        </div>
                        
                        <div class="p-grid-row">
                            <select name="inspector_id" class="audit-control">
                                <?php foreach($inspectors_list as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= $ins['inspector_id'] == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['fullname'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="inspector_position" class="audit-control" value="<?= htmlspecialchars($ins['inspector_position'] ?? '') ?>" placeholder="VD: Nhân viên IT">
                        </div>

                        <div class="p-grid-row">
                            <input type="text" name="bql_name_1" class="audit-control" value="<?= htmlspecialchars($ins['bql_name_1'] ?? '') ?>" placeholder="Đại diện BQL 1">
                            <input type="text" name="bql_pos_1" class="audit-control" value="<?= htmlspecialchars($ins['bql_pos_1'] ?? '') ?>" placeholder="Chức vụ 1">
                        </div>

                        <div class="p-grid-row">
                            <input type="text" name="bql_name_2" class="audit-control" value="<?= htmlspecialchars($ins['bql_name_2'] ?? '') ?>" placeholder="Đại diện BQL 2">
                            <input type="text" name="bql_pos_2" class="audit-control" value="<?= htmlspecialchars($ins['bql_pos_2'] ?? '') ?>" placeholder="Chức vụ 2">
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION II & III -->
            <div class="audit-card">
                <div class="card-title-bar bg-green-tint">
                    <i class="fas fa-file-signature"></i>
                    <h3>II & III. NỘI DUNG & Ý KIẾN KIẾN NGHỊ</h3>
                </div>
                <div class="side-card-body" style="padding: 20px;">
                    <div class="audit-input-group">
                        <label>Tóm tắt kết quả (Nội dung chính)</label>
                        <textarea name="results_summary" class="audit-control" rows="3" placeholder="Ghi nhận chung về cuộc kiểm tra..."><?= htmlspecialchars($ins['results_summary'] ?? '') ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="audit-input-group">
                                <label>Số xe vi phạm</label>
                                <input type="number" name="violation_count" class="audit-control" value="<?= $ins['violation_count'] ?? 0 ?>" style="font-weight: 700;">
                            </div>
                        </div>
                    </div>

                    <div class="audit-input-group">
                        <label>Danh sách biển số xe vi phạm chi tiết</label>
                        <textarea name="violation_details" class="audit-control" rows="4" placeholder="VD: 59C2-360.15..."><?= htmlspecialchars($ins['violation_details'] ?? '') ?></textarea>
                    </div>

                    <div class="audit-input-group">
                        <label>Ý kiến khác & Kiến nghị</label>
                        <textarea name="other_opinions" class="audit-control" rows="2" placeholder="Ghi nhận ý kiến các bên..."><?= htmlspecialchars($ins['other_opinions'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

        </main>
    </div>
</form>
