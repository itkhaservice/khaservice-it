<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý IT - KHASERVICE</title>
    <base href="/khaservice-it/public/" />
    
    <!-- FontAwesome 5.15.4 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Main Style -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    
    <script src="../assets/js/audio_feedback.js" defer></script>
    <style>
        .settings-link { color: #64748b; font-size: 1.2rem; margin: 0 10px; transition: color 0.2s; }
        .settings-link:hover { color: var(--primary-color); }
        .logout-link { color: #ef4444; font-size: 1.2rem; }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="logo">
            <a href="/khaservice-it/public/index.php">KHASERVICE IT</a>
        </div>
        
        <nav class="main-nav">
            <ul>
                <li><a href="/khaservice-it/public/index.php?page=devices/list">Thiết bị</a></li>
                <li><a href="/khaservice-it/public/index.php?page=maintenance/history">Công tác</a></li>
                <li><a href="/khaservice-it/public/index.php?page=services/list">Dịch vụ</a></li>
                <li><a href="/khaservice-it/public/index.php?page=projects/list">Dự án</a></li>
                <li><a href="/khaservice-it/public/index.php?page=suppliers/list">Nhà cung cấp</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li><a href="/khaservice-it/public/index.php?page=users/list">Người dùng</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="user-info">
            <span class="username"><i class="far fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username']); ?></span>
            <a href="/khaservice-it/public/index.php?page=users/settings" class="settings-link" title="Cài đặt tài khoản"><i class="fas fa-cog"></i></a>
            <a href="/khaservice-it/public/logout.php" class="logout-link" title="Thoát"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </header>
    <main class="main-content">
        <div class="container">