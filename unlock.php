<?php
$dbPath = __DIR__ . "/securedesk.sqlite";
$db = new PDO("sqlite:" . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Limpia intentos de login antiguos
$db->exec("DELETE FROM login_attempts;");

// Activa WAL para evitar futuros bloqueos
$db->exec("PRAGMA journal_mode=WAL;");

echo "DB desbloqueada y login_attempts limpio";
?>