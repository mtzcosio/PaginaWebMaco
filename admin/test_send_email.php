<?php
// admin/send_email.php
// Este es un script de prueba para la funcionalidad de envío de correos.
// Asegúrate de que solo los administradores puedan ejecutarlo.

require_once 'session_config.php';
require_once 'auth_check.php';
require_once 'mailer_service.php'; // Incluimos el nuevo servicio de correo

// --- Parámetros de prueba ---
$test_recipient_email = 'mtz.cosio27@gmail.com'; // Cambia esto por un correo de prueba
$test_recipient_name = 'Usuario de Prueba';
$test_subject = 'Correo de prueba desde MACO';
$test_body_html = '<h1>¡Hola!</h1><p>Este es un correo de prueba enviado desde el sistema de MACO.</p><p>Si lo recibes, la configuración de SMTP funciona correctamente.</p>';

echo "Intentando enviar correo de prueba a: " . htmlspecialchars($test_recipient_email, ENT_QUOTES, 'UTF-8') . "<br><hr>";

try {
    // Llamar a la función de envío de correo
    $sent = send_system_email(
        $test_recipient_email,
        $test_recipient_name,
        $test_subject,
        $test_body_html,
        true // Es HTML
    );

    if ($sent) {
        echo '<div class="alert alert-success"><strong>¡Éxito!</strong> El correo de prueba se envió correctamente. Revisa la bandeja de entrada de ' . htmlspecialchars($test_recipient_email, ENT_QUOTES, 'UTF-8') . '.</div>';
    } else {
        echo '<div class="alert alert-danger"><strong>Error:</strong> El correo no se pudo enviar. Revisa los logs de errores de PHP para más detalles.</div>';
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger"><strong>Error Crítico:</strong> Se produjo una excepción: ' . $e->getMessage() . '</div>';
    echo "<p>Por favor, ve a <strong>Configuración del Sistema</strong> y asegúrate de que los siguientes parámetros existan, estén activos y sean correctos:</p>";
    echo "<ul><li><code>smtp_host</code></li><li><code>smtp_user</code></li><li><code>smtp_pass</code> (debe estar encriptado)</li><li><code>smtp_port</code></li><li><code>smtp_secure</code> (usualmente 'tls' o 'ssl')</li><li><code>from_email</code></li><li><code>from_name</code></li></ul>";
}
?>