<?php
// setup_config.php
$host = 'localhost'; $dbname = 'khaservice_it'; $username = 'root'; $password = '';
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // 1. Thiết lập Nhóm
    $groups = [
        ['Văn phòng', 'VP'],
        ['Bãi xe', 'BX']
    ];
    foreach($groups as $g) {
        $pdo->prepare("INSERT INTO settings_device_groups (group_name, group_code) VALUES (?, ?) 
                       ON DUPLICATE KEY UPDATE group_code = VALUES(group_code)")->execute($g);
    }

    // 2. Thiết lập Loại
    $types = [
        ['Máy tính', 'PC', 'Văn phòng'],
        ['Linh kiện', 'LK', 'Văn phòng'],
        ['Hệ thống', 'HT', 'Bãi xe'],
        ['Máy tính', 'PC', 'Bãi xe'],
        ['Camera', 'CAM', 'Bãi xe'],
        ['Barrier', 'BR', 'Bãi xe'],
        ['Linh kiện', 'LK', 'Bãi xe']
    ];
    foreach($types as $t) {
        $pdo->prepare("INSERT INTO settings_device_types (type_name, type_code, group_name) VALUES (?, ?, ?) 
                       ON DUPLICATE KEY UPDATE type_code = VALUES(type_code), group_name = VALUES(group_name)")->execute($t);
    }

    echo "DA CHUAN HOA CAU HINH NHOM & LOAI!\n";
} catch (Exception $e) { echo "LOI: " . $e->getMessage(); }
?>
