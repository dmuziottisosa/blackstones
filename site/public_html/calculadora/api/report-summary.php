<?php
// ============================================================
// report-summary.php — Datos para el dashboard del tab Reporte
// ============================================================
// GET /api/report-summary.php
//
// Lógica: para cada mes en los últimos 24 meses,
//   - si existe bs-data/registro/by-month/{YYYY-MM}.json → usar (canónico).
//   - sino → live scan de bs-data/clientes/ filtrando entregados de ese mes.
//
// Agrega: top materiales, totales acumulados, banner de mes sin viewed.
//
// Response (resumido):
//   { ok: true, totales: {...}, top_materiales: [...], meses: [...],
//     banner_mes_pendiente: "2026-05"|null }
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

bs_require_auth();
bs_ensure_dirs();

$months_back = 24;
$now = time();

// ============================================================
// 1. Obtener datos por mes (archivo o live)
// ============================================================
$meses = [];
$total_usd = 0; $total_ars = 0; $total_count = 0;
$clientes_unicos = [];
$materiales_map = []; // material_color → ['count' => N, 'monto_usd' => M]
// Breakdown por origen del presupuesto (publicidad / organico).
// Si el campo no existe en la cotizacion, se asume 'organico'.
$by_origen = [
    'publicidad' => ['count' => 0, 'monto_usd' => 0, 'monto_ars' => 0],
    'organico'   => ['count' => 0, 'monto_usd' => 0, 'monto_ars' => 0],
];

for ($i = 0; $i < $months_back; $i++) {
    $month_ts = strtotime("-$i month", $now);
    $year_month = date('Y-m', $month_ts);
    $report_path = BS_REGISTRO_BY_MONTH_DIR . '/' . $year_month . '.json';

    if (file_exists($report_path)) {
        // Fuente: archivo
        $data = bs_read_json($report_path);
        if (!$data) continue;
        $totales_mes = $data['totales'] ?? [];
        $count = intval($totales_mes['entregados_count'] ?? 0);
        if ($count === 0 && empty($data['entregados'])) continue;

        // Breakdown por origen del archivo (default organico si no esta)
        $mes_by_origen = $totales_mes['by_origen'] ?? null;
        if (!is_array($mes_by_origen)) {
            // Archivos historicos sin breakdown — todo va a 'organico'
            $mes_by_origen = [
                'organico'   => ['count' => $count, 'monto_usd' => floatval($totales_mes['monto_usd'] ?? 0), 'monto_ars' => floatval($totales_mes['monto_ars'] ?? 0)],
                'publicidad' => ['count' => 0, 'monto_usd' => 0, 'monto_ars' => 0],
            ];
        }

        $meses[] = [
            'mes'              => $year_month,
            'entregados_count' => $count,
            'monto_usd'        => floatval($totales_mes['monto_usd'] ?? 0),
            'monto_ars'        => floatval($totales_mes['monto_ars'] ?? 0),
            'by_origen'        => $mes_by_origen,
            'source'           => 'archivo',
        ];
        $total_count += $count;
        $total_usd += floatval($totales_mes['monto_usd'] ?? 0);
        $total_ars += floatval($totales_mes['monto_ars'] ?? 0);
        foreach (['publicidad', 'organico'] as $ori) {
            $by_origen[$ori]['count']     += intval($mes_by_origen[$ori]['count'] ?? 0);
            $by_origen[$ori]['monto_usd'] += floatval($mes_by_origen[$ori]['monto_usd'] ?? 0);
            $by_origen[$ori]['monto_ars'] += floatval($mes_by_origen[$ori]['monto_ars'] ?? 0);
        }

        foreach ($data['entregados'] ?? [] as $e) {
            $cn = $e['cliente_nro'] ?? '';
            if ($cn) $clientes_unicos[$cn] = true;
            foreach ($e['materiales'] ?? [] as $mat_color) {
                if (!$mat_color) continue;
                if (!isset($materiales_map[$mat_color])) $materiales_map[$mat_color] = ['count' => 0, 'monto_usd' => 0];
                $materiales_map[$mat_color]['count']++;
            }
        }
        foreach ($data['top_materiales'] ?? [] as $tm) {
            $key = $tm['material_color'] ?? '';
            if ($key && isset($materiales_map[$key])) {
                $materiales_map[$key]['monto_usd'] += floatval($tm['monto_usd'] ?? 0);
            }
        }
    } else {
        // Fuente: live scan
        $month_start = strtotime("first day of $year_month 00:00:00");
        $month_end   = strtotime("last day of $year_month 23:59:59");
        $live = _live_scan_month($month_start, $month_end);
        if ($live['count'] === 0) continue;

        $meses[] = [
            'mes'              => $year_month,
            'entregados_count' => $live['count'],
            'monto_usd'        => $live['monto_usd'],
            'monto_ars'        => $live['monto_ars'],
            'by_origen'        => $live['by_origen'],
            'source'           => 'live',
        ];
        $total_count += $live['count'];
        $total_usd += $live['monto_usd'];
        $total_ars += $live['monto_ars'];
        foreach (['publicidad', 'organico'] as $ori) {
            $by_origen[$ori]['count']     += intval($live['by_origen'][$ori]['count'] ?? 0);
            $by_origen[$ori]['monto_usd'] += floatval($live['by_origen'][$ori]['monto_usd'] ?? 0);
            $by_origen[$ori]['monto_ars'] += floatval($live['by_origen'][$ori]['monto_ars'] ?? 0);
        }
        foreach ($live['clientes_unicos'] as $cn) $clientes_unicos[$cn] = true;
        foreach ($live['materiales'] as $mat_color => $info) {
            if (!isset($materiales_map[$mat_color])) $materiales_map[$mat_color] = ['count' => 0, 'monto_usd' => 0];
            $materiales_map[$mat_color]['count'] += $info['count'];
            $materiales_map[$mat_color]['monto_usd'] += $info['monto_usd'];
        }
    }
}

// ============================================================
// 2. Top 10 materiales
// ============================================================
$tops = [];
foreach ($materiales_map as $key => $info) {
    $tops[] = ['material_color' => $key, 'count' => $info['count'], 'monto_usd' => $info['monto_usd']];
}
usort($tops, function($a, $b) { return $b['count'] - $a['count']; });
$tops = array_slice($tops, 0, 10);

// ============================================================
// 3. Banner: mes pasado tiene reporte pero sin .viewed
// ============================================================
$banner_pendiente = null;
$prev_month = date('Y-m', strtotime('-1 month', $now));
$prev_report = BS_REGISTRO_BY_MONTH_DIR . '/' . $prev_month . '.json';
$prev_viewed = BS_REGISTRO_VIEWED_DIR . '/' . $prev_month . '.flag';
if (file_exists($prev_report) && !file_exists($prev_viewed)) {
    $banner_pendiente = $prev_month;
}

// ============================================================
// 4. Response
// ============================================================
bs_ok([
    'totales' => [
        'entregados_count' => $total_count,
        'monto_usd'        => $total_usd,
        'monto_ars'        => $total_ars,
        'clientes_unicos'  => count($clientes_unicos),
        'by_origen'        => $by_origen,
    ],
    'top_materiales'        => $tops,
    'meses'                 => $meses,
    'banner_mes_pendiente'  => $banner_pendiente,
]);

// ============================================================
// Helper: live scan de un mes
// ============================================================
function _live_scan_month($month_start, $month_end) {
    $result = [
        'count' => 0, 'monto_usd' => 0, 'monto_ars' => 0,
        'clientes_unicos' => [], 'materiales' => [],
        'by_origen' => [
            'publicidad' => ['count' => 0, 'monto_usd' => 0, 'monto_ars' => 0],
            'organico'   => ['count' => 0, 'monto_usd' => 0, 'monto_ars' => 0],
        ],
    ];
    if (!is_dir(BS_CLIENTES_DIR)) return $result;

    foreach (scandir(BS_CLIENTES_DIR) as $f) {
        if (!preg_match('/^(\d{4,})\.json$/', $f)) continue;
        $cliente = bs_read_json(BS_CLIENTES_DIR . '/' . $f);
        if (!$cliente) continue;

        foreach ($cliente['cotizaciones'] ?? [] as $cot) {
            if (($cot['estado'] ?? '') !== 'entregado') continue;
            $entregado_at = strtotime($cot['entregado_at'] ?? $cot['fecha'] ?? '');
            if (!$entregado_at) continue;
            if ($entregado_at < $month_start || $entregado_at > $month_end) continue;

            $totales = $cot['presupuesto']['totales'] ?? [];
            $monto_usd = floatval($totales['usd'] ?? 0);
            $monto_ars = floatval($totales['ars'] ?? 0);
            $result['count']++;
            $result['monto_usd'] += $monto_usd;
            $result['monto_ars'] += $monto_ars;
            $result['clientes_unicos'][] = $cliente['cliente_nro'] ?? '';
            // Breakdown por origen. Default 'organico' si no hay campo.
            $ori = ($cot['origen'] ?? 'organico');
            if ($ori !== 'publicidad') $ori = 'organico';
            $result['by_origen'][$ori]['count']++;
            $result['by_origen'][$ori]['monto_usd'] += $monto_usd;
            $result['by_origen'][$ori]['monto_ars'] += $monto_ars;

            foreach (['m','a','l','i','b'] as $sec) {
                foreach ($cot['presupuesto']['secciones'][$sec]['items'] ?? [] as $item) {
                    $key = ($item['mat'] ?? '') . ' · ' . ($item['color'] ?? '');
                    if ($key === ' · ') continue;
                    if (!isset($result['materiales'][$key])) $result['materiales'][$key] = ['count' => 0, 'monto_usd' => 0];
                    $result['materiales'][$key]['count']++;
                    $result['materiales'][$key]['monto_usd'] += $monto_usd;
                }
            }
        }
    }
    $result['clientes_unicos'] = array_values(array_unique($result['clientes_unicos']));
    return $result;
}
