<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// File: public/form_landing.php
session_start();

// If user is already logged in, redirect them straight to the forms list
if (isset($_SESSION['user_id'])) {
    header('Location: user_forms_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bắt đầu với KHAService Forms</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Be Vietnam Pro', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .gateway-container {
            text-align: center;
            background: #fff;
            padding: 50px;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
        }
        .gateway-container h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        .gateway-container p {
            color: #475569;
            margin-bottom: 30px;
            font-size: 1.1rem;
            line-height: 1.6;
        }
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .btn {
            display: block;
            padding: 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.2s;
        }
        .btn-primary {
            background-color: var(--primary-color);
            color: #fff;
        }
        .btn-primary:hover {
            background-color: var(--primary-dark-color);
        }
        .btn-secondary {
            background-color: #e2e8f0;
            color: #1e293b;
        }
        .btn-secondary:hover {
            background-color: #cbd5e1;
        }
    </style>
</head>
<body>
    <div class="gateway-container">
        <h1><i class="fas fa-clipboard-list"></i> KHAService Forms</h1>
        <p>Đăng nhập hoặc tạo tài khoản miễn phí để bắt đầu xây dựng biểu mẫu của bạn.</p>
        <div class="action-buttons">
            <a href="login.php" class="btn btn-primary">Đăng nhập</a>
            <a href="register.php" class="btn btn-secondary">Đăng ký tài khoản mới</a>
        </div>
    </div>
</body>
</html>
