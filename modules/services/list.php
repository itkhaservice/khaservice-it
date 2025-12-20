<?php
$rows_per_page = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : 10;
$current_page  = (isset($_GET['p']) && is_numeric($_GET['p'])) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

$filter_keyword = trim($_GET['filter_keyword'] ?? '');
$where_sql = $filter_keyword !== '' ? " WHERE (s.ten_dich_vu LIKE :kw OR s.loai_dich_vu LIKE :kw OR p.ten_du_an LIKE :kw)" : "";
$bind_params = $filter_keyword !== '' ? [':kw' => '%' . $filter_keyword . '%'] : [];

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM services s LEFT JOIN projects p ON s.project_id = p.id $where_sql");
foreach ($bind_params as $k => $v) $count_stmt->bindValue($k, $v);
$count_stmt->execute();
$total_rows  = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $rows_per_page));
$offset = ($current_page - 1) * $rows_per_page;

$data_sql = "
    SELECT s.*, p.ten_du_an, sup.ten_npp 
    FROM services s 
    LEFT JOIN projects p ON s.project_id = p.id 
    LEFT JOIN suppliers sup ON s.supplier_id = sup.id
    $where_sql 
    ORDER BY s.ngay_het_han ASC 
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($data_sql);
foreach ($bind_params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $rows_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h2><i class="fas fa-cloud"></i> Quản lý Dịch vụ & Gia hạn</h2>
    <?php if(isIT()): ?><a href="index.php?page=services/add" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm dịch vụ</a><?php endif; ?>
</div>

<div class="card filter-section">
    <form action="index.php" method="GET" class="filter-form">
        <input type="hidden" name="page" value="services/list">
        <div class="filter-group" style="flex: 2;">
            <label>Tìm kiếm</label>
            <input type="text" name="filter_keyword" placeholder="Tên dịch vụ, dự án..." value="<?php echo htmlspecialchars($filter_keyword); ?>">
        </div>
        <div class="filter-actions" style="margin-left: auto;">
            <button type="submit" class="btn btn-primary">Lọc</button>
            <a href="index.php?page=services/list" class="btn btn-secondary"><i class="fas fa-undo"></i></a>
        </div>
    </form>
</div>

<div class="table-container card">
    <table class="content-table">
        <thead>
            <tr>
                <th>Tên Dịch vụ</th>
                <th>Dự án</th>
                <th>Ngày hết hạn</th>
                <th>Còn lại</th>
                <th>Đề nghị TT</th>
                <th>Trạng thái</th>
                <th width="100" class="text-center">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($services)): ?>
                <tr><td colspan="7" class="empty-state">Chưa có dịch vụ nào</td></tr>
            <?php else: ?>
                <?php foreach ($services as $s): 
                    $today = new DateTime();
                    $expiry = new DateTime($s['ngay_het_han']);
                    $diff = $today->diff($expiry);
                    $days_left = (int)$diff->format("%r%a");
                    
                    $status_class = "text-success";
                    if ($days_left <= 0) $status_class = "text-danger font-bold";
                    elseif ($days_left <= 30) $status_class = "text-warning font-bold";
                ?>
                    <tr>
                        <td>
                            <div class="font-bold"><?php echo htmlspecialchars($s['ten_dich_vu']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($s['loai_dich_vu']); ?> - <?php echo htmlspecialchars($s['ten_npp'] ?? 'N/A'); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($s['ten_du_an'] ?: "Dùng chung"); ?></td>
                        <td class="<?php echo $status_class; ?>"><?php echo date('d/m/Y', strtotime($s['ngay_het_han'])); ?></td>
                        <td class="<?php echo $status_class; ?>">
                            <?php 
                                if($days_left < 0) echo "Quá hạn " . abs($days_left) . " ngày";
                                elseif($days_left == 0) echo "Hết hạn hôm nay";
                                else echo $days_left . " ngày";
                            ?>
                        </td>
                        <td>
                            <?php if($s['ngay_nhan_de_nghi']): ?>
                                <span class="text-info"><i class="fas fa-envelope-open-text"></i> <?php echo date('d/m/Y', strtotime($s['ngay_nhan_de_nghi'])); ?></span>
                            <?php else: ?>
                                <span class="text-muted">Chưa nhận</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                                $trang_thai = $s['trang_thai'];
                                $badge_class = "status-info";
                                if($trang_thai === 'Chờ thanh toán') $badge_class = "status-warning";
                                if($trang_thai === 'Đang hoạt động') $badge_class = "status-active";
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($trang_thai); ?></span>
                        </td>
                        <td class="actions text-center">
                            <a href="index.php?page=services/view&id=<?php echo $s['id']; ?>" class="btn-icon" title="Chi tiết"><i class="fas fa-eye"></i></a>
                            <?php if(isIT()): ?><a href="index.php?page=services/edit&id=<?php echo $s['id']; ?>" class="btn-icon" title="Sửa"><i class="fas fa-edit"></i></a><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>