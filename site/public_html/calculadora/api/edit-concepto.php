<?php
// ============================================================
// edit-concepto.php — Editar el campo "concepto" de una sub-versión
// ============================================================
// POST /api/edit-concepto.php
// Body JSON: { "cliente_nro": "0042", "sub": 3, "concepto": "Cocina principal" }
// Response: { "ok": true, "concepto": "Cocina principal" }
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

bs_require_auth();
bs_check_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    bs_error('Solo POST', 405);
}

$body = json_decode(@file_get_contents('php://input'), true);
if (!is_array($body)) bs_error('Body JSON inválido');

$cliente_nro = str_pad((string)intval($body['cliente_nro'] ?? ''), 4, '0', STR_PAD_LEFT);
$sub = intval($body['sub'] ?? 0);
$concepto = trim((string)($body['concepto'] ?? ''));

if (intval($cliente_nro) <= 0) bs_error('cliente_nro inválido');
if ($sub <= 0) bs_error('sub inválido');
if (strlen($concepto) > 200) bs_error('Concepto demasiado largo (max 200 chars)');

$cliente_path = BS_CLIENTES_DIR . '/' . $cliente_nro . '.json';

$lockfile = BS_CLIENTES_DIR . '/.write.lock';
$fp = bs_lock_acquire($lockfile);
if (!$fp) bs_error('No se pudo adquirir lock', 500);

try {
    $cliente_obj = bs_read_json($cliente_path);
    if (!$cliente_obj) {
        bs_lock_release($fp);
        bs_error('Cliente no encontrado', 404);
    }

    $found = false;
    foreach ($cliente_obj['cotizaciones'] as &$cot) {
        if (intval($cot['sub']) === $sub) {
            $cot['concepto'] = $concepto;
            $found = true;
            break;
        }
    }
    unset($cot);

    if (!$found) {
        bs_lock_release($fp);
        bs_error('Sub-versión no encontrada', 404);
    }

    $cliente_obj['ultima_actualizacion'] = date('c');
    if (!bs_write_json_atomic($cliente_path, $cliente_obj)) {
        bs_lock_release($fp);
        bs_error('No se pudo escribir el cliente', 500);
    }

    bs_lock_release($fp);
    bs_ok(['concepto' => $concepto]);

} catch (Throwable $e) {
    bs_lock_release($fp);
    bs_error('Excepción: ' . $e->getMessage(), 500);
}
