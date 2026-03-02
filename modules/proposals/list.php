<?php
// modules/proposals/list.php
$rows_per_page = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : 10;
$current_page  = (isset($_GET['p']) && is_numeric($_GET['p'])) ? (int)$_GET['p'] : 1;
if ($current_page < 1) $current_page = 1;

$filter_keyword = trim($_GET['filter_keyword'] ?? '');
$filter_status = trim($_GET['filter_status'] ?? '');

$where_clauses = ["p.deleted_at IS NULL"];
$bind_params   = [];

if ($filter_keyword !== '') {
    $where_clauses[] = "(p.proposal_number LIKE :kw1 OR p.title LIKE :kw2 OR p.amount_in_words LIKE :kw3)";
    $bind_params[':kw1'] = $bind_params[':kw2'] = $bind_params[':kw3'] = '%' . $filter_keyword . '%';
}

if ($filter_status !== '') {
    $where_clauses[] = "p.status = :status";
    $bind_params[':status'] = $filter_status;
}

$where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

// Count Total
$count_sql = "SELECT COUNT(*) FROM internal_proposals p $where_sql";
$count_stmt = $pdo->prepare($count_sql);
foreach ($bind_params as $key => $value) $count_stmt->bindValue($key, $value);
$count_stmt->execute();
$total_rows  = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $rows_per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $rows_per_page;

// Fetch Data
$data_sql = "SELECT p.*, u.fullname as proposer_name 
             FROM internal_proposals p 
             LEFT JOIN users u ON p.proposer_id = u.id 
             $where_sql 
             ORDER BY p.proposal_date DESC, p.id DESC 
             LIMIT :limit OFFSET :offset";
             
$stmt = $pdo->prepare($data_sql);
foreach ($bind_params as $key => $value) $stmt->bindValue($key, $value);
$stmt->bindValue(':limit',  $rows_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="form-container" style="margin: 0 auto;">
    <div class="page-header">
        <h2><i class="fas fa-file-invoice-dollar"></i> Đề xuất nội bộ IT</h2>
        <?php if(isIT()): ?><a href="index.php?page=proposals/add" class="btn btn-primary"><i class="fas fa-plus"></i> Tạo đề xuất mới</a><?php endif; ?>
    </div>

<div class="card filter-section-modern">
    <form action="index.php" method="GET" class="filter-form-modern">
        <input type="hidden" name="page" value="proposals/list">
        <div class="filter-main-grid">
            <div class="filter-item" style="flex: 2;">
                <label>Tìm kiếm từ khóa</label>
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="filter_keyword" placeholder="Số đề xuất, tiêu đề, số tiền bằng chữ..." value="<?php echo htmlspecialchars($filter_keyword); ?>" class="form-control-sm">
                </div>
            </div>
            <div class="filter-item">
                <label>Trạng thái</label>
                <select name="filter_status" class="form-select-sm auto-submit-filter" onchange="this.form.submit()">
                    <option value="">-- Tất cả --</option>
                    <option value="Draft" <?php echo $filter_status === 'Draft' ? 'selected' : ''; ?>>Bản nháp</option>
                    <option value="Pending" <?php echo $filter_status === 'Pending' ? 'selected' : ''; ?>>Chờ duyệt</option>
                    <option value="Approved" <?php echo $filter_status === 'Approved' ? 'selected' : ''; ?>>Đã duyệt</option>
                    <option value="Rejected" <?php echo $filter_status === 'Rejected' ? 'selected' : ''; ?>>Từ chối</option>
                </select>
            </div>
            <div class="filter-item" style="flex: 0 0 auto; justify-content: flex-end;">
                <label>&nbsp;</label>
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Lọc</button>
                    <a href="index.php?page=proposals/list" class="btn btn-secondary btn-sm" title="Xóa lọc"><i class="fas fa-undo"></i></a>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
/* CSS MODERN FILTER - ĐỒNG BỘ DASHBOARD & DEVICES */
.filter-section-modern { 
    padding: 20px; 
    margin-bottom: 25px; 
    background: #fff; 
    border-radius: 12px; 
    box-shadow: 0 2px 12px rgba(0,0,0,0.06); 
    border-left: 5px solid var(--primary-color) !important;
}
.filter-form-modern { display: flex; flex-direction: column; gap: 15px; }
.filter-main-grid { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
.filter-item { display: flex; flex-direction: column; gap: 6px; min-width: 150px; }
.filter-item label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }

.search-input-wrapper { position: relative; width: 100%; }
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem; }
.search-input-wrapper input { padding-left: 38px !important; width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; height: 38px; transition: all 0.2s; }
.search-input-wrapper input:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(36, 162, 92, 0.1); outline: none; }

.form-select-sm { height: 38px !important; border: 1px solid #cbd5e1 !important; border-radius: 8px !important; font-size: 0.9rem !important; padding: 0 12px !important; transition: all 0.2s !important; }
.form-select-sm:focus { border-color: var(--primary-color) !important; box-shadow: 0 0 0 3px rgba(36, 162, 92, 0.1) !important; outline: none !important; }

/* Highlight Active Filter */
.form-select-sm.active-filter, 
select[name="filter_status"] option:not([value=""]):checked ~ select {
    border: 2px solid var(--primary-color) !important;
    background-color: #f0fdf4 !important;
}

.filter-buttons { display: flex; gap: 10px; }
.filter-buttons .btn { height: 38px; padding: 0 18px; border-radius: 8px; font-weight: 600; }

@media (max-width: 768px) {
    .filter-main-grid { flex-direction: column; align-items: stretch; gap: 12px; }
    .filter-item { width: 100% !important; }
    .filter-buttons { display: grid; grid-template-columns: 1fr 45px; }
}

/* Thêm hiệu ứng cho hàng trong bảng */
.content-table tbody tr { transition: background-color 0.2s; }
.content-table tbody tr:hover { background-color: #f8fafc; }
.font-medium { font-weight: 500; }
.text-right { text-align: right; }
</style>

<div class="table-container card">
    <table class="content-table">
        <thead>
            <tr>
                <th width="150">Số đề xuất</th>
                <th width="120">Ngày lập</th>
                <th>Tiêu đề / Căn cứ</th>
                <th width="150" class="text-right">Tổng tiền (VAT)</th>
                <th width="120" class="text-center">Người lập</th>
                <th width="120" class="text-center">Trạng thái</th>
                <th width="120" class="text-center">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($proposals)): ?>
                <tr><td colspan="7" class="text-center">Chưa có đề xuất nào.</td></tr>
            <?php else: ?>
                <?php foreach ($proposals as $p): ?>
                    <tr>
                        <td class="font-medium text-primary"><?php echo htmlspecialchars($p['proposal_number'] ?: '---'); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($p['proposal_date'])); ?></td>
                        <td><?php echo htmlspecialchars($p['title']); ?></td>
                        <td class="text-right font-bold"><?php echo number_format($p['total_amount_after_vat'], 0, ',', '.'); ?> ₫</td>
                        <td class="text-center"><?php echo htmlspecialchars($p['proposer_name']); ?></td>
                        <td class="text-center">
                            <?php 
                            $status_map = [
                                'Draft' => ['Bản nháp', 'status-default'],
                                'Pending' => ['Chờ duyệt', 'status-warning'],
                                'Approved' => ['Đã duyệt', 'status-active'],
                                'Rejected' => ['Từ chối', 'status-error']
                            ];
                            $curr_status = $status_map[$p['status']] ?? [$p['status'], 'status-default'];
                            ?>
                            <span class="badge <?php echo $curr_status[1]; ?>"><?php echo $curr_status[0]; ?></span>
                        </td>
                        <td class="actions text-center">
                            <a href="index.php?page=proposals/print&id=<?php echo $p['id']; ?>" class="btn-icon" title="In A4" target="_blank"><i class="fas fa-print"></i></a>
                            <?php if(isIT()): ?>
                                <a href="index.php?page=proposals/edit&id=<?php echo $p['id']; ?>" class="btn-icon" title="Sửa"><i class="fas fa-edit"></i></a>
                                <?php if(isAdmin()): ?>
                                    <a href="index.php?page=proposals/delete&id=<?php echo $p['id']; ?>" class="btn-icon delete-btn" title="Xóa"><i class="fas fa-trash-alt"></i></a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="pagination-container">
    <div class="rows-per-page">
        <form action="index.php" method="GET" class="rows-per-page-form">
            <input type="hidden" name="page" value="proposals/list">
            <label>Hiển thị</label>
            <select name="limit" onchange="this.form.submit()" class="form-select-sm" style="width: auto;">
                <?php foreach([10,25,50,100] as $lim): ?>
                    <option value="<?php echo $lim; ?>" <?php echo $rows_per_page == $lim ? 'selected' : ''; ?>><?php echo $lim; ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="pagination-links">
        <?php $q = $_GET; unset($q['p']); $base = 'index.php?' . http_build_query($q); ?>
        <a href="<?php echo $base . '&p=1'; ?>" class="page-link <?php echo $current_page <= 1 ? 'disabled' : ''; ?>"><i class="fas fa-angle-double-left"></i></a>
        <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
            <a href="<?php echo $base . '&p=' . $i; ?>" class="page-link <?php echo $i == $current_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <a href="<?php echo $base . '&p=' . $total_pages; ?>" class="page-link <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>"><i class="fas fa-angle-double-right"></i></a>
    </div>
</div>
</div>
