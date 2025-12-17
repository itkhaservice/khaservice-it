<?php
$search_query = $_GET['search_query'] ?? '';
$search_query = trim($search_query); // Remove leading/trailing whitespace

$devices_results = [];
$projects_results = [];
$suppliers_results = [];

if (!empty($search_query)) {
    $search_param = '%' . $search_query . '%';

    // Search in devices
    $stmt_devices = $pdo->prepare("
        SELECT d.id, d.ma_tai_san, d.ten_thiet_bi, d.loai_thiet_bi, d.serial, p.ten_du_an, s.ten_npp
        FROM devices d
        LEFT JOIN projects p ON d.project_id = p.id
        LEFT JOIN suppliers s ON d.supplier_id = s.id
        WHERE d.ma_tai_san LIKE ? OR d.ten_thiet_bi LIKE ? OR d.serial LIKE ?
        ORDER BY d.ten_thiet_bi
    ");
    $stmt_devices->execute([$search_param, $search_param, $search_param]);
    $devices_results = $stmt_devices->fetchAll();

    // Search in projects
    $stmt_projects = $pdo->prepare("
        SELECT id, ma_du_an, ten_du_an, dia_chi
        FROM projects
        WHERE ma_du_an LIKE ? OR ten_du_an LIKE ?
        ORDER BY ten_du_an
    ");
    $stmt_projects->execute([$search_param, $search_param]);
    $projects_results = $stmt_projects->fetchAll();

    // Search in suppliers
    $stmt_suppliers = $pdo->prepare("
        SELECT id, ten_npp, nguoi_lien_he, dien_thoai
        FROM suppliers
        WHERE ten_npp LIKE ? OR nguoi_lien_he LIKE ?
        ORDER BY ten_npp
    ");
    $stmt_suppliers->execute([$search_param, $search_param]);
    $suppliers_results = $stmt_suppliers->fetchAll();
}
?>

<h2>Kết quả tìm kiếm cho "<?php echo htmlspecialchars($search_query); ?>"</h2>

<?php if (empty($search_query)): ?>
    <p>Vui lòng nhập từ khóa để tìm kiếm.</p>
<?php else: ?>
    <?php if (empty($devices_results) && empty($projects_results) && empty($suppliers_results)): ?>
        <p>Không tìm thấy kết quả nào phù hợp.</p>
    <?php else: ?>

        <?php if (!empty($devices_results)): ?>
            <h3>Thiết bị</h3>
            <div class="content-table-wrapper">
                <table class="content-table">
                    <thead>
                        <tr>
                            <th>Mã Tài sản</th>
                            <th>Tên Thiết bị</th>
                            <th>Loại Thiết bị</th>
                            <th>Serial</th>
                            <th>Dự án</th>
                            <th>Nhà cung cấp</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($devices_results as $device): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($device['ma_tai_san']); ?></td>
                                <td><?php echo htmlspecialchars($device['ten_thiet_bi']); ?></td>
                                <td><?php echo htmlspecialchars($device['loai_thiet_bi']); ?></td>
                                <td><?php echo htmlspecialchars($device['serial']); ?></td>
                                <td><?php echo htmlspecialchars($device['ten_du_an'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($device['ten_npp'] ?? 'N/A'); ?></td>
                                <td class="actions">
                                    <a href="index.php?page=devices/view&id=<?php echo $device['id']; ?>" class="btn view-btn">Xem</a>
                                    <a href="index.php?page=devices/edit&id=<?php echo $device['id']; ?>" class="btn edit-btn">Sửa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (!empty($projects_results)): ?>
            <h3>Dự án</h3>
            <div class="content-table-wrapper">
                <table class="content-table">
                    <thead>
                        <tr>
                            <th>Mã Dự án</th>
                            <th>Tên Dự án</th>
                            <th>Địa chỉ</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects_results as $project): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($project['ma_du_an']); ?></td>
                                <td><?php echo htmlspecialchars($project['ten_du_an']); ?></td>
                                <td><?php echo htmlspecialchars($project['dia_chi']); ?></td>
                                <td class="actions">
                                    <a href="index.php?page=projects/edit&id=<?php echo $project['id']; ?>" class="btn edit-btn">Sửa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (!empty($suppliers_results)): ?>
            <h3>Nhà cung cấp</h3>
            <div class="content-table-wrapper">
                <table class="content-table">
                    <thead>
                        <tr>
                            <th>Tên Nhà phân phối</th>
                            <th>Người liên hệ</th>
                            <th>Điện thoại</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers_results as $supplier): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($supplier['ten_npp']); ?></td>
                                <td><?php echo htmlspecialchars($supplier['nguoi_lien_he']); ?></td>
                                <td><?php echo htmlspecialchars($supplier['dien_thoai']); ?></td>
                                <td class="actions">
                                    <a href="index.php?page=suppliers/edit&id=<?php echo $supplier['id']; ?>" class="btn edit-btn">Sửa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    <?php endif; ?>
<?php endif; ?>
