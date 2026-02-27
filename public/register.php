<?php
// File: public/register.php
session_start();
require '../config/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($fullname)) $errors[] = 'Họ và tên là bắt buộc.';
    if (empty($username)) $errors[] = 'Tên đăng nhập là bắt buộc.';
    if (empty($password)) $errors[] = 'Mật khẩu là bắt buộc.';
    if (strlen($password) < 6) $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';

    // Check if username already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = 'Tên đăng nhập này đã tồn tại. Vui lòng chọn tên khác.';
        }
    }

    // If no errors, create user
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'Guest'; // Assign the specific role for form creators

            $stmt = $pdo->prepare("INSERT INTO users (fullname, username, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$fullname, $username, $hashed_password, $role]);
            $user_id = $pdo->lastInsertId();

            // Automatically log the user in
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['fullname'] = $fullname;
            $_SESSION['role'] = $role;

            // Redirect to the forms list page
            header('Location: ../index.php?page=forms/list');
            exit;

        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $errors[] = 'Lỗi hệ thống, không thể tạo tài khoản. Vui lòng thử lại sau.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản - KHAService Forms</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/login.css"> <!-- Reuse login styles for consistency -->
</head>
<body>
    <div class="split-login-wrapper">
        <div class="login-intro-section">
             <div class="login-intro-content">
                <div class="logo">KHASERVICE FORMS</div>
                <div class="intro-text-block">
                    <h1>Xây dựng biểu mẫu chuyên nghiệp</h1>
                    <p>Nhanh chóng tạo các cuộc khảo sát và biểu mẫu đẹp mắt, thu thập dữ liệu hiệu quả và phân tích kết quả một cách trực quan.</p>
                </div>
            </div>
        </div>
        <div class="login-form-section">
            <div class="login-container">
                <h2>Tạo tài khoản miễn phí</h2>
                <?php if (!empty($errors)): ?>
                    <div class="error-container">
                        <?php foreach ($errors as $error): ?>
                            <p class="error"><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <div class="login-form-group">
                        <label for="fullname">Họ và tên</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user-circle"></i>
                            <input type="text" id="fullname" name="fullname" required placeholder="Nhập họ và tên của bạn" value="<?= htmlspecialchars($fullname ?? '') ?>">
                        </div>
                    </div>
                    <div class="login-form-group">
                        <label for="username">Tên đăng nhập</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="username" name="username" required placeholder="VD: nguyenvana" value="<?= htmlspecialchars($username ?? '') ?>">
                        </div>
                    </div>
                    <div class="login-form-group">
                        <label for="password">Mật khẩu</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" required placeholder="Ít nhất 6 ký tự">
                        </div>
                    </div>
                    <button type="submit" class="btn-login">Đăng ký</button>
                </form>
                <div class="login-footer-link">
                    Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<style>
.error-container {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}
.error-container .error {
    color: #991b1b;
    margin: 0;
    padding: 0;
}
.error-container .error:not(:last-child) {
    margin-bottom: 5px;
}
.login-footer-link {
    text-align: center;
    margin-top: 20px;
    font-size: 0.9rem;
}
.login-footer-link a {
    color: var(--primary-color);
    font-weight: 600;
    text-decoration: none;
}
.login-footer-link a:hover {
    text-decoration: underline;
}
</style>
