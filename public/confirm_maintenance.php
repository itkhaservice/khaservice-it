<?php
ob_start();
session_start();
require_once __DIR__ . '/../config/db.php';

$token = $_GET['token'] ?? null;
if (!$token) die("Thiếu mã xác thực.");

// Lấy thông tin phiếu công tác
$stmt = $pdo->prepare("SELECT ml.*, p.ten_du_an, u.fullname as it_name 
                      FROM maintenance_logs ml 
                      JOIN projects p ON ml.project_id = p.id 
                      JOIN users u ON ml.user_id = u.id 
                      WHERE ml.signing_token = ? AND ml.deleted_at IS NULL");
$stmt->execute([$token]);
$log = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$log) die("Mã xác thực không hợp lệ hoặc phiếu đã bị xóa.");

// Xử lý lưu chữ ký (AJAX POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signature_data'])) {
    header('Content-Type: application/json');
    try {
        $role_idx = $_POST['role_idx']; // 1: IT, 2: Customer
        $sig_data = $_POST['signature_data'];
        $signer_name = trim($_POST['signer_name'] ?? '');
        
        if ($role_idx == 1) {
            $sql = "UPDATE maintenance_logs SET it_signature = ?, it_signed_at = NOW() WHERE id = ?";
            $params = [$sig_data, $log['id']];
        } else {
            $sql = "UPDATE maintenance_logs SET customer_signature = ?, customer_signed_at = NOW(), client_name = ? WHERE id = ?";
            $params = [$sig_data, $signer_name, $log['id']];
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
    <title>Xác nhận Phiếu công tác</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #108042; --primary-light: #ecfdf5; --text: #1e293b; --gray: #64748b; }
        body { font-family: -apple-system, system-ui, sans-serif; background: #f4f7f6; margin: 0; padding: 0; color: var(--text); -webkit-tap-highlight-color: transparent; }
        
        .main-container { max-width: 500px; margin: 0 auto; padding: 20px; box-sizing: border-box; }
        .header-logo { text-align: center; margin-bottom: 20px; }
        .header-logo img { height: 40px; }
        
        .card { background: #fff; border-radius: 24px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-title { font-size: 1.3rem; font-weight: 800; color: var(--primary); text-align: center; margin: 0 0 15px 0; }
        
        .info-list { margin-bottom: 15px; }
        .info-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; }
        .info-label { color: var(--gray); font-weight: 500; }
        .info-value { font-weight: 700; text-align: right; }

        .btn-view-report { width: 100%; padding: 12px; border: 2px solid var(--primary); background: #fff; color: var(--primary); border-radius: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 5px; }

        .section-label { font-size: 0.8rem; font-weight: 800; color: var(--gray); text-transform: uppercase; margin: 20px 0 10px 0; display: block; }
        
        .modern-input { width: 100%; padding: 14px; border: 2px solid #f1f5f9; border-radius: 12px; font-size: 1rem; font-weight: 600; box-sizing: border-box; margin-bottom: 12px; transition: border-color 0.2s; }
        .modern-input:focus { border-color: var(--primary); outline: none; background: #fff; }

        /* Role Selector Cards */
        .role-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
        .role-card { background: #fff; border: 2px solid #f1f5f9; border-radius: 16px; padding: 15px; text-align: center; cursor: pointer; transition: all 0.2s; }
        .role-card.active { border-color: var(--primary); background: var(--primary-light); }
        .role-card i { font-size: 1.5rem; color: var(--gray); margin-bottom: 8px; }
        .role-card.active i { color: var(--primary); }
        .role-name { font-size: 0.85rem; font-weight: 700; display: block; }
        .role-card.signed { opacity: 0.6; pointer-events: none; background: #f8fafc; }
        .role-card.signed .signed-text { color: var(--primary); font-size: 0.65rem; font-weight: 800; display: block; margin-top: 5px; }

        .btn-start-sign { width: 100%; padding: 16px; background: var(--primary); color: #fff; border: none; border-radius: 16px; font-size: 1rem; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 8px 20px rgba(16, 128, 66, 0.2); }

        /* VÙNG KÝ TÊN TOÀN MÀN HÌNH */
        #sig-panel { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #fff; z-index: 10000; display: none; flex-direction: column; }
        .sig-top-bar { padding: 15px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; }
        .sig-content { flex: 1; position: relative; background: #f8fafc; display: flex; align-items: center; justify-content: center; }
        .sig-canvas-area { width: 95%; height: 85%; background: #fff; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); position: relative; border: 1px solid #e2e8f0; }
        canvas { width: 100%; height: 100%; touch-action: none; border-radius: 15px; cursor: crosshair; }
        
        .sig-guide-line { position: absolute; left: 10%; right: 10%; bottom: 25%; border-bottom: 2px dashed #e2e8f0; pointer-events: none; }
        .sig-guide-text { position: absolute; left: 0; right: 0; bottom: 10%; text-align: center; color: #cbd5e1; font-weight: 700; font-size: 0.8rem; pointer-events: none; }

        .sig-bottom-bar { padding: 20px; display: grid; grid-template-columns: 1fr 2fr; gap: 15px; background: #fff; border-top: 1px solid #eee; padding-bottom: calc(20px + var(--safe-area-inset-bottom, 0px)); }
        .btn-clear { padding: 14px; background: #f1f5f9; color: #64748b; border: none; border-radius: 12px; font-weight: 700; }
        .btn-done { padding: 14px; background: var(--primary); color: #fff; border: none; border-radius: 12px; font-weight: 800; font-size: 1rem; }

        /* Report Modal */
        #report-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #fff; z-index: 20000; display: none; flex-direction: column; }
        .report-header { padding: 15px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; background: #fff; position: sticky; top: 0; z-index: 10; }
        iframe { flex: 1; width: 100%; border: none; background: #525659; }

        /* Success/Confirm Modal */
        .modal-blur { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 12000; display: none; align-items: center; justify-content: center; padding: 20px; }
        .modal-box { background: #fff; border-radius: 24px; padding: 30px; width: 100%; max-width: 350px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.2); }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header-logo"><img src="../uploads/system/logo.png" onerror="this.style.display='none'"></div>
        
        <div class="card">
            <h1 class="card-title">Xác nhận Phiếu</h1>
            <div class="info-list">
                <div class="info-item"><span class="info-label">Dự án:</span> <span class="info-value"><?php echo htmlspecialchars($log['ten_du_an']); ?></span></div>
                <div class="info-item"><span class="info-label">IT phụ trách:</span> <span class="info-value"><?php echo htmlspecialchars($log['it_name']); ?></span></div>
            </div>
            <button class="btn-view-report" onclick="openReport()"><i class="fas fa-file-invoice"></i> Xem nội dung chi tiết</button>
        </div>

        <span class="section-label">1. Chọn người ký xác nhận</span>
        <div class="role-grid">
            <div class="role-card <?php echo !empty($log['it_signature']) ? 'signed' : ''; ?>" id="role-1" onclick="selectRole(1, '<?php echo addslashes($log['it_name']); ?>')">
                <i class="fas fa-user-shield"></i>
                <span class="role-name">Nhân viên IT</span>
                <?php if($log['it_signature']): ?><span class="signed-text">ĐÃ KÝ</span><?php endif; ?>
            </div>
            <div class="role-card <?php echo !empty($log['customer_signature']) ? 'signed' : ''; ?>" id="role-2" onclick="selectRole(2, '<?php echo addslashes($log['client_name'] ?? ''); ?>')">
                <i class="fas fa-user-tie"></i>
                <span class="role-name">Khách hàng</span>
                <?php if($log['customer_signature']): ?><span class="signed-text">ĐÃ KÝ</span><?php endif; ?>
            </div>
        </div>

        <div class="input-group" id="name-group" style="display: none;">
            <span class="section-label">2. Xác nhận họ tên</span>
            <input type="text" id="signer_name" class="modern-input" placeholder="Nhập đầy đủ họ tên của bạn">
        </div>

        <button class="btn-start-sign" onclick="startSigning()"><i class="fas fa-signature"></i> TIẾN HÀNH KÝ TÊN</button>
    </div>

    <!-- PANEL KÝ TÊN -->
    <div id="sig-panel">
        <div class="sig-top-bar">
            <button onclick="stopSigning()" style="background:none; border:none; color:var(--gray); font-weight:700;">ĐÓNG</button>
            <div style="font-weight:800; color:var(--primary);" id="signing-title">ĐANG KÝ TÊN</div>
            <div style="width: 50px;"></div>
        </div>
        <div class="sig-content">
            <div class="sig-canvas-area" id="canvas-wrapper">
                <div class="sig-guide-line"></div>
                <div class="sig-guide-text">VUI LÒNG KÝ VÀO ĐÂY</div>
                <canvas id="signature-pad"></canvas>
            </div>
        </div>
        <div class="sig-bottom-bar">
            <button class="btn-clear" onclick="signaturePad.clear()">XÓA KÝ LẠI</button>
            <button class="btn-done" onclick="confirmSignature()">HOÀN TẤT KÝ</button>
        </div>
    </div>

    <!-- REPORT MODAL -->
    <div id="report-modal">
        <div class="report-header">
            <div style="font-weight:800; color:var(--primary);">NỘI DUNG PHIẾU CÔNG TÁC</div>
            <button onclick="closeReport()" style="background:var(--primary); color:#fff; border:none; padding:8px 20px; border-radius:8px; font-weight:700;">ĐÓNG</button>
        </div>
        <iframe id="report-frame" src="about:blank"></iframe>
    </div>

    <!-- MODALS -->
    <div id="modal-confirm" class="modal-blur">
        <div class="modal-box">
            <h3 style="margin-top:0;">Xác nhận chữ ký?</h3>
            <div style="background:#f8fafc; border-radius:15px; padding:15px; margin:20px 0; border:1px solid #eee;">
                <img id="preview-img" style="max-width:100%; max-height:120px;">
                <div id="preview-name" style="font-weight:800; margin-top:10px; color:var(--primary); text-transform:uppercase;"></div>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <button class="btn-clear" style="margin:0; width: 100%;" onclick="hideModal('modal-confirm')">KÝ LẠI</button>
                <button class="btn-done" style="margin:0; width: 100%;" onclick="submitSignature()">ĐỒNG Ý</button>
            </div>
        </div>
    </div>

    <div id="modal-success" class="modal-blur">
        <div class="modal-box">
            <i class="fas fa-check-circle" style="font-size:4rem; color:var(--primary); margin-bottom:15px;"></i>
            <h2>Thành công!</h2>
            <p style="color:var(--gray); margin-bottom:25px;">Chữ ký của bạn đã được lưu vào phiếu công tác.</p>
            <button class="btn-done" style="width:100%;" onclick="window.location.reload()">XEM LẠI</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <script>
        let selectedIdx = null;
        let signaturePad;
        let finalSignatureData = null;

        function selectRole(idx, name) {
            selectedIdx = idx;
            document.querySelectorAll('.role-card').forEach(c => c.classList.remove('active'));
            document.getElementById('role-' + idx).classList.add('active');
            document.getElementById('name-group').style.display = 'block';
            
            const nameInput = document.getElementById('signer_name');
            nameInput.value = name;
            
            // Nếu là dấu chấm, bôi đen để xóa
            if (name.includes('................')) {
                nameInput.value = '';
                nameInput.focus();
            } else if (idx === 2) {
                nameInput.select();
            }
        }

        function startSigning() {
            if (!selectedIdx) return alert("Vui lòng chọn người ký xác nhận");
            if (!document.getElementById('signer_name').value.trim()) return alert("Vui lòng nhập họ tên người ký");
            document.getElementById('sig-panel').style.display = 'flex';
            document.getElementById('signing-title').textContent = "KÝ TÊN: " + document.getElementById('signer_name').value.toUpperCase();
            setTimeout(initCanvas, 100);
        }

        function initCanvas() {
            const canvas = document.getElementById('signature-pad');
            const wrapper = document.getElementById('canvas-wrapper');
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = wrapper.offsetWidth * ratio;
            canvas.height = wrapper.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            if (!signaturePad) {
                signaturePad = new SignaturePad(canvas, { penColor: '#000', minWidth: 1.5, maxWidth: 4.5 });
            } else { signaturePad.clear(); }
        }

        function confirmSignature() {
            if (signaturePad.isEmpty()) return alert("Vui lòng ký tên vào vùng trắng");
            finalSignatureData = trimCanvas(document.getElementById('signature-pad'));
            document.getElementById('preview-img').src = finalSignatureData;
            document.getElementById('preview-name').textContent = document.getElementById('signer_name').value;
            document.getElementById('modal-confirm').style.display = 'flex';
        }

        function submitSignature() {
            const formData = new URLSearchParams();
            formData.append('signature_data', finalSignatureData);
            formData.append('role_idx', selectedIdx);
            formData.append('signer_name', document.getElementById('signer_name').value);

            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    hideModal('modal-confirm');
                    document.getElementById('modal-success').style.display = 'flex';
                } else alert(data.message);
            });
        }

        function trimCanvas(canvas) {
            const ctx = canvas.getContext('2d');
            const w = canvas.width; const h = canvas.height;
            const pixels = ctx.getImageData(0, 0, w, h);
            let bound = { top: null, left: null, bottom: null, right: null };
            for (let i = 0; i < pixels.data.length; i += 4) {
                if (pixels.data[i + 3] !== 0) {
                    const x = (i / 4) % w; const y = Math.floor((i / 4) / w);
                    if (bound.top === null || y < bound.top) bound.top = y;
                    if (bound.left === null || x < bound.left) bound.left = x;
                    if (bound.bottom === null || y > bound.bottom) bound.bottom = y;
                    if (bound.right === null || x > bound.right) bound.right = x;
                }
            }
            if (bound.top === null) return canvas.toDataURL();
            const pad = 20;
            const tw = Math.min(w, bound.right - bound.left + pad * 2);
            const th = Math.min(h, bound.bottom - bound.top + pad * 2);
            const copy = document.createElement('canvas');
            copy.width = tw; copy.height = th;
            copy.getContext('2d').putImageData(ctx.getImageData(bound.left - pad, bound.top - pad, tw, th), 0, 0);
            return copy.toDataURL();
        }

        function openReport() {
            const modal = document.getElementById('report-modal');
            const frame = document.getElementById('report-frame');
            modal.style.display = 'flex';
            frame.src = 'render_maintenance_preview.php?token=<?php echo $token; ?>';
        }

        function stopSigning() { document.getElementById('sig-panel').style.display = 'none'; }
        function closeReport() { 
            document.getElementById('report-modal').style.display = 'none'; 
            document.getElementById('report-frame').src = 'about:blank';
        }
        function hideModal(id) { document.getElementById(id).style.display = 'none'; }
        window.addEventListener('resize', () => { if(document.getElementById('sig-panel').style.display === 'flex') initCanvas(); });
    </script>
</body>
</html>
