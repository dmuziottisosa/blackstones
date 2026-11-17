<?php
// ============================================================
// presencia.php — Quién está conectado ahora y en qué pantalla
// ============================================================
// POST /api/presencia.php  { pagina, [fuera] }      cualquiera que entró
//   Lo manda solo cada pantalla, cada 20 segundos mientras está a la vista (el aviso sale del
//   menú: auth_nav_html() en auth_check.php). 'fuera' = cerró o salió de esa pantalla.
//
//   Le contesta quiénes están en línea y dónde ('linea'), para las iniciales de "quién está
//   trabajando" que se ven arriba de cualquier pantalla (permiso 'presencia': hoy, todos).
//
// GET  /api/presencia.php                           administradores
//   → { ventana, usuarios: { <id>: { en_linea: [paginas], visto: <segundos>, pagina: <la última> } }, linea: [...] }
//
// Se guarda en bs-data/usuarios/presencia.json: por usuario, la última señal de cada pantalla.
// "En línea" = mandó señal hace menos de BS_PRESENCIA_VENTANA segundos. Con la pestaña tapada
// o el celular bloqueado el navegador deja de avisar, y la persona pasa a "visto hace…".
// No se anota en el registro de cambios (es una señal, no un cambio) y si falla no rompe nada.
// Mientras no haya usuarios (contraseña compartida) no guarda ni informa nada.
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

if (!auth_is_valid()) bs_json_response(['ok' => false, 'error' => 'No autenticado'], 401);
bs_check_csrf();

const BS_PRESENCIA_VENTANA = 50;   // segundos sin señal para dejar de estar "en línea" (la señal sale cada 20)
const BS_PRESENCIA_NOMBRES = ['calculadora' => 'Calculadora', 'presupuestos' => 'Presupuestos', 'tablero' => 'Tablero', 'calendario' => 'Calendario',
    'contratos' => 'Relación-Contrato', 'stock' => 'Stock', 'usuarios' => 'Usuarios'];
const BS_PRESENCIA_PAGINAS = ['calculadora', 'presupuestos', 'tablero', 'calendario', 'contratos', 'stock', 'usuarios'];

$yo = auth_usuario();
$archivo = dirname(auth_usuarios_archivo()) . '/presencia.json';

function bs_presencia_leer($archivo) {
    $j = json_decode((string)@file_get_contents($archivo), true);
    return is_array($j) ? $j : [];
}

/** [por usuario, quiénes están en línea]: lo que ve un administrador. */
function bs_presencia_resumen($d) {
    $out = []; $linea = [];
    $ahora = time();
    $db = auth_db();
    foreach ($d as $id => $paginas) {
        if (!is_array($paginas) || !isset($db['usuarios'][$id])) continue;
        $en = []; $ult = 0; $ult_pag = '';
        foreach (BS_PRESENCIA_PAGINAS as $pag) { // en el orden del menú
            $at = intval($paginas[$pag] ?? 0);
            if ($at <= 0) continue;
            if ($ahora - $at <= BS_PRESENCIA_VENTANA) $en[] = $pag;
            if ($at > $ult) { $ult = $at; $ult_pag = $pag; }
        }
        if ($ult <= 0) continue;
        $out[(string)$id] = ['en_linea' => $en, 'visto' => max(0, $ahora - $ult), 'pagina' => $ult_pag];
        if ($en) {
            $n = array_map(function ($p) { return BS_PRESENCIA_NOMBRES[$p]; }, $en);
            $donde = count($n) > 1 ? implode(', ', array_slice($n, 0, -1)) . ' y ' . end($n) : $n[0];
            $linea[] = ['id' => (string)$id, 'nombre' => (string)$db['usuarios'][$id]['nombre'], 'paginas' => $en, 'donde' => $donde];
        }
    }
    usort($linea, function ($a, $b) { return strcmp($a['nombre'], $b['nombre']); });
    return [$out, $linea];
}

// ---------- GET: la lista, para administradores ----------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    if (!auth_puede('usuarios')) bs_json_response(['ok' => false, 'error' => 'Tu usuario no tiene permiso para esto.'], 403);
    list($out, $linea) = bs_presencia_resumen(bs_presencia_leer($archivo));
    bs_ok(['ventana' => BS_PRESENCIA_VENTANA, 'usuarios' => (object)$out, 'linea' => $linea]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') bs_error('Solo GET o POST', 405);

// ---------- POST: la señal de una pantalla ----------
if (!empty($yo['viejo']) || $yo['id'] === '') bs_ok([]); // sin usuarios no hay a quién anotar
$in = json_decode((string)@file_get_contents('php://input'), true);
$pagina = is_array($in) ? (string)($in['pagina'] ?? '') : '';
if (!in_array($pagina, BS_PRESENCIA_PAGINAS, true)) bs_error('pagina inválida');
$fuera = !empty($in['fuera']);

$fp = @fopen($archivo, 'c+');
if (!$fp || !flock($fp, LOCK_EX)) { if ($fp) fclose($fp); bs_ok([]); } // no es grave: la próxima señal lo intenta de nuevo
$d = json_decode((string)stream_get_contents($fp), true);
if (!is_array($d)) $d = [];
$id = (string)$yo['id'];
$ahora = time();
if (!is_array($d[$id] ?? null)) $d[$id] = [];
if ($fuera) {
    // Salió de esa pantalla: deja de figurar "en línea" ahí, pero queda cuándo se lo vio por última vez.
    if (isset($d[$id][$pagina])) $d[$id][$pagina] = min(intval($d[$id][$pagina]), $ahora - BS_PRESENCIA_VENTANA - 1);
} else {
    $d[$id][$pagina] = $ahora;
}
// Limpieza: usuarios que ya no existen y señales de hace más de 30 días.
$db = auth_db();
foreach ($d as $k => $pags) {
    if (!is_array($pags) || !isset($db['usuarios'][$k])) { unset($d[$k]); continue; }
    foreach ($pags as $pag => $at) {
        if (!in_array($pag, BS_PRESENCIA_PAGINAS, true) || $ahora - intval($at) > 30 * 86400) unset($d[$k][$pag]);
    }
    if (!$d[$k]) unset($d[$k]);
}
ftruncate($fp, 0); rewind($fp);
fwrite($fp, json_encode($d));
fflush($fp); flock($fp, LOCK_UN); fclose($fp);
// La respuesta: quiénes están en línea y dónde (lo que se ve arriba de cada pantalla).
if (auth_puede('presencia')) { list(, $linea) = bs_presencia_resumen($d); bs_ok(['linea' => $linea]); }
bs_ok([]);
