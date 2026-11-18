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
//   Avance:
//   - borrador → enviado | perdido
//   - enviado → aprobado | perdido
//   - aprobado → entregado
//   Retroceso paso a paso (agregado 2026-06-27 — la cadena es reversible
//   hasta borrador, el primer estado):
//   - aprobado → enviado
//   - enviado  → borrador
//   - perdido  → enviado  (recuperar un presupuesto marcado perdido)
//   - entregado → aprobado (ya existía: "devolver a activos")
//   Estos retrocesos solo re-etiquetan; no borran ni regeneran nada
//   (salvo entregado → aprobado, que limpia entregado_at y el ZIP).
//   - Al pasar a "entregado":
//     * Borrar todas las hermanas no-entregado del mismo cliente
//       (salvo que el body traiga "conservar_hermanas": true — lo usa el
//       tablero de obras, que archiva sin borrar nada).
//     * Marcar `entregado_at` en la cotización.
//     * Generar ZIP automático (delegado a _zip-gen.php).
//
// Response: { "ok": true, "estado_anterior": "...", "estado_nuevo": "...",
//             "siblings_eliminadas": N }
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_zip-gen.php';

// Lo que viene del tablero (borrar la ficha, archivar, devolver) pide permiso del tablero;
// lo demás, de Presupuestos.
$bs_pre = json_decode((string)@file_get_contents('php://input'), true);
bs_require_permiso((is_array($bs_pre) && (!empty($bs_pre['desde_tablero']) || !empty($bs_pre['conservar_hermanas']))) ? 'tablero_operar' : 'presupuestos');
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
// Opt-in (lo manda el tablero de obras al archivar): pasa a entregado SIN
// borrar las otras cotizaciones del cliente. Sin el flag, todo sigue igual.
$conservar_hermanas = !empty($body['conservar_hermanas']);

if (intval($cliente_nro) <= 0) bs_error('cliente_nro inválido');
if ($sub <= 0) bs_error('sub inválido');

$VALID_STATES = ['borrador', 'enviado', 'aprobado', 'entregado', 'perdido'];
if (!in_array($nuevo_estado, $VALID_STATES, true)) bs_error('nuevo_estado inválido');

// Reglas de transición permitidas
$ALLOWED = [
    'borrador'  => ['enviado', 'perdido'],
    // enviado → borrador: retroceso paso a paso. Solo re-etiqueta.
    'enviado'   => ['aprobado', 'perdido', 'borrador'],
    // aprobado → enviado: retroceso paso a paso. Solo re-etiqueta.
    'aprobado'  => ['entregado', 'enviado'],
    // entregado → aprobado: "devolver a activos". Reversión deliberada desde
    // el hub. NO recupera las hermanas borradas al entregar (ya no existen).
    'entregado' => ['aprobado'],
    // perdido → enviado: recuperar un presupuesto marcado perdido por error.
    'perdido'   => ['enviado'],
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

            // La etapa la dicta el tablero (regla del dueño, 16/11/2026): una obra viva
            // ("aprobado") no se saca del tablero cambiándole el estado desde Presupuestos.
            // Se archiva o se borra desde el tablero, que manda "desde_tablero": true.
            if ($estado_anterior === 'aprobado' && empty($body['desde_tablero'])) {
                bs_lock_release($fp);
                bs_error('Esa obra está en el tablero: la etapa se cambia desde ahí (se archiva o se borra la ficha en el tablero).', 409);
            }

            // "→ Aprobado" ya no existe en Presupuestos (dueño, 17/11/2026): si una obra está en el
            // tablero es que fue aprobada. Un presupuesto entra al tablero desde el tablero ("Cargar
            // obra" con su N°) o desde su contrato ("Cargar en Por medir"), que no pasan por acá.
            // Devolver un entregado al tablero (desde el Reporte) sigue permitido.
            // (18/11/2026) Tampoco con "desde_tablero": por acá solo se devuelve una archivada. Una obra
            // nueva entra por tablero-cargar.php, que la deja en Por medir y vacía.
            if ($nuevo_estado === 'aprobado' && $estado_anterior !== 'entregado') {
                bs_lock_release($fp);
                bs_error('Un presupuesto pasa al tablero desde el tablero ("Cargar obra" con su N°) o desde su contrato en Relación-Contrato.', 409);
            }

            // ARCHIVAR lo valida el servidor, como cualquier otro cambio de etapa (barrido del 18/11/2026).
            // Antes solo lo miraba la pantalla: con el tablero desactualizado se archivaba una obra a la
            // que otro le había sacado "Saldo cobrado" o había devuelto a Por instalar.
            if ($estado_anterior === 'aprobado' && $nuevo_estado === 'entregado') {
                require_once __DIR__ . '/_tablero.php';
                $bs_t = bs_tablero_leer();
                $bs_o = (array)($bs_t['obras'][$cliente_nro . '-' . $sub] ?? []);
                $bs_e = isset($BS_ETAPAS[(string)($bs_o['etapa'] ?? '')]) ? (string)$bs_o['etapa'] : 'medir';
                if ($bs_e !== 'archivar' && $bs_e !== 'retiro') {
                    bs_lock_release($fp);
                    bs_error('Esa obra está en ' . $BS_ETAPAS[$bs_e]['titulo'] . ': se archiva desde Por archivar. Actualizá el tablero.', 409);
                }
                $bs_faltan = [];
                foreach ($BS_ETAPAS[$bs_e]['checks'] as $bs_cid => $bs_label) { if (empty($bs_o['checks'][$bs_cid])) $bs_faltan[] = $bs_label; }
                if ($bs_faltan) {
                    bs_lock_release($fp);
                    bs_error('Falta: ' . implode(', ', $bs_faltan) . '. Actualizá el tablero.', 409);
                }
            }

            // Validar transición
            $allowed_from = $ALLOWED[$estado_anterior] ?? [];
            if (!in_array($nuevo_estado, $allowed_from, true)) {
                bs_lock_release($fp);
                bs_error("Transición no permitida: {$estado_anterior} → {$nuevo_estado}");
            }

            $cot['estado'] = $nuevo_estado;
            $cot['transiciones'][] = ['estado' => $nuevo_estado, 'at' => $now];
            // Sale del tablero y vuelve a ser un presupuesto: si lo habían eliminado de Presupuestos
            // mientras era una obra, vuelve a verse (si no, quedaba invisible hasta los 180 días).
            if ($nuevo_estado === 'enviado') unset($cot['fuera_de_presupuestos']);

            // Si pasa a entregado, marcar entregado_at
            if ($nuevo_estado === 'entregado') {
                $cot['entregado_at'] = $now;
            }

            // Reversión entregado → aprobado ("devolver a activos"):
            // limpiar entregado_at y borrar el ZIP (ya no aplica).
            if ($estado_anterior === 'entregado' && $nuevo_estado === 'aprobado') {
                unset($cot['entregado_at']);
                if (!empty($cot['zip_path'])) {
                    $zip_abs = BS_DATA_DIR . '/' . $cot['zip_path'];
                    if (file_exists($zip_abs)) @unlink($zip_abs);
                    $cot['zip_path'] = null;
                }
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
            } elseif ($cot['estado'] === 'entregado' || $conservar_hermanas) {
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
