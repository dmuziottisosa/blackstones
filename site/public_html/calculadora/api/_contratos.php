<?php
// ============================================================
// _contratos.php — Lo que comparten los endpoints de Relación-Contrato
// BlackStones Marmolería
// ============================================================
// Dónde viven los datos, las listas cerradas y los adjuntos.
// Lo usan contratos.php y contratos-adjunto.php; nadie más.
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

// Quién vende y de dónde llega el cliente: listas cerradas (las dictó el dueño, 16/11/2026).
// Para sumar o sacar un nombre se toca solo acá; la página las recibe con el GET.
$BS_CONTRATO_VENDEDORES  = ['Laura', 'Sol', 'Diego', 'Jose', 'Lara', 'Dioge'];
$BS_CONTRATO_PROCEDENCIAS = ['Alberdi', 'Publicación/WhatsApp', 'Publicación/Alberdi'];

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
    foreach (['pdf', 'xlsx', 'xls'] as $e) @unlink($dir . '/presupuesto.' . $e);
    @rmdir($dir);
}
