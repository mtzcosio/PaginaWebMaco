<?php
// admin/get_config_value.php
require_once 'session_config.php';
require_once 'auth_check.php'; // Asegura que solo usuarios autenticados puedan acceder
require_once 'encryption_handler.php';
require_once '../backend/db_config.php';

// Establecer la cabecera para devolver una respuesta JSON
header('Content-Type: application/json');

// 1. Validar la clave de entrada desde la URL (ej: /get_config_value.php?key=smtp_user)
$config_key = trim($_GET['key'] ?? '');
if (empty($config_key)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Clave de configuración no proporcionada.']);
    exit();
}

// 2. Conectar a la base de datos
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    error_log("Error de conexión a la BD: " . $conn->connect_error);
    echo json_encode(['status' => 'error', 'message' => 'Error de base de datos.']);
    exit();
}
$conn->set_charset(DB_CHARSET);

// 3. Obtener, desencriptar (si es necesario) y devolver el valor
try {
    // Solo se obtienen configuraciones activas
    $stmt = $conn->prepare("SELECT config_key, config_value, description, is_encrypted, is_active, config_group FROM system_configuration WHERE config_key = ? AND is_active = 1");
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }
    $stmt->bind_param("s", $config_key);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404); // Not Found
        echo json_encode(['status' => 'error', 'message' => 'Parámetro de configuración no encontrado o inactivo.']);
        exit();
    }

    $config = $result->fetch_assoc();
    
    // Desencriptar el valor si está marcado como encriptado
    if ($config['is_encrypted']) {
        $decrypted_value = decrypt_value($config['config_value']);
        if ($decrypted_value === false) {
            throw new Exception('Fallo al desencriptar el valor. La clave podría haber cambiado o los datos estar corruptos.');
        }
        $config['config_value'] = $decrypted_value;
    }

    // No queremos exponer la bandera de encriptación en la respuesta final
    unset($config['is_encrypted']);

    echo json_encode([
        'status' => 'success',
        'data' => $config
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en get_config_value.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}