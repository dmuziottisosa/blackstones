<?php
// ============================================================
// BlackStones — Scraper de Dólar Oficial desde dolarhoy.com
// Ubicación: blackstones.com.ar/calculadora/dolar.php
// Versión 5 — Apunta a la HOME y usa /cotizaciondolaroficial
//             como ancla única e inequívoca para encontrar
//             el bloque correcto del Dólar Oficial.
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=300');

$cacheFile = __DIR__ . '/dolar_cache.json';
$cacheLifetime = 300; // 5 min

$forceRefresh = isset($_GET['nocache']);
$debugMode = isset($_GET['debug']);

if (!$forceRefresh && !$debugMode && file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheLifetime) {
    echo file_get_contents($cacheFile);
    exit;
}

// ============================================================
// Helper: convierte "1.435,00" → 1435.00 / "$1390" → 1390
// ============================================================
function parseValor($s) {
    $s = trim($s);
    $s = str_replace('$', '', $s);
    $s = str_replace(' ', '', $s);
    if (strpos($s, '.') !== false && strpos($s, ',') !== false) {
        $s = str_replace('.', '', $s);
        $s = str_replace(',', '.', $s);
    } else if (strpos($s, ',') !== false) {
        $s = str_replace(',', '.', $s);
    }
    return floatval($s);
}

// ============================================================
// Helper: cURL request
// ============================================================
function fetchUrl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: es-AR,es;q=0.9,en;q=0.8',
        'Cache-Control: no-cache',
    ]);

    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    return ['html' => $html, 'httpCode' => $httpCode, 'error' => $err];
}

// ============================================================
// FETCH — la HOME de dolarhoy.com tiene el bloque oficial
// completo y bien estructurado. La URL /cotizaciondolaroficial
// aparece UNA sola vez y JUSTO antes de los valores del oficial.
// ============================================================
$compra = null;
$venta = null;
$source = '';
$debug = [];

$res = fetchUrl('https://dolarhoy.com/');

if ($res['httpCode'] === 200 && $res['html']) {
    $html = $res['html'];
    $debug['html_length'] = strlen($html);

    // ESTRATEGIA PRINCIPAL — anclar en la URL única /cotizaciondolaroficial
    // y capturar los DOS primeros <div class="val">$XXXX</div> que aparecen
    // inmediatamente después. Estos son siempre Compra y Venta del oficial.
    if (preg_match('/href="\/cotizaciondolaroficial".*?<div class="val">\$?([\d.,]+)<\/div>.*?<div class="val">\$?([\d.,]+)<\/div>/su', $html, $m)) {
        $compra = parseValor($m[1]);
        $venta = parseValor($m[2]);
        $source = 'home-anchor-url';
        $debug['raw_compra'] = $m[1];
        $debug['raw_venta'] = $m[2];
    }

    // FALLBACK 1 — buscar por aria-label (por si cambian la URL)
    if (!$venta) {
        if (preg_match('/aria-label="Link a D[óo]lar Oficial".*?<div class="val">\$?([\d.,]+)<\/div>.*?<div class="val">\$?([\d.,]+)<\/div>/su', $html, $m)) {
            $compra = parseValor($m[1]);
            $venta = parseValor($m[2]);
            $source = 'home-anchor-arialabel';
        }
    }

    // FALLBACK 2 — texto "Dólar Oficial" entre Blue y MEP
    if (!$venta) {
        if (preg_match('/D[óo]lar Oficial.*?<div class="val">\$?([\d.,]+)<\/div>.*?<div class="val">\$?([\d.,]+)<\/div>.*?D[óo]lar MEP/su', $html, $m)) {
            $compra = parseValor($m[1]);
            $venta = parseValor($m[2]);
            $source = 'home-text-between';
        }
    }
}

// ============================================================
// MODO DEBUG — devuelve HTML con info detallada
// ============================================================
if ($debugMode) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>Debug Dólar Scraper v5</h1>";
    echo "<p><strong>URL:</strong> https://dolarhoy.com/</p>";
    echo "<p><strong>HTTP Code:</strong> " . htmlspecialchars($res['httpCode']) . "</p>";
    echo "<p><strong>cURL Error:</strong> " . htmlspecialchars($res['error']) . "</p>";
    echo "<p><strong>HTML length:</strong> " . strlen($res['html']) . " bytes</p>";
    echo "<p><strong>Source matched:</strong> " . htmlspecialchars($source ?: 'NINGUNO') . "</p>";
    echo "<p><strong>Compra parseado:</strong> " . ($compra ?? 'null') . "</p>";
    echo "<p><strong>Venta parseado:</strong> " . ($venta ?? 'null') . "</p>";
    echo "<h3>Debug interno:</h3><pre>" . htmlspecialchars(print_r($debug, true)) . "</pre>";
    exit;
}

// ============================================================
// VALIDACIÓN — el oficial debe estar en un rango razonable
// ============================================================
if (!$venta || $venta < 1000 || $venta > 5000) {
    http_response_code(500);
    echo json_encode([
        'error' => 'No se pudo obtener cotización válida',
        'compra_parsed' => $compra,
        'venta_parsed' => $venta,
        'source' => $source,
        'http_code' => $res['httpCode']
    ]);
    exit;
}

// ============================================================
// JSON limpio + cache
// ============================================================
$result = [
    'compra' => $compra,
    'venta' => $venta,
    'fuente' => 'dolarhoy.com',
    'source' => $source,
    'fechaActualizacion' => date('c')
];

$json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
@file_put_contents($cacheFile, $json);

echo $json;
