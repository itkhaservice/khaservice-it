<?php
// includes/audit_helper.php

function log_action($pdo, $action, $target_type, $target_id = null, $details = null) {
    $user_id = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    
    $sql = "INSERT INTO audit_logs (user_id, action, target_type, target_id, details, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $action, $target_type, $target_id, $details, $ip]);
}
