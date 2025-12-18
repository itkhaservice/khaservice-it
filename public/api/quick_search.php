<?php
require_once '../../config/db.php'; // Adjusted path

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (empty($query)) {
    echo json_encode([]);
    exit;
}

$search_term = '%' . $query . '%';

try {
    $stmt = $pdo->prepare("
        SELECT id, ma_tai_san, ten_thiet_bi
        FROM devices
        WHERE ma_tai_san LIKE :search_term OR serial LIKE :search_term
        LIMIT 10
    ");
    $stmt->bindParam(':search_term', $search_term);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);

} catch (PDOException $e) {
    // Log the error for debugging, but don't show sensitive info to user
    error_log("Quick search error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}
?>