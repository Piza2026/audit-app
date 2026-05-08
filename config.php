<?php
$dbPath = __DIR__ . "/securedesk.sqlite";

try {
    $db = new PDO("sqlite:" . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    die("Error de conexión a la base de datos");
}

date_default_timezone_set('Europe/Madrid');

function logAction(
    $db,
    $user_id,
    $action,
    $ticket_id = null,
    $entity = "ticket",
    $entity_id = null,
    $details = null
) {
    $entity_id = $entity_id ?? ($ticket_id ?? 0);
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    $sql = "
        INSERT INTO audit_logs 
        (user_id, action, entity, entity_id, details, ip_address, created_at)
        VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$user_id, $action, $entity, $entity_id, $details, $ip]);
}

function logExport($db, $user_id, $format, $entity = "tickets") {
    logAction($db, $user_id, "EXPORT_" . strtoupper($format), null, $entity);
}