<?php
// This file checks for a "Remember Me" cookie and logs in the user if valid.

if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    require_once '../config/db.php'; // Ensure PDO connection is available

    $token_from_cookie = $_COOKIE['remember_me'];

    if ($token_from_cookie) {
        $stmt = $pdo->prepare("SELECT * FROM auth_tokens WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token_from_cookie]);
        $token_record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($token_record) {
            // Token is valid, log the user in
            $user_stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
            $user_stmt->execute([$token_record['user_id']]);
            $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

                            if ($user) {
                                // Start session explicitly if not already started
                                // Removed redundant session_start() as it's handled in public/index.php
            
                                $_SESSION['user_id'] = $user['id'];
                                $_SESSION['username'] = $user['username'];
                                $_SESSION['role'] = $user['role'];
            
                                // Regenerate session ID for security
                                session_regenerate_id(true);
            
                                // --- Security: Generate a new token and cookie ---
                                // For a single token approach, regenerate the token to prevent replay attacks
                                $new_token = bin2hex(random_bytes(64));
                                $new_expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
            
                                // Update token in database
                                $update_stmt = $pdo->prepare("UPDATE auth_tokens SET token = ?, expires_at = ?, created_at = NOW() WHERE id = ?");
                                $update_stmt->execute([$new_token, $new_expires_at, $token_record['id']]);
            
                                // Set new cookie
                                setcookie(
                                    'remember_me',
                                    $new_token,
                                    [
                                        'expires' => strtotime('+30 days'),
                                        'path' => '/',
                                        'httponly' => true,
                                        'samesite' => 'Lax',
                                        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' // Dynamically set secure flag
                                    ]
                                );
                                // --- End Security Update ---
                            }        } else {
            // No token found or expired. Clear the cookie.
            setcookie('remember_me', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', true);
        }
    } else {
        // Malformed or empty cookie. Clear it.
        setcookie('remember_me', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', true);
    }
}
?>