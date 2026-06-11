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

define('BS_DATA_DIR',                $BS_BASE);
define('BS_CLIENTES_DIR',            $BS_BASE . '/clientes');
define('BS_ZIPS_DIR',                $BS_BASE . '/zips');
define('BS_REGISTRO_DIR',            $BS_BASE . '/registro');
define('BS_REGISTRO_BY_MONTH_DIR',   $BS_BASE . '/registro/by-month');
define('BS_REGISTRO_VIEWED_DIR',     $BS_BASE . '/registro/viewed');
define('BS_REGISTRO_CRON_LOG',       $BS_BASE . '/registro/_cron-log.txt');
define('BS_CLIENTES_INDEX',          $BS_BASE . '/registro/clientes-index.json');

// Política de retención (días)
define('BS_RETENTION_DAYS', 90);

// Versión del JSON contract
define('BS_CONTRACT_VERSION', '1.0');

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
