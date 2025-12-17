<?php
// Main router
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/messages.php'; // Include messages helper

// The header already starts the session and has the necessary HTML boilerplate
include_once __DIR__ . '/../includes/header.php';

// Display messages
display_messages(); // Call to display messages

// Simple router based on 'page' GET parameter
$page = $_GET['page'] ?? 'home';

// Sanitize the page parameter to prevent directory traversal
$page = str_replace(['/', '\\', '.'], '', $page);

$module_path = __DIR__ . '/../modules/' . $page . '.php';

if (file_exists($module_path)) {
    include $module_path;
} else {
    // Show a default home/dashboard page if the page is not found or on first load
    echo "<h1>Chào mừng, " . htmlspecialchars($_SESSION['username']) . "!</h1>";
    echo "<p>Sử dụng thanh điều hướng để quản lý các mục.</p>";
}

include_once __DIR__ . '/../includes/footer.php';
