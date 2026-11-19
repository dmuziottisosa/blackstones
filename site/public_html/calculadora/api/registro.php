<?php
// ============================================================
// registro.php — Ver el registro de acciones (quién hizo qué y a qué hora)
// ============================================================
// Solo administradores (permiso "reportes"). Lo muestra /presupuestos/reportes.php.
// Lee bs-data/usuarios/registro-AAAA-MM.log, que escribe auth_registrar() en cada cambio.
// Es solo lectura: no escribe nada, y si este archivo se borra el sistema sigue igual.
//
//   GET ?dia=AAAA-MM-DD&usuario=&q=&desde=0     (sin "dia": hoy, hora de Argentina)
//     → { ok, dia, hoy, usuarios:[{usuario,nombre}], total, filas:[{at,usuario,nombre,que,obra}], hay_mas }
//   Se ve UN día por vez (dueño, 18/11/2026: "lo que quiero ver es el real time del día"). Las filas
//   van de la más nueva a la más vieja, de a 20.
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_tablero.php';

bs_require_permiso('reportes');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') bs_error('Solo GET', 405);
date_default_timezone_set('America/Argentina/Buenos_Aires');

$dir = dirname(auth_usuarios_archivo());
$hoy = date('Y-m-d');
$dia = (string)($_GET['dia'] ?? '');
$pd = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dia, $m) ? $m : null;
if (!$pd || !checkdate(intval($pd[2]), intval($pd[3]), intval($pd[1])) || $dia > $hoy) $dia = $hoy;
$mes = substr($dia, 0, 7);
$f_usuario = trim((string)($_GET['usuario'] ?? ''));
$q = mb_strtolower(trim((string)($_GET['q'] ?? '')), 'UTF-8');
$desde = max(0, intval($_GET['desde'] ?? 0));
const BS_REGISTRO_PAGINA = 20;

// Quién es quién (para mostrar el nombre y no solo el usuario).
$nombres = ['equipo' => 'Equipo (contraseña compartida)', '-' => 'Sin sesión'];
$db = auth_db();
if (is_array($db) && empty($db['roto'])) foreach ((array)($db['usuarios'] ?? []) as $u) $nombres[(string)($u['usuario'] ?? '')] = (string)($u['nombre'] ?? '');

// Cómo se dice cada cosa.
$tildes = []; $etapas = [];
foreach ($BS_ETAPAS as $eid => $e) { $etapas[$eid] = $e['titulo']; foreach ((array)($e['checks'] ?? []) as $cid => $label) $tildes[$cid] = $label; }
$ADJ = ['plano' => 'un plano', 'bacha' => 'una foto de bacha', 'entrega' => 'una foto de entrega'];
function bs_registro_que($script, $d, $tildes, $etapas, $ADJ) {
    $a = $d['accion'] ?? '';
    switch ($script) {
        case 'tablero.php':
            if ($a === 'check') { $l = $tildes[$d['tilde'] ?? ''] ?? ''; $v = $d['valor'] ?? null; return ($v === null ? 'Tildó o destildó' : (in_array($v, ['si', '1', 'true'], true) ? 'Tildó' : 'Destildó')) . ($l !== '' ? ' "' . $l . '"' : ' un ítem'); }
            if ($a === 'mover') return 'Pasó la obra a ' . ($etapas[$d['etapa'] ?? ''] ?? 'otra etapa');
            if ($a === 'fecha') return !empty($d['fecha']) ? 'Puso el turno: ' . implode('/', array_reverse(explode('-', $d['fecha']))) . (!empty($d['hora']) ? ' ' . str_replace('_', ':', $d['hora']) . ' hs' : '') : (array_key_exists('fecha', $d) ? 'Le sacó el turno' : 'Cambió el turno');
            if ($a === 'plazo') return 'Cambió el plazo de entrega';
            if ($a === 'nota') return 'Cambió la nota';
            if ($a === 'bacha') return 'Anotó la bacha';
            if ($a === 'borrar') return 'Borró la ficha';
            if ($a === 'nro_contrato') return 'Pasó el N° del contrato a la ficha';
            return 'Cambió la ficha';
        case 'tablero-cargar.php': return 'Cargó una obra en Por medir';
        case 'tablero-editar.php': return !empty($d['auto']) ? 'Se completaron datos de la ficha desde su contrato (automático)' : 'Editó los datos de la obra';
        case 'tablero-adjunto.php': return ($a === 'borrar' ? 'Borró ' : ($a === 'girar' ? 'Giró ' : 'Subió ')) . ($ADJ[$d['tipo'] ?? ''] ?? 'un archivo');
        case 'tablero-papelera.php': return $a === 'restaurar' ? 'Recuperó la ficha de la papelera' : ($a === 'tirar' ? 'Mandó la ficha a la papelera' : 'Papelera del tablero');
        case 'tablero-importar.php': return 'Importó fichas';
        case 'state.php': $n = $d['nuevo_estado'] ?? ''; return $n === 'entregado' ? 'Archivó la obra' : ($n === 'aprobado' ? 'Devolvió la obra al tablero' : 'Cambió el estado del presupuesto a ' . $n);
        case 'contratos.php':
            $t = ['crear' => 'Creó un contrato', 'editar' => 'Editó un contrato', 'sena' => 'Cargó o cambió la seña', 'pago' => 'Cargó un pago', 'borrar_pago' => 'Borró un pago', 'borrar' => 'Borró un contrato', 'borrar_enlazado' => 'Borró el contrato de una ficha borrada',
                'restaurar' => 'Recuperó un contrato de la papelera', 'restaurar_enlazado' => 'Recuperó el contrato de una ficha', 'mudar_enlazado' => 'El contrato siguió a su ficha (cambio de N°)', 'de_ficha' => 'El contrato tomó la seña o la nota de su ficha', 'de_fichas' => 'Los contratos tomaron la seña o la nota de sus fichas', 'enlazar' => 'Enlazó un contrato con el tablero', 'desenlazar' => 'Desenlazó un contrato',
                'prueba' => 'Cargó contratos de prueba', 'borrar_prueba' => 'Borró los contratos de prueba'];
            return $t[$a] ?? 'Cambió un contrato';
        case 'contratos-adjunto.php': return 'Adjuntó o quitó el presupuesto de un contrato';
        case 'contratos-importar.php': return 'Importó contratos';
        case 'save.php': return 'Guardó un presupuesto en la calculadora';
        case 'delete.php': return 'Eliminó un presupuesto';
        case 'edit-cliente.php': return 'Editó los datos de un cliente';
        case 'edit-concepto.php': case 'edit-cotizacion.php': return 'Editó un presupuesto';
        case 'add-manual.php': return 'Cargó un entregado a mano';
        case 'cierre-mes.php': return 'Bajó el cierre del mes';
        case 'control.php': return $a === 'deshacer' ? 'Volvió a dejar pendiente un recordatorio' : 'Marcó como hecho un recordatorio';
        case 'usuarios.php':
            $t = ['primer_admin' => 'Creó el primer administrador', 'crear' => 'Creó un usuario', 'editar' => 'Cambió un usuario (rol o alta/baja)', 'clave' => 'Le cambió la contraseña a un usuario', 'borrar' => 'Borró un usuario', 'mi_clave' => 'Cambió su propia contraseña'];
            return $t[$a] ?? 'Cambió usuarios';
    }
    return 'Hizo un cambio (' . $script . ')';
}

// Nombre actual de cada obra, para no mostrar solo el número. El registro no guarda nombres.
$nombre_obra = function ($nro, $sub) {
    static $cache = [];
    if (!preg_match('/^\d{1,6}$/', (string)$nro)) return '';
    $k = str_pad((string)intval($nro), 4, '0', STR_PAD_LEFT);
    if (!isset($cache[$k])) { $c = bs_read_json(BS_CLIENTES_DIR . '/' . $k . '.json'); $cache[$k] = is_array($c) ? [(string)($c['cliente']['nombre'] ?? ''), intval($k) >= BS_NRO_FICHA_MANUAL_DESDE] : ['', false]; }
    return trim(($cache[$k][1] ? '' : $k . '-' . intval($sub) . ' ') . $cache[$k][0]) ?: ($k . '-' . intval($sub));
};

$filas = []; $usuarios_mes = []; // (los usuarios que hicieron algo ese día)
$lineas = @file($dir . '/registro-' . $mes . '.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

// LA HORA ES SIEMPRE LA DE ARGENTINA (UTC-3). Las líneas nuevas terminan en "ART" y ya vienen así.
// Las anteriores al 18/11/2026 se anotaron con la hora del servidor (UTC, 3 horas adelantada), salvo
// las de usuarios y del cierre del mes, que ya salían bien. Hubo además unas horas en que se anotó
// bien pero sin la marca: se reconocen porque el reloj "vuelve atrás" más de 2 horas de una línea a la
// siguiente. Desde ese punto no se corrige nada; antes, se restan las 3 horas.
$YA_BIEN = ['usuarios.php', 'cierre-mes.php'];
$corte = null; $antes = null;
foreach ($lineas as $i => $l) {
    $p = explode("\t", $l);
    if (count($p) < 4) continue;
    if (($p[5] ?? '') === 'ART') { $corte = $i; break; }
    if (in_array($p[2], $YA_BIEN, true)) continue;
    $t = strtotime($p[0] . ' UTC');
    if ($t === false) continue; // una línea con la fecha ilegible no decide nada
    if ($antes !== null && $t < $antes - 7200) { $corte = $i; break; }
    $antes = $t;
}
$hora_ar = function ($i, $p) use ($corte, $YA_BIEN) {
    if (($p[5] ?? '') === 'ART' || ($corte !== null && $i >= $corte) || in_array($p[2], $YA_BIEN, true)) return $p[0];
    $t = strtotime($p[0] . ' UTC');
    return $t ? gmdate('Y-m-d H:i:s', $t - 3 * 3600) : $p[0];
};

for ($i = count($lineas) - 1; $i >= 0; $i--) {
    $p = explode("\t", $lineas[$i]);
    if (count($p) < 4) continue;
    $p[0] = $hora_ar($i, $p);
    if (substr($p[0], 0, 10) !== $dia) continue; // solo el día pedido
    $d = [];
    foreach (preg_split('/\s+/', trim($p[4] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $par) { $x = explode('=', $par, 2); if (count($x) === 2) $d[$x[0]] = $x[1]; }
    $usuarios_mes[$p[1]] = true;
    if ($f_usuario !== '' && $p[1] !== $f_usuario) continue;
    $fila = ['at' => $p[0], 'usuario' => $p[1], 'nombre' => ($nombres[$p[1]] ?? '') ?: $p[1], 'que' => bs_registro_que($p[2], $d, $tildes, $etapas, $ADJ),
        'obra' => isset($d['cliente_nro']) ? $nombre_obra($d['cliente_nro'], $d['sub'] ?? 1) : ''];
    if ($q !== '' && mb_strpos(mb_strtolower($fila['at'] . ' ' . $fila['nombre'] . ' ' . $fila['que'] . ' ' . $fila['obra'], 'UTF-8'), $q) === false) continue;
    $filas[] = $fila;
}
usort($filas, function ($x, $y) { return strcmp($y['at'], $x['at']); }); // lo más nuevo arriba, ya con la hora corregida
$total = count($filas);
$us = [];
foreach (array_keys($usuarios_mes) as $u) $us[] = ['usuario' => $u, 'nombre' => ($nombres[$u] ?? '') ?: $u];
usort($us, function ($a, $b) { return strcasecmp($a['nombre'], $b['nombre']); });
bs_ok(['dia' => $dia, 'hoy' => $hoy, 'usuarios' => $us, 'total' => $total, 'filas' => array_slice($filas, $desde, BS_REGISTRO_PAGINA), 'hay_mas' => $desde + BS_REGISTRO_PAGINA < $total]);
