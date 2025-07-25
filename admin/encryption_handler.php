<?php
// admin/encryption_handler.php

/**
 * ¡AVISO DE SEGURIDAD CRÍTICO!
 * La clave de encriptación NUNCA debe estar directamente en el código en un entorno de producción.
 * Esta clave debe cargarse desde una ubicación segura y no accesible desde la web, como:
 * - Una variable de entorno en el servidor (método recomendado).
 * - Un servicio de gestión de secretos (ej. AWS Secrets Manager, HashiCorp Vault).
 * - Un archivo de configuración fuera del directorio raíz de la web.
 *
 * Ejemplo usando una variable de entorno:
 * define('ENCRYPTION_KEY', getenv('APP_ENCRYPTION_KEY'));
 *
 * Para generar una clave segura, puedes usar el siguiente comando en tu terminal:
 * php -r "echo bin2hex(random_bytes(32));"
 */
define('ENCRYPTION_KEY', '5f2b4a7b9d8c1e3f0a6d7c8b9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f'); // <-- ¡CAMBIAR ESTA CLAVE Y MOVERLA A UN LUGAR SEGURO!

// Define el método de encriptación
define('ENCRYPTION_CIPHER', 'aes-256-cbc');

/**
 * Encripta un valor de texto plano.
 *
 * @param string $plaintext El valor a encriptar.
 * @return string El valor encriptado y codificado en base64, con el IV (vector de inicialización) prependido.
 */
function encrypt_value($plaintext) {
    $iv_length = openssl_cipher_iv_length(ENCRYPTION_CIPHER);
    $iv = openssl_random_pseudo_bytes($iv_length);
    $ciphertext = openssl_encrypt($plaintext, ENCRYPTION_CIPHER, ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $ciphertext);
}

/**
 * Desencripta un valor.
 *
 * @param string $encrypted_value El valor encriptado y codificado en base64.
 * @return string|false El texto plano desencriptado, o false si falla.
 */
function decrypt_value($encrypted_value) {
    $data = base64_decode($encrypted_value);
    if ($data === false) {
        return false;
    }
    $iv_length = openssl_cipher_iv_length(ENCRYPTION_CIPHER);
    $iv = substr($data, 0, $iv_length);
    $ciphertext = substr($data, $iv_length);
    return openssl_decrypt($ciphertext, ENCRYPTION_CIPHER, ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
}
?>