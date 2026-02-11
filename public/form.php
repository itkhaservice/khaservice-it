<?php
// File: public/form.php
// Public-facing page for users to fill out a form.

// Manually include necessary files as this is a standalone page
require_once '../config/db.php';
require_once '../includes/audit_helper.php';
session_start(); // Needed for potential messages, though we'll handle it locally.

// Compute final_base (same logic as includes/header.php) so favicon and asset links resolve correctly
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$base_dir = dirname(dirname($script_name));
$base_dir = rtrim($base_dir, '/\\') . '/';
$final_base = $protocol . '://' . $host . $base_dir;

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
        <link rel="icon" type="image/png" href="<?php echo $final_base; ?>uploads/system/Logo1024x.png">
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
        <link rel="icon" type="image/png" href="<?php echo $final_base; ?>uploads/system/Logo1024x.png">
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
    <link rel="icon" type="image/png" href="<?php echo $final_base; ?>uploads/system/Logo1024x.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?php echo htmlspecialchars($form['theme_color'] ?? '#108042'); ?>;
            --primary-light: <?php echo htmlspecialchars($form['theme_color'] ?? '#108042'); ?>12;
            --primary-lighter: <?php echo htmlspecialchars($form['theme_color'] ?? '#108042'); ?>20;
            --primary-dark: #0d6a35;
            --bg-color: #f8f9fa;
            --bg-gradient: linear-gradient(135deg, var(--primary-color) 0%, #0d6a35 100%);
            --card-shadow: 0 2px 8px rgba(0,0,0,0.08);
            --card-shadow-hover: 0 12px 24px rgba(0,0,0,0.12);
            --text-main: #1a202c;
            --text-secondary: #718096;
            --border-color: #e2e8f0;
            --input-hover: #f7fafc;
        }

        * {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            margin: 0;
            padding: 50px 15px;
            line-height: 1.65;
            letter-spacing: -0.3px;
            background-image: linear-gradient(180deg, var(--primary-color) 0%, var(--primary-color) 250px, var(--bg-color) 250px);
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .form-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }

        .form-card-header {
            background: #ffffff;
            border-radius: 12px;
            margin-bottom: 20px;
            overflow: hidden;
            border-top: 6px solid var(--primary-color);
            box-shadow: var(--card-shadow);
            padding: 32px 36px;
            animation: slideUp 0.5s ease-out;
        }

        .form-card-header h1 {
            margin: 0 0 14px 0;
            font-size: 2.4rem;
            font-weight: 700;
            letter-spacing: -0.8px;
            color: var(--text-main);
            line-height: 1.2;
        }

        .form-card-header p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 1.05rem;
            line-height: 1.7;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--border-color);
            font-weight: 400;
            text-align: justify;
            text-align-last: left;
        }

        .required-info {
            color: #e53e3e;
            font-size: 0.8rem;
            margin-top: 14px;
            display: block;
            font-weight: 500;
            letter-spacing: 0.2px;
        }

        .question-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 28px 36px;
            margin-bottom: 16px;
            border: 1px solid var(--border-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: slideUp 0.5s ease-out forwards;
        }

        .question-card:nth-child(n) {
            animation-delay: calc(0.1s * var(--index, 1));
        }

        .question-card:hover {
            box-shadow: var(--card-shadow-hover);
            border-color: var(--primary-lighter);
        }

        .question-title {
            font-weight: 600;
            font-size: 1.15rem;
            margin-bottom: 18px;
            display: block;
            word-wrap: break-word;
            color: var(--text-main);
            letter-spacing: -0.3px;
        }

        .required-star {
            color: #e53e3e;
            margin-left: 4px;
            font-weight: 700;
        }

        /* Custom Input Styles */
        .form-control {
            width: 100%;
            padding: 12px 0;
            border: none;
            border-bottom: 2px solid var(--border-color);
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            background: transparent;
            color: var(--text-main);
            letter-spacing: -0.2px;
        }

        .form-control::placeholder {
            color: #cbd5e0;
            font-weight: 400;
        }

        .form-control:hover {
            border-bottom-color: #cbd5e0;
            background-color: var(--input-hover);
        }

        .form-control:focus {
            outline: none;
            border-bottom: 2px solid var(--primary-color);
            background-color: transparent;
            box-shadow: 0 1px 0 0 var(--primary-color);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.5 1.5L6 6L10.5 1.5' stroke='%23718096' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0 center;
            padding-right: 24px;
        }

        textarea.form-control {
            border: 1.5px solid var(--border-color);
            padding: 14px 12px;
            border-radius: 8px;
            min-height: 110px;
            resize: vertical;
            line-height: 1.6;
        }

        textarea.form-control:hover {
            border-color: #cbd5e0;
        }

        textarea.form-control:focus {
            border: 1.5px solid var(--primary-color);
            box-shadow: 0 0 0 3px var(--primary-lighter);
        }

        /* Modern Radio & Checkbox */
        .options-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .option-item {
            display: flex;
            align-items: center;
            padding: 14px 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
            border: 1px solid transparent;
        }

        .option-item:hover {
            background-color: var(--primary-lighter);
            border-color: var(--primary-light);
        }

        .option-item input {
            margin-right: 12px;
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary-color);
            flex-shrink: 0;
        }

        .option-item span {
            font-size: 0.95rem;
            color: var(--text-main);
            font-weight: 400;
        }

        .submit-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 32px;
            padding: 0 0;
        }

        .submit-btn {
            background: var(--bg-gradient);
            color: white;
            border: none;
            padding: 14px 40px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        .submit-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Thank you card refinement */
        .thank-you-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 80px 45px;
            text-align: center;
            border-top: 6px solid var(--primary-color);
            animation: scaleIn 0.5s ease-out;
        }

        .thank-you-card i {
            font-size: 5rem;
            color: var(--primary-color);
            margin-bottom: 28px;
            display: inline-block;
            animation: bounce 0.6s ease-out;
        }

        .thank-you-card h2 {
            font-size: 2.3rem;
            margin-bottom: 16px;
            color: var(--text-main);
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .thank-you-card p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 36px;
            line-height: 1.7;
            letter-spacing: -0.2px;
        }

        .btn-reload {
            display: inline-block;
            padding: 12px 28px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            border: 2px solid var(--primary-color);
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            letter-spacing: 0.2px;
        }

        .btn-reload:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Grid & Scale Styling */
        .grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 18px;
            border: 1.5px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }

        .grid-table th {
            background-color: #f7fafc;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 16px 12px;
            border-bottom: 1.5px solid var(--border-color);
        }

        .grid-table td {
            padding: 16px 12px;
            border-bottom: 1px solid var(--border-color);
            text-align: center;
        }

        .grid-table th:first-child,
        .grid-table td:first-child {
            text-align: left;
            font-weight: 500;
            color: var(--text-main);
            padding-left: 18px;
            background-color: #ffffff;
        }

        .grid-table tr:last-child td {
            border-bottom: none;
        }

        .grid-table tr:hover td {
            background-color: var(--primary-lighter);
        }

        .grid-table input[type="radio"],
        .grid-table input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary-color);
        }

        .scale-group {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 28px 0;
            max-width: 580px;
            margin: 0 auto;
            gap: 8px;
        }

        .scale-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            flex: 1;
        }

        .scale-item input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color);
        }

        .scale-item label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            font-weight: 500;
            letter-spacing: 0.1px;
        }

        .scale-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            padding-bottom: 8px;
            max-width: 90px;
            text-align: center;
            font-weight: 500;
        }

        /* File Upload Styling */
        .file-input-wrapper {
            position: relative;
            margin-top: 12px;
        }

        .file-input-wrapper input[type="file"] {
            display: block;
            width: 100%;
            padding: 12px 0;
            border: none;
            border-bottom: 2px solid var(--border-color);
            background: transparent;
            transition: all 0.25s ease;
        }

        .file-input-wrapper input[type="file"]:hover {
            border-bottom-color: #cbd5e0;
        }

        .file-input-wrapper input[type="file"]:focus {
            outline: none;
            border-bottom-color: var(--primary-color);
            box-shadow: 0 1px 0 0 var(--primary-color);
        }

        .file-input-wrapper small {
            color: var(--text-secondary);
            display: block;
            margin-top: 8px;
            font-size: 0.8rem;
            font-weight: 400;
            letter-spacing: 0.1px;
        }

        /* Animations */
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 20px 10px;
                background-image: linear-gradient(180deg, var(--primary-color) 0%, var(--primary-color) 160px, var(--bg-color) 160px);
            }

            .form-container {
                max-width: 100%;
            }

            .form-card-header,
            .question-card {
                padding: 24px 20px;
                border-radius: 10px;
                margin-bottom: 12px;
            }

            .form-card-header h1 {
                font-size: 1.8rem;
                margin-bottom: 10px;
            }

            .form-card-header p {
                font-size: 1rem;
                text-align: left;
                padding-bottom: 15px;
            }

            .question-title {
                font-size: 1.05rem;
                margin-bottom: 15px;
            }

            .submit-section {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
                text-align: center;
            }

            .submit-btn {
                width: 100%;
                padding: 16px;
                font-size: 1rem;
            }

            /* Grid Table Mobile Optimization */
            .grid-table {
                display: block;
                border: none;
            }

            .grid-table thead {
                display: none;
            }

            .grid-table tbody, .grid-table tr, .grid-table td {
                display: block;
                width: 100%;
            }

            .grid-table tr {
                border: 1px solid var(--border-color);
                border-radius: 10px;
                margin-bottom: 15px;
                background: #fff;
                box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            }

            .grid-table td {
                padding: 12px 15px;
                text-align: left;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid #f1f5f9;
            }

            .grid-table td:first-child {
                background-color: var(--primary-light);
                color: var(--primary-color);
                font-weight: 700;
                font-size: 1rem;
                border-radius: 10px 10px 0 0;
                padding: 12px 15px;
            }

            .grid-table td:last-child {
                border-bottom: none;
            }

            .grid-table td:not(:first-child)::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--text-secondary);
                font-size: 0.85rem;
                margin-right: 10px;
            }

            .grid-table input[type="radio"],
            .grid-table input[type="checkbox"] {
                margin: 0;
            }

            /* Linear Scale Mobile Optimization */
            .scale-group {
                flex-direction: column;
                align-items: stretch;
                gap: 5px;
                padding: 10px 0;
            }

            .scale-item {
                flex-direction: row;
                justify-content: space-between;
                padding: 12px 15px;
                background: #f8fafc;
                border-radius: 8px;
                border: 1px solid var(--border-color);
            }

            .scale-item label {
                font-size: 1rem;
                font-weight: 600;
                color: var(--text-main);
                order: 1;
            }

            .scale-item input {
                order: 2;
                width: 20px;
                height: 20px;
            }

            .scale-label {
                padding: 8px 15px;
                text-align: left;
                max-width: 100%;
                font-weight: 700;
                color: var(--primary-color);
                font-size: 0.8rem;
                text-transform: uppercase;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 15px 8px;
                background-image: linear-gradient(180deg, var(--primary-color) 0%, var(--primary-color) 140px, var(--bg-color) 140px);
            }

            .form-card-header,
            .question-card {
                padding: 20px 15px;
            }

            .form-card-header h1 {
                font-size: 1.5rem;
            }

            .thank-you-card {
                padding: 60px 20px;
            }

            .thank-you-card h2 {
                font-size: 1.8rem;
            }
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