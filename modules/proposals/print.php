<?php
// modules/proposals/print.php
$id = $_GET['id'] ?? null;
if (!$id) die("Thiếu ID đề xuất.");

if (ob_get_length()) ob_end_clean();

$stmt = $pdo->prepare("SELECT p.*, u1.fullname as proposer_name, u2.fullname as head_name 
                      FROM internal_proposals p 
                      LEFT JOIN users u1 ON p.proposer_id = u1.id 
                      LEFT JOIN users u2 ON p.department_head_id = u2.id 
                      WHERE p.id = ? AND p.deleted_at IS NULL");
$stmt->execute([$id]);
$prop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prop) die("Không tìm thấy dữ liệu.");

$stmt_items = $pdo->prepare("SELECT * FROM proposal_items WHERE proposal_id = ? ORDER BY sort_order ASC");
$stmt_items->execute([$id]);
$items = $stmt_items->fetchAll();

// Trình bày ngày tháng
$d = date('d', strtotime($prop['proposal_date']));
$m = date('m', strtotime($prop['proposal_date']));
$y = date('Y', strtotime($prop['proposal_date']));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đề xuất - <?= htmlspecialchars($prop['proposal_number'] ?? '') ?></title>
    <style>
        @page { size: A4; margin: 15mm 15mm 15mm 25mm; }
        body { font-family: "Times New Roman", Times, serif; line-height: 1.4; color: #000; margin: 0; padding: 0; font-size: 13pt; }
        .print-container { width: 100%; max-width: 170mm; margin: 0 auto; }
        
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .company-name { font-weight: bold; text-align: center; width: 45%; font-size: 11pt; vertical-align: top; text-transform: uppercase; }
        .national-motto { font-weight: bold; text-align: center; width: 55%; font-size: 12pt; vertical-align: top; }
        .national-motto .sub { font-weight: bold; font-size: 13pt; border-bottom: 1px solid #000; display: inline-block; padding-bottom: 2px; line-height: 1; }
        .line { border-bottom: 1px solid #000; width: 120px; margin: 5px auto; }

        .proposal-meta { display: flex; justify-content: space-between; font-style: italic; font-size: 12pt; margin-top: 10px; }
        .proposal-title { text-align: center; font-size: 16pt; font-weight: bold; margin: 30px 0 20px 0; text-transform: uppercase; }
        
        .recipient-section { margin-bottom: 20px; text-align: center; }
        .recipient-section b { font-size: 13pt; }

        .content-section { text-align: justify; margin-bottom: 15px; }
        .content-section u { font-weight: bold; }

        .data-table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 12pt; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 8px 5px; }
        .data-table th { background: #f2f2f2; text-transform: uppercase; font-size: 11pt; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }

        .amount-words { font-style: italic; margin: 10px 0; }
        
        .signature-table { width: 100%; border-collapse: collapse; margin-top: 30px; table-layout: fixed; }
        .signature-table td { text-align: center; vertical-align: top; padding-bottom: 100px; font-weight: bold; font-size: 12pt; }
        .signature-table .name-row td { padding-bottom: 0; vertical-align: bottom; }

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
        <button onclick="window.print()" class="btn-print"><i class="fas fa-print"></i> IN ĐỀ XUẤT</button>
    </div>

    <div class="print-container">
        <table class="header-table">
            <tr>
                <td class="company-name">
                    CÔNG TY CỔ PHẦN QUẢN LÝ VÀ<br>VẬN HÀNH CAO ỐC KHÁNH HỘI
                    <div class="line"></div>
                </td>
                <td class="national-motto">
                    CỘNG HÀA XÃ HỘI CHỦ NGHĨA VIỆT NAM<br>
                    <span class="sub">Độc lập – Tự do – Hạnh phúc</span>
                </td>
            </tr>
        </table>

        <div class="proposal-meta">
            <div>Số: <?= htmlspecialchars($prop['proposal_number'] ?? '……/ĐX-P.IT-' . $m . '-' . substr($y, 2)) ?></div>
            <div>TP. HCM, ngày <?= $d ?> tháng <?= $m ?> năm <?= $y ?></div>
        </div>

        <div class="proposal-title">PHIẾU ĐỀ XUẤT</div>

        <div class="content-section">
            <u><b>Kính gửi:</b></u> <?= htmlspecialchars($prop['recipient'] ?? '') ?>
        </div>

        <div class="content-section">
            <u><b>Nội dung:</b></u> <?= nl2br(htmlspecialchars($prop['title'] ?? '')) ?>.
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th width="40">STT</th>
                    <th>Nội dung</th>
                    <th width="60">ĐVT</th>
                    <th width="60">SL</th>
                    <th width="120">Đơn giá</th>
                    <th width="130">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $index => $item): ?>
                <tr>
                    <td class="text-center"><?= str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($item['item_name'] ?? '') ?></td>
                    <td class="text-center"><?= htmlspecialchars($item['unit'] ?? '') ?></td>
                    <td class="text-center"><?= (float)($item['quantity'] ?? 0) ?></td>
                    <td class="text-right"><?= number_format($item['unit_price'] ?? 0, 0, ',', '.') ?></td>
                    <td class="text-right"><?= number_format($item['total_price'] ?? 0, 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="5" class="text-center font-bold">TỔNG CỘNG</td>
                    <td class="text-right font-bold"><?= number_format($prop['total_amount_before_vat'] ?? 0, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td colspan="5" class="text-center font-bold">VAT (<?= $prop['vat_percentage'] ?? 0 ?>%)</td>
                    <td class="text-right font-bold"><?= number_format(($prop['total_amount_after_vat'] ?? 0) - ($prop['total_amount_before_vat'] ?? 0), 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td colspan="5" class="text-center font-bold">THÀNH TIỀN</td>
                    <td class="text-right font-bold"><?= number_format($prop['total_amount_after_vat'] ?? 0, 0, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>

        <div class="amount-words">
            <b>Bằng chữ:</b> <?= htmlspecialchars($prop['amount_in_words'] ?? '') ?>
        </div>

        <div class="content-section">
            <b>Ghi chú:</b> Giá trên đã bao gồm thuế VAT theo quy định hiện hành. 
            <?php if(!empty($prop['notes'])): ?>
                <?= htmlspecialchars($prop['notes'] ?? '') ?>
            <?php endif; ?>
        </div>

        <div class="content-section" style="margin-top: 15px;">
            Phòng IT kính trình đề xuất đến Tổng Giám đốc xem xét và phê duyệt.
        </div>
        
        <div style="font-weight: bold; margin-bottom: 20px;">Trân trọng!</div>

        <table class="signature-table">
            <tr>
                <td>NGƯỜI ĐỀ XUẤT</td>
                <td>TRƯỞNG BỘ PHẬN</td>
                <td>BAN TỔNG GIÁM ĐỐC</td>
            </tr>
            <tr class="name-row">
                <td><?= htmlspecialchars($prop['proposer_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($prop['head_name'] ?? '') ?></td>
                <td></td>
            </tr>
        </table>
    </div>
</body>
</html>
<?php exit; ?>
