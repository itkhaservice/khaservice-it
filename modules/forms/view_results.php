<?php
// modules/forms/view_results.php
$form_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ? AND user_id = ?");
$stmt->execute([$form_id, $user_id]);
$form = $stmt->fetch();
if (!$form) die("Dữ liệu không tồn tại.");

$questions = $pdo->query("SELECT id, question_text FROM form_questions WHERE form_id = $form_id ORDER BY question_order ASC")->fetchAll(PDO::FETCH_KEY_PAIR);
$results = $pdo->query("SELECT s.id, s.submitted_at, a.question_id, a.answer_text FROM form_submissions s JOIN submission_answers a ON s.id = a.submission_id WHERE s.form_id = $form_id ORDER BY s.submitted_at DESC")->fetchAll();

$submissions = [];
foreach ($results as $row) {
    $sid = $row['id'];
    if (!isset($submissions[$sid])) $submissions[$sid] = ['time' => $row['submitted_at'], 'answers' => []];
    $submissions[$sid]['answers'][$row['question_id']] = $row['answer_text'];
}
?>
<link rel="stylesheet" href="<?php echo $final_base; ?>assets/css/form_builder.css?v=<?php echo time(); ?>">

<div class="form-module-container">
    <div class="form-page-header">
        <h2><i class="fas fa-chart-pie"></i> Phân tích: <?php echo htmlspecialchars($form['title']); ?></h2>
        <div class="header-actions">
            <a href="user_forms_dashboard.php?page=forms/list" class="btn-f btn-f-secondary">Quay lại</a>
            <button onclick="toggleShare()" class="btn-f btn-f-secondary"><i class="fas fa-share-alt"></i> Chia sẻ</button>
            <a href="user_forms_dashboard.php?page=forms/export&id=<?php echo $form_id; ?>" class="btn-f btn-f-primary"><i class="fas fa-file-excel"></i> Xuất Excel</a>
        </div>
    </div>

        <div id="toast-container" aria-live="polite" aria-atomic="true"></div>

    <div class="kpi-row">
        <div class="kpi-box">
            <div class="kpi-label">Lượt phản hồi</div>
            <div class="kpi-value"><?php echo count($submissions); ?></div>
        </div>
        <div class="kpi-box">
            <div class="kpi-label">Trạng thái</div>
            <div class="kpi-value" style="font-size: 1rem; color: var(--f-text);"><?php echo $form['status']=='published'?'ĐANG MỞ':'ĐANG ĐÓNG'; ?></div>
        </div>
        <div class="kpi-box">
            <div class="kpi-label">Ngày khởi tạo</div>
            <div class="kpi-value" style="font-size: 1rem; color: var(--f-text);"><?php echo date('d/m/Y', strtotime($form['created_at'])); ?></div>
        </div>
    </div>

    <div id="share-panel" class="form-card" style="display: none; background: var(--f-primary-light); border-color: #bbf7d0;">
        <div style="display: flex; gap: 25px; align-items: stretch;">
            <div id="qrcode-container" style="background: #fff; padding: 0; border-radius: 8px; border: 1px solid var(--f-border); display: flex; justify-content: center; align-items: center; width: 160px; height: 160px; flex-shrink: 0; align-self: center; overflow: hidden;">
                <canvas id="qr-code" style="width: 100%; height: 100%; display: block;"></canvas>
            </div>
            <div style="flex: 1; display: flex; flex-direction: column; justify-content: center;">
                <label style="font-size: 0.75rem; font-weight: 800; color: #166534; display: block; margin-bottom: 8px;">ĐƯỜNG DẪN CÔNG KHAI:</label>
                <input type="text" id="public-link" readonly value="<?php echo $final_base . 'public/form.php?slug=' . $form['slug']; ?>" style="width: 100%; padding: 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-family: 'Consolas', monospace; font-size: 0.85rem; background: #fff; margin-bottom: 12px;">
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button onclick="copyLink()" class="btn-f btn-f-primary" style="height: 36px; font-size: 0.75rem;"><i class="fas fa-copy"></i> Sao chép Link</button>
                    <button onclick="copyQRToClipboard()" class="btn-f btn-f-secondary" style="height: 36px; font-size: 0.75rem;"><i class="fas fa-image"></i> Sao chép mã QR</button>
                    <button onclick="downloadQR()" class="btn-f btn-f-secondary" style="height: 36px; font-size: 0.75rem;"><i class="fas fa-download"></i> Tải mã QR</button>
                </div>
            </div>
        </div>
    </div>

    <div class="form-card" style="padding: 0 !important; overflow: hidden;">
        <div style="padding: 12px 20px; background: #fff; border-bottom: 1px solid var(--f-border); font-weight: 800; font-size: 0.9rem; color: var(--f-text);">Danh sách phản hồi chi tiết</div>
        <div style="overflow-x: auto;">
            <table class="form-table">
                <thead>
                    <tr>
                        <th width="160">Thời gian nộp</th>
                        <?php foreach ($questions as $q_text): ?>
                            <th><?php echo htmlspecialchars($q_text); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($submissions)): ?>
                        <tr><td colspan="<?php echo count($questions)+1; ?>" class="text-center" style="padding: 40px; color: var(--f-text-light);">Chưa có phản hồi nào được ghi nhận.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($submissions as $sub): ?>
                        <tr>
                            <td style="color: var(--f-text-light); font-weight: 600; font-size: 0.8rem;"><?php echo date('d/m/Y H:i', strtotime($sub['time'])); ?></td>
                            <?php foreach ($questions as $qid => $q_text): 
                                $ans = $sub['answers'][$qid] ?? '';
                                ?>
                                <td>
                                    <?php 
                                    if (strpos($ans, 'uploads/forms/') === 0) {
                                        echo "<a href='api/download_form_file.php?file=" . urlencode($ans) . "' target='_blank' style='color: var(--f-primary); font-weight: 700; text-decoration: none;'><i class='fas fa-paperclip'></i> Xem tệp</a>";
                                    } else {
                                        echo htmlspecialchars($ans);
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
<style>
  #toast-container{position:fixed;right:20px;bottom:20px;z-index:99999;display:flex;flex-direction:column;gap:10px;align-items:flex-end;pointer-events:none}
  .toast-item{min-width:220px;max-width:360px;color:#fff;padding:10px 14px;border-radius:10px;box-shadow:0 8px 24px rgba(2,6,23,0.35);transform:translateY(10px) scale(.98);opacity:0;transition:transform .28s cubic-bezier(.2,.8,.2,1),opacity .28s;pointer-events:auto;font-weight:700}
  .toast-item.show{transform:translateY(0) scale(1);opacity:1}
  .toast-success{background:linear-gradient(90deg,#059669,#10b981)}
  .toast-error{background:linear-gradient(90deg,#b91c1c,#ef4444)}
  .toast-body{font-size:0.95rem}
</style>
<script>
    function toggleShare() {
        const p = document.getElementById('share-panel');
        p.style.display = p.style.display === 'none' ? 'block' : 'none';
        if (p.style.display === 'block') {
            new QRious({ 
                element: document.getElementById('qr-code'), 
                value: document.getElementById('public-link').value, 
                size: 160, 
                padding: 10,
                level: 'H'
            });
        }
    }

    function showToast(message, type='success', timeout=3200){
        const container = document.getElementById('toast-container');
        if(!container) return;
        const el = document.createElement('div');
        el.className = 'toast-item ' + (type==='error' ? 'toast-error' : 'toast-success');
        el.innerHTML = '<div class="toast-body">'+message+'</div>';
        container.appendChild(el);
        // allow animation
        requestAnimationFrame(()=> el.classList.add('show'));
        setTimeout(()=>{
            el.classList.remove('show');
            el.addEventListener('transitionend', ()=> el.remove(), {once:true});
        }, timeout);
    }

    function copyLink() {
        const input = document.getElementById('public-link');
        input.select();
        try{
            document.execCommand('copy');
            showToast('Đã sao chép liên kết vào bộ nhớ tạm!', 'success');
        }catch(e){
            showToast('Sao chép thất bại. Vui lòng sao chép thủ công.', 'error');
        }
    }

    function copyQRToClipboard() {
        const canvas = document.getElementById('qr-code');
        if (!canvas || !canvas.width) {
            showToast('Vui lòng tạo mã QR trước (bấm Chia sẻ)!', 'error');
            return;
        }
        canvas.toBlob(blob => {
            if (!navigator.clipboard || typeof ClipboardItem === 'undefined'){
                showToast('Trình duyệt không hỗ trợ sao chép hình ảnh. Hãy Tải mã QR.', 'error');
                return;
            }
            const item = new ClipboardItem({ 'image/png': blob });
            navigator.clipboard.write([item]).then(() => {
                showToast('✓ Đã sao chép mã QR vào bộ nhớ tạm!', 'success');
            }).catch(err => {
                console.error('Copy failed:', err);
                showToast('❌ Sao chép thất bại. Hãy thử Tải mã QR thay thế.', 'error');
            });
        }, 'image/png');
    }

    function downloadQR() {
        const canvas = document.getElementById('qr-code');
        if (!canvas || !canvas.width) {
            showToast('Vui lòng tạo mã QR trước (bấm Chia sẻ)!', 'error');
            return;
        }
        const link = document.createElement('a');
        link.download = 'form-qr-code.png';
        link.href = canvas.toDataURL();
        link.click();
        showToast('Bắt đầu tải mã QR...', 'success');
    }
</script>