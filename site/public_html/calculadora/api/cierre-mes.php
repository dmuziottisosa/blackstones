<?php
// ============================================================
// cierre-mes.php — El cierre del mes: bajar TODO en un archivo
// ============================================================
// Idea del dueño (18/11/2026): al final de cada mes se baja todo y se guarda dos veces, una copia
// en físico y otra en digital. El botón solo funciona el ÚLTIMO DÍA DEL MES (28, 29, 30 o 31) y
// baja una foto del sistema tal como está en ese momento.
//
// Solo administradores (permiso "reportes"). La pantalla es /presupuestos/reportes.php.
//   GET                 → { ok, hoy, cierre, habilitado, mes, fichas, archivadas_mes, contratos,
//                           presupuestos, planos, entregas, bachas, mb:{base,planos,entregas,bachas} }
//   GET ?bajar=1&planos=1&entregas=1&bachas=0  → el ZIP (409 si hoy no es el último día del mes)
//
// Qué trae el ZIP:
//   LEEME.txt                  qué es y cómo se usa
//   para-imprimir.html         una hoja por obra, con sus datos, su contrato, su historial y sus fotos
//   fichas.csv                 todas las obras (se abre en Excel)
//   contratos.csv              todos los contratos, de todos los meses
//   fichas/<N° Nombre>/        la carpeta del cliente: su ficha (ficha.html), el presupuesto adjunto del
//                              contrato y, si se tildan, planos, fotos de entrega y fotos de bacha
//   datos/                     los archivos del sistema tal cual, para poder reconstruirlo
//
// Qué obras entran: las que están vivas en el tablero y las que se archivaron en el mes que se
// cierra (las archivadas antes ya salieron en el cierre de su mes).
// No trae el archivo de usuarios: tiene las contraseñas, y este ZIP se guarda fuera del sistema.
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_tablero.php';
require_once __DIR__ . '/_contratos.php';

bs_require_permiso('reportes');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') bs_error('Solo GET', 405);
date_default_timezone_set('America/Argentina/Buenos_Aires'); // el último día del mes es el de acá

// La fecha de hoy. En el servidor de pruebas (nunca en producción) se puede simular.
function bs_cierre_hoy() {
    if (php_sapi_name() === 'cli-server' && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)getenv('BS_FECHA_PRUEBA'))) return getenv('BS_FECHA_PRUEBA');
    return date('Y-m-d');
}
$HOY = bs_cierre_hoy();
$CIERRE = date('Y-m-t', strtotime($HOY)); // último día de este mes
$MES = substr($HOY, 0, 7);
$MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$MES_TXT = $MESES[intval(substr($HOY, 5, 2)) - 1] . ' de ' . substr($HOY, 0, 4);

// ---------- juntar todo ----------
$tablero = bs_tablero_leer();
$contratos = bs_contratos_leer()['contratos'];
$contrato_de = []; // key de ficha → contrato
foreach ($contratos as $id => $c) {
    if (!empty($c['borrado'])) continue;
    $e = $c['enlace'] ?? null;
    if (is_array($e)) $contrato_de[$e['cliente_nro'] . '-' . intval($e['sub'] ?? 0)] = ['id' => (string)$id] + $c;
}
$dia = function ($iso) { return preg_match('/^(\d{4})-(\d{2})-(\d{2})/', (string)$iso, $m) ? "$m[3]/$m[2]/$m[1]" : ''; };
$plata = function ($n, $mon) { return $n > 0 ? ($mon === 'USD' ? 'USD ' : '$ ') . number_format($n, fmod($n, 1) ? 2 : 0, ',', '.') : ''; };
$carpeta = function ($s) {
    $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n', 'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N']);
    return trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Za-z0-9 ._-]/', ' ', $s))) ?: 'obra';
};

$fichas = [];
foreach (@scandir(BS_CLIENTES_DIR) ?: [] as $f) {
    if (!preg_match('/^(\d{4,})\.json$/', $f, $m)) continue;
    $cli_obj = bs_read_json(BS_CLIENTES_DIR . '/' . $f);
    if (!is_array($cli_obj)) continue;
    $nro = (string)($cli_obj['cliente_nro'] ?? $m[1]);
    $cli = (array)($cli_obj['cliente'] ?? []);
    foreach ((array)($cli_obj['cotizaciones'] ?? []) as $cot) {
        $estado = (string)($cot['estado'] ?? '');
        // el día en que se archivó, en hora de Argentina (lo guardado antes del 18/11/2026 está en UTC)
        $bs_ea = $estado === 'entregado' ? strtotime((string)($cot['entregado_at'] ?? '')) : false;
        $archivada = $bs_ea ? date('Y-m-d', $bs_ea) : '';
        $key = $nro . '-' . intval($cot['sub'] ?? 0);
        if ($estado === 'entregado' && !isset($tablero['obras'][$key])) continue;   // un entregado histórico, sin ficha
        if (!($estado === 'aprobado' || ($archivada !== '' && substr($archivada, 0, 7) === $MES))) continue;
        $obra = array_merge(bs_tablero_obra_vacia(), (array)($tablero['obras'][$key] ?? []));
        $manual = !empty($cot['manual']);
        $con = $contrato_de[$key] ?? null;
        $nro_ver = $manual ? (string)($obra['nro_contrato'] ?? '') : $key;
        list($historia, ) = bs_tablero_historia($obra);
        $sena = bs_tablero_sena($obra);
        $pagado = ['USD' => 0.0, 'ARS' => 0.0];
        if ($con) { foreach (array_merge(is_array($con['sena'] ?? null) ? [$con['sena']] : [], (array)($con['pagos'] ?? [])) as $p) { $mo = ($p['moneda'] ?? '') === 'USD' ? 'USD' : 'ARS'; $pagado[$mo] += floatval($p['monto'] ?? 0); } }
        $fichas[] = [
            'key' => $key, 'nro' => $nro_ver, 'manual' => $manual, 'archivada' => $archivada,
            'nombre' => (string)($cli['nombre'] ?? ''), 'celular' => (string)($cli['celular'] ?? ''), 'direccion' => (string)($cli['direccion'] ?? ''),
            'material' => bs_tablero_material($cot)[0],
            'etapa' => $archivada !== '' ? 'Archivada' : (string)($GLOBALS['BS_ETAPAS'][$obra['etapa']]['titulo'] ?? $obra['etapa']),
            'etapa_id' => (string)$obra['etapa'], 'fecha' => (string)$obra['fecha'], 'hora' => (string)$obra['hora'],
            'medido' => (string)$obra['medido_at'], 'plazo' => (string)($obra['plazo'] ?? ''),
            'sena' => $sena ? $plata($sena['monto'], $sena['moneda']) : '',
            'bacha' => ['nuestra' => 'Nuestra', 'cliente' => 'Del cliente', 'no' => 'No lleva'][$obra['bacha']] ?? '', 'bacha_modelo' => (string)($obra['bacha_modelo'] ?? ''),
            'mide' => implode(', ', (array)($obra['medidor'] ?? [])), 'corta' => implode(', ', (array)($obra['cortador'] ?? [])),
            'fabrica' => implode(', ', (array)($obra['fabrica'] ?? [])), 'instala' => implode(', ', (array)($obra['equipo'] ?? [])),
            'nota' => (string)$obra['nota'], 'historia' => $historia, 'adjuntos' => bs_tablero_adjuntos($obra), 'contrato' => $con, 'pagado' => $pagado,
            'carpeta' => $carpeta(($nro_ver !== '' ? $nro_ver : 'Sin N') . ' ' . ((string)($cli['nombre'] ?? '') ?: 'Sin nombre')) . ' (' . $key . ')',
        ];
    }
}
usort($fichas, function ($a, $b) { return strnatcmp($a['carpeta'], $b['carpeta']); });

// ---------- qué archivos van, por tipo ----------
$archivos = ['presupuesto' => [], 'plano' => [], 'entrega' => [], 'bacha' => []]; // tipo → [[ruta en el zip, ruta real, bytes]]
foreach ($fichas as $i => $fi) {
    $n = ['plano' => 0, 'entrega' => 0, 'bacha' => 0];
    $fichas[$i]['archivos'] = []; $fichas[$i]['links'] = [];
    $a = $fi['contrato'] ? bs_contrato_archivo($fi['contrato']) : null;
    if ($a && is_file($p = bs_contrato_adj_path($fi['contrato']['id'], $a['ext']))) {
        $z = 'fichas/' . $fi['carpeta'] . '/presupuesto.' . $a['ext'];
        $archivos['presupuesto'][] = [$z, $p, filesize($p)]; $fichas[$i]['archivos'][] = ['presupuesto', $z, false];
    }
    foreach ($fi['adjuntos'] as $ad) {
        if ($ad['ext'] === 'link') { $fichas[$i]['links'][] = $GLOBALS['BS_TABLERO_ADJ_TIPOS'][$ad['tipo']] . ': ' . $ad['url']; continue; }
        $p = bs_tablero_adj_carpeta($fi['key']) . '/' . $ad['id'] . '.' . $ad['ext'];
        if (!is_file($p)) continue;
        $z = 'fichas/' . $fi['carpeta'] . '/' . $ad['tipo'] . '-' . (++$n[$ad['tipo']]) . '.' . $ad['ext'];
        $archivos[$ad['tipo']][] = [$z, $p, filesize($p)]; $fichas[$i]['archivos'][] = [$ad['tipo'], $z, $ad['ext'] !== 'pdf'];
    }
}
// Los presupuestos adjuntos de los contratos que no tienen una ficha en este cierre (contrato suelto,
// o de una obra que ya salió en otro mes): también van, en su propia carpeta (barrido del 18/11/2026).
// Ese PDF es lo único que queda del presupuesto pasados los 180 días.
$bs_ya = [];
foreach ($fichas as $fi) { if ($fi['contrato']) $bs_ya[(string)$fi['contrato']['id']] = true; }
foreach ($contratos as $id => $c) {
    if (!empty($c['borrado']) || isset($bs_ya[(string)$id])) continue;
    $a = bs_contrato_archivo($c);
    if (!$a || !is_file($p = bs_contrato_adj_path((string)$id, $a['ext']))) continue;
    $z = 'contratos-sin-ficha/' . $carpeta((trim((string)($c['nro'] ?? '')) ?: 'Sin N') . ' ' . (trim((string)($c['cliente'] ?? '')) ?: 'Sin nombre')) . ' (' . preg_replace('/[^A-Za-z0-9_-]/', '', (string)$id) . ')/presupuesto.' . $a['ext'];
    $archivos['presupuesto'][] = [$z, $p, filesize($p)];
}
$mb = function ($l) { return round(array_sum(array_column($l, 2)) / 1048576, 1); };
$vivas = count(array_filter($fichas, function ($f) { return $f['archivada'] === ''; }));
$n_contratos = count(array_filter($contratos, function ($c) { return empty($c['borrado']); }));

if (empty($_GET['bajar'])) {
    bs_ok(['hoy' => $HOY, 'cierre' => $CIERRE, 'habilitado' => $HOY === $CIERRE, 'mes' => $MES_TXT,
        'fichas' => $vivas, 'archivadas_mes' => count($fichas) - $vivas, 'contratos' => $n_contratos,
        'presupuestos' => count($archivos['presupuesto']), 'planos' => count($archivos['plano']), 'entregas' => count($archivos['entrega']), 'bachas' => count($archivos['bacha']),
        'mb' => ['base' => $mb($archivos['presupuesto']), 'planos' => $mb($archivos['plano']), 'entregas' => $mb($archivos['entrega']), 'bachas' => $mb($archivos['bacha'])]]);
}

if ($HOY !== $CIERRE) bs_error('El cierre del mes se baja el último día del mes: el ' . $dia($CIERRE) . '.', 409);
if (!class_exists('ZipArchive')) bs_error('El servidor no puede armar el archivo (falta ZipArchive).', 500);
$quiero = ['presupuesto' => true, 'plano' => !empty($_GET['planos']), 'entrega' => !empty($_GET['entregas']), 'bacha' => !empty($_GET['bachas'])];

// ---------- los textos ----------
$celda = function ($v) { $t = trim(preg_replace('/\r?\n/', ' ', (string)$v)); if (preg_match('/^[=+\-@]/', $t)) $t = "'" . $t; return preg_match('/[";]/', $t) ? '"' . str_replace('"', '""', $t) . '"' : $t; };
$csv = function ($cab, $filas) use ($celda) { $o = "\xEF\xBB\xBF" . implode(';', array_map($celda, $cab)) . "\r\n"; foreach ($filas as $f) $o .= implode(';', array_map($celda, $f)) . "\r\n"; return $o; };
$num = function ($n) { return abs($n) > 0.004 ? str_replace('.', ',', (string)round($n, 2)) : ''; };
$turno = function ($fi) use ($dia) { return $fi['fecha'] !== '' ? $dia($fi['fecha']) . ($fi['hora'] !== '' ? ' ' . $fi['hora'] : '') : ''; };

$f_csv = $csv(['N°', 'Nombre y apellido', 'Teléfono', 'Dirección', 'Material', 'Etapa', 'Archivada el', 'Turno', 'Medido el', 'Seña (tablero)', 'Bacha', 'Modelo de bacha', 'Mide', 'Corta', 'Fabrica', 'Instala', 'Nota', 'N° de contrato', 'Total USD', 'Total $', 'Cobrado USD', 'Cobrado $', 'Presupuesto adjunto', 'Planos', 'Fotos de entrega', 'Fotos de bacha', 'Carpeta', 'Ficha (número interno)'],
    array_map(function ($fi) use ($dia, $turno, $num) {
        $c = $fi['contrato']; $cu = function ($t) use ($fi) { return count(array_filter($fi['archivos'], function ($a) use ($t) { return $a[0] === $t; })) ?: ''; };
        return [$fi['nro'], $fi['nombre'], $fi['celular'], $fi['direccion'], $fi['material'], $fi['etapa'], $dia($fi['archivada']), $turno($fi), $dia($fi['medido']), $fi['sena'], $fi['bacha'], $fi['bacha_modelo'], $fi['mide'], $fi['corta'], $fi['fabrica'], $fi['instala'], $fi['nota'],
            $c ? (string)($c['nro'] ?? '') : '', $c ? $num(floatval($c['total_usd'] ?? 0)) : '', $c ? $num(floatval($c['total_ars'] ?? 0)) : '', $num($fi['pagado']['USD']), $num($fi['pagado']['ARS']), $cu('presupuesto') ? 'SI' : 'NO', $cu('plano'), $cu('entrega'), $cu('bacha'), 'fichas/' . $fi['carpeta'], $fi['key']];
    }, $fichas));

$c_filas = [];
foreach ($contratos as $id => $c) {
    if (!empty($c['borrado'])) continue;
    $pg = ['USD' => 0.0, 'ARS' => 0.0]; $det = [];
    foreach (array_merge(is_array($c['sena'] ?? null) ? [['s' => 1] + $c['sena']] : [], (array)($c['pagos'] ?? [])) as $p) { $mo = ($p['moneda'] ?? '') === 'USD' ? 'USD' : 'ARS'; $pg[$mo] += floatval($p['monto'] ?? 0); $det[] = (isset($p['s']) ? 'Seña ' : 'Pago ') . $dia($p['fecha'] ?? '') . ' ' . $plata(floatval($p['monto'] ?? 0), $mo) . (!empty($p['cotizacion']) ? ' (cotiz. ' . $p['cotizacion'] . ')' : ''); }
    $e = is_array($c['enlace'] ?? null) ? $c['enlace']['cliente_nro'] . '-' . intval($c['enlace']['sub'] ?? 0) : '';
    $c_filas[] = [$dia($c['fecha'] ?? ''), (string)($c['nro'] ?? ''), (string)($c['contrato_paralelo'] ?? ''), (string)($c['cliente'] ?? ''), (string)($c['telefono'] ?? ''), (string)($c['direccion'] ?? ''), (string)($c['vendedor'] ?? ''), (string)($c['procedencia'] ?? ''), (string)($c['material'] ?? ''), (string)($c['medidas'] ?? ''),
        $num(floatval($c['total_usd'] ?? 0)), $num(floatval($c['total_ars'] ?? 0)), $num($pg['USD']), $num($pg['ARS']), implode(' | ', $det), (string)($c['factura'] ?? ''), (string)($c['muebles'] ?? ''), bs_contrato_archivo($c) ? 'SI' : 'NO', $e, (string)($c['nota'] ?? '')];
}
usort($c_filas, function ($a, $b) { return strcmp(implode('', array_reverse(explode('/', $b[0]))), implode('', array_reverse(explode('/', $a[0])))); });
$c_csv = $csv(['Fecha', 'N° de contrato', 'Ctto paralelo', 'Nombre y apellido', 'Teléfono', 'Dirección', 'Vendedor', 'Procedencia', 'Material', 'Medidas', 'Total USD', 'Total $', 'Cobrado USD', 'Cobrado $', 'Seña y pagos', 'Factura', 'Muebles', 'Presupuesto adjunto', 'Ficha enlazada (número interno)', 'Notas'], $c_filas);

// La hoja para imprimir: una página por obra.
$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
$html = '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>BlackStones · Cierre de ' . $h($MES_TXT) . '</title><style>'
    . 'body{font:13px/1.45 Arial,Helvetica,sans-serif;color:#111;margin:24px}h1{font-size:22px;margin:0 0 4px}h2{font-size:19px;margin:0 0 2px}.sub{color:#555;margin:0 0 14px}'
    . '.obra{page-break-after:always;padding-bottom:18px;margin-bottom:22px;border-bottom:1px solid #bbb}.obra:last-child{page-break-after:auto;border:0}'
    . 'table{border-collapse:collapse;width:100%;margin:8px 0}td,th{border:1px solid #bbb;padding:4px 7px;text-align:left;vertical-align:top}th{width:150px;background:#f1f1f1;font-weight:bold}'
    . 'h3{font-size:14px;margin:14px 0 4px}.hist td{border:0;border-bottom:1px solid #ddd;padding:2px 6px}.fotos img{max-width:48%;max-height:330px;margin:4px 1% 4px 0;border:1px solid #999}'
    . '.ind td{padding:3px 7px}@media print{body{margin:10mm}a{color:#111;text-decoration:none}}</style></head><body>'
    . '<h1>BlackStones · Cierre de ' . $h($MES_TXT) . '</h1><p class="sub">Foto del sistema del ' . $h($dia($HOY)) . ' a las ' . date('H:i') . '. ' . $vivas . ' obras en el tablero, ' . (count($fichas) - $vivas) . ' archivadas en el mes, ' . $n_contratos . ' contratos.</p>'
    . '<div class="obra"><h3>Índice</h3><table class="ind"><tr><th style="width:auto">N°</th><th style="width:auto">Nombre y apellido</th><th style="width:auto">Etapa</th><th style="width:auto">Material</th><th style="width:auto">Saldo a mirar en</th></tr>';
foreach ($fichas as $fi) $html .= '<tr><td>' . $h($fi['nro'] ?: 'Sin N°') . '</td><td>' . $h($fi['nombre']) . '</td><td>' . $h($fi['etapa']) . '</td><td>' . $h($fi['material']) . '</td><td>' . ($fi['contrato'] ? 'contratos.csv' : 'sin contrato') . '</td></tr>';
$html .= '</table></div>';
// La hoja de una obra. $en_carpeta: los links y las fotos se nombran como archivos de su misma carpeta
// (para la ficha suelta que va adentro de cada carpeta); si no, con la ruta entera desde la raíz del ZIP.
$hoja = function ($fi, $en_carpeta) use ($h, $dia, $plata, $turno, $quiero) {
    $c = $fi['contrato']; $fila = function ($t, $v) use ($h) { return (string)$v === '' ? '' : '<tr><th>' . $t . '</th><td>' . $h($v) . '</td></tr>'; };
    $ruta = function ($z) use ($en_carpeta) { return $en_carpeta ? basename($z) : $z; };
    $o = '<div class="obra"><h2>' . $h(($fi['nro'] ?: 'Sin N°') . ' · ' . ($fi['nombre'] ?: 'Sin nombre')) . '</h2><p class="sub">' . $h($fi['etapa'] . ($fi['archivada'] ? ' el ' . $dia($fi['archivada']) : '')) . ' · ficha ' . $h($fi['key']) . '</p><table>'
        . $fila('Teléfono', $fi['celular']) . $fila('Dirección', $fi['direccion']) . $fila('Material', $fi['material']) . $fila('Turno', $turno($fi)) . $fila('Medido el', $dia($fi['medido']))
        . $fila('Seña (tablero)', $fi['sena']) . $fila('Bacha', trim($fi['bacha'] . ' ' . $fi['bacha_modelo'])) . $fila('Mide', $fi['mide']) . $fila('Corta', $fi['corta']) . $fila('Fabrica', $fi['fabrica']) . $fila('Instala', $fi['instala']) . $fila('Nota', $fi['nota']) . '</table>';
    if ($c) {
        $o .= '<h3>Contrato</h3><table>' . $fila('N° de contrato', (string)($c['nro'] ?? '')) . $fila('Ctto paralelo', (string)($c['contrato_paralelo'] ?? '')) . $fila('Fecha', $dia($c['fecha'] ?? '')) . $fila('Medidas', (string)($c['medidas'] ?? ''))
            . $fila('Total', trim($plata(floatval($c['total_usd'] ?? 0), 'USD') . ' ' . $plata(floatval($c['total_ars'] ?? 0), 'ARS'))) . $fila('Cobrado', trim($plata($fi['pagado']['USD'], 'USD') . ' ' . $plata($fi['pagado']['ARS'], 'ARS'))) . $fila('Notas', (string)($c['nota'] ?? '')) . '</table>';
    } else $o .= '<p class="sub">Sin contrato enlazado.</p>';
    if ($fi['historia']) {
        $o .= '<h3>Historial</h3><table class="hist">';
        foreach ($fi['historia'] as $et) { $o .= '<tr><td colspan="2"><b>' . $h($et['etapa']) . '</b>' . ($et['salio'] ? ' · salió el ' . $dia($et['salio']) : '') . '</td></tr>'; foreach ($et['items'] as $it) $o .= '<tr><td>' . $h($it['txt']) . '</td><td>' . ($it['fecha'] ? $dia($it['fecha']) : '—') . '</td></tr>'; }
        $o .= '</table>';
    }
    $van = array_values(array_filter($fi['archivos'], function ($a) use ($quiero) { return !empty($quiero[$a[0]]); }));
    if ($van || $fi['links']) {
        $o .= '<h3>Archivos</h3><p>' . implode(' · ', array_map(function ($a) use ($h, $ruta) { return '<a href="' . $h($ruta($a[1])) . '">' . $h(basename($a[1])) . '</a>'; }, $van)) . ($fi['links'] ? '<br>' . $h(implode(' · ', $fi['links'])) : '') . '</p><div class="fotos">';
        foreach ($van as $a) if ($a[2]) $o .= '<img src="' . $h($ruta($a[1])) . '" alt="' . $h(basename($a[1])) . '">';
        $o .= '</div>';
    }
    return $o . '</div>';
};
$cabeza = substr($html, 0, strpos($html, '<body>') + 6); // el mismo encabezado y estilos, para la ficha suelta de cada carpeta
foreach ($fichas as $fi) $html .= $hoja($fi, false);
$html .= '</body></html>';

$leeme = "BLACKSTONES · CIERRE DE " . mb_strtoupper($MES_TXT, 'UTF-8') . "\r\nFoto del sistema del " . $dia($HOY) . " a las " . date('H:i') . ".\r\n\r\n"
    . "QUE HAY ACA\r\n- para-imprimir.html: abrilo con doble clic y mandalo a imprimir. Sale una hoja por obra.\r\n- fichas.csv y contratos.csv: se abren en Excel.\r\n"
    . "- fichas/: una carpeta por cliente, lista para guardar: su ficha (ficha.html, se abre e imprime sola), el presupuesto adjunto de su contrato" . ($quiero['plano'] ? ', sus planos' : '') . ($quiero['entrega'] ? ', sus fotos de entrega' : '') . ($quiero['bacha'] ? ', sus fotos de bacha' : '') . ".\r\n"
    . "- datos/: los archivos del sistema tal cual. Sirven para reconstruirlo si hiciera falta.\r\n\r\n"
    . "QUE OBRAS ENTRAN\r\nLas " . $vivas . " que estan en el tablero y las " . (count($fichas) - $vivas) . " que se archivaron este mes. Las archivadas antes salieron en el cierre de su mes.\r\n\r\n"
    . "COMO GUARDARLO\r\nDos copias: una impresa (para-imprimir.html y lo que haga falta de cada carpeta) y otra digital, en un lugar que no sea esta computadora.\r\n"
    . "Este archivo tiene nombres, telefonos, direcciones y plata de clientes: no se manda por canales abiertos.\r\nNo trae los usuarios ni sus contrasenas.\r\n";

// ---------- armar el ZIP y mandarlo ----------
@set_time_limit(0);
@ignore_user_abort(true);
$tmp = tempnam(sys_get_temp_dir(), 'bscierre');
$zip = new ZipArchive();
if ($tmp === false || $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) bs_error('No se pudo armar el archivo.', 500);
$zip->addFromString('LEEME.txt', $leeme);
$zip->addFromString('para-imprimir.html', $html);
$zip->addFromString('fichas.csv', $f_csv);
$zip->addFromString('contratos.csv', $c_csv);
// En cada carpeta, la ficha de esa obra: se abre sola, con sus fotos, y se imprime o se guarda con la carpeta.
foreach ($fichas as $fi) $zip->addFromString('fichas/' . $fi['carpeta'] . '/ficha.html', $cabeza . $hoja($fi, true) . '</body></html>');
foreach ($quiero as $tipo => $si) {
    if (!$si) continue;
    foreach ($archivos[$tipo] as $a) { if ($zip->addFile($a[1], $a[0]) && method_exists($zip, 'setCompressionName')) $zip->setCompressionName($a[0], ZipArchive::CM_STORE); } // las fotos ya vienen comprimidas
}
// los datos tal cual (sin usuarios)
$n_datos = 0;
foreach ([[BS_CLIENTES_DIR, 'clientes'], [BS_TABLERO_DIR, 'tablero'], [BS_CONTRATOS_DIR, 'contratos']] as $d) {
    foreach (@scandir($d[0]) ?: [] as $f) { if (preg_match('/^[\w.-]+\.json$/', $f) && is_file($d[0] . '/' . $f) && filesize($d[0] . '/' . $f) < 8 * 1048576) { $zip->addFile($d[0] . '/' . $f, 'datos/' . $d[1] . '/' . $f); $n_datos++; } }
}
if (!$zip->close() || !is_file($tmp) || filesize($tmp) === 0) { @unlink($tmp); bs_error('No se pudo cerrar el archivo.', 500); }
if (function_exists('auth_registrar')) auth_registrar('reportes', ['que' => 'cierre del mes ' . $MES, 'fichas' => count($fichas), 'bytes' => filesize($tmp)]);

while (ob_get_level()) ob_end_clean();
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="blackstones-cierre-' . $MES . '.zip"');
header('Content-Length: ' . filesize($tmp));
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
readfile($tmp);
@unlink($tmp);
exit;
