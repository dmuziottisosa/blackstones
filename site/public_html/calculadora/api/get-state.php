<?php
// ============================================================
// get-state.php — Devuelve el state completo de una sub-versión
// ============================================================
// GET /api/get-state.php?nro=0042&sub=3
// Response: {
//   ok: true,
//   cliente_nro: "0042",
//   sub: 3,
//   fecha: "2026-05-03T...",
//   concepto: "...",
//   estado: "borrador",
//   cliente: { nombre, dni, celular, direccion, email },
//   presupuesto: { secciones, zocalos, extras, variantes, bachas, adicionales, factura, dolar_venta, totales },
//   exports_options: { ... }
// }
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

bs_require_auth();

$nro = str_pad((string)intval($_GET['nro'] ?? ''), 4, '0', STR_PAD_LEFT);
$sub = intval($_GET['sub'] ?? 0);

if (intval($nro) <= 0) bs_error('nro inválido');
if ($sub <= 0) bs_error('sub inválido');

$path = BS_CLIENTES_DIR . '/' . $nro . '.json';
$obj = bs_read_json($path);
if (!$obj) bs_error('Cliente no encontrado', 404);

foreach ($obj['cotizaciones'] ?? [] as $cot) {
    if (intval($cot['sub']) === $sub) {
        bs_ok([
            'cliente_nro'     => $obj['cliente_nro'] ?? $nro,
            'sub'             => $cot['sub'],
            'fecha'           => $cot['fecha'] ?? '',
            'concepto'        => $cot['concepto'] ?? '',
            'estado'          => $cot['estado'] ?? '',
            'cliente'         => $obj['cliente'] ?? [],
            'presupuesto'     => $cot['presupuesto'] ?? [],
            'exports_options' => $cot['presupuesto']['exports_options'] ?? null,
        ]);
    }
}

bs_error('Sub-versión no encontrada', 404);
