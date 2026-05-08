<?php
session_start();
require_once __DIR__ . '/../db/config.php';

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

$id = $_GET["id"] ?? null;

if (!$id) {
    exit("Archivo no encontrado");
}

$sql = "SELECT * FROM attachments WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$id]);

$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    exit("Archivo no encontrado");
}

$path = $file["filepath"];

if (!file_exists($path)) {
    exit("Archivo no existe");
}

header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"" . $file["filename"] . "\"");
header("Content-Length: " . filesize($path));

readfile($path);
exit();
?>