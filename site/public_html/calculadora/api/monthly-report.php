<?php
// ============================================================
// monthly-report.php — Cron diario idempotente
// ============================================================
// Corre todas las noches a las 02:00 ART desde panel Hostinger.
// Comando cron sugerido:
//   0 2 * * * php /home/u144473384/domains/blackstones.com.ar/public_html/calculadora/api/monthly-report.php
//
// Lógica idempotente:
//   Para cada mes en los últimos 3 cerrados (no incluye el actual):
//     Si bs-data/registro/by-month/{YYYY-MM}.json NO existe:
//       Generar reporte del mes desde bs-data/clientes/*.json
//       Si hay datos: persistir el JSON.
//
//   Actualizar bs-data/registro/clientes-index.json con los entregados
//   activos (de modo incremental — agrega entregados nuevos).
//
//   El Excel del reporte mensual NO se persiste (es regenerable on-demand
//   desde el frontend: tab Reporte ya consume `report-summary.php`, y el
//   hub/Activos tiene CSV resumen + Excel per-presupuesto).
// ============================================================

require_once __DIR__ . '/_config.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit;
}

bs_ensure_dirs();

$now = time();
$generated = [];
$skipped = [];

// ============================================================
// Generar reportes faltantes de los últimos 3 meses cerrados
// ============================================================

for ($i = 1; $i <= 3; $i++) {
    $target_ts = strtotime("-$i month", $now);
    $target_year_month = date('Y-m', $target_ts);
    $report_path = BS_REGISTRO_BY_MONTH_DIR . '/' . $target_year_month . '.json';

    if (file_exists($report_path)) {
        $skipped[] = $target_year_month;
        continue;
    }

    // Generar reporte del mes objetivo
    $month_start = strtotime("first day of $target_year_month 00:00:00");
    $month_end = strtotime("last day of $target_year_month 23:59:59");

    $entregados_del_mes = [];
    $monto_usd_total = 0;
    $monto_ars_total = 0;
    $m2_total = 0;
    $clientes_unicos = [];
    $materiales_count = [];

    if (is_dir(BS_CLIENTES_DIR)) {
        foreach (scandir(BS_CLIENTES_DIR) as $f) {
            if (!preg_match('/^(\d{4,})\.json$/', $f)) continue;
            $cliente = bs_read_json(BS_CLIENTES_DIR . '/' . $f);
            if (!$cliente) continue;

            foreach ($cliente['cotizaciones'] ?? [] as $cot) {
                if (($cot['estado'] ?? '') !== 'entregado') continue;
                $entregado_at = strtotime($cot['entregado_at'] ?? $cot['fecha'] ?? '');
                if (!$entregado_at) continue;
                if ($entregado_at < $month_start || $entregado_at > $month_end) continue;

                $cli = $cliente['cliente'] ?? [];
                $totales = $cot['presupuesto']['totales'] ?? [];
                $monto_usd = floatval($totales['usd'] ?? 0);
                $monto_ars = floatval($totales['ars'] ?? 0);

                // Materiales únicos
                $mats_in_cot = [];
                foreach (['m', 'a', 'l', 'i', 'b'] as $sec) {
                    foreach ($cot['presupuesto']['secciones'][$sec]['items'] ?? [] as $item) {
                        $key = ($item['mat'] ?? '') . ' · ' . ($item['color'] ?? '');
                        if ($key !== ' · ') {
                            $mats_in_cot[$key] = true;
                        }
                    }
                }
                foreach (array_keys($mats_in_cot) as $mat_key) {
                    if (!isset($materiales_count[$mat_key])) {
                        $materiales_count[$mat_key] = ['count' => 0, 'monto_usd' => 0];
                    }
                    $materiales_count[$mat_key]['count']++;
                    $materiales_count[$mat_key]['monto_usd'] += $monto_usd;
                }

                $entregados_del_mes[] = [
                    'cliente_nro'    => $cliente['cliente_nro'] ?? '',
                    'sub'            => $cot['sub'] ?? 0,
                    'cliente_nombre' => $cli['nombre'] ?? '',
                    'cliente_dni'    => $cli['dni'] ?? '',
                    'fecha_entregado'=> date('Y-m-d', $entregado_at),
                    'concepto'       => $cot['concepto'] ?? '',
                    'monto_usd'      => $monto_usd,
                    'monto_ars'      => $monto_ars,
                    'materiales'     => array_keys($mats_in_cot),
                ];

                $monto_usd_total += $monto_usd;
                $monto_ars_total += $monto_ars;
                $clientes_unicos[$cliente['cliente_nro'] ?? ''] = true;
            }
        }
    }

    // Si no hay datos del mes, NO generar archivo (logear y seguir)
    if (count($entregados_del_mes) === 0) {
        @file_put_contents(BS_REGISTRO_CRON_LOG,
            "[" . date('c') . "] mes $target_year_month: sin datos, no se generó reporte\n",
            FILE_APPEND | LOCK_EX);
        continue;
    }

    // Top materiales del mes
    arsort($materiales_count);
    $top_materiales = [];
    $i_mat = 0;
    foreach ($materiales_count as $key => $v) {
        if ($i_mat++ >= 10) break;
        $top_materiales[] = ['material_color' => $key, 'count' => $v['count'], 'monto_usd' => $v['monto_usd']];
    }

    $report = [
        'version' => BS_CONTRACT_VERSION,
        'mes' => $target_year_month,
        'generado_at' => date('c'),
        'generado_por' => 'cron-auto',
        'totales' => [
            'entregados_count' => count($entregados_del_mes),
            'clientes_unicos'  => count($clientes_unicos),
            'monto_usd'        => $monto_usd_total,
            'monto_ars'        => $monto_ars_total,
        ],
        'top_materiales' => $top_materiales,
        'entregados'     => $entregados_del_mes,
    ];

    if (bs_write_json_atomic($report_path, $report)) {
        $generated[] = $target_year_month;
    }
}

// ============================================================
// Actualizar clientes-index.json
// ============================================================

$index = bs_read_json(BS_CLIENTES_INDEX);
if (!is_array($index)) $index = [];

if (is_dir(BS_CLIENTES_DIR)) {
    foreach (scandir(BS_CLIENTES_DIR) as $f) {
        if (!preg_match('/^(\d{4,})\.json$/', $f, $m)) continue;
        $cliente_nro = str_pad($m[1], 4, '0', STR_PAD_LEFT);
        $cliente = bs_read_json(BS_CLIENTES_DIR . '/' . $f);
        if (!$cliente) continue;

        foreach ($cliente['cotizaciones'] ?? [] as $cot) {
            if (($cot['estado'] ?? '') !== 'entregado') continue;

            $entregado_at = $cot['entregado_at'] ?? $cot['fecha'] ?? '';
            $totales = $cot['presupuesto']['totales'] ?? [];
            $monto_usd = floatval($totales['usd'] ?? 0);
            $cli = $cliente['cliente'] ?? [];

            if (!isset($index[$cliente_nro])) {
                $index[$cliente_nro] = [
                    'nombre'   => $cli['nombre'] ?? '',
                    'dni'      => $cli['dni'] ?? '',
                    'celular'  => $cli['celular'] ?? '',
                    'entregas' => [],
                    'total_entregas' => 0,
                    'total_usd_acumulado' => 0,
                    'primera_entrega' => substr($entregado_at, 0, 10),
                    'ultima_entrega'  => substr($entregado_at, 0, 10),
                ];
            }

            // Verificar si ya está esta entrega indexada
            $sub_key = $cliente_nro . '-' . ($cot['sub'] ?? 0);
            $ya_indexado = false;
            foreach ($index[$cliente_nro]['entregas'] as $e) {
                if (($e['sub'] ?? '') == ($cot['sub'] ?? 0)) { $ya_indexado = true; break; }
            }
            if ($ya_indexado) continue;

            // Extraer materiales únicos de esta cotización
            $mats_in_cot = [];
            foreach (['m', 'a', 'l', 'i', 'b'] as $sec) {
                foreach ($cot['presupuesto']['secciones'][$sec]['items'] ?? [] as $item) {
                    $key = ($item['mat'] ?? '') . ' · ' . ($item['color'] ?? '');
                    if ($key !== ' · ') {
                        $mats_in_cot[$key] = true;
                    }
                }
            }

            $index[$cliente_nro]['entregas'][] = [
                'sub' => $cot['sub'] ?? 0,
                'mes' => substr($entregado_at, 0, 7),
                'fecha_entregado' => substr($entregado_at, 0, 10),
                'concepto' => $cot['concepto'] ?? '',
                'monto_usd' => $monto_usd,
                'materiales' => array_keys($mats_in_cot),
            ];
            $index[$cliente_nro]['total_entregas']++;
            $index[$cliente_nro]['total_usd_acumulado'] += $monto_usd;
            $f_entregado = substr($entregado_at, 0, 10);
            if ($f_entregado < $index[$cliente_nro]['primera_entrega']) $index[$cliente_nro]['primera_entrega'] = $f_entregado;
            if ($f_entregado > $index[$cliente_nro]['ultima_entrega'])  $index[$cliente_nro]['ultima_entrega'] = $f_entregado;
            // Refrescar nombre/dni/cel con los del último entregado (no perdemos)
            foreach (['nombre', 'dni', 'celular'] as $k) {
                if (!empty($cli[$k])) $index[$cliente_nro][$k] = $cli[$k];
            }
        }
    }
}
bs_write_json_atomic(BS_CLIENTES_INDEX, $index);

// ============================================================
// Log
// ============================================================
$log_line = sprintf(
    "[%s] monthly-report: generados=[%s] skipped=[%s]\n",
    date('c'),
    implode(',', $generated),
    implode(',', $skipped)
);
@file_put_contents(BS_REGISTRO_CRON_LOG, $log_line, FILE_APPEND | LOCK_EX);

echo $log_line;
