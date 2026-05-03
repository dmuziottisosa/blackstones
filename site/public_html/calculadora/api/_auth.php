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
