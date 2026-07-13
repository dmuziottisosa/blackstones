<?php
// ============================================================
// _render-helpers.php — Renderizadores compartidos
// ============================================================
// Usado por render-pdf.php (preview editorial) y state.php (ZIP del entregado).
// Reproduce el output de generarPDF() de la calc 1:1 (formato editorial).
// ============================================================

// --------------------------------------------------------
// Constantes que matchean calc.html
// --------------------------------------------------------
function bs_mat_labels() {
    return [
        'Guidoni'    => 'Guidoni',
        'Marmol'     => 'Mármol',
        'Granito_n'  => 'Granito Nacional',
        'Granito_i'  => 'Granito Importado',
        'Xtone'      => 'Xtone',
        'Pura'       => 'Purastone',
        'Prima'      => 'Pura Prima',
        'Dekton'     => 'Dekton',
        'Silestone'  => 'Silestone',
        'Neolith'    => 'Neolith',
        'Suprastone' => 'Suprastone',
    ];
}
function bs_mat_label($m) {
    $L = bs_mat_labels();
    return $L[$m] ?? ($m ?? '');
}
function bs_is_sint($m) {
    return in_array($m, ['Xtone', 'Prima', 'Neolith', 'Dekton', 'Suprastone'], true);
}
// Fecha YYYY-MM-DD → DD/MM/AAAA (referencias de tipo de cambio en el render del hub)
function bs_fmt_fecha_dmy($s) {
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', (string)$s, $m)) return $m[3] . '/' . $m[2] . '/' . $m[1];
    return (string)$s;
}

// --------------------------------------------------------
// Lógica de cálculo portada de calc.js
// --------------------------------------------------------
function bs_get_r($it) {
    $v = floatval($it['rv'] ?? 0) / 100;
    $r = $it['reg'] ?? (bs_is_sint($it['mat'] ?? '') ? 'L+A' : 'Sin');
    if ($r === 'L')   return [0, $v];
    if ($r === 'A')   return [$v, 0];
    if ($r === 'L+A') return [$v, $v];
    return [0, 0];
}
function bs_calc_m2($s, $it, $pzL = '1') {
    $d1 = floatval($it['d1'] ?? 0);
    $d2 = floatval($it['d2'] ?? 0);
    $d3 = floatval($it['d3'] ?? 0);
    $d4 = floatval($it['d4'] ?? 0);
    list($aL, $aA) = bs_get_r($it);

    if ($s === 'm') { if (!$d1 && !$d2) return 0; return ($d1 + $aL) * ($d2 + $aA); }
    if ($s === 'a') { if (!$d1 && !$d2) return 0; return $d1 * $d2; }
    if ($s === 'l') {
        if ($pzL === '1') { if (!$d1 && !$d3) return 0; return ($d1 + $aL) * ($d3 + $aL); }
        if (!$d1 && !$d3) return 0;
        return ($d1 + $aL) * ($d2 + $aA) + ($d3 + $aL) * ($d4 + $aA);
    }
    if ($s === 'i') {
        if (!$d1 && !$d2) return 0;
        $regActive = bs_is_sint($it['mat'] ?? '') || (($it['reg'] ?? '') === 'Sí');
        $regCm = floatval($it['rv'] ?? 4); if ($regCm <= 0) $regCm = 4;
        $reg = $regActive ? ($regCm / 100) : 0;
        $tapa = ($d1 + $reg * 2) * ($d2 + $reg * 2);
        $alto = floatval($it['altoLat'] ?? 0); if ($alto <= 0) $alto = 0.90;
        $aLat = $d4;
        $nLat = isset($it['nLat']) && is_numeric($it['nLat']) ? intval($it['nLat']) : 1;
        if ($regActive) $alto = $alto + $reg * 2;
        $latExt = $alto * $aLat * $nLat;
        $latInt = (($it['caraInt'] ?? 'No') === 'Sí') ? $alto * $aLat * $nLat : 0;
        return $tapa + $latExt + $latInt;
    }
    if ($s === 'b') {
        if (!$d1) return 0;
        $tipo = $it['tipo'] ?? 'Bacha armada';
        $reg = floatval($it['rv'] ?? 0) / 100;
        $bL = 0; $bA = 0;
        if (($it['reg'] ?? '') === 'Solo frente')      { $bL = $reg; }
        elseif (($it['reg'] ?? '') === 'Frente + 1 lat') { $bL = $reg; $bA = $reg; }
        elseif (($it['reg'] ?? '') === 'Frente + 2 lat') { $bL = $reg; $bA = $reg * 2; }
        $tapa = ($d1 + $bL) * ($d2 + $bA);
        if ($tipo !== 'Bacha armada') return $tapa;
        return $tapa + ($d3 * $d4) + 0.6;
    }
    return 0;
}
function bs_ag_cost($it, $s) {
    // Baño: solo "Con Traforo" cobra agujeros = N bachas (traforos), sin anafe
    if ($s === 'b') {
        if (($it['tipo'] ?? 'Bacha armada') !== 'Con Traforo') return 0;
        $n = max(1, intval($it['nBachas'] ?? 1));
        return (($it['mon'] ?? 'USD') === 'USD' ? 80 : 100000) * $n;
    }
    $ag = $it['ag'] ?? 'Sin';
    if (!$ag || $ag === 'Sin') return 0;
    if ($s === 'a') {
        $p = floatval($it['agPrice'] ?? 0);
        return $ag === 'Ambos' ? $p * 2 : $p;
    }
    $isUSD = ($it['mon'] ?? 'USD') === 'USD';
    $perHole = $isUSD ? 80 : 100000;
    return $ag === 'Ambos' ? $perHole * 2 : $perHole;
}
function bs_ag_label($it, $s) {
    // Baño Con Traforo: N bachas (traforos), sin anafe
    if ($s === 'b') {
        if (($it['tipo'] ?? 'Bacha armada') !== 'Con Traforo') return '';
        $n = max(1, intval($it['nBachas'] ?? 1));
        $cu = ($it['mon'] ?? 'USD') === 'USD' ? 'USD 80 c/u' : '$100.000 c/u';
        return $n . ' bacha' . ($n > 1 ? 's' : '') . ' · traforo (' . $cu . ')';
    }
    $ag = $it['ag'] ?? 'Sin';
    if (!$ag || $ag === 'Sin') return '';
    if ($s === 'a') {
        $p = floatval($it['agPrice'] ?? 0);
        $priceTxt = $p > 0 ? (($it['mon'] ?? 'USD') === 'USD' ? ' (USD ' . number_format($p, 2, ',', '.') . ' c/u)' : ' ($ ' . number_format($p, 0, ',', '.') . ' c/u)') : '';
        return $ag === 'Ambos' ? "Traforo gas + tomas{$priceTxt}" : "{$ag}{$priceTxt}";
    }
    $isUSD = ($it['mon'] ?? 'USD') === 'USD';
    if ($ag === 'Bacha') return $isUSD ? 'Ag. bacha USD 80' : 'Ag. bacha $100.000';
    if ($ag === 'Anafe') return $isUSD ? 'Ag. anafe USD 80' : 'Ag. anafe $100.000';
    return $isUSD ? 'Ag. bacha+anafe USD 160' : 'Ag. bacha+anafe $200.000';
}

// --------------------------------------------------------
// Render principal
// --------------------------------------------------------
function bs_render_html_summary($cliente_obj, $cot_data, $opts = []) {
    $show_print = $opts['show_print_button'] ?? false;
    $wrap_html  = $opts['wrap_html']         ?? true;

    $cli         = $cliente_obj['cliente'] ?? [];
    $cliente_nro = $cliente_obj['cliente_nro'] ?? '';
    $sub         = $cot_data['sub'] ?? 0;
    $presup      = $cot_data['presupuesto'] ?? [];
    $estado      = $cot_data['estado'] ?? '';
    $fecha       = substr($cot_data['fecha'] ?? '', 0, 10);

    $factura     = !empty($presup['factura']);
    $dolar       = floatval($presup['dolar_venta'] ?? 0);
    $opts_export = $presup['exports_options'] ?? null;
    $incTotal    = $opts_export['incTotal']    ?? true;
    $incSena     = $opts_export['incSena']     ?? false;
    $incSaldo    = $opts_export['incSaldo']    ?? false;
    $incTotalARS = $opts_export['incTotalARS'] ?? false;

    $secciones_labels = [
        'm' => 'Mesada de cocina',
        'a' => 'Alzada',
        'l' => 'Mesada en L',
        'i' => 'Isla',
        'b' => 'Mesada de baño',
    ];

    $h    = function($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
    $fUSD = function($n) { return 'USD ' . number_format(floatval($n), 2, ',', '.'); };
    $fARS = function($n) { return '$ '   . number_format(floatval($n), 0, ',', '.'); };

    if ($wrap_html) {
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">';
        echo '<title>BlackStones · Presupuesto N° ' . $h($cliente_nro . '-' . $sub) . '</title>';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<style>' . bs_render_styles() . '</style>';
        echo '</head><body>';
    }

    if ($show_print) {
        echo '<div class="no-print">';
        echo '<button onclick="window.print()">🖨 Imprimir / Guardar PDF</button>';
        echo '<button onclick="window.close()" style="background:transparent;color:#FAF8F4;border:1px solid #C4A77D">Cerrar</button>';
        echo '<div style="margin-top:8px;font-size:10px;color:#A89580">En el diálogo de impresión: Destino → "Guardar como PDF" · Más opciones → activá "Gráficos de fondo" para mantener los colores</div>';
        echo '</div>';
    }

    // Header marca
    echo '<div style="background:#1A1816;padding:16px 20px">';
    echo '<div style="font-size:20px;font-weight:700;color:#FAF8F4">BlackStones</div>';
    echo '<div style="font-size:9px;color:#C4A77D;margin-top:2px">M A R M O L E R Í A  ·  Av. Juan Bautista Alberdi 3575 · CABA  ·  +54 9 11 2468-5820  ·  contacto@blackstones.com.ar  ·  blackstones.com.ar</div>';
    echo '</div>';
    echo '<div style="height:3px;background:#C4A77D"></div>';

    // Meta
    echo '<div style="background:#FAF8F4;padding:8px 12px;font-size:11px;display:flex;justify-content:space-between;align-items:center">';
    echo '<b>Presupuesto N° ' . $h($cliente_nro . '-' . $sub) . '</b>';
    echo '<span><b>Estado:</b> <span class="estado est-' . $h($estado) . '">' . $h(strtoupper($estado)) . '</span></span>';
    echo '<span>' . $h($fecha) . '</span>';
    echo '</div>';

    // Cliente
    echo '<table style="margin:8px 0">';
    echo '<tr><td><b>CLIENTE:</b> ' . $h(strtoupper($cli['nombre'] ?? '—')) . '</td>';
    echo '<td><b>DNI/CUIT:</b> ' . $h($cli['dni'] ?? '—') . '</td></tr>';
    echo '<tr><td><b>DIRECCIÓN:</b> ' . $h(strtoupper($cli['direccion'] ?? '—')) . '</td>';
    echo '<td><b>CELULAR:</b> ' . $h($cli['celular'] ?? '—') . '</td></tr>';
    echo '</table>';

    // Tabla items
    echo '<table><thead><tr style="background:#1A1816;color:#FAF8F4">';
    echo '<th style="text-align:center;font-size:10px;width:6%">CANT</th>';
    echo '<th style="text-align:left;font-size:10px;width:48%;padding-left:10px">DETALLE</th>';
    echo '<th style="text-align:center;font-size:10px;width:11%">L (m)</th>';
    echo '<th style="text-align:center;font-size:10px;width:13%">A (m)</th>';
    echo '<th style="text-align:center;font-size:10px;width:11%">USD</th>';
    echo '<th style="text-align:center;font-size:10px;width:11%">ARS</th>';
    echo '</tr></thead><tbody>';

    $sumU = 0; $sumA = 0;
    $pzL = '1'; // default; podríamos persistirlo en el JSON pero por ahora asumimos 1 pieza

    // Items por sección
    $td = function($content, $align = 'center', $extra = '') {
        $a = $align === 'left' ? 'left' : 'center';
        return '<td style="text-align:' . $a . ';vertical-align:top;padding:9px 6px' . ($extra ? ';' . $extra : '') . '">' . $content . '</td>';
    };

    foreach (['m', 'b'] as $secKey) {
        $secObj = $presup['secciones'][$secKey] ?? null;
        if (!$secObj || empty($secObj['items'])) continue;

        // filtrar items válidos — misma regla que la calc (_itemHasTotal):
        // material + precio + m² > 0 ⇒ el item aporta al total y se muestra.
        // (Antes se exigían todas las medidas y un baño sin bacha interior
        // sumaba al total pero desaparecía del desglose.)
        $valid = [];
        foreach ($secObj['items'] as $it) {
            if (empty($it['mat']) || empty($it['price'])) continue;
            if (bs_calc_m2($secKey, $it, $pzL) <= 0) continue;
            $valid[] = $it;
        }
        if (empty($valid)) continue;

        $secLabel = $secciones_labels[$secKey];
        echo '<tr style="background:#F2EDE3"><td style="text-align:center;color:#7A6649">●</td>';
        echo '<td colspan="5" style="font-weight:700;padding:6px 10px">' . $h($secLabel) . '</td></tr>';

        $secU = 0; $secA = 0;
        foreach ($valid as $it) {
            $cant = max(1, intval($it['cant'] ?? 1));
            $m2u = bs_calc_m2($secKey, $it, $pzL);   // m² de una pieza
            $price = floatval($it['price']);
            $tipoB = $it['tipo'] ?? 'Bacha armada';
            $supportsAg = ($secKey === 'm' || $secKey === 'a' || $secKey === 'l' || $secKey === 'i' || ($secKey === 'b' && $tipoB === 'Con Traforo'));
            $agU = $supportsAg ? bs_ag_cost($it, $secKey) : 0;
            // Valores de línea: (unidad) × cantidad
            $m2 = $m2u * $cant;
            $rowTotal = ($m2u * $price + $agU) * $cant;

            $matName = bs_mat_label($it['mat'] ?? '');
            $colorTxt = $it['color'] ?? '';

            // Línea principal
            $colorPart = $colorTxt ? ' · <strong style="color:#1A1816">' . $h($colorTxt) . '</strong>' : '';
            $matPart = $matName ? ' <span style="color:#7A6649;font-style:italic;font-size:10.5px">(' . $h($matName) . ')</span>' : '';
            $secLabelDisplay = $secLabel;
            if ($secKey === 'b' && !empty($it['tipo'])) {
                $secLabelDisplay = $secLabel . ' · ' . $it['tipo'];
            }
            $mainLine = $h($secLabelDisplay) . $colorPart . $matPart;

            // Sub-línea: regrueso + agujeros
            $subBits = [];
            $regL = $it['reg'] ?? '';
            $rv = floatval($it['rv'] ?? 0);
            if ($regL && $regL !== 'Sin' && $regL !== 'No' && $rv > 0) {
                $subBits[] = "Regrueso {$regL} · " . $rv . ' cm';
            }
            $agL = bs_ag_label($it, $secKey);
            if ($agL) $subBits[] = $agL;
            $subLineHtml = !empty($subBits)
                ? '<div style="font-size:9px;color:#7A6649;margin-top:3px;font-style:italic">' . $h(implode(' · ', $subBits)) . '</div>'
                : '';

            // Dimensiones a mostrar
            $dimL = $it['d1'] ?? '';
            $dimA = $it['d2'] ?? '';
            if ($secKey === 'l') {
                if ($pzL === '1') { $dimA = $it['d3'] ?? ($it['d2'] ?? ''); }
                else { $dimL = $it['d1'] ?? ''; $dimA = $it['d3'] ?? ''; }
            }
            $m2Cell = '<div>' . $h($dimA) . '</div><div style="font-size:9px;color:#A89580;margin-top:2px;letter-spacing:.02em">' . number_format($m2, 2, ',', '.') . ' m²</div>';

            $mon = $it['mon'] ?? 'USD';
            $usd = $mon === 'USD' ? $rowTotal : 0;
            $ars = $mon === 'ARS' ? $rowTotal : 0;
            $usdCell = $usd > 0 ? '<span style="color:#1A1816;font-weight:700">' . $fUSD($usd) . '</span>' : '<span style="color:#A89580">—</span>';
            $arsCell = $ars > 0 ? '<span style="color:#1A1816;font-weight:700">' . $fARS($ars) . '</span>' : '<span style="color:#A89580">—</span>';

            echo '<tr style="border-bottom:1px solid #F0ECE6">';
            echo $td((string)$cant, 'center');
            echo '<td style="vertical-align:top;padding:9px 10px;line-height:1.4">' . $mainLine . $subLineHtml . '</td>';
            echo $td($h($dimL), 'center');
            echo $td($m2Cell, 'center');
            echo $td($usdCell, 'center');
            echo $td($arsCell, 'center');
            echo '</tr>';

            if ($mon === 'USD') { $sumU += $rowTotal; $secU += $rowTotal; }
            else { $sumA += $rowTotal; $secA += $rowTotal; }
        }

        // Subtotal de sección si hay >1 item válido
        if (count($valid) > 1 && ($secU > 0 || $secA > 0)) {
            $usdSub = $secU > 0 ? $fUSD($secU) : '';
            $arsSub = $secA > 0 ? $fARS($secA) : '';
            echo '<tr style="background:#EDE5D0;border-top:1.5px solid #C4A77D">';
            echo '<td colspan="4" style="text-align:right;font-style:italic;color:#5A4A35;padding:7px 12px;font-size:10px;font-weight:600">Subtotal ' . $h($secLabel) . '</td>';
            echo '<td style="text-align:center;font-weight:700;color:#5A4A35">' . $usdSub . '</td>';
            echo '<td style="text-align:center;font-weight:700;color:#5A4A35">' . $arsSub . '</td>';
            echo '</tr>';
        }
    }

    // Servicios adicionales
    $adic = $presup['adicionales'] ?? [];
    $fleteVal = floatval($adic['flete']['monto'] ?? 0);
    $escP = $adic['escalera'] ?? null;
    $angP = $adic['angulos']  ?? null;
    // "Sin flete cotizado": flete en 0 con zona explícita "Sin flete"
    // (refleja la opción "Sin flete" del dropdown de la calc).
    $fleteSinCotizar = ($fleteVal == 0 && strtolower(trim($adic['flete']['zona'] ?? '')) === 'sin flete');
    $hasServ = $fleteVal > 0
        || (!empty($escP['total']) && floatval($escP['total']) > 0)
        || (!empty($angP['total']) && floatval($angP['total']) > 0)
        || $fleteSinCotizar;
    if ($hasServ) {
        echo '<tr style="background:#F2EDE3"><td style="text-align:center;color:#7A6649">●</td>';
        echo '<td colspan="5" style="font-weight:700;padding:6px 10px">Servicios adicionales</td></tr>';
        if ($fleteVal > 0) {
            $zona = $h($adic['flete']['zona'] ?? '');
            echo '<tr style="border-bottom:1px solid #F0ECE6">';
            echo $td('1') . '<td colspan="3" style="vertical-align:top;padding:9px 10px;font-weight:600;color:#1A1816">Flete y colocación: ' . $zona . '</td>';
            echo $td('<span style="color:#A89580">—</span>') . $td('<span style="color:#1A1816;font-weight:700">' . $fARS($fleteVal) . '</span>');
            echo '</tr>';
            $sumA += $fleteVal;
        } elseif ($fleteSinCotizar) {
            echo '<tr style="border-bottom:1px solid #F0ECE6">';
            echo $td('') . '<td colspan="3" style="vertical-align:top;padding:9px 10px;font-weight:600;color:#1A1816">Flete y colocación</td>';
            echo $td('<span style="color:#A89580">—</span>') . $td('<span style="color:#A89580;font-style:italic">Sin flete cotizado</span>');
            echo '</tr>';
        }
        if (!empty($escP['total']) && floatval($escP['total']) > 0) {
            $cant = intval($escP['cant'] ?? 0);
            $monto = floatval($escP['monto'] ?? 0);
            $mon = $escP['mon'] ?? 'USD';
            $total = floatval($escP['total']);
            $desc = "Subidas a escalera ({$cant} × " . ($mon === 'USD' ? $fUSD($monto) : $fARS($monto)) . ')';
            $usd = $mon === 'USD' ? $total : 0; $ars = $mon === 'ARS' ? $total : 0;
            $usdC = $usd > 0 ? '<span style="color:#1A1816;font-weight:700">' . $fUSD($usd) . '</span>' : '<span style="color:#A89580">—</span>';
            $arsC = $ars > 0 ? '<span style="color:#1A1816;font-weight:700">' . $fARS($ars) . '</span>' : '<span style="color:#A89580">—</span>';
            echo '<tr style="border-bottom:1px solid #F0ECE6">';
            echo $td($cant) . '<td colspan="3" style="vertical-align:top;padding:9px 10px;font-weight:600;color:#1A1816">' . $h($desc) . '</td>';
            echo $td($usdC) . $td($arsC) . '</tr>';
            if ($mon === 'USD') $sumU += $total; else $sumA += $total;
        }
        if (!empty($angP['total']) && floatval($angP['total']) > 0) {
            $cant = intval($angP['cant'] ?? 0);
            $monto = floatval($angP['monto'] ?? 0);
            $mon = $angP['mon'] ?? 'USD';
            $total = floatval($angP['total']);
            $desc = "Ángulos / Ménsulas ({$cant} × " . ($mon === 'USD' ? $fUSD($monto) : $fARS($monto)) . ')';
            $usd = $mon === 'USD' ? $total : 0; $ars = $mon === 'ARS' ? $total : 0;
            $usdC = $usd > 0 ? '<span style="color:#1A1816;font-weight:700">' . $fUSD($usd) . '</span>' : '<span style="color:#A89580">—</span>';
            $arsC = $ars > 0 ? '<span style="color:#1A1816;font-weight:700">' . $fARS($ars) . '</span>' : '<span style="color:#A89580">—</span>';
            echo '<tr style="border-bottom:1px solid #F0ECE6">';
            echo $td($cant) . '<td colspan="3" style="vertical-align:top;padding:9px 10px;font-weight:600;color:#1A1816">' . $h($desc) . '</td>';
            echo $td($usdC) . $td($arsC) . '</tr>';
            if ($mon === 'USD') $sumU += $total; else $sumA += $total;
        }
    }

    // Zócalos > 5 cm
    $zocs = [];
    foreach ($presup['zocalos']['items'] ?? [] as $z) {
        if (!empty($z['mat']) && floatval($z['d1'] ?? 0) > 0 && floatval($z['d2'] ?? 0) > 0 && floatval($z['price'] ?? 0) > 0) $zocs[] = $z;
    }
    if (!empty($zocs)) {
        echo '<tr style="background:#F2EDE3"><td style="text-align:center;color:#7A6649">●</td>';
        echo '<td colspan="5" style="font-weight:700;padding:6px 10px">Zócalos (mayores a 5 cm)</td></tr>';
        $zU = 0; $zA = 0;
        foreach ($zocs as $z) {
            $d1 = floatval($z['d1']); $d2 = floatval($z['d2']);
            $m2 = $d1 * $d2; $t = $m2 * floatval($z['price']);
            $colorPart = !empty($z['color']) ? ' · <strong style="color:#1A1816">' . $h($z['color']) . '</strong>' : '';
            $matPart = !empty($z['mat']) ? ' <span style="color:#7A6649;font-style:italic;font-size:10.5px">(' . $h(bs_mat_label($z['mat'])) . ')</span>' : '';
            $main = 'Zócalo' . $colorPart . $matPart;
            $m2Cell = '<div>' . $h($d2) . '</div><div style="font-size:9px;color:#A89580;margin-top:2px">' . number_format($m2, 2, ',', '.') . ' m²</div>';
            $mon = $z['mon'] ?? 'USD';
            $usd = $mon === 'USD' ? $t : 0; $ars = $mon === 'ARS' ? $t : 0;
            $usdC = $usd > 0 ? '<span style="color:#1A1816;font-weight:700">' . $fUSD($usd) . '</span>' : '<span style="color:#A89580">—</span>';
            $arsC = $ars > 0 ? '<span style="color:#1A1816;font-weight:700">' . $fARS($ars) . '</span>' : '<span style="color:#A89580">—</span>';
            echo '<tr style="border-bottom:1px solid #F0ECE6">';
            echo $td('1') . '<td style="vertical-align:top;padding:9px 10px;line-height:1.4">' . $main . '</td>';
            echo $td($h($d1)) . $td($m2Cell) . $td($usdC) . $td($arsC) . '</tr>';
            if ($mon === 'USD') { $sumU += $t; $zU += $t; } else { $sumA += $t; $zA += $t; }
        }
        if ($zU > 0 || $zA > 0) {
            echo '<tr style="background:#EDE5D0;border-top:1.5px solid #C4A77D">';
            echo '<td colspan="4" style="text-align:right;font-style:italic;color:#5A4A35;padding:7px 12px;font-size:10px;font-weight:600">Subtotal Zócalos</td>';
            echo '<td style="text-align:center;font-weight:700;color:#5A4A35">' . ($zU > 0 ? $fUSD($zU) : '') . '</td>';
            echo '<td style="text-align:center;font-weight:700;color:#5A4A35">' . ($zA > 0 ? $fARS($zA) : '') . '</td></tr>';
        }
    }

    // Otros conceptos
    $extras_raw = $presup['extras'] ?? [];
    $extras_list = [];
    if (isset($extras_raw['items']) && is_array($extras_raw['items'])) $extras_raw = $extras_raw['items'];
    foreach ((array)$extras_raw as $e) {
        if (!empty(trim($e['name'] ?? '')) && floatval($e['amount'] ?? 0) > 0) $extras_list[] = $e;
    }
    if (!empty($extras_list)) {
        echo '<tr style="background:#F2EDE3"><td style="text-align:center;color:#7A6649">●</td>';
        echo '<td colspan="5" style="font-weight:700;padding:6px 10px">Otros conceptos</td></tr>';
        $eU = 0; $eA = 0;
        foreach ($extras_list as $e) {
            $amount = floatval($e['amount']);
            $mon = $e['mon'] ?? 'USD';
            $usd = $mon === 'USD' ? $amount : 0; $ars = $mon === 'ARS' ? $amount : 0;
            $usdC = $usd > 0 ? '<span style="color:#1A1816;font-weight:700">' . $fUSD($usd) . '</span>' : '<span style="color:#A89580">—</span>';
            $arsC = $ars > 0 ? '<span style="color:#1A1816;font-weight:700">' . $fARS($ars) . '</span>' : '<span style="color:#A89580">—</span>';
            echo '<tr style="border-bottom:1px solid #F0ECE6">';
            echo $td('1') . '<td colspan="3" style="vertical-align:top;padding:9px 10px;font-weight:600;color:#1A1816">' . $h($e['name']) . '</td>';
            echo $td($usdC) . $td($arsC) . '</tr>';
            if ($mon === 'USD') { $sumU += $amount; $eU += $amount; } else { $sumA += $amount; $eA += $amount; }
        }
        if ($eU > 0 || $eA > 0) {
            echo '<tr style="background:#EDE5D0;border-top:1.5px solid #C4A77D">';
            echo '<td colspan="4" style="text-align:right;font-style:italic;color:#5A4A35;padding:7px 12px;font-size:10px;font-weight:600">Subtotal Otros conceptos</td>';
            echo '<td style="text-align:center;font-weight:700;color:#5A4A35">' . ($eU > 0 ? $fUSD($eU) : '') . '</td>';
            echo '<td style="text-align:center;font-weight:700;color:#5A4A35">' . ($eA > 0 ? $fARS($eA) : '') . '</td></tr>';
        }
    }

    // Bachas y accesorios
    $bachas_raw = $presup['bachas'] ?? [];
    if (isset($bachas_raw['items']) && is_array($bachas_raw['items'])) $bachas_raw = $bachas_raw['items'];
    if (!empty($bachas_raw)) {
        echo '<tr style="background:#F2EDE3"><td style="text-align:center;color:#7A6649">●</td>';
        echo '<td colspan="5" style="font-weight:700;padding:6px 10px">Bachas y accesorios</td></tr>';
        $bSum = 0;
        foreach ((array)$bachas_raw as $b) {
            $price = floatval($b['price'] ?? 0); $qty = intval($b['qty'] ?? 1);
            if ($price <= 0) continue;
            $t = $price * $qty; $bSum += $t;
            $main = '<strong style="color:#1A1816">' . $h($b['code'] ?? '') . '</strong> · ' . $h($b['desc'] ?? '');
            if (!empty($b['cat'])) $main .= ' <span style="color:#7A6649;font-style:italic;font-size:10.5px">(' . $h($b['cat']) . ')</span>';
            echo '<tr style="border-bottom:1px solid #F0ECE6">';
            echo $td($qty) . '<td colspan="3" style="vertical-align:top;padding:9px 10px;line-height:1.4">' . $main . '</td>';
            echo $td('<span style="color:#A89580">—</span>') . $td('<span style="color:#1A1816;font-weight:700">' . $fARS($t) . '</span>') . '</tr>';
        }
        if ($bSum > 0) {
            $sumA += $bSum;
            echo '<tr style="background:#EDE5D0;border-top:1.5px solid #C4A77D">';
            echo '<td colspan="4" style="text-align:right;font-style:italic;color:#5A4A35;padding:7px 12px;font-size:10px;font-weight:600">Subtotal Bachas</td>';
            echo '<td style="text-align:center"><span style="color:#A89580">—</span></td>';
            echo '<td style="text-align:center;font-weight:700;color:#5A4A35">' . $fARS($bSum) . '</td></tr>';
        }
    }

    // === Totales ===
    $ivaU = $factura ? $sumU * 0.21 : 0;
    $ivaA = $factura ? $sumA * 0.21 : 0;
    $totU = $sumU + $ivaU;
    $totA = $sumA + $ivaA;
    $senaU = round($totU / 2);
    $senaA = round($totA / 2);
    $saldoU = $totU - $senaU;
    $saldoA = $totA - $senaA;

    if ($incTotal) {
        echo '<tr style="background:#F2EDE3"><td colspan="4" style="font-weight:700;padding-left:12px">Subtotal</td>';
        echo '<td style="text-align:center;font-weight:700;color:#1A1816">' . ($sumU > 0 ? $fUSD($sumU) : '') . '</td>';
        echo '<td style="text-align:center;font-weight:700;color:#1A1816">' . ($sumA > 0 ? $fARS($sumA) : '') . '</td></tr>';
        if ($factura) {
            echo '<tr><td colspan="4" style="padding-left:12px">I.V.A. (21%)</td>';
            echo '<td style="text-align:center;font-weight:700;color:#1A1816">' . ($ivaU > 0 ? $fUSD($ivaU) : '') . '</td>';
            echo '<td style="text-align:center;font-weight:700;color:#1A1816">' . ($ivaA > 0 ? $fARS($ivaA) : '') . '</td></tr>';
        }
        echo '<tr style="background:#1A1816;color:#FAF8F4"><td colspan="4" style="font-weight:700;font-size:13px;padding-left:12px">TOTAL</td>';
        echo '<td style="text-align:center;font-weight:700;color:#25D366;font-size:13px">' . ($totU > 0 ? $fUSD($totU) : '') . '</td>';
        echo '<td style="text-align:center;font-weight:700;color:#0EA5E9;font-size:13px">' . ($totA > 0 ? $fARS($totA) : '') . '</td></tr>';
    }
    if ($incSena) {
        echo '<tr style="background:#1A1816;color:#FAF8F4"><td colspan="4" style="font-weight:700;padding-left:12px">SEÑA</td>';
        echo '<td style="text-align:center;font-weight:700;color:#C4A77D">' . ($senaU > 0 ? $fUSD($senaU) : '') . '</td>';
        echo '<td style="text-align:center;font-weight:700;color:#C4A77D">' . ($senaA > 0 ? $fARS($senaA) : '') . '</td></tr>';
    }
    if ($incSaldo) {
        echo '<tr style="background:#1A1816;color:#FAF8F4"><td colspan="4" style="font-weight:700;padding-left:12px">SALDO PENDIENTE</td>';
        echo '<td style="text-align:center;font-weight:700;color:#C4A77D">' . ($saldoU > 0 ? $fUSD($saldoU) : '') . '</td>';
        echo '<td style="text-align:center;font-weight:700;color:#C4A77D">' . ($saldoA > 0 ? $fARS($saldoA) : '') . '</td></tr>';
    }
    if ($incTotalARS && $totU > 0 && $dolar > 0) {
        $usdEnArs = $totU * $dolar;
        $totalCombinado = $totA + $usdEnArs;
        echo '<tr><td colspan="6" style="padding:0;height:3px;background:#C4A77D"></td></tr>';
        echo '<tr><td colspan="6" style="padding:0;height:6px;background:#FAF8F4"></td></tr>';
        echo '<tr style="background:#2A2522;color:#FAF8F4">';
        echo '<td colspan="4" style="font-weight:700;padding:10px 12px;font-size:12px">TOTAL EN ARS · al cambio del ' . $h(bs_fmt_fecha_dmy($fecha)) . '<br>';
        echo '<span style="font-size:9px;color:#A89580;font-weight:400">USD ' . number_format($totU, 2, ',', '.') . ' × ' . $fARS($dolar) . ' por dólar</span></td>';
        echo '<td colspan="2" style="text-align:center;font-weight:700;color:#0EA5E9;font-size:14px;padding:10px">' . $fARS($totalCombinado) . '</td></tr>';
    }

    echo '</tbody></table>';

    // Tipo de cambio
    echo '<div style="margin-top:12px;padding:8px;background:#FAF8F4;border-radius:4px;font-size:10px"><b>Tipo de cambio referencia' . ($dolar > 0 ? ' al ' . $h(bs_fmt_fecha_dmy($fecha)) : '') . ':</b> ';
    echo ($dolar > 0 ? 'USD 1 = ' . $fARS($dolar) . ' (Dólar Oficial Venta · DolarHoy). Los importes en USD se convierten a esta cotización; sujeto al valor del día de pago.' : 'consultar al momento del pago');
    echo '</div>';

    // Notas editoriales
    echo '<div class="notes-block" style="margin-top:12px;padding:14px 16px;background:#FAF8F4;border-radius:4px;font-size:10px;color:#3A332E;line-height:1.5">';

    echo '<div style="font-family:\'Fraunces\',serif;font-weight:600;color:#1A1816;font-size:11px;letter-spacing:.04em;margin-bottom:6px;border-bottom:1px solid rgba(196,167,125,.35);padding-bottom:4px">FORMA DE PAGO</div>';
    echo '<div><b>Anticipo del 50 %</b> a la aceptación del presupuesto · <b>Saldo restante</b> contra entrega en obra.<br><span style="color:#6B6560;font-style:italic">La fabricación se inicia una vez acreditada la seña.</span></div>';

    echo '<div style="font-family:\'Fraunces\',serif;font-weight:600;color:#1A1816;font-size:11px;letter-spacing:.04em;margin:14px 0 6px;border-bottom:1px solid rgba(196,167,125,.35);padding-bottom:4px">ALCANCE DEL PRESUPUESTO</div>';
    echo '<div style="margin-bottom:4px"><b style="color:#C4A77D">●</b> Incluye la <b>fabricación</b> de los trabajos detallados <b>(con las medidas indicadas)</b> y <b>zócalos perimetrales de hasta 5 cm</b> de altura si el cliente lo requiere.</div>';
    echo '<div style="margin-bottom:4px"><b style="color:#C4A77D">●</b> El <b>traslado e instalación</b> quedan cubiertos únicamente si están <b>detallados en este presupuesto</b>; de no figurar, se cotizan aparte según la zona.</div>';
    echo '<div style="margin-bottom:4px"><b style="color:#C4A77D">●</b> No incluye trabajos de plomería ni instalación de equipos eléctricos como anafes, campanas, hornos u otros artefactos.</div>';
    echo '<div><b style="color:#C4A77D">●</b> No incluye subida de piezas por escalera; este servicio se cotiza por separado en función del piso y la complejidad de acceso.</div>';

    echo '<div style="font-family:\'Fraunces\',serif;font-weight:600;color:#1A1816;font-size:11px;letter-spacing:.04em;margin:14px 0 6px;border-bottom:1px solid rgba(196,167,125,.35);padding-bottom:4px">MEDICIONES Y AJUSTES</div>';
    echo '<div style="margin-bottom:4px"><b style="color:#C4A77D">●</b> Cuando las medidas son provistas por el cliente, los valores quedan sujetos a verificación. Toda diferencia de superficie detectada en obra se ajustará en el presupuesto final según los m² reales.</div>';
    echo '<div><b style="color:#C4A77D">●</b> La medición técnica en obra se realiza con los muebles bajo mesada ya instalados y nivelados, y los trabajos de albañilería de la cocina finalizados. Si al concurrir no se reúnen estas condiciones, una segunda visita de medición se factura por separado.</div>';

    echo '<div style="font-family:\'Fraunces\',serif;font-weight:600;color:#1A1816;font-size:11px;letter-spacing:.04em;margin:14px 0 6px;border-bottom:1px solid rgba(196,167,125,.35);padding-bottom:4px">MATERIALES Y MUESTRAS</div>';
    echo '<div style="margin-bottom:4px"><b style="color:#C4A77D">●</b> El granito y el mármol son rocas naturales y, como tales, presentan variaciones de color, tono, vetas, poros y otras características inherentes a su formación geológica. Estas variaciones <b>no constituyen defectos</b> de fabricación ni de calidad, y son parte del valor estético del material.</div>';
    echo '<div style="margin-bottom:4px"><b style="color:#C4A77D">●</b> Las muestras físicas y digitales son orientativas: representan a escala reducida un sector de la placa y no implican uniformidad estricta con el suministro real.</div>';
    echo '<div><b style="color:#C4A77D">●</b> El <b>granito no cuenta con garantía de fábrica</b> por sus características naturales. Las piedras industrializadas (cuarzo, sinterizadas) mantienen las garantías otorgadas por el fabricante de origen.</div>';

    echo '<div style="font-family:\'Fraunces\',serif;font-weight:600;color:#1A1816;font-size:11px;letter-spacing:.04em;margin:14px 0 6px;border-bottom:1px solid rgba(196,167,125,.35);padding-bottom:4px">INSTALACIÓN</div>';
    echo '<div style="margin-bottom:4px"><b style="color:#C4A77D">●</b> Las instalaciones se realizan en <b>horario laboral, de lunes a viernes</b>.</div>';
    echo '<div><b style="color:#C4A77D">●</b> Si por causas atribuibles al cliente la instalación no puede concluirse en la fecha pactada, la reprogramación implica un costo adicional de visita.</div>';

    echo '</div>';

    echo '<div style="text-align:center;font-size:8px;color:#6B6560;margin-top:12px;padding-top:8px;border-top:1px solid #F2EDE3">BlackStones Marmolería  ·  Cel: 11 2468-5820  ·  Of: 5611-5919  ·  contacto@blackstones.com.ar  ·  Av. J.B. Alberdi 3575, CABA  ·  blackstones.com.ar</div>';

    if ($wrap_html) echo '</body></html>';
}

function bs_render_styles() {
    return <<<CSS
@page { size: A4 portrait; margin: 10mm; }
* { margin: 0; padding: 0; box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; color-adjust: exact !important; }
html, body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
body { font-family: Arial, sans-serif; color: #1A1816; font-size: 11px; background: #fff; padding: 12px; }
table { width: 100%; border-collapse: collapse; }
td, th { padding: 5px 8px; }
.no-print { padding: 16px; background: #1A1816; color: #FAF8F4; text-align: center; margin-bottom: 12px; border-radius: 6px; }
.no-print button { background: #C4A77D; color: #1A1816; border: none; padding: 10px 24px; font-size: 14px; font-weight: 700; border-radius: 6px; cursor: pointer; margin: 0 6px; }
.notes-block { break-inside: avoid; page-break-inside: avoid; }
.estado { padding: 2px 8px; border-radius: 3px; font-weight: 600; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; }
.est-borrador  { background: #F2EDE3; color: #7A6649; }
.est-enviado   { background: #E5F0F8; color: #0EA5E9; }
.est-aprobado  { background: #E5F8E5; color: #25A036; }
.est-entregado { background: #1A1816; color: #C4A77D; }
.est-perdido   { background: #FDF1F1; color: #C44747; }
@media print {
  .no-print { display: none; }
  body { padding: 0; }
  * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; color-adjust: exact !important; }
  table { break-inside: auto; }
  tr { break-inside: avoid; page-break-inside: avoid; }
}
CSS;
}
