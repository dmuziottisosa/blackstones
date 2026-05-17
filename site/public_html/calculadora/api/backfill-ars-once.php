<?php
// ============================================================
// backfill-ars-once.php — One-shot fix del bug de parseo AR
// ============================================================
// Bug: bsParseAR interpretaba "658.000" como 658 (parseFloat
// trata el punto como decimal). Resultado: cotizaciones con
// monto ARS guardado 1000x más chico de lo real.
//
// Fix: recalcular ars = round(usd * dolar_venta) cuando la
// cotización tiene ambos datos y el ARS guardado parece estar
// mal (ars < usd * 100 — heurística: 1 USD = ~1000 ARS hoy,
// así que ars muy chico vs usd indica el bug).
//
// USO:
//   1. Abrir en browser autenticado:
//      https://blackstones.com.ar/calculadora/api/backfill-ars-once.php?confirm=YES
//   2. Modo dry-run (solo lista qué tocaría):
//      https://blackstones.com.ar/calculadora/api/backfill-ars-once.php?confirm=YES&dry=1
//
//   Después de correrlo: BORRAR ESTE ARCHIVO del FTP.
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

bs_require_auth();

if (($_GET['confirm'] ?? '') !== 'YES') {
    bs_error('Backfill requiere ?confirm=YES. Para evitar accidentes.', 400);
}

$dry = ($_GET['dry'] ?? '') === '1';

$stats = [
    'clientes_revisados'      => 0,
    'cotizaciones_revisadas'  => 0,
    'cotizaciones_corregidas' => 0,
    'cotizaciones_skipped'    => 0,
];
$fixes = [];
$errors = [];

if (!is_dir(BS_CLIENTES_DIR)) {
    bs_error('No existe ' . BS_CLIENTES_DIR, 500);
}

foreach (scandir(BS_CLIENTES_DIR) as $f) {
    if (!preg_match('/^(\d{4,})\.json$/', $f)) continue;
    $path = BS_CLIENTES_DIR . '/' . $f;
    $obj = bs_read_json($path);
    if (!$obj) { $errors[] = "No se pudo leer $f"; continue; }
    $stats['clientes_revisados']++;

    $changed = false;
    foreach ($obj['cotizaciones'] ?? [] as &$cot) {
        $stats['cotizaciones_revisadas']++;

        $totales     = $cot['presupuesto']['totales'] ?? null;
        $dolar_venta = floatval($cot['presupuesto']['dolar_venta'] ?? 0);
        $usd         = $totales ? floatval($totales['usd'] ?? 0) : 0;
        $ars_actual  = $totales ? floatval($totales['ars'] ?? 0) : 0;

        // Necesitamos USD > 0 y dolar_venta > 0 para recalcular
        if ($usd <= 0 || $dolar_venta <= 0) {
            $stats['cotizaciones_skipped']++;
            continue;
        }

        $ars_esperado = round($usd * $dolar_venta);

        // Heurística: el ARS está mal si es <1% del esperado (bug típico: /1000)
        $ratio = $ars_actual > 0 ? ($ars_actual / $ars_esperado) : 0;
        if ($ratio >= 0.5 && $ratio <= 2.0) {
            // Está dentro del margen razonable, no tocar
            $stats['cotizaciones_skipped']++;
            continue;
        }

        // Bug detectado: ars mal. Recalcular.
        $fixes[] = [
            'cliente_nro'   => $obj['cliente_nro'] ?? '?',
            'sub'           => $cot['sub'] ?? '?',
            'usd'           => $usd,
            'dolar_venta'   => $dolar_venta,
            'ars_actual'    => $ars_actual,
            'ars_esperado'  => $ars_esperado,
            'ratio_actual'  => round($ratio, 4),
        ];

        if (!$dry) {
            $cot['presupuesto']['totales']['ars'] = $ars_esperado;
            $changed = true;
        }
        $stats['cotizaciones_corregidas']++;
    }
    unset($cot);

    if ($changed && !$dry) {
        $obj['ultima_actualizacion'] = date('c');
        if (!bs_write_json_atomic($path, $obj)) {
            $errors[] = "No se pudo escribir $f";
        }
    }
}

bs_ok([
    'dry_run'      => $dry,
    'stats'        => $stats,
    'fixes'        => $fixes,
    'errors'       => $errors,
    'recordatorio' => 'BORRAR ESTE ARCHIVO (backfill-ars-once.php) del FTP cuando termines.',
]);
