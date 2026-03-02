<?php
// public/api/get_inspection_details.php
session_start();
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(['error' => 'Missing ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT ci.*, p.ten_du_an, u.fullname as inspector_name 
                          FROM car_inspections ci
                          JOIN projects p ON ci.project_id = p.id
                          JOIN users u ON ci.inspector_id = u.id
                          WHERE ci.id = ?");
    $stmt->execute([$id]);
    $details = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($details) {
        // Tự động sinh token nếu chưa có
        if (empty($details['signing_token'])) {
            $token = bin2hex(random_bytes(16));
            $pdo->prepare("UPDATE car_inspections SET signing_token = ? WHERE id = ?")->execute([$token, $id]);
            $details['signing_token'] = $token;
        }

        // Tạo URL ký
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $base_dir = str_replace('/api', '', dirname($_SERVER['SCRIPT_NAME']));
        $details['signing_url'] = $protocol . $host . $base_dir . '/confirm_inspection.php?token=' . $details['signing_token'];

        echo json_encode($details);
    } else {
        echo json_encode(['error' => 'Not found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
