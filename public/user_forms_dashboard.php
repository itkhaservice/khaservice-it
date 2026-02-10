<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// public/user_forms_dashboard.php
// This is the dedicated dashboard for 'user' roles, specifically for managing forms.

session_start(); // Start session
require_once __DIR__ . '/../includes/remember_me_check.php'; // Check for remember me cookie
require_once __DIR__ . '/../includes/auth.php'; // Handle authentication
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/messages.php'; // Include messages helper

// --- AUTHENTICATION AND AUTHORIZATION CHECK ---
// Only 'user' role can access this dashboard
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: login.php'); // Redirect to login if not logged in or not a 'user'
    exit;
}
// --- END AUTHENTICATION AND AUTHORIZATION CHECK ---

$page = $_GET['page'] ?? 'forms/list'; // Default to forms list for user dashboard
error_log("DEBUG: Initial page from GET: " . ($_GET['page'] ?? 'N/A'));
$page = preg_replace('/[^a-zA-Z0-9\/_.-]/', '', $page); // Sanitize
error_log("DEBUG: Sanitized page: " . $page);

// Ensure only forms module pages are accessible and not the API endpoint
if (strpos($page, 'forms/') !== 0 || $page === 'forms/api') { // If page doesn't start with 'forms/' OR it's the API
    error_log("DEBUG: Page forced to forms/list due to condition.");
    $page = 'forms/list'; // Force to forms list
}
error_log("DEBUG: Final page to include: " . $page);

$requested_file = __DIR__ . '/../modules/' . $page . '.php';
$base_path = realpath(__DIR__ . '/../modules');
$module_path = realpath($requested_file);

// --- KIỂM TRA NẾU LÀ TRANG EXPORT (KHÔNG HIỂN THỊ GIAO DIỆN) ---
$is_export = (strpos($page, 'export') !== false);

if (!$is_export) {
    // Include the standard header
    include_once __DIR__ . '/../includes/header.php';
    display_messages(); // Display any session messages
}

// --- Load Module Content ---
if ($module_path && strpos($module_path, $base_path) === 0 && file_exists($module_path)) {
    // No specific authorization needed here as auth is handled at the top
    // and forms module files already have their own ownership checks.
    include $module_path;
} else { 
    // Fallback if module file not found (shouldn't happen with forced 'forms/list')
    echo '<div class="alert alert-danger">Không tìm thấy trang yêu cầu.</div>';
}
// --- End Load Module Content ---

if (!$is_export) {
    // Include the standard footer
    include_once __DIR__ . '/../includes/footer.php';
}
?>