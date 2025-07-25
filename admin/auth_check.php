<?php
// admin/auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=session_expired');
    exit();
}

// 2. (Opcional) Verificar inactividad de la sesión
$session_timeout = 1800; // 30 minutos
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header('Location: index.php?error=session_expired');
    exit();
}
$_SESSION['last_activity'] = time();

// 3. Funciones para verificar roles y permisos
function has_role($role) {
    return isset($_SESSION['roles']) && in_array($role, $_SESSION['roles']);
}

// 4. Proteger esta sección solo para Admins
if (!has_role('Admin')) {
    http_response_code(403);
    die('Acceso Denegado. No tienes los permisos necesarios para ver esta página.');
}