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
        echo json_encode($details);
    } else {
        echo json_encode(['error' => 'Not found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
