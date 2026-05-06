<?php
// ============================================================
// auth_check.php — Lógica de autenticación
// ============================================================

require_once __DIR__ . '/auth_config.php';

/**
 * Genera el contenido firmado de la cookie.
 * Formato: timestamp_expiracion.firma_hmac
 */
function auth_make_cookie_value(int $expiresAt): string {
    $payload = (string)$expiresAt;
    $signature = hash_hmac('sha256', $payload, AUTH_SECRET);
    return $payload . '.' . $signature;
}

/**
 * Verifica si la cookie es válida y no expiró.
 */
function auth_is_valid(): bool {
    if (!isset($_COOKIE[AUTH_COOKIE_NAME])) return false;

    $value = $_COOKIE[AUTH_COOKIE_NAME];
    $parts = explode('.', $value, 2);
    if (count($parts) !== 2) return false;

    [$expiresAt, $signature] = $parts;

    // Verificar firma (uso hash_equals para evitar timing attacks)
    $expected = hash_hmac('sha256', $expiresAt, AUTH_SECRET);
    if (!hash_equals($expected, $signature)) return false;

    // Verificar que no expiró
    if ((int)$expiresAt < time()) return false;

    return true;
}

/**
 * Setea la cookie de autenticación.
 */
function auth_login(): void {
    $expiresAt = time() + AUTH_COOKIE_LIFETIME;
    $value = auth_make_cookie_value($expiresAt);

    setcookie(AUTH_COOKIE_NAME, $value, [
        'expires'  => $expiresAt,
        'path'     => '/',
        'domain'   => '',           // dominio actual
        'secure'   => true,         // solo HTTPS
        'httponly' => true,         // no accesible desde JS
        'samesite' => 'Lax',        // protección CSRF razonable
    ]);
}

/**
 * Borra la cookie (logout).
 * Borra cookies de path=/ (nueva) y path=/calculadora/ (legacy) por compat.
 */
function auth_logout(): void {
    foreach (['/', '/calculadora/'] as $p) {
        setcookie(AUTH_COOKIE_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => $p,
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

/**
 * Verifica password y devuelve true si es correcta.
 */
function auth_check_password(string $password): bool {
    return password_verify($password, AUTH_PASSWORD_HASH);
}

// ============================================================
// RATE LIMITING — controla intentos fallidos por IP
// ============================================================

function auth_rate_file(): string {
    return __DIR__ . '/.auth_attempts.json';
}

function auth_get_attempts(): array {
    $file = auth_rate_file();
    if (!file_exists($file)) return [];
    $raw = @file_get_contents($file);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function auth_save_attempts(array $data): void {
    @file_put_contents(auth_rate_file(), json_encode($data));
}

function auth_get_ip(): string {
    // Solo REMOTE_ADDR — X-Forwarded-For es spoofeable por el cliente.
    // En Hostinger shared hosting el cliente llega directo sin proxy nuestro,
    // así que confiar en headers HTTP de la request rompe el rate limiting.
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Devuelve segundos restantes de bloqueo, o 0 si no está bloqueado.
 */
function auth_check_lockout(): int {
    $ip = auth_get_ip();
    $data = auth_get_attempts();
    if (!isset($data[$ip])) return 0;

    $entry = $data[$ip];
    if (($entry['count'] ?? 0) < AUTH_MAX_ATTEMPTS) return 0;

    $unlockAt = ($entry['last'] ?? 0) + AUTH_LOCKOUT_TIME;
    $remaining = $unlockAt - time();
    return $remaining > 0 ? $remaining : 0;
}

function auth_record_failure(): void {
    $ip = auth_get_ip();
    $data = auth_get_attempts();
    $entry = $data[$ip] ?? ['count' => 0, 'last' => 0];

    // Reset si ya pasó la ventana de bloqueo
    if (time() - $entry['last'] > AUTH_LOCKOUT_TIME) {
        $entry = ['count' => 0, 'last' => 0];
    }

    $entry['count']++;
    $entry['last'] = time();
    $data[$ip] = $entry;

    // Limpieza: borrar entradas viejas (>1 día) para que el archivo no crezca
    foreach ($data as $k => $v) {
        if (time() - ($v['last'] ?? 0) > 86400) unset($data[$k]);
    }

    auth_save_attempts($data);
}

function auth_reset_attempts(): void {
    $ip = auth_get_ip();
    $data = auth_get_attempts();
    if (isset($data[$ip])) {
        unset($data[$ip]);
        auth_save_attempts($data);
    }
}
