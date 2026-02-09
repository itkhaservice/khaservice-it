<?php
// File: modules/forms/export.php
// Xuất kết quả biểu mẫu ra file Excel (.xls)

$form_id = $_GET['id'] ?? null;
if (!$form_id || !is_numeric($form_id)) {
    die("ID Biểu mẫu không hợp lệ.");
}
$form_id = (int)$form_id;

// === SECURITY CHECK ===
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT title FROM forms WHERE id = ? AND user_id = ?");
$stmt->execute([$form_id, $user_id]);
$form = $stmt->fetch();

if (!$form) {
    die("Bạn không có quyền xuất dữ liệu biểu mẫu này.");
}

// Fetch questions for headers
$stmt_q = $pdo->prepare("SELECT id, question_text FROM form_questions WHERE form_id = ? ORDER BY question_order ASC");
$stmt_q->execute([$form_id]);
$questions = $stmt_q->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch submissions
$stmt_s = $pdo->prepare("
    SELECT s.id, s.submitted_at, a.question_id, a.answer_text
    FROM form_submissions s
    LEFT JOIN submission_answers a ON s.id = a.submission_id
    WHERE s.form_id = ?
    ORDER BY s.submitted_at DESC
");
$stmt_s->execute([$form_id]);
$results = $stmt_s->fetchAll();

$submissions = [];
foreach ($results as $row) {
    $sid = $row['id'];
    if (!isset($submissions[$sid])) {
        $submissions[$sid] = [
            'time' => $row['submitted_at'],
            'answers' => []
        ];
    }
    if ($row['question_id']) {
        $submissions[$sid]['answers'][$row['question_id']] = $row['answer_text'];
    }
}

// Export Logic
$filename = "Ket_qua_Form_" . preg_replace('/[^a-zA-Z0-9]/', '_', $form['title']) . "_" . date('Ymd_His') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Expires: 0");

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta http-equiv="content-type" content="text/plain; charset=UTF-8"></head><body>';
echo '<table border="1">';
echo '<tr>';
echo '<th style="background-color: #108042; color: #ffffff;">Thời gian nộp</th>';
foreach ($questions as $q_text) {
    echo '<th style="background-color: #108042; color: #ffffff;">' . htmlspecialchars($q_text) . '</th>';
}
echo '</tr>';

foreach ($submissions as $sub) {
    echo '<tr>';
    echo '<td>' . $sub['time'] . '</td>';
    foreach ($questions as $qid => $q_text) {
        $answer = $sub['answers'][$qid] ?? '';
        echo '<td>' . htmlspecialchars($answer) . '</td>';
    }
    echo '</tr>';
}
echo '</table></body></html>';
exit;
