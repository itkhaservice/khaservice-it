<?php
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

$project_id = $_GET['project_id'] ?? '';

try {
    if ($project_id === '') {
        // Nếu không chọn dự án, trả về rỗng hoặc tất cả (tùy logic, ở đây trả về rỗng để bắt buộc chọn dự án)
        echo json_encode([]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id, ma_tai_san, ten_thiet_bi 
        FROM devices 
        WHERE project_id = ? 
        ORDER BY ten_thiet_bi
    ");
    $stmt->execute([$project_id]);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($devices);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
