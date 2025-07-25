<?php
require_once 'session_config.php';
require_once 'auth_check.php';
require_once 'csrf_handler.php';
require_once '../backend/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit();
}

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    header('Location: users.php?status=error&msg='.urlencode('Token CSRF inválido.'));
    exit();
}

$action = $_POST['action'] ?? '';
if (empty($action)) {
    header('Location: users.php?status=error&msg='.urlencode('Acción no especificada.'));
    exit();
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("Error de conexión a la BD: " . $conn->connect_error);
    header('Location: users.php?status=error&msg='.urlencode('Error de base de datos.'));
    exit();
}
$conn->set_charset(DB_CHARSET);

$redirect_url = 'users.php';
$status = 'success';
$msg = '';

try {
    $conn->begin_transaction();

    switch ($action) {
        case 'create':
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $roles = $_POST['roles'] ?? [];

            if (empty($username) || empty($email) || empty($password)) {
                throw new Exception('Todos los campos son obligatorios.');
            }

            $password_hash = password_hash($password, PASSWORD_ARGON2ID);

            $stmt = $conn->prepare("INSERT INTO Users (Username, Email, PasswordHash) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $password_hash);
            if (!$stmt->execute()) {
                if ($conn->errno === 1062) throw new Exception('El nombre de usuario o email ya existe.');
                throw new Exception('No se pudo crear el usuario.');
            }
            $user_id = $stmt->insert_id;
            $stmt->close();

            if (!empty($roles)) {
                $stmt_roles = $conn->prepare("INSERT INTO UserRoles (UserID, RoleID) VALUES (?, ?)");
                foreach ($roles as $role_id) {
                    $stmt_roles->bind_param("ii", $user_id, $role_id);
                    $stmt_roles->execute();
                }
                $stmt_roles->close();
            }
            $msg = 'Usuario creado correctamente.';
            break;

        case 'update':
            $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $roles = $_POST['roles'] ?? [];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $is_locked = isset($_POST['is_locked']) ? 1 : 0;

            if (!$user_id || empty($username) || empty($email)) {
                throw new Exception('Datos de usuario inválidos.');
            }

            // Prevenir que el admin principal se desactive o bloquee a sí mismo
            if ($user_id === $_SESSION['user_id'] && (!$is_active || $is_locked)) {
                throw new Exception('No puedes desactivar o bloquear tu propia cuenta.');
            }

            $stmt = $conn->prepare("UPDATE Users SET Username = ?, Email = ?, IsActive = ?, IsLocked = ? WHERE UserID = ?");
            $stmt->bind_param("ssiii", $username, $email, $is_active, $is_locked, $user_id);
            if (!$stmt->execute()) {
                 if ($conn->errno === 1062) throw new Exception('El nombre de usuario o email ya está en uso por otro usuario.');
                throw new Exception('No se pudo actualizar el usuario.');
            }
            $stmt->close();

            // Actualizar roles
            $stmt_del = $conn->prepare("DELETE FROM UserRoles WHERE UserID = ?");
            $stmt_del->bind_param("i", $user_id);
            $stmt_del->execute();
            $stmt_del->close();

            if (!empty($roles)) {
                $stmt_roles = $conn->prepare("INSERT INTO UserRoles (UserID, RoleID) VALUES (?, ?)");
                foreach ($roles as $role_id) {
                    $stmt_roles->bind_param("ii", $user_id, $role_id);
                    $stmt_roles->execute();
                }
                $stmt_roles->close();
            }
            $msg = 'Usuario actualizado correctamente.';
            break;

        case 'change_password':
            $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            $password = $_POST['password'];

            if (!$user_id || empty($password)) {
                throw new Exception('Datos inválidos para cambiar la contraseña.');
            }

            $password_hash = password_hash($password, PASSWORD_ARGON2ID);
            $stmt = $conn->prepare("UPDATE Users SET PasswordHash = ? WHERE UserID = ?");
            $stmt->bind_param("si", $password_hash, $user_id);
            $stmt->execute();
            $stmt->close();
            
            // Opcional: Guardar en historial de contraseñas
            $stmt_hist = $conn->prepare("INSERT INTO PasswordHistory (UserID, PasswordHash, ChangedAt) VALUES (?, ?, NOW())");
            $stmt_hist->bind_param("is", $user_id, $password_hash);
            $stmt_hist->execute();
            $stmt_hist->close();

            $msg = 'Contraseña actualizada correctamente.';
            break;

        default:
            throw new Exception('Acción desconocida.');
    }

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    $status = 'error';
    $msg = $e->getMessage();
} finally {
    $conn->close();
}

header("Location: {$redirect_url}?status={$status}&msg=".urlencode($msg));
exit();