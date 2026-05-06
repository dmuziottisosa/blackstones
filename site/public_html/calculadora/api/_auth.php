<?php
// ============================================================
// _auth.php — Wrapper de auth para endpoints del registry
// BlackStones Marmolería · Registry de Presupuestos v1
// ============================================================
//
// Reutiliza el HMAC cookie auth de la calc (auth_check.php).
// Cualquier endpoint que requiera auth empieza con:
//   require_once __DIR__ . '/_auth.php';
//   bs_require_auth();
//
// Si no autenticado → 401, exit. Si OK → continúa.
// ============================================================

require_once __DIR__ . '/../auth_check.php';

function bs_require_auth() {
    if (!auth_is_valid()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
        exit;
    }
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
