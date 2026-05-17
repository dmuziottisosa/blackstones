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

    if (!isset($obj['cotizaciones']) || !is_array($obj['cotizaciones'])) {
        continue;
    }
    $changed = false;
    // CRITICO: iterar sobre $obj['cotizaciones'] directamente, NO via "?? []".
    // El "?? []" crea una expresion temporal y las mutaciones a &$cot no
    // propagan al $obj original. Bug subtil que reportaba "X corregidas"
    // pero escribia el archivo sin cambios.
    foreach ($obj['cotizaciones'] as &$cot) {
        $stats['cotizaciones_revisadas']++;

        $totales     = $cot['presupuesto']['totales'] ?? null;
        $dolar_venta = floatval($cot['presupuesto']['dolar_venta'] ?? 0);
        $usd         = $totales ? floatval($totales['usd'] ?? 0) : 0;
        $ars_actual  = $totales ? floatval($totales['ars'] ?? 0) : 0;

        // Si ya tiene _subtotal_* en algun item (data post-fix de la calc),
        // significa que el presupuesto fue re-guardado con la calc arreglada
        // y los totales ya son correctos. Skip.
        $hasSubtotals = false;
        foreach (($cot['presupuesto']['secciones'] ?? []) as $sec) {
            foreach (($sec['items'] ?? []) as $it) {
                if (isset($it['_subtotal_ars']) || isset($it['_subtotal_usd'])) {
                    $hasSubtotals = true;
                    break 2;
                }
            }
        }
        if ($hasSubtotals) {
            $stats['cotizaciones_skipped']++;
            continue;
        }

        $ars_esperado = null;
        $metodo = null;

        // Heuristica A: ars actual es 0 PERO hay base USD -> recomputar
        // (caso donde se perdio totalmente el ars al guardar)
        if ($usd > 0 && $dolar_venta > 0 && $ars_actual == 0) {
            $ars_esperado = round($usd * $dolar_venta);
            $metodo = 'usd_x_dolar_ars_cero';
        }

        // Heuristica B: factor 1000 con USD presente. Requiere que
        // ars_actual * 1000 sea coherente con usd * dolar_venta
        // (smoking gun del bug parseo "658.000" -> 658). Si no matchea,
        // asumimos que ars es legitimo (ej: flete en ARS sumado al USD).
        if ($ars_esperado === null && $usd > 0 && $dolar_venta > 0 && $ars_actual > 0) {
            $candidato = $ars_actual * 1000;
            $esperado_usd = $usd * $dolar_venta;
            if ($candidato >= $esperado_usd * 0.5 && $candidato <= $esperado_usd * 2) {
                $ars_esperado = $candidato;
                $metodo = 'factor_1000_con_usd';
            }
        }

        // Heuristica C: factor 1000 puro (cotizacion sin USD).
        // ars implausiblemente bajo (<10000) y sin USD -> bug claro,
        // no existen presupuestos reales <10k ARS en marmoleria.
        if ($ars_esperado === null && $usd == 0 && $ars_actual > 0 && $ars_actual < 10000) {
            $ars_esperado = $ars_actual * 1000;
            $metodo = 'factor_1000_ars_puro';
        }

        if ($ars_esperado === null) {
            $stats['cotizaciones_skipped']++;
            continue;
        }

        if ($ars_esperado === null) {
            $stats['cotizaciones_skipped']++;
            continue;
        }

        $fixes[] = [
            'cliente_nro'   => $obj['cliente_nro'] ?? '?',
            'sub'           => $cot['sub'] ?? '?',
            'metodo'        => $metodo,
            'usd'           => $usd,
            'dolar_venta'   => $dolar_venta,
            'ars_actual'    => $ars_actual,
            'ars_esperado'  => $ars_esperado,
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
        } else {
            // Sanity readback: leer el archivo recien escrito y verificar
            // que los valores hayan persistido. Detecta el bug de iteracion
            // por valor (ver comentario arriba) y problemas de permisos
            // silenciosos.
            clearstatcache(true, $path);
            $verif = bs_read_json($path);
            if (!$verif) {
                $errors[] = "Write reportado OK pero no se pudo releer $f";
            } else {
                foreach ($verif['cotizaciones'] ?? [] as $vcot) {
                    $vt = $vcot['presupuesto']['totales']['ars'] ?? null;
                    foreach ($fixes as $fix) {
                        if ($fix['cliente_nro'] === ($verif['cliente_nro'] ?? '') && $fix['sub'] == ($vcot['sub'] ?? '')) {
                            if (abs(($vt ?? 0) - $fix['ars_esperado']) > 1) {
                                $errors[] = "Write NO persistio en {$fix['cliente_nro']}-{$fix['sub']}: disk={$vt}, esperado={$fix['ars_esperado']}";
                            }
                        }
                    }
                }
            }
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
