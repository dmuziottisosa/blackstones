<?php
// ============================================================
// edit-cotizacion.php — Edición rápida de metadata de una cotización
// ============================================================
// POST /api/edit-cotizacion.php
//
// Body JSON (todos los campos son opcionales — solo se actualizan los que vienen):
//   {
//     "cliente_nro": "0042",
//     "sub": 3,
//     "cliente": {                    // opcional, datos del cliente (compartidos por sub-versiones)
//       "nombre":    "Carolina Pereyra",
//       "celular":   "1124981031",
//       "dni":       "28456789",
//       "email":     "...",
//       "direccion": "..."
//     },
//     "concepto":   "Cocina principal",  // opcional, por sub-versión
//     "monto_usd":  1234.56,              // opcional, marca ajuste_manual
//     "monto_ars":  1234567,              // opcional, marca ajuste_manual
//     "fecha":      "2026-05-14"          // opcional, YYYY-MM-DD
//   }
//
// Response:
//   { "ok": true, "cliente_updated": [...], "cotizacion_updated": [...] }
//
// Pensado para el modal de edición rápida del hub /presupuestos/?tab=activos
// cuando NO se quiere abrir la calc entera para tocar 2 campos.
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

bs_require_auth();
bs_check_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') bs_error('Solo POST', 405);

$body = json_decode(@file_get_contents('php://input'), true);
if (!is_array($body)) bs_error('Body JSON inválido');

$cliente_nro = str_pad((string)intval($body['cliente_nro'] ?? ''), 4, '0', STR_PAD_LEFT);
$sub = intval($body['sub'] ?? 0);
if (intval($cliente_nro) <= 0) bs_error('cliente_nro inválido');
if ($sub <= 0) bs_error('sub inválido');

// Cliente-level fields
$CLIENTE_FIELDS = [
    'nombre'    => 120,
    'celular'   => 30,
    'dni'       => 20,
    'direccion' => 200,
    'email'     => 100,
];

$cliente_updates = [];
if (isset($body['cliente']) && is_array($body['cliente'])) {
    foreach ($CLIENTE_FIELDS as $field => $maxlen) {
        if (!array_key_exists($field, $body['cliente'])) continue;
        $val = trim((string)$body['cliente'][$field]);
        if (strlen($val) > $maxlen) bs_error("cliente.$field demasiado largo (max $maxlen chars)");
        $cliente_updates[$field] = $val;
    }
}

// Cotización-level fields
$cot_updates = [];
$marca_ajuste = false;

if (array_key_exists('concepto', $body)) {
    $c = trim((string)$body['concepto']);
    if (strlen($c) > 200) bs_error('concepto demasiado largo (max 200 chars)');
    $cot_updates['concepto'] = $c;
}

// Material final: el material confirmado de la venta entregada. Sobrescribe
// los items del desglose en el top del reporte. Formato libre.
if (array_key_exists('material_final', $body)) {
    $mf = trim((string)$body['material_final']);
    if (strlen($mf) > 200) bs_error('material_final demasiado largo (max 200 chars)');
    $cot_updates['material_final'] = $mf;
}

if (array_key_exists('notas', $body)) {
    $n = trim((string)$body['notas']);
    if (strlen($n) > 2000) bs_error('notas demasiado largas (max 2000 chars)');
    $cot_updates['notas'] = $n;
}

if (array_key_exists('monto_usd', $body)) {
    $cot_updates['monto_usd'] = floatval($body['monto_usd']);
    $marca_ajuste = true;
}

if (array_key_exists('monto_ars', $body)) {
    $cot_updates['monto_ars'] = floatval($body['monto_ars']);
    $marca_ajuste = true;
}

if (array_key_exists('fecha', $body)) {
    $f = trim((string)$body['fecha']);
    if ($f !== '') {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $f)) bs_error('fecha formato YYYY-MM-DD');
        $cot_updates['fecha'] = strpos($f, 'T') !== false ? $f : ($f . 'T12:00:00-03:00');
    }
}

// Fecha de contrato: la fecha real del acuerdo con el cliente, distinta de
// 'fecha' (cuando se guardo el presupuesto). Es la que manda para filtrar y
// ordenar en Activos. Vacia = se usa 'fecha' como fallback.
if (array_key_exists('fecha_contrato', $body)) {
    $fc = trim((string)$body['fecha_contrato']);
    if ($fc === '') {
        $cot_updates['fecha_contrato'] = ''; // vaciar el campo
    } else {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $fc)) bs_error('fecha_contrato formato YYYY-MM-DD');
        $cot_updates['fecha_contrato'] = strpos($fc, 'T') !== false ? $fc : ($fc . 'T12:00:00-03:00');
    }
}

// Pago en efectivo: flag de como se cobro. Vive dentro de 'presupuesto'
// (al lado de 'factura'). Editarlo NO recalcula los montos: solo corrige
// el registro. Para cambiar el precio se editan los montos o se reabre en
// la calculadora.
if (array_key_exists('efectivo', $body)) {
    $cot_updates['efectivo'] = !empty($body['efectivo']);
}

if (array_key_exists('origen', $body)) {
    // Whitelist: solo 'publicidad' o 'local'. Aceptamos 'organico' como
    // alias historico del periodo 1ed8c6f-7c33dd4 y lo normalizamos a
    // 'local' al persistir. Presupuestos viejos sin el campo se asumen
    // 'local' al renderear.
    $o = trim((string)$body['origen']);
    if ($o === 'organico') $o = 'local';
    if (!in_array($o, ['publicidad', 'local'], true)) {
        bs_error("origen debe ser 'publicidad' o 'local'");
    }
    $cot_updates['origen'] = $o;
}

if (empty($cliente_updates) && empty($cot_updates)) {
    bs_error('No hay campos para actualizar');
}

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

    // Actualizar cliente fields (compartidos por todas las sub-versiones)
    if (!empty($cliente_updates)) {
        if (!isset($cliente_obj['cliente']) || !is_array($cliente_obj['cliente'])) {
            $cliente_obj['cliente'] = [];
        }
        foreach ($cliente_updates as $k => $v) {
            $cliente_obj['cliente'][$k] = $v;
        }
    }

    // Actualizar la cotización específica
    if (!empty($cot_updates)) {
        $found = false;
        foreach ($cliente_obj['cotizaciones'] as &$cot) {
            if (intval($cot['sub']) === $sub) {
                if (isset($cot_updates['concepto'])) {
                    $cot['concepto'] = $cot_updates['concepto'];
                }
                if (isset($cot_updates['material_final'])) {
                    if ($cot_updates['material_final'] === '') {
                        unset($cot['material_final']);
                    } else {
                        $cot['material_final'] = $cot_updates['material_final'];
                    }
                }
                if (isset($cot_updates['notas'])) {
                    $cot['notas'] = $cot_updates['notas'];
                }
                if (array_key_exists('efectivo', $cot_updates)) {
                    if (!isset($cot['presupuesto']) || !is_array($cot['presupuesto'])) {
                        $cot['presupuesto'] = [];
                    }
                    $cot['presupuesto']['efectivo'] = $cot_updates['efectivo'];
                }
                if (array_key_exists('fecha_contrato', $cot_updates)) {
                    if ($cot_updates['fecha_contrato'] === '') unset($cot['fecha_contrato']);
                    else $cot['fecha_contrato'] = $cot_updates['fecha_contrato'];
                }
                if (isset($cot_updates['fecha'])) {
                    $cot['fecha'] = $cot_updates['fecha'];
                }
                if (isset($cot_updates['origen'])) {
                    $cot['origen'] = $cot_updates['origen'];
                }
                if (isset($cot_updates['monto_usd']) || isset($cot_updates['monto_ars'])) {
                    if (!isset($cot['presupuesto']) || !is_array($cot['presupuesto'])) {
                        $cot['presupuesto'] = [];
                    }
                    if (!isset($cot['presupuesto']['totales']) || !is_array($cot['presupuesto']['totales'])) {
                        $cot['presupuesto']['totales'] = [];
                    }
                    if (isset($cot_updates['monto_usd'])) {
                        $cot['presupuesto']['totales']['usd'] = $cot_updates['monto_usd'];
                    }
                    if (isset($cot_updates['monto_ars'])) {
                        $cot['presupuesto']['totales']['ars'] = $cot_updates['monto_ars'];
                    }
                }
                if ($marca_ajuste) {
                    $cot['ajuste_manual'] = [
                        'at'   => date('c'),
                        'nota' => 'Monto editado desde hub sin recalcular items',
                    ];
                }
                $found = true;
                break;
            }
        }
        unset($cot);

        if (!$found) {
            bs_lock_release($fp);
            bs_error('Sub-versión no encontrada', 404);
        }
    }

    $cliente_obj['ultima_actualizacion'] = date('c');

    if (!bs_write_json_atomic($cliente_path, $cliente_obj)) {
        bs_lock_release($fp);
        bs_error('No se pudo escribir el cliente', 500);
    }

    bs_lock_release($fp);
    bs_ok([
        'cliente_updated'    => array_keys($cliente_updates),
        'cotizacion_updated' => array_keys($cot_updates),
        'ajuste_manual'      => $marca_ajuste,
    ]);

} catch (Throwable $e) {
    bs_lock_release($fp);
    bs_error('Excepción: ' . $e->getMessage(), 500);
}
