<?php
// seed_final.php
$host = 'localhost'; $dbname = 'khaservice_it'; $username = 'root'; $password = '';
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $p_id = 38; 
    $p_code = 'KHM';

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE maintenance_logs; TRUNCATE TABLE devices; SET FOREIGN_KEY_CHECKS = 1;");

    // --- 1. NHÓM VĂN PHÒNG ---
    $pdo->prepare("INSERT INTO devices (ma_tai_san, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, project_id, trang_thai) VALUES (?, ?, 'Văn phòng', 'Máy tính', ?, 'Đang sử dụng'")
        ->execute(["KHAS-$p_code-VP-PC-001", "Máy tính Kế toán", $p_id]);
    $pc_vp_id = $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO devices (ma_tai_san, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, project_id, parent_id, trang_thai) VALUES (?, ?, 'Văn phòng', 'Linh kiện', ?, ?, 'Đang sử dụng'")
        ->execute(["KHAS-$p_code-VP-LK-001", "RAM DDR4 8GB", $p_id, $pc_vp_id]);
    $pdo->prepare("INSERT INTO devices (ma_tai_san, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, project_id, parent_id, trang_thai) VALUES (?, ?, 'Văn phòng', 'Linh kiện', ?, ?, 'Đang sử dụng'")
        ->execute(["KHAS-$p_code-VP-LK-002", "SSD 256GB Kingfast", $p_id, $pc_vp_id]);

    // --- 2. NHÓM HỆ THỐNG XE ---
    $pdo->prepare("INSERT INTO devices (ma_tai_san, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, project_id, trang_thai) VALUES (?, ?, 'Bãi xe', 'Hệ thống', ?, 'Đang sử dụng'")
        ->execute(["KHAS-$p_code-BX-HT-001", "Hệ thống kiểm soát xe Cổng chính", $p_id]);
    $ht_id = $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO devices (ma_tai_san, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, project_id, parent_id, trang_thai) VALUES (?, ?, 'Bãi xe', 'Máy tính', ?, ?, 'Đang sử dụng'")
        ->execute(["KHAS-$p_code-BX-PC-001", "Máy tính xử lý biển số", $p_id, $ht_id]);
    $pc_bx_id = $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO devices (ma_tai_san, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, project_id, parent_id, trang_thai) VALUES (?, ?, 'Bãi xe', 'Linh kiện', ?, ?, 'Đang sử dụng'")
        ->execute(["KHAS-$p_code-BX-LK-001", "Card AI xử lý biển số", $p_id, $pc_bx_id]);

    for($i=1; $i<=4; $i++) {
        $pdo->prepare("INSERT INTO devices (ma_tai_san, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, project_id, parent_id, trang_thai) VALUES (?, ?, 'Bãi xe', 'Camera', ?, ?, 'Đang sử dụng'")
            ->execute(["KHAS-$p_code-BX-CAM-00$i", "Camera LPR Cổng chính -$i", $p_id, $ht_id]);
    }
    for($i=1; $i<=2; $i++) {
        $pdo->prepare("INSERT INTO devices (ma_tai_san, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, project_id, parent_id, trang_thai) VALUES (?, ?, 'Bãi xe', 'Barrier', ?, ?, 'Đang sử dụng'")
            ->execute(["KHAS-$p_code-BX-BR-00$i", "Barrier MAG Cổng chính -$i", $p_id, $ht_id]);
    }

    echo "DA NAP DU LIEU MAU CHUAN!\n";
} catch (Exception $e) { echo "LOI: " . $e->getMessage(); }
?>
