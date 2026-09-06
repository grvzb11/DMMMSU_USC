<?php
/**
 * Developed by: George Rexy Vincent Z. Bacani
 * College: College of Information Technology
 * Role: System Developer / Front-End Developer
 * Development Year: 2026–2027
 * Institution: Don Mariano Marcos Memorial State University
 * Version: v1.0
 * Email: rexygeorge11@gmail.com
 * Copyright: © 2026–2027. All rights reserved.
 */
declare(strict_types=1);
require_once __DIR__.'/app.php';

function app_data_key_path(): string {
    return app_storage_path('keys/data.key');
}
function app_data_key(): string {
    static $key = null;
    if ($key !== null) return $key;
    $env = trim((string)app_env('APP_DATA_KEY', ''));
    if ($env !== '') {
        $decoded = base64_decode($env, true);
        if ($decoded !== false && strlen($decoded) >= 32) return $key = substr($decoded, 0, 32);
        if (strlen($env) >= 32) return $key = substr(hash('sha256', $env, true), 0, 32);
    }
    $path = app_data_key_path();
    if (is_file($path)) {
        $raw = @file_get_contents($path);
        if (is_string($raw) && strlen($raw) >= 32) return $key = substr($raw, 0, 32);
    }
    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) throw new RuntimeException('Unable to create protected encryption-key storage.');
    $raw = random_bytes(32);
    if (@file_put_contents($path, $raw, LOCK_EX) === false) throw new RuntimeException('Unable to persist the protected encryption key.');
    @chmod($path, 0600);
    return $key = $raw;
}
function app_encrypt_sensitive(?string $plaintext): ?string {
    if ($plaintext === null || $plaintext === '') return $plaintext;
    if (str_starts_with($plaintext, 'enc:v1:')) return $plaintext;
    if (!function_exists('openssl_encrypt')) return $plaintext;
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', app_data_key(), OPENSSL_RAW_DATA, $iv, $tag, 'DMMMSU-USC-v1');
    if ($cipher === false) throw new RuntimeException('Unable to encrypt protected data.');
    return 'enc:v1:'.base64_encode($iv.$tag.$cipher);
}
function app_decrypt_sensitive(?string $value): ?string {
    if ($value === null || $value === '') return $value;
    if (!str_starts_with($value, 'enc:v1:')) return $value;
    if (!function_exists('openssl_decrypt')) return null;
    $raw = base64_decode(substr($value, 7), true);
    if ($raw === false || strlen($raw)<29) return null;
    $iv = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $cipher = substr($raw, 28);
    $plain = openssl_decrypt($cipher, 'aes-256-gcm', app_data_key(), OPENSSL_RAW_DATA, $iv, $tag, 'DMMMSU-USC-v1');
    return $plain === false?null:$plain;
}
function app_sensitive_is_encrypted(?string $value): bool {
    return is_string($value) && str_starts_with($value, 'enc:v1:');
}
