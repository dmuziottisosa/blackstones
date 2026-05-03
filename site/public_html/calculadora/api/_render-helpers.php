<?php
// ============================================================
// _render-helpers.php — Renderizadores compartidos
// ============================================================
// Usado por render-pdf.php (preview) y state.php (al generar ZIP del entregado).
// ============================================================

/**
 * Renderiza el HTML completo de un summary de presupuesto.
 *
 * @param array $cliente_obj  El cliente.json completo
 * @param array $cot_data      La cotización (sub-versión) específica
 * @param array $opts          ['show_print_button'=>bool, 'wrap_html'=>bool]
 *                              - wrap_html=true: emite <!DOCTYPE>...</html>
 *                              - wrap_html=false: solo body content (para incluir en otra page)
 */
function bs_render_html_summary($cliente_obj, $cot_data, $opts = []) {
    $show_print  = $opts['show_print_button'] ?? false;
    $wrap_html   = $opts['wrap_html'] ?? true;

    $cli         = $cliente_obj['cliente'] ?? [];
    $cliente_nro = $cliente_obj['cliente_nro'] ?? '';
    $sub         = $cot_data['sub'] ?? 0;
    $presupuesto = $cot_data['presupuesto'] ?? [];
    $estado      = $cot_data['estado'] ?? '';
    $concepto    = $cot_data['concepto'] ?? '';
    $fecha       = substr($cot_data['fecha'] ?? '', 0, 10);
    $totales     = $presupuesto['totales'] ?? [];
    $factura     = !empty($presupuesto['factura']);
    $dolar       = floatval($presupuesto['dolar_venta'] ?? 0);

    $secciones_labels = [
        'm' => 'Mesada de cocina',
        'a' => 'Alzada',
        'l' => 'Mesada en L',
        'i' => 'Isla',
        'b' => 'Mesada de baño',
    ];

    $h = function($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
    $fUSD = function($n) { return 'USD ' . number_format(floatval($n), 2, ',', '.'); };
    $fARS = function($n) { return '$ ' . number_format(floatval($n), 0, ',', '.'); };
    $fM2  = function($n) { return number_format(floatval($n), 2, ',', '.') . ' m²'; };

    if ($wrap_html) {
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">';
        echo '<title>BlackStones · Presupuesto N° ' . $h($cliente_nro . '-' . $sub) . '</title>';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<style>';
        echo bs_render_styles();
        echo '</style></head><body>';
    }

    if ($show_print) {
        echo '<div class="no-print" style="padding:14px;background:#1A1816;color:#FAF8F4;text-align:center;margin-bottom:12px;border-radius:6px">';
        echo '<button onclick="window.print()" style="background:#C4A77D;color:#1A1816;border:none;padding:10px 24px;font-size:14px;font-weight:700;border-radius:6px;cursor:pointer;margin-right:8px">🖨 Imprimir / Guardar PDF</button>';
        echo '<button onclick="window.close()" style="background:transparent;color:#FAF8F4;border:1px solid #C4A77D;padding:10px 20px;font-size:14px;font-weight:600;border-radius:6px;cursor:pointer">Cerrar</button>';
        echo '<div style="margin-top:8px;font-size:10px;color:#A89580">En el diálogo de impresión: Destino → "Guardar como PDF" · Más opciones → activá "Gráficos de fondo"</div>';
        echo '</div>';
    }

    // Header marca
    echo '<div class="bs-header">';
    echo '<div class="bs-brand">BlackStones</div>';
    echo '<div class="bs-brand-sub">M A R M O L E R Í A · Av. Juan Bautista Alberdi 3575 · CABA · +54 9 11 2468-5820 · contacto@blackstones.com.ar · blackstones.com.ar</div>';
    echo '</div>';

    echo '<div class="bs-meta">';
    echo '<span><b>Presupuesto N°</b> ' . $h($cliente_nro . '-' . $sub) . '</span>';
    echo '<span><b>Estado:</b> <span class="estado est-' . $h($estado) . '">' . $h(ucfirst($estado)) . '</span></span>';
    echo '<span>' . $h($fecha) . '</span>';
    echo '</div>';

    // Cliente
    echo '<table class="bs-cliente"><tr>';
    echo '<td><b>CLIENTE:</b> ' . $h($cli['nombre'] ?? '—') . '</td>';
    echo '<td><b>DNI:</b> ' . $h($cli['dni'] ?? '—') . '</td>';
    echo '</tr><tr>';
    echo '<td><b>DIRECCIÓN:</b> ' . $h($cli['direccion'] ?? '—') . '</td>';
    echo '<td><b>CELULAR:</b> ' . $h($cli['celular'] ?? '—') . '</td>';
    echo '</tr>';
    if (!empty($concepto)) {
        echo '<tr><td colspan="2"><b>CONCEPTO:</b> ' . $h($concepto) . '</td></tr>';
    }
    echo '</table>';

    // Tabla de items
    echo '<table class="bs-items"><thead><tr>';
    echo '<th>CANT</th><th>DETALLE</th><th>L (m)</th><th>A (m)</th><th>USD</th><th>ARS</th>';
    echo '</tr></thead><tbody>';

    $sumU = 0; $sumA = 0;

    // Items por sección
    foreach (['m', 'a', 'l', 'i', 'b'] as $secKey) {
        $secObj = $presupuesto['secciones'][$secKey] ?? null;
        if (!$secObj || empty($secObj['items'])) continue;
        $secLabel = $secciones_labels[$secKey];

        echo '<tr class="bs-section-header"><td colspan="6">▪ ' . $h($secLabel) . '</td></tr>';

        foreach ($secObj['items'] as $item) {
            if (empty($item['d1']) || empty($item['price'])) continue;
            $color = $item['color'] ?? '';
            $mat = $item['mat'] ?? '';
            $d1 = $item['d1'] ?? 0;
            $d2 = $item['d2'] ?? 0;
            $price = floatval($item['price'] ?? 0);
            $mon = $item['mon'] ?? 'USD';
            $tipo = $item['tipo'] ?? '';

            // Calcular m² aprox (no replico todo el calcM2 — para summary alcanza este aprox)
            $m2 = floatval($d1) * floatval($d2);
            // Si tiene regrueso, sumamos un margen (no exacto pero razonable para summary)
            $rv = floatval($item['rv'] ?? 0) / 100;
            $reg = $item['reg'] ?? 'Sin';
            if (in_array($reg, ['L', 'L+A', 'Solo frente', 'Frente + 1 lat', 'Frente + 2 lat'])) {
                $m2 += $d1 * $rv;
            }
            if (in_array($reg, ['A', 'L+A', 'Frente + 1 lat', 'Frente + 2 lat'])) {
                $m2 += $d2 * $rv;
            }
            $total = $m2 * $price;
            $usd = $mon === 'USD' ? $total : 0;
            $ars = $mon === 'ARS' ? $total : 0;
            $sumU += $usd; $sumA += $ars;

            $detail = $h($secLabel);
            if ($color) $detail .= ' · <b>' . $h($color) . '</b>';
            if ($mat) $detail .= ' <i style="color:#7A6649">(' . $h($mat) . ')</i>';
            if ($secKey === 'b' && $tipo) $detail = $h($secLabel) . ' · ' . $h($tipo) . ($color ? ' · <b>' . $h($color) . '</b>' : '');

            echo '<tr>';
            echo '<td class="num">1</td>';
            echo '<td>' . $detail . '</td>';
            echo '<td class="num">' . $h(number_format($d1, 2, ',', '')) . '</td>';
            echo '<td class="num">' . $h(number_format($d2, 2, ',', '')) . '<br><span class="m2">' . $h(number_format($m2, 2, ',', '')) . ' m²</span></td>';
            echo '<td class="num">' . ($usd > 0 ? $fUSD($usd) : '—') . '</td>';
            echo '<td class="num">' . ($ars > 0 ? $fARS($ars) : '—') . '</td>';
            echo '</tr>';
        }
    }

    // Adicionales: zócalos
    $zocalos = $presupuesto['zocalos']['items'] ?? [];
    if (!empty($zocalos)) {
        echo '<tr class="bs-section-header"><td colspan="6">▪ Zócalos > 5 cm</td></tr>';
        foreach ($zocalos as $z) {
            if (empty($z['price'])) continue;
            $m2 = floatval($z['d1'] ?? 0) * floatval($z['d2'] ?? 0);
            $price = floatval($z['price']);
            $mon = $z['mon'] ?? 'USD';
            $total = $m2 * $price;
            $usd = $mon === 'USD' ? $total : 0;
            $ars = $mon === 'ARS' ? $total : 0;
            $sumU += $usd; $sumA += $ars;
            $detail = 'Zócalo';
            if (!empty($z['color'])) $detail .= ' · <b>' . $h($z['color']) . '</b>';
            if (!empty($z['mat']))   $detail .= ' <i style="color:#7A6649">(' . $h($z['mat']) . ')</i>';
            echo '<tr>';
            echo '<td class="num">1</td>';
            echo '<td>' . $detail . '</td>';
            echo '<td class="num">' . $h(number_format($z['d1'] ?? 0, 2, ',', '')) . '</td>';
            echo '<td class="num">' . $h(number_format($z['d2'] ?? 0, 2, ',', '')) . '<br><span class="m2">' . $h(number_format($m2, 2, ',', '')) . ' m²</span></td>';
            echo '<td class="num">' . ($usd > 0 ? $fUSD($usd) : '—') . '</td>';
            echo '<td class="num">' . ($ars > 0 ? $fARS($ars) : '—') . '</td>';
            echo '</tr>';
        }
    }

    // Adicionales: extras
    $extras = $presupuesto['extras'] ?? [];
    if (!empty($extras)) {
        echo '<tr class="bs-section-header"><td colspan="6">▪ Otros conceptos</td></tr>';
        foreach ($extras as $e) {
            if (empty($e['amount']) || empty($e['name'])) continue;
            $amount = floatval($e['amount']);
            $mon = $e['mon'] ?? 'USD';
            $usd = $mon === 'USD' ? $amount : 0;
            $ars = $mon === 'ARS' ? $amount : 0;
            $sumU += $usd; $sumA += $ars;
            echo '<tr>';
            echo '<td class="num">1</td>';
            echo '<td colspan="3">' . $h($e['name']) . '</td>';
            echo '<td class="num">' . ($usd > 0 ? $fUSD($usd) : '—') . '</td>';
            echo '<td class="num">' . ($ars > 0 ? $fARS($ars) : '—') . '</td>';
            echo '</tr>';
        }
    }

    // Adicionales: bachas
    $bachas = $presupuesto['bachas'] ?? [];
    if (!empty($bachas)) {
        echo '<tr class="bs-section-header"><td colspan="6">▪ Bachas y accesorios</td></tr>';
        foreach ($bachas as $b) {
            $price = floatval($b['price'] ?? 0);
            $qty = intval($b['qty'] ?? 1);
            $total = $price * $qty;
            $sumA += $total;
            $detail = '<b>' . $h($b['code'] ?? '') . '</b> · ' . $h($b['desc'] ?? '');
            if (!empty($b['cat'])) $detail .= ' <i style="color:#7A6649">(' . $h($b['cat']) . ')</i>';
            echo '<tr>';
            echo '<td class="num">' . $qty . '</td>';
            echo '<td colspan="3">' . $detail . '</td>';
            echo '<td class="num">—</td>';
            echo '<td class="num">' . $fARS($total) . '</td>';
            echo '</tr>';
        }
    }

    // Adicionales: servicios (flete, escalera, ángulos)
    $adic = $presupuesto['adicionales'] ?? [];
    $hasServ = false;
    if (!empty($adic['flete']['monto']) || !empty($adic['escalera']['total']) || !empty($adic['angulos']['total'])) {
        $hasServ = true;
    }
    if ($hasServ) {
        echo '<tr class="bs-section-header"><td colspan="6">▪ Servicios adicionales</td></tr>';
        if (!empty($adic['flete']['monto'])) {
            $m = floatval($adic['flete']['monto']);
            $sumA += $m;
            echo '<tr><td class="num">1</td><td colspan="3">Flete y colocación: ' . $h($adic['flete']['zona'] ?? '') . '</td><td class="num">—</td><td class="num">' . $fARS($m) . '</td></tr>';
        }
        if (!empty($adic['escalera']['total'])) {
            $m = floatval($adic['escalera']['total']);
            $mon = $adic['escalera']['mon'] ?? 'USD';
            if ($mon === 'USD') $sumU += $m; else $sumA += $m;
            $cant = intval($adic['escalera']['cant'] ?? 0);
            echo '<tr><td class="num">' . $cant . '</td><td colspan="3">Subidas a escalera</td>';
            echo '<td class="num">' . ($mon === 'USD' ? $fUSD($m) : '—') . '</td>';
            echo '<td class="num">' . ($mon === 'ARS' ? $fARS($m) : '—') . '</td></tr>';
        }
        if (!empty($adic['angulos']['total'])) {
            $m = floatval($adic['angulos']['total']);
            $mon = $adic['angulos']['mon'] ?? 'USD';
            if ($mon === 'USD') $sumU += $m; else $sumA += $m;
            $cant = intval($adic['angulos']['cant'] ?? 0);
            echo '<tr><td class="num">' . $cant . '</td><td colspan="3">Ángulos / Ménsulas</td>';
            echo '<td class="num">' . ($mon === 'USD' ? $fUSD($m) : '—') . '</td>';
            echo '<td class="num">' . ($mon === 'ARS' ? $fARS($m) : '—') . '</td></tr>';
        }
    }

    // Totales (preferimos usar los del presupuesto si existen, sino los calculados)
    $tUSD = isset($totales['usd']) ? floatval($totales['usd']) : $sumU;
    $tARS = isset($totales['ars']) ? floatval($totales['ars']) : $sumA;

    echo '<tr class="bs-totales"><td colspan="4"><b>SUBTOTAL</b></td>';
    echo '<td class="num"><b>' . ($tUSD > 0 ? $fUSD($tUSD) : '—') . '</b></td>';
    echo '<td class="num"><b>' . ($tARS > 0 ? $fARS($tARS) : '—') . '</b></td></tr>';

    if ($factura) {
        $ivaU = $tUSD * 0.21;
        $ivaA = $tARS * 0.21;
        echo '<tr><td colspan="4">I.V.A. (21%)</td>';
        echo '<td class="num">' . ($ivaU > 0 ? $fUSD($ivaU) : '—') . '</td>';
        echo '<td class="num">' . ($ivaA > 0 ? $fARS($ivaA) : '—') . '</td></tr>';
        $tUSD += $ivaU; $tARS += $ivaA;
    }

    echo '<tr class="bs-total-final"><td colspan="4"><b>TOTAL</b></td>';
    echo '<td class="num bs-total-usd"><b>' . ($tUSD > 0 ? $fUSD($tUSD) : '—') . '</b></td>';
    echo '<td class="num bs-total-ars"><b>' . ($tARS > 0 ? $fARS($tARS) : '—') . '</b></td></tr>';

    echo '</tbody></table>';

    // Tipo de cambio y disclaimer
    echo '<div class="bs-tipocambio"><b>Tipo de cambio referencia:</b> ' . ($dolar > 0 ? 'USD 1 = ' . $fARS($dolar) . ' (Dólar Oficial Venta · DolarHoy)' : 'consultar al momento del pago') . '</div>';

    echo '<div class="bs-disclaimer">';
    echo '<p><b>Vista resumida del presupuesto guardado.</b> Para reproducir el formato editorial completo (Excel + PDF con estilo de marca), abrí la calculadora y cotizá fresh.</p>';
    echo '</div>';

    // Footer
    echo '<div class="bs-footer">BlackStones Marmolería · Cel: 11 2468-5820 · Of: 5611-5919 · contacto@blackstones.com.ar · Av. J.B. Alberdi 3575, CABA · blackstones.com.ar</div>';

    if ($wrap_html) {
        echo '</body></html>';
    }
}

function bs_render_styles() {
    return <<<CSS
@page { size: A4 portrait; margin: 10mm; }
* { margin: 0; padding: 0; box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
body { font-family: 'Manrope', Arial, sans-serif; color: #1A1816; font-size: 12px; background: #fff; padding: 16px; }
.bs-header { background: #1A1816; padding: 16px 20px; color: #FAF8F4; }
.bs-brand { font-size: 22px; font-weight: 700; font-family: 'Fraunces', serif; }
.bs-brand-sub { font-size: 9px; color: #C4A77D; margin-top: 3px; letter-spacing: .03em; }
.bs-header + div { height: 3px; background: #C4A77D; }
.bs-meta { background: #FAF8F4; padding: 8px 14px; font-size: 11px; display: flex; justify-content: space-between; border-bottom: 1px solid #E5DECF; }
.estado { padding: 2px 8px; border-radius: 3px; font-weight: 600; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; }
.est-borrador { background: #F2EDE3; color: #7A6649; }
.est-enviado { background: #E5F0F8; color: #0EA5E9; }
.est-aprobado { background: #E5F8E5; color: #25A036; }
.est-entregado { background: #1A1816; color: #C4A77D; }
.est-perdido { background: #FDF1F1; color: #C44747; }
.bs-cliente { width: 100%; border-collapse: collapse; margin: 10px 0 12px; }
.bs-cliente td { padding: 5px 12px; font-size: 11px; }
.bs-items { width: 100%; border-collapse: collapse; }
.bs-items th { background: #1A1816; color: #FAF8F4; text-align: center; font-size: 10px; padding: 6px; }
.bs-items th:nth-child(2) { text-align: left; padding-left: 12px; }
.bs-items td { padding: 8px; border-bottom: 1px solid #F0ECE6; font-size: 11px; vertical-align: top; }
.bs-items td.num { text-align: center; }
.bs-items .m2 { font-size: 9px; color: #A89580; display: block; margin-top: 2px; }
.bs-section-header td { background: #F2EDE3; font-weight: 700; padding: 6px 10px; }
.bs-totales td { background: #F2EDE3; font-weight: 700; padding: 8px 12px; }
.bs-total-final td { background: #1A1816; color: #FAF8F4; font-weight: 700; padding: 10px 12px; font-size: 13px; }
.bs-total-final .bs-total-usd { color: #25D366; }
.bs-total-final .bs-total-ars { color: #0EA5E9; }
.bs-tipocambio { margin: 12px 0; padding: 8px; background: #FAF8F4; border-radius: 4px; font-size: 10px; }
.bs-disclaimer { margin: 16px 0; padding: 10px 14px; background: #FDF6E7; border-left: 3px solid #C4A77D; font-size: 10px; color: #7A6649; }
.bs-footer { text-align: center; font-size: 8px; color: #6B6560; margin-top: 12px; padding-top: 8px; border-top: 1px solid #F2EDE3; }
@media print {
  .no-print { display: none; }
  body { padding: 0; }
  table { break-inside: auto; }
  tr { break-inside: avoid; page-break-inside: avoid; }
}
CSS;
}
