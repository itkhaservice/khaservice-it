<?php
$log = null;
if (isset($_GET['id'])) {
    $log_id = $_GET['id'];
    // Cập nhật truy vấn: JOIN projects từ maintenance_logs.project_id
    $stmt = $pdo->prepare("
        SELECT ml.*, d.ma_tai_san, d.ten_thiet_bi, d.loai_thiet_bi, d.model, d.ngay_mua,
               COALESCE(p_log.ten_du_an, p_dev.ten_du_an) as ten_du_an, 
               COALESCE(p_log.dia_chi, p_dev.dia_chi) as dia_chi_du_an, 
               d.trang_thai as trang_thai_tb
        FROM maintenance_logs ml
        LEFT JOIN devices d ON ml.device_id = d.id
        LEFT JOIN projects p_log ON ml.project_id = p_log.id
        LEFT JOIN projects p_dev ON d.project_id = p_dev.id
        WHERE ml.id = ?
    ");
    $stmt->execute([$log_id]);
    $log = $stmt->fetch();
}

if (!$log) {
    set_message('error', 'Nhật ký bảo trì không tìm thấy!');
    header("Location: index.php?page=maintenance/history");
    exit;
}

// Xử lý logic hiển thị
$is_custom_device = empty($log['device_id']);

// Tên hiển thị trên WEB (để quản lý)
$web_display_name = $is_custom_device ? ($log['custom_device_name'] ?? "Hỗ trợ chung") : $log['ten_thiet_bi'];
$web_display_code = $is_custom_device ? "N/A" : $log['ma_tai_san'];

// Dữ liệu hiển thị trên PHIẾU IN (theo yêu cầu bỏ trống)
$print_device_name = $is_custom_device ? "" : $log['ten_thiet_bi'];
$print_usage_time = "";

if (!$is_custom_device && !empty($log['ngay_mua'])) {
    $purchase_date = new DateTime($log['ngay_mua']);
    $now = new DateTime();
    $interval = $purchase_date->diff($now);
    $print_usage_time = ($interval->y > 0 ? $interval->y . " năm " : "") . ($interval->m > 0 ? $interval->m . " tháng" : "");
    if ($print_usage_time == "") $print_usage_time = "Mới mua";
}

// Lấy lần hỗ trợ cuối
$last_support_str = 'Lần đầu';
if (!$is_custom_device) {
    $stmt_last = $pdo->prepare("SELECT ngay_su_co FROM maintenance_logs WHERE device_id = ? AND id < ? ORDER BY ngay_su_co DESC LIMIT 1");
    $stmt_last->execute([$log['device_id'], $log['id']]);
} elseif (!empty($log['project_id'])) {
    $stmt_last = $pdo->prepare("SELECT ngay_su_co FROM maintenance_logs WHERE project_id = ? AND id < ? ORDER BY ngay_su_co DESC LIMIT 1");
    $stmt_last->execute([$log['project_id'], $log['id']]);
} else {
    $stmt_last = null;
}

if ($stmt_last) {
    $last_support_date = $stmt_last->fetchColumn();
    $last_support_str = $last_support_date ? date('d/m/Y', strtotime($last_support_date)) : 'Lần đầu';
}

$current_user_name = $_SESSION['username'] ?? 'IT Support';
?>

<!-- GIAO DIỆN WEB -->
<div class="web-view">
    <div class="page-header">
        <h2><i class="fas fa-file-invoice"></i> Chi tiết Phiếu Bảo trì #<?php echo $log['id']; ?></h2>
        <div class="header-actions">
            <a href="index.php?page=maintenance/history" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
            <button class="btn btn-warning" onclick="togglePrintDebug()"><i class="fas fa-eye"></i> Soi mẫu in</button>
            <button class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> In phiếu A4</button>
            <a href="index.php?page=maintenance/edit&id=<?php echo $log['id']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Chỉnh sửa</a>
        </div>
    </div>

    <div class="view-grid-layout maintenance-view">
        <div class="main-content">
            <div class="card ticket-card">
                <div class="ticket-header">
                    <div class="ticket-status"><span class="label">Ngày sự cố</span><span class="value date"><i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($log['ngay_su_co'])); ?></span></div>
                    <div class="ticket-cost"><span class="label">Chi phí</span><span class="value cost"><?php echo number_format($log['chi_phi']); ?> ₫</span></div>
                </div>
                <div class="ticket-body">
                    <div class="content-block problem"><h4 class="block-title"><i class="fas fa-exclamation-circle"></i> Hiện tượng / Yêu cầu</h4><div class="block-content"><?php echo nl2br(htmlspecialchars($log['noi_dung'])); ?></div></div>
                    <div class="content-block diagnosis"><h4 class="block-title"><i class="fas fa-microscope"></i> Nguyên nhân / Hư hỏng</h4><div class="block-content"><?php echo !empty($log['hu_hong']) ? nl2br(htmlspecialchars($log['hu_hong'])) : '<em>Chưa ghi nhận</em>'; ?></div></div>
                    <div class="content-block solution"><h4 class="block-title"><i class="fas fa-check-circle"></i> Biện pháp Xử lý</h4><div class="block-content"><?php echo !empty($log['xu_ly']) ? nl2br(htmlspecialchars($log['xu_ly'])) : '<em>Chưa ghi nhận</em>'; ?></div></div>
                </div>
            </div>
        </div>
        <div class="side-content">
            <div class="card device-profile-card">
                <div class="profile-header">
                    <div class="device-icon-large"><i class="fas fa-<?php echo $is_custom_device ? 'cube' : 'server'; ?>"></i></div>
                    <div class="profile-title">
                        <h3><?php echo htmlspecialchars($web_display_name); ?></h3>
                        <span class="code"><?php echo htmlspecialchars($web_display_code); ?></span>
                    </div>
                </div>
                <div class="profile-details">
                    <div class="detail-row"><span class="d-label">Dự án</span><span class="d-value"><?php echo htmlspecialchars($log['ten_du_an']); ?></span></div>
                    <div class="detail-row"><span class="d-label">Đại diện dự án</span><span class="d-value"><?php echo htmlspecialchars($log['client_name'] ?? '---'); ?></span></div>
                    <div class="detail-row"><span class="d-label">Liên hệ</span><span class="d-value"><?php echo htmlspecialchars($log['client_phone'] ?? '---'); ?></span></div>
                    <div class="detail-row"><span class="d-label">TG Có mặt</span><span class="d-value"><?php echo $log['arrival_time'] ? date('H:i d/m/Y', strtotime($log['arrival_time'])) : '-'; ?></span></div>
                    <div class="detail-row"><span class="d-label">Hoàn thành</span><span class="d-value"><?php echo $log['completion_time'] ? date('H:i d/m/Y', strtotime($log['completion_time'])) : '-'; ?></span></div>
                </div>
                <?php if (!$is_custom_device): ?>
                <div class="profile-actions"><a href="index.php?page=devices/view&id=<?php echo $log['device_id']; ?>" class="btn btn-primary" style="display: flex; width: auto; justify-content: center;"><i class="fas fa-external-link-alt"></i> Xem hồ sơ thiết bị</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================== -->
<!-- MẪU IN PHIẾU CÔNG TÁC (LAYOUT A4 CHUẨN - 1 TRANG) -->
<!-- ========================================================================== -->
<div class="print-only">
    <div class="a4-page-wrapper">
        
        <!-- NỘI DUNG CHÍNH -->
        <div class="print-content-flow">
            <!-- HEADER (Nén khoảng cách) -->
            <table class="p-header-table" style="margin-bottom: 0; border-bottom: 2px solid #000;">
                <!-- Dòng 1: Logo -->
                <tr>
                    <td colspan="2" style="padding-bottom: 0;">
                        <img src="../uploads/system/logo.png" alt="Logo" style="width: 160px; height: auto; display: block;">
                    </td>
                </tr>
                
                <!-- Dòng 2: Số phiếu & Ngày tháng (Ngang hàng) -->
                <tr>
                    <td style="text-align: left; vertical-align: bottom; padding: 2px 0;">
                        <div class="p-ticket-no-clean">Số: <?php echo str_pad($log['id'], 4, '0', STR_PAD_LEFT); ?>/CT-P.IT/<?php echo date('Y'); ?></div>
                    </td>
                    <td style="text-align: right; vertical-align: bottom; padding: 2px 0;">
                        <div class="p-date">TP.HCM, ngày <?php echo date('d'); ?> tháng <?php echo date('m'); ?> năm <?php echo date('Y'); ?></div>
                    </td>
                </tr>
            </table>

            <div class="p-title" style="margin: 5px 0 10px 0;">PHIẾU CÔNG TÁC</div>

            <!-- DETAIL TABLE -->
            <table class="p-table">
                <colgroup>
                    <col style="width: 18%;">
                    <col style="width: 32%;">
                    <col style="width: 18%;">
                    <col style="width: 32%;">
                </colgroup>
                
                <tr>
                    <td class="pt-label">Dự Án:</td>
                    <td class="p-line-single"><?php echo htmlspecialchars($log['ten_du_an']); ?></td>
                    <td class="pt-label">Bộ phận:</td>
                    <td class="p-line-single">IT / Kỹ thuật</td>
                </tr>

                <tr>
                    <td class="pt-label-top">Địa chỉ:</td>
                    <td class="p-line-double"><?php echo htmlspecialchars($log['dia_chi_du_an'] ?? ''); ?></td>
                    <td class="pt-label-top">Người đại diện:</td>
                    <td class="p-line-double" style="text-transform: uppercase;"><?php echo htmlspecialchars($current_user_name); ?></td>
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
                    <td class="p-line-single"><?php echo $last_support_str; ?></td>
                </tr>
                <tr>
                    <td class="pt-label">TG có mặt:</td>
                    <td class="p-line-single"><?php echo $log['arrival_time'] ? date('H:i d/m/Y', strtotime($log['arrival_time'])) : ''; ?></td>
                    <td class="pt-label">Công việc:</td>
                    <td class="p-line-single">Xử lý sự cố</td>
                </tr>
                <tr>
                    <td class="pt-label">TG hoàn thành:</td>
                    <td class="p-line-single"><?php echo $log['completion_time'] ? date('H:i d/m/Y', strtotime($log['completion_time'])) : ''; ?></td>
                    <td class="pt-label">Người thực hiện:</td>
                    <td class="p-line-single" style="text-transform: uppercase;"><?php echo htmlspecialchars($current_user_name); ?></td>
                </tr>
            </table>

            <!-- CONTENT BOXES -->
            <div class="p-content-boxes">
                <div class="p-box box-short">
                    <div class="pb-title">I. YÊU CẦU CỦA DỰ ÁN</div>
                    <div class="pb-content lined-paper">
                        <?php echo nl2br(htmlspecialchars($log['noi_dung'])); ?>
                    </div>
                </div>

                <div class="p-box box-long">
                    <div class="pb-title">II. CÔNG VIỆC THỰC HIỆN / KẾT QUẢ</div>
                    <div class="pb-content lined-paper">
                        <?php 
                        if(!empty($log['hu_hong'])) echo "<strong>- Tình trạng:</strong> " . nl2br(htmlspecialchars($log['hu_hong'])) . "<br><br>";
                        if(!empty($log['xu_ly'])) echo "<strong>- Xử lý:</strong> " . nl2br(htmlspecialchars($log['xu_ly']));
                        ?>
                    </div>
                </div>
            </div>
        </div> 

        <!-- FOOTER SIGNATURE (Sát lề dưới) -->
        <div class="print-footer-signature">
            <div class="p-sig">
                <strong>ĐẠI DIỆN BAN QUẢN LÝ</strong><br>
                <span>(Ký, ghi rõ họ tên)</span>
                <div class="sig-space"></div>
            </div>
            <div class="p-sig">
                <strong>NGƯỜI LẬP PHIẾU</strong><br>
                <span>(Ký, ghi rõ họ tên)</span>
                <div class="sig-space"></div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePrintDebug() { document.body.classList.toggle('debug-print-mode'); }
</script>

<style>
/* WEB STYLES */
.maintenance-view { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: start; }
.ticket-card { padding: 0; overflow: hidden; }
.ticket-header { background: #f8fafc; padding: 20px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; }
.ticket-status .label, .ticket-cost .label { display: block; font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600; margin-bottom: 4px; }
.ticket-status .value { font-size: 1.1rem; font-weight: 600; color: #334155; }
.ticket-cost .value { font-size: 1.2rem; font-weight: 700; color: #d97706; }
.ticket-body { padding: 25px; }
.content-block { margin-bottom: 25px; background: #fff; border-left: 4px solid transparent; padding-left: 15px; }
.block-title { margin: 0 0 10px 0; font-size: 0.95rem; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 8px; }
.block-content { font-size: 0.95rem; color: #1e293b; line-height: 1.6; background: #f8fafc; padding: 12px 15px; border-radius: 6px; }
.device-profile-card { padding: 0; overflow: hidden; }
.profile-header { padding: 25px; text-align: center; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-bottom: 1px solid #e2e8f0; }
.device-icon-large { width: 64px; height: 64px; background: #fff; border-radius: 50%; margin: 0 auto 15px auto; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--primary-color); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.profile-title h3 { margin: 0; font-size: 1.1rem; color: #0f172a; }
.profile-title .code { font-size: 0.85rem; color: #64748b; font-weight: 600; background: #e2e8f0; padding: 2px 8px; border-radius: 12px; margin-top: 5px; display: inline-block; }
.profile-details { padding: 20px; }
.detail-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.9rem; border-bottom: 1px dashed #f1f5f9; padding-bottom: 8px; }
.d-value { font-weight: 500; color: #334155; }
.profile-actions { padding: 0 20px 20px 20px; }

/* CSS CHO PHẦN IN ẤN */
.print-only { display: none; }

/* DEBUG PRINT MODE: Hiển thị đúng kích thước A4 trên màn hình */
body.debug-print-mode { background: #555 !important; padding: 40px 0 !important; overflow: auto; }
body.debug-print-mode .web-view, body.debug-print-mode .main-header, body.debug-print-mode .footer { display: none !important; }
body.debug-print-mode .print-only { 
    display: block !important; 
    width: 210mm; 
    height: 297mm; /* Cố định chiều cao A4 */
    background: #fff; 
    margin: 0 auto; 
    padding: 0; 
    box-shadow: 0 0 20px rgba(0,0,0,0.5); 
    box-sizing: border-box;
    position: relative;
}
body.debug-print-mode .a4-page-wrapper {
    padding: 10mm; /* Giả lập margin 1cm */
    height: 100%;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
}

/* PRINT STYLES CHÍNH THỨC */
@media print {
    @page { 
        size: A4; 
        margin: 10mm; /* Giữ lề 1cm */
    }
    html, body {
        height: 100%;
        overflow: hidden !important; /* QUAN TRỌNG: Cắt bỏ mọi nội dung tràn sang trang 2 */
        margin: 0; 
        padding: 0;
    }
    body { background: #fff !important; font-family: "Times New Roman", Times, serif; font-size: 11pt; color: #000; }
    .web-view, .main-header, .footer, .page-header, footer, .header-actions { display: none !important; }
    
    /* Hiển thị vùng in */
    .print-only { 
        display: block !important; 
        width: 100%; 
        /* Giảm xuống 270mm để tạo vùng an toàn tuyệt đối, tránh nhảy trang */
        height: 270mm; 
        box-sizing: border-box;
        font-family: "Times New Roman", Times, serif;
        font-size: 13pt;
        color: #000;
        line-height: 1.4;
        overflow: hidden; /* Ẩn phần thừa nếu có */
        position: relative;
    }

    .a4-page-wrapper {
        width: 100%;
        height: 100%; 
        display: flex;
        flex-direction: column;
    }

    /* Container chính của nội dung sẽ giãn ra để chiếm chỗ */
    .print-content-flow {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* --- CÁC THÀNH PHẦN NỘI DUNG --- */
    .p-header-table { width: 100%; margin-bottom: 5px; flex-shrink: 0; }
    .p-header-table td { vertical-align: bottom; }
    
    .p-date { font-size: 13pt; font-style: italic; margin-bottom: 0; }
    .p-ticket-no-clean { font-size: 13pt; font-weight: bold; margin-bottom: 0; }

    .p-title { text-align: center; font-size: 24pt; font-weight: bold; margin: 5px 0 10px 0; text-transform: uppercase; flex-shrink: 0; }

    .p-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; flex-shrink: 0; table-layout: fixed; }
    .p-table td { padding: 0; margin: 0; vertical-align: bottom; } /* Căn đáy */
    
    /* LABEL ALIGNMENT - ĐỒNG NHẤT LINE-HEIGHT VỚI DÒNG KẺ */
    .pt-label { 
        font-weight: bold; 
        white-space: nowrap; 
        padding-right: 5px; 
        height: 30px; 
        line-height: 30px; 
        font-size: 13pt; 
        vertical-align: bottom; 
    }
    
    .pt-label-top { 
        font-weight: bold; 
        white-space: nowrap; 
        padding-right: 5px; 
        vertical-align: top !important; 
        padding-top: 0; 
        line-height: 30px; 
        font-size: 13pt; 
    }
    
    /* DÒNG KẺ NGANG */
    .p-line-single {
        padding-left: 5px; width: auto;
        height: 30px; 
        line-height: 30px; 
        background-image: repeating-linear-gradient(transparent, transparent 29px, #000 30px);
        background-attachment: local;
        font-size: 13pt;
        vertical-align: bottom;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
    
    .p-line-double {
        padding-left: 5px; width: auto;
        height: 60px; 
        line-height: 30px;
        background-image: repeating-linear-gradient(transparent, transparent 29px, #000 30px);
        background-attachment: local;
        vertical-align: top !important;
        font-size: 13pt;
    }

    /* Wrapper của các Box sẽ giãn hết cỡ */
    .p-content-boxes { 
        display: flex; 
        flex-direction: column; 
        gap: 10px; 
        flex-grow: 1; /* Quan trọng: Giãn để lấp đầy trang */
        overflow: hidden;
    }
    
    .p-box { border: 1.5pt solid #000; display: flex; flex-direction: column; }
    
    /* Box I giãn mạnh nhất */
    .box-short { 
        flex-grow: 2; 
        min-height: 80px; 
    } 
    
    /* Box II giãn vừa phải */
    .box-long { 
        flex-grow: 1.5; 
        min-height: 80px; 
    }

    .pb-title { font-weight: bold; background: #e0e0e0 !important; padding: 4px 8px; border-bottom: 1.5pt solid #000; -webkit-print-color-adjust: exact; font-size: 13pt; }
    .pb-content { padding: 4px 8px; flex-grow: 1; background-image: repeating-linear-gradient(transparent, transparent 29px, #bbb 30px); line-height: 30px; background-attachment: local; font-size: 13pt; }

    /* --- FOOTER CHỮ KÝ --- */
    .print-footer-signature { 
        display: flex; 
        justify-content: space-between; 
        width: 100%; 
        margin-top: 5px;
        flex-shrink: 0; 
    }
    .p-sig { text-align: center; width: 45%; font-size: 13pt; }
    .sig-space { 
        height: 3.5cm; /* Giảm còn 3.5cm để đảm bảo an toàn tuyệt đối cho 1 trang */
    }
}
</style>
