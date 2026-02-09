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
        <a href="index.php?page=forms/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a>
        <button type="button" class="btn btn-success"><i class="fas fa-file-excel"></i> Xuất Excel</button>
    </div>
</div>

<div class="card table-container">
    <?php if (empty($submissions)): ?>
        <div class="empty-state">
            <i class="fas fa-poll-h empty-icon"></i>
            <h3>Chưa có lượt trả lời nào</h3>
            <p>Chia sẻ liên kết biểu mẫu của bạn để bắt đầu thu thập câu trả lời.</p>
            <div class="share-link-box">
                <input type="text" readonly value="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/khaservice-it/public/form.php?slug=' . $form['slug']; ?>" id="public-link">
                <button onclick="copyLink()" class="btn btn-primary"><i class="fas fa-copy"></i> Sao chép</button>
            </div>
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
                        <?php foreach ($questions as $q_id => $q_text): ?>
                            <td>
                                <?php echo htmlspecialchars($sub['answers'][$q_id] ?? ''); ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
function copyLink() {
    const linkInput = document.getElementById('public-link');
    linkInput.select();
    linkInput.setSelectionRange(0, 99999); // For mobile devices
    document.execCommand('copy');
    alert('Đã sao chép liên kết!');
}
</script>

<style>
.empty-state { text-align: center; padding: 60px 40px; }
.empty-state .empty-icon { font-size: 4rem; color: #cbd5e1; margin-bottom: 20px; }
.empty-state h3 { font-size: 1.5rem; font-weight: 600; margin-bottom: 10px; }
.empty-state p { color: #64748b; margin-bottom: 25px; }
.share-link-box { display: flex; justify-content: center; }
.share-link-box input {
    width: 400px;
    padding: 10px;
    border: 1px solid #cbd5e1;
    border-radius: 8px 0 0 8px;
    background: #f8fafc;
    color: #334155;
}
.share-link-box button {
    border-radius: 0 8px 8px 0;
}
.content-table td {
    white-space: normal;
    word-wrap: break-word;
    max-width: 300px; /* Adjust as needed */
}
</style>