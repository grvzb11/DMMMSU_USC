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

if (!function_exists('app_root')) {
    function app_root(string $path = ''): string {
        $root = dirname(__DIR__);
        return $path === ''?$root:$root.'/'.ltrim($path, '/\\');
    }

    function app_load_env(): array {
        static $env = null;
        if ($env !== null) return $env;
        $env = [];
        $file = app_root('.env');
        if (is_file($file) && is_readable($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:[] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
                [$key, $value] = array_map('trim', explode('=', $line, 2));
                if ($key === '') continue;
                if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) $value = substr($value, 1, -1);
                $env[$key] = $value;
            }
        }
        foreach ($_ENV as $k => $v) if (is_string($v)) $env[$k] = $v;
        return $env;
    }

    function app_env(string $key, mixed $default = null): mixed {
        $server = getenv($key);
        if ($server !== false) return $server;
        $env = app_load_env();
        return array_key_exists($key, $env)?$env[$key]:$default;
    }

    function app_env_bool(string $key, bool $default = false): bool {
        $v = app_env($key, $default?'1':'0');
        if (is_bool($v)) return $v;
        return in_array(strtolower(trim((string)$v)), ['1', 'true', 'yes', 'on'], true);
    }

    function app_environment(): string {
        return strtolower(trim((string)app_env('APP_ENV', 'local')));
    }
    function app_is_production(): bool {
        return app_environment() === 'production';
    }
    function app_base_url(): string {
        $configured = rtrim(trim((string)app_env('APP_URL', '')), '/');
        if ($configured !== '') return $configured;
        if (PHP_SAPI === 'cli') return '';
        $scheme = app_is_https()?'https':'http';
        $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === '') return '';
        $projectFs = realpath(app_root())?:app_root();
        $documentFs = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? ''))?:'';
        $basePath = '';
        if ($documentFs !== '' && str_starts_with(strtolower(str_replace('\\', '/', $projectFs)), strtolower(rtrim(str_replace('\\', '/', $documentFs), '/')))) {
            $basePath = '/'.trim(substr(str_replace('\\', '/', $projectFs), strlen(rtrim(str_replace('\\', '/', $documentFs), '/'))), '/');
            if ($basePath === '/') $basePath = '';
        }
        return $scheme.'://'.$host.$basePath;
    }
    function app_absolute_url(string $path = ''): string {
        if (preg_match('~^https?://~i', $path)) return $path;
        $base = app_base_url();
        return $base !== ''?$base.'/'.ltrim($path, '/'):$path;
    }
    function app_is_https(): bool {
        return (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }

    function app_storage_path(string $path = ''): string {
        return app_root('storage'.($path !== ''?'/'.ltrim($path, '/\\'):''));
    }

    function app_log_error(string $message, array $context = []): void {
        $dir = app_storage_path('logs');
        if (!is_dir($dir)) @mkdir($dir, 0750, true);
        $line = '['.date('Y-m-d H:i:s').'] '.$message;
        if ($context) $line.=' '.json_encode($context, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        @file_put_contents($dir.'/app.log', $line.PHP_EOL, FILE_APPEND|LOCK_EX);
    }

    function app_error_reference(): string {
        return strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
    }

    function app_send_security_headers(): void {
        if (PHP_SAPI === 'cli' || headers_sent()) return;
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        if (app_is_https()) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        $csp = "default-src 'self'; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; script-src 'self' 'unsafe-inline'; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'";
        if (app_env_bool('APP_CSP_ENFORCE', false)) header('Content-Security-Policy: '.$csp);
        else header('Content-Security-Policy-Report-Only: '.$csp);
    }

    function app_configure_session(): void {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.cookie_secure', app_is_https()?'1':'0');
        session_name('DMMMSU_USC_SESSION');
        session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => app_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
        ]);
    }

    function app_render_error_page(int $status, string $title, string $message, ?string $reference = null): never {
        http_response_code($status);
        $reference = $reference?:app_error_reference();
        $file = app_root('errors/'.$status.'.php');
        if (is_file($file)) {
            require $file;
            exit;
        }
        echo '<!doctype html><meta charset="utf-8"><title>'.htmlspecialchars($title).'</title><h1>'.htmlspecialchars($title).'</h1><p>'.htmlspecialchars($message).'</p><small>Reference: '.htmlspecialchars($reference).'</small>';
        exit;
    }

    function app_register_error_handler(): void {
        static $registered = false;
        if ($registered) return;
        $registered = true;
        error_reporting(E_ALL);
        ini_set('display_errors', app_is_production()?'0':'1');
        ini_set('log_errors', '0');
        set_error_handler(function (int $severity, string $message, string $file, int $line) : bool {
            if (!(error_reporting()&$severity)) return false;
            app_log_error('PHP error', ['severity' => $severity, 'message' => $message, 'file' => $file, 'line' => $line]);
            return app_is_production();
        });
        set_exception_handler(function (Throwable $e) : void {
            $ref = app_error_reference();
            app_log_error('Uncaught exception', ['reference' => $ref, 'type' => get_class($e), 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => app_is_production()?null:$e->getTraceAsString()]);
            if (PHP_SAPI === 'cli') {
                fwrite(STDERR, "Unhandled error [$ref]: ".$e->getMessage().PHP_EOL); return;
            }
            app_render_error_page(500, 'Something went wrong', 'The system could not complete this request. Please try again or give the reference code to the System Administrator.', $ref);
        });
    }

    function app_bootstrap(): void {
        static $booted = false;
        if ($booted) return;
        $booted = true;
        date_default_timezone_set((string)app_env('APP_TIMEZONE', 'Asia/Manila'));
        app_configure_session();
        app_send_security_headers();
        app_register_error_handler();
    }

    app_bootstrap();
}
