<?php
require_once __DIR__ . '/../db/config.php';

$users = [
    ["admin", "1234", "admin"],
    ["tecnico", "1234", "tecnico"],
    ["lector", "1234", "lector"]
];

foreach ($users as $u) {

    $username = $u[0];
    $password = $u[1];
    $role = $u[2];
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // 1️⃣ Comprobar si el usuario ya existe
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // 2️⃣ Si existe, actualizar contraseña y rol
        $stmt = $db->prepare("UPDATE users SET password_hash = ?, role = ? WHERE id = ?");
        $stmt->execute([$hash, $role, $existing["id"]]);
    } else {
        // 3️⃣ Si no existe, insertar
        $stmt = $db->prepare("INSERT INTO users (username, password_hash, role, created_at)
                              VALUES (?, ?, ?, datetime('now'))");
        $stmt->execute([$username, $hash, $role]);
    }
}

echo "Usuarios restaurados/actualizados: admin, tecnico, lector (contraseña 1234)";
