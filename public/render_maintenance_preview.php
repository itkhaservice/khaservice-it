<?php
// Trang xem trước Phiếu công tác - Chuẩn phông chữ A4
ob_start();
session_start();
require_once __DIR__ . '/../config/db.php';

$token = $_GET['token'] ?? null;
if (!$token) die("Thiếu mã xác thực.");

$stmt = $pdo->prepare("
    SELECT ml.*, 
           d.ma_tai_san, d.ten_thiet_bi, d.loai_thiet_bi, d.model, d.ngay_mua,
           p.ten_du_an, p.dia_chi_duong, p.dia_chi_phuong_xa, p.dia_chi_tinh_tp,
           u.fullname as nguoi_thuc_hien
    FROM maintenance_logs ml
    LEFT JOIN devices d ON ml.device_id = d.id
    JOIN projects p ON ml.project_id = p.id
    JOIN users u ON ml.user_id = u.id
    WHERE ml.signing_token = ? AND ml.deleted_at IS NULL
");
$stmt->execute([$token]);
$log = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$log) die("Không tìm thấy dữ liệu.");

$display_project_name = $log['ten_du_an'];
$addr_parts = array_filter([$log['dia_chi_duong'], $log['dia_chi_phuong_xa'], $log['dia_chi_tinh_tp']]);
$display_address = implode(', ', $addr_parts);
$display_city = $log['dia_chi_tinh_tp'] ?: "TP. Hồ Chí Minh";
$current_user_name = $log['nguoi_thuc_hien'];
$is_web_preview = true;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        body { margin: 0; padding: 0; background: #525659; display: flex; justify-content: center; font-family: "Times New Roman", Times, serif; }
        .pdf-canvas { padding: 30px 10px; display: flex; justify-content: center; width: 100%; box-sizing: border-box; }
        
        /* ÉP BUỘC CỠ CHỮ CHUẨN A4 */
        .a4-page-wrapper {
            background: white !important;
            width: 210mm !important;
            min-height: 297mm !important;
            padding: 15mm !important;
            box-shadow: 0 0 20px rgba(0,0,0,0.5) !important;
            box-sizing: border-box !important;
            position: relative;
            color: #000 !important;
            line-height: 1.3 !important;
            font-size: 11pt !important; /* Cỡ chữ body chuẩn */
        }

        .a4-page-wrapper * { font-size: 11pt !important; } /* Ép mọi thứ về 11pt mặc định */

        .p-title { text-align: center !important; font-size: 22pt !important; font-weight: bold !important; margin: 15px 0 !important; text-transform: uppercase !important; }
        .p-date, .p-ticket-no-clean i { font-size: 12pt !important; font-style: italic !important; }
        .p-ticket-no-clean { font-size: 12pt !important; font-weight: bold !important; }
        
        .data-table { border-collapse: collapse !important; width: 100% !important; table-layout: fixed !important; margin-bottom: 10px !important; border: 1.5px solid #000 !important; }
        .data-table td { border: 1px solid #000 !important; padding: 6px 8px !important; vertical-align: top !important; }
        .label-cell { font-weight: bold !important; background-color: #f5f5f5 !important; width: 130px !important; }
        
        .box-header { background-color: #e0e0e0 !important; border-bottom: 1px solid #000 !important; padding: 6px 10px !important; font-weight: bold !important; text-transform: uppercase !important; }
        .content-box { border: 1.5px solid #000 !important; margin-bottom: 15px !important; }
        
        .sig-table { margin-top: 30px !important; text-align: center !important; }
        .sig-table strong { font-size: 11pt !important; text-transform: uppercase !important; }
        .sig-img { max-height: 80px !important; max-width: 140px !important; }

        @media (max-width: 210mm) {
            .a4-page-wrapper { transform: scale(calc(100vw / 225mm)); transform-origin: top center; margin-bottom: calc(-297mm * (1 - (100vw / 225mm))) !important; }
        }
    </style>
</head>
<body>
    <div class="pdf-canvas">
        <?php include __DIR__ . '/../modules/maintenance/print_template.inc.php'; ?>
    </div>
</body>
</html>
<?php exit; ?>
