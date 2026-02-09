<?php
// File: modules/forms/view_results.php
// Hiển thị kết quả thu thập được từ biểu mẫu.

$form_id = $_GET['id'] ?? null;
if (!$form_id || !is_numeric($form_id)) {
    die("ID Biểu mẫu không hợp lệ.");
}
$form_id = (int)$form_id;

// === SECURITY CHECK ===
$user_id = $_SESSION['user_id']; // Get current logged-in user ID

$sql_auth = "SELECT * FROM forms WHERE id = :form_id";
$stmt_auth = $pdo->prepare($sql_auth);
$stmt_auth->execute([':form_id' => $form_id]);
$form = $stmt_auth->fetch();

if (!$form) {
    die("Biểu mẫu không tồn tại.");
}

// Ownership check: form's user_id must match logged-in user_id
if ($form['user_id'] !== $user_id) {
    die("Bạn không có quyền truy cập vào kết quả của biểu mẫu này.");
}
// === END SECURITY CHECK ===


// Fetch questions for table headers
$sql_questions = "SELECT id, question_text FROM form_questions WHERE form_id = ? ORDER BY question_order ASC";
$stmt_questions = $pdo->prepare($sql_questions);
$stmt_questions->execute([$form_id]);
$questions = $stmt_questions->fetchAll(PDO::FETCH_KEY_PAIR); // id => question_text

// Fetch all submissions and their answers
$sql_submissions = "
    SELECT 
        s.id as submission_id, 
        s.submitted_at,
        a.question_id,
        a.answer_text
    FROM form_submissions s
    JOIN submission_answers a ON s.id = a.submission_id
    WHERE s.form_id = ?
    ORDER BY s.submitted_at DESC, a.question_id ASC
";
$stmt_submissions = $pdo->prepare($sql_submissions);
$stmt_submissions->execute([$form_id]);
$all_answers = $stmt_submissions->fetchAll();

// Process data into a structured array: [submission_id => [ 'submitted_at' => ..., 'answers' => [question_id => answer_text] ]]
$submissions = [];
foreach ($all_answers as $answer) {
    $sub_id = $answer['submission_id'];
    if (!isset($submissions[$sub_id])) {
        $submissions[$sub_id] = [
            'submitted_at' => $answer['submitted_at'],
            'answers' => []
        ];
    }
    $submissions[$sub_id]['answers'][$answer['question_id']] = $answer['answer_text'];
}

?>

<div class="page-header">
    <h2><i class="fas fa-chart-bar"></i> Kết quả: <?php echo htmlspecialchars($form['title']); ?></h2>
<div class="header-actions">
        <a href="user_forms_dashboard.php?page=forms/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a>
        <a href="user_forms_dashboard.php?page=forms/export&id=<?php echo $form_id; ?>" class="btn btn-success"><i class="fas fa-file-excel"></i> Xuất Excel</a>
    </div>
</div>

<div class="share-section card">
    <div class="card-header-custom">
        <h3><i class="fas fa-share-alt"></i> Chia sẻ Biểu mẫu</h3>
    </div>
    <div class="card-body-custom share-content">
        <div class="share-url-box">
            <label>Liên kết biểu mẫu:</label>
            <div class="input-group">
                <input type="text" readonly value="<?php echo $final_base . 'public/form.php?slug=' . $form['slug']; ?>" id="public-link">
                <button onclick="copyLink()" class="btn btn-primary"><i class="fas fa-copy"></i> Sao chép</button>
                <a href="<?php echo $final_base . 'public/form.php?slug=' . $form['slug']; ?>" target="_blank" class="btn btn-secondary"><i class="fas fa-external-link-alt"></i> Mở</a>
            </div>
        </div>
        <div class="qr-box">
            <canvas id="qr-code"></canvas>
            <button onclick="downloadQR()" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i> Tải mã QR</button>
        </div>
    </div>
</div>

<div class="card table-container">
    <?php if (empty($submissions)): ?>
        <div class="empty-state">
            <i class="fas fa-poll-h empty-icon"></i>
            <h3>Chưa có lượt trả lời nào</h3>
            <p>Sử dụng liên kết ở trên để bắt đầu thu thập câu trả lời.</p>
        </div>
    <?php else: ?>
        <table class="content-table">
            <thead>
                <tr>
                    <th>Ngày nộp</th>
                    <?php foreach ($questions as $q_text): ?>
                        <th><?php echo htmlspecialchars($q_text); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $sub): ?>
                    <tr>
                        <td class="font-medium"><?php echo date('d/m/Y H:i', strtotime($sub['submitted_at'])); ?></td>
                        <?php foreach ($questions as $q_id => $q_text): 
                            $ans = $sub['answers'][$q_id] ?? '';
                            ?>
                            <td>
                                <?php 
                                if (strpos($ans, 'uploads/forms/') === 0) {
                                    // Secure link
                                    echo "<a href='api/download_form_file.php?file=" . urlencode($ans) . "' target='_blank' class='btn-link'><i class='fas fa-paperclip'></i> Xem tệp</a>";
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
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var qr = new QRious({
        element: document.getElementById('qr-code'),
        value: document.getElementById('public-link').value,
        size: 150,
        padding: 10
    });
});

function downloadQR() {
    var canvas = document.getElementById('qr-code');
    var link = document.createElement('a');
    link.download = 'form-qr-<?php echo $form['slug']; ?>.png';
    link.href = canvas.toDataURL();
    link.click();
}

function copyLink() {
    const linkInput = document.getElementById('public-link');
    linkInput.select();
    linkInput.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    // UI Feedback
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> Đã chép';
    btn.classList.replace('btn-primary', 'btn-success');
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.classList.replace('btn-success', 'btn-primary');
    }, 2000);
}
</script>

<style>
.share-section { margin-bottom: 25px; }
.share-content { display: flex; gap: 40px; align-items: flex-start; }
.share-url-box { flex: 1; }
.share-url-box label { display: block; margin-bottom: 8px; font-weight: 600; color: #64748b; }
.share-url-box .input-group { display: flex; gap: 10px; }
.share-url-box input { flex: 1; padding: 12px; border: 1px solid #dddfe2; border-radius: 8px; background: #f8fafc; font-family: monospace; }

.qr-box { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 10px; background: #fff; border: 1px solid #dddfe2; border-radius: 12px; }
#qr-code { width: 150px; height: 150px; }

.btn-outline-primary { border: 1px solid var(--primary-color); color: var(--primary-color); background: transparent; }
.btn-outline-primary:hover { background: var(--primary-color); color: #fff; }

.empty-state { text-align: center; padding: 60px 40px; }
.empty-state .empty-icon { font-size: 4rem; color: #cbd5e1; margin-bottom: 20px; }
.empty-state h3 { font-size: 1.5rem; font-weight: 600; margin-bottom: 10px; }
.empty-state p { color: #64748b; margin-bottom: 25px; }

.content-table td { white-space: normal; word-wrap: break-word; max-width: 300px; }
</style>