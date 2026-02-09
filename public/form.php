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
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?php echo htmlspecialchars($form['theme_color'] ?? '#108042'); ?>;
            --primary-dark: #0d6a35;
            --light-gray: #f1f5f9;
            --medium-gray: #cbd5e1;
            --dark-gray: #475569;
            --text-color: #1e293b;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
        }
        body {
            font-family: 'Be Vietnam Pro', sans-serif;
            background-color: var(--light-gray);
            color: var(--text-color);
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }
        .form-container {
            width: 100%;
            max-width: 700px;
            background: #fff;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }
        .form-header {
            padding: 30px;
            background-color: var(--primary-color);
            color: #fff;
        }
        .form-header h1 {
            margin: 0 0 10px 0;
            font-size: 1.8rem;
        }
        .form-header p {
            margin: 0;
            opacity: 0.9;
            line-height: 1.6;
        }
        .form-body {
            padding: 30px;
        }
        .thank-you-message {
            text-align: center;
            padding: 80px 30px;
        }
        .thank-you-message h2 {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        .question-card {
            margin-bottom: 25px;
            padding: 20px;
            border: 1px solid var(--medium-gray);
            border-radius: 8px;
        }
        .question-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
        .question-title .required-star {
            color: #ef4444;
            margin-left: 4px;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--medium-gray);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(16, 129, 185, 0.1);
        }
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        .options-group label {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background-color 0.2s, border-color 0.2s;
        }
        .options-group label:hover {
            background-color: var(--light-gray);
        }
        .options-group input:checked + span {
            font-weight: 600;
            color: var(--primary-color);
        }
        .submit-btn {
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
            background-color: var(--primary-color);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .submit-btn:hover {
            background-color: var(--primary-dark);
        }
    </style>
</head>
<body>

    <div class="form-container">
        <div class="form-header">
            <h1><?php echo htmlspecialchars($form['title']); ?></h1>
            <?php if (!empty($form['description'])): ?>
                <p><?php echo nl2br(htmlspecialchars($form['description'])); ?></p>
            <?php endif; ?>
        </div>

        <div class="form-body">
            <?php if ($submission_successful): ?>
                <div class="thank-you-message">
                    <h2>Cảm ơn bạn!</h2>
                    <p>Câu trả lời của bạn đã được ghi lại thành công.</p>
                </div>
            <?php else: ?>
                <form action="" method="POST">
                    <?php foreach ($questions as $q): ?>
                        <div class="question-card">
                            <div class="question-title">
                                <?php echo htmlspecialchars($q['question_text']); ?>
                                <?php if ($q['is_required']): ?><span class="required-star">*</span><?php endif; ?>
                            </div>
                            <div class="question-input">
                                <?php
                                $input_name = "answers[{$q['id']}]";
                                $required_attr = $q['is_required'] ? 'required' : '';
                                switch ($q['question_type']) {
                                    case 'text':
                                        echo "<input type='text' name='{$input_name}' class='form-control' {$required_attr}>";
                                        break;
                                    case 'textarea':
                                        echo "<textarea name='{$input_name}' class='form-control' {$required_attr}></textarea>";
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
                                                echo "<label><input type='{$type}' name='{$name_attr}' value='" . htmlspecialchars($opt) . "' {$required_attr}> <span>" . htmlspecialchars($opt) . "</span></label>";
                                            }
                                            echo "</div>";
                                        }
                                        break;
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="submit-btn">Gửi câu trả lời</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>