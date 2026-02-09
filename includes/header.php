<?php
// Tự động xác định BASE_URL một cách chính xác nhất
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];

// Lấy thư mục hiện tại của script đang chạy (public/index.php)
$script_name = $_SERVER['SCRIPT_NAME']; // vd: /khaservice-it/public/index.php
$base_dir = dirname(dirname($script_name)); // Lùi lại 2 cấp từ index.php để ra thư mục gốc dự án

// Chuẩn hóa base_dir để luôn có dấu gạch chéo ở cuối và không bị lặp
$base_dir = rtrim($base_dir, '/\\') . '/';
$final_base = $protocol . "://" . $host . $base_dir;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý IT - KHASERVICE</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo $final_base; ?>uploads/system/Logo1024x.png">
    
    <!-- FontAwesome 5.15.4 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Main Style -->
    <link rel="stylesheet" href="<?php echo $final_base; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $final_base; ?>assets/css/layout.css">
    
    <script src="<?php echo $final_base; ?>assets/js/audio_feedback.js" defer></script>
    <style>
        .settings-link { color: #64748b; font-size: 1.2rem; transition: color 0.2s; }
        .settings-link:hover { color: var(--primary-color); }
        .logout-link { color: #ef4444; font-size: 1.2rem; }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="header-left">
            <button class="hamburger" id="hamburger-menu" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
            <div class="logo">
                <a href="<?php echo $final_base; ?>index.php">KHASERVICE IT</a>
            </div>
        </div>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'user'): // Admin/IT navigation ?>
        <nav class="main-nav" id="mobile-menu">
            <div class="mobile-nav-header">
                <span class="brand">MENU</span>
                <button class="close-menu" onclick="document.getElementById('hamburger-menu').click()">&times;</button>
            </div>
            <ul>
                <li><a href="index.php?page=devices/list"><i class="fas fa-server"></i> Thiết bị</a></li>
                <li><a href="index.php?page=maintenance/history"><i class="fas fa-tools"></i> Công tác</a></li>
                <li><a href="index.php?page=services/list"><i class="fas fa-cloud"></i> Dịch vụ</a></li>
                <li><a href="index.php?page=projects/list"><i class="fas fa-building"></i> Dự án</a></li>
                
                <li class="dropdown">
                    <a href="javascript:void(0)" onclick="toggleDropdown(this)"><i class="fas fa-ellipsis-h"></i> Khác <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <a href="index.php?page=car_systems/list"><i class="fas fa-car-battery"></i> Hệ thống xe</a>
                        <a href="index.php?page=suppliers/list"><i class="fas fa-truck"></i> Nhà cung cấp</a>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <a href="index.php?page=users/list"><i class="fas fa-users"></i> Người dùng</a>
                            <a href="index.php?page=settings/system"><i class="fas fa-cogs"></i> Cài đặt</a>
                        <?php endif; ?>
                        <a href="index.php?page=trash/list" class="trash-link"><i class="fas fa-trash-alt"></i> Thùng rác</a>
                    </div>
                </li>
            </ul>
        </nav>
        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'user'): // User-specific navigation ?>
        <nav class="main-nav" id="mobile-menu">
            <div class="mobile-nav-header">
                <span class="brand">MENU</span>
                <button class="close-menu" onclick="document.getElementById('hamburger-menu').click()">&times;</button>
            </div>
            <ul>
                <li><a href="user_forms_dashboard.php?page=forms/list"><i class="fas fa-clipboard-list"></i> Biểu mẫu của tôi</a></li>
            </ul>
        </nav>
        <?php endif; ?>

        <div class="user-info">
            <div class="user-meta">
                <span class="user-name-text"><?php echo htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username']); ?></span>
                <span class="user-role"><?php echo strtoupper($_SESSION['role']); ?></span>
            </div>
            <a href="index.php?page=users/settings" class="settings-link" title="Cài đặt"><i class="fas fa-cog"></i></a>
            <a href="logout.php" class="logout-link" title="Thoát"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </header>
    
    <script>
        // Universal Dropdown Toggle (Desktop & Mobile)
        function toggleDropdown(element) {
            // Ngăn chặn sự kiện click lan ra ngoài (để không kích hoạt document click ngay lập tức)
            event.stopPropagation();
            
            // Tìm thẻ cha li.dropdown
            const dropdownLi = element.closest('.dropdown');
            
            // Toggle class active
            dropdownLi.classList.toggle('active');
        }

        // Close dropdown when clicking outside (Desktop only)
        document.addEventListener('click', function(event) {
            if (window.innerWidth > 1100) {
                const dropdowns = document.querySelectorAll('.dropdown');
                dropdowns.forEach(function(dropdown) {
                    // Nếu click không nằm trong dropdown này thì đóng nó lại
                    if (!dropdown.contains(event.target)) {
                        dropdown.classList.remove('active');
                    }
                });
            }
        });
    </script>
    <main class="main-content">
        <div class="container">