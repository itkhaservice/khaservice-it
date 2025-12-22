<?php
session_start();
require '../config/db.php';

if (isset($_COOKIE['remember_me'])) {
    $pdo->prepare("DELETE FROM auth_tokens WHERE token = ?")
        ->execute([$_COOKIE['remember_me']]);

    setcookie('remember_me', '', time() - 3600, '/');
}

session_destroy();
header('Location: login.php');
exit;

