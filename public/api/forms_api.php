<?php
error_reporting(0); // Temporarily suppress all errors for debugging JSON output
ini_set('display_errors', 0); // Temporarily suppress display errors
session_start(); // Ensure session is started for this standalone API endpoint

header('Content-Type: application/json');

// Ensure DB is available
require_once '../../config/db.php';
require_once '../../includes/audit_helper.php';

$action = $_GET['action'] ?? null;

switch ($action) {
    case 'save_form':
        handle_save_form($pdo);
        break;
    case 'update_form':
        handle_update_form($pdo);
        break;
    case 'duplicate_form':
        handle_duplicate_form($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        exit;
        break;
}

function handle_save_form($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'POST method required.']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['title'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tiêu đề biểu mẫu là bắt buộc.']);
        exit;
    }

    // User must be logged in to save a form
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để lưu biểu mẫu.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $slug = create_slug($data['title']);
        $sql_form = "INSERT INTO forms (user_id, title, description, slug, status, expires_at, response_limit, theme_color, thank_you_message) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_form = $pdo->prepare($sql_form);
        $stmt_form->execute([
            $user_id, 
            $data['title'], 
            $data['description'], 
            $slug, 
            $data['status'], 
            !empty($data['expires_at']) ? $data['expires_at'] : null,
            !empty($data['response_limit']) ? (int)$data['response_limit'] : null,
            $data['theme_color'], 
            $data['thank_you_message'] ?? ''
        ]);
        $form_id = $pdo->lastInsertId();

        insert_questions_and_options($pdo, $form_id, $data['questions']);

        log_action($pdo, 'CREATE_FORM', 'forms', $form_id, "Title: " . $data['title']);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Biểu mẫu đã được lưu thành công!',
            'redirect_url' => 'user_forms_dashboard.php?page=forms/list' // Corrected redirect URL
        ]);
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        error_log("Form save error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
        exit;
    }
}

function handle_update_form($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'POST method required.']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $form_id = $data['id'] ?? null;

    if (empty($form_id) || empty($data['title'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Thiếu ID hoặc Tiêu đề biểu mẫu.']);
        exit;
    }

    // User must be logged in to update a form
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để cập nhật biểu mẫu.']);
        exit;
    }

    try {
        // Ownership Security Check - now only checks user_id
        $stmt = $pdo->prepare("SELECT user_id FROM forms WHERE id = ?");
        $stmt->execute([$form_id]);
        $form_owner = $stmt->fetch();

        if (!$form_owner || $form_owner['user_id'] !== $user_id) {
            http_response_code(403); // Forbidden
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền chỉnh sửa biểu mẫu này.']);
            exit;
        }

        $pdo->beginTransaction();

        // Update main form details
        $sql_form = "UPDATE forms SET title = ?, description = ?, status = ?, expires_at = ?, response_limit = ?, theme_color = ?, thank_you_message = ? WHERE id = ?";
        $stmt_form = $pdo->prepare($sql_form);
        $stmt_form->execute([
            $data['title'], 
            $data['description'], 
            $data['status'], 
            !empty($data['expires_at']) ? $data['expires_at'] : null,
            !empty($data['response_limit']) ? (int)$data['response_limit'] : null,
            $data['theme_color'], 
            $data['thank_you_message'] ?? '', 
            $form_id
        ]);

        // Get existing question IDs for this form
        $stmt_existing = $pdo->prepare("SELECT id FROM form_questions WHERE form_id = ?");
        $stmt_existing->execute([$form_id]);
        $existing_q_ids = $stmt_existing->fetchAll(PDO::FETCH_COLUMN);

        $submitted_q_ids = [];
        foreach ($data['questions'] as $q_order => $question) {
            if (!empty($question['id'])) {
                $submitted_q_ids[] = (int)$question['id'];
                // Update existing question
                $sql_update_q = "UPDATE form_questions SET question_text = ?, question_type = ?, is_required = ?, question_order = ?, logic_config = ? WHERE id = ? AND form_id = ?";
                $stmt_update_q = $pdo->prepare($sql_update_q);
                $stmt_update_q->execute([
                    $question['title'],
                    $question['type'],
                    $question['is_required'] ? 1 : 0,
                    $q_order,
                    $question['logic_config'] ?? null,
                    $question['id'],
                    $form_id
                ]);
                $question_id = $question['id'];
            } else {
                // Insert new question
                $sql_insert_q = "INSERT INTO form_questions (form_id, question_text, question_type, is_required, question_order, logic_config) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_insert_q = $pdo->prepare($sql_insert_q);
                $stmt_insert_q->execute([
                    $form_id,
                    $question['title'],
                    $question['type'],
                    $question['is_required'] ? 1 : 0,
                    $q_order,
                    $question['logic_config'] ?? null
                ]);
                $question_id = $pdo->lastInsertId();
            }

            // Update options: For simplicity, we still recreate options for each question
            // because options usually don't have their own IDs in this simple implementation
            $stmt_del_opts = $pdo->prepare("DELETE FROM question_options WHERE question_id = ?");
            $stmt_del_opts->execute([$question_id]);

            if (!empty($question['options']) && is_array($question['options'])) {
                $sql_option = "INSERT INTO question_options (question_id, option_text, option_type, option_order) VALUES (?, ?, ?, ?)";
                $stmt_option = $pdo->prepare($sql_option);
                foreach ($question['options'] as $o_order => $option) {
                    $option_text = is_array($option) ? $option['text'] : $option;
                    $option_type = is_array($option) ? ($option['type'] ?? 'choice') : 'choice';
                    $stmt_option->execute([$question_id, $option_text, $option_type, $o_order]);
                }
            }
        }

        // Soft delete questions that were removed in the UI
        $qs_to_delete = array_diff($existing_q_ids, $submitted_q_ids);
        if (!empty($qs_to_delete)) {
            $placeholders = implode(',', array_fill(0, count($qs_to_delete), '?'));
            $stmt_delete_qs = $pdo->prepare("UPDATE form_questions SET deleted_at = NOW() WHERE id IN ($placeholders) AND form_id = ?");
            $params = array_values($qs_to_delete);
            $params[] = $form_id;
            $stmt_delete_qs->execute($params);
        }

        log_action($pdo, 'UPDATE_FORM', 'forms', $form_id, "Updated title or structure.");

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Biểu mẫu đã được cập nhật thành công!',
            'redirect_url' => 'user_forms_dashboard.php?page=forms/list' // Corrected redirect URL
        ]);
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        error_log("Form update error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
        exit;
    }
}

function insert_questions_and_options($pdo, $form_id, $questions) {
    if (empty($questions) || !is_array($questions)) return;

    $sql_question = "INSERT INTO form_questions (form_id, question_text, question_type, is_required, question_order, logic_config) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_question = $pdo->prepare($sql_question);

    $sql_option = "INSERT INTO question_options (question_id, option_text, option_type, option_order) VALUES (?, ?, ?, ?)";
    $stmt_option = $pdo->prepare($sql_option);

    foreach ($questions as $q_order => $question) {
        $stmt_question->execute([
            $form_id,
            $question['title'],
            $question['type'],
            $question['is_required'] ? 1 : 0,
            $q_order,
            $question['logic_config'] ?? null
        ]);
        $question_id = $pdo->lastInsertId();

        if (!empty($question['options']) && is_array($question['options'])) {
            foreach ($question['options'] as $o_order => $option) {
                $option_text = is_array($option) ? $option['text'] : $option;
                $option_type = is_array($option) ? ($option['type'] ?? 'choice') : 'choice';
                $stmt_option->execute([$question_id, $option_text, $option_type, $o_order]);
            }
        }
    }
}

function create_slug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    if (empty($text)) return 'n-a-' . substr(md5(time()), 0, 6);
    return $text . '-' . substr(md5(time()), 0, 4);
}

function handle_duplicate_form($pdo) {
    $form_id = $_GET['id'] ?? null;
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$form_id || !$user_id) {
        echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Fetch original form
        $stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ? AND user_id = ?");
        $stmt->execute([$form_id, $user_id]);
        $original = $stmt->fetch();

        if (!$original) throw new Exception("Không tìm thấy biểu mẫu gốc.");

        // 2. Clone form record
        $new_title = $original['title'] . " (Bản sao)";
        $new_slug = create_slug($new_title);
        
        $sql_clone = "INSERT INTO forms (user_id, title, description, slug, status, expires_at, response_limit, theme_color, thank_you_message) 
                      VALUES (?, ?, ?, ?, 'draft', ?, ?, ?, ?)";
        $stmt_clone = $pdo->prepare($sql_clone);
        $stmt_clone->execute([
            $user_id, $new_title, $original['description'], $new_slug, 
            $original['expires_at'], $original['response_limit'], 
            $original['theme_color'], $original['thank_you_message']
        ]);
        $new_form_id = $pdo->lastInsertId();

        // 3. Clone questions and options
        $stmt_qs = $pdo->prepare("SELECT * FROM form_questions WHERE form_id = ? AND deleted_at IS NULL ORDER BY question_order ASC");
        $stmt_qs->execute([$form_id]);
        $old_questions = $stmt_qs->fetchAll();

        foreach ($old_questions as $q) {
            $sql_q = "INSERT INTO form_questions (form_id, question_text, question_type, question_order, is_required) VALUES (?, ?, ?, ?, ?)";
            $stmt_q = $pdo->prepare($sql_q);
            $stmt_q->execute([$new_form_id, $q['question_text'], $q['question_type'], $q['question_order'], $q['is_required']]);
            $new_q_id = $pdo->lastInsertId();

            // Clone options
            $stmt_opts = $pdo->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY option_order ASC");
            $stmt_opts->execute([$q['id']]);
            $old_opts = $stmt_opts->fetchAll();

            foreach ($old_opts as $o) {
                $sql_o = "INSERT INTO question_options (question_id, option_text, option_type, option_order) VALUES (?, ?, ?, ?)";
                $stmt_o = $pdo->prepare($sql_o);
                $stmt_o->execute([$new_q_id, $o['option_text'], $o['option_type'], $o['option_order']]);
            }
        }

        log_action($pdo, 'DUPLICATE_FORM', 'forms', $new_form_id, "Cloned from ID: " . $form_id);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Đã sao chép biểu mẫu thành công!', 'redirect_url' => 'user_forms_dashboard.php?page=forms/list']);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}