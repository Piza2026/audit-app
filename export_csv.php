<?php

require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/audit_helper.php';

// permisos
requireRole(["admin", "tecnico"]);

$user_id = $_SESSION["user"]["id"] ?? null;
if (!$user_id) {
    die("Acceso denegado");
}

// log correcto
logAudit(
    $db,
    $user_id,
    "EXPORT_CSV",
    "ticket",
    null,
    "Exportación de tickets en CSV"
);

// cabeceras CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="tickets.csv"');

// salida
$output = fopen("php://output", "w");

// cabecera columnas
fputcsv($output, ["ID", "Título", "Estado", "Prioridad", "Asignado", "Fecha"]);

// datos
$stmt = $db->query("
    SELECT id, title, status, priority, assigned_to, created_at 
    FROM tickets 
    ORDER BY created_at DESC
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row["id"],
        $row["title"],
        $row["status"],
        $row["priority"],
        $row["assigned_to"] ?? "",
        $row["created_at"]
    ]);
}

fclose($output);
exit();