<?php
// Trang xem trước Audit - Chuẩn phông chữ A4
ob_start();
session_start();
require_once __DIR__ . '/../config/db.php';

$token = $_GET['token'] ?? null;
if (!$token) die("Thiếu mã xác thực.");

$stmt = $pdo->prepare("SELECT ci.*, p.ten_du_an, u.fullname as inspector_name 
                      FROM car_inspections ci 
                      JOIN projects p ON ci.project_id = p.id 
                      JOIN users u ON ci.inspector_id = u.id 
                      WHERE ci.signing_token = ?");
$stmt->execute([$token]);
$ins = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ins) die("Không tìm thấy dữ liệu.");

$day = date('d', strtotime($ins['inspection_date']));
$month = date('m', strtotime($ins['inspection_date']));
$year = date('Y', strtotime($ins['inspection_date']));
$project_display_name = "Chung cư " . $ins['ten_du_an'];

$first_signer = null;
if (!empty($ins['bql_signature_1']) && !empty($ins['bql_signature_2'])) {
    $first_signer = (strtotime($ins['signed_at_1']) <= strtotime($ins['signed_at_2'])) ? 1 : 2;
} elseif (!empty($ins['bql_signature_1'])) { $first_signer = 1; } 
elseif (!empty($ins['bql_signature_2'])) { $first_signer = 2; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        body { margin: 0; padding: 0; background: #525659; display: flex; justify-content: center; font-family: "Times New Roman", Times, serif; }
        .pdf-container { padding: 30px 10px; display: flex; justify-content: center; width: 100%; box-sizing: border-box; }
        
        .a4-page { 
            background: white !important; 
            width: 210mm !important; 
            min-height: 297mm !important; 
            padding: 20mm 15mm !important; 
            box-shadow: 0 0 20px rgba(0,0,0,0.4) !important; 
            box-sizing: border-box !important; 
            color: #000 !important;
            line-height: 1.5 !important;
            font-size: 13pt !important; /* Cỡ chữ mặc định Admin Audit */
        }

        .a4-page * { font-size: 13pt !important; }

        .comp-name { font-size: 11pt !important; font-weight: bold !important; text-transform: uppercase !important; text-align: center !important; }
        .nat-motto { font-size: 12pt !important; font-weight: bold !important; text-align: center !important; }
        .line { border-bottom: 1px solid #000; width: 120px; margin: 5px auto; }

        .report-title { text-align: center !important; font-size: 16pt !important; font-weight: bold !important; margin: 35px 0 20px 0 !important; text-transform: uppercase !important; }
        .section-title { font-weight: bold !important; margin-top: 25px !important; text-transform: uppercase !important; }
        
        .info-table { width: 100% !important; border-collapse: collapse !important; margin-bottom: 5px !important; }
        .label-cell { font-weight: bold !important; width: 110px !important; }

        .sig-section { width: 100% !important; display: flex !important; justify-content: space-between !important; margin-top: 50px !important; text-align: center !important; }
        .sig-box { width: 48% !important; font-weight: bold !important; }
        .sig-img { max-height: 90px !important; max-width: 100% !important; }

        @media (max-width: 210mm) {
            .a4-page { transform: scale(calc(100vw / 225mm)); transform-origin: top center; margin-bottom: calc(-297mm * (1 - (100vw / 225mm))) !important; }
        }
    </style>
</head>
<body>
    <div class="pdf-container">
        <div class="a4-page">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td class="comp-name">CÔNG TY CỔ PHẦN QUẢN LÝ VÀ<br>VẬN HÀNH CAO ỐC KHÁNH HỘI<div class="line"></div></td>
                    <td class="nat-motto">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM<br>Độc lập – Tự do – Hạnh phúc<div class="line"></div></td>
                </tr>
            </table>
            <div class="report-title">BIÊN BẢN KIỂM TRA XE</div>
            <p>Hôm nay, ngày <?= $day ?> tháng <?= $month ?> năm <?= $year ?>, tiến hành kiểm tra xe tại <?= htmlspecialchars($project_display_name) ?> cụ thể như sau:</p>
            
            <div class="section-title">I. THÀNH PHẦN:</div>
            <div style="font-weight: bold;">1. BÊN KIỂM TRA:</div>
            <table class="info-table" style="margin-left: 20px;">
                <tr>
                    <td style="width: 60%;">Họ và tên: <span style="font-weight: normal;"><?= htmlspecialchars($ins['inspector_name']) ?></span></td>
                    <td style="width: 40%;">Chức vụ: <span style="font-weight: normal;"><?= htmlspecialchars($ins['inspector_position'] ?: '......................') ?></span></td>
                </tr>
            </table>

            <div style="font-weight: bold; margin-top: 10px;">2. THÔNG TIN BAN QUẢN LÝ:</div>
            <table class="info-table" style="margin-left: 20px;">
                <tr><td class="label-cell">Dự án:</td><td style="font-weight: normal;"><?= htmlspecialchars($project_display_name) ?></td></tr>
                <tr><td class="label-cell">Địa chỉ:</td><td style="font-weight: normal;"><?= htmlspecialchars($ins['project_address'] ?: '....................................................................') ?></td></tr>
            </table>
            <table class="info-table" style="margin-left: 20px;">
                <tr>
                    <td style="width: 60%;">Đại diện 1: <span style="font-weight: normal;"><?= htmlspecialchars($ins['bql_name_1'] ?: '..............................') ?></span></td>
                    <td style="width: 40%;">Chức vụ: <span style="font-weight: normal;"><?= htmlspecialchars($ins['bql_pos_1'] ?: '......................') ?></span></td>
                </tr>
                <tr>
                    <td style="width: 60%;">Đại diện 2: <span style="font-weight: normal;"><?= htmlspecialchars($ins['bql_name_2'] ?: '..............................') ?></span></td>
                    <td style="width: 40%;">Chức vụ: <span style="font-weight: normal;"><?= htmlspecialchars($ins['bql_pos_2'] ?: '......................') ?></span></td>
                </tr>
            </table>

            <div class="section-title">II. NỘI DUNG KIỂM TRA:</div>
            <div style="text-align: justify; margin-top: 5px;"><?= nl2br(htmlspecialchars($ins['results_summary'] ?: 'Tiến hành kiểm tra tình trạng thực tế tại bãi xe.')) ?></div>
            
            <?php if(!empty($ins['violation_details'])): ?>
                <div style="margin: 10px 0 10px 40px; font-weight: bold;">
                    <?php foreach(preg_split('/[,\n\r]+/', $ins['violation_details']) as $plate) if(trim($plate)) echo "<div>- " . htmlspecialchars(trim($plate)) . "</div>"; ?>
                </div>
            <?php endif; ?>

            <div class="section-title">III. Ý KIẾN KHÁC:</div>
            <div style="text-align: justify; margin-top: 5px; min-height: 60px;"><?= nl2br(htmlspecialchars($ins['other_opinions'] ?: 'Ban quản lý sẽ tiến hành rà soát và báo cáo lại cho Ban lãnh đạo.')) ?></div>
            <p style="margin-top: 20px;">Biên bản kết thúc vào lúc <?= !empty($ins['end_time']) ? date('H', strtotime($ins['end_time'])) : '......' ?> giờ <?= !empty($ins['end_time']) ? date('i', strtotime($ins['end_time'])) : '......' ?> phút cùng ngày và đọc lại cho các bên cùng nghe, đồng ý ký tên.</p>

            <div class="sig-section">
                <div class="sig-box">
                    ĐẠI DIỆN BAN QUẢN LÝ
                    <div style="height: 100px; display: flex; align-items: center; justify-content: center;">
                        <?php if($first_signer == 1): ?><img src="<?= $ins['bql_signature_1'] ?>" class="sig-img">
                        <?php elseif($first_signer == 2): ?><img src="<?= $ins['bql_signature_2'] ?>" class="sig-img"><?php endif; ?>
                    </div>
                    <div style="font-weight: normal;"><?= htmlspecialchars($first_signer == 1 ? $ins['bql_name_1'] : ($first_signer == 2 ? $ins['bql_name_2'] : '')) ?></div>
                </div>
                <div class="sig-box">
                    NGƯỜI KIỂM TRA
                    <div style="height: 100px; display: flex; align-items: center; justify-content: center;">
                        <?php if(!empty($ins['it_signature'])): ?><img src="<?= $ins['it_signature'] ?>" class="sig-img"><?php endif; ?>
                    </div>
                    <div style="font-weight: normal;"><?= htmlspecialchars($ins['inspector_name']) ?></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php exit; ?>
