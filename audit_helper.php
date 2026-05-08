<?php

function logAudit($db, $userId, $action, $entity = null, $entityId = null, $details = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    $stmt = $db->prepare("
        INSERT INTO audit_logs (user_id, action, entity, entity_id, details, ip_address, created_at)
        VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
    ");

    $stmt->execute([$userId, $action, $entity, $entityId, $details, $ip]);
}