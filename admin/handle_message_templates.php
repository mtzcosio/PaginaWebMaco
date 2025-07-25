<?php
require_once 'session_config.php';
require_once 'auth_check.php';
require_once 'csrf_handler.php';
require_once '../backend/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: message_templates.php');
    exit();
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    header('Location: message_templates.php?status=error&msg='.urlencode('Token CSRF inválido.'));
    exit();
}

$action = $_POST['action'] ?? '';
if (empty($action)) {
    header('Location: message_templates.php?status=error&msg='.urlencode('Acción no especificada.'));
    exit();
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("Error de conexión a la BD: " . $conn->connect_error);
    header('Location: message_templates.php?status=error&msg='.urlencode('Error de base de datos.'));
    exit();
}
$conn->set_charset(DB_CHARSET);

$redirect_url = 'message_templates.php';
$status = 'success';
$msg = '';

try {
    switch ($action) {
        case 'create':
            $stmt = $conn->prepare("INSERT INTO message_templates (name, template_key, channel, subject, body, is_html, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $name = trim($_POST['name']);
            $key = trim($_POST['template_key']);
            $channel = $_POST['channel'];
            $subject = $channel === 'email' ? trim($_POST['subject']) : null;
            $body = trim($_POST['body']);
            $is_html = isset($_POST['is_html']) ? 1 : 0;
            $desc = trim($_POST['description']);
            $stmt->bind_param("sssssis", $name, $key, $channel, $subject, $body, $is_html, $desc);
            $stmt->execute();
            $msg = 'Plantilla creada correctamente.';
            break;

        case 'update':
            $id = filter_input(INPUT_POST, 'template_id', FILTER_VALIDATE_INT);
            $stmt = $conn->prepare("UPDATE message_templates SET name = ?, template_key = ?, channel = ?, subject = ?, body = ?, is_html = ?, description = ? WHERE template_id = ?");
            $name = trim($_POST['name']);
            $key = trim($_POST['template_key']);
            $channel = $_POST['channel'];
            $subject = $channel === 'email' ? trim($_POST['subject']) : null;
            $body = trim($_POST['body']);
            $is_html = isset($_POST['is_html']) ? 1 : 0;
            $desc = trim($_POST['description']);
            $stmt->bind_param("sssssisi", $name, $key, $channel, $subject, $body, $is_html, $desc, $id);
            $stmt->execute();
            $msg = 'Plantilla actualizada correctamente.';
            break;

        case 'toggle_status':
            $id = filter_input(INPUT_POST, 'template_id', FILTER_VALIDATE_INT);
            $stmt = $conn->prepare("UPDATE message_templates SET is_active = NOT is_active WHERE template_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $msg = 'Estado de la plantilla cambiado correctamente.';
            break;

        default:
            throw new Exception('Acción desconocida.');
    }
} catch (Exception $e) {
    $status = 'error';
    $msg = $conn->errno === 1062 ? 'La clave de plantilla (template_key) ya existe.' : $e->getMessage();
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}

header("Location: {$redirect_url}?status={$status}&msg=".urlencode($msg));
exit();