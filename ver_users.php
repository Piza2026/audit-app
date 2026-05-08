<?php
require_once __DIR__ . '/../db/config.php';

$stmt = $db->query("SELECT username, password_hash, role FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($users);
echo "</pre>";