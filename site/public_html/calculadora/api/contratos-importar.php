<?php
// ============================================================
// contratos-importar.php — Pasar la planilla de contratos a Relación-Contrato
// BlackStones Marmolería
// ============================================================
// Se parte del TABLERO: por cada ficha viva se arma su contrato, con los
// datos de la planilla del equipo cuando esa ficha está en la planilla.
// Las filas de la planilla que no tienen ficha en el tablero no se cargan.
// Cada contrato nace enlazado con su ficha.
//
// 1) Subir por FTP el archivo  bs-data/contratos/importar.json  (FUERA de
//    public_html: tiene nombres, teléfonos, direcciones y montos; no va al
//    repo ni queda accesible por web).
// 2) Abrir, con sesión iniciada:
//      https://blackstones.com.ar/calculadora/api/contratos-importar.php
//    Muestra, ficha por ficha, qué contrato le toca y por qué. No toca nada.
// 3) Confirmar. Recién ahí se cargan.
//
// Cómo se busca en la planilla el contrato de cada ficha:
//   a) por N° de presupuesto (si la obra vino de la calculadora)
//   b) por teléfono (los 10 dígitos)
//   c) por nombre igual
//   d) por nombre parecido ("Marta" con "Marta Ejemplo")
// Las dudas las resuelve la FECHA DE MEDICIÓN de la ficha: el contrato se
// firma cerca de la medición (entre 7 días después y 35 días antes). Si hay
// dos filas posibles, va la de fecha más cercana; un nombre parecido solo se
// acepta si la fecha acompaña. Lo que sigue en duda queda SIN tildar.
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
// Al terminar, importar.json se BORRA del servidor: las filas que no tenían
// ficha no quedan cargadas en ningún lado, y no se puede correr dos veces.
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
    bs_imp_pagina('Contratos de las fichas del tablero', '<div class="caja">No hay nada para cargar. Falta subir el archivo <b>importar.json</b> a <code>bs-data/contratos/</code>, o ya se cargó.</div><p><a class="btn" href="/presupuestos/relacion-contrato.php">Ir a Relación-Contrato</a></p>');
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
            'direccion' => (string)($cli['cliente']['direccion'] ?? ''),
            'material' => (string)($cot['material_final'] ?? ''),
            'bacha' => in_array($obra['bacha'] ?? '', ['nuestra', 'cliente', 'no'], true) ? $obra['bacha'] : '',
            'bacha_modelo' => (string)($obra['bacha_modelo'] ?? ''),
            // Cuándo se midió; si todavía no, el turno de medición que tiene dado
            'medicion' => bs_tablero_fecha_valida($obra['medido_at'] ?? '') ? $obra['medido_at']
                : (($etapa === 'medir' && bs_tablero_fecha_valida($obra['fecha'] ?? '')) ? $obra['fecha'] : ''),
        ];
    }
}
$contratos = bs_contratos_leer();
$con_contrato = [];
foreach ($contratos['contratos'] as $c) {
    if (is_array($c['enlace'] ?? null)) $con_contrato[$c['enlace']['cliente_nro'] . '-' . intval($c['enlace']['sub'])] = (string)($c['cliente'] ?? '');
}

// ------------------------------------------------------------
// A cada ficha, su fila de la planilla
// ------------------------------------------------------------
// nivel: 1 N° de presupuesto · 2 teléfono · 3 nombre igual · 4 nombre parecido
$NIVEL = [1 => 'N° de presupuesto', 2 => 'Teléfono', 3 => 'Nombre', 4 => 'Nombre parecido'];
// El contrato se firma cerca de la medición: hasta 35 días antes y hasta 7 después
// (en la planilla de oct-nov 2026, los que coinciden por teléfono van de 29 antes a 4 después).
define('BS_IMP_ANTES', 35);
define('BS_IMP_DESPUES', 7);
function bs_imp_dm($f) { return preg_match('/^\d{4}-(\d{2})-(\d{2})$/', (string)$f, $m) ? $m[2] . '/' . $m[1] : ''; }

$pares = [];
foreach ($fichas as $k => $x) {
    if (isset($con_contrato[$k])) continue;
    $px = bs_imp_palabras($x['nombre']);
    foreach ($filas as $i => $f) {
        $t = bs_imp_tel10($f['telefono']);
        $pf = bs_imp_palabras($f['cliente']);
        $nivel = 0;
        if ($f['nro'] !== '' && !$x['manual']) {
            $p = explode('-', $f['nro']);
            if ($x['cliente_nro'] === $p[0] && (!isset($p[1]) || $x['sub'] === intval($p[1]))) $nivel = 1;
        }
        if (!$nivel && $t !== '' && $t === $x['tel10']) $nivel = 2;
        if (!$nivel && $px && $pf) {
            if (count($px) === count($pf) && !array_diff($px, $pf)) $nivel = 3;
            elseif (!array_diff($px, $pf) || !array_diff($pf, $px)) $nivel = 4;
        }
        if (!$nivel) continue;
        // por nombre, con los dos teléfonos a la vista y distintos: puede ser otra persona
        $tel_dist = $nivel >= 3 && $t !== '' && $x['tel10'] !== '' && $t !== $x['tel10'];
        // días entre el contrato y la medición (positivo: el contrato es anterior)
        $dias = $x['medicion'] !== '' ? intval(round((strtotime($x['medicion']) - strtotime($f['fecha'])) / 86400)) : null;
        $en_fecha = $dias !== null && $dias >= -BS_IMP_DESPUES && $dias <= BS_IMP_ANTES;
        $pares[] = ['k' => $k, 'i' => $i, 'nivel' => $nivel, 'tel_dist' => $tel_dist, 'dias' => $dias, 'en_fecha' => $en_fecha,
                    // mejor = número más chico: primero la certeza, después que la fecha acompañe
                    'rango' => $nivel * 10 + ($tel_dist ? 5 : 0) + ($en_fecha ? 0 : ($dias === null ? 1 : 2)),
                    'lejos' => $dias === null ? 9999 : abs($dias)];
    }
}
usort($pares, function ($a, $b) { return ($a['rango'] <=> $b['rango']) ?: ($a['lejos'] <=> $b['lejos']) ?: ($a['i'] <=> $b['i']); });
$de_ficha = []; $de_fila = [];
foreach ($pares as $n => $p) {
    if (isset($de_ficha[$p['k']]) || isset($de_fila[$p['i']])) continue;
    $de_ficha[$p['k']] = $n; $de_fila[$p['i']] = $n;
}

// Una línea por ficha del tablero: su fila (si la hay), por qué, y si queda tildada.
$lineas = [];
$en_disputa = []; // fichas que compiten por una fila que quedó en duda
foreach ($fichas as $k => $x) {
    if (isset($con_contrato[$k])) continue;
    $l = ['ficha' => $x, 'fila' => null, 'por' => '', 'aviso' => [], 'tildada' => true, 'nivel' => 0];
    if (isset($de_ficha[$k])) {
        $p = $pares[$de_ficha[$k]];
        $f = $filas[$p['i']];
        // ¿Había otra fila (u otra ficha) igual de posible? Solo cuenta si quedó libre o peor ubicada.
        $rival = [];
        foreach ($pares as $n => $q) {
            if ($n === $de_ficha[$k] || ($q['k'] !== $k && $q['i'] !== $p['i'])) continue;
            if ($q['rango'] !== $p['rango'] || abs($q['lejos'] - $p['lejos']) >= 3) continue;
            $otro_n = $q['k'] === $k ? ($de_fila[$q['i']] ?? null) : ($de_ficha[$q['k']] ?? null);
            if ($otro_n !== null && $pares[$otro_n]['rango'] < $q['rango']) continue;
            if ($q['k'] !== $k) $en_disputa[$q['k']] = 'Podría ser la fila ' . $filas[$p['i']]['fila'] . ' (' . $filas[$p['i']]['cliente'] . '), que quedó en duda con la ficha ' . $k;
            $rival[] = $q['k'] === $k ? 'la fila ' . $filas[$q['i']]['fila'] . ' (' . $filas[$q['i']]['cliente'] . ')' : 'la ficha ' . $q['k'] . ' (' . $fichas[$q['k']]['nombre'] . ')';
        }
        $l['fila'] = $f; $l['nivel'] = $p['nivel'];
        $l['por'] = $NIVEL[$p['nivel']];
        if ($p['dias'] !== null) {
            $l['por'] .= ' · contrato ' . ($p['dias'] === 0 ? 'el día de la medición' : (abs($p['dias']) === 1 ? '1 día ' : abs($p['dias']) . ' días ') . ($p['dias'] > 0 ? 'antes de la medición' : 'después de la medición'));
        }
        if ($rival) { $l['tildada'] = false; $l['aviso'][] = 'También podría ser ' . implode(' o ', array_unique($rival)) . ': la fecha de medición no alcanza para decidir'; }
        if ($p['nivel'] === 3) {
            if ($p['tel_dist'] && !$p['en_fecha']) { $l['tildada'] = false; $l['aviso'][] = 'El teléfono es otro y la fecha de medición no lo confirma'; }
            elseif ($p['tel_dist']) $l['aviso'][] = 'El teléfono es otro; lo confirma la fecha de medición';
        } elseif ($p['nivel'] === 4) {
            // un nombre parecido solo se acepta si la fecha de medición acompaña y el teléfono no lo contradice
            if ($p['dias'] === null) { $l['tildada'] = false; $l['aviso'][] = 'La ficha no tiene fecha de medición para confirmarlo'; }
            elseif (!$p['en_fecha']) { $l['tildada'] = false; $l['aviso'][] = 'La fecha del contrato queda lejos de la medición'; }
            elseif ($p['tel_dist']) { $l['tildada'] = false; $l['aviso'][] = 'La fecha acompaña, pero el teléfono es otro'; }
            else $l['aviso'][] = 'Lo confirma la fecha de medición';
        } elseif ($p['dias'] !== null && !$p['en_fecha']) {
            $l['aviso'][] = 'La fecha del contrato queda lejos de la medición (se carga igual: coincide el ' . ($p['nivel'] === 1 ? 'N°' : 'teléfono') . ')';
        }
        if ($x['sena'] && (!$f['sena'] || $f['sena']['moneda'] !== $x['sena']['moneda'] || abs($f['sena']['monto'] - $x['sena']['monto']) > 0.5)) {
            $l['aviso'][] = 'En el tablero la seña figura ' . bs_imp_plata($x['sena']['monto'], $x['sena']['moneda']) . ': enlazado, se muestra esa';
        }
    } else {
        // No está en la planilla: el contrato se arma con lo que tiene la ficha.
        $l['por'] = 'No está en la planilla';
        $l['fila'] = [
            'fila' => 0, 'cliente' => $x['nombre'], 'telefono' => bs_contrato_tel($x['celular']), 'direccion' => $x['direccion'],
            'material' => $x['material'], 'medidas' => '', 'vendedor' => '', 'procedencia' => '', 'contrato_paralelo' => '',
            'nro' => $x['manual'] ? '' : $x['cliente_nro'] . ($x['sub'] > 1 ? '-' . $x['sub'] : ''),
            'fecha' => $x['medicion'] !== '' ? $x['medicion'] : date('Y-m-d'),
            'factura' => '', 'muebles' => '', 'bacha' => $x['bacha'], 'bacha_modelo' => $x['bacha'] === 'nuestra' ? $x['bacha_modelo'] : '',
            'total_usd' => 0, 'total_ars' => 0,
            'sena' => $x['sena'] ? ['fecha' => date('Y-m-d'), 'monto' => $x['sena']['monto'], 'moneda' => $x['sena']['moneda'], 'cotizacion' => 0, 'aplica' => $x['sena']['moneda']] : null,
            'pagos' => [],
            'nota' => 'Creado desde la ficha del tablero: no estaba en la planilla. Falta el total.' . ($x['medicion'] !== '' ? ' La fecha es la de la medición.' : ''),
        ];
    }
    $lineas[$k] = $l;
}
// Una ficha sin fila propia que compite por una fila en duda tampoco se tilda sola.
foreach ($en_disputa as $k => $txt) {
    if (isset($lineas[$k]) && $lineas[$k]['nivel'] === 0) { $lineas[$k]['tildada'] = false; $lineas[$k]['aviso'][] = $txt; }
}
// Primero lo que hay que mirar; después por nombre.
uasort($lineas, function ($a, $b) {
    return ($a['tildada'] <=> $b['tildada']) ?: (($a['nivel'] === 0) <=> ($b['nivel'] === 0)) ?: strcmp(bs_imp_llano($a['ficha']['nombre']), bs_imp_llano($b['ficha']['nombre']));
});
$sin_ficha = [];
foreach ($filas as $i => $f) if (!isset($de_fila[$i])) $sin_ficha[] = $f;

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
    $con = count(array_filter($lineas, function ($l) { return $l['nivel'] > 0; }));
    $dudas = count(array_filter($lineas, function ($l) { return !$l['tildada']; }));
    $c .= '<div class="caja">En el tablero hay <b>' . count($fichas) . '</b> fichas' . ($con_contrato ? ' (' . count($con_contrato) . ' ya tienen contrato y no se tocan)' : '') . '. Cada una recibe su contrato:'
        . '<br><b>' . $con . '</b> con los datos de la planilla' . ($dudas ? ', de las cuales <span class="amb">' . $dudas . ' quedan en duda</span> (en amarillo y sin tildar: miralas y tildá las que estén bien)' : '') . '.'
        . '<br><b>' . (count($lineas) - $con) . '</b> no están en la planilla: el contrato se arma con lo que tiene la ficha, y el total se carga después.'
        . '<br><span class="gris">Se cargan solo las tildadas. Cada contrato queda enlazado con su ficha. En el tablero no se cambia nada. De la planilla no se carga ninguna fila que no tenga ficha.</span></div>';

    $c .= '<form method="post">';
    $c .= '<h2>Fichas del tablero</h2><div class="caja" style="overflow-x:auto"><table><tr><th></th><th>Ficha del tablero</th><th>Contrato en la planilla</th><th>Cómo se encontró</th><th class="num">Total</th><th class="num">Cobrado</th><th class="num">Saldo</th></tr>';
    foreach ($lineas as $k => $l) {
        $x = $l['ficha']; $f = $l['fila'];
        list($tot, $cob, $sal) = bs_imp_cuentas($f);
        $c .= '<tr' . ($l['tildada'] ? '' : ' class="rev"') . '><td><input type="checkbox" name="fichas[]" value="' . h($k) . '"' . ($l['tildada'] ? ' checked' : '') . ' aria-label="Cargar el contrato de la ficha ' . h($k) . '"></td>'
            . '<td><b>' . h($x['nombre']) . '</b><br><span class="gris">' . h($k) . ' · ' . h($x['etapa']) . ($x['medicion'] !== '' ? ' · medición ' . h(bs_imp_dm($x['medicion'])) : ' · sin fecha de medición') . ($x['celular'] !== '' ? '<br>' . h(bs_contrato_tel($x['celular'])) : '') . '</span></td>';
        if ($l['nivel'] > 0) {
            $c .= '<td><b>' . h($f['cliente']) . '</b><br><span class="gris">Fila ' . intval($f['fila']) . ' · contrato del ' . h(bs_imp_dm($f['fecha'])) . ($f['nro'] !== '' ? ' · N° ' . h($f['nro']) : '') . ($f['telefono'] !== '' ? '<br>' . h($f['telefono']) : '') . ($f['direccion'] !== '' ? '<br>' . h($f['direccion']) : '') . '</span></td>';
        } else {
            $c .= '<td class="gris">No está en la planilla.<br>Se arma con los datos de la ficha.</td>';
        }
        $c .= '<td>' . ($l['nivel'] > 0 ? h($l['por']) : ($l['aviso'] ? '' : '—'));
        foreach ($l['aviso'] as $n => $a) $c .= ($n || $l['nivel'] > 0 ? '<br>' : '') . '<span class="amb">' . h($a) . '</span>';
        $c .= '</td><td class="num">' . h($tot) . '</td><td class="num">' . h($cob) . '</td><td class="num">' . h($sal) . '</td></tr>';
    }
    if (!$lineas) $c .= '<tr><td colspan="7" class="gris">Todas las fichas del tablero ya tienen su contrato.</td></tr>';
    $c .= '</table></div>';

    if ($lineas && !$errores) {
        $c .= '<div class="caja"><label><input type="checkbox" name="confirmo" value="SI" required> Revisé la lista y quiero cargar los contratos de las fichas tildadas.</label><br><br>'
            . '<button type="submit">Cargar los tildados</button> &nbsp; <a href="/presupuestos/relacion-contrato.php">Cancelar</a></div>';
    }
    $c .= '</form>';

    $c .= '<h2>Filas de la planilla sin ficha en el tablero: no se cargan (' . count($sin_ficha) . ')</h2><div class="caja"><span class="gris">'
        . h(implode(' · ', array_map(function ($f) { return $f['cliente'] . ' (fila ' . $f['fila'] . ', ' . bs_imp_dm($f['fecha']) . ')'; }, $sin_ficha)) ?: 'Ninguna.') . '</span></div>';
    bs_imp_pagina('Contratos de las fichas del tablero', $c);
}

// ============================================================
// POST — cargar los tildados
// ============================================================
$T = 'Contratos de las fichas del tablero';
if ($errores) bs_imp_pagina($T, '<div class="caja rojo">El archivo tiene errores. No se cargó nada.</div>');
if (($_POST['confirmo'] ?? '') !== 'SI') bs_imp_pagina($T, '<div class="caja rojo">Falta tildar la confirmación. No se cargó nada.</div><p><a class="btn" href="">Volver</a></p>');
$elegidas = array_map('strval', (array)($_POST['fichas'] ?? []));
if (!$elegidas) bs_imp_pagina($T, '<div class="caja rojo">No hay ninguna ficha tildada. No se cargó nada.</div><p><a class="btn" href="">Volver</a></p>');

if (!is_dir(BS_CONTRATOS_DIR) && !@mkdir(BS_CONTRATOS_DIR, 0755, true)) bs_imp_pagina($T, '<div class="caja rojo">No se pudo crear la carpeta de contratos.</div>');
$fp = bs_lock_acquire(BS_CONTRATOS_LOCK);
if (!$fp) bs_imp_pagina($T, '<div class="caja rojo">No se pudo tomar el control del archivo. Probá de nuevo.</div>');

try {
    $d = bs_contratos_leer();
    $ya = [];
    foreach ($d['contratos'] as $c) {
        if (is_array($c['enlace'] ?? null)) $ya[$c['enlace']['cliente_nro'] . '-' . intval($c['enlace']['sub'])] = true;
    }
    $ahora = date('c');
    $base = time() - count($filas) - 1;
    $con_planilla = 0; $solo_ficha = 0; $salteados = [];
    $n = 0;
    foreach ($lineas as $k => $l) {
        $n++;
        if (!in_array((string)$k, $elegidas, true)) continue;
        $x = $l['ficha']; $f = $l['fila'];
        if (isset($ya[$k])) { $salteados[] = $x['nombre'] . ' (' . $k . '): ya tenía contrato'; continue; }
        if (count($d['contratos']) >= BS_CONTRATOS_MAX) throw new Exception('Se llegó al máximo de contratos.');
        $id = 'c' . date('ymdHis') . bin2hex(random_bytes(3)) . dechex($n);
        $pagos = [];
        foreach ($f['pagos'] as $p) $pagos[] = array_merge(['id' => 'p' . bin2hex(random_bytes(4))], $p);
        $d['contratos'][$id] = [
            'id' => $id,
            // 'creado' escalonado: a igual fecha, la lista respeta el orden de la planilla
            'creado' => date('c', $base + intval($f['fila'])), 'actualizado' => $ahora,
            'fecha' => $f['fecha'], 'cliente' => $f['cliente'], 'telefono' => $f['telefono'], 'direccion' => $f['direccion'],
            'vendedor' => $f['vendedor'], 'procedencia' => $f['procedencia'], 'contrato' => '', 'contrato_paralelo' => $f['contrato_paralelo'],
            'nro' => $f['nro'], 'material' => $f['material'], 'medidas' => $f['medidas'],
            'factura' => $f['factura'], 'muebles' => $f['muebles'], 'bacha' => $f['bacha'], 'bacha_modelo' => $f['bacha_modelo'],
            'medicion' => '', 'total_usd' => $f['total_usd'], 'total_ars' => $f['total_ars'],
            'sena' => $f['sena'], 'pagos' => $pagos, 'nota' => $f['nota'], 'prueba' => false,
            'enlace' => ['cliente_nro' => $x['cliente_nro'], 'sub' => $x['sub'], 'at' => $ahora],
            'importado' => ['at' => $ahora, 'fila' => intval($f['fila'])],
        ];
        $ya[$k] = true;
        if ($l['nivel'] > 0) $con_planilla++; else $solo_ficha++;
    }
    $cargados = $con_planilla + $solo_ficha;
    if ($cargados && !bs_write_json_atomic(BS_CONTRATOS_FILE, $d)) throw new Exception('No se pudo guardar');

    // La planilla subida se borra: lo que no tenía ficha no queda cargado en ningún lado,
    // y la carga no se puede correr dos veces.
    if ($cargados) @unlink(BS_CONTRATOS_IMPORT);
    bs_lock_release($fp);

    $c = '<div class="caja"><b>Listo.</b> Se cargaron ' . $cargados . ' contratos, cada uno enlazado con su ficha del tablero: ' . $con_planilla . ' con los datos de la planilla y ' . $solo_ficha . ' solo con los de la ficha (a esos les falta el total).';
    $faltan = count($lineas) - $cargados - count($salteados);
    if ($faltan > 0) $c .= '<br>' . $faltan . ' fichas quedaron sin contrato. Se les crea desde Relación-Contrato, en "Obras en el tablero sin contrato".';
    if ($salteados) {
        $c .= '<br><span class="amb">No se cargaron:</span><ul>';
        foreach ($salteados as $s) $c .= '<li>' . h($s) . '</li>';
        $c .= '</ul>';
    }
    $c .= '</div><p><a class="btn" href="/presupuestos/relacion-contrato.php">Ir a Relación-Contrato</a></p>';
    bs_imp_pagina('Contratos cargados', $c);

} catch (Throwable $e) {
    bs_lock_release($fp);
    bs_imp_pagina($T, '<div class="caja rojo">Se interrumpió: ' . h($e->getMessage()) . '</div><p>No se cargó nada. Avisá antes de volver a correrlo.</p><p><a class="btn" href="/presupuestos/relacion-contrato.php">Ir a Relación-Contrato</a></p>');
}
