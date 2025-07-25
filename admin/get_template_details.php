<?php
// admin/get_template_details.php
require_once 'session_config.php';
require_once 'auth_check.php'; // Asegura que solo usuarios autenticados puedan acceder
require_once '../backend/db_config.php';

// Establecer la cabecera para devolver una respuesta JSON
header('Content-Type: application/json');

// 1. Validar la clave de entrada desde la URL (ej: /get_template_details.php?key=bienvenida_usuario)
$template_key = trim($_GET['key'] ?? '');
if (empty($template_key)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Clave de plantilla no proporcionada.']);
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

// 3. Obtener los detalles de la plantilla
try {
    $stmt = $conn->prepare("SELECT template_id, name, template_key, channel, subject, body, description, is_html, is_active FROM message_templates WHERE template_key = ?");
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }
    $stmt->bind_param("s", $template_key);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404); // Not Found
        echo json_encode(['status' => 'error', 'message' => 'Plantilla no encontrada.']);
        exit();
    }

    $template = $result->fetch_assoc();
    
    // Asegurarse de que los valores booleanos sean correctos en el JSON
    $template['is_html'] = (bool)$template['is_html'];
    $template['is_active'] = (bool)$template['is_active'];

    echo json_encode([
        'status' => 'success',
        'data' => $template
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en get_template_details.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}