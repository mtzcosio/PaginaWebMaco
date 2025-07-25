<?php
$page_title = 'Gestionar Permisos';
require_once 'session_config.php';
require_once 'auth_check.php';
require_once 'csrf_handler.php';
require_once '../backend/db_config.php';

// Generar token CSRF para el formulario
$csrf_token = generate_csrf_token();

// Conexión a la BD para obtener la lista de permisos
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    // En un caso real, registrar el error y mostrar un mensaje amigable
    $permissions = [];
    $db_error = "Error de conexión a la base de datos.";
} else {
    $conn->set_charset(DB_CHARSET);
    $result = $conn->query("SELECT PermissionID, PermissionName, Description, CreatedAt, IsActive FROM Permissions ORDER BY PermissionName ASC");
    $permissions = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $conn->close();
}

require_once 'header.php';
?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2>Gestión de Permisos</h2>
            <p>Aquí puedes ver, agregar y administrar los permisos de la aplicación.</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPermissionModal">
            <i class="bi bi-plus-circle-fill me-2"></i>Añadir Permiso
        </button>
    </div>

    <?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
        <div class="alert alert-<?php echo $_GET['status'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <i class="bi <?php echo $_GET['status'] == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
            <?php echo htmlspecialchars(urldecode($_GET['msg']), ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Columna para listar permisos -->
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-primary">Permisos Existentes</h6>
                    <div class="col-md-5">
                        <input type="text" id="permissionFilter" class="form-control form-control-sm" placeholder="Buscar por nombre o descripción...">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Nombre del Permiso</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                    <th>Fecha de Creación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="permissionsTableBody">
                                <?php if (empty($permissions)): ?>
                                    <tr><td colspan="5" class="text-center">No hay permisos definidos.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($permissions as $perm): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($perm['PermissionName'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($perm['Description'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $perm['IsActive'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $perm['IsActive'] ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($perm['CreatedAt'])); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-warning edit-btn" data-bs-toggle="modal" data-bs-target="#editPermissionModal" data-id="<?php echo $perm['PermissionID']; ?>" data-name="<?php echo htmlspecialchars($perm['PermissionName'], ENT_QUOTES, 'UTF-8'); ?>" data-description="<?php echo htmlspecialchars($perm['Description'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <form action="handle_permissions.php" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de que quieres cambiar el estado de este permiso?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="permission_id" value="<?php echo $perm['PermissionID']; ?>">
                                                    <button type="submit" class="btn btn-sm <?php echo $perm['IsActive'] ? 'btn-danger' : 'btn-success'; ?>" title="<?php echo $perm['IsActive'] ? 'Desactivar' : 'Activar'; ?>">
                                                        <i class="bi <?php echo $perm['IsActive'] ? 'bi-trash-fill' : 'bi-check-circle-fill'; ?>"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <tr id="no-permissions-found" class="d-none"><td colspan="5" class="text-center">No se encontraron permisos que coincidan con la búsqueda.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Añadir Permiso -->
<div class="modal fade" id="addPermissionModal" tabindex="-1" aria-labelledby="addPermissionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPermissionModalLabel">Añadir Nuevo Permiso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="handle_permissions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mb-3">
                        <label for="permission_name" class="form-label">Nombre del Permiso:</label>
                        <input type="text" class="form-control" id="permission_name" name="permission_name" placeholder="ej: manage_products" required>
                        <small class="form-text text-muted">Debe ser único y en formato `accion_recurso`.</small>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción:</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="ej: Permite crear, editar y eliminar productos."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Añadir Permiso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Permiso -->
<div class="modal fade" id="editPermissionModal" tabindex="-1" aria-labelledby="editPermissionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPermissionModalLabel">Editar Permiso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="handle_permissions.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="permission_id" id="edit_permission_id">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mb-3">
                        <label for="edit_permission_name" class="form-label">Nombre del Permiso:</label>
                        <input type="text" class="form-control" id="edit_permission_name" name="permission_name" required>
                        <small class="form-text text-muted">Debe ser único y en formato `accion_recurso`.</small>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Descripción:</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Script para el modal de edición
    var editPermissionModal = document.getElementById('editPermissionModal');
    editPermissionModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var permissionId = button.getAttribute('data-id');
        var permissionName = button.getAttribute('data-name');
        var permissionDescription = button.getAttribute('data-description');

        var modalBody = editPermissionModal.querySelector('.modal-body');
        modalBody.querySelector('#edit_permission_id').value = permissionId;
        modalBody.querySelector('#edit_permission_name').value = permissionName;
        modalBody.querySelector('#edit_description').value = permissionDescription;
    });

    // Script para el filtro de la tabla
    const filterInput = document.getElementById('permissionFilter');
    const tableBody = document.getElementById('permissionsTableBody');
    const permissionRows = tableBody.querySelectorAll('tr:not(#no-permissions-found)');
    const noResultsRow = document.getElementById('no-permissions-found');

    if (filterInput) {
        filterInput.addEventListener('keyup', function() {
            const filterText = this.value.toLowerCase();
            let visibleCount = 0;

            permissionRows.forEach(function(row) {
                const rowText = row.textContent.toLowerCase();
                if (rowText.includes(filterText)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            noResultsRow.classList.toggle('d-none', visibleCount > 0);
        });
    }
});
</script>