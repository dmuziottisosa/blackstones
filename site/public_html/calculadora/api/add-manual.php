<?php
// ============================================================
// add-manual.php — Alta manual de un entregado histórico
// ============================================================
// POST /api/add-manual.php
//
// Crea un cliente nuevo con una cotización YA en estado "entregado".
// Pensado para cargar ventas que no pasaron por la calculadora (mesadas
// vendidas antes del sistema, ventas rápidas, históricos). Aparece en el
// tab Reporte (live scan) y en la tabla de entregados del hub.
//
// Body JSON:
//   {
//     "nombre":    "Carolina Pereyra",   // requerido
//     "celular":   "1124981031",          // opcional
//     "monto_usd": 1234.56,               // opcional (al menos uno de los dos)
//     "monto_ars": 1234567,               // opcional
//     "fecha":     "2026-05-14",          // opcional, default hoy (= entregado_at)
//     "concepto":  "Cocina principal",    // opcional
//     "origen":    "publicidad"|"local"   // default "local"
//   }
//
// Response: { "ok": true, "cliente_nro": "0044", "sub": 1 }
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

bs_require_auth();
bs_check_csrf();
bs_ensure_dirs();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') bs_error('Solo POST', 405);

$body = json_decode(@file_get_contents('php://input'), true);
if (!is_array($body)) bs_error('Body JSON inválido');

$nombre = trim((string)($body['nombre'] ?? ''));
if ($nombre === '') bs_error('El nombre es requerido');
if (mb_strlen($nombre) > 120) bs_error('nombre demasiado largo');

$celular  = trim((string)($body['celular'] ?? ''));
$concepto = trim((string)($body['concepto'] ?? ''));
if (mb_strlen($concepto) > 200) bs_error('concepto demasiado largo');
$material_final = trim((string)($body['material_final'] ?? ''));
if (mb_strlen($material_final) > 200) bs_error('material_final demasiado largo');

$monto_usd = floatval($body['monto_usd'] ?? 0);
$monto_ars = floatval($body['monto_ars'] ?? 0);
if ($monto_usd <= 0 && $monto_ars <= 0) {
    bs_error('Cargá al menos un monto (USD o ARS)');
}

// Origen: whitelist (mismo criterio que edit-cotizacion.php)
$origen = trim((string)($body['origen'] ?? 'local'));
if ($origen === 'organico') $origen = 'local';
if (!in_array($origen, ['publicidad', 'local'], true)) $origen = 'local';

// Fecha (= fecha de entrega). Default: ahora.
$now = date('c');
$entregado_at = $now;
$fecha = $now;
if (!empty($body['fecha'])) {
    $f = trim((string)$body['fecha']);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $f)) bs_error('fecha formato YYYY-MM-DD');
    $fecha = strpos($f, 'T') !== false ? $f : ($f . 'T12:00:00-03:00');
    $entregado_at = $fecha;
}

$lockfile = BS_CLIENTES_DIR . '/.write.lock';
$fp = bs_lock_acquire($lockfile);
if (!$fp) bs_error('No se pudo adquirir lock', 500);

try {
    // Reservar el próximo nro (mismo criterio que next-nro.php: max de
    // archivos + max del índice de borrados, para no reciclar).
    $max = 0;
    foreach (@scandir(BS_CLIENTES_DIR) ?: [] as $f) {
        if (preg_match('/^(\d{4,})\.json$/', $f, $mm)) {
            $n = intval($mm[1]);
            if ($n > $max) $max = $n;
        }
    }
    $idx = bs_read_json(BS_CLIENTES_INDEX);
    if (is_array($idx)) {
        foreach ($idx as $nro_str => $_v) {
            $n = intval($nro_str);
            if ($n > $max) $max = $n;
        }
    }
    $next = $max + 1;
    if ($next > 9999) {
        bs_lock_release($fp);
        bs_error('Límite de 9999 clientes alcanzado.', 507);
    }
    $target_nro = str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    $cliente_path = BS_CLIENTES_DIR . '/' . $target_nro . '.json';

    $cliente_obj = [
        'version' => defined('BS_CONTRACT_VERSION') ? BS_CONTRACT_VERSION : 1,
        'cliente_nro' => $target_nro,
        'cliente' => [
            'nombre'    => $nombre,
            'dni'       => '',
            'celular'   => $celular,
            'direccion' => '',
            'email'     => '',
        ],
        'primera_cotizacion' => $now,
        'ultima_actualizacion' => $now,
        'cotizaciones' => [
            [
                'sub'      => 1,
                'fecha'    => $fecha,
                'concepto' => $concepto,
                'material_final' => $material_final,
                'estado'   => 'entregado',
                'origen'   => $origen,
                'entregado_at' => $entregado_at,
                'transiciones' => [
                    ['estado' => 'entregado', 'at' => $now],
                ],
                'presupuesto' => [
                    'totales' => [
                        'usd' => $monto_usd,
                        'ars' => $monto_ars,
                    ],
                ],
                // Marca de alta manual: no pasó por la calc, no tiene items.
                'manual' => true,
                'ajuste_manual' => [
                    'at'   => $now,
                    'nota' => 'Entregado cargado manualmente desde el hub',
                ],
                'zip_path' => null,
            ],
        ],
    ];

    if (!bs_write_json_atomic($cliente_path, $cliente_obj)) {
        bs_lock_release($fp);
        bs_error('No se pudo escribir el cliente', 500);
    }

    bs_lock_release($fp);
    bs_ok(['cliente_nro' => $target_nro, 'sub' => 1]);

} catch (Throwable $e) {
    bs_lock_release($fp);
    bs_error('Excepción: ' . $e->getMessage(), 500);
}
