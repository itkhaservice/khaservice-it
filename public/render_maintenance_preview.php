<?php
// Trang xem trước Phiếu công tác - Đồng bộ 100% Layout và Viền bảng
ob_start();
session_start();
require_once __DIR__ . '/../config/db.php';

$token = $_GET['token'] ?? null;
if (!$token) die("Thiếu mã xác thực.");

try {
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

} catch (Exception $e) {
    die("Lỗi: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Xem trước Phiếu công tác</title>
    <style>
        /* RESET VÀ GIẢ LẬP PDF */
        body { margin: 0; padding: 0; background: #525659; display: flex; justify-content: center; overflow-x: hidden; font-family: "Times New Roman", Times, serif; -webkit-text-size-adjust: 100%; -moz-text-size-adjust: 100%; text-size-adjust: 100%; }
        .pdf-canvas { padding: 30px 10px; display: flex; justify-content: center; box-sizing: border-box; }
        
        /* HIỆN THỊ PHẦN TỬ BỊ ẨN TRONG TEMPLATE */
        .print-only { display: block !important; }
        
        /* ĐỊNH DẠNG TRANG A4 - ÉP BUỘC CSS GIỐNG @MEDIA PRINT */
        .a4-page-wrapper {
            background: white !important;
            width: 210mm !important;
            min-height: 297mm !important;
            padding: 10mm 15mm !important; /* Lề chuẩn A4 */
            box-shadow: 0 0 20px rgba(0,0,0,0.5) !important;
            box-sizing: border-box !important;
            transform-origin: top center;
            display: flex !important;
            flex-direction: column !important;
            color: #000 !important;
            line-height: 1.3 !important;
            font-size: 11pt !important;
        }

        /* ĐỒNG BỘ VIỀN BẢNG VÀ MÀU NỀN CHÍNH XÁC */
        .data-table { 
            width: 100% !important; 
            border-collapse: collapse !important; 
            border: 1.5px solid #000 !important; /* Viền ngoài đậm */
            margin-bottom: 10px !important;
            table-layout: fixed !important;
        }
        .data-table td { 
            border: 1px solid #000 !important; /* Viền ô */
            padding: 6px 8px !important; 
            vertical-align: top !important; 
            overflow: hidden !important;
            word-wrap: break-word !important;
        }
        
        .label-cell { 
            font-weight: bold !important; 
            background-color: #f5f5f5 !important; /* Màu xám nhạt cho nhãn */
            width: 130px !important;
            white-space: nowrap !important;
        }

        .content-box { 
            border: 1.5px solid #000 !important; 
            margin-bottom: 15px !important; 
            display: flex !important; 
            flex-direction: column !important;
        }
        .box-header { 
            background-color: #e0e0e0 !important; /* Màu xám đậm cho tiêu đề khối */
            border-bottom: 1px solid #000 !important; 
            padding: 6px 10px !important; 
            font-weight: bold !important; 
            text-transform: uppercase !important;
        }
        .box-body { padding: 10px !important; min-height: 60px !important; }

        /* ĐỒNG BỘ HEADER VÀ TIÊU ĐỀ */
        .p-header-table { width: 100% !important; border-bottom: 2px solid #000 !important; margin-bottom: 5px !important; }
        .p-title { text-align: center !important; font-size: 24pt !important; font-weight: bold !important; margin: 15px 0 10px 0 !important; text-transform: uppercase !important; }
        .p-date { font-size: 13pt !important; font-style: italic !important; }
        .p-ticket-no-clean { font-size: 13pt !important; }

        /* CHỮ KÝ */
        .sig-table { width: 100% !important; text-align: center !important; margin-top: auto !important; border-collapse: collapse !important; }
        .sig-table td { border: none !important; width: 50% !important; vertical-align: top !important; }
        .sig-img { 
            max-height: 80px !important; 
            max-width: 140px !important; 
            object-fit: contain !important;
            /* Filter biến đen thành xanh đậm #003399 */
            filter: invert(12%) sepia(100%) saturate(3088%) hue-rotate(218deg) brightness(92%) contrast(106%);
        }

        /* DẤU CHẤM */
        .dot-placeholder { color: #555 !important; letter-spacing: 1px !important; white-space: nowrap !important; overflow: hidden !important; display: block !important; }

        /* SCALE CHO MOBILE CHỐNG VỠ UI */
        @media (max-width: 210mm) {
            .pdf-canvas { padding: 10px 0; }
            .a4-page-wrapper {
                transform: scale(calc(100vw / 225mm));
                margin-bottom: calc(-297mm * (1 - (100vw / 225mm))) !important;
                box-shadow: none !important;
            }
        }

        @media print {
            body { background: white !important; }
            .pdf-canvas { padding: 0 !important; }
            .a4-page-wrapper { 
                box-shadow: none !important; 
                margin: 0 !important; 
                padding: 10mm 15mm !important; 
                transform: none !important; 
                width: 100% !important; 
                min-height: auto !important; /* Loại bỏ chiều cao cố định */
            }
            .sig-table { 
                margin-top: 30px !important; /* Thay thế margin-top auto */
                page-break-inside: avoid !important; /* Chống cắt đôi chữ ký */
            }
        }
    </style>
</head>
<body>
    <div class="pdf-canvas">
        <?php include __DIR__ . '/../modules/maintenance/print_template.inc.php'; ?>
    </div>
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
    <script>
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('print')) {
                setTimeout(() => {
                    window.print();
                }, 500);
            }
        }

        // Lắng nghe tín hiệu từ trang mẹ (confirm_maintenance.php)
        window.addEventListener('message', function(event) {
            if (event.data === 'saveAsImage') {
                saveAsImage();
            }
        });

        async function saveAsImage() {
            const element = document.querySelector('.a4-page-wrapper');
            
            // Xử lý "nhuộm xanh" chữ ký cho html2canvas (vì thư viện này không hiểu CSS filter)
            const sigImages = document.querySelectorAll('.sig-img');
            const originalSrcs = [];
            
            for (let img of sigImages) {
                originalSrcs.push(img.src);
                try {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    canvas.width = img.naturalWidth;
                    canvas.height = img.naturalHeight;
                    ctx.drawImage(img, 0, 0);
                    
                    // Nhuộm màu xanh #003399
                    ctx.globalCompositeOperation = 'source-in';
                    ctx.fillStyle = '#003399';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    img.src = canvas.toDataURL();
                } catch (e) { console.error("Colorize failed", e); }
            }

            const canvas = await html2canvas(element, {
                scale: 2,
                useCORS: true,
                backgroundColor: "#ffffff"
            });
            
            // Trả lại ảnh gốc cho giao diện sau khi chụp xong
            sigImages.forEach((img, i) => img.src = originalSrcs[i]);
            
            const link = document.createElement('a');
            link.download = 'Phieu-cong-tac-<?= $log['id'] ?>.jpg';
            link.href = canvas.toDataURL('image/jpeg', 0.9);
            link.click();
        }
    </script>
</body>
</html>
<?php exit; ?>
