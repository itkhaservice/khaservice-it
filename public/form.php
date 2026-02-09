<?php
// File: public/form.php
// Public-facing page for users to fill out a form.

// Manually include necessary files as this is a standalone page
require_once '../config/db.php';
require_once '../includes/audit_helper.php';
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
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Không tìm thấy biểu mẫu</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Inter', sans-serif; background: #f0f4f8; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
            .error-card { background: white; padding: 50px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; max-width: 500px; border-top: 8px solid #ef4444; }
            h1 { color: #1e293b; margin-bottom: 20px; font-size: 1.8rem; }
            p { color: #64748b; font-size: 1.1rem; line-height: 1.6; }
            .icon { font-size: 4rem; color: #f87171; margin-bottom: 20px; }
            .btn { display: inline-block; margin-top: 25px; padding: 12px 24px; background: #108042; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; }
        </style>
    </head>
    <body>
        <div class="error-card">
            <div class="icon">🔍</div>
            <h1>Không tìm thấy biểu mẫu</h1>
            <p>Liên kết bạn truy cập không tồn tại hoặc biểu mẫu này đang ở trạng thái bản nháp.</p>
            <p>Vui lòng kiểm tra lại đường dẫn hoặc liên hệ với người quản trị.</p>
            <a href="../index.php" class="btn">Quay lại trang chủ</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- NEW: CHECK LIMITS ---
$is_closed = false;
$closed_reason = "";

// Check Expiration
if ($form['expires_at'] && strtotime($form['expires_at']) < time()) {
    $is_closed = true;
    $closed_reason = "Biểu mẫu này đã hết hạn vào lúc " . date('H:i d/m/Y', strtotime($form['expires_at'])) . ".";
}

// Check Response Limit
if (!$is_closed && $form['response_limit']) {
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM form_submissions WHERE form_id = ?");
    $stmt_count->execute([$form['id']]);
    $current_submissions = $stmt_count->fetchColumn();
    
    if ($current_submissions >= $form['response_limit']) {
        $is_closed = true;
        $closed_reason = "Biểu mẫu này đã đạt giới hạn số lượt trả lời tối đa (" . $form['response_limit'] . ").";
    }
}

if ($is_closed) {
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Biểu mẫu đã đóng - <?php echo htmlspecialchars($form['title']); ?></title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Inter', sans-serif; background: #f0f4f8; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
            .closed-card { background: white; padding: 50px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; max-width: 500px; border-top: 8px solid #64748b; }
            h1 { color: #1e293b; margin-bottom: 20px; font-size: 1.8rem; }
            p { color: #64748b; font-size: 1.1rem; line-height: 1.6; }
            .icon { font-size: 4rem; color: #94a3b8; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class="closed-card">
            <div class="icon">🚫</div>
            <h1>Biểu mẫu đã đóng</h1>
            <p><?php echo $closed_reason; ?></p>
            <p>Vui lòng liên hệ với người tạo biểu mẫu để biết thêm chi tiết.</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}
// --- END CHECK LIMITS ---

// Fetch questions if form exists
$stmt = $pdo->prepare("
    SELECT q.*, 
           GROUP_CONCAT(CONCAT(o.option_text, ':::', IFNULL(o.option_type, 'choice')) ORDER BY o.option_order ASC SEPARATOR '|||') as options_with_type
    FROM form_questions q
    LEFT JOIN question_options o ON q.id = o.question_id
    WHERE q.form_id = ? AND q.deleted_at IS NULL
    GROUP BY q.id
    ORDER BY q.question_order ASC
");
$stmt->execute([$form['id']]);
$questions = $stmt->fetchAll();


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $stmt_sub = $pdo->prepare("INSERT INTO form_submissions (form_id, submitter_ip) VALUES (?, ?)");
        $stmt_sub->execute([$form['id'], $_SERVER['REMOTE_ADDR']]);
        $submission_id = $pdo->lastInsertId();

        $stmt_ans = $pdo->prepare("INSERT INTO submission_answers (submission_id, question_id, answer_text) VALUES (?, ?, ?)");

        // Process standard answers
        if (isset($_POST['answers']) && is_array($_POST['answers'])) {
            foreach ($_POST['answers'] as $question_id => $answer) {
                if (is_array($answer)) {
                    // Check if it's a grid answer (row_id => col_value) or multiple checkboxes
                    if (key($answer) !== 0) {
                        $answer_text = json_encode($answer, JSON_UNESCAPED_UNICODE);
                    } else {
                        $answer_text = implode(', ', $answer);
                    }
                } else {
                    $answer_text = trim($answer);
                }
                
                if ($answer_text !== '') {
                    $stmt_ans->execute([$submission_id, $question_id, $answer_text]);
                }
            }
        }

        // Process file uploads
        if (!empty($_FILES['files']['name'])) {
            foreach ($_FILES['files']['name'] as $question_id => $file_name) {
                if ($_FILES['files']['error'][$question_id] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['files']['tmp_name'][$question_id];
                    $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                    $new_name = uniqid('form_') . '_' . time() . '.' . $ext;
                    $upload_path = '../uploads/forms/' . $new_name;
                    
                    if (move_uploaded_file($tmp_name, $upload_path)) {
                        $stmt_ans->execute([$submission_id, $question_id, 'uploads/forms/' . $new_name]);
                    }
                }
            }
        }

        log_action($pdo, 'SUBMIT_FORM', 'forms', $form['id'], "New submission from IP: " . $_SERVER['REMOTE_ADDR']);

        $pdo->commit();
        $submission_successful = true;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Form submission error: " . $e->getMessage());
        die("Đã xảy ra lỗi khi gửi biểu mẫu: " . $e->getMessage());
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

        /* Grid & Scale Styling */
        .grid-table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0;
            margin-top: 15px; 
            border: 1px solid #f0f0f0;
            border-radius: 8px;
            overflow: hidden;
        }
        .grid-table th {
            background-color: #f8f9fa;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 15px 10px;
        }
        .grid-table td { 
            padding: 15px 10px; 
            border-top: 1px solid #f0f0f0; 
            text-align: center; 
        }
        .grid-table th:first-child, .grid-table td:first-child { 
            text-align: left; 
            font-weight: 500; 
            color: var(--text-main);
            padding-left: 20px;
            background-color: #fff;
        }
        .grid-table tr:nth-child(even) td {
            background-color: #fafafa;
        }
        .grid-table tr:hover td { 
            background-color: var(--primary-light); 
        }
        .grid-table input[type="radio"], .grid-table input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--primary-color);
        }
        
        .scale-group { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-end; 
            padding: 25px 0; 
            max-width: 550px; 
            margin: 0 auto; 
        }
        .scale-item { 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            gap: 12px;
            flex: 1;
        }
        .scale-item label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-weight: 500;
        }
        .scale-label { 
            font-size: 0.95rem; 
            color: var(--text-main); 
            padding-bottom: 5px;
            max-width: 100px;
            text-align: center;
        }

        /* File Upload Styling */
        .file-input-wrapper {
            position: relative;
            margin-top: 10px;
        }
        .custom-file-upload {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            color: var(--text-secondary);
        }
        .custom-file-upload:hover {
            border-color: var(--primary-color);
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        .custom-file-upload i { font-size: 1.5rem; }
        
        input[type="file"]::file-selector-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            margin-right: 15px;
            cursor: pointer;
            font-weight: 500;
            transition: 0.2s;
        }
        input[type="file"]::file-selector-button:hover {
            background-color: var(--primary-dark);
        }

        @media (max-width: 600px) {
            body { padding: 20px 0; }
            .form-card-header, .question-card { padding: 20px; border-radius: 0; }
            .form-card-header h1 { font-size: 1.7rem; }
            .submit-section { flex-direction: column; gap: 16px; align-items: flex-start; }
            .grid-table { display: block; overflow-x: auto; white-space: nowrap; }
            .scale-group { overflow-x: auto; justify-content: flex-start; gap: 15px; }
            .scale-item { flex: none; min-width: 40px; }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
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

            <form action="" method="POST" enctype="multipart/form-data">
                <?php 
                $q_index = 0;
                foreach ($questions as $q): 
                    $q_index++;
                    $logic_attr = $q['logic_config'] ? 'data-logic=\'' . $q['logic_config'] . '\'' : '';
                ?>
                    <div class="question-card" id="block-q_<?php echo $q_index; ?>" <?php echo $logic_attr; ?>>
                        <label class="question-title">
                            <?php echo htmlspecialchars($q['question_text']); ?>
                            <?php if ($q['is_required']): ?><span class="required-star">*</span><?php endif; ?>
                        </label>
                        <div class="question-input">
                            <?php
                            $input_name = "answers[{$q['id']}]";
                            $required_attr = $q['is_required'] ? 'required' : '';
                            
                            $options_list = [];
                            if ($q['options_with_type']) {
                                foreach (explode('|||', $q['options_with_type']) as $opt_str) {
                                    $parts = explode(':::', $opt_str);
                                    $options_list[] = ['text' => $parts[0] ?? '', 'type' => $parts[1] ?? 'choice'];
                                }
                            }

                            switch ($q['question_type']) {
                                case 'text':
                                    echo "<input type='text' name='{$input_name}' class='form-control' placeholder='Câu trả lời của bạn' {$required_attr}>";
                                    break;
                                case 'textarea':
                                    echo "<textarea name='{$input_name}' class='form-control' placeholder='Câu trả lời của bạn' {$required_attr}></textarea>";
                                    break;
                                case 'number':
                                    echo "<input type='number' name='{$input_name}' class='form-control' placeholder='Nhập số' {$required_attr}>";
                                    break;
                                case 'date':
                                    echo "<input type='date' name='{$input_name}' class='form-control' {$required_attr}>";
                                    break;
                                case 'time':
                                    echo "<input type='time' name='{$input_name}' class='form-control' {$required_attr}>";
                                    break;
                                case 'datetime':
                                    echo "<input type='datetime-local' name='{$input_name}' class='form-control' {$required_attr}>";
                                    break;
                                case 'file':
                                    echo "<div class='file-input-wrapper'>
                                            <input type='file' name='files[{$q['id']}]' class='form-control' style='border-bottom:none; padding: 5px 0;' {$required_attr}>
                                            <small style='color:var(--text-secondary); display:block; margin-top:5px;'><i class='fas fa-info-circle'></i> Chấp nhận ảnh, PDF, Word, Excel...</small>
                                          </div>";
                                    break;
                                case 'multiple_choice':
                                case 'checkboxes':
                                case 'dropdown':
                                    if ($q['question_type'] == 'dropdown') {
                                        echo "<select name='{$input_name}' class='form-control' {$required_attr}>";
                                        echo "<option value=''>-- Chọn một mục --</option>";
                                        foreach ($options_list as $opt) {
                                            echo "<option value='" . htmlspecialchars($opt['text']) . "'>" . htmlspecialchars($opt['text']) . "</option>";
                                        }
                                        echo "</select>";
                                    } else {
                                        $type = $q['question_type'] == 'checkboxes' ? 'checkbox' : 'radio';
                                        $name_attr = $q['question_type'] == 'checkboxes' ? "{$input_name}[]" : $input_name;
                                        echo "<div class='options-group'>";
                                        foreach ($options_list as $opt) {
                                            echo "<label class='option-item'><input type='{$type}' name='{$name_attr}' value='" . htmlspecialchars($opt['text']) . "' {$required_attr}> <span>" . htmlspecialchars($opt['text']) . "</span></label>";
                                        }
                                        echo "</div>";
                                    }
                                    break;
                                case 'linear_scale':
                                    $min = (int)($options_list[0]['text'] ?? 1);
                                    $max = (int)($options_list[1]['text'] ?? 5);
                                    $min_label = $options_list[2]['text'] ?? '';
                                    $max_label = $options_list[3]['text'] ?? '';
                                    echo "<div class='scale-group'>";
                                    if ($min_label) echo "<span class='scale-label'>{$min_label}</span>";
                                    for ($i = $min; $i <= $max; $i++) {
                                        echo "<div class='scale-item'>
                                                <label>{$i}</label>
                                                <input type='radio' name='{$input_name}' value='{$i}' {$required_attr} style='width:20px; height:20px; accent-color:var(--primary-color);'>
                                              </div>";
                                    }
                                    if ($max_label) echo "<span class='scale-label'>{$max_label}</span>";
                                    echo "</div>";
                                    break;
                                case 'multiple_choice_grid':
                                case 'checkbox_grid':
                                    $rows = array_filter($options_list, fn($o) => $o['type'] === 'row');
                                    $cols = array_filter($options_list, fn($o) => $o['type'] === 'column');
                                    $input_type = ($q['question_type'] === 'checkbox_grid') ? 'checkbox' : 'radio';
                                    echo "<table class='grid-table'><thead><tr><th></th>";
                                    foreach ($cols as $col) echo "<th>" . htmlspecialchars($col['text']) . "</th>";
                                    echo "</tr></thead><tbody>";
                                    foreach ($rows as $row) {
                                        $row_id = preg_replace('/[^a-z0-9]/i', '_', $row['text']);
                                        $sub_name = ($q['question_type'] === 'checkbox_grid') ? "{$input_name}[{$row_id}][]" : "{$input_name}[{$row_id}]";
                                        echo "<tr><td>" . htmlspecialchars($row['text']) . "</td>";
                                        foreach ($cols as $col) {
                                            echo "<td><input type='{$input_type}' name='{$sub_name}' value='" . htmlspecialchars($col['text']) . "' {$required_attr} style='width:18px; height:18px; accent-color:var(--primary-color);'></td>";
                                        }
                                        echo "</tr>";
                                    }
                                    echo "</tbody></table>";
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const cards = document.querySelectorAll('.question-card');

        function evaluateLogic() {
            cards.forEach(card => {
                const logicStr = card.getAttribute('data-logic');
                if (!logicStr) return;

                const logic = JSON.parse(logicStr);
                const depCard = document.getElementById('block-' + logic.dependsOn);
                if (!depCard) return;

                // Find the value of the dependent question
                let depValue = '';
                const inputs = depCard.querySelectorAll('input, select, textarea');
                
                inputs.forEach(input => {
                    if ((input.type === 'radio' || input.type === 'checkbox')) {
                        if (input.checked) depValue = input.value;
                    } else {
                        depValue = input.value;
                    }
                });

                if (depValue == logic.value) {
                    card.style.display = 'block';
                    card.querySelectorAll('input, select, textarea').forEach(i => i.disabled = false);
                } else {
                    card.style.display = 'none';
                    card.querySelectorAll('input, select, textarea').forEach(i => i.disabled = true);
                }
            });
        }

        if (form) {
            form.addEventListener('change', evaluateLogic);
            evaluateLogic(); // Initial run
        }
    });
    </script>
</body>
</html>