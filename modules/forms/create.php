<?php
// modules/forms/create.php
?>
<link rel="stylesheet" href="<?php echo $final_base; ?>assets/css/form_builder.css?v=<?php echo time(); ?>">

<script>
    function switchTab(btn, targetId) {
        document.querySelectorAll('.form-tab-btn').forEach(l => l.classList.remove('active'));
        document.querySelectorAll('.form-tab-content').forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        const target = document.getElementById(targetId);
        if (target) target.classList.add('active');
    }
</script>

<div class="form-module-container">
    <form action="user_forms_dashboard.php?page=forms/api&action=save_form" method="POST" id="create-form">
        <div class="form-page-header">
            <h2><i class="fas fa-plus-circle"></i> Thiết kế biểu mẫu mới</h2>
            <div class="header-actions">
                <a href="user_forms_dashboard.php?page=forms/list" class="btn-f btn-f-secondary"><i class="fas fa-times"></i> Hủy bỏ</a>
                <button type="submit" class="btn-f btn-f-primary"><i class="fas fa-save"></i> Lưu & Xuất bản</button>
            </div>
        </div>

        <div class="form-nav-tabs">
            <button type="button" class="form-tab-btn active" onclick="switchTab(this, 'tab-info')"><i class="fas fa-info-circle"></i> 1. THÔNG TIN CHUNG</button>
            <button type="button" class="form-tab-btn" onclick="switchTab(this, 'tab-questions')"><i class="fas fa-list-ul"></i> 2. NỘI DUNG CÂU HỎI</button>
            <button type="button" class="form-tab-btn" onclick="switchTab(this, 'tab-config')"><i class="fas fa-sliders-h"></i> 3. CÀI ĐẶT HỆ THỐNG</button>
        </div>
        
        <!-- TAB 1: THÔNG TIN CHUNG -->
        <div id="tab-info" class="form-tab-content active">
            <div class="form-card">
                <div class="form-card-title"><i class="fas fa-heading"></i> Cấu hình định danh biểu mẫu</div>
                <div class="form-group-f">
                    <label><i class="fas fa-tag"></i> Tiêu đề biểu mẫu <span style="color: #e53e3e;">*</span></label>
                    <input type="text" id="form_title" name="form_title" required placeholder="Ví dụ: Phiếu đăng ký tham gia sự kiện" style="font-size: 1.1rem;">
                </div>
                <div class="form-group-f" style="margin-top: 18px;">
                    <label><i class="fas fa-align-left"></i> Mô tả chi tiết hoặc Hướng dẫn</label>
                    <textarea id="form_description" name="form_description" rows="5" placeholder="Nhập lời chào hoặc các lưu ý cho người điền biểu mẫu..."></textarea>
                </div>
                <div style="padding: 16px; background: #f0fdf4; border-radius: 10px; border-left: 4px solid #108042; margin-top: 16px;">
                    <p style="margin: 0; font-size: 0.85rem; color: #0d6a35; font-weight: 500;">
                        <i class="fas fa-lightbulb"></i> Mẹo: Viết mô tả rõ ràng để giúp người khác hiểu được mục đích của biểu mẫu này.
                    </p>
                </div>
            </div>
        </div>

        <!-- TAB 2: NỘI DUNG CÂU HỎI -->
        <div id="tab-questions" class="form-tab-content">
            <div id="question-container">
                <div class="text-center" style="padding: 50px; border: 2.5px dashed var(--f-border); border-radius: 12px; background: #fff;">
                    <i class="fas fa-clipboard-question" style="font-size: 3rem; color: var(--f-border); margin-bottom: 15px; display: block;"></i>
                    <p class="text-muted" style="font-weight: 600; color: var(--f-text-light);">Danh sách câu hỏi đang trống. Hãy thêm câu hỏi đầu tiên!</p>
                </div>
            </div>
            <div style="margin-top: 20px; display: flex; gap: 12px;">
                <button type="button" id="add-question-btn" class="btn-f btn-f-primary" style="flex: 1;"><i class="fas fa-plus"></i> Thêm câu hỏi mới</button>
                <button type="button" class="quick-add-btn"><i class="fas fa-clone"></i> Nhân đôi câu hỏi phía trên</button>
            </div>
        </div>

        <!-- TAB 3: CÀI ĐẶT HỆ THỐNG -->
        <div id="tab-config" class="form-tab-content">
            <div class="form-card">
                <div class="form-card-title"><i class="fas fa-cogs"></i> Cài đặt vận hành & Giao diện</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group-f">
                        <label><i class="fas fa-toggle-on"></i> Trạng thái phát hành</label>
                        <select name="form_status" id="form_status">
                            <option value="draft" selected>Bản nháp (Chưa công khai)</option>
                            <option value="published">Công khai (Bắt đầu thu thập)</option>
                        </select>
                    </div>
                    <div class="form-group-f">
                        <label><i class="fas fa-calendar-times"></i> Thời hạn kết thúc</label>
                        <input type="datetime-local" name="expires_at" id="expires_at">
                    </div>
                    <div class="form-group-f">
                        <label><i class="fas fa-chart-bar"></i> Giới hạn số lượt phản hồi</label>
                        <input type="number" name="response_limit" id="response_limit" placeholder="Để trống nếu không giới hạn">
                    </div>
                    <div class="form-group-f">
                        <label><i class="fas fa-palette"></i> Màu sắc giao diện chủ đạo</label>
                        <input type="color" name="theme_color" id="theme_color" value="#108042" style="height: 42px; padding: 2px; cursor: pointer;">
                    </div>
                </div>
                <div class="form-group-f">
                    <label><i class="fas fa-smile"></i> Thông điệp sau khi hoàn tất (Cảm ơn)</label>
                    <textarea name="thank_you_message" id="thank_you_message" rows="3" placeholder="Ví dụ: Xin cảm ơn bạn đã dành thời gian hoàn thành phiếu khảo sát này!"></textarea>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    let finalBaseUrl = "<?php echo $final_base; ?>";
</script>
<script src="<?php echo $final_base; ?>assets/js/audio_feedback.js"></script>
<script src="<?php echo $final_base; ?>assets/js/form_builder.js"></script>