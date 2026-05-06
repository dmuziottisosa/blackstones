<?php
// ============================================================
// download-zip.php — Descarga el ZIP de un presupuesto entregado
// ============================================================
// GET /api/download-zip.php?nro=0042&sub=3
//
// Sirve el ZIP que vive en bs-data/zips/ (fuera de public_html, no es
// directamente accesible por URL — este endpoint es el único portal).
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

bs_require_auth();

$cliente_nro = str_pad((string)intval($_GET['nro'] ?? ''), 4, '0', STR_PAD_LEFT);
$sub = intval($_GET['sub'] ?? 0);

if (intval($cliente_nro) <= 0) bs_error('nro inválido');
if ($sub <= 0) bs_error('sub inválido');

$zip_path = BS_ZIPS_DIR . '/' . $cliente_nro . '-' . $sub . '.zip';

if (!file_exists($zip_path)) {
    bs_error('ZIP no encontrado (puede haber expirado por retención de 60 días o la cotización no está entregada)', 404);
}

$cli_obj = bs_read_json(BS_CLIENTES_DIR . '/' . $cliente_nro . '.json');
$cli_nombre = $cli_obj['cliente']['nombre'] ?? 'cliente';

// Filename ASCII-safe (fallback navegadores viejos): solo a-z, 0-9, _ y -
$cli_nombre_ascii = preg_replace('/[^a-zA-Z0-9_-]/', '_', $cli_nombre);
$download_name_ascii = "BlackStones_{$cliente_nro}-{$sub}_{$cli_nombre_ascii}.zip";

// Filename UTF-8 con tildes/ñ (navegadores modernos via RFC 5987)
$cli_nombre_utf8 = preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]/', '_', $cli_nombre); // sólo chars ilegales en filenames
$download_name_utf8 = "BlackStones_{$cliente_nro}-{$sub}_{$cli_nombre_utf8}.zip";

header('Content-Type: application/zip');
header(
    'Content-Disposition: attachment; ' .
    'filename="' . $download_name_ascii . '"; ' .
    "filename*=UTF-8''" . rawurlencode($download_name_utf8)
);
header('Content-Length: ' . filesize($zip_path));
header('Cache-Control: no-cache, no-store, must-revalidate');
readfile($zip_path);
exit;
