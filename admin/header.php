<?php
// admin/partials/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') : 'Panel de Administración'; ?> - MACO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Estilos de Bootstrap y Vendor -->
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Estilos personalizados para el panel -->
    <style>
        :root {
            --accent-color: #ff6403;
            --heading-color: #45505b;
            --sidebar-bg: #212529;
            --sidebar-link-color: #c2c7d0;
            --sidebar-link-hover-bg: #495057;
            --sidebar-link-active-bg: var(--accent-color);
        }
        body {
            background-color: #f4f6f9;
            display: flex;
        }
        #sidebar {
            min-width: 250px;
            max-width: 250px;
            background: var(--sidebar-bg);
            color: #fff;
            transition: all 0.3s;
            min-height: 100vh;
        }
        #sidebar .sidebar-header {
            padding: 20px;
            background: #1a1d20;
            text-align: center;
        }
        #sidebar .sidebar-header img {
            max-width: 100px;
        }
        #sidebar ul.components {
            padding: 20px 0;
        }
        #sidebar ul li a {
            padding: 12px 20px;
            font-size: 1em;
            display: block;
            color: var(--sidebar-link-color);
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        #sidebar ul li a:hover {
            color: #fff;
            background: var(--sidebar-link-hover-bg);
            border-left-color: var(--accent-color);
        }
        #sidebar ul li.active > a, a[aria-expanded="true"] {
            color: #fff;
            background: var(--sidebar-link-active-bg);
        }
        #sidebar ul ul a {
            font-size: 0.9em !important;
            padding-left: 30px !important;
            background: var(--sidebar-link-hover-bg);
            border-left: 3px solid transparent; /* Ocultar borde para sub-items */
        }

        #sidebar ul li a i {
            margin-right: 10px;
        }
        #content {
            width: 100%;
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s;
        }
        .page-header {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        .card-icon {
            font-size: 2.5rem;
            opacity: 0.3;
        }
        #sidebar-toggler {
            display: none; /* Oculto por defecto en pantallas grandes */
        }

        /* Estilos para pantallas pequeñas (móviles y tablets) */
        @media (max-width: 991.98px) {
            body {
                overflow-x: hidden;
            }
            #sidebar {
                position: fixed; /* Fijar la barra lateral */
                z-index: 1000; /* Asegurar que esté por encima del contenido */
                margin-left: -250px; /* Ocultar fuera de la pantalla */
            }
            #sidebar.active {
                margin-left: 0; /* Mostrar al activar */
            }
            #content {
                width: 100%;
                padding: 20px;
            }
            #sidebar-toggler {
                display: block; /* Mostrar el botón de hamburguesa */
            }
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
                z-index: 999; /* Detrás de la barra lateral pero sobre el contenido */
                display: none;
            }
            body.sidebar-active .sidebar-overlay {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php"><img src="../assets/img/Logo_SinFondo.png" alt="Logo MACO"></a>
        </div>

        <ul class="list-unstyled components">
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            </li>
            <li class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['users.php']) ? 'active' : ''; ?>">
                <a href="users.php"><i class="bi bi-people"></i> Usuarios</a>
            </li>
            <li class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['permissions.php', 'roles.php', 'assign_permissions.php']) ? 'active' : ''; ?>">
                <a href="#securitySubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['permissions.php', 'roles.php', 'assign_permissions.php']) ? 'true' : 'false'; ?>" class="dropdown-toggle"><i class="bi bi-shield-lock"></i> Seguridad</a>
                <ul class="collapse list-unstyled <?php echo in_array(basename($_SERVER['PHP_SELF']), ['permissions.php', 'roles.php', 'assign_permissions.php']) ? 'show' : ''; ?>" id="securitySubmenu">
                    <li><a href="permissions.php">Permisos</a></li>
                    <li><a href="roles.php">Roles</a></li>
                </ul>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'message_templates.php' ? 'active' : ''; ?>">
                <a href="message_templates.php"><i class="bi bi-envelope-paper"></i> Plantillas</a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'system_config.php' ? 'active' : ''; ?>">
                <a href="system_config.php"><i class="bi bi-gear-wide-connected"></i> Configuración</a>
            </li>
            <li><a href="#"><i class="bi bi-journal-text"></i> Logs del Sistema</a></li>
        </ul>
        <ul class="list-unstyled mt-auto p-3">
             <li><a href="logout.php" class="btn btn-dark w-100"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
        </ul>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <div class="sidebar-overlay"></div>
        <nav class="navbar navbar-expand-lg navbar-light bg-white mb-4 shadow-sm rounded">
            <div class="container-fluid">
                <button type="button" id="sidebar-toggler" class="btn btn-outline-secondary me-3">
                    <i class="bi bi-list"></i>
                </button>
                <span class="navbar-brand mb-0 h1">Bienvenido, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </nav>