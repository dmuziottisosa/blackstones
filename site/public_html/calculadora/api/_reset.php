<?php
// ============================================================
// _reset.php — One-shot reset del registro
// ============================================================
// USO:
//   1. Abrir en browser autenticado:
//      https://blackstones.com.ar/calculadora/api/_reset.php?confirm=YES&scope=full
//
//   2. ?scope=light  → borra clientes/*.json + clientes-index.json
//                      (próximo presupuesto = 0001, mantiene reportes mensuales históricos)
//   3. ?scope=full   → borra TODO: clientes/, zips/, clientes-index.json, registro/by-month/*.json
//                      (próximo presupuesto = 0001, sin historial alguno)
//
//   Después de correrlo: BORRAR ESTE ARCHIVO del FTP.
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

bs_require_auth();

if (($_GET['confirm'] ?? '') !== 'YES') {
    bs_error('Reset requiere ?confirm=YES&scope=light|full. Para evitar accidentes.', 400);
}

$scope = $_GET['scope'] ?? 'light';
if (!in_array($scope, ['light', 'full'], true)) {
    bs_error('scope debe ser "light" o "full"', 400);
}

$deleted = ['clientes' => 0, 'zips' => 0, 'reports' => 0, 'index' => false];
$errors = [];

// 1. Borrar clientes/*.json (siempre)
if (is_dir(BS_CLIENTES_DIR)) {
    foreach (scandir(BS_CLIENTES_DIR) as $f) {
        if (preg_match('/^\d{4,}\.json$/', $f)) {
            $path = BS_CLIENTES_DIR . '/' . $f;
            if (@unlink($path)) {
                $deleted['clientes']++;
            } else {
                $errors[] = "No se pudo borrar $f";
            }
        }
    }
}

// 2. Borrar clientes-index.json (siempre — sino el contador no se resetea por la dedup histórica)
if (file_exists(BS_CLIENTES_INDEX)) {
    if (@unlink(BS_CLIENTES_INDEX)) {
        $deleted['index'] = true;
    } else {
        $errors[] = 'No se pudo borrar clientes-index.json';
    }
}

// 3. Si scope=full, también borrar zips/ y by-month/
if ($scope === 'full') {
    if (is_dir(BS_ZIPS_DIR)) {
        foreach (scandir(BS_ZIPS_DIR) as $f) {
            if (preg_match('/\.zip$/', $f)) {
                if (@unlink(BS_ZIPS_DIR . '/' . $f)) $deleted['zips']++;
            }
        }
    }
    if (is_dir(BS_REGISTRO_BY_MONTH_DIR)) {
        foreach (scandir(BS_REGISTRO_BY_MONTH_DIR) as $f) {
            if (preg_match('/^\d{4}-\d{2}\.json$/', $f)) {
                if (@unlink(BS_REGISTRO_BY_MONTH_DIR . '/' . $f)) $deleted['reports']++;
            }
        }
    }
}

bs_ok([
    'scope' => $scope,
    'deleted' => $deleted,
    'errors' => $errors,
    'siguiente_nro' => '0001',
    'recordatorio' => 'BORRAR ESTE ARCHIVO (_reset.php) del FTP ahora.',
]);
