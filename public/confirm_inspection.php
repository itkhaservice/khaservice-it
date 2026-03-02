<?php
ob_start();
session_start();
require_once __DIR__ . '/../config/db.php';

$token = $_GET['token'] ?? null;
if (!$token) die("Thiếu mã xác thực.");

// Lấy thông tin biên bản
$stmt = $pdo->prepare("SELECT ci.*, p.ten_du_an, u.fullname as inspector_name 
                      FROM car_inspections ci 
                      JOIN projects p ON ci.project_id = p.id 
                      JOIN users u ON ci.inspector_id = u.id 
                      WHERE ci.signing_token = ?");
$stmt->execute([$token]);
$ins = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ins) die("Mã xác thực không hợp lệ hoặc biên bản đã bị xóa.");

// Biến hỗ trợ hiển thị biên bản
$day = date('d', strtotime($ins['inspection_date']));
$month = date('m', strtotime($ins['inspection_date']));
$year = date('Y', strtotime($ins['inspection_date']));
$project_display_name = "Chung cư " . $ins['ten_du_an'];

// Xử lý lưu chữ ký
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signature_data'])) {
    $person_idx = $_POST['person_idx']; // 1 hoặc 2
    $sig_data = $_POST['signature_data'];
    $signer_name = trim($_POST['signer_name'] ?? '');
    
    if ($person_idx == 1) {
        $sql = "UPDATE car_inspections SET bql_signature_1 = ?, signed_at_1 = NOW()";
        if (!empty($signer_name)) $sql .= ", bql_name_1 = ?";
        $sql .= " WHERE id = ?";
        $stmt_up = $pdo->prepare($sql);
        $params = (!empty($signer_name)) ? [$sig_data, $signer_name, $ins['id']] : [$sig_data, $ins['id']];
    } else {
        $sql = "UPDATE car_inspections SET bql_signature_2 = ?, signed_at_2 = NOW()";
        if (!empty($signer_name)) $sql .= ", bql_name_2 = ?";
        $sql .= " WHERE id = ?";
        $stmt_up = $pdo->prepare($sql);
        $params = (!empty($signer_name)) ? [$sig_data, $signer_name, $ins['id']] : [$sig_data, $ins['id']];
    }
    
    if ($stmt_up->execute($params)) {
        echo json_encode(['success' => true]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Lỗi lưu dữ liệu']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Xác nhận Biên bản kiểm tra xe</title>
    <link rel="icon" type="image/png" href="../uploads/system/Logo1024x.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #108042; --primary-dark: #0d6635; --bg: #f4f7f6; --text: #1e293b; }
        body { font-family: -apple-system, system-ui, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--bg); margin: 0; padding: 20px; color: var(--text); line-height: 1.5; overflow-x: hidden; }
        
        .container { max-width: 500px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); }
        h2 { color: var(--primary); text-align: center; margin-top: 0; font-size: 1.5rem; font-weight: 800; }
        
        .info-card { background: #f0fdf4; border-radius: 15px; padding: 18px; margin-bottom: 20px; border: 1px solid #dcfce7; }
        .info-item { display: flex; margin-bottom: 8px; font-size: 0.95rem; }
        .info-label { font-weight: bold; color: #64748b; width: 90px; flex-shrink: 0; }
        .info-value { font-weight: 600; }
        
        .person-selector { margin-bottom: 20px; }
        .selector-title { font-weight: 700; margin-bottom: 12px; color: #475569; font-size: 0.85rem; text-transform: uppercase; }
        .person-option { 
            display: flex; align-items: center; padding: 12px; border: 2px solid #f1f5f9; border-radius: 12px; margin-bottom: 10px; cursor: pointer; transition: all 0.2s;
        }
        .person-option.active { border-color: var(--primary); background: #f0fdf4; }
        .person-option i.avatar { width: 35px; height: 35px; background: #f1f5f9; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 12px; color: #94a3b8; }
        .person-option.active i.avatar { background: var(--primary); color: #fff; }
        .person-name { font-weight: 700; font-size: 0.95rem; }
        .person-pos { font-size: 0.75rem; color: #64748b; }
        .signed-badge { margin-left: auto; background: #dcfce7; color: var(--primary); font-size: 0.7rem; font-weight: 800; padding: 3px 8px; border-radius: 10px; }

        .form-group { margin-top: 15px; display: none; }
        .form-control { width: 100%; padding: 12px; border: 2px solid #f1f5f9; border-radius: 10px; box-sizing: border-box; font-size: 1rem; font-weight: 600; }
        .form-control:focus { border-color: var(--primary); outline: none; background: #fff; }

        .btn { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px; border: none; border-radius: 12px; font-size: 0.95rem; font-weight: 800; cursor: pointer; transition: all 0.2s; margin-top: 15px; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-outline { background: #fff; color: var(--primary); border: 2px solid var(--primary); }
        .btn:disabled { background: #e2e8f0; color: #94a3b8; cursor: not-allowed; border-color: #e2e8f0; }

        /* Signature Area */
        #sig-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #fff; z-index: 1000; display: none; flex-direction: column; }
        .sig-header { padding: 15px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; background: #fff; }
        .sig-body { flex: 1; position: relative; background: #fafafa; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        canvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; cursor: crosshair; touch-action: none; }
        .sig-guide-box { position: absolute; width: 80%; height: 60%; border: 2px dashed #cbd5e1; border-radius: 15px; pointer-events: none; display: flex; align-items: center; justify-content: center; z-index: 1; }
        .sig-guide-text { color: #94a3b8; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; opacity: 0.5; }
        .landscape-hint { position: absolute; top: 20px; left: 50%; transform: translateX(-50%); text-align: center; color: #ef4444; z-index: 10; font-weight: bold; background: rgba(255,255,255,0.8); padding: 5px 15px; border-radius: 20px; display: none; }

        /* Preview Area */
        #report-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #fff; z-index: 1500; display: none; flex-direction: column; overflow-y: auto; }
        .report-preview-container { padding: 40px 20px; max-width: 800px; margin: 0 auto; background: #fff; font-family: "Times New Roman", Times, serif; color: #000; font-size: 12pt; line-height: 1.4; }
        .report-header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .report-title { text-align: center; font-size: 14pt; font-weight: bold; margin: 20px 0; text-transform: uppercase; }
        .report-section-title { font-weight: bold; margin-top: 15px; text-transform: uppercase; }
        .report-signature-section { display: flex; justify-content: space-between; margin-top: 30px; text-align: center; }
        .report-sig-box { width: 45%; font-weight: bold; }
        .report-sig-img { max-height: 80px; max-width: 100%; margin: 5px 0; }

        /* Modals */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(5px); z-index: 2000; display: none; align-items: center; justify-content: center; padding: 20px; }
        .custom-modal { background: #fff; border-radius: 24px; padding: 30px; width: 100%; max-width: 400px; text-align: center; animation: modalIn 0.3s ease-out; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        @keyframes modalIn { from { opacity: 0; transform: scale(0.9) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        .modal-icon { font-size: 3rem; margin-bottom: 20px; }
        .modal-btn { width: 100%; padding: 14px; border-radius: 12px; border: none; font-weight: 800; cursor: pointer; margin-top: 12px; transition: all 0.2s; }
        
        /* Preview Signature in Modal */
        .preview-sig-container { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin: 20px 0; display: flex; flex-direction: column; align-items: center; }
        .preview-sig-img { max-height: 100px; max-width: 100%; margin-bottom: 10px; }
        .preview-signer-name { font-weight: bold; font-size: 1.1rem; color: #1e293b; border-top: 1px solid #e2e8f0; padding-top: 10px; width: 80%; }
    </style>
</head>
<body>
    <div class="container" id="main-view">
        <div style="text-align: center; margin-bottom: 15px;">
            <img src="../uploads/system/logo.png" style="height: 35px;" onerror="this.style.display='none'">
        </div>
        <h2>Xác nhận Biên bản</h2>
        
        <div class="info-card">
            <div class="info-item"><span class="info-label">Dự án:</span> <span class="info-value"><?= htmlspecialchars($ins['ten_du_an']) ?></span></div>
            <div class="info-item"><span class="info-label">Ngày kiểm:</span> <span class="info-value"><?= $day ?>/<?= $month ?>/<?= $year ?></span></div>
            <div class="info-item"><span class="info-label">Phụ trách:</span> <span class="info-value"><?= htmlspecialchars($ins['inspector_name']) ?></span></div>
        </div>

        <button class="btn btn-outline" onclick="openReport()">
            <i class="fas fa-file-alt"></i> XEM NỘI DUNG BIÊN BẢN
        </button>

        <div class="person-selector" style="margin-top: 20px;">
            <div class="selector-title">Chọn vai trò ký xác nhận</div>
            <div class="person-option <?= $ins['bql_signature_1'] ? 'signed' : '' ?>" onclick="selectPerson(1, '<?= addslashes($ins['bql_name_1'] ?? 'Đại diện BQL 1') ?>', <?= $ins['bql_signature_1'] ? 'true' : 'false' ?>)">
                <i class="avatar"><i class="fas fa-user-tie"></i></i>
                <div>
                    <div class="person-name"><?= htmlspecialchars($ins['bql_name_1'] ?: 'Đại diện BQL 1') ?></div>
                    <div class="person-pos"><?= htmlspecialchars($ins['bql_pos_1'] ?: 'Ban Quản lý') ?></div>
                </div>
                <?php if($ins['bql_signature_1']): ?><span class="signed-badge">ĐÃ KÝ</span><?php endif; ?>
            </div>

            <div class="person-option <?= $ins['bql_signature_2'] ? 'signed' : '' ?>" onclick="selectPerson(2, '<?= addslashes($ins['bql_name_2'] ?? 'Đại diện BQL 2') ?>', <?= $ins['bql_signature_2'] ? 'true' : 'false' ?>)">
                <i class="avatar"><i class="fas fa-user-tie"></i></i>
                <div>
                    <div class="person-name"><?= htmlspecialchars($ins['bql_name_2'] ?: 'Đại diện BQL 2') ?></div>
                    <div class="person-pos"><?= htmlspecialchars($ins['bql_pos_2'] ?: 'Ban Quản lý') ?></div>
                </div>
                <?php if($ins['bql_signature_2']): ?><span class="signed-badge">ĐÃ KÝ</span><?php endif; ?>
            </div>
        </div>

        <div id="name-input-group" class="form-group">
            <label style="font-size: 0.8rem; font-weight: 700; color: #64748b;">HỌ VÀ TÊN NGƯỜI KÝ:</label>
            <input type="text" id="signer_name" class="form-control" placeholder="Nhập họ tên của bạn">
        </div>

        <button id="btn-open-sig" class="btn btn-primary" disabled onclick="openSignature()">
            <i class="fas fa-pen-nib"></i> BẮT ĐẦU KÝ TÊN
        </button>
    </div>

    <!-- REPORT PREVIEW -->
    <div id="report-overlay">
        <div style="position: sticky; top: 0; background: #fff; padding: 10px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; z-index: 100;">
            <span style="font-weight: 800; color: var(--primary);">NỘI DUNG BIÊN BẢN</span>
            <button onclick="closeReport()" style="background: var(--primary); color:#fff; border:none; padding: 8px 20px; border-radius: 8px; font-weight:bold;">ĐÓNG LẠI</button>
        </div>
        <div class="report-preview-container">
            <table class="report-header-table">
                <tr>
                    <td style="width: 50%; text-align: center; font-weight: bold; font-size: 10pt;">
                        CÔNG TY CỔ PHẦN QUẢN LÝ VÀ<br>VẬN HÀNH CAO ỐC KHÁNH HỘI
                        <div style="border-bottom: 1px solid #000; width: 100px; margin: 5px auto;"></div>
                    </td>
                    <td style="width: 50%; text-align: center; font-weight: bold; font-size: 11pt;">
                        CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM<br>
                        Độc lập – Tự do – Hạnh phúc
                        <div style="border-bottom: 1px solid #000; width: 120px; margin: 5px auto;"></div>
                    </td>
                </tr>
            </table>
            <div class="report-title">BIÊN BẢN KIỂM TRA XE</div>
            <p>Hôm nay, ngày <?= $day ?> tháng <?= $month ?> năm <?= $year ?>, tiến hành kiểm tra xe tại <?= htmlspecialchars($project_display_name) ?> cụ thể như sau:</p>
            
            <div class="report-section-title">I. THÀNH PHẦN:</div>
            <p><b>1. BÊN KIỂM TRA:</b> Ông/Bà <b><?= htmlspecialchars($ins['inspector_name']) ?></b> - Chức vụ: <?= htmlspecialchars($ins['inspector_position'] ?: '...') ?></p>
            <p><b>2. BAN QUẢN LÝ:</b> <?= htmlspecialchars($project_display_name) ?></p>
            <p>- Địa chỉ: <?= htmlspecialchars($ins['project_address'] ?: '...') ?></p>
            <p>- Đại diện 1: <?= htmlspecialchars($ins['bql_name_1'] ?: '...') ?> - Chức vụ: <?= htmlspecialchars($ins['bql_pos_1'] ?: '...') ?></p>
            <p>- Đại diện 2: <?= htmlspecialchars($ins['bql_name_2'] ?: '...') ?> - Chức vụ: <?= htmlspecialchars($ins['bql_pos_2'] ?: '...') ?></p>

            <div class="report-section-title">II. NỘI DUNG KIỂM TRA:</div>
            <div style="text-align: justify;"><?= nl2br(htmlspecialchars($ins['results_summary'] ?: 'Tiến hành kiểm tra tình trạng thực tế tại bãi xe.')) ?></div>
            
            <?php if(!empty($ins['violation_details'])): ?>
                <div style="margin-left: 30px; font-weight: bold; margin-top: 10px;">
                    <?php foreach(preg_split('/[,\n\r]+/', $ins['violation_details']) as $plate) if(trim($plate)) echo "<div>- " . htmlspecialchars(trim($plate)) . "</div>"; ?>
                </div>
            <?php endif; ?>

            <div class="report-section-title">III. Ý KIẾN KHÁC:</div>
            <div style="text-align: justify;"><?= nl2br(htmlspecialchars($ins['other_opinions'] ?: 'Biên bản kết thúc và đọc lại cho các bên cùng nghe, đồng ý ký tên.')) ?></div>

            <div class="report-signature-section">
                <div class="report-sig-box">
                    <p>ĐẠI DIỆN BAN QUẢN LÝ</p>
                    <?php 
                        // Xác định người ký đầu tiên
                        $first_signer = null;
                        if ($ins['bql_signature_1'] && $ins['bql_signature_2']) {
                            $first_signer = (strtotime($ins['signed_at_1']) <= strtotime($ins['signed_at_2'])) ? 1 : 2;
                        } elseif ($ins['bql_signature_1']) {
                            $first_signer = 1;
                        } elseif ($ins['bql_signature_2']) {
                            $first_signer = 2;
                        }
                    ?>
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100px;">
                        <?php if($first_signer == 1): ?>
                            <img src="<?= $ins['bql_signature_1'] ?>" class="report-sig-img">
                            <div style="font-weight: normal; font-size: 10pt; margin-top: 5px;"><?= htmlspecialchars($ins['bql_name_1']) ?></div>
                        <?php elseif($first_signer == 2): ?>
                            <img src="<?= $ins['bql_signature_2'] ?>" class="report-sig-img">
                            <div style="font-weight: normal; font-size: 10pt; margin-top: 5px;"><?= htmlspecialchars($ins['bql_name_2']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="report-sig-box">
                    <p>NGƯỜI KIỂM TRA</p>
                    <div style="min-height: 100px; display: flex; align-items: flex-end; justify-content: center;">
                        <p style="font-weight: normal; margin: 0;"><?= htmlspecialchars($ins['inspector_name']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SIGNATURE PAD OVERLAY -->
    <div id="sig-overlay">
        <div class="sig-header">
            <button onclick="closeSignature()" style="background:none; border:none; color:#666; font-weight:bold;">HỦY BỎ</button>
            <div id="signing-for" style="font-weight: bold; color: var(--primary);"></div>
            <button onclick="showPreviewModal()" style="background: var(--primary); color:#fff; border:none; padding: 10px 20px; border-radius: 8px; font-weight:bold;">HOÀN TẤT</button>
        </div>
        <div class="sig-body" id="canvas-container">
            <div class="landscape-hint" id="l-hint">Vui lòng xoay ngang điện thoại để ký</div>
            <div class="sig-guide-box">
                <div class="sig-guide-text">KÝ TÊN VÀO ĐÂY</div>
            </div>
            <canvas id="signature-pad"></canvas>
        </div>
        <div style="padding: 15px; display:flex; justify-content: center; background: #f1f5f9; gap: 20px;">
            <button onclick="signaturePad.clear()" style="background:none; border:none; color:#64748b; font-weight:bold;"><i class="fas fa-eraser"></i> XÓA KÝ LẠI</button>
        </div>
    </div>

    <!-- PREVIEW & KEEP MODAL -->
    <div id="modal-confirm" class="modal-overlay">
        <div class="custom-modal">
            <div class="modal-title" style="margin-bottom: 5px;">Xem lại chữ ký</div>
            <p style="color: #64748b; font-size: 0.85rem;">Kiểm tra kích thước và độ cân đối</p>
            
            <div class="preview-sig-container">
                <img id="preview-sig-img" class="preview-sig-img">
                <div id="preview-signer-name" class="preview-signer-name"></div>
            </div>

            <div style="display: flex; gap: 10px;">
                <button class="modal-btn" style="background: #f1f5f9; color:#64748b;" onclick="hideModal('modal-confirm')">KÝ LẠI</button>
                <button class="modal-btn" style="background: var(--primary); color:#fff;" onclick="saveSignature()">GIỮ LẠI CHỮ KÝ</button>
            </div>
        </div>
    </div>

    <div id="modal-success" class="modal-overlay">
        <div class="custom-modal">
            <div class="modal-icon" style="color: var(--primary);"><i class="fas fa-check-circle"></i></div>
            <div class="modal-title">Thành công!</div>
            <p class="modal-text">Cảm ơn bạn đã ký xác nhận biên bản.</p>
            <button class="modal-btn" style="background: var(--primary); color:#fff;" onclick="window.location.reload()">XEM LẠI BIÊN BẢN</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <script>
        let selectedIdx = null;
        let signaturePad;
        let trimmedDataUrl = '';

        function openReport() { document.getElementById('report-overlay').style.display = 'flex'; }
        function closeReport() { document.getElementById('report-overlay').style.display = 'none'; }
        function hideModal(id) { document.getElementById(id).style.display = 'none'; }

        function selectPerson(idx, name, isSigned) {
            if (isSigned) { openReport(); return; }
            selectedIdx = idx;
            document.querySelectorAll('.person-option').forEach(opt => opt.classList.remove('active'));
            event.currentTarget.classList.add('active');
            document.getElementById('name-input-group').style.display = 'block';
            document.getElementById('signer_name').value = name.includes('Đại diện BQL') ? '' : name;
            document.getElementById('btn-open-sig').disabled = false;
        }

        function openSignature() {
            const name = document.getElementById('signer_name').value.trim();
            if (!name) { alert("Vui lòng nhập họ tên."); return; }

            document.getElementById('sig-overlay').style.display = 'flex';
            document.getElementById('signing-for').textContent = name;
            
            const canvas = document.getElementById('signature-pad');
            const container = document.getElementById('canvas-container');
            
            // Fix: Set canvas dimensions correctly
            canvas.width = container.clientWidth;
            canvas.height = container.clientHeight;

            if (!signaturePad) {
                signaturePad = new SignaturePad(canvas, { penColor: '#000', minWidth: 2, maxWidth: 4 });
            } else {
                signaturePad.clear();
            }
            checkOrientation();
        }

        function closeSignature() { document.getElementById('sig-overlay').style.display = 'none'; }

        // Trim helper
        function trimCanvas(canvas) {
            const ctx = canvas.getContext('2d');
            const copy = document.createElement('canvas').getContext('2d');
            const pixels = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const l = pixels.data.length;
            let bound = { top: null, left: null, bottom: null, right: null };
            let x, y;

            for (let i = 0; i < l; i += 4) {
                if (pixels.data[i + 3] !== 0) {
                    x = (i / 4) % canvas.width;
                    y = ~~((i / 4) / canvas.width);
                    if (bound.top === null || y < bound.top) bound.top = y;
                    if (bound.left === null || x < bound.left) bound.left = x;
                    if (bound.bottom === null || y > bound.bottom) bound.bottom = y;
                    if (bound.right === null || x > bound.right) bound.right = x;
                }
            }

            if (bound.top === null) return null;

            const pad = 10;
            const trimHeight = bound.bottom - bound.top + pad * 2;
            const trimWidth = bound.right - bound.left + pad * 2;
            const trimmed = ctx.getImageData(bound.left - pad, bound.top - pad, trimWidth, trimHeight);

            copy.canvas.width = trimWidth;
            copy.canvas.height = trimHeight;
            copy.putImageData(trimmed, 0, 0);
            return copy.canvas.toDataURL();
        }

        function showPreviewModal() {
            if (signaturePad.isEmpty()) { alert("Vui lòng ký tên."); return; }
            
            const name = document.getElementById('signer_name').value.trim();
            trimmedDataUrl = trimCanvas(document.getElementById('signature-pad'));
            
            document.getElementById('preview-sig-img').src = trimmedDataUrl;
            document.getElementById('preview-signer-name').textContent = name;
            document.getElementById('modal-confirm').style.display = 'flex';
        }

        function saveSignature() {
            hideModal('modal-confirm');
            const name = document.getElementById('signer_name').value.trim();
            
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `signature_data=${encodeURIComponent(trimmedDataUrl)}&person_idx=${selectedIdx}&signer_name=${encodeURIComponent(name)}`
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

        function checkOrientation() {
            document.getElementById('l-hint').style.display = (window.innerHeight > window.innerWidth) ? 'block' : 'none';
        }

        window.addEventListener('resize', () => {
            if (document.getElementById('sig-overlay').style.display === 'flex') {
                const canvas = document.getElementById('signature-pad');
                const container = document.getElementById('canvas-container');
                const tempUrl = signaturePad.toDataURL();
                canvas.width = container.clientWidth;
                canvas.height = container.clientHeight;
                signaturePad.fromDataURL(tempUrl);
                checkOrientation();
            }
        });
    </script>
</body>
</html>
