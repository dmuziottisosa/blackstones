<?php
// ============================================================
// list.php — Listar cotizaciones activas para tab "Activos"
// ============================================================
// GET /api/list.php
// Query params (opcionales):
//   - estado: filtrar por estado (borrador, enviado, aprobado, entregado, perdido)
//   - q: search en nombre, celular, dni, concepto
//   - desde: YYYY-MM-DD (filtrar por fecha)
//   - hasta: YYYY-MM-DD
//   - page: 1-based (default 1)
//   - per_page: default 50, max 200
//
// Response:
//   {
//     "ok": true,
//     "total": 187,
//     "page": 1,
//     "per_page": 50,
//     "results": [
//       {
//         "cliente_nro", "cliente_nombre", "cliente_celular", "cliente_dni",
//         "sub", "fecha", "concepto", "estado",
//         "monto_usd", "monto_ars", "m2"
//       }
//     ]
//   }
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_tablero.php';

bs_require_permiso('presupuestos');

// La etapa de una obra la dicta el tablero, pero en Presupuestos se ve y se puede filtrar:
// cada cotización "aprobado" (obra viva) sale con su etapa del tablero ("etapa", "etapa_titulo"),
// y ?etapa=cortar deja solo las que están ahí. Solo se LEE el tablero; no se toca.
$etapa_filter = (string)($_GET['etapa'] ?? '');
if ($etapa_filter !== '' && !isset($BS_ETAPAS[$etapa_filter])) $etapa_filter = '';
$bs_obras_tablero = bs_tablero_leer()['obras'] ?? [];
// (dueño, 18/11/2026) Las obras nuevas nacen con un código interno y llevan el N° de presupuesto como
// dato: el presupuesto ya no pasa a "aprobado". Para que en Presupuestos se siga viendo que ese
// presupuesto es una obra del tablero, y en qué etapa, se busca por ese N°. Solo se lee.
//   $bs_por_nro[N° sin ceros]['' o versión] = clave de la ficha
$bs_por_nro = [];
foreach ($bs_obras_tablero as $bs_k => $bs_o) {
    $bs_n = trim((string)(is_array($bs_o) ? ($bs_o['nro_contrato'] ?? '') : ''));
    if (!preg_match('/^(\d{1,6})(?:-(\d{1,3}))?$/', $bs_n, $bs_m)) continue;
    $bs_kp = explode('-', (string)$bs_k);
    $bs_cf = bs_read_json(BS_CLIENTES_DIR . '/' . $bs_kp[0] . '.json');
    $bs_viva = false;
    foreach ((array)(is_array($bs_cf) ? ($bs_cf['cotizaciones'] ?? []) : []) as $bs_x) {
        if (intval($bs_x['sub'] ?? 0) === intval($bs_kp[1] ?? 0) && ($bs_x['estado'] ?? '') === 'aprobado' && !empty($bs_x['manual'])) $bs_viva = true;
    }
    if ($bs_viva) $bs_por_nro[intval($bs_m[1])][isset($bs_m[2]) ? intval($bs_m[2]) : ''] = (string)$bs_k;
}

$estado_filter = $_GET['estado'] ?? '';
$origen_filter = $_GET['origen'] ?? ''; // 'publicidad' | 'local' | ''
$q_raw = trim($_GET['q'] ?? '');
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = min(200, max(1, intval($_GET['per_page'] ?? 50)));

// Normalizador para busqueda loose:
//  - lowercase + remueve tildes (NFD + strip combining marks)
//  - colapsa todo lo no-alfanumerico a un solo espacio + trim
// "Pérez, María 11-5678" -> "perez maria 11 5678"
if (!function_exists('bs_loose')) {
    function bs_loose($s) {
        $s = mb_strtolower((string)$s, 'UTF-8');
        if (class_exists('Normalizer')) {
            $s = Normalizer::normalize($s, Normalizer::FORM_D);
            $s = preg_replace('/\p{Mn}+/u', '', $s);
        } else {
            // Fallback sin ext intl: reemplazo a mano de tildes comunes
            $s = strtr($s, [
                'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u',
                'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u','â'=>'a','ê'=>'e',
                'î'=>'i','ô'=>'o','û'=>'u','ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o',
            ]);
        }
        $s = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $s);
        return trim($s);
    }
}
$q = bs_loose($q_raw);

$results = [];

if (is_dir(BS_CLIENTES_DIR)) {
    $files = scandir(BS_CLIENTES_DIR);
    // Optimización: orden DESC por nombre (= por nro = aprox por fecha de creación)
    // así los más recientes se procesan primero. No cambia el resultado final
    // (hay un usort por fecha al final), pero ordena el cache de FS warming
    // para que las páginas iniciales (las más usadas) tengan locality.
    usort($files, function($a, $b) { return strcmp($b, $a); });

    foreach ($files as $f) {
        if (!preg_match('/^(\d{4,})\.json$/', $f)) continue;
        $cliente_obj = bs_read_json(BS_CLIENTES_DIR . '/' . $f);
        if (!$cliente_obj) continue;

        $cli = $cliente_obj['cliente'] ?? [];
        $cliente_nro = $cliente_obj['cliente_nro'] ?? '';

        foreach ($cliente_obj['cotizaciones'] ?? [] as $cot) {
            // Eliminado de Presupuestos (a mano o por los 180 días) mientras la obra sigue en el
            // tablero: acá no aparece más. Tampoco cuando después se archiva (18/11/2026): la pestaña
            // Reporte ya no existe, y lo que se eliminó de Presupuestos no tiene por qué volver.
            if (!empty($cot['fuera_de_presupuestos'])) continue;
            // Ficha cargada a mano en el tablero: no es un presupuesto, no se lista (dueño, 17/11/2026).
            // Archivada sí: pasa a ser una entrega del reporte, como cualquier alta manual.
            if (!empty($cot['manual']) && ($cot['estado'] ?? '') === 'aprobado') continue;
            // ...y archivada tampoco, si es de la serie interna (90001+): ese número no se muestra nunca.
            if (!empty($cot['manual']) && defined('BS_NRO_FICHA_MANUAL_DESDE') && intval($cliente_nro) >= BS_NRO_FICHA_MANUAL_DESDE) continue;
            // Ficha borrada, en la papelera del tablero (30 días): tampoco se lista.
            if (($cot['estado'] ?? '') === 'papelera') continue;

            // ¿Este presupuesto es una obra del tablero por su N° (ficha con código interno)?
            // Sin versión en el N° de la ficha, vale para la última versión del presupuesto.
            $en_tablero = '';
            if (empty($cot['manual']) && in_array(($cot['estado'] ?? ''), ['borrador', 'enviado', 'perdido'], true) && isset($bs_por_nro[intval($cliente_nro)])) {
                $bs_s = intval($cot['sub'] ?? 0); $bs_c = $bs_por_nro[intval($cliente_nro)];
                $bs_max = 0; foreach ($cliente_obj['cotizaciones'] ?? [] as $bs_y) { if (empty($bs_y['manual'])) $bs_max = max($bs_max, intval($bs_y['sub'] ?? 0)); }
                if (isset($bs_c[$bs_s])) $en_tablero = $bs_c[$bs_s];
                elseif (isset($bs_c['']) && $bs_s === $bs_max) $en_tablero = $bs_c[''];
            }

            // Filtro estado (una obra del tablero cuenta como tal, no como "Enviado")
            if ($estado_filter !== '' && ($en_tablero !== '' ? 'aprobado' : ($cot['estado'] ?? '')) !== $estado_filter) continue;

            // Etapa del tablero (solo las obras vivas la tienen). Sin registro todavía: Por medir.
            $etapa = '';
            if (($cot['estado'] ?? '') === 'aprobado' || $en_tablero !== '') {
                $e = (string)($bs_obras_tablero[$en_tablero !== '' ? $en_tablero : $cliente_nro . '-' . intval($cot['sub'] ?? 0)]['etapa'] ?? '');
                $etapa = isset($BS_ETAPAS[$e]) ? $e : 'medir';
            }
            if ($etapa_filter !== '' && $etapa !== $etapa_filter) continue;

            // Filtro origen (publicidad / local; 'organico' es alias historico)
            if ($origen_filter !== '') {
                $cot_origen = ($cot['origen'] ?? 'local');
                if ($cot_origen === 'organico') $cot_origen = 'local';
                if ($cot_origen !== 'local' && $cot_origen !== 'publicidad') $cot_origen = 'local';
                if ($cot_origen !== $origen_filter) continue;
            }

            // Filtro fechas — manda la fecha de contrato; si no esta cargada,
            // se usa la fecha del presupuesto como fallback.
            $fecha = $cot['fecha'] ?? '';
            $fecha_contrato = trim((string)($cot['fecha_contrato'] ?? ''));
            $fecha_efectiva = bs_fecha_ref($cot);
            $fecha_date = substr($fecha_efectiva, 0, 10); // YYYY-MM-DD
            if ($desde !== '' && $fecha_date < $desde) continue;
            if ($hasta !== '' && $fecha_date > $hasta) continue;

            // Filtro search — loose: ambos lados se normalizan igual (sin
            // tildes, sin puntuacion, lowercase). Asi "perez", "PEREZ" y
            // "Pérez, José" matchean lo mismo.
            if ($q !== '') {
                $haystack = bs_loose(
                    ($cli['nombre'] ?? '') . ' ' .
                    ($cli['celular'] ?? '') . ' ' .
                    ($cli['dni'] ?? '') . ' ' .
                    ($cot['concepto'] ?? '') . ' ' .
                    ($cot['material_final'] ?? '') . ' ' .
                    $cliente_nro
                );
                if (strpos($haystack, $q) === false) continue;
            }

            // Calcular totales rápidamente desde presupuesto.totales si existe
            $monto_usd = 0;
            $monto_ars = 0;
            $m2 = 0;
            if (isset($cot['presupuesto']['totales'])) {
                $t = $cot['presupuesto']['totales'];
                $monto_usd = floatval($t['usd'] ?? 0);
                $monto_ars = floatval($t['ars'] ?? 0);
            }

            $results[] = [
                'cliente_nro'       => $cliente_nro,
                'cliente_nombre'    => $cli['nombre'] ?? '',
                'cliente_celular'   => $cli['celular'] ?? '',
                'cliente_dni'       => $cli['dni'] ?? '',
                'cliente_email'     => $cli['email'] ?? '',
                'cliente_direccion' => $cli['direccion'] ?? '',
                'sub'               => $cot['sub'] ?? 0,
                'fecha'             => $fecha,
                'fecha_contrato'    => $fecha_contrato,
                'fecha_efectiva'    => $fecha_efectiva,
                'concepto'          => $cot['concepto'] ?? '',
                'material_final'    => $cot['material_final'] ?? '',
                'notas'             => $cot['notas'] ?? '',
                'estado'            => $cot['estado'] ?? '',
                'etapa'             => $etapa,
                'en_tablero'        => $en_tablero, // clave de su ficha, si es una obra por N° (ficha con código interno)
                'etapa_titulo'      => $etapa !== '' ? (string)$BS_ETAPAS[$etapa]['titulo'] : '',
                'monto_usd'         => $monto_usd,
                'monto_ars'         => $monto_ars,
                'm2'                => $m2,
                'ajuste_manual'     => isset($cot['ajuste_manual']) ? true : false,
                // Se cobro con el descuento de pago en efectivo (-20%)
                'efectivo'          => !empty($cot['presupuesto']['efectivo']),
                // Origen del presupuesto: 'publicidad' o 'local'. Default
                // 'local' si no existe el campo. 'organico' es alias
                // historico que mapeamos a 'local'.
                'origen'            => (($cot['origen'] ?? 'local') === 'organico' ? 'local' : ($cot['origen'] ?? 'local')),
            ];
        }
    }
}

// Ordenar por fecha efectiva desc (contrato si existe, si no la del presupuesto)
usort($results, function($a, $b) {
    return strcmp($b['fecha_efectiva'], $a['fecha_efectiva']);
});

$total = count($results);
$offset = ($page - 1) * $per_page;
$page_results = array_slice($results, $offset, $per_page);

bs_ok([
    'total'    => $total,
    'page'     => $page,
    'per_page' => $per_page,
    'results'  => $page_results,
]);
