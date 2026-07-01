<?php
// web/src/backend/logout.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Vaciar todas las variables de sesión del servidor
$_SESSION = array();

// 2. Destruir la cookie de sesión en el navegador si existe
if (ini_get("session_use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destruir la sesión físicamente en el servidor
session_destroy();

header("Location: ../index.php");
exit;
?>