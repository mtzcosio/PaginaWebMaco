<?php
// admin/csrf_handler.php

/**
 * Manejador de tokens CSRF (Cross-Site Request Forgery).
 * Este script proporciona funciones para generar y verificar tokens de un solo uso
 * que se utilizan en los formularios para proteger contra ataques CSRF.
 * Un token se genera y se almacena en la sesión del usuario. Luego se añade como
 * un campo oculto en cada formulario. Cuando el formulario se envía, el token recibido
 * se compara con el que está en la sesión. Si no coinciden, la solicitud se rechaza.
 */

// Asegura que la sesión esté iniciada antes de cualquier operación.
// `session_status() === PHP_SESSION_NONE` es la forma recomendada de verificar
// si una sesión ya ha sido iniciada, para evitar errores.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Genera un token CSRF seguro y lo almacena en la sesión.
 * Si ya existe un token en la sesión, lo reutiliza para esa página.
 * El token se crea una vez por sesión de usuario para mantener la consistencia
 * en múltiples pestañas del navegador.
 *
 * @return string El token CSRF.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        // `random_bytes(32)` genera 32 bytes criptográficamente seguros.
        // `bin2hex` convierte esos bytes en una cadena hexadecimal, resultando en 64 caracteres.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica si el token proporcionado coincide con el almacenado en la sesión.
 * Utiliza `hash_equals()` para una comparación segura contra ataques de temporización.
 *
 * @param string $token El token recibido del formulario.
 * @return bool True si el token es válido, false en caso contrario.
 */
function verify_csrf_token($token) {
    // `hash_equals` es una función de comparación de cadenas segura que previene
    // ataques de temporización, donde un atacante podría adivinar un token
    // midiendo el tiempo que tarda la comparación.
    if (isset($_SESSION['csrf_token']) && !empty($token) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}
?>