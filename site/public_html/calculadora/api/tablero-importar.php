<?php
// ============================================================
// tablero-importar.php — Carga masiva de fichas al tablero
// BlackStones Marmolería · Registry de Presupuestos
// ============================================================
// Para pasar al tablero una planilla entera de obras de una sola vez.
//
// 1) Subir por FTP el archivo  bs-data/tablero/importar.json  (FUERA de
//    public_html: tiene nombres, teléfonos y direcciones de clientes; no
//    va al repo ni queda accesible por web).
// 2) Abrir, con sesión iniciada:
//      https://blackstones.com.ar/calculadora/api/tablero-importar.php
//    Muestra qué hay hoy en el tablero y qué se va a cargar. No toca nada.
// 3) Confirmar con el botón. Recién ahí se ejecuta.
//
// Formato de importar.json:
//   {
//     "borrar_todo": true,      // antes de cargar, vaciar el tablero
//     "obras": [
//       { "nombre": "...", "celular": "...", "direccion": "...",
//         "material": "...", "concepto": "...",
//         "etapa": "medir" | "fabricar" | "instalar" | "archivar" | "retiro",
//         "fecha": "2026-11-14", "hora": "09:30",   // turno de esa etapa
//         "medido_at": "2026-10-24",                // día en que se midió
//         "checks": ["medido", "medida"],           // tildes ya cumplidos
//         "bacha": "nuestra" | "cliente" | "no" | "",
//         "nota": "..." }
//     ]
//   }
//
// "borrar_todo" hace lo mismo que "Borrar ficha", para todas:
//   - obra cargada a mano  → se borra completa
//   - obra de un presupuesto de la calculadora → el presupuesto NO se
//     borra, vuelve a "enviado"
//   y vacía tildes, datos y adjuntos del tablero.
//
// Cada obra importada se crea como presupuesto manual aprobado (igual que
// "Cargar obra"), con importe en 0.
//
// Al terminar, importar.json se renombra a importar-hecho-<fecha>.json
// para que no se pueda correr dos veces.
// ============================================================

require_once __DIR__ . '/_tablero.php';
require_once __DIR__ . '/_auth.php';

auth_exigir_pagina('mantenimiento');
bs_check_csrf();
bs_ensure_dirs();

date_default_timezone_set('America/Argentina/Buenos_Aires');

define('BS_TABLERO_IMPORT', BS_TABLERO_DIR . '/importar.json');

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function bs_imp_pagina($titulo, $cuerpo) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="robots" content="noindex, nofollow"><title>' . h($titulo) . '</title><style>'
       . 'body{font-family:system-ui,-apple-system,Segoe UI,sans-serif;background:#FAF8F4;color:#1A1816;margin:0;padding:24px 16px;line-height:1.5}'
       . '.c{max-width:860px;margin:0 auto}h1{font-size:22px;margin:0 0 14px}h2{font-size:15px;margin:22px 0 8px}'
       . '.caja{background:#fff;border:1px solid #E5DECF;border-radius:10px;padding:14px 16px;margin-bottom:12px}'
       . '.rojo{color:#B03A3A;font-weight:700}.gris{color:#8B7860}'
       . 'table{border-collapse:collapse;width:100%;font-size:13px}td,th{text-align:left;padding:6px 8px;border-bottom:1px solid #F0EAD8;vertical-align:top}th{color:#8B7860;font-weight:600}'
       . 'button{font:inherit;font-weight:700;padding:12px 18px;border-radius:8px;border:none;background:#B03A3A;color:#fff;cursor:pointer}'
       . 'a.btn{display:inline-block;font-weight:700;padding:12px 18px;border-radius:8px;background:#1A1816;color:#FAF8F4;text-decoration:none}'
       . 'label{display:block;margin:10px 0}</style></head><body><div class="c"><h1>' . h($titulo) . '</h1>' . $cuerpo . '</div></body></html>';
    exit;
}

// ------------------------------------------------------------
// Leer y validar el archivo
// ------------------------------------------------------------
$plan = bs_read_json(BS_TABLERO_IMPORT);
if (!is_array($plan) || empty($plan['obras']) || !is_array($plan['obras'])) {
    bs_imp_pagina('Importar fichas', '<div class="caja">No hay nada para importar. Falta subir el archivo <b>importar.json</b> a <code>bs-data/tablero/</code>, o ya se importó.</div><p><a class="btn" href="/presupuestos/tablero.php">Ir al tablero</a></p>');
}
$borrar_todo = !empty($plan['borrar_todo']);

$todos_los_tildes = [];
foreach ($BS_ETAPAS as $def) foreach ($def['checks'] as $cid => $_l) $todos_los_tildes[$cid] = true;

$filas = [];
$errores = [];
foreach ($plan['obras'] as $i => $o) {
    $n = $i + 1;
    if (!is_array($o)) { $errores[] = "Fila $n: no es un objeto."; continue; }
    $txt = function ($campo, $max) use ($o) {
        return mb_substr(trim(preg_replace('/\s+/u', ' ', (string)($o[$campo] ?? ''))), 0, $max);
    };
    $f = [
        'nombre'    => $txt('nombre', 120),
        'celular'   => $txt('celular', 40),
        'direccion' => $txt('direccion', 200),
        'material'  => $txt('material', 200),
        'concepto'  => $txt('concepto', 200),
        'nota'      => $txt('nota', BS_TABLERO_NOTA_MAX),
        'etapa'     => (string)($o['etapa'] ?? 'medir'),
        'fecha'     => (string)($o['fecha'] ?? ''),
        'hora'      => (string)($o['hora'] ?? ''),
        'medido_at' => (string)($o['medido_at'] ?? ''),
        'bacha'     => (string)($o['bacha'] ?? ''),
        'checks'    => [],
    ];
    if ($f['nombre'] === '') { $errores[] = "Fila $n: falta el nombre."; continue; }
    if (!isset($BS_ETAPAS[$f['etapa']])) { $errores[] = "Fila $n ({$f['nombre']}): etapa desconocida \"{$f['etapa']}\"."; continue; }
    if ($f['fecha'] !== '' && !bs_tablero_fecha_valida($f['fecha'])) { $errores[] = "Fila $n ({$f['nombre']}): fecha inválida."; continue; }
    if ($f['hora'] !== '' && !bs_tablero_hora_valida($f['hora'])) { $errores[] = "Fila $n ({$f['nombre']}): hora inválida."; continue; }
    if ($f['medido_at'] !== '' && !bs_tablero_fecha_valida($f['medido_at'])) { $errores[] = "Fila $n ({$f['nombre']}): fecha de medición inválida."; continue; }
    if (!in_array($f['bacha'], ['', 'nuestra', 'cliente', 'no'], true)) { $errores[] = "Fila $n ({$f['nombre']}): bacha inválida."; continue; }
    if ($f['fecha'] === '' || empty($BS_ETAPAS[$f['etapa']]['fecha'])) { $f['fecha'] = ''; $f['hora'] = ''; }
    if (empty($BS_ETAPAS[$f['etapa']]['pide_hora'])) $f['hora'] = '';
    foreach ((array)($o['checks'] ?? []) as $cid) {
        if (!isset($todos_los_tildes[(string)$cid])) { $errores[] = "Fila $n ({$f['nombre']}): tilde desconocido \"$cid\"."; continue 2; }
        $f['checks'][(string)$cid] = true;
    }
    if ($f['bacha'] !== '') $f['checks']['bacha'] = true;
    if (!empty($f['checks']['medido']) && $f['medido_at'] === '') $f['medido_at'] = date('Y-m-d');
    $filas[] = $f;
}

// Qué hay hoy en el tablero
$hoy_manual = 0; $hoy_calc = 0;
foreach (@scandir(BS_CLIENTES_DIR) ?: [] as $archivo) {
    if (!preg_match('/^(\d{4,})\.json$/', $archivo)) continue;
    $cli = bs_read_json(BS_CLIENTES_DIR . '/' . $archivo);
    foreach (($cli['cotizaciones'] ?? []) as $cot) {
        if (($cot['estado'] ?? '') !== 'aprobado') continue;
        if (!empty($cot['manual'])) $hoy_manual++; else $hoy_calc++;
    }
}

$por_etapa = [];
foreach ($filas as $f) $por_etapa[$f['etapa']] = ($por_etapa[$f['etapa']] ?? 0) + 1;

// ============================================================
// GET — vista previa (no toca nada)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $c = '';
    if ($errores) {
        $c .= '<div class="caja"><span class="rojo">El archivo tiene errores. No se puede importar hasta corregirlo.</span><ul>';
        foreach ($errores as $e) $c .= '<li>' . h($e) . '</li>';
        $c .= '</ul></div>';
    }
    $c .= '<div class="caja"><b>Hoy en el tablero:</b> ' . ($hoy_manual + $hoy_calc) . ' fichas.';
    if ($borrar_todo) {
        $c .= '<br><span class="rojo">Se borran todas antes de cargar.</span><ul>'
            . '<li>' . $hoy_calc . ' vienen de presupuestos de la calculadora: el presupuesto <b>no</b> se borra, vuelve a Enviado.</li>'
            . '<li>' . $hoy_manual . ' fueron cargadas a mano: se borran completas.</li>'
            . '<li>Se pierden los tildes, fechas, notas, fotos y planos de todas.</li></ul>';
    } else {
        $c .= '<br>No se borra nada: las fichas nuevas se suman.';
    }
    $c .= '</div>';
    $c .= '<div class="caja"><b>Se cargan ' . count($filas) . ' fichas:</b> ';
    $partes = [];
    foreach ($BS_ETAPAS as $id => $def) if (!empty($por_etapa[$id])) $partes[] = $por_etapa[$id] . ' en ' . h($def['titulo']);
    $c .= implode(' · ', $partes) . '.</div>';

    $c .= '<h2>Detalle</h2><div class="caja" style="overflow-x:auto"><table><tr><th>Cliente</th><th>Etapa</th><th>Material</th><th>Fecha</th><th>Ya cumplido</th><th>Nota</th></tr>';
    foreach ($filas as $f) {
        $tildes = [];
        foreach ($BS_ETAPAS as $def) foreach ($def['checks'] as $cid => $label) if (!empty($f['checks'][$cid]) && !in_array($label, $tildes, true)) $tildes[] = $label;
        $fecha = $f['fecha'] !== '' ? $f['fecha'] . ($f['hora'] !== '' ? ' ' . $f['hora'] : '') : ($f['medido_at'] !== '' ? 'medido ' . $f['medido_at'] : '');
        $c .= '<tr><td><b>' . h($f['nombre']) . '</b><br><span class="gris">' . h($f['direccion']) . '<br>' . h($f['celular']) . '</span></td>'
            . '<td>' . h($BS_ETAPAS[$f['etapa']]['titulo']) . '</td><td>' . h($f['material']) . '</td><td>' . h($fecha) . '</td>'
            . '<td>' . h(implode(', ', $tildes)) . '</td><td>' . h($f['nota']) . '</td></tr>';
    }
    $c .= '</table></div>';

    if (!$errores) {
        $c .= '<form method="post" class="caja"><label><input type="checkbox" name="confirmo" value="SI" required> Leí el detalle y quiero '
            . ($borrar_todo ? 'borrar las ' . ($hoy_manual + $hoy_calc) . ' fichas actuales y ' : '') . 'cargar estas ' . count($filas) . '.</label>'
            . '<button type="submit">' . ($borrar_todo ? 'Borrar todo y cargar' : 'Cargar') . '</button> &nbsp; <a href="/presupuestos/tablero.php">Cancelar</a></form>';
    }
    bs_imp_pagina('Importar fichas al tablero', $c);
}

// ============================================================
// POST — ejecutar
// ============================================================
if ($errores) bs_imp_pagina('Importar fichas', '<div class="caja rojo">El archivo tiene errores. No se importó nada.</div>');
if (($_POST['confirmo'] ?? '') !== 'SI') bs_imp_pagina('Importar fichas', '<div class="caja rojo">Falta tildar la confirmación. No se importó nada.</div><p><a class="btn" href="">Volver</a></p>');

if (!is_dir(BS_TABLERO_DIR)) @mkdir(BS_TABLERO_DIR, 0755, true);
$fp_cli = bs_lock_acquire(BS_CLIENTES_DIR . '/.write.lock');
$fp_tab = bs_lock_acquire(BS_TABLERO_LOCK);
if (!$fp_cli || !$fp_tab) {
    bs_lock_release($fp_cli); bs_lock_release($fp_tab);
    bs_imp_pagina('Importar fichas', '<div class="caja rojo">No se pudo tomar el control de los archivos. Probá de nuevo.</div>');
}

try {
    $now = date('c');
    $res = ['vueltas_a_enviado' => 0, 'manuales_borradas' => 0, 'creadas' => 0];

    $tablero = bs_tablero_leer();

    // 1) Vaciar el tablero
    if ($borrar_todo) {
        $idx = bs_read_json(BS_CLIENTES_INDEX);
        if (!is_array($idx)) $idx = [];
        $idx_cambio = false;
        foreach (@scandir(BS_CLIENTES_DIR) ?: [] as $archivo) {
            if (!preg_match('/^(\d{4,})\.json$/', $archivo, $mm)) continue;
            $path = BS_CLIENTES_DIR . '/' . $archivo;
            $cli = bs_read_json($path);
            if (!$cli) continue;
            $kept = []; $cambio = false;
            foreach (($cli['cotizaciones'] ?? []) as $cot) {
                if (($cot['estado'] ?? '') !== 'aprobado') { $kept[] = $cot; continue; }
                $cambio = true;
                if (!empty($cot['manual'])) { $res['manuales_borradas']++; continue; }
                $cot['estado'] = 'enviado';
                $cot['transiciones'][] = ['estado' => 'enviado', 'at' => $now];
                $kept[] = $cot;
                $res['vueltas_a_enviado']++;
            }
            if (!$cambio) continue;
            if (!$kept) {
                @unlink($path);
                // Mismo criterio que delete.php: el nro no se recicla.
                $idx[$mm[1]] = ['deleted_at' => $now, 'reason' => 'manual'];
                $idx_cambio = true;
            } else {
                $cli['cotizaciones'] = $kept;
                $cli['ultima_actualizacion'] = $now;
                if (!bs_write_json_atomic($path, $cli)) throw new Exception('No se pudo escribir ' . $archivo);
            }
        }
        if ($idx_cambio) bs_write_json_atomic(BS_CLIENTES_INDEX, $idx);

        foreach (array_keys($tablero['obras']) as $key) bs_tablero_borrar_carpeta((string)$key);
        if (is_dir(BS_TABLERO_ADJ_DIR)) {
            foreach (scandir(BS_TABLERO_ADJ_DIR) as $d) {
                if ($d !== '.' && $d !== '..') bs_tablero_borrar_carpeta($d);
            }
        }
        $tablero['obras'] = [];
    }

    // 2) Próximo número: la serie interna de las fichas a mano (no son presupuestos)
    $max = intval(bs_tablero_nro_ficha_manual()) - 1;

    // 3) Crear las fichas
    $base = time();
    foreach ($filas as $i => $f) {
        $nro = str_pad((string)($max + 1 + $i), 4, '0', STR_PAD_LEFT);
        $cliente_obj = [
            'version' => defined('BS_CONTRACT_VERSION') ? BS_CONTRACT_VERSION : 1,
            'cliente_nro' => $nro,
            'cliente' => ['nombre' => $f['nombre'], 'dni' => '', 'celular' => $f['celular'], 'direccion' => $f['direccion'], 'email' => ''],
            'primera_cotizacion' => $now,
            'ultima_actualizacion' => $now,
            'cotizaciones' => [[
                'sub' => 1,
                'fecha' => $now,
                'concepto' => $f['concepto'],
                'material_final' => $f['material'],
                'estado' => 'aprobado',
                'origen' => 'local',
                'transiciones' => [['estado' => 'aprobado', 'at' => $now]],
                'presupuesto' => ['totales' => ['usd' => 0, 'ars' => 0]],
                'manual' => true,
                'ajuste_manual' => ['at' => $now, 'nota' => 'Obra importada al tablero desde planilla'],
                'zip_path' => null,
            ]],
        ];
        if (!bs_write_json_atomic(BS_CLIENTES_DIR . '/' . $nro . '.json', $cliente_obj)) throw new Exception('No se pudo escribir el cliente ' . $nro);

        $obra = bs_tablero_obra_vacia();
        $obra['etapa'] = $f['etapa'];
        $obra['fecha'] = $f['fecha'];
        $obra['hora'] = $f['hora'];
        $obra['checks'] = $f['checks'];
        $obra['nota'] = $f['nota'];
        $obra['medido_at'] = $f['medido_at'];
        $obra['bacha'] = $f['bacha'];
        // Orden de la planilla = orden de la cola.
        $obra['desde'] = date('c', $base + $i);
        $obra['upd'] = $now;
        $tablero['obras'][$nro . '-1'] = $obra;
        $res['creadas']++;
    }

    if (!bs_write_json_atomic(BS_TABLERO_FILE, $tablero)) throw new Exception('No se pudo guardar el tablero');

    // 4) Que no se pueda correr dos veces
    @rename(BS_TABLERO_IMPORT, BS_TABLERO_DIR . '/importar-hecho-' . date('Ymd-His') . '.json');

    bs_lock_release($fp_tab); bs_lock_release($fp_cli);

    $c = '<div class="caja"><b>Listo.</b><ul>';
    if ($borrar_todo) {
        $c .= '<li>' . $res['vueltas_a_enviado'] . ' presupuestos volvieron a Enviado.</li><li>' . $res['manuales_borradas'] . ' obras cargadas a mano se borraron.</li>';
    }
    $c .= '<li>' . $res['creadas'] . ' fichas cargadas.</li></ul></div><p><a class="btn" href="/presupuestos/tablero.php">Ir al tablero</a></p>';
    bs_imp_pagina('Importación terminada', $c);

} catch (Throwable $e) {
    bs_lock_release($fp_tab); bs_lock_release($fp_cli);
    bs_imp_pagina('Importar fichas', '<div class="caja rojo">Se interrumpió: ' . h($e->getMessage()) . '</div><p>Abrí el tablero para ver cómo quedó y avisá antes de volver a correrlo.</p><p><a class="btn" href="/presupuestos/tablero.php">Ir al tablero</a></p>');
}
