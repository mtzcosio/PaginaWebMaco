<?php
// admin/session_config.php

// Configura los parámetros de la cookie de sesión para mayor seguridad
session_set_cookie_params([
    'lifetime' => 3600, // 1 hora
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']), // Solo enviar cookie sobre HTTPS
    'httponly' => true,                  // Prevenir acceso a la cookie desde JavaScript
    'samesite' => 'Lax'                  // Mitiga ataques CSRF
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>