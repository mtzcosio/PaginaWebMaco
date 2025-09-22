<?php
// admin/handle_login.php 


// Iniciar la sesión al principio de todo. Es crucial para la seguridad y el estado del usuario.
session_start();

// Incluir la configuración de la base de datos
require_once 'csrf_handler.php';
require_once '../backend/db_config.php';

// --- 1. Validación de la Solicitud ---
// Solo permitir solicitudes POST para este script
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    error_log("Invalid CSRF token for login attempt.");
    header("Location: index.php?error=invalid_token");
    exit();
}

// Obtener y sanear las entradas del formulario
$email = $_POST['email'] ?? '';
$input_password = $_POST['password'] ?? '';

// Validar que los campos no estén vacíos
if (empty($email) || empty($input_password)) {
    header("Location: index.php?error=credentials_required");
    exit();
}


// --- 2. Conexión a la Base de Datos ---
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    // En un entorno de producción, es mejor registrar el error que mostrarlo al usuario.
    error_log("Error de conexión a la BD: " . $conn->connect_error);
    header("Location: index.php?error=db_connection_failed");
    exit();
}
$conn->set_charset(DB_CHARSET);


// --- 3. Consulta Segura del Usuario ---
// Consulta para obtener el usuario, sus roles y permisos de una sola vez para mayor eficiencia.
$sql = "SELECT u.UserID, u.Username, u.PasswordHash, u.IsActive, u.IsLocked,
               GROUP_CONCAT(DISTINCT r.RoleName) as Roles,
               GROUP_CONCAT(DISTINCT p.PermissionName) as Permissions
        FROM Users u
        LEFT JOIN UserRoles ur ON u.UserID = ur.UserID
        LEFT JOIN Roles r ON ur.RoleID = r.RoleID
        LEFT JOIN RolePermissions rp ON r.RoleID = rp.RoleID
        LEFT JOIN Permissions p ON rp.PermissionID = p.PermissionID
        WHERE u.Email = ?
        GROUP BY u.UserID";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    error_log("Error al preparar la consulta de login: " . $conn->error);
    header("Location: index.php?error=server_error");
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();


// --- 4. Verificación y Creación de Sesión ---
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Verificar la contraseña y el estado de la cuenta
    if (password_verify($input_password, $user['PasswordHash']) && $user['IsActive'] && !$user['IsLocked']) {
        
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['roles'] = !empty($user['Roles']) ? explode(',', $user['Roles']) : [];
        $_SESSION['permissions'] = !empty($user['Permissions']) ? explode(',', $user['Permissions']) : [];
        $_SESSION['last_activity'] = time();

        $update_stmt = $conn->prepare("UPDATE Users SET LastLogin = NOW(), FailedAttempts = 0 WHERE UserID = ?");
        $update_stmt->bind_param("i", $user['UserID']);
        $update_stmt->execute();
        $update_stmt->close();

        header("Location: dashboard.php");
        exit();
    }
}

$stmt->close();
$conn->close();
header("Location: index.php?error=invalid_credentials");
exit();