<?php
// admin/handle_permissions.php

require_once 'session_config.php';
require_once 'auth_check.php';
require_once 'csrf_handler.php';
require_once '../backend/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: permissions.php');
    exit();
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    header('Location: permissions.php?status=error&msg=Token CSRF inválido.');
    exit();
}

$action = $_POST['action'] ?? '';
if (empty($action)) {
    header('Location: permissions.php?status=error&msg=Acción no especificada.');
    exit();
}

// 4. Conectar a la BD e insertar el nuevo permiso
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("Error de conexión a la BD: " . $conn->connect_error);
    header('Location: permissions.php?status=error&msg=Error de base de datos.');
    exit();
}
$conn->set_charset(DB_CHARSET);

switch ($action) {
    case 'create':
        $permission_name = trim($_POST['permission_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if (empty($permission_name)) {
            header('Location: permissions.php?status=error&msg=El nombre del permiso es obligatorio.');
            exit();
        }
        $stmt = $conn->prepare("INSERT INTO Permissions (PermissionName, Description) VALUES (?, ?)");
        $stmt->bind_param("ss", $permission_name, $description);
        break;

    case 'update':
        $permission_id = filter_input(INPUT_POST, 'permission_id', FILTER_VALIDATE_INT);
        $permission_name = trim($_POST['permission_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if (!$permission_id || empty($permission_name)) {
            header('Location: permissions.php?status=error&msg=Datos inválidos para actualizar.');
            exit();
        }
        $stmt = $conn->prepare("UPDATE Permissions SET PermissionName = ?, Description = ? WHERE PermissionID = ?");
        $stmt->bind_param("ssi", $permission_name, $description, $permission_id);
        break;

    case 'toggle_status':
        $permission_id = filter_input(INPUT_POST, 'permission_id', FILTER_VALIDATE_INT);
        if (!$permission_id) {
            header('Location: permissions.php?status=error&msg=ID de permiso inválido.');
            exit();
        }
        $stmt = $conn->prepare("UPDATE Permissions SET IsActive = NOT IsActive WHERE PermissionID = ?");
        $stmt->bind_param("i", $permission_id);
        break;

    default:
        header('Location: permissions.php?status=error&msg=Acción desconocida.');
        exit();
}

if ($stmt->execute()) {
    $success_messages = [
        'create' => 'Permiso añadido correctamente.',
        'update' => 'Permiso actualizado correctamente.',
        'toggle_status' => 'Estado del permiso cambiado correctamente.'
    ];
    $msg = urlencode($success_messages[$action] ?? 'Operación exitosa.');
    header("Location: permissions.php?status=success&msg={$msg}");
} else {
    // Error 1062 indica una entrada duplicada (UNIQUE constraint)
    if ($conn->errno === 1062) {
        $error_msg = urlencode('El nombre del permiso ya existe.');
        header("Location: permissions.php?status=error&msg={$error_msg}");
    } else {
        error_log("Error en la ejecución de la consulta: " . $stmt->error);
        $error_msg = urlencode('No se pudo completar la operación.');
        header("Location: permissions.php?status=error&msg={$error_msg}");
    }
}

$stmt->close();
$conn->close();
exit();