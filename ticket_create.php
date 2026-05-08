<?php
session_start();

require "../app/auth.php";
require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../app/auth_check.php';

requireRole(["admin", "tecnico"]);

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verifyCSRF($_POST["csrf_token"] ?? "")) {
        forbidden("Token inválido");
    }

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $priority = $_POST["priority"] ?? "";
    $category = trim($_POST["category"] ?? "");
    $assigned_to = $_POST["assigned_to"] !== "" ? (int)$_POST["assigned_to"] : null;

    if ($title === "") $errors[] = "Título obligatorio";
    if ($priority === "") $errors[] = "Prioridad obligatoria";

    $valid = ["baja", "media", "alta"];
    if (!in_array($priority, $valid)) {
        $errors[] = "Prioridad inválida";
    }

    if (empty($errors)) {

        $stmt = $db->prepare("
            INSERT INTO tickets
            (title, description, status, priority, category, created_by, assigned_to, created_at, updated_at)
            VALUES (?, ?, 'nuevo', ?, ?, ?, ?, datetime('now'), datetime('now'))
        ");

        $stmt->execute([
            $title,
            $description,
            $priority,
            $category,
            $_SESSION["user"]["id"],
            $assigned_to
        ]);

        header("Location: tickets.php");
        exit();
    }
}

// usuarios técnicos
$stmtUsers = $db->prepare("SELECT id, username FROM users WHERE role='tecnico'");
$stmtUsers->execute();
$users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Crear Ticket</title>
</head>
<body>

<h1>Crear Ticket</h1>

<?php foreach ($errors as $e): ?>
<p style="color:red"><?= htmlspecialchars($e) ?></p>
<?php endforeach; ?>

<form method="POST">
<input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">

<p>Título</p>
<input type="text" name="title">

<p>Descripción</p>
<textarea name="description"></textarea>

<p>Categoría</p>
<input type="text" name="category">

<p>Prioridad</p>
<select name="priority">
    <option value="baja">Baja</option>
    <option value="media">Media</option>
    <option value="alta">Alta</option>
</select>

<p>Asignado a</p>
<select name="assigned_to">
    <option value="">Nadie</option>
    <?php foreach ($users as $u): ?>
        <option value="<?= $u["id"] ?>">
            <?= htmlspecialchars($u["username"]) ?>
        </option>
    <?php endforeach; ?>
</select>

<br><br>
<button type="submit">Crear</button>

</form>

</body>
</html>