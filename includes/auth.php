<?php
// File: includes/auth.php

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/**
 * Kiểm tra xem người dùng có phải là Admin không
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Kiểm tra xem người dùng có phải là IT Staff trở lên không (IT hoặc Admin)
 */
function isIT() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'it' || $_SESSION['role'] === 'admin');
}

/**
 * Chặn truy cập nếu không phải Admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        set_message('error', 'Bạn không có quyền thực hiện hành động này (Yêu cầu quyền Admin).');
        header("Location: index.php");
        exit;
    }
}

/**
 * Chặn truy cập nếu không phải IT Staff hoặc Admin
 */
function requireIT() {
    if (!isIT()) {
        set_message('error', 'Bạn không có quyền thực hiện hành động này (Yêu cầu quyền IT Staff).');
        header("Location: index.php");
        exit;
    }
}
?>