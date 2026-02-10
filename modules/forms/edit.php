<?php
// modules/forms/edit.php
$form_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM forms WHERE id = ? AND user_id = ?");
$stmt->execute([$form_id, $user_id]);
$form = $stmt->fetch();
if (!$form) die("Yêu cầu không hợp lệ.");

$sql_questions = "SELECT q.*, GROUP_CONCAT(CONCAT(o.option_text, ':::', IFNULL(o.option_type, 'choice')) ORDER BY o.option_order ASC SEPARATOR '|||') as options_with_type
                  FROM form_questions q LEFT JOIN question_options o ON q.id = o.question_id
                  WHERE q.form_id = ? AND q.deleted_at IS NULL GROUP BY q.id ORDER BY q.question_order ASC";
$stmt_questions = $pdo->prepare($sql_questions);
$stmt_questions->execute([$form_id]);
$questions_data = $stmt_questions->fetchAll();

$existing_form_data = [
    'id' => $form['id'], 'title' => $form['title'], 'description' => $form['description'], 'status' => $form['status'],
    'theme_color' => $form['theme_color'], 'thank_you_message' => $form['thank_you_message'] ?? '',
    'questions' => array_map(function($q) {
        $options = [];
        if ($q['options_with_type']) {
            foreach (explode('|||', $q['options_with_type']) as $opt_str) {
                $parts = explode(':::', $opt_str);
                $options[] = ['text' => $parts[0] ?? '', 'type' => $parts[1] ?? 'choice'];
            }
        }
        return ['id' => $q['id'], 'title' => $q['question_text'], 'type' => $q['question_type'], 'is_required' => (bool)$q['is_required'], 'logic_config' => $q['logic_config'], 'options' => $options];
    }, $questions_data)
];
?>
<link rel="stylesheet" href="<?php echo $final_base; ?>assets/css/form_builder.css?v=<?php echo time(); ?>">

<script>
    function switchTab(btn, targetId) {
        // Remove active class from all buttons and contents
        document.querySelectorAll('.form-tab-btn').forEach(l => l.classList.remove('active'));
        document.querySelectorAll('.form-tab-content').forEach(c => c.classList.remove('active'));
        
        // Add active class to clicked button and target content
        btn.classList.add('active');
        const target = document.getElementById(targetId);
        if (target) target.classList.add('active');
    }
</script>

<div class="form-module-container">
    <div class="form-page-header">
        <h2><i class="fas fa-edit"></i> Chỉnh sửa thiết kế biểu mẫu</h2>
        <div class="header-actions">
            <a href="user_forms_dashboard.php?page=forms/list" class="btn-f btn-f-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
            <button type="submit" form="edit-form" class="btn-f btn-f-primary"><i class="fas fa-save"></i> Cập nhật thiết kế</button>
        </div>
    </div>

    <div class="form-nav-tabs">
        <button type="button" class="form-tab-btn active" onclick="switchTab(this, 'tab-info')"><i class="fas fa-info-circle"></i> 1. THÔNG TIN CHUNG</button>
        <button type="button" class="form-tab-btn" onclick="switchTab(this, 'tab-questions')"><i class="fas fa-list-ul"></i> 2. NỘI DUNG CÂU HỎI</button>
        <button type="button" class="form-tab-btn" onclick="switchTab(this, 'tab-config')"><i class="fas fa-sliders-h"></i> 3. CÀI ĐẶT HỆ THỐNG</button>
    </div>

    <form action="user_forms_dashboard.php?page=forms/api&action=update_form" method="POST" id="edit-form">
        <input type="hidden" name="form_id" value="<?php echo $form['id']; ?>">
        
        <div id="tab-info" class="form-tab-content active">
            <div class="form-card">
                <div class="form-card-title"><i class="fas fa-heading"></i> Tiêu đề & Mô tả hiện tại</div>
                <div class="form-group-f">
                    <label><i class="fas fa-tag"></i> Tiêu đề biểu mẫu <span style="color: #e53e3e;">*</span></label>
                    <input type="text" id="form_title" name="form_title" required value="<?php echo htmlspecialchars($form['title']); ?>" style="font-size: 1.1rem;">
                </div>
                <div class="form-group-f" style="margin-top: 18px;">
                    <label><i class="fas fa-align-left"></i> Mô tả chi tiết hoặc Hướng dẫn</label>
                    <textarea id="form_description" name="form_description" rows="5"><?php echo htmlspecialchars($form['description'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <div id="tab-questions" class="form-tab-content">
            <div id="question-container">
                <div class="text-center" style="padding: 50px; border: 2.5px dashed var(--f-border); border-radius: 12px; background: #fff;">
                    <i class="fas fa-clipboard-question" style="font-size: 3rem; color: var(--f-border); margin-bottom: 15px; display: block;"></i>
                    <p class="text-muted" style="font-weight: 600;">Danh sách câu hỏi đang trống. Hãy thêm câu hỏi đầu tiên!</p>
                </div>
            </div>
            <div style="margin-top: 20px; display: flex; gap: 12px;">
                <button type="button" id="add-question-btn" class="btn-f btn-f-primary" style="flex: 1;"><i class="fas fa-plus"></i> Thêm câu hỏi mới</button>
                <button type="button" class="quick-add-btn"><i class="fas fa-clone"></i> Nhân đôi câu hỏi phía trên</button>
            </div>
        </div>

        <div id="tab-config" class="form-tab-content">
            <div class="form-card">
                <div class="form-card-title"><i class="fas fa-cogs"></i> Quản lý trạng thái & Giao diện</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group-f">
                        <label><i class="fas fa-toggle-on"></i> Trạng thái vận hành</label>
                        <select name="form_status" id="form_status">
                            <option value="draft" <?php echo $form['status']=='draft'?'selected':''; ?>>Bản nháp</option>
                            <option value="published" <?php echo $form['status']=='published'?'selected':''; ?>>Công khai</option>
                        </select>
                    </div>
                    <div class="form-group-f">
                        <label><i class="fas fa-calendar-times"></i> Ngày hết hạn</label>
                        <input type="datetime-local" name="expires_at" id="expires_at" value="<?php echo $form['expires_at']?date('Y-m-d\TH:i',strtotime($form['expires_at'])):''; ?>">
                    </div>
                    <div class="form-group-f">
                        <label><i class="fas fa-chart-bar"></i> Giới hạn phản hồi</label>
                        <input type="number" name="response_limit" id="response_limit" value="<?php echo $form['response_limit']; ?>">
                    </div>
                    <div class="form-group-f">
                        <label><i class="fas fa-palette"></i> Màu sắc giao diện</label>
                        <input type="color" name="theme_color" id="theme_color" value="<?php echo $form['theme_color']; ?>" style="height: 42px; padding: 2px; border: 1.5px solid var(--f-border);">
                    </div>
                </div>
                <div class="form-group-f">
                    <label><i class="fas fa-smile"></i> Thông điệp kết thúc (Cảm ơn)</label>
                    <textarea name="thank_you_message" id="thank_you_message" rows="3"><?php echo htmlspecialchars($form['thank_you_message'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    let existingFormData = <?php echo json_encode($existing_form_data, JSON_UNESCAPED_UNICODE); ?>;
    let finalBaseUrl = "<?php echo $final_base; ?>";
</script>
<script src="<?php echo $final_base; ?>assets/js/audio_feedback.js"></script>
<script src="<?php echo $final_base; ?>assets/js/form_builder.js"></script>