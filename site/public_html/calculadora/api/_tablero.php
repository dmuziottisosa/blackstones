<?php
// ============================================================
// _tablero.php — Lo que comparten los endpoints del tablero de obras
// BlackStones Marmolería · Registry de Presupuestos
// ============================================================
// Lo incluyen tablero.php (tarjetas), tablero-adjunto.php (fotos y planos)
// y cleanup-retention.php (purga).
//
// Storage: bs-data/tablero/
//   obras.json                     estado de cada obra, clave "{cliente_nro}-{sub}"
//   adjuntos/{clave}/{id}.{ext}    foto de bacha o plano
//   adjuntos/{clave}/{id}.t.jpg    miniatura (solo imágenes)
// ============================================================

require_once __DIR__ . '/_config.php';

define('BS_TABLERO_DIR',      BS_DATA_DIR . '/tablero');
define('BS_TABLERO_FILE',     BS_TABLERO_DIR . '/obras.json');
define('BS_TABLERO_LOCK',     BS_TABLERO_DIR . '/.write.lock');
define('BS_TABLERO_ADJ_DIR',  BS_TABLERO_DIR . '/adjuntos');
define('BS_TABLERO_NOTA_MAX', 140);

// Adjuntos: qué se puede colgar de una obra y cuánto.
define('BS_TABLERO_ADJ_MAX_BYTES',    10 * 1024 * 1024); // por archivo
define('BS_TABLERO_ADJ_MAX_POR_TIPO', 6);
$BS_TABLERO_ADJ_TIPOS = ['bacha' => 'Bacha', 'plano' => 'Plano'];
// mime real del archivo → extensión con la que se guarda
$BS_TABLERO_ADJ_MIMES = [
    'image/jpeg'      => 'jpg',
    'image/png'       => 'png',
    'image/webp'      => 'webp',
    'application/pdf' => 'pdf',
];

function bs_tablero_leer() {
    $data = bs_read_json(BS_TABLERO_FILE);
    if (!is_array($data) || !isset($data['obras']) || !is_array($data['obras'])) {
        return ['version' => '1.0', 'obras' => []];
    }
    return $data;
}

function bs_tablero_obra_vacia() {
    return ['etapa' => 'medir', 'fecha' => '', 'hora' => '', 'checks' => [], 'nota' => '', 'medido_at' => '', 'sena' => null, 'acceso' => null, 'bacha' => '', 'equipo' => [], 'adjuntos' => [], 'desde' => '', 'hist' => []];
}

/** Adjuntos de una obra, ya validados: [{id, tipo, ext}]. */
function bs_tablero_adjuntos($obra) {
    global $BS_TABLERO_ADJ_TIPOS, $BS_TABLERO_ADJ_MIMES;
    $out = [];
    foreach ((array)($obra['adjuntos'] ?? []) as $a) {
        if (!is_array($a)) continue;
        $id = (string)($a['id'] ?? '');
        $tipo = (string)($a['tipo'] ?? '');
        $ext = (string)($a['ext'] ?? '');
        if (!preg_match('/^[a-f0-9]{16}$/', $id)) continue;
        if (!isset($BS_TABLERO_ADJ_TIPOS[$tipo]) || !in_array($ext, $BS_TABLERO_ADJ_MIMES, true)) continue;
        $out[] = ['id' => $id, 'tipo' => $tipo, 'ext' => $ext, 'at' => (string)($a['at'] ?? '')];
    }
    return $out;
}

/** Carpeta de adjuntos de una obra. $key ya validada ("0042-1"). */
function bs_tablero_adj_carpeta($key) {
    return BS_TABLERO_ADJ_DIR . '/' . $key;
}

/** Borra todos los archivos adjuntos de una obra (la usa la purga). */
function bs_tablero_borrar_carpeta($key) {
    if (!preg_match('/^\d{4,}-\d+$/', $key)) return;
    $dir = bs_tablero_adj_carpeta($key);
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        @unlink($dir . '/' . $f);
    }
    @rmdir($dir);
}
