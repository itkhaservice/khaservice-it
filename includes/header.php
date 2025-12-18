
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý IT - KHASERVICE</title>
    <base href="/khaservice-it/public/" />
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPYXKCqfR8cvBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/table.css">
    <link rel="stylesheet" href="../assets/css/form.css">
    <link rel="stylesheet" href="../assets/css/view.css">
    <script src="../assets/js/audio_feedback.js" defer></script>
</head>
<body>
    <header class="main-header">
        <div class="logo">
            <a href="/khaservice-it/public/index.php">KHASERVICE IT</a>
        </div>
        <button class="hamburger" id="hamburger-menu" aria-label="Toggle navigation">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="mobile-nav-wrapper" id="mobile-menu">
            <nav class="main-nav">
                <ul>
                    <li><a href="/khaservice-it/public/index.php?page=devices/list">Thiết bị</a></li>
                    <li><a href="/khaservice-it/public/index.php?page=maintenance/history">Bảo trì</a></li>
                                <li><a href="/khaservice-it/public/index.php?page=projects/list">Dự án</a></li>
                                <li><a href="/khaservice-it/public/index.php?page=suppliers/list">Nhà cung cấp</a></li>
                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                    <li><a href="/khaservice-it/public/index.php?page=users/list">Quản lý người dùng</a></li>
                                <?php endif; ?>
                            </ul>
                        </nav>
            <!-- <div class="search-box"> -->
                <!-- <form action="/khaservice-it/public/index.php" method="GET"> -->
                    <!-- <input type="hidden" name="page" value="search/results"> Will create this module -->
                    <!-- <input type="text" name="search_query" placeholder="Tìm kiếm tài sản, serial..." value="<?php echo htmlspecialchars($_GET['search_query'] ?? ''); ?>"> -->
                    <!-- <button type="submit" class="btn btn-primary btn-search">Tìm</button> -->
                <!-- </form> -->
            <!-- </div> -->
        </div>
        <div class="user-info">
            <!-- Quick Search Bar -->
            <div class="quick-search-box">
                <input type="text" id="quick-search-input" placeholder="Tìm kiếm nhanh: Mã TS, Serial...">
                <div id="quick-search-results" class="search-results-dropdown">
                    <!-- Search results will be loaded here by JavaScript -->
                </div>
            </div>

            <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="/khaservice-it/public/logout.php">Đăng xuất</a>
        </div>
    </header>
    <main class="main-content">
        <div class="container">
