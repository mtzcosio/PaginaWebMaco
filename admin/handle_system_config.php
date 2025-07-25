<?php
require_once 'session_config.php';
require_once 'auth_check.php';
require_once 'csrf_handler.php';
require_once 'encryption_handler.php'; // Incluir el manejador de encriptación
require_once '../backend/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: system_config.php');
    exit();
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    header('Location: system_config.php?status=error&msg='.urlencode('Token CSRF inválido.'));
    exit();
}

$action = $_POST['action'] ?? '';
if (empty($action)) {
    header('Location: system_config.php?status=error&msg='.urlencode('Acción no especificada.'));
    exit();
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("Error de conexión a la BD: " . $conn->connect_error);
    header('Location: system_config.php?status=error&msg='.urlencode('Error de base de datos.'));
    exit();
}
$conn->set_charset(DB_CHARSET);

$redirect_url = 'system_config.php';
$status = 'success';
$msg = '';

try {
    switch ($action) {
        case 'create':
            $group = trim($_POST['config_group']);
            $key = trim($_POST['config_key']);
            $value = trim($_POST['config_value']);
            $desc = trim($_POST['description']);
            $is_encrypted = isset($_POST['is_encrypted']) ? 1 : 0;

            // Encriptar el valor si se ha marcado la opción
            if ($is_encrypted && !empty($value)) {
                $value = encrypt_value($value);
            }

            $stmt = $conn->prepare("INSERT INTO system_configuration (config_group, config_key, config_value, description, is_encrypted) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $group, $key, $value, $desc, $is_encrypted);
            $stmt->execute();
            $msg = 'Parámetro creado correctamente.';
            break;

        case 'update':
            $id = filter_input(INPUT_POST, 'config_id', FILTER_VALIDATE_INT);
            $group = trim($_POST['config_group']);
            $key = trim($_POST['config_key']);
            $value = trim($_POST['config_value']);
            $desc = trim($_POST['description']);
            $is_encrypted = isset($_POST['is_encrypted']) ? 1 : 0;

            // Encriptar el nuevo valor si se proporciona y se marca la opción
            if ($is_encrypted && !empty($value)) {
                $value = encrypt_value($value);
            }

            if (empty($value)) { // Si el valor viene vacío, no se actualiza (para no sobreescribir valores encriptados)
                $stmt = $conn->prepare("UPDATE system_configuration SET config_group = ?, config_key = ?, description = ?, is_encrypted = ? WHERE config_id = ?");
                $stmt->bind_param("sssii", $group, $key, $desc, $is_encrypted, $id);
            } else {
                $stmt = $conn->prepare("UPDATE system_configuration SET config_group = ?, config_key = ?, config_value = ?, description = ?, is_encrypted = ? WHERE config_id = ?");
                $stmt->bind_param("ssssii", $group, $key, $value, $desc, $is_encrypted, $id);
            }
            $stmt->execute();
            $msg = 'Parámetro actualizado correctamente.';
            break;

        case 'toggle_status':
            $id = filter_input(INPUT_POST, 'config_id', FILTER_VALIDATE_INT);
            $stmt = $conn->prepare("UPDATE system_configuration SET is_active = NOT is_active WHERE config_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $msg = 'Estado del parámetro cambiado correctamente.';
            break;

        default:
            throw new Exception('Acción desconocida.');
    }
} catch (Exception $e) {
    $status = 'error';
    $msg = $conn->errno === 1062 ? 'La combinación de grupo y clave ya existe.' : $e->getMessage();
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}

header("Location: {$redirect_url}?status={$status}&msg=".urlencode($msg));
exit();