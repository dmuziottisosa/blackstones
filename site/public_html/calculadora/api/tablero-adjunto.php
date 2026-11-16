<?php
// ============================================================
// tablero-adjunto.php — Fotos de bacha, planos y fotos de entrega de una obra
// BlackStones Marmolería · Registry de Presupuestos
// ============================================================
// GET  ?nro=0042&sub=1&id=<id>[&t=1]
//   Devuelve el archivo (o su miniatura con t=1). Solo con sesión.
//
// POST multipart/form-data  → subir
//   cliente_nro, sub, tipo ("bacha" | "plano" | "entrega"), archivo, [mini]
//   "archivo": JPG / PNG / WEBP / PDF, hasta 10 MB. El tablero achica las
//   fotos antes de mandarlas; "mini" es la miniatura JPG que arma él mismo.
//
// POST JSON → colgar un link de Drive en vez de subir el archivo
//   { "cliente_nro": "0042", "sub": 1, "accion": "link", "tipo": "plano",
//     "url": "https://drive.google.com/file/d/…/view" }
//   Solo links https de Google (Drive, Docs, Fotos). El servidor no los abre:
//   los guarda y el tablero los muestra como un botón que lleva a Drive.
//
// POST JSON → borrar
//   { "cliente_nro": "0042", "sub": 1, "accion": "borrar", "id": "<id>" }
//
// Los archivos viven en bs-data/tablero/adjuntos/{clave}/, fuera de
// public_html: solo se llega a ellos por este endpoint, con sesión.
// El nombre en disco lo genera el servidor; el del usuario no se usa.
// ============================================================

require_once __DIR__ . '/_tablero.php';
require_once __DIR__ . '/_auth.php';

bs_require_auth();
bs_check_csrf();
bs_ensure_dirs();

date_default_timezone_set('America/Argentina/Buenos_Aires');

/** Tipo real del archivo, leído de su contenido. */
function bs_adj_mime($path) {
    if (class_exists('finfo')) return (string)(new finfo(FILEINFO_MIME_TYPE))->file($path);
    $img = @getimagesize($path);
    if ($img && !empty($img['mime'])) return $img['mime'];
    return (@file_get_contents($path, false, null, 0, 5) === '%PDF-') ? 'application/pdf' : '';
}

function bs_adj_key($nro, $sub) {
    $cliente_nro = str_pad((string)intval($nro), 4, '0', STR_PAD_LEFT);
    $sub = intval($sub);
    if (intval($cliente_nro) <= 0 || $sub <= 0) bs_error('Obra inválida');
    return [$cliente_nro, $sub, $cliente_nro . '-' . $sub];
}

// ============================================================
// GET — servir un archivo
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    list(, , $key) = bs_adj_key($_GET['nro'] ?? '', $_GET['sub'] ?? '');
    $id = (string)($_GET['id'] ?? '');
    $mini = !empty($_GET['t']);

    $tablero = bs_tablero_leer();
    $adj = null;
    foreach (bs_tablero_adjuntos($tablero['obras'][$key] ?? []) as $a) {
        if ($a['id'] === $id) { $adj = $a; break; }
    }
    if (!$adj || $adj['ext'] === 'link') { http_response_code(404); exit('No existe'); }

    $dir = bs_tablero_adj_carpeta($key);
    $path = $dir . '/' . $adj['id'] . '.' . $adj['ext'];
    $ext = $adj['ext'];
    if ($mini && is_file($dir . '/' . $adj['id'] . '.t.jpg')) {
        $path = $dir . '/' . $adj['id'] . '.t.jpg';
        $ext = 'jpg';
    }
    if (!is_file($path)) { http_response_code(404); exit('No existe'); }

    $tipos = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];
    header('Content-Type: ' . $tipos[$ext]);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: inline; filename="' . $key . '-' . $adj['tipo'] . '.' . $ext . '"');
    header('X-Content-Type-Options: nosniff');
    // El id es único por archivo: el navegador lo puede guardar sin volver a pedirlo.
    header('Cache-Control: private, max-age=31536000, immutable');
    readfile($path);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') bs_error('Solo GET o POST', 405);

// ¿Viene un archivo o un pedido JSON?
$es_subida = stripos((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'multipart/form-data') === 0;
if ($es_subida) {
    // PHP descarta todo el POST si el archivo supera el límite del servidor.
    if (empty($_POST) && empty($_FILES)) bs_error('El archivo es demasiado pesado.', 413);
    $in = $_POST;
    $accion = 'subir';
} else {
    $in = json_decode(@file_get_contents('php://input'), true);
    if (!is_array($in)) bs_error('Body JSON inválido');
    $accion = (string)($in['accion'] ?? '');
    if (!in_array($accion, ['borrar', 'link'], true)) bs_error('accion inválida');
}

list($cliente_nro, $sub, $key) = bs_adj_key($in['cliente_nro'] ?? '', $in['sub'] ?? '');

// Solo sobre obras del tablero (presupuesto aprobado).
$cliente_obj = bs_read_json(BS_CLIENTES_DIR . '/' . $cliente_nro . '.json');
if (!$cliente_obj) bs_error('Cliente no encontrado', 404);
$aprobada = false;
foreach ($cliente_obj['cotizaciones'] ?? [] as $cot) {
    if (intval($cot['sub'] ?? 0) === $sub && ($cot['estado'] ?? '') === 'aprobado') { $aprobada = true; break; }
}
if (!$aprobada) bs_error('Esa obra ya no está aprobada. Actualizá el tablero.', 409);

// ---- Validar el archivo ANTES de tomar el lock ----
$tmp_archivo = null; $ext = ''; $tmp_mini = null; $tipo = ''; $link = '';
if ($accion === 'link') {
    $tipo = (string)($in['tipo'] ?? '');
    if (!isset($BS_TABLERO_ADJ_TIPOS[$tipo])) bs_error('tipo inválido');
    $link = bs_tablero_link_valido($in['url'] ?? '');
    if ($link === '') bs_error('Ese link no sirve. Tiene que ser un link de Google Drive (empieza con https://drive.google.com/).');
}
if ($accion === 'subir') {
    $tipo = (string)($in['tipo'] ?? '');
    if (!isset($BS_TABLERO_ADJ_TIPOS[$tipo])) bs_error('tipo inválido');

    $f = $_FILES['archivo'] ?? null;
    if (!$f || !is_array($f) || is_array($f['error'])) bs_error('Falta el archivo.');
    if (in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $f['size'] > BS_TABLERO_ADJ_MAX_BYTES) {
        bs_error('El archivo es demasiado pesado (máximo 10 MB).', 413);
    }
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) bs_error('No se pudo recibir el archivo.');

    // El tipo se decide por el contenido real, no por el nombre ni por lo que diga el navegador.
    $mime = bs_adj_mime($f['tmp_name']);
    if (!isset($BS_TABLERO_ADJ_MIMES[$mime])) bs_error('Tiene que ser una foto (JPG, PNG, WEBP) o un PDF.');
    $ext = $BS_TABLERO_ADJ_MIMES[$mime];
    if ($ext !== 'pdf' && @getimagesize($f['tmp_name']) === false) bs_error('La imagen está dañada.');
    if ($ext === 'pdf' && $tipo !== 'plano') bs_error($tipo === 'bacha' ? 'La foto de la bacha tiene que ser una imagen.' : 'La foto de entrega tiene que ser una imagen.');
    $tmp_archivo = $f['tmp_name'];

    // Miniatura (opcional): JPG chico armado por el tablero.
    $m = $_FILES['mini'] ?? null;
    if ($ext !== 'pdf' && $m && is_array($m) && !is_array($m['error']) && $m['error'] === UPLOAD_ERR_OK
        && is_uploaded_file($m['tmp_name']) && $m['size'] <= 400 * 1024
        && bs_adj_mime($m['tmp_name']) === 'image/jpeg'
        && @getimagesize($m['tmp_name']) !== false) {
        $tmp_mini = $m['tmp_name'];
    }
}

if (!is_dir(BS_TABLERO_DIR) && !@mkdir(BS_TABLERO_DIR, 0755, true)) {
    bs_error('No se pudo crear el directorio del tablero', 500);
}
$fp = bs_lock_acquire(BS_TABLERO_LOCK);
if (!$fp) bs_error('No se pudo adquirir lock', 500);

try {
    $tablero = bs_tablero_leer();
    $obra = array_merge(bs_tablero_obra_vacia(), $tablero['obras'][$key] ?? []);
    $adjuntos = bs_tablero_adjuntos($obra);
    $dir = bs_tablero_adj_carpeta($key);

    if ($accion === 'subir' || $accion === 'link') {
        $del_tipo = 0;
        foreach ($adjuntos as $a) {
            if ($a['tipo'] !== $tipo) continue;
            $del_tipo++;
            if ($accion === 'link' && $a['ext'] === 'link' && $a['url'] === $link) {
                bs_lock_release($fp);
                bs_error('Ese link ya está cargado en esta obra.');
            }
        }
        if ($del_tipo >= BS_TABLERO_ADJ_MAX_POR_TIPO) {
            bs_lock_release($fp);
            bs_error('Ya hay ' . BS_TABLERO_ADJ_MAX_POR_TIPO . ' archivos de ese tipo. Borrá uno para sumar otro.');
        }
    }
    if ($accion === 'link') {
        $id = bin2hex(random_bytes(8));
        $adjuntos[] = ['id' => $id, 'tipo' => $tipo, 'ext' => 'link', 'url' => $link, 'at' => date('c')];

    } elseif ($accion === 'subir') {
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            bs_lock_release($fp);
            bs_error('No se pudo crear la carpeta de adjuntos', 500);
        }
        $id = bin2hex(random_bytes(8));
        if (!@move_uploaded_file($tmp_archivo, $dir . '/' . $id . '.' . $ext)) {
            bs_lock_release($fp);
            bs_error('No se pudo guardar el archivo', 500);
        }
        @chmod($dir . '/' . $id . '.' . $ext, 0644);
        if ($tmp_mini) @move_uploaded_file($tmp_mini, $dir . '/' . $id . '.t.jpg');
        $adjuntos[] = ['id' => $id, 'tipo' => $tipo, 'ext' => $ext, 'at' => date('c')];

    } else { // borrar
        $id = (string)($in['id'] ?? '');
        $quedan = []; $borrado = null;
        foreach ($adjuntos as $a) {
            if ($a['id'] === $id) $borrado = $a; else $quedan[] = $a;
        }
        if (!$borrado) {
            bs_lock_release($fp);
            bs_error('Ese archivo ya no existe. Actualizá el tablero.', 404);
        }
        if ($borrado['ext'] !== 'link') { // un link no tiene archivo en el servidor
            @unlink($dir . '/' . $borrado['id'] . '.' . $borrado['ext']);
            @unlink($dir . '/' . $borrado['id'] . '.t.jpg');
        }
        $adjuntos = $quedan;
    }

    $obra['adjuntos'] = $adjuntos;
    $obra['upd'] = date('c');
    $tablero['obras'][$key] = $obra;
    if (!bs_write_json_atomic(BS_TABLERO_FILE, $tablero)) {
        bs_lock_release($fp);
        bs_error('No se pudo guardar el tablero', 500);
    }
    bs_lock_release($fp);
    bs_ok(['key' => $key, 'id' => $id]);

} catch (Throwable $e) {
    bs_lock_release($fp);
    bs_error('Excepción: ' . $e->getMessage(), 500);
}
