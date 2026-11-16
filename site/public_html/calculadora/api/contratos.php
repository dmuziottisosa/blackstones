<?php
// ============================================================
// contratos.php — Relación-Contrato (lo administrativo de cada obra)
// BlackStones Marmolería
// ============================================================
// Un renglón por contrato: quién es el cliente, cuánto es el total y qué
// se cobró. Nace cuando el cliente dice "contrato" y se le coordina la
// medición, ANTES de que exista una ficha en el tablero.
//
// Guarda sus datos aparte (bs-data/contratos/contratos.json): no toca
// clientes/ ni tablero/. El enlace con el tablero es solo una referencia
// ("cliente_nro-sub"); la ficha del tablero la crea la página con los
// endpoints que ya existen (tablero-cargar.php, tablero.php).
//
// GET  → { ok, hoy, contratos: [ ... ] }   (los más nuevos primero)
// POST → body JSON con "accion":
//   crear          { nro, vendedor, fecha, cliente, contrato, contrato_paralelo, procedencia,
//                    material, medidas, total_usd, total_ars, sena_monto, sena_moneda, sena_fecha,
//                    direccion, telefono, factura, muebles, bacha, bacha_modelo, medicion, nota }
//                  (son los campos de la planilla del dueño, 16/11/2026; "pileta" es bacha)
//   editar         { id, ...los mismos campos }
//   sena           { id, monto, moneda, fecha }      (monto 0 = sin seña)
//   pago           { id, fecha, monto, moneda, medio, nota }
//   borrar_pago    { id, pago_id }
//   borrar         { id }
//   enlazar        { id, cliente_nro, sub }           (la ficha ya existe en el tablero)
//   desenlazar     { id }
//   prueba         {}                                 (carga 3 renglones de prueba)
//   borrar_prueba  {}                                 (borra todos los de prueba)
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

bs_require_auth();
bs_check_csrf();
bs_ensure_dirs();

define('BS_CONTRATOS_DIR',  BS_DATA_DIR . '/contratos');
define('BS_CONTRATOS_FILE', BS_CONTRATOS_DIR . '/contratos.json');
define('BS_CONTRATOS_LOCK', BS_CONTRATOS_DIR . '/.write.lock');
define('BS_CONTRATOS_MAX',  5000);

function bs_contratos_leer() {
    $d = bs_read_json(BS_CONTRATOS_FILE);
    if (!is_array($d) || !isset($d['contratos']) || !is_array($d['contratos'])) $d = ['version' => 1, 'contratos' => []];
    return $d;
}

function bs_contrato_fecha_ok($f) {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string)$f, $m)) return false;
    return checkdate(intval($m[2]), intval($m[3]), intval($m[1]));
}

function bs_contrato_texto($body, $campo, $max, $etiqueta) {
    $v = trim(preg_replace('/\s+/u', ' ', (string)($body[$campo] ?? '')));
    if (mb_strlen($v) > $max) bs_error($etiqueta . ' demasiado largo');
    return $v;
}

// Teléfono: valen "+54 9 11 5555-1234" (como se copia de WhatsApp) y "1155551234".
// Son el mismo número; se guarda "11 5555-1234". Lo que no se reconoce queda como vino.
function bs_contrato_tel($txt) {
    $d = preg_replace('/\D/', '', (string)$txt);
    if (strlen($d) === 13 && strpos($d, '549') === 0) $d = substr($d, 3);
    elseif (strlen($d) === 12 && strpos($d, '54') === 0) $d = substr($d, 2);
    elseif (strlen($d) === 11 && $d[0] === '0') $d = substr($d, 1);
    if (strlen($d) === 12 && strpos($d, '1115') === 0) $d = '11' . substr($d, 4);
    if (strlen($d) === 10) {
        return strpos($d, '11') === 0
            ? '11 ' . substr($d, 2, 4) . '-' . substr($d, 6)
            : substr($d, 0, 3) . ' ' . substr($d, 3, 3) . '-' . substr($d, 6);
    }
    return trim(preg_replace('/\s+/', ' ', preg_replace('/[^\d+()\-\s]/u', '', (string)$txt)));
}

function bs_contrato_monto($body, $campo, $etiqueta) {
    $v = $body[$campo] ?? 0;
    if ($v === '' || $v === null) $v = 0;
    if (!is_numeric($v)) bs_error($etiqueta . ': tiene que ser un número');
    $v = round(floatval($v), 2);
    if ($v < 0 || $v > 999999999999) bs_error($etiqueta . ': monto fuera de rango');
    return $v;
}

// Los campos que se escriben a mano en el renglón (alta y edición).
function bs_contrato_campos($body) {
    $c = [];
    $c['cliente']   = bs_contrato_texto($body, 'cliente', 120, 'Cliente');
    $c['telefono']  = bs_contrato_tel(bs_contrato_texto($body, 'telefono', 60, 'Teléfono'));
    $c['direccion'] = bs_contrato_texto($body, 'direccion', 200, 'Dirección');
    $c['material']  = bs_contrato_texto($body, 'material', 200, 'Material');
    // La nota es el lugar de los detalles: larga y con sus renglones.
    $nota = str_replace(["\r\n", "\r"], "\n", (string)($body['nota'] ?? ''));
    $nota = trim(preg_replace('/\n{3,}/', "\n\n", preg_replace('/[ \t]+/u', ' ', $nota)));
    if (mb_strlen($nota) > 4000) bs_error('La nota es muy larga (máximo 4000 caracteres)');
    $c['nota'] = $nota;
    $c['vendedor']  = bs_contrato_texto($body, 'vendedor', 60, 'Vendedor');
    $c['contrato']  = bs_contrato_texto($body, 'contrato', 80, 'Contrato');
    $c['contrato_paralelo'] = bs_contrato_texto($body, 'contrato_paralelo', 80, 'Contrato paralelo');
    $c['procedencia'] = bs_contrato_texto($body, 'procedencia', 80, 'Procedencia');
    $c['medidas']   = bs_contrato_texto($body, 'medidas', 300, 'Medidas');
    foreach (['factura', 'muebles'] as $k) {
        $v = (string)($body[$k] ?? '');
        $c[$k] = in_array($v, ['si', 'no'], true) ? $v : '';
    }
    // Fecha del contrato: si no viene, hoy.
    $f = trim((string)($body['fecha'] ?? ''));
    if ($f === '') $f = date('Y-m-d');
    if (!bs_contrato_fecha_ok($f)) bs_error('Fecha del contrato inválida');
    $c['fecha'] = $f;
    if ($c['cliente'] === '') bs_error('Falta el nombre del cliente.');

    // N° de presupuesto: no es obligatorio, pero si está tiene que tener forma de número.
    $nro = trim((string)($body['nro'] ?? ''));
    if ($nro !== '') {
        if (!preg_match('/^#?\s*(\d{1,6})(?:\s*-\s*(\d{1,3}))?$/', $nro, $m)) {
            bs_error('El N° de presupuesto va como 0042, o 0042-2 si tiene versiones.');
        }
        $nro = str_pad((string)intval($m[1]), 4, '0', STR_PAD_LEFT) . (isset($m[2]) ? '-' . intval($m[2]) : '');
    }
    $c['nro'] = $nro;

    $bacha = (string)($body['bacha'] ?? '');
    $c['bacha'] = in_array($bacha, ['nuestra', 'cliente', 'no'], true) ? $bacha : '';
    $c['bacha_modelo'] = ($c['bacha'] === 'nuestra') ? bs_contrato_texto($body, 'bacha_modelo', 80, 'Modelo de bacha') : '';

    $med = trim((string)($body['medicion'] ?? ''));
    if ($med !== '' && !bs_contrato_fecha_ok($med)) bs_error('Fecha de medición inválida');
    $c['medicion'] = $med;

    $c['total_usd'] = bs_contrato_monto($body, 'total_usd', 'Total en dólares');
    $c['total_ars'] = bs_contrato_monto($body, 'total_ars', 'Total en pesos');
    $c['sena'] = bs_contrato_sena($body['sena_monto'] ?? 0, $body['sena_moneda'] ?? '', $body['sena_fecha'] ?? '');
    return $c;
}

function bs_contrato_sena($monto, $moneda, $fecha) {
    if ($monto === '' || $monto === null) $monto = 0;
    if (!is_numeric($monto)) bs_error('Seña: tiene que ser un número');
    $monto = round(floatval($monto), 2);
    if ($monto < 0 || $monto > 999999999) bs_error('Seña: monto fuera de rango');
    if ($monto <= 0) return null;
    $fecha = trim((string)$fecha);
    if ($fecha === '' || !bs_contrato_fecha_ok($fecha)) $fecha = date('Y-m-d');
    return ['monto' => $monto, 'moneda' => ($moneda === 'USD') ? 'USD' : 'ARS', 'fecha' => $fecha];
}

function bs_contrato_salida($c) {
    return [
        'id'           => (string)$c['id'],
        'creado'       => (string)($c['creado'] ?? ''),
        'fecha'        => (string)($c['fecha'] ?? substr((string)($c['creado'] ?? ''), 0, 10)),
        'vendedor'     => (string)($c['vendedor'] ?? ''),
        'contrato'     => (string)($c['contrato'] ?? ''),
        'contrato_paralelo' => (string)($c['contrato_paralelo'] ?? ''),
        'procedencia'  => (string)($c['procedencia'] ?? ''),
        'medidas'      => (string)($c['medidas'] ?? ''),
        'factura'      => (string)($c['factura'] ?? ''),
        'muebles'      => (string)($c['muebles'] ?? ''),
        'cliente'      => (string)($c['cliente'] ?? ''),
        'telefono'     => (string)($c['telefono'] ?? ''),
        'direccion'    => (string)($c['direccion'] ?? ''),
        'nro'          => (string)($c['nro'] ?? ''),
        'material'     => (string)($c['material'] ?? ''),
        'bacha'        => (string)($c['bacha'] ?? ''),
        'bacha_modelo' => (string)($c['bacha_modelo'] ?? ''),
        'medicion'     => (string)($c['medicion'] ?? ''),
        'total_usd'    => floatval($c['total_usd'] ?? 0),
        'total_ars'    => floatval($c['total_ars'] ?? 0),
        'sena'         => (is_array($c['sena'] ?? null) && floatval($c['sena']['monto'] ?? 0) > 0) ? $c['sena'] : null,
        'pagos'        => array_values(is_array($c['pagos'] ?? null) ? $c['pagos'] : []),
        'nota'         => (string)($c['nota'] ?? ''),
        'prueba'       => !empty($c['prueba']),
        'enlace'       => is_array($c['enlace'] ?? null) ? $c['enlace'] : null,
    ];
}

function bs_contrato_id() {
    return 'c' . date('ymdHis') . bin2hex(random_bytes(3));
}

// ============================================================
// GET — la lista
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $d = bs_contratos_leer();
    $lista = array_map('bs_contrato_salida', array_values($d['contratos']));
    usort($lista, function ($a, $b) { return strcmp($b['creado'], $a['creado']) ?: strcmp($b['id'], $a['id']); });
    bs_ok(['hoy' => date('Y-m-d'), 'contratos' => $lista]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') bs_error('Solo GET o POST', 405);

$body = json_decode(@file_get_contents('php://input'), true);
if (!is_array($body)) bs_error('Body JSON inválido');
$accion = (string)($body['accion'] ?? '');
$acciones = ['crear', 'editar', 'sena', 'pago', 'borrar_pago', 'borrar', 'enlazar', 'desenlazar', 'prueba', 'borrar_prueba'];
if (!in_array($accion, $acciones, true)) bs_error('accion inválida');

// Todo lo que puede fallar por datos mal cargados se valida ANTES de tomar el lock.
$campos = in_array($accion, ['crear', 'editar'], true) ? bs_contrato_campos($body) : null;
$id = (string)($body['id'] ?? '');
if (!in_array($accion, ['crear', 'prueba', 'borrar_prueba'], true) && !preg_match('/^c[0-9a-f]{6,40}$/', $id)) bs_error('id inválido');

$sena_nueva = null;
if ($accion === 'sena') $sena_nueva = bs_contrato_sena($body['monto'] ?? 0, $body['moneda'] ?? '', $body['fecha'] ?? '');

$pago = null;
if ($accion === 'pago') {
    $monto = bs_contrato_monto($body, 'monto', 'Monto');
    if ($monto <= 0) bs_error('Cargá el monto del pago.');
    $fecha = trim((string)($body['fecha'] ?? ''));
    if ($fecha === '') $fecha = date('Y-m-d');
    if (!bs_contrato_fecha_ok($fecha)) bs_error('Fecha del pago inválida');
    $medio = (string)($body['medio'] ?? '');
    $pago = [
        'id'     => 'p' . bin2hex(random_bytes(4)),
        'fecha'  => $fecha,
        'monto'  => $monto,
        'moneda' => (($body['moneda'] ?? '') === 'USD') ? 'USD' : 'ARS',
        'medio'  => in_array($medio, ['efectivo', 'transferencia'], true) ? $medio : '',
        'nota'   => bs_contrato_texto($body, 'nota', 200, 'Nota del pago'),
    ];
}

$enlace = null;
if ($accion === 'enlazar') {
    $nro = intval($body['cliente_nro'] ?? 0);
    $sub = intval($body['sub'] ?? 0);
    if ($nro <= 0 || $sub <= 0) bs_error('Falta a qué ficha del tablero enlazar.');
    $enlace = ['cliente_nro' => str_pad((string)$nro, 4, '0', STR_PAD_LEFT), 'sub' => $sub, 'at' => date('c')];
}

if (!is_dir(BS_CONTRATOS_DIR) && !@mkdir(BS_CONTRATOS_DIR, 0755, true)) bs_error('No se pudo crear el directorio de contratos', 500);
$fp = bs_lock_acquire(BS_CONTRATOS_LOCK);
if (!$fp) bs_error('No se pudo adquirir lock', 500);

function bs_contratos_salir($fp, $msg, $code = 400) { bs_lock_release($fp); bs_error($msg, $code); }

try {
    $d = bs_contratos_leer();
    $ahora = date('c');
    $extra = [];

    if ($accion === 'crear') {
        if (count($d['contratos']) >= BS_CONTRATOS_MAX) bs_contratos_salir($fp, 'Se llegó al máximo de contratos.', 507);
        $id = bs_contrato_id();
        $d['contratos'][$id] = array_merge($campos, ['id' => $id, 'creado' => $ahora, 'actualizado' => $ahora, 'pagos' => [], 'prueba' => false, 'enlace' => null]);

    } elseif ($accion === 'prueba') {
        foreach ($d['contratos'] as $c) {
            if (!empty($c['prueba'])) bs_contratos_salir($fp, 'Ya hay renglones de prueba cargados.', 409);
        }
        $hoy = date('Y-m-d');
        $pruebas = [
            ['cliente' => 'PRUEBA · Cocina en L', 'telefono' => '11 5550-0001', 'direccion' => 'Calle de prueba 123, CABA', 'nro' => '',
             'fecha' => $hoy, 'vendedor' => 'Prueba', 'contrato' => '', 'contrato_paralelo' => '', 'procedencia' => 'Instagram', 'medidas' => '2,40 x 0,62 + 1,60 x 0,62', 'factura' => 'si', 'muebles' => 'si',
             'material' => 'Granito Nacional · Negro Boreal', 'bacha' => 'nuestra', 'bacha_modelo' => '403-E',
             'medicion' => date('Y-m-d', strtotime('+3 days')), 'total_usd' => 1200, 'total_ars' => 0,
             'sena' => ['monto' => 500, 'moneda' => 'USD', 'fecha' => $hoy], 'pagos' => [], 'nota' => 'Renglón de prueba: se puede borrar.'],
            ['cliente' => 'PRUEBA · Baño doble', 'telefono' => '11 5550-0002', 'direccion' => 'Calle de prueba 456, Lanús', 'nro' => '',
             'fecha' => $hoy, 'vendedor' => 'Prueba', 'contrato' => '', 'contrato_paralelo' => '', 'procedencia' => 'Recomendación', 'medidas' => '1,20 x 0,50', 'factura' => 'no', 'muebles' => 'no',
             'material' => 'Purastone · Blanco Paloma (N y J)', 'bacha' => 'cliente', 'bacha_modelo' => '',
             'medicion' => '', 'total_usd' => 0, 'total_ars' => 850000,
             'sena' => null, 'pagos' => [], 'nota' => 'Renglón de prueba: sin seña y sin medición coordinada.'],
            ['cliente' => 'PRUEBA · Isla y mesada', 'telefono' => '11 5550-0003', 'direccion' => 'Calle de prueba 789, Quilmes', 'nro' => '',
             'fecha' => $hoy, 'vendedor' => 'Prueba', 'contrato' => '', 'contrato_paralelo' => '', 'procedencia' => 'Local', 'medidas' => 'Isla 1,80 x 0,90 + mesada 3,10 x 0,62', 'factura' => '', 'muebles' => 'si',
             'material' => 'Silestone · Blanco Zeus', 'bacha' => 'no', 'bacha_modelo' => '',
             'medicion' => date('Y-m-d', strtotime('+7 days')), 'total_usd' => 2100, 'total_ars' => 300000,
             'sena' => ['monto' => 300000, 'moneda' => 'ARS', 'fecha' => $hoy],
             'pagos' => [['id' => 'p' . bin2hex(random_bytes(4)), 'fecha' => $hoy, 'monto' => 600, 'moneda' => 'USD', 'medio' => 'efectivo', 'nota' => 'Pago de prueba']],
             'nota' => 'Renglón de prueba: total en dólares y pesos.'],
        ];
        foreach ($pruebas as $i => $p) {
            $id = bs_contrato_id() . $i;
            // 'creado' escalonado para que queden en este orden en la lista
            $d['contratos'][$id] = array_merge($p, ['id' => $id, 'creado' => date('c', time() - $i), 'actualizado' => $ahora, 'prueba' => true, 'enlace' => null]);
        }

    } elseif ($accion === 'borrar_prueba') {
        // Devuelve qué fichas del tablero se habían creado desde esos renglones: la página las borra.
        $enlaces = [];
        foreach ($d['contratos'] as $k => $c) {
            if (empty($c['prueba'])) continue;
            if (is_array($c['enlace'] ?? null)) $enlaces[] = ['cliente_nro' => $c['enlace']['cliente_nro'], 'sub' => $c['enlace']['sub']];
            unset($d['contratos'][$k]);
        }
        $extra['enlaces'] = $enlaces;

    } else {
        if (!isset($d['contratos'][$id])) bs_contratos_salir($fp, 'Ese contrato ya no existe. Actualizá la página.', 404);
        $c = $d['contratos'][$id];

        if ($accion === 'editar') {
            $c = array_merge($c, $campos);
        } elseif ($accion === 'sena') {
            $c['sena'] = $sena_nueva;
        } elseif ($accion === 'pago') {
            if (!is_array($c['pagos'] ?? null)) $c['pagos'] = [];
            if (count($c['pagos']) >= 200) bs_contratos_salir($fp, 'Demasiados pagos en un mismo contrato.');
            $c['pagos'][] = $pago;
        } elseif ($accion === 'borrar_pago') {
            $pid = (string)($body['pago_id'] ?? '');
            $c['pagos'] = array_values(array_filter((array)($c['pagos'] ?? []), function ($p) use ($pid) { return ($p['id'] ?? '') !== $pid; }));
        } elseif ($accion === 'enlazar') {
            foreach ($d['contratos'] as $k => $otro) {
                if ($k === $id || !is_array($otro['enlace'] ?? null)) continue;
                if ($otro['enlace']['cliente_nro'] === $enlace['cliente_nro'] && intval($otro['enlace']['sub']) === $enlace['sub']) {
                    bs_contratos_salir($fp, 'Esa ficha del tablero ya está enlazada con otro contrato (' . ($otro['cliente'] ?? '') . ').', 409);
                }
            }
            $c['enlace'] = $enlace;
        } elseif ($accion === 'desenlazar') {
            $c['enlace'] = null;
        }

        if ($accion === 'borrar') {
            unset($d['contratos'][$id]);
        } else {
            $c['actualizado'] = $ahora;
            $d['contratos'][$id] = $c;
        }
    }

    if (!bs_write_json_atomic(BS_CONTRATOS_FILE, $d)) bs_contratos_salir($fp, 'No se pudo guardar', 500);
    bs_lock_release($fp);
    bs_ok(array_merge(['id' => $id], $extra));

} catch (Throwable $e) {
    bs_lock_release($fp);
    bs_error('Excepción: ' . $e->getMessage(), 500);
}
