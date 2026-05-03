<?php
// ============================================================
// next-nro.php — Devuelve el siguiente N° de cliente libre
// ============================================================
// GET /api/next-nro.php
// Response: { "ok": true, "next": "0044" }
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

bs_require_auth();
bs_ensure_dirs();

// Scan de clientes/, encontrar max nro existente
$max = 0;
if (is_dir(BS_CLIENTES_DIR)) {
    $files = @scandir(BS_CLIENTES_DIR);
    if ($files !== false) {
        foreach ($files as $f) {
            if (preg_match('/^(\d{4,})\.json$/', $f, $m)) {
                $n = intval($m[1]);
                if ($n > $max) $max = $n;
            }
        }
    }
}

// También chequear el clientes-index.json para nros que existieron pero ya fueron borrados por retención
// (así no reciclamos nros de clientes históricos)
$idx = bs_read_json(BS_CLIENTES_INDEX);
if (is_array($idx)) {
    foreach ($idx as $nro_str => $_v) {
        $n = intval($nro_str);
        if ($n > $max) $max = $n;
    }
}

$next = $max + 1;
$next_str = str_pad((string)$next, 4, '0', STR_PAD_LEFT);

bs_ok(['next' => $next_str]);
