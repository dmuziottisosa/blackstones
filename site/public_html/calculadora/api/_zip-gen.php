<?php
// ============================================================
// _zip-gen.php — Genera ZIP de presupuesto entregado
// ============================================================
// Llamado por state.php cuando se transiciona a "entregado".
//
// El ZIP contiene:
//   - presupuesto.json (data canónica)
//   - presupuesto.html (vista imprimible — el equipo abre y print-to-PDF)
//   - README.txt (instrucciones)
//
// El ZIP queda en bs-data/zips/{nro}-{sub}.zip durante 180 días.
// El path relativo se guarda en cot.zip_path para referencia.
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_render-helpers.php';

/**
 * Genera el ZIP de una cotización entregada.
 * Retorna el path relativo (a BS_DATA_DIR) del ZIP creado, o false en error.
 */
function bs_generate_zip($cliente_obj, $cot_data) {
    if (!class_exists('ZipArchive')) {
        // PHP sin extensión zip → fallback: NO generar (loggear)
        @file_put_contents(BS_REGISTRO_CRON_LOG,
            "[" . date('c') . "] WARN: PHP sin ZipArchive, no se generó ZIP\n",
            FILE_APPEND | LOCK_EX);
        return false;
    }

    if (!is_dir(BS_ZIPS_DIR)) {
        @mkdir(BS_ZIPS_DIR, 0755, true);
    }

    $cliente_nro = $cliente_obj['cliente_nro'] ?? '';
    $sub = $cot_data['sub'] ?? 0;
    $cli_nombre = $cliente_obj['cliente']['nombre'] ?? 'cliente';
    $cli_nombre_safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $cli_nombre);

    $zip_filename = $cliente_nro . '-' . $sub . '.zip';
    $zip_path = BS_ZIPS_DIR . '/' . $zip_filename;

    $zip = new ZipArchive();
    $tmp_path = $zip_path . '.tmp.' . getmypid();
    if ($zip->open($tmp_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }

    // 1. JSON canónico (datos completos)
    $json_blob = json_encode([
        'cliente'     => $cliente_obj['cliente'] ?? [],
        'cliente_nro' => $cliente_nro,
        'cotizacion'  => $cot_data,
        'generado_at' => date('c'),
        'version'     => BS_CONTRACT_VERSION,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $zip->addFromString('presupuesto.json', $json_blob);

    // 2. HTML imprimible (summary)
    ob_start();
    bs_render_html_summary($cliente_obj, $cot_data, [
        'show_print_button' => true,
        'wrap_html'         => true,
    ]);
    $html_blob = ob_get_clean();
    $zip->addFromString('presupuesto.html', $html_blob);

    // 3. README de instrucciones
    $readme = <<<TXT
BlackStones · Presupuesto entregado · {$cliente_nro}-{$sub}
================================================================

Cliente:    {$cli_nombre}
Concepto:   {$cot_data['concepto']}
Generado:   {date('c')}

Archivos en este ZIP:
  presupuesto.html  → Abrí en cualquier browser, hacé Imprimir > Guardar como PDF
  presupuesto.json  → Datos crudos (backup canónico)

Política de retención:
  Este ZIP se elimina automáticamente 180 días después de la entrega.
  Si necesitás guardarlo más tiempo, descargá los archivos antes.

—
BlackStones Marmolería
Av. Juan Bautista Alberdi 3575, CABA
+54 9 11 2468-5820
contacto@blackstones.com.ar
TXT;
    $zip->addFromString('README.txt', $readme);

    if (!$zip->close()) return false;

    // Atómico: rename del tmp al final
    if (!rename($tmp_path, $zip_path)) {
        @unlink($tmp_path);
        return false;
    }

    // Retornar path relativo a BS_DATA_DIR
    return 'zips/' . $zip_filename;
}
