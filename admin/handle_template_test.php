<?php
// admin/handle_template_test.php
require_once 'session_config.php';
require_once 'auth_check.php';
require_once 'csrf_handler.php';
require_once '../backend/db_config.php';

// Establecer la cabecera para devolver una respuesta JSON
header('Content-Type: application/json');

// --- 1. Validación Básica ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit();
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido.']);
    exit();
}

// --- 2. Obtener y Validar Entradas ---
$template_key = trim($_POST['template_key'] ?? '');
$json_data_string = $_POST['json_data'] ?? '';
$recipient = trim($_POST['recipient'] ?? '');

if (empty($template_key) || empty($json_data_string) || empty($recipient)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
    exit();
}

$data = json_decode($json_data_string, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'El formato del JSON no es válido. Error: ' . json_last_error_msg()]);
    exit();
}

// --- 3. Base de Datos y Procesamiento de Plantilla ---
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    error_log("Error de conexión a la BD: " . $conn->connect_error);
    echo json_encode(['status' => 'error', 'message' => 'Error de base de datos.']);
    exit();
}
$conn->set_charset(DB_CHARSET);

try {
    // Obtener la plantilla de la base de datos
    $stmt = $conn->prepare("SELECT subject, body, is_html FROM message_templates WHERE template_key = ? AND is_active = 1");
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }
    $stmt->bind_param("s", $template_key);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Plantilla no encontrada o inactiva.']);
        exit();
    }

    $template = $result->fetch_assoc();
    $processed_subject = $template['subject'];
    $processed_body = $template['body'];

    // Reemplazar placeholders
    foreach ($data as $key => $value) {
        $placeholder = '{{' . $key . '}}';
        if (is_scalar($value)) { // Solo reemplazar con valores escalares (string, int, etc.)
            $processed_subject = str_replace($placeholder, htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $processed_subject);
            $processed_body = str_replace($placeholder, $value, $processed_body);
        }
    }

    // En una aplicación real, aquí se llamaría a una función para enviar el email/SMS.
    // Por ahora, solo devolvemos el contenido procesado.

    echo json_encode([
        'status' => 'success',
        'message' => 'Plantilla procesada correctamente.',
        'processed_subject' => $processed_subject,
        'processed_body' => $processed_body,
        'is_html' => (bool)$template['is_html']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en handle_template_test.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}