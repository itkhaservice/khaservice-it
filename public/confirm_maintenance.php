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
        if ($stmt_up->execute($params)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi lưu dữ liệu.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Xác nhận Phiếu công tác</title>
    <link rel="icon" type="image/png" href="../uploads/system/Logo1024x.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #108042; --primary-dark: #0d6635; --bg: #f4f7f6; --text: #1e293b; }
        body { font-family: -apple-system, system-ui, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--bg); margin: 0; padding: 15px; color: var(--text); line-height: 1.5; }
        .container { max-width: 500px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); }
        h2 { color: var(--primary); text-align: center; margin-top: 0; font-size: 1.4rem; font-weight: 800; }
        .info-card { background: #f0fdf4; border-radius: 15px; padding: 18px; margin-bottom: 20px; border: 1px solid #dcfce7; }
        .info-item { display: flex; margin-bottom: 8px; font-size: 0.9rem; }
        .info-label { font-weight: bold; color: #64748b; width: 100px; flex-shrink: 0; }
        .info-value { font-weight: 600; }
        .person-option { display: flex; align-items: center; padding: 12px; border: 2px solid #f1f5f9; border-radius: 12px; margin-bottom: 10px; cursor: pointer; transition: all 0.2s; }
        .person-option.active { border-color: var(--primary); background: #f0fdf4; }
        .person-option i.avatar { width: 35px; height: 35px; background: #f1f5f9; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 12px; color: #94a3b8; }
        .person-option.active i.avatar { background: var(--primary); color: #fff; }
        .person-name { font-weight: 700; font-size: 0.95rem; }
        .signed-badge { margin-left: auto; background: #dcfce7; color: var(--primary); font-size: 0.7rem; font-weight: 800; padding: 3px 8px; border-radius: 10px; }
        .form-control { width: 100%; padding: 12px; border: 2px solid #f1f5f9; border-radius: 10px; box-sizing: border-box; font-size: 1rem; margin-top: 5px; }
        .btn { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px; border: none; border-radius: 12px; font-size: 0.95rem; font-weight: 800; cursor: pointer; transition: all 0.2s; margin-top: 15px; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-outline { background: #fff; color: var(--primary); border: 2px solid var(--primary); }
        #sig-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #fff; z-index: 1000; display: none; flex-direction: column; }
        .sig-header { padding: 15px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; background: #fff; }
        .sig-body { flex: 1; position: relative; background: #f8fafc; overflow: hidden; display: flex; align-items: center; justify-content: center; padding: 20px; }
        #canvas-container { width: 100%; height: 100%; border: 2px dashed #cbd5e1; border-radius: 15px; position: relative; background: #fff; }
        canvas { width: 100%; height: 100%; cursor: crosshair; touch-action: none; border-radius: 15px; }
        .sig-hint { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #94a3b8; font-size: 0.9rem; font-weight: 600; pointer-events: none; text-transform: uppercase; opacity: 0.5; }
        #report-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #fff; z-index: 1500; display: none; flex-direction: column; overflow-y: auto; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; display: none; align-items: center; justify-content: center; padding: 20px; }
        .custom-modal { background: #fff; border-radius: 20px; padding: 25px; width: 100%; max-width: 400px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Xác nhận Phiếu công tác</h2>
        <div class="info-card">
            <div class="info-item"><span class="info-label">Dự án:</span> <span class="info-value"><?php echo htmlspecialchars($log['ten_du_an']); ?></span></div>
            <div class="info-item"><span class="info-label">Nội dung:</span> <span class="info-value"><?php echo htmlspecialchars($log['noi_dung']); ?></span></div>
            <div class="info-item"><span class="info-label">IT phụ trách:</span> <span class="info-value"><?php echo htmlspecialchars($log['it_name']); ?></span></div>
        </div>

        <button class="btn btn-outline" onclick="openReport()">
            <i class="fas fa-file-alt"></i> XEM NỘI DUNG PHIẾU
        </button>

        <!-- Chỉ hiển thị phần Họ tên khi cần -->
        <div id="name-input-container" style="margin-top: 25px; padding: 18px; background: #fff; border: 1px solid #e2e8f0; border-radius: 15px; display: none;">
            <label style="font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Họ và tên người ký:</label>
            <input type="text" id="signer_name" class="form-control" placeholder="Nhập họ tên của bạn">
        </div>

        <div class="person-selector" style="margin-top: 20px;">
            <div class="selector-title" style="font-size: 0.8rem; font-weight: 700; color: #475569; margin-bottom: 10px;">CHỌN VAI TRÒ KÝ</div>
            
            <div class="person-option <?php echo !empty($log['it_signature']) ? 'signed' : ''; ?>" onclick="selectRole(1, 'Nhân viên phụ trách (IT)', <?php echo !empty($log['it_signature']) ? 'true' : 'false'; ?>, '<?php echo addslashes($log['it_name']); ?>')">
                <i class="avatar"><i class="fas fa-user-shield"></i></i>
                <div>
                    <div class="person-name">Nhân viên phụ trách (IT)</div>
                    <div style="font-size: 0.75rem; color: #64748b;"><?php echo htmlspecialchars($log['it_name']); ?></div>
                </div>
                <?php if(!empty($log['it_signature'])): ?><span class="signed-badge">ĐÃ KÝ</span><?php endif; ?>
            </div>

            <div class="person-option <?php echo !empty($log['customer_signature']) ? 'signed' : ''; ?>" onclick="selectRole(2, 'Người liên hệ', <?php echo !empty($log['customer_signature']) ? 'true' : 'false'; ?>, '<?php echo addslashes($log['client_name'] ?? ''); ?>')">
                <i class="avatar"><i class="fas fa-user"></i></i>
                <div>
                    <div class="person-name">Người liên hệ (Dự án)</div>
                    <div style="font-size: 0.75rem; color: #64748b;">Khách hàng / BQL dự án</div>
                </div>
                <?php if(!empty($log['customer_signature'])): ?><span class="signed-badge">ĐÃ KÝ</span><?php endif; ?>
            </div>
        </div>

        <button id="btn-open-sig" class="btn btn-primary" disabled onclick="openSignature()">
            <i class="fas fa-pen-nib"></i> BẮT ĐẦU KÝ TÊN
        </button>
    </div>

    <!-- SIGNATURE PAD -->
    <div id="sig-overlay">
        <div class="sig-header">
            <button onclick="closeSignature()" style="background:none; border:none; color:#64748b; font-weight:bold; font-size: 0.9rem;">HỦY BỎ</button>
            <div id="signing-for" style="font-weight: 800; color: var(--primary); font-size: 1rem;"></div>
            <button onclick="showPreviewModal()" style="background: var(--primary); color:#fff; border:none; padding: 10px 20px; border-radius: 8px; font-weight:bold;">HOÀN TẤT</button>
        </div>
        <div class="sig-body">
            <div id="canvas-container">
                <div class="sig-hint">KÝ TÊN VÀO ĐÂY</div>
                <canvas id="signature-pad"></canvas>
            </div>
        </div>
        <div style="padding: 20px; text-align: center; background: #fff; border-top: 1px solid #eee;">
            <button onclick="signaturePad.clear()" style="background: #f1f5f9; border: none; color: #64748b; padding: 12px 30px; border-radius: 10px; font-weight: bold; font-size: 0.9rem;">
                <i class="fas fa-eraser"></i> XÓA KÝ LẠI
            </button>
        </div>
    </div>

    <!-- MODALS -->
    <div id="modal-confirm" class="modal-overlay">
        <div class="custom-modal">
            <h3 style="margin-top:0;">Xác nhận chữ ký</h3>
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:15px; margin:15px 0;">
                <img id="preview-sig-img" style="max-height:100px; max-width:100%;">
                <div id="preview-role-name" style="font-weight:bold; margin-top:10px; border-top:1px solid #eee; padding-top:10px;"></div>
            </div>
            <div style="display:flex; gap:10px;">
                <button class="btn btn-outline" style="margin-top:0; flex:1;" onclick="hideModal('modal-confirm')">KÝ LẠI</button>
                <button class="btn btn-primary" style="margin-top:0; flex:1;" onclick="saveSignature()">LƯU CHỮ KÝ</button>
            </div>
        </div>
    </div>

    <div id="modal-success" class="modal-overlay">
        <div class="custom-modal">
            <i class="fas fa-check-circle" style="font-size:3rem; color:var(--primary); margin-bottom:15px;"></i>
            <h3>Thành công!</h3>
            <p>Chữ ký của bạn đã được lưu.</p>
            <button class="btn btn-primary" onclick="window.location.reload()">ĐÓNG LẠI</button>
        </div>
    </div>

    <div id="report-overlay">
        <div style="padding:15px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
            <b>NỘI DUNG PHIẾU CÔNG TÁC</b>
            <button class="btn btn-primary" style="margin-top:0; width:auto; padding:8px 20px;" onclick="closeReport()">ĐÓNG</button>
        </div>
        <div id="report-preview-content" style="padding:20px;"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <script>
        let selectedRole = null;
        let signaturePad;
        let trimmedDataUrl = '';

        function selectRole(idx, name, isSigned, currentName) {
            if (isSigned) { openReport(); return; }
            selectedRole = idx;
            document.querySelectorAll('.person-option').forEach(opt => opt.classList.remove('active'));
            event.currentTarget.classList.add('active');
            
            document.getElementById('signer_name').value = currentName || '';
            document.getElementById('name-input-container').style.display = 'block';
            document.getElementById('btn-open-sig').disabled = false;
        }

        function openSignature() {
            if (!document.getElementById('signer_name').value.trim()) { alert("Vui lòng nhập họ tên."); return; }
            document.getElementById('sig-overlay').style.display = 'flex';
            document.getElementById('signing-for').textContent = "Ký tên: " + document.getElementById('signer_name').value;
            
            const canvas = document.getElementById('signature-pad');
            const container = document.getElementById('canvas-container');
            
            // Fix kích thước canvas theo container thực tế
            canvas.width = container.offsetWidth;
            canvas.height = container.offsetHeight;
            
            if (!signaturePad) {
                signaturePad = new SignaturePad(canvas, { penColor: '#000', minWidth: 2, maxWidth: 4 });
            } else {
                signaturePad.clear();
            }
        }

        window.addEventListener('resize', () => {
            if (document.getElementById('sig-overlay').style.display === 'flex') {
                const canvas = document.getElementById('signature-pad');
                const container = document.getElementById('canvas-container');
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                
                // Lưu lại nội dung hiện tại
                const data = signaturePad.toData();
                
                canvas.width = container.offsetWidth;
                canvas.height = container.offsetHeight;
                
                signaturePad.clear();
                signaturePad.fromData(data);
            }
        });

        function closeSignature() { document.getElementById('sig-overlay').style.display = 'none'; }
        function hideModal(id) { document.getElementById(id).style.display = 'none'; }

        function trimCanvas(canvas) {
            const ctx = canvas.getContext('2d');
            const copy = document.createElement('canvas').getContext('2d');
            const pixels = ctx.getImageData(0, 0, canvas.width, canvas.height);
            let bound = { top: null, left: null, bottom: null, right: null };
            for (let i = 0; i < pixels.data.length; i += 4) {
                if (pixels.data[i + 3] !== 0) {
                    const x = (i / 4) % canvas.width;
                    const y = Math.floor((i / 4) / canvas.width);
                    if (bound.top === null || y < bound.top) bound.top = y;
                    if (bound.left === null || x < bound.left) bound.left = x;
                    if (bound.bottom === null || y > bound.bottom) bound.bottom = y;
                    if (bound.right === null || x > bound.right) bound.right = x;
                }
            }
            if (bound.top === null) return null;
            const pad = 10;
            const tw = bound.right - bound.left + pad * 2;
            const th = bound.bottom - bound.top + pad * 2;
            copy.canvas.width = tw; copy.canvas.height = th;
            copy.putImageData(ctx.getImageData(bound.left - pad, bound.top - pad, tw, th), 0, 0);
            return copy.canvas.toDataURL();
        }

        function showPreviewModal() {
            if (signaturePad.isEmpty()) { alert("Vui lòng ký tên."); return; }
            trimmedDataUrl = trimCanvas(document.getElementById('signature-pad'));
            document.getElementById('preview-sig-img').src = trimmedDataUrl;
            document.getElementById('preview-role-name').textContent = document.getElementById('signer_name').value;
            document.getElementById('modal-confirm').style.display = 'flex';
        }

        function saveSignature() {
            hideModal('modal-confirm');
            const formData = new URLSearchParams();
            formData.append('signature_data', trimmedDataUrl);
            formData.append('role_idx', selectedRole);
            formData.append('signer_name', document.getElementById('signer_name').value);

            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('modal-success').style.display = 'flex';
                } else {
                    alert("Lỗi: " + data.message);
                }
            });
        }

        function openReport() {
            document.getElementById('report-overlay').style.display = 'flex';
            document.getElementById('report-preview-content').innerHTML = "<div style='padding:40px; text-align:center;'><i class='fas fa-spinner fa-spin fa-2x'></i><br><br>Đang tải nội dung phiếu...</div>";
            
            fetch('render_maintenance_preview.php?token=<?php echo $token; ?>')
                .then(r => {
                    if (!r.ok) throw new Error("Lỗi tải phiếu.");
                    return r.text();
                })
                .then(html => {
                    document.getElementById('report-preview-content').innerHTML = html;
                })
                .catch(err => {
                    document.getElementById('report-preview-content').innerHTML = "<div style='padding:20px; color:red; text-align:center;'>Không thể tải bản xem trước. Vui lòng thử lại.</div>";
                });
        }
        function closeReport() { document.getElementById('report-overlay').style.display = 'none'; }
    </script>
</body>
</html>
