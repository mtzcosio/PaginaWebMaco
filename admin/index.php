<?php
session_start();
require_once 'csrf_handler.php';
$csrf_token = generate_csrf_token();

// Si el usuario ya está logueado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Mensajes de error basados en el parámetro GET
$error_messages = [
    'credentials_required' => 'Por favor, introduce tu correo y contraseña.',
    'db_connection_failed' => 'Error de conexión. Inténtalo más tarde.',
    'account_locked' => 'Tu cuenta ha sido bloqueada temporalmente por seguridad.',
    'invalid_token' => 'La solicitud no es válida. Por favor, inténtalo de nuevo.',
    'invalid_credentials' => 'Correo o contraseña incorrectos.'
];

$error_message = '';
if (isset($_GET['error']) && array_key_exists($_GET['error'], $error_messages)) {
    $error_message = $error_messages[$_GET['error']];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Back Office MACO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Estilos de Bootstrap para consistencia con la página principal -->
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Estilos personalizados para la página de login -->
    <style>
        :root {
            --accent-color: #ff6403; /* Tomado de mainV1.css */
            --heading-color: #45505b; /* Tomado de mainV1.css */
        }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-color: #f8f9fa;
            font-family: "Roboto", sans-serif;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 2rem;
        }
        .card-title {
            color: var(--heading-color);
            font-weight: 700;
        }
        .btn-primary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        .btn-primary:hover {
            background-color: #e05a02;
            border-color: #d35400;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="text-center"><img src="../assets/img/Logo_SinFondo.png" alt="Logo MACO" class="logo"></div>
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="card-title text-center mb-4">Acceso al Panel</h4>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                <?php endif; ?>
                <form action="handle_login.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mb-3"><label for="email" class="form-label">Correo Electrónico</label><input type="email" class="form-control" id="email" name="email" required autofocus></div>
                    <div class="mb-3"><label for="password" class="form-label">Contraseña</label><input type="password" class="form-control" id="password" name="password" required></div>
                    <div class="d-grid mt-4"><button type="submit" class="btn btn-primary btn-lg">Iniciar Sesión</button></div>
                </form>
            </div>
        </div>
        <div class="text-center mt-3"><a href="../index.html" class="text-muted text-decoration-none" style="font-size: 0.9em;">&larr; Volver a la página principal</a></div>
    </div>
</body>
</html>