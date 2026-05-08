<?php

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../db/config.php';

use Dompdf\Dompdf;

$id = $_GET["id"] ?? null;

if (!$id) {
    die("Ticket no encontrado");
}

$sql = "SELECT * FROM tickets WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    die("Ticket no existe");
}

$sqlComments = "SELECT c.comment, u.username 
                FROM comments c 
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.ticket_id = ?";
$stmtComments = $db->prepare($sqlComments);
$stmtComments->execute([$id]);
$comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);

$dompdf = new Dompdf();

ob_start();
include 'ticket_report.php'; 
$html = ob_get_clean();

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("ticket_".$id.".pdf", ["Attachment" => true]);