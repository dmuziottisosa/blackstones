<?php
// ============================================================
// BlackStones — ig-feed.php
// Los últimos posts de @blackstones.ar para la sección Instagram de la landing.
// Ubicación: blackstones.com.ar/ig-feed.php
//
// Qué hace
//   La landing le pide a este archivo la lista de posts. La tiene guardada y la
//   devuelve al instante. Como mucho una vez por hora le pregunta a Instagram si hay
//   algo nuevo (API oficial, "Instagram API with Instagram Login"), baja la foto de
//   cada post nuevo y se queda con una copia propia, porque los links de las fotos
//   de Instagram vencen.
//
// Dónde vive cada cosa
//   bs-data/ig/token.txt     la llave de Instagram. Fuera de la web y fuera del repo.
//   bs-data/ig/feed.json     la última lista buena de posts.
//   bs-data/ig/estado.json   cuándo se intentó, qué contestó Instagram, cuándo vence la llave.
//   ig_section/cache/        las copias de las fotos (dentro de la web: las sirve el servidor).
//
// Lo que no hace nunca
//   - Mostrar la llave: no sale en ninguna respuesta ni se copia a otro archivo.
//   - Dejar la sección vacía: si Instagram falla o la llave vence, sigue devolviendo la
//     última lista buena. Si no hay ninguna, la landing muestra sus fotos fijas.
//   - Hacer esperar al visitante: a Instagram se le pregunta después de contestarle.
//   - Mostrar otra cuenta: si la llave no es de @blackstones.ar, no publica nada.
//
// Para ver qué está pasando: /ig-feed.php?estado=1
// Doc: 04-operations/instagram-en-la-landing.md
// ============================================================

ini_set('display_errors', '0');
@ini_set('zlib.output_compression', '0'); // la respuesta pesa 1 KB: sin comprimir se puede contestar antes de terminar
ob_start();
$ig_bufer = ob_get_level(); // lo que se escriba sin querer (un aviso de PHP) no llega a la respuesta
date_default_timezone_set('America/Argentina/Buenos_Aires');

define('IG_CUENTA',          'blackstones.ar'); // la única cuenta que se muestra
define('IG_PERFIL',          'https://www.instagram.com/blackstones.ar/');
define('IG_TILES',           6);                // fotos de la grilla
define('IG_MIRAR',           25);               // posts que se miran para juntar los de la grilla: alguno puede no tener foto
define('IG_TTL',             3600);             // a Instagram se le pregunta, como mucho, una vez por hora
define('IG_ESPERA_ERROR',    900);              // si falló, no se insiste hasta dentro de 15 minutos
define('IG_ESPERA_LLAVE',    21600);            // si rechazó la llave, cada 6 horas: no se arregla sola
define('IG_LLAVE_DURA',      60 * 86400);       // una llave dura 60 días
define('IG_RENOVAR_CADA',    7 * 86400);        // y se renueva sola cada semana
define('IG_RENOVAR_ESPERA',  86400);            // si no se pudo renovar, se prueba al otro día
define('IG_LADO_GRANDE',     900);              // lado corto de la foto del tile grande
define('IG_LADO_CHICO',      480);              // y de los tiles chicos
define('IG_MAX_FOTO',        8 * 1024 * 1024);
define('IG_TIEMPO_FOTOS',    20);               // segundos, entre todas las fotos de una pasada
define('IG_GUARDAR_DIAS',    7);                // una copia que ya no se muestra se borra a la semana
define('IG_RE_LLAVE',        '/^[A-Za-z0-9_.\-|%=]{30,2000}$/');
define('IG_RE_FOTO',         '/^\d{5,30}-[gco]\.(webp|jpg|png)$/');

$IG_DATOS = __DIR__ . '/../../../bs-data/ig';
$IG_FOTOS = __DIR__ . '/ig_section/cache';
$IG_API   = 'https://graph.instagram.com';
$IG_HOSTS = ['cdninstagram.com', 'fbcdn.net']; // de dónde se acepta una foto
$IG_HTTP  = false;

// Solo para probar en la computadora de desarrollo, contra un Instagram de mentira.
$ig_prueba = getenv('BS_IG_API');
if (is_string($ig_prueba) && preg_match('#^http://127\.0\.0\.1:\d{2,5}$#', $ig_prueba)) {
    $IG_API = $ig_prueba; $IG_HOSTS[] = '127.0.0.1'; $IG_HTTP = true;
}

// ------------------------------------------------------------
// Archivos
// ------------------------------------------------------------
function ig_leer($ruta) {
    $t = @file_get_contents($ruta);
    $d = is_string($t) ? json_decode($t, true) : null;
    return is_array($d) ? $d : [];
}

function ig_escribir_archivo($ruta, $contenido) {
    $tmp = $ruta . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, $contenido, LOCK_EX) === false) { @unlink($tmp); return false; }
    @chmod($tmp, 0600);
    if (!@rename($tmp, $ruta)) { @unlink($tmp); return false; }
    return true;
}

function ig_escribir($ruta, $datos) {
    $json = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    return $json !== false && ig_escribir_archivo($ruta, $json);
}

// La llave, o '' si falta o si el archivo no trae una llave.
function ig_llave($ruta) {
    if (!is_file($ruta)) return ['', 'falta'];
    $t = @file_get_contents($ruta, false, null, 0, 8192);
    if (!is_string($t)) return ['', 'ilegible'];
    $t = str_replace("\0", '', $t); // por si la guardaron en UTF-16
    $t = trim(preg_replace('/^(\xEF\xBB\xBF|\xFF\xFE|\xFE\xFF)/', '', $t));
    return preg_match(IG_RE_LLAVE, $t) ? [$t, 'cargada'] : ['', 'ilegible'];
}

// ------------------------------------------------------------
// Pedidos hacia afuera
// ------------------------------------------------------------
function ig_pedir($url, $segundos, $max_bytes, $es_foto) {
    global $IG_HTTP;
    $cuerpo = ''; $cortado = false;
    $prot = CURLPROTO_HTTPS | ($IG_HTTP ? CURLPROTO_HTTP : 0);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => $segundos,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_PROTOCOLS      => $prot,
        CURLOPT_REDIR_PROTOCOLS => $prot,
        // Un pedido con la llave no sigue redirecciones. Una foto, hasta tres.
        CURLOPT_FOLLOWLOCATION => $es_foto,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_ENCODING       => '',
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; ig-feed/1.0)',
        CURLOPT_WRITEFUNCTION  => function ($ch, $trozo) use (&$cuerpo, &$cortado, $max_bytes, $es_foto) {
            if ($cuerpo === '' && $es_foto) {
                $tipo = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                if (preg_match('#^(video|audio|text)/#i', $tipo)) { $cortado = true; return -1; } // un video: no se baja
            }
            if (strlen($cuerpo) + strlen($trozo) > $max_bytes) { $cortado = true; return -1; }
            $cuerpo .= $trozo;
            return strlen($trozo);
        },
    ]);
    curl_exec($ch);
    $r = [
        'http'    => (int)curl_getinfo($ch, CURLINFO_HTTP_CODE),
        'errno'   => curl_errno($ch),
        'final'   => (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
        'cuerpo'  => $cuerpo,
        'cortado' => $cortado,
    ];
    $ch = null;
    return $r;
}

// Un mensaje de error que se puede mostrar: sin la llave adentro, corto y de una línea.
function ig_limpio($texto, $llave) {
    $t = (string)$texto;
    if ($llave !== '') $t = str_replace($llave, '[llave]', $t);
    $t = preg_replace('/[A-Za-z0-9_\-|%]{40,}/', '[...]', $t);
    $t = trim(preg_replace('/\s+/', ' ', preg_replace('/[^\P{C}\s]/u', '', $t) ?? $t));
    return function_exists('mb_substr') ? mb_substr($t, 0, 240, 'UTF-8') : substr($t, 0, 240);
}

function ig_error($tipo, $codigo, $mensaje, $llave = '') {
    return ['ok' => false, 'error' => ['tipo' => $tipo, 'codigo' => (int)$codigo, 'mensaje' => ig_limpio($mensaje, $llave), 'cuando' => time()]];
}

// Una llamada a la API de Instagram. Devuelve ['ok'=>true,'datos'=>...] o un error.
//   tipo 'llave'      Instagram no acepta la llave: hay que generar otra.
//   tipo 'instagram'  no contestó o contestó otra cosa: se reintenta solo.
function ig_api($ruta, $params, $llave) {
    global $IG_API;
    $params['access_token'] = $llave;
    $r = ig_pedir($IG_API . '/' . $ruta . '?' . http_build_query($params), 10, 2 * 1024 * 1024, false);
    if ($r['errno'] !== 0) return ig_error('instagram', 0, 'No se pudo conectar con Instagram (cURL ' . $r['errno'] . ').');
    $d = json_decode($r['cuerpo'], true);
    if (is_array($d) && isset($d['error']) && is_array($d['error'])) {
        $cod = (int)($d['error']['code'] ?? 0);
        // 190: llave vencida, revocada o mal copiada. 102: sesión. 10 y 200-299: a la llave le falta el permiso.
        $de_llave = $cod === 190 || $cod === 102 || $cod === 10 || ($cod >= 200 && $cod <= 299);
        return ig_error($de_llave ? 'llave' : 'instagram', $cod, (string)($d['error']['message'] ?? 'Error sin mensaje'), $llave);
    }
    if (is_array($d) && isset($d['error_message'])) { // el formato viejo de Instagram
        $de_llave = stripos((string)($d['error_type'] ?? ''), 'oauth') !== false;
        return ig_error($de_llave ? 'llave' : 'instagram', $de_llave ? 190 : (int)($d['code'] ?? 0), (string)$d['error_message'], $llave);
    }
    if ($r['http'] !== 200 || !is_array($d)) return ig_error('instagram', 0, 'Instagram contestó algo que no se entiende (HTTP ' . $r['http'] . ').');
    return ['ok' => true, 'datos' => $d];
}

// ------------------------------------------------------------
// Fotos
// ------------------------------------------------------------
function ig_host_ok($url) {
    global $IG_HOSTS, $IG_HTTP;
    if (!is_string($url) || $url === '') return false;
    $p = @parse_url($url);
    if (!is_array($p) || empty($p['host']) || empty($p['scheme'])) return false;
    $esq = strtolower($p['scheme']);
    if ($esq !== 'https' && !($IG_HTTP && $esq === 'http')) return false;
    $host = strtolower($p['host']);
    foreach ($IG_HOSTS as $h) {
        if ($host === $h || substr($host, -strlen('.' . $h)) === '.' . $h) return true;
    }
    return false;
}

// Los links de un post de los que puede salir una foto, en orden.
// De un video solo sirve la portada (thumbnail_url); media_url es el video.
function ig_candidatas($it) {
    $c = [];
    if (($it['media_type'] ?? '') !== 'VIDEO') $c[] = $it['media_url'] ?? '';
    $c[] = $it['thumbnail_url'] ?? '';
    $ok = [];
    foreach ($c as $u) {
        if (!ig_host_ok($u)) continue;
        if (preg_match('/\.(mp4|mov|m4v|webm)$/i', (string)parse_url($u, PHP_URL_PATH))) continue;
        $ok[] = $u;
    }
    return array_values(array_unique($ok));
}

function ig_en_cache($id) {
    global $IG_FOTOS;
    foreach (['webp', 'jpg'] as $e) {
        if (is_file("$IG_FOTOS/$id-g.$e") && is_file("$IG_FOTOS/$id-c.$e")) return ['g' => "$id-g.$e", 'c' => "$id-c.$e"];
    }
    foreach (['jpg', 'png', 'webp'] as $e) {
        if (is_file("$IG_FOTOS/$id-o.$e")) return ['g' => "$id-o.$e", 'c' => "$id-o.$e"];
    }
    return null;
}

// Baja una foto y guarda las dos copias (grande y chica). Devuelve sus nombres, o null.
function ig_bajar($url, $id) {
    global $IG_FOTOS;
    $r = ig_pedir($url, 10, IG_MAX_FOTO, true);
    if ($r['errno'] !== 0 || $r['http'] !== 200 || $r['cortado'] || !ig_host_ok($r['final'])) return null;
    $bytes = $r['cuerpo'];
    $info = @getimagesizefromstring($bytes);
    $ext_orig = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($ext_orig[$info[2]]) || $info[0] < 200 || $info[1] < 200 || $info[0] * $info[1] > 40000000) return null;
    $w = (int)$info[0]; $h = (int)$info[1];
    if (!is_dir($IG_FOTOS) && !@mkdir($IG_FOTOS, 0755, true)) return null;

    $ext = function_exists('imagewebp') ? 'webp' : (function_exists('imagejpeg') ? 'jpg' : '');
    $src = false;
    if ($ext !== '' && function_exists('imagecreatefromstring') && function_exists('imagecreatetruecolor')) {
        $src = @imagecreatefromstring($bytes);
    }
    if ($src) {
        $nombres = [];
        foreach (['g' => IG_LADO_GRANDE, 'c' => IG_LADO_CHICO] as $k => $lado) {
            $e = min(1, $lado / min($w, $h)); // nunca se agranda
            $nw = max(1, (int)round($w * $e)); $nh = max(1, (int)round($h * $e));
            $dst = imagecreatetruecolor($nw, $nh);
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // por si trae transparencia
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            $nombre = "$id-$k.$ext";
            $tmp = "$IG_FOTOS/$nombre." . bin2hex(random_bytes(3)) . '.tmp';
            if ($ext === 'jpg') @imageinterlace($dst, true);
            $ok = $ext === 'webp' ? @imagewebp($dst, $tmp, 80) : @imagejpeg($dst, $tmp, 82);
            $dst = null;
            if (!$ok || !@filesize($tmp) || !@rename($tmp, "$IG_FOTOS/$nombre")) { @unlink($tmp); return null; }
            $nombres[$k] = $nombre;
        }
        return $nombres;
    }
    // Sin la librería de imágenes: se guarda como vino.
    $nombre = "$id-o." . $ext_orig[$info[2]];
    $tmp = "$IG_FOTOS/$nombre." . bin2hex(random_bytes(3)) . '.tmp';
    if (@file_put_contents($tmp, $bytes) === false || !@rename($tmp, "$IG_FOTOS/$nombre")) { @unlink($tmp); return null; }
    return ['g' => $nombre, 'c' => $nombre];
}

// Borra las copias que ya no se muestran y tienen más de una semana. Solo toca archivos
// con el nombre que este mismo archivo les pone, y solo dentro de ig_section/cache/.
function ig_limpiar($en_uso, $ahora) {
    global $IG_FOTOS;
    $limite = $ahora - IG_GUARDAR_DIAS * 86400;
    $lista = @scandir($IG_FOTOS);
    if (!is_array($lista)) return;
    foreach ($lista as $n) {
        $base = preg_replace('/\.[0-9a-f]{6}\.tmp$/', '', $n);
        if (!preg_match(IG_RE_FOTO, $base) || isset($en_uso[$n])) continue;
        $f = "$IG_FOTOS/$n";
        if (is_file($f) && (int)@filemtime($f) < $limite) @unlink($f);
    }
}

// ------------------------------------------------------------
// La pasada: preguntarle a Instagram y dejar todo guardado
// ------------------------------------------------------------
function ig_toca($llave, $estado, $feed, $ahora) {
    if ($llave === '' || !function_exists('curl_init')) return [false, false];
    $nueva = !hash_equals((string)($estado['llave_hash'] ?? ''), hash('sha256', $llave));
    $vencido = $ahora - (int)($feed['actualizado'] ?? 0) >= IG_TTL;
    return [$nueva || ($vencido && $ahora >= (int)($estado['proximo'] ?? 0)), $nueva];
}

function ig_etiqueta($tipo, $ts) {
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $que = $tipo === 'VIDEO' ? 'Video de Instagram' : 'Post de Instagram';
    return $ts > 0 ? $que . ' del ' . (int)date('j', $ts) . ' de ' . $meses[(int)date('n', $ts) - 1] : $que;
}

function ig_link($permalink) {
    $p = is_string($permalink) ? @parse_url($permalink) : null;
    $ok = is_array($p) && strtolower($p['scheme'] ?? '') === 'https'
        && in_array(strtolower($p['host'] ?? ''), ['www.instagram.com', 'instagram.com'], true)
        && !preg_match('/["\'<>\s\\\\]/', $permalink);
    return $ok ? $permalink : IG_PERFIL;
}

function ig_refrescar($ahora, $esperar) {
    global $IG_DATOS;
    @ignore_user_abort(true);
    @set_time_limit(120);
    $candado = @fopen("$IG_DATOS/candado", 'c');
    if (!$candado) return;
    // Si otro pedido ya lo está haciendo, no se hace dos veces. Quien pide el estado lo espera, hasta 30 segundos.
    $vueltas = $esperar ? 150 : 0;
    while (!flock($candado, LOCK_EX | LOCK_NB)) {
        if ($vueltas-- <= 0) { fclose($candado); return; }
        usleep(200000);
    }

    // Se vuelve a mirar todo con el candado puesto: otro pedido pudo haberlo hecho recién.
    list($llave) = ig_llave("$IG_DATOS/token.txt");
    $estado = ig_leer("$IG_DATOS/estado.json");
    $feed = ig_leer("$IG_DATOS/feed.json");
    list($toca, $nueva) = ig_toca($llave, $estado, $feed, $ahora);
    if ($toca) {
        if ($nueva) {
            // Llave recién cargada: se olvida lo que se sabía de la anterior.
            $estado = ['llave_hash' => hash('sha256', $llave), 'llave_desde' => $ahora, 'llave_vence' => $ahora + IG_LLAVE_DURA];
        }
        // Se anota que empezó antes de salir a preguntar: si el servidor corta el pedido a la mitad,
        // el próximo no arranca de nuevo en el acto.
        // Y si no se puede anotar (carpeta sin permiso, disco lleno), no se pregunta: sin la anotación
        // se le preguntaría a Instagram en cada visita.
        $estado['intento'] = $ahora; $estado['en_curso'] = true; $estado['proximo'] = $ahora + IG_ESPERA_ERROR;
        if (!ig_escribir("$IG_DATOS/estado.json", $estado)) { flock($candado, LOCK_UN); fclose($candado); return; }
        $r = ig_pasada($llave, $estado, $ahora);
        $estado['en_curso'] = false;
        if ($r['ok']) {
            $estado['error'] = null; $estado['proximo'] = 0; $estado['bien'] = $ahora;
        } else {
            $estado['error'] = $r['error'];
            $estado['proximo'] = $ahora + ($r['error']['tipo'] === 'instagram' || $r['error']['tipo'] === 'fotos' ? IG_ESPERA_ERROR : IG_ESPERA_LLAVE);
        }
        ig_escribir("$IG_DATOS/estado.json", $estado);
    }
    flock($candado, LOCK_UN);
    fclose($candado);
}

function ig_pasada(&$llave, &$estado, $ahora) {
    global $IG_DATOS;

    // 1) ¿De qué cuenta es la llave? Se mira una vez por llave cargada.
    if (empty($estado['cuenta_ok'])) {
        $yo = ig_api('me', ['fields' => 'user_id,username'], $llave);
        if (!$yo['ok']) return $yo;
        $usuario = strtolower(preg_replace('/[^A-Za-z0-9._]/', '', (string)($yo['datos']['username'] ?? '')));
        $estado['cuenta'] = $usuario;
        if ($usuario !== IG_CUENTA) {
            return ig_error('cuenta', 0, 'La llave es de la cuenta @' . ($usuario !== '' ? $usuario : '?') . ', no de @' . IG_CUENTA . '. Hay que generar la llave de la cuenta del negocio.');
        }
        $ids = [];
        foreach (['user_id', 'id'] as $k) {
            $v = (string)($yo['datos'][$k] ?? '');
            if (preg_match('/^\d{3,30}$/', $v)) $ids[] = $v;
        }
        $estado['ids'] = array_values(array_unique($ids));
        $estado['cuenta_ok'] = true;
    }

    // 2) La lista de posts. La documentación da /<id de la cuenta>/media; se guarda la ruta que anduvo.
    $rutas = [];
    if (!empty($estado['ruta'])) $rutas[] = (string)$estado['ruta'];
    foreach ((array)($estado['ids'] ?? []) as $i) $rutas[] = $i . '/media';
    $rutas[] = 'me/media';
    $rutas = array_values(array_unique($rutas));
    $items = null; $ultimo = null;
    foreach ($rutas as $ruta) {
        if (!preg_match('#^(\d{3,30}|me)/media$#', $ruta)) continue;
        $r = ig_api($ruta, ['fields' => 'id,media_type,media_url,thumbnail_url,permalink,timestamp'], $llave);
        if ($r['ok'] && isset($r['datos']['data']) && is_array($r['datos']['data'])) { $items = $r['datos']['data']; $estado['ruta'] = $ruta; break; }
        $ultimo = $r['ok'] ? ig_error('instagram', 0, 'Instagram contestó sin la lista de posts.') : $r;
        // Otra ruta se prueba solo si el problema puede ser la ruta: no con la llave rechazada, sin
        // respuesta, ni con Instagram caído o pidiendo que se llame menos (1, 2, 4, 17, 32, 341, 613, 80002).
        if ($ultimo['error']['tipo'] === 'llave' || in_array($ultimo['error']['codigo'], [0, 1, 2, 4, 17, 32, 341, 613, 80002], true)) break;
    }
    if ($items === null) { unset($estado['ruta']); return $ultimo; }

    // 3) La foto de cada post, hasta juntar las de la grilla. Lo que ya está copiado no se vuelve a bajar.
    $posts = []; $fallos = 0; $t0 = microtime(true);
    foreach (array_slice($items, 0, IG_MIRAR) as $it) {
        if (count($posts) >= IG_TILES) break;
        $id = is_array($it) ? (string)($it['id'] ?? '') : '';
        if (!preg_match('/^\d{5,30}$/', $id)) continue;
        $tipo = (string)($it['media_type'] ?? '');
        $fotos = ig_en_cache($id);
        if (!$fotos) {
            $cand = ig_candidatas($it);
            $habia_link = false; $hijos_vistos = false;
            while (!$fotos) {
                foreach ($cand as $u) {
                    $habia_link = true;
                    if (microtime(true) - $t0 > IG_TIEMPO_FOTOS) break 2;
                    $fotos = ig_bajar($u, $id);
                    if ($fotos) break 2;
                }
                // Un álbum que empieza con un video: se busca la primera foto de adentro.
                if ($tipo !== 'CAROUSEL_ALBUM' || $hijos_vistos) break;
                $hijos_vistos = true; $cand = [];
                $h = ig_api($id . '/children', ['fields' => 'id,media_type,media_url,thumbnail_url'], $llave);
                if (!$h['ok']) { $habia_link = true; break; } // no se pudo mirar adentro: se reintenta
                foreach (array_slice((array)($h['datos']['data'] ?? []), 0, 5) as $hijo) {
                    if (is_array($hijo)) $cand = array_merge($cand, ig_candidatas($hijo));
                }
                if (!$cand) break;
            }
            if (!$fotos) {
                // Un post sin ningún link de foto se saltea y no es una falla. Con link y sin foto, sí.
                if ($habia_link) $fallos++;
                continue;
            }
        }
        $ts = (int)strtotime((string)($it['timestamp'] ?? ''));
        $posts[] = [
            'id'       => $id,
            'g'        => $fotos['g'],
            'c'        => $fotos['c'],
            'link'     => ig_link($it['permalink'] ?? ''),
            'etiqueta' => ig_etiqueta($tipo, $ts),
            'fecha'    => $ts > 0 ? date('Y-m-d', $ts) : '',
        ];
    }

    // 4) Si faltó alguna foto y no alcanzan para la grilla, queda la lista anterior y se reintenta.
    if (count($posts) < IG_TILES && $fallos > 0) {
        return ig_error('fotos', 0, 'No se pudieron bajar ' . $fallos . ' de las fotos. Queda la lista anterior y se reintenta en unos minutos.');
    }
    if (!ig_escribir("$IG_DATOS/feed.json", ['actualizado' => $ahora, 'posts' => $posts])) {
        return ig_error('instagram', 0, 'No se pudo guardar la lista de posts en el servidor.');
    }
    $en_uso = [];
    foreach ($posts as $p) { $en_uso[$p['g']] = true; $en_uso[$p['c']] = true; }
    ig_limpiar($en_uso, $ahora);

    // 5) La llave dura 60 días: se renueva sola cada semana. Si la pasada ya vino larga, queda para la próxima.
    if ($ahora - (int)($estado['llave_desde'] ?? $ahora) >= IG_RENOVAR_CADA && $ahora >= (int)($estado['renovar_proximo'] ?? 0) && time() - $ahora < 40) {
        $n = ig_api('refresh_access_token', ['grant_type' => 'ig_refresh_token'], $llave);
        $nueva = $n['ok'] ? (string)($n['datos']['access_token'] ?? '') : '';
        if ($nueva !== '' && preg_match(IG_RE_LLAVE, $nueva) && ig_escribir_archivo("$IG_DATOS/token.txt", $nueva)) {
            $dura = (int)($n['datos']['expires_in'] ?? 0);
            $llave = $nueva;
            $estado['llave_hash'] = hash('sha256', $nueva);
            $estado['llave_desde'] = $ahora;
            $estado['llave_vence'] = $ahora + ($dura > 0 ? $dura : IG_LLAVE_DURA);
            $estado['renovada'] = $ahora;
            $estado['renovar_error'] = null; $estado['renovar_proximo'] = 0;
        } else {
            $e = $n['ok'] ? ig_error('instagram', 0, $nueva === '' ? 'Instagram no devolvió la llave renovada.' : 'No se pudo guardar la llave renovada.') : $n;
            $estado['renovar_error'] = $e['error'];
            $estado['renovar_proximo'] = $ahora + IG_RENOVAR_ESPERA;
        }
    }
    return ['ok' => true];
}

// ------------------------------------------------------------
// Lo que se contesta
// ------------------------------------------------------------
function ig_hace($seg) {
    if ($seg < 90) return 'recién';
    if ($seg < 5400) return 'hace ' . (int)round($seg / 60) . ' minutos';
    if ($seg < 129600) return 'hace ' . (int)round($seg / 3600) . ' horas';
    return 'hace ' . (int)round($seg / 86400) . ' días';
}

function ig_salida($feed, $estado, $llave_estado, $diag, $ahora) {
    global $IG_FOTOS, $IG_DATOS;
    $posts = [];
    foreach ((array)($feed['posts'] ?? []) as $p) {
        if (!is_array($p)) continue;
        $g = (string)($p['g'] ?? ''); $c = (string)($p['c'] ?? '');
        if (!preg_match(IG_RE_FOTO, $g) || !preg_match(IG_RE_FOTO, $c)) continue;
        if (!is_file("$IG_FOTOS/$g") || !is_file("$IG_FOTOS/$c")) continue; // sin la copia de la foto, ese post no se ofrece
        $posts[] = [
            'g'        => 'ig_section/cache/' . $g,
            'c'        => 'ig_section/cache/' . $c,
            'link'     => ig_link($p['link'] ?? ''),
            'etiqueta' => (string)($p['etiqueta'] ?? 'Post de Instagram'),
        ];
    }
    $n = count($posts);
    $s = ['ok' => $n >= IG_TILES, 'posts' => $posts];
    if (!$diag) return $s;

    $act = (int)($feed['actualizado'] ?? 0);
    $err = is_array($estado['error'] ?? null) ? $estado['error'] : null;
    $vence = (int)($estado['llave_vence'] ?? 0);
    $dias = $vence > 0 ? (int)floor(($vence - $ahora) / 86400) : null;
    $guardados = $n >= IG_TILES ? ' Mientras tanto se siguen mostrando los ' . $n . ' posts guardados.' : ' Mientras tanto la landing muestra las fotos fijas.';

    if (!function_exists('curl_init')) {
        $resumen = 'Este servidor no tiene cURL: así no se le puede preguntar nada a Instagram.' . $guardados;
    } elseif ($llave_estado === 'falta') {
        $resumen = 'Falta cargar la llave de Instagram.' . $guardados;
    } elseif ($llave_estado === 'ilegible') {
        $resumen = 'El archivo de la llave está vacío o no trae una llave. Hay que cargarla de nuevo.' . $guardados;
    } elseif (!is_writable($IG_DATOS) || (is_file("$IG_DATOS/estado.json") && !is_writable("$IG_DATOS/estado.json"))) {
        $resumen = 'El servidor no puede escribir en la carpeta bs-data/ig, así que no se le pregunta nada a Instagram.' . $guardados;
    } elseif ($err && $err['tipo'] === 'cuenta') {
        $resumen = $err['mensaje'] . $guardados;
    } elseif ($err && $err['tipo'] === 'llave') {
        $resumen = 'Instagram no acepta la llave (código ' . (int)$err['codigo'] . '). Hay que generar una nueva y cargarla.' . $guardados;
    } elseif ($err && $err['tipo'] === 'fotos') {
        $resumen = 'La última vez no se pudieron bajar algunas fotos. Se reintenta solo en unos minutos.' . $guardados;
    } elseif ($err) {
        $resumen = 'Instagram no respondió bien la última vez. Se reintenta solo en unos minutos.' . $guardados;
    } elseif (!empty($estado['en_curso']) && $ahora - (int)($estado['intento'] ?? 0) > 150) {
        $resumen = 'La última consulta a Instagram se cortó antes de terminar. Se reintenta sola en unos minutos.' . $guardados;
    } elseif (!empty($estado['en_curso'])) {
        $resumen = 'Se le está preguntando a Instagram en este momento. Volvé a mirar en un minuto.';
    } elseif ($act === 0) {
        $resumen = 'La llave está cargada y todavía no se le preguntó nada a Instagram.';
    } elseif ($n < IG_TILES) {
        $resumen = 'Instagram devolvió ' . $n . ' posts con foto y la grilla necesita ' . IG_TILES . '. La landing muestra las fotos fijas.';
    } else {
        $resumen = 'Todo bien: ' . $n . ' posts de @' . IG_CUENTA . ', actualizados ' . ig_hace($ahora - $act) . '.'
            . ($dias !== null ? ' La llave vence en ' . max(0, $dias) . ' días y se renueva sola.' : '');
    }

    $fecha = function ($ts) { return $ts > 0 ? date('Y-m-d H:i', (int)$ts) : null; };
    $s['diagnostico'] = [
        'resumen'               => $resumen,
        'llave'                 => $llave_estado,
        'cuenta'                => isset($estado['cuenta']) ? '@' . $estado['cuenta'] : null,
        'posts'                 => $n,
        'actualizado'           => $fecha($act),
        'ultimo_intento'        => $fecha($estado['intento'] ?? 0),
        'proximo_intento'       => $fecha(max((int)($estado['proximo'] ?? 0), $act > 0 ? $act + IG_TTL : 0)),
        'error'                 => $err ? ['tipo' => $err['tipo'], 'codigo' => (int)$err['codigo'], 'mensaje' => (string)$err['mensaje'], 'cuando' => $fecha($err['cuando'] ?? 0)] : null,
        'llave_cargada'         => $fecha($estado['llave_desde'] ?? 0),
        'llave_vence_en_dias'   => $dias,
        'llave_renovada'        => $fecha($estado['renovada'] ?? 0),
        'renovacion_error'      => is_array($estado['renovar_error'] ?? null) ? (string)$estado['renovar_error']['mensaje'] : null,
        'ruta'                  => isset($estado['ruta']) ? (strpos((string)$estado['ruta'], 'me/') === 0 ? 'me' : 'id') : null,
        'fotos'                 => function_exists('imagecreatefromstring') ? (function_exists('imagewebp') ? 'webp' : 'jpg') : 'como vienen',
        'corte'                 => function_exists('fastcgi_finish_request') ? 'fastcgi' : (function_exists('litespeed_finish_request') ? 'litespeed' : 'ninguno'),
    ];
    return $s;
}

// ------------------------------------------------------------
// El pedido
// ------------------------------------------------------------
$diag  = isset($_GET['estado']);
$ahora = time();
list($llave, $llave_estado) = ig_llave("$IG_DATOS/token.txt");
$estado = ig_leer("$IG_DATOS/estado.json");
$feed   = ig_leer("$IG_DATOS/feed.json");
list($toca, $nueva) = ig_toca($llave, $estado, $feed, $ahora);

// Antes de contestar solo se pregunta a Instagram si no hay nada guardado, si la llave es
// nueva o si piden el estado (que además espera a la consulta que esté en curso). En el resto
// de los casos se contesta primero con lo guardado.
$antes = ($toca && ($nueva || empty($feed['posts']) || $diag)) || ($diag && !empty($estado['en_curso']));
if ($antes) {
    ig_refrescar($ahora, $diag);
    $estado = ig_leer("$IG_DATOS/estado.json");
    $feed   = ig_leer("$IG_DATOS/feed.json");
}

$salida = ig_salida($feed, $estado, $llave_estado, $diag, $ahora);
$json = json_encode($salida, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | ($diag ? JSON_PRETTY_PRINT : 0));
if ($json === false) { $json = '{"ok":false,"posts":[]}'; $salida['ok'] = false; }
while (ob_get_level() >= $ig_bufer && @ob_end_clean()) { }
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex');
// Con posts, el navegador guarda la respuesta cinco minutos. Sin posts no la guarda: apenas se
// carga la llave, la landing lo ve.
header('Cache-Control: ' . (!$diag && $salida['ok'] ? 'public, max-age=300' : 'no-store'));

if ($toca && !$antes) {
    // Se contesta ya y, con el visitante atendido, se le pregunta a Instagram.
    $corte = function_exists('fastcgi_finish_request') ? 'fastcgi_finish_request' : (function_exists('litespeed_finish_request') ? 'litespeed_finish_request' : '');
    if ($corte === '' && !ini_get('zlib.output_compression') && !in_array('ob_gzhandler', ob_list_handlers(), true)) header('Content-Length: ' . strlen($json));
    echo $json;
    if ($corte !== '') {
        $corte();
    } else {
        $n = ob_get_level();
        while ($n-- > 0 && @ob_end_flush()) { }
        @flush();
    }
    ig_refrescar($ahora, false);
    exit;
}
echo $json;
