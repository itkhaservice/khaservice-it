<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// File: modules/forms/edit.php
// Giao diện để chỉnh sửa một biểu mẫu đã có.

$form_id = $_GET['id'] ?? null;
if (!$form_id || !is_numeric($form_id)) {
    die("ID Biểu mẫu không hợp lệ.");
}
$form_id = (int)$form_id;

// === SECURITY CHECK ===
$user_id = $_SESSION['user_id']; // Get current logged-in user ID

$stmt = $pdo->prepare("SELECT * FROM forms WHERE id = :form_id");
$stmt->execute([':form_id' => $form_id]);
$form = $stmt->fetch();

if (!$form) {
    die("Biểu mẫu không tồn tại.");
}

// Ownership check: form's user_id must match logged-in user_id
if ($form['user_id'] !== $user_id) {
    die("Bạn không có quyền chỉnh sửa biểu mẫu này.");
}
// === END SECURITY CHECK ===

// Fetch all questions and their options
$sql_questions = "
    SELECT q.*, GROUP_CONCAT(o.option_text ORDER BY o.option_order ASC SEPARATOR '|||') as options
    FROM form_questions q
    LEFT JOIN question_options o ON q.id = o.question_id
    WHERE q.form_id = ?
    GROUP BY q.id
    ORDER BY q.question_order ASC
";
$stmt_questions = $pdo->prepare($sql_questions);
$stmt_questions->execute([$form_id]);
$questions_data = $stmt_questions->fetchAll();

// Prepare data for JavaScript
$existing_form_data = [
    'id' => $form['id'],
    'title' => $form['title'],
    'description' => $form['description'],
    'status' => $form['status'],
    'theme_color' => $form['theme_color'],
    'questions' => array_map(function($q) {
        return [
            'title' => $q['question_text'],
            'type' => $q['question_type'],
            'is_required' => (bool)$q['is_required'],
            'options' => $q['options'] ? explode('|||', $q['options']) : []
        ];
    }, $questions_data)
];
?>

<div class="page-header">
    <h2><i class="fas fa-edit"></i> Chỉnh sửa Biểu mẫu</h2>
    <div class="header-actions">
        <a href="user_forms_dashboard.php?page=forms/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
    </div>
</div>

<form action="user_forms_dashboard.php?page=forms/api&action=update_form" method="POST" id="edit-form" class="edit-layout">
    <input type="hidden" name="form_id" value="<?php echo $form_id; ">
    
    <!-- Left Panel: Form Content -->
    <div class="left-panel">
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-file-alt"></i> Nội dung Biểu mẫu</h3>
            </div>
            <div class="card-body-custom">
                <div class="form-group">
                    <label for="form_title">Tiêu đề Biểu mẫu <span class="required">*</span></label>
                    <input type="text" id="form_title" name="form_title" required class="input-highlight" placeholder="VD: Khảo sát mức độ hài lòng" value="<?php echo htmlspecialchars($form['title']); ">
                </div>
                
                <div class="form-group">
                    <label for="form_description">Mô tả</label>
                    <textarea id="form_description" name="form_description" rows="3" placeholder="Thêm mô tả..."><?php echo htmlspecialchars($form['description']); ?></textarea>
                </div>
            </div>
        </div>

        <div class="card">
             <div class="card-header-custom">
                <h3><i class="fas fa-tasks"></i> Các câu hỏi</h3>
            </div>
            <div class="card-body-custom" id="question-container">
                <!-- Questions will be dynamically populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Right Panel: Settings & Controls -->
    <div class="right-panel">
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-tools"></i> Điều khiển</h3>
            </div>
            <div class="card-body-custom">
                <button type="button" id="add-question-btn" class="btn btn-success btn-full-width"><i class="fas fa-plus"></i> Thêm Câu hỏi</button>
            </div>
        </div>
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-cog"></i> Cài đặt</h3>
            </div>
            <div class="card-body-custom">
                 <div class="form-group">
                    <label for="form_status">Trạng thái</label>
                    <select id="form_status" name="form_status">
                        <option value="draft" <?php echo $form['status'] == 'draft' ? 'selected' : ''; ?>>Bản nháp</option>
                        <option value="published" <?php echo $form['status'] == 'published' ? 'selected' : ''; ?>>Phát hành</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="theme_color">Màu chủ đạo</label>
                    <input type="color" id="theme_color" name="theme_color" value="<?php echo htmlspecialchars($form['theme_color']); ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions-bottom">
        <button type="submit" class="btn btn-primary btn-full-width"><i class="fas fa-save"></i> Cập nhật Biểu mẫu</button>
    </div>
</form>
    <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
    
    <!-- Left Panel: Form Content -->
    <div class="left-panel">
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-file-alt"></i> Nội dung Biểu mẫu</h3>
            </div>
            <div class="card-body-custom">
                <div class="form-group">
                    <label for="form_title">Tiêu đề Biểu mẫu <span class="required">*</span></label>
                    <input type="text" id="form_title" name="form_title" required class="input-highlight" placeholder="VD: Khảo sát mức độ hài lòng" value="<?php echo htmlspecialchars($form['title']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="form_description">Mô tả</label>
                    <textarea id="form_description" name="form_description" rows="3" placeholder="Thêm mô tả..."><?php echo htmlspecialchars($form['description']); ?></textarea>
                </div>
            </div>
        </div>

        <div class="card">
             <div class="card-header-custom">
                <h3><i class="fas fa-tasks"></i> Các câu hỏi</h3>
            </div>
            <div class="card-body-custom" id="question-container">
                <!-- Questions will be dynamically populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Right Panel: Settings & Controls -->
    <div class="right-panel">
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-tools"></i> Điều khiển</h3>
            </div>
            <div class="card-body-custom">
                <button type="button" id="add-question-btn" class="btn btn-success btn-full-width"><i class="fas fa-plus"></i> Thêm Câu hỏi</button>
            </div>
        </div>
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-cog"></i> Cài đặt</h3>
            </div>
            <div class="card-body-custom">
                 <div class="form-group">
                    <label for="form_status">Trạng thái</label>
                    <select id="form_status" name="form_status">
                        <option value="draft" <?php echo $form['status'] == 'draft' ? 'selected' : ''; ?>>Bản nháp</option>
                        <option value="published" <?php echo $form['status'] == 'published' ? 'selected' : ''; ?>>Phát hành</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="theme_color">Màu chủ đạo</label>
                    <input type="color" id="theme_color" name="theme_color" value="<?php echo htmlspecialchars($form['theme_color']); ?>">
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    // Pass PHP data to JavaScript
    let existingFormData = <?php echo json_encode($existing_form_data, JSON_UNESCAPED_UNICODE); ?>;
    let finalBaseUrl = "<?php echo $final_base; ?>";
</script>
<script src="<?php echo $final_base; ?>assets/js/audio_feedback.js"></script>
<script src="<?php echo $final_base; ?>assets/js/form_builder.js"></script>
<link rel="stylesheet" href="<?php echo $final_base; ?>assets/css/form_builder.css?v=<?php echo time(); ?>">