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
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // ==== Verify password ====
        if ($user && password_verify($password, $user['password'])) {

            // ==== Set session ====
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
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

            header('Location: index.php');
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
                    <div class="form-group">
                        <label for="username">Tên đăng nhập</label>
                        <input type="text" id="username" name="username" placeholder="Tên đăng nhập"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Mật khẩu</label>
                        <input type="password" id="password" name="password" placeholder="Mật khẩu" required>
                    </div>
                    <div class="form-group remember-me">
                        <input type="checkbox" id="remember_me" name="remember_me" value="1">
                        <label for="remember_me">Ghi nhớ đăng nhập</label>
                    </div>
                    <button type="submit">Đăng nhập</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
