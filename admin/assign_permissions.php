<?php
$page_title = 'Asignar Permisos a Rol';
require_once 'session_config.php';
require_once 'auth_check.php';
require_once 'csrf_handler.php';
require_once '../backend/db_config.php';

// 1. Validar RoleID de la URL
$role_id = filter_input(INPUT_GET, 'role_id', FILTER_VALIDATE_INT);
if (!$role_id) {
    header('Location: roles.php?status=error&msg='.urlencode('ID de rol no válido.'));
    exit();
}

// Generar token CSRF para el formulario
$csrf_token = generate_csrf_token();

// 2. Conectar a la BD
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset(DB_CHARSET);

// 3. Obtener datos del Rol
$stmt_role = $conn->prepare("SELECT RoleName FROM Roles WHERE RoleID = ?");
$stmt_role->bind_param("i", $role_id);
$stmt_role->execute();
$role_result = $stmt_role->get_result();
if ($role_result->num_rows === 0) {
    header('Location: roles.php?status=error&msg='.urlencode('Rol no encontrado.'));
    exit();
}
$role = $role_result->fetch_assoc();
$stmt_role->close();

// 4. Obtener todos los permisos activos
$all_permissions = [];
$perm_result = $conn->query("SELECT PermissionID, PermissionName, Description FROM Permissions WHERE IsActive = 1 ORDER BY PermissionName ASC");
if ($perm_result) {
    $all_permissions = $perm_result->fetch_all(MYSQLI_ASSOC);
}

// 5. Obtener los permisos ya asignados a este rol
$assigned_permissions = [];
$stmt_assigned = $conn->prepare("SELECT PermissionID FROM RolePermissions WHERE RoleID = ?");
$stmt_assigned->bind_param("i", $role_id);
$stmt_assigned->execute();
$assigned_result = $stmt_assigned->get_result();
if ($assigned_result) {
    while ($row = $assigned_result->fetch_assoc()) {
        $assigned_permissions[] = $row['PermissionID'];
    }
}
$stmt_assigned->close();
$conn->close();

require_once 'header.php';
?>

<div class="container-fluid">
    <div class="page-header">
        <h2>Asignar Permisos al Rol: <span class="text-primary"><?php echo htmlspecialchars($role['RoleName'], ENT_QUOTES, 'UTF-8'); ?></span></h2>
        <p>Selecciona los permisos que deseas asignar a este rol.</p>
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
            <h6 class="m-0 fw-bold text-primary">Lista de Permisos</h6>
            <div class="col-md-5">
                <input type="text" id="permissionFilter" class="form-control form-control-sm" placeholder="Buscar permisos por nombre o descripción...">
            </div>
        </div>
        <div class="card-body" style="max-height: 60vh; overflow-y: auto;">
            <form action="handle_assign_permissions.php" method="POST">
                <input type="hidden" name="role_id" value="<?php echo $role_id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                
                <?php if (!empty($all_permissions)): ?>
                <div class="form-check form-switch mb-3 border-bottom pb-3">
                    <input class="form-check-input" type="checkbox" id="selectAllPermissions">
                    <label class="form-check-label fw-bold" for="selectAllPermissions">Seleccionar / Deseleccionar Todos los Visibles</label>
                </div>
                <?php endif; ?>

                <div class="row" id="permissionsList">
                    <?php if (empty($all_permissions)): ?>
                        <div class="col-12" id="no-permissions-message"><p class="text-center">No hay permisos activos para asignar.</p></div>
                    <?php else: ?>
                        <?php foreach ($all_permissions as $permission): ?>
                            <div class="col-md-4 col-sm-6 mb-3 permission-item">
                                <div class="form-check form-switch form-check-lg">
                                    <input class="form-check-input permission-checkbox" type="checkbox" name="permission_ids[]" value="<?php echo $permission['PermissionID']; ?>" id="perm_<?php echo $permission['PermissionID']; ?>" <?php echo in_array($permission['PermissionID'], $assigned_permissions) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="perm_<?php echo $permission['PermissionID']; ?>">
                                        <strong><?php echo htmlspecialchars($permission['PermissionName'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                        <small class="text-muted fst-italic"><?php echo htmlspecialchars($permission['Description'], ENT_QUOTES, 'UTF-8'); ?></small>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="col-12 d-none" id="no-results-message"><p class="text-center text-muted mt-3">No se encontraron permisos que coincidan con la búsqueda.</p></div>
                </div>
                <hr>
                <div class="d-flex justify-content-end">
                    <a href="roles.php" class="btn btn-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary" <?php if (empty($all_permissions)) echo 'disabled'; ?>>Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterInput = document.getElementById('permissionFilter');
    const permissionItems = document.querySelectorAll('#permissionsList .permission-item');
    const noResultsMessage = document.getElementById('no-results-message');
    const selectAllCheckbox = document.getElementById('selectAllPermissions');

    if (filterInput) {
        filterInput.addEventListener('keyup', function() {
            const filterText = this.value.toLowerCase();
            let visibleCount = 0;

            permissionItems.forEach(function(item) {
                const labelText = item.querySelector('label').textContent.toLowerCase();
                if (labelText.includes(filterText)) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            noResultsMessage.classList.toggle('d-none', visibleCount > 0);
        });
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            permissionItems.forEach(function(item) {
                // Solo afecta a los elementos visibles (no filtrados)
                if (item.style.display !== 'none') {
                    const checkbox = item.querySelector('.permission-checkbox');
                    if (checkbox) {
                        checkbox.checked = selectAllCheckbox.checked;
                    }
                }
            });
        });
    }
});
</script>