<?php
require_once 'session_config.php';
require_once 'auth_check.php'; // Asegura que solo administradores autenticados puedan acceder
require_once 'encryption_handler.php';
require_once '../backend/db_config.php';

// Establecer la cabecera para devolver una respuesta JSON
header('Content-Type: application/json');

// 1. Validar el ID de entrada
$config_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$config_id) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'ID de configuración no válido.']);
    exit();
}

// 2. Conectar a la base de datos
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    error_log("Error de conexión a la BD: " . $conn->connect_error);
    echo json_encode(['error' => 'Error de base de datos.']);
    exit();
}
$conn->set_charset(DB_CHARSET);

// 3. Obtener, desencriptar y devolver el valor
try {
    $stmt = $conn->prepare("SELECT config_value, is_encrypted FROM system_configuration WHERE config_id = ?");
    $stmt->bind_param("i", $config_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'Parámetro no encontrado.']);
        exit();
    }

    $config = $result->fetch_assoc();
    $decrypted_value = '';

    if ($config['is_encrypted']) {
        $decrypted_value = decrypt_value($config['config_value']);
        if ($decrypted_value === false) {
            throw new Exception('Fallo al desencriptar el valor. La clave podría haber cambiado o los datos estar corruptos.');
        }
    } else {
        $decrypted_value = $config['config_value']; // No está encriptado, devolver tal cual
    }

    echo json_encode(['value' => $decrypted_value]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error al desencriptar: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}