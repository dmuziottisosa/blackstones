<?php
// ============================================================
// list.php — Listar cotizaciones activas para tab "Activos"
// ============================================================
// GET /api/list.php
// Query params (opcionales):
//   - estado: filtrar por estado (borrador, enviado, aprobado, entregado, perdido)
//   - q: search en nombre, celular, dni, concepto
//   - desde: YYYY-MM-DD (filtrar por fecha)
//   - hasta: YYYY-MM-DD
//   - page: 1-based (default 1)
//   - per_page: default 50, max 200
//
// Response:
//   {
//     "ok": true,
//     "total": 187,
//     "page": 1,
//     "per_page": 50,
//     "results": [
//       {
//         "cliente_nro", "cliente_nombre", "cliente_celular", "cliente_dni",
//         "sub", "fecha", "concepto", "estado",
//         "monto_usd", "monto_ars", "m2"
//       }
//     ]
//   }
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

bs_require_auth();

$estado_filter = $_GET['estado'] ?? '';
$q = strtolower(trim($_GET['q'] ?? ''));
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = min(200, max(1, intval($_GET['per_page'] ?? 50)));

$results = [];

if (is_dir(BS_CLIENTES_DIR)) {
    $files = scandir(BS_CLIENTES_DIR);
    // Optimización: orden DESC por nombre (= por nro = aprox por fecha de creación)
    // así los más recientes se procesan primero. No cambia el resultado final
    // (hay un usort por fecha al final), pero ordena el cache de FS warming
    // para que las páginas iniciales (las más usadas) tengan locality.
    usort($files, function($a, $b) { return strcmp($b, $a); });

    foreach ($files as $f) {
        if (!preg_match('/^(\d{4,})\.json$/', $f)) continue;
        $cliente_obj = bs_read_json(BS_CLIENTES_DIR . '/' . $f);
        if (!$cliente_obj) continue;

        $cli = $cliente_obj['cliente'] ?? [];
        $cliente_nro = $cliente_obj['cliente_nro'] ?? '';

        foreach ($cliente_obj['cotizaciones'] ?? [] as $cot) {
            // Filtro estado
            if ($estado_filter !== '' && ($cot['estado'] ?? '') !== $estado_filter) continue;

            // Filtro fechas
            $fecha = $cot['fecha'] ?? '';
            $fecha_date = substr($fecha, 0, 10); // YYYY-MM-DD
            if ($desde !== '' && $fecha_date < $desde) continue;
            if ($hasta !== '' && $fecha_date > $hasta) continue;

            // Filtro search
            if ($q !== '') {
                $haystack = strtolower(
                    ($cli['nombre'] ?? '') . ' ' .
                    ($cli['celular'] ?? '') . ' ' .
                    ($cli['dni'] ?? '') . ' ' .
                    ($cot['concepto'] ?? '') . ' ' .
                    $cliente_nro
                );
                if (strpos($haystack, $q) === false) continue;
            }

            // Calcular totales rápidamente desde presupuesto.totales si existe
            $monto_usd = 0;
            $monto_ars = 0;
            $m2 = 0;
            if (isset($cot['presupuesto']['totales'])) {
                $t = $cot['presupuesto']['totales'];
                $monto_usd = floatval($t['usd'] ?? 0);
                $monto_ars = floatval($t['ars'] ?? 0);
            }

            $results[] = [
                'cliente_nro'       => $cliente_nro,
                'cliente_nombre'    => $cli['nombre'] ?? '',
                'cliente_celular'   => $cli['celular'] ?? '',
                'cliente_dni'       => $cli['dni'] ?? '',
                'cliente_email'     => $cli['email'] ?? '',
                'cliente_direccion' => $cli['direccion'] ?? '',
                'sub'               => $cot['sub'] ?? 0,
                'fecha'             => $fecha,
                'concepto'          => $cot['concepto'] ?? '',
                'notas'             => $cot['notas'] ?? '',
                'estado'            => $cot['estado'] ?? '',
                'monto_usd'         => $monto_usd,
                'monto_ars'         => $monto_ars,
                'm2'                => $m2,
                'ajuste_manual'     => isset($cot['ajuste_manual']) ? true : false,
                // Origen del presupuesto: 'publicidad' u 'organico'. Si el
                // campo no existe (presupuestos viejos), default 'organico'.
                'origen'            => ($cot['origen'] ?? 'organico'),
            ];
        }
    }
}

// Ordenar por fecha desc
usort($results, function($a, $b) {
    return strcmp($b['fecha'], $a['fecha']);
});

$total = count($results);
$offset = ($page - 1) * $per_page;
$page_results = array_slice($results, $offset, $per_page);

bs_ok([
    'total'    => $total,
    'page'     => $page,
    'per_page' => $per_page,
    'results'  => $page_results,
]);
