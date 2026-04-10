<?php
// public/confirm_health_check.php
ob_start();
session_start();
require_once __DIR__ . '/../config/db.php';

$token = $_GET['token'] ?? null;
if (!$token) die("Thiếu mã xác thực.");

// Fetch health check info
$stmt = $pdo->prepare("SELECT h.*, p.ten_du_an, u.fullname as it_name 
                      FROM it_system_health_checks h 
                      JOIN projects p ON h.project_id = p.id 
                      JOIN users u ON h.checked_by = u.id 
                      WHERE h.signing_token = ?");
$stmt->execute([$token]);
$check = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$check) die("Mã xác thực không hợp lệ hoặc báo cáo đã bị xóa.");

// Handle Signature Save (AJAX POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signature_data'])) {
    header('Content-Type: application/json');
    try {
        $role_idx = $_POST['role_idx']; // 1: IT, 2: Project Rep
        $sig_data = $_POST['signature_data'];
        $signer_name = trim($_POST['signer_name'] ?? '');
        
        if ($role_idx == 1) {
            $sql = "UPDATE it_system_health_checks SET it_signature = ? WHERE id = ?";
            $params = [$sig_data, $check['id']];
        } else {
            $sql = "UPDATE it_system_health_checks SET client_signature = ?, client_name = ? WHERE id = ?";
            $params = [$sig_data, $signer_name, $check['id']];
        }
        
        $stmt_up = $pdo->prepare($sql);
        if ($stmt_up->execute($params)) echo json_encode(['success' => true]);
        else echo json_encode(['success' => false, 'message' => 'Lỗi lưu dữ liệu.']);
    } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Xác nhận Kiểm tra Hệ thống</title>
    <link rel="icon" type="image/png" href="../uploads/system/Logo1024x.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #108042; --text: #1e293b; --gray: #64748b; }
        body { font-family: -apple-system, sans-serif; background: #f4f7f6; margin: 0; color: var(--text); }
        .main-container { max-width: 500px; margin: 0 auto; padding: 20px; box-sizing: border-box; }
        .card { background: #fff; border-radius: 20px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-title { font-size: 1.2rem; font-weight: 800; color: var(--primary); text-align: center; margin-bottom: 15px; }
        .info-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        .info-label { color: var(--gray); }
        .info-value { font-weight: 700; text-align: right; }
        .modern-input { width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; font-size: 1rem; margin-bottom: 15px; box-sizing: border-box; }
        .role-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
        .role-card { background: #fff; border: 2px solid #f1f5f9; border-radius: 16px; padding: 15px; text-align: center; cursor: pointer; }
        .role-card.active { border-color: var(--primary); background: #ecfdf5; }
        .role-card.signed { opacity: 0.5; pointer-events: none; background: #f8fafc; }
        .btn-start { width: 100%; padding: 16px; background: var(--primary); color: #fff; border: none; border-radius: 16px; font-size: 1rem; font-weight: 800; cursor: pointer; }
        
        #sig-panel { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #fff; z-index: 10000; display: none; flex-direction: column; }
        .sig-canvas-area { flex: 1; position: relative; background: #f8fafc; display: flex; align-items: center; justify-content: center; padding: 10px; }
        canvas { background: #fff; border: 1px solid #e2e8f0; border-radius: 15px; width: 100%; height: 80%; touch-action: none; }
        .sig-bottom { padding: 20px; display: grid; grid-template-columns: 1fr 2fr; gap: 15px; border-top: 1px solid #eee; }
        .btn-clear { padding: 14px; background: #f1f5f9; border: none; border-radius: 12px; font-weight: 700; }
        .btn-done { padding: 14px; background: var(--primary); color: #fff; border: none; border-radius: 12px; font-weight: 800; }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="card">
            <div class="card-title">XÁC NHẬN KIỂM TRA IT</div>
            <div class="info-list">
                <div class="info-item"><span class="info-label">Dự án:</span><span class="info-value"><?= htmlspecialchars($check['ten_du_an']) ?></span></div>
                <div class="info-item"><span class="info-label">Ngày kiểm tra:</span><span class="info-value"><?= date('d/m/Y', strtotime($check['check_date'])) ?></span></div>
                <div class="info-item"><span class="info-label">IT thực hiện:</span><span class="info-value"><?= htmlspecialchars($check['it_name']) ?></span></div>
            </div>
        </div>

        <div class="card">
            <label class="info-label">CHỌN NGƯỜI KÝ:</label>
            <div class="role-grid">
                <div class="role-card <?= $check['it_signature'] ? 'signed' : 'active' ?>" onclick="selectRole(1, this)">
                    <i class="fas fa-user-shield"></i><div style="font-weight:700">IT Admin</div>
                    <?php if($check['it_signature']): ?><div style="font-size:10px; color:var(--primary)">ĐÃ KÝ</div><?php endif; ?>
                </div>
                <div class="role-card <?= $check['client_signature'] ? 'signed' : '' ?>" onclick="selectRole(2, this)">
                    <i class="fas fa-user-tie"></i><div style="font-weight:700">Cán bộ Dự án</div>
                    <?php if($check['client_signature']): ?><div style="font-size:10px; color:var(--primary)">ĐÃ KÝ</div><?php endif; ?>
                </div>
            </div>

            <div id="signer-name-wrapper" style="display:none">
                <label class="info-label">HỌ TÊN NGƯỜI KÝ:</label>
                <input type="text" id="signer_name" class="modern-input" placeholder="Nhập họ tên của bạn...">
            </div>

            <button class="btn-start" onclick="startSigning()"><i class="fas fa-pen-nib"></i> BẮT ĐẦU KÝ</button>
        </div>
    </div>

    <div id="sig-panel">
        <div style="padding:15px; font-weight:800; text-align:center; border-bottom:1px solid #eee">KÝ TÊN XÁC NHẬN</div>
        <div class="sig-canvas-area">
            <canvas id="sig-canvas"></canvas>
        </div>
        <div class="sig-bottom">
            <button class="btn-clear" onclick="clearSig()">XÓA</button>
            <button class="btn-done" onclick="saveSig()">HOÀN TẤT</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script>
        let currentRole = <?= $check['it_signature'] ? 2 : 1 ?>;
        const canvas = document.getElementById('sig-canvas');
        const signaturePad = new SignaturePad(canvas, { penColor: "rgb(0, 0, 128)" });

        function selectRole(role, el) {
            currentRole = role;
            document.querySelectorAll('.role-card').forEach(c => c.classList.remove('active'));
            el.classList.add('active');
            document.getElementById('signer-name-wrapper').style.display = (role === 2) ? 'block' : 'none';
        }

        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.clear();
        }

        function startSigning() {
            if (currentRole === 2 && !document.getElementById('signer_name').value.trim()) {
                alert("Vui lòng nhập họ tên cán bộ dự án."); return;
            }
            document.getElementById('sig-panel').style.display = 'flex';
            resizeCanvas();
        }

        function clearSig() { signaturePad.clear(); }

        function saveSig() {
            if (signaturePad.isEmpty()) { alert("Vui lòng ký tên."); return; }
            const data = signaturePad.toDataURL('image/png');
            const signerName = document.getElementById('signer_name').value;

            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `role_idx=${currentRole}&signature_data=${encodeURIComponent(data)}&signer_name=${encodeURIComponent(signerName)}`
            })
            .then(r => r.json())
            .then(res => {
                if (data.success) location.reload();
                else window.location.reload(); // Success or fail, reload to show status
            });
        }
    </script>
</body>
</html>