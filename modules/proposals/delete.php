<?php
// modules/proposals/delete.php
$id = $_GET['id'] ?? null;

if (!$id) {
    $_SESSION['error'] = "Thiếu ID đề xuất.";
    header("Location: index.php?page=proposals/list");
    exit;
}

try {
    // Thực hiện Soft Delete bằng cách cập nhật deleted_at
    $stmt = $pdo->prepare("UPDATE internal_proposals SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = "Đã xóa đề xuất thành công.";
    } else {
        $_SESSION['error'] = "Không tìm thấy đề xuất hoặc đề xuất đã bị xóa trước đó.";
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Lỗi khi xóa đề xuất: " . $e->getMessage();
}

header("Location: index.php?page=proposals/list");
exit;
?>
