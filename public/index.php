<?php
session_start(); // Start session once at the top
require_once __DIR__ . '/../includes/remember_me_check.php'; // Check for remember me cookie
require_once __DIR__ . '/../includes/auth.php'; // Handle authentication
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/messages.php'; // Include messages helper

// The header already starts the session and has the necessary HTML boilerplate
include_once __DIR__ . '/../includes/header.php';

// Display messages
display_messages(); // Call to display messages

// Simple router based on 'page' GET parameter
$page = $_GET['page'] ?? 'home';

// Sanitize the page parameter to prevent directory traversal.
// We allow a-z, A-Z, 0-9, and the forward slash '/' for subdirectories.
$page = preg_replace('/[^a-zA-Z0-9\/_.-]/', '', $page); // Allow safe characters

// Construct the full path
$requested_file = __DIR__ . '/../modules/' . $page . '.php';

// Normalize paths and check if the requested file is within the modules directory
$base_path = realpath(__DIR__ . '/../modules');
$module_path = realpath($requested_file);

// Check if the resolved path is inside the modules directory and the file exists
if ($module_path === false || strpos($module_path, $base_path) !== 0) {
    // If not, invalidate the path to trigger the 'else' block.
    $module_path = false;
}

if ($module_path && file_exists($module_path)) {
    include $module_path;
} else {
    // Show a default home/dashboard page if the page is not found or on first load
    echo "<h1>Chào mừng, " . htmlspecialchars($_SESSION['username']) . "!</h1>";
    echo "<p>Sử dụng thanh điều hướng để quản lý các mục.</p>";
}

// Custom Confirmation Modal HTML
?>
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
            <button id="confirmBtn" class="btn btn-danger">Xác nhận</button>
            <button id="cancelBtn" class="btn btn-secondary">Hủy</button>
        </div>
    </div>
</div>

<?php
include_once __DIR__ . '/../includes/footer.php';
