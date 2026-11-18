<?php
// ============================================================
// tablero.php — Tablero de obras (4 etapas + retiro en local)
// BlackStones Marmolería · Registry de Presupuestos
// ============================================================
// GET  /api/tablero.php
//   Una tarjeta por cada cotización en estado "aprobado". La obra entra
//   sola al tablero cuando el presupuesto se aprueba, y sale cuando pasa
//   a "entregado" (eso lo sigue haciendo state.php, sin cambios).
//
// POST /api/tablero.php
//   Body JSON: { "cliente_nro": "0042", "sub": 3, "accion": "...", ... }
//     accion "check" → { "clave": "medida", "valor": true }
//                      La seña pide además el monto que trajo el medidor:
//                      { "clave": "sena", "valor": true, "monto": 250000, "moneda": "ARS" | "USD" }
//                      Piso y ascensor pide cómo sube la pieza:
//                      { "clave": "acceso", "valor": true, "modo": "pb" | "ascensor" | "escalera", "pisos": 3 }
//                      La bacha pide de quién es:
//                      { "clave": "bacha", "valor": true, "origen": "nuestra" | "cliente" | "no" }
//                      Instaladores pide quiénes van (uno o varios de $BS_INSTALADORES):
//                      { "clave": "equipo", "valor": true, "personas": ["Negro", "David"] }
//                      Fabricadores, igual, con $BS_FABRICADORES:
//                      { "clave": "fabrica", "valor": true, "personas": ["Pedro"] }
//                      Medidor, igual, con $BS_MEDIDORES:
//                      { "clave": "medidor", "valor": true, "personas": ["Cesar"] }
//     accion "fecha" → { "fecha": "2026-11-24" }   ("" la borra)
//                      En Por instalar el turno lleva hora: { "fecha": "2026-11-24", "hora": "09:30" }
//     accion "nota"  → { "nota": "Falta la bacha" } ("" la borra)
//     accion "bacha" → { "origen": "nuestra" | "cliente" | "no", "modelo": "Johnson E37" }
//                      Anota de quién es la bacha SIN tildar "Tenemos la bacha"
//                      (lo usa "Cargar obra"). Con "no" sí queda tildado.
//                      Vale en cualquier etapa: "Editar obra" lo usa para
//                      cargar o corregir el modelo después de Por medir.
//     accion "borrar" → borra la ficha: tildes, datos y adjuntos de la obra
//     accion "mover" → { "a": "fabricar" }  (arrastre: etapa de destino)
//                      { "hacia": "adelante" | "atras" | "retiro" }  (botones)
//                      "retiro" saca la obra de las columnas y la pasa al
//                      cuadro "Retiro en local". Desde ahí, "atras" la
//                      devuelve a la columna de la que salió.
//
// Fotos de bacha y planos: los sube y los sirve tablero-adjunto.php.
// Acá solo se listan en cada tarjeta ("adjuntos").
//
// Storage propio: bs-data/tablero/obras.json
//   Vive aparte de clientes/ a propósito: no cambia el JSON contract del
//   registry y la calc/hub siguen funcionando igual si este archivo falta.
//   Clave de cada obra: "{cliente_nro}-{sub}".
// ============================================================

require_once __DIR__ . '/_tablero.php';
require_once __DIR__ . '/_auth.php';

// Leer: quien ve el tablero. Cambiar: quien lo opera; salvo el dato de la bacha (de quién es y
// qué modelo), que también lo puede cargar quien tiene 'tablero_bacha' sin operar nada más.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    bs_require_permiso('tablero');
} else {
    $bs_pre = json_decode((string)@file_get_contents('php://input'), true);
    bs_require_permiso((is_array($bs_pre) && ($bs_pre['accion'] ?? '') === 'bacha') ? 'tablero_bacha' : 'tablero_operar');
}
bs_check_csrf();
bs_ensure_dirs();

date_default_timezone_set('America/Argentina/Buenos_Aires');

// Las funciones que leen una obra (material, seña, historial, acceso, zona) viven en _tablero.php:
// las usa también el cierre del mes (cierre-mes.php).

// ============================================================
// GET — listar tarjetas
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tablero = bs_tablero_leer();
    $cards = [];
    $en_papelera = 0; // fichas borradas que todavía se pueden recuperar
    // Obras archivadas (entregadas) que pasaron por el tablero: se pueden devolver desde "Archivadas".
    // Las ventas históricas cargadas a mano en el hub no tienen registro en el tablero y no cuentan.
    $archivadas = [];

    if (is_dir(BS_CLIENTES_DIR)) {
        foreach (scandir(BS_CLIENTES_DIR) as $f) {
            if (!preg_match('/^(\d{4,})\.json$/', $f)) continue;
            $cliente_obj = bs_read_json(BS_CLIENTES_DIR . '/' . $f);
            if (!$cliente_obj) continue;

            $cli = $cliente_obj['cliente'] ?? [];
            $cliente_nro = (string)($cliente_obj['cliente_nro'] ?? '');
            $cots = $cliente_obj['cotizaciones'] ?? [];

            foreach ($cots as $cot) {
                if (($cot['estado'] ?? '') === 'papelera') $en_papelera++;
                if (($cot['estado'] ?? '') === 'entregado' && isset($tablero['obras'][$cliente_nro . '-' . intval($cot['sub'] ?? 0)])) {
                    $at = strtotime((string)($cot['entregado_at'] ?? '')) ?: time();
                    $archivadas[] = [
                        'key' => $cliente_nro . '-' . intval($cot['sub'] ?? 0), 'cliente_nro' => $cliente_nro, 'sub' => intval($cot['sub'] ?? 0),
                        'nombre' => (string)($cli['nombre'] ?? ''), 'material' => bs_tablero_material($cot)[0], 'manual' => !empty($cot['manual']),
                        'archivada' => date('Y-m-d', $at),
                        'quedan' => max(0, BS_ARCHIVADAS_DAYS - intval(floor((time() - $at) / 86400))),
                    ];
                }
                if (($cot['estado'] ?? '') !== 'aprobado') continue;

                $sub = intval($cot['sub'] ?? 0);
                $key = $cliente_nro . '-' . $sub;
                $obra = array_merge(bs_tablero_obra_vacia(), $tablero['obras'][$key] ?? []);
                if (!isset($BS_ETAPAS[$obra['etapa']])) $obra['etapa'] = 'medir';
                $obra = bs_tablero_normalizar($obra);

                list($material, $plazo) = bs_tablero_material($cot);

                // Plazo prometido: medición + plazo en días CORRIDOS (cuentan sábados,
                // domingos y feriados). 'limite' es ese día exacto; 'semana', el lunes
                // de su semana. Sin medida tomada todavía no hay estimación.
                // El plazo se puede fijar a mano (desde el calendario): si hay uno,
                // manda sobre el calculado. 'limite_auto' queda como referencia.
                $semana = '';
                $limite_auto = '';
                if (bs_tablero_fecha_valida($obra['medido_at'])) {
                    $limite_auto = date('Y-m-d', strtotime($obra['medido_at'] . ' +' . $plazo . ' days'));
                }
                $plazo_manual = bs_tablero_fecha_valida((string)$obra['plazo']);
                $limite_dia = $plazo_manual ? (string)$obra['plazo'] : $limite_auto;
                if ($limite_dia !== '') {
                    $limite = strtotime($limite_dia);
                    $semana = date('Y-m-d', strtotime('-' . (intval(date('N', $limite)) - 1) . ' days', $limite));
                }

                // Lugar en la cola: desde cuándo está en esta etapa.
                $desde = (string)$obra['desde'];
                if ($desde === '') $desde = bs_tablero_aprobado_at($cot);

                list($historia, $hitos) = bs_tablero_historia($obra);

                $cards[] = [
                    'key'         => $key,
                    'cliente_nro' => $cliente_nro,
                    'sub'         => $sub,
                    'nombre'      => (string)($cli['nombre'] ?? ''),
                    'celular'     => (string)($cli['celular'] ?? ''),
                    'direccion'   => (string)($cli['direccion'] ?? ''),
                    'concepto'    => (string)($cot['concepto'] ?? ''),
                    'material'    => $material,
                    'zona'        => bs_tablero_zona($cot),
                    // Las obras cargadas a mano no tienen presupuesto detallado para abrir.
                    'manual'      => !empty($cot['manual']), // cargada a mano desde el tablero
                    // El N° de su contrato enlazado, para mostrar en vez de "Sin N°" (solo fichas a mano).
                    'nro_contrato' => !empty($cot['manual']) ? (string)($obra['nro_contrato'] ?? '') : '',
                    'tiene_detalle' => empty($cot['manual']) && isset($cot['presupuesto']) && is_array($cot['presupuesto']),
                    'etapa'       => $obra['etapa'],
                    'fecha'       => bs_tablero_fecha_valida($obra['fecha']) ? $obra['fecha'] : '',
                    'hora'        => (bs_tablero_fecha_valida($obra['fecha']) && bs_tablero_hora_valida($obra['hora'])) ? $obra['hora'] : '',
                    'checks'      => (object)array_filter((array)$obra['checks']),
                    'medido_at'   => bs_tablero_fecha_valida($obra['medido_at']) ? $obra['medido_at'] : '',
                    // Día en que se cumplió el botón de la etapa actual (medido / cortado).
                    'boton_at'    => (function ($o) use ($BS_ETAPAS) {
                        $b = (string)($BS_ETAPAS[$o['etapa']]['boton'] ?? '');
                        $at = in_array($b, ['medido', 'cortado', 'fabricado', 'instalado'], true) ? ($o[$b . '_at'] ?? '') : '';
                        return bs_tablero_fecha_valida((string)$at) ? $at : '';
                    })($obra),
                    // La nota es texto libre y a veces lleva plata ("Saldo $ …"): a quien no la ve se le tapa el monto.
                    // Quién dio cada aprobación que está tildada: { qc_medicion: "Nombre", … }
                    'por'         => (object)array_filter(array_map(function ($cid) use ($obra) {
                        return empty($obra['checks'][$cid]) ? '' : bs_tablero_tilde_por($obra, $cid);
                    }, array_combine(array_keys($BS_TILDES_APROBACION), array_keys($BS_TILDES_APROBACION)))),
                    'nota'        => auth_puede('tablero_plata') ? (string)$obra['nota'] : auth_tapar_plata((string)$obra['nota']),
                    'historia'    => $historia,
                    'hitos'       => $hitos,
                    // La seña solo la ve quien tiene permiso; al resto no le sale del servidor.
                    'sena'        => auth_puede('tablero_plata') ? bs_tablero_sena($obra) : null,
                    'acceso'      => bs_tablero_acceso($obra),
                    'bacha'       => in_array($obra['bacha'], ['nuestra', 'cliente', 'no'], true) ? $obra['bacha'] : '',
                    'bacha_modelo' => (string)($obra['bacha_modelo'] ?? ''),
                    'equipo'      => array_values(array_intersect($BS_INSTALADORES, (array)$obra['equipo'])),
                    'fabrica'     => array_values(array_intersect($BS_FABRICADORES, (array)($obra['fabrica'] ?? []))),
                    'cortador'    => array_values(array_intersect($BS_CORTADORES, (array)($obra['cortador'] ?? []))),
                    'medidor'     => array_values(array_intersect($BS_MEDIDORES, (array)($obra['medidor'] ?? []))),
                    'adjuntos'    => array_map(function ($a) {
                        return ['id' => $a['id'], 'tipo' => $a['tipo'], 'pdf' => $a['ext'] === 'pdf', 'link' => $a['ext'] === 'link' ? $a['url'] : '',
                            // ¿lo puede borrar o girar el que está mirando? (los suyos siempre; los ajenos, con permiso)
                            'mio' => bs_tablero_adj_mio($a)];
                    }, bs_tablero_adjuntos($obra)),
                    'semana'      => $semana,
                    'limite'      => $limite_dia,
                    'limite_auto' => $limite_auto,
                    'plazo_manual' => $plazo_manual,
                    'plazo_dias'  => $plazo,
                    'desde'       => $desde,
                ];
            }
        }
    }

    $etapas_out = [];
    $con_obras = [];
    foreach ($cards as $c) $con_obras[$c['etapa']] = true;
    foreach ($BS_ETAPAS as $id => $def) {
        // Una etapa desactivada solo se informa si todavía tiene alguna obra adentro.
        if (!empty($def['oculta']) && empty($con_obras[$id])) continue;
        $checks = [];
        foreach ($def['checks'] as $cid => $label) $checks[] = ['id' => $cid, 'label' => $label];
        $etapas_out[] = ['id' => $id, 'titulo' => $def['titulo'], 'aparte' => !empty($def['aparte']), 'oculta' => !empty($def['oculta']), 'fecha' => $def['fecha'], 'cierra' => $def['cierra'], 'exige_fecha' => !empty($def['exige_fecha']), 'pide_hora' => !empty($def['pide_hora']), 'avisa_fecha' => !empty($def['avisa_fecha']), 'boton' => (string)($def['boton'] ?? ''), 'boton_txt' => (string)($def['boton_txt'] ?? ''), 'boton_listo' => (string)($def['boton_listo'] ?? ''), 'pendientes' => (string)($def['pendientes'] ?? ''), 'urge' => array_values((array)($def['urge'] ?? [])), 'en_manos' => array_values((array)($def['en_manos'] ?? [])), 'hecha_txt' => array_values((array)($def['hecha_txt'] ?? [])), 'urge_txt' => (string)($def['urge_txt'] ?? ''), 'urge_ok_txt' => (string)($def['urge_ok_txt'] ?? ''), 'hecho' => (string)($def['hecho'] ?? ''), 'checks' => $checks];
    }

    if (isset($_GET['archivadas'])) {
        bs_require_permiso('tablero_operar');
        usort($archivadas, function ($a, $b) { return strcmp($b['archivada'], $a['archivada']); });
        bs_ok(['dias' => BS_ARCHIVADAS_DAYS, 'obras' => $archivadas]);
    }
    bs_ok(['hoy' => date('Y-m-d'), 'papelera' => auth_puede('tablero_operar') ? $en_papelera : 0, 'archivadas' => auth_puede('tablero_operar') ? count($archivadas) : 0, 'ventana' => BS_PLAZO_VENTANA, 'etapas' => $etapas_out, 'instaladores' => $BS_INSTALADORES, 'fabricadores' => $BS_FABRICADORES, 'cortadores' => $BS_CORTADORES, 'medidores' => $BS_MEDIDORES,
        // Los tildes que son aprobaciones (muestran quién los dio) y si el que mira puede darlas.
        'aprobaciones' => array_keys($BS_TILDES_APROBACION), 'puede_aprobar' => auth_puede('tablero_aprobar'), 'cards' => $cards]);
}

// ============================================================
// POST — modificar una tarjeta
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    bs_error('Solo GET o POST', 405);
}

$body = json_decode(@file_get_contents('php://input'), true);
if (!is_array($body)) bs_error('Body JSON inválido');

$cliente_nro = str_pad((string)intval($body['cliente_nro'] ?? ''), 4, '0', STR_PAD_LEFT);
$sub = intval($body['sub'] ?? 0);
$accion = (string)($body['accion'] ?? '');

if (intval($cliente_nro) <= 0) bs_error('cliente_nro inválido');
if ($sub <= 0) bs_error('sub inválido');
if (!in_array($accion, ['check', 'fecha', 'plazo', 'nota', 'bacha', 'nro_contrato', 'mover', 'borrar'], true)) bs_error('accion inválida');

// La obra tiene que existir como cotización aprobada: el tablero no crea
// obras, solo acompaña a las que el hub ya aprobó.
$cliente_obj = bs_read_json(BS_CLIENTES_DIR . '/' . $cliente_nro . '.json');
if (!$cliente_obj) bs_error('Cliente no encontrado', 404);
$aprobada = false; $es_manual = false;
foreach ($cliente_obj['cotizaciones'] ?? [] as $cot) {
    if (intval($cot['sub'] ?? 0) === $sub && ($cot['estado'] ?? '') === 'aprobado') { $aprobada = true; $es_manual = !empty($cot['manual']); break; }
}
if (!$aprobada) bs_error('Esa obra ya no está aprobada. Actualizá el tablero.', 409);

if (!is_dir(BS_TABLERO_DIR) && !@mkdir(BS_TABLERO_DIR, 0755, true)) {
    bs_error('No se pudo crear el directorio del tablero', 500);
}
$fp = bs_lock_acquire(BS_TABLERO_LOCK);
if (!$fp) bs_error('No se pudo adquirir lock', 500);

try {
    $tablero = bs_tablero_leer();
    $key = $cliente_nro . '-' . $sub;
    $obra = array_merge(bs_tablero_obra_vacia(), $tablero['obras'][$key] ?? []);
    if (!isset($BS_ETAPAS[$obra['etapa']])) $obra['etapa'] = 'medir';
    $obra = bs_tablero_normalizar($obra);
    if (!is_array($obra['hist'])) $obra['hist'] = [];

    $hoy = date('Y-m-d');
    $etapa = $obra['etapa'];

    // Borrar la ficha: se van sus tildes, datos, fotos y planos. Lo que
    // pasa con el presupuesto lo decide el tablero después (delete.php si
    // la obra se cargó a mano, state.php → "enviado" si vino de la calc).
    if ($accion === 'borrar') {
        bs_tablero_borrar_carpeta($key);
        unset($tablero['obras'][$key]);
        if (!bs_write_json_atomic(BS_TABLERO_FILE, $tablero)) {
            bs_lock_release($fp);
            bs_error('No se pudo guardar el tablero', 500);
        }
        bs_lock_release($fp);
        bs_ok(['key' => $key, 'borrada' => true]);
    }

    if ($accion === 'check') {
        $clave = (string)($body['clave'] ?? '');
        $valor = !empty($body['valor']);
        if (!isset($BS_ETAPAS[$etapa]['checks'][$clave])) {
            bs_lock_release($fp);
            bs_error('Ese tilde no es de esta etapa. Actualizá el tablero.', 409);
        }
        // Las aprobaciones: las da quien tiene el permiso, y no se aprueba lo que todavía no se hizo.
        if (isset($BS_TILDES_APROBACION[$clave])) {
            if (!auth_puede('tablero_aprobar')) {
                bs_lock_release($fp);
                bs_error('Tu usuario no puede dar aprobaciones.', 403);
            }
            $pide = $BS_TILDES_APROBACION[$clave]['pide'];
            if ($valor && $pide !== '' && empty($obra['checks'][$pide])) {
                bs_lock_release($fp);
                bs_error($BS_TILDES_APROBACION[$clave]['falta']);
            }
        }

        // La seña es el bloqueante de salida de "Por medir": no alcanza con
        // tildar, hay que cargar cuánto se cobró.
        if ($clave === 'sena') {
            if ($valor) {
                $monto = round(floatval($body['monto'] ?? 0), 2);
                $moneda = (($body['moneda'] ?? '') === 'USD') ? 'USD' : 'ARS';
                if ($monto <= 0 || $monto > 999999999) {
                    bs_lock_release($fp);
                    bs_error('Cargá el monto de la seña.');
                }
                $obra['sena'] = ['monto' => $monto, 'moneda' => $moneda, 'at' => $hoy];
            } else {
                $obra['sena'] = null;
            }
        }

        // Piso y ascensor es un dato de la medición que después usa el que
        // instala (por escalera se cobra por piso): se guarda cómo sube.
        if ($clave === 'acceso') {
            if ($valor) {
                $modo = (string)($body['modo'] ?? '');
                $pisos = intval($body['pisos'] ?? 0);
                if (!in_array($modo, ['pb', 'ascensor', 'escalera'], true)) {
                    bs_lock_release($fp);
                    bs_error('Elegí cómo sube la pieza.');
                }
                if ($modo === 'escalera' && ($pisos < 1 || $pisos > 60)) {
                    bs_lock_release($fp);
                    bs_error('Cargá cuántos pisos por escalera.');
                }
                $obra['acceso'] = ['modo' => $modo, 'pisos' => ($modo === 'escalera') ? $pisos : 0];
            } else {
                $obra['acceso'] = null;
            }
        }

        // La bacha: de quién es (la proveemos nosotros, la trae el cliente, o no lleva).
        if ($clave === 'bacha') {
            if ($valor) {
                $origen = (string)($body['origen'] ?? '');
                if (!in_array($origen, ['nuestra', 'cliente', 'no'], true)) {
                    bs_lock_release($fp);
                    bs_error('Elegí de quién es la bacha.');
                }
                // El modelo de la bacha se carga junto con el tilde: es lo que el
                // cortador necesita para el traforo. Es obligatorio solo cuando la
                // bacha es nuestra (la proveemos y sabemos cuál es). Si es del
                // cliente es opcional, y si la obra no lleva bacha no aplica.
                $modelo = trim(preg_replace('/\s+/u', ' ', (string)($body['modelo'] ?? '')));
                if (mb_strlen($modelo) > 80) {
                    bs_lock_release($fp);
                    bs_error('El modelo de la bacha es demasiado largo.');
                }
                if ($origen === 'no') $modelo = '';
                elseif ($origen === 'nuestra' && $modelo === '') {
                    bs_lock_release($fp);
                    bs_error('Poné el modelo de la bacha.');
                }
                $obra['bacha'] = $origen;
                $obra['bacha_modelo'] = $modelo;
            } else {
                $obra['bacha'] = '';
                $obra['bacha_modelo'] = '';
            }
        }

        // Instaladores y fabricadores: uno o varios de su lista.
        if (isset($BS_TILDES_PERSONAS[$clave])) {
            list($lista, $campo) = $BS_TILDES_PERSONAS[$clave];
            if ($valor) {
                $elegidos = array_values(array_intersect($lista, (array)($body['personas'] ?? $body['equipo'] ?? [])));
                if (!$elegidos) {
                    bs_lock_release($fp);
                    $quien = ['medidor' => 'quién mide', 'cortador' => 'quién corta', 'fabrica' => 'quiénes fabrican', 'equipo' => 'quiénes instalan'];
                    bs_error('Elegí ' . $quien[$clave] . '.');
                }
                $obra[$campo] = $elegidos;
            } else {
                $obra[$campo] = [];
            }
        }

        if ($valor) $obra['checks'][$clave] = true;
        else unset($obra['checks'][$clave]);

        // Cuándo se tildó cada cosa: es lo que después arma el historial de la obra.
        if (!is_array($obra['tildes_at'] ?? null)) $obra['tildes_at'] = [];
        if ($valor) $obra['tildes_at'][$clave] = $hoy;
        else unset($obra['tildes_at'][$clave]);

        // Quién lo tildó (desde que hay usuarios). Se muestra en las aprobaciones.
        if (!is_array($obra['tildes_por'] ?? null)) $obra['tildes_por'] = [];
        $bs_yo = auth_usuario();
        if ($valor && $bs_yo && empty($bs_yo['viejo'])) $obra['tildes_por'][$clave] = ['u' => $bs_yo['usuario'], 'n' => $bs_yo['nombre']];
        else unset($obra['tildes_por'][$clave]);

        // Si se saca el tilde del que depende una aprobación, la aprobación se cae con él:
        // sacar "Medido" deja sin efecto "Medición aprobada".
        if (!$valor) {
            foreach ($BS_TILDES_APROBACION as $ap => $regla) {
                if ($regla['pide'] === $clave && !empty($obra['checks'][$ap])) {
                    unset($obra['checks'][$ap], $obra['tildes_at'][$ap], $obra['tildes_por'][$ap]);
                }
            }
        }

        // "Medido", "Cortado" y "Fabricado" son los botones de etapa: guardan el día en
        // que se hicieron. Son independientes de los demás tildes: tildar
        // "Medidas y planos listos" NO marca la obra como medida, ni
        // "Corte aprobado" la marca como cortada. Así un tilde común nunca
        // le cambia a una ficha su lugar en la cola.
        if ($clave === 'medido') {
            if ($valor) {
                // Desde ese día se cuenta el plazo de entrega: la fecha de
                // medición cargada si ya pasó; si no, hoy.
                $f = $obra['fecha'];
                $obra['medido_at'] = (bs_tablero_fecha_valida($f) && $f <= $hoy) ? $f : $hoy;
            } else {
                $obra['medido_at'] = '';
            }
        }
        if ($clave === 'cortado' || $clave === 'fabricado' || $clave === 'instalado') {
            $obra[$clave . '_at'] = $valor ? $hoy : '';
        }

    } elseif ($accion === 'fecha') {
        $fecha = trim((string)($body['fecha'] ?? ''));
        if ($fecha !== '' && !bs_tablero_fecha_valida($fecha)) {
            bs_lock_release($fp);
            bs_error('Fecha inválida');
        }
        $obra['fecha'] = $fecha;

        // La hora solo existe en las etapas que la piden, y siempre atada a una fecha.
        $hora = trim((string)($body['hora'] ?? ''));
        if ($fecha === '' || empty($BS_ETAPAS[$etapa]['pide_hora'])) $hora = '';
        if ($hora !== '' && !bs_tablero_hora_valida($hora)) {
            bs_lock_release($fp);
            bs_error('Hora inválida');
        }
        $obra['hora'] = $hora;

    } elseif ($accion === 'plazo') {
        // Plazo de entrega fijado a mano. Vacío = volver al calculado
        // (medición + 20 días corridos).
        $fecha = trim((string)($body['fecha'] ?? ''));
        if ($fecha !== '' && !bs_tablero_fecha_valida($fecha)) {
            bs_lock_release($fp);
            bs_error('Fecha inválida');
        }
        $obra['plazo'] = $fecha;

    } elseif ($accion === 'nota') {
        $nota = trim(preg_replace('/\s+/u', ' ', (string)($body['nota'] ?? '')));
        if (mb_strlen($nota) > BS_TABLERO_NOTA_MAX) {
            bs_lock_release($fp);
            bs_error('La nota es muy larga (máximo ' . BS_TABLERO_NOTA_MAX . ' caracteres)');
        }
        $obra['nota'] = $nota;

    } elseif ($accion === 'nro_contrato') {
        // El N° que se ve en una ficha cargada a mano: el de su contrato enlazado (dueño, 17/11/2026).
        // Lo escribe Relación-Contrato cuando el contrato tiene N°; vacío lo borra. No muda la ficha a
        // ningún presupuesto ni cambia su clave: es solo el número que se muestra en vez de "Sin N°".
        // Una ficha que viene de un presupuesto ya tiene su número y no lo lleva.
        $nro = trim((string)($body['nro'] ?? ''));
        if ($nro !== '' && !preg_match('/^\d{1,6}(?:-\d{1,3})?$/', $nro)) {
            bs_lock_release($fp);
            bs_error('El N° va como 0042, o 0042-2 si tiene versiones.');
        }
        if (!$es_manual) {
            bs_lock_release($fp);
            bs_error('Esa ficha viene de un presupuesto: ya tiene su N°.', 409);
        }
        $obra['nro_contrato'] = $nro;

    } elseif ($accion === 'bacha') {
        // De quién es la bacha, anotado al cargar la obra. Es un dato, no el
        // tilde: "Tenemos la bacha" se sigue tildando a mano cuando la bacha
        // está en el taller (ahí se exige el modelo si es nuestra). La única
        // excepción es "no lleva": no hay nada que esperar, queda tildado.
        // Vale en cualquier etapa: el modelo se puede cargar o corregir
        // cuando la obra ya salió de Por medir (lo usa "Editar obra").
        $origen = (string)($body['origen'] ?? '');
        if (!in_array($origen, ['nuestra', 'cliente', 'no'], true)) {
            bs_lock_release($fp);
            bs_error('Elegí de quién es la bacha.');
        }
        $modelo = trim(preg_replace('/\s+/u', ' ', (string)($body['modelo'] ?? '')));
        if (mb_strlen($modelo) > 80) {
            bs_lock_release($fp);
            bs_error('El modelo de la bacha es demasiado largo.');
        }
        if ($origen === 'no') $modelo = '';
        elseif ($origen === 'nuestra' && $modelo === '' && !empty($obra['checks']['bacha'])) {
            bs_lock_release($fp);
            bs_error('Poné el modelo de la bacha.');
        }
        $obra['bacha'] = $origen;
        $obra['bacha_modelo'] = $modelo;
        if ($origen === 'no' && isset($BS_ETAPAS[$etapa]['checks']['bacha'])) {
            $obra['checks']['bacha'] = true;
            if (!is_array($obra['tildes_at'] ?? null)) $obra['tildes_at'] = [];
            $obra['tildes_at']['bacha'] = $hoy;
        }

    } else { // mover
        // Destino: una etapa puntual ("a", lo que manda el arrastre) o un
        // paso relativo ("hacia", lo que manda el botón Siguiente).
        $en_retiro = !empty($BS_ETAPAS[$etapa]['aparte']);
        $idx = array_search($etapa, $BS_ETAPA_IDS, true);
        $hacia = (string)($body['hacia'] ?? '');
        $nueva = (string)($body['a'] ?? '');

        if ($nueva === '') {
            if ($hacia === 'retiro') {
                $nueva = 'retiro';
            } elseif ($hacia === 'adelante' && !$en_retiro && $idx < count($BS_ETAPA_IDS) - 1) {
                $nueva = $BS_ETAPA_IDS[$idx + 1];
            } elseif ($hacia === 'adelante') {
                bs_lock_release($fp);
                bs_error('Ya está en la última etapa. Para cerrarla, archivala.');
            } elseif ($hacia === 'atras' && $en_retiro) {
                // Vuelve a la columna de la que salió.
                $nueva = $BS_ETAPA_IDS[0];
                foreach ($obra['hist'] as $h) {
                    if (($h['a'] ?? '') === 'retiro' && in_array($h['de'] ?? '', $BS_ETAPA_IDS, true)) $nueva = $h['de'];
                }
            } elseif ($hacia === 'atras' && $idx > 0) {
                $nueva = $BS_ETAPA_IDS[$idx - 1];
            } elseif ($hacia === 'atras') {
                bs_lock_release($fp);
                bs_error('Ya está en la primera etapa.');
            }
        }
        if (!isset($BS_ETAPAS[$nueva])) {
            bs_lock_release($fp);
            bs_error('Etapa de destino inválida');
        }
        if (!empty($BS_ETAPAS[$nueva]['oculta'])) {
            bs_lock_release($fp);
            bs_error('"' . $BS_ETAPAS[$nueva]['titulo'] . '" está desactivado por ahora.');
        }
        if ($nueva === $etapa) {
            bs_lock_release($fp);
            bs_ok(['key' => $key, 'etapa' => $etapa]);
        }

        // Regla única, valga para el botón o para el arrastre: hacia atrás
        // o hacia Retiro se mueve libre; hacia adelante hay que tener
        // completos los tildes de la etapa actual (y de las que saltee).
        $destino = array_search($nueva, $BS_ETAPA_IDS, true);
        $retrocede = !$en_retiro && $destino !== false && $destino < $idx;
        if ($destino !== false && !$retrocede) {
            $faltan = [];
            // La fecha de la etapa actual, si esa etapa la exige para salir.
            if (!$en_retiro && !empty($BS_ETAPAS[$etapa]['exige_fecha'])) {
                $nombre = mb_strtolower($BS_ETAPAS[$etapa]['fecha']);
                $con_hora = !empty($BS_ETAPAS[$etapa]['pide_hora']);
                if (!bs_tablero_fecha_valida($obra['fecha'])) {
                    $faltan[] = ($con_hora ? 'Fecha y hora de ' : 'Fecha de ') . $nombre;
                } elseif ($con_hora && !bs_tablero_hora_valida($obra['hora'])) {
                    $faltan[] = 'Hora de ' . $nombre;
                }
            }
            // Tildes de la etapa en la que está y de las que se saltearía. Lo
            // de más atrás no se vuelve a pedir (una obra importada puede
            // haber entrado directo a una etapa avanzada).
            for ($i = ($en_retiro ? 0 : $idx); $i < $destino; $i++) {
                foreach ($BS_ETAPAS[$BS_ETAPA_IDS[$i]]['checks'] as $cid => $label) {
                    if (empty($obra['checks'][$cid])) $faltan[] = $label;
                }
            }
            if ($faltan) {
                bs_lock_release($fp);
                bs_error('Falta: ' . implode(', ', $faltan));
            }
        }

        // Cada etapa tiene su propia fecha: al cambiar de etapa se guarda
        // la anterior en el historial y la tarjeta queda sin fecha.
        $obra['hist'][] = ['de' => $etapa, 'a' => $nueva, 'fecha' => $obra['fecha'], 'hora' => $obra['hora'], 'at' => date('c')];
        $obra['etapa'] = $nueva;
        $obra['fecha'] = '';
        $obra['hora'] = '';
        $obra['desde'] = date('c'); // entra última a la cola de la etapa nueva
    }

    $obra['upd'] = date('c');
    $tablero['obras'][$key] = $obra;

    if (!bs_write_json_atomic(BS_TABLERO_FILE, $tablero)) {
        bs_lock_release($fp);
        bs_error('No se pudo guardar el tablero', 500);
    }

    bs_lock_release($fp);
    bs_ok(['key' => $key, 'etapa' => $obra['etapa']]);

} catch (Throwable $e) {
    bs_lock_release($fp);
    bs_error('Excepción: ' . $e->getMessage(), 500);
}
