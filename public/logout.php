<?php
session_start();
require '../config/db.php';

// Clear remember me token from DB and cookie
if (isset($_COOKIE['remember_me'])) {
    $pdo->prepare("DELETE FROM auth_tokens WHERE token = ?")
        ->execute([$_COOKIE['remember_me']]);

    setcookie('remember_me', '', time() - 3600, '/'); // Expire the cookie
}

// Unset all of the session variables.
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

header('Location: login.php');
exit;
