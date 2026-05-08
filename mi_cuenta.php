<?php
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

require "../views/menu.php";
?>

<h2>Mi Cuenta</h2>

<p><strong>Usuario:</strong> <?= $_SESSION["user"]["username"] ?></p>
<p><strong>Rol:</strong> <?= $_SESSION["role"] ?></p>