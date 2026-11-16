<?php
// ============================================================
// tablero-papelera.php — Papelera de fichas del tablero (30 días)
// BlackStones Marmolería · Tablero de obras
// ============================================================
// Borrar una ficha ya no la destruye: va a la papelera y se puede
// recuperar durante BS_PAPELERA_DIAS (30). Decisión del dueño, 16/11/2026.
//
// Cómo: la cotización de la obra pasa de "aprobado" a "papelera". Con eso
// deja de verse en el tablero, el calendario y Presupuestos, y nada más se
// toca: el registro del tablero (tildes, fechas, datos) y sus fotos y planos
// quedan donde estaban. Restaurar es volverla a "aprobado".
// A los 30 días la borra bs_tablero_papelera_purgar() (ver _tablero.php).
//
// GET  → { ok, dias, fichas: [ { key, cliente_nro, sub, nombre, material,
//          etapa, etapa_titulo, adjuntos, manual, borrada, quedan } ] }
// POST → { "accion": "tirar" | "restaurar", "cliente_nro": "0042", "sub": 1 }
// ============================================================

require_once __DIR__ . '/_tablero.php';
require_once __DIR__ . '/_auth.php';

bs_require_auth();
bs_check_csrf();
bs_ensure_dirs();

date_default_timezone_set('America/Argentina/Buenos_Aires');

// ============================================================
// GET — qué hay en la papelera (lo vencido se borra antes de listar)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    bs_tablero_papelera_purgar();
    $tablero = bs_tablero_leer();
    $fichas = [];
    if (is_dir(BS_CLIENTES_DIR)) {
        foreach (scandir(BS_CLIENTES_DIR) as $f) {
            if (!preg_match('/^(\d{4,})\.json$/', $f)) continue;
            $cli = bs_read_json(BS_CLIENTES_DIR . '/' . $f);
            if (!$cli) continue;
            $nro = (string)($cli['cliente_nro'] ?? '');
            foreach ($cli['cotizaciones'] ?? [] as $cot) {
                if (($cot['estado'] ?? '') !== 'papelera') continue;
                $sub = intval($cot['sub'] ?? 0);
                $key = $nro . '-' . $sub;
                $obra = array_merge(bs_tablero_obra_vacia(), $tablero['obras'][$key] ?? []);
                $etapa = isset($BS_ETAPAS[$obra['etapa']]) ? $obra['etapa'] : 'medir';
                $at = strtotime((string)($cot['papelera']['at'] ?? '')) ?: time();
                // material: el final si está cargado, si no el del presupuesto (mismo dato que muestra la ficha)
                $material = trim((string)($cot['material_final'] ?? ''));
                if ($material === '') $material = trim((string)($cot['presupuesto']['material'] ?? ($cot['material'] ?? '')));
                $fichas[] = [
                    'key'          => $key,
                    'cliente_nro'  => $nro,
                    'sub'          => $sub,
                    'nombre'       => (string)($cli['cliente']['nombre'] ?? ''),
                    'material'     => $material,
                    'etapa'        => $etapa,
                    'etapa_titulo' => (string)$BS_ETAPAS[$etapa]['titulo'],
                    'adjuntos'     => count(bs_tablero_adjuntos($obra)),
                    'manual'       => !empty($cot['manual']),
                    'borrada'      => date('Y-m-d', $at),
                    'quedan'       => max(0, BS_PAPELERA_DIAS - intval(floor((time() - $at) / 86400))),
                ];
            }
        }
    }
    usort($fichas, function ($a, $b) { return strcmp($b['borrada'], $a['borrada']) ?: strcmp($a['key'], $b['key']); });
    bs_ok(['dias' => BS_PAPELERA_DIAS, 'fichas' => $fichas]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') bs_error('Solo GET o POST', 405);

$body = json_decode(@file_get_contents('php://input'), true);
if (!is_array($body)) bs_error('Body JSON inválido');
$accion = (string)($body['accion'] ?? '');
if (!in_array($accion, ['tirar', 'restaurar'], true)) bs_error('accion inválida');
$cliente_nro = str_pad((string)intval($body['cliente_nro'] ?? ''), 4, '0', STR_PAD_LEFT);
$sub = intval($body['sub'] ?? 0);
if (intval($cliente_nro) <= 0 || $sub <= 0) bs_error('Obra inválida');

$path = BS_CLIENTES_DIR . '/' . $cliente_nro . '.json';
$fp = bs_lock_acquire(BS_CLIENTES_DIR . '/.write.lock');
if (!$fp) bs_error('No se pudo adquirir lock', 500);

try {
    $cli = bs_read_json($path);
    if (!$cli) { bs_lock_release($fp); bs_error('Esa obra ya no existe. Actualizá el tablero.', 404); }
    $ahora = date('c');
    $hecho = false;
    foreach ($cli['cotizaciones'] as $i => $cot) {
        if (intval($cot['sub'] ?? 0) !== $sub) continue;
        $estado = (string)($cot['estado'] ?? '');
        if ($accion === 'tirar') {
            if ($estado === 'papelera') { $hecho = true; break; } // ya estaba: nada que hacer
            if ($estado !== 'aprobado') { bs_lock_release($fp); bs_error('Esa obra ya no está en el tablero. Actualizá el tablero.', 409); }
            $cli['cotizaciones'][$i]['estado'] = 'papelera';
            $cli['cotizaciones'][$i]['papelera'] = ['at' => $ahora];
            $cli['cotizaciones'][$i]['transiciones'][] = ['estado' => 'papelera', 'at' => $ahora];
        } else {
            if ($estado === 'aprobado') { $hecho = true; break; } // ya estaba de vuelta
            if ($estado !== 'papelera') { bs_lock_release($fp); bs_error('Esa ficha ya no está en la papelera.', 409); }
            $cli['cotizaciones'][$i]['estado'] = 'aprobado';
            unset($cli['cotizaciones'][$i]['papelera']);
            $cli['cotizaciones'][$i]['transiciones'][] = ['estado' => 'aprobado', 'at' => $ahora];
        }
        $cli['ultima_actualizacion'] = $ahora;
        if (!bs_write_json_atomic($path, $cli)) { bs_lock_release($fp); bs_error('No se pudo guardar', 500); }
        $hecho = true;
        break;
    }
    bs_lock_release($fp);
    if (!$hecho) bs_error('Esa obra ya no existe. Actualizá el tablero.', 404);
    bs_ok(['key' => $cliente_nro . '-' . $sub, 'accion' => $accion, 'dias' => BS_PAPELERA_DIAS]);
} catch (Throwable $e) {
    bs_lock_release($fp);
    bs_error('Excepción: ' . $e->getMessage(), 500);
}
