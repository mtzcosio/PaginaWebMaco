<?php
// admin/logout.php
session_start();

// Destruir todas las variables de sesión.
$_SESSION = array();

// Si se desea destruir la sesión completamente, borre también la cookie de sesión.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
header("Location: index.php?status=logged_out");
exit();