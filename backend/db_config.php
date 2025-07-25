<?php
// backend/db_config.php

// Se recomienda usar una librería como vlucas/phpdotenv para cargar variables desde un archivo .env en desarrollo.
// En producción, estas variables se configuran directamente en el servidor.

// Obtener credenciales desde variables de entorno con valores por defecto para desarrollo local.
$servername = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: 'admin'; // Contraseña vacía para XAMPP por defecto
$dbname = getenv('DB_NAME') ?: 'serviciosmaco';

// Establecer el charset para la conexión para evitar problemas con caracteres especiales
define('DB_CHARSET', 'utf8mb4');

?>