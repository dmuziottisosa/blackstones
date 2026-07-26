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

// Filtro por origen: 'publicidad' | 'local' | '' (= todos).
// Si esta activo, todo el dashboard se recalcula contra ese bucket.
$origen_filter = $_GET['origen'] ?? '';
if (!in_array($origen_filter, ['publicidad', 'local'], true)) $origen_filter = '';

// ============================================================
// 1. Obtener datos por mes (archivo o live)
// ============================================================
$meses = [];
$total_usd = 0; $total_ars = 0; $total_count = 0;
$clientes_unicos = [];
$materiales_map = []; // material_color → ['count' => N, 'monto_usd' => M]
// Breakdown por origen del presupuesto (publicidad / local).
// Si el campo no existe en la cotizacion, se asume 'local'.
// 'organico' es alias historico que mapeamos a 'local'.
$by_origen = [
    'publicidad' => ['count' => 0, 'monto_usd' => 0, 'monto_ars' => 0],
    'local'      => ['count' => 0, 'monto_usd' => 0, 'monto_ars' => 0],
];

for ($i = 0; $i < $months_back; $i++) {
    $month_ts = strtotime("-$i month", $now);
    $year_month = date('Y-m', $month_ts);
    $report_path = BS_REGISTRO_BY_MONTH_DIR . '/' . $year_month . '.json';

    // FUENTE DE VERDAD = los datos vivos de bs-data/clientes.
    // Reflejan el origen actual (editable desde el hub) y las entregas
    // marcadas DESPUES de que el cron archivo el mes. El archivo mensual
    // queda solo como fallback para meses cuya data ya se purgo por
    // retencion (ahi es lo unico que queda).
    $month_start = strtotime("first day of $year_month 00:00:00");
    $month_end   = strtotime("last day of $year_month 23:59:59");
    $live = _live_scan_month($month_start, $month_end, $origen_filter);

    if ($live['all_count'] === 0 && file_exists($report_path)) {
        // Fuente: archivo
        $data = bs_read_json($report_path);
        if (!$data) continue;
        $totales_mes = $data['totales'] ?? [];
        $count = intval($totales_mes['entregados_count'] ?? 0);
        if ($count === 0 && empty($data['entregados'])) continue;

        // Breakdown por origen del archivo (default local si no esta)
        $mes_by_origen = $totales_mes['by_origen'] ?? null;
        if (!is_array($mes_by_origen)) {
            // Archivos historicos sin breakdown — todo va a 'local'
            $mes_by_origen = [
                'local'      => ['count' => $count, 'monto_usd' => floatval($totales_mes['monto_usd'] ?? 0), 'monto_ars' => floatval($totales_mes['monto_ars'] ?? 0)],
                'publicidad' => ['count' => 0, 'monto_usd' => 0, 'monto_ars' => 0],
            ];
        } else {
            // Normalizar: si el archivo tenia el bucket 'organico' (alias
            // historico), mover el contenido a 'local'.
            if (isset($mes_by_origen['organico'])) {
                if (!isset($mes_by_origen['local'])) {
                    $mes_by_origen['local'] = ['count' => 0, 'monto_usd' => 0, 'monto_ars' => 0];
                }
                $mes_by_origen['local']['count']     += intval($mes_by_origen['organico']['count'] ?? 0);
                $mes_by_origen['local']['monto_usd'] += floatval($mes_by_origen['organico']['monto_usd'] ?? 0);
                $mes_by_origen['local']['monto_ars'] += floatval($mes_by_origen['organico']['monto_ars'] ?? 0);
                unset($mes_by_origen['organico']);
            }
        }

        // Con filtro: los totales del mes son los del bucket; sin filtro: todos.
        if ($origen_filter !== '') {
            $b = $mes_by_origen[$origen_filter] ?? ['count' => 0, 'monto_usd' => 0, 'monto_ars' => 0];
            $mes_count = intval($b['count'] ?? 0);
            $mes_usd   = floatval($b['monto_usd'] ?? 0);
            $mes_ars   = floatval($b['monto_ars'] ?? 0);
        } else {
            $mes_count = $count;
            $mes_usd   = floatval($totales_mes['monto_usd'] ?? 0);
            $mes_ars   = floatval($totales_mes['monto_ars'] ?? 0);
        }

        if ($mes_count > 0 || $origen_filter === '') {
            $meses[] = [
                'mes'              => $year_month,
                'entregados_count' => $mes_count,
                'monto_usd'        => $mes_usd,
                'monto_ars'        => $mes_ars,
                'by_origen'        => $mes_by_origen,
                'source'           => 'archivo',
            ];
        }
        $total_count += $mes_count;
        $total_usd   += $mes_usd;
        $total_ars   += $mes_ars;
        // by_origen acumula SIEMPRE los dos buckets (asi el switch tiene
        // contadores correctos en el panel 'Por origen').
        foreach (['publicidad', 'local'] as $ori) {
            $by_origen[$ori]['count']     += intval($mes_by_origen[$ori]['count'] ?? 0);
            $by_origen[$ori]['monto_usd'] += floatval($mes_by_origen[$ori]['monto_usd'] ?? 0);
            $by_origen[$ori]['monto_ars'] += floatval($mes_by_origen[$ori]['monto_ars'] ?? 0);
        }

        // Clientes únicos y top materiales del archivo: solo cargan SI no
        // hay filtro (el archivo no tiene breakdown por cotización individual,
        // así que no se puede atribuir cada cliente/material a un bucket).
        if ($origen_filter === '') {
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
        }
    } else {
        // Fuente: datos vivos.
        // by_origen acumula SIEMPRE los dos buckets, aunque haya filtro
        // activo — igual que la rama de archivo. Asi el panel 'Por origen'
        // muestra una sola escala y no mezcla archivo con live.
        foreach (['publicidad', 'local'] as $ori) {
            $by_origen[$ori]['count']     += intval($live['by_origen'][$ori]['count'] ?? 0);
            $by_origen[$ori]['monto_usd'] += floatval($live['by_origen'][$ori]['monto_usd'] ?? 0);
            $by_origen[$ori]['monto_ars'] += floatval($live['by_origen'][$ori]['monto_ars'] ?? 0);
        }
        // Sin filas para el filtro activo: no hay mes que mostrar, pero el
        // breakdown de arriba ya quedo contabilizado.
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
        // (by_origen ya se acumulo arriba, antes del early-continue)
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
// Lee todos los clientes UNA sola vez por request. El dashboard escanea
// 24 meses; sin este cache releia el directorio completo 24 veces.
function _bs_clientes_all() {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = [];
    if (!is_dir(BS_CLIENTES_DIR)) return $cache;
    foreach (scandir(BS_CLIENTES_DIR) as $f) {
        if (!preg_match('/^(\d{4,})\.json$/', $f)) continue;
        $c = bs_read_json(BS_CLIENTES_DIR . '/' . $f);
        if ($c) $cache[] = $c;
    }
    return $cache;
}

function _live_scan_month($month_start, $month_end, $origen_filter = '') {
    $result = [
        'count' => 0, 'monto_usd' => 0, 'monto_ars' => 0,
        // all_count = entregados del mes SIN filtrar. Indica si el mes tiene
        // data viva (y por lo tanto si hace falta caer al archivo).
        'all_count' => 0,
        'clientes_unicos' => [], 'materiales' => [],
        'by_origen' => [
            'publicidad' => ['count' => 0, 'monto_usd' => 0, 'monto_ars' => 0],
            'local'      => ['count' => 0, 'monto_usd' => 0, 'monto_ars' => 0],
        ],
    ];

    foreach (_bs_clientes_all() as $cliente) {
        foreach ($cliente['cotizaciones'] ?? [] as $cot) {
            if (($cot['estado'] ?? '') !== 'entregado') continue;
            // El mes se decide por la fecha de contrato si esta cargada;
            // si no, por la fecha de entrega (comportamiento historico).
            $ref = bs_fecha_ref($cot);
            $entregado_at = strtotime($ref);
            if (!$entregado_at) continue;
            if ($entregado_at < $month_start || $entregado_at > $month_end) continue;

            // Normalizar origen ('organico' alias historico de 'local')
            $ori = ($cot['origen'] ?? 'local');
            if ($ori !== 'publicidad') $ori = 'local';

            $totales = $cot['presupuesto']['totales'] ?? [];
            $monto_usd = floatval($totales['usd'] ?? 0);
            $monto_ars = floatval($totales['ars'] ?? 0);

            // by_origen y all_count acumulan SIEMPRE, sin importar el filtro:
            // el panel 'Por origen' muestra los dos buckets aunque estes
            // filtrando, y all_count decide si el mes tiene data viva.
            $result['all_count']++;
            $result['by_origen'][$ori]['count']++;
            $result['by_origen'][$ori]['monto_usd'] += $monto_usd;
            $result['by_origen'][$ori]['monto_ars'] += $monto_ars;

            // De aca para abajo si respetamos el filtro (totales del mes,
            // clientes unicos y top materiales).
            if ($origen_filter !== '' && $ori !== $origen_filter) continue;

            $result['count']++;
            $result['monto_usd'] += $monto_usd;
            $result['monto_ars'] += $monto_ars;
            $result['clientes_unicos'][] = $cliente['cliente_nro'] ?? '';

            // Top materiales: prioridad al material_final si esta seteado
            // (es el material confirmado de la venta; gana sobre los items
            // del desglose). Si no, fallback a los items de la cot.
            $mf = trim((string)($cot['material_final'] ?? ''));
            if ($mf !== '') {
                if (!isset($result['materiales'][$mf])) $result['materiales'][$mf] = ['count' => 0, 'monto_usd' => 0];
                $result['materiales'][$mf]['count']++;
                $result['materiales'][$mf]['monto_usd'] += $monto_usd;
            } else {
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
    }
    $result['clientes_unicos'] = array_values(array_unique($result['clientes_unicos']));
    return $result;
}
