<?php
session_start();

require_once __DIR__ . '/../db/config.php';
require "../app/auth.php";
require "../app/audit_helper.php";

if (!isset($_SESSION["user"])) {
    die("Acceso denegado");
}

$user = $_SESSION["user"];
$role = $user["role"];
$user_id = $user["id"];

logAudit($db, $user_id, "EXPORT_CSV", "tickets");

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="tickets.csv"');

$output = fopen("php://output", "w");

fputcsv($output, ["ID","Título","Estado","Prioridad","Asignado","Fecha"]);

$sql = "SELECT id, title, status, priority, assigned_to, created_at FROM tickets";
$params = [];

/* 🔧 FIX: rol correcto */
if ($role === "tecnico") {
    $sql .= " WHERE assigned_to = ?";
    $params[] = $user_id;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row["id"],
        $row["title"],
        $row["status"],
        $row["priority"],
        $row["assigned_to"] ?? "Sin asignar",
        $row["created_at"]
    ]);
}

fclose($output);
exit();