<?php
// admin/handle_assign_permissions.php

require_once 'session_config.php';
require_once 'auth_check.php';
require_once 'csrf_handler.php';
require_once '../backend/db_config.php';

// 1. Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: roles.php');
    exit();
}

// 2. Verificar el token CSRF
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    header('Location: roles.php?status=error&msg='.urlencode('Token CSRF inválido.'));
    exit();
}

// 3. Validar RoleID y permisos
$role_id = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
$permission_ids = $_POST['permission_ids'] ?? [];

if (!$role_id) {
    header('Location: roles.php?status=error&msg='.urlencode('ID de rol inválido.'));
    exit();
}

// 4. Conectar a la BD y realizar la transacción
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("Error de conexión a la BD: " . $conn->connect_error);
    header('Location: assign_permissions.php?role_id='.$role_id.'&status=error&msg='.urlencode('Error de base de datos.'));
    exit();
}
$conn->set_charset(DB_CHARSET);

// Iniciar transacción para garantizar la integridad de los datos
$conn->begin_transaction();

try {
    // Primero, eliminar todos los permisos existentes para este rol
    $stmt_delete = $conn->prepare("DELETE FROM RolePermissions WHERE RoleID = ?");
    $stmt_delete->bind_param("i", $role_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    // Luego, insertar los nuevos permisos seleccionados
    if (!empty($permission_ids)) {
        $stmt_insert = $conn->prepare("INSERT INTO RolePermissions (RoleID, PermissionID) VALUES (?, ?)");
        foreach ($permission_ids as $permission_id) {
            $pid = filter_var($permission_id, FILTER_VALIDATE_INT);
            if ($pid) {
                $stmt_insert->bind_param("ii", $role_id, $pid);
                $stmt_insert->execute();
            }
        }
        $stmt_insert->close();
    }

    $conn->commit();
    header("Location: assign_permissions.php?role_id={$role_id}&status=success&msg=".urlencode('Permisos actualizados correctamente.'));
} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    error_log("Error en la transacción de asignación de permisos: " . $exception->getMessage());
    header("Location: assign_permissions.php?role_id={$role_id}&status=error&msg=".urlencode('No se pudieron actualizar los permisos.'));
} finally {
    $conn->close();
}

exit();