<?php
$page_title = 'Gestionar Roles';
require_once 'session_config.php';
require_once 'auth_check.php';
require_once 'csrf_handler.php';
require_once '../backend/db_config.php';

// Generar token CSRF para los formularios
$csrf_token = generate_csrf_token();

// Conexión a la BD para obtener la lista de roles
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    $roles = [];
    $db_error = "Error de conexión a la base de datos.";
} else {
    $conn->set_charset(DB_CHARSET);
    $result = $conn->query("SELECT RoleID, RoleName, Description, CreatedAt, IsActive FROM Roles ORDER BY RoleName ASC");
    $roles = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $conn->close();
}

require_once 'header.php';
?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2>Gestión de Roles</h2>
            <p>Define los roles que los usuarios pueden tener en la aplicación.</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
            <i class="bi bi-plus-circle-fill me-2"></i>Añadir Rol
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
        <!-- Columna para listar roles -->
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-primary">Roles Existentes</h6>
                    <div class="col-md-5">
                        <input type="text" id="roleFilter" class="form-control form-control-sm" placeholder="Buscar por nombre o descripción...">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Nombre del Rol</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                    <th>Fecha de Creación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="rolesTableBody">
                                <?php if (empty($roles)): ?>
                                    <tr><td colspan="5" class="text-center">No hay roles definidos.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($roles as $role): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($role['RoleName'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($role['Description'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $role['IsActive'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $role['IsActive'] ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($role['CreatedAt'])); ?></td>
                                            <td>
                                                <a href="assign_permissions.php?role_id=<?php echo $role['RoleID']; ?>" class="btn btn-sm btn-info" title="Asignar Permisos">
                                                    <i class="bi bi-shield-check"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-warning edit-btn" data-bs-toggle="modal" data-bs-target="#editRoleModal" data-id="<?php echo $role['RoleID']; ?>" data-name="<?php echo htmlspecialchars($role['RoleName'], ENT_QUOTES, 'UTF-8'); ?>" data-description="<?php echo htmlspecialchars($role['Description'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <form action="handle_roles.php" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de que quieres cambiar el estado de este rol?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="role_id" value="<?php echo $role['RoleID']; ?>">
                                                    <button type="submit" class="btn btn-sm <?php echo $role['IsActive'] ? 'btn-danger' : 'btn-success'; ?>" title="<?php echo $role['IsActive'] ? 'Desactivar' : 'Activar'; ?>">
                                                        <i class="bi <?php echo $role['IsActive'] ? 'bi-trash-fill' : 'bi-check-circle-fill'; ?>"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <tr id="no-roles-found" class="d-none"><td colspan="5" class="text-center">No se encontraron roles que coincidan con la búsqueda.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Añadir Rol -->
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addRoleModalLabel">Añadir Nuevo Rol</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="handle_roles.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mb-3">
                        <label for="role_name" class="form-label">Nombre del Rol:</label>
                        <input type="text" class="form-control" id="role_name" name="role_name" placeholder="ej: Editor" required>
                        <small class="form-text text-muted">Debe ser único y descriptivo.</small>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción:</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="ej: Puede editar contenido pero no administrar usuarios."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Añadir Rol</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Rol -->
<div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRoleModalLabel">Editar Rol</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="handle_roles.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="role_id" id="edit_role_id">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mb-3">
                        <label for="edit_role_name" class="form-label">Nombre del Rol:</label>
                        <input type="text" class="form-control" id="edit_role_name" name="role_name" required>
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
    var editRoleModal = document.getElementById('editRoleModal');
    editRoleModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var roleId = button.getAttribute('data-id');
        var roleName = button.getAttribute('data-name');
        var roleDescription = button.getAttribute('data-description');

        var modalBody = editRoleModal.querySelector('.modal-body');
        modalBody.querySelector('#edit_role_id').value = roleId;
        modalBody.querySelector('#edit_role_name').value = roleName;
        modalBody.querySelector('#edit_description').value = roleDescription;
    });

    // Script para el filtro de la tabla
    const filterInput = document.getElementById('roleFilter');
    const tableBody = document.getElementById('rolesTableBody');
    const roleRows = tableBody.querySelectorAll('tr:not(#no-roles-found)');
    const noResultsRow = document.getElementById('no-roles-found');

    if (filterInput) {
        filterInput.addEventListener('keyup', function() {
            const filterText = this.value.toLowerCase();
            let visibleCount = 0;

            roleRows.forEach(function(row) {
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