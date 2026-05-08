<?php
session_start();

require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../includes/error_handler.php';
require "../app/auth.php";
require_once __DIR__ . '/../app/auth_check.php';

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

// 🎯 Roles
$role = $_SESSION["user"]["role"];
$isAdmin = $role === "admin";
$isTech = $role === "tecnico";
$isReader = $role === "lector";

$user_id = $_SESSION["user"]["id"];

// 🔎 ID ticket
$id = $_GET["id"] ?? null;

if (!$id) {
    notFound("ID de ticket no proporcionado");
}

// 🔎 Ticket
$stmt = $db->prepare("SELECT * FROM tickets WHERE id = ?");
$stmt->execute([$id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    notFound("El ticket no existe o ha sido eliminado");
}

// 📜 Historial
function saveHistory($db, $ticket_id, $user_id, $field, $old, $new) {
    $stmt = $db->prepare("
        INSERT INTO ticket_history
        (ticket_id, user_id, field_changed, old_value, new_value, created_at)
        VALUES (?, ?, ?, ?, ?, datetime('now'))
    ");
    $stmt->execute([$ticket_id, $user_id, $field, $old, $new]);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["comment"])) {

    if (!verifyCSRF($_POST["csrf_token"] ?? "")) {
        showError(403, "Token de seguridad inválido");
    }

    if (!($isAdmin || $isTech)) {
        forbidden("No tienes permisos para esta acción");
    }

    $ticket_id = $_POST["ticket_id"];
    $comment = trim($_POST["comment"]);

    if ($comment !== "") {
        $stmt = $db->prepare("
            INSERT INTO comments (ticket_id, user_id, comment, created_at)
            VALUES (?, ?, ?, datetime('now'))
        ");
        $stmt->execute([$ticket_id, $user_id, $comment]);

        logAction($db, $user_id, "Añadió comentario", $ticket_id);
    }

    header("Location: ticket_detail.php?id=" . $ticket_id);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["upload_file"])) {

    if (!verifyCSRF($_POST["csrf_token"] ?? "")) {
        showError(403, "Token de seguridad inválido");
    }

    if (!($isAdmin || $isTech)) {
        forbidden("No tienes permisos para esta acción");
    }

    $errors = [];
    $ticket_id = $_POST["ticket_id"];
    $file = $_FILES["file"];

    $maxSize = 5 * 1024 * 1024;
    if ($file["size"] > $maxSize) {
        $errors[] = "Archivo demasiado grande (máx 5MB)";
    }

    $allowedExtensions = ["png","jpg","jpeg","pdf","txt","log","csv"];
    $fileExtension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

    if (!in_array($fileExtension, $allowedExtensions)) {
        $errors[] = "Extensión no permitida";
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file["tmp_name"]);

    $allowedMimes = [
        "image/png",
        "image/jpeg",
        "application/pdf",
        "text/plain",
        "text/csv"
    ];

    if (!in_array($mimeType, $allowedMimes)) {
        $errors[] = "MIME no válido";
    }

    if (!empty($errors)) {
        showError(400, implode(", ", $errors));
    }

    $basePath = "../uploads/";
    $folder = $basePath . "ticket_" . $ticket_id . "/";

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $fileName = uniqid("file_", true) . "." . $fileExtension;
    $finalPath = $folder . $fileName;

    if (!move_uploaded_file($file["tmp_name"], $finalPath)) {
        showError(500, "Error al subir archivo");
    }

    $stmt = $db->prepare("
        INSERT INTO ticket_attachments
        (ticket_id, uploaded_by, file_name, file_path, mime_type, size)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $ticket_id,
        $user_id,
        $fileName,
        $finalPath,
        $mimeType,
        $file["size"]
    ]);

    header("Location: ticket_detail.php?id=" . $ticket_id);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["description"])) {

    if (!verifyCSRF($_POST["csrf_token"] ?? "")) {
        showError(403, "Token inválido");
    }

    if (!($isAdmin || $isTech)) {
        forbidden("No autorizado");
    }

    $description = trim($_POST["description"]);
    $status = $_POST["status"];
    $priority = $_POST["priority"];
    $assigned = $_POST["assigned_to"] !== "" ? (int)$_POST["assigned_to"] : null;

    $stmtOld = $db->prepare("SELECT status, priority, assigned_to FROM tickets WHERE id = ?");
    $stmtOld->execute([$id]);
    $old = $stmtOld->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("
        UPDATE tickets 
        SET description=?, status=?, priority=?, assigned_to=?, updated_at=datetime('now')
        WHERE id=?
    ");

    $stmt->execute([$description, $status, $priority, $assigned, $id]);

    logAction($db, $user_id, "Editó ticket", $id);

    header("Location: ticket_detail.php?id=".$id);
    exit();
}

$sqlComments = "SELECT c.comment, c.created_at, u.username
                FROM comments c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.ticket_id = ?
                ORDER BY c.created_at ASC";

$stmtComments = $db->prepare($sqlComments);
$stmtComments->execute([$id]);
$comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);

$sqlFiles = "SELECT * FROM ticket_attachments WHERE ticket_id = ?";
$stmtFiles = $db->prepare($sqlFiles);
$stmtFiles->execute([$id]);
$files = $stmtFiles->fetchAll(PDO::FETCH_ASSOC);

$sqlUsers = "SELECT id, username FROM users";
$stmtUsers = $db->prepare($sqlUsers);
$stmtUsers->execute();
$allUsers = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Detalle Ticket</title>
</head>
<body>

<h1>Detalle del Ticket</h1>

<p><b>Modo:</b> <?= $isReader ? "Solo lectura" : "Edición habilitada" ?></p>

<form method="POST">
<input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">

<p><b>ID:</b> <?= $ticket["id"] ?></p>
<p><b>Título:</b> <?= htmlspecialchars($ticket["title"]) ?></p>

<p><b>Descripción:</b><br>
<?php if (!$isReader): ?>
<textarea name="description"><?= htmlspecialchars($ticket["description"]) ?></textarea>
<?php else: ?>
<?= htmlspecialchars($ticket["description"]) ?>
<?php endif; ?>
</p>

<p><b>Estado:</b>
<?php if (!$isReader): ?>
<select name="status">
<option value="nuevo" <?= $ticket["status"]=="nuevo"?"selected":"" ?>>Nuevo</option>
<option value="en_proceso" <?= $ticket["status"]=="en_proceso"?"selected":"" ?>>En proceso</option>
<option value="resuelto" <?= $ticket["status"]=="resuelto"?"selected":"" ?>>Resuelto</option>
</select>
<?php else: ?>
<?= $ticket["status"] ?>
<?php endif; ?>
</p>

<p><b>Prioridad:</b>
<?php if (!$isReader): ?>
<select name="priority">
<option value="baja" <?= $ticket["priority"]=="baja"?"selected":"" ?>>Baja</option>
<option value="media" <?= $ticket["priority"]=="media"?"selected":"" ?>>Media</option>
<option value="alta" <?= $ticket["priority"]=="alta"?"selected":"" ?>>Alta</option>
</select>
<?php else: ?>
<?= $ticket["priority"] ?>
<?php endif; ?>
</p>

<p><b>Asignado a:</b>
<?php if (!$isReader): ?>
<select name="assigned_to">
<option value="">-- Ninguno --</option>
<?php foreach($allUsers as $u): ?>
<option value="<?= $u['id'] ?>" <?= $ticket["assigned_to"] == $u['id'] ? 'selected' : '' ?>>
<?= htmlspecialchars($u['username']) ?>
</option>
<?php endforeach; ?>
</select>
<?php else: ?>
<?= $ticket["assigned_to"] ?>
<?php endif; ?>
</p>

<?php if (!$isReader): ?>
<button type="submit">Guardar cambios</button>
<?php endif; ?>
</form>

<h3>Comentarios</h3>

<?php foreach ($comments as $c): ?>
<p><b><?= htmlspecialchars($c["username"] ?? 'Desconocido') ?>:</b> <?= htmlspecialchars($c["comment"]) ?></p>
<?php endforeach; ?>

<?php if (!$isReader): ?>
<h4>Añadir comentario</h4>
<form method="POST">
<input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
<input type="hidden" name="ticket_id" value="<?= $ticket["id"] ?>">
<textarea name="comment"></textarea>
<button type="submit">Comentar</button>
</form>
<?php endif; ?>

<h3>Adjuntos</h3>

<?php foreach ($files as $f): ?>
<p><?= htmlspecialchars($f["file_name"]) ?></p>
<?php endforeach; ?>

<?php if (!$isReader): ?>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
<input type="hidden" name="ticket_id" value="<?= $ticket["id"] ?>">
<input type="file" name="file" required>
<button type="submit" name="upload_file">Subir archivo</button>
</form>
<?php endif; ?>

</body>
</html>