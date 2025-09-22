<?php
$page_title = 'Prueba de Envío de Correo';
require_once 'session_config.php';
require_once 'auth_check.php';
require_once 'mailer_service.php'; // Incluimos el nuevo servicio de correo
require_once 'header.php';

// --- Parámetros de prueba ---
// --- Parámetros de prueba para la plantilla ---
$template_key_to_test = 'bienvenida_usuario'; // Clave de la plantilla a probar (debes crearla en el panel)
$data_for_template = [
    'nombre_usuario' => 'Juan Pérez de Prueba',
    'enlace_activacion' => 'https://servicios-maco.com/activar?token=123456'
];

// --- Destinatarios y adjuntos ---
$to_recipients = [
    ['address' => 'mtz.cosio27@gmail.com', 'name' => 'Usuario de Prueba Principal'], // Cambia esto por tu correo de prueba
    // 'otro.correo@ejemplo.com' // Puedes añadir más correos sin nombre
];

$cc_recipients = [
    // ['address' => 'cc.test@ejemplo.com', 'name' => 'Copia Carbón']
];

$bcc_recipients = [
    // 'bcc.test@ejemplo.com'
];

// --- Archivos adjuntos de prueba ---
// ¡Asegúrate de que estos archivos existan en la ruta especificada para que la prueba funcione!
$attachment1_path = __DIR__ . '/../documents/documento_prueba.pdf';
$attachment2_path = __DIR__ . '/../assets/img/Logo_SinFondo.png';

// Crear un directorio y un archivo de prueba si no existen para que el script no falle.
if (!is_dir(dirname($attachment1_path))) {
    mkdir(dirname($attachment1_path), 0777, true);
}
if (!file_exists($attachment1_path)) {
    file_put_contents($attachment1_path, 'Este es un documento de prueba.');
    echo '<div class="alert alert-info">Se creó un archivo de prueba en <code>' . htmlspecialchars($attachment1_path, ENT_QUOTES, 'UTF-8') . '</code> para la demostración.</div>';
}

$attachments_to_send = [];
if (file_exists($attachment1_path)) {
    $attachments_to_send[] = ['path' => $attachment1_path, 'name' => 'Documento_de_Prueba.pdf'];
}
if (file_exists($attachment2_path)) {
    $attachments_to_send[] = ['path' => $attachment2_path]; // Usará el nombre original del archivo
}

$recipient_string = implode(', ', array_map(function($r) {
    return is_array($r) ? htmlspecialchars("{$r['name']} <{$r['address']}>", ENT_QUOTES, 'UTF-8') : htmlspecialchars($r, ENT_QUOTES, 'UTF-8');
}, $to_recipients));

echo "<h4>Prueba de Envío con Plantilla</h4>";
echo "<p>Intentando enviar correo usando la plantilla: <strong>" . htmlspecialchars($template_key_to_test, ENT_QUOTES, 'UTF-8') . "</strong></p>";
echo "<p>Destinatarios: <strong>" . $recipient_string . "</strong></p>";
echo "<p>Archivos adjuntos: " . count($attachments_to_send) . "</p><hr>";

try {
    // Llamar a la nueva función de envío de correo con plantilla
    $sent = send_email_template(
        $template_key_to_test,
        $to_recipients,
        $data_for_template,
        $cc_recipients,
        $bcc_recipients,
        $attachments_to_send
    );

    if ($sent) {
        echo '<div class="alert alert-success"><strong>¡Éxito!</strong> El correo de prueba con plantilla se envió correctamente. Revisa las bandejas de entrada.</div>';
    } else {
        echo '<div class="alert alert-danger"><strong>Error:</strong> El correo no se pudo enviar. Revisa los logs de errores de PHP para más detalles.</div>';
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger"><strong>Error Crítico:</strong> Se produjo una excepción: ' . $e->getMessage() . '</div>';
    echo "<p>Por favor, asegúrate de que la plantilla '<strong>".htmlspecialchars($template_key_to_test, ENT_QUOTES, 'UTF-8')."</strong>' exista, esté activa y sea de tipo 'email'.</p>";
    echo "<p>También, revisa la <strong>Configuración del Sistema</strong> para los parámetros de SMTP.</p>";
}

require_once 'footer.php';
?>