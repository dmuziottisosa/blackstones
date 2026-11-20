<?php
// ============================================================
// tablero-cargar.php — Cargar en "Por medir" la obra de un contrato
// BlackStones Marmolería · Registry de Presupuestos
// ============================================================
// POST /api/tablero-cargar.php
//
// Crea la ficha de la obra con un CÓDIGO INTERNO (serie 90001+, no se muestra) y la deja enlazada
// con su contrato. El N° de presupuesto del contrato viaja como dato ("nro_contrato" de la ficha):
// no se aprueba ni se toca ningún presupuesto de la calculadora.
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
// Response: { "ok": true, "cliente_nro": "90012", "sub": 1, "enlazado": true, "nro": "2086" }
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
// La ficha: siempre con código interno, tenga o no N° el contrato
// ------------------------------------------------------------
// (dueño, 18/11/2026) Lo que une la ficha con su contrato es un código interno que no se ve
// (la serie 90001+), no el N° de presupuesto. El N° es un dato que comparten los dos: se cambia
// en uno y cambia en el otro, y la ficha no se mueve de lugar ("desaparece la mudanza"). Por eso
// acá ya no se aprueba ningún presupuesto: el presupuesto sigue siendo un presupuesto, en su pestaña.
// Los datos de la ficha salen del contrato guardado; lo que mande la pantalla solo completa lo que falte.
$bs_de = function ($campo_contrato, $campo_body, $max, $etiqueta) use ($bs_con, $body) {
    $v = trim(preg_replace('/\s+/u', ' ', (string)($bs_con[$campo_contrato] ?? '')));
    if ($v === '') $v = bs_cargar_texto($body, $campo_body, $max, $etiqueta);
    return mb_substr($v, 0, $max);
};
$nombre    = $bs_de('cliente', 'nombre', 120, 'Nombre');
$celular   = $bs_de('telefono', 'celular', 40, 'Teléfono');
$direccion = $bs_de('direccion', 'direccion', 200, 'Dirección');
$material  = $bs_de('material', 'material', 200, 'Material');
$concepto  = bs_cargar_texto($body, 'concepto', 200, 'Trabajo');
if ($nombre === '') bs_error('Falta el nombre del cliente: ponelo en el contrato.');

// El N° del contrato, como dato de la ficha (lo que se ve en el tablero y el calendario).
$bs_nro = trim((string)($bs_con['nro'] ?? ''));
if (!preg_match('/^\d{1,6}(?:-\d{1,3})?$/', $bs_nro)) $bs_nro = '';
if ($bs_nro !== '') {
    $bs_p = explode('-', $bs_nro);
    $bs_n4 = str_pad((string)intval($bs_p[0]), 4, '0', STR_PAD_LEFT);
    $bs_igual = function ($x) use ($bs_p) { // ¿ese texto es este mismo N°? ("2086" = "2086-1" = "02086")
        if (!preg_match('/^(\d{1,6})(?:-(\d{1,3}))?$/', trim((string)$x), $m)) return false;
        return intval($m[1]) === intval($bs_p[0]) && intval($m[2] ?? 1) === intval($bs_p[1] ?? 1);
    };
    // UNA OBRA, UNA FICHA: si ya hay una obra con ese N° en el tablero (o en su papelera), no se carga
    // otra. Es lo que pasó con una clienta cargada dos veces, una a mano y otra con su N°.
    //  a) una ficha vieja que nació de ese presupuesto
    $bs_pc = bs_read_json(BS_CLIENTES_DIR . '/' . $bs_n4 . '.json');
    foreach ((array)(is_array($bs_pc) ? ($bs_pc['cotizaciones'] ?? []) : []) as $bs_x) {
        if (!empty($bs_x['manual']) || !in_array((string)($bs_x['estado'] ?? ''), ['aprobado', 'papelera'], true)) continue;
        if (isset($bs_p[1]) && intval($bs_x['sub'] ?? 0) !== intval($bs_p[1])) continue;
        bs_error('El N° ' . $bs_nro . ' ya está en el tablero' . (($bs_x['estado'] ?? '') === 'papelera' ? ' (en la papelera: recuperalo desde "Papelera")' : '') . ', a nombre de ' . (trim((string)($bs_pc['cliente']['nombre'] ?? '')) ?: 'otra obra') . '. No se cargó de nuevo. Si es esta misma obra, usá "Enlazar con el tablero".', 409);
    }
    //  b) una ficha con código interno que ya lleva ese N°
    foreach ((array)(bs_tablero_leer()['obras'] ?? []) as $bs_k => $bs_o) {
        if (!is_array($bs_o) || !$bs_igual($bs_o['nro_contrato'] ?? '')) continue;
        $bs_kp = explode('-', (string)$bs_k);
        $bs_fc2 = bs_read_json(BS_CLIENTES_DIR . '/' . $bs_kp[0] . '.json');
        foreach ((array)(is_array($bs_fc2) ? ($bs_fc2['cotizaciones'] ?? []) : []) as $bs_x) {
            if (intval($bs_x['sub'] ?? 0) !== intval($bs_kp[1] ?? 0) || !in_array((string)($bs_x['estado'] ?? ''), ['aprobado', 'papelera'], true)) continue;
            bs_error('El N° ' . $bs_nro . ' ya está en el tablero' . (($bs_x['estado'] ?? '') === 'papelera' ? ' (en la papelera: recuperalo desde "Papelera")' : '') . ', a nombre de ' . (trim((string)($bs_fc2['cliente']['nombre'] ?? '')) ?: 'otra obra') . '. No se cargó de nuevo. Si es esta misma obra, usá "Enlazar con el tablero".', 409);
        }
    }
    // UN NÚMERO, UNA PERSONA: si ese N° es un presupuesto de la calculadora y es de OTRA persona que la
    // del contrato, el N° del contrato está mal. No se carga, y se avisa de quién es cada uno.
    $bs_cots = array_values(array_filter((array)(is_array($bs_pc) ? ($bs_pc['cotizaciones'] ?? []) : []), function ($x) use ($bs_p) { return empty($x['manual']) && (!isset($bs_p[1]) || intval($x['sub'] ?? 0) === intval($bs_p[1])); }));
    if ($bs_cots && !bs_misma_persona((array)($bs_pc['cliente'] ?? []), ['nombre' => $nombre, 'celular' => $celular])) {
        $bs_de = trim((string)($bs_pc['cliente']['nombre'] ?? ''));
        $bs_tp = trim((string)($bs_pc['cliente']['celular'] ?? ''));
        // EL NOMBRE COINCIDE Y EL TELÉFONO NO (dueño, 20/11/2026: el cartel decía "es de Ana, no de Ana", con el mismo
        // nombre de los dos lados). Acá se llega con dos teléfonos de verdad y distintos: uno de relleno ya no cuenta
        // como teléfono (ver bs_misma_persona).
        // La regla no cambia: con teléfono en los dos, manda el teléfono, porque dos personas pueden llamarse
        // igual. Lo que cambia es el cartel: dice que lo que no coincide es el teléfono, muestra los dos uno
        // debajo del otro (para ver si es otra persona o un error de tipeo) y dice qué revisar.
        if ($bs_de !== '' && $bs_tp !== '' && $celular !== '' && bs_misma_persona(['nombre' => $bs_de], ['nombre' => $nombre])) {
            // el teléfono, prolijo si se reconoce ("11 5555-1234"); si no, tal como está escrito, para que se vea qué tiene de más
            $bs_t = function ($x) { $l = bs_contrato_tel($x); return strlen(preg_replace('/\D/', '', $l)) === 10 ? $l : trim(preg_replace('/\s+/u', ' ', (string)$x)); };
            // Cada teléfono al principio de su renglón: quedan uno debajo del otro y se comparan dígito por dígito.
            // Después, qué hacer. Primero el N°, que es lo más seguro: emparejar los teléfonos para que cargue,
            // sin mirar de quién es cada uno, es justo el cruce que la regla evita.
            bs_error('El teléfono no coincide con el del presupuesto N° ' . $bs_nro . ".\n"
                . $bs_t($bs_tp) . ' en el presupuesto, a nombre de ' . $bs_de . "\n"
                . $bs_t($celular) . ' en el contrato, a nombre de ' . $nombre . "\n"
                . "Si son dos personas distintas, el N° del contrato está mal: corregilo.\n"
                . 'Si es la misma, dejá el mismo teléfono en los dos: el del presupuesto se cambia en Presupuestos, con el lápiz. No se cargó nada.', 409);
        }
        bs_error('El presupuesto N° ' . $bs_nro . ' es de ' . ($bs_de ?: 'otro cliente') . ', no de ' . $nombre . '. Revisá el N° del contrato. No se cargó nada.', 409);
    }
    // Si es de la misma persona, se aprovecha el material que el contrato no tenga escrito. Nada se
    // escribe en el presupuesto.
    if ($bs_cots && $material === '') { $bs_cot = end($bs_cots); $material = mb_substr(trim((string)(bs_tablero_material($bs_cot)[0] ?? '')), 0, 200); if ($concepto === '') $concepto = mb_substr(trim((string)($bs_cot['concepto'] ?? '')), 0, 200); }
}

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
                    'nota' => 'Obra cargada desde su contrato (Relación-Contrato)',
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

    // El N° compartido queda en la ficha desde el primer momento.
    if ($bs_nro !== '') {
        if (!is_dir(BS_TABLERO_DIR)) @mkdir(BS_TABLERO_DIR, 0755, true);
        $bs_ft = bs_lock_acquire(BS_TABLERO_LOCK);
        if ($bs_ft) {
            $bs_t = bs_tablero_leer();
            $bs_t['obras'][$target_nro . '-1'] = array_merge((array)($bs_t['obras'][$target_nro . '-1'] ?? []), ['nro_contrato' => $bs_nro]);
            bs_write_json_atomic(BS_TABLERO_FILE, $bs_t);
            bs_lock_release($bs_ft);
        }
    }
    // Y el contrato queda enlazado con su ficha acá mismo, no en un segundo pedido de la pantalla:
    // así no puede quedar una ficha suelta si ese segundo pedido falla.
    $bs_enlazado = false;
    $bs_fc = bs_lock_acquire(BS_CONTRATOS_LOCK);
    if ($bs_fc) {
        $bs_d = bs_contratos_leer();
        if (isset($bs_d['contratos'][$bs_cid]) && is_array($bs_d['contratos'][$bs_cid])) {
            $bs_d['contratos'][$bs_cid]['enlace'] = ['cliente_nro' => $target_nro, 'sub' => 1, 'at' => $now];
            $bs_d['contratos'][$bs_cid]['actualizado'] = $now;
            $bs_enlazado = bs_write_json_atomic(BS_CONTRATOS_FILE, $bs_d);
        }
        bs_lock_release($bs_fc);
    }
    bs_ok(['cliente_nro' => $target_nro, 'sub' => 1, 'enlazado' => $bs_enlazado, 'nro' => $bs_nro]);

} catch (Throwable $e) {
    bs_lock_release($fp);
    bs_error('Excepción: ' . $e->getMessage(), 500);
}
