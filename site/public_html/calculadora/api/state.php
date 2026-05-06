<?php
// ============================================================
// state.php — Cambiar el estado de una cotización
// ============================================================
// POST /api/state.php
// Body JSON:
//   {
//     "cliente_nro": "0042",
//     "sub": 3,
//     "nuevo_estado": "aprobado" | "enviado" | "perdido" | "entregado"
//   }
//
// Reglas (ver json-contract-v1.md § 4.2):
//   - borrador → enviado | perdido
//   - enviado → aprobado | perdido
//   - aprobado → entregado
//   - SIN transiciones inversas
//   - Al pasar a "entregado":
//     * Borrar todas las hermanas no-entregado del mismo cliente.
//     * Marcar `entregado_at` en la cotización.
//     * Generar ZIP automático (delegado a _zip-gen.php).
//
// Response: { "ok": true, "estado_anterior": "...", "estado_nuevo": "...",
//             "siblings_eliminadas": N }
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_zip-gen.php';

bs_require_auth();
bs_check_csrf();
bs_ensure_dirs();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    bs_error('Solo POST', 405);
}

$body = json_decode(@file_get_contents('php://input'), true);
if (!is_array($body)) bs_error('Body JSON inválido');

$cliente_nro = str_pad((string)intval($body['cliente_nro'] ?? ''), 4, '0', STR_PAD_LEFT);
$sub = intval($body['sub'] ?? 0);
$nuevo_estado = $body['nuevo_estado'] ?? '';

if (intval($cliente_nro) <= 0) bs_error('cliente_nro inválido');
if ($sub <= 0) bs_error('sub inválido');

$VALID_STATES = ['borrador', 'enviado', 'aprobado', 'entregado', 'perdido'];
if (!in_array($nuevo_estado, $VALID_STATES, true)) bs_error('nuevo_estado inválido');

// Reglas de transición permitidas
$ALLOWED = [
    'borrador'  => ['enviado', 'perdido'],
    'enviado'   => ['aprobado', 'perdido'],
    'aprobado'  => ['entregado'],
    'entregado' => [],
    'perdido'   => [],
];

$cliente_path = BS_CLIENTES_DIR . '/' . $cliente_nro . '.json';

$lockfile = BS_CLIENTES_DIR . '/.write.lock';
$fp = bs_lock_acquire($lockfile);
if (!$fp) bs_error('No se pudo adquirir lock', 500);

try {
    $cliente_obj = bs_read_json($cliente_path);
    if (!$cliente_obj) {
        bs_lock_release($fp);
        bs_error('Cliente no encontrado', 404);
    }

    $now = date('c');
    $found = false;
    $estado_anterior = '';
    $siblings_eliminadas = 0;

    foreach ($cliente_obj['cotizaciones'] as $i => &$cot) {
        if (intval($cot['sub']) === $sub) {
            $estado_anterior = $cot['estado'];

            // Validar transición
            $allowed_from = $ALLOWED[$estado_anterior] ?? [];
            if (!in_array($nuevo_estado, $allowed_from, true)) {
                bs_lock_release($fp);
                bs_error("Transición no permitida: {$estado_anterior} → {$nuevo_estado}");
            }

            $cot['estado'] = $nuevo_estado;
            $cot['transiciones'][] = ['estado' => $nuevo_estado, 'at' => $now];

            // Si pasa a entregado, marcar entregado_at
            if ($nuevo_estado === 'entregado') {
                $cot['entregado_at'] = $now;
            }

            $found = true;
            break;
        }
    }
    unset($cot);

    if (!$found) {
        bs_lock_release($fp);
        bs_error('Sub-versión no encontrada', 404);
    }

    // Cleanup de hermanas si pasamos a entregado + generar ZIP
    $zip_generated_path = null;
    if ($nuevo_estado === 'entregado') {
        $kept = [];
        foreach ($cliente_obj['cotizaciones'] as $cot) {
            if (intval($cot['sub']) === $sub) {
                // Generar ZIP para esta cotización entregada
                $zip_rel = bs_generate_zip($cliente_obj, $cot);
                if ($zip_rel !== false) {
                    $cot['zip_path'] = $zip_rel;
                    $zip_generated_path = $zip_rel;
                }
                $kept[] = $cot; // mantener la entregada
            } elseif ($cot['estado'] === 'entregado') {
                $kept[] = $cot; // mantener otras entregadas (no se pisan)
            } else {
                $siblings_eliminadas++;
                // Borrar ZIP huérfano si la hermana tenía uno
                if (!empty($cot['zip_path'])) {
                    $orphan_zip = BS_DATA_DIR . '/' . $cot['zip_path'];
                    if (file_exists($orphan_zip)) @unlink($orphan_zip);
                }
            }
        }
        $cliente_obj['cotizaciones'] = $kept;
    }

    $cliente_obj['ultima_actualizacion'] = $now;

    if (!bs_write_json_atomic($cliente_path, $cliente_obj)) {
        bs_lock_release($fp);
        bs_error('No se pudo escribir el cliente', 500);
    }

    bs_lock_release($fp);
    bs_ok([
        'estado_anterior'      => $estado_anterior,
        'estado_nuevo'         => $nuevo_estado,
        'siblings_eliminadas'  => $siblings_eliminadas,
        'zip_path'             => $zip_generated_path,
    ]);

} catch (Throwable $e) {
    bs_lock_release($fp);
    bs_error('Excepción: ' . $e->getMessage(), 500);
}
