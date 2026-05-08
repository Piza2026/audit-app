<?php

require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/audit_helper.php';
require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../views/menu.php';

// proteger acceso
requireLogin();

$user_id = $_SESSION["user"]["id"];

$status = $_GET["status"] ?? "";
$priority = $_GET["priority"] ?? "";
$filtro = $_GET["filtro"] ?? "";
$buscar = $_GET["buscar"] ?? "";

// log búsqueda
if (!empty($buscar)) {
    logAudit($db, $user_id, "SEARCH_TICKETS", "tickets", null, $buscar);
}

// query base
$sql = "SELECT * FROM tickets WHERE 1=1";
$params = [];

// filtros
if ($status !== "") {
    $sql .= " AND status = ?";
    $params[] = $status;
}

if ($priority !== "") {
    $sql .= " AND priority = ?";
    $params[] = $priority;
}

if ($filtro === "criticos") {
    $sql .= " AND priority = 'alta'";
}

if ($filtro === "sin_asignar") {
    $sql .= " AND assigned_to IS NULL";
}

if ($buscar !== "") {
    $sql .= " AND (title LIKE ? OR description LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Tickets</title>
</head>
<body>

<h1>Listado de Tickets</h1>

<a href="export_csv.php">Export CSV</a>

<h3>Filtros rápidos</h3>
<a href="?filtro=criticos">Críticos</a> |
<a href="?filtro=sin_asignar">Sin asignar</a> |
<a href="tickets.php">Todos</a>

<form method="GET">
    <input type="text" name="buscar" placeholder="Buscar..." value="<?= htmlspecialchars($buscar) ?>">

    <select name="status">
        <option value="">Estado</option>
        <option value="nuevo" <?= $status=="nuevo"?"selected":"" ?>>Nuevo</option>
        <option value="en_proceso" <?= $status=="en_proceso"?"selected":"" ?>>En proceso</option>
        <option value="resuelto" <?= $status=="resuelto"?"selected":"" ?>>Resuelto</option>
    </select>

    <select name="priority">
        <option value="">Prioridad</option>
        <option value="baja" <?= $priority=="baja"?"selected":"" ?>>Baja</option>
        <option value="media" <?= $priority=="media"?"selected":"" ?>>Media</option>
        <option value="alta" <?= $priority=="alta"?"selected":"" ?>>Alta</option>
    </select>

    <button type="submit">Filtrar</button>
</form>

<br>

<table border="1" cellpadding="8">
<tr>
    <th>Título</th>
    <th>Estado</th>
    <th>Prioridad</th>
    <th>Asignado</th>
    <th>Fecha</th>
    <th>Acciones</th>
</tr>

<?php if ($tickets): ?>
    <?php foreach ($tickets as $t): ?>
    <tr>
        <td><?= htmlspecialchars($t["title"]) ?></td>
        <td><?= htmlspecialchars($t["status"]) ?></td>
        <td><?= htmlspecialchars($t["priority"]) ?></td>
        <td><?= htmlspecialchars($t["assigned_to"] ?? "Sin asignar") ?></td>
        <td><?= htmlspecialchars($t["created_at"]) ?></td>
        <td>
            <a href="ticket_detail.php?id=<?= $t["id"] ?>">Ver</a>
        </td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
<tr>
    <td colspan="6">No hay tickets</td>
</tr>
<?php endif; ?>

</table>

</body>
</html>