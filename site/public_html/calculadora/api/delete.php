<?php
// ============================================================
// delete.php — Eliminación manual de una cotización (sub-versión)
// ============================================================
// POST /api/delete.php
// Body JSON:
//   {
//     "cliente_nro": "0042",
//     "sub": 3
//   }
//
// Comportamiento:
//   - Borra esa sub-versión del array de cotizaciones.
//   - Si el cliente.json queda con 0 cotizaciones → borra el archivo entero.
//
// Response: { "ok": true, "cliente_borrado": true|false }
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

if (intval($cliente_nro) <= 0) bs_error('cliente_nro inválido');
if ($sub <= 0) bs_error('sub inválido');

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

    $kept = [];
    $found = false;
    foreach ($cliente_obj['cotizaciones'] as $cot) {
        if (intval($cot['sub']) === $sub) {
            $found = true;
            // Borrar el ZIP asociado si existe
            if (!empty($cot['zip_path'])) {
                $zip = BS_DATA_DIR . '/' . $cot['zip_path'];
                if (file_exists($zip)) @unlink($zip);
            }
            continue;
        }
        $kept[] = $cot;
    }

    if (!$found) {
        bs_lock_release($fp);
        bs_error('Sub-versión no encontrada', 404);
    }

    $cliente_borrado = false;

    if (count($kept) === 0) {
        // Cliente queda vacío → borrar archivo entero
        @unlink($cliente_path);
        $cliente_borrado = true;
        // Preservar el nro en clientes-index.json para no reciclarlo
        $idx = bs_read_json(BS_CLIENTES_INDEX);
        if (!is_array($idx)) $idx = [];
        $idx[$cliente_nro] = [
            'deleted_at' => date('c'),
            'reason'     => 'manual',
        ];
        bs_write_json_atomic(BS_CLIENTES_INDEX, $idx);
    } else {
        $cliente_obj['cotizaciones'] = $kept;
        $cliente_obj['ultima_actualizacion'] = date('c');
        if (!bs_write_json_atomic($cliente_path, $cliente_obj)) {
            bs_lock_release($fp);
            bs_error('No se pudo escribir el cliente', 500);
        }
    }

    bs_lock_release($fp);
    bs_ok(['cliente_borrado' => $cliente_borrado]);

} catch (Throwable $e) {
    bs_lock_release($fp);
    bs_error('Excepción: ' . $e->getMessage(), 500);
}
