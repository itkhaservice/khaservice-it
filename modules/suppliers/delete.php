<?php
if (isset($_GET['id'])) {
    $supplier_id = $_GET['id'];

    try {
        $check_stmt = $pdo->prepare("SELECT id FROM suppliers WHERE id = ?");
        $check_stmt->execute([$supplier_id]);
        if ($check_stmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
            $stmt->execute([$supplier_id]);
            set_message('success', 'Nhà cung cấp đã được xóa thành công!');
        } else {
            set_message('error', 'Nhà cung cấp không tìm thấy để xóa.');
        }
    } catch (PDOException $e) {
        set_message('error', 'Lỗi khi xóa nhà cung cấp: ' . $e->getMessage());
    }
} else {
    set_message('error', 'Không có ID nhà cung cấp được cung cấp.');
}

header("Location: index.php?page=suppliers/list");
exit;
