<?php
require_once __DIR__ . '/../config/db.php';

$token = $_GET['token'] ?? null;
if (!$token) die("Thiếu mã xác thực.");

// Lấy thông tin phiếu công tác dựa trên Token
$stmt = $pdo->prepare("SELECT ml.*, p.ten_du_an, p.dia_chi_duong, p.dia_chi_phuong_xa, p.dia_chi_tinh_tp, u.fullname as it_name 
                      FROM maintenance_logs ml 
                      JOIN projects p ON ml.project_id = p.id 
                      JOIN users u ON ml.user_id = u.id 
                      WHERE ml.signing_token = ? AND ml.deleted_at IS NULL");
$stmt->execute([$token]);
$log = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$log) die("Không tìm thấy dữ liệu.");

// --- LOGIC DỮ LIỆU ---
$is_custom_device = empty($log['device_id']);
$print_device_name = "";

if (!$is_custom_device) {
    $stmt_d = $pdo->prepare("SELECT ten_thiet_bi, ma_tai_san, ngay_mua FROM devices WHERE id = ?");
    $stmt_d->execute([$log['device_id']]);
    $device = $stmt_d->fetch();
    if ($device) {
        $log['ten_thiet_bi'] = $device['ten_thiet_bi'];
        $log['ma_tai_san'] = $device['ma_tai_san'];
        $log['ngay_mua'] = $device['ngay_mua'];
        $print_device_name = $device['ten_thiet_bi'];
    }
} else {
    $print_device_name = $log['custom_device_name'] ?: "";
}

$addr_parts = array_filter([$log['dia_chi_duong'], $log['dia_chi_phuong_xa'], $log['dia_chi_tinh_tp']]);
$display_address = implode(', ', $addr_parts);
$display_project_name = $log['ten_du_an'];
$display_city = $log['dia_chi_tinh_tp'] ?: "TP. Hồ Chí Minh";
$current_user_name = $log['it_name'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xem trước Phiếu công tác</title>
    <style>
        body { 
            margin: 0; padding: 0; background: #f0f2f5; 
            display: flex; justify-content: center; 
            padding-top: 20px; padding-bottom: 20px;
        }
        
        .a4-container {
            background: white;
            width: 210mm;
            min-height: 297mm;
            padding: 15mm 15mm;
            margin: 0 auto;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            box-sizing: border-box;
            font-family: "Times New Roman", Times, serif;
            color: #000;
            line-height: 1.3;
            font-size: 11pt;
        }

        .data-table { 
            width: 100% !important; 
            border-collapse: collapse !important; 
            border: 1.5px solid #000 !important; 
            margin-bottom: 10px !important;
            table-layout: fixed !important;
        }
        
        .data-table td { 
            border: 1px solid #000 !important; 
            padding: 6px 8px !important; 
            vertical-align: top !important;
            word-wrap: break-word;
            overflow: hidden;
        }

        .dot-placeholder {
            display: block;
            width: 100%;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: clip;
            color: #555;
        }

        .label-cell { font-weight: bold; background-color: #f5f5f5 !important; }
        .print-only { display: block !important; }
        .a4-page-wrapper { border: none !important; box-shadow: none !important; padding: 0 !important; width: 100% !important; }
        .p-header-table { width: 100%; border-bottom: 2px solid #000; margin-bottom: 5px; }
        .p-title { text-align: center; font-size: 22pt; font-weight: bold; margin: 15px 0 10px 0; text-transform: uppercase; }
        .content-box { border: 1.5px solid #000; margin-bottom: 15px; }
        .box-header { background-color: #e0e0e0 !important; border-bottom: 1px solid #000; padding: 6px 10px; font-weight: bold; text-transform: uppercase; }
        .box-body { padding: 10px; min-height: 60px; }
        .sig-table { width: 100%; text-align: center; margin-top: 20px; }
        .sig-table td { width: 50%; vertical-align: top; }
        .text-upper { text-transform: uppercase; }

        @media (max-width: 210mm) {
            body { padding: 0; }
            .a4-container { width: 100%; height: auto; padding: 10mm; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="a4-container">
        <?php include __DIR__ . '/../modules/maintenance/print_template.inc.php'; ?>
    </div>
</body>
</html>
<?php exit; ?>
