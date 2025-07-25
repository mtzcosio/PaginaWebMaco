<?php
$page_title = 'Dashboard';
require_once 'auth_check.php'; // ¡CRÍTICO! Esta línea debe estar descomentada.
require_once 'header.php'; // O 'partials/header.php' dependiendo de tu estructura final

// Para obtener los contadores, necesitarías una conexión a la BD aquí
require_once '../backend/db_config.php';
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    // Manejar error de conexión sin terminar el script si es posible
    $user_count = 'Error';
    $role_count = 'Error';
} else {
    $conn->set_charset(DB_CHARSET);
    $user_count = $conn->query("SELECT COUNT(*) FROM Users")->fetch_row()[0];
    $role_count = $conn->query("SELECT COUNT(*) FROM Roles")->fetch_row()[0];
    $conn->close();
}
?>

<div class="container-fluid">
    <div class="page-header">
        <h2>Panel de Administración</h2>
    </div>

    <div class="row">
        <!-- Card: Total de Usuarios -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-primary border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total de Usuarios</div>
                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $user_count; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people-fill card-icon text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card: Roles Definidos -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-success border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Roles Definidos</div>
                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $role_count; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-shield-lock-fill card-icon text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card: Actividad Reciente -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-info border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col me-2">
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">Último Login</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">
                                <?php echo isset($_SESSION['last_login']) ? date('d/m/Y H:i', strtotime($_SESSION['last_login'])) : 'N/A'; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-clipboard-data-fill card-icon text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">Próximos Pasos</h6>
                </div>
                <div class="card-body">
                    <p>Bienvenido al panel de administración de MACO. Desde aquí podrás gestionar todos los aspectos de seguridad de la aplicación. Utiliza el menú de la izquierda para navegar entre las diferentes secciones.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'footer.php';
?>