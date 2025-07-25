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
 * @param string $to_email Email del destinatario.
 * @param string $to_name Nombre del destinatario.
 * @param string $subject Asunto del correo.
 * @param string $body Cuerpo del correo (puede ser HTML).
 * @param bool $is_html Indica si el cuerpo es HTML.
 * @return bool True si el correo se envió, false en caso contrario.
 * @throws Exception Si la configuración de SMTP no está completa en la base de datos.
 */
function send_system_email(string $to_email, string $to_name, string $subject, string $body, bool $is_html = true): bool {
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
        $mail->addAddress($to_email, $to_name);
        $mail->addReplyTo($from_email, $from_name);

        $mail->isHTML($is_html);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        if ($is_html) $mail->AltBody = strip_tags($body);

        return $mail->send();
    } catch (Exception $e) {
        error_log("El correo no se pudo enviar a {$to_email}. Error de PHPMailer: {$mail->ErrorInfo}");
        return false;
    }
}