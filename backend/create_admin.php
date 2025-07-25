<?php
// backend/create_admin.php
require_once 'db_config.php';

// --- CONFIGURA TU PRIMER ADMIN AQUÍ ---
$admin_username = 'admin';
$admin_email = 'admin@servicios-maco.com';
$admin_password = 'admin123'; // ¡Cámbiala!
// ------------------------------------

// Conexión a la BD
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset(DB_CHARSET);

// Hashear la contraseña de forma segura
$password_hash = password_hash($admin_password, PASSWORD_ARGON2ID);

// 1. Insertar el usuario
$stmt = $conn->prepare("INSERT INTO Users (Username, Email, PasswordHash, IsActive) VALUES (?, ?, ?, 1)");
$stmt->bind_param("sss", $admin_username, $admin_email, $password_hash);

if ($stmt->execute()) {
    $user_id = $stmt->insert_id;
    echo "Usuario admin creado con ID: {$user_id}.<br>";

    // 2. Obtener el ID del rol 'Admin'
    $role_stmt = $conn->prepare("SELECT RoleID FROM Roles WHERE RoleName = 'Admin'");
    $role_stmt->execute();
    $result = $role_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $role = $result->fetch_assoc();
        $role_id = $role['RoleID'];

        // 3. Asignar el rol al usuario
        $user_role_stmt = $conn->prepare("INSERT INTO UserRoles (UserID, RoleID) VALUES (?, ?)");
        $user_role_stmt->bind_param("ii", $user_id, $role_id);
        if ($user_role_stmt->execute()) {
            echo "Rol 'Admin' asignado correctamente al usuario.<br>";
            echo "¡Proceso completado! Ya puedes borrar este archivo.";
        } else {
            echo "Error al asignar el rol: " . $user_role_stmt->error;
        }
        $user_role_stmt->close();
    } else {
        echo "Error: El rol 'Admin' no se encuentra en la base de datos. Asegúrate de haberlo insertado primero.";
    }
    $role_stmt->close();
} else {
    echo "Error al crear el usuario: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
