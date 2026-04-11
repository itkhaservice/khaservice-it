<?php
// modules/it_activities/print.php
if (!isset($pdo)) {
    require_once __DIR__ . '/../../config/db.php';
}

$id = $_GET['id'] ?? null;
if (!$id) die("Missing ID");

// Fetch main info with advanced project address handling
$stmt = $pdo->prepare("SELECT h.*, p.ten_du_an, p.ma_du_an, p.dia_chi, p.dia_chi_duong, p.dia_chi_phuong_xa, p.dia_chi_tinh_tp, u.fullname as checker_name 
                      FROM it_system_health_checks h
                      JOIN projects p ON h.project_id = p.id
                      JOIN users u ON h.checked_by = u.id
                      WHERE h.id = ?");
$stmt->execute([$id]);
$check = $stmt->fetch();
if (!$check) die("Not found");

// Build full address if 'dia_chi' is empty
$display_address = $check['dia_chi'];
if (empty($display_address)) {
    $addr_parts = array_filter([$check['dia_chi_duong'], $check['dia_chi_phuong_xa'], $check['dia_chi_tinh_tp']]);
    $display_address = implode(', ', $addr_parts);
}

// Fetch details and Build Tree grouped by Category
$stmt = $pdo->prepare("SELECT d.*, dev.ten_thiet_bi, dev.ma_tai_san, dev.nhom_thiet_bi, dev.parent_id
                      FROM it_system_health_check_details d
                      JOIN devices dev ON d.device_id = dev.id
                      WHERE d.check_id = ?
                      ORDER BY dev.nhom_thiet_bi, dev.parent_id ASC, dev.ten_thiet_bi");
$stmt->execute([$id]);
$details = $stmt->fetchAll();

$tree_by_group = [];
$roots = [];
$children = [];
foreach ($details as $row) {
    if (!$row['parent_id']) {
        $roots[$row['nhom_thiet_bi'] ?: 'Khác'][] = $row;
    } else {
        $children[$row['parent_id']][] = $row;
    }
}

foreach ($roots as $group => $root_list) {
    foreach ($root_list as $root) {
        $tree_by_group[$group][] = ['item' => $root, 'level' => 0];
        if (isset($children[$root['device_id']])) {
            foreach ($children[$root['device_id']] as $child) {
                $tree_by_group[$group][] = ['item' => $child, 'level' => 1];
            }
        }
    }
}

$health_map = ['good' => 'Tốt', 'warning' => 'Cảnh báo', 'broken' => 'Hỏng'];
$overall_health_map = ['good' => 'TỐT (ỔN ĐỊNH)', 'warning' => 'CẢNH BÁO', 'critical' => 'KHẨN CẤP'];
$p_d = date('d', strtotime($check['check_date']));
$p_m = date('m', strtotime($check['check_date']));
$p_y = date('Y', strtotime($check['check_date']));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>In Báo cáo Kiểm tra IT - <?= htmlspecialchars($check['ten_du_an']) ?></title>
    <link rel="icon" type="image/png" href="../uploads/system/Logo1024x.png">
    <style>
        body { font-family: "Times New Roman", Times, serif; font-size: 11pt; line-height: 1.3; color: #000; background: #fff; margin: 0; padding: 0; }
        .print-container { width: 210mm; margin: 0 auto; padding: 10mm 15mm; box-sizing: border-box; }
        .p-header-table { width: 100%; border-bottom: 2px solid #000; margin-bottom: 5px; border-collapse: collapse; }
        .logo { width: 180px; display: block; margin-bottom: 5px; }
        .p-ticket-no { font-size: 12pt; font-style: italic; }
        .p-date { text-align: right; vertical-align: bottom; padding-bottom: 5px; font-size: 12pt; font-style: italic; }
        .p-title { text-align: center; font-size: 18pt; font-weight: bold; margin: 15px 0 10px 0; text-transform: uppercase; line-height: 1.2; }
        
        .data-table { width: 100%; border-collapse: collapse; border: 1.5px solid #000; table-layout: fixed; margin-bottom: 15px; }
        .data-table td { border: 1px solid #000; padding: 6px 10px; vertical-align: top; }
        .label-cell { font-weight: bold; background-color: #f5f5f5 !important; width: 130px; -webkit-print-color-adjust: exact; }
        
        .content-table { width: 100%; border-collapse: collapse; border: 1.5px solid #000; margin-top: 10px; }
        .content-table th, .content-table td { border: 1px solid #000; padding: 6px 8px; font-size: 10pt; }
        .content-table th { background-color: #e0e0e0 !important; -webkit-print-color-adjust: exact; text-transform: uppercase; font-weight: bold; }
        
        .group-header-row { background-color: #f0f0f0 !important; font-weight: bold; text-transform: uppercase; font-size: 10pt; -webkit-print-color-adjust: exact; }
        .row-root { font-weight: bold; }
        .indent { padding-left: 20px; }
        
        .sig-table { width: 100%; text-align: center; margin-top: 30px; border-collapse: collapse; }
        .sig-table td { width: 50%; vertical-align: top; padding: 10px; }
        .sig-space { height: 80px; display: flex; align-items: center; justify-content: center; margin: 5px 0; }
        .sig-img { max-height: 70px; max-width: 140px; }
        .signer-name { font-weight: bold; text-transform: uppercase; margin-top: 5px; }

        @media print {
            @page { size: A4; margin: 15mm; }
            .no-print { display: none; }
            .print-container { width: 100%; margin: 0; padding: 0; }
            .content-table tr { page-break-inside: avoid; }
            .sig-table { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="background: #f8f9fa; padding: 10px; text-align: center; border-bottom: 1px solid #ddd;">
        <button onclick="window.print()" style="padding: 10px 25px; background: #108042; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">XÁC NHẬN IN BÁO CÁO (A4)</button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: #fff; border: none; border-radius: 8px; cursor: pointer; margin-left: 10px;">ĐÓNG</button>
    </div>

    <div class="print-container">
        <table class="p-header-table">
            <tr>
                <td style="width: 190px;">
                    <img src="../uploads/system/logo.png" class="logo" alt="Logo">
                    <div class="p-ticket-no">Số: <?= str_pad($check['id'], 3, '0', STR_PAD_LEFT) ?>/BC-P.IT/<?= date('y', strtotime($check['check_date'])) ?></div>
                </td>
                <td class="p-date">TP. Hồ Chí Minh, ngày <?= $p_d ?> tháng <?= $p_m ?> năm <?= $p_y ?></td>
            </tr>
        </table>

        <div class="p-title">BÁO CÁO KIỂM TRA TÌNH TRẠNG<br>HỆ THỐNG VÀ THIẾT BỊ</div>

        <table class="data-table">
            <tr>
                <td class="label-cell">Dự Án:</td>
                <td class="value-cell" colspan="3"><strong><?= htmlspecialchars($check['ten_du_an']) ?></strong></td>
            </tr>
            <tr>
                <td class="label-cell">Địa chỉ:</td>
                <td class="value-cell" colspan="3"><?= htmlspecialchars($display_address ?: '-') ?></td>
            </tr>
            <tr>
                <td class="label-cell">Người kiểm tra:</td>
                <td class="value-cell" style="text-transform: uppercase;"><strong><?= htmlspecialchars($check['checker_name']) ?></strong></td>
                <td class="label-cell">Đánh giá chung:</td>
                <td class="value-cell"><strong><?= ($overall_health_map[$check['overall_health']] ?? $check['overall_health']) ?></strong></td>
            </tr>
            <?php if($check['summary_notes']): ?>
            <tr>
                <td class="label-cell">Ghi chú hệ thống:</td>
                <td class="value-cell" colspan="3"><?= nl2br(htmlspecialchars($check['summary_notes'])) ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <table class="content-table">
            <thead>
                <tr>
                    <th width="5%">STT</th>
                    <th width="35%">Tên thiết bị / Linh kiện</th>
                    <th width="10%">S.Lượng</th>
                    <th width="15%">Sử dụng</th>
                    <th width="15%">Sức khỏe</th>
                    <th width="20%">Tình trạng chi tiết</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $stt = 1;
                foreach ($tree_by_group as $group_name => $nodes): 
                ?>
                    <tr class="group-header-row">
                        <td colspan="6" align="left">NHÓM: <?= htmlspecialchars($group_name) ?></td>
                    </tr>
                    <?php foreach ($nodes as $node): 
                        $d = $node['item']; $lvl = $node['level'];
                    ?>
                        <tr class="<?= $lvl == 0 ? 'row-root' : 'row-child' ?>">
                            <td align="center"><?= $lvl == 0 ? $stt++ : '' ?></td>
                            <td>
                                <div class="<?= $lvl > 0 ? 'indent' : '' ?>">
                                    <?= $lvl > 0 ? '↳ ' : '' ?>
                                    <?= htmlspecialchars($d['ten_thiet_bi']) ?>
                                </div>
                            </td>
                            <td align="center"><strong><?= $d['quantity'] ?: 1 ?></strong></td>
                            <td align="center"><?= htmlspecialchars($d['status']) ?></td>
                            <td align="center"><?= ($health_map[$d['health_status']] ?? $d['health_status']) ?></td>
                            <td>
                                <?php if($d['cause']): ?><strong>Lỗi:</strong> <?= htmlspecialchars($d['cause']) ?><br><?php endif; ?>
                                <?= htmlspecialchars($d['notes']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table class="sig-table">
            <tr>
                <td>
                    <strong>ĐẠI DIỆN DỰ ÁN</strong><br>
                    <span style="font-size: 9pt; font-style: italic;">(Ký, ghi rõ họ tên)</span>
                    <div class="sig-space">
                        <?php if($check['client_signature']): ?>
                            <img src="<?= $check['client_signature'] ?>" class="sig-img">
                        <?php endif; ?>
                    </div>
                    <div class="signer-name"><?= htmlspecialchars($check['client_name'] ?: '') ?></div>
                </td>
                <td>
                    <strong>NGUỜI KIỂM TRA</strong><br>
                    <span style="font-size: 9pt; font-style: italic;">(Ký, ghi rõ họ tên)</span>
                    <div class="sig-space">
                        <?php if($check['it_signature']): ?>
                            <img src="<?= $check['it_signature'] ?>" class="sig-img">
                        <?php endif; ?>
                    </div>
                    <div class="signer-name"><?= htmlspecialchars($check['checker_name']) ?></div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>