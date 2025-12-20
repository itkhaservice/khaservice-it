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
</head>
<body>
    <header class="main-header">
        <div class="logo">
            <a href="/khaservice-it/public/index.php">KHASERVICE IT</a>
        </div>
        
        <nav class="main-nav">
            <ul>
                <li><a href="/khaservice-it/public/index.php?page=devices/list">Thiết bị</a></li>
                <li><a href="/khaservice-it/public/index.php?page=maintenance/history">Bảo trì</a></li>
                <li><a href="/khaservice-it/public/index.php?page=projects/list">Dự án</a></li>
                <li><a href="/khaservice-it/public/index.php?page=suppliers/list">Nhà cung cấp</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li><a href="/khaservice-it/public/index.php?page=users/list">Người dùng</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="user-info">
            <!-- <div class="quick-search-box">
                <input type="text" id="quick-search-input" placeholder="Tìm nhanh mã TS, Serial...">
                <div id="quick-search-results" class="search-results-dropdown"></div>
            </div> -->
            <span class="username"><i class="far fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="/khaservice-it/public/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </header>
    <main class="main-content">
        <div class="container">