</div>
    </main>
    <footer class="main-footer">
        <p>&copy; <?php echo date('Y'); ?> KHASERVICE IT. All rights reserved.</p>
    </footer>

    <!-- Shared Confirmation Modal (Moved here for better positioning) -->
    <div id="customConfirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Xác nhận hành động</h3>
                <span class="close-button">&times;</span>
            </div>
            <div class="modal-body">
                <p id="modalMessage">Bạn có chắc chắn muốn thực hiện hành động này?</p>
            </div>
            <div class="modal-footer">
                <button id="cancelBtn" class="btn btn-secondary">Hủy</button>
                <button id="confirmBtn" class="btn btn-danger">Xác nhận</button>
            </div>
        </div>
    </div>

    <!-- Loading Spinner Overlay -->
    <div id="spinner-overlay" class="spinner-overlay">
        <div class="spinner"></div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>