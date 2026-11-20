<?php
// ============================================================
// _auth.php — Wrapper de auth para endpoints del registry
// BlackStones Marmolería · Registry de Presupuestos v1
// ============================================================
//
// Reutiliza la sesión de la calc (auth_check.php).
// Cualquier endpoint empieza con:
//   require_once __DIR__ . '/_auth.php';
//   bs_require_permiso('<permiso>');   // ver AUTH_PERMISOS en auth_check.php
//
// Sin sesión → 401. Sin permiso → 403. Si OK → continúa.
// ============================================================

require_once __DIR__ . '/../auth_check.php';

/**
 * Exige sesión Y permiso (ver AUTH_PERMISOS en auth_check.php).
 * Sin sesión → 401. Con sesión pero sin permiso → 403.
 * Los cambios (todo lo que no sea GET) quedan anotados con el usuario.
 */
function bs_require_permiso($permiso) {
    if (!auth_is_valid()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!auth_puede($permiso)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Tu usuario no tiene permiso para esto.', 'sin_permiso' => $permiso], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // La copia de las últimas 24 horas (dueño, 19/11/2026): el primer pedido de cada hora la toma acá,
    // antes de que el endpoint cambie nada. Si _copias.php no está o falla, se sigue de largo.
    if (is_file(__DIR__ . '/_copias.php')) {
        try { require_once __DIR__ . '/_copias.php'; bs_copia_si_toca(); } catch (Throwable $e) { @error_log('copia: ' . $e->getMessage()); }
    }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        $in = json_decode((string)@file_get_contents('php://input'), true);
        if (!is_array($in)) $in = $_POST;
        $d = [];
        $campos = ['cliente_nro', 'sub', 'id', 'accion', 'etapa', 'nuevo_estado', 'tipo', 'auto'];
        foreach ($campos as $k) {
            if (isset($in[$k]) && is_scalar($in[$k])) $d[$k] = $in[$k];
        }
        // Solo en el tablero se anota además qué tilde fue y qué turno se puso (18/11/2026). El tilde se
        // escribe como "tilde=", nunca como "clave=": en usuarios.php "clave" es la contraseña, y en el
        // registro no aparece jamás un campo con ese nombre.
        if (basename((string)($_SERVER['SCRIPT_NAME'] ?? '')) === 'tablero.php') {
            if (isset($in['clave']) && is_scalar($in['clave'])) $d['tilde'] = $in['clave'];
            foreach (['valor', 'fecha', 'hora'] as $k) { if (isset($in[$k]) && is_scalar($in[$k])) $d[$k] = is_bool($in[$k]) ? ($in[$k] ? 'si' : 'no') : $in[$k]; }
        }
        // Se anota al terminar, y solo si la acción salió bien (barrido del 18/11/2026). Antes se
        // anotaba al entrar: una carga rechazada quedaba en el registro como "Cargó una obra".
        register_shutdown_function(function () use ($permiso, $d) {
            $c = http_response_code();
            if (!empty($GLOBALS['bs_no_registrar'])) return; // el endpoint avisó que no cambió nada
            if ($c === false || $c < 400) auth_registrar($permiso, $d);
        });
    }
}

/**
 * Un endpoint que solo pide "estar adentro" no dice qué rol puede usarlo.
 * Por las dudas queda cerrado a administradores: todo endpoint tiene que
 * llamar a bs_require_permiso() con su permiso.
 */
function bs_require_auth() {
    bs_require_permiso('mantenimiento');
}

/**
 * CSRF defense via Origin/Referer header check.
 *
 * Solo permite requests POST/PUT/DELETE cuyo Origin (o Referer si no hay
 * Origin) coincida con el host del servidor. Bloquea requests cross-site
 * incluso si el browser envía la cookie (defense-in-depth sobre SameSite=Lax).
 *
 * Llamar después de bs_require_auth() en cualquier endpoint que muta estado.
 */
function bs_check_csrf() {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) return;

    $expected_host = $_SERVER['HTTP_HOST'] ?? '';
    if (!$expected_host) return; // sin host conocido, no podemos validar

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';

    $source = $origin ?: $referer;
    if (!$source) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'CSRF: falta Origin/Referer'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $parsed = parse_url($source);
    $source_host = $parsed['host'] ?? '';
    if ($source_host !== $expected_host) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'CSRF: origen no permitido'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
