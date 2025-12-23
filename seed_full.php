<?php
$host = 'localhost'; $dbname = 'khaservice_it'; $username = 'root'; $password = '';
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $p_id = 38; 
    $p_code = 'DA-DEMO';

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE maintenance_logs; TRUNCATE TABLE devices; SET FOREIGN_KEY_CHECKS = 1;");
    $pdo->beginTransaction();

    // 1. MÁY TÍNH 1
    $pdo->prepare("INSERT INTO devices (ma_tai_san, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, model, project_id, trang_thai) VALUES (?, ?, 'Văn phòng', 'Máy tính', 'OptiPlex 3050', ?, 'Đang sử dụng')")->execute(["KHAS-$p_code-VP-PC-001", "Máy tính Dell Kế toán", $p_id]);
    $pc1 = $pdo->lastInsertId();
    $parts1 = [["RAM 8GB DDR4", "Kingston"], ["SSD 250GB", "Samsung EVO"], ["PSU 450W", "Cooler Master"], ["Mainboard H110", "ASUS"], ["Màn hình 24 inch", "Dell UltraSharp"]];
    foreach($parts1 as $idx => $p) $pdo->prepare("INSERT INTO devices (ma_tai_san, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, model, project_id, parent_id, trang_thai) VALUES (?, ?, 'Văn phòng', 'Linh kiện', ?, ?, ?, 'Đang sử dụng')")->execute(["KHAS-$p_code-VP-LK-00".($idx+1), $p[0], $p[1], $p_id, $pc1]);

    // 2. MÁY TÍNH 2
    $pdo->prepare("INSERT INTO devices (ma_tai_san, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, model, project_id, trang_thai) VALUES (?, ?, 'Văn phòng', 'Máy tính', 'ProDesk 400', ?, 'Đang sử dụng')")->execute(["KHAS-$p_code-VP-PC-002", "Máy tính HP Hành chính", $p_id]);
    $pc2 = $pdo->lastInsertId();
    $parts2 = [["RAM 8GB DDR4", "G.Skill"], ["SSD 250GB", "WD Green"], ["PSU 450W", "Acbel"], ["Mainboard H110", "Gigabyte"], ["Màn hình 24 inch", "Samsung Curved"]];
    foreach($parts2 as $idx => $p) $pdo->prepare("INSERT INTO devices (ma_tai_san, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, model, project_id, parent_id, trang_thai) VALUES (?, ?, 'Văn phòng', 'Linh kiện', ?, ?, ?, 'Đang sử dụng')")->execute(["KHAS-$p_code-VP-LK-0".($idx+10), $p[0], $p[1], $p_id, $pc2]);

    // 3. HỆ THỐNG XE
    $pdo->prepare("INSERT INTO devices (ma_tai_san, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, project_id, trang_thai) VALUES (?, ?, 'Bãi xe', 'Hệ thống', ?, 'Đang sử dụng')")->execute(["KHAS-$p_code-BX-HT-001", "Hệ thống kiểm soát xe Cổng A", $p_id]);
    $ht = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO devices (ma_tai_san, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, model, project_id, parent_id, trang_thai) VALUES (?, ?, 'Bãi xe', 'Máy tính', 'Advantech IPC', ?, ?, 'Đang sử dụng')")->execute(["KHAS-$p_code-BX-PC-001", "Máy tính xử lý LPR", $p_id, $ht]);
    $pcbx = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO devices (ma_tai_san, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, model, project_id, parent_id, trang_thai) VALUES (?, ?, 'Bãi xe', 'Linh kiện', 'NVIDIA RTX 3060', ?, ?, 'Đang sử dụng')")->execute(["KHAS-$p_code-BX-LK-001", "Card đồ họa AI", $p_id, $pcbx]);

    for($i=1;$i<=4;$i++) $pdo->prepare("INSERT INTO devices (ma_tai_san, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, model, project_id, parent_id, trang_thai) VALUES (?, ?, 'Bãi xe', 'Camera', 'Hikvision 2MP', ?, ?, 'Đang sử dụng')")->execute(["KHAS-$p_code-BX-CAM-00$i", "Camera LPR Cổng A-$i", $p_id, $ht]);
    for($i=1;$i<=2;$i++) $pdo->prepare("INSERT INTO devices (ma_tai_san, ten_thiet_bi, nhom_thiet_bi, loai_thiet_bi, model, project_id, parent_id, trang_thai) VALUES (?, ?, 'Bãi xe', 'Barrier', 'MAG BR630', ?, ?, 'Đang sử dụng')")->execute(["KHAS-$p_code-BX-BR-00$i", "Barrier MAG-$i", $p_id, $ht]);

    $pdo->commit(); echo "DONE";
} catch (Exception $e) { if($pdo->inTransaction()) $pdo->rollBack(); echo "ERROR: " . $e->getMessage(); }
?>