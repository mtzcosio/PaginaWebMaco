<?php
// admin/handle_template_send_test.php
require_once 'session_config.php';
require_once 'auth_check.php';
require_once 'csrf_handler.php';
require_once 'mailer_service.php'; // ¡Importante!

header('Content-Type: application/json');

// 1. Validación de la solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit();
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido.']);
    exit();
}

// 2. Obtener y validar entradas
$template_key = trim($_POST['template_key'] ?? '');
$json_data_string = $_POST['json_data'] ?? '';
$recipient = trim($_POST['recipient'] ?? '');

if (empty($template_key) || empty($json_data_string) || empty($recipient)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Clave, destinatario y datos JSON son obligatorios.']);
    exit();
}

if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'El destinatario no es un correo electrónico válido.']);
    exit();
}

$data = json_decode($json_data_string, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'El formato del JSON no es válido: ' . json_last_error_msg()]);
    exit();
}

// 3. Enviar el correo electrónico
try {
    // El destinatario debe estar en el formato que espera send_email_template
    $to_recipients = [['address' => $recipient]];

    // Llamar a la función del servicio de correo
    $sent = send_email_template(
        $template_key,
        $to_recipients,
        $data
    );

    if ($sent) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Correo de prueba enviado correctamente a ' . htmlspecialchars($recipient, ENT_QUOTES, 'UTF-8') . '. Por favor, revisa la bandeja de entrada.'
        ]);
    } else {
        // send_email_template devuelve false en caso de error y registra los detalles.
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'El servicio de correo no pudo enviar el mensaje. Revisa la configuración SMTP y los logs de error del servidor.'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en handle_template_send_test.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error crítico: ' . $e->getMessage()]);
}

exit();