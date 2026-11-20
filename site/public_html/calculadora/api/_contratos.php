<?php
// ============================================================
// _contratos.php — Lo que comparten los endpoints de Relación-Contrato
// BlackStones Marmolería
// ============================================================
// Dónde viven los datos, las listas cerradas y los adjuntos.
// Lo usan contratos.php, contratos-adjunto.php y contratos-importar.php; nadie más.
// (El tablero, el calendario y el hub NO incluyen este archivo:
//  Relación-Contrato se tiene que poder borrar entera sin que se caiga nada.)
// ============================================================

require_once __DIR__ . '/_config.php';

define('BS_CONTRATOS_DIR',  BS_DATA_DIR . '/contratos');
define('BS_CONTRATOS_FILE', BS_CONTRATOS_DIR . '/contratos.json');
define('BS_CONTRATOS_LOCK', BS_CONTRATOS_DIR . '/.write.lock');
define('BS_CONTRATOS_MAX',  5000);
define('BS_CONTRATOS_ADJ_DIR', BS_CONTRATOS_DIR . '/adjuntos');
define('BS_CONTRATOS_ADJ_MAX_BYTES', 10 * 1024 * 1024);
// Papelera: un contrato borrado se puede recuperar durante estos días.
define('BS_CONTRATOS_PAPELERA_DIAS', 30);

// Quién vende y de dónde llega el cliente: listas cerradas (las dictó el dueño, 16/11/2026).
// Para sumar o sacar un nombre se toca solo acá; la página las recibe con el GET.
$BS_CONTRATO_VENDEDORES  = ['Laura', 'Sol', 'Diego', 'Jose', 'Lara', 'Dioge'];
// Procedencia: dos opciones (dueño, 20/11/2026: "ponemos 2 opciones: Alberdi, Publicidad/Página").
$BS_CONTRATO_PROCEDENCIAS = ['Alberdi', 'Publicidad/Página'];
// Las cinco que hubo hasta el 20/11/2026 y a cuál de las dos pasa cada una. Lo eligió el dueño ese día:
// "Alberdi viejo" es Alberdi, y todo lo que decía "Publicación" (también "Publicación/Alberdi": de dónde
// llegó el cliente, no dónde se lo atendió) es Publicidad/Página.
// No se reescribe ningún contrato: el valor viejo se traduce al leerlo (bs_contrato_procedencia) y queda
// guardado con el nombre nuevo la próxima vez que ese contrato se guarde.
$BS_CONTRATO_PROCEDENCIAS_ANTES = [
    'Alberdi viejo'        => 'Alberdi',
    'Publicación'          => 'Publicidad/Página',
    'Publicación/WhatsApp' => 'Publicidad/Página',
    'Publicación/Alberdi'  => 'Publicidad/Página',
];
/** La procedencia como se llama hoy: una de las cinco de antes pasa a su opción nueva; lo demás queda igual. */
function bs_contrato_procedencia($v) {
    global $BS_CONTRATO_PROCEDENCIAS_ANTES;
    $v = trim((string)$v);
    return isset($BS_CONTRATO_PROCEDENCIAS_ANTES[$v]) ? $BS_CONTRATO_PROCEDENCIAS_ANTES[$v] : $v;
}

function bs_contratos_leer() {
    $d = bs_read_json(BS_CONTRATOS_FILE);
    if (!is_array($d) || !isset($d['contratos']) || !is_array($d['contratos'])) $d = ['version' => 1, 'contratos' => []];
    return $d;
}

function bs_contrato_id_ok($id) {
    return is_string($id) && preg_match('/^c[0-9a-f]{6,40}$/', $id) === 1;
}

/** El presupuesto adjunto (PDF o Excel) de un contrato: ['ext','nombre','bytes','at'] o null. */
function bs_contrato_archivo($c) {
    $a = $c['archivo'] ?? null;
    if (!is_array($a) || !in_array($a['ext'] ?? '', ['pdf', 'xlsx', 'xls'], true)) return null;
    return ['ext' => $a['ext'], 'nombre' => (string)($a['nombre'] ?? ''), 'bytes' => intval($a['bytes'] ?? 0), 'at' => (string)($a['at'] ?? '')];
}

/** Ruta en disco del adjunto. El nombre lo pone el servidor; el del usuario no se usa en el disco. */
function bs_contrato_adj_path($id, $ext) {
    return BS_CONTRATOS_ADJ_DIR . '/' . $id . '/presupuesto.' . $ext;
}

/** Borra el adjunto de un contrato (todas las extensiones posibles) y su carpeta. */
function bs_contrato_adj_borrar($id) {
    if (!bs_contrato_id_ok($id)) return;
    $dir = BS_CONTRATOS_ADJ_DIR . '/' . $id;
    if (!is_dir($dir)) return;
    // no se destruye: queda en copias/borrados/ mientras una copia lo pueda necesitar (ver _copias.php)
    foreach (['pdf', 'xlsx', 'xls'] as $e) { function_exists('bs_copia_borrar') ? bs_copia_borrar($dir . '/presupuesto.' . $e) : @unlink($dir . '/presupuesto.' . $e); }
    @rmdir($dir);
}

function bs_contrato_fecha_ok($f) {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string)$f, $m)) return false;
    return checkdate(intval($m[2]), intval($m[3]), intval($m[1]));
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
