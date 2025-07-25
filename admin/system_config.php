<?php
$page_title = 'Configuración del Sistema';
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

// Obtener y agrupar todas las configuraciones
$configs_result = $conn->query("SELECT * FROM system_configuration ORDER BY config_group, config_key ASC");
$grouped_configs = [];
if ($configs_result) {
    while ($row = $configs_result->fetch_assoc()) {
        $grouped_configs[$row['config_group']][] = $row;
    }
}
$conn->close();

require_once 'header.php';
?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2>Configuración del Sistema</h2>
            <p>Gestiona los parámetros y ajustes generales de la aplicación.</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addConfigModal">
            <i class="bi bi-plus-circle-fill me-2"></i>Añadir Parámetro
        </button>
    </div>

    <?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
        <div class="alert alert-<?php echo $_GET['status'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <i class="bi <?php echo $_GET['status'] == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
            <?php echo htmlspecialchars(urldecode($_GET['msg']), ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="accordion" id="configAccordion">
        <?php if (empty($grouped_configs)): ?>
            <div class="card">
                <div class="card-body text-center">No hay parámetros de configuración definidos.</div>
            </div>
        <?php else: ?>
            <?php foreach ($grouped_configs as $group => $configs): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading-<?php echo htmlspecialchars($group, ENT_QUOTES, 'UTF-8'); ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo htmlspecialchars($group, ENT_QUOTES, 'UTF-8'); ?>" aria-expanded="false" aria-controls="collapse-<?php echo htmlspecialchars($group, ENT_QUOTES, 'UTF-8'); ?>">
                            <strong><?php echo htmlspecialchars(strtoupper($group), ENT_QUOTES, 'UTF-8'); ?></strong>
                        </button>
                    </h2>
                    <div id="collapse-<?php echo htmlspecialchars($group, ENT_QUOTES, 'UTF-8'); ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?php echo htmlspecialchars($group, ENT_QUOTES, 'UTF-8'); ?>" data-bs-parent="#configAccordion">
                        <div class="accordion-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Clave</th>
                                            <th>Valor</th>
                                            <th>Descripción</th>
                                            <th class="text-center">Estado</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($configs as $config): ?>
                                            <tr>
                                                <td class="font-monospace"><?php echo htmlspecialchars($config['config_key'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td>
                                                    <?php if ($config['is_encrypted']): ?>
                                                        <span class="text-muted font-monospace" title="Valor encriptado">********</span>
                                                    <?php else:
                                                        $display_value = htmlspecialchars($config['config_value'], ENT_QUOTES, 'UTF-8');
                                                        $truncated_value = (mb_strlen($display_value) > 50) ? mb_substr($display_value, 0, 50) . '...' : $display_value;
                                                    ?>
                                                        <span class="font-monospace" title="<?php echo $display_value; ?>"><?php echo $truncated_value; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($config['description'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-<?php echo $config['is_active'] ? 'success' : 'secondary'; ?>">
                                                        <?php echo $config['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <?php
                                                        // Prepara los datos para el modal, pero elimina el valor si está encriptado para no exponerlo en el HTML.
                                                        $modal_data = $config;
                                                        if ($modal_data['is_encrypted']) {
                                                            $modal_data['config_value'] = '';
                                                        }
                                                    ?>
                                                    <button type="button" class="btn btn-sm btn-warning edit-btn" data-bs-toggle="modal" data-bs-target="#editConfigModal" data-config='<?php echo htmlspecialchars(json_encode($modal_data), ENT_QUOTES, 'UTF-8'); ?>' title="Editar"><i class="bi bi-pencil-square"></i></button>
                                                    <form action="handle_system_config.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="config_id" value="<?php echo $config['config_id']; ?>">
                                                        <button type="submit" class="btn btn-sm <?php echo $config['is_active'] ? 'btn-secondary' : 'btn-success'; ?>" title="<?php echo $config['is_active'] ? 'Desactivar' : 'Activar'; ?>"><i class="bi <?php echo $config['is_active'] ? 'bi-eye-slash-fill' : 'bi-eye-fill'; ?>"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para Añadir Configuración -->
<div class="modal fade" id="addConfigModal" tabindex="-1" aria-labelledby="addConfigModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="addConfigModalLabel">Añadir Nuevo Parámetro</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form action="handle_system_config.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mb-3"><label for="config_group" class="form-label">Grupo</label><input type="text" class="form-control" id="config_group" name="config_group" placeholder="ej: SMTP, GENERAL" required></div>
                    <div class="mb-3"><label for="config_key" class="form-label">Clave</label><input type="text" class="form-control" id="config_key" name="config_key" placeholder="ej: smtp_host" required></div>
                    <div class="mb-3"><label for="config_value" class="form-label">Valor</label><textarea class="form-control" id="config_value" name="config_value" rows="3" required></textarea></div>
                    <div class="mb-3"><label for="description" class="form-label">Descripción</label><input type="text" class="form-control" id="description" name="description"></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_encrypted" id="is_encrypted" value="1"><label class="form-check-label" for="is_encrypted">Ocultar/Encriptar valor</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar Parámetro</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Configuración -->
<div class="modal fade" id="editConfigModal" tabindex="-1" aria-labelledby="editConfigModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="editConfigModalLabel">Editar Parámetro</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form action="handle_system_config.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="config_id" id="edit_config_id">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mb-3"><label for="edit_config_group" class="form-label">Grupo</label><input type="text" class="form-control" id="edit_config_group" name="config_group" required></div>
                    <div class="mb-3"><label for="edit_config_key" class="form-label">Clave</label><input type="text" class="form-control" id="edit_config_key" name="config_key" required></div>
                    <div class="mb-3">
                        <label for="edit_config_value" class="form-label">Valor</label>
                        <textarea class="form-control" id="edit_config_value" name="config_value" rows="3"></textarea>
                        <small class="form-text text-muted">Si el valor está encriptado, déjalo en blanco para no modificarlo.</small>
                    </div>
                    <div class="mb-3"><label for="edit_description" class="form-label">Descripción</label><input type="text" class="form-control" id="edit_description" name="description"></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_encrypted" id="edit_is_encrypted" value="1"><label class="form-check-label" for="edit_is_encrypted">Ocultar/Encriptar valor</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar Cambios</button></div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editConfigModal = document.getElementById('editConfigModal');
    if (editConfigModal) {
        editConfigModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const config = JSON.parse(button.getAttribute('data-config'));
            
            const modal = this;
            modal.querySelector('#edit_config_id').value = config.config_id;
            modal.querySelector('#edit_config_group').value = config.config_group;
            modal.querySelector('#edit_config_key').value = config.config_key;
            modal.querySelector('#edit_description').value = config.description;
            modal.querySelector('#edit_is_encrypted').checked = config.is_encrypted == 1;

            const valueTextarea = modal.querySelector('#edit_config_value');
            valueTextarea.value = ""; // Limpiar valor anterior

            if (config.is_encrypted == 1) {
                valueTextarea.placeholder = "Cargando valor desencriptado...";
                valueTextarea.disabled = true;

                // Llamada AJAX para obtener y desencriptar el valor de forma segura
                fetch(`get_decrypted_value.php?id=${config.config_id}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error de red o del servidor al obtener el valor.');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            valueTextarea.placeholder = `Error: ${data.error}`;
                        } else {
                            valueTextarea.value = data.value;
                            valueTextarea.placeholder = "Introduce un nuevo valor si deseas cambiarlo.";
                        }
                    })
                    .catch(error => {
                        console.error('Error al obtener el valor:', error);
                        valueTextarea.placeholder = "No se pudo cargar el valor.";
                    })
                    .finally(() => {
                        valueTextarea.disabled = false;
                    });
            } else {
                valueTextarea.placeholder = "";
                valueTextarea.value = config.config_value;
            }
        });
    }
});
</script>