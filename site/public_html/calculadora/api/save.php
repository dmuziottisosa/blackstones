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

bs_require_auth();
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
                    if ($n > $max) $max = $n;
                }
            }
        }
        $idx = bs_read_json(BS_CLIENTES_INDEX);
        if (is_array($idx)) {
            foreach ($idx as $nro_str => $_v) {
                $n = intval($nro_str);
                if ($n > $max) $max = $n;
            }
        }

        if ($proposed_int > 0 && $proposed_int > $max && !file_exists(BS_CLIENTES_DIR . '/' . str_pad($proposed, 4, '0', STR_PAD_LEFT) . '.json')) {
            // Proposed está libre y es mayor que cualquier existente → usarlo
            $target_nro = str_pad((string)$proposed_int, 4, '0', STR_PAD_LEFT);
        } else {
            // Conflict → asignar next disponible
            $target_nro = str_pad((string)($max + 1), 4, '0', STR_PAD_LEFT);
            if ($proposed_int > 0 && $target_nro !== str_pad((string)$proposed_int, 4, '0', STR_PAD_LEFT)) {
                $warning = "El N° {$proposed} ya estaba tomado. Se asignó {$target_nro}.";
            }
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
            'warning'     => null,
        ]);
    }
} catch (Throwable $e) {
    bs_lock_release($fp);
    bs_error('Excepción: ' . $e->getMessage(), 500);
}
