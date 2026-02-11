<?php
// modules/car_inspections/print.php
$id = $_GET['id'] ?? null;
if (!$id) die("Thiếu ID biên bản.");

// Xóa sạch nội dung đã xuất ra trước đó (như header.php)
if (ob_get_length()) ob_end_clean();

$stmt = $pdo->prepare("SELECT ci.*, p.ten_du_an, u.fullname as inspector_name 
                      FROM car_inspections ci 
                      JOIN projects p ON ci.project_id = p.id 
                      JOIN users u ON ci.inspector_id = u.id 
                      WHERE ci.id = ?");
$stmt->execute([$id]);
$ins = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ins) die("Không tìm thấy dữ liệu.");

// Trình bày ngày tháng tiếng Việt
$day = date('d', strtotime($ins['inspection_date']));
$month = date('m', strtotime($ins['inspection_date']));
$year = date('Y', strtotime($ins['inspection_date']));

// Tên dự án có thêm chữ Chung cư
$project_display_name = "Chung cư " . $ins['ten_du_an'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Biên bản kiểm tra xe - <?= htmlspecialchars($project_display_name) ?></title>
    <style>
        @page { size: A4; margin: 15mm; }
        body { font-family: "Times New Roman", Times, serif; line-height: 1.5; color: #000; margin: 0; padding: 0; font-size: 13pt; }
        .print-container { width: 100%; max-width: 190mm; margin: 0 auto; background: #fff; }
        
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .company-name { font-weight: bold; text-align: center; width: 50%; font-size: 11pt; vertical-align: top; text-transform: uppercase; }
        .national-motto { font-weight: bold; text-align: center; width: 50%; font-size: 12pt; vertical-align: top; }
        .national-motto .sub { font-weight: bold; font-size: 12pt; }
        .line { border-bottom: 1px solid #000; width: 160px; margin: 5px auto; }

        .report-title { text-align: center; font-size: 15pt; font-weight: bold; margin: 30px 0 15px 0; text-transform: uppercase; }
        .report-date { text-align: justify; font-style: normal; margin-bottom: 20px; font-size: 13pt; }

        .section-title { font-weight: bold; margin-top: 20px; margin-bottom: 10px; text-transform: uppercase; }
        .sub-section { margin-bottom: 15px; }
        .sub-title { font-weight: bold; margin-bottom: 8px; }
        
        /* Table for aligned info with 6:4 ratio */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; table-layout: fixed; }
        .info-table td { padding: 3px 0; vertical-align: top; overflow: hidden; }
        
        /* Define column widths: 60% | 40% */
        .col-name-group { width: 60%; }
        .col-pos-group { width: 40%; }
        
        .label-cell { width: 100px; }
        .val-cell { font-weight: normal; }
        .pos-label-cell { width: 80px; }

        .content-body { text-align: justify; margin-top: 10px; }
        .text-block { white-space: pre-wrap; margin-bottom: 15px; }
        .violation-list { margin: 10px 0 15px 50px; font-weight: bold; }

        .signature-section { width: 100%; display: flex; justify-content: space-between; margin-top: 40px; }
        .signature-box { text-align: center; width: 48%; font-weight: bold; }
        .signature-space { height: 100px; }

        @media print {
            .no-print { display: none; }
            body { margin: 0; padding: 0; }
        }
        .no-print-toolbar { background: #444; padding: 10px; text-align: center; position: sticky; top: 0; z-index: 100; }
        .btn-print { background: #108042; color: #fff; border: none; padding: 10px 25px; border-radius: 5px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <div class="no-print-toolbar no-print">
        <button onclick="window.print()" class="btn-print">IN BIÊN BẢN</button>
    </div>

    <div class="print-container">
        <table class="header-table">
            <tr>
                <td class="company-name">
                    CÔNG TY CỔ PHẦN QUẢN LÝ VÀ<br>VẬN HÀNH CAO ỐC KHÁNH HỘI
                    <div class="line"></div>
                </td>
                <td class="national-motto">
                    CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM<br>
                    <span class="sub">Độc lập – Tự do – Hạnh phúc</span>
                    <div class="line"></div>
                </td>
            </tr>
        </table>

        <div class="report-title">BIÊN BẢN KIỂM TRA XE</div>
        <div class="report-date">Hôm nay, ngày <?= $day ?> tháng <?= $month ?> năm <?= $year ?>, tiến hành kiểm tra xe tại <?= htmlspecialchars($project_display_name) ?> cụ thể như sau:</div>

        <div class="section-title">I. THÀNH PHẦN:</div>
        
        <div class="sub-section">
            <div class="sub-title">1. BÊN KIỂM TRA:</div>
            <table class="info-table">
                <colgroup>
                    <col class="col-name-group">
                    <col class="col-pos-group">
                </colgroup>
                <tr>
                    <td>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td class="label-cell">Họ và tên:</td>
                                <td class="val-cell"><?= htmlspecialchars($ins['inspector_name']) ?></td>
                            </tr>
                        </table>
                    </td>
                    <td>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td class="pos-label-cell">Chức vụ:</td>
                                <td class="val-cell"><?= htmlspecialchars($ins['inspector_position'] ?: '............................') ?></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <div class="sub-section">
            <div class="sub-title">2. THÔNG TIN BAN QUẢN LÝ:</div>
            <table class="info-table" style="margin-bottom: 0;">
                <tr>
                    <td class="label-cell" style="width: 100px;">Dự án:</td>
                    <td class="val-cell" style="font-weight: normal;"><?= htmlspecialchars($project_display_name) ?></td>
                </tr>
                <tr>
                    <td class="label-cell">Địa chỉ:</td>
                    <td class="val-cell" style="font-weight: normal;"><?= htmlspecialchars($ins['project_address'] ?: '....................................................................................') ?></td>
                </tr>
            </table>
            
            <table class="info-table">
                <colgroup>
                    <col class="col-name-group">
                    <col class="col-pos-group">
                </colgroup>
                <tr>
                    <td>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td class="label-cell">Họ và tên:</td>
                                <td class="val-cell"><?= htmlspecialchars($ins['bql_name_1'] ?: '......................................................') ?></td>
                            </tr>
                        </table>
                    </td>
                    <td>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td class="pos-label-cell">Chức vụ:</td>
                                <td class="val-cell"><?= htmlspecialchars($ins['bql_pos_1'] ?: '............................') ?></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td class="label-cell">Họ và tên:</td>
                                <td class="val-cell"><?= htmlspecialchars($ins['bql_name_2'] ?: '......................................................') ?></td>
                            </tr>
                        </table>
                    </td>
                    <td>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td class="pos-label-cell">Chức vụ:</td>
                                <td class="val-cell"><?= htmlspecialchars($ins['bql_pos_2'] ?: '............................') ?></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section-title">II. NỘI DUNG KIỂM TRA:</div>
        <div class="content-body">
            <div class="text-block"><?= !empty($ins['results_summary']) ? htmlspecialchars($ins['results_summary']) : "Tiến hành kiểm tra tình trạng thực tế tại bãi xe." ?></div>
            
            <?php if(!empty($ins['violation_details'])): ?>
                <div class="violation-list">
                    <?php 
                        $plates = preg_split('/[,\n\r]+/', $ins['violation_details']);
                        foreach($plates as $plate) {
                            if(trim($plate)) echo "<div>- " . htmlspecialchars(trim($plate)) . "</div>";
                        }
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="section-title">III. Ý KIẾN KHÁC:</div>
        <div class="content-body">
            <div class="text-block"><?= !empty($ins['other_opinions']) ? htmlspecialchars($ins['other_opinions']) : "Biên bản kết thúc vào lúc ...... giờ ...... phút cùng ngày và đọc lại cho các bên cùng nghe, đồng ý ký tên." ?></div>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                ĐẠI DIỆN BAN QUẢN LÝ
                <div class="signature-space"></div>
            </div>
            <div class="signature-box">
                NGƯỜI KIỂM TRA
                <div class="signature-space"></div>
                <?php 
                    // Refetch inspector name in case it was changed
                    $stmt_u = $pdo->prepare("SELECT fullname FROM users WHERE id = ?");
                    $stmt_u->execute([$ins['inspector_id']]);
                    echo htmlspecialchars($stmt_u->fetchColumn());
                ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php exit; ?>
