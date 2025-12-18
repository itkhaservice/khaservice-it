<?php
session_start(); // Start session once at the top
require_once __DIR__ . '/../includes/remember_me_check.php'; // Check for remember me cookie
require_once __DIR__ . '/../includes/auth.php'; // Handle authentication
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/messages.php'; // Include messages helper

// --- Dashboard Data Fetching Logic ---
if (($page ?? 'home') === 'home') { // Only fetch dashboard data if on the home page
    try {
        // KPI Cards Data
        $total_devices = $pdo->query("SELECT COUNT(id) FROM devices")->fetchColumn();
        $devices_nearing_warranty = $pdo->query("SELECT COUNT(id) FROM devices WHERE bao_hanh_den IS NOT NULL AND bao_hanh_den BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)")->fetchColumn();
        $broken_or_liquidated_devices = $pdo->query("SELECT COUNT(id) FROM devices WHERE trang_thai IN ('Hỏng', 'Thanh lý')")->fetchColumn();
        $total_maintenance_logs = $pdo->query("SELECT COUNT(id) FROM maintenance_logs")->fetchColumn();

        // Action Zone - Overdue Warranty Devices
        $overdue_warranty_devices_stmt = $pdo->prepare("SELECT id, ma_tai_san, ten_thiet_bi, bao_hanh_den FROM devices WHERE bao_hanh_den IS NOT NULL AND bao_hanh_den < CURDATE() LIMIT 5");
        $overdue_warranty_devices_stmt->execute();
        $overdue_warranty_devices = $overdue_warranty_devices_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Action Zone - Broken Devices with liquidation notes
        $broken_with_liquidation_notes_stmt = $pdo->prepare("SELECT id, ma_tai_san, ten_thiet_bi, ghi_chu FROM devices WHERE (trang_thai = 'Hỏng' OR trang_thai = 'Thanh lý') AND ghi_chu LIKE '%thanh lý%' LIMIT 5");
        $broken_with_liquidation_notes_stmt->execute();
        $broken_with_liquidation_notes = $broken_with_liquidation_notes_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Summary Table - Device Group Summary (Office/Parking)
        $device_group_summary_stmt = $pdo->query("
            SELECT
                nhom_thiet_bi,
                COUNT(id) AS total_devices,
                COUNT(CASE WHEN trang_thai = 'Đang sử dụng' THEN 1 END) AS in_use,
                COUNT(CASE WHEN trang_thai IN ('Hỏng', 'Thanh lý') THEN 1 END) AS broken_or_liquidated
            FROM devices
            GROUP BY nhom_thiet_bi
            ORDER BY nhom_thiet_bi
        ");
        $device_group_summary = $device_group_summary_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Summary Table - Top 5 Projects with Most Broken Devices
        $top_5_broken_projects_stmt = $pdo->query("
            SELECT
                p.ma_du_an,
                p.ten_du_an,
                COUNT(d.id) AS broken_devices_count,
                MAX(ml.ngay_su_co) AS last_maintenance_date
            FROM devices d
            JOIN projects p ON d.project_id = p.id
            LEFT JOIN maintenance_logs ml ON d.id = ml.device_id
            WHERE d.trang_thai IN ('Hỏng', 'Thanh lý')
            GROUP BY p.id, p.ma_du_an, p.ten_du_an
            ORDER BY broken_devices_count DESC, last_maintenance_date DESC
            LIMIT 5
        ");
        $top_5_broken_projects = $top_5_broken_projects_stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // Handle database errors (e.g., log, display a generic message)
        error_log("Dashboard data fetch error: " . $e->getMessage());
        // Set variables to empty arrays/default values to prevent errors in HTML
        $total_devices = 0;
        $devices_nearing_warranty = 0;
        $broken_or_liquidated_devices = 0;
        $total_maintenance_logs = 0;
        $overdue_warranty_devices = [];
        $broken_with_liquidation_notes = [];
        $device_group_summary = [];
        $top_5_broken_projects = [];
        set_message("Đã xảy ra lỗi khi tải dữ liệu trang tổng quan. Vui lòng thử lại sau.", "error");
    }
}
// --- End Dashboard Data Fetching Logic ---

// The header already starts the session and has the necessary HTML boilerplate
include_once __DIR__ . '/../includes/header.php';

// Display messages
display_messages(); // Call to display messages

// Simple router based on 'page' GET parameter
$page = $_GET['page'] ?? 'home';

// Sanitize the page parameter to prevent directory traversal.
// We allow a-z, A-Z, 0-9, and the forward slash '/' for subdirectories.
$page = preg_replace('/[^a-zA-Z0-9\/_.-]/', '', $page); // Allow safe characters

// Construct the full path
$requested_file = __DIR__ . '/../modules/' . $page . '.php';

// Normalize paths and check if the requested file is within the modules directory
$base_path = realpath(__DIR__ . '/../modules');
$module_path = realpath($requested_file);

// Check if the resolved path is inside the modules directory and the file exists
if ($module_path === false || strpos($module_path, $base_path) !== 0) {
    // If not, invalidate the path to trigger the 'else' block.
    $module_path = false;
}

if ($module_path && file_exists($module_path)) {
    include $module_path;
} else { // This is the dashboard content
?>
    <div class="dashboard-grid">
        <!-- KPI Cards -->
        <div class="kpi-cards">
            <div class="kpi-card">
                <span class="kpi-label">Tổng Thiết bị Đang Quản lý</span>
                <span class="kpi-value"><?= htmlspecialchars($total_devices) ?></span>
                <span class="kpi-icon"><i class="fas fa-microchip"></i></span>
            </div>
            <div class="kpi-card warning">
                <span class="kpi-label">Thiết bị Sắp Hết Bảo hành (90 ngày)</span>
                <span class="kpi-value"><?= htmlspecialchars($devices_nearing_warranty) ?></span>
                <span class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></span>
            </div>
            <div class="kpi-card error">
                <span class="kpi-label">Thiết bị Đang Hỏng / Thanh lý</span>
                <span class="kpi-value"><?= htmlspecialchars($broken_or_liquidated_devices) ?></span>
                <span class="kpi-icon"><i class="fas fa-times-circle"></i></span>
            </div>
            <div class="kpi-card info">
                <span class="kpi-label">Tổng Lượt Sự cố/Sửa chữa</span>
                <span class="kpi-value"><?= htmlspecialchars($total_maintenance_logs) ?></span>
                <span class="kpi-icon"><i class="fas fa-wrench"></i></span>
            </div>
        </div>

        <!-- Action Zone -->
        <div class="action-zone-card card">
            <h3>Cần Hành động Khẩn cấp</h3>
            <?php if (empty($overdue_warranty_devices) && empty($broken_with_liquidation_notes)): ?>
                <p class="no-action-needed">Không có hành động khẩn cấp nào cần thực hiện.</p>
            <?php else: ?>
                <ul class="action-list">
                    <?php foreach ($overdue_warranty_devices as $device): ?>
                        <li>
                            <i class="fas fa-clock action-icon"></i>
                            <a href="index.php?page=devices/view&id=<?= $device['id'] ?>">Thiết bị <?= htmlspecialchars($device['ma_tai_san']) ?>: Quá hạn bảo hành (<?= date('d/m/Y', strtotime($device['bao_hanh_den'])) ?>)</a>
                        </li>
                    <?php endforeach; ?>
                    <?php foreach ($broken_with_liquidation_notes as $device): ?>
                        <li>
                            <i class="fas fa-trash-alt action-icon"></i>
                            <a href="index.php?page=devices/view&id=<?= $device['id'] ?>">Thiết bị <?= htmlspecialchars($device['ma_tai_san']) ?>: Hỏng/Thanh lý (Ghi chú: <?= htmlspecialchars($device['ghi_chu']) ?>)</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Summary Tables -->
        <div class="summary-tables">
            <div class="card summary-table-card">
                <h3>Tổng hợp theo Nhóm Thiết bị</h3>
                <div class="content-table-wrapper">
                    <table class="content-table">
                        <thead>
                            <tr>
                                <th>Nhóm Thiết bị</th>
                                <th>Tổng số</th>
                                <th>Đang sử dụng</th>
                                <th>Hỏng / Thanh lý</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($device_group_summary)): ?>
                                <tr><td colspan="4">Không có dữ liệu nhóm thiết bị.</td></tr>
                            <?php else: ?>
                                <?php foreach ($device_group_summary as $group): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($group['nhom_thiet_bi']) ?></td>
                                        <td><?= htmlspecialchars($group['total_devices']) ?></td>
                                        <td><?= htmlspecialchars($group['in_use']) ?></td>
                                        <td><?= htmlspecialchars($group['broken_or_liquidated']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card summary-table-card">
                <h3>Top 5 Dự án có nhiều thiết bị hỏng nhất</h3>
                <div class="content-table-wrapper">
                    <table class="content-table">
                        <thead>
                            <tr>
                                <th>Mã Dự án</th>
                                <th>Tên Dự án</th>
                                <th>Số thiết bị hỏng</th>
                                <th>Ngày sửa chữa gần nhất</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($top_5_broken_projects)): ?>
                                <tr><td colspan="4">Không có dữ liệu dự án hỏng.</td></tr>
                            <?php else: ?>
                                <?php foreach ($top_5_broken_projects as $project): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($project['ma_du_an']) ?></td>
                                        <td><?= htmlspecialchars($project['ten_du_an']) ?></td>
                                        <td><?= htmlspecialchars($project['broken_devices_count']) ?></td>
                                        <td><?= $project['last_maintenance_date'] ? date('d/m/Y', strtotime($project['last_maintenance_date'])) : 'N/A' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php
}
?>

// Custom Confirmation Modal HTML
?>
<div id="customConfirmModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Xác nhận hành động</h3>
            <span class="close-button">&times;</span>
        </div>
        <div class="modal-body">
            <p id="modalMessage">Bạn có chắc chắn muốn thực hiện hành động này?</p>
        </div>
        <div class="modal-footer">
            <button id="confirmBtn" class="btn btn-danger">Xác nhận</button>
            <button id="cancelBtn" class="btn btn-secondary">Hủy</button>
        </div>
    </div>
</div>

<?php
include_once __DIR__ . '/../includes/footer.php';
