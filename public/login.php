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
    <link rel="stylesheet" href="/khaservice-it/assets/css/style.css"> <!-- Keep general styles -->
    <link rel="stylesheet" href="/khaservice-it/assets/css/login.css"> <!-- Specific login styles -->
</head>
<body>
<div class="login-container">
    <h2>Đăng nhập</h2>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Tên đăng nhập"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>

        <input type="password" name="password" placeholder="Mật khẩu" required>

        <label>
            <input type="checkbox" name="remember_me" value="1">
            Ghi nhớ đăng nhập
        </label>

        <button type="submit">Đăng nhập</button>
    </form>
</div>
</body>
</html>
