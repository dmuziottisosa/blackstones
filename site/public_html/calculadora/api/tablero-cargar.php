<?php
// ============================================================
// tablero-cargar.php — Cargar en "Por medir" la obra de un contrato
// BlackStones Marmolería · Registry de Presupuestos
// ============================================================
// POST /api/tablero-cargar.php
//
// Para obras que no pasaron por la calculadora. Crea un cliente nuevo con
// una cotización YA en estado "aprobado": por eso aparece sola en el
// tablero (Por medir) y también en Presupuestos · Activos. Mismo criterio
// que add-manual.php, que hace lo propio con los entregados históricos.
//
// Con "nro": no crea nada. Busca ese presupuesto en el hub y lo pasa a
// "aprobado", así la ficha queda atada al presupuesto real (se ve el
// detalle, el material, la zona). Acepta "0042" o "0042-2" (versión).
//
// REGLA (dueño, 18/11/2026, decisión final): TODO NACE DE RELACIÓN-CONTRATO. Una obra entra al
// tablero solo por acá y solo desde su contrato ("Cargar en Por medir"): el pedido tiene que traer
// "contrato" (el id). En el tablero ya no hay "Cargar obra". Así el contrato y la ficha hacen todo
// el ciclo enlazados y no quedan fichas sueltas. Siempre nace en Por medir, vacía: nunca hereda la
// etapa ni los tildes de otra obra. Lo usan administradores y operadores (permiso contratos_editar).
// Si Relación-Contrato no está instalada, no se pueden cargar obras nuevas (el resto del tablero sigue).
//
// Body JSON:
//   {
//     "contrato":  "c261118...",              // REQUERIDO: el contrato del que nace la obra
//     "nro":       "0042-2",                  // opcional: presupuesto existente
//     "nombre":    "Carolina Pereyra",        // requerido si no hay nro
//     "celular":   "1124981031",              // opcional
//     "direccion": "Av. Rivadavia 6200, CABA",// opcional
//     "material":  "Blanco Suggar",           // opcional (queda como material_final)
//     "concepto":  "Cocina en L"              // opcional
//   }
//
// El importe queda en 0: se carga después desde Presupuestos (editar),
// que es donde administración lleva la plata.
//
// Response: { "ok": true, "cliente_nro": "0044", "sub": 1 }
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_tablero.php';

bs_require_permiso('contratos_editar');
bs_check_csrf();
bs_ensure_dirs();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') bs_error('Solo POST', 405);

$body = json_decode(@file_get_contents('php://input'), true);
if (!is_array($body)) bs_error('Body JSON inválido');

// ------------------------------------------------------------
// El contrato del que nace la obra
// ------------------------------------------------------------
$bs_cid = (string)($body['contrato'] ?? '');
$bs_no = 'Las obras se cargan desde su contrato, en Relación-Contrato ("Cargar en Por medir"). Desde el tablero ya no se cargan.';
if (!is_file(__DIR__ . '/_contratos.php')) bs_error($bs_no, 409);
require_once __DIR__ . '/_contratos.php';
$bs_con = ($bs_cid !== '' && bs_contrato_id_ok($bs_cid)) ? (bs_contratos_leer()['contratos'][$bs_cid] ?? null) : null;
if (!is_array($bs_con)) bs_error($bs_no, 409);
if (is_array($bs_con['enlace'] ?? null)) {
    // ya tiene una ficha: si sigue viva, no se carga otra
    $bs_cli = bs_read_json(BS_CLIENTES_DIR . '/' . str_pad((string)intval($bs_con['enlace']['cliente_nro'] ?? 0), 4, '0', STR_PAD_LEFT) . '.json');
    foreach ((array)(is_array($bs_cli) ? ($bs_cli['cotizaciones'] ?? []) : []) as $bs_x) {
        if (intval($bs_x['sub'] ?? 0) === intval($bs_con['enlace']['sub'] ?? 0) && in_array((string)($bs_x['estado'] ?? ''), ['aprobado', 'papelera'], true)) {
            bs_error('Ese contrato ya tiene su ficha en el tablero' . (($bs_x['estado'] ?? '') === 'papelera' ? ' (está en la papelera: recuperala desde "Papelera")' : '') . '.', 409);
        }
    }
}

function bs_cargar_texto($body, $campo, $max, $etiqueta) {
    $v = trim(preg_replace('/\s+/u', ' ', (string)($body[$campo] ?? '')));
    if (mb_strlen($v) > $max) bs_error($etiqueta . ' demasiado largo');
    return $v;
}

/** Borra del tablero cualquier registro previo de esa clave: la ficha nueva nace vacía, en Por medir. */
function bs_cargar_ciclo_nuevo($key) {
    if (!is_dir(BS_TABLERO_DIR)) return;
    $ft = bs_lock_acquire(BS_TABLERO_LOCK);
    if (!$ft) return;
    $t = bs_tablero_leer();
    if (isset($t['obras'][$key])) {
        bs_tablero_borrar_carpeta($key);
        unset($t['obras'][$key]);
        bs_write_json_atomic(BS_TABLERO_FILE, $t);
    }
    bs_lock_release($ft);
}

// ------------------------------------------------------------
// Con N° de presupuesto: aprobar el que ya existe en el hub
// ------------------------------------------------------------
$nro_txt = trim((string)($body['nro'] ?? ''));
if ($nro_txt !== '') {
    if (!preg_match('/^#?\s*(\d{1,6})(?:\s*-\s*(\d{1,3}))?$/', $nro_txt, $m)) {
        bs_error('El N° de presupuesto va como 0042, o 0042-2 si tiene versiones.');
    }
    $nro = str_pad((string)intval($m[1]), 4, '0', STR_PAD_LEFT);
    $sub_pedido = isset($m[2]) ? intval($m[2]) : 0;
    $cliente_path = BS_CLIENTES_DIR . '/' . $nro . '.json';

    $fp = bs_lock_acquire(BS_CLIENTES_DIR . '/.write.lock');
    if (!$fp) bs_error('No se pudo adquirir lock', 500);
    try {
        $cliente_obj = bs_read_json($cliente_path);
        $cots = is_array($cliente_obj) ? ($cliente_obj['cotizaciones'] ?? []) : [];
        if (!$cots) {
            bs_lock_release($fp);
            bs_error('No hay ningún presupuesto N° ' . $nro . '. Para cargar la obra sin presupuesto, dejá el número vacío.', 404);
        }
        $subs = array_map(function ($c) { return intval($c['sub'] ?? 0); }, $cots);
        if ($sub_pedido === 0) {
            if (count($cots) > 1) {
                bs_lock_release($fp);
                bs_error('El ' . $nro . ' tiene ' . count($cots) . ' versiones (' . implode(', ', $subs) . '). Escribí cuál, por ejemplo ' . $nro . '-' . max($subs) . '.');
            }
            $sub_pedido = $subs[0];
        } elseif (!in_array($sub_pedido, $subs, true)) {
            bs_lock_release($fp);
            bs_error('El ' . $nro . ' no tiene la versión ' . $sub_pedido . '. Tiene: ' . implode(', ', $subs) . '.', 404);
        }
        $now = date('c');
        foreach ($cliente_obj['cotizaciones'] as &$cot) {
            if (intval($cot['sub'] ?? 0) !== $sub_pedido) continue;
            $estado = (string)($cot['estado'] ?? '');
            // Una ficha cargada a mano no es un presupuesto, aunque por dentro tenga un número (las
            // viejas lo tienen en la misma serie, y no se ve). Si alguien escribe justo ese número,
            // no se la toca ni se enlaza nada con ella (dueño, 18/11/2026).
            if (!empty($cot['manual'])) {
                bs_lock_release($fp);
                bs_error('No hay ningún presupuesto N° ' . $nro . '. Para cargar la obra sin presupuesto, dejá el número vacío.', 404);
            }
            // Lo que está en la papelera se recupera desde la Papelera, con todo lo que tenía.
            // "Cargar" siempre empieza una obra de cero, en Por medir.
            if ($estado === 'papelera') {
                bs_lock_release($fp);
                bs_error('El ' . $nro . '-' . $sub_pedido . ' está en la papelera del tablero. Recuperalo desde "Papelera".', 409);
            }
            if ($estado === 'aprobado') {
                bs_lock_release($fp);
                bs_error('El ' . $nro . '-' . $sub_pedido . ' ya está en el tablero.', 409);
            }
            if ($estado === 'entregado') {
                bs_lock_release($fp);
                bs_error('El ' . $nro . '-' . $sub_pedido . ' ya figura como entregado.', 409);
            }
            // UN NÚMERO, UNA PERSONA (barrido del 18/11/2026). Si el presupuesto de ese N° es de otra
            // persona que la del contrato, el N° del contrato está mal: no se carga, y se avisa. Antes se
            // cargaba y la ficha quedaba con el nombre de uno y la seña, la pileta y la dirección del otro.
            // De quién es el contrato lo dice el contrato guardado, no lo que mande la pantalla.
            $bs_esp = ['nombre' => (string)($bs_con['cliente'] ?? ''), 'celular' => (string)($bs_con['telefono'] ?? '')];
            if ($bs_esp && trim((string)($bs_esp['nombre'] ?? '') . (string)($bs_esp['celular'] ?? '')) !== ''
                && !bs_misma_persona((array)($cliente_obj['cliente'] ?? []), ['nombre' => (string)($bs_esp['nombre'] ?? ''), 'celular' => (string)($bs_esp['celular'] ?? '')])) {
                bs_lock_release($fp);
                bs_error('El presupuesto N° ' . $nro . ' es de ' . (trim((string)($cliente_obj['cliente']['nombre'] ?? '')) ?: 'otro cliente') . ', no de ' . (trim((string)($bs_esp['nombre'] ?? '')) ?: 'este contrato') . '. Revisá el N° del contrato. No se cargó nada.', 409);
            }
            $cot['estado'] = 'aprobado';
            $cot['transiciones'][] = ['estado' => 'aprobado', 'at' => $now];
        }
        unset($cot);
        $cliente_obj['ultima_actualizacion'] = $now;
        if (!bs_write_json_atomic($cliente_path, $cliente_obj)) {
            bs_lock_release($fp);
            bs_error('No se pudo escribir el cliente', 500);
        }
        bs_lock_release($fp);
        // La obra que se carga empieza su ciclo en Por medir (dueño, 18/11/2026): si en el tablero
        // quedó un registro viejo con esa clave (tildes, etapa, fotos de otra vuelta), no se hereda.
        bs_cargar_ciclo_nuevo($nro . '-' . $sub_pedido);
        bs_ok(['cliente_nro' => $nro, 'sub' => $sub_pedido, 'vinculada' => true]);
    } catch (Throwable $e) {
        bs_lock_release($fp);
        bs_error('Excepción: ' . $e->getMessage(), 500);
    }
}

// ------------------------------------------------------------
// Sin N°: obra nueva cargada a mano
// ------------------------------------------------------------
$nombre    = bs_cargar_texto($body, 'nombre', 120, 'Nombre');
$celular   = bs_cargar_texto($body, 'celular', 40, 'Teléfono');
$direccion = bs_cargar_texto($body, 'direccion', 200, 'Dirección');
$material  = bs_cargar_texto($body, 'material', 200, 'Material');
$concepto  = bs_cargar_texto($body, 'concepto', 200, 'Trabajo');
if ($nombre === '') bs_error('Falta el nombre del cliente.');

$now = date('c');

$lockfile = BS_CLIENTES_DIR . '/.write.lock';
$fp = bs_lock_acquire($lockfile);
if (!$fp) bs_error('No se pudo adquirir lock', 500);

try {
    // Una obra cargada a mano no es un presupuesto: su número sale de la serie interna de
    // fichas (90001 en adelante) y no toca la numeración de la calculadora.
    $target_nro = bs_tablero_nro_ficha_manual();
    $cliente_path = BS_CLIENTES_DIR . '/' . $target_nro . '.json';

    $cliente_obj = [
        'version' => defined('BS_CONTRACT_VERSION') ? BS_CONTRACT_VERSION : 1,
        'cliente_nro' => $target_nro,
        'cliente' => [
            'nombre'    => $nombre,
            'dni'       => '',
            'celular'   => $celular,
            'direccion' => $direccion,
            'email'     => '',
        ],
        'primera_cotizacion' => $now,
        'ultima_actualizacion' => $now,
        'cotizaciones' => [
            [
                'sub'      => 1,
                'fecha'    => $now,
                'concepto' => $concepto,
                'material_final' => $material,
                'estado'   => 'aprobado',
                'origen'   => 'local',
                'transiciones' => [
                    ['estado' => 'aprobado', 'at' => $now],
                ],
                'presupuesto' => [
                    'totales' => ['usd' => 0, 'ars' => 0],
                ],
                // Alta manual: no pasó por la calc, no tiene items.
                'manual' => true,
                'ajuste_manual' => [
                    'at'   => $now,
                    'nota' => 'Obra cargada a mano desde el tablero',
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
    bs_cargar_ciclo_nuevo($target_nro . '-1');
    bs_ok(['cliente_nro' => $target_nro, 'sub' => 1]);

} catch (Throwable $e) {
    bs_lock_release($fp);
    bs_error('Excepción: ' . $e->getMessage(), 500);
}
