<?php
// ============================================================
// edit-cliente.php — Editar campos del cliente (compartidos por sub-versiones)
// ============================================================
// POST /api/edit-cliente.php
// Body JSON: { "cliente_nro": "0042", "nombre": "Carolina Pereyra" }
//   - opcional: "celular", "dni", "direccion", "email"
// Response: { "ok": true, "updated": ["nombre"], "cliente": {...} }
//
// Importante: como el bloque `cliente` vive a nivel ROOT del archivo
// {nro}.json (no por sub-versión), un cambio acá afecta a TODAS las
// cotizaciones del mismo cliente_nro. Eso es correcto: es la misma persona.
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
if (intval($cliente_nro) <= 0) bs_error('cliente_nro inválido');

// Campos editables y sus límites
$EDITABLE = [
    'nombre'    => 120,
    'celular'   => 30,
    'dni'       => 20,
    'direccion' => 200,
    'email'     => 100,
];

$updates = [];
foreach ($EDITABLE as $field => $maxlen) {
    if (!array_key_exists($field, $body)) continue;
    $val = trim((string)$body[$field]);
    if (strlen($val) > $maxlen) bs_error("$field demasiado largo (max $maxlen chars)");
    $updates[$field] = $val;
}

if (empty($updates)) bs_error('No hay campos para actualizar');

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

    if (!isset($cliente_obj['cliente']) || !is_array($cliente_obj['cliente'])) {
        $cliente_obj['cliente'] = [];
    }
    foreach ($updates as $k => $v) {
        $cliente_obj['cliente'][$k] = $v;
    }
    $cliente_obj['ultima_actualizacion'] = date('c');

    if (!bs_write_json_atomic($cliente_path, $cliente_obj)) {
        bs_lock_release($fp);
        bs_error('No se pudo escribir el cliente', 500);
    }

    bs_lock_release($fp);
    bs_ok([
        'updated' => array_keys($updates),
        'cliente' => $cliente_obj['cliente'],
    ]);

} catch (Throwable $e) {
    bs_lock_release($fp);
    bs_error('Excepción: ' . $e->getMessage(), 500);
}
