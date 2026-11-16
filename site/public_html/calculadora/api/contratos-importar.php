<?php
// ============================================================
// contratos-importar.php — Pasar la planilla de contratos a Relación-Contrato
// BlackStones Marmolería
// ============================================================
// Carga de una sola vez los contratos de la planilla del equipo, pero SOLO
// los de clientes que hoy tienen su ficha en el tablero. Cada contrato nace
// enlazado con su ficha. Lo que no está en el tablero no se carga.
//
// 1) Subir por FTP el archivo  bs-data/contratos/importar.json  (FUERA de
//    public_html: tiene nombres, teléfonos, direcciones y montos; no va al
//    repo ni queda accesible por web).
// 2) Abrir, con sesión iniciada:
//      https://blackstones.com.ar/calculadora/api/contratos-importar.php
//    Muestra qué fila va con qué ficha y por qué. No toca nada.
// 3) Tildar o destildar y confirmar. Recién ahí se cargan.
//
// Cómo se busca la ficha de cada fila, en este orden:
//   a) por N° de presupuesto (si la obra vino de la calculadora)
//   b) por teléfono (los 10 dígitos)
//   c) por nombre igual
//   d) por nombre parecido ("Marta" con "Marta Ejemplo") → queda SIN tildar:
//      se mira y se tilda a mano
// Una ficha lleva un solo contrato. No se toca el tablero: solo se lee.
//
// Formato de importar.json:
//   { "contratos": [ {
//       "fila": 12, "fecha": "2026-10-15", "cliente": "...", "telefono": "...",
//       "direccion": "...", "vendedor": "Diego", "procedencia": "Alberdi",
//       "nro": "0042", "contrato_paralelo": "0815", "material": "...",
//       "medidas": "...", "total_ars": 0, "total_usd": 0,
//       "factura": "si|no|cf|", "muebles": "si|no|", "bacha": "nuestra|cliente|no|",
//       "bacha_modelo": "...", "nota": "...",
//       "sena":  { "monto": 0, "moneda": "ARS|USD", "fecha": "...", "cotizacion": 0, "aplica": "ARS|USD" },
//       "pagos": [ { "fecha": "...", "monto": 0, "moneda": "ARS|USD", "medio": "", "nota": "", "cotizacion": 0, "aplica": "ARS" } ]
//   } ] }
//
// Al terminar, importar.json se renombra a importar-hecho-<fecha>.json
// para que no se pueda correr dos veces.
// ============================================================

require_once __DIR__ . '/_contratos.php';
require_once __DIR__ . '/_tablero.php';
require_once __DIR__ . '/_auth.php';

if (!auth_is_valid()) {
    header('Location: /calculadora/login.php');
    exit;
}
bs_check_csrf();
bs_ensure_dirs();

date_default_timezone_set('America/Argentina/Buenos_Aires');

define('BS_CONTRATOS_IMPORT', BS_CONTRATOS_DIR . '/importar.json');

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function bs_imp_plata($v, $m = 'ARS') { return ($m === 'USD' ? 'USD ' : '$ ') . number_format((float)$v, (floor($v) == $v) ? 0 : 2, ',', '.'); }

function bs_imp_pagina($titulo, $cuerpo) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="robots" content="noindex, nofollow"><title>' . h($titulo) . '</title><style>'
       . 'body{font-family:system-ui,-apple-system,Segoe UI,sans-serif;background:#FAF8F4;color:#1A1816;margin:0;padding:24px 16px;line-height:1.5}'
       . '.c{max-width:1080px;margin:0 auto}h1{font-size:22px;margin:0 0 14px}h2{font-size:15px;margin:22px 0 8px}'
       . '.caja{background:#fff;border:1px solid #E5DECF;border-radius:10px;padding:14px 16px;margin-bottom:12px}'
       . '.rojo{color:#B03A3A;font-weight:700}.gris{color:#8B7860}.amb{color:#9A6A12;font-weight:600}.num{text-align:right;white-space:nowrap}'
       . 'table{border-collapse:collapse;width:100%;font-size:13px}td,th{text-align:left;padding:7px 8px;border-bottom:1px solid #F0EAD8;vertical-align:top}th{color:#8B7860;font-weight:600}'
       . 'tr.rev td{background:#FDF6E3}input[type=checkbox]{width:18px;height:18px}'
       . 'button{font:inherit;font-weight:700;padding:12px 18px;border-radius:8px;border:none;background:#1A1816;color:#FAF8F4;cursor:pointer}'
       . 'a.btn{display:inline-block;font-weight:700;padding:12px 18px;border-radius:8px;background:#1A1816;color:#FAF8F4;text-decoration:none}'
       . '</style></head><body><div class="c"><h1>' . h($titulo) . '</h1>' . $cuerpo . '</div></body></html>';
    exit;
}

// Nombre sin mayúsculas, acentos ni signos; y sus palabras.
function bs_imp_llano($s) {
    $s = mb_strtolower(trim((string)$s), 'UTF-8');
    $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    return trim(preg_replace('/[^a-z0-9]+/', ' ', $s));
}
function bs_imp_palabras($s) {
    // sin tratamientos ni conectores: "Arq. Laura" y "Laura" son la misma persona
    return array_values(array_diff(array_filter(explode(' ', bs_imp_llano($s))), ['y', 'e', 'de', 'del', 'la', 'sr', 'sra', 'arq', 'ing', 'dr', 'dra']));
}
/** Los 10 dígitos del teléfono, o '' si no se reconoce. */
function bs_imp_tel10($t) {
    $d = preg_replace('/\D/', '', bs_contrato_tel($t));
    return strlen($d) === 10 ? $d : '';
}

// ------------------------------------------------------------
// Leer y validar el archivo
// ------------------------------------------------------------
$plan = bs_read_json(BS_CONTRATOS_IMPORT);
if (!is_array($plan) || empty($plan['contratos']) || !is_array($plan['contratos'])) {
    bs_imp_pagina('Cargar la planilla de contratos', '<div class="caja">No hay nada para cargar. Falta subir el archivo <b>importar.json</b> a <code>bs-data/contratos/</code>, o ya se cargó.</div><p><a class="btn" href="/presupuestos/relacion-contrato.php">Ir a Relación-Contrato</a></p>');
}

$filas = [];
$errores = [];
foreach ($plan['contratos'] as $i => $o) {
    $n = is_array($o) ? intval($o['fila'] ?? ($i + 1)) : $i + 1;
    if (!is_array($o)) { $errores[] = "Fila $n: no es un objeto."; continue; }
    $txt = function ($campo, $max) use ($o) {
        return mb_substr(trim(preg_replace('/\s+/u', ' ', (string)($o[$campo] ?? ''))), 0, $max);
    };
    $monto = function ($v) { return is_numeric($v) ? max(0, round(floatval($v), 2)) : 0; };
    $cobro = function ($x, $quien) use ($monto, &$errores) {
        if (!is_array($x) || $monto($x['monto'] ?? 0) <= 0) return null;
        $mon = (($x['moneda'] ?? '') === 'USD') ? 'USD' : 'ARS';
        $apl = in_array($x['aplica'] ?? '', ['USD', 'ARS'], true) ? $x['aplica'] : $mon;
        $cot = $apl !== $mon ? round(floatval($x['cotizacion'] ?? 0), 2) : 0;
        if ($apl !== $mon && ($cot < 1 || $cot > 1000000)) { $errores[] = "$quien: cobro en otra moneda sin cotización."; return null; }
        $fecha = (string)($x['fecha'] ?? '');
        return ['fecha' => bs_contrato_fecha_ok($fecha) ? $fecha : '', 'monto' => $monto($x['monto']), 'moneda' => $mon, 'cotizacion' => $cot, 'aplica' => $apl,
                'medio' => in_array($x['medio'] ?? '', ['efectivo', 'transferencia'], true) ? $x['medio'] : '',
                'nota' => mb_substr(trim((string)($x['nota'] ?? '')), 0, 200)];
    };
    $f = [
        'fila'      => $n,
        'cliente'   => $txt('cliente', 120),
        'telefono'  => bs_contrato_tel($txt('telefono', 60)),
        'direccion' => $txt('direccion', 200),
        'material'  => $txt('material', 200),
        'medidas'   => $txt('medidas', 300),
        'vendedor'  => $txt('vendedor', 60),
        'procedencia' => $txt('procedencia', 80),
        'contrato_paralelo' => $txt('contrato_paralelo', 80),
        'nro'       => '',
        'fecha'     => (string)($o['fecha'] ?? ''),
        'factura'   => in_array($o['factura'] ?? '', ['si', 'no', 'cf'], true) ? $o['factura'] : '',
        'muebles'   => in_array($o['muebles'] ?? '', ['si', 'no'], true) ? $o['muebles'] : '',
        'bacha'     => in_array($o['bacha'] ?? '', ['nuestra', 'cliente', 'no'], true) ? $o['bacha'] : '',
        'bacha_modelo' => '',
        'total_usd' => $monto($o['total_usd'] ?? 0),
        'total_ars' => $monto($o['total_ars'] ?? 0),
        'nota'      => mb_substr(trim(str_replace(["\r\n", "\r"], "\n", (string)($o['nota'] ?? ''))), 0, 4000),
        'pagos'     => [],
    ];
    if ($f['cliente'] === '') { $errores[] = "Fila $n: falta el nombre del cliente."; continue; }
    if (!bs_contrato_fecha_ok($f['fecha'])) { $errores[] = "Fila $n ({$f['cliente']}): fecha del contrato inválida."; continue; }
    if ($f['bacha'] === 'nuestra') $f['bacha_modelo'] = $txt('bacha_modelo', 80);
    if (preg_match('/^(\d{1,6})(?:-(\d{1,3}))?$/', trim((string)($o['nro'] ?? '')), $m)) {
        $f['nro'] = str_pad((string)intval($m[1]), 4, '0', STR_PAD_LEFT) . (isset($m[2]) ? '-' . intval($m[2]) : '');
    }
    // Vendedor y procedencia: solo los de las listas. Lo que no está, a la nota.
    foreach ([['vendedor', $BS_CONTRATO_VENDEDORES, 'Vendedor'], ['procedencia', $BS_CONTRATO_PROCEDENCIAS, 'Procedencia']] as $r) {
        if ($f[$r[0]] !== '' && !in_array($f[$r[0]], $r[1], true)) {
            $f['nota'] = trim($f['nota'] . "\n" . $r[2] . ' en la planilla: ' . $f[$r[0]]);
            $f[$r[0]] = '';
        }
    }
    $quien = "Fila $n ({$f['cliente']})";
    $s = $cobro($o['sena'] ?? null, $quien);
    if ($s) { if ($s['fecha'] === '') $s['fecha'] = $f['fecha']; unset($s['medio'], $s['nota']); }
    $f['sena'] = $s;
    foreach ((array)($o['pagos'] ?? []) as $p) {
        $p = $cobro($p, $quien);
        if (!$p) continue;
        if ($p['fecha'] === '') $p['fecha'] = $f['fecha'];
        $f['pagos'][] = $p;
    }
    $filas[] = $f;
}

// ------------------------------------------------------------
// Las fichas vivas del tablero (solo lectura) y las que ya tienen contrato
// ------------------------------------------------------------
$tablero = bs_tablero_leer();
$fichas = [];
foreach (@scandir(BS_CLIENTES_DIR) ?: [] as $archivo) {
    if (!preg_match('/^(\d{4,})\.json$/', $archivo, $mm)) continue;
    $cli = bs_read_json(BS_CLIENTES_DIR . '/' . $archivo);
    foreach ((array)($cli['cotizaciones'] ?? []) as $cot) {
        if (($cot['estado'] ?? '') !== 'aprobado') continue;
        $sub = intval($cot['sub'] ?? 0);
        $key = $mm[1] . '-' . $sub;
        $obra = is_array($tablero['obras'][$key] ?? null) ? $tablero['obras'][$key] : [];
        $sena = is_array($obra['sena'] ?? null) && floatval($obra['sena']['monto'] ?? 0) > 0
            ? ['monto' => floatval($obra['sena']['monto']), 'moneda' => (($obra['sena']['moneda'] ?? '') === 'USD') ? 'USD' : 'ARS'] : null;
        $etapa = (string)($obra['etapa'] ?? 'medir');
        $fichas[$key] = [
            'key' => $key, 'cliente_nro' => $mm[1], 'sub' => $sub,
            'nombre' => (string)($cli['cliente']['nombre'] ?? ''),
            'tel10' => bs_imp_tel10($cli['cliente']['celular'] ?? ''),
            'celular' => (string)($cli['cliente']['celular'] ?? ''),
            'manual' => !empty($cot['manual']),
            'etapa' => isset($BS_ETAPAS[$etapa]) ? $BS_ETAPAS[$etapa]['titulo'] : $etapa,
            'sena' => $sena,
        ];
    }
}
$contratos = bs_contratos_leer();
$con_contrato = [];
foreach ($contratos['contratos'] as $c) {
    if (is_array($c['enlace'] ?? null)) $con_contrato[$c['enlace']['cliente_nro'] . '-' . intval($c['enlace']['sub'])] = (string)($c['cliente'] ?? '');
}

// ------------------------------------------------------------
// Emparejar cada fila con su ficha
// ------------------------------------------------------------
// nivel: 1 N° de presupuesto · 2 teléfono · 3 nombre igual · 4 nombre parecido
$NIVEL = [1 => 'N° de presupuesto', 2 => 'Teléfono', 3 => 'Nombre', 4 => 'Nombre parecido'];
$uno = function ($l) { return count($l) === 1 ? $l[0] : null; };
foreach ($filas as $i => $f) {
    $filas[$i]['ficha'] = null; $filas[$i]['nivel'] = 0; $filas[$i]['motivo'] = 'No está en el tablero';
    $pal = bs_imp_palabras($f['cliente']);
    $cand = null; $nivel = 0;
    // a) N° de presupuesto: la obra que vino de ese presupuesto de la calculadora
    if ($f['nro'] !== '') {
        $p = explode('-', $f['nro']);
        $l = [];
        foreach ($fichas as $k => $x) if (!$x['manual'] && $x['cliente_nro'] === $p[0] && (!isset($p[1]) || $x['sub'] === intval($p[1]))) $l[] = $k;
        if (($cand = $uno($l)) !== null) $nivel = 1;
    }
    // b) teléfono
    $t = bs_imp_tel10($f['telefono']);
    if ($cand === null && $t !== '') {
        $l = [];
        foreach ($fichas as $k => $x) if ($x['tel10'] === $t) $l[] = $k;
        if (($cand = $uno($l)) !== null) $nivel = 2;
        elseif (count($l) > 1) $filas[$i]['motivo'] = 'Ese teléfono está en ' . count($l) . ' fichas del tablero (' . implode(', ', $l) . ')';
    }
    // c) nombre igual · d) nombre parecido (todas las palabras del más corto están en el otro)
    if ($cand === null && $pal) {
        $igual = []; $parecido = [];
        foreach ($fichas as $k => $x) {
            $px = bs_imp_palabras($x['nombre']);
            if (!$px) continue;
            if ($px === $pal || (count($px) === count($pal) && !array_diff($px, $pal))) $igual[] = $k;
            elseif (!array_diff($px, $pal) || !array_diff($pal, $px)) $parecido[] = $k;
        }
        if (($cand = $uno($igual)) !== null) $nivel = 3;
        elseif (!$igual && ($cand = $uno($parecido)) !== null) $nivel = 4;
        elseif (count($igual) + count($parecido) > 1 && $filas[$i]['motivo'] === 'No está en el tablero') {
            $filas[$i]['motivo'] = 'Hay ' . (count($igual) + count($parecido)) . ' fichas con ese nombre (' . implode(', ', array_merge($igual, $parecido)) . '): se enlaza a mano';
        }
    }
    if ($cand !== null) { $filas[$i]['ficha'] = $cand; $filas[$i]['nivel'] = $nivel; $filas[$i]['motivo'] = ''; }
}
// Una ficha, un contrato. Si dos filas van a la misma ficha, gana la que la encontró con más
// certeza; a igual certeza, ninguna queda tildada (se elige a mano).
$por_ficha = [];
foreach ($filas as $i => $f) if ($f['ficha'] !== null) $por_ficha[$f['ficha']][] = $i;
foreach ($filas as $i => $f) {
    $filas[$i]['tildada'] = false; $filas[$i]['aviso'] = [];
    if ($f['ficha'] === null) continue;
    if (isset($con_contrato[$f['ficha']])) {
        $filas[$i]['motivo'] = 'Su ficha (' . $f['ficha'] . ') ya tiene contrato';
        $filas[$i]['ficha'] = null;
        continue;
    }
    $otras = array_values(array_diff($por_ficha[$f['ficha']], [$i]));
    $mejor = min(array_map(function ($j) use ($filas) { return $filas[$j]['nivel']; }, $por_ficha[$f['ficha']]));
    $empate = count(array_filter($por_ficha[$f['ficha']], function ($j) use ($filas, $mejor) { return $filas[$j]['nivel'] === $mejor; })) > 1;
    if ($otras) $filas[$i]['aviso'][] = 'La misma ficha también coincide con la fila ' . implode(', ', array_map(function ($j) use ($filas) { return $filas[$j]['fila']; }, $otras)) . ': va una sola';
    $filas[$i]['tildada'] = $f['nivel'] < 4 && $f['nivel'] === $mejor && !$empate;
    $x = $fichas[$f['ficha']];
    if ($x['sena'] && (!$f['sena'] || $f['sena']['moneda'] !== $x['sena']['moneda'] || abs($f['sena']['monto'] - $x['sena']['monto']) > 0.5)) {
        $filas[$i]['aviso'][] = 'En el tablero la seña figura ' . bs_imp_plata($x['sena']['monto'], $x['sena']['moneda']) . ': enlazado, se muestra esa';
    }
}

/** Cuentas de una fila: total, cobrado (lo que entró en cada moneda) y saldo ya convertido. */
function bs_imp_cuentas($f) {
    $pag = ['USD' => 0, 'ARS' => 0]; $apl = ['USD' => 0, 'ARS' => 0];
    foreach (array_merge($f['sena'] ? [$f['sena']] : [], $f['pagos']) as $x) {
        $pag[$x['moneda']] += $x['monto'];
        if ($x['aplica'] !== $x['moneda'] && $x['cotizacion'] > 0) $apl[$x['aplica']] += $x['moneda'] === 'USD' ? $x['monto'] * $x['cotizacion'] : $x['monto'] / $x['cotizacion'];
        else $apl[$x['moneda']] += $x['monto'];
    }
    $j = function ($o) { $l = []; foreach (['USD', 'ARS'] as $m) if (abs($o[$m]) > 0.004) $l[] = bs_imp_plata($o[$m], $m); return $l ? implode(' + ', $l) : '—'; };
    return [$j(['USD' => $f['total_usd'], 'ARS' => $f['total_ars']]), $j($pag), $j(['USD' => round($f['total_usd'] - $apl['USD'], 2), 'ARS' => round($f['total_ars'] - $apl['ARS'], 2)])];
}

$con_ficha = array_values(array_filter($filas, function ($f) { return $f['ficha'] !== null; }));
$sin_ficha = array_values(array_filter($filas, function ($f) { return $f['ficha'] === null; }));

// ============================================================
// GET — vista previa (no toca nada)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $c = '';
    if ($errores) {
        $c .= '<div class="caja"><span class="rojo">El archivo tiene errores. No se puede cargar hasta corregirlo.</span><ul>';
        foreach ($errores as $e) $c .= '<li>' . h($e) . '</li>';
        $c .= '</ul></div>';
    }
    $tildadas = count(array_filter($con_ficha, function ($f) { return $f['tildada']; }));
    $c .= '<div class="caja">La planilla trae <b>' . count($filas) . '</b> contratos y en el tablero hay <b>' . count($fichas) . '</b> fichas.'
        . '<br><b>' . count($con_ficha) . '</b> tienen su ficha en el tablero: ' . $tildadas . ' ya tildados' . (count($con_ficha) - $tildadas ? ' y <span class="amb">' . (count($con_ficha) - $tildadas) . ' para revisar</span> (marcados en amarillo, sin tildar)' : '') . '.'
        . '<br><b>' . count($sin_ficha) . '</b> no se cargan (abajo dice por qué).'
        . '<br><span class="gris">Se cargan solo los tildados. Cada contrato queda enlazado con su ficha. En el tablero no se cambia nada.</span></div>';

    $c .= '<form method="post">';
    $c .= '<h2>Con ficha en el tablero</h2><div class="caja" style="overflow-x:auto"><table><tr><th></th><th>Planilla</th><th>Ficha del tablero</th><th>Coincide por</th><th class="num">Total</th><th class="num">Cobrado</th><th class="num">Saldo</th></tr>';
    foreach ($con_ficha as $f) {
        $x = $fichas[$f['ficha']];
        list($tot, $cob, $sal) = bs_imp_cuentas($f);
        $c .= '<tr' . ($f['tildada'] ? '' : ' class="rev"') . '><td><input type="checkbox" name="filas[]" value="' . intval($f['fila']) . '"' . ($f['tildada'] ? ' checked' : '') . ' aria-label="Cargar la fila ' . intval($f['fila']) . '"></td>'
            . '<td><b>' . h($f['cliente']) . '</b><br><span class="gris">' . h($f['telefono']) . ($f['telefono'] !== '' && $f['direccion'] !== '' ? ' · ' : '') . h($f['direccion']) . '<br>Fila ' . intval($f['fila']) . ' · ' . h($f['fecha']) . ($f['nro'] !== '' ? ' · N° ' . h($f['nro']) : '') . '</span></td>'
            . '<td><b>' . h($x['nombre']) . '</b><br><span class="gris">' . h($x['key']) . ' · ' . h($x['etapa']) . ($x['celular'] !== '' ? '<br>' . h(bs_contrato_tel($x['celular'])) : '') . '</span></td>'
            . '<td>' . ($f['nivel'] === 4 ? '<span class="amb">Nombre parecido: revisar</span>' : h($NIVEL[$f['nivel']]));
        foreach ($f['aviso'] as $a) $c .= '<br><span class="amb">' . h($a) . '</span>';
        $c .= '</td><td class="num">' . h($tot) . '</td><td class="num">' . h($cob) . '</td><td class="num">' . h($sal) . '</td></tr>';
    }
    if (!$con_ficha) $c .= '<tr><td colspan="7" class="gris">Ninguna fila de la planilla coincide con una ficha del tablero.</td></tr>';
    $c .= '</table></div>';

    if ($con_ficha && !$errores) {
        $c .= '<div class="caja"><label><input type="checkbox" name="confirmo" value="SI" required> Revisé la lista y quiero cargar los contratos tildados.</label><br><br>'
            . '<button type="submit">Cargar los tildados</button> &nbsp; <a href="/presupuestos/relacion-contrato.php">Cancelar</a></div>';
    }
    $c .= '</form>';

    $c .= '<h2>No se cargan (' . count($sin_ficha) . ')</h2><div class="caja" style="overflow-x:auto"><table><tr><th>Planilla</th><th>Por qué</th></tr>';
    foreach ($sin_ficha as $f) {
        $c .= '<tr><td><b>' . h($f['cliente']) . '</b> <span class="gris">· fila ' . intval($f['fila']) . ' · ' . h($f['fecha']) . ($f['telefono'] !== '' ? ' · ' . h($f['telefono']) : '') . '</span></td><td>' . h($f['motivo']) . '</td></tr>';
    }
    if (!$sin_ficha) $c .= '<tr><td colspan="2" class="gris">Todas las filas tienen su ficha.</td></tr>';
    $c .= '</table></div>';

    // Fichas del tablero que la planilla no nombra: después de cargar, siguen sin contrato.
    $tomadas = [];
    foreach ($con_ficha as $f) $tomadas[$f['ficha']] = true;
    $huerfanas = array_filter($fichas, function ($x) use ($tomadas, $con_contrato) { return !isset($tomadas[$x['key']]) && !isset($con_contrato[$x['key']]); });
    if ($huerfanas) {
        $c .= '<h2>Fichas del tablero que no están en la planilla (' . count($huerfanas) . ')</h2><div class="caja"><span class="gris">Quedan sin contrato. Después se les crea desde Relación-Contrato, en "Obras en el tablero sin contrato".</span><br>'
            . h(implode(' · ', array_map(function ($x) { return $x['nombre'] . ' (' . $x['key'] . ')'; }, $huerfanas))) . '</div>';
    }
    bs_imp_pagina('Cargar la planilla de contratos', $c);
}

// ============================================================
// POST — cargar los tildados
// ============================================================
if ($errores) bs_imp_pagina('Cargar la planilla de contratos', '<div class="caja rojo">El archivo tiene errores. No se cargó nada.</div>');
if (($_POST['confirmo'] ?? '') !== 'SI') bs_imp_pagina('Cargar la planilla de contratos', '<div class="caja rojo">Falta tildar la confirmación. No se cargó nada.</div><p><a class="btn" href="">Volver</a></p>');
$elegidas = array_map('intval', (array)($_POST['filas'] ?? []));
if (!$elegidas) bs_imp_pagina('Cargar la planilla de contratos', '<div class="caja rojo">No hay ninguna fila tildada. No se cargó nada.</div><p><a class="btn" href="">Volver</a></p>');

if (!is_dir(BS_CONTRATOS_DIR) && !@mkdir(BS_CONTRATOS_DIR, 0755, true)) bs_imp_pagina('Cargar la planilla de contratos', '<div class="caja rojo">No se pudo crear la carpeta de contratos.</div>');
$fp = bs_lock_acquire(BS_CONTRATOS_LOCK);
if (!$fp) bs_imp_pagina('Cargar la planilla de contratos', '<div class="caja rojo">No se pudo tomar el control del archivo. Probá de nuevo.</div>');

try {
    $d = bs_contratos_leer();
    $ya = [];
    foreach ($d['contratos'] as $c) {
        if (is_array($c['enlace'] ?? null)) $ya[$c['enlace']['cliente_nro'] . '-' . intval($c['enlace']['sub'])] = true;
    }
    $ahora = date('c');
    $base = time() - count($filas);
    $cargados = []; $salteados = [];
    foreach ($filas as $i => $f) {
        if ($f['ficha'] === null || !in_array($f['fila'], $elegidas, true)) continue;
        if (isset($ya[$f['ficha']])) { $salteados[] = $f['cliente'] . ' (fila ' . $f['fila'] . '): la ficha ' . $f['ficha'] . ' ya quedó con otro contrato'; continue; }
        if (count($d['contratos']) >= BS_CONTRATOS_MAX) throw new Exception('Se llegó al máximo de contratos.');
        $x = $fichas[$f['ficha']];
        $id = 'c' . date('ymdHis') . bin2hex(random_bytes(3)) . dechex($i);
        $pagos = [];
        foreach ($f['pagos'] as $p) $pagos[] = array_merge(['id' => 'p' . bin2hex(random_bytes(4))], $p);
        $d['contratos'][$id] = [
            'id' => $id,
            // 'creado' escalonado: a igual fecha, la lista respeta el orden de la planilla
            'creado' => date('c', $base + $i), 'actualizado' => $ahora,
            'fecha' => $f['fecha'], 'cliente' => $f['cliente'], 'telefono' => $f['telefono'], 'direccion' => $f['direccion'],
            'vendedor' => $f['vendedor'], 'procedencia' => $f['procedencia'], 'contrato' => '', 'contrato_paralelo' => $f['contrato_paralelo'],
            'nro' => $f['nro'], 'material' => $f['material'], 'medidas' => $f['medidas'],
            'factura' => $f['factura'], 'muebles' => $f['muebles'], 'bacha' => $f['bacha'], 'bacha_modelo' => $f['bacha_modelo'],
            'medicion' => '', 'total_usd' => $f['total_usd'], 'total_ars' => $f['total_ars'],
            'sena' => $f['sena'], 'pagos' => $pagos, 'nota' => $f['nota'], 'prueba' => false,
            'enlace' => ['cliente_nro' => $x['cliente_nro'], 'sub' => $x['sub'], 'at' => $ahora],
            'importado' => ['at' => $ahora, 'fila' => $f['fila']],
        ];
        $ya[$f['ficha']] = true;
        $cargados[] = $f['cliente'];
    }
    if ($cargados && !bs_write_json_atomic(BS_CONTRATOS_FILE, $d)) throw new Exception('No se pudo guardar');

    // Que no se pueda correr dos veces
    if ($cargados) @rename(BS_CONTRATOS_IMPORT, BS_CONTRATOS_DIR . '/importar-hecho-' . date('Ymd-His') . '.json');
    bs_lock_release($fp);

    $c = '<div class="caja"><b>Listo.</b> Se cargaron ' . count($cargados) . ' contratos, cada uno enlazado con su ficha del tablero.';
    if ($salteados) {
        $c .= '<br><span class="amb">No se cargaron:</span><ul>';
        foreach ($salteados as $s) $c .= '<li>' . h($s) . '</li>';
        $c .= '</ul>';
    }
    $c .= '</div><p><a class="btn" href="/presupuestos/relacion-contrato.php">Ir a Relación-Contrato</a></p>';
    bs_imp_pagina('Planilla cargada', $c);

} catch (Throwable $e) {
    bs_lock_release($fp);
    bs_imp_pagina('Cargar la planilla de contratos', '<div class="caja rojo">Se interrumpió: ' . h($e->getMessage()) . '</div><p>No se cargó nada. Avisá antes de volver a correrlo.</p><p><a class="btn" href="/presupuestos/relacion-contrato.php">Ir a Relación-Contrato</a></p>');
}
