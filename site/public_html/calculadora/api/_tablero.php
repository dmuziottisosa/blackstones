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
// Papelera: una ficha borrada se puede recuperar durante estos días (decisión del dueño, 16/11/2026).
define('BS_PAPELERA_DIAS', 30);

// Una ficha cargada a mano NO es un presupuesto (dueño, 17/11/2026): no aparece en la pestaña
// Presupuestos ni se lleva un N° de la calculadora. Por dentro necesita un número para existir;
// sale de una serie aparte, de 90001 en adelante, que no se muestra como N° de presupuesto.
if (!defined('BS_NRO_FICHA_MANUAL_DESDE')) define('BS_NRO_FICHA_MANUAL_DESDE', 90000);

/** Próximo número interno para una ficha cargada a mano (se llama con el lock de clientes tomado). */
function bs_tablero_nro_ficha_manual() {
    $max = BS_NRO_FICHA_MANUAL_DESDE;
    foreach (@scandir(BS_CLIENTES_DIR) ?: [] as $f) {
        if (preg_match('/^(\d{4,})\.json$/', $f, $m)) $max = max($max, intval($m[1]));
    }
    $idx = bs_read_json(BS_CLIENTES_INDEX);
    if (is_array($idx)) foreach ($idx as $nro => $_v) $max = max($max, intval($nro));
    return (string)($max + 1);
}

// Adjuntos: qué se puede colgar de una obra y cuánto.
define('BS_TABLERO_ADJ_MAX_BYTES',    10 * 1024 * 1024); // por archivo
define('BS_TABLERO_ADJ_MAX_POR_TIPO', 6);
$BS_TABLERO_ADJ_TIPOS = ['bacha' => 'Bacha', 'plano' => 'Plano', 'entrega' => 'Entrega'];
// Un adjunto también puede ser un link (la foto o el plano viven en Drive):
// no ocupa lugar en el servidor. Solo se aceptan links https de Google.
define('BS_TABLERO_LINK_MAX', 600);
$BS_TABLERO_LINK_HOSTS = ['drive.google.com', 'docs.google.com', 'photos.app.goo.gl', 'photos.google.com'];

/** Link de Drive ya validado, o '' si no sirve. El server nunca lo abre: solo lo guarda. */
function bs_tablero_link_valido($url) {
    global $BS_TABLERO_LINK_HOSTS;
    $url = trim((string)$url);
    if ($url === '' || strlen($url) > BS_TABLERO_LINK_MAX) return '';
    if (preg_match('/[\s"\'<>\\\\\x00-\x1f\x7f]/', $url)) return '';
    $p = parse_url($url);
    if (!is_array($p) || strtolower((string)($p['scheme'] ?? '')) !== 'https') return '';
    if (isset($p['user']) || isset($p['pass']) || isset($p['port'])) return '';
    if (!in_array(strtolower((string)($p['host'] ?? '')), $BS_TABLERO_LINK_HOSTS, true)) return '';
    return $url;
}
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
        'hecho'       => 'Ya medidas · falta pasar a cortar',
        'pendientes'  => 'Necesitan medirse',  // texto del filtro "falta ejecutar"
        // hecha_txt: cómo se nombra, en el detalle del aviso "por avanzar", a la
        // ficha que ya está hecha y sigue en la columna [singular, plural].
        'hecha_txt'   => ['ya medida', 'ya medidas'],
        // confirma: el tilde que dice que el cliente confirmó el turno (dueño, 18/11/2026). Va primero.
        // Solo se puede tildar con el día puesto, se cae solo si cambia el día o la hora, y deja de
        // pedirse cuando la visita ya se hizo ("cierra"): una obra ya medida no queda trabada por él.
        'confirma' => 'turno_ok',
        'checks' => [
            'turno_ok' => 'Turno confirmado',
            'medidor' => 'Medidor',
            'medido' => 'Medido',
            'medida' => 'Medidas y planos listos',
            'sena'   => 'Trajo la seña',
            'bacha'  => 'Tenemos la bacha',
            'placa'  => 'Tenemos la placa',
            'acceso' => 'Piso y ascensor',
            // Cierre de la medición (dueño, 17/11/2026): alguien revisa que la medición
            // esté bien y la aprueba. Queda quién la aprobó. Sin esto no pasa a Por cortar.
            'qc_medicion' => 'Medición aprobada',
        ],
        // Su fecha queda a la vista en las etapas siguientes, como "Corte aprobado".
        'hitos' => ['qc_medicion'],
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
        'hecho'       => 'Ya cortadas · falta el control',
        'pendientes'  => 'Necesitan cortarse',
        'hecha_txt'   => ['ya cortada', 'ya cortadas'],
        // urge: tildes que, si faltan, ponen la ficha en "por avanzar" aunque
        // no esté hecha ni atrasada. Acá: todavía no se le entregó al cortador.
        'urge'        => ['entregado_corte'],
        // urge_txt: el nombre de ese caso en el detalle del aviso.
        'urge_txt'    => 'sin entregar',
        // urge_ok_txt: la marca de la ficha plegada cuando ese paso YA está (se ve sin abrirla).
        'urge_ok_txt' => 'Entregado',
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
        'hecho'       => 'Ya fabricadas · falta el control',
        'pendientes'  => 'Necesitan fabricarse',
        'hecha_txt'   => ['ya fabricada', 'ya fabricadas'],
        // Igual que en Por cortar: lo que todavía no se le entregó al
        // fabricador cuenta como "por avanzar", y lo ya entregado y sin
        // terminar es su lista de trabajo ("Con el fabricador").
        'urge'        => ['entregado_fab'],
        'urge_txt'    => 'sin entregar',
        // urge_ok_txt: la marca de la ficha plegada cuando ese paso YA está (se ve sin abrirla).
        'urge_ok_txt' => 'Entregado',
        'en_manos'    => ['entregado_fab', 'Con el fabricador'],
        // Igual que "Corte aprobado": su fecha queda a la vista en las etapas siguientes.
        'hitos'       => ['qc_fab'],
        // Control de la pieza antes de que salga del taller.
        'checks' => [
            'fabrica'       => 'Fabricadores',
            // Quién fabrica y si ya se le entregó son dos datos distintos:
            // se puede asignar hoy y entregarle la pieza recién mañana.
            'entregado_fab' => 'Entregado al fabricador',
            'fabricado'     => 'Fabricado',
            'qc_medida'     => 'Medida y color correctos',
            'qc_inglete'    => 'Inglete y zócalos',
            'qc_bacha'      => 'Es la bacha correcta',
            'qc_bacha_lado' => 'Bacha hacia el lado correcto',
            // Cierre del control: el gerente da por buena la pieza.
            'qc_fab'        => 'Fabricación aprobada',
        ],
    ],
    'instalar' => [
        'titulo' => 'Por instalar',
        // Si la fecha de instalación pasó y todavía no está "Instalado", se marca atrasada.
        // exige_fecha: el turno de instalación es día Y hora, y se avisa en rojo mientras falte ("Sin turno").
        // Desde el 18/11/2026 NO frena el pase a Por archivar: lo que frena son los tildes.
        // pide_hora: el turno lleva hora; con exige_fecha, al poner el día la hora es obligatoria.
        'fecha'  => 'Instalación',
        // "Instalado" es el botón de la etapa (dueño, 17/11/2026), igual que Medido, Cortado y
        // Fabricado: avisa que la mesada ya se colocó. La ficha NO avanza sola: baja a
        // "Ya instaladas" y espera el conforme del cliente y la aprobación.
        // cierra: con "Instalado" puesto, una fecha de instalación pasada ya no cuenta como atrasada.
        'cierra' => 'instalado',
        'exige_fecha' => true,
        'pide_hora'   => true,
        'boton'       => 'instalado',
        'boton_txt'   => 'Marcar instalado',
        'boton_listo' => 'Ya instalado',
        'hecho'       => 'Ya instaladas · falta la aprobación',
        'pendientes'  => 'Necesitan instalarse',
        'hecha_txt'   => ['ya instalada', 'ya instaladas'],
        // confirma: en esta etapa ese tilde ya existía como "Se avisó al cliente". Es el mismo dato,
        // así que se llama igual que en Por medir ("Turno confirmado") y se porta igual (18/11/2026).
        'confirma' => 'avisado',
        'checks' => [
            'avisado' => 'Turno confirmado',
            // Antes de mandar al equipo: confirmar con el cliente a qué dirección se va.
            'dir_ok'  => 'Dirección confirmada',
            'equipo'  => 'Instaladores',
            'instalado' => 'Instalado',
            // Cierre de la instalación: la mesada quedó colocada y el cliente
            // dio el visto bueno. Sin esto no pasa a Por archivar.
            'conforme' => 'Entregado y conforme',
            // Cierre de la etapa (dueño, 17/11/2026): alguien da por buena la instalación.
            // Queda quién la aprobó. Sin esto no pasa a Por archivar.
            'qc_instalacion' => 'Instalación aprobada',
        ],
        'hitos' => ['qc_instalacion'],
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
        // oculta: desactivada por ahora (decisión del dueño, 2026-11-15). No se
        // muestra ni se le pueden mandar obras. Si alguna obra ya estaba ahí, su
        // cuadro sigue apareciendo para que no se pierda de vista. Para
        // reactivarla alcanza con borrar esta línea.
        'oculta' => true,
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

// Tildes que son una APROBACIÓN: alguien da por bueno el trabajo de la etapa. De estos se
// guarda y se muestra quién los tildó (en la ficha y en el historial), y los puede tildar
// quien tiene el permiso 'tablero_aprobar'.
// 'pide': el tilde que tiene que estar antes (no se aprueba lo que todavía no se hizo);
// si ese tilde se saca, la aprobación se cae sola.
$BS_TILDES_APROBACION = [
    'qc_medicion'    => ['pide' => 'medido',   'falta' => 'Primero marcá la obra como medida.'],
    'qc_corte'       => ['pide' => '',         'falta' => ''],
    'qc_fab'         => ['pide' => '',         'falta' => ''],
    'qc_instalacion' => ['pide' => 'instalado', 'falta' => 'Primero marcá la obra como instalada.'],
];

/**
 * ¿Ese tilde no hace falta para avanzar? Hoy es uno solo: "Turno confirmado" nunca frena el pase de
 * etapa (dueño, 18/11/2026; antes dejaba de pedirse recién con la visita hecha). Es un dato de la
 * agenda, no del trabajo. $obra queda en la firma por si otra regla vuelve a depender de la ficha.
 */
function bs_tablero_tilde_liberado($etapa_id, $cid, $obra) {
    global $BS_ETAPAS;
    $def = $BS_ETAPAS[$etapa_id] ?? [];
    return ($def['confirma'] ?? '') !== '' && $def['confirma'] === $cid;
}

/** Quién tildó ese tilde (nombre), o ''. Solo se guarda desde que hay usuarios. */
function bs_tablero_tilde_por($obra, $clave) {
    $p = $obra['tildes_por'][$clave] ?? null;
    return is_array($p) ? trim((string)($p['n'] ?? '')) : '';
}

// Las columnas, en orden. "retiro" queda afuera de la secuencia.
$BS_ETAPA_IDS = [];
foreach ($BS_ETAPAS as $id => $def) {
    if (empty($def['aparte'])) $BS_ETAPA_IDS[] = $id;
}

// Plazo de entrega, en días corridos desde la medición.
// Estándar único: 20 días para todo (decisión del dueño, 16/11/2026; antes el
// granito llevaba 15). El día 20 vence. La entrega se espera entre el día 15 y
// el 20: esa "ventana" es la franja de cada bloque de medición en el calendario.
define('BS_PLAZO_DIAS',    20);
define('BS_PLAZO_VENTANA', 15);

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
    return ['etapa' => 'medir', 'fecha' => '', 'hora' => '', 'plazo' => '', 'checks' => [], 'nota' => '', 'medido_at' => '', 'sena' => null, 'acceso' => null, 'bacha' => '', 'bacha_modelo' => '', 'equipo' => [], 'fabrica' => [], 'medidor' => [], 'cortador' => [], 'cortado_at' => '', 'fabricado_at' => '', 'instalado_at' => '', 'tildes_at' => [], 'tildes_por' => [], 'adjuntos' => [], 'desde' => '', 'hist' => []];
}

/** Adjuntos de una obra, ya validados: [{id, tipo, ext}] (los links traen además "url", con ext "link"). */
function bs_tablero_adjuntos($obra) {
    global $BS_TABLERO_ADJ_TIPOS, $BS_TABLERO_ADJ_MIMES;
    $out = [];
    foreach ((array)($obra['adjuntos'] ?? []) as $a) {
        if (!is_array($a)) continue;
        $id = (string)($a['id'] ?? '');
        $tipo = (string)($a['tipo'] ?? '');
        $ext = (string)($a['ext'] ?? '');
        if (!preg_match('/^[a-f0-9]{16}$/', $id)) continue;
        if (!isset($BS_TABLERO_ADJ_TIPOS[$tipo])) continue;
        if ($ext === 'link') {
            $url = bs_tablero_link_valido($a['url'] ?? '');
            if ($url === '') continue;
            $out[] = ['id' => $id, 'tipo' => $tipo, 'ext' => 'link', 'url' => $url, 'at' => (string)($a['at'] ?? ''), 'por' => (string)($a['por'] ?? '')];
            continue;
        }
        if (!in_array($ext, $BS_TABLERO_ADJ_MIMES, true)) continue;
        $out[] = ['id' => $id, 'tipo' => $tipo, 'ext' => $ext, 'at' => (string)($a['at'] ?? ''), 'por' => (string)($a['por'] ?? '')];
    }
    return $out;
}

/**
 * ¿El que entró puede borrar o girar este adjunto? Los que subió él, siempre.
 * Los de otro (o los de antes de que hubiera usuarios, que no dicen de quién son),
 * solo con el permiso 'tablero_adj_ajeno'. Necesita auth_check.php cargado.
 */
function bs_tablero_adj_mio($a) {
    if (!function_exists('auth_usuario')) return true;
    if (auth_puede('tablero_adj_ajeno')) return true;
    $u = auth_usuario();
    return $u && $u['usuario'] !== '' && (string)($a['por'] ?? '') === $u['usuario'];
}

/** Carpeta de adjuntos de una obra. $key ya validada ("0042-1"). */
function bs_tablero_adj_carpeta($key) {
    return BS_TABLERO_ADJ_DIR . '/' . $key;
}

/** Borra todos los archivos adjuntos de una obra (la usa la purga). */
/**
 * Papelera del tablero — borrado definitivo de lo que ya venció.
 *
 * Una ficha borrada no se pierde en el momento: su cotización pasa de "aprobado" a
 * "papelera" (deja de verse en el tablero, el calendario y Presupuestos) y su registro,
 * sus tildes y sus adjuntos quedan donde estaban. Pasados BS_PAPELERA_DIAS se borra:
 *   - obra cargada a mano  → se va entera (como hacía "Borrar ficha" antes);
 *   - vino de la calculadora → el presupuesto vuelve a "enviado" y se va solo la ficha.
 * Lo llaman tablero-papelera.php (cada vez que se mira la papelera) y la limpieza nocturna.
 * Devuelve cuántas fichas borró.
 */
function bs_tablero_papelera_purgar() {
    if (!is_dir(BS_CLIENTES_DIR)) return 0;
    $limite = time() - BS_PAPELERA_DIAS * 86400;
    $vencidas = [];
    $fp = bs_lock_acquire(BS_CLIENTES_DIR . '/.write.lock');
    if (!$fp) return 0;
    foreach (scandir(BS_CLIENTES_DIR) as $f) {
        if (!preg_match('/^(\d{4,})\.json$/', $f)) continue;
        $path = BS_CLIENTES_DIR . '/' . $f;
        $cli = bs_read_json($path);
        if (!$cli) continue;
        $nro = (string)($cli['cliente_nro'] ?? '');
        $kept = [];
        $cambio = false;
        foreach ($cli['cotizaciones'] ?? [] as $cot) {
            if (($cot['estado'] ?? '') === 'papelera') {
                $at = strtotime((string)($cot['papelera']['at'] ?? ''));
                if ($at && $at < $limite) {
                    $vencidas[] = $nro . '-' . intval($cot['sub'] ?? 0);
                    $cambio = true;
                    if (!empty($cot['manual'])) {
                        if (!empty($cot['zip_path']) && file_exists(BS_DATA_DIR . '/' . $cot['zip_path'])) @unlink(BS_DATA_DIR . '/' . $cot['zip_path']);
                        continue;
                    }
                    $cot['estado'] = 'enviado';
                    $cot['transiciones'][] = ['estado' => 'enviado', 'at' => date('c')];
                    unset($cot['papelera'], $cot['fuera_de_presupuestos']); // vuelve a verse en Presupuestos
                }
            }
            $kept[] = $cot;
        }
        if (!$cambio) continue;
        if (!count($kept)) {
            @unlink($path);
            // el número no se recicla (mismo criterio que delete.php)
            $idx = bs_read_json(BS_CLIENTES_INDEX);
            if (!is_array($idx)) $idx = [];
            $idx[$nro] = ['deleted_at' => date('c'), 'reason' => 'papelera'];
            bs_write_json_atomic(BS_CLIENTES_INDEX, $idx);
        } else {
            $cli['cotizaciones'] = $kept;
            $cli['ultima_actualizacion'] = date('c');
            bs_write_json_atomic($path, $cli);
        }
    }
    bs_lock_release($fp);

    if ($vencidas && is_dir(BS_TABLERO_DIR)) {
        $fp = bs_lock_acquire(BS_TABLERO_LOCK);
        if ($fp) {
            $tablero = bs_tablero_leer();
            foreach ($vencidas as $key) {
                bs_tablero_borrar_carpeta($key);
                unset($tablero['obras'][$key]);
            }
            bs_write_json_atomic(BS_TABLERO_FILE, $tablero);
            bs_lock_release($fp);
        }
    }
    return count($vencidas);
}

function bs_tablero_borrar_carpeta($key) {
    if (!preg_match('/^\d{4,}-\d+$/', $key)) return;
    $dir = bs_tablero_adj_carpeta($key);
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        // no se destruye: queda en copias/borrados/ mientras una copia lo pueda necesitar (ver _copias.php)
        function_exists('bs_copia_borrar') ? bs_copia_borrar($dir . '/' . $f) : @unlink($dir . '/' . $f);
    }
    @rmdir($dir);
}

// ============================================================
// Leer una obra: material, seña, historial, acceso, zona
// (Estaban en tablero.php; se mudaron acá el 18/11/2026 para que las use también cierre-mes.php.)
// ============================================================

// Van dentro de un "si no existen": mientras se sube una versión nueva, por unos segundos puede quedar
// el tablero.php anterior (que todavía las trae) con este archivo ya nuevo, y así no chocan.
if (!function_exists('bs_tablero_material')) {

/**
 * Material a mostrar y plazo prometido, leídos del presupuesto guardado.
 * Si el hub tiene cargado "material_final", manda ese texto.
 */
function bs_tablero_material($cot) {
    $colores = [];
    $hay_items = false;

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
                if ($color !== '' && !in_array($color, $colores, true)) $colores[] = $color;
            }
        }
    }

    $texto = trim((string)($cot['material_final'] ?? ''));
    if ($texto === '') {
        $texto = implode(' + ', array_slice($colores, 0, 2));
        if (count($colores) > 2) $texto .= ' …';
    }

    // El plazo es el mismo para todos los materiales (ver BS_PLAZO_DIAS).
    return [$texto, BS_PLAZO_DIAS];
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
function bs_tablero_historia($obra, $inicio = '') {
    global $BS_ETAPAS, $BS_ETAPA_IDS, $BS_TILDES_PERSONAS, $BS_TILDES_APROBACION;
    $historia = []; $hitos = [];
    $actual = array_search($obra['etapa'], $BS_ETAPA_IDS, true);
    if ($actual === false) return [$historia, $hitos];
    $tildes_at = is_array($obra['tildes_at'] ?? null) ? $obra['tildes_at'] : [];
    $estuvo = bs_tablero_dias_por_etapa($obra, $inicio);

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
            } elseif ($cid === 'sena' && auth_puede('tablero_plata') && ($s = bs_tablero_sena($obra))) { // el monto, solo a quien puede verlo
                $txt .= ': ' . ($s['moneda'] === 'USD' ? 'USD ' : '$ ') . number_format($s['monto'], 0, ',', '.');
            } elseif ($cid === 'bacha' && in_array($obra['bacha'] ?? '', ['nuestra', 'cliente', 'no'], true)) {
                $txt = ['nuestra' => 'Bacha nuestra', 'cliente' => 'Bacha del cliente', 'no' => 'No lleva bacha'][$obra['bacha']];
                if ($obra['bacha'] !== 'no' && trim((string)($obra['bacha_modelo'] ?? '')) !== '') $txt .= ' · ' . trim((string)$obra['bacha_modelo']);
            } elseif ($cid === 'acceso' && ($a = bs_tablero_acceso($obra))) {
                $txt .= ': ' . ($a['modo'] === 'pb' ? 'planta baja' : ($a['modo'] === 'ascensor' ? 'por ascensor' : 'por escalera, ' . $a['pisos'] . ($a['pisos'] === 1 ? ' piso' : ' pisos')));
            }
            // Las aprobaciones dicen quién las dio.
            $por = isset($BS_TILDES_APROBACION[$cid]) ? bs_tablero_tilde_por($obra, $cid) : '';
            if ($por !== '') $txt .= ' · ' . $por;
            $items[] = ['txt' => $txt, 'fecha' => $fecha, 'por' => $por];
            if ($cid === $boton || in_array($cid, (array)($def['hitos'] ?? []), true)) $hitos[] = ['txt' => $label, 'fecha' => $fecha, 'por' => $por];
        }
        // Día en que salió de esa etapa (el último, si pasó más de una vez).
        // 'turno': el día y la hora que tenía agendados cuando salió (el pase los guarda y después se borran de
        // la ficha). El calendario lo usa para marcar la instalación en el día de su turno (dueño, 19/11/2026).
        $salio = ''; $turno = null;
        foreach ((array)($obra['hist'] ?? []) as $h) {
            if (($h['de'] ?? '') !== $eid || empty($h['at'])) continue;
            $salio = substr((string)$h['at'], 0, 10);
            $turno = bs_tablero_fecha_valida((string)($h['fecha'] ?? ''))
                ? ['fecha' => (string)$h['fecha'], 'hora' => bs_tablero_hora_valida((string)($h['hora'] ?? '')) ? (string)$h['hora'] : '']
                : null;
        }
        // 'dias': cuántos días estuvo en esa etapa (null si no se sabe: entró antes de que se anotaran los pases).
        $historia[] = ['id' => $eid, 'etapa' => $def['titulo'], 'items' => $items, 'salio' => bs_tablero_fecha_valida($salio) ? $salio : '', 'dias' => $estuvo[$eid] ?? null, 'turno' => $turno];
    }
    return [$historia, $hitos];
}

/** Días corridos entre dos fechas AAAA-MM-DD (nunca negativo). */
function bs_tablero_dias_entre($desde, $hasta) {
    return max(0, intval(round((strtotime($hasta . ' 12:00:00') - strtotime($desde . ' 12:00:00')) / 86400)));
}

/**
 * Cuánto tiempo estuvo la ficha en cada etapa (dueño, 18/11/2026): { id de etapa: días corridos }.
 * Sale de los pases que ya se anotan en 'hist' (de, a, at): cada pase cierra la estadía en la etapa
 * de la que sale. $inicio es el día en que la ficha entró al tablero (de ahí arranca la primera).
 * Si pasó dos veces por la misma etapa (la devolvieron), se suman. La etapa actual no va acá.
 */
function bs_tablero_dias_por_etapa($obra, $inicio = '') {
    $t = substr((string)$inicio, 0, 10);
    if (!bs_tablero_fecha_valida($t)) $t = '';
    $out = [];
    foreach ((array)($obra['hist'] ?? []) as $h) {
        $at = substr((string)($h['at'] ?? ''), 0, 10);
        if (!bs_tablero_fecha_valida($at)) continue;
        $de = (string)($h['de'] ?? '');
        if ($t !== '' && $de !== '') $out[$de] = ($out[$de] ?? 0) + bs_tablero_dias_entre($t, $at);
        $t = $at;
    }
    return $out;
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

} // fin de: si no existen
