<?php
// admin/mailer_service.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Autoload de Composer y dependencias del proyecto
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../backend/db_config.php';
require_once __DIR__ . '/encryption_handler.php';

/**
 * Obtiene un valor de configuración del sistema desde la base de datos.
 *
 * @param mysqli $conn Conexión a la base de datos.
 * @param string $key La clave de configuración a obtener.
 * @return string|null El valor de la configuración o null si no se encuentra o está inactivo.
 */
function get_system_config(mysqli $conn, string $key): ?string {
    // Prepara la consulta para obtener un valor de configuración activo
    $stmt = $conn->prepare("SELECT config_value, is_encrypted FROM system_configuration WHERE config_key = ? AND is_active = 1");
    if (!$stmt) {
        error_log("Error al preparar la consulta para la clave de configuración: $key");
        return null;
    }
    
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($config = $result->fetch_assoc()) {
        // Si el valor está encriptado, lo desencripta antes de devolverlo
        if ($config['is_encrypted'] && !empty($config['config_value'])) {
            $decrypted = decrypt_value($config['config_value']);
            return $decrypted !== false ? $decrypted : null;
        }
        return $config['config_value'];
    }
    
    $stmt->close();
    return null;
}

/**
 * Envía un correo electrónico utilizando la configuración del sistema.
 *
 * @param array $to Array de destinatarios. Formato: ['email@example.com'] o [['address' => 'email@example.com', 'name' => 'Nombre']]
 * @param string $subject Asunto del correo.
 * @param string $body Cuerpo del correo (puede ser HTML).
 * @param array $cc Array de destinatarios en CC. Mismo formato que $to.
 * @param array $bcc Array de destinatarios en BCC. Mismo formato que $to.
 * @param bool $is_html Indica si el cuerpo es HTML.
 * @param array $attachments Array de adjuntos. Cada adjunto es un array con 'path' y opcionalmente 'name'.
 * @return bool True si el correo se envió, false en caso contrario.
 * @throws Exception Si la configuración de SMTP no está completa en la base de datos.
 */
function send_system_email(array $to, string $subject, string $body, array $cc = [], array $bcc = [], bool $is_html = true, array $attachments = []): bool {
    global $servername, $username, $password, $dbname;
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        error_log("Mailer Service: Error de conexión a la BD: " . $conn->connect_error);
        return false;
    }
    $conn->set_charset(DB_CHARSET);

    // Obtener configuración de SMTP desde la BD
    $smtp_host = get_system_config($conn, 'smtp_host');
    $smtp_user = get_system_config($conn, 'smtp_user');
    $smtp_pass = get_system_config($conn, 'smtp_pass');
    $smtp_port = (int)get_system_config($conn, 'smtp_port');
    $smtp_secure = get_system_config($conn, 'smtp_secure');
    $from_email = get_system_config($conn, 'from_email');
    $from_name = get_system_config($conn, 'from_name');
    $conn->close();

    if (!$smtp_host || !$smtp_user || !$smtp_pass || !$smtp_port || !$smtp_secure || !$from_email || !$from_name) {
        throw new Exception('La configuración de SMTP no está completa o está inactiva en la base de datos.');
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        $mail->SMTPSecure = $smtp_secure; // 'ssl' o 'tls'
        $mail->Port       = $smtp_port;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($from_email, $from_name);
        $mail->addReplyTo($from_email, $from_name);

        // Añadir destinatarios principales (To)
        foreach ($to as $recipient) {
            if (is_array($recipient) && isset($recipient['address'])) {
                $mail->addAddress($recipient['address'], $recipient['name'] ?? '');
            } elseif (is_string($recipient)) {
                $mail->addAddress($recipient);
            }
        }

        // Añadir destinatarios en copia (CC)
        foreach ($cc as $recipient) {
            if (is_array($recipient) && isset($recipient['address'])) {
                $mail->addCC($recipient['address'], $recipient['name'] ?? '');
            } elseif (is_string($recipient)) {
                $mail->addCC($recipient);
            }
        }

        // Añadir destinatarios en copia oculta (BCC)
        foreach ($bcc as $recipient) {
            $mail->addBCC($recipient);
        }

        $mail->isHTML($is_html);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        if ($is_html) $mail->AltBody = strip_tags($body);

        // Añadir archivos adjuntos
        foreach ($attachments as $attachment) {
            if (isset($attachment['path']) && file_exists($attachment['path'])) {
                $name = $attachment['name'] ?? '';
                $mail->addAttachment($attachment['path'], $name);
            } else {
                error_log("Archivo adjunto no encontrado: " . ($attachment['path'] ?? 'ruta no especificada'));
            }
        }

        return $mail->send();
    } catch (Exception $e) {
        $recipient_list = json_encode($to);
        error_log("El correo no se pudo enviar a {$recipient_list}. Error de PHPMailer: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Procesa una plantilla de correo y la envía usando send_system_email.
 *
 * @param string $template_key La clave única de la plantilla a usar.
 * @param array $to Array de destinatarios principales.
 * @param array $data Array asociativo con los datos para reemplazar en la plantilla (ej: ['nombre_usuario' => 'Juan']).
 * @param array $cc Array de destinatarios en CC.
 * @param array $bcc Array de destinatarios en BCC.
 * @param array $attachments Array de archivos adjuntos.
 * @return bool True si el correo se envió, false en caso contrario.
 * @throws Exception Si la plantilla no se encuentra, no es de tipo email, o si falla el envío.
 */
function send_email_template(string $template_key, array $to, array $data, array $cc = [], array $bcc = [], array $attachments = []): bool {
    global $servername, $username, $password, $dbname;

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        error_log("Mailer Service (Template): Error de conexión a la BD: " . $conn->connect_error);
        return false;
    }
    $conn->set_charset(DB_CHARSET);

    // 1. Obtener la plantilla de la base de datos
    $stmt = $conn->prepare("SELECT subject, body, is_html, channel FROM message_templates WHERE template_key = ? AND is_active = 1");
    if (!$stmt) {
        $conn->close();
        throw new Exception("Error al preparar la consulta de plantilla: " . $conn->error);
    }
    $stmt->bind_param("s", $template_key);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        throw new Exception("Plantilla de correo '{$template_key}' no encontrada o está inactiva.");
    }

    $template = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    if ($template['channel'] !== 'email') {
        throw new Exception("La plantilla '{$template_key}' no es para el canal de Email.");
    }

    // 2. Procesar la plantilla con los datos
    $processed_subject = str_replace(array_map(fn($k) => "{{{$k}}}", array_keys($data)), array_values($data), $template['subject']);
    $processed_body = str_replace(array_map(fn($k) => "{{{$k}}}", array_keys($data)), array_values($data), $template['body']);

    // 3. Llamar a la función de envío de correo del sistema
    return send_system_email($to, $processed_subject, $processed_body, $cc, $bcc, (bool)$template['is_html'], $attachments);
}