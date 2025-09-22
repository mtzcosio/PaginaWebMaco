<?php
$page_title = 'Plantillas de Mensajes';
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

$templates_result = $conn->query("SELECT * FROM message_templates ORDER BY name ASC");
$templates = $templates_result ? $templates_result->fetch_all(MYSQLI_ASSOC) : [];
$conn->close();

require_once 'header.php';
?>

<div class="container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2>Plantillas de Mensajes</h2>
            <p>Gestiona las plantillas para correos, SMS y WhatsApp.</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
            <i class="bi bi-plus-circle-fill me-2"></i>Añadir Plantilla
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
            <h6 class="m-0 fw-bold text-primary">Plantillas Existentes</h6>
            <div class="col-md-4">
                <input type="text" id="templateFilter" class="form-control form-control-sm" placeholder="Buscar por nombre, clave o canal...">
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Clave Única</th>
                            <th>Canal</th>
                            <th>Asunto</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="templatesTableBody">
                        <?php if (empty($templates)): ?>
                            <tr><td colspan="6" class="text-center">No hay plantillas definidas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($templates as $template): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($template['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="font-monospace"><?php echo htmlspecialchars($template['template_key'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if ($template['channel'] == 'email'): ?><i class="bi bi-envelope-fill text-primary"></i>
                                        <?php elseif ($template['channel'] == 'sms'): ?><i class="bi bi-chat-text-fill text-info"></i>
                                        <?php elseif ($template['channel'] == 'whatsapp'): ?><i class="bi bi-whatsapp text-success"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars(ucfirst($template['channel']), ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($template['subject'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo $template['is_active'] ? 'success' : 'secondary'; ?>"><?php echo $template['is_active'] ? 'Activa' : 'Inactiva'; ?></span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-success test-btn" data-bs-toggle="modal" data-bs-target="#testTemplateModal" data-template='<?php echo htmlspecialchars(json_encode($template), ENT_QUOTES, 'UTF-8'); ?>' title="Probar Plantilla"><i class="bi bi-send"></i></button>
                                        <button type="button" class="btn btn-sm btn-info preview-btn" data-bs-toggle="modal" data-bs-target="#previewTemplateModal" data-template='<?php echo htmlspecialchars(json_encode($template), ENT_QUOTES, 'UTF-8'); ?>' title="Vista Previa"><i class="bi bi-eye"></i></button>
                                        <button type="button" class="btn btn-sm btn-warning edit-btn" data-bs-toggle="modal" data-bs-target="#editTemplateModal" data-template='<?php echo htmlspecialchars(json_encode($template), ENT_QUOTES, 'UTF-8'); ?>' title="Editar"><i class="bi bi-pencil-square"></i></button>
                                        <form action="handle_message_templates.php" method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="template_id" value="<?php echo $template['template_id']; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $template['is_active'] ? 'btn-secondary' : 'btn-success'; ?>" title="<?php echo $template['is_active'] ? 'Desactivar' : 'Activar'; ?>"><i class="bi <?php echo $template['is_active'] ? 'bi-eye-slash-fill' : 'bi-eye-fill'; ?>"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <tr id="no-templates-found" class="d-none"><td colspan="6" class="text-center">No se encontraron plantillas que coincidan con la búsqueda.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Añadir Plantilla -->
<div class="modal fade" id="addTemplateModal" tabindex="-1" aria-labelledby="addTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="addTemplateModalLabel">Añadir Nueva Plantilla</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form action="handle_message_templates.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="name" class="form-label">Nombre</label><input type="text" class="form-control" name="name" required></div>
                        <div class="col-md-6 mb-3"><label for="template_key" class="form-label">Clave Única</label><input type="text" class="form-control" name="template_key" placeholder="ej: bienvenida_usuario" required></div>
                    </div>
                    <div class="mb-3"><label for="channel" class="form-label">Canal</label><select class="form-select modal-channel-select" name="channel" required><option value="email">Email</option><option value="sms">SMS</option><option value="whatsapp">WhatsApp</option></select></div>
                    <div class="mb-3 modal-subject-field"><label for="subject" class="form-label">Asunto (para Email)</label><input type="text" class="form-control" name="subject"></div>
                    <div class="mb-3"><label for="body" class="form-label">Cuerpo del Mensaje</label><textarea class="form-control" name="body" rows="6" required></textarea><small class="form-text text-muted">Puedes usar placeholders como `{{nombre_usuario}}` o `{{enlace_factura}}`.</small></div>
                    <div class="mb-3"><label for="description" class="form-label">Descripción Interna</label><textarea class="form-control" name="description" rows="2"></textarea></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_html" value="1"><label class="form-check-label" for="is_html">El cuerpo del mensaje es HTML</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar Plantilla</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Plantilla -->
<div class="modal fade" id="editTemplateModal" tabindex="-1" aria-labelledby="editTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="editTemplateModalLabel">Editar Plantilla</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form action="handle_message_templates.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="template_id" id="edit_template_id">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="edit_name" class="form-label">Nombre</label><input type="text" class="form-control" id="edit_name" name="name" required></div>
                        <div class="col-md-6 mb-3"><label for="edit_template_key" class="form-label">Clave Única</label><input type="text" class="form-control" id="edit_template_key" name="template_key" required></div>
                    </div>
                    <div class="mb-3"><label for="edit_channel" class="form-label">Canal</label><select class="form-select modal-channel-select" id="edit_channel" name="channel" required><option value="email">Email</option><option value="sms">SMS</option><option value="whatsapp">WhatsApp</option></select></div>
                    <div class="mb-3 modal-subject-field"><label for="edit_subject" class="form-label">Asunto (para Email)</label><input type="text" class="form-control" id="edit_subject" name="subject"></div>
                    <div class="mb-3"><label for="edit_body" class="form-label">Cuerpo del Mensaje</label><textarea class="form-control" id="edit_body" name="body" rows="6" required></textarea></div>
                    <div class="mb-3"><label for="edit_description" class="form-label">Descripción Interna</label><textarea class="form-control" id="edit_description" name="description" rows="2"></textarea></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_html" id="edit_is_html" value="1"><label class="form-check-label" for="edit_is_html">El cuerpo del mensaje es HTML</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar Cambios</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Probar Plantilla -->
<div class="modal fade" id="testTemplateModal" tabindex="-1" aria-labelledby="testTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testTemplateModalLabel">Probar Plantilla</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="testTemplateForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mb-3">
                        <label for="test_template_key" class="form-label">Clave de Plantilla</label>
                        <input type="text" class="form-control" id="test_template_key" name="template_key" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="test_recipient" class="form-label">Destinatario de Prueba</label>
                        <input type="text" class="form-control" id="test_recipient" name="recipient" placeholder="ej: test@dominio.com o +525512345678" required>
                        <small class="form-text text-muted">Email o número de teléfono para enviar la prueba.</small>
                    </div>
                    <div class="mb-3"><label for="test_json_data" class="form-label">Datos JSON de Prueba</label><textarea class="form-control" id="test_json_data" name="json_data" rows="8" placeholder='{"nombre_usuario": "Juan Pérez", "enlace_activacion": "https://ejemplo.com/factura/123"}' required></textarea><small class="form-text text-muted">Introduce los datos para reemplazar los placeholders `{{...}}`.</small></div>
                    <div id="testResult" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary" title="Muestra cómo se verá el mensaje procesado sin enviarlo."><i class="bi bi-display"></i> Procesar y Ver Previa</button>
                    <button type="button" id="sendTestEmailBtn" class="btn btn-success" title="Envía un correo real al destinatario de prueba."><i class="bi bi-send-check-fill"></i> Enviar Correo de Prueba</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Vista Previa de Plantilla -->
<div class="modal fade" id="previewTemplateModal" tabindex="-1" aria-labelledby="previewTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewTemplateModalLabel">Vista Previa de Plantilla</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <iframe id="previewIframe" style="width: 100%; height: 65vh; border: 1px solid #dee2e6;" title="Vista previa del contenido del mensaje"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Función para manejar la visibilidad del campo de asunto en los modales
    const handleSubjectField = (modal) => {
        const channelSelect = modal.querySelector('.modal-channel-select');
        const subjectField = modal.querySelector('.modal-subject-field');
        subjectField.style.display = channelSelect.value === 'email' ? 'block' : 'none';
    };

    // Modal de Añadir
    const addModal = document.getElementById('addTemplateModal');
    addModal.addEventListener('show.bs.modal', () => {
        handleSubjectField(addModal);
    });
    addModal.querySelector('.modal-channel-select').addEventListener('change', () => handleSubjectField(addModal));

    // Modal de Editar
    const editModal = document.getElementById('editTemplateModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const template = JSON.parse(button.getAttribute('data-template'));
        const modal = this;

        modal.querySelector('#edit_template_id').value = template.template_id,
        modal.querySelector('#edit_name').value = template.name,
        modal.querySelector('#edit_template_key').value = template.template_key,
        modal.querySelector('#edit_channel').value = template.channel,
        modal.querySelector('#edit_subject').value = template.subject,
        modal.querySelector('#edit_description').value = template.description,
        modal.querySelector('#edit_is_html').checked = 1 == template.is_html;

        const bodyTextarea = modal.querySelector('#edit_body');
        bodyTextarea.value = template.body;
        handleSubjectField(modal);
    });
    editModal.querySelector('.modal-channel-select').addEventListener('change', () => handleSubjectField(editModal));

    // Modal de Vista Previa
    const previewModal = document.getElementById('previewTemplateModal');
    previewModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const template = JSON.parse(button.getAttribute('data-template'));
        const modal = this;

        const modalTitle = modal.querySelector('.modal-title');
        const previewIframe = modal.querySelector('#previewIframe');

        modalTitle.textContent = 'Vista Previa: ' + template.name;

        // Usar srcdoc para renderizar el HTML de forma segura.
        if (template.is_html == 1) {
            previewIframe.srcdoc = template.body;
        } else {
            // Para texto plano, lo envolvemos en <pre> para mantener saltos de línea y espacios.
            previewIframe.srcdoc = `<pre style="white-space: pre-wrap; word-wrap: break-word;">${document.createTextNode(template.body).textContent}</pre>`;
        }
    });

    previewModal.addEventListener('hidden.bs.modal', function () {
        this.querySelector('#previewIframe').srcdoc = '';
    });

    // Modal para Probar Plantilla
    const testModal = document.getElementById('testTemplateModal');
    const testForm = document.getElementById('testTemplateForm');
    const testResultDiv = document.getElementById('testResult');
    const sendTestEmailBtn = document.getElementById('sendTestEmailBtn');

    testModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const template = JSON.parse(button.getAttribute('data-template'));
        const modal = this;

        modal.querySelector('#test_template_key').value = template.template_key;

        // Habilitar/deshabilitar botón de envío y cambiar placeholder según el canal
        if (template.channel !== 'email') {
            sendTestEmailBtn.style.display = 'none';
            modal.querySelector('#test_recipient').placeholder = 'ej: +525512345678 (no se enviará)';
        } else {
            sendTestEmailBtn.style.display = 'inline-block';
            modal.querySelector('#test_recipient').placeholder = 'ej: test@dominio.com';
        }

        // Limpiar resultados previos y rellenar con ejemplos
        testResultDiv.innerHTML = '';
        testResultDiv.className = 'mt-3';
        modal.querySelector('#test_json_data').value = '{\n  "nombre_usuario": "Juan Pérez",\n  "enlace_factura": "https://ejemplo.com/factura/123"\n}';
        modal.querySelector('#test_recipient').value = template.channel === 'email' ? 'test@ejemplo.com' : '';
    });

    testForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(testForm);
        const submitButton = testForm.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;
        
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
        testResultDiv.innerHTML = '';
        testResultDiv.className = 'mt-3';

        fetch('handle_template_test.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                testResultDiv.className = 'alert alert-success';
                let previewContent = `<h6>Asunto Procesado:</h6><p class="font-monospace bg-light p-2 rounded">${data.processed_subject || 'N/A'}</p>`;
                previewContent += `<h6>Cuerpo Procesado:</h6>`;
                
                const previewContainer = document.createElement('div');
                previewContainer.style.border = '1px solid #ccc';
                previewContainer.style.padding = '15px';
                previewContainer.style.borderRadius = '5px';
                previewContainer.style.backgroundColor = '#f8f9fa';
                previewContainer.style.maxHeight = '40vh';
                previewContainer.style.overflowY = 'auto';

                if (data.is_html) {
                    const iframe = document.createElement('iframe');
                    iframe.style.width = '100%';
                    iframe.style.height = '35vh';
                    iframe.style.border = 'none';
                    iframe.srcdoc = data.processed_body;
                    previewContainer.appendChild(iframe);
                } else {
                    const pre = document.createElement('pre');
                    pre.style.whiteSpace = 'pre-wrap';
                    pre.textContent = data.processed_body;
                    previewContainer.appendChild(pre);
                }
                
                testResultDiv.innerHTML = previewContent;
                testResultDiv.appendChild(previewContainer);
            } else {
                testResultDiv.className = 'alert alert-danger';
                testResultDiv.textContent = `Error: ${data.message}`;
            }
        })
        .catch(error => {
            testResultDiv.className = 'alert alert-danger';
            testResultDiv.textContent = 'Ocurrió un error de red. Por favor, revisa la consola.';
            console.error('Error:', error);
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        });
    });

    if (sendTestEmailBtn) {
        sendTestEmailBtn.addEventListener('click', function() {
            if (!testForm.checkValidity()) {
                testForm.reportValidity();
                return;
            }

            const formData = new FormData(testForm);
            const originalButtonText = this.innerHTML;
            
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...';
            testResultDiv.innerHTML = '';
            testResultDiv.className = 'alert alert-info';
            testResultDiv.textContent = 'Intentando enviar el correo de prueba...';

            fetch('handle_template_send_test.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json().then(data => ({ status: response.status, body: data })))
            .then(({ status, body }) => {
                if (body.status === 'success') {
                    testResultDiv.className = 'alert alert-success';
                    testResultDiv.textContent = body.message;
                } else {
                    testResultDiv.className = 'alert alert-danger';
                    testResultDiv.textContent = `Error: ${body.message}`;
                }
            })
            .catch(error => {
                testResultDiv.className = 'alert alert-danger';
                testResultDiv.textContent = 'Ocurrió un error de red. Por favor, revisa la consola.';
                console.error('Error:', error);
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = originalButtonText;
            });
        });
    }

    // Script para el filtro de la tabla
    const filterInput = document.getElementById('templateFilter');
    const tableBody = document.getElementById('templatesTableBody');
    const templateRows = tableBody.querySelectorAll('tr:not(#no-templates-found)');
    const noResultsRow = document.getElementById('no-templates-found');

    if (filterInput) {
        filterInput.addEventListener('keyup', function() {
            const filterText = this.value.toLowerCase();
            let visibleCount = 0;
            templateRows.forEach(function(row) {
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