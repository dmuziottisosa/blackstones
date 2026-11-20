<?php
// ============================================================
// cleanup-retention.php — Cron diario de cleanup
// ============================================================
// Corre todas las noches a las 03:00 ART desde panel Hostinger.
// Comando cron sugerido:
//   0 3 * * * php /home/u144473384/domains/blackstones.com.ar/public_html/calculadora/api/cleanup-retention.php
//
// Lógica:
//   Para cada bs-data/clientes/{nro}.json:
//     Para cada cotización:
//       Si estado == "entregado" (obra ARCHIVADA) Y (now - entregado_at) > 365 días
//         (BS_ARCHIVADAS_DAYS; dueño, 17/11/2026, antes 180):
//         Borrar la cotización del array. Con ella se van su ficha, fotos y planos.
//       Si estado == "aprobado": NO se borra nunca por antigüedad. Es una obra
//         viva en el tablero, y la etapa la dicta el tablero (regla del dueño,
//         16/11/2026): sale de ahí cuando se archiva (pasa a "entregado" y recién
//         entonces empieza a correr su reloj) o cuando alguien borra la ficha.
//         Antes se borraba a los 180 días de aprobada, con sus fotos y planos.
//         A los 180 días se la marca "fuera_de_presupuestos": deja de verse en la
//         pestaña Presupuestos, y la ficha sigue en el tablero.
//       Si estado != "entregado" Y NO existe ningún entregado vigente (menos de 365 días) en este cliente
//          Y (now - última_modificación) > 180 días:
//         Borrar la cotización del array.
//     Si quedaron 0 cotizaciones:
//       Borrar el cliente.json entero.
//
//   Para cada bs-data/zips/*.zip con mtime > 180 días:
//     Borrar el archivo.
//
//   Tablero de obras: toda obra cuya cotización ya no existe (la borró
//   esta purga u otra acción del hub) pierde su registro y sus adjuntos
//   (fotos de bacha y planos).
//
// Idempotente: corridas duplicadas no rompen nada.
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_tablero.php';
if (is_file(__DIR__ . '/_copias.php')) require_once __DIR__ . '/_copias.php'; // la copia de las últimas 24 horas

// CLI-only (NO debe ser invocable via HTTP)
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit;
}

bs_ensure_dirs();

// Antes de borrar nada, la copia de esta hora: lo que esta limpieza se lleve se puede traer de vuelta
// mientras dure esa copia.
if (function_exists('bs_copia_si_toca')) { try { bs_copia_si_toca(); } catch (Throwable $e) { /* sin copia, la limpieza corre igual */ } }

$now = time();
$retention_seconds = BS_RETENTION_DAYS * 24 * 3600;
$archivadas_seconds = BS_ARCHIVADAS_DAYS * 24 * 3600; // las obras archivadas duran más que los presupuestos

$stats = [
    'cotizaciones_borradas' => 0,
    'clientes_borrados'     => 0,
    'zips_borrados'         => 0,
    'obras_tablero_borradas' => 0,
    'ocultos'               => 0,
    'errores'               => [],
];

// ============================================================
// Papelera del tablero: lo que lleva más de 30 días borrado se va de verdad.
// Antes que nada, así lo que queda en clientes/ ya está al día.
// ============================================================
$stats['papelera'] = bs_tablero_papelera_purgar();

// ============================================================
// Cleanup de clientes/
// ============================================================

// (barrido del 18/11/2026) Todo lo que sigue lee y reescribe archivos de clientes: se hace con el
// mismo candado que usa el resto del sistema, para no pisar algo que se esté guardando en ese momento.
$bs_fp_cli = bs_lock_acquire(BS_CLIENTES_DIR . '/.write.lock');
if (!$bs_fp_cli) { @file_put_contents(BS_REGISTRO_CRON_LOG, '[' . date('c') . "] cleanup: no se pudo tomar el candado de clientes; no se hizo nada\n", FILE_APPEND | LOCK_EX); exit(1); }

if (is_dir(BS_CLIENTES_DIR)) {
    foreach (scandir(BS_CLIENTES_DIR) as $f) {
        if (!preg_match('/^(\d{4,})\.json$/', $f, $bs_m)) continue;
        $path = BS_CLIENTES_DIR . '/' . $f;

        $cliente = bs_read_json($path);
        if (!$cliente) {
            $stats['errores'][] = "No se pudo leer $f";
            continue;
        }

        // Detectar si tiene algún entregado
        $tiene_entregado_activo = false;
        foreach ($cliente['cotizaciones'] ?? [] as $cot) {
            if (($cot['estado'] ?? '') === 'entregado') {
                $entregado_at = strtotime($cot['entregado_at'] ?? $cot['fecha'] ?? '');
                if ($entregado_at && ($now - $entregado_at) <= $archivadas_seconds) {
                    $tiene_entregado_activo = true;
                    break;
                }
            }
        }

        // Filtrar cotizaciones que sobreviven
        $kept = [];
        $ocultos_cliente = 0;
        foreach ($cliente['cotizaciones'] ?? [] as $cot) {
            $estado = $cot['estado'] ?? '';
            if ($estado === 'entregado') {
                $entregado_at = strtotime($cot['entregado_at'] ?? $cot['fecha'] ?? '');
                if ($entregado_at && ($now - $entregado_at) > $archivadas_seconds) {
                    // Entregado expiró → borrar (también su ZIP)
                    if (!empty($cot['zip_path'])) {
                        $zip = BS_DATA_DIR . '/' . $cot['zip_path'];
                        if (file_exists($zip)) @unlink($zip);
                    }
                    $stats['cotizaciones_borradas']++;
                    continue;
                }
                $kept[] = $cot;
            } elseif ($estado === 'aprobado') {
                // Obra viva en el tablero: no vence por antigüedad (ver arriba). A los 180 días
                // el PRESUPUESTO sale de la pestaña Presupuestos, pero la ficha queda donde está.
                if (empty($cot['fuera_de_presupuestos'])) {
                    $last_mod = strtotime($cot['fecha'] ?? '');
                    if (!empty($cot['transiciones']) && is_array($cot['transiciones'])) {
                        $last_t = end($cot['transiciones']);
                        if ($last_t && !empty($last_t['at'])) $last_mod = max($last_mod, strtotime($last_t['at']));
                    }
                    if ($last_mod && ($now - $last_mod) > $retention_seconds) {
                        $cot['fuera_de_presupuestos'] = date('c');
                        $stats['ocultos']++;
                        $ocultos_cliente++;
                    }
                }
                $kept[] = $cot;
            } elseif ($estado === 'papelera') {
                // En la papelera del tablero: la maneja bs_tablero_papelera_purgar() (30 días).
                $kept[] = $cot;
            } else {
                // No-entregado: si no hay entregado activo Y no se modificó hace >180 días → borrar
                if (!$tiene_entregado_activo) {
                    $last_mod = strtotime($cot['fecha'] ?? '');
                    // Tomar la última transición como mejor indicador de "última modificación"
                    if (!empty($cot['transiciones']) && is_array($cot['transiciones'])) {
                        $last_t = end($cot['transiciones']);
                        if ($last_t && !empty($last_t['at'])) {
                            $last_mod = max($last_mod, strtotime($last_t['at']));
                        }
                    }
                    if ($last_mod && ($now - $last_mod) > $retention_seconds) {
                        $stats['cotizaciones_borradas']++;
                        continue;
                    }
                }
                $kept[] = $cot;
            }
        }

        if (count($kept) === 0) {
            // Cliente vacío → borrar archivo entero. El número no se recicla (mismo criterio que delete.php).
            @unlink($path);
            $stats['clientes_borrados']++;
            $bs_idx = bs_read_json(BS_CLIENTES_INDEX);
            if (!is_array($bs_idx)) $bs_idx = [];
            $bs_idx[$bs_m[1]] = ['deleted_at' => date('c'), 'reason' => 'retention'];
            bs_write_json_atomic(BS_CLIENTES_INDEX, $bs_idx);
        } elseif (count($kept) !== count($cliente['cotizaciones'] ?? []) || $ocultos_cliente > 0) {
            // Cambió el array → reescribir
            $cliente['cotizaciones'] = $kept;
            $cliente['ultima_actualizacion'] = date('c');
            bs_write_json_atomic($path, $cliente);
        }
    }
}

// ============================================================
// Cleanup de zips/
// ============================================================

if (is_dir(BS_ZIPS_DIR)) {
    foreach (scandir(BS_ZIPS_DIR) as $f) {
        if (substr($f, 0, 1) === '.') continue;
        $path = BS_ZIPS_DIR . '/' . $f;
        if (is_file($path)) {
            $mtime = filemtime($path);
            // el ZIP de una obra archivada dura lo que dura la obra archivada (1 año), no 180 días
            if ($mtime && ($now - $mtime) > max($retention_seconds, $archivadas_seconds)) {
                @unlink($path);
                $stats['zips_borrados']++;
            }
        }
    }
}

// ============================================================
// Cleanup del tablero de obras (registro + adjuntos)
// ============================================================
// Se hace después de limpiar clientes/: lo que quedó ahí es lo que existe.

$vivas = [];
if (is_dir(BS_CLIENTES_DIR)) {
    foreach (scandir(BS_CLIENTES_DIR) as $f) {
        if (!preg_match('/^(\d{4,})\.json$/', $f)) continue;
        $cliente = bs_read_json(BS_CLIENTES_DIR . '/' . $f);
        if (!$cliente) { $vivas = null; break; } // ante la duda, no se borra nada
        foreach ($cliente['cotizaciones'] ?? [] as $cot) {
            $vivas[(string)($cliente['cliente_nro'] ?? '') . '-' . intval($cot['sub'] ?? 0)] = true;
        }
    }
}
if (is_array($vivas) && is_dir(BS_TABLERO_DIR)) {
    $fp = bs_lock_acquire(BS_TABLERO_LOCK);
    if ($fp) {
        $tablero = bs_tablero_leer();
        $cambio = false;
        foreach (array_keys($tablero['obras']) as $key) {
            if (isset($vivas[$key])) continue;
            bs_tablero_borrar_carpeta((string)$key);
            unset($tablero['obras'][$key]);
            $stats['obras_tablero_borradas']++;
            $cambio = true;
        }
        if ($cambio) bs_write_json_atomic(BS_TABLERO_FILE, $tablero);
        // Carpetas de adjuntos sin registro (subida cortada a la mitad, etc.)
        if (is_dir(BS_TABLERO_ADJ_DIR)) {
            foreach (scandir(BS_TABLERO_ADJ_DIR) as $d) {
                if ($d === '.' || $d === '..' || isset($vivas[$d])) continue;
                bs_tablero_borrar_carpeta($d);
            }
        }
        bs_lock_release($fp);
    }
}
bs_lock_release($bs_fp_cli);

// ============================================================
// Log
// ============================================================

$log_line = sprintf(
    "[%s] cleanup: cotizaciones=%d clientes=%d zips=%d obras_tablero=%d fuera_de_presupuestos=%d papelera=%d errores=%d\n",
    date('c'),
    $stats['cotizaciones_borradas'],
    $stats['clientes_borrados'],
    $stats['zips_borrados'],
    $stats['obras_tablero_borradas'],
    $stats['ocultos'],
    $stats['papelera'],
    count($stats['errores'])
);
@file_put_contents(BS_REGISTRO_CRON_LOG, $log_line, FILE_APPEND | LOCK_EX);

echo $log_line;
foreach ($stats['errores'] as $e) {
    echo "  ERROR: $e\n";
}
