<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// File: modules/forms/delete.php
// Xử lý logic xóa một biểu mẫu.

// Ensure user is logged in and is a 'Guest' role (handled by user_forms_dashboard.php)
// Ensure $pdo is available (handled by user_forms_dashboard.php)

$form_id = $_GET['id'] ?? null;

if (!$form_id || !is_numeric($form_id)) {
    set_message('error', 'ID Biểu mẫu không hợp lệ.');
    header('Location: user_forms_dashboard.php?page=forms/list');
    exit;
}

$form_id = (int)$form_id;
$user_id = $_SESSION['user_id'];

try {
    // Security check: Ensure the logged-in user owns this form
    $stmt = $pdo->prepare("SELECT user_id FROM forms WHERE id = ?");
    $stmt->execute([$form_id]);
    $form_owner = $stmt->fetchColumn();

    if (!$form_owner || $form_owner !== $user_id) {
        set_message('error', 'Bạn không có quyền xóa biểu mẫu này.');
        header('Location: user_forms_dashboard.php?page=forms/list');
        exit;
    }

    // Delete the form
    require_once __DIR__ . '/../../includes/audit_helper.php';
    log_action($pdo, 'DELETE_FORM', 'forms', $form_id, "Form ID: " . $form_id);

    // ON DELETE CASCADE will handle deleting associated questions, options, submissions, and answers
    $stmt_delete = $pdo->prepare("DELETE FROM forms WHERE id = ?");
    $stmt_delete->execute([$form_id]);

    set_message('success', 'Biểu mẫu đã được xóa thành công!');
    header('Location: user_forms_dashboard.php?page=forms/list');
    exit;

} catch (PDOException $e) {
    set_message('error', 'Lỗi khi xóa biểu mẫu: ' . $e->getMessage());
    error_log("Form deletion error: " . $e->getMessage());
    header('Location: user_forms_dashboard.php?page=forms/list');
    exit;
}