<?php
session_start();
require_once __DIR__ . '/../db/config.php';
require "../app/auth.php";

if (!isset($_SESSION["user_id"])) {
    die("Acceso denegado");
}

$user_id = $_SESSION["user_id"];
// 👇 NUEVO
$usuario = $_SESSION["user"]["name"] ?? ("User #" . $user_id);
$fecha_generacion = date("d/m/Y H:i");
$ticket_id = $_GET["id"] ?? null;

if (!$ticket_id) {
    die("Ticket no encontrado");
}

// Registrar exportación en auditoría
logExport($db, $user_id, "TICKET_HTML");

// Datos del ticket
$stmt = $db->prepare("SELECT * FROM tickets WHERE id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    die("Ticket no encontrado");
}

// Comentarios del ticket
$stmt = $db->prepare("SELECT * FROM comments WHERE ticket_id = ? ORDER BY created_at ASC");
$stmt->execute([$ticket_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Historial de cambios críticos
$stmt = $db->prepare("SELECT * FROM ticket_history WHERE ticket_id = ? ORDER BY created_at ASC");
$stmt->execute([$ticket_id]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Adjuntos
$stmt = $db->prepare("SELECT * FROM attachments WHERE ticket_id = ?");
$stmt->execute([$ticket_id]);
$attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Ticket #<?= htmlspecialchars($ticket["id"]) ?></title>
    <style>
    body { 
        font-family: Arial, sans-serif; 
        margin: 40px; 
        color: #333;
    }

    h1, h2 { margin-bottom: 5px; }

    table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-bottom: 20px; 
    }

    th, td { 
        border: 1px solid #ccc; 
        padding: 8px; 
    }

    th { background-color: #f0f0f0; }

    .section { margin-bottom: 30px; }

    /* 👇 NUEVO */
    .report-header {
        border-bottom: 2px solid #000;
        margin-bottom: 20px;
        padding-bottom: 10px;
    }

    .report-header h1 {
        margin: 0;
        font-size: 28px;
    }

    .report-meta {
        margin-top: 10px;
        font-size: 14px;
    }

    .report-meta p {
        margin: 3px 0;
    }

    .footer {
        margin-top: 40px;
        font-size: 12px;
        color: #777;
        border-top: 1px solid #ccc;
        padding-top: 10px;
    }
</style>
</head>
<body>

<div class="report-header">
    <h1>SecureDesk DAM</h1>
    <p><strong>Informe de Ticket</strong></p>

    <div class="report-meta">
        <p><strong>Generado por:</strong> <?= htmlspecialchars($usuario) ?></p>
        <p><strong>Fecha:</strong> <?= $fecha_generacion ?></p>
        <p><strong>Ticket ID:</strong> #<?= htmlspecialchars($ticket["id"]) ?></p>
    </div>
</div>

<div class="section">
    <h2>Datos del Ticket</h2>
    <table>
        <tr><th>Título</th><td><?= htmlspecialchars($ticket["title"]) ?></td></tr>
        <tr><th>Estado</th><td><?= htmlspecialchars($ticket["status"]) ?></td></tr>
        <tr><th>Prioridad</th><td><?= htmlspecialchars($ticket["priority"]) ?></td></tr>
        <tr><th>Asignado a</th><td><?= htmlspecialchars($ticket["assigned_to"] ?? "Sin asignar") ?></td></tr>
        <tr><th>Creado</th><td><?= htmlspecialchars($ticket["created_at"]) ?></td></tr>
        <tr><th>Última actualización</th><td><?= htmlspecialchars($ticket["updated_at"] ?? "-") ?></td></tr>
    </table>
</div>

<div class="section">
    <h2>Comentarios</h2>
    <table>
        <tr>
            <th>Usuario</th>
            <th>Comentario</th>
            <th>Fecha</th>
        </tr>
        <?php foreach ($comments as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c["user"] ?? "Desconocido") ?></td>
            <td><?= htmlspecialchars($c["comment"]) ?></td>
            <td><?= htmlspecialchars($c["created_at"]) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="section">
    <h2>Historial de Cambios</h2>
    <table>
        <tr>
            <th>Usuario</th>
            <th>Acción</th>
            <th>Fecha</th>
        </tr>
        <?php foreach ($history as $h): ?>
        <tr>
            <td><?= htmlspecialchars($h["user"] ?? "Desconocido") ?></td>
            <td><?= htmlspecialchars($h["action"]) ?></td>
            <td><?= htmlspecialchars($h["created_at"]) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="section">
    <h2>Archivos Adjuntos</h2>
    <table>
        <tr>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Subido</th>
        </tr>
        <?php foreach ($attachments as $a): ?>
        <tr>
            <td><?= htmlspecialchars($a["filename"]) ?></td>
            <td><?= htmlspecialchars($a["mime_type"]) ?></td>
            <td><?= htmlspecialchars($a["created_at"]) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<div class="footer">
    Documento generado automáticamente por SecureDesk DAM
</div>
<button onclick="window.print()">Imprimir Informe</button>

</body>
</html>