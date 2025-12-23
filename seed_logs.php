<?php
$host = 'localhost'; $dbname = 'khaservice_it'; $username = 'root'; $password = '';
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $p_id = 38; 
    $user_id = 1; // Admin

    // 1. PHIẾU KIỂM TRA ĐỊNH KỲ (Chọn Hệ thống chính, không chọn linh kiện)
    $pdo->prepare("INSERT INTO maintenance_logs 
        (user_id, project_id, device_id, work_type, ngay_su_co, ngay_lap_phieu, noi_dung, hu_hong, xu_ly, client_name) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([
            $user_id, $p_id, 13, 
            'Kiểm tra định kỳ', date('Y-m-d'), date('Y-m-d'), 
            'Kiểm tra tác phong nhân viên và tình trạng vận hành thiết bị tại cổng chính.',
            'Tác phong chỉnh tề, thiết bị bám bụi nhẹ.',
            'Đã nhắc nhở vệ sinh thiết bị, lau chùi camera.',
            'Ban quản lý Khahomex'
        ]);

    // 2. PHIẾU SỬA CHỮA LINH KIỆN (Chọn chính xác linh kiện con)
    $pdo->prepare("INSERT INTO maintenance_logs 
        (user_id, project_id, device_id, work_type, ngay_su_co, ngay_lap_phieu, noi_dung, hu_hong, xu_ly, client_name) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([
            $user_id, $p_id, 15, // Card đồ họa AI (linh kiện con)
            'Sửa chữa / Thay thế', date('Y-m-d'), date('Y-m-d'), 
            'Máy tính bãi xe hay bị treo khi xử lý biển số.',
            'Card đồ họa AI bị quá nhiệt, quạt không quay.',
            'Vệ sinh quạt card đồ họa, tra keo tản nhiệt mới. Đã hoạt động ổn định.',
            'Kỹ thuật tòa nhà'
        ]);

    echo "DA TAO PHIEU MAU THANH CONG!";
} catch (Exception $e) { echo "ERROR: " . $e->getMessage(); }
?>