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

bs_require_auth();
bs_check_csrf();
bs_ensure_dirs();

date_default_timezone_set('America/Argentina/Buenos_Aires');

/**
 * Material a mostrar y plazo prometido, leídos del presupuesto guardado.
 * Si el hub tiene cargado "material_final", manda ese texto.
 */
function bs_tablero_material($cot) {
    $colores = [];
    $hay_items = false;
    $solo_granito = true;

    $secciones = $cot['presupuesto']['secciones'] ?? [];
    if (is_array($secciones)) {
        foreach ($secciones as $sec) {
            $items = (isset($sec['items']) && is_array($sec['items'])) ? $sec['items'] : [];
            $qty = isset($sec['qty']) ? intval($sec['qty']) : count($items);
            foreach (array_slice($items, 0, max($qty, 0)) as $it) {
                $color = trim((string)($it['color'] ?? ''));
                $mat   = trim((string)($it['mat'] ?? ''));
                if ($color === '' && $mat === '') continue;
                $hay_items = true;
                if (strpos($mat, 'Granito') !== 0) $solo_granito = false;
                if ($color !== '' && !in_array($color, $colores, true)) $colores[] = $color;
            }
        }
    }

    $texto = trim((string)($cot['material_final'] ?? ''));
    if ($texto === '') {
        $texto = implode(' + ', array_slice($colores, 0, 2));
        if (count($colores) > 2) $texto .= ' …';
    }

    $plazo = ($hay_items && $solo_granito) ? BS_PLAZO_GRANITO : BS_PLAZO_RESTO;
    return [$texto, $plazo];
}

/** Cuándo se aprobó el presupuesto: es el momento en que la obra entró al tablero. */
function bs_tablero_aprobado_at($cot) {
    $at = '';
    foreach ((array)($cot['transiciones'] ?? []) as $t) {
        if (($t['estado'] ?? '') === 'aprobado' && !empty($t['at'])) $at = (string)$t['at'];
    }
    if ($at === '') $at = (string)($cot['fecha'] ?? '');
    return $at;
}

function bs_tablero_sena($obra) {
    $s = $obra['sena'] ?? null;
    if (!is_array($s) || floatval($s['monto'] ?? 0) <= 0) return null;
    return ['monto' => floatval($s['monto']), 'moneda' => (($s['moneda'] ?? '') === 'USD') ? 'USD' : 'ARS'];
}

/**
 * Historial de la obra: qué se hizo en cada etapa que ya pasó, y cuándo.
 * Devuelve [historia, hitos]:
 *   historia → por etapa anterior: sus tildes cumplidos con fecha y dato, y el día en que salió de ahí
 *   hitos    → lo más importante, para ver de un vistazo en la ficha (Medido, Cortado, Corte aprobado…)
 * Las fechas de tildes existen desde que se guardan (tildes_at); lo anterior figura sin fecha.
 */
function bs_tablero_historia($obra) {
    global $BS_ETAPAS, $BS_ETAPA_IDS, $BS_TILDES_PERSONAS;
    $historia = []; $hitos = [];
    $actual = array_search($obra['etapa'], $BS_ETAPA_IDS, true);
    if ($actual === false) return [$historia, $hitos];
    $tildes_at = is_array($obra['tildes_at'] ?? null) ? $obra['tildes_at'] : [];

    for ($i = 0; $i < $actual; $i++) {
        $eid = $BS_ETAPA_IDS[$i];
        $def = $BS_ETAPAS[$eid];
        $boton = (string)($def['boton'] ?? '');
        $items = [];
        foreach ($def['checks'] as $cid => $label) {
            if (empty($obra['checks'][$cid])) continue;
            // El botón de etapa tiene su propia fecha (la medición puede ser anterior al día en que se tildó).
            $fecha = (string)($tildes_at[$cid] ?? '');
            if ($cid === $boton && bs_tablero_fecha_valida((string)($obra[$cid . '_at'] ?? ''))) $fecha = $obra[$cid . '_at'];
            if (!bs_tablero_fecha_valida($fecha)) $fecha = '';

            $txt = $label;
            if (isset($BS_TILDES_PERSONAS[$cid])) {
                $quienes = array_values(array_intersect($BS_TILDES_PERSONAS[$cid][0], (array)($obra[$BS_TILDES_PERSONAS[$cid][1]] ?? [])));
                if ($quienes) $txt .= ': ' . implode(', ', $quienes);
            } elseif ($cid === 'sena' && ($s = bs_tablero_sena($obra))) {
                $txt .= ': ' . ($s['moneda'] === 'USD' ? 'USD ' : '$ ') . number_format($s['monto'], 0, ',', '.');
            } elseif ($cid === 'bacha' && in_array($obra['bacha'] ?? '', ['nuestra', 'cliente', 'no'], true)) {
                $txt = ['nuestra' => 'Bacha nuestra', 'cliente' => 'Bacha del cliente', 'no' => 'No lleva bacha'][$obra['bacha']];
                if ($obra['bacha'] !== 'no' && trim((string)($obra['bacha_modelo'] ?? '')) !== '') $txt .= ' · ' . trim((string)$obra['bacha_modelo']);
            } elseif ($cid === 'acceso' && ($a = bs_tablero_acceso($obra))) {
                $txt .= ': ' . ($a['modo'] === 'pb' ? 'planta baja' : ($a['modo'] === 'ascensor' ? 'por ascensor' : 'por escalera, ' . $a['pisos'] . ($a['pisos'] === 1 ? ' piso' : ' pisos')));
            }
            $items[] = ['txt' => $txt, 'fecha' => $fecha];
            if ($cid === $boton || in_array($cid, (array)($def['hitos'] ?? []), true)) $hitos[] = ['txt' => $label, 'fecha' => $fecha];
        }
        // Día en que salió de esa etapa (el último, si pasó más de una vez).
        $salio = '';
        foreach ((array)($obra['hist'] ?? []) as $h) {
            if (($h['de'] ?? '') === $eid && !empty($h['at'])) $salio = substr((string)$h['at'], 0, 10);
        }
        $historia[] = ['etapa' => $def['titulo'], 'items' => $items, 'salio' => bs_tablero_fecha_valida($salio) ? $salio : ''];
    }
    return [$historia, $hitos];
}

/** Cómo sube la pieza: planta baja, por ascensor o por escalera (con cuántos pisos). */
function bs_tablero_acceso($obra) {
    $a = $obra['acceso'] ?? null;
    if (!is_array($a) || !in_array($a['modo'] ?? '', ['pb', 'ascensor', 'escalera'], true)) return null;
    return ['modo' => $a['modo'], 'pisos' => ($a['modo'] === 'escalera') ? max(1, intval($a['pisos'] ?? 1)) : 0];
}

function bs_tablero_zona($cot) {
    $flete = $cot['presupuesto']['adicionales']['flete'] ?? [];
    $zona = trim((string)($flete['zona_nombre'] ?? ''));
    if ($zona === '') $zona = trim((string)($flete['zona'] ?? ''));
    if (in_array($zona, ['Sin flete', 'Otra zona', 'manual', '0'], true)) return '';
    return $zona;
}

// ============================================================
// GET — listar tarjetas
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tablero = bs_tablero_leer();
    $cards = [];

    if (is_dir(BS_CLIENTES_DIR)) {
        foreach (scandir(BS_CLIENTES_DIR) as $f) {
            if (!preg_match('/^(\d{4,})\.json$/', $f)) continue;
            $cliente_obj = bs_read_json(BS_CLIENTES_DIR . '/' . $f);
            if (!$cliente_obj) continue;

            $cli = $cliente_obj['cliente'] ?? [];
            $cliente_nro = (string)($cliente_obj['cliente_nro'] ?? '');
            $cots = $cliente_obj['cotizaciones'] ?? [];

            foreach ($cots as $cot) {
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
                    'tiene_detalle' => empty($cot['manual']) && isset($cot['presupuesto']) && is_array($cot['presupuesto']),
                    'etapa'       => $obra['etapa'],
                    'fecha'       => bs_tablero_fecha_valida($obra['fecha']) ? $obra['fecha'] : '',
                    'hora'        => (bs_tablero_fecha_valida($obra['fecha']) && bs_tablero_hora_valida($obra['hora'])) ? $obra['hora'] : '',
                    'checks'      => (object)array_filter((array)$obra['checks']),
                    'medido_at'   => bs_tablero_fecha_valida($obra['medido_at']) ? $obra['medido_at'] : '',
                    // Día en que se cumplió el botón de la etapa actual (medido / cortado).
                    'boton_at'    => (function ($o) use ($BS_ETAPAS) {
                        $b = (string)($BS_ETAPAS[$o['etapa']]['boton'] ?? '');
                        $at = in_array($b, ['medido', 'cortado', 'fabricado'], true) ? ($o[$b . '_at'] ?? '') : '';
                        return bs_tablero_fecha_valida((string)$at) ? $at : '';
                    })($obra),
                    'nota'        => (string)$obra['nota'],
                    'historia'    => $historia,
                    'hitos'       => $hitos,
                    'sena'        => bs_tablero_sena($obra),
                    'acceso'      => bs_tablero_acceso($obra),
                    'bacha'       => in_array($obra['bacha'], ['nuestra', 'cliente', 'no'], true) ? $obra['bacha'] : '',
                    'bacha_modelo' => (string)($obra['bacha_modelo'] ?? ''),
                    'equipo'      => array_values(array_intersect($BS_INSTALADORES, (array)$obra['equipo'])),
                    'fabrica'     => array_values(array_intersect($BS_FABRICADORES, (array)($obra['fabrica'] ?? []))),
                    'cortador'    => array_values(array_intersect($BS_CORTADORES, (array)($obra['cortador'] ?? []))),
                    'medidor'     => array_values(array_intersect($BS_MEDIDORES, (array)($obra['medidor'] ?? []))),
                    'adjuntos'    => array_map(function ($a) {
                        return ['id' => $a['id'], 'tipo' => $a['tipo'], 'pdf' => $a['ext'] === 'pdf'];
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
        $etapas_out[] = ['id' => $id, 'titulo' => $def['titulo'], 'aparte' => !empty($def['aparte']), 'oculta' => !empty($def['oculta']), 'fecha' => $def['fecha'], 'cierra' => $def['cierra'], 'exige_fecha' => !empty($def['exige_fecha']), 'pide_hora' => !empty($def['pide_hora']), 'avisa_fecha' => !empty($def['avisa_fecha']), 'boton' => (string)($def['boton'] ?? ''), 'boton_txt' => (string)($def['boton_txt'] ?? ''), 'boton_listo' => (string)($def['boton_listo'] ?? ''), 'pendientes' => (string)($def['pendientes'] ?? ''), 'urge' => array_values((array)($def['urge'] ?? [])), 'en_manos' => array_values((array)($def['en_manos'] ?? [])), 'hecha_txt' => array_values((array)($def['hecha_txt'] ?? [])), 'urge_txt' => (string)($def['urge_txt'] ?? ''), 'hecho' => (string)($def['hecho'] ?? ''), 'checks' => $checks];
    }

    bs_ok(['hoy' => date('Y-m-d'), 'etapas' => $etapas_out, 'instaladores' => $BS_INSTALADORES, 'fabricadores' => $BS_FABRICADORES, 'cortadores' => $BS_CORTADORES, 'medidores' => $BS_MEDIDORES, 'cards' => $cards]);
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
if (!in_array($accion, ['check', 'fecha', 'plazo', 'nota', 'mover', 'borrar'], true)) bs_error('accion inválida');

// La obra tiene que existir como cotización aprobada: el tablero no crea
// obras, solo acompaña a las que el hub ya aprobó.
$cliente_obj = bs_read_json(BS_CLIENTES_DIR . '/' . $cliente_nro . '.json');
if (!$cliente_obj) bs_error('Cliente no encontrado', 404);
$aprobada = false;
foreach ($cliente_obj['cotizaciones'] ?? [] as $cot) {
    if (intval($cot['sub'] ?? 0) === $sub && ($cot['estado'] ?? '') === 'aprobado') { $aprobada = true; break; }
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
        if ($clave === 'cortado' || $clave === 'fabricado') {
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
        // (medición + 15/20 días corridos).
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
