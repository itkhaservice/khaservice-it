<?php
session_start(); // Start session once at the top

// Redirect 'user' roles to their dedicated dashboard
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'user') {
    header('Location: user_forms_dashboard.php');
    exit;
}

require_once __DIR__ . '/../includes/remember_me_check.php'; // Check for remember me cookie
require_once __DIR__ . '/../includes/auth.php'; // Handle authentication
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/messages.php'; // Include messages helper

// --- Dashboard Data Fetching Logic ---
if (($page ?? 'home') === 'home') {
    try {
        // 1. KPI Cards Data
        $total_devices = $pdo->query("SELECT COUNT(id) FROM devices WHERE deleted_at IS NULL")->fetchColumn();
        $devices_nearing_warranty = $pdo->query("SELECT COUNT(id) FROM devices WHERE deleted_at IS NULL AND bao_hanh_den IS NOT NULL AND bao_hanh_den BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
        $broken_devices = $pdo->query("SELECT COUNT(id) FROM devices WHERE deleted_at IS NULL AND trang_thai IN ('Hỏng', 'Cảnh báo')")->fetchColumn();
        $total_maintenance_logs = $pdo->query("SELECT COUNT(id) FROM maintenance_logs WHERE deleted_at IS NULL")->fetchColumn();

        // 2. Action Zone - Critical Alerts
        // Devices with overdue warranty
        $overdue_warranty_devices_stmt = $pdo->prepare("SELECT id, ma_tai_san, ten_thiet_bi, bao_hanh_den FROM devices WHERE deleted_at IS NULL AND bao_hanh_den IS NOT NULL AND bao_hanh_den < CURDATE() ORDER BY bao_hanh_den ASC LIMIT 5");
        $overdue_warranty_devices_stmt->execute();
        $overdue_warranty_devices = $overdue_warranty_devices_stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Chart Data: Device Status Distribution (Dynamic from config)
        $status_stats_stmt = $pdo->query("
            SELECT s.status_name, COUNT(d.id) as count 
            FROM settings_device_statuses s 
            LEFT JOIN devices d ON s.status_name = d.trang_thai AND d.deleted_at IS NULL 
            GROUP BY s.status_name
        ");
        $device_status_stats = $status_stats_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // 4. Chart Data: Device Type Distribution (Top 5)
        $device_type_stats_stmt = $pdo->query("SELECT loai_thiet_bi, COUNT(*) as count FROM devices WHERE deleted_at IS NULL AND loai_thiet_bi != '' GROUP BY loai_thiet_bi ORDER BY count DESC LIMIT 5");
        $device_type_stats = $device_type_stats_stmt->fetchAll(PDO::FETCH_ASSOC);

        // 5. Recent Activity Feed (Latest Maintenance Logs)
        $recent_activities_stmt = $pdo->query("
            SELECT 
                ml.id, 
                ml.ngay_su_co, 
                ml.noi_dung, 
                ml.created_at, 
                ml.custom_device_name,
                d.ma_tai_san, 
                d.ten_thiet_bi 
            FROM maintenance_logs ml
            LEFT JOIN devices d ON ml.device_id = d.id
            WHERE ml.deleted_at IS NULL
            ORDER BY ml.created_at DESC 
            LIMIT 5
        ");
        $recent_activities = $recent_activities_stmt->fetchAll(PDO::FETCH_ASSOC);

        // 6. Services Expiring Soon
        $expiring_services_stmt = $pdo->query("
            SELECT id, ten_dich_vu, ngay_het_han, DATEDIFF(ngay_het_han, CURDATE()) as days_left 
            FROM services 
            WHERE deleted_at IS NULL AND ngay_het_han <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ORDER BY ngay_het_han ASC 
            LIMIT 5
        ");
        $expiring_services = $expiring_services_stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Dashboard data fetch error: " . $e->getMessage());
        $total_devices = 0; $devices_nearing_warranty = 0; $broken_devices = 0; $total_maintenance_logs = 0;
        $overdue_warranty_devices = []; $device_status_stats = []; $device_type_stats = []; $recent_activities = []; $expiring_services = [];
        set_message("Lỗi tải dữ liệu Dashboard. Vui lòng thử lại sau.", "error");
    }
}
// --- End Dashboard Data Fetching Logic ---

$page = $_GET['page'] ?? 'home';
$page = preg_replace('/[^a-zA-Z0-9\/_.-]/', '', $page); // Sanitize

// --- XỬ LÝ POST REQUEST TRƯỚC KHI XUẤT HTML (Tránh lỗi Headers already sent) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'car_inspections/list' && isset($_POST['quick_add'])) {
    try {
        // Lấy địa chỉ mặc định từ bảng projects (nối các trường địa chỉ)
        $stmt_p = $pdo->prepare("SELECT dia_chi_duong, dia_chi_phuong_xa, dia_chi_tinh_tp FROM projects WHERE id = ?");
        $stmt_p->execute([$_POST['project_id']]);
        $p_data = $stmt_p->fetch(PDO::FETCH_ASSOC);
        
        $addr_parts = [];
        if (!empty($p_data['dia_chi_duong'])) $addr_parts[] = $p_data['dia_chi_duong'];
        if (!empty($p_data['dia_chi_phuong_xa'])) $addr_parts[] = $p_data['dia_chi_phuong_xa'];
        if (!empty($p_data['dia_chi_tinh_tp'])) $addr_parts[] = $p_data['dia_chi_tinh_tp'];
        
        $default_address = implode(', ', $addr_parts);

        $stmt = $pdo->prepare("INSERT INTO car_inspections (project_id, inspector_id, inspection_date, inspection_time, project_address, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$_POST['project_id'], $_SESSION['user_id'], $_POST['inspection_date'], $_POST['inspection_time'], $default_address]);
        set_message("success", "Đã đặt lịch kiểm tra thành công!");
        header("Location: index.php?page=car_inspections/list&month=" . substr($_POST['inspection_date'], 0, 7));
        exit;
    } catch (PDOException $e) {
        set_message("error", "Lỗi: " . $e->getMessage());
    }
}

// Xử lý cho module devices/edit
if ($page === 'devices/edit') {
    $device_id = $_GET['id'] ?? null;
    $device = null;
    if ($device_id) {
        $stmt = $pdo->prepare("SELECT * FROM devices WHERE id = ?");
        $stmt->execute([$device_id]);
        $device = $stmt->fetch();
    }
    if (!$device) {
        set_message('error', 'Thiết bị không tìm thấy!');
        header("Location: index.php?page=devices/list");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validation và Update logic
        if (empty($_POST['ma_tai_san'])) set_message('error', 'Mã tài sản là bắt buộc.');
        if (empty($_POST['ten_thiet_bi'])) set_message('error', 'Tên thiết bị là bắt buộc.');

        if (!isset($_SESSION['messages']) || empty(array_filter($_SESSION['messages'], function($msg) { return $msg['type'] === 'error'; }))) {
            try {
                $sql = "UPDATE devices SET
                            ma_tai_san = ?, ten_thiet_bi = ?, nhom_thiet_bi = ?, loai_thiet_bi = ?, model = ?, serial = ?,
                            project_id = ?, parent_id = ?, supplier_id = ?, ngay_mua = ?, gia_mua = ?, bao_hanh_den = ?, trang_thai = ?, ghi_chu = ?
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $_POST['ma_tai_san'], $_POST['ten_thiet_bi'], $_POST['nhom_thiet_bi'], $_POST['loai_thiet_bi'], $_POST['model'], $_POST['serial'],
                    $_POST['project_id'] ?: null, $_POST['parent_id'] ?: null, $_POST['supplier_id'] ?: null, $_POST['ngay_mua'] ?: null, 
                    $_POST['gia_mua'] ?: null, $_POST['bao_hanh_den'] ?: null, $_POST['trang_thai'], $_POST['ghi_chu'], $device_id
                ]);
                set_message('success', 'Thiết bị đã được cập nhật thành công!');
                header("Location: index.php?page=devices/view&id=" . $device_id);
                exit;
            } catch (PDOException $e) {
                set_message('error', 'Lỗi khi cập nhật thiết bị: ' . $e->getMessage());
            }
        }
    }
}

// Xử lý cho module car_inspections/edit
if ($page === 'car_inspections/edit') {
    $id = $_GET['id'] ?? null;
    // Kiểm tra tồn tại
    $ins = null;
    if ($id) {
        $stmt = $pdo->prepare("SELECT id FROM car_inspections WHERE id = ?");
        $stmt->execute([$id]);
        $ins = $stmt->fetch();
    }
    if (!$ins) {
        set_message('error', 'Không tìm thấy biên bản kiểm tra!');
        header("Location: index.php?page=car_inspections/list");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $sql = "UPDATE car_inspections SET 
                    violation_count = ?, violation_details = ?, 
                    results_summary = ?, other_opinions = ?, status = ?,
                    inspection_date = ?, inspection_time = ?, project_address = ?,
                    inspector_id = ?, inspector_position = ?, 
                    bql_name_1 = ?, bql_pos_1 = ?, bql_name_2 = ?, bql_pos_2 = ?
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['violation_count'], $_POST['violation_details'], $_POST['results_summary'], 
                $_POST['other_opinions'], $_POST['status'], 
                $_POST['inspection_date'], $_POST['inspection_time'], $_POST['project_address'],
                $_POST['inspector_id'], $_POST['inspector_position'], 
                $_POST['bql_name_1'], $_POST['bql_pos_1'], $_POST['bql_name_2'], $_POST['bql_pos_2'],
                $id
            ]);
            
            set_message("success", "Cập nhật biên bản kiểm tra thành công!");
            header("Location: index.php?page=car_inspections/list"); 
            exit;
        } catch (PDOException $e) {
            set_message("error", "Lỗi: " . $e->getMessage());
        }
    }
}

// Xử lý cho module car_inspections/delete
if ($page === 'car_inspections/delete') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM car_inspections WHERE id = ?");
            $stmt->execute([$id]);
            set_message("success", "Đã xóa lịch kiểm tra thành công!");
        } catch (PDOException $e) {
            set_message("error", "Lỗi khi xóa: " . $e->getMessage());
        }
    }
    header("Location: index.php?page=car_inspections/list");
    exit;
}

$requested_file = __DIR__ . '/../modules/' . $page . '.php';
$base_path = realpath(__DIR__ . '/../modules');
$module_path = realpath($requested_file);

// --- KIỂM TRA NẾU LÀ TRANG EXPORT HOẶC PRINT HOẶC DELETE (KHÔNG HIỂN THỊ GIAO DIỆN) ---
$is_export = (strpos($page, 'export') !== false || strpos($page, 'print') !== false || strpos($page, 'delete') !== false);

if (!$is_export) {
    include_once __DIR__ . '/../includes/header.php';
    display_messages();
}

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

    <style>
        /* Chỉ tập trung fix UI cho 2 thẻ biểu đồ trên Mobile */
        @media (max-width: 768px) {
            .chart-canvas-wrapper {
                height: 260px !important; /* Chiều cao vừa vặn cho điện thoại */
                position: relative;
                width: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .chart-canvas-wrapper.doughnut {
                height: 300px !important; /* Biểu đồ tròn cần cao hơn chút để hiện Legend */
            }
            .charts-grid .card {
                padding: 15px !important;
                margin-bottom: 15px !important;
            }
            .dashboard-card-header h3 {
                font-size: 0.95rem !important;
            }
        }
        
        /* Đảm bảo canvas luôn chiếm hết wrapper */
        .chart-canvas-wrapper canvas {
            max-width: 100% !important;
            max-height: 100% !important;
        }
    </style>

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
                    <span class="stat-label">Hỏng / Cảnh báo</span>
                    <span class="stat-value"><?= number_format($broken_devices) ?></span>
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
                    <h3><i class="fas fa-chart-bar" style="color: #3b82f6;"></i> Phân loại (Top 5)</h3>
                </div>
                <div class="chart-canvas-wrapper">
                    <canvas id="deviceTypeChart"></canvas>
                </div>
            </div>

            <!-- Side Chart: Status (Doughnut) -->
            <div class="card">
                <div class="dashboard-card-header">
                    <h3><i class="fas fa-chart-pie" style="color: #10b981;"></i> Tình trạng</h3>
                </div>
                <div class="chart-canvas-wrapper doughnut">
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
                            <?php 
                                $deviceName = !empty($activity['ten_thiet_bi']) ? $activity['ten_thiet_bi'] : ($activity['custom_device_name'] ?? 'Thiết bị không xác định');
                                $deviceCode = !empty($activity['ma_tai_san']) ? " (" . $activity['ma_tai_san'] . ")" : "";
                            ?>
                            <li class="feed-item">
                                <div class="feed-icon">
                                    <i class="fas fa-wrench"></i>
                                </div>
                                <div class="feed-content">
                                    <h4><?= htmlspecialchars($deviceName . $deviceCode) ?></h4>
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

                <?php if (empty($overdue_warranty_devices) && empty($expiring_services)): ?>
                    <div style="text-align: center; padding: 40px 20px;">
                        <i class="fas fa-check-circle" style="font-size: 3rem; color: #10b981; margin-bottom: 15px;"></i>
                        <p style="font-weight: 500; color: #059669;">Mọi thứ đều ổn định!</p>
                        <p style="color: #64748b; font-size: 0.9rem;">Không có cảnh báo khẩn cấp nào.</p>
                    </div>
                <?php else: ?>
                    <ul class="action-list">
                        <?php foreach ($overdue_warranty_devices as $device): ?>
                            <li>
                                <a href="index.php?page=devices/view&id=<?= $device['id'] ?>" class="action-item danger">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <div class="action-content">
                                        <span class="action-title"><?= htmlspecialchars($device['ma_tai_san']) ?> - <?= htmlspecialchars($device['ten_thiet_bi']) ?></span>
                                        <span class="action-desc">ĐÃ HẾT BẢO HÀNH: <?= date('d/m/Y', strtotime($device['bao_hanh_den'])) ?></span>
                                    </div>
                                    <i class="fas fa-chevron-right action-arrow"></i>
                                </a>
                            </li>
                        <?php endforeach; ?>

                        <?php foreach ($expiring_services as $service): ?>
                            <li>
                                <a href="index.php?page=services/view&id=<?= $service['id'] ?>" class="action-item <?= ($service['days_left'] <= 0) ? 'danger' : 'warning' ?>">
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
    <?php
        // Prepare colors for chart
        $chart_colors = [];
        $status_configs = $pdo->query("SELECT status_name, color_class FROM settings_device_statuses")->fetchAll(PDO::FETCH_KEY_PAIR);
        $color_map = [
            'status-active' => '#10b981', // Green
            'status-warning' => '#f59e0b', // Amber
            'status-error' => '#ef4444', // Red
            'status-info' => '#3b82f6', // Blue
            'status-default' => '#94a3b8' // Slate/Gray
        ];
        
        foreach (array_keys($device_status_stats) as $s_name) {
            $class = $status_configs[$s_name] ?? 'status-default';
            $chart_colors[] = $color_map[$class] ?? '#94a3b8';
        }
    ?>
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
                        backgroundColor: <?= json_encode($chart_colors) ?>,
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
if (!$is_export) {
    include_once __DIR__ . '/../includes/footer.php';
}