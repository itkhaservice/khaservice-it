<?php
session_start(); // Start session once at the top
require_once __DIR__ . '/../includes/remember_me_check.php'; // Check for remember me cookie
require_once __DIR__ . '/../includes/auth.php'; // Handle authentication
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/messages.php'; // Include messages helper

// --- Dashboard Data Fetching Logic ---
if (($page ?? 'home') === 'home') {
    try {
        // 1. KPI Cards Data
        $total_devices = $pdo->query("SELECT COUNT(id) FROM devices")->fetchColumn();
        $devices_nearing_warranty = $pdo->query("SELECT COUNT(id) FROM devices WHERE bao_hanh_den IS NOT NULL AND bao_hanh_den BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)")->fetchColumn();
        $broken_or_liquidated_devices = $pdo->query("SELECT COUNT(id) FROM devices WHERE trang_thai IN ('Hỏng', 'Thanh lý')")->fetchColumn();
        $total_maintenance_logs = $pdo->query("SELECT COUNT(id) FROM maintenance_logs")->fetchColumn();

        // 2. Action Zone - Critical Alerts
        $overdue_warranty_devices_stmt = $pdo->prepare("SELECT id, ma_tai_san, ten_thiet_bi, bao_hanh_den FROM devices WHERE bao_hanh_den IS NOT NULL AND bao_hanh_den < CURDATE() LIMIT 5");
        $overdue_warranty_devices_stmt->execute();
        $overdue_warranty_devices = $overdue_warranty_devices_stmt->fetchAll(PDO::FETCH_ASSOC);

        $broken_with_liquidation_notes_stmt = $pdo->prepare("SELECT id, ma_tai_san, ten_thiet_bi, ghi_chu FROM devices WHERE (trang_thai = 'Hỏng' OR trang_thai = 'Thanh lý') AND ghi_chu LIKE '%thanh lý%' LIMIT 5");
        $broken_with_liquidation_notes_stmt->execute();
        $broken_with_liquidation_notes = $broken_with_liquidation_notes_stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Chart Data: Device Status Distribution
        $device_status_stats = $pdo->query("SELECT trang_thai, COUNT(*) as count FROM devices GROUP BY trang_thai")->fetchAll(PDO::FETCH_KEY_PAIR);
        $statuses = ['Đang sử dụng', 'Hỏng', 'Thanh lý', 'Mới nhập'];
        foreach ($statuses as $s) {
            if (!isset($device_status_stats[$s])) $device_status_stats[$s] = 0;
        }

        // 4. Chart Data: Device Type Distribution (Top 5)
        $device_type_stats_stmt = $pdo->query("SELECT loai_thiet_bi, COUNT(*) as count FROM devices GROUP BY loai_thiet_bi ORDER BY count DESC LIMIT 5");
        $device_type_stats = $device_type_stats_stmt->fetchAll(PDO::FETCH_ASSOC);

        // 5. Recent Activity Feed (Latest Maintenance Logs)
        $recent_activities_stmt = $pdo->query("
            SELECT 
                ml.id, 
                ml.ngay_su_co, 
                ml.noi_dung, 
                ml.created_at, 
                d.ma_tai_san, 
                d.ten_thiet_bi 
            FROM maintenance_logs ml
            LEFT JOIN devices d ON ml.device_id = d.id
            ORDER BY ml.created_at DESC 
            LIMIT 5
        ");
        $recent_activities = $recent_activities_stmt->fetchAll(PDO::FETCH_ASSOC);

        // 6. Services Expiring Soon (New)
        $expiring_services_stmt = $pdo->query("
            SELECT id, ten_dich_vu, ngay_het_han, DATEDIFF(ngay_het_han, CURDATE()) as days_left 
            FROM services 
            WHERE ngay_het_han <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ORDER BY ngay_het_han ASC 
            LIMIT 5
        ");
        $expiring_services = $expiring_services_stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Dashboard data fetch error: " . $e->getMessage());
        $total_devices = 0; $devices_nearing_warranty = 0; $broken_or_liquidated_devices = 0; $total_maintenance_logs = 0;
        $overdue_warranty_devices = []; $broken_with_liquidation_notes = [];
        $device_status_stats = []; $device_type_stats = []; $recent_activities = [];
        set_message("Lỗi tải dữ liệu Dashboard. Vui lòng thử lại sau.", "error");
    }
}
// --- End Dashboard Data Fetching Logic ---

include_once __DIR__ . '/../includes/header.php';
display_messages();

// Routing Logic
$page = $_GET['page'] ?? 'home';
$page = preg_replace('/[^a-zA-Z0-9\/_.-]/', '', $page); // Sanitize
$requested_file = __DIR__ . '/../modules/' . $page . '.php';
$base_path = realpath(__DIR__ . '/../modules');
$module_path = realpath($requested_file);

// --- PHÂN QUYỀN TẬP TRUNG (CENTRALIZED AUTHORIZATION) ---
if ($module_path && strpos($module_path, $base_path) === 0 && file_exists($module_path)) {
    // 1. Chỉ Admin mới được vào các trang Quản lý Người dùng hoặc Cài đặt hệ thống
    if ((strpos($page, 'users/') !== false && $page !== 'users/settings') || strpos($page, 'settings/') !== false) {
        requireAdmin();
    }
    // 2. Chỉ Admin mới được vào các trang XÓA (Trừ module Maintenance hoặc Trash do IT cũng có quyền)
    elseif (strpos($page, 'delete') !== false && strpos($page, 'maintenance/') === false && strpos($page, 'trash/') === false) {
        requireAdmin();
    }
    // 3. Chỉ IT trở lên mới được vào các trang THÊM, SỬA hoặc QUẢN LÝ FILE
    elseif (strpos($page, 'add') !== false || strpos($page, 'edit') !== false || strpos($page, 'manage') !== false) {
        requireIT();
    }
    
    include $module_path;
} else { 
    // --- DASHBOARD VIEW ---
?>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="dashboard-container">
        <div class="page-header">
            <h2 style="margin-bottom: 0;"><i class="fas fa-home"></i> Tổng quan Hệ thống</h2>
            <div style="font-size: 0.9rem; color: #64748b;">Hôm nay: <?php echo date('d/m/Y'); ?></div>
        </div>

        <!-- 1. KPI Cards Grid -->
        <div class="kpi-grid">
            <div class="stat-card primary">
                <div class="stat-content">
                    <span class="stat-label">Tổng Thiết bị</span>
                    <span class="stat-value"><?= number_format($total_devices) ?></span>
                </div>
                <div class="stat-icon"><i class="fas fa-server"></i></div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-content">
                    <span class="stat-label">Sắp hết Bảo hành</span>
                    <span class="stat-value"><?= number_format($devices_nearing_warranty) ?></span>
                </div>
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
            </div>

            <div class="stat-card danger">
                <div class="stat-content">
                    <span class="stat-label">Hỏng / Thanh lý</span>
                    <span class="stat-value"><?= number_format($broken_or_liquidated_devices) ?></span>
                </div>
                <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
            </div>

            <div class="stat-card info">
                <div class="stat-content">
                    <span class="stat-label">Lượt Bảo trì</span>
                    <span class="stat-value"><?= number_format($total_maintenance_logs) ?></span>
                </div>
                <div class="stat-icon"><i class="fas fa-tools"></i></div>
            </div>
        </div>

        <!-- 2. Charts Section -->
        <div class="charts-grid">
            <!-- Main Chart: Device Types (Bar) -->
            <div class="card">
                <div class="dashboard-card-header">
                    <h3><i class="fas fa-chart-bar" style="color: #3b82f6;"></i> Phân loại Thiết bị (Top 5)</h3>
                </div>
                <div style="height: 300px; position: relative;">
                    <canvas id="deviceTypeChart"></canvas>
                </div>
            </div>

            <!-- Side Chart: Status (Doughnut) -->
            <div class="card">
                <div class="dashboard-card-header">
                    <h3><i class="fas fa-chart-pie" style="color: #10b981;"></i> Tình trạng</h3>
                </div>
                <div style="height: 300px; position: relative; display: flex; justify-content: center;">
                    <canvas id="deviceStatusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- 3. Bottom Grid: Activity & Actions -->
        <div class="activity-grid">
            <!-- Recent Activity Feed -->
            <div class="card">
                <div class="dashboard-card-header">
                    <h3><i class="fas fa-history" style="color: #6366f1;"></i> Hoạt động Gần đây</h3>
                    <a href="index.php?page=maintenance/history" class="btn btn-sm btn-secondary">Xem tất cả</a>
                </div>
                
                <?php if (empty($recent_activities)): ?>
                    <div style="text-align: center; padding: 30px; color: #94a3b8;">
                        <i class="fas fa-clipboard-check" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                        <p>Chưa có hoạt động nào.</p>
                    </div>
                <?php else: ?>
                    <ul class="feed-list">
                        <?php foreach ($recent_activities as $activity): ?>
                            <li class="feed-item">
                                <div class="feed-icon">
                                    <i class="fas fa-wrench"></i>
                                </div>
                                <div class="feed-content">
                                    <h4><?= htmlspecialchars($activity['ten_thiet_bi']) ?> (<?= htmlspecialchars($activity['ma_tai_san']) ?>)</h4>
                                    <p><?= htmlspecialchars($activity['noi_dung']) ?></p>
                                    <span class="feed-time"><i class="far fa-clock"></i> <?= date('H:i d/m/Y', strtotime($activity['created_at'])) ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Action Zone -->
            <div class="card">
                <div class="dashboard-card-header">
                    <h3><i class="fas fa-bell" style="color: #f59e0b;"></i> Cần Xử lý Gấp</h3>
                </div>

                <?php if (empty($overdue_warranty_devices) && empty($broken_with_liquidation_notes)): ?>
                    <div style="text-align: center; padding: 40px 20px;">
                        <i class="fas fa-check-circle" style="font-size: 3rem; color: #10b981; margin-bottom: 15px;"></i>
                        <p style="font-weight: 500; color: #059669;">Mọi thứ đều ổn định!</p>
                        <p style="color: #64748b; font-size: 0.9rem;">Không có cảnh báo khẩn cấp nào.</p>
                    </div>
                <?php else: ?>
                    <ul class="action-list">
                        <?php foreach ($overdue_warranty_devices as $device): ?>
                            <li>
                                <a href="index.php?page=devices/view&id=<?= $device['id'] ?>" class="action-item warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <div class="action-content">
                                        <span class="action-title"><?= htmlspecialchars($device['ma_tai_san']) ?></span>
                                        <span class="action-desc">Quá hạn BH: <?= date('d/m/Y', strtotime($device['bao_hanh_den'])) ?></span>
                                    </div>
                                    <i class="fas fa-chevron-right action-arrow"></i>
                                </a>
                            </li>
                        <?php endforeach; ?>

                        <?php foreach ($broken_with_liquidation_notes as $device): ?>
                            <li>
                                <a href="index.php?page=devices/view&id=<?= $device['id'] ?>" class="action-item danger">
                                    <i class="fas fa-trash-alt"></i>
                                    <div class="action-content">
                                        <span class="action-title"><?= htmlspecialchars($device['ma_tai_san']) ?></span>
                                        <span class="action-desc">Cần Thanh lý (Ghi chú có từ khóa 'thanh lý')</span>
                                    </div>
                                    <i class="fas fa-chevron-right action-arrow"></i>
                                </a>
                            </li>
                        <?php endforeach; ?>

                        <?php foreach ($expiring_services as $service): ?>
                            <li>
                                <a href="index.php?page=services/list" class="action-item <?= ($service['days_left'] <= 0) ? 'danger' : 'warning' ?>">
                                    <i class="fas fa-cloud"></i>
                                    <div class="action-content">
                                        <span class="action-title"><?= htmlspecialchars($service['ten_dich_vu']) ?></span>
                                        <span class="action-desc">
                                            <?= ($service['days_left'] <= 0) ? 'ĐÃ HẾT HẠN' : 'Hết hạn sau ' . $service['days_left'] . ' ngày' ?>
                                            (<?= date('d/m/Y', strtotime($service['ngay_het_han'])) ?>)
                                        </span>
                                    </div>
                                    <i class="fas fa-chevron-right action-arrow"></i>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Chart Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
            Chart.defaults.color = '#64748b';

            // 1. Device Status Chart (Doughnut)
            const statusCtx = document.getElementById('deviceStatusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_keys($device_status_stats)) ?>,
                    datasets: [{
                        data: <?= json_encode(array_values($device_status_stats)) ?>,
                        backgroundColor: [
                            '#10b981', // Đang sử dụng (Green)
                            '#ef4444', // Hỏng (Red)
                            '#f59e0b', // Thanh lý (Amber)
                            '#3b82f6'  // Mới nhập (Blue)
                        ],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } },
                        tooltip: { backgroundColor: '#1e293b', padding: 10, cornerRadius: 8 }
                    },
                    cutout: '75%'
                }
            });

            // 2. Device Type Chart (Bar)
            const typeCtx = document.getElementById('deviceTypeChart').getContext('2d');
            const typeLabels = <?= json_encode(array_column($device_type_stats, 'loai_thiet_bi')) ?>;
            const typeCounts = <?= json_encode(array_column($device_type_stats, 'count')) ?>;
            
            // Create gradient for bars
            const gradientBar = typeCtx.createLinearGradient(0, 0, 0, 400);
            gradientBar.addColorStop(0, '#3b82f6');
            gradientBar.addColorStop(1, '#2563eb');

            new Chart(typeCtx, {
                type: 'bar',
                data: {
                    labels: typeLabels,
                    datasets: [{
                        label: 'Số lượng',
                        data: typeCounts,
                        backgroundColor: gradientBar,
                        borderRadius: 6,
                        barPercentage: 0.5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { backgroundColor: '#1e293b', padding: 10, cornerRadius: 8 }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            ticks: { precision: 0 },
                            grid: { borderDash: [2, 4], color: '#e2e8f0' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        });
    </script>
<?php
}
?>

<?php
include_once __DIR__ . '/../includes/footer.php';