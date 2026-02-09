<?php
error_reporting(0); // Temporarily suppress all errors for debugging JSON output
ini_set('display_errors', 0); // Temporarily suppress display errors
session_start(); // Ensure session is started for this standalone API endpoint

header('Content-Type: application/json');

// Ensure DB is available
require_once '../../config/db.php';


$action = $_GET['action'] ?? null;

switch ($action) {
    case 'save_form':
        handle_save_form($pdo);
        break;
    case 'update_form':
        handle_update_form($pdo);
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
        $sql_form = "INSERT INTO forms (user_id, title, description, slug, status, theme_color, thank_you_message) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_form = $pdo->prepare($sql_form);
        $stmt_form->execute([$user_id, $data['title'], $data['description'], $slug, $data['status'], $data['theme_color'], $data['thank_you_message'] ?? '']);
        $form_id = $pdo->lastInsertId();

        insert_questions_and_options($pdo, $form_id, $data['questions']);

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
        $sql_form = "UPDATE forms SET title = ?, description = ?, status = ?, theme_color = ?, thank_you_message = ? WHERE id = ?";
        $stmt_form = $pdo->prepare($sql_form);
        $stmt_form->execute([$data['title'], $data['description'], $data['status'], $data['theme_color'], $data['thank_you_message'] ?? '', $form_id]);

        // Get existing question IDs for this form
        $stmt_existing = $pdo->prepare("SELECT id FROM form_questions WHERE form_id = ?");
        $stmt_existing->execute([$form_id]);
        $existing_q_ids = $stmt_existing->fetchAll(PDO::FETCH_COLUMN);

        $submitted_q_ids = [];
        foreach ($data['questions'] as $q_order => $question) {
            if (!empty($question['id'])) {
                $submitted_q_ids[] = (int)$question['id'];
                // Update existing question
                $sql_update_q = "UPDATE form_questions SET question_text = ?, question_type = ?, is_required = ?, question_order = ? WHERE id = ? AND form_id = ?";
                $stmt_update_q = $pdo->prepare($sql_update_q);
                $stmt_update_q->execute([
                    $question['title'],
                    $question['type'],
                    $question['is_required'] ? 1 : 0,
                    $q_order,
                    $question['id'],
                    $form_id
                ]);
                $question_id = $question['id'];
            } else {
                // Insert new question
                $sql_insert_q = "INSERT INTO form_questions (form_id, question_text, question_type, is_required, question_order) VALUES (?, ?, ?, ?, ?)";
                $stmt_insert_q = $pdo->prepare($sql_insert_q);
                $stmt_insert_q->execute([
                    $form_id,
                    $question['title'],
                    $question['type'],
                    $question['is_required'] ? 1 : 0,
                    $q_order
                ]);
                $question_id = $pdo->lastInsertId();
            }

            // Update options: For simplicity, we still recreate options for each question
            // because options usually don't have their own IDs in this simple implementation
            $stmt_del_opts = $pdo->prepare("DELETE FROM question_options WHERE question_id = ?");
            $stmt_del_opts->execute([$question_id]);

            if (!empty($question['options']) && is_array($question['options'])) {
                $sql_option = "INSERT INTO question_options (question_id, option_text, option_order) VALUES (?, ?, ?)";
                $stmt_option = $pdo->prepare($sql_option);
                foreach ($question['options'] as $o_order => $option_text) {
                    $stmt_option->execute([$question_id, $option_text, $o_order]);
                }
            }
        }

        // Delete questions that were removed in the UI
        $qs_to_delete = array_diff($existing_q_ids, $submitted_q_ids);
        if (!empty($qs_to_delete)) {
            $placeholders = implode(',', array_fill(0, count($qs_to_delete), '?'));
            $stmt_delete_qs = $pdo->prepare("DELETE FROM form_questions WHERE id IN ($placeholders) AND form_id = ?");
            $params = array_values($qs_to_delete);
            $params[] = $form_id;
            $stmt_delete_qs->execute($params);
        }

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

    $sql_question = "INSERT INTO form_questions (form_id, question_text, question_type, is_required, question_order) VALUES (?, ?, ?, ?, ?)";
    $stmt_question = $pdo->prepare($sql_question);

    $sql_option = "INSERT INTO question_options (question_id, option_text, option_order) VALUES (?, ?, ?)";
    $stmt_option = $pdo->prepare($sql_option);

    foreach ($questions as $q_order => $question) {
        $stmt_question->execute([
            $form_id,
            $question['title'],
            $question['type'],
            $question['is_required'] ? 1 : 0,
            $q_order
        ]);
        $question_id = $pdo->lastInsertId();

        if (!empty($question['options']) && is_array($question['options'])) {
            foreach ($question['options'] as $o_order => $option_text) {
                $stmt_option->execute([$question_id, $option_text, $o_order]);
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