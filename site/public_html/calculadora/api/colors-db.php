<?php
// ============================================================
// colors-db.php — Diccionario de colores y materiales
// ============================================================
// GET /api/colors-db.php
//
// Extrae COLORS_DB + MAT_LABELS de calc.html (source of truth) y
// los devuelve normalizados para autocompletado en el hub.
//
// Cache en disco con key = mtime de calc.html. La primera llamada
// despues de un cambio en la calc recompila; las demas leen el cache.
//
// Response: {
//   ok: true,
//   colors: [
//     { n: "Calacata Gold", m: "Guidoni", marca_label: "Guidoni",
//       full: "Guidoni · Calacata Gold" },
//     ...
//   ],
//   marcas: [ "Guidoni", "Marmol", ... ],
//   mat_labels: { "Guidoni": "Guidoni", "Granito_n": "Granito Nacional", ... }
// }
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

bs_require_auth();

$calc_path  = realpath(__DIR__ . '/../calc.html');
$cache_path = BS_DATA_DIR . '/.colors-db.cache.json';

if (!$calc_path || !is_file($calc_path)) {
    bs_error('No se encontró calc.html', 500);
}

$calc_mtime = filemtime($calc_path);

// Cache hit: misma mtime => reusar
if (is_file($cache_path)) {
    $cache = bs_read_json($cache_path);
    if (is_array($cache) && (int)($cache['_src_mtime'] ?? 0) === $calc_mtime) {
        unset($cache['_src_mtime']);
        $cache['ok'] = true;
        $cache['cached'] = true;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

// Cache miss: parsear calc.html
$src = @file_get_contents($calc_path);
if ($src === false) bs_error('No se pudo leer calc.html', 500);

// --- MAT_LABELS ---
$mat_labels = [];
if (preg_match('/const\s+MAT_LABELS\s*=\s*\{(.+?)\};/s', $src, $m)) {
    if (preg_match_all("/'([^']+)'\s*:\s*'([^']*)'/", $m[1], $kvs, PREG_SET_ORDER)) {
        foreach ($kvs as $kv) {
            $mat_labels[$kv[1]] = $kv[2];
        }
    }
}

// --- COLORS_DB ---
$colors = [];
if (preg_match('/const\s+COLORS_DB\s*=\s*\[(.+?)\];/s', $src, $m)) {
    // Cada entrada: {n:'...',m:'...',p:NUM,c:'USD'}
    if (preg_match_all(
        "/\{\s*n\s*:\s*'([^']+)'\s*,\s*m\s*:\s*'([^']+)'\s*,\s*p\s*:\s*([0-9.]+)\s*,\s*c\s*:\s*'([^']+)'\s*\}/",
        $m[1],
        $entries,
        PREG_SET_ORDER
    )) {
        foreach ($entries as $e) {
            $n = $e[1];
            $marca = $e[2];
            $marca_label = $mat_labels[$marca] ?? $marca;
            $colors[] = [
                'n'           => $n,
                'm'           => $marca,
                'marca_label' => $marca_label,
                'full'        => $marca_label . ' · ' . $n,
            ];
        }
    }
}

// Marcas unicas en orden de aparicion en COLORS_DB
$marcas = [];
foreach ($colors as $c) {
    if (!in_array($c['marca_label'], $marcas, true)) {
        $marcas[] = $c['marca_label'];
    }
}

if (empty($colors)) {
    bs_error('No se pudo extraer COLORS_DB de calc.html', 500);
}

$payload = [
    'colors'     => $colors,
    'marcas'     => $marcas,
    'mat_labels' => $mat_labels,
];

// Persistir cache con la mtime de la fuente
$to_cache = $payload + ['_src_mtime' => $calc_mtime];
bs_write_json_atomic($cache_path, $to_cache);

$payload['ok'] = true;
$payload['cached'] = false;
header('Content-Type: application/json; charset=utf-8');
echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
