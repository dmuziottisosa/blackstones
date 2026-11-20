<?php
// ============================================================
// copias.php — El respaldo: las copias de las últimas 24 horas, verlas y volver a una
// ============================================================
// SOLO ADMINISTRADORES (permiso "copias"; dueño, 20/11/2026: "solo los administradores pueden acceder
// al backup"). La pantalla es Control (/presupuestos/reportes.php), que también es solo de ellos.
// Las copias se toman solas (ver _copias.php); acá solo se listan y se vuelve a una.
// Regresar pide DOS confirmaciones: el botón y escribir USAR RESPALDO, en mayúsculas. La segunda la
// exige este archivo, no la pantalla.
//
//   GET  → { ok, puede, guarda, ahora, fallo, copias: [ { id, tipo: "hora"|"antes", at, archivos,
//            kb, fichas, contratos, presupuestos, acciones, personas } ] }   (la más nueva primero)
//          "acciones" y "personas": lo que se anotó en el registro después de esa copia, o sea lo que
//          se deshace si se vuelve a ella.
//   POST { accion: "volver", id, confirmo: "USAR RESPALDO" }
//        → { ok, vuelta: { id, at }, antes: { id, at }, archivos, numeros_usados, adjuntos }
//
// Nunca devuelve nombres, teléfonos ni plata: solo cuentas.
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

bs_require_permiso('copias');
require_once __DIR__ . '/_copias.php';

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($metodo === 'GET') {
    $copias = bs_copias_lista();
    // Qué se anotó en el registro después de cada copia. Solo cuenta lo que una vuelta deshace:
    // usuarios, Control y las copias mismas no se tocan.
    $NO = ['usuarios.php', 'control.php', 'copias.php', 'cierre-mes.php', 'presencia.php'];
    $marcas = []; // [hora de la acción (texto, Argentina), usuario]
    if ($copias) {
        $dir = dirname(auth_usuarios_archivo());
        $desde = min(array_column($copias, 't'));
        $meses = array_unique([date('Y-m', $desde), date('Y-m', bs_copias_ahora())]);
        foreach ($meses as $mes) {
            foreach (@file($dir . '/registro-' . $mes . '.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $l) {
                $p = explode("\t", $l);
                if (count($p) < 4 || in_array($p[2], $NO, true)) continue;
                $marcas[] = [$p[0], $p[1]];
            }
        }
    }
    $salida = [];
    foreach ($copias as $c) {
        $cuando = date('Y-m-d H:i:s', $c['t']);
        $n = 0; $quienes = [];
        // ">=": lo anotado en el mismo segundo que la copia es posterior a ella (la copia se toma al entrar el pedido; la acción se anota al salir)
        foreach ($marcas as $m) { if (strcmp($m[0], $cuando) >= 0) { $n++; $quienes[$m[1]] = true; } }
        $salida[] = ['id' => $c['id'], 'tipo' => $c['tipo'], 'at' => date('c', $c['t']), 'archivos' => intval($c['archivos'] ?? 0),
            'kb' => intval(round(intval($c['zip_bytes'] ?? 0) / 1024)),
            'fichas' => intval($c['cuenta']['fichas'] ?? 0), 'contratos' => intval($c['cuenta']['contratos'] ?? 0), 'presupuestos' => intval($c['cuenta']['presupuestos'] ?? 0),
            'acciones' => $n, 'personas' => count($quienes)];
    }
    $fallo = @filemtime(BS_COPIAS_DIR . '/.fallo');
    bs_ok(['puede' => class_exists('ZipArchive'), 'guarda' => BS_COPIAS_CUANTAS, 'confirma' => BS_COPIAS_CONFIRMA, 'ahora' => date('c', bs_copias_ahora()),
        'fallo' => $fallo ? date('c', $fallo) : '', 'copias' => $salida]);
}

if ($metodo !== 'POST') bs_error('Solo GET o POST', 405);
bs_check_csrf();

$in = json_decode((string)@file_get_contents('php://input'), true);
if (!is_array($in)) bs_error('Pedido inválido');
if (($in['accion'] ?? '') !== 'volver') bs_error('Acción desconocida');
// Tal cual y en mayúsculas: "usar respaldo" no alcanza.
if (trim((string)($in['confirmo'] ?? '')) !== BS_COPIAS_CONFIRMA) bs_error('Para usar un respaldo hay que escribir ' . BS_COPIAS_CONFIRMA . ', en mayúsculas.');

try {
    $r = bs_copia_volver((string)($in['id'] ?? ''));
} catch (Throwable $e) {
    bs_error($e->getMessage(), 409);
}
if (!empty($r['mal'])) {
    // Se pisó casi todo pero algún archivo no se dejó escribir: se avisa, y la copia "antes de volver" sigue ahí.
    @error_log('copias.php: no se pudieron pisar ' . implode(', ', $r['mal']));
    $r['aviso'] = 'El respaldo quedó a medias: ' . count($r['mal']) . ' archivo(s) no se pudieron escribir. Se puede deshacer con "Antes de usar el respaldo".';
}
$r['mal'] = count($r['mal']);
bs_ok($r);
