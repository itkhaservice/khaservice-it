<?php
$id = $_GET['id'] ?? null;
if (!$id) { header("Location: index.php?page=maintenance/history"); exit; }

$stmt = $pdo->prepare("SELECT * FROM maintenance_logs WHERE id = ?");
$stmt->execute([$id]);
$log = $stmt->fetch();

if (!$log) { header("Location: index.php?page=maintenance/history"); exit; }

$projects = $pdo->query("SELECT id, ten_du_an FROM projects ORDER BY ten_du_an")->fetchAll();
$current_project_id = $log['project_id'];

function getFastDateTime($h, $m, $d, $mon, $y) {
    if (empty($h) || empty($m) || empty($d) || empty($mon) || empty($y)) return null;
    return sprintf("%04d-%02d-%02d %02d:%02d:00", $y, $mon, $d, $h, $m);
}

function parseFastDateTime($dt) {
    if (!$dt) return ['h'=>'', 'm'=>'', 'd'=>'', 'mon'=>'', 'y'=>''];
    $ts = strtotime($dt);
    return [
        'h' => date('H', $ts), 'm' => date('i', $ts),
        'd' => date('d', $ts), 'mon' => date('m', $ts), 'y' => date('Y', $ts)
    ];
}

$arr = parseFastDateTime($log['arrival_time']);
$comp = parseFastDateTime($log['completion_time']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $arrival_time = getFastDateTime($_POST['arr_h'], $_POST['arr_m'], $_POST['arr_d'], $_POST['arr_mon'], $_POST['arr_y']);
        $completion_time = getFastDateTime($_POST['comp_h'], $_POST['comp_m'], $_POST['comp_d'], $_POST['comp_mon'], $_POST['comp_y']);

        $stmt = $pdo->prepare("UPDATE maintenance_logs SET 
            project_id=?, device_id=?, custom_device_name=?, usage_time_manual=?, ngay_su_co=?, noi_dung=?, hu_hong=?, xu_ly=?, chi_phi=?, 
            client_name=?, client_phone=?, arrival_time=?, completion_time=?, work_type=? WHERE id=?");
        $stmt->execute([
            $_POST['project_id'], !empty($_POST['device_id']) ? $_POST['device_id'] : null,
            !empty($_POST['custom_device_name']) ? $_POST['custom_device_name'] : null,
            !empty($_POST['usage_time_manual']) ? $_POST['usage_time_manual'] : null,
            $_POST['ngay_su_co'] ?: date('Y-m-d'), 
            $_POST['noi_dung'] ?: null, 
            $_POST['hu_hong'], $_POST['xu_ly'], $_POST['chi_phi'] ?: 0,
            $_POST['client_name'], $_POST['client_phone'], $arrival_time, $completion_time, 
            $_POST['work_type'] ?: 'Bảo trì / Sửa chữa', $id
        ]);
        set_message('success', 'Cập nhật thành công!');
        header("Location: index.php?page=maintenance/view&id=" . $id);
        exit;
    } catch (PDOException $e) { set_message('error', 'Lỗi: ' . $e->getMessage()); }
}
?>

<style>
    .fast-time-group { display: flex; align-items: center; gap: 2px; background: #fff; border: 1px solid #ddd; padding: 2px 5px; border-radius: 4px; width: fit-content; }
    .fast-time-group input { border: none; padding: 5px 2px; text-align: center; font-size: 14px; outline: none; }
    .fast-time-group input:focus { background: #e0f2fe; }
    .fast-time-group .sep { font-weight: bold; color: #999; }
    .input-h, .input-m, .input-d, .input-mon { width: 25px; }
    .input-y { width: 45px; }
    input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
</style>

<div class="page-header">
    <h2><i class="fas fa-edit"></i> Sửa Phiếu Công tác #<?php echo $id; ?></h2>
    <div class="header-actions">
        <a href="index.php?page=maintenance/view&id=<?php echo $id; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
        <button type="submit" form="edit-maintenance-form" class="btn btn-primary">Cập nhật</button>
    </div>
</div>

<form action="index.php?page=maintenance/edit&id=<?php echo $id; ?>" method="POST" id="edit-maintenance-form" class="edit-layout">
    <div class="left-panel">
        <div class="card">
            <div class="card-header-custom"><h3>Nội dung</h3></div>
            <div class="card-body-custom">
                <div class="form-group"><label>Loại công việc</label><input type="text" name="work_type" value="<?php echo htmlspecialchars($log['work_type']); ?>"></div>
                <div class="form-group"><label>Yêu cầu</label><textarea name="noi_dung" rows="4"><?php echo htmlspecialchars($log['noi_dung']); ?></textarea></div>
                <div class="form-group"><label>Hư hỏng</label><textarea name="hu_hong" rows="3"><?php echo htmlspecialchars($log['hu_hong']); ?></textarea></div>
                <div class="form-group"><label>Xử lý</label><textarea name="xu_ly" rows="3"><?php echo htmlspecialchars($log['xu_ly']); ?></textarea></div>
            </div>
        </div>

        <div class="card mt-20">
            <div class="card-header-custom"><h3>Thời gian thực hiện</h3></div>
            <div class="card-body-custom">
                <div class="form-row">
                    <div class="form-group half">
                        <label>Có mặt</label>
                        <div class="fast-time-group">
                            <input type="number" name="arr_h" class="input-h auto-tab" maxlength="2" value="<?php echo $arr['h']; ?>">
                            <span class="sep">:</span>
                            <input type="number" name="arr_m" class="input-m auto-tab" maxlength="2" value="<?php echo $arr['m']; ?>">
                            <span class="sep">&nbsp;</span>
                            <input type="number" name="arr_d" class="input-d auto-tab" maxlength="2" value="<?php echo $arr['d']; ?>">
                            <span class="sep">/</span>
                            <input type="number" name="arr_mon" class="input-mon auto-tab" maxlength="2" value="<?php echo $arr['mon']; ?>">
                            <span class="sep">/</span>
                            <input type="number" name="arr_y" class="input-y auto-tab" maxlength="4" value="<?php echo $arr['y']; ?>">
                        </div>
                    </div>
                    <div class="form-group half">
                        <label>Hoàn thành</label>
                        <div class="fast-time-group">
                            <input type="number" name="comp_h" class="input-h auto-tab" maxlength="2" value="<?php echo $comp['h']; ?>">
                            <span class="sep">:</span>
                            <input type="number" name="comp_m" class="input-m auto-tab" maxlength="2" value="<?php echo $comp['m']; ?>">
                            <span class="sep">&nbsp;</span>
                            <input type="number" name="comp_d" class="input-d auto-tab" maxlength="2" value="<?php echo $comp['d']; ?>">
                            <span class="sep">/</span>
                            <input type="number" name="comp_mon" class="input-mon auto-tab" maxlength="2" value="<?php echo $comp['mon']; ?>">
                            <span class="sep">/</span>
                            <input type="number" name="comp_y" class="input-y auto-tab" maxlength="4" value="<?php echo $comp['y']; ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="right-panel">
        <div class="card">
            <div class="card-header-custom"><h3>Đối tượng</h3></div>
            <div class="card-body-custom">
                <div class="form-group">
                    <label>Dự án</label>
                    <select name="project_id" required class="input-highlight" onchange="loadDevices(this.value)">
                        <?php foreach ($projects as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($current_project_id == $p['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['ten_du_an']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Loại đối tượng:</label>
                    <div style="display: flex; gap: 10px;">
                        <label><input type="radio" name="target_mode" value="device" <?php echo !empty($log['device_id']) ? 'checked' : ''; ?> onclick="toggleTargetMode('device')"> Thiết bị</label>
                        <label><input type="radio" name="target_mode" value="custom" <?php echo empty($log['device_id']) ? 'checked' : ''; ?> onclick="toggleTargetMode('custom')"> Nhập tay</label>
                    </div>
                </div>
                <div id="device-selection-area" style="<?php echo !empty($log['device_id']) ? 'display:block' : 'display:none'; ?>">
                    <div class="form-group"><label>Thiết bị</label><select id="device_id" name="device_id" class="input-highlight"></select></div>
                </div>
                <div id="custom-name-area" style="<?php echo empty($log['device_id']) ? 'display:block' : 'display:none'; ?>">
                    <div class="form-group"><label>Tên</label><input type="text" name="custom_device_name" value="<?php echo htmlspecialchars($log['custom_device_name']); ?>"></div>
                    <div class="form-group"><label>Thời gian sử dụng</label><input type="text" name="usage_time_manual" value="<?php echo htmlspecialchars($log['usage_time_manual'] ?? ''); ?>" placeholder="VD: 2 năm"></div>
                </div>
                <div class="form-group"><label>Ngày yêu cầu</label><input type="date" name="ngay_su_co" value="<?php echo $log['ngay_su_co']; ?>"></div>
            </div>
        </div>
        <div class="card mt-20">
            <div class="card-header-custom"><h3><i class="fas fa-user-tag"></i> Khách hàng</h3></div>
            <div class="card-body-custom">
                <div class="form-group"><label>Đại diện</label><input type="text" name="client_name" value="<?php echo htmlspecialchars($log['client_name'] ?? ''); ?>"></div>
                <div class="form-group"><label>SĐT</label><input type="text" name="client_phone" value="<?php echo htmlspecialchars($log['client_phone'] ?? ''); ?>"></div>
            </div>
        </div>
    </div>
</form>

<script>
document.querySelectorAll('.auto-tab').forEach(input => {
    input.addEventListener('input', function() {
        if (this.value.length >= parseInt(this.getAttribute('maxlength'))) {
            let next = this.nextElementSibling;
            while (next && next.tagName !== 'INPUT') { next = next.nextElementSibling; }
            if (next) next.focus();
        }
    });
});

function toggleTargetMode(mode) {
    document.getElementById('device-selection-area').style.display = (mode === 'device' ? 'block' : 'none');
    document.getElementById('custom-name-area').style.display = (mode === 'custom' ? 'block' : 'none');
}

function loadDevices(projectId, selectedId = "<?php echo $log['device_id']; ?>") {
    const ds = document.getElementById('device_id');
    if (!projectId) return;
    fetch(`api/get_devices_by_project.php?project_id=${projectId}`)
        .then(r => r.json()).then(data => {
            ds.innerHTML = '<option value="">-- Chọn thiết bị --</option>';
            data.forEach(d => { ds.innerHTML += `<option value="${d.id}" ${d.id == selectedId ? 'selected' : ''}>${d.ten_thiet_bi}</option>`; });
        });
}
document.addEventListener('DOMContentLoaded', () => loadDevices("<?php echo $current_project_id; ?>"));
</script>
