<?php
// ============================================================
// tablero-cargar.php — Cargar una obra a mano en "Por medir"
// BlackStones Marmolería · Registry de Presupuestos
// ============================================================
// POST /api/tablero-cargar.php
//
// Para obras que no pasaron por la calculadora. Crea un cliente nuevo con
// una cotización YA en estado "aprobado": por eso aparece sola en el
// tablero (Por medir) y también en Presupuestos · Activos. Mismo criterio
// que add-manual.php, que hace lo propio con los entregados históricos.
//
// Con "nro": no crea nada. Busca ese presupuesto en el hub y lo pasa a
// "aprobado", así la ficha queda atada al presupuesto real (se ve el
// detalle, el material, la zona). Acepta "0042" o "0042-2" (versión).
//
// Body JSON:
//   {
//     "nro":       "0042-2",                  // opcional: presupuesto existente
//     "nombre":    "Carolina Pereyra",        // requerido si no hay nro
//     "celular":   "1124981031",              // opcional
//     "direccion": "Av. Rivadavia 6200, CABA",// opcional
//     "material":  "Blanco Suggar",           // opcional (queda como material_final)
//     "concepto":  "Cocina en L"              // opcional
//   }
//
// El importe queda en 0: se carga después desde Presupuestos (editar),
// que es donde administración lleva la plata.
//
// Response: { "ok": true, "cliente_nro": "0044", "sub": 1 }
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

bs_require_auth();
bs_check_csrf();
bs_ensure_dirs();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') bs_error('Solo POST', 405);

$body = json_decode(@file_get_contents('php://input'), true);
if (!is_array($body)) bs_error('Body JSON inválido');

function bs_cargar_texto($body, $campo, $max, $etiqueta) {
    $v = trim(preg_replace('/\s+/u', ' ', (string)($body[$campo] ?? '')));
    if (mb_strlen($v) > $max) bs_error($etiqueta . ' demasiado largo');
    return $v;
}

// ------------------------------------------------------------
// Con N° de presupuesto: aprobar el que ya existe en el hub
// ------------------------------------------------------------
$nro_txt = trim((string)($body['nro'] ?? ''));
if ($nro_txt !== '') {
    if (!preg_match('/^#?\s*(\d{1,6})(?:\s*-\s*(\d{1,3}))?$/', $nro_txt, $m)) {
        bs_error('El N° de presupuesto va como 0042, o 0042-2 si tiene versiones.');
    }
    $nro = str_pad((string)intval($m[1]), 4, '0', STR_PAD_LEFT);
    $sub_pedido = isset($m[2]) ? intval($m[2]) : 0;
    $cliente_path = BS_CLIENTES_DIR . '/' . $nro . '.json';

    $fp = bs_lock_acquire(BS_CLIENTES_DIR . '/.write.lock');
    if (!$fp) bs_error('No se pudo adquirir lock', 500);
    try {
        $cliente_obj = bs_read_json($cliente_path);
        $cots = is_array($cliente_obj) ? ($cliente_obj['cotizaciones'] ?? []) : [];
        if (!$cots) {
            bs_lock_release($fp);
            bs_error('No hay ningún presupuesto N° ' . $nro . '. Para cargar la obra sin presupuesto, dejá el número vacío.', 404);
        }
        $subs = array_map(function ($c) { return intval($c['sub'] ?? 0); }, $cots);
        if ($sub_pedido === 0) {
            if (count($cots) > 1) {
                bs_lock_release($fp);
                bs_error('El ' . $nro . ' tiene ' . count($cots) . ' versiones (' . implode(', ', $subs) . '). Escribí cuál, por ejemplo ' . $nro . '-' . max($subs) . '.');
            }
            $sub_pedido = $subs[0];
        } elseif (!in_array($sub_pedido, $subs, true)) {
            bs_lock_release($fp);
            bs_error('El ' . $nro . ' no tiene la versión ' . $sub_pedido . '. Tiene: ' . implode(', ', $subs) . '.', 404);
        }
        $now = date('c');
        foreach ($cliente_obj['cotizaciones'] as &$cot) {
            if (intval($cot['sub'] ?? 0) !== $sub_pedido) continue;
            $estado = (string)($cot['estado'] ?? '');
            if ($estado === 'aprobado') {
                bs_lock_release($fp);
                bs_error('El ' . $nro . '-' . $sub_pedido . ' ya está en el tablero.', 409);
            }
            if ($estado === 'entregado') {
                bs_lock_release($fp);
                bs_error('El ' . $nro . '-' . $sub_pedido . ' ya figura como entregado.', 409);
            }
            // (si estaba en la papelera del tablero, esto la restaura con todo lo que tenía)
            unset($cot['papelera']);
            $cot['estado'] = 'aprobado';
            $cot['transiciones'][] = ['estado' => 'aprobado', 'at' => $now];
        }
        unset($cot);
        $cliente_obj['ultima_actualizacion'] = $now;
        if (!bs_write_json_atomic($cliente_path, $cliente_obj)) {
            bs_lock_release($fp);
            bs_error('No se pudo escribir el cliente', 500);
        }
        bs_lock_release($fp);
        bs_ok(['cliente_nro' => $nro, 'sub' => $sub_pedido, 'vinculada' => true]);
    } catch (Throwable $e) {
        bs_lock_release($fp);
        bs_error('Excepción: ' . $e->getMessage(), 500);
    }
}

// ------------------------------------------------------------
// Sin N°: obra nueva cargada a mano
// ------------------------------------------------------------
$nombre    = bs_cargar_texto($body, 'nombre', 120, 'Nombre');
$celular   = bs_cargar_texto($body, 'celular', 40, 'Teléfono');
$direccion = bs_cargar_texto($body, 'direccion', 200, 'Dirección');
$material  = bs_cargar_texto($body, 'material', 200, 'Material');
$concepto  = bs_cargar_texto($body, 'concepto', 200, 'Trabajo');
if ($nombre === '') bs_error('Falta el nombre del cliente.');

$now = date('c');

$lockfile = BS_CLIENTES_DIR . '/.write.lock';
$fp = bs_lock_acquire($lockfile);
if (!$fp) bs_error('No se pudo adquirir lock', 500);

try {
    // Próximo nro: mismo criterio que next-nro.php y add-manual.php (max de
    // archivos + max del índice de borrados, para no reciclar números).
    $max = 0;
    foreach (@scandir(BS_CLIENTES_DIR) ?: [] as $f) {
        if (preg_match('/^(\d{4,})\.json$/', $f, $mm)) {
            $n = intval($mm[1]);
            if ($n > $max) $max = $n;
        }
    }
    $idx = bs_read_json(BS_CLIENTES_INDEX);
    if (is_array($idx)) {
        foreach ($idx as $nro_str => $_v) {
            $n = intval($nro_str);
            if ($n > $max) $max = $n;
        }
    }
    $next = $max + 1;
    if ($next > 9999) {
        bs_lock_release($fp);
        bs_error('Límite de 9999 clientes alcanzado.', 507);
    }
    $target_nro = str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    $cliente_path = BS_CLIENTES_DIR . '/' . $target_nro . '.json';

    $cliente_obj = [
        'version' => defined('BS_CONTRACT_VERSION') ? BS_CONTRACT_VERSION : 1,
        'cliente_nro' => $target_nro,
        'cliente' => [
            'nombre'    => $nombre,
            'dni'       => '',
            'celular'   => $celular,
            'direccion' => $direccion,
            'email'     => '',
        ],
        'primera_cotizacion' => $now,
        'ultima_actualizacion' => $now,
        'cotizaciones' => [
            [
                'sub'      => 1,
                'fecha'    => $now,
                'concepto' => $concepto,
                'material_final' => $material,
                'estado'   => 'aprobado',
                'origen'   => 'local',
                'transiciones' => [
                    ['estado' => 'aprobado', 'at' => $now],
                ],
                'presupuesto' => [
                    'totales' => ['usd' => 0, 'ars' => 0],
                ],
                // Alta manual: no pasó por la calc, no tiene items.
                'manual' => true,
                'ajuste_manual' => [
                    'at'   => $now,
                    'nota' => 'Obra cargada a mano desde el tablero',
                ],
                'zip_path' => null,
            ],
        ],
    ];

    if (!bs_write_json_atomic($cliente_path, $cliente_obj)) {
        bs_lock_release($fp);
        bs_error('No se pudo escribir el cliente', 500);
    }

    bs_lock_release($fp);
    bs_ok(['cliente_nro' => $target_nro, 'sub' => 1]);

} catch (Throwable $e) {
    bs_lock_release($fp);
    bs_error('Excepción: ' . $e->getMessage(), 500);
}
