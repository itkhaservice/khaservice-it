<?php
// File: public/form.php
// Public-facing page for users to fill out a form.

// Manually include necessary files as this is a standalone page
require_once '../config/db.php';
session_start(); // Needed for potential messages, though we'll handle it locally.

$slug = $_GET['slug'] ?? null;
$form = null;
$questions = [];
$submission_successful = false;

if (!$slug) {
    die("Không tìm thấy biểu mẫu.");
}

// Fetch form details
$stmt = $pdo->prepare("SELECT * FROM forms WHERE slug = ? AND status = 'published'");
$stmt->execute([$slug]);
$form = $stmt->fetch();

if (!$form) {
    die("Biểu mẫu không tồn tại hoặc chưa được phát hành.");
}

// Fetch questions if form exists
$stmt = $pdo->prepare("
    SELECT q.*, GROUP_CONCAT(o.option_text ORDER BY o.option_order ASC SEPARATOR '|||') as options
    FROM form_questions q
    LEFT JOIN question_options o ON q.id = o.question_id
    WHERE q.form_id = ?
    GROUP BY q.id
    ORDER BY q.question_order ASC
");
$stmt->execute([$form['id']]);
$questions = $stmt->fetchAll();


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO form_submissions (form_id, submitter_ip) VALUES (?, ?)");
        $stmt->execute([$form['id'], $_SERVER['REMOTE_ADDR']]);
        $submission_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO submission_answers (submission_id, question_id, answer_text) VALUES (?, ?, ?)");

        foreach ($_POST['answers'] as $question_id => $answer) {
            if (is_array($answer)) {
                // Handle checkboxes
                $answer_text = implode(', ', $answer);
            } else {
                $answer_text = trim($answer);
            }
            
            if (!empty($answer_text)) {
                $stmt->execute([$submission_id, $question_id, $answer_text]);
            }
        }

        $pdo->commit();
        $submission_successful = true;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Form submission error: " . $e->getMessage());
        die("Đã xảy ra lỗi khi gửi biểu mẫu. Vui lòng thử lại.");
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($form['title']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?php echo htmlspecialchars($form['theme_color'] ?? '#108042'); ?>;
            --primary-light: <?php echo htmlspecialchars($form['theme_color'] ?? '#108042'); ?>15;
            --primary-dark: #0d6a35;
            --bg-color: #f0f4f8;
            --card-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            --card-shadow-hover: 0 10px 20px rgba(0,0,0,0.1), 0 6px 6px rgba(0,0,0,0.1);
            --text-main: #202124;
            --text-secondary: #5f6368;
            --border-color: #dadce0;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            margin: 0;
            padding: 40px 10px;
            line-height: 1.6;
            background-image: linear-gradient(180deg, var(--primary-color) 0%, var(--primary-color) 200px, var(--bg-color) 200px);
            background-repeat: no-repeat;
        }
        .form-container {
            width: 100%;
            max-width: 770px;
            margin: 0 auto;
        }
        .form-card-header {
            background: #fff;
            border-radius: 8px;
            margin-bottom: 12px;
            overflow: hidden;
            border-top: 10px solid var(--primary-color);
            box-shadow: var(--card-shadow);
            padding: 24px 32px;
        }
        .form-card-header h1 {
            margin: 0 0 12px 0;
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .form-card-header p {
            margin: 0;
            color: var(--text-main);
            font-size: 1rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 24px;
        }
        .required-info {
            color: #d93025;
            font-size: 0.85rem;
            margin-top: 12px;
            display: block;
        }

        .question-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            padding: 24px 32px;
            margin-bottom: 12px;
            border: 1px solid var(--border-color);
            transition: box-shadow 0.3s;
        }
        .question-title {
            font-weight: 500;
            font-size: 1.1rem;
            margin-bottom: 16px;
            display: block;
            word-wrap: break-word;
        }
        .required-star { color: #d93025; margin-left: 4px; }
        
        /* Custom Input Styles */
        .form-control {
            width: 100%;
            padding: 10px 0;
            border: none;
            border-bottom: 1px solid var(--border-color);
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background: transparent;
            color: var(--text-main);
        }
        .form-control:focus {
            outline: none;
            border-bottom: 2px solid var(--primary-color);
        }
        textarea.form-control {
            border: 1px solid var(--border-color);
            padding: 12px;
            border-radius: 4px;
            min-height: 100px;
        }
        textarea.form-control:focus {
            border: 2px solid var(--primary-color);
        }

        /* Modern Radio & Checkbox */
        .options-group { display: flex; flex-direction: column; gap: 8px; }
        .option-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
            position: relative;
        }
        .option-item:hover {
            background-color: #f8f9fa;
        }
        .option-item input {
            margin-right: 14px;
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--primary-color);
        }
        .option-item span {
            font-size: 0.95rem;
            color: #3c4043;
        }

        .submit-section { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-top: 24px;
            padding: 0 10px;
        }
        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
            transition: all 0.2s ease;
        }
        .submit-btn:hover { 
            background-color: var(--primary-dark);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        /* Thank you card refinement */
        .thank-you-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            padding: 70px 40px;
            text-align: center;
            border-top: 10px solid var(--primary-color);
        }
        .thank-you-card i {
            font-size: 4.5rem;
            color: var(--primary-color);
            margin-bottom: 24px;
        }
        .thank-you-card h2 { font-size: 2.2rem; margin-bottom: 16px; color: var(--text-main); }
        .thank-you-card p { color: var(--text-secondary); font-size: 1.1rem; margin-bottom: 32px; }
        .btn-reload {
            display: inline-block;
            padding: 10px 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            transition: background 0.2s;
        }
        .btn-reload:hover { background: #f8f9fa; }

        @media (max-width: 600px) {
            body { padding: 20px 0; }
            .form-card-header, .question-card { padding: 20px; border-radius: 0; }
            .form-card-header h1 { font-size: 1.7rem; }
            .submit-section { flex-direction: column; gap: 16px; align-items: flex-start; }
        }
    </style>
</head>
<body>

    <div class="form-container">
        <?php if ($submission_successful): ?>
            <div class="thank-you-card">
                <i class="fas fa-check-circle"></i>
                <h2>Cảm ơn bạn!</h2>
                <p><?php echo !empty($form['thank_you_message']) ? nl2br(htmlspecialchars($form['thank_you_message'])) : 'Câu trả lời của bạn đã được ghi lại thành công.'; ?></p>
                <a href="form.php?slug=<?php echo $slug; ?>" class="btn-reload">Gửi phản hồi khác</a>
            </div>
        <?php else: ?>
            <div class="form-card-header">
                <h1><?php echo htmlspecialchars($form['title']); ?></h1>
                <?php if (!empty($form['description'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($form['description'])); ?></p>
                <?php endif; ?>
                <span class="required-info">* Biểu thị câu hỏi bắt buộc</span>
            </div>

            <form action="" method="POST">
                <?php foreach ($questions as $q): ?>
                    <div class="question-card">
                        <label class="question-title">
                            <?php echo htmlspecialchars($q['question_text']); ?>
                            <?php if ($q['is_required']): ?><span class="required-star">*</span><?php endif; ?>
                        </label>
                        <div class="question-input">
                            <?php
                            $input_name = "answers[{$q['id']}]";
                            $required_attr = $q['is_required'] ? 'required' : '';
                            switch ($q['question_type']) {
                                case 'text':
                                    echo "<input type='text' name='{$input_name}' class='form-control' placeholder='Câu trả lời của bạn' {$required_attr}>";
                                    break;
                                case 'textarea':
                                    echo "<textarea name='{$input_name}' class='form-control' placeholder='Câu trả lời của bạn' {$required_attr}></textarea>";
                                    break;
                                case 'date':
                                    echo "<input type='date' name='{$input_name}' class='form-control' {$required_attr}>";
                                    break;
                                case 'multiple_choice':
                                case 'checkboxes':
                                case 'dropdown':
                                    $options = explode('|||', $q['options']);
                                    if ($q['question_type'] == 'dropdown') {
                                        echo "<select name='{$input_name}' class='form-control' {$required_attr}>";
                                        echo "<option value=''>-- Chọn một mục --</option>";
                                        foreach ($options as $opt) {
                                            echo "<option value='" . htmlspecialchars($opt) . "'>" . htmlspecialchars($opt) . "</option>";
                                        }
                                        echo "</select>";
                                    } else {
                                        $type = $q['question_type'] == 'checkboxes' ? 'checkbox' : 'radio';
                                        $name_attr = $q['question_type'] == 'checkboxes' ? "{$input_name}[]" : $input_name;
                                        echo "<div class='options-group'>";
                                        foreach ($options as $opt) {
                                            echo "<label class='option-item'><input type='{$type}' name='{$name_attr}' value='" . htmlspecialchars($opt) . "' {$required_attr}> <span>" . htmlspecialchars($opt) . "</span></label>";
                                        }
                                        echo "</div>";
                                    }
                                    break;
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="submit-section">
                    <button type="submit" class="submit-btn">Gửi</button>
                    <span style="color: var(--text-secondary); font-size: 0.9rem;">Không bao giờ gửi mật khẩu thông qua Biểu mẫu.</span>
                </div>
            </form>
        <?php endif; ?>
    </div>

</body>
</html>

</body>
</html>