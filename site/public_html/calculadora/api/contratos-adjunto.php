<?php
// ============================================================
// contratos-adjunto.php — El PDF o Excel del presupuesto de un contrato
// BlackStones Marmolería · Relación-Contrato
// ============================================================
// Un contrato queda VERIFICADO cuando tiene adjunto el presupuesto que
// se le dio al cliente. Un archivo por contrato: subir otro reemplaza
// al anterior.
//
// GET  ?id=<id del contrato>
//   Devuelve el archivo. El PDF se abre en el navegador; el Excel se baja.
//
// POST multipart/form-data → subir
//   id, archivo (PDF, XLSX o XLS, hasta 10 MB)
//
// POST JSON → quitar
//   { "accion": "borrar", "id": "<id>" }
//
// Los archivos viven en bs-data/contratos/adjuntos/{id}/, fuera de
// public_html: solo se llega a ellos por acá, con sesión. El nombre en
// disco lo pone el servidor; el del usuario se guarda solo para mostrarlo.
// ============================================================

require_once __DIR__ . '/_contratos.php';
require_once __DIR__ . '/_auth.php';

bs_require_auth();
bs_check_csrf();
bs_ensure_dirs();

/** Qué es el archivo, mirando su contenido y no su nombre: 'pdf', 'xlsx', 'xls' o ''. */
function bs_contrato_adj_tipo($path, $nombre) {
    $ini = (string)@file_get_contents($path, false, null, 0, 8);
    $ext = strtolower(pathinfo((string)$nombre, PATHINFO_EXTENSION));
    if (strncmp($ini, '%PDF-', 5) === 0) return 'pdf';
    // Un .xlsx es un zip; un .xls viejo es un contenedor OLE. Los dos tienen que llamarse como lo que son.
    if (strncmp($ini, "PK\x03\x04", 4) === 0 && $ext === 'xlsx') return 'xlsx';
    if (strncmp($ini, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1", 8) === 0 && $ext === 'xls') return 'xls';
    return '';
}

$MIMES = [
    'pdf'  => 'application/pdf',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'xls'  => 'application/vnd.ms-excel',
];

// ============================================================
// GET — servir el archivo
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = (string)($_GET['id'] ?? '');
    if (!bs_contrato_id_ok($id)) bs_error('id inválido');
    $d = bs_contratos_leer();
    $a = isset($d['contratos'][$id]) ? bs_contrato_archivo($d['contratos'][$id]) : null;
    $path = $a ? bs_contrato_adj_path($id, $a['ext']) : '';
    if (!$a || !is_file($path)) bs_error('Ese contrato no tiene el presupuesto adjunto.', 404);

    // Nombre para la descarga: solo letras, números, guiones y puntos.
    $base = preg_replace('/[^A-Za-z0-9._-]+/', '-', pathinfo($a['nombre'], PATHINFO_FILENAME));
    $base = trim((string)$base, '-.');
    if ($base === '') $base = 'presupuesto';
    header('Content-Type: ' . $MIMES[$a['ext']]);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: ' . ($a['ext'] === 'pdf' ? 'inline' : 'attachment') . '; filename="' . $base . '.' . $a['ext'] . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile($path);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') bs_error('Solo GET o POST', 405);

$es_subida = isset($_FILES['archivo']) || isset($_POST['id']);

// ============================================================
// POST JSON — quitar el archivo
// ============================================================
if (!$es_subida) {
    $body = json_decode(@file_get_contents('php://input'), true);
    // Un archivo más grande que lo que acepta el servidor llega con el POST vacío.
    if (!is_array($body)) bs_error('No se pudo recibir el archivo. Puede ser demasiado grande (máximo 10 MB).');
    $id = (string)($body['id'] ?? '');
    if (($body['accion'] ?? '') !== 'borrar') bs_error('accion inválida');
    if (!bs_contrato_id_ok($id)) bs_error('id inválido');

    if (!is_dir(BS_CONTRATOS_DIR) && !@mkdir(BS_CONTRATOS_DIR, 0755, true)) bs_error('No se pudo crear el directorio de contratos', 500);
    $fp = bs_lock_acquire(BS_CONTRATOS_LOCK);
    if (!$fp) bs_error('No se pudo adquirir lock', 500);
    $d = bs_contratos_leer();
    if (!isset($d['contratos'][$id])) { bs_lock_release($fp); bs_error('Ese contrato ya no existe. Actualizá la página.', 404); }
    $d['contratos'][$id]['archivo'] = null;
    $d['contratos'][$id]['actualizado'] = date('c');
    if (!bs_write_json_atomic(BS_CONTRATOS_FILE, $d)) { bs_lock_release($fp); bs_error('No se pudo guardar', 500); }
    bs_contrato_adj_borrar($id);
    bs_lock_release($fp);
    bs_ok(['id' => $id, 'archivo' => null]);
}

// ============================================================
// POST multipart — subir (reemplaza al que hubiera)
// ============================================================
$id = (string)($_POST['id'] ?? '');
if (!bs_contrato_id_ok($id)) bs_error('id inválido');
$f = $_FILES['archivo'] ?? null;
if (!is_array($f) || is_array($f['error'] ?? null)) bs_error('Falta el archivo.');
if (in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || intval($f['size']) > BS_CONTRATOS_ADJ_MAX_BYTES) {
    bs_error('El archivo es demasiado grande (máximo 10 MB).');
}
if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) bs_error('No se pudo recibir el archivo.');
if (intval($f['size']) < 100) bs_error('El archivo está vacío.');

$ext = bs_contrato_adj_tipo($f['tmp_name'], (string)$f['name']);
if ($ext === '') bs_error('Tiene que ser el presupuesto en PDF o en Excel (.xlsx o .xls).');

// El nombre original, solo para mostrarlo: sin rutas ni caracteres de control.
$nombre = trim(preg_replace('/[\x00-\x1F\x7F\/\\\\]+/u', ' ', basename((string)$f['name'])));
if ($nombre === '' || mb_strlen($nombre) > 120) $nombre = 'presupuesto.' . $ext;

if (!is_dir(BS_CONTRATOS_DIR) && !@mkdir(BS_CONTRATOS_DIR, 0755, true)) bs_error('No se pudo crear el directorio de contratos', 500);
$fp = bs_lock_acquire(BS_CONTRATOS_LOCK);
if (!$fp) bs_error('No se pudo adquirir lock', 500);

try {
    $d = bs_contratos_leer();
    if (!isset($d['contratos'][$id])) { bs_lock_release($fp); bs_error('Ese contrato ya no existe. Actualizá la página.', 404); }

    $dir = BS_CONTRATOS_ADJ_DIR . '/' . $id;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) { bs_lock_release($fp); bs_error('No se pudo crear la carpeta del adjunto', 500); }
    // Uno por contrato: el anterior (de cualquier tipo) se va.
    foreach (['pdf', 'xlsx', 'xls'] as $e) @unlink($dir . '/presupuesto.' . $e);
    if (!@move_uploaded_file($f['tmp_name'], bs_contrato_adj_path($id, $ext))) { bs_lock_release($fp); bs_error('No se pudo guardar el archivo', 500); }

    $archivo = ['ext' => $ext, 'nombre' => $nombre, 'bytes' => intval($f['size']), 'at' => date('c')];
    $d['contratos'][$id]['archivo'] = $archivo;
    $d['contratos'][$id]['actualizado'] = date('c');
    if (!bs_write_json_atomic(BS_CONTRATOS_FILE, $d)) {
        @unlink(bs_contrato_adj_path($id, $ext));
        bs_lock_release($fp);
        bs_error('No se pudo guardar', 500);
    }
    bs_lock_release($fp);
    bs_ok(['id' => $id, 'archivo' => $archivo]);
} catch (Throwable $e) {
    bs_lock_release($fp);
    bs_error('Excepción: ' . $e->getMessage(), 500);
}
