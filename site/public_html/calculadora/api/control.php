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
//   GET  → { ok, hoy, recordatorios:[{id, grupo, titulo, detalle, para, cuando, total, confirmados, link, link_txt,
//                                      fichas_txt, fichas:[{txt,link}], fichas_mas, completo, hecho:{at,por}|null, nuevos}] }
//   POST { accion:'hecho'|'deshacer', id } → { ok }
//
// QUÉ ESTÁ PRENDIDO (dueño, 18/11/2026): "Por los momentos vamos a dejar solo aviso de confirmación medir
// y aviso de confirmación instalar. Nada más." Son los dos de "Confirmar los turnos": aparecen 2 días
// hábiles antes del día y quedan hasta ese día inclusive. Abajo de cada uno dice qué fichas faltan confirmar.
//
// Los demás (aprobados el mismo día y apagados horas después, al verlos en uso) siguen escritos y con sus
// pruebas, pero NO se muestran: ver $BS_CONTROL_PRENDIDOS. Para volver a prender uno se suma su tipo ahí,
// y antes se le pregunta al dueño.
//  Turnos           · datos-medir / datos-instalar: el día hábil anterior, lo que falta para ir.
//                   · pasadas-medir / pasadas-instalar: lo que ya pasó y no quedó marcado (14 días).
//  Taller y plazo   · dar-turno · plazo-taller · placa · pasar-cortar · cerrar-instaladas
//  Plata y contratos· sena · contratos-sin-cargar · adjuntar
//  Fin de mes       · cierre
// Un renglón por tipo (los de turnos, uno por día). Marcado a mano como hecho, cualquiera vuelve a
// aparecer si después se le suma una obra.
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_tablero.php';

bs_require_permiso('reportes');

define('BS_CONTROL_DIR',  BS_DATA_DIR . '/control');
define('BS_CONTROL_FILE', BS_CONTROL_DIR . '/hechos.json');
define('BS_CONTROL_LOCK', BS_CONTROL_DIR . '/.write.lock');
define('BS_CONTROL_DIAS_ANTES', 2); // días hábiles (lunes a viernes) antes del turno

// Los tipos de recordatorio que se muestran. El tipo es el id sin la fecha del final.
$BS_CONTROL_PRENDIDOS = ['turnos-medir', 'turnos-instalar'];
// Solo para las pruebas automáticas, con el servidor de desarrollo de PHP: probar también los apagados.
$BS_CONTROL_TODOS = php_sapi_name() === 'cli-server' && getenv('BS_CONTROL_TODOS') === '1';

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
// Las obras vivas del tablero, con lo que cada recordatorio necesita saber
// ------------------------------------------------------------
$tablero = bs_tablero_leer();
$obras = [];
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
        $checks = is_array($obra['checks']) ? $obra['checks'] : [];
        $faltan = [];
        foreach ($def['checks'] as $cid => $label) { if (empty($checks[$cid]) && !bs_tablero_tilde_liberado($et, $cid, $obra)) $faltan[] = $label; }
        $fecha_ok = bs_tablero_fecha_valida($obra['fecha']);
        $medido = bs_tablero_fecha_valida($obra['medido_at']) ? (string)$obra['medido_at'] : '';
        $inst = (string)($obra['instalado_at'] ?? '');   // el día en que se marcó "Instalado"
        if ($inst === '' && is_array($obra['tildes_at'] ?? null)) $inst = (string)($obra['tildes_at']['instalado'] ?? '');
        $obras[] = [
            'key' => $key, 'et' => $et, 'checks' => $checks, 'faltan' => $faltan,
            'fecha' => $fecha_ok ? (string)$obra['fecha'] : '', 'hora' => ($fecha_ok && bs_tablero_hora_valida($obra['hora'])) ? (string)$obra['hora'] : '',
            'hecha' => ($def['cierra'] ?? '') !== '' && !empty($checks[$def['cierra']]),            // ya se midió / ya se instaló
            'conf' => !empty($checks[(string)($def['confirma'] ?? '')]),
            'medido' => $medido, 'instalado' => bs_tablero_fecha_valida($inst) ? $inst : '',
            // el mismo plazo de entrega que muestra el tablero: el puesto a mano, o medición + 20 días corridos
            'limite' => bs_tablero_fecha_valida((string)$obra['plazo']) ? (string)$obra['plazo'] : ($medido !== '' ? date('Y-m-d', strtotime($medido . ' +' . BS_PLAZO_DIAS . ' days')) : ''),
            'dir' => trim((string)($cli['cliente']['direccion'] ?? '')), 'cel' => trim((string)($cli['cliente']['celular'] ?? '')),
            // cómo se nombra la ficha: igual que en el tablero (el N° de una ficha a mano es el de su contrato, si tiene)
            'nombre' => trim((string)($cli['cliente']['nombre'] ?? '')),
            'nro' => empty($cot['manual']) ? $key : trim((string)($obra['nro_contrato'] ?? '')),
        ];
    }
}

// ------------------------------------------------------------
// Herramientas comunes
// ------------------------------------------------------------
$hechos = bs_control_hechos();
$rec = [];
$dia_txt = function ($f) use ($DIAS) { $t = strtotime($f . ' 12:00:00'); return $DIAS[intval(date('w', $t))] . ' ' . date('d/m', $t); };
$dias_a  = function ($f) use ($HOY) { return intval(round((strtotime($f . ' 12:00:00') - strtotime($HOY . ' 12:00:00')) / 86400)); };
$mas     = function ($n) use ($HOY) { return date('Y-m-d', strtotime($HOY . ' 12:00:00 ' . ($n >= 0 ? '+' : '') . $n . ' days')); };
$s       = function ($n, $uno, $varios) { return $n === 1 ? $uno : str_replace('#', (string)$n, $varios); };
$cuando  = function ($f) use ($dias_a) { $x = $dias_a($f); return $x < 0 ? 'ya pasó' : ($x === 0 ? 'es hoy' : ($x === 1 ? 'es mañana' : 'en ' . $x . ' días')); };
$vence   = function ($f) use ($dias_a) { $x = $dias_a($f); return $x < 0 ? 'plazo vencido' : ($x === 0 ? 'vence hoy' : ($x === 1 ? 'vence mañana' : 'vence en ' . $x . ' días')); };
$hace    = function ($f) use ($dias_a) { $x = -$dias_a($f); return $x <= 0 ? 'hoy' : ($x === 1 ? 'ayer' : 'hace ' . $x . ' días'); };
// Los tildes que más faltan en un grupo de obras: "Medición aprobada (2), Trajo la seña (1)".
$que_falta = function ($lista) {
    $c = []; $n = count($lista);
    foreach ($lista as $o) foreach ($o['faltan'] as $l) $c[$l] = ($c[$l] ?? 0) + 1;
    arsort($c);
    $out = [];
    foreach (array_slice($c, 0, 3, true) as $l => $k) $out[] = $l . ($n > 1 ? ' (' . $k . ')' : '');
    return $out ? 'falta: ' . implode(', ', $out) : '';
};
// Arma un recordatorio: le pone el "Hecho" a mano (con quién y cuándo), que se cae si después se le suma algo.
// $grupo: 1 Turnos · 2 Taller y plazo · 3 Plata y contratos · 4 Fin de mes.
$GRUPOS = [1 => 'Turnos', 2 => 'Taller y plazo', 3 => 'Plata y contratos', 4 => 'Fin de mes'];
$sec = 0;
$por_key = array_column($obras, null, 'key');
define('BS_CONTROL_FICHAS', 6);   // cuántas fichas se nombran abajo de un recordatorio; el resto va como "y N más"
$armar = function ($grupo, $id, $keys, array $r) use ($hechos, $GRUPOS, $BS_CONTROL_PRENDIDOS, $BS_CONTROL_TODOS, $por_key, &$sec, &$rec) {
    if (!$BS_CONTROL_TODOS && !in_array(substr($id, 0, -11), $BS_CONTROL_PRENDIDOS, true)) return;   // apagado
    $keys = array_values(array_map('strval', $keys)); sort($keys);
    $ord = (string)($r['ord'] ?? ($r['para'] ?? '')); unset($r['ord']);   // dentro del grupo: por fecha, lo más cercano arriba
    // Qué fichas son (dueño, 18/11/2026: "abajito debe aparecer qué ficha es"): nombre y N°, con link a la ficha.
    $fk = $r['fichas_keys'] ?? $keys; unset($r['fichas_keys']);
    $fichas = [];
    foreach ($fk as $k) {
        $o = $por_key[$k] ?? null;
        if (!$o) continue;
        $fichas[] = ['txt' => ($o['nombre'] !== '' ? $o['nombre'] : 'Sin nombre') . ($o['nro'] !== '' ? ' (' . $o['nro'] . ')' : ''), 'link' => '/presupuestos/tablero.php?q=' . rawurlencode($k)];
    }
    $h = is_array($hechos[$id] ?? null) ? $hechos[$id] : null;
    $nuevos = $h ? count(array_diff($keys, (array)($h['keys'] ?? []))) : 0;
    if ($h && $nuevos > 0) $h = null;
    if ($nuevos) $r['detalle'] .= ' · ' . ($nuevos === 1 ? 'se sumó 1 después de marcarlo' : 'se sumaron ' . $nuevos . ' después de marcarlo');
    $rec[] = array_merge(['id' => $id, 'grupo' => $GRUPOS[$grupo], 'total' => count($keys), 'confirmados' => 0, 'nuevos' => $nuevos, 'completo' => false, 'falta' => 9,
        'link_txt' => 'Ver en el tablero →', 'link' => '/presupuestos/tablero.php',
        'fichas_txt' => count($fichas) === 1 ? 'Ficha' : 'Fichas', 'fichas' => array_slice($fichas, 0, BS_CONTROL_FICHAS), 'fichas_mas' => max(0, count($fichas) - BS_CONTROL_FICHAS),
        'hecho' => $h ? ['at' => (string)($h['at'] ?? ''), 'por' => (string)($h['por'] ?? '')] : null,
        'keys' => $keys, 'orden' => sprintf('%d%s%03d', $grupo, $ord, $sec++)], $r);
};
$filtrar = function ($fn) use ($obras) { return array_values(array_filter($obras, $fn)); };
$mas_vieja = function ($lista, $campo) { $v = array_column($lista, $campo); sort($v); return $v[0]; };

// ============================================================
// 1. TURNOS
// ============================================================
foreach (['medir' => ['medición', 'med', 'Medido'], 'instalar' => ['instalación', 'inst', 'Instalado']] as $et => $T) {
    $por_dia = []; $pasadas = [];
    foreach ($obras as $o) {
        if ($o['et'] !== $et || $o['fecha'] === '' || $o['hecha']) continue;
        if ($o['fecha'] >= $HOY) $por_dia[$o['fecha']][] = $o;
        elseif ($o['fecha'] >= $mas(-14)) $pasadas[] = $o;
    }
    ksort($por_dia);
    foreach ($por_dia as $fecha => $lista) {
        $link = '/presupuestos/calendario.php?crono=' . $T[1] . '&dia=' . $fecha;
        // 1a. Confirmar los turnos: dos días hábiles antes, y hasta el mismo día.
        if (bs_control_desde($fecha, BS_CONTROL_DIAS_ANTES) <= $HOY) {
            $n = count($lista);
            $ok = count(array_filter($lista, function ($x) { return $x['conf']; }));
            $sin_hora = count(array_filter($lista, function ($x) { return $x['hora'] === ''; }));
            $pend = array_values(array_filter($lista, function ($x) { return !$x['conf']; }));
            usort($pend, function ($a, $b) { return strcmp($a['hora'] === '' ? '99' : $a['hora'], $b['hora'] === '' ? '99' : $b['hora']); });
            $sin_conf = array_column($pend, 'key');
            $det = [$s($n, '1 turno', '# turnos'), $ok === $n ? $s($n, 'confirmado', 'todos confirmados') : ($ok . ' de ' . $n . ($ok === 1 ? ' confirmado' : ' confirmados'))];
            if ($sin_hora) $det[] = $s($sin_hora, '1 sin hora', '# sin hora');
            $armar(1, 'turnos-' . $et . '-' . $fecha, array_column($lista, 'key'), [
                'para' => $fecha, 'titulo' => 'Confirmar los turnos de ' . $T[0] . ' del ' . $dia_txt($fecha),
                'detalle' => implode(' · ', $det), 'cuando' => $cuando($fecha), 'falta' => $dias_a($fecha),
                'confirmados' => $ok, 'completo' => $ok === $n, 'link' => $link, 'link_txt' => 'Ver la ruta →',
                'fichas_txt' => 'Falta confirmar', 'fichas_keys' => $sin_conf,
            ]);
        }
        // 1b. El día hábil anterior: lo que falta para poder ir. Solo aparece si falta algo.
        if (bs_control_desde($fecha, 1) <= $HOY) {
            if ($et === 'medir') {
                $a = array_filter($lista, function ($x) { return $x['dir'] === ''; }); $b = array_filter($lista, function ($x) { return $x['cel'] === ''; });
                $det = array_filter([$a ? $s(count($a), '1 sin dirección', '# sin dirección') : '', $b ? $s(count($b), '1 sin teléfono', '# sin teléfono') : '']);
                $tit = 'Completar los datos de la medición del ' . $dia_txt($fecha);
            } else {
                $a = array_filter($lista, function ($x) { return empty($x['checks']['equipo']); }); $b = array_filter($lista, function ($x) { return empty($x['checks']['dir_ok']); });
                $det = array_filter([$a ? $s(count($a), '1 sin instaladores', '# sin instaladores') : '', $b ? $s(count($b), '1 sin dirección confirmada', '# sin dirección confirmada') : '']);
                $tit = 'Preparar la instalación del ' . $dia_txt($fecha);
            }
            if ($det) $armar(1, 'datos-' . $et . '-' . $fecha, array_unique(array_merge(array_column($a, 'key'), array_column($b, 'key'))), [
                'para' => $fecha, 'titulo' => $tit, 'detalle' => implode(' · ', $det), 'cuando' => $cuando($fecha), 'falta' => $dias_a($fecha),
                'link' => $link, 'link_txt' => 'Ver la ruta →',
            ]);
        }
    }
    // 1c. Lo que ya pasó y no quedó marcado: o no se fue (reprogramar) o falta tildar.
    if ($pasadas) {
        $n = count($pasadas); $vieja = $mas_vieja($pasadas, 'fecha');
        $armar(1, 'pasadas-' . $et . '-' . $HOY, array_column($pasadas, 'key'), [
            'para' => $HOY, 'falta' => 0, 'ord' => '0',   // lo que ya pasó va arriba de todo
            'titulo' => 'Marcar o reprogramar ' . ($et === 'medir' ? $s($n, '1 medición que ya pasó', '# mediciones que ya pasaron') : $s($n, '1 instalación que ya pasó', '# instalaciones que ya pasaron')),
            'detalle' => ($et === 'medir' ? 'Si se midió, falta marcar "Medido": de ahí arrancan los 20 días' : 'Si se instaló, falta marcar "Instalado"') . ' · ' . $s($n, 'era del ', 'la más vieja era del ') . $dia_txt($vieja),
            'cuando' => $hace($vieja), 'link' => '/presupuestos/calendario.php?crono=' . $T[1], 'link_txt' => 'Ver la ruta →',
        ]);
    }
}

// ============================================================
// 2. TALLER Y PLAZO
// ============================================================
// 2a. Dar turno de instalación: en Por instalar, sin día u hora, con el plazo a 7 días o menos.
$l = $filtrar(function ($o) use ($mas) { return $o['et'] === 'instalar' && !$o['hecha'] && ($o['fecha'] === '' || $o['hora'] === '') && $o['limite'] !== '' && $o['limite'] <= $mas(7); });
if ($l) {
    $n = count($l); $primero = $mas_vieja($l, 'limite');
    $venc = count(array_filter($l, function ($x) use ($HOY) { return $x['limite'] < $HOY; }));
    $det = [$s($n, 'Vence en 7 días o menos y no tiene turno', 'Vencen en 7 días o menos y no tienen turno'), $s($n, 'vence el ', 'la primera, el ') . $dia_txt($primero)];
    if ($venc) $det[] = $s($venc, '1 ya vencida', '# ya vencidas');
    $armar(2, 'dar-turno-' . $HOY, array_column($l, 'key'), [
        'para' => $primero, 'titulo' => 'Dar turno de instalación a ' . $s($n, '1 obra', '# obras'), 'detalle' => implode(' · ', $det),
        'cuando' => $vence($primero), 'falta' => $dias_a($primero),
        'link' => '/presupuestos/calendario.php?crono=inst' . (array_filter($l, function ($x) { return $x['fecha'] === ''; }) ? '&dia=sin' : ''), 'link_txt' => 'Dar los turnos →',
    ]);
}
// 2b. Plazo en riesgo en el taller: vence en 5 días o menos y todavía está en Por cortar o Por fabricar.
$l = $filtrar(function ($o) use ($mas) { return in_array($o['et'], ['cortar', 'fabricar'], true) && $o['limite'] !== '' && $o['limite'] <= $mas(5); });
if ($l) {
    $n = count($l); $primero = $mas_vieja($l, 'limite');
    $venc = count(array_filter($l, function ($x) use ($HOY) { return $x['limite'] < $HOY; }));
    $c = count(array_filter($l, function ($x) { return $x['et'] === 'cortar'; })); $fb = $n - $c;
    $det = array_filter([$c ? $c . ' en Por cortar' : '', $fb ? $fb . ' en Por fabricar' : '', $s($n, 'vence el ', 'la primera vence el ') . $dia_txt($primero), $venc ? $s($venc, '1 ya vencida', '# ya vencidas') : '']);
    $armar(2, 'plazo-taller-' . $HOY, array_column($l, 'key'), [
        'para' => $primero, 'titulo' => 'Apurar ' . $s($n, '1 obra que vence en 5 días o menos', '# obras que vencen en 5 días o menos'), 'detalle' => implode(' · ', $det),
        'cuando' => $vence($primero), 'falta' => $dias_a($primero),
    ]);
}
// 2c. Falta la placa: ya medida y sin "Tenemos la placa". Sin placa no se corta, y pedirla lleva días.
$l = $filtrar(function ($o) { return $o['et'] === 'medir' && $o['hecha'] && empty($o['checks']['placa']); });
if ($l) {
    $n = count($l); $con = array_filter($l, function ($x) { return $x['medido'] !== ''; }); $vieja = $con ? $mas_vieja($con, 'medido') : $HOY;
    $armar(2, 'placa-' . $HOY, array_column($l, 'key'), [
        'para' => $HOY, 'titulo' => 'Pedir la placa de ' . $s($n, '1 obra ya medida', '# obras ya medidas'),
        'detalle' => 'Sin placa no se corta · ' . $s($n, 'se midió el ', 'la más vieja se midió el ') . $dia_txt($vieja),
        'cuando' => $hace($vieja), 'falta' => -$dias_a($vieja) >= 3 ? 0 : 9,
    ]);
}
// 2d. Pasar a cortar: medida hace más de 2 días y sigue en Por medir. Las que esperan la placa o la seña no se
//     repiten acá: ya están en su propio recordatorio, que dice qué las frena.
$l = $filtrar(function ($o) use ($mas) { return $o['et'] === 'medir' && $o['hecha'] && $o['medido'] !== '' && $o['medido'] <= $mas(-3) && !empty($o['checks']['placa']) && !empty($o['checks']['sena']); });
if ($l) {
    $n = count($l); $vieja = $mas_vieja($l, 'medido');
    $det = array_filter([$s($n, 'Lleva más de 2 días en Por medir', 'Llevan más de 2 días en Por medir'), $s($n, 'se midió el ', 'la más vieja se midió el ') . $dia_txt($vieja), $que_falta($l)]);
    $armar(2, 'pasar-cortar-' . $HOY, array_column($l, 'key'), [
        'para' => $HOY, 'titulo' => 'Pasar a cortar ' . $s($n, '1 obra ya medida', '# obras ya medidas'), 'detalle' => implode(' · ', $det),
        'cuando' => $hace($vieja), 'falta' => -$dias_a($vieja) >= 5 ? 0 : 9,
    ]);
}
// 2e. Instaladas sin cerrar: instalada hace más de 2 días y sigue en Por instalar.
$l = $filtrar(function ($o) use ($mas) { return $o['et'] === 'instalar' && $o['hecha'] && $o['instalado'] !== '' && $o['instalado'] <= $mas(-3); });
if ($l) {
    $n = count($l); $vieja = $mas_vieja($l, 'instalado');
    $det = array_filter([$s($n, 'Lleva más de 2 días en Por instalar', 'Llevan más de 2 días en Por instalar'), $s($n, 'se instaló el ', 'la más vieja se instaló el ') . $dia_txt($vieja), $que_falta($l)]);
    $armar(2, 'cerrar-instaladas-' . $HOY, array_column($l, 'key'), [
        'para' => $HOY, 'titulo' => 'Cerrar ' . $s($n, '1 obra ya instalada', '# obras ya instaladas'), 'detalle' => implode(' · ', $det),
        'cuando' => $hace($vieja), 'falta' => -$dias_a($vieja) >= 5 ? 0 : 9,
    ]);
}

// ============================================================
// 3. PLATA Y CONTRATOS
// ============================================================
// 3a. Medida sin seña: para no fabricar sin haber cobrado.
$l = $filtrar(function ($o) { return $o['et'] === 'medir' && $o['hecha'] && empty($o['checks']['sena']); });
if ($l) {
    $n = count($l); $con = array_filter($l, function ($x) { return $x['medido'] !== ''; }); $vieja = $con ? $mas_vieja($con, 'medido') : $HOY;
    $armar(3, 'sena-' . $HOY, array_column($l, 'key'), [
        'para' => $HOY, 'titulo' => 'Cobrar la seña de ' . $s($n, '1 obra ya medida', '# obras ya medidas'),
        'detalle' => 'Para no fabricar sin seña · ' . $s($n, 'se midió el ', 'la más vieja se midió el ') . $dia_txt($vieja),
        'cuando' => $hace($vieja), 'falta' => -$dias_a($vieja) >= 3 ? 0 : 9,
    ]);
}
// Los dos que siguen leen los contratos. Si Relación-Contrato no está instalada, simplemente no aparecen.
if (($BS_CONTROL_TODOS || array_intersect(['contratos-sin-cargar', 'adjuntar'], $BS_CONTROL_PRENDIDOS)) && is_file(__DIR__ . '/_contratos.php')) {
    require_once __DIR__ . '/_contratos.php';
    $contratos = (array)(bs_contratos_leer()['contratos'] ?? []);
    // 3b. Contrato cargado hace 2 días o más que no está en el tablero: una obra vendida que nadie sigue.
    //     Solo los de los últimos 30 días (por la fecha del contrato): los históricos importados no cuentan.
    $sueltos = [];
    foreach ($contratos as $id => $c) {
        if (!is_array($c) || !empty($c['prueba']) || is_array($c['enlace'] ?? null)) continue;
        $creado = substr((string)($c['creado'] ?? ''), 0, 10);
        if (!bs_tablero_fecha_valida($creado) || $creado > $mas(-2)) continue;
        $fc = bs_tablero_fecha_valida((string)($c['fecha'] ?? '')) ? (string)$c['fecha'] : $creado;
        if ($fc < $mas(-30)) continue;
        $sueltos[] = ['key' => (string)($c['id'] ?? $id), 'creado' => $creado];
    }
    if ($sueltos) {
        $n = count($sueltos); $vieja = $mas_vieja($sueltos, 'creado');
        $armar(3, 'contratos-sin-cargar-' . $HOY, array_column($sueltos, 'key'), [
            'para' => $HOY, 'titulo' => 'Cargar en Por medir ' . $s($n, '1 contrato', '# contratos'),
            'detalle' => $s($n, 'Se cargó hace 2 días o más y no está en el tablero', 'Se cargaron hace 2 días o más y no están en el tablero') . ' · ' . $s($n, 'es del ', 'el más viejo, del ') . $dia_txt($vieja),
            'cuando' => $hace($vieja), 'falta' => -$dias_a($vieja) >= 4 ? 0 : 9,
            'link' => '/presupuestos/relacion-contrato.php' . ($n === 1 ? '?contrato=' . rawurlencode($sueltos[0]['key']) : ''), 'link_txt' => $s($n, 'Abrir el contrato →', 'Ver los contratos →'),
        ]);
    }
    // 3c. El presupuesto dura 180 días. Cuando al de un contrato le quedan 15 o menos y el contrato no lo tiene
    //     adjunto, se avisa: pasado ese día, el adjunto es lo único que queda del presupuesto.
    $por_vencer = [];
    foreach ($contratos as $id => $c) {
        if (!is_array($c) || !empty($c['prueba']) || bs_contrato_archivo($c) !== null) continue;
        if (!preg_match('/^(\d{1,6})(?:-(\d{1,3}))?$/', trim((string)($c['nro'] ?? '')), $m)) continue;
        $pc = bs_read_json(BS_CLIENTES_DIR . '/' . str_pad((string)intval($m[1]), 4, '0', STR_PAD_LEFT) . '.json');
        if (!is_array($pc)) continue;
        // La misma cuenta que hace la limpieza nocturna (cleanup-retention.php): 180 días desde la última vez que
        // se tocó. Si ese N° ya es una obra (viva o archivada), el presupuesto tiene otro reloj y no se avisa.
        $ult = 0; $otro_reloj = false;
        foreach ((array)($pc['cotizaciones'] ?? []) as $cot) {
            if (!is_array($cot) || !empty($cot['manual'])) continue;
            if (in_array((string)($cot['estado'] ?? ''), ['aprobado', 'entregado', 'papelera'], true)) { $otro_reloj = true; break; }
            if (isset($m[2]) && intval($cot['sub'] ?? 0) !== intval($m[2])) continue;
            $t = strtotime((string)($cot['fecha'] ?? ''));
            if (!empty($cot['transiciones']) && is_array($cot['transiciones'])) { $tr = end($cot['transiciones']); if (is_array($tr) && !empty($tr['at'])) $t = max($t, strtotime((string)$tr['at'])); }
            $ult = max($ult, intval($t));   // con varias versiones, cuenta la última que se va a borrar
        }
        if ($otro_reloj || !$ult) continue;
        $se_borra = date('Y-m-d', $ult + BS_RETENTION_DAYS * 86400);
        if ($se_borra >= $HOY && $se_borra <= $mas(15)) $por_vencer[] = ['key' => (string)($c['id'] ?? $id), 'se_borra' => $se_borra];
    }
    if ($por_vencer) {
        $n = count($por_vencer); $primero = $mas_vieja($por_vencer, 'se_borra');
        $armar(3, 'adjuntar-' . $HOY, array_column($por_vencer, 'key'), [
            'para' => $primero, 'titulo' => 'Adjuntar el presupuesto a ' . $s($n, '1 contrato', '# contratos'),
            'detalle' => 'El presupuesto dura 180 días y ' . $s($n, 'a este le quedan 15 o menos', 'a estos les quedan 15 o menos') . ' · ' . $s($n, 'se borra el ', 'el primero se borra el ') . $dia_txt($primero) . ' · adjunto en el contrato queda para siempre',
            'cuando' => $cuando($primero), 'falta' => $dias_a($primero) <= 3 ? 0 : 9,
            'link' => '/presupuestos/relacion-contrato.php' . ($n === 1 ? '?contrato=' . rawurlencode($por_vencer[0]['key']) : ''), 'link_txt' => $s($n, 'Abrir el contrato →', 'Ver los contratos →'),
        ]);
    }
}

// ============================================================
// 4. FIN DE MES — bajar el cierre, solo el último día
// ============================================================
if ($HOY === date('Y-m-t', strtotime($HOY . ' 12:00:00'))) {
    $MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $mes = substr($HOY, 0, 7);
    // ¿ya se bajó? cierre-mes.php lo deja anotado en el registro ("que=cierre_del_mes_AAAA-MM")
    $bajado = '';
    $dir = dirname(auth_usuarios_archivo());
    foreach (array_unique([$mes, date('Y-m')]) as $m) {
        foreach (@file($dir . '/registro-' . $m . '.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $ln) {
            $p = explode("\t", $ln);
            if (($p[2] ?? '') === 'cierre-mes.php' && strpos((string)($p[4] ?? ''), 'que=cierre_del_mes_' . $mes) !== false) $bajado = substr((string)$p[0], 11, 5);
        }
    }
    $armar(4, 'cierre-' . $HOY, [], [
        'para' => $HOY, 'titulo' => 'Bajar el cierre de ' . $MESES[intval(substr($HOY, 5, 2)) - 1],
        'detalle' => $bajado !== '' ? 'Ya se bajó hoy, a las ' . $bajado . ' hs' : 'Hoy es el último día del mes: se baja, se imprime y va a la carpeta',
        'cuando' => 'es hoy', 'falta' => 0, 'completo' => $bajado !== '', 'link' => '#cajaCierre', 'link_txt' => 'Ir al cierre →',
    ]);
}

// Por grupo y, dentro de cada grupo, por fecha: lo que ya pasó, después lo más cercano.
usort($rec, function ($a, $b) { return strcmp($a['orden'], $b['orden']); });

// ------------------------------------------------------------
// GET
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    bs_ok(['hoy' => $HOY, 'recordatorios' => array_map(function ($r) { unset($r['keys'], $r['orden']); return $r; }, $rec)]);
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
