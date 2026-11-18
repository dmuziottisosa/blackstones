<?php
// ============================================================
// registro.php — Ver el registro de acciones (quién hizo qué y a qué hora)
// ============================================================
// Solo administradores (permiso "reportes"). Lo muestra /presupuestos/reportes.php.
// Lee bs-data/usuarios/registro-AAAA-MM.log, que escribe auth_registrar() en cada cambio.
// Es solo lectura: no escribe nada, y si este archivo se borra el sistema sigue igual.
//
//   GET ?mes=AAAA-MM&usuario=&q=&desde=0
//     → { ok, mes, meses:[…], usuarios:[{usuario,nombre}], total, filas:[{at,usuario,nombre,que,obra}], hay_mas }
//   Las filas van de la más nueva a la más vieja, de a 200.
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_tablero.php';

bs_require_permiso('reportes');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') bs_error('Solo GET', 405);
date_default_timezone_set('America/Argentina/Buenos_Aires');

$dir = dirname(auth_usuarios_archivo());
$meses = [];
foreach (@scandir($dir) ?: [] as $f) { if (preg_match('/^registro-(\d{4}-\d{2})\.log$/', $f, $m)) $meses[] = $m[1]; }
rsort($meses);
$mes = (string)($_GET['mes'] ?? '');
if (!preg_match('/^\d{4}-\d{2}$/', $mes) || !in_array($mes, $meses, true)) $mes = $meses[0] ?? date('Y-m');
$f_usuario = trim((string)($_GET['usuario'] ?? ''));
$q = mb_strtolower(trim((string)($_GET['q'] ?? '')), 'UTF-8');
$desde = max(0, intval($_GET['desde'] ?? 0));
const BS_REGISTRO_PAGINA = 200;

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
        case 'tablero-editar.php': return 'Editó los datos de la obra';
        case 'tablero-adjunto.php': return ($a === 'borrar' ? 'Borró ' : ($a === 'girar' ? 'Giró ' : 'Subió ')) . ($ADJ[$d['tipo'] ?? ''] ?? 'un archivo');
        case 'tablero-papelera.php': return $a === 'restaurar' ? 'Recuperó la ficha de la papelera' : ($a === 'tirar' ? 'Mandó la ficha a la papelera' : 'Papelera del tablero');
        case 'tablero-importar.php': return 'Importó fichas';
        case 'state.php': $n = $d['nuevo_estado'] ?? ''; return $n === 'entregado' ? 'Archivó la obra' : ($n === 'aprobado' ? 'Devolvió la obra al tablero' : 'Cambió el estado del presupuesto a ' . $n);
        case 'contratos.php':
            $t = ['crear' => 'Creó un contrato', 'editar' => 'Editó un contrato', 'sena' => 'Cargó o cambió la seña', 'pago' => 'Cargó un pago', 'borrar_pago' => 'Borró un pago', 'borrar' => 'Borró un contrato', 'borrar_enlazado' => 'Borró el contrato de una ficha borrada',
                'restaurar' => 'Recuperó un contrato de la papelera', 'restaurar_enlazado' => 'Recuperó el contrato de una ficha', 'mudar_enlazado' => 'El contrato siguió a su ficha (cambio de N°)', 'enlazar' => 'Enlazó un contrato con el tablero', 'desenlazar' => 'Desenlazó un contrato',
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

$filas = []; $usuarios_mes = [];
$lineas = @file($dir . '/registro-' . $mes . '.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
for ($i = count($lineas) - 1; $i >= 0; $i--) {
    $p = explode("\t", $lineas[$i]);
    if (count($p) < 4) continue;
    $d = [];
    foreach (preg_split('/\s+/', trim($p[4] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $par) { $x = explode('=', $par, 2); if (count($x) === 2) $d[$x[0]] = $x[1]; }
    $usuarios_mes[$p[1]] = true;
    if ($f_usuario !== '' && $p[1] !== $f_usuario) continue;
    $fila = ['at' => $p[0], 'usuario' => $p[1], 'nombre' => ($nombres[$p[1]] ?? '') ?: $p[1], 'que' => bs_registro_que($p[2], $d, $tildes, $etapas, $ADJ),
        'obra' => isset($d['cliente_nro']) ? $nombre_obra($d['cliente_nro'], $d['sub'] ?? 1) : ''];
    if ($q !== '' && mb_strpos(mb_strtolower($fila['at'] . ' ' . $fila['nombre'] . ' ' . $fila['que'] . ' ' . $fila['obra'], 'UTF-8'), $q) === false) continue;
    $filas[] = $fila;
}
$total = count($filas);
$us = [];
foreach (array_keys($usuarios_mes) as $u) $us[] = ['usuario' => $u, 'nombre' => ($nombres[$u] ?? '') ?: $u];
usort($us, function ($a, $b) { return strcasecmp($a['nombre'], $b['nombre']); });
bs_ok(['mes' => $mes, 'meses' => $meses, 'usuarios' => $us, 'total' => $total, 'filas' => array_slice($filas, $desde, BS_REGISTRO_PAGINA), 'hay_mas' => $desde + BS_REGISTRO_PAGINA < $total]);
