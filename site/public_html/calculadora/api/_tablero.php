<?php
// ============================================================
// _tablero.php — Lo que comparten los endpoints del tablero de obras
// BlackStones Marmolería · Registry de Presupuestos
// ============================================================
// Lo incluyen tablero.php (tarjetas), tablero-adjunto.php (fotos y planos),
// tablero-importar.php (carga masiva) y cleanup-retention.php (purga).
// Acá vive la definición de etapas, tildes y listas de personas.
//
// Storage: bs-data/tablero/
//   obras.json                     estado de cada obra, clave "{cliente_nro}-{sub}"
//   adjuntos/{clave}/{id}.{ext}    foto de bacha o plano
//   adjuntos/{clave}/{id}.t.jpg    miniatura (solo imágenes)
// ============================================================

require_once __DIR__ . '/_config.php';

define('BS_TABLERO_DIR',      BS_DATA_DIR . '/tablero');
define('BS_TABLERO_FILE',     BS_TABLERO_DIR . '/obras.json');
define('BS_TABLERO_LOCK',     BS_TABLERO_DIR . '/.write.lock');
define('BS_TABLERO_ADJ_DIR',  BS_TABLERO_DIR . '/adjuntos');
define('BS_TABLERO_NOTA_MAX', 300);

// Adjuntos: qué se puede colgar de una obra y cuánto.
define('BS_TABLERO_ADJ_MAX_BYTES',    10 * 1024 * 1024); // por archivo
define('BS_TABLERO_ADJ_MAX_POR_TIPO', 6);
$BS_TABLERO_ADJ_TIPOS = ['bacha' => 'Bacha', 'plano' => 'Plano'];
// mime real del archivo → extensión con la que se guarda
$BS_TABLERO_ADJ_MIMES = [
    'image/jpeg'      => 'jpg',
    'image/png'       => 'png',
    'image/webp'      => 'webp',
    'application/pdf' => 'pdf',
];

// ------------------------------------------------------------
// Definición única de las etapas. El frontend dibuja desde acá:
// para cambiar un tilde o un título se toca solo este array.
//   fecha  → qué significa la fecha de la tarjeta en esa etapa
//   cierra → tilde que da por cumplida esa fecha (si falta y la fecha ya
//            pasó, la tarjeta se marca atrasada). Vacío = la fecha se cumple
//            recién cuando la tarjeta sale de la columna.
//   checks → tildes que hay que completar para pasar a la siguiente
//   boton / hecho → ver "medir"
// ------------------------------------------------------------
$BS_ETAPAS = [
    'medir' => [
        'titulo' => 'Por medir',
        'fecha'  => 'Medición',
        'cierra' => 'medido',
        // boton: tilde que en la tarjeta es un botón propio ("Medido") y no
        // una pastilla. Las obras que ya lo tienen bajan al final de la
        // columna, bajo el título "hecho": arriba queda la cola real de lo
        // que falta medir.
        // El turno de medición puede llevar hora, pero es opcional: sin
        // exige_fecha, ni la fecha ni la hora frenan el avance.
        'pide_hora'   => true,
        // avisa_fecha: estar en Por medir sin fecha de medición acordada es
        // un estado indeseable. No frena nada, pero se marca y se puede filtrar.
        'avisa_fecha' => true,
        'boton'       => 'medido',
        'boton_txt'   => 'Marcar medido',   // lo que dice el botón antes
        'boton_listo' => 'Ya medido',       // y después
        'hecho'       => 'Ya medidas · esperando pasar a cortar',
        'pendientes'  => 'Necesitan medirse',  // texto del filtro "falta ejecutar"
        // hecha_txt: cómo se nombra, en el detalle del aviso "por avanzar", a la
        // ficha que ya está hecha y sigue en la columna [singular, plural].
        'hecha_txt'   => ['ya medida', 'ya medidas'],
        'checks' => [
            'medidor' => 'Medidor',
            'medido' => 'Medido',
            'medida' => 'Medidas y planos listos',
            'sena'   => 'Trajo la seña',
            'bacha'  => 'Tenemos la bacha',
            'placa'  => 'Tenemos la placa',
            'acceso' => 'Piso y ascensor',
        ],
    ],
    // Sin fecha a propósito: se corta en el orden en que entró (la cola).
    'cortar' => [
        'titulo' => 'Por cortar',
        'fecha'  => '',
        'cierra' => '',
        // El cortador avisa que terminó con el botón "Cortado". La ficha NO
        // avanza sola: baja a "Ya cortadas" y espera que el gerente haga el
        // control ("Corte aprobado") y la pase a Por fabricar.
        'boton'       => 'cortado',
        'boton_txt'   => 'Marcar cortado',
        'boton_listo' => 'Ya cortado',
        'hecho'       => 'Ya cortadas · esperando el control del gerente',
        'pendientes'  => 'Necesitan cortarse',
        'hecha_txt'   => ['ya cortada', 'ya cortadas'],
        // urge: tildes que, si faltan, ponen la ficha en "por avanzar" aunque
        // no esté hecha ni atrasada. Acá: todavía no se le entregó al cortador.
        'urge'        => ['entregado_corte'],
        // urge_txt: el nombre de ese caso en el detalle del aviso.
        'urge_txt'    => 'sin entregar',
        // en_manos: [tilde, texto del filtro]. Fichas que ya tienen ese tilde y
        // todavía no están hechas: lo que el cortador tiene hoy para cortar.
        'en_manos'    => ['entregado_corte', 'Con el cortador'],
        // hitos: tildes cuya fecha conviene ver de un vistazo en las etapas
        // siguientes, además del botón de la etapa (que siempre es un hito).
        'hitos'       => ['qc_corte'],
        'checks' => [
            'cortador' => 'Cortador',
            // El cortador no ve el tablero: solo trabaja lo que se le entrega
            // (plano u orden). Este tilde deja dicho que ya lo recibió.
            'entregado_corte' => 'Entregado al cortador',
            'cortado'  => 'Cortado',
            // Control del corte, punto por punto, antes de aprobarlo.
            'qc_c_mesada'   => 'Medida de mesada correcta',
            'qc_c_zocalos'  => 'Medida de zócalos correcta',
            'qc_c_color'    => 'Color de mesada correcto',
            'qc_c_traforos' => 'Traforos / agujeros correctos',
            'qc_corte' => 'Corte aprobado',
        ],
    ],
    // Sin fecha a propósito: se fabrica en el orden en que entró (la cola).
    'fabricar' => [
        'titulo' => 'Por fabricar',
        'fecha'  => '',
        'cierra' => '',
        // Igual que en Por cortar: "Fabricado" avisa que se terminó; la ficha
        // baja a "Ya fabricadas" y espera el control y que la muevan.
        'boton'       => 'fabricado',
        'boton_txt'   => 'Marcar fabricado',
        'boton_listo' => 'Ya fabricado',
        'hecho'       => 'Ya fabricadas · esperando el control',
        'pendientes'  => 'Necesitan fabricarse',
        'hecha_txt'   => ['ya fabricada', 'ya fabricadas'],
        // Control de la pieza antes de que salga del taller.
        'checks' => [
            'fabrica'       => 'Fabricadores',
            'fabricado'     => 'Fabricado',
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
        // pide_hora: el turno lleva día y hora, y la hora también es obligatoria.
        'fecha'  => 'Instalación',
        'cierra' => '',
        'exige_fecha' => true,
        'pide_hora'   => true,
        'checks' => [
            'avisado' => 'Se avisó al cliente',
            'equipo'  => 'Instaladores',
            // Cierre de la instalación: la mesada quedó colocada y el cliente
            // dio el visto bueno. Sin esto no pasa a Por archivar.
            'conforme' => 'Entregado y conforme',
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
    // Fuera de las columnas: el cliente retira por el local.
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
// Quiénes fabrican. Uno o varios por obra (tilde "Fabricadores", en Por fabricar).
$BS_FABRICADORES = ['Negro', 'Espinola', 'David', 'Jostin', 'Pedro'];
// Quiénes cortan. Uno o varios por obra (tilde "Cortador", en Por cortar).
$BS_CORTADORES = ['Jose', 'Johander'];
// Quiénes miden. Uno o varios por obra (tilde "Medidor", en Por medir).
$BS_MEDIDORES = ['Cesar', 'David', 'Negro', 'Jose'];
// tilde → [lista de nombres válidos, campo de la obra donde se guarda]
$BS_TILDES_PERSONAS = [
    'medidor' => [$BS_MEDIDORES, 'medidor'],
    'equipo'  => [$BS_INSTALADORES, 'equipo'],
    'cortador' => [$BS_CORTADORES, 'cortador'],
    'fabrica' => [$BS_FABRICADORES, 'fabrica'],
];

// Las columnas, en orden. "retiro" queda afuera de la secuencia.
$BS_ETAPA_IDS = [];
foreach ($BS_ETAPAS as $id => $def) {
    if (empty($def['aparte'])) $BS_ETAPA_IDS[] = $id;
}

// Plazo que se le dice al cliente, en días corridos desde la medición.
// Regla del negocio: granito 15, todo lo demás 20. En el tablero se muestra
// como SEMANA estimada de entrega: es guía, el orden lo da la cola.
define('BS_PLAZO_GRANITO', 15);
define('BS_PLAZO_RESTO',   20);

function bs_tablero_hora_valida($h) {
    return is_string($h) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $h) === 1;
}

function bs_tablero_fecha_valida($f) {
    if (!is_string($f) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $f, $m)) return false;
    return checkdate(intval($m[2]), intval($m[3]), intval($m[1]));
}

/** Deja la obra con la forma esperada (tildes como array). No infiere ningún tilde a partir de otro. */
function bs_tablero_normalizar($obra) {
    if (!is_array($obra['checks'])) $obra['checks'] = [];
    return $obra;
}

function bs_tablero_leer() {
    $data = bs_read_json(BS_TABLERO_FILE);
    if (!is_array($data) || !isset($data['obras']) || !is_array($data['obras'])) {
        return ['version' => '1.0', 'obras' => []];
    }
    return $data;
}

function bs_tablero_obra_vacia() {
    return ['etapa' => 'medir', 'fecha' => '', 'hora' => '', 'plazo' => '', 'checks' => [], 'nota' => '', 'medido_at' => '', 'sena' => null, 'acceso' => null, 'bacha' => '', 'equipo' => [], 'fabrica' => [], 'medidor' => [], 'cortador' => [], 'cortado_at' => '', 'fabricado_at' => '', 'tildes_at' => [], 'adjuntos' => [], 'desde' => '', 'hist' => []];
}

/** Adjuntos de una obra, ya validados: [{id, tipo, ext}]. */
function bs_tablero_adjuntos($obra) {
    global $BS_TABLERO_ADJ_TIPOS, $BS_TABLERO_ADJ_MIMES;
    $out = [];
    foreach ((array)($obra['adjuntos'] ?? []) as $a) {
        if (!is_array($a)) continue;
        $id = (string)($a['id'] ?? '');
        $tipo = (string)($a['tipo'] ?? '');
        $ext = (string)($a['ext'] ?? '');
        if (!preg_match('/^[a-f0-9]{16}$/', $id)) continue;
        if (!isset($BS_TABLERO_ADJ_TIPOS[$tipo]) || !in_array($ext, $BS_TABLERO_ADJ_MIMES, true)) continue;
        $out[] = ['id' => $id, 'tipo' => $tipo, 'ext' => $ext, 'at' => (string)($a['at'] ?? '')];
    }
    return $out;
}

/** Carpeta de adjuntos de una obra. $key ya validada ("0042-1"). */
function bs_tablero_adj_carpeta($key) {
    return BS_TABLERO_ADJ_DIR . '/' . $key;
}

/** Borra todos los archivos adjuntos de una obra (la usa la purga). */
function bs_tablero_borrar_carpeta($key) {
    if (!preg_match('/^\d{4,}-\d+$/', $key)) return;
    $dir = bs_tablero_adj_carpeta($key);
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        @unlink($dir . '/' . $f);
    }
    @rmdir($dir);
}
