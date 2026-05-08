<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../db/config.php';
require "../app/auth.php";
require "../app/audit_helper.php";
require_once __DIR__ . '/../includes/error_handler.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verifyCSRF($_POST["csrf_token"] ?? "")) {
        showError(403, "Token CSRF inválido");
    }

    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $ip = $_SERVER["REMOTE_ADDR"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Usuario y contraseña obligatorios";
    } else {

        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password_hash"])) {

            $_SESSION["user"] = [
                "id" => $user["id"],
                "username" => $user["username"],
                "role" => $user["role"]
            ];
            $_SESSION["last_activity"] = time();

            // limpiar intentos
            $stmt = $db->prepare("DELETE FROM login_attempts WHERE username = ?");
            $stmt->execute([$username]);

            logAudit($db, $user["id"], "LOGIN_OK", "user", $user["id"], $ip);

            header("Location: dashboard.php");
            exit();

        } else {

            logAudit($db, null, "LOGIN_FAIL", "user", null, $username);

            $error = "Credenciales incorrectas";
        }
    }
}
?>

<h2>Login SecureDesk</h2>
<?php if (isset($_GET["error"]) && $_GET["error"] === "timeout"): ?>
    <p style="color:red">Sesión expirada por inactividad</p>
<?php endif; ?>

<?php if ($error): ?>
<p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">

    Usuario:
    <input name="username" value="<?= htmlspecialchars($_POST["username"] ?? "") ?>"><br>

    Password:
    <input type="password" name="password"><br>

    <button type="submit">Entrar</button>
</form>