<?php
// Trang xem trước Audit độc lập - Đồng bộ 100% với mẫu in mới
ob_start();
session_start();
require_once __DIR__ . '/../config/db.php';

$token = $_GET['token'] ?? null;
if (!$token) die("Thiếu mã xác thực.");

// Lấy thông tin biên bản
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <style>
        body { margin: 0; padding: 0; background: #525659; display: flex; justify-content: center; font-family: "Times New Roman", Times, serif; color: #000; }
        .pdf-container { padding: 30px 10px; display: flex; justify-content: center; width: 100%; box-sizing: border-box; }
        
        .a4-page { 
            background: white !important; 
            width: 210mm !important; 
            min-height: 297mm !important; 
            padding: 20mm 15mm !important; 
            box-shadow: 0 0 20px rgba(0,0,0,0.4) !important; 
            box-sizing: border-box !important; 
            transform-origin: top center;
            color: #000 !important;
            line-height: 1.4 !important;
            font-size: 13pt !important;
        }

        /* HEADER */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; table-layout: fixed; }
        .company-name { font-weight: bold; text-align: center; width: 45%; font-size: 12pt !important; text-transform: uppercase; vertical-align: top; }
        .nat-motto { font-weight: bold; text-align: center; width: 55%; font-size: 12pt !important; vertical-align: top; white-space: nowrap; }
        .line { border-bottom: 1px solid #000; width: 120px; margin: 5px auto; }

        .report-title { text-align: center !important; font-size: 16pt !important; font-weight: bold !important; margin: 35px 0 25px 0 !important; text-transform: uppercase !important; }
        .intro-text { text-align: justify; margin-bottom: 20px; }

        .section-title { font-weight: bold !important; margin-top: 25px !important; margin-bottom: 10px !important; text-transform: uppercase !important; }
        
        /* INFO TABLE */
        .info-table { width: 100% !important; border-collapse: collapse !important; margin-bottom: 5px !important; table-layout: fixed !important; }
        .info-table td { padding: 2px 0; vertical-align: top; }
        .col-main { width: 60%; }
        .col-side { width: 40%; }
        .label { font-weight: normal !important; } /* KHÔNG IN ĐẬM LABEL */

        .content-body { text-align: justify; white-space: pre-wrap; margin-top: 10px; }
        .violation-list { margin: 10px 0 15px 40px; font-weight: bold; }

        /* SIGNATURE */
        .sig-section { width: 100% !important; display: flex !important; justify-content: space-between !important; margin-top: 30px !important; text-align: center !important; }
        .sig-box { width: 48% !important; font-weight: bold !important; }
        .sig-space { height: 100px; display: flex; align-items: center; justify-content: center; }
        .sig-img { max-height: 90px; max-width: 100% !important; object-fit: contain; }

        @media (max-width: 210mm) {
            .a4-page { transform: scale(calc(100vw / 225mm)); margin-bottom: calc(-297mm * (1 - (100vw / 225mm))) !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>
    <div class="pdf-container">
        <div class="a4-page">
            <table class="header-table">
                <tr>
                    <td class="company-name">CÔNG TY CỔ PHẦN QUẢN LÝ VÀ<br>VẬN HÀNH CAO ỐC KHÁNH HỘI<div class="line"></div></td>
                    <td class="nat-motto">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM<br>Độc lập – Tự do – Hạnh phúc<div class="line"></div></td>
                </tr>
            </table>
            <div class="report-title">BIÊN BẢN KIỂM TRA XE</div>
            <div class="intro-text">Hôm nay, ngày <?= $day ?> tháng <?= $month ?> năm <?= $year ?>, tiến hành kiểm tra xe tại <?= htmlspecialchars($project_display_name) ?> cụ thể như sau:</div>
            
            <div class="section-title">I. THÀNH PHẦN:</div>
            <div style="font-weight: bold; margin-bottom: 5px;">1. BÊN KIỂM TRA:</div>
            <table class="info-table">
                <colgroup><col class="col-main"><col class="col-side"></colgroup>
                <tr>
                    <td><span class="label">Họ và tên:</span> Ông/Bà <?php echo htmlspecialchars($ins['inspector_name']); ?></td>
                    <td><span class="label">Chức vụ:</span> <?php echo htmlspecialchars($ins['inspector_position'] ?: '......................'); ?></td>
                </tr>
            </table>

            <div style="font-weight: bold; margin-top: 15px; margin-bottom: 5px;">2. BAN QUẢN LÝ DỰ ÁN:</div>
            <table class="info-table">
                <tr><td><span class="label">Dự án:</span> <?php echo htmlspecialchars($project_display_name); ?></td></tr>
                <tr><td><span class="label">Địa chỉ:</span> <?php echo htmlspecialchars($ins['project_address'] ?: '....................................................................................'); ?></td></tr>
            </table>
            <table class="info-table" style="margin-top: 5px;">
                <colgroup><col class="col-main"><col class="col-side"></colgroup>
                <tr>
                    <td><span class="label">Họ và tên:</span> Ông/Bà <?php echo htmlspecialchars($ins['bql_name_1'] ?: '..............................'); ?></td>
                    <td><span class="label">Chức vụ:</span> <?php echo htmlspecialchars($ins['bql_pos_1'] ?: '......................'); ?></td>
                </tr>
                <tr>
                    <td><span class="label">Họ và tên:</span> Ông/Bà <?php echo htmlspecialchars($ins['bql_name_2'] ?: '..............................'); ?></td>
                    <td><span class="label">Chức vụ:</span> <?php echo htmlspecialchars($ins['bql_pos_2'] ?: '......................'); ?></td>
                </tr>
            </table>

            <div class="section-title">II. NỘI DUNG KIỂM TRA:</div>
            <div class="content-body"><?php echo nl2br(htmlspecialchars($ins['results_summary'] ?: "Tiến hành kiểm tra tình trạng thực tế tại bãi xe.")); ?></div>
            
            <?php if(!empty($ins['violation_details'])): ?>
                <div class="violation-list">
                    <?php foreach(preg_split('/[,\n\r]+/', $ins['violation_details']) as $plate) if(trim($plate)) echo "<div>- " . htmlspecialchars(trim($plate)) . "</div>"; ?>
                </div>
            <?php endif; ?>

            <div class="section-title">III. Ý KIẾN KHÁC:</div>
            <div class="content-body" style="margin-bottom: 20px;"><?php echo nl2br(htmlspecialchars($ins['other_opinions'] ?: "Ban quản lý sẽ tiến hành rà soát và báo cáo lại cho Ban lãnh đạo.")); ?></div>
            <div class="content-body">Biên bản kết thúc vào lúc <?php echo !empty($ins['end_time']) ? date('H', strtotime($ins['end_time'])) : '......'; ?> giờ <?php echo !empty($ins['end_time']) ? date('i', strtotime($ins['end_time'])) : '......'; ?> phút cùng ngày và đọc lại cho các bên cùng nghe, đồng ý ký tên.</div>

            <div class="sig-section">
                <div class="sig-box">
                    ĐẠI DIỆN BAN QUẢN LÝ
                    <div class="sig-space">
                        <?php if($first_signer == 1): ?><img src="<?= $ins['bql_signature_1'] ?>" class="sig-img">
                        <?php elseif($first_signer == 2): ?><img src="<?= $ins['bql_signature_2'] ?>" class="sig-img"><?php endif; ?>
                    </div>
                    <div style="font-weight: normal;"><?= htmlspecialchars($first_signer == 1 ? $ins['bql_name_1'] : ($first_signer == 2 ? $ins['bql_name_2'] : '')) ?></div>
                </div>
                <div class="sig-box">
                    NGƯỜI KIỂM TRA
                    <div class="sig-space">
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
