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
// GET ?verificar=0042 → { ok, presup }  (¿existe ese N° en Presupuestos? solo informa)
// GET ?enlace=0042-1  → { ok, contrato }  (el contrato enlazado con esa ficha del tablero, o null)
// GET ?papelera=1     → { ok, dias, contratos }  (los borrados que todavía se pueden recuperar)
//   restaurar          { id }                         (saca un contrato de la papelera)
//   restaurar_enlazado { cliente_nro, sub }           (ídem, por la ficha con la que estaba enlazado)
//   mudar_enlazado { de:{cliente_nro,sub}, a:{cliente_nro,sub} }  (el tablero avisa que la ficha cambió de N°: el contrato la sigue y toma ese N°)
//   de_ficha       { cliente_nro, sub }               (el tablero avisa que cambió la seña o la nota de esa ficha: su contrato las toma)
//   de_fichas      {}                                 (lo mismo para todos los contratos enlazados; lo pide esta página al abrirse)
//
// LA SEÑA Y LA NOTA DE LA FICHA PASAN AL CONTRATO (dueño, 19/11/2026; cambia la regla del 17/11 de
// "no se pisan nunca"). El servidor lee la ficha él mismo: nadie le dicta un monto por el pedido.
// Qué pasa y qué no, en bs_contrato_traer().
//
// LAS NOTAS SE VEN EN LOS DOS LADOS (dueño, 25/11/2026). La de la ficha ya llegaba al contrato como
// el renglón "Tablero: ..."; ahora, si se borra en la ficha, ese renglón se va. La del contrato se
// ve en la ficha del tablero como "Contrato: ...": el tablero la pide con GET ?enlaces=1 → "notas",
// que sale de bs_contrato_nota_propia() y no se guarda en la ficha (borrada acá, allá deja de verse).
//
// PAPELERA (30 días, decisión del dueño 16/11/2026): borrar un contrato no lo destruye. Pasa a
// "papelera" dentro del mismo archivo, con sus pagos, y su presupuesto adjunto queda en disco.
// A los 30 días se borra de verdad. Los renglones de prueba no pasan por la papelera.
//   borrar_enlazado { cliente_nro, sub }              (borra el contrato enlazado con esa ficha)
//
// Enlazados, la ficha se borra en los dos lados (regla del dueño, 16/11/2026): borrar el
// contrato borra la ficha del tablero (lo hace la página) y borrar la ficha del tablero
// borra el contrato (el tablero llama a borrar_enlazado; si Relación-Contrato no existe,
// esa llamada falla y el tablero sigue como si nada).
//
// El PDF o Excel del presupuesto se sube y se baja por contratos-adjunto.php.
// Un contrato queda VERIFICADO solo cuando tiene ese archivo adjunto.
//   borrar_prueba  {}                                 (borra todos los de prueba)
// ============================================================

require_once __DIR__ . '/_contratos.php';
require_once __DIR__ . '/_auth.php';
// Para listar las fotos y planos de la ficha enlazada (bs_tablero_adjuntos). Solo se usa para leer;
// si el archivo no está, los contratos salen sin fotos y todo lo demás anda igual.
if (is_file(__DIR__ . '/_tablero.php')) require_once __DIR__ . '/_tablero.php';

// Ver: administradores y operadores. Cambiar: solo administradores, salvo lo que el tablero
// dispara al borrar, recuperar o cambiarle el N° a una ficha enlazada (borrar_enlazado /
// restaurar_enlazado / mudar_enlazado),
// que lo puede quien opera el tablero.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    bs_require_permiso('contratos');
} else {
    $bs_pre = json_decode((string)@file_get_contents('php://input'), true);
    $bs_acc = is_array($bs_pre) ? (string)($bs_pre['accion'] ?? '') : '';
    // los renglones de prueba son de mantenimiento: solo administradores (el Operador edita contratos desde el 18/11/2026)
    bs_require_permiso(in_array($bs_acc, ['borrar_enlazado', 'restaurar_enlazado', 'mudar_enlazado', 'nro_de_ficha', 'de_ficha'], true) ? 'tablero_operar' : (in_array($bs_acc, ['prueba', 'borrar_prueba'], true) ? 'mantenimiento' : 'contratos_editar'));
}
bs_check_csrf();
bs_ensure_dirs();

function bs_contrato_texto($body, $campo, $max, $etiqueta) {
    $v = trim(preg_replace('/\s+/u', ' ', (string)($body[$campo] ?? '')));
    if (mb_strlen($v) > $max) bs_error($etiqueta . ' demasiado largo');
    return $v;
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
    $c['procedencia'] = bs_contrato_procedencia(bs_contrato_texto($body, 'procedencia', 80, 'Procedencia')); // una opción vieja (pestaña sin recargar) entra con su nombre nuevo
    $c['medidas']   = bs_contrato_texto($body, 'medidas', 300, 'Medidas');
    $v = (string)($body['factura'] ?? '');
    $c['factura'] = in_array($v, ['si', 'no', 'cf'], true) ? $v : ''; // cf = consumidor final
    $v = (string)($body['muebles'] ?? '');
    $c['muebles'] = in_array($v, ['si', 'no'], true) ? $v : '';
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
    $c['sena'] = bs_contrato_sena($body['sena_monto'] ?? 0, $body['sena_moneda'] ?? '', $body['sena_fecha'] ?? '', $body['sena_cotizacion'] ?? '', $body['sena_aplica'] ?? '');
    return $c;
}

/**
 * Cotización de un cobro. Hace falta cuando se cobra en una moneda y se descuenta de un total
 * en la otra (lo habitual: contrato en pesos, pago en dólares). "aplica" es de qué total se
 * descuenta; si es la misma moneda del cobro, no hay conversión.
 * Devuelve ['cotizacion' => float|0, 'aplica' => 'USD'|'ARS'].
 */
function bs_contrato_cambio($moneda, $cotizacion, $aplica, $etiqueta) {
    $aplica = ($aplica === 'USD' || $aplica === 'ARS') ? $aplica : $moneda;
    if ($aplica === $moneda) return ['cotizacion' => 0, 'aplica' => $moneda];
    if ($cotizacion === '' || $cotizacion === null || !is_numeric($cotizacion)) bs_error($etiqueta . ': falta la cotización del dólar.');
    $cot = round(floatval($cotizacion), 2);
    if ($cot < 1 || $cot > 1000000) bs_error($etiqueta . ': la cotización del dólar no parece correcta.');
    return ['cotizacion' => $cot, 'aplica' => $aplica];
}

function bs_contrato_sena($monto, $moneda, $fecha, $cotizacion = '', $aplica = '') {
    if ($monto === '' || $monto === null) $monto = 0;
    if (!is_numeric($monto)) bs_error('Seña: tiene que ser un número');
    $monto = round(floatval($monto), 2);
    if ($monto < 0 || $monto > 999999999) bs_error('Seña: monto fuera de rango');
    if ($monto <= 0) return null;
    $fecha = trim((string)$fecha);
    if ($fecha === '' || !bs_contrato_fecha_ok($fecha)) $fecha = date('Y-m-d');
    $moneda = ($moneda === 'USD') ? 'USD' : 'ARS';
    return array_merge(['monto' => $monto, 'moneda' => $moneda, 'fecha' => $fecha], bs_contrato_cambio($moneda, $cotizacion, $aplica, 'Seña'));
}

function bs_contrato_salida($c) {
    global $BS_CONTRATO_VENDEDORES, $BS_CONTRATO_PROCEDENCIAS;
    // Los renglones de prueba cargados antes de que existieran las listas traían el vendedor
    // "Prueba" y procedencias inventadas: no son de la lista, así que salen vacíos.
    // La procedencia sale con el nombre de hoy (dos opciones desde el 20/11/2026): lo guardado con uno de
    // los cinco nombres de antes se traduce acá, sin tocar el archivo.
    $c['procedencia'] = bs_contrato_procedencia($c['procedencia'] ?? '');
    if (!empty($c['prueba'])) {
        if (!in_array((string)($c['vendedor'] ?? ''), $BS_CONTRATO_VENDEDORES, true)) $c['vendedor'] = '';
        if (!in_array((string)($c['procedencia'] ?? ''), $BS_CONTRATO_PROCEDENCIAS, true)) $c['procedencia'] = '';
    }
    $s = [
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
        // Qué pasó con la ficha enlazada: 'viva', 'archivada', 'papelera' o 'no_existe' ('' si no hay enlace).
        'ficha'        => ($bs_ficha = bs_contrato_ficha_estado($c['enlace'] ?? null)),
        // Las fotos y planos de esa ficha (plano, bacha, entrega): se ven también desde el contrato.
        'adjuntos'     => bs_contrato_adjuntos($c['enlace'] ?? null, $bs_ficha),
        // El presupuesto adjunto: con archivo, el contrato está verificado.
        'archivo'      => bs_contrato_archivo($c),
        // Si el N° existe en Presupuestos (solo informa; no verifica).
        'presup'       => bs_contrato_presup((string)($c['nro'] ?? '')),
    ];
    // ¿La ficha tiene una seña o una nota que este contrato todavía no miró? La página pide "de_fichas".
    $s['traer'] = $s['ficha'] === 'viva' && bs_contrato_traer($c, bs_contrato_obra($c['enlace']))[1];
    // Sin permiso para ver la plata, los montos no salen del servidor.
    if (!auth_puede('contratos_plata')) {
        $s['total_usd'] = 0; $s['total_ars'] = 0; $s['sena'] = null; $s['pagos'] = [];
        $s['nota'] = auth_tapar_plata($s['nota']); // la nota es texto libre: se le tapan los montos
        if (is_array($s['presup'])) { $s['presup']['total_usd'] = 0; $s['presup']['total_ars'] = 0; }
    }
    return $s;
}

/**
 * ¿Existe ese N° en Presupuestos? Solo lee clientes/NNNN.json; no lo modifica.
 * Devuelve null si no hay N°; si no, ['existe', 'cliente_nro', 'sub', 'cliente', 'total_usd', 'total_ars', 'versiones', 'detalle'].
 */
function bs_contrato_presup($nro) {
    if (!preg_match('/^(\d{1,6})(?:-(\d{1,3}))?$/', $nro, $m)) return null;
    $n = str_pad((string)intval($m[1]), 4, '0', STR_PAD_LEFT);
    $pedido = isset($m[2]) ? intval($m[2]) : 0;
    $no = ['existe' => false, 'cliente_nro' => $n, 'sub' => $pedido];
    $cli = bs_read_json(BS_CLIENTES_DIR . '/' . $n . '.json');
    $cots = is_array($cli) ? ($cli['cotizaciones'] ?? []) : [];
    if (!is_array($cots) || !$cots) return $no;
    $cot = null;
    foreach ($cots as $x) {
        // eliminado de Presupuestos (a mano o por los 180 días): para Presupuestos ya no existe
        if (!empty($x['fuera_de_presupuestos']) && ($x['estado'] ?? '') !== 'entregado') continue;
        if (($x['estado'] ?? '') === 'papelera') continue; // ficha en la papelera del tablero
        if (!empty($x['manual']) && ($x['estado'] ?? '') === 'aprobado') continue; // ficha a mano: no es un presupuesto
        $s = intval($x['sub'] ?? 0);
        if ($pedido ? $s === $pedido : ($cot === null || $s > intval($cot['sub'] ?? 0))) $cot = $x;
    }
    if ($cot === null) return $no;
    $tot = $cot['presupuesto']['totales'] ?? [];
    return [
        'existe'      => true,
        'cliente_nro' => $n,
        'sub'         => intval($cot['sub'] ?? 0),
        'cliente'     => (string)($cli['cliente']['nombre'] ?? ''),
        'total_usd'   => floatval($tot['usd'] ?? 0),
        'total_ars'   => floatval($tot['ars'] ?? 0),
        'versiones'   => count($cots),
        'detalle'     => empty($cot['manual']) && isset($cot['presupuesto']) && is_array($cot['presupuesto']),
    ];
}

/**
 * Dónde está hoy la ficha con la que el contrato está enlazado. Solo lee clientes/NNNN.json.
 * 'viva' (en el tablero) · 'archivada' (entregada) · 'papelera' · 'no_existe' (borrada, o le cambiaron el N°).
 */
function bs_contrato_ficha_estado($enlace) {
    if (!is_array($enlace)) return '';
    $cli = bs_read_json(BS_CLIENTES_DIR . '/' . str_pad((string)intval($enlace['cliente_nro'] ?? 0), 4, '0', STR_PAD_LEFT) . '.json');
    foreach ((array)(is_array($cli) ? ($cli['cotizaciones'] ?? []) : []) as $x) {
        if (intval($x['sub'] ?? 0) !== intval($enlace['sub'] ?? 0)) continue;
        $e = (string)($x['estado'] ?? '');
        if ($e === 'aprobado') return 'viva';
        if ($e === 'entregado') return 'archivada';
        if ($e === 'papelera') return 'papelera';
        return 'no_existe'; // el presupuesto existe, pero ya no es una obra del tablero
    }
    return 'no_existe';
}

/**
 * Las fotos y planos de la ficha enlazada (plano, bacha, entrega), para verlas desde el contrato
 * (dueño, 25/11/2026: "lo debo poder ver en la ficha de Relación-Contrato también").
 * Es solo la lista, y solo para mirar: [{id, tipo, pdf, link, at}]. El archivo lo entrega
 * tablero-adjunto.php con su propio permiso, así que la lista sale únicamente para quien puede
 * ver el tablero. Hay lista mientras la ficha exista con sus adjuntos: viva o archivada (una
 * archivada los guarda un año). De una ficha en la papelera no sale nada.
 */
function bs_contrato_adjuntos($enlace, $estado) {
    if (!in_array($estado, ['viva', 'archivada'], true)) return [];
    if (!function_exists('bs_tablero_adjuntos') || !auth_puede('tablero')) return [];
    $obra = bs_contrato_obra($enlace);
    if (!is_array($obra)) return [];
    $out = [];
    foreach (bs_tablero_adjuntos($obra) as $a) {
        $at = substr((string)$a['at'], 0, 10);
        $out[] = ['id' => $a['id'], 'tipo' => $a['tipo'], 'pdf' => $a['ext'] === 'pdf', 'link' => $a['ext'] === 'link' ? $a['url'] : '',
            'at' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $at) ? $at : ''];
    }
    return $out;
}

/** El contrato enlazado con esa ficha del tablero: su clave en el archivo, o null. */
function bs_contrato_de_ficha($d, $nro, $sub) {
    $n = str_pad((string)intval($nro), 4, '0', STR_PAD_LEFT);
    foreach ($d['contratos'] as $k => $c) {
        $e = $c['enlace'] ?? null;
        if (is_array($e) && (string)($e['cliente_nro'] ?? '') === $n && intval($e['sub'] ?? 0) === intval($sub)) return $k;
    }
    return null;
}

// Cómo se escribe en la nota del contrato el renglón que llega de la ficha.
define('BS_CONTRATO_NOTA_FICHA', 'Tablero: ');

/**
 * La nota propia del contrato: lo que se escribió en Relación-Contrato, sin los renglones que
 * llegaron de la ficha ("Tablero: ..."). Es lo que el tablero muestra en la ficha (dueño,
 * 25/11/2026: "debería poder ver las notas que pongo en relación de contrato, en tablero y
 * viceversa"). Lo que vino del tablero no vuelve al tablero. No se guarda en ningún lado: se lee
 * de la nota cada vez, así que si la nota del contrato se borra, en el tablero deja de verse.
 * Tampoco sale el renglón que pone sola la importación al crear un contrato desde su ficha
 * ("Creado desde la ficha del tablero...", contratos-importar.php): no lo escribió nadie, es un aviso
 * para completar el contrato, y la planilla de Relación-Contrato tampoco lo muestra.
 */
function bs_contrato_nota_propia($c) {
    $fuera = [mb_strtolower(rtrim(BS_CONTRATO_NOTA_FICHA)), 'creado desde la ficha del tablero'];
    $out = [];
    foreach (explode("\n", (string)($c['nota'] ?? '')) as $l) {
        $m = mb_strtolower(ltrim($l));
        foreach ($fuera as $f) { if (mb_strpos($m, $f) === 0) continue 2; }
        $out[] = rtrim($l);
    }
    return trim((string)preg_replace('/\n{3,}/', "\n\n", implode("\n", $out)));
}

/** La ficha del tablero enlazada con ese contrato (su renglón en obras.json, esté viva, archivada o en la papelera), o null. Solo lee. */
function bs_contrato_obra($enlace) {
    static $obras = null;
    if (!is_array($enlace)) return null;
    if ($obras === null) {
        $t = bs_read_json(BS_DATA_DIR . '/tablero/obras.json');
        $obras = (is_array($t) && is_array($t['obras'] ?? null)) ? $t['obras'] : [];
    }
    $k = str_pad((string)intval($enlace['cliente_nro'] ?? 0), 4, '0', STR_PAD_LEFT) . '-' . intval($enlace['sub'] ?? 0);
    return is_array($obras[$k] ?? null) ? $obras[$k] : null;
}

/** La seña dicha en una línea, para saber si la de la ficha cambió desde la última vez que se miró. */
function bs_contrato_sena_firma($s) {
    return is_array($s) ? rtrim(rtrim(number_format(floatval($s['monto']), 2, '.', ''), '0'), '.') . ' ' . $s['moneda'] : '';
}

/**
 * La seña y la nota de la ficha pasan a su contrato (dueño, 19/11/2026).
 *
 * SEÑA. Cada seña que se carga o se cambia en el tablero viaja UNA vez al contrato:
 *   - el contrato no tiene seña ni pagos            → la toma;
 *   - el contrato ya tomó una de la ficha y cambió  → toma la nueva (conserva la cotización si es la misma moneda);
 *   - primera vez que se miran y los dos tienen una → no se toca: sigue el aviso (eran datos cargados a mano en los dos lados);
 *   - el contrato no tiene seña pero sí pagos       → no se toca: la seña puede ser uno de esos pagos y se contaría dos veces;
 *   - la seña entró en una moneda y el contrato solo tiene total en la otra → no se copia: hace falta la
 *     cotización, y esa la pone una persona (fue el error del 16/11: dólares sin cotización contra un total en pesos).
 * Destildar la seña en el tablero no borra la del contrato. Lo que después se corrija a mano en el
 * contrato queda, hasta que alguien vuelva a cambiar la seña en el tablero.
 *
 * NOTA. La nota de la ficha queda como un renglón al final de la nota del contrato ("Tablero: ...").
 * Si la nota de la ficha se edita, se reemplaza ese renglón (no se apila una copia por cada tecla);
 * si se borra, su renglón se va del contrato (dueño, 25/11/2026: "si borro, se debe borrar su
 * append correspondiente"; hasta ese día quedaba). Lo escrito a mano en el contrato no se toca,
 * y un renglón que alguien ya cambió a mano en el contrato tampoco: dejó de ser la copia.
 * Al revés, la nota del contrato se ve en la ficha sin guardarse en ella: bs_contrato_nota_propia().
 *
 * "sena_ficha" y "nota_ficha" anotan lo último que se miró de la ficha; no se muestran.
 * Devuelve [contrato, hay_que_guardar, ['sena' => ''|'copiada'|'cotizacion', 'nota' => bool]].
 */
function bs_contrato_traer($c, $obra) {
    $info = ['sena' => '', 'nota' => false];
    $guardar = false;

    $s = null;
    $x = is_array($obra) ? ($obra['sena'] ?? null) : null;
    if (is_array($x) && floatval($x['monto'] ?? 0) > 0) {
        $s = ['monto' => round(floatval($x['monto']), 2), 'moneda' => (($x['moneda'] ?? '') === 'USD') ? 'USD' : 'ARS', 'fecha' => substr((string)($x['at'] ?? ''), 0, 10)];
    }
    $firma = bs_contrato_sena_firma($s);
    $vista = array_key_exists('sena_ficha', $c);
    if (!$vista || (string)$c['sena_ficha'] !== $firma) {
        $guardar = true;
        if ($s) {
            $vieja = (is_array($c['sena'] ?? null) && floatval($c['sena']['monto'] ?? 0) > 0) ? $c['sena'] : null;
            $igual = $vieja && ($vieja['moneda'] ?? '') === $s['moneda'] && abs(floatval($vieja['monto']) - $s['monto']) < 0.005;
            $pagos = is_array($c['pagos'] ?? null) ? count($c['pagos']) : 0;
            if ($vieja ? ($vista && !$igual) : ($pagos === 0)) {
                $otra = $s['moneda'] === 'USD' ? 'ARS' : 'USD';
                $tot = ['USD' => floatval($c['total_usd'] ?? 0), 'ARS' => floatval($c['total_ars'] ?? 0)];
                $con_cot = $vieja && ($vieja['moneda'] ?? '') === $s['moneda'] && floatval($vieja['cotizacion'] ?? 0) > 0;
                if ($tot[$s['moneda']] <= 0 && $tot[$otra] > 0 && !$con_cot) {
                    $info['sena'] = 'cotizacion';
                } else {
                    $c['sena'] = [
                        'monto' => $s['monto'], 'moneda' => $s['moneda'],
                        'fecha' => bs_contrato_fecha_ok($s['fecha']) ? $s['fecha'] : date('Y-m-d'),
                        'cotizacion' => $con_cot ? floatval($vieja['cotizacion']) : 0,
                        'aplica' => $con_cot ? (string)($vieja['aplica'] ?? $s['moneda']) : $s['moneda'],
                    ];
                    $info['sena'] = 'copiada';
                }
            }
        }
        $c['sena_ficha'] = $firma;
    }

    $n = trim(preg_replace('/\s+/u', ' ', is_array($obra) ? (string)($obra['nota'] ?? '') : ''));
    $vista = array_key_exists('nota_ficha', $c);
    $antes = $vista ? (string)$c['nota_ficha'] : '';
    if (!$vista || $antes !== $n) {
        $guardar = true;
        $nota = (string)($c['nota'] ?? '');
        $lineas = $nota === '' ? [] : explode("\n", $nota);
        $igual = function ($a, $b) { return mb_strtolower(trim($a)) === mb_strtolower(trim($b)); };
        if ($n !== '') {
            $donde = -1; $ya = false;
            foreach ($lineas as $i => $l) {
                if ($antes !== '' && $donde < 0 && $igual($l, BS_CONTRATO_NOTA_FICHA . $antes)) $donde = $i;
                if ($igual($l, $n) || $igual($l, BS_CONTRATO_NOTA_FICHA . $n)) $ya = true;
            }
            if ($donde >= 0) $lineas[$donde] = BS_CONTRATO_NOTA_FICHA . $n;
            elseif (!$ya) $lineas[] = BS_CONTRATO_NOTA_FICHA . $n;
            $nueva = implode("\n", $lineas);
            if ($nueva !== $nota && mb_strlen($nueva) <= 4000) { $c['nota'] = $nueva; $info['nota'] = true; }
        } elseif ($antes !== '') {
            // La nota de la ficha se borró: su renglón se va del contrato. Solo ese (el primero que diga
            // exactamente "Tablero: <lo que decía>"); el resto de la nota queda letra por letra.
            foreach ($lineas as $i => $l) {
                if (!$igual($l, BS_CONTRATO_NOTA_FICHA . $antes)) continue;
                unset($lineas[$i]);
                $c['nota'] = trim((string)preg_replace('/\n{3,}/', "\n\n", implode("\n", $lineas)));
                $info['nota'] = true;
                break;
            }
        }
        $c['nota_ficha'] = $n;
    }
    return [$c, $guardar, $info];
}

/** Papelera: saca del archivo (y del disco) lo que lleva más de 30 días borrado. Devuelve si cambió algo. */
function bs_contratos_purgar_papelera(&$d) {
    if (!isset($d['papelera']) || !is_array($d['papelera'])) { $d['papelera'] = []; return false; }
    $limite = time() - BS_CONTRATOS_PAPELERA_DIAS * 86400;
    $cambio = false;
    foreach ($d['papelera'] as $k => $c) {
        $at = strtotime((string)($c['borrado_at'] ?? ''));
        if ($at && $at >= $limite) continue;
        bs_contrato_adj_borrar((string)$k);
        unset($d['papelera'][$k]);
        $cambio = true;
    }
    return $cambio;
}

/** Manda un contrato a la papelera (se llama con el lock tomado). */
function bs_contrato_a_papelera(&$d, $k) {
    if (!isset($d['papelera']) || !is_array($d['papelera'])) $d['papelera'] = [];
    $c = $d['contratos'][$k];
    $c['borrado_at'] = date('c');
    $d['papelera'][$k] = $c;
    unset($d['contratos'][$k]);
}

function bs_contrato_id() {
    return 'c' . date('ymdHis') . bin2hex(random_bytes(3));
}

// ============================================================
// GET — la lista
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['verificar'])) {
        bs_require_permiso('contratos_editar');
        $nro = trim((string)$_GET['verificar']);
        if (!preg_match('/^#?\s*(\d{1,6})(?:\s*-\s*(\d{1,3}))?$/', $nro, $m)) bs_error('El N° de presupuesto va como 0042, o 0042-2 si tiene versiones.');
        bs_ok(['presup' => bs_contrato_presup(intval($m[1]) . (isset($m[2]) ? '-' . intval($m[2]) : ''))]);
    }
    $d = bs_contratos_leer();
    if (isset($_GET['enlaces'])) {
        // Lo consulta el tablero al cargar, para marcar "Con contrato" en la ficha abierta, y para
        // mostrar en la ficha la nota de su contrato ("notas": ficha → texto; solo las que tienen algo).
        // Es de solo lectura. Como en la lista, a quien no ve la plata se le tapan los montos.
        $enl = []; $notas = [];
        $ve_plata = auth_puede('contratos_plata');
        foreach ($d['contratos'] as $c) {
            if (!is_array($c['enlace'] ?? null)) continue;
            $k = $c['enlace']['cliente_nro'] . '-' . intval($c['enlace']['sub']);
            $enl[$k] = (string)$c['id'];
            $n = bs_contrato_nota_propia($c);
            if ($n !== '') $notas[$k] = $ve_plata ? $n : auth_tapar_plata($n);
        }
        bs_ok(['enlaces' => (object)$enl, 'notas' => (object)$notas]);
    }
    if (isset($_GET['enlace'])) {
        if (!preg_match('/^(\d{1,6})-(\d{1,3})$/', (string)$_GET['enlace'], $m)) bs_error('enlace inválido');
        $k = bs_contrato_de_ficha($d, intval($m[1]), intval($m[2]));
        $c = $k === null ? null : $d['contratos'][$k];
        bs_ok(['contrato' => $c === null ? null : [
            'id'      => (string)$c['id'],
            'cliente' => (string)($c['cliente'] ?? ''),
            'pagos'   => count(is_array($c['pagos'] ?? null) ? $c['pagos'] : []) + (is_array($c['sena'] ?? null) ? 1 : 0),
            'archivo' => bs_contrato_archivo($c) !== null,
        ]]);
    }
    if (isset($_GET['papelera'])) {
        bs_require_permiso('contratos_editar');
        $pap = [];
        $limite = time() - BS_CONTRATOS_PAPELERA_DIAS * 86400;
        foreach ((array)($d['papelera'] ?? []) as $c) {
            $at = strtotime((string)($c['borrado_at'] ?? '')) ?: time();
            if ($at < $limite) continue; // vencido: lo borra la próxima escritura
            $s = bs_contrato_salida($c);
            $s['borrado'] = date('Y-m-d', $at);
            $s['quedan'] = max(0, BS_CONTRATOS_PAPELERA_DIAS - intval(floor((time() - $at) / 86400)));
            $pap[] = $s;
        }
        usort($pap, function ($a, $b) { return strcmp($b['borrado'], $a['borrado']); });
        bs_ok(['dias' => BS_CONTRATOS_PAPELERA_DIAS, 'contratos' => $pap]);
    }
    $lista = array_map('bs_contrato_salida', array_values($d['contratos']));
    usort($lista, function ($a, $b) { return strcmp($b['creado'], $a['creado']) ?: strcmp($b['id'], $a['id']); });
    $en_papelera = 0;
    foreach ((array)($d['papelera'] ?? []) as $c) {
        if ((strtotime((string)($c['borrado_at'] ?? '')) ?: time()) >= time() - BS_CONTRATOS_PAPELERA_DIAS * 86400) $en_papelera++;
    }
    bs_ok(['hoy' => date('Y-m-d'), 'papelera' => $en_papelera, 'vendedores' => $BS_CONTRATO_VENDEDORES, 'procedencias' => $BS_CONTRATO_PROCEDENCIAS, 'contratos' => $lista]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') bs_error('Solo GET o POST', 405);

$body = json_decode(@file_get_contents('php://input'), true);
if (!is_array($body)) bs_error('Body JSON inválido');
$accion = (string)($body['accion'] ?? '');
$acciones = ['crear', 'editar', 'sena', 'pago', 'borrar_pago', 'borrar', 'borrar_enlazado', 'restaurar', 'restaurar_enlazado', 'mudar_enlazado', 'nro_de_ficha', 'de_ficha', 'de_fichas', 'enlazar', 'desenlazar', 'prueba', 'borrar_prueba'];
if (!in_array($accion, $acciones, true)) bs_error('accion inválida');

// Todo lo que puede fallar por datos mal cargados se valida ANTES de tomar el lock.
$campos = in_array($accion, ['crear', 'editar'], true) ? bs_contrato_campos($body) : null;
$id = (string)($body['id'] ?? '');
if (!in_array($accion, ['crear', 'prueba', 'borrar_prueba', 'borrar_enlazado', 'restaurar_enlazado', 'mudar_enlazado', 'nro_de_ficha', 'de_ficha', 'de_fichas'], true) && !bs_contrato_id_ok($id)) bs_error('id inválido');

$sena_nueva = null;
if ($accion === 'sena') $sena_nueva = bs_contrato_sena($body['monto'] ?? 0, $body['moneda'] ?? '', $body['fecha'] ?? '', $body['cotizacion'] ?? '', $body['aplica'] ?? '');

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
    $pago = array_merge($pago, bs_contrato_cambio($pago['moneda'], $body['cotizacion'] ?? '', $body['aplica'] ?? '', 'Pago'));
}

// Enlazar con una ficha del tablero. "crear" también puede traer el enlace: es el contrato que
// se arma desde "Obras en el tablero sin contrato", que nace enlazado.
$enlace = null;
$crear_enlazado = $accion === 'crear' && is_array($body['enlace'] ?? null);
if ($accion === 'enlazar' || $crear_enlazado) {
    $nro = intval($crear_enlazado ? ($body['enlace']['cliente_nro'] ?? 0) : ($body['cliente_nro'] ?? 0));
    $sub = intval($crear_enlazado ? ($body['enlace']['sub'] ?? 0) : ($body['sub'] ?? 0));
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
    $borrar_adj = []; // contratos borrados DEFINITIVAMENTE: su PDF o Excel se va con ellos
    bs_contratos_purgar_papelera($d); // lo que venció en la papelera sale con esta escritura

    // Vendedor y procedencia salen de las listas. Un contrato viejo conserva lo que ya tenía
    // (por si un nombre se saca de la lista), pero no se le puede poner otro valor de afuera.
    if ($campos !== null) {
        $viejo = ($accion === 'editar' && isset($d['contratos'][$id])) ? $d['contratos'][$id] : [];
        foreach ([['vendedor', $BS_CONTRATO_VENDEDORES, 'Elegí el vendedor de la lista.'], ['procedencia', $BS_CONTRATO_PROCEDENCIAS, 'Elegí la procedencia de la lista.']] as $r) {
            $v = $campos[$r[0]];
            if ($v !== '' && !in_array($v, $r[1], true) && $v !== (string)($viejo[$r[0]] ?? '')) bs_contratos_salir($fp, $r[2]);
        }
    }

    if ($accion === 'crear') {
        if (count($d['contratos']) >= BS_CONTRATOS_MAX) bs_contratos_salir($fp, 'Se llegó al máximo de contratos.', 507);
        if ($enlace !== null && ($k = bs_contrato_de_ficha($d, intval($enlace['cliente_nro']), $enlace['sub'])) !== null) {
            bs_contratos_salir($fp, 'Esa obra del tablero ya tiene contrato (' . ($d['contratos'][$k]['cliente'] ?? '') . ').', 409);
        }
        $id = bs_contrato_id();
        $d['contratos'][$id] = array_merge($campos, ['id' => $id, 'creado' => $ahora, 'actualizado' => $ahora, 'pagos' => [], 'prueba' => false, 'enlace' => $enlace]);

    } elseif ($accion === 'prueba') {
        foreach ($d['contratos'] as $c) {
            if (!empty($c['prueba'])) bs_contratos_salir($fp, 'Ya hay renglones de prueba cargados.', 409);
        }
        $hoy = date('Y-m-d');
        $pruebas = [
            ['cliente' => 'PRUEBA · Cocina en L', 'telefono' => '11 5550-0001', 'direccion' => 'Calle de prueba 123, CABA', 'nro' => '',
             'fecha' => $hoy, 'vendedor' => $BS_CONTRATO_VENDEDORES[0], 'contrato' => '', 'contrato_paralelo' => '', 'procedencia' => $BS_CONTRATO_PROCEDENCIAS[1], 'medidas' => '2,40 x 0,62 + 1,60 x 0,62', 'factura' => 'si', 'muebles' => 'si',
             'material' => 'Granito Nacional · Negro Boreal', 'bacha' => 'nuestra', 'bacha_modelo' => '403-E',
             'medicion' => date('Y-m-d', strtotime('+3 days')), 'total_usd' => 1200, 'total_ars' => 0,
             'sena' => ['monto' => 500, 'moneda' => 'USD', 'fecha' => $hoy], 'pagos' => [], 'nota' => 'Renglón de prueba: se puede borrar.'],
            ['cliente' => 'PRUEBA · Baño doble', 'telefono' => '11 5550-0002', 'direccion' => 'Calle de prueba 456, Lanús', 'nro' => '',
             'fecha' => $hoy, 'vendedor' => $BS_CONTRATO_VENDEDORES[1], 'contrato' => '', 'contrato_paralelo' => '', 'procedencia' => $BS_CONTRATO_PROCEDENCIAS[0], 'medidas' => '1,20 x 0,50', 'factura' => 'no', 'muebles' => 'no',
             'material' => 'Purastone · Blanco Paloma (N y J)', 'bacha' => 'cliente', 'bacha_modelo' => '',
             'medicion' => '', 'total_usd' => 0, 'total_ars' => 850000,
             'sena' => null, 'pagos' => [], 'nota' => 'Renglón de prueba: sin seña y sin medición coordinada.'],
            ['cliente' => 'PRUEBA · Isla y mesada', 'telefono' => '11 5550-0003', 'direccion' => 'Calle de prueba 789, Quilmes', 'nro' => '',
             'fecha' => $hoy, 'vendedor' => $BS_CONTRATO_VENDEDORES[2], 'contrato' => '', 'contrato_paralelo' => '', 'procedencia' => $BS_CONTRATO_PROCEDENCIAS[1], 'medidas' => 'Isla 1,80 x 0,90 + mesada 3,10 x 0,62', 'factura' => '', 'muebles' => 'si',
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

    } elseif ($accion === 'nro_de_ficha') {
        // EL N° ES UN DATO QUE COMPARTEN LA FICHA Y SU CONTRATO (dueño, 18/11/2026). Lo llama el tablero
        // cuando en "Editar obra" le cambian el N° a una ficha con código interno: el contrato toma ese
        // mismo N°. La ficha no se mueve de lugar y el enlace no cambia. Vacío lo borra.
        $f_n = intval($body['cliente_nro'] ?? 0); $f_s = intval($body['sub'] ?? 0);
        $nro_nuevo = trim((string)($body['nro'] ?? ''));
        if ($f_n <= 0 || $f_s <= 0) bs_contratos_salir($fp, 'Falta de qué ficha.');
        if ($nro_nuevo !== '' && !preg_match('/^\d{1,6}(?:-\d{1,3})?$/', $nro_nuevo)) bs_contratos_salir($fp, 'El N° va como 0042, o 0042-2 si tiene versiones.');
        $k = bs_contrato_de_ficha($d, $f_n, $f_s);
        if ($k === null) bs_contratos_salir($fp, 'Esta ficha no tiene contrato. El N° es de la obra y de su contrato a la vez: primero creale el contrato en Relación-Contrato.', 409);
        $id = (string)$k;
        $d['contratos'][$k]['nro'] = $nro_nuevo;
        $d['contratos'][$k]['actualizado'] = $ahora;
        $extra['nro'] = $nro_nuevo;

    } elseif ($accion === 'mudar_enlazado') {
        // Enlazados, el contrato y la ficha comparten el N° (dueño, 17/11/2026). Lo llama el tablero
        // cuando a una ficha se le cambia el N° de presupuesto desde "Editar obra": la ficha pasa a
        // otra clave, y su contrato la sigue (no queda apuntando a una ficha que ya no existe) y toma
        // ese N°. Solo vale si la mudanza ocurrió de verdad: la ficha vieja ya no está viva y la nueva sí.
        $de = is_array($body['de'] ?? null) ? $body['de'] : [];
        $a  = is_array($body['a'] ?? null) ? $body['a'] : [];
        $de_n = intval($de['cliente_nro'] ?? 0); $de_s = intval($de['sub'] ?? 0);
        $a_n  = intval($a['cliente_nro'] ?? 0);  $a_s  = intval($a['sub'] ?? 0);
        if ($de_n <= 0 || $de_s <= 0 || $a_n <= 0 || $a_s <= 0) bs_contratos_salir($fp, 'Falta de qué ficha a cuál.');
        if (bs_contrato_ficha_estado(['cliente_nro' => $de_n, 'sub' => $de_s]) === 'viva'
            || bs_contrato_ficha_estado(['cliente_nro' => $a_n, 'sub' => $a_s]) !== 'viva') {
            bs_contratos_salir($fp, 'Esa ficha no cambió de N°: el contrato no se toca.', 409);
        }
        $k = bs_contrato_de_ficha($d, $de_n, $de_s);
        $extra['mudado'] = false;
        if ($k !== null && bs_contrato_de_ficha($d, $a_n, $a_s) === null) {
            $id = (string)$k;
            $nro_nuevo = str_pad((string)$a_n, 4, '0', STR_PAD_LEFT);
            $d['contratos'][$k]['enlace'] = ['cliente_nro' => $nro_nuevo, 'sub' => $a_s, 'at' => $ahora];
            $d['contratos'][$k]['nro'] = $nro_nuevo . ($a_s > 1 ? '-' . $a_s : '');
            $d['contratos'][$k]['actualizado'] = $ahora;
            $extra['mudado'] = true;
            $extra['nro'] = $d['contratos'][$k]['nro'];
        }

    } elseif ($accion === 'de_ficha' || $accion === 'de_fichas') {
        // La seña y la nota de la ficha pasan a su contrato (ver bs_contrato_traer). "de_ficha" lo llama el
        // tablero al guardar una de las dos; "de_fichas", esta página al abrirse, por lo que haya quedado sin pasar.
        // Los montos se leen de la ficha acá: del pedido solo llega de qué ficha se trata.
        $claves = [];
        if ($accion === 'de_ficha') {
            $k = bs_contrato_de_ficha($d, intval($body['cliente_nro'] ?? 0), intval($body['sub'] ?? 0));
            if ($k !== null) { $claves[] = $k; $id = (string)$k; }
        } else {
            foreach ($d['contratos'] as $k => $c) { if (is_array($c['enlace'] ?? null)) $claves[] = $k; }
        }
        $guardar = false; $senas = 0; $notas = 0; $sin_cot = 0;
        foreach ($claves as $k) {
            $c = $d['contratos'][$k];
            if (bs_contrato_ficha_estado($c['enlace'] ?? null) !== 'viva') continue;
            list($nuevo, $g, $info) = bs_contrato_traer($c, bs_contrato_obra($c['enlace']));
            if (!$g) continue;
            $guardar = true;
            if ($info['sena'] === 'copiada') $senas++;
            if ($info['sena'] === 'cotizacion') $sin_cot++;
            if ($info['nota']) $notas++;
            if ($info['sena'] === 'copiada' || $info['nota']) $nuevo['actualizado'] = $ahora;
            $d['contratos'][$k] = $nuevo;
        }
        $extra = ['senas' => $senas, 'notas' => $notas, 'sin_cotizacion' => $sin_cot];
        // Si no pasó nada que se vea, no queda en el registro (y si no hay nada que anotar, ni se escribe).
        if (!$senas && !$notas) $GLOBALS['bs_no_registrar'] = true;
        if (!$guardar) { bs_lock_release($fp); bs_ok(array_merge(['id' => $id, 'sin_cambios' => true], $extra)); }

    } elseif ($accion === 'borrar_enlazado') {
        // Lo llama el tablero al borrar una ficha: si tenía un contrato enlazado, se va con ella.
        // Solo vale si la ficha de verdad salió del tablero: con la ficha viva (o archivada) el contrato
        // no se toca. Así quien opera el tablero no puede mandar contratos a la papelera por su cuenta.
        $est = bs_contrato_ficha_estado(['cliente_nro' => intval($body['cliente_nro'] ?? 0), 'sub' => intval($body['sub'] ?? 0)]);
        if ($est === 'viva' || $est === 'archivada') bs_contratos_salir($fp, 'Esa ficha sigue en el tablero: su contrato no se borra.', 409);
        $k = bs_contrato_de_ficha($d, intval($body['cliente_nro'] ?? 0), intval($body['sub'] ?? 0));
        $extra['borrado'] = $k !== null;
        if ($k !== null) {
            $id = (string)$k;
            if (!empty($d['contratos'][$k]['prueba'])) { $borrar_adj[] = $id; unset($d['contratos'][$k]); }
            else bs_contrato_a_papelera($d, $k);
        }

    } elseif ($accion === 'restaurar' || $accion === 'restaurar_enlazado') {
        // De la papelera a la lista, con sus pagos y su adjunto.
        $k = null;
        if ($accion === 'restaurar') {
            $k = isset($d['papelera'][$id]) ? $id : null;
        } else {
            // El contrato vuelve con su ficha: solo si la ficha ya está de nuevo en el tablero.
            if (bs_contrato_ficha_estado(['cliente_nro' => intval($body['cliente_nro'] ?? 0), 'sub' => intval($body['sub'] ?? 0)]) !== 'viva') {
                bs_contratos_salir($fp, 'Esa ficha no está en el tablero: su contrato no se recupera solo.', 409);
            }
            $n = str_pad((string)intval($body['cliente_nro'] ?? 0), 4, '0', STR_PAD_LEFT);
            foreach ((array)$d['papelera'] as $kk => $c) {
                $e = $c['enlace'] ?? null;
                if (is_array($e) && (string)($e['cliente_nro'] ?? '') === $n && intval($e['sub'] ?? 0) === intval($body['sub'] ?? 0)) $k = (string)$kk;
            }
        }
        $extra['restaurado'] = $k !== null;
        if ($k === null) {
            if ($accion === 'restaurar') bs_contratos_salir($fp, 'Ese contrato ya no está en la papelera.', 404);
        } else {
            $c = $d['papelera'][$k];
            unset($c['borrado_at']);
            // si mientras tanto esa ficha se enlazó con otro contrato, este vuelve suelto
            $e = $c['enlace'] ?? null;
            if (is_array($e) && bs_contrato_de_ficha($d, intval($e['cliente_nro'] ?? 0), intval($e['sub'] ?? 0)) !== null) $c['enlace'] = null;
            $c['actualizado'] = $ahora;
            $d['contratos'][$k] = $c;
            unset($d['papelera'][$k]);
            $id = $k;
            $extra['enlace'] = is_array($c['enlace'] ?? null) ? ['cliente_nro' => $c['enlace']['cliente_nro'], 'sub' => $c['enlace']['sub']] : null;
        }

    } elseif ($accion === 'borrar_prueba') {
        // Devuelve qué fichas del tablero se habían creado desde esos renglones: la página las borra.
        $enlaces = [];
        foreach ($d['contratos'] as $k => $c) {
            if (empty($c['prueba'])) continue;
            if (is_array($c['enlace'] ?? null)) $enlaces[] = ['cliente_nro' => $c['enlace']['cliente_nro'], 'sub' => $c['enlace']['sub']];
            $borrar_adj[] = (string)$k;
            unset($d['contratos'][$k]);
        }
        $extra['enlaces'] = $enlaces;

    } else {
        if (!isset($d['contratos'][$id])) bs_contratos_salir($fp, 'Ese contrato ya no existe. Actualizá la página.', 404);
        $c = $d['contratos'][$id];

        if ($accion === 'editar') {
            $c = array_merge($c, $campos);
        } elseif ($accion === 'sena') {
            // El tablero no sabe de cotizaciones: si la seña llega de ahí sin cambio de moneda,
            // se conserva la cotización que el contrato ya tenía anotada.
            $vieja = is_array($c['sena'] ?? null) ? $c['sena'] : null;
            if ($sena_nueva && $vieja && empty($sena_nueva['cotizacion']) && !isset($body['aplica'])
                && ($vieja['moneda'] ?? '') === $sena_nueva['moneda'] && !empty($vieja['cotizacion'])) {
                $sena_nueva['cotizacion'] = $vieja['cotizacion'];
                $sena_nueva['aplica'] = $vieja['aplica'] ?? $sena_nueva['moneda'];
            }
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
            unset($c['sena_ficha'], $c['nota_ficha']); // otra ficha: lo que se miró de la anterior no vale
            // Enlazados comparten el N°: un contrato sin N° que se enlaza (por teléfono) con la ficha de
            // un presupuesto toma ese N° (barrido del 18/11/2026). Si ya tiene uno, no se le cambia acá.
            if (trim((string)($c['nro'] ?? '')) === '') {
                $bs_cli = bs_read_json(BS_CLIENTES_DIR . '/' . $enlace['cliente_nro'] . '.json');
                foreach ((array)($bs_cli['cotizaciones'] ?? []) as $bs_x) {
                    if (intval($bs_x['sub'] ?? 0) === $enlace['sub'] && empty($bs_x['manual'])) $c['nro'] = $enlace['cliente_nro'] . ($enlace['sub'] > 1 ? '-' . $enlace['sub'] : '');
                }
            }
        } elseif ($accion === 'desenlazar') {
            $c['enlace'] = null;
            unset($c['sena_ficha'], $c['nota_ficha']);
        }

        if ($accion === 'borrar') {
            // A la papelera (30 días). Los de prueba, o con "definitivo", se borran de verdad.
            if (!empty($c['prueba']) || !empty($body['definitivo'])) { $borrar_adj[] = $id; unset($d['contratos'][$id]); }
            else bs_contrato_a_papelera($d, $id);
        } else {
            $c['actualizado'] = $ahora;
            $d['contratos'][$id] = $c;
        }
    }

    if (!bs_write_json_atomic(BS_CONTRATOS_FILE, $d)) bs_contratos_salir($fp, 'No se pudo guardar', 500);
    foreach ($borrar_adj as $k) bs_contrato_adj_borrar($k);
    bs_lock_release($fp);
    bs_ok(array_merge(['id' => $id], $extra));

} catch (Throwable $e) {
    bs_lock_release($fp);
    bs_error('Excepción: ' . $e->getMessage(), 500);
}
