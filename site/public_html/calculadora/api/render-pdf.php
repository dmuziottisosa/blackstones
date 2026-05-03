<?php
// ============================================================
// render-pdf.php — Genera el PDF de una cotización guardada (on-demand)
// ============================================================
// GET /api/render-pdf.php?nro=0042&sub=3
//
// Estrategia: emite un HTML standalone que contiene los datos JSON +
// el código JS de exports.js, abre en pestaña nueva del browser, dispara
// el render PDF (window.print()) usando exactamente la misma plantilla
// que la calc.
//
// Mismo input/output que la calc — coherencia total.
//
// MVP nota: en Fase 2 se va a extraer generarPDF() de calc.html a
// exports.js compartido. Mientras tanto, este endpoint devuelve el JSON
// y un mensaje de placeholder. Cuando exports.js exista, se completa.
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
    if (intval($cot['sub']) === $sub) {
        $cot_data = $cot;
        break;
    }
}
if (!$cot_data) bs_error('Sub-versión no encontrada', 404);

if (!isset($cot_data['presupuesto']) || !is_array($cot_data['presupuesto'])) {
    bs_error('Esta cotización no tiene detalle de presupuesto');
}

// Emitir HTML que carga exports.js (Fase 2) y dispara render
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$cliente_json   = json_encode($cliente_obj['cliente'] ?? [], JSON_UNESCAPED_UNICODE);
$presupuesto    = json_encode($cot_data['presupuesto'], JSON_UNESCAPED_UNICODE);
$nro_str        = htmlspecialchars($cliente_nro . '-' . $sub);
$concepto       = htmlspecialchars($cot_data['concepto'] ?? '');
$fecha          = htmlspecialchars(substr($cot_data['fecha'] ?? '', 0, 10));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>BlackStones · Presupuesto N° <?= $nro_str ?></title>
<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #FAF8F4; color: #1A1816; }
.placeholder { max-width: 600px; margin: 40px auto; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,.08); }
h1 { font-family: 'Fraunces', serif; }
.note { background: #FDF6E7; border-left: 3px solid #C4A77D; padding: 12px 16px; margin: 16px 0; border-radius: 4px; font-size: 14px; color: #7A6649; }
pre { background: #F2EDE3; padding: 12px; border-radius: 4px; overflow-x: auto; font-size: 11px; }
</style>
</head>
<body>
<div class="placeholder">
  <h1>BlackStones · Presupuesto N° <?= $nro_str ?></h1>
  <p><strong>Concepto:</strong> <?= $concepto ?></p>
  <p><strong>Fecha:</strong> <?= $fecha ?></p>

  <div class="note">
    <strong>Render PDF — Fase 2 pendiente.</strong><br>
    En la Fase 2 de implementación, este endpoint cargará <code>exports.js</code>
    (extraído de <code>calc.html</code>) y disparará <code>generarPDF(data)</code>
    automáticamente. Mientras tanto, los datos están disponibles abajo en JSON
    para el frontend que quiera consumirlos.
  </div>

  <h3>Datos del cliente</h3>
  <pre id="data-cliente"><?= htmlspecialchars($cliente_json) ?></pre>

  <h3>Presupuesto</h3>
  <pre id="data-presupuesto"><?= htmlspecialchars($presupuesto) ?></pre>
</div>

<!-- TODO Fase 2: descomentar
<script src="/calculadora/exports.js"></script>
<script>
  const cliente = <?= $cliente_json ?>;
  const presupuesto = <?= $presupuesto ?>;
  generarPDF({ cliente, presupuesto, nro: '<?= $nro_str ?>', concepto: '<?= $concepto ?>', fecha: '<?= $fecha ?>' });
</script>
-->
</body>
</html>
