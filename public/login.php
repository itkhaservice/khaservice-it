<?php
session_start();
require '../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ==== Validate input ====
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ thông tin.';
    } else {

        // ==== Find user ====
        $stmt = $pdo->prepare("SELECT id, username, fullname, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // ==== Verify password ====
        if ($user && password_verify($password, $user['password'])) {

            // ==== Set session ====
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'] ?? $user['username']; // Fallback to username if empty
            $_SESSION['role'] = $user['role'];

            // ==== Remember Me ====
            if (!empty($_POST['remember_me'])) {

                $token = bin2hex(random_bytes(64));
                $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));

                // Xóa token cũ
                $pdo->prepare("DELETE FROM auth_tokens WHERE user_id = ?")
                    ->execute([$user['id']]);

                // Lưu token mới
                $pdo->prepare("
                    INSERT INTO auth_tokens (user_id, token, expires_at)
                    VALUES (?, ?, ?)
                ")->execute([$user['id'], $token, $expires_at]);

                // Set cookie
                setcookie(
                    'remember_me',
                    $token,
                    [
                        'expires' => time() + 60 * 60 * 24 * 30,
                        'path' => '/',
                        'httponly' => true,
                        'samesite' => 'Lax',
                        'secure' => !empty($_SERVER['HTTPS'])
                    ]
                );
            }

            // Redirect based on role
            if ($user['role'] === 'user') {
                header('Location: user_forms_dashboard.php');
            } else {
                header('Location: ../index.php');
            }
            exit;

        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - KHASERVICE IT</title>
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Keep general styles -->
    <link rel="stylesheet" href="../assets/css/login.css"> <!-- Specific login styles -->
    <style>
        /* Added for Form Feature Promo */
        .divider {
            margin: 25px 0;
            text-align: center;
            position: relative;
            color: #94a3b8;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .divider span {
            background: #fff;
            padding: 0 10px;
            position: relative;
            z-index: 1;
        }
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e2e8f0;
            z-index: 0;
        }
        .extra-feature-promo {
            text-align: center;
        }
        .extra-feature-promo p {
            color: #475569;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        .btn-feature-promo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border-radius: 8px;
            background: #f1f5f9;
            color: #1e293b;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
        }
        .btn-feature-promo:hover {
            background: #e2e8f0;
            border-color: #cbd5e1;
        }
        .btn-feature-promo i {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="split-login-wrapper">
        <div class="login-intro-section">
            <div class="login-intro-content">
                <div class="logo">KHASERVICE IT</div>
                <div class="intro-text-block">
                    <h1>Hệ thống Quản lý Thiết bị IT</h1>
                    <p>Phần mềm này giúp công ty KHASERVICE quản lý tập trung toàn bộ thông tin thiết bị IT nội bộ.</p>
                    <p>Theo dõi đầy đủ vòng đời thiết bị từ mua, lắp đặt, sử dụng, hư hỏng, đến sửa chữa hoặc thay thế</p>
                    <p>Cho phép nhân viên IT tra cứu nhanh lịch sử thiết bị ngay tại hiện trường.</p>
                </div>
            </div>
        </div>
        <div class="login-form-section">
            <div class="login-container">
                <h2>Đăng nhập</h2>
                <?php if ($error): ?>
                    <p class="error"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>
                <form method="POST">
            <div class="login-form-group">
                <label for="username">Tên đăng nhập</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" required placeholder="Nhập username" autocomplete="username">
                </div>
            </div>

            <div class="login-form-group">
                <label for="password">Mật khẩu</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required placeholder="Nhập mật khẩu" autocomplete="current-password">
                </div>
            </div>

            <div class="remember-me-container">
                <input type="checkbox" id="remember_me" name="remember_me" value="1">
                <label for="remember_me">Ghi nhớ đăng nhập</label>
            </div>

            <button type="submit" class="btn-login">Đăng nhập</button>
                </form>

                <div class="divider">
                    <span>HOẶC</span>
                </div>

                <div class="extra-feature-promo">
                    <p>Tạo, chia sẻ và phân tích các biểu mẫu một cách dễ dàng.</p>
                    <a href="form_landing.php" class="btn-feature-promo">
                        <i class="fas fa-clipboard-list"></i> Tạo Biểu mẫu miễn phí
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
