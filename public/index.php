<?php
ob_start(); // Bắt đầu đệm đầu ra ngay lập tức
session_start();

// Bật hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/messages.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/remember_me_check.php';

// Hàm chuyển hướng an toàn (Global)
function safe_redirect($url) {
    // Nếu chưa gửi header thì dùng header location (nhanh và chuẩn)
    if (!headers_sent()) {
        header("Location: " . $url);
    }
    // Luôn chèn JS fallback phòng trường hợp header bị chặn hoặc buffer bị flush
    echo '<script>window.location.href="' . $url . '";</script>';
    // Quan trọng: xóa buffer và dừng script để không nạp footer/nội dung thừa
    ob_end_flush(); 
    exit;
}

// Router
$page = $_GET['page'] ?? 'home';
$page = preg_replace('/[^a-zA-Z0-9\/_.-]/', '', $page);

// Chuyển hướng user thường
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'user' && $page === 'home') {
    safe_redirect('user_forms_dashboard.php');
}

// --- LOGIC DASHBOARD (Chỉ khi home) ---
if ($page === 'home') {
    try {
        $total_devices = $pdo->query("SELECT COUNT(id) FROM devices WHERE deleted_at IS NULL")->fetchColumn();
        $devices_nearing_warranty = $pdo->query("SELECT COUNT(id) FROM devices WHERE deleted_at IS NULL AND bao_hanh_den IS NOT NULL AND bao_hanh_den BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
        $broken_devices = $pdo->query("SELECT COUNT(id) FROM devices WHERE deleted_at IS NULL AND trang_thai IN ('Hỏng', 'Cảnh báo')")->fetchColumn();
        $total_maintenance_logs = $pdo->query("SELECT COUNT(id) FROM maintenance_logs WHERE deleted_at IS NULL")->fetchColumn();
        $overdue_warranty_devices = $pdo->query("SELECT id, ma_tai_san, ten_thiet_bi, bao_hanh_den FROM devices WHERE deleted_at IS NULL AND bao_hanh_den IS NOT NULL AND bao_hanh_den < CURDATE() ORDER BY bao_hanh_den ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        $device_status_stats = $pdo->query("SELECT s.status_name, COUNT(d.id) as count FROM settings_device_statuses s LEFT JOIN devices d ON s.status_name = d.trang_thai AND d.deleted_at IS NULL GROUP BY s.status_name")->fetchAll(PDO::FETCH_KEY_PAIR);
        $device_type_stats = $pdo->query("SELECT loai_thiet_bi, COUNT(*) as count FROM devices WHERE deleted_at IS NULL AND loai_thiet_bi != '' GROUP BY loai_thiet_bi ORDER BY count DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        $recent_activities = $pdo->query("SELECT ml.id, ml.ngay_su_co, ml.noi_dung, ml.created_at, ml.custom_device_name, d.ma_tai_san, d.ten_thiet_bi FROM maintenance_logs ml LEFT JOIN devices d ON ml.device_id = d.id WHERE ml.deleted_at IS NULL ORDER BY ml.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        $expiring_services = $pdo->query("SELECT id, ten_dich_vu, ngay_het_han, DATEDIFF(ngay_het_han, CURDATE()) as days_left FROM services WHERE deleted_at IS NULL AND ngay_het_han <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY ngay_het_han ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { error_log("Dashboard error"); }
}

$module_file = __DIR__ . '/../modules/' . $page . '.php';
$is_export = (strpos($page, 'export') !== false || strpos($page, 'print') !== false || strpos($page, 'delete') !== false);

// 1. INCLUDE HEADER (Nếu không phải trang export)
if (!$is_export) {
    include_once __DIR__ . '/../includes/header.php';
    display_messages();
}

// 2. INCLUDE MODULE (Nội dung chính)
if (file_exists($module_file)) {
    // Phân quyền tập trung
    if ((strpos($page, 'users/') !== false && $page !== 'users/settings') || strpos($page, 'settings/') !== false) { requireAdmin(); }
    elseif (strpos($page, 'delete') !== false && strpos($page, 'maintenance/') === false && strpos($page, 'trash/') === false) { requireAdmin(); }
    elseif (strpos($page, 'add') !== false || strpos($page, 'edit') !== false || strpos($page, 'manage') !== false) { requireIT(); }
    
    include $module_file;
} else if ($page === 'home') {
    // Hiển thị Dashboard
?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <div class="dashboard-container">
        <div class="page-header"><h2 style="margin-bottom: 0;"><i class="fas fa-home"></i> Tổng quan</h2><div style="font-size: 0.9rem; color: #64748b;">Hôm nay: <?php echo date('d/m/Y'); ?></div></div>
        <div class="kpi-grid">
            <div class="stat-card primary"><div class="stat-content"><span class="stat-label">Thiết bị</span><span class="stat-value"><?= number_format($total_devices??0) ?></span></div><div class="stat-icon"><i class="fas fa-server"></i></div></div>
            <div class="stat-card warning"><div class="stat-content"><span class="stat-label">Bảo hành</span><span class="stat-value"><?= number_format($devices_nearing_warranty??0) ?></span></div><div class="stat-icon"><i class="fas fa-clock"></i></div></div>
            <div class="stat-card danger"><div class="stat-content"><span class="stat-label">Hỏng</span><span class="stat-value"><?= number_format($broken_devices??0) ?></span></div><div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div></div>
            <div class="stat-card info"><div class="stat-content"><span class="stat-label">Bảo trì</span><span class="stat-value"><?= number_format($total_maintenance_logs??0) ?></span></div><div class="stat-icon"><i class="fas fa-tools"></i></div></div>
        </div>
        <div class="charts-grid">
            <div class="card"><div class="dashboard-card-header"><h3><i class="fas fa-chart-bar"></i> Phân loại</h3></div><div class="chart-canvas-wrapper"><canvas id="deviceTypeChart"></canvas></div></div>
            <div class="card"><div class="dashboard-card-header"><h3><i class="fas fa-chart-pie"></i> Tình trạng</h3></div><div class="chart-canvas-wrapper doughnut"><canvas id="deviceStatusChart"></canvas></div></div>
        </div>
        <div class="activity-grid">
            <div class="card"><div class="dashboard-card-header"><h3><i class="fas fa-history"></i> Hoạt động</h3><a href="index.php?page=maintenance/history" class="btn btn-sm btn-secondary">Tất cả</a></div>
                <?php if (empty($recent_activities)): ?><p style="text-align:center; padding:20px; color:#94a3b8;">Trống.</p>
                <?php else: ?><ul class="feed-list"><?php foreach ($recent_activities as $activity): ?>
                    <li class="feed-item"><div class="feed-icon"><i class="fas fa-wrench"></i></div><div class="feed-content"><h4><?= htmlspecialchars(($activity['ten_thiet_bi']??$activity['custom_device_name']??'---')) ?></h4><p><?= htmlspecialchars($activity['noi_dung']) ?></p><span class="feed-time"><?= date('H:i d/m/Y', strtotime($activity['created_at'])) ?></span></div></li><?php endforeach; ?></ul><?php endif; ?></div>
            <div class="card"><div class="dashboard-card-header"><h3><i class="fas fa-bell"></i> Cần Xử lý Gấp</h3></div>
                <?php if (empty($overdue_warranty_devices) && empty($expiring_services)): ?><p style="text-align:center; padding:20px; color:#10b981;">Ổn định.</p>
                <?php else: ?><ul class="action-list"><?php foreach (($overdue_warranty_devices??[]) as $device): ?>
                    <li><a href="index.php?page=devices/view&id=<?= $device['id'] ?>" class="action-item danger"><i class="fas fa-exclamation-triangle"></i><div class="action-content"><span class="action-title"><?= htmlspecialchars($device['ma_tai_san']) ?></span><span class="action-desc">HẾT BẢO HÀNH</span></div></a></li><?php endforeach; ?>
                    <?php foreach (($expiring_services??[]) as $service): ?><li><a href="index.php?page=services/view&id=<?= $service['id'] ?>" class="action-item warning"><i class="fas fa-cloud"></i><div class="action-content"><span class="action-title"><?= htmlspecialchars($service['ten_dich_vu']) ?></span><span class="action-desc">Sắp hết hạn</span></div></a></li><?php endforeach; ?></ul><?php endif; ?></div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Chart.defaults.font.family = "'Inter', sans-serif"; Chart.defaults.color = '#64748b';
            new Chart(document.getElementById('deviceStatusChart').getContext('2d'), { type: 'doughnut', data: { labels: <?= json_encode(array_keys($device_status_stats??[])) ?>, datasets: [{ data: <?= json_encode(array_values($device_status_stats??[])) ?>, backgroundColor: ['#10b981','#f59e0b','#ef4444','#3b82f6','#94a3b8'], borderWidth: 0 }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '75%', plugins: { legend: { position: 'bottom' } } } });
            const typeCtx = document.getElementById('deviceTypeChart').getContext('2d'); const grad = typeCtx.createLinearGradient(0, 0, 0, 400); grad.addColorStop(0, '#3b82f6'); grad.addColorStop(1, '#2563eb');
            new Chart(typeCtx, { type: 'bar', data: { labels: <?= json_encode(array_column($device_type_stats??[], 'loai_thiet_bi')) ?>, datasets: [{ label: 'Số lượng', data: <?= json_encode(array_column($device_type_stats??[], 'count')) ?>, backgroundColor: grad, borderRadius: 6 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
        });
    </script>
<?php
} else {
    echo '<div class="container" style="padding: 20px;"><div class="card" style="padding:20px; text-align:center;"><h3>404 - Không tìm thấy trang</h3><p>Trang bạn tìm kiếm không tồn tại.</p><a href="index.php" class="btn btn-primary">Về Trang chủ</a></div></div>';
}

// 3. INCLUDE FOOTER
if (!$is_export) {
    include_once __DIR__ . '/../includes/footer.php';
}

// Gửi toàn bộ nội dung đệm ra trình duyệt
ob_end_flush();
?>