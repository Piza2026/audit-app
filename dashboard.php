<?php

require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/audit_helper.php';

// proteger acceso
requireLogin();

require_once __DIR__ . '/../views/menu.php';

$user = $_SESSION["user"];
$user_id = $user["id"];
$role = $user["role"];

// auditoría
logAudit($db, $user_id, "ACCESS_DASHBOARD", "dashboard", null);

// total tickets
$totalTickets = $db->query("SELECT COUNT(*) FROM tickets")->fetchColumn();

// categorías
$resultCategorias = $db->query("
    SELECT category, COUNT(*) as total 
    FROM tickets 
    GROUP BY category
");

$categorias = [];
while ($row = $resultCategorias->fetch(PDO::FETCH_ASSOC)) {
    $categorias[$row['category']] = $row['total'];
}

// estados
$resultEstados = $db->query("
    SELECT status, COUNT(*) as total
    FROM tickets
    GROUP BY status
");

$estados = [
    'nuevo' => 0,
    'en_proceso' => 0,
    'resuelto' => 0
];

while ($row = $resultEstados->fetch(PDO::FETCH_ASSOC)) {
    $estados[$row['status']] = $row['total'];
}

// prioridades
$resultPrioridades = $db->query("
    SELECT priority, COUNT(*) as total
    FROM tickets
    GROUP BY priority
");

$prioridades = [
    'baja' => 0,
    'media' => 0,
    'alta' => 0,
    'critica' => 0
];

while ($row = $resultPrioridades->fetch(PDO::FETCH_ASSOC)) {
    $prioridades[$row['priority']] = $row['total'];
}

$fechaActual = date("d/m/Y H:i:s");
?>

<h1>Bienvenido <?= htmlspecialchars($user["username"]) ?></h1>
<p>Rol: <?= htmlspecialchars($role) ?></p>

<hr>

<h2>Dashboard</h2>
<p><strong>Última actualización:</strong> <?= $fechaActual ?></p>

<hr>

<h3>Accesos rápidos</h3>

<a href="tickets.php?filtro=criticos">Tickets críticos</a><br>
<a href="tickets.php?filtro=sin_asignar">Tickets sin asignar</a>

<hr>

<h2>Distribución de Tickets</h2>

<h3>Por Categoría</h3>
<table border="1">
    <tr>
        <th>Categoría</th>
        <th>Total</th>
    </tr>
    <?php foreach ($categorias as $cat => $total): ?>
    <tr>
        <td><?= htmlspecialchars($cat) ?></td>
        <td><?= $total ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<h3>Por Estado</h3>
<table border="1">
    <tr>
        <th>Estado</th>
        <th>Total</th>
    </tr>
    <?php foreach ($estados as $estado => $total): ?>
    <tr>
        <td><?= htmlspecialchars($estado) ?></td>
        <td><?= $total ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<hr>

<h3>Resumen Tickets</h3>
<ul>
    <li>Total: <?= $totalTickets ?></li>
    <li>Nuevos: <?= $estados['nuevo'] ?></li>
    <li>En proceso: <?= $estados['en_proceso'] ?></li>
    <li>Resueltos: <?= $estados['resuelto'] ?></li>
</ul>

<h3>Prioridades</h3>
<ul>
    <li>Baja: <?= $prioridades['baja'] ?></li>
    <li>Media: <?= $prioridades['media'] ?></li>
    <li>Alta: <?= $prioridades['alta'] ?></li>
    <li>Crítica: <?= $prioridades['critica'] ?></li>
</ul>

<hr>

<?php if ($role === "admin"): ?>
    <a href="users.php">Gestionar Usuarios</a><br>
    <a href="audit.php">Ver Auditoría</a><br>
<?php endif; ?>

<?php if ($role !== "lector"): ?>
    <a href="ticket_create.php">Crear Ticket</a><br>
<?php endif; ?>

<a href="tickets.php">Ver Tickets</a><br>
<a href="export_csv.php">Exportar CSV</a><br>
<a href="logout.php">Cerrar sesión</a>