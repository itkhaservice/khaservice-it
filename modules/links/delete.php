<?php
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    set_message('error', 'ID không hợp lệ.');
    header("Location: index.php?page=links/list");
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE links SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    
    set_message('success', 'Đã xóa Link thành công!');
} catch (PDOException $e) {
    set_message('error', 'Lỗi khi xóa: ' . $e->getMessage());
}

header("Location: index.php?page=links/list");
exit;
