<?php
// ============================================================
// _config.php — Paths y constantes del registry
// BlackStones Marmolería · Registry de Presupuestos v1
// ============================================================
//
// Path mental:
//   Este archivo vive en:
//     /home/u144473384/domains/blackstones.com.ar/public_html/calculadora/api/_config.php
//   El storage del registry vive FUERA de public_html en:
//     /home/u144473384/bs-data/
//
// Eso son 5 niveles arriba desde __DIR__ + /bs-data.
// ============================================================

$BS_BASE = __DIR__ . '/../../../../../bs-data';

// LA HORA ES SIEMPRE LA DE ARGENTINA (barrido del 18/11/2026). El servidor corre en UTC: sin esto,
// lo que se guardaba de 21 a 24 hs quedaba con la fecha del día siguiente (un presupuesto, una obra
// archivada el último día del mes que caía en el cierre del mes siguiente, el "hoy" de los contratos).
date_default_timezone_set('America/Argentina/Buenos_Aires');

define('BS_DATA_DIR',                $BS_BASE);
define('BS_CLIENTES_DIR',            $BS_BASE . '/clientes');
define('BS_ZIPS_DIR',                $BS_BASE . '/zips');
define('BS_REGISTRO_DIR',            $BS_BASE . '/registro');
define('BS_REGISTRO_BY_MONTH_DIR',   $BS_BASE . '/registro/by-month');
define('BS_REGISTRO_VIEWED_DIR',     $BS_BASE . '/registro/viewed');
define('BS_REGISTRO_CRON_LOG',       $BS_BASE . '/registro/_cron-log.txt');
define('BS_CLIENTES_INDEX',          $BS_BASE . '/registro/clientes-index.json');

// Política de retención (días)
// Presupuestos (pestaña Presupuestos) y sus ZIP: 180 días.
define('BS_RETENTION_DAYS', 180);
// Obras ARCHIVADAS del tablero, con sus fotos, planos, tildes e historial: 1 año desde que se
// archivan (dueño, 17/11/2026; antes 180 días). Pasado el año se borran de verdad, sin papelera.
// Cada obra pesa unos 3 MB de fotos (medido): un año de 500 obras archivadas son ~1,5 GB.
define('BS_ARCHIVADAS_DAYS', 365);

// Versión del JSON contract
define('BS_CONTRACT_VERSION', '1.0');

/**
 * UN NÚMERO, UNA PERSONA (dueño, 18/11/2026). ¿Estos dos juegos de datos son de la misma persona?
 * Es estricto a propósito: equivocarse para el lado de "es otra" solo cuesta un aviso o un N° nuevo;
 * equivocarse para el lado de "es la misma" mezcla dos clientes.
 *   - Si los dos tienen teléfono, manda el teléfono (últimos 8 dígitos).
 *   - Si a alguno le falta: el nombre tiene que ser el mismo, o compartir dos palabras enteras
 *     ("Ana Lopez" y "Ana Maria Lopez"). Un nombre suelto no alcanza: "Ana" no es "Mariana Lopez",
 *     ni "Maria" es cualquier Maria.
 *   - Un teléfono de relleno no es un teléfono (dueño, 20/11/2026): si los últimos 8 dígitos son todos
 *     iguales ("111111111", "0000000000") cuenta como que falta, y decide el nombre. Un presupuesto
 *     guardado con "111111111" frenaba la carga del contrato de esa misma persona, que sí tenía su teléfono.
 * La usan la calculadora (save.php), "Editar obra" y "Cargar en Por medir" del contrato.
 */
if (!function_exists('bs_misma_persona')) {
    function bs_misma_persona(array $viejo, array $nuevo) {
        $tel = function ($s) {
            $n = preg_replace('/\D/', '', (string)$s);
            if (strlen($n) < 8) return '';
            $u = substr($n, -8);
            return preg_match('/^(\d)\1{7}$/', $u) ? '' : $u; // de relleno (todos iguales): como si no hubiera
        };
        $llano = function ($s) {
            $s = mb_strtolower(trim((string)$s), 'UTF-8');
            $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
            return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9 ]/', ' ', $s)));
        };
        $tv = $tel($viejo['celular'] ?? ''); $tn = $tel($nuevo['celular'] ?? '');
        // a veces el teléfono está cargado en el lugar del nombre
        if ($tv === '' && !preg_match('/[a-z]/', $llano($viejo['nombre'] ?? ''))) $tv = $tel($viejo['nombre'] ?? '');
        if ($tn === '' && !preg_match('/[a-z]/', $llano($nuevo['nombre'] ?? ''))) $tn = $tel($nuevo['nombre'] ?? '');
        if ($tv !== '' && $tn !== '') return $tv === $tn;
        $nv = $llano($viejo['nombre'] ?? ''); $nn = $llano($nuevo['nombre'] ?? '');
        if ($nv === '' || $nn === '' || !preg_match('/[a-z]/', $nv) || !preg_match('/[a-z]/', $nn)) return false;
        if ($nv === $nn) return true;
        $pal = function ($s) { return array_values(array_unique(array_filter(explode(' ', $s), function ($w) { return strlen($w) >= 3 && !ctype_digit($w); }))); };
        return count(array_intersect($pal($nv), $pal($nn))) >= 2;
    }
}

// ============================================================
// Helpers de filesystem
// ============================================================

/**
 * Lee un JSON, devuelve array asociativo o null si no existe / inválido.
 *
 * Si el archivo existe pero está corrupto (truncado por un crash mid-write,
 * por ejemplo), intenta restaurar desde el .bak antes de devolver null.
 */
function bs_read_json($path) {
    if (!file_exists($path)) return null;
    $content = @file_get_contents($path);
    if ($content === false) return null;
    $data = json_decode($content, true);
    if (is_array($data)) return $data;

    // JSON corrupto: intentar fallback al .bak (si existe y es válido)
    $bak = $path . '.bak';
    if (file_exists($bak)) {
        $bak_content = @file_get_contents($bak);
        if ($bak_content !== false) {
            $bak_data = json_decode($bak_content, true);
            if (is_array($bak_data)) {
                // Log + restaurar el archivo principal desde el .bak
                @error_log("bs_read_json: $path corrupto, restaurado desde .bak");
                @file_put_contents($path, $bak_content);
                return $bak_data;
            }
        }
    }
    @error_log("bs_read_json: $path corrupto y sin .bak válido");
    return null;
}

/**
 * Escribe un JSON atómicamente:
 *   1. Si existe el archivo target, copia a .bak (rotación simple)
 *   2. Escribe a .tmp con datos nuevos
 *   3. Rename atómico .tmp → target
 * Si pasa 1+2 pero falla 3, el .bak conserva el estado previo válido.
 *
 * Crea el directorio padre si no existe.
 * Retorna true si OK, false si falló.
 */
function bs_write_json_atomic($path, $data) {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true)) return false;
    }
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false) return false;

    // Backup del estado válido actual antes de pisar
    if (file_exists($path)) {
        @copy($path, $path . '.bak');
    }

    $tmp = $path . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $json) === false) return false;
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

/**
 * Asegura que todos los directorios del registry existan.
 * Idempotente. Llamada al inicio de los endpoints que escriben.
 */
function bs_ensure_dirs() {
    foreach ([BS_DATA_DIR, BS_CLIENTES_DIR, BS_ZIPS_DIR, BS_REGISTRO_DIR, BS_REGISTRO_BY_MONTH_DIR, BS_REGISTRO_VIEWED_DIR] as $d) {
        if (!is_dir($d)) @mkdir($d, 0755, true);
    }
}

/**
 * Lockea un archivo o directorio para escritura. Devuelve handle o false.
 * El llamador debe hacer flock($fp, LOCK_UN) y fclose() al terminar.
 */
function bs_lock_acquire($lockfile_path) {
    $fp = @fopen($lockfile_path, 'c');
    if ($fp === false) return false;
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }
    return $fp;
}

function bs_lock_release($fp) {
    if ($fp) {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

// ============================================================
// Helpers de respuesta HTTP
// ============================================================

function bs_json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function bs_error($message, $code = 400) {
    bs_json_response(['ok' => false, 'error' => $message], $code);
}

function bs_ok($data = []) {
    bs_json_response(array_merge(['ok' => true], $data), 200);
}

// ============================================================
// Fecha de referencia de una cotizacion — UNA sola definicion
// ============================================================
// Es la fecha con la que se categoriza, ordena y filtra en TODA la
// pestaña de presupuestos: listado de activos, tabla de entregados,
// agrupacion por mes del dashboard y generacion de reportes mensuales.
//
// Cadena unica, en este orden:
//   1. fecha_contrato  — la fecha real del acuerdo (editable en el hub)
//   2. entregado_at    — cuando se entrego (las activas no lo tienen)
//   3. fecha           — cuando se guardo el presupuesto
//
// IMPORTANTE: la funcion NO acepta un fallback por parametro a proposito.
// Cuando lo aceptaba, cada consumidor pasaba el suyo y las vistas
// terminaban mandando la misma cotizacion a meses distintos ('Por mes'
// usaba entregado_at y la tabla usaba fecha). Con una sola definicion
// cerrada eso no puede volver a pasar.
//
// NO se usa para la retencion (cleanup-retention.php): ahi el reloj
// tiene que seguir corriendo desde la entrega real, no desde el contrato.
function bs_fecha_ref($cot) {
    $fc = trim((string)($cot['fecha_contrato'] ?? ''));
    if ($fc !== '') return $fc;
    $ea = trim((string)($cot['entregado_at'] ?? ''));
    if ($ea !== '') return $ea;
    return (string)($cot['fecha'] ?? '');
}
