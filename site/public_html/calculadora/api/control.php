<?php
// ============================================================
// control.php — Los recordatorios de la pestaña Control
// BlackStones Marmolería · Registry de Presupuestos
// ============================================================
// Solo administradores (permiso "reportes", el de la pestaña Control). Lo muestra
// /presupuestos/reportes.php, que desde el 18/11/2026 se llama "Control".
//
// REGLA (dueño, 18/11/2026): el recordatorio se hace ADENTRO del sistema, y sin ruido. Un
// recordatorio existe solo si tiene fecha, si una persona lo puede hacer hoy, y si desaparece
// cuando se hizo. Lo que es un estado (atrasadas, plazo vencido, sin contrato) vive en el tablero.
//
// Los recordatorios NO se guardan: se calculan cada vez leyendo el tablero. Lo único que guarda
// Control es el tilde "Hecho" de cada uno, en su propio archivo (bs-data/control/hechos.json).
// Nada más del sistema lee ese archivo: Control se puede borrar entero sin que se rompa nada.
//
//   GET  → { ok, hoy, recordatorios:[{id, titulo, detalle, para, cuando, total, confirmados,
//                                      link, completo, hecho:{at,por}|null, nuevos}] }
//   POST { accion:'hecho'|'deshacer', id } → { ok }
//
// LOS CUATRO TIPOS QUE HAY (cada uno lo pidió el dueño; para sumar otro, primero se le pregunta):
//  1. Confirmar los turnos de un día de medición o de instalación. Aparece 2 días hábiles antes
//     (un bloque de sábado y otro de lunes aparecen los dos el jueves) y se va cuando pasa ese día.
//     Se da por hecho solo si todas sus obras tienen "Turno confirmado".
//  2. Bajar el cierre del mes: solo el último día del mes. Se da por hecho solo cuando se baja.
//  3. Dar turno de instalación: obras en Por instalar sin día u hora cuyo plazo vence en 7 días o
//     menos (o ya venció). Se va solo cuando tienen turno.
//  4. Pasar a cortar: obras medidas hace más de 2 días que siguen en Por medir. Se va solo cuando pasan.
// Marcado a mano como hecho, cualquiera vuelve a aparecer si después se le suma una obra. Los tipos 3
// y 4 son de todos los días: el "Hecho" vale por hoy, y mañana vuelven si siguen sin resolverse.
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_tablero.php';

bs_require_permiso('reportes');

define('BS_CONTROL_DIR',  BS_DATA_DIR . '/control');
define('BS_CONTROL_FILE', BS_CONTROL_DIR . '/hechos.json');
define('BS_CONTROL_LOCK', BS_CONTROL_DIR . '/.write.lock');
define('BS_CONTROL_DIAS_ANTES', 2); // días hábiles (lunes a viernes) antes del turno

$DIAS  = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$HOY   = date('Y-m-d');
// Solo para las pruebas automáticas, con el servidor de desarrollo de PHP: fijar "hoy" (igual que cierre-mes.php).
if (php_sapi_name() === 'cli-server' && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)getenv('BS_FECHA_PRUEBA'))) $HOY = (string)getenv('BS_FECHA_PRUEBA');

/** El día en que el recordatorio empieza a mostrarse: N días hábiles antes del turno. */
function bs_control_desde($fecha, $habiles) {
    $t = strtotime($fecha . ' 12:00:00');
    while ($habiles > 0) {
        $t -= 86400;
        if (!in_array(intval(date('w', $t)), [0, 6], true)) $habiles--;
    }
    return date('Y-m-d', $t);
}

function bs_control_hechos() {
    $d = bs_read_json(BS_CONTROL_FILE);
    return (is_array($d) && is_array($d['hechos'] ?? null)) ? $d['hechos'] : [];
}

// ------------------------------------------------------------
// Los turnos que hay, por etapa y por día (solo lo que todavía no se hizo)
// ------------------------------------------------------------
$tablero = bs_tablero_leer();
$turnos = []; // [etapa][fecha] => [ ['key', 'confirmado', 'hora'] ]
$sin_turno = []; // Por instalar, sin instalar, sin día u hora, con el plazo encima: [ ['key', 'limite', 'sin_dia'] ]
$medidas = [];   // Por medir, ya medidas hace más de 2 días: [ ['key', 'medido', 'faltan'=>[labels]] ]
$en7 = date('Y-m-d', strtotime($HOY . ' 12:00:00 +7 days'));
$hace3 = date('Y-m-d', strtotime($HOY . ' 12:00:00 -3 days'));
foreach (@scandir(BS_CLIENTES_DIR) ?: [] as $f) {
    if (!preg_match('/^(\d{4,})\.json$/', $f)) continue;
    $cli = bs_read_json(BS_CLIENTES_DIR . '/' . $f);
    if (!is_array($cli)) continue;
    $nro = (string)($cli['cliente_nro'] ?? '');
    foreach ((array)($cli['cotizaciones'] ?? []) as $cot) {
        if (($cot['estado'] ?? '') !== 'aprobado') continue;
        $key = $nro . '-' . intval($cot['sub'] ?? 0);
        $obra = array_merge(bs_tablero_obra_vacia(), (array)($tablero['obras'][$key] ?? []));
        $et = isset($BS_ETAPAS[$obra['etapa']]) ? $obra['etapa'] : 'medir';
        $def = $BS_ETAPAS[$et];
        $hecha = ($def['cierra'] ?? '') !== '' && !empty($obra['checks'][$def['cierra']]); // ya se midió / ya se instaló
        // 3. Por instalar sin turno completo, con el plazo de entrega a 7 días o menos (mismo plazo que muestra el tablero)
        if ($et === 'instalar' && !$hecha && (!bs_tablero_fecha_valida($obra['fecha']) || !bs_tablero_hora_valida($obra['hora']))) {
            $lim = bs_tablero_fecha_valida((string)$obra['plazo']) ? (string)$obra['plazo'] : (bs_tablero_fecha_valida($obra['medido_at']) ? date('Y-m-d', strtotime($obra['medido_at'] . ' +' . BS_PLAZO_DIAS . ' days')) : '');
            if ($lim !== '' && $lim <= $en7) $sin_turno[] = ['key' => $key, 'limite' => $lim, 'sin_dia' => !bs_tablero_fecha_valida($obra['fecha'])];
        }
        // 4. Por medir, medida hace más de 2 días
        if ($et === 'medir' && $hecha && bs_tablero_fecha_valida($obra['medido_at']) && (string)$obra['medido_at'] <= $hace3) {
            $faltan = [];
            foreach ($def['checks'] as $cid => $label) { if (empty($obra['checks'][$cid]) && !bs_tablero_tilde_liberado($et, $cid, $obra)) $faltan[] = $label; }
            $medidas[] = ['key' => $key, 'medido' => (string)$obra['medido_at'], 'faltan' => $faltan];
        }
        $conf = (string)($def['confirma'] ?? '');
        if ($conf === '' || !bs_tablero_fecha_valida($obra['fecha'])) continue;        // etapa sin turno, o sin día puesto
        if ($hecha) continue;
        $turnos[$et][(string)$obra['fecha']][] = ['key' => $key, 'confirmado' => !empty($obra['checks'][$conf]), 'hora' => (string)$obra['hora']];
    }
}

$hechos = bs_control_hechos();
$rec = [];
foreach ($turnos as $et => $por_dia) {
    foreach ($por_dia as $fecha => $lista) {
        if ($fecha < $HOY || bs_control_desde($fecha, BS_CONTROL_DIAS_ANTES) > $HOY) continue; // ya pasó, o todavía falta
        $id = 'turnos-' . $et . '-' . $fecha;
        $keys = array_column($lista, 'key'); sort($keys);
        $n = count($lista);
        $ok = count(array_filter($lista, function ($x) { return $x['confirmado']; }));
        $sin_hora = count(array_filter($lista, function ($x) { return $x['hora'] === ''; }));
        $h = is_array($hechos[$id] ?? null) ? $hechos[$id] : null;
        // marcado a mano, pero después se sumó un turno a ese día: vuelve a estar pendiente
        $nuevos = $h ? count(array_diff($keys, (array)($h['keys'] ?? []))) : 0;
        if ($h && $nuevos > 0) $h = null;
        $falta = intval(round((strtotime($fecha . ' 12:00:00') - strtotime($HOY . ' 12:00:00')) / 86400));
        $que = $et === 'medir' ? 'medición' : 'instalación';
        $det = [$n === 1 ? '1 turno' : $n . ' turnos', $ok === $n ? ($n === 1 ? 'confirmado' : 'todos confirmados') : ($ok . ' de ' . $n . ($ok === 1 ? ' confirmado' : ' confirmados'))];
        if ($sin_hora) $det[] = $sin_hora === 1 ? '1 sin hora' : $sin_hora . ' sin hora';
        if ($nuevos) $det[] = $nuevos === 1 ? 'se sumó 1 turno después de marcarlo' : 'se sumaron ' . $nuevos . ' turnos después de marcarlo';
        $rec[] = [
            'id' => $id, 'para' => $fecha, 'etapa' => $et,
            'titulo' => 'Confirmar los turnos de ' . $que . ' del ' . $DIAS[intval(date('w', strtotime($fecha . ' 12:00:00')))] . ' ' . date('d/m', strtotime($fecha . ' 12:00:00')),
            'detalle' => implode(' · ', $det),
            'cuando' => $falta === 0 ? 'es hoy' : ($falta === 1 ? 'es mañana' : 'en ' . $falta . ' días'),
            'falta' => $falta, 'total' => $n, 'confirmados' => $ok, 'nuevos' => $nuevos,
            'completo' => $ok === $n,
            'hecho' => $h ? ['at' => (string)($h['at'] ?? ''), 'por' => (string)($h['por'] ?? '')] : null,
            'link' => '/presupuestos/calendario.php?crono=' . ($et === 'medir' ? 'med' : 'inst') . '&dia=' . $fecha,
            'link_txt' => 'Ver la ruta →', 'orden' => '0' . $fecha . ($et === 'medir' ? '0' : '1'),
            'keys' => $keys,
        ];
    }
}

// Lo que tienen en común los otros tres: el "Hecho" a mano (con quién y cuándo), que se cae si se suma una obra.
$dia_txt = function ($f) use ($DIAS) { $t = strtotime($f . ' 12:00:00'); return $DIAS[intval(date('w', $t))] . ' ' . date('d/m', $t); };
$dias_a = function ($f) use ($HOY) { return intval(round((strtotime($f . ' 12:00:00') - strtotime($HOY . ' 12:00:00')) / 86400)); };
$armar = function ($id, $keys, array $r) use ($hechos) {
    sort($keys);
    $h = is_array($hechos[$id] ?? null) ? $hechos[$id] : null;
    $nuevos = $h ? count(array_diff($keys, (array)($h['keys'] ?? []))) : 0;
    if ($h && $nuevos > 0) $h = null;
    if ($nuevos) $r['detalle'] .= ' · ' . ($nuevos === 1 ? 'se sumó 1 después de marcarlo' : 'se sumaron ' . $nuevos . ' después de marcarlo');
    return array_merge(['id' => $id, 'total' => count($keys), 'confirmados' => 0, 'nuevos' => $nuevos, 'completo' => false, 'etapa' => '',
        'hecho' => $h ? ['at' => (string)($h['at'] ?? ''), 'por' => (string)($h['por'] ?? '')] : null, 'keys' => $keys], $r);
};

// 2. Bajar el cierre del mes: solo el último día.
if ($HOY === date('Y-m-t', strtotime($HOY . ' 12:00:00'))) {
    $MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $mes = substr($HOY, 0, 7);
    // ¿ya se bajó? cierre-mes.php lo deja anotado en el registro ("que=cierre_del_mes_AAAA-MM")
    $bajado = '';
    $dir = dirname(auth_usuarios_archivo());
    foreach (array_unique([$mes, date('Y-m')]) as $m) {
        foreach (@file($dir . '/registro-' . $m . '.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $l) {
            $p = explode("\t", $l);
            if (($p[2] ?? '') === 'cierre-mes.php' && strpos((string)($p[4] ?? ''), 'que=cierre_del_mes_' . $mes) !== false) $bajado = substr((string)$p[0], 11, 5);
        }
    }
    $rec[] = $armar('cierre-' . $HOY, [], [
        'para' => $HOY, 'titulo' => 'Bajar el cierre de ' . $MESES[intval(substr($HOY, 5, 2)) - 1],
        'detalle' => $bajado !== '' ? 'Ya se bajó hoy, a las ' . $bajado . ' hs' : 'Hoy es el último día del mes: se baja, se imprime y va a la carpeta',
        'cuando' => 'es hoy', 'falta' => 0, 'completo' => $bajado !== '',
        'link' => '#cajaCierre', 'link_txt' => 'Ir al cierre →', 'orden' => '1' . $HOY . '2',
    ]);
}

// 3. Dar turno de instalación.
if ($sin_turno) {
    usort($sin_turno, function ($a, $b) { return strcmp($a['limite'], $b['limite']); });
    $n = count($sin_turno); $primero = $sin_turno[0]['limite']; $f = $dias_a($primero);
    $venc = count(array_filter($sin_turno, function ($x) use ($HOY) { return $x['limite'] < $HOY; }));
    $det = ['Vence' . ($n === 1 ? '' : 'n') . ' en 7 días o menos y no tiene' . ($n === 1 ? '' : 'n') . ' turno', ($n === 1 ? 'vence el ' : 'la primera, el ') . $dia_txt($primero)];
    if ($venc) $det[] = $venc === 1 ? '1 ya vencida' : $venc . ' ya vencidas';
    $rec[] = $armar('dar-turno-' . $HOY, array_column($sin_turno, 'key'), [
        'para' => $primero, 'titulo' => 'Dar turno de instalación a ' . ($n === 1 ? '1 obra' : $n . ' obras'),
        'detalle' => implode(' · ', $det),
        'cuando' => $f < 0 ? 'plazo vencido' : ($f === 0 ? 'vence hoy' : ($f === 1 ? 'vence mañana' : 'vence en ' . $f . ' días')), 'falta' => $f,
        'link' => '/presupuestos/calendario.php?crono=inst' . (array_filter($sin_turno, function ($x) { return $x['sin_dia']; }) ? '&dia=sin' : ''),
        'link_txt' => 'Dar los turnos →', 'orden' => '1' . $HOY . '3',
    ]);
}

// 4. Pasar a cortar.
if ($medidas) {
    usort($medidas, function ($a, $b) { return strcmp($a['medido'], $b['medido']); });
    $n = count($medidas); $vieja = $medidas[0]['medido']; $hace = -$dias_a($vieja);
    $cuenta = [];
    foreach ($medidas as $m) foreach ($m['faltan'] as $l) $cuenta[$l] = ($cuenta[$l] ?? 0) + 1;
    arsort($cuenta);
    $det = ['Lleva' . ($n === 1 ? '' : 'n') . ' más de 2 días en Por medir', ($n === 1 ? 'se midió el ' : 'la más vieja se midió el ') . $dia_txt($vieja)];
    if ($cuenta) $det[] = 'falta: ' . implode(', ', array_map(function ($l, $c) use ($n) { return $l . ($n > 1 ? ' (' . $c . ')' : ''); }, array_slice(array_keys($cuenta), 0, 3), array_slice(array_values($cuenta), 0, 3)));
    $rec[] = $armar('pasar-cortar-' . $HOY, array_column($medidas, 'key'), [
        'para' => $HOY, 'titulo' => 'Pasar a cortar ' . ($n === 1 ? '1 obra ya medida' : $n . ' obras ya medidas'),
        'detalle' => implode(' · ', $det),
        'cuando' => 'hace ' . $hace . ' días', 'falta' => $hace >= 5 ? 0 : 9,
        'link' => '/presupuestos/tablero.php', 'link_txt' => 'Ver en el tablero →', 'orden' => '1' . $HOY . '4',
    ]);
}

// Primero los turnos, lo más cercano arriba (mismo día: medición antes que instalación); después el resto.
usort($rec, function ($a, $b) { return strcmp($a['orden'], $b['orden']); });

// ------------------------------------------------------------
// GET
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    bs_ok(['hoy' => $HOY, 'recordatorios' => array_map(function ($r) { unset($r['keys'], $r['etapa'], $r['orden']); return $r; }, $rec)]);
}

// ------------------------------------------------------------
// POST — tildar o destildar "Hecho"
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') bs_error('Método no permitido', 405);
bs_check_csrf();
$body = json_decode(@file_get_contents('php://input'), true);
if (!is_array($body)) bs_error('Body JSON inválido');
$accion = (string)($body['accion'] ?? '');
$id = (string)($body['id'] ?? '');
if (!in_array($accion, ['hecho', 'deshacer'], true)) bs_error('accion inválida');
$uno = null;
foreach ($rec as $r) { if ($r['id'] === $id) $uno = $r; }
if (!$uno) bs_error('Ese recordatorio ya no está: el turno cambió de día o ya pasó. Actualizá la página.', 409);

if (!is_dir(BS_CONTROL_DIR) && !@mkdir(BS_CONTROL_DIR, 0755, true)) bs_error('No se pudo crear la carpeta de Control', 500);
$fp = bs_lock_acquire(BS_CONTROL_LOCK);
if (!$fp) bs_error('No se pudo adquirir lock', 500);
$hechos = bs_control_hechos();
// lo viejo se va solo: un tilde de un turno de hace más de 45 días no sirve para nada
foreach ($hechos as $k => $h) { if (!preg_match('/(\d{4}-\d{2}-\d{2})$/', (string)$k, $m) || $m[1] < date('Y-m-d', time() - 45 * 86400)) unset($hechos[$k]); }
if ($accion === 'hecho') {
    $u = auth_usuario();
    $hechos[$id] = ['at' => date('c'), 'por' => $u ? (trim((string)($u['nombre'] ?? '')) ?: (string)($u['usuario'] ?? '')) : '', 'keys' => $uno['keys']];
} else {
    unset($hechos[$id]);
}
$bien = bs_write_json_atomic(BS_CONTROL_FILE, ['version' => 1, 'hechos' => $hechos]);
bs_lock_release($fp);
if (!$bien) bs_error('No se pudo guardar', 500);
bs_ok(['id' => $id, 'hecho' => $accion === 'hecho']);
