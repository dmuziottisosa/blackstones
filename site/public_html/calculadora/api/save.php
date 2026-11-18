<?php
// ============================================================
// save.php — Guardar una cotización
// ============================================================
// POST /api/save.php
// Body JSON:
//   {
//     "mode": "new_client" | "append_to",
//     "cliente_nro_proposed": "0044",      // si mode=new_client
//     "cliente_nro_target": "0042",         // si mode=append_to
//     "cliente": { nombre, dni, celular, direccion, email },
//     "concepto": "cocina principal",
//     "presupuesto": { ...full data ver json-contract-v1.md § 3 }
//   }
//
// Response:
//   { "ok": true, "cliente_nro": "0044", "sub": 1, "estado": "borrador",
//     "warning": null | "..." }
//
// Concurrencia: flock sobre lockfile global del directorio clientes/.
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

// Las fichas cargadas a mano en el tablero NO son presupuestos: llevan un número interno de
// otra serie (90001 en adelante) y no cuentan para el próximo N° de presupuesto.
if (!defined('BS_NRO_FICHA_MANUAL_DESDE')) define('BS_NRO_FICHA_MANUAL_DESDE', 90000);

bs_require_permiso('calculadora');
bs_check_csrf();
bs_ensure_dirs();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    bs_error('Solo POST', 405);
}

$body = json_decode(@file_get_contents('php://input'), true);
if (!is_array($body)) bs_error('Body JSON inválido');

$mode = $body['mode'] ?? '';
if (!in_array($mode, ['new_client', 'append_to'], true)) {
    bs_error('mode debe ser new_client o append_to');
}

$cliente_data = $body['cliente'] ?? null;
if (!is_array($cliente_data) || empty($cliente_data['nombre'])) {
    bs_error('cliente.nombre es obligatorio');
}

$concepto = $body['concepto'] ?? '';
$presupuesto = $body['presupuesto'] ?? null;
if (!is_array($presupuesto)) bs_error('presupuesto es obligatorio');

// Lock global del directorio clientes/ para serializar concurrencia
/** El N° de presupuesto más alto que existe (sin contar las fichas a mano, que van de 90001 en adelante). */
function bs_save_max_nro() {
    $max = 0;
    foreach (@scandir(BS_CLIENTES_DIR) ?: [] as $f) {
        if (preg_match('/^(\d{4,})\.json$/', $f, $m) && intval($m[1]) < BS_NRO_FICHA_MANUAL_DESDE) $max = max($max, intval($m[1]));
    }
    $idx = bs_read_json(BS_CLIENTES_INDEX);
    if (is_array($idx)) foreach ($idx as $nro_str => $_v) { if (intval($nro_str) < BS_NRO_FICHA_MANUAL_DESDE) $max = max($max, intval($nro_str)); }
    return $max;
}

/**
 * ¿El cliente que ya tiene ese N° es la misma persona que la del presupuesto que llega?
 * Es estricto a propósito: equivocarse para el lado de "es otra" solo cuesta un N° nuevo;
 * equivocarse para el lado de "es la misma" mezcla dos clientes.
 *   - Si los dos tienen teléfono, manda el teléfono (últimos 8 dígitos).
 *   - Si a alguno le falta, tienen que coincidir los nombres (igual, o dos palabras enteras en común).
 */
function bs_save_misma_persona(array $viejo, array $nuevo) {
    return bs_misma_persona($viejo, $nuevo); // una sola regla para todo el sistema: _config.php
}

$lockfile = BS_CLIENTES_DIR . '/.write.lock';
$fp = bs_lock_acquire($lockfile);
if (!$fp) bs_error('No se pudo adquirir lock', 500);

try {
    $now = date('c'); // ISO 8601 con timezone
    $warning = null;

    if ($mode === 'new_client') {
        // Asignar N°: usar el propuesto si está libre, sino next disponible
        $proposed = $body['cliente_nro_proposed'] ?? '';
        $proposed_int = intval($proposed);

        $target_nro = null;

        // Encontrar max existente (clientes/ + index)
        $max = 0;
        if (is_dir(BS_CLIENTES_DIR)) {
            foreach (scandir(BS_CLIENTES_DIR) as $f) {
                if (preg_match('/^(\d{4,})\.json$/', $f, $m)) {
                    $n = intval($m[1]);
                    if ($n >= BS_NRO_FICHA_MANUAL_DESDE) continue; // ficha a mano: no es un presupuesto
                    if ($n > $max) $max = $n;
                }
            }
        }
        $idx = bs_read_json(BS_CLIENTES_INDEX);
        if (is_array($idx)) {
            foreach ($idx as $nro_str => $_v) {
                $n = intval($nro_str);
            if ($n >= BS_NRO_FICHA_MANUAL_DESDE) continue;
                if ($n > $max) $max = $n;
            }
        }

        // Un N° de más de 4 cifras es un error de tipeo ("22510" por "2251"): dejaba la serie trabada
        // (next-nro.php corta en 9999) o caía en la serie de las fichas a mano. Se da el siguiente.
        if ($proposed_int > 9999) { $proposed_int = 0; $warning = "El N° {$proposed} no es válido (hasta 4 cifras)."; }
        if ($proposed_int > 0 && $proposed_int > $max && !file_exists(BS_CLIENTES_DIR . '/' . str_pad($proposed, 4, '0', STR_PAD_LEFT) . '.json')) {
            // Proposed está libre y es mayor que cualquier existente → usarlo
            $target_nro = str_pad((string)$proposed_int, 4, '0', STR_PAD_LEFT);
        } else {
            // Conflict → asignar next disponible
            $target_nro = str_pad((string)($max + 1), 4, '0', STR_PAD_LEFT);
            if ($proposed_int > 0 && $target_nro !== str_pad((string)$proposed_int, 4, '0', STR_PAD_LEFT)) {
                $warning = "El N° {$proposed} ya estaba tomado. Se asignó {$target_nro}.";
            }
            if ($warning !== null && intval($proposed) > 9999) $warning .= " Se asignó {$target_nro}.";
        }

        // Crear el archivo cliente nuevo
        $cliente_path = BS_CLIENTES_DIR . '/' . $target_nro . '.json';
        $cliente_obj = [
            'version' => BS_CONTRACT_VERSION,
            'cliente_nro' => $target_nro,
            'cliente' => [
                'nombre'    => trim($cliente_data['nombre']),
                'dni'       => trim($cliente_data['dni'] ?? ''),
                'celular'   => trim($cliente_data['celular'] ?? ''),
                'direccion' => trim($cliente_data['direccion'] ?? ''),
                'email'     => trim($cliente_data['email'] ?? ''),
            ],
            'primera_cotizacion' => $now,
            'ultima_actualizacion' => $now,
            'cotizaciones' => [
                [
                    'sub' => 1,
                    'fecha' => $now,
                    'concepto' => $concepto,
                    'estado' => 'borrador',
                    'transiciones' => [
                        ['estado' => 'borrador', 'at' => $now]
                    ],
                    'presupuesto' => $presupuesto,
                    'zip_path' => null,
                ]
            ],
        ];

        if (!bs_write_json_atomic($cliente_path, $cliente_obj)) {
            bs_lock_release($fp);
            bs_error('No se pudo escribir el cliente', 500);
        }

        bs_lock_release($fp);
        bs_ok([
            'cliente_nro' => $target_nro,
            'sub'         => 1,
            'estado'      => 'borrador',
            'warning'     => $warning,
        ]);

    } else {
        // mode = append_to → agregar sub-versión a un cliente existente
        $target = $body['cliente_nro_target'] ?? '';
        $target = str_pad((string)intval($target), 4, '0', STR_PAD_LEFT);
        if (intval($target) <= 0) {
            bs_lock_release($fp);
            bs_error('cliente_nro_target inválido');
        }

        $cliente_path = BS_CLIENTES_DIR . '/' . $target . '.json';
        $cliente_obj = bs_read_json($cliente_path);
        // N° tipeado de más de 4 cifras que no existe: error de tipeo (ver arriba). Va el siguiente.
        if (!$cliente_obj && intval($target) > 9999) {
            $target_viejo = $target;
            $target = str_pad((string)(bs_save_max_nro() + 1), 4, '0', STR_PAD_LEFT);
            $cliente_path = BS_CLIENTES_DIR . '/' . $target . '.json';
            $cliente_obj = bs_read_json($cliente_path);
            $warning = "El N° {$target_viejo} no es válido (hasta 4 cifras). Este presupuesto se guardó con el N° {$target}.";
        }

        // PROTECCIÓN (dueño, 18/11/2026, "hubo un traspapeleo que no puede pasar nunca más"):
        // el N° que viaja puede ser viejo (la calculadora lo recupera de un borrador o quedó de una
        // pestaña abierta) y entre tanto pudo tomarlo OTRA persona: una ficha cargada a mano, que usa
        // la misma serie y no muestra su número, u otro presupuesto. Antes se guardaba encima: le
        // cambiaba el nombre, el teléfono y la dirección a la obra de otro cliente.
        // Ahora solo se agrega una versión si es la misma persona. Si no, el presupuesto se guarda
        // con un N° nuevo y se avisa. Una ficha cargada a mano nunca recibe un presupuesto por acá.
        if ($cliente_obj) {
            $es_ficha_manual = false;
            foreach ($cliente_obj['cotizaciones'] ?? [] as $cot_v) { if (!empty($cot_v['manual'])) $es_ficha_manual = true; }
            if ($es_ficha_manual || !bs_save_misma_persona((array)($cliente_obj['cliente'] ?? []), $cliente_data)) {
                $de_quien = $es_ficha_manual ? 'una obra cargada a mano en el tablero' : (trim((string)($cliente_obj['cliente']['nombre'] ?? '')) ?: 'otro cliente');
                $target_viejo = $target;
                $target = str_pad((string)(bs_save_max_nro() + 1), 4, '0', STR_PAD_LEFT);
                $cliente_path = BS_CLIENTES_DIR . '/' . $target . '.json';
                $cliente_obj = null;
                $warning = "El N° {$target_viejo} ya era de {$de_quien}. Para no mezclarlos, este presupuesto se guardó con el N° {$target}.";
                @file_put_contents(BS_REGISTRO_DIR . '/_cruces-evitados.log', '[' . $now . "] save.php: N° {$target_viejo} ya tomado; se guardó como {$target}\n", FILE_APPEND | LOCK_EX);
            }
        }

        if (!$cliente_obj) {
            // No existe → crear cliente nuevo con ese nro (caso "tipeé manual un nro nuevo")
            $cliente_obj = [
                'version' => BS_CONTRACT_VERSION,
                'cliente_nro' => $target,
                'cliente' => [
                    'nombre'    => trim($cliente_data['nombre']),
                    'dni'       => trim($cliente_data['dni'] ?? ''),
                    'celular'   => trim($cliente_data['celular'] ?? ''),
                    'direccion' => trim($cliente_data['direccion'] ?? ''),
                    'email'     => trim($cliente_data['email'] ?? ''),
                ],
                'primera_cotizacion' => $now,
                'ultima_actualizacion' => $now,
                'cotizaciones' => [],
            ];
            $next_sub = 1;
        } else {
            // Existe → calcular próximo sub
            $max_sub = 0;
            foreach ($cliente_obj['cotizaciones'] ?? [] as $cot) {
                $s = intval($cot['sub'] ?? 0);
                if ($s > $max_sub) $max_sub = $s;
            }
            $next_sub = $max_sub + 1;
            $cliente_obj['ultima_actualizacion'] = $now;
            // Actualizar datos del cliente con los del nuevo input (no perdemos data)
            foreach (['nombre', 'dni', 'celular', 'direccion', 'email'] as $k) {
                $v = trim($cliente_data[$k] ?? '');
                if ($v !== '') $cliente_obj['cliente'][$k] = $v;
            }
        }

        $cliente_obj['cotizaciones'][] = [
            'sub' => $next_sub,
            'fecha' => $now,
            'concepto' => $concepto,
            'estado' => 'borrador',
            'transiciones' => [
                ['estado' => 'borrador', 'at' => $now]
            ],
            'presupuesto' => $presupuesto,
            'zip_path' => null,
        ];

        if (!bs_write_json_atomic($cliente_path, $cliente_obj)) {
            bs_lock_release($fp);
            bs_error('No se pudo escribir el cliente', 500);
        }

        bs_lock_release($fp);
        bs_ok([
            'cliente_nro' => $target,
            'sub'         => $next_sub,
            'estado'      => 'borrador',
            'warning'     => $warning,
        ]);
    }
} catch (Throwable $e) {
    bs_lock_release($fp);
    bs_error('Excepción: ' . $e->getMessage(), 500);
}
