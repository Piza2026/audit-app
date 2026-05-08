<?php
if (!isset($_SESSION)) {
    session_start();
}

$role = $_SESSION["user"]["role"] ?? null;
?>

<nav>
    <a href="dashboard.php">Inicio</a>

    <?php if ($role == "admin"): ?>
        <a href="users.php">Gestionar Usuarios</a>
    <?php endif; ?>

    <?php if ($role == "admin" || $role == "tecnico"): ?>
        <a href="ticket_create.php">Crear Ticket</a>
    <?php endif; ?>

    <a href="tickets.php">Ver Tickets</a>

    <?php if ($role == "admin"): ?>
        <a href="export_csv.php">Export CSV</a>
        <a href="audit.php">Auditoría</a>
    <?php endif; ?>

    <a href="mi_cuenta.php">Mi Cuenta</a>
    <a href="logout.php">Cerrar Sesión</a>
</nav>

<hr>