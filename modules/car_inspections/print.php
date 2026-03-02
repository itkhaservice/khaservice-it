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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biên bản kiểm tra xe - <?= htmlspecialchars($project_display_name) ?></title>
    <link rel="icon" type="image/png" href="../uploads/system/Logo1024x.png">
    <style>
        @page { size: A4; margin: 15mm 20mm; }
        body { font-family: "Times New Roman", Times, serif; line-height: 1.4; color: #000; margin: 0; padding: 0; font-size: 13pt; background: #fff; }
        
        .print-container { width: 100%; max-width: 190mm; margin: 0 auto; background: #fff; }
        
        /* HEADER */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; table-layout: fixed; }
        .header-cell { text-align: center; vertical-align: top; font-weight: bold; }
        .comp-name { font-size: 12pt; text-transform: uppercase; width: 45%; }
        .nat-motto { font-size: 12pt; width: 55%; white-space: nowrap; }
        .nat-motto span { font-size: 12pt; display: block; margin-top: 2px; }
        .line { border-bottom: 1px solid #000; width: 120px; margin: 5px auto; }

        .report-title { text-align: center; font-size: 16pt; font-weight: bold; margin: 35px 0 25px 0; text-transform: uppercase; }
        .intro-text { text-align: justify; margin-bottom: 20px; }

        .section-title { font-weight: bold; margin-top: 25px; margin-bottom: 10px; text-transform: uppercase; font-size: 13pt; }
        
        /* INFO TABLE 6:4 RATIO - NO MARGIN */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; table-layout: fixed; }
        .info-table td { padding: 3px 0; vertical-align: top; }
        .col-main { width: 60%; }
        .col-side { width: 40%; }
        .label { font-weight: normal; } /* KHÔNG IN ĐẬM LABEL */
        
        .content-body { text-align: justify; white-space: pre-wrap; margin-top: 10px; }
        .violation-list { margin: 10px 0 15px 25px; font-weight: bold; }

        /* SIGNATURE */
        .sig-section { width: 100%; display: flex; justify-content: space-between; margin-top: 30px; text-align: center; }
        .sig-box { width: 48%; font-weight: bold; }
        .sig-space { height: 100px; display: flex; align-items: center; justify-content: center; }
        .sig-img { max-height: 90px; max-width: 100%; object-fit: contain; }

        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
            .print-container { max-width: none; width: 100%; }
        }
        .no-print-toolbar { background: #444; padding: 10px; text-align: center; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 10px rgba(0,0,0,0.3); }
        .btn-print { background: #108042; color: #fff; border: none; padding: 10px 30px; border-radius: 6px; cursor: pointer; font-weight: 800; font-size: 1rem; transition: background 0.2s; }
        .btn-print:hover { background: #0d6635; }
    </style>
</head>
<body>
    <div class="no-print-toolbar no-print">
        <button onclick="window.print()" class="btn-print"><i class="fas fa-print"></i> IN BIÊN BẢN (A4)</button>
    </div>

    <div class="print-container">
        <!-- CƠ QUAN CHỦ QUẢN & TIÊU NGỮ -->
        <table class="header-table">
            <tr>
                <td class="header-cell comp-name">
                    CÔNG TY CỔ PHẦN QUẢN LÝ VÀ<br>VẬN HÀNH CAO ỐC KHÁNH HỘI
                    <div class="line"></div>
                </td>
                <td class="header-cell nat-motto">
                    CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM<br>
                    <span>Độc lập – Tự do – Hạnh phúc</span>
                    <div class="line"></div>
                </td>
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
                <?php 
                    $plates = preg_split('/[,\n\r]+/', $ins['violation_details']);
                    foreach($plates as $plate) {
                        if(trim($plate)) echo "<div>- " . htmlspecialchars(trim($plate)) . "</div>";
                    }
                ?>
            </div>
        <?php endif; ?>

        <div class="section-title">III. Ý KIẾN KHÁC:</div>
        <div class="content-body" style="margin-bottom: 20px;"><?php echo nl2br(htmlspecialchars($ins['other_opinions'] ?: "Ban quản lý sẽ tiến hành rà soát và báo cáo lại cho Ban lãnh đạo.")); ?></div>
        
        <div class="content-body">Biên bản kết thúc vào lúc <?php echo !empty($ins['end_time']) ? date('H', strtotime($ins['end_time'])) : '......'; ?> giờ <?php echo !empty($ins['end_time']) ? date('i', strtotime($ins['end_time'])) : '......'; ?> phút cùng ngày và đọc lại cho các bên cùng nghe, đồng ý ký tên.</div>

        <!-- CHỮ KÝ CÁC BÊN -->
        <div class="sig-section">
            <div class="sig-box">
                ĐẠI DIỆN BAN QUẢN LÝ
                <?php 
                    $first_signer = null;
                    if (!empty($ins['bql_signature_1']) && !empty($ins['bql_signature_2'])) {
                        $first_signer = (strtotime($ins['signed_at_1']) <= strtotime($ins['signed_at_2'])) ? 1 : 2;
                    } elseif (!empty($ins['bql_signature_1'])) {
                        $first_signer = 1;
                    } elseif (!empty($ins['bql_signature_2'])) {
                        $first_signer = 2;
                    }
                ?>
                <div class="sig-space">
                    <?php if($first_signer == 1): ?>
                        <img src="<?php echo $ins['bql_signature_1']; ?>" class="sig-img">
                    <?php elseif($first_signer == 2): ?>
                        <img src="<?php echo $ins['bql_signature_2']; ?>" class="sig-img">
                    <?php endif; ?>
                </div>
                <div style="font-weight: normal;">
                    <?php 
                        if($first_signer == 1) echo htmlspecialchars($ins['bql_name_1']);
                        elseif($first_signer == 2) echo htmlspecialchars($ins['bql_name_2']);
                    ?>
                </div>
            </div>
            <div class="sig-box">
                NGƯỜI KIỂM TRA
                <div class="sig-space">
                    <?php if (!empty($ins['it_signature'])): ?>
                        <img src="<?php echo $ins['it_signature']; ?>" class="sig-img">
                    <?php endif; ?>
                </div>
                <div style="font-weight: normal;">
                    <?php 
                        $stmt_u = $pdo->prepare("SELECT fullname FROM users WHERE id = ?");
                        $stmt_u->execute([$ins['inspector_id']]);
                        echo htmlspecialchars($stmt_u->fetchColumn());
                    ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php exit; ?>
