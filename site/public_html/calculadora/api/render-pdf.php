<?php
// ============================================================
// render-pdf.php — Vista imprimible de una cotización guardada
// ============================================================
// GET /api/render-pdf.php?nro=0042&sub=3
//
// Emite un HTML server-side rendered con summary completo del presupuesto.
// Usuario hace Ctrl+P o "Guardar como PDF" en el browser.
//
// Esta vista es server-side, NO depende de exports.js de la calc.
// Eso garantiza que renderiza siempre, incluso si la calc cambia.
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

bs_require_auth();

$cliente_nro = str_pad((string)intval($_GET['nro'] ?? ''), 4, '0', STR_PAD_LEFT);
$sub = intval($_GET['sub'] ?? 0);

if (intval($cliente_nro) <= 0) bs_error('nro inválido');
if ($sub <= 0) bs_error('sub inválido');

$cliente_path = BS_CLIENTES_DIR . '/' . $cliente_nro . '.json';
$cliente_obj = bs_read_json($cliente_path);
if (!$cliente_obj) bs_error('Cliente no encontrado', 404);

$cot_data = null;
foreach ($cliente_obj['cotizaciones'] ?? [] as $cot) {
    if (intval($cot['sub']) === $sub) { $cot_data = $cot; break; }
}
if (!$cot_data) bs_error('Sub-versión no encontrada', 404);
if (!isset($cot_data['presupuesto']) || !is_array($cot_data['presupuesto'])) {
    bs_error('Esta cotización no tiene detalle de presupuesto');
}

require_once __DIR__ . '/_render-helpers.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

bs_render_html_summary($cliente_obj, $cot_data, [
    'show_print_button' => true,
    'wrap_html'         => true,
]);
