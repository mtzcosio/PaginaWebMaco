<?php
// backend/test_conexion.php

// Incluir el archivo de configuración de la base de datos
require_once 'db_config.php';

// Intentar conectar a la base de datos
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    // Si hay un error, preparar un mensaje de fallo
    $status_message = "Error de conexión: " . $conn->connect_error;
    $status_class = "error";
    $status_title = "¡Falló la conexión!";
} else {
    // Si la conexión es exitosa, preparar un mensaje de éxito
    $status_message = "La conexión con la base de datos '" . htmlspecialchars($dbname, ENT_QUOTES, 'UTF-8') . "' se ha establecido correctamente.";
    $status_class = "success";
    $status_title = "¡Conexión Exitosa!";
    // Cerrar la conexión ya que solo era para prueba
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Conexión a Base de Datos</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f5f7;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: #333;
        }
        .container {
            text-align: center;
            padding: 40px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            width: 90%;
            max-width: 500px;
        }
        .status-box { padding: 20px; border-radius: 8px; margin-top: 20px; font-weight: bold; font-size: 1.1em; word-wrap: break-word; }
        .success { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        h1 { color: #45505b; }
        p { margin-top: 15px; font-size: 0.9em; color: #6c757d; }
        code { background-color: #e9ecef; padding: 2px 4px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Prueba de Conexión a la Base de Datos</h1>
        <div class="status-box <?php echo htmlspecialchars($status_class, ENT_QUOTES, 'UTF-8'); ?>">
            <strong><?php echo htmlspecialchars($status_title, ENT_QUOTES, 'UTF-8'); ?></strong>
            <p><?php echo htmlspecialchars($status_message, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <p>Este script intentó conectarse usando las credenciales del archivo <code>backend/db_config.php</code>.</p>
    </div>
</body>
</html>