<?php
// ============================================================
// render-resumen.php — Resumen en lote de varias cotizaciones
// ============================================================
// GET /api/render-resumen.php?ids=0042-3,0050-1,0048-1
//
// Emite un HTML imprimible (Ctrl+P -> Guardar PDF) con una tabla
// resumen: N°, cliente, concepto, estado, origen, monto USD/ARS, fecha.
// Mas un total general. Pensado para que el equipo arme un reporte
// rapido de un set de presupuestos seleccionados desde el hub.
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

bs_require_auth();

$ids_raw = $_GET['ids'] ?? '';
if ($ids_raw === '') bs_error('Faltan ids');

// Parse "0042-3,0050-1" -> [['nro'=>'0042','sub'=>3], ...]
$pairs = [];
foreach (explode(',', $ids_raw) as $tok) {
    $tok = trim($tok);
    if (!preg_match('/^(\d{4,})-(\d+)$/', $tok, $m)) continue;
    $pairs[] = ['nro' => str_pad($m[1], 4, '0', STR_PAD_LEFT), 'sub' => intval($m[2])];
}
if (!$pairs) bs_error('Ningún id válido');
if (count($pairs) > 200) bs_error('Demasiados items (max 200)');

$ESTADOS = [
    'borrador' => 'Borrador', 'enviado' => 'Enviado', 'aprobado' => 'Aprobado',
    'entregado' => 'Entregado', 'perdido' => 'Perdido',
];

$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
$fUSD = function ($n) { return $n > 0 ? 'USD ' . number_format($n, 2, ',', '.') : '—'; };
$fARS = function ($n) { return $n > 0 ? '$ ' . number_format($n, 0, ',', '.') : '—'; };
$fFecha = function ($s) {
    if (!$s) return '—';
    $t = strtotime($s);
    return $t ? date('d/m/Y', $t) : '—';
};

$rows = [];
$tot_usd = 0; $tot_ars = 0;
$cache = []; // nro -> cliente_obj

foreach ($pairs as $p) {
    $nro = $p['nro'];
    if (!isset($cache[$nro])) {
        $cache[$nro] = bs_read_json(BS_CLIENTES_DIR . '/' . $nro . '.json') ?: false;
    }
    $obj = $cache[$nro];
    if (!$obj) continue;
    $cli = $obj['cliente'] ?? [];
    foreach ($obj['cotizaciones'] ?? [] as $cot) {
        if (intval($cot['sub']) !== $p['sub']) continue;
        $t = $cot['presupuesto']['totales'] ?? [];
        $usd = floatval($t['usd'] ?? 0);
        $ars = floatval($t['ars'] ?? 0);
        $tot_usd += $usd; $tot_ars += $ars;
        $origen = ($cot['origen'] ?? 'local') === 'publicidad' ? 'Publicidad' : 'Local';
        $rows[] = [
            'id'       => $nro . '-' . $cot['sub'],
            'cliente'  => $cli['nombre'] ?? '—',
            'celular'  => $cli['celular'] ?? '',
            'concepto' => $cot['concepto'] ?? '',
            'estado'   => $ESTADOS[$cot['estado'] ?? ''] ?? ($cot['estado'] ?? ''),
            'origen'   => $origen,
            'usd'      => $usd,
            'ars'      => $ars,
            'fecha'    => $cot['fecha'] ?? '',
        ];
        break;
    }
}

if (!$rows) bs_error('No se encontraron cotizaciones para los ids dados', 404);

$fecha_hoy = date('d/m/Y H:i');
$n_items = count($rows);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Resumen de presupuestos · BlackStones</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:'Helvetica Neue',Arial,sans-serif;color:#1A1816;background:#fff;padding:32px;font-size:13px;line-height:1.5}
  .head{background:#16130F;color:#FAF8F4;padding:22px 26px;border-radius:8px;border-bottom:3px solid #C4A77D;margin-bottom:6px}
  .head h1{font-size:24px;font-weight:700;letter-spacing:.02em}
  .head .meta{color:#C4A77D;font-size:11px;letter-spacing:.06em;text-transform:uppercase;margin-top:4px}
  .subhead{display:flex;justify-content:space-between;align-items:baseline;padding:10px 4px 16px;color:#7A6649;font-size:12px}
  table{width:100%;border-collapse:collapse;font-size:12px}
  th{background:#F2EDE3;color:#7A6649;text-align:left;padding:9px 10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid #C4A77D}
  th.num,td.num{text-align:right;font-variant-numeric:tabular-nums}
  td{padding:9px 10px;border-bottom:1px solid #EDE7DD;vertical-align:top}
  tbody tr:nth-child(even){background:#FBF9F5}
  .id{font-weight:700;font-variant-numeric:tabular-nums;white-space:nowrap}
  .muted{color:#A89580}
  .badge{display:inline-block;font-size:10px;font-weight:600;padding:2px 8px;border-radius:10px;background:#EDE7DD;color:#7A6649}
  .badge.pub{background:rgba(196,167,125,.18);color:#8A6D3B}
  tfoot td{padding:12px 10px;font-weight:700;border-top:2px solid #16130F;font-size:13px;background:#F2EDE3}
  tfoot .lbl{text-transform:uppercase;letter-spacing:.05em;font-size:11px;color:#7A6649}
  .print-btn{position:fixed;top:20px;right:20px;background:#16130F;color:#FAF8F4;border:0;padding:11px 20px;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;box-shadow:0 4px 14px rgba(0,0,0,.2)}
  .print-btn:hover{background:#2A2522}
  @media print{
    body{padding:0}
    .print-btn{display:none}
    .head{border-radius:0}
  }
</style>
</head>
<body>
  <button class="print-btn" onclick="window.print()">Imprimir / Guardar PDF</button>

  <div class="head">
    <h1>BlackStones</h1>
    <div class="meta">Resumen de presupuestos</div>
  </div>
  <div class="subhead">
    <span><b><?= $n_items ?></b> presupuesto<?= $n_items === 1 ? '' : 's' ?></span>
    <span>Generado <?= $h($fecha_hoy) ?></span>
  </div>

  <table>
    <thead>
      <tr>
        <th>N°</th>
        <th>Cliente</th>
        <th>Concepto</th>
        <th>Estado</th>
        <th>Origen</th>
        <th class="num">USD</th>
        <th class="num">ARS</th>
        <th>Fecha</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td class="id"><?= $h($r['id']) ?></td>
        <td>
          <?= $h($r['cliente']) ?>
          <?php if ($r['celular']): ?><br><span class="muted" style="font-size:11px"><?= $h($r['celular']) ?></span><?php endif; ?>
        </td>
        <td><?= $r['concepto'] ? $h($r['concepto']) : '<span class="muted">—</span>' ?></td>
        <td><span class="badge"><?= $h($r['estado']) ?></span></td>
        <td><span class="badge<?= $r['origen'] === 'Publicidad' ? ' pub' : '' ?>"><?= $h($r['origen']) ?></span></td>
        <td class="num"><?= $r['usd'] > 0 ? $h($fUSD($r['usd'])) : '<span class="muted">—</span>' ?></td>
        <td class="num"><?= $r['ars'] > 0 ? $h($fARS($r['ars'])) : '<span class="muted">—</span>' ?></td>
        <td class="muted" style="white-space:nowrap"><?= $h($fFecha($r['fecha'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="5" class="lbl">Total general (<?= $n_items ?> ítem<?= $n_items === 1 ? '' : 's' ?>)</td>
        <td class="num"><?= $tot_usd > 0 ? $h($fUSD($tot_usd)) : '—' ?></td>
        <td class="num"><?= $tot_ars > 0 ? $h($fARS($tot_ars)) : '—' ?></td>
        <td></td>
      </tr>
    </tfoot>
  </table>
</body>
</html>
