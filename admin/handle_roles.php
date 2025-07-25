<?php
// admin/handle_roles.php

require_once 'session_config.php';
require_once 'auth_check.php';
require_once 'csrf_handler.php';
require_once '../backend/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: roles.php');
    exit();
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    header('Location: roles.php?status=error&msg='.urlencode('Token CSRF inválido.'));
    exit();
}

$action = $_POST['action'] ?? '';
if (empty($action)) {
    header('Location: roles.php?status=error&msg='.urlencode('Acción no especificada.'));
    exit();
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("Error de conexión a la BD: " . $conn->connect_error);
    header('Location: roles.php?status=error&msg='.urlencode('Error de base de datos.'));
    exit();
}
$conn->set_charset(DB_CHARSET);

switch ($action) {
    case 'create':
        $role_name = trim($_POST['role_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if (empty($role_name)) {
            header('Location: roles.php?status=error&msg='.urlencode('El nombre del rol es obligatorio.'));
            exit();
        }
        $stmt = $conn->prepare("INSERT INTO Roles (RoleName, Description) VALUES (?, ?)");
        $stmt->bind_param("ss", $role_name, $description);
        break;

    case 'update':
        $role_id = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
        $role_name = trim($_POST['role_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if (!$role_id || empty($role_name)) {
            header('Location: roles.php?status=error&msg='.urlencode('Datos inválidos para actualizar.'));
            exit();
        }
        $stmt = $conn->prepare("UPDATE Roles SET RoleName = ?, Description = ? WHERE RoleID = ?");
        $stmt->bind_param("ssi", $role_name, $description, $role_id);
        break;

    case 'toggle_status':
        $role_id = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
        if (!$role_id) {
            header('Location: roles.php?status=error&msg='.urlencode('ID de rol inválido.'));
            exit();
        }
        // Prevenir la desactivación del rol 'Admin'
        $check_stmt = $conn->prepare("SELECT RoleName FROM Roles WHERE RoleID = ?");
        $check_stmt->bind_param("i", $role_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();
        if ($check_result && $check_result['RoleName'] === 'Admin') {
             header('Location: roles.php?status=error&msg='.urlencode('No se puede desactivar el rol de Administrador.'));
             exit();
        }
        $stmt = $conn->prepare("UPDATE Roles SET IsActive = NOT IsActive WHERE RoleID = ?");
        $stmt->bind_param("i", $role_id);
        break;

    default:
        header('Location: roles.php?status=error&msg='.urlencode('Acción desconocida.'));
        exit();
}

if ($stmt->execute()) {
    $success_messages = ['create' => 'Rol añadido correctamente.','update' => 'Rol actualizado correctamente.','toggle_status' => 'Estado del rol cambiado correctamente.'];
    $msg = urlencode($success_messages[$action] ?? 'Operación exitosa.');
    header("Location: roles.php?status=success&msg={$msg}");
} else {
    if ($conn->errno === 1062) {
        header('Location: roles.php?status=error&msg='.urlencode('El nombre del rol ya existe.'));
    } else {
        error_log("Error en la ejecución de la consulta de roles: " . $stmt->error);
        header('Location: roles.php?status=error&msg='.urlencode('No se pudo completar la operación.'));
    }
}

$stmt->close();
$conn->close();
exit();