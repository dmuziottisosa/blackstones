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
//                      { "clave": "equipo", "valor": true, "equipo": ["Negro", "David"] }
//     accion "fecha" → { "fecha": "2026-11-24" }   ("" la borra)
//     accion "nota"  → { "nota": "Falta la bacha" } ("" la borra)
//     accion "mover" → { "a": "fabricar" }  (arrastre: etapa de destino)
//                      { "hacia": "adelante" | "atras" | "retiro" }  (botones)
//                      "retiro" saca la obra de las 4 columnas y la pasa al
//                      cuadro "Retiro en local". Desde ahí, "atras" la
//                      devuelve a la columna de la que salió.
//
// Storage propio: bs-data/tablero/obras.json
//   Vive aparte de clientes/ a propósito: no cambia el JSON contract del
//   registry y la calc/hub siguen funcionando igual si este archivo falta.
//   Clave de cada obra: "{cliente_nro}-{sub}".
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

bs_require_auth();
bs_check_csrf();
bs_ensure_dirs();

date_default_timezone_set('America/Argentina/Buenos_Aires');

define('BS_TABLERO_DIR',  BS_DATA_DIR . '/tablero');
define('BS_TABLERO_FILE', BS_TABLERO_DIR . '/obras.json');
define('BS_TABLERO_LOCK', BS_TABLERO_DIR . '/.write.lock');
define('BS_TABLERO_NOTA_MAX', 140);

// ------------------------------------------------------------
// Definición única de las etapas. El frontend dibuja desde acá:
// para cambiar un tilde o un título se toca solo este array.
//   fecha  → qué significa la fecha de la tarjeta en esa etapa
//   cierra → tilde que da por cumplida esa fecha (si falta y la fecha ya
//            pasó, la tarjeta se marca atrasada). Vacío = la fecha se cumple
//            recién cuando la tarjeta sale de la columna.
//   checks → tildes que hay que completar para pasar a la siguiente
// ------------------------------------------------------------
$BS_ETAPAS = [
    'medir' => [
        'titulo' => 'Por medir',
        'fecha'  => 'Medición',
        'cierra' => 'medida',
        'checks' => [
            'apta'   => 'Obra lista para medir',
            'medida' => 'Medidas y planos listos',
            'sena'   => 'Trajo la seña',
            'bacha'  => 'Tenemos la bacha',
            'placa'  => 'Tenemos la placa',
            'acceso' => 'Piso y ascensor',
            'equipo' => 'Instaladores',
        ],
    ],
    // Sin fecha a propósito: se fabrica en el orden en que entró (la cola).
    'fabricar' => [
        'titulo' => 'Por fabricar',
        'fecha'  => '',
        'cierra' => '',
        // Control de la pieza antes de que salga del taller.
        'checks' => [
            'qc_corte'      => 'Se cortó bien',
            'qc_medida'     => 'Medida y color correctos',
            'qc_inglete'    => 'Inglete y zócalos',
            'qc_bacha'      => 'Es la bacha correcta',
            'qc_bacha_lado' => 'Bacha hacia el lado correcto',
        ],
    ],
    'instalar' => [
        'titulo' => 'Por instalar',
        // Sin tilde de cierre: instalada = se pasa a "Por archivar". Si la
        // fecha de instalación pasó y sigue acá, se marca atrasada.
        // exige_fecha: sin fecha de instalación cargada no sale de la columna.
        'fecha'  => 'Instalación',
        'cierra' => '',
        'exige_fecha' => true,
        'checks' => [
            'avisado' => 'Se avisó al cliente',
            'equipo'  => 'Instaladores',
        ],
    ],
    'archivar' => [
        'titulo' => 'Por archivar',
        'fecha'  => '',
        'cierra' => '',
        'checks' => [
            'saldo' => 'Saldo cobrado',
        ],
    ],
    // Fuera de las 4 columnas: el cliente retira por el local.
    'retiro' => [
        'titulo' => 'Retiro en local',
        'aparte' => true,
        'fecha'  => 'Retiro',
        'cierra' => 'retirada',
        'checks' => [
            'sena'      => 'Seña cobrada',
            'terminada' => 'Pieza terminada',
            'avisado'   => 'Se avisó al cliente',
            'retirada'  => 'Retirada',
            'saldo'     => 'Saldo cobrado',
        ],
    ],
];
// Quiénes instalan. Se eligen uno o varios por obra (tilde "Instaladores").
$BS_INSTALADORES = ['Negro', 'Espinola', 'Cesar', 'David', 'Jostin'];

// Las 4 columnas, en orden. "retiro" queda afuera de la secuencia.
$BS_ETAPA_IDS = [];
foreach ($BS_ETAPAS as $id => $def) {
    if (empty($def['aparte'])) $BS_ETAPA_IDS[] = $id;
}

// Plazo que se le dice al cliente, en días corridos desde la medición.
// Regla del negocio: granito 15, todo lo demás 20. En el tablero se muestra
// como SEMANA estimada de entrega: es guía, el orden lo da la cola.
define('BS_PLAZO_GRANITO', 15);
define('BS_PLAZO_RESTO',   20);

function bs_tablero_leer() {
    $data = bs_read_json(BS_TABLERO_FILE);
    if (!is_array($data) || !isset($data['obras']) || !is_array($data['obras'])) {
        return ['version' => '1.0', 'obras' => []];
    }
    return $data;
}

function bs_tablero_obra_vacia() {
    return ['etapa' => 'medir', 'fecha' => '', 'checks' => [], 'nota' => '', 'medido_at' => '', 'sena' => null, 'acceso' => null, 'bacha' => '', 'equipo' => [], 'desde' => '', 'hist' => []];
}

function bs_tablero_fecha_valida($f) {
    if (!is_string($f) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $f, $m)) return false;
    return checkdate(intval($m[2]), intval($m[3]), intval($m[1]));
}

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

                list($material, $plazo) = bs_tablero_material($cot);

                // Semana estimada de entrega: lunes de la semana en que cae
                // medición + plazo. Sin medida tomada todavía no hay estimación.
                $semana = '';
                if (bs_tablero_fecha_valida($obra['medido_at'])) {
                    $limite = strtotime($obra['medido_at'] . ' +' . $plazo . ' days');
                    $semana = date('Y-m-d', strtotime('-' . (intval(date('N', $limite)) - 1) . ' days', $limite));
                }

                // Lugar en la cola: desde cuándo está en esta etapa.
                $desde = (string)$obra['desde'];
                if ($desde === '') $desde = bs_tablero_aprobado_at($cot);

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
                    'tiene_detalle' => isset($cot['presupuesto']) && is_array($cot['presupuesto']),
                    'etapa'       => $obra['etapa'],
                    'fecha'       => bs_tablero_fecha_valida($obra['fecha']) ? $obra['fecha'] : '',
                    'checks'      => (object)array_filter((array)$obra['checks']),
                    'nota'        => (string)$obra['nota'],
                    'sena'        => bs_tablero_sena($obra),
                    'acceso'      => bs_tablero_acceso($obra),
                    'bacha'       => in_array($obra['bacha'], ['nuestra', 'cliente', 'no'], true) ? $obra['bacha'] : '',
                    'equipo'      => array_values(array_intersect($BS_INSTALADORES, (array)$obra['equipo'])),
                    'semana'      => $semana,
                    'desde'       => $desde,
                ];
            }
        }
    }

    $etapas_out = [];
    foreach ($BS_ETAPAS as $id => $def) {
        $checks = [];
        foreach ($def['checks'] as $cid => $label) $checks[] = ['id' => $cid, 'label' => $label];
        $etapas_out[] = ['id' => $id, 'titulo' => $def['titulo'], 'aparte' => !empty($def['aparte']), 'fecha' => $def['fecha'], 'cierra' => $def['cierra'], 'exige_fecha' => !empty($def['exige_fecha']), 'checks' => $checks];
    }

    bs_ok(['hoy' => date('Y-m-d'), 'etapas' => $etapas_out, 'instaladores' => $BS_INSTALADORES, 'cards' => $cards]);
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
if (!in_array($accion, ['check', 'fecha', 'nota', 'mover'], true)) bs_error('accion inválida');

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
    if (!is_array($obra['checks'])) $obra['checks'] = [];
    if (!is_array($obra['hist'])) $obra['hist'] = [];

    $hoy = date('Y-m-d');
    $etapa = $obra['etapa'];

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
                $obra['bacha'] = $origen;
            } else {
                $obra['bacha'] = '';
            }
        }

        // Instaladores: uno o varios de la lista.
        if ($clave === 'equipo') {
            if ($valor) {
                $equipo = array_values(array_intersect($BS_INSTALADORES, (array)($body['equipo'] ?? [])));
                if (!$equipo) {
                    bs_lock_release($fp);
                    bs_error('Elegí quiénes instalan.');
                }
                $obra['equipo'] = $equipo;
            } else {
                $obra['equipo'] = [];
            }
        }

        if ($valor) $obra['checks'][$clave] = true;
        else unset($obra['checks'][$clave]);

        // "Medida tomada" fija desde cuándo se cuenta el plazo. Toma la
        // fecha de medición cargada si ya pasó; si no, el día de hoy.
        if ($clave === 'medida') {
            if ($valor) {
                $f = $obra['fecha'];
                $obra['medido_at'] = (bs_tablero_fecha_valida($f) && $f <= $hoy) ? $f : $hoy;
            } else {
                $obra['medido_at'] = '';
            }
        }

    } elseif ($accion === 'fecha') {
        $fecha = trim((string)($body['fecha'] ?? ''));
        if ($fecha !== '' && !bs_tablero_fecha_valida($fecha)) {
            bs_lock_release($fp);
            bs_error('Fecha inválida');
        }
        $obra['fecha'] = $fecha;

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
        if ($nueva === $etapa) {
            bs_lock_release($fp);
            bs_ok(['key' => $key, 'etapa' => $etapa]);
        }

        // Regla única, valga para el botón o para el arrastre: hacia atrás
        // o hacia Retiro se mueve libre; hacia adelante hay que tener
        // completos los tildes de todas las etapas que quedan atrás.
        $destino = array_search($nueva, $BS_ETAPA_IDS, true);
        $retrocede = !$en_retiro && $destino !== false && $destino < $idx;
        if ($destino !== false && !$retrocede) {
            $faltan = [];
            // La fecha de la etapa actual, si esa etapa la exige para salir.
            if (!$en_retiro && !empty($BS_ETAPAS[$etapa]['exige_fecha']) && !bs_tablero_fecha_valida($obra['fecha'])) {
                $faltan[] = 'Fecha de ' . mb_strtolower($BS_ETAPAS[$etapa]['fecha']);
            }
            for ($i = 0; $i < $destino; $i++) {
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
        $obra['hist'][] = ['de' => $etapa, 'a' => $nueva, 'fecha' => $obra['fecha'], 'at' => date('c')];
        $obra['etapa'] = $nueva;
        $obra['fecha'] = '';
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
