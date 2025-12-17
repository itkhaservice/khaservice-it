<?php
if (isset($_GET['id'])) {
    $supplier_id = $_GET['id'];

    $check_stmt = $pdo->prepare("SELECT id FROM suppliers WHERE id = ?");
    $check_stmt->execute([$supplier_id]);
    if ($check_stmt->fetch()) {
        // Before deleting a supplier, consider handling dependent devices.
        // For simplicity, we'll just delete the supplier. In a real app,
        // you might want to set device.supplier_id to NULL, or prevent deletion
        // if devices are linked.
        $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
        $stmt->execute([$supplier_id]);
        $_SESSION['message'] = 'Nhà cung cấp đã được xóa thành công!'; // For future use
    } else {
        $_SESSION['error'] = 'Nhà cung cấp không tìm thấy để xóa.'; // For future use
    }
}

header("Location: index.php?page=suppliers/list");
exit;
