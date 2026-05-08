<?php

function showError($code, $message) {
    http_response_code($code);
    include __DIR__ . '/../views/error.php';
    exit();
}

function notFound($msg = "Recurso no encontrado") {
    showError(404, $msg);
}

function forbidden($msg = "No tienes permisos para acceder a esto") {
    showError(403, $msg);
}