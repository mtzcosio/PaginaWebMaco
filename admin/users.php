<?php
$page_title = 'Gestionar Usuarios';
require_once 'session_config.php';
require_once 'auth_check.php';
require_once 'csrf_handler.php';
require_once '../backend/db_config.php';

$csrf_token = generate_csrf_token();

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset(DB_CHARSET);

// Obtener todos los usuarios con sus roles
$sql_users = "SELECT u.UserID, u.Username, u.Email, u.IsActive, u.IsLocked, u.LastLogin, GROUP_CONCAT(r.RoleName SEPARATOR ', ') as Roles
              FROM Users u
              LEFT JOIN UserRoles ur ON u.UserID = ur.UserID
              LEFT JOIN Roles r ON ur.RoleID = r.RoleID
              GROUP BY u.UserID
              ORDER BY u.Username ASC";
$users_result = $conn->query($sql_users);
$users = $users_result ? $users_result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener todos los roles activos para los formularios
$roles_result = $conn->query("SELECT RoleID, RoleName FROM Roles WHERE IsActive = 1 ORDER BY RoleName ASC");
$all_roles = $roles_result ? $roles_result->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();

require_once 'header.php';
?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2>Gestión de Usuarios</h2>
            <p>Administra los usuarios, sus roles y estados en el sistema.</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-plus-circle-fill me-2"></i>Añadir Usuario
        </button>
    </div>

    <?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
        <div class="alert alert-<?php echo $_GET['status'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <i class="bi <?php echo $_GET['status'] == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
            <?php echo htmlspecialchars(urldecode($_GET['msg']), ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 fw-bold text-primary">Lista de Usuarios</h6>
            <div class="col-md-4">
                <input type="text" id="userFilter" class="form-control form-control-sm" placeholder="Buscar por nombre, email o rol...">
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Roles</th>
                            <th>Estado</th>
                            <th>Último Login</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <?php if (empty($users)): ?>
                            <tr><td colspan="6" class="text-center">No hay usuarios registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['Username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($user['Email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($user['Roles'] ?? 'Sin rol', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['IsActive'] ? 'success' : 'secondary'; ?>"><?php echo $user['IsActive'] ? 'Activo' : 'Inactivo'; ?></span>
                                        <span class="badge bg-<?php echo $user['IsLocked'] ? 'danger' : 'primary'; ?>"><?php echo $user['IsLocked'] ? 'Bloqueado' : 'Desbloqueado'; ?></span>
                                    </td>
                                    <td><?php echo $user['LastLogin'] ? date('d/m/Y H:i', strtotime($user['LastLogin'])) : 'Nunca'; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning edit-btn" data-bs-toggle="modal" data-bs-target="#editUserModal" data-user='<?php echo htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8'); ?>' title="Editar Usuario"><i class="bi bi-pencil-square"></i></button>
                                        <button type="button" class="btn btn-sm btn-secondary password-btn" data-bs-toggle="modal" data-bs-target="#changePasswordModal" data-id="<?php echo $user['UserID']; ?>" title="Cambiar Contraseña"><i class="bi bi-key-fill"></i></button>
                                        <!-- Add more actions like toggle status if needed -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <tr id="no-users-found" class="d-none"><td colspan="6" class="text-center">No se encontraron usuarios que coincidan con la búsqueda.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Añadir Usuario -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Añadir Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="handle_users.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Nombre de Usuario</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="roles" class="form-label">Roles</label>
                        <select name="roles[]" id="roles" class="form-select" multiple>
                            <?php foreach ($all_roles as $role): ?>
                                <option value="<?php echo $role['RoleID']; ?>"><?php echo htmlspecialchars($role['RoleName'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Mantén presionada la tecla Ctrl (o Cmd en Mac) para seleccionar varios roles.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Usuario -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="handle_users.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_username" class="form-label">Nombre de Usuario</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_email" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_roles" class="form-label">Roles</label>
                        <select name="roles[]" id="edit_roles" class="form-select" multiple>
                            <?php foreach ($all_roles as $role): ?>
                                <option value="<?php echo $role['RoleID']; ?>"><?php echo htmlspecialchars($role['RoleName'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                                <label class="form-check-label" for="edit_is_active">Usuario Activo</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_locked" id="edit_is_locked" value="1">
                                <label class="form-check-label" for="edit_is_locked">Usuario Bloqueado</label>
                            </div>
                        </div>
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

<!-- Modal para Cambiar Contraseña -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel">Cambiar Contraseña</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="handle_users.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="user_id" id="password_user_id">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" id="new_password" name="password" required>
                        <small class="form-text text-muted">La contraseña se actualizará inmediatamente.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar Contraseña</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Script para el modal de edición
    const editUserModal = document.getElementById('editUserModal');
    editUserModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const user = JSON.parse(button.getAttribute('data-user'));
        
        const modal = this;
        modal.querySelector('#edit_user_id').value = user.UserID;
        modal.querySelector('#edit_username').value = user.Username;
        modal.querySelector('#edit_email').value = user.Email;
        modal.querySelector('#edit_is_active').checked = user.IsActive == 1;
        modal.querySelector('#edit_is_locked').checked = user.IsLocked == 1;

        const rolesSelect = modal.querySelector('#edit_roles');
        const assignedRoles = user.Roles ? user.Roles.split(', ') : [];
        Array.from(rolesSelect.options).forEach(option => {
            option.selected = assignedRoles.includes(option.text);
        });
    });

    // Script para el modal de cambio de contraseña
    const changePasswordModal = document.getElementById('changePasswordModal');
    changePasswordModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const userId = button.getAttribute('data-id');
        this.querySelector('#password_user_id').value = userId;
    });

    // Script para el filtro de la tabla
    const filterInput = document.getElementById('userFilter');
    const tableBody = document.getElementById('usersTableBody');
    const userRows = tableBody.querySelectorAll('tr:not(#no-users-found)');
    const noResultsRow = document.getElementById('no-users-found');

    if (filterInput) {
        filterInput.addEventListener('keyup', function() {
            const filterText = this.value.toLowerCase();
            let visibleCount = 0;
            userRows.forEach(function(row) {
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