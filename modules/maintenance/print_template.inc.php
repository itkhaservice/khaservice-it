<?php
/**
 * Tệp này chứa logic và giao diện in ấn cố định cho Phiếu Công Tác.
 * Thiết kế: Nâng cỡ chữ lên 13pt chuẩn văn bản, tối ưu hiển thị rõ nét.
 */

// --- 1. LOGIC XỬ LÝ DỮ LIỆU ---
$is_custom_device = empty($log['device_id']);
$print_device_name = $is_custom_device ? ($log['custom_device_name'] ?: "") : $log['ten_thiet_bi'];

// Ngày tháng
$print_doc_date = !empty($log['ngay_lap_phieu']) ? $log['ngay_lap_phieu'] : $log['created_at'];
$p_d = date('d', strtotime($print_doc_date));
$p_m = date('m', strtotime($print_doc_date));
$p_y = date('Y', strtotime($print_doc_date));

// Thời gian sử dụng
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

// Thông tin hỗ trợ lần trước
$last_support_date = '';
$last_support_work = '';
$last_support_performer = '';

if (!empty($log['project_id'])) {
    $stmt_last = $pdo->prepare("
        SELECT ml.ngay_su_co, ml.completion_time, ml.work_type, u.fullname 
        FROM maintenance_logs ml 
        LEFT JOIN users u ON ml.user_id = u.id 
        WHERE ml.project_id = ? AND ml.id < ? AND ml.deleted_at IS NULL 
        ORDER BY ml.id DESC LIMIT 1
    ");
    $stmt_last->execute([$log['project_id'], $log['id']]);
    $last_log = $stmt_last->fetch();
    
    if ($last_log) {
        $last_support_date = $last_log['completion_time'] ? date('d/m/Y', strtotime($last_log['completion_time'])) : date('d/m/Y', strtotime($last_log['ngay_su_co']));
        $last_support_work = $last_log['work_type'];
        $last_support_performer = $last_log['fullname'];
    }
}

// --- ĐỊNH NGHĨA DẤU CHẤM ---
$dots = '................................................................................................................................................................................................................................................................';

function renderValue($val, $dots, $isBold = false) {
    if (empty($val)) return '<span class="dot-placeholder">' . $dots . '</span>';
    return $isBold ? '<b>' . htmlspecialchars($val) . '</b>' : htmlspecialchars($val);
}
?>

<div class="print-only">
    <div class="a4-page-wrapper">
        <!-- HEADER -->
        <div class="print-header-section">
            <table class="p-header-table" style="width: 100%; border-collapse: collapse;">       
                <tr>
                    <td style="padding-left: 0; width: 220px; vertical-align: top; text-align: center;">
                        <img src="../uploads/system/logo.png" alt="Logo" style="width: 200px; display: block; margin-bottom: 5px;">
                        <div class="p-ticket-no-clean" style="width: 200px;">Số: <i><?php echo str_pad($log['id'], 2, '0', STR_PAD_LEFT); ?>/CT-P.IT<?php echo date('m', strtotime($log['created_at'])); ?>/<?php echo date('y', strtotime($log['created_at'])); ?></i></div>
                    </td>
                    <td style="text-align: right; vertical-align: bottom; padding-bottom: 8px;">
                        <div class="p-date"><?php echo htmlspecialchars($display_city); ?>, ngày <?php echo $p_d; ?> tháng <?php echo $p_m; ?> năm <?php echo $p_y; ?></div>
                    </td>
                </tr>
            </table>

            <div class="p-title">PHIẾU CÔNG TÁC</div>
        </div>

        <!-- TABLE 1: THÔNG TIN CHUNG -->
        <table class="data-table">
            <colgroup>
                <col style="width: 150px;">
                <col style="width: auto;">
                <col style="width: 140px;">
                <col style="width: 25%;">
            </colgroup>
            <tr>
                <td class="label-cell no-border-bottom">Dự Án:</td>
                <td class="value-cell no-border-bottom"><?php echo renderValue($display_project_name, $dots, true); ?></td>
                <td class="label-cell no-border-bottom">Bộ phận:</td>
                <td class="value-cell no-border-bottom">IT / Kỹ thuật</td>
            </tr>
            <tr>
                <td class="label-cell no-border-top no-border-bottom">Địa chỉ:</td>
                <td class="value-cell no-border-top no-border-bottom allow-wrap" style="height: 3.2em; vertical-align: top;">
                    <?php echo !empty($display_address) ? htmlspecialchars($display_address) : '<span class="dot-placeholder">' . $dots . '<br>' . $dots . '</span>'; ?>
                </td>
                <td class="label-cell no-border-top no-border-bottom">Người đại diện:</td>
                <td class="value-cell text-upper no-border-top no-border-bottom"><b><?php echo htmlspecialchars($log['nguoi_thuc_hien'] ?: $current_user_name); ?></b></td>
            </tr>
            <tr>
                <td class="label-cell no-border-top no-border-bottom">Đại diện:</td>
                <td class="value-cell no-border-top no-border-bottom"><?php echo renderValue($log['client_name'], $dots); ?></td>
                <td class="label-cell no-border-top" rowspan="2" style="vertical-align: middle;">Công việc:</td>
                <td class="value-cell no-border-top" rowspan="2" style="vertical-align: middle;"><?php echo renderValue($log['work_type'], $dots); ?></td>
            </tr>
            <tr>
                <td class="label-cell no-border-top">Chức vụ:</td>
                <td class="value-cell no-border-top"><?php echo renderValue($log['client_phone'], $dots); ?></td>
            </tr>
        </table>

        <div class="spacer-10"></div>

        <!-- TABLE 2: THÔNG TIN THIẾT BỊ -->
        <table class="data-table">
            <colgroup>
                <col style="width: 150px;">
                <col style="width: auto;">
                <col style="width: 140px;">
                <col style="width: 25%;">
            </colgroup>
            <tr>
                <td class="label-cell">Thiết bị:</td>
                <td class="value-cell"><?php echo renderValue($print_device_name, $dots, true); ?></td>
                <td class="label-cell">TG sử dụng:</td>
                <td class="value-cell"><?php echo renderValue($print_usage_time, $dots); ?></td>
            </tr>
        </table>

        <div class="spacer-5"></div>

        <!-- TABLE 3: THÔNG TIN THỜI GIAN -->
        <table class="data-table">
            <colgroup>
                <col style="width: 150px;">
                <col style="width: auto;">
                <col style="width: 140px;">
                <col style="width: 25%;">
            </colgroup>
            <tr>
                <td class="label-cell no-border-bottom">TG yêu cầu:</td>
                <td class="value-cell no-border-bottom"><?php echo date('d/m/Y', strtotime($log['ngay_su_co'])); ?></td>
                <td class="label-cell no-border-bottom">Hỗ trợ lần cuối:</td>
                <td class="value-cell no-border-bottom"><?php echo renderValue($last_support_date, $dots); ?></td>
            </tr>
            <tr>
                <td class="label-cell no-border-top no-border-bottom">TG có mặt:</td>
                <td class="value-cell no-border-top no-border-bottom"><?php echo $log['arrival_time'] ? date('H:i d/m/Y', strtotime($log['arrival_time'])) : renderValue('', $dots); ?></td>
                <td class="label-cell no-border-top no-border-bottom">Công việc:</td>
                <td class="value-cell no-border-top no-border-bottom"><?php echo renderValue($last_support_work, $dots); ?></td>
            </tr>
            <tr>
                <td class="label-cell no-border-top">TG hoàn thành:</td>
                <td class="value-cell no-border-top"><?php echo $log['completion_time'] ? date('H:i d/m/Y', strtotime($log['completion_time'])) : renderValue('', $dots); ?></td>
                <td class="label-cell no-border-top">Người thực hiện:</td>
                <td class="value-cell no-border-top text-upper"><?php echo renderValue($last_support_performer, $dots); ?></td>
            </tr>
        </table>

        <div class="spacer-15"></div>

        <!-- CONTENT SECTION -->
        <div class="content-box">
            <div class="box-header">I. YÊU CẦU CỦA DỰ ÁN</div>
            <div class="box-body" style="min-height: 80px;">
                <?php if (!empty($log['noi_dung'])): ?>
                    <?php echo nl2br(htmlspecialchars($log['noi_dung'])); ?>
                <?php else: ?>
                    <div style="line-height: 35px;" class="dot-placeholder"><?php echo $dots; ?><br><?php echo $dots; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="spacer-15"></div>

        <div class="content-box flex-grow">
            <div class="box-header">II. CÔNG VIỆC THỰC HIỆN / KẾT QUẢ</div>
            <div class="box-body">
                <?php
                    $hu_hong = trim($log['hu_hong'] ?? '');
                    $xu_ly = trim($log['xu_ly'] ?? '');
                ?>
                <div class="sub-section">
                    <div class="sub-label">- Tình trạng / Kiểm tra:</div>
                    <div class="sub-text">
                        <?php if (!empty($hu_hong)): ?>
                            <?php echo nl2br(htmlspecialchars($hu_hong)); ?>
                        <?php else: ?>
                            <div style="line-height: 30px;" class="dot-placeholder"><?php echo $dots; ?><br><?php echo $dots; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="sub-divider"></div>

                <div class="sub-section">
                    <div class="sub-label">- Xử lý / Kết quả:</div>
                    <div class="sub-text">
                        <?php if (!empty($xu_ly)): ?>
                            <?php echo nl2br(htmlspecialchars($xu_ly)); ?>
                        <?php else: ?>
                            <div style="line-height: 30px;" class="dot-placeholder"><?php echo $dots; ?><br><?php echo $dots; ?><br><?php echo $dots; ?><br><?php echo $dots; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="spacer-20"></div>

        <!-- FOOTER SIGNATURES -->
        <table class="sig-table">
            <tr>
                <td>
                    <strong>ĐẠI DIỆN DỰ ÁN</strong><br>
                    <span>(Ký, ghi rõ họ tên)</span>
                </td>
                <td>
                    <strong>NGƯỜI THỰC HIỆN</strong><br>
                    <span>(Ký, ghi rõ họ tên)</span>
                </td>
            </tr>
            <tr class="sig-space-row">
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="text-upper"><b><?php echo htmlspecialchars($log['client_name'] ?? ''); ?></b></td>
                <td class="text-upper"><b><?php echo htmlspecialchars($log['nguoi_thuc_hien'] ?: $current_user_name); ?></b></td>
            </tr>
        </table>
    </div>
</div>

<style>
.print-only { display: none; }

@media print {
    @page { size: A4; margin: 10mm 15mm; }
    
    html, body { margin: 0; padding: 0; background: #fff !important; }
    body { font-family: "Times New Roman", Times, serif; color: #000; font-size: 13pt; line-height: 1.4; } /* Nâng lên 13pt */
    
    .web-view, .main-header, .main-footer, footer, .header-actions, .message-container { display: none !important; } 
    .print-only { display: block !important; width: 100%; height: 100%; }
    .a4-page-wrapper { min-height: 95vh; display: flex; flex-direction: column; }

    .text-upper { text-transform: uppercase; }
    .spacer-5 { height: 5px; }
    .spacer-10 { height: 12px; }
    .spacer-15 { height: 18px; }
    .spacer-20 { height: 25px; }

    /* Header */
    .p-header-table { width: 100%; border-bottom: 2px solid #000; margin-bottom: 5px; }
    .p-ticket-no-clean { font-size: 14pt; font-weight: normal; margin-top: 5px; }
    .p-date { font-size: 13pt; font-style: italic; }
    .p-title { text-align: center; font-size: 26pt; font-weight: bold; margin: 15px 0 12px 0; text-transform: uppercase; }

    /* Data Table */
    .data-table { 
        width: 100%; 
        border-collapse: collapse; 
        border: 1.5px solid #000; 
        table-layout: fixed; 
    }
    .data-table td { 
        border: 1px solid #000; 
        padding: 7px 15px 7px 10px; 
        vertical-align: top;
        height: 1.5em; 
    }
    
    .dot-placeholder {
        color: #555;
        font-weight: normal !important;
        letter-spacing: 1px;
        overflow: hidden;
        display: block;
        white-space: nowrap;
    }
    
    .data-table td.allow-wrap {
        white-space: normal !important;
    }
    .data-table td.allow-wrap .dot-placeholder {
        white-space: normal !important;
    }
    
    .no-border-bottom { border-bottom: none !important; }
    .no-border-top { border-top: none !important; }

    .label-cell { 
        font-weight: bold; 
        background-color: #f5f5f5 !important; 
        -webkit-print-color-adjust: exact; 
        white-space: nowrap;
        width: 150px;
        font-size: 13pt;
    }

    /* Content Box */
    .content-box { border: 1.5px solid #000; display: flex; flex-direction: column; }
    .flex-grow { flex-grow: 1; min-height: 200px; }
    
    .box-header { 
        background-color: #e0e0e0 !important; 
        -webkit-print-color-adjust: exact; 
        border-bottom: 1px solid #000; 
        padding: 8px 12px; 
        font-weight: bold; 
        font-size: 13pt;
        text-transform: uppercase;
    }
    .box-body { padding: 12px; font-size: 13pt; }
    
    .sub-section { margin-bottom: 8px; }
    .sub-label { font-weight: bold; margin-bottom: 4px; }
    .sub-divider { border-top: 1px dashed #ccc; margin: 10px 0; }

    /* Signatures */
    .sig-table { width: 100%; text-align: center; margin-top: auto; font-size: 13pt; }
    .sig-table td { width: 50%; vertical-align: top; }
    .sig-space-row td { height: 90px; }
}
</style>