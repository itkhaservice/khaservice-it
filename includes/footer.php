</div>
    </main>
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand-info">
                    <span class="footer-logo-text">KHASERVICE IT</span>
                    <span class="footer-tagline">Hệ thống Quản lý Thiết bị & Công tác Nội bộ</span>
                </div>
                <div class="footer-copyright-text">
                    <p>&copy; <?php echo date('Y'); ?> All rights reserved.</p>
                    <p class="mobile-hide">Developed for KHASERVICE IT Department.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Shared Confirmation Modal -->
    <div id="customConfirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header" style="display:none;"> <!-- Hide header for cleaner look if icon is present -->
                <h3 id="modalTitle">Xác nhận hành động</h3>
                <span class="close-button">&times;</span>
            </div>
            <div class="modal-body text-center" style="padding: 30px;">
                <div class="modal-icon-wrapper danger" id="modalIcon" style="width: 60px; height: 60px; background: #fee2e2; color: #ef4444; border-radius: 50%; font-size: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 id="modalTitleDisplay" style="margin-bottom: 10px; font-weight: 700;">Xác nhận?</h3>
                <p id="modalMessage" style="color: #64748b; line-height: 1.5;">Bạn có chắc chắn muốn thực hiện hành động này?</p>
            </div>
            <div class="modal-footer" style="background: #f8fafc; padding: 15px 25px; display: flex; justify-content: center; gap: 15px; border-radius: 0 0 12px 12px;">
                <button id="cancelBtn" class="btn btn-secondary" style="min-width: 100px;">Hủy</button>
                <button id="confirmBtn" class="btn btn-danger" style="min-width: 100px;">Xác nhận</button>
            </div>
        </div>
    </div>

    <!-- Loading Spinner Overlay -->
    <div id="spinner-overlay" class="spinner-overlay">
        <div class="spinner"></div>
    </div>

    <script src="<?php echo $final_base; ?>assets/js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>