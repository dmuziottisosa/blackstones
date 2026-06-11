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
//       Si estado == "entregado" Y (now - entregado_at) > 90 días:
//         Borrar la cotización del array.
//       Si estado != "entregado" Y NO existe ningún entregado en este cliente
//          Y (now - última_modificación) > 90 días:
//         Borrar la cotización del array.
//     Si quedaron 0 cotizaciones:
//       Borrar el cliente.json entero.
//
//   Para cada bs-data/zips/*.zip con mtime > 90 días:
//     Borrar el archivo.
//
// Idempotente: corridas duplicadas no rompen nada.
// ============================================================

require_once __DIR__ . '/_config.php';

// CLI-only (NO debe ser invocable via HTTP)
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit;
}

bs_ensure_dirs();

$now = time();
$retention_seconds = BS_RETENTION_DAYS * 24 * 3600;

$stats = [
    'cotizaciones_borradas' => 0,
    'clientes_borrados'     => 0,
    'zips_borrados'         => 0,
    'errores'               => [],
];

// ============================================================
// Cleanup de clientes/
// ============================================================

if (is_dir(BS_CLIENTES_DIR)) {
    foreach (scandir(BS_CLIENTES_DIR) as $f) {
        if (!preg_match('/^(\d{4,})\.json$/', $f)) continue;
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
                if ($entregado_at && ($now - $entregado_at) <= $retention_seconds) {
                    $tiene_entregado_activo = true;
                    break;
                }
            }
        }

        // Filtrar cotizaciones que sobreviven
        $kept = [];
        foreach ($cliente['cotizaciones'] ?? [] as $cot) {
            $estado = $cot['estado'] ?? '';
            if ($estado === 'entregado') {
                $entregado_at = strtotime($cot['entregado_at'] ?? $cot['fecha'] ?? '');
                if ($entregado_at && ($now - $entregado_at) > $retention_seconds) {
                    // Entregado expiró → borrar (también su ZIP)
                    if (!empty($cot['zip_path'])) {
                        $zip = BS_DATA_DIR . '/' . $cot['zip_path'];
                        if (file_exists($zip)) @unlink($zip);
                    }
                    $stats['cotizaciones_borradas']++;
                    continue;
                }
                $kept[] = $cot;
            } else {
                // No-entregado: si no hay entregado activo Y no se modificó hace >90 días → borrar
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
            // Cliente vacío → borrar archivo entero
            @unlink($path);
            $stats['clientes_borrados']++;
        } elseif (count($kept) !== count($cliente['cotizaciones'] ?? [])) {
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
            if ($mtime && ($now - $mtime) > $retention_seconds) {
                @unlink($path);
                $stats['zips_borrados']++;
            }
        }
    }
}

// ============================================================
// Log
// ============================================================

$log_line = sprintf(
    "[%s] cleanup: cotizaciones=%d clientes=%d zips=%d errores=%d\n",
    date('c'),
    $stats['cotizaciones_borradas'],
    $stats['clientes_borrados'],
    $stats['zips_borrados'],
    count($stats['errores'])
);
@file_put_contents(BS_REGISTRO_CRON_LOG, $log_line, FILE_APPEND | LOCK_EX);

echo $log_line;
foreach ($stats['errores'] as $e) {
    echo "  ERROR: $e\n";
}
