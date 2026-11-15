<?php
// ============================================================
// tablero-editar.php — Editar una obra del tablero
// BlackStones Marmolería · Registry de Presupuestos
// ============================================================
// POST /api/tablero-editar.php
//
// Dos cosas, en cualquier momento de la obra:
//
// 1) Corregir sus datos: cliente, teléfono, dirección, material, trabajo.
//    Solo se tocan los campos que vienen en el body.
//
// 2) Cambiarle el N° de presupuesto ("nro"): la ficha se muda a otro
//    presupuesto del hub SIN perder nada de lo hecho. Sus tildes, fechas,
//    seña, historial, fotos y planos pasan a la clave nueva. Caso típico:
//    la obra se cargó a mano y después se hizo el presupuesto en la
//    calculadora; o se ató al número equivocado.
//      - El presupuesto nuevo pasa a "aprobado" (así queda en el tablero).
//      - El anterior: si era una obra cargada a mano, se borra (era solo
//        un contenedor); si era un presupuesto de la calculadora, vuelve
//        a "enviado". Mismo criterio que borrar una ficha.
//      - Teléfono y dirección: si el presupuesto nuevo no los tiene, se
//        completan con los que traía la ficha.
//    Cuando cambia el N°, los datos los pone el presupuesto nuevo: los
//    campos de texto del mismo body se ignoran.
//
// Body JSON:
//   {
//     "cliente_nro": "0049", "sub": 1,          // la obra a editar
//     "nro":       "2250" | "2250-2",           // opcional: mudarla a ese presupuesto
//     "nombre":    "...", "celular": "...", "direccion": "...",
//     "material":  "...", "concepto": "..."     // opcionales: solo los que cambian
//   }
//
// Response: { "ok": true, "cliente_nro": "2250", "sub": 1, "mudada": true|false }
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_tablero.php';

bs_require_auth();
bs_check_csrf();
bs_ensure_dirs();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') bs_error('Solo POST', 405);

$body = json_decode(@file_get_contents('php://input'), true);
if (!is_array($body)) bs_error('Body JSON inválido');

$cliente_nro = str_pad((string)intval($body['cliente_nro'] ?? ''), 4, '0', STR_PAD_LEFT);
$sub = intval($body['sub'] ?? 0);
if (intval($cliente_nro) <= 0) bs_error('cliente_nro inválido');
if ($sub <= 0) bs_error('sub inválido');

/** Texto de un campo, ya limpio; null si el campo no vino en el body. */
function bs_editar_texto($body, $campo, $max, $etiqueta) {
    if (!array_key_exists($campo, $body)) return null;
    $v = trim(preg_replace('/\s+/u', ' ', (string)$body[$campo]));
    if (mb_strlen($v) > $max) bs_error($etiqueta . ' demasiado largo');
    return $v;
}
$nombre    = bs_editar_texto($body, 'nombre', 120, 'Nombre');
$celular   = bs_editar_texto($body, 'celular', 40, 'Teléfono');
$direccion = bs_editar_texto($body, 'direccion', 200, 'Dirección');
$material  = bs_editar_texto($body, 'material', 200, 'Material');
$concepto  = bs_editar_texto($body, 'concepto', 200, 'Trabajo');
if ($nombre === '') bs_error('El nombre del cliente no puede quedar vacío.');

// ¿Pide mudarla a otro presupuesto?
$nro_txt = trim((string)($body['nro'] ?? ''));
$nuevo_nro = ''; $nuevo_sub = 0;
if ($nro_txt !== '') {
    if (!preg_match('/^#?\s*(\d{1,6})(?:\s*-\s*(\d{1,3}))?$/', $nro_txt, $m)) {
        bs_error('El N° de presupuesto va como 0042, o 0042-2 si tiene versiones.');
    }
    $nuevo_nro = str_pad((string)intval($m[1]), 4, '0', STR_PAD_LEFT);
    $nuevo_sub = isset($m[2]) ? intval($m[2]) : 0;
}

$path_de = function ($nro) { return BS_CLIENTES_DIR . '/' . $nro . '.json'; };
$buscar = function ($obj, $s) {
    foreach (($obj['cotizaciones'] ?? []) as $i => $c) if (intval($c['sub'] ?? 0) === $s) return $i;
    return -1;
};

$fp = bs_lock_acquire(BS_CLIENTES_DIR . '/.write.lock');
if (!$fp) bs_error('No se pudo adquirir lock', 500);
$fp_t = null;
$soltar = function () use (&$fp, &$fp_t) {
    if ($fp_t) { bs_lock_release($fp_t); $fp_t = null; }
    if ($fp) { bs_lock_release($fp); $fp = null; }
};
$fallar = function ($msg, $code = 400) use ($soltar) { $soltar(); bs_error($msg, $code); };

try {
    $origen = bs_read_json($path_de($cliente_nro));
    if (!is_array($origen)) $fallar('Cliente no encontrado', 404);
    $i_origen = $buscar($origen, $sub);
    if ($i_origen < 0 || ($origen['cotizaciones'][$i_origen]['estado'] ?? '') !== 'aprobado') {
        $fallar('Esa obra ya no está en el tablero. Actualizá la página.', 409);
    }
    $now = date('c');

    // --------------------------------------------------------
    // Mudanza a otro presupuesto
    // --------------------------------------------------------
    if ($nuevo_nro !== '') {
        $mismo_cliente = ($nuevo_nro === $cliente_nro);
        // Si es el mismo cliente se trabaja sobre un solo objeto, para no pisar una escritura con otra.
        $destino = $mismo_cliente ? $origen : bs_read_json($path_de($nuevo_nro));
        $cots = is_array($destino) ? ($destino['cotizaciones'] ?? []) : [];
        if (!$cots) $fallar('No hay ningún presupuesto N° ' . $nuevo_nro . '.', 404);
        $subs = array_map(function ($c) { return intval($c['sub'] ?? 0); }, $cots);
        if ($nuevo_sub === 0) {
            if (count($cots) > 1) {
                $fallar('El ' . $nuevo_nro . ' tiene ' . count($cots) . ' versiones (' . implode(', ', $subs) . '). Escribí cuál, por ejemplo ' . $nuevo_nro . '-' . max($subs) . '.');
            }
            $nuevo_sub = $subs[0];
        } elseif (!in_array($nuevo_sub, $subs, true)) {
            $fallar('El ' . $nuevo_nro . ' no tiene la versión ' . $nuevo_sub . '. Tiene: ' . implode(', ', $subs) . '.', 404);
        }

        if (!($mismo_cliente && $nuevo_sub === $sub)) { // (pedir el mismo número que ya tiene no es un cambio)
            $i_destino = $buscar($destino, $nuevo_sub);
            $estado = (string)($destino['cotizaciones'][$i_destino]['estado'] ?? '');
            if ($estado === 'aprobado')  $fallar('El ' . $nuevo_nro . '-' . $nuevo_sub . ' ya está en el tablero como otra ficha.', 409);
            if ($estado === 'entregado') $fallar('El ' . $nuevo_nro . '-' . $nuevo_sub . ' ya figura como entregado.', 409);

            if (!is_dir(BS_TABLERO_DIR) && !@mkdir(BS_TABLERO_DIR, 0755, true)) $fallar('No se pudo crear el directorio del tablero', 500);
            $fp_t = bs_lock_acquire(BS_TABLERO_LOCK);
            if (!$fp_t) $fallar('No se pudo adquirir lock', 500);

            $key_vieja = $cliente_nro . '-' . $sub;
            $key_nueva = $nuevo_nro . '-' . $nuevo_sub;
            $cot_vieja = $origen['cotizaciones'][$i_origen];
            $cli_viejo = $origen['cliente'] ?? [];

            // 1) El presupuesto nuevo entra al tablero. Lo que no tenga de contacto lo hereda de la ficha.
            $destino['cotizaciones'][$i_destino]['estado'] = 'aprobado';
            $destino['cotizaciones'][$i_destino]['transiciones'][] = ['estado' => 'aprobado', 'at' => $now];
            foreach (['celular', 'direccion'] as $k) {
                if (trim((string)($destino['cliente'][$k] ?? '')) === '' && trim((string)($cli_viejo[$k] ?? '')) !== '') {
                    $destino['cliente'][$k] = $cli_viejo[$k];
                }
            }

            // 2) El anterior sale: la obra cargada a mano se borra, el presupuesto de la calc vuelve a "enviado".
            $era_manual = !empty($cot_vieja['manual']);
            if ($mismo_cliente) {
                // $destino y $origen son el mismo objeto: se resuelve acá y se escribe una sola vez.
                if ($era_manual) {
                    array_splice($destino['cotizaciones'], $i_origen, 1);
                } else {
                    $destino['cotizaciones'][$i_origen]['estado'] = 'enviado';
                    $destino['cotizaciones'][$i_origen]['transiciones'][] = ['estado' => 'enviado', 'at' => $now];
                }
            }
            $destino['ultima_actualizacion'] = $now;
            if (!bs_write_json_atomic($path_de($nuevo_nro), $destino)) $fallar('No se pudo escribir el presupuesto nuevo', 500);

            // 3) La ficha (tildes, fechas, seña, historial) pasa a la clave nueva, con sus adjuntos.
            $tablero = bs_tablero_leer();
            if (isset($tablero['obras'][$key_vieja])) {
                $tablero['obras'][$key_nueva] = $tablero['obras'][$key_vieja];
                unset($tablero['obras'][$key_vieja]);
            } else {
                unset($tablero['obras'][$key_nueva]); // la vieja no tenía nada cargado: la nueva arranca limpia
            }
            $tablero['updated_at'] = $now;
            bs_tablero_borrar_carpeta($key_nueva); // por si quedó una carpeta vieja con esa clave
            $dir_vieja = bs_tablero_adj_carpeta($key_vieja);
            if (is_dir($dir_vieja)) @rename($dir_vieja, bs_tablero_adj_carpeta($key_nueva));
            if (!bs_write_json_atomic(BS_TABLERO_FILE, $tablero)) $fallar('No se pudo escribir el tablero', 500);

            // 4) Limpieza del anterior, si vivía en otro archivo.
            if (!$mismo_cliente) {
                if ($era_manual) {
                    array_splice($origen['cotizaciones'], $i_origen, 1);
                    if (count($origen['cotizaciones']) === 0) {
                        @unlink($path_de($cliente_nro));
                        // el número no se recicla (mismo criterio que delete.php)
                        $idx = bs_read_json(BS_CLIENTES_INDEX);
                        if (!is_array($idx)) $idx = [];
                        $idx[$cliente_nro] = ['deleted_at' => $now, 'reason' => 'manual'];
                        bs_write_json_atomic(BS_CLIENTES_INDEX, $idx);
                    } else {
                        $origen['ultima_actualizacion'] = $now;
                        bs_write_json_atomic($path_de($cliente_nro), $origen);
                    }
                } else {
                    $origen['cotizaciones'][$i_origen]['estado'] = 'enviado';
                    $origen['cotizaciones'][$i_origen]['transiciones'][] = ['estado' => 'enviado', 'at' => $now];
                    $origen['ultima_actualizacion'] = $now;
                    bs_write_json_atomic($path_de($cliente_nro), $origen);
                }
            }

            $soltar();
            bs_ok(['cliente_nro' => $nuevo_nro, 'sub' => $nuevo_sub, 'mudada' => true]);
        }
    }

    // --------------------------------------------------------
    // Corrección de datos (misma obra, mismo N°)
    // --------------------------------------------------------
    if (!isset($origen['cliente']) || !is_array($origen['cliente'])) $origen['cliente'] = [];
    if ($nombre !== null)    $origen['cliente']['nombre'] = $nombre;
    if ($celular !== null)   $origen['cliente']['celular'] = $celular;
    if ($direccion !== null) $origen['cliente']['direccion'] = $direccion;
    if ($concepto !== null)  $origen['cotizaciones'][$i_origen]['concepto'] = $concepto;
    if ($material !== null) {
        // Vacío en un presupuesto de la calc = volver al material que sale de sus ítems.
        if ($material === '' && empty($origen['cotizaciones'][$i_origen]['manual'])) unset($origen['cotizaciones'][$i_origen]['material_final']);
        else $origen['cotizaciones'][$i_origen]['material_final'] = $material;
    }
    $origen['ultima_actualizacion'] = $now;
    if (!bs_write_json_atomic($path_de($cliente_nro), $origen)) $fallar('No se pudo escribir el cliente', 500);

    $soltar();
    bs_ok(['cliente_nro' => $cliente_nro, 'sub' => $sub, 'mudada' => false]);

} catch (Throwable $e) {
    $soltar();
    bs_error('Excepción: ' . $e->getMessage(), 500);
}
