<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// File: modules/forms/create.php
// Giao diện chính để người dùng thiết kế biểu mẫu mới.
?>

<div class="page-header">
    <h2><i class="fas fa-plus-circle"></i> Tạo Biểu mẫu mới</h2>
    <div class="header-actions">
        <a href="user_forms_dashboard.php?page=forms/list" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
    </div>
</div>

<form action="user_forms_dashboard.php?page=forms/api&action=save_form" method="POST" id="create-form" class="edit-layout">
    
    <!-- Left Panel: Form Content -->
    <div class="left-panel">
        <div class="card">
            <div class="card-header-custom">
                <h3><i class="fas fa-file-alt"></i> Nội dung Biểu mẫu</h3>
            </div>
            <div class="card-body-custom">
                <div class="form-group">
                    <label for="form_title">Tiêu đề Biểu mẫu <span class="required">*</span></label>
                    <input type="text" id="form_title" name="form_title" required class="input-highlight" placeholder="VD: Khảo sát mức độ hài lòng của nhân viên">
                </div>
                
                <div class="form-group">
                    <label for="form_description">Mô tả</label>
                    <textarea id="form_description" name="form_description" rows="3" placeholder="Thêm mô tả chi tiết cho biểu mẫu của bạn..."></textarea>
                </div>
            </div>
        </div>

        <div class="card">
             <div class="card-header-custom">
                <h3><i class="fas fa-tasks"></i> Các câu hỏi</h3>
            </div>
            <div class="card-body-custom" id="question-container">
                <p class="text-muted">Chưa có câu hỏi nào. Nhấn "Thêm Câu hỏi" để bắt đầu.</p>
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
                        <option value="draft" selected>Bản nháp</option>
                        <option value="published">Phát hành</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="theme_color">Màu chủ đạo</label>
                    <input type="color" id="theme_color" name="theme_color" value="#108042">
                </div>
                <div class="form-group">
                    <label for="thank_you_message">Lời cảm ơn sau khi gửi</label>
                    <textarea id="thank_you_message" name="thank_you_message" rows="2" placeholder="VD: Cảm ơn bạn đã dành thời gian phản hồi!"></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions-bottom">
        <button type="submit" class="btn btn-primary btn-full-width"><i class="fas fa-save"></i> Lưu Biểu mẫu</button>
    </div>
</form>

<script>
    let finalBaseUrl = "<?php echo $final_base; ?>";
</script>
<script src="<?php echo $final_base; ?>assets/js/audio_feedback.js"></script>

<script src="<?php echo $final_base; ?>assets/js/form_builder.js"></script>

<link rel="stylesheet" href="<?php echo $final_base; ?>assets/css/form_builder.css?v=<?php echo time(); ?>">


