<?php
// ============================================================
// _copias.php — La copia de las últimas 24 horas (dueño, 19/11/2026)
// ============================================================
// "Necesito que haya una copia de las últimas 24 horas siempre, y se vaya pisando: que siempre
//  podamos volver a las últimas 24 horas."
//
// CÓMO ANDA
//   · Una copia por cada hora en que algo cambió. La toma el primer pedido de la hora, ANTES de que
//     ese pedido cambie nada (la llama bs_require_permiso al entrar), y la limpieza de las 3 de la
//     mañana antes de borrar. No hay que programar nada en el hosting.
//   · Si desde la última copia no cambió ningún dato, no se copia de nuevo: una pantalla que quedó
//     abierta toda la noche o todo el fin de semana no gasta copias. Cada copia es un estado distinto.
//   · Se guardan las últimas BS_COPIAS_CUANTAS (25) y se pisa la más vieja. Con 25 copias de una hora
//     nunca hay menos de 24 horas para atrás; como de noche no se trabaja, suelen ser más de dos días.
//   · Qué entra: los datos. Presupuestos (clientes/), fichas del tablero (tablero/obras.json),
//     contratos (contratos/contratos.json) y el índice de números (registro/).
//   · Qué no entra: usuarios y contraseñas, el registro de acciones, los tildes de Control, y las
//     fotos, planos, PDF y ZIP, que pesan demasiado para copiarlos cada hora. Para que volver no deje
//     fichas con fotos rotas, un archivo que se borra no se destruye: queda en copias/borrados/
//     mientras exista una copia que lo pueda necesitar (bs_copia_borrar) y vuelve a su lugar junto
//     con esa copia.
//   · Volver (SOLO ADMINISTRADORES, desde Control; endpoint copias.php, permiso "copias"): pide dos
//     confirmaciones, el botón y escribir USAR RESPALDO en mayúsculas (dueño, 20/11/2026). Antes de
//     pisar nada se guarda sola una copia de cómo está todo en ese momento ("antes de volver"), así
//     que la vuelta también se puede deshacer. Los números de presupuesto y de ficha que se dieron después de la
//     copia no se vuelven a usar.
//
// Si este archivo no está, el sistema anda igual, sin copias: todo el que lo usa pregunta antes con
// is_file() o function_exists().
// ============================================================

require_once __DIR__ . '/_config.php';

define('BS_COPIAS_DIR',       BS_DATA_DIR . '/copias');
define('BS_COPIAS_BORRADOS',  BS_COPIAS_DIR . '/borrados');
define('BS_COPIAS_CUANTAS',   25);                 // una por hora trabajada: 25 cubren siempre 24 horas
define('BS_COPIAS_ANTES',     'antes-de-volver');  // la que se toma sola antes de volver a otra
// Para regresar a un respaldo hacen falta DOS confirmaciones (dueño, 20/11/2026): tocar el botón y
// escribir esta frase, tal cual, en mayúsculas. La pantalla la pide; el que la exige es copias.php.
define('BS_COPIAS_CONFIRMA',  'USAR RESPALDO');
define('BS_COPIAS_REINTENTO', 300);                // si una copia falla, no se reintenta en cada pedido: cada 5 minutos

/** El reloj. En el servidor de pruebas (nunca en producción) se puede simular con copias/.ahora-prueba. */
function bs_copias_ahora() {
    if (php_sapi_name() === 'cli-server') {
        $t = intval(@file_get_contents(BS_COPIAS_DIR . '/.ahora-prueba'));
        if ($t > 0) return $t;
    }
    return time();
}

/** El nombre de la copia de una hora: día y hora de Argentina (la zona la fija _config.php). */
function bs_copias_id($t) {
    return date('Ymd-H', $t);
}

/** Lo único que puede haber adentro de una copia. Cualquier otro nombre la da por dañada. */
function bs_copias_rel_ok($rel) {
    return (bool)preg_match('~^(clientes/\d{4,}\.json|registro/clientes-index\.json|registro/by-month/[\w.-]+\.json|tablero/obras\.json|contratos/contratos\.json)$~', (string)$rel);
}

/** Los archivos que se copian: [ruta en disco, nombre adentro de la copia]. */
function bs_copias_archivos() {
    $l = [];
    foreach (@scandir(BS_CLIENTES_DIR) ?: [] as $f) {
        if (preg_match('/^\d{4,}\.json$/', $f)) $l[] = [BS_CLIENTES_DIR . '/' . $f, 'clientes/' . $f];
    }
    foreach (['registro/clientes-index.json', 'tablero/obras.json', 'contratos/contratos.json'] as $r) {
        if (is_file(BS_DATA_DIR . '/' . $r)) $l[] = [BS_DATA_DIR . '/' . $r, $r];
    }
    foreach (@scandir(BS_REGISTRO_BY_MONTH_DIR) ?: [] as $f) {
        if (preg_match('/^[\w.-]+\.json$/', $f) && is_file(BS_REGISTRO_BY_MONTH_DIR . '/' . $f)) $l[] = [BS_REGISTRO_BY_MONTH_DIR . '/' . $f, 'registro/by-month/' . $f];
    }
    return $l;
}

/**
 * La huella de los datos: cambia si cambia un solo byte de cualquiera de los archivos que se copian,
 * o si aparece o desaparece uno. Sirve para no copiar dos veces lo mismo.
 */
function bs_copias_huella() {
    $h = hash_init('md5');
    foreach (bs_copias_archivos() as $a) hash_update($h, $a[1] . "\0" . (string)@md5_file($a[0]) . "\n");
    return hash_final($h);
}

/**
 * Los candados de escritura, en el mismo orden en que los toma el resto del sistema (clientes,
 * tablero, contratos): con los tres tomados nadie está a mitad de guardar algo.
 */
function bs_copias_candados() {
    $c = [];
    foreach ([BS_CLIENTES_DIR, BS_DATA_DIR . '/tablero', BS_DATA_DIR . '/contratos'] as $d) {
        if (!is_dir($d)) continue; // todavía no se usó: no hay nada que cuidar
        $fp = bs_lock_acquire($d . '/.write.lock');
        if ($fp) $c[] = $fp;
    }
    return $c;
}

function bs_copias_soltar(array $candados) {
    foreach (array_reverse($candados) as $fp) bs_lock_release($fp);
}

/**
 * Arma una copia y la deja en copias/<id>.zip, con su ficha al lado (<id>.json). Pisa la que hubiera
 * con ese nombre. Se llama con los candados de escritura tomados.
 * Un archivo ilegible no frena la copia: se usa su .bak si está sano y, si no, se saltea y queda
 * anotado ("saltados"); al volver, ese archivo no se toca.
 */
function bs_copia_hacer($id, $tipo, $t, $huella = '') {
    if (!class_exists('ZipArchive')) return false;
    if (!is_dir(BS_COPIAS_DIR) && !@mkdir(BS_COPIAS_DIR, 0755, true)) return false;
    @set_time_limit(120);
    $tmp = BS_COPIAS_DIR . '/.' . $id . '.' . getmypid() . '.' . bin2hex(random_bytes(3)) . '.tmp';
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) return false;
    $n = 0; $bytes = 0; $saltados = [];
    $cuenta = ['presupuestos' => 0, 'fichas' => 0, 'contratos' => 0];
    foreach (bs_copias_archivos() as $a) {
        list($abs, $rel) = $a;
        $txt = @file_get_contents($abs);
        $d = $txt === false ? null : json_decode($txt, true);
        if (!is_array($d)) { // roto: se prueba con el .bak
            $abs .= '.bak';
            $txt = @file_get_contents($abs);
            $d = $txt === false ? null : json_decode($txt, true);
        }
        // addFile y no addFromString: el archivo se lee del disco al cerrar el ZIP (con los candados
        // tomados nadie lo cambia), y la memoria no crece con la cantidad de presupuestos.
        if (!is_array($d) || !$zip->addFile($abs, $rel)) { $saltados[] = $rel; continue; }
        $n++; $bytes += strlen($txt);
        if (strpos($rel, 'clientes/') === 0) {
            if (intval(basename($rel, '.json')) < 90000) $cuenta['presupuestos']++; // de 90001 en adelante son fichas a mano
            foreach ((array)($d['cotizaciones'] ?? []) as $cot) { if (($cot['estado'] ?? '') === 'aprobado') $cuenta['fichas']++; }
        } elseif ($rel === 'contratos/contratos.json') {
            foreach ((array)($d['contratos'] ?? []) as $c) { if (empty($c['borrado'])) $cuenta['contratos']++; }
        }
    }
    $ficha = ['version' => 1, 'id' => $id, 'tipo' => $tipo, 'at' => date('c', $t), 'archivos' => $n, 'bytes' => $bytes, 'cuenta' => $cuenta, 'saltados' => $saltados, 'huella' => (string)$huella];
    $zip->addFromString('copia.json', json_encode($ficha, JSON_UNESCAPED_UNICODE));
    if (!$zip->close() || !is_file($tmp) || filesize($tmp) === 0) { @unlink($tmp); return false; }
    $ficha['zip_bytes'] = filesize($tmp);
    if (!@rename($tmp, BS_COPIAS_DIR . '/' . $id . '.zip')) { @unlink($tmp); return false; }
    @chmod(BS_COPIAS_DIR . '/' . $id . '.zip', 0600);
    bs_write_json_atomic(BS_COPIAS_DIR . '/' . $id . '.json', $ficha);
    @unlink(BS_COPIAS_DIR . '/' . $id . '.json.bak');
    return $ficha;
}

/** Las copias que hay, la más nueva primero. Cada una: su ficha (id, tipo, at, archivos, cuenta…). */
function bs_copias_lista() {
    $l = [];
    foreach (@scandir(BS_COPIAS_DIR) ?: [] as $f) {
        if (!preg_match('/^(\d{8}-\d{2}|' . BS_COPIAS_ANTES . ')\.zip$/', $f, $m)) continue;
        $j = bs_read_json(BS_COPIAS_DIR . '/' . $m[1] . '.json');
        if (!is_array($j) || empty($j['at']) || strtotime((string)$j['at']) === false) { // sin ficha al lado: se lee de adentro
            if (!class_exists('ZipArchive')) continue;
            $zip = new ZipArchive(); $j = null;
            if ($zip->open(BS_COPIAS_DIR . '/' . $f) === true) { $j = json_decode((string)$zip->getFromName('copia.json'), true); $zip->close(); }
            if (!is_array($j) || empty($j['at']) || strtotime((string)$j['at']) === false) continue;
        }
        $j['id'] = $m[1];
        $j['tipo'] = $m[1] === BS_COPIAS_ANTES ? 'antes' : 'hora';
        $j['t'] = strtotime((string)$j['at']);
        $l[] = $j;
    }
    usort($l, function ($a, $b) { return $b['t'] <=> $a['t'] ?: strcmp($b['id'], $a['id']); });
    return $l;
}

/**
 * Se pisa lo viejo: quedan las últimas BS_COPIAS_CUANTAS copias de hora. Con ellas se van los
 * archivos borrados que ya ninguna copia puede necesitar y la copia "antes de volver" que quedó
 * más vieja que todas.
 */
function bs_copias_rotar() {
    $todas = bs_copias_lista();
    $horas = array_values(array_filter($todas, function ($c) { return $c['tipo'] === 'hora'; }));
    foreach (array_slice($horas, BS_COPIAS_CUANTAS) as $c) {
        @unlink(BS_COPIAS_DIR . '/' . $c['id'] . '.zip');
        @unlink(BS_COPIAS_DIR . '/' . $c['id'] . '.json');
    }
    $quedan = array_slice($horas, 0, BS_COPIAS_CUANTAS);
    if (!$quedan) return;
    $mas_vieja = min(array_column($quedan, 't'));
    foreach ($todas as $c) {
        if ($c['tipo'] === 'antes' && $c['t'] < $mas_vieja && count($horas) >= BS_COPIAS_CUANTAS) {
            @unlink(BS_COPIAS_DIR . '/' . $c['id'] . '.zip');
            @unlink(BS_COPIAS_DIR . '/' . $c['id'] . '.json');
        } elseif ($c['tipo'] === 'antes') {
            $mas_vieja = min($mas_vieja, $c['t']);
        }
    }
    // los borrados: se van los que se borraron antes de la copia más vieja (esa copia ya no los nombra)
    foreach (bs_copias_borrados() as $b) {
        if ($b['t'] < $mas_vieja) @unlink($b['ruta']);
    }
    bs_copias_podar(BS_COPIAS_BORRADOS);
    // restos de una copia o de una vuelta que se cortó a la mitad
    $ahora = bs_copias_ahora();
    foreach (@scandir(BS_COPIAS_DIR) ?: [] as $f) {
        $p = BS_COPIAS_DIR . '/' . $f;
        if (preg_match('/\.tmp$/', $f) && is_file($p) && abs($ahora - filemtime($p)) > 3600) @unlink($p);
        if (strpos($f, '.volver-') === 0 && is_dir($p) && abs($ahora - filemtime($p)) > 3600) bs_copias_vaciar($p);
    }
}

/** Los archivos guardados en copias/borrados/: [ruta, rel (dónde estaba, desde bs-data), t (cuándo se borró)]. */
function bs_copias_borrados() {
    $l = [];
    if (!is_dir(BS_COPIAS_BORRADOS)) return $l;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(BS_COPIAS_BORRADOS, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!$f->isFile()) continue;
        $rel = substr($f->getPathname(), strlen(BS_COPIAS_BORRADOS) + 1);
        if (!preg_match('/^(.+)\.(\d{9,11})$/', $rel, $m)) continue;
        $l[] = ['ruta' => $f->getPathname(), 'rel' => $m[1], 't' => intval($m[2])];
    }
    return $l;
}

/** Borra las carpetas que quedaron vacías adentro de $dir (no borra $dir). */
function bs_copias_podar($dir) {
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) { if ($f->isDir()) @rmdir($f->getPathname()); }
}

/** Borra una carpeta de trabajo de copias/ con todo lo que tenga. Solo adentro de copias/. */
function bs_copias_vaciar($dir) {
    if (!is_dir($dir) || strpos($dir, BS_COPIAS_DIR . '/') !== 0) return;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) { if ($f->isDir()) @rmdir($f->getPathname()); else @unlink($f->getPathname()); }
    @rmdir($dir);
}

/**
 * La copia de esta hora, si hace falta. Es lo que se llama en cada pedido: lo normal es que la hora
 * ya esté vista y esto cueste leer un archivo de 11 letras.
 * Nunca frena el trabajo: si no puede copiar, sigue de largo y lo vuelve a intentar a los 5 minutos.
 */
function bs_copia_si_toca() {
    static $ya = false;
    if ($ya) return; $ya = true;
    if (!class_exists('ZipArchive') || !is_dir(BS_DATA_DIR)) return;
    $t = bs_copias_ahora(); $id = bs_copias_id($t);
    if (@file_get_contents(BS_COPIAS_DIR . '/.hora') === $id) return; // esta hora ya se miró
    $fallo = @filemtime(BS_COPIAS_DIR . '/.fallo');
    if ($fallo && abs(time() - $fallo) < BS_COPIAS_REINTENTO) return;
    if (!is_dir(BS_COPIAS_DIR) && !@mkdir(BS_COPIAS_DIR, 0755, true)) return;
    $fp = @fopen(BS_COPIAS_DIR . '/.lock', 'c');
    if (!$fp) return;
    if (!flock($fp, LOCK_EX | LOCK_NB)) { fclose($fp); return; } // otro pedido la está haciendo ahora mismo
    $candados = [];
    try {
        if (@file_get_contents(BS_COPIAS_DIR . '/.hora') !== $id) {
            $candados = bs_copias_candados();
            $huella = bs_copias_huella();
            $ultima = null;
            foreach (bs_copias_lista() as $c) { if ($c['tipo'] === 'hora') { $ultima = $c; break; } }
            $bien = true;
            if (!$ultima || (string)($ultima['huella'] ?? '') !== $huella) $bien = (bool)bs_copia_hacer($id, 'hora', $t, $huella); // si no cambió nada, la última copia sigue valiendo
            bs_copias_soltar($candados); $candados = [];
            if ($bien) { @file_put_contents(BS_COPIAS_DIR . '/.hora', $id, LOCK_EX); @unlink(BS_COPIAS_DIR . '/.fallo'); bs_copias_rotar(); }
            else { @touch(BS_COPIAS_DIR . '/.fallo'); @error_log('bs_copia_si_toca: no se pudo armar la copia ' . $id); }
        }
    } catch (Throwable $e) {
        bs_copias_soltar($candados);
        @touch(BS_COPIAS_DIR . '/.fallo');
        @error_log('bs_copia_si_toca: ' . $e->getMessage());
    }
    flock($fp, LOCK_UN); fclose($fp);
}

/**
 * Borra un archivo (foto, plano, PDF o ZIP) sin destruirlo: lo guarda en copias/borrados/ con la
 * hora en que se borró, por si se vuelve a una copia que lo nombra. Si no lo puede guardar, lo borra
 * como siempre. Lo que se guarda acá se va solo cuando ya no queda ninguna copia anterior a su borrado.
 */
function bs_copia_borrar($path) {
    if (!is_file($path)) return;
    $base = realpath(BS_DATA_DIR); $real = realpath($path);
    if ($base !== false && $real !== false && strpos($real, $base . '/') === 0 && strpos($real, $base . '/copias/') !== 0) {
        $t = bs_copias_ahora();
        $dest = BS_COPIAS_BORRADOS . '/' . substr($real, strlen($base) + 1) . '.' . $t;
        if ((is_dir(dirname($dest)) || @mkdir(dirname($dest), 0755, true)) && @rename($real, $dest)) return;
    }
    @unlink($path);
}

/**
 * Volver a una copia. Devuelve qué se hizo; si algo no cierra tira una excepción con el mensaje para
 * mostrar y NO cambia nada (todo lo que puede fallar se revisa antes de pisar el primer archivo).
 */
function bs_copia_volver($id) {
    if (!class_exists('ZipArchive')) throw new RuntimeException('El servidor no puede abrir las copias (falta ZipArchive).');
    if (!preg_match('/^(\d{8}-\d{2}|' . BS_COPIAS_ANTES . ')$/', (string)$id)) throw new RuntimeException('Esa copia no existe.');
    if (!is_dir(BS_COPIAS_DIR)) throw new RuntimeException('Todavía no hay ninguna copia.');
    @set_time_limit(180);
    @ignore_user_abort(true); // si se corta la conexión, la vuelta termina igual: no puede quedar a medias
    $fp = @fopen(BS_COPIAS_DIR . '/.lock', 'c');
    if (!$fp || !flock($fp, LOCK_EX)) { if ($fp) fclose($fp); throw new RuntimeException('No se pudo empezar. Probá de nuevo en un momento.'); }
    $tmp = BS_COPIAS_DIR . '/.volver-' . getmypid() . '-' . bin2hex(random_bytes(3));
    $candados = [];
    try {
        // 1. Sacar la copia a una carpeta aparte y revisar que esté sana, sin tocar nada todavía.
        $zipf = BS_COPIAS_DIR . '/' . $id . '.zip';
        $zip = new ZipArchive();
        if (!is_file($zipf) || $zip->open($zipf) !== true) throw new RuntimeException('Esa copia ya no está: se pisó con una más nueva. Actualizá la lista.');
        $ficha = json_decode((string)$zip->getFromName('copia.json'), true);
        $T = is_array($ficha) ? strtotime((string)($ficha['at'] ?? '')) : false;
        if (!$T) { $zip->close(); throw new RuntimeException('Esa copia está dañada y no se puede usar. Elegí otra.'); }
        $en = []; // nombre adentro de la copia → archivo ya sacado
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $rel = (string)$zip->getNameIndex($i);
            if ($rel === 'copia.json') continue;
            $txt = bs_copias_rel_ok($rel) ? $zip->getFromIndex($i) : false;
            if ($txt === false || !is_array(json_decode($txt, true))) { $zip->close(); throw new RuntimeException('Esa copia está dañada y no se puede usar. Elegí otra.'); }
            $dst = $tmp . '/' . $rel;
            if ((!is_dir(dirname($dst)) && !@mkdir(dirname($dst), 0755, true)) || @file_put_contents($dst, $txt) === false) { $zip->close(); throw new RuntimeException('No hay lugar para preparar la vuelta. No se cambió nada.'); }
            $en[$rel] = $dst;
        }
        $zip->close();
        if (count($en) !== intval($ficha['archivos'] ?? -1)) throw new RuntimeException('Esa copia está incompleta y no se puede usar. Elegí otra.');
        $saltados = array_flip(array_map('strval', (array)($ficha['saltados'] ?? []))); // lo que la copia no pudo leer: no se toca

        // 2. Nadie guarda nada mientras tanto.
        $candados = bs_copias_candados();

        // 3. Antes de pisar: una copia de cómo está todo ahora, para poder deshacer la vuelta.
        $ahora = bs_copias_ahora();
        $antes = bs_copia_hacer(BS_COPIAS_ANTES, 'antes', $ahora, bs_copias_huella());
        if (!$antes) throw new RuntimeException('No se pudo guardar la copia de cómo está ahora. No se cambió nada.');

        // 4. Los números que existen hoy y no están en la copia quedan usados para siempre: un
        //    presupuesto nuevo no puede llevar el N° de uno que ya se le mandó a un cliente, ni una
        //    ficha nueva heredar la carpeta de fotos de otra.
        $actuales = bs_copias_archivos();
        $idx = isset($en['registro/clientes-index.json']) ? json_decode((string)file_get_contents($en['registro/clientes-index.json']), true) : [];
        if (!is_array($idx)) $idx = [];
        $quemar = array_keys((array)bs_read_json(BS_CLIENTES_INDEX));
        foreach ($actuales as $a) { if (strpos($a[1], 'clientes/') === 0) $quemar[] = basename($a[1], '.json'); }
        $quemados = 0;
        foreach (array_unique(array_map('strval', $quemar)) as $nro) {
            if (!preg_match('/^\d{4,}$/', $nro) || isset($idx[$nro]) || isset($en['clientes/' . $nro . '.json'])) continue;
            $idx[$nro] = ['deleted_at' => date('c', $ahora), 'reason' => 'copia'];
            $quemados++;
        }
        if ($quemados || isset($en['registro/clientes-index.json'])) {
            $dst = $tmp . '/registro/clientes-index.json';
            if ((!is_dir(dirname($dst)) && !@mkdir(dirname($dst), 0755, true)) || @file_put_contents($dst, json_encode($idx, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) === false) throw new RuntimeException('No hay lugar para preparar la vuelta. No se cambió nada.');
            $en['registro/clientes-index.json'] = $dst;
        }

        // 5. Pisar. Cada archivo entra de un saque (rename); lo que hoy existe y la copia no tiene, se va.
        $mal = [];
        foreach ($actuales as $a) {
            list($abs, $rel) = $a;
            if (isset($en[$rel]) || isset($saltados[$rel])) continue;
            if (!@unlink($abs)) $mal[] = $rel;
            @unlink($abs . '.bak');
        }
        foreach ($en as $rel => $src) {
            $abs = BS_DATA_DIR . '/' . $rel;
            if ((!is_dir(dirname($abs)) && !@mkdir(dirname($abs), 0755, true)) || !@rename($src, $abs)) { $mal[] = $rel; continue; }
            @unlink($abs . '.bak'); // el respaldo viejo es de después de la copia: no tiene que poder volver solo
        }

        // 6. Los archivos que se borraron después de la copia vuelven a su lugar. Si en ese lugar hay
        //    uno más nuevo (el PDF de un contrato que se cambió), ese pasa a "borrados" y vuelve si se
        //    deshace la vuelta.
        $vuelven = [];
        foreach (bs_copias_borrados() as $b) {
            if ($b['t'] >= $T && (!isset($vuelven[$b['rel']]) || $b['t'] < $vuelven[$b['rel']]['t'])) $vuelven[$b['rel']] = $b;
        }
        $adjuntos = 0;
        foreach ($vuelven as $rel => $b) {
            $abs = BS_DATA_DIR . '/' . $rel;
            if (is_file($abs)) {
                $corrido = BS_COPIAS_BORRADOS . '/' . $rel . '.' . $ahora;
                if ($corrido === $b['ruta'] || !@rename($abs, $corrido)) continue;
            }
            if ((is_dir(dirname($abs)) || @mkdir(dirname($abs), 0755, true)) && @rename($b['ruta'], $abs)) $adjuntos++;
        }
        bs_copias_podar(BS_COPIAS_BORRADOS);
        bs_copias_soltar($candados); $candados = [];
        bs_copias_vaciar($tmp);
        flock($fp, LOCK_UN); fclose($fp);
        return ['vuelta' => ['id' => $id, 'at' => date('c', $T)], 'antes' => ['id' => BS_COPIAS_ANTES, 'at' => date('c', $ahora)],
            'archivos' => count($en), 'numeros_usados' => $quemados, 'adjuntos' => $adjuntos, 'mal' => $mal];
    } catch (Throwable $e) {
        bs_copias_soltar($candados);
        bs_copias_vaciar($tmp);
        flock($fp, LOCK_UN); fclose($fp);
        throw $e;
    }
}
