<?php
if (!isset($_SESSION)) {
    session_start();
}

$timeout = 900; 

if (!isset($_SESSION["user"]["id"])) {
    header("Location: login.php");
    exit;
}

if (isset($_SESSION["last_activity"])) {
    if (time() - $_SESSION["last_activity"] > $timeout) {
        session_unset();
        session_destroy();

        header("Location: login.php?error=timeout");
        exit;
    }
}

$_SESSION["last_activity"] = time();