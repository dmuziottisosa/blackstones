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
// HOY HAY UN SOLO TIPO: confirmar los turnos de un día de medición o de instalación. Aparece
// 2 días hábiles antes (un bloque de sábado y otro de lunes aparecen los dos el jueves) y se va
// cuando pasa ese día. Se da por hecho solo si todas sus obras tienen "Turno confirmado".
// Marcado a mano como hecho, vuelve a aparecer si después se suma un turno a ese día.
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
        $conf = (string)($def['confirma'] ?? '');
        if ($conf === '' || !bs_tablero_fecha_valida($obra['fecha'])) continue;        // etapa sin turno, o sin día puesto
        if (($def['cierra'] ?? '') !== '' && !empty($obra['checks'][$def['cierra']])) continue; // ya se midió / ya se instaló
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
            'keys' => $keys,
        ];
    }
}
// Lo más cercano primero; mismo día, medición antes que instalación.
usort($rec, function ($a, $b) { return strcmp($a['para'] . ($a['etapa'] === 'medir' ? '0' : '1'), $b['para'] . ($b['etapa'] === 'medir' ? '0' : '1')); });

// ------------------------------------------------------------
// GET
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    bs_ok(['hoy' => $HOY, 'recordatorios' => array_map(function ($r) { unset($r['keys'], $r['etapa']); return $r; }, $rec)]);
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
