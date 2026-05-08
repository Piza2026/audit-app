<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/error_handler.php';

function requireLogin() {
    if (!isset($_SESSION["user"]["id"])) {
        header("Location: login.php");
        exit();
    }
}

function requireRole($roles) {
    requireLogin();

    $role = $_SESSION["user"]["role"] ?? null;

    if (!$role) {
        forbidden("Rol no definido");
    }

    if (!in_array($role, $roles)) {
        forbidden("No tienes permisos para acceder a esta sección");
    }
}

function generateCSRF() {
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

function verifyCSRF($token) {
    return isset($_SESSION["csrf_token"]) && hash_equals($_SESSION["csrf_token"], $token);
}