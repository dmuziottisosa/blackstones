<?php
// ============================================================
// auth_check.php — Lógica de autenticación
// ============================================================

require_once __DIR__ . '/auth_config.php';

// ============================================================
// USUARIOS Y ROLES (17/11/2026)
// ============================================================
// Cada persona entra con su usuario y su contraseña. Los usuarios viven en
// bs-data/usuarios/usuarios.json, fuera de la web y fuera del repo:
//   { version, secreto, usuarios: { <id>: { usuario, nombre, rol, hash, activo, ver, creado, ultimo } } }
//
// Mientras ese archivo no exista el sistema anda como siempre (una contraseña
// compartida, todos administradores). Cuando se crea el primer administrador
// desde /presupuestos/usuarios.php, la contraseña compartida deja de servir y
// la cookie pasa a firmarse con un secreto propio del servidor ('secreto'),
// que no está en el repo.
//
// QUÉ PUEDE CADA ROL está en AUTH_PERMISOS y en ningún otro lado. Las páginas
// esconden lo que el rol no usa, pero el bloqueo de verdad lo hace el servidor:
// cada endpoint llama a bs_require_permiso() (api/_auth.php).

const AUTH_ROLES = [
    'admin'    => 'Administrador',
    'operador' => 'Operador',
    'planos'   => 'Planos y bacha',
];

const AUTH_PERMISOS = [
    'calculadora'       => ['admin', 'operador'],
    'presupuestos'      => ['admin', 'operador'],
    'tablero'           => ['admin', 'operador', 'planos'], // ver el tablero y abrir fichas
    'tablero_operar'    => ['admin', 'operador'],           // cargar, mover, tildar, editar, archivar, borrar, papelera
    'tablero_adjuntar'  => ['admin', 'operador', 'planos'], // subir foto de bacha, plano y entrega
    'tablero_adj_ajeno' => ['admin', 'operador'],           // borrar o girar un adjunto que subió otro
    'tablero_plata'     => ['admin', 'operador'],           // ver la seña de la ficha
    'calendario'        => ['admin', 'operador'],
    'stock'             => ['admin', 'operador'],
    'contratos'         => ['admin', 'operador'],           // ver Relación-Contrato
    'contratos_editar'  => ['admin'],                       // crear, editar, borrar, pagos, adjuntos
    'contratos_plata'   => ['admin'],                       // ver montos, señas, pagos y el resumen
    'usuarios'          => ['admin'],
    'mantenimiento'     => ['admin'],                       // importadores y scripts de una sola vez
];

function auth_usuarios_archivo(): string {
    return __DIR__ . '/../../../../bs-data/usuarios/usuarios.json';
}

/**
 * La base de usuarios. null = todavía no existe (modo de contraseña compartida).
 * Si el archivo existe pero no se puede leer, devuelve ['roto' => true] y nadie entra:
 * un archivo dañado nunca reabre la contraseña compartida.
 */
function auth_db(bool $recargar = false): ?array {
    static $db = false;
    if ($db !== false && !$recargar) return $db;
    $f = auth_usuarios_archivo();
    if (!file_exists($f) && !file_exists($f . '.bak')) return $db = null;
    foreach ([$f, $f . '.bak'] as $g) {
        $j = json_decode((string)@file_get_contents($g), true);
        if (is_array($j) && !empty($j['secreto']) && is_array($j['usuarios'] ?? null) && $j['usuarios']) return $db = $j;
    }
    return $db = ['roto' => true, 'usuarios' => [], 'secreto' => ''];
}

/** Guarda la base entera. Devuelve false si no pudo. */
function auth_db_guardar(array $db): bool {
    $f = auth_usuarios_archivo();
    $dir = dirname($f);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return false;
    $db['version'] = 1;
    $txt = json_encode($db, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($txt === false) return false;
    $tmp = $f . '.tmp' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $txt, LOCK_EX) === false) return false;
    if (file_exists($f)) @copy($f, $f . '.bak');
    if (!@rename($tmp, $f)) { @unlink($tmp); return false; }
    auth_db(true);
    return true;
}

/** Candado para que dos cambios de usuarios no se pisen. Devuelve el handle o null. */
function auth_db_candado() {
    $dir = dirname(auth_usuarios_archivo());
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $fp = @fopen($dir . '/.lock', 'c');
    if (!$fp) return null;
    if (!flock($fp, LOCK_EX)) { fclose($fp); return null; }
    return $fp;
}

/**
 * Cookie del modo viejo (contraseña compartida). Formato: vencimiento.firma
 */
function auth_make_cookie_value(int $expiresAt): string {
    $payload = (string)$expiresAt;
    $signature = hash_hmac('sha256', $payload, AUTH_SECRET);
    return $payload . '.' . $signature;
}

/**
 * Cookie de un usuario. Formato: u.<id>.<vencimiento>.<ver>.<firma>
 * 'ver' sube cuando se le cambia la contraseña: las sesiones viejas dejan de servir.
 */
function auth_cookie_usuario(string $id, int $ver, int $expiresAt): string {
    $db = auth_db();
    $payload = 'u.' . $id . '.' . $expiresAt . '.' . $ver;
    return $payload . '.' . hash_hmac('sha256', $payload, (string)($db['secreto'] ?? ''));
}

/**
 * Quién está entrando: ['id', 'usuario', 'nombre', 'rol', 'viejo'] o null.
 * En el modo viejo no hay personas: es "Equipo", administrador.
 */
function auth_usuario(): ?array {
    static $hecho = false, $u = null;
    if ($hecho) return $u;
    $hecho = true;
    $valor = (string)($_COOKIE[AUTH_COOKIE_NAME] ?? '');
    if ($valor === '') return null;
    $db = auth_db();

    if ($db === null) {
        $parts = explode('.', $valor, 2);
        if (count($parts) !== 2) return null;
        [$expiresAt, $signature] = $parts;
        if (!hash_equals(hash_hmac('sha256', $expiresAt, AUTH_SECRET), $signature)) return null;
        if ((int)$expiresAt < time()) return null;
        return $u = ['id' => '', 'usuario' => '', 'nombre' => 'Equipo', 'rol' => 'admin', 'viejo' => true];
    }

    if (!empty($db['roto'])) return null;
    $p = explode('.', $valor);
    if (count($p) !== 5 || $p[0] !== 'u') return null;
    [, $id, $expiresAt, $ver, $firma] = $p;
    $payload = 'u.' . $id . '.' . $expiresAt . '.' . $ver;
    if (!hash_equals(hash_hmac('sha256', $payload, (string)$db['secreto']), $firma)) return null;
    if ((int)$expiresAt < time()) return null;
    $reg = $db['usuarios'][$id] ?? null;
    if (!is_array($reg) || empty($reg['activo'])) return null;
    if ((int)($reg['ver'] ?? 1) !== (int)$ver) return null;
    if (!isset(AUTH_ROLES[$reg['rol'] ?? ''])) return null;
    return $u = ['id' => $id, 'usuario' => (string)$reg['usuario'], 'nombre' => (string)$reg['nombre'], 'rol' => (string)$reg['rol'], 'viejo' => false];
}

function auth_is_valid(): bool {
    return auth_usuario() !== null;
}

/** ¿El que entró puede hacer esto? Un permiso que no existe no lo puede nadie. */
function auth_puede(string $permiso): bool {
    $u = auth_usuario();
    if (!$u) return false;
    return in_array($u['rol'], AUTH_PERMISOS[$permiso] ?? [], true);
}

/**
 * Tapa los montos escritos en un texto libre (notas), para quien no puede ver la plata.
 * "Saldo $ 285.720" → "Saldo $ ···". Reconoce $, USD, U$S, US$ y "pesos"/"dólares" después del número.
 * Es lo mejor que se puede hacer con texto libre: un monto escrito sin signo ("200 mil") no se reconoce.
 */
function auth_tapar_plata(string $txt): string {
    $txt = (string)preg_replace('/(?:U\$S|US\$|USD|U\$D|\$)\s*\d[\d.,]*(?:\s*(?:mil(?:lones)?|k|m)\b)?/iu', '$ ···', $txt);
    return (string)preg_replace('/\d[\d.,]*\s*(?:mil(?:lones)?\s*)?(?:pesos|d[oó]lares|usd)\b/iu', '$ ···', $txt);
}

/** Todo lo que puede el que entró, para que la página esconda lo que no usa. */
function auth_permisos_js(): string {
    $u = auth_usuario();
    $p = [];
    foreach (AUTH_PERMISOS as $k => $roles) $p[$k] = $u ? in_array($u['rol'], $roles, true) : false;
    return json_encode([
        'usuario' => $u ? $u['usuario'] : '', 'nombre' => $u ? $u['nombre'] : '', 'rol' => $u ? $u['rol'] : '',
        'rol_txt' => $u ? AUTH_ROLES[$u['rol']] : '', 'viejo' => $u ? !empty($u['viejo']) : false, 'puede' => $p,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
}

/**
 * El menú de arriba, igual en todas las páginas: solo lo que el rol puede abrir.
 * $activa: 'calculadora' | 'presupuestos' | 'tablero' | 'calendario' | 'contratos' | 'stock' | 'usuarios'
 */
function auth_nav_html(string $activa): string {
    $items = [
        ['calculadora',  '/calculadora/',                        'Calculadora'],
        ['presupuestos', '/presupuestos/',                       'Presupuestos'],
        ['tablero',      '/presupuestos/tablero.php',            'Tablero'],
        ['calendario',   '/presupuestos/calendario.php',         'Calendario'],
        ['contratos',    '/presupuestos/relacion-contrato.php',  'Relación-Contrato'],
        ['stock',        '/presupuestos/stock.php',              'Stock'],
    ];
    $h = "<nav class=\"h-tabs\">\n";
    foreach ($items as [$permiso, $url, $txt]) {
        if (!auth_puede($permiso)) continue;
        $h .= '  <a href="' . $url . '"' . ($activa === $permiso ? ' class="active"' : '') . '>' . $txt . "</a>\n";
    }
    $h .= '  <a href="/presupuestos/usuarios.php"' . ($activa === 'usuarios' ? ' class="active"' : '') . '>' . (auth_puede('usuarios') ? 'Usuarios' : 'Mi usuario') . "</a>\n";
    return $h . '</nav>';
}

/** Quién está adentro, para el encabezado (solo se ve en pantallas anchas). */
function auth_quien_html(): string {
    $u = auth_usuario();
    if (!$u || !empty($u['viejo'])) return '<span>blackstones.com.ar</span>';
    return '<span>' . htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8') . ' · ' . AUTH_ROLES[$u['rol']] . '</span>';
}

/** La primera pantalla de cada rol. */
function auth_inicio(): string {
    if (auth_puede('calculadora')) return '/calculadora/';
    if (auth_puede('tablero')) return '/presupuestos/tablero.php';
    return '/calculadora/logout.php';
}

/** Para páginas: sin sesión va al login; con sesión pero sin permiso, a su primera pantalla. */
function auth_exigir_pagina(string $permiso): void {
    if (!auth_is_valid()) { header('Location: /calculadora/login.php'); exit; }
    if (!auth_puede($permiso)) { header('Location: ' . auth_inicio()); exit; }
}

/**
 * Setea la cookie de autenticación. Sin usuario, la del modo viejo.
 */
function auth_login(?string $id = null): void {
    $expiresAt = time() + AUTH_COOKIE_LIFETIME;
    if ($id === null) {
        $value = auth_make_cookie_value($expiresAt);
    } else {
        $db = auth_db();
        $value = auth_cookie_usuario($id, (int)($db['usuarios'][$id]['ver'] ?? 1), $expiresAt);
    }

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
 * Contraseña compartida. Solo sirve mientras no haya usuarios.
 */
function auth_check_password(string $password): bool {
    if (auth_db() !== null) return false;
    return password_verify($password, AUTH_PASSWORD_HASH);
}

function auth_usuario_llano(string $s): string {
    return strtolower(trim($s));
}

/** Busca un usuario por su nombre de entrada. Devuelve su id o ''. */
function auth_buscar(string $usuario, ?array $db = null): string {
    $db = $db ?? auth_db();
    $q = auth_usuario_llano($usuario);
    if ($q === '' || !$db) return '';
    foreach ($db['usuarios'] as $id => $r) {
        if (auth_usuario_llano((string)($r['usuario'] ?? '')) === $q) return (string)$id;
    }
    return '';
}

/**
 * Usuario y contraseña. Devuelve el id si entran, '' si no.
 * Tarda lo mismo exista o no el usuario.
 */
function auth_entrar(string $usuario, string $password): string {
    $db = auth_db();
    if (!$db || !empty($db['roto'])) return '';
    $id = auth_buscar($usuario, $db);
    $hash = $id !== '' ? (string)($db['usuarios'][$id]['hash'] ?? '') : AUTH_PASSWORD_HASH;
    $ok = password_verify($password, $hash);
    if ($id === '' || !$ok || empty($db['usuarios'][$id]['activo'])) return '';
    return $id;
}

/** Reglas de una contraseña nueva. Devuelve el error o ''. */
function auth_clave_error(string $clave): string {
    if (strlen($clave) < 8) return 'La contraseña tiene que tener al menos 8 caracteres.';
    if (strlen($clave) > 200) return 'La contraseña es demasiado larga.';
    return '';
}

/**
 * Registro de quién hizo qué: una línea por cada cambio, en bs-data/usuarios/registro-AAAA-MM.log
 * (fecha, usuario, archivo, permiso, datos mínimos). Nunca guarda contraseñas ni nombres de clientes.
 */
function auth_registrar(string $permiso, array $datos = []): void {
    $u = auth_usuario();
    $dir = dirname(auth_usuarios_archivo());
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return;
    $quien = $u ? ($u['usuario'] !== '' ? $u['usuario'] : 'equipo') : '-';
    $partes = [];
    foreach ($datos as $k => $v) {
        if (is_scalar($v) && $v !== '') $partes[] = $k . '=' . preg_replace('/[^\w\-\.:]/u', '_', substr((string)$v, 0, 60));
    }
    $linea = date('Y-m-d H:i:s') . "\t" . $quien . "\t" . basename((string)($_SERVER['SCRIPT_NAME'] ?? '')) . "\t" . $permiso . "\t" . implode(' ', $partes) . "\n";
    @file_put_contents($dir . '/registro-' . date('Y-m') . '.log', $linea, FILE_APPEND | LOCK_EX);
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
