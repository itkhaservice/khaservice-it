<?php
/**
 * Tệp này chứa logic và giao diện in ấn cố định cho Phiếu Công Tác.
 * Thiết kế chuẩn A4: Header cố định -> Content co giãn -> Signatures đáy trang.
 */

// 1. Logic xử lý dữ liệu
$is_custom_device = empty($log['device_id']);
$print_device_name = $is_custom_device ? ($log['custom_device_name'] ?: "") : $log['ten_thiet_bi'];

// Xử lý ngày in trên đầu phiếu
$print_doc_date = !empty($log['ngay_lap_phieu']) ? $log['ngay_lap_phieu'] : $log['created_at'];
$p_d = date('d', strtotime($print_doc_date));
$p_m = date('m', strtotime($print_doc_date));
$p_y = date('Y', strtotime($print_doc_date));

$print_usage_time = "";
if (!empty($log['usage_time_manual'])) {
    $print_usage_time = $log['usage_time_manual'];
} elseif (!$is_custom_device && !empty($log['ngay_mua'])) {
    try {
        $purchase_date = new DateTime($log['ngay_mua']);
        $now = new DateTime();
        $interval = $purchase_date->diff($now);
        $print_usage_time = ($interval->y > 0 ? $interval->y . " năm " : "") . ($interval->m > 0 ? $interval->m . " tháng" : "");
        if ($print_usage_time == "") $print_usage_time = "Mới mua";
    } catch (Exception $e) { $print_usage_time = ""; }
}

$last_support_date = '';
$last_support_work = '';
$last_support_performer = '';
$stmt_last = null;
$sql_last = "SELECT ml.ngay_su_co, ml.work_type, u.fullname FROM maintenance_logs ml LEFT JOIN users u ON ml.user_id = u.id WHERE ";
if (!$is_custom_device) {
    $sql_last .= "ml.device_id = ? AND ml.id < ? ORDER BY ml.ngay_su_co DESC LIMIT 1";
    $stmt_last = $pdo->prepare($sql_last);
    $stmt_last->execute([$log['device_id'], $log['id']]);
} elseif (!empty($log['project_id'])) {
    $sql_last .= "ml.project_id = ? AND ml.id < ? ORDER BY ml.ngay_su_co DESC LIMIT 1";
    $stmt_last = $pdo->prepare($sql_last);
    $stmt_last->execute([$log['project_id'], $log['id']]);
}
if ($stmt_last) {
    $last_log = $stmt_last->fetch();
    if ($last_log) {
        $last_support_date = date('d/m/Y', strtotime($last_log['ngay_su_co']));
        $last_support_work = $last_log['work_type'];
        $last_support_performer = $last_log['fullname'];
    }
}
?>

<div class="print-only">
    <div class="a4-page-wrapper">
        <!-- PHẦN CỐ ĐỊNH: HEADER & INFO -->
        <div class="print-header-section">
            <table class="p-header-table" style="width: 100%; border-collapse: collapse;">       
                <tr>
                    <td style="padding-left: 0; width: 190px; vertical-align: top; text-align: center;">
                        <img src="../uploads/system/logo.png" alt="Logo" style="width: 190px; display: block; margin-bottom: 5px;">
                        <div class="p-ticket-no-clean" style="width: 190px;">Số: <i><?php echo str_pad($log['id'], 2, '0', STR_PAD_LEFT); ?>/CT-P.IT<?php echo date('m', strtotime($log['created_at'])); ?>/<?php echo date('y', strtotime($log['created_at'])); ?></i></div>
                    </td>
                    <td style="text-align: right; vertical-align: bottom; padding-bottom: 5px;">
                        <div class="p-date"><?php echo htmlspecialchars($display_city); ?>, ngày <?php echo $p_d; ?> tháng <?php echo $p_m; ?> năm <?php echo $p_y; ?></div>
                    </td>
                </tr>
            </table>

            <div class="p-title">PHIẾU CÔNG TÁC</div>

            <table class="p-table">
                <colgroup><col style="width: 18%;"><col style="width: 32%;"><col style="width: 18%;"><col style="width: 32%;"></colgroup>
                <tr>
                    <td class="pt-label">Dự Án:</td>
                    <td class="p-line-single"><?php echo htmlspecialchars($display_project_name); ?></td> 
                    <td class="pt-label">Bộ phận:</td>
                    <td class="p-line-single">IT / Kỹ thuật</td>
                </tr>
                <tr>
                    <td class="pt-label-top">Địa chỉ:</td>
                    <td class="p-line-double"><?php echo htmlspecialchars($display_address); ?></td>      
                    <td class="pt-label-top">Người đại diện:</td>
                    <td class="p-line-double" style="text-transform: uppercase;"><?php echo htmlspecialchars($log['nguoi_thuc_hien'] ?: $current_user_name); ?></td>
                </tr>
                <tr>
                    <td class="pt-label">Đại diện:</td>
                    <td class="p-line-single"><?php echo htmlspecialchars($log['client_name'] ?? ''); ?></td>
                    <td class="pt-label-top" rowspan="2">Công việc:</td>
                    <td class="p-line-double" rowspan="2" style="height: 60px;"><?php echo htmlspecialchars($log['work_type'] ?? 'Bảo trì / Sửa chữa'); ?></td>
                </tr>
                <tr>
                    <td class="pt-label">Điện thoại:</td>
                    <td class="p-line-single"><?php echo htmlspecialchars($log['client_phone'] ?? ''); ?></td>
                </tr>
                <tr><td colspan="4" style="padding: 10px 0;"><div style="border-top: 2px solid #000;"></div></td></tr>
                <tr>
                    <td class="pt-label">Thiết bị:</td>
                    <td class="p-line-single"><strong><?php echo htmlspecialchars($print_device_name); ?></strong></td>
                    <td class="pt-label">TG sử dụng:</td>
                    <td class="p-line-single"><?php echo $print_usage_time; ?></td>
                </tr>
                <tr>
                    <td class="pt-label">TG yêu cầu:</td>
                    <td class="p-line-single"><?php echo date('d/m/Y', strtotime($log['ngay_su_co'])); ?></td>
                    <td class="pt-label">Hỗ trợ lần cuối:</td>
                    <td class="p-line-single"><?php echo $last_support_date ?: ''; ?></td>
                </tr>
                <tr>
                    <td class="pt-label">TG có mặt:</td>
                    <td class="p-line-single"><?php echo $log['arrival_time'] ? date('H:i d/m/Y', strtotime($log['arrival_time'])) : ''; ?></td>
                    <td class="pt-label">Công việc:</td>
                    <td class="p-line-single"><?php echo !empty($last_support_work) ? htmlspecialchars($last_support_work) : ''; ?></td>
                </tr>
                <tr>
                    <td class="pt-label">TG hoàn thành:</td>
                    <td class="p-line-single"><?php echo $log['completion_time'] ? date('H:i d/m/Y', strtotime($log['completion_time'])) : ''; ?></td>
                    <td class="pt-label">Người thực hiện:</td>
                    <td class="p-line-single" style="text-transform: uppercase;"><?php echo !empty($last_support_performer) ? htmlspecialchars($last_support_performer) : ''; ?></td>
                </tr>
            </table>
        </div>

        <!-- PHẦN CO GIÃN: NỘI DUNG I & II -->
        <div class="print-content-section">
            <div class="p-box box-req">
                <div class="pb-title">I. YÊU CẦU CỦA DỰ ÁN</div>
                <div class="pb-content lined-paper"><?php echo nl2br(htmlspecialchars($log['noi_dung'] ?? '')); ?></div>
            </div>
            <div class="p-box box-res">
                <div class="pb-title">II. CÔNG VIỆC THỰC HIỆN / KẾT QUẢ</div>
                <div class="pb-content lined-paper"><?php
                    $hu_hong = trim($log['hu_hong'] ?? '');
                    $xu_ly = trim($log['xu_ly'] ?? '');
                    if(!empty($hu_hong)) echo "<strong>- Tình trạng:</strong> " . nl2br(htmlspecialchars($hu_hong)) . "<br><br>";
                    if(!empty($xu_ly)) echo "<strong>- Xử lý:</strong> " . nl2br(htmlspecialchars($xu_ly));
                ?></div>
            </div>
        </div>

        <!-- PHẦN CỐ ĐỊNH: CHỮ KÝ (LUÔN Ở ĐÁY) -->
        <div class="print-footer-section">
            <div class="p-sig"><strong>ĐẠI DIỆN BAN QUẢN LÝ</strong><br><span>(Ký, ghi rõ họ tên)</span><div class="sig-space"></div></div>
            <div class="p-sig"><strong>NGƯỜI LẬP PHIẾU</strong><br><span>(Ký, ghi rõ họ tên)</span><div class="sig-space"></div></div>
        </div>
    </div>
</div>

<style>
.print-only { display: none; }

@media print {
    @page { size: A4; margin: 10mm; }
    html, body { height: 100%; margin: 0; padding: 0; }
    body { background: #fff !important; font-family: "Times New Roman", Times, serif; color: #000; }
    
    .web-view, .main-header, .main-footer, .page-header, .header-actions, .message-container, footer { display: none !important; } 
    
    .print-only { display: block !important; width: 100%; min-height: 277mm; box-sizing: border-box; position: relative; }
    
    .a4-page-wrapper { 
        min-height: 277mm; 
        display: flex; 
        flex-direction: column; 
        padding: 5mm; 
        box-sizing: border-box;
    }

    .print-header-section { flex-shrink: 0; }
    .print-content-section { flex-grow: 1; display: flex; flex-direction: column; gap: 15px; margin: 15px 0; }
    .print-footer-section { 
        flex-shrink: 0; 
        display: flex; 
        justify-content: space-between; 
        margin-top: auto; 
        padding-top: 20px;
        page-break-inside: avoid; 
    }

    .p-header-table { width: 100%; border-bottom: 2px solid #000; margin-bottom: 5px; }
    .p-ticket-no-clean { font-size: 13pt; font-weight: normal; }
    .p-date { font-size: 13pt; font-style: italic; }
    .p-title { text-align: center; font-size: 24pt; font-weight: bold; margin: 10px 0; text-transform: uppercase; }
    
    .p-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .pt-label { font-weight: bold; height: 30px; line-height: 30px; font-size: 13pt; }
    .pt-label-top { font-weight: bold; vertical-align: top; line-height: 30px; font-size: 13pt; }
    .p-line-single { background-image: repeating-linear-gradient(transparent, transparent 29px, #000 30px); line-height: 30px; font-size: 13pt; overflow: hidden; }
    .p-line-double { background-image: repeating-linear-gradient(transparent, transparent 29px, #000 30px); line-height: 30px; font-size: 13pt; height: 60px; vertical-align: top; }

    .p-box { border: 1.5pt solid #000; display: flex; flex-direction: column; page-break-inside: auto; }
    .box-req { flex-grow: 0; min-height: 120px; margin-bottom: 10px; }
    .box-res { flex-grow: 1; min-height: 200px; }
    .pb-title { font-weight: bold; background: #e0e0e0 !important; padding: 5px 10px; border-bottom: 1.5pt solid #000; -webkit-print-color-adjust: exact; font-size: 13pt; }
    .pb-content { 
        padding: 5px 10px; 
        flex-grow: 1; 
        background-image: repeating-linear-gradient(transparent, transparent 29px, #ccc 30px); 
        line-height: 30px; 
        font-size: 13pt; 
        word-wrap: break-word;
        white-space: normal;
    }
    
    .p-sig { text-align: center; width: 45%; font-size: 13pt; }
    .sig-space { height: 3cm; }
}

/* DEBUG MODE */
body.debug-print-mode { 
    background: #555 !important; 
    padding: 40px 0 !important; 
    overflow: auto; 
    -ms-overflow-style: none; 
    scrollbar-width: none; 
}
body.debug-print-mode::-webkit-scrollbar { 
    display: none; 
}
</style>