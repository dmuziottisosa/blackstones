<?php
// ============================================================
// auth_check.php — Lógica de autenticación
// ============================================================

require_once __DIR__ . '/auth_config.php';

// ============================================================
// USUARIOS Y ROLES (17/11/2026)
// ============================================================
// Cada persona entra con su usuario y su contraseña. Los usuarios viven en
// bs-data/usuarios/usuarios.json, fuera de la web y fuera del repo:
//   { version, secreto, usuarios: { <id>: { usuario, nombre, rol, hash, activo, ver, creado, ultimo } } }
//
// Mientras ese archivo no exista el sistema anda como siempre (una contraseña
// compartida, todos administradores). Cuando se crea el primer administrador
// desde /presupuestos/usuarios.php, la contraseña compartida deja de servir y
// la cookie pasa a firmarse con un secreto propio del servidor ('secreto'),
// que no está en el repo.
//
// QUÉ PUEDE CADA ROL está en AUTH_PERMISOS y en ningún otro lado. Las páginas
// esconden lo que el rol no usa, pero el bloqueo de verdad lo hace el servidor:
// cada endpoint llama a bs_require_permiso() (api/_auth.php).

const AUTH_ROLES = [
    'admin'    => 'Administrador',
    'operador' => 'Operador',
    'planos'   => 'Planos y bacha',
];

const AUTH_PERMISOS = [
    'calculadora'       => ['admin', 'operador'],
    'presupuestos'      => ['admin', 'operador'],
    'tablero'           => ['admin', 'operador', 'planos'], // ver el tablero y abrir fichas
    'tablero_operar'    => ['admin', 'operador'],           // cargar, mover, tildar, editar, archivar, borrar, papelera
    'tablero_aprobar'   => ['admin', 'operador'],           // tildar las aprobaciones: Medición, Corte, Fabricación e Instalación aprobada
    'tablero_adjuntar'  => ['admin', 'operador', 'planos'], // subir foto de bacha, plano y entrega
    'tablero_bacha'     => ['admin', 'operador', 'planos'], // anotar o corregir de quién es la bacha y su modelo (dueño, 17/11/2026: también planos)
    'tablero_adj_ajeno' => ['admin', 'operador', 'planos'], // borrar o girar un adjunto que subió otro (dueño, 17/11/2026: también planos)
    'tablero_plata'     => ['admin', 'operador'],           // ver la seña de la ficha
    'calendario'        => ['admin', 'operador'],
    'stock'             => ['admin', 'operador'],
    'contratos'         => ['admin', 'operador'],           // ver Relación-Contrato
    'contratos_editar'  => ['admin'],                       // crear, editar, borrar, pagos, adjuntos
    'contratos_plata'   => ['admin', 'operador'],           // ver el total, la seña, los pagos y el saldo de cada contrato
    'contratos_resumen' => ['admin'],                       // ver el resumen de plata del mes (contratado, cobrado, por cobrar) y las sumas del pie
    'presencia'         => ['admin', 'operador', 'planos'], // ver quién está trabajando ahora y en qué pantalla
    'reportes'          => ['admin'],                       // pestaña Reportes: el cierre del mes (baja todo)
    'usuarios'          => ['admin'],
    'mantenimiento'     => ['admin'],                       // importadores y scripts de una sola vez
];

// Un permiso para CAMBIAR no sirve sin el de VER la plata de eso mismo: quien no la recibe
// guardaría de vuelta los montos tapados o en cero. Si a un rol se le saca el de la derecha,
// pierde también el de la izquierda, aunque figure en AUTH_PERMISOS.
const AUTH_REQUIERE = [
    'tablero_operar'   => 'tablero_plata',
    'contratos_editar' => 'contratos_plata',
];

function auth_usuarios_archivo(): string {
    return __DIR__ . '/../../../../bs-data/usuarios/usuarios.json';
}

/**
 * La base de usuarios. null = todavía no existe (modo de contraseña compartida).
 * Si el archivo existe pero no se puede leer, devuelve ['roto' => true] y nadie entra:
 * un archivo dañado nunca reabre la contraseña compartida.
 */
function auth_db(bool $recargar = false): ?array {
    static $db = false;
    if ($db !== false && !$recargar) return $db;
    $f = auth_usuarios_archivo();
    if (!file_exists($f) && !file_exists($f . '.bak')) return $db = null;
    foreach ([$f, $f . '.bak'] as $g) {
        $j = json_decode((string)@file_get_contents($g), true);
        if (is_array($j) && !empty($j['secreto']) && is_array($j['usuarios'] ?? null) && $j['usuarios']) return $db = $j;
    }
    return $db = ['roto' => true, 'usuarios' => [], 'secreto' => ''];
}

/** Guarda la base entera. Devuelve false si no pudo. */
function auth_db_guardar(array $db): bool {
    $f = auth_usuarios_archivo();
    $dir = dirname($f);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return false;
    $db['version'] = 1;
    $txt = json_encode($db, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($txt === false) return false;
    $tmp = $f . '.tmp' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $txt, LOCK_EX) === false) return false;
    // La copia de respaldo solo se pisa con un archivo sano: si el principal está dañado, la copia buena se conserva.
    $actual = file_exists($f) ? json_decode((string)@file_get_contents($f), true) : null;
    if (is_array($actual) && !empty($actual['secreto']) && !empty($actual['usuarios'])) @copy($f, $f . '.bak');
    if (!@rename($tmp, $f)) { @unlink($tmp); return false; }
    auth_db(true);
    return true;
}

/** Candado para que dos cambios de usuarios no se pisen. Devuelve el handle o null. */
function auth_db_candado() {
    $dir = dirname(auth_usuarios_archivo());
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $fp = @fopen($dir . '/.lock', 'c');
    if (!$fp) return null;
    if (!flock($fp, LOCK_EX)) { fclose($fp); return null; }
    return $fp;
}

/**
 * Cookie del modo viejo (contraseña compartida). Formato: vencimiento.firma
 */
function auth_make_cookie_value(int $expiresAt): string {
    $payload = (string)$expiresAt;
    $signature = hash_hmac('sha256', $payload, AUTH_SECRET);
    return $payload . '.' . $signature;
}

/**
 * Cookie de un usuario. Formato: u.<id>.<vencimiento>.<ver>.<firma>
 * 'ver' sube cuando se le cambia la contraseña: las sesiones viejas dejan de servir.
 */
function auth_cookie_usuario(string $id, int $ver, int $expiresAt): string {
    $db = auth_db();
    $payload = 'u.' . $id . '.' . $expiresAt . '.' . $ver;
    return $payload . '.' . hash_hmac('sha256', $payload, (string)($db['secreto'] ?? ''));
}

/**
 * Quién está entrando: ['id', 'usuario', 'nombre', 'rol', 'viejo'] o null.
 * En el modo viejo no hay personas: es "Equipo", administrador.
 */
function auth_usuario(): ?array {
    static $hecho = false, $u = null;
    if ($hecho) return $u;
    $hecho = true;
    $valor = (string)($_COOKIE[AUTH_COOKIE_NAME] ?? '');
    if ($valor === '') return null;
    $db = auth_db();

    if ($db === null) {
        $parts = explode('.', $valor, 2);
        if (count($parts) !== 2) return null;
        [$expiresAt, $signature] = $parts;
        if (!hash_equals(hash_hmac('sha256', $expiresAt, AUTH_SECRET), $signature)) return null;
        if ((int)$expiresAt < time()) return null;
        return $u = ['id' => '', 'usuario' => '', 'nombre' => 'Equipo', 'rol' => 'admin', 'viejo' => true];
    }

    if (!empty($db['roto'])) return null;
    $p = explode('.', $valor);
    if (count($p) !== 5 || $p[0] !== 'u') return null;
    [, $id, $expiresAt, $ver, $firma] = $p;
    $payload = 'u.' . $id . '.' . $expiresAt . '.' . $ver;
    if (!hash_equals(hash_hmac('sha256', $payload, (string)$db['secreto']), $firma)) return null;
    if ((int)$expiresAt < time()) return null;
    $reg = $db['usuarios'][$id] ?? null;
    if (!is_array($reg) || empty($reg['activo'])) return null;
    if ((int)($reg['ver'] ?? 1) !== (int)$ver) return null;
    if (!isset(AUTH_ROLES[$reg['rol'] ?? ''])) return null;
    return $u = ['id' => $id, 'usuario' => (string)$reg['usuario'], 'nombre' => (string)$reg['nombre'], 'rol' => (string)$reg['rol'], 'viejo' => false];
}

function auth_is_valid(): bool {
    return auth_usuario() !== null;
}

/** ¿El que entró puede hacer esto? Un permiso que no existe no lo puede nadie. */
function auth_puede(string $permiso): bool {
    $u = auth_usuario();
    if (!$u) return false;
    if (!in_array($u['rol'], AUTH_PERMISOS[$permiso] ?? [], true)) return false;
    $base = AUTH_REQUIERE[$permiso] ?? '';
    return $base === '' || in_array($u['rol'], AUTH_PERMISOS[$base] ?? [], true);
}

/**
 * Tapa los montos escritos en un texto libre (notas), para quien no puede ver la plata.
 * "Saldo $ 285.720" → "Saldo $ ···". Reconoce el signo antes o después del número ($, U$S, US$,
 * USD, ARS, "pesos", "dólares"), con o sin dos puntos, miles separados por punto o por espacio,
 * y cantidades en "mil", "millones" o "lucas".
 * Es lo mejor que se puede hacer con texto libre: un número suelto ("quedan 200") no se reconoce.
 */
function auth_tapar_plata(string $txt): string {
    $num = '\d(?:[\d.,]*\d)?(?:[ \x{00A0}]\d{3}\b)*'; // no se lleva el punto o la coma del final de la frase
    $mon = '(?:U\$S|US\$|U\$D|USD|ARS|AR\$|\$)';
    $x = '$ ···';
    // signo adelante: "$ 285.720", "USD: 1200", "ARS 90.000", "$ 1 500 000", "$2M"
    $txt = (string)preg_replace('/' . $mon . '\s*:?\s*' . $num . '(?:\s*(?:mil(?:lones)?|lucas|k|m)\b)?/iu', $x, $txt);
    // signo atrás: "200.000$", "500 U$S", "1.500 dólares" (no si lo que sigue ya es otro monto)
    $txt = (string)preg_replace('/' . $num . '\s*(?:mil(?:lones)?\s*)?(?:' . $mon . '(?!\s*[:\d·])|(?:pesos|d[oó]lares)\b)/iu', $x, $txt);
    // sin signo pero dicho como plata: "350mil", "2 millones", "200 lucas"
    return (string)preg_replace('/' . $num . '\s*(?:mil(?:lones)?|lucas)\b/iu', $x, $txt);
}

/** Todo lo que puede el que entró, para que la página esconda lo que no usa. */
function auth_permisos_js(): string {
    $u = auth_usuario();
    $p = [];
    foreach (AUTH_PERMISOS as $k => $roles) $p[$k] = auth_puede($k);
    return json_encode([
        'usuario' => $u ? $u['usuario'] : '', 'nombre' => $u ? $u['nombre'] : '', 'rol' => $u ? $u['rol'] : '',
        'rol_txt' => $u ? AUTH_ROLES[$u['rol']] : '', 'viejo' => $u ? !empty($u['viejo']) : false, 'puede' => $p,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
}

/**
 * El menú de arriba, igual en todas las páginas: solo lo que el rol puede abrir.
 * $activa: 'calculadora' | 'presupuestos' | 'tablero' | 'calendario' | 'contratos' | 'stock' | 'reportes' | 'usuarios'
 */
function auth_nav_html(string $activa): string {
    $items = [
        ['calculadora',  '/calculadora/',                        'Calculadora'],
        ['presupuestos', '/presupuestos/',                       'Presupuestos'],
        ['tablero',      '/presupuestos/tablero.php',            'Tablero'],
        ['calendario',   '/presupuestos/calendario.php',         'Calendario'],
        ['contratos',    '/presupuestos/relacion-contrato.php',  'Relación-Contrato'],
        ['stock',        '/presupuestos/stock.php',              'Stock'],
        ['reportes',     '/presupuestos/reportes.php',           'Reportes'],
    ];
    $h = "<nav class=\"h-tabs\">\n";
    foreach ($items as [$permiso, $url, $txt]) {
        if (!auth_puede($permiso)) continue;
        // Reportes se puede borrar entero sin que nada se rompa: si la página no está, no hay pestaña.
        if ($permiso === 'reportes' && !is_file(__DIR__ . '/../presupuestos/reportes.php')) continue;
        $h .= '  <a href="' . $url . '"' . ($activa === $permiso ? ' class="active"' : '') . '>' . $txt . "</a>\n";
    }
    $h .= '  <a href="/presupuestos/usuarios.php"' . ($activa === 'usuarios' ? ' class="active"' : '') . '>' . (auth_puede('usuarios') ? 'Usuarios' : 'Mi usuario') . "</a>\n";
    return $h . '</nav>' . auth_presencia_js($activa);
}

/**
 * "Quién está trabajando": cada pantalla le avisa al servidor, cada 20 segundos mientras está
 * a la vista, que esa persona la tiene abierta (api/presencia.php), y el servidor le contesta
 * quiénes están en línea y en qué pantalla. Arriba de cada página se ven sus iniciales (con
 * borde verde los que están en esta misma pantalla); al tocarlas se abre la lista con nombre
 * y pantalla. Lo ve todo el que tenga el permiso 'presencia'.
 * Viaja pegado al menú, así lo tienen todas las páginas sin tocarlas una por una.
 * Los nombres se escriben siempre como texto, nunca como HTML. Si algo falla, no se nota.
 */
function auth_presencia_js(string $pagina): string {
    $u = auth_usuario();
    if (!$u || !empty($u['viejo'])) return ''; // con la contraseña compartida no hay a quién anotar
    $js = <<<'JS'
(function(){
var P=__PAG__,YO=__YO__,VE=__VE__,ADM=__ADM__,U='/calculadora/api/presencia.php',L=[],pop=null,btn=null;
function el(t,css,txt){var e=document.createElement(t);if(css)e.style.cssText=css;if(txt!=null)e.textContent=txt;return e;}
function ini(n){var p=String(n||'').trim().split(/\s+/);return ((p[0]||'?').charAt(0)+(p.length>1?p[p.length-1].charAt(0):'')).toUpperCase();}
function aca(x){return x.paginas.indexOf(P)>=0;}
function pintar(){
  try{
    if(!VE)return;var h=document.querySelector('.h-right');if(!h)return;
    if(!btn){btn=el('button','display:inline-flex;align-items:center;gap:0;background:none;border:none;padding:4px 2px;margin:0;cursor:pointer;-webkit-tap-highlight-color:transparent');
      btn.type='button';btn.id='bsLinea';btn.setAttribute('aria-haspopup','true');btn.addEventListener('click',function(e){e.stopPropagation();abrir(!pop);});h.insertBefore(btn,h.firstChild);}
    var l=L.slice().sort(function(a,b){return (aca(b)-aca(a))||(a.nombre<b.nombre?-1:1);}),chico=window.innerWidth<700,max=chico?0:4;
    btn.textContent='';
    // En el celular no hay lugar para las iniciales: un solo círculo con cuántos son. Al tocarlo, la lista.
    if(chico){h.style.gap='8px';btn.style.padding='0';} // en el celular cada píxel cuenta: que no tape el nombre
    if(chico&&l.length)btn.appendChild(el('span','min-width:26px;height:26px;padding:0 5px;box-sizing:border-box;border-radius:13px;display:inline-flex;align-items:center;justify-content:center;font:700 12px/1 Manrope,sans-serif;letter-spacing:0;text-transform:none;color:#0E2A18;background:#34D670',String(l.length)));
    l.slice(0,max).forEach(function(x,k){
      btn.appendChild(el('span','width:28px;height:28px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font:700 10.5px/1 Manrope,sans-serif;letter-spacing:.02em;text-transform:none;color:'+(aca(x)?'#0E2A18':'#C4A77D')+';background:'+(aca(x)?'#34D670':'#2A2522')+';border:2px solid #1A1816;margin-left:'+(k?'-7px':'0'),ini(x.nombre)));
    });
    if(!chico&&l.length>max)btn.appendChild(el('span','font:700 11px/1 Manrope,sans-serif;color:#C4A77D;margin-left:5px;letter-spacing:0;text-transform:none','+'+(l.length-max)));
    btn.title='Trabajando ahora: '+l.map(function(x){return x.nombre+' ('+x.donde+')';}).join(', ');
    btn.setAttribute('aria-label',btn.title);btn.style.display=l.length?'inline-flex':'none';
    if(chico)entrar();
    if(pop)lista();
  }catch(e){}
}
// En pantallas angostas el círculo no puede pisar el nombre: si no entra, se achica el título de a poco.
function entrar(){
  var hd=document.querySelector('.header'),t=hd&&hd.querySelector('h1'),im=hd&&hd.querySelector('img');if(!t||!btn)return;
  var pasos=[function(){t.style.fontSize='1.15rem';if(im)im.style.setProperty('height','30px','important');},
    function(){t.style.fontSize='1.02rem';},
    function(){if(im){im.style.setProperty('height','26px','important');if(im.parentNode)im.parentNode.style.gap='8px';}},
    function(){t.style.fontSize='.92rem';}];
  for(var i=0;i<pasos.length&&btn.getBoundingClientRect().left<t.getBoundingClientRect().right+4;i++)pasos[i]();
}
function lista(){
  pop.textContent='';
  pop.appendChild(el('div','font:700 10.5px/1 Manrope,sans-serif;letter-spacing:.1em;text-transform:uppercase;color:#C4A77D;margin-bottom:10px','Trabajando ahora · '+L.length));
  L.slice().sort(function(a,b){return (aca(b)-aca(a))||(a.nombre<b.nombre?-1:1);}).forEach(function(x){
    var f=el('div','display:flex;align-items:center;gap:9px;padding:6px 0');
    f.appendChild(el('span','flex:none;width:8px;height:8px;border-radius:50%;background:'+(aca(x)?'#34D670':'#8B7860')));
    var t=el('div','min-width:0');
    t.appendChild(el('div','font:700 13.5px/1.25 Manrope,sans-serif;color:#FAF8F4;overflow:hidden;text-overflow:ellipsis;white-space:nowrap',x.nombre+(x.id===YO?' (vos)':'')));
    t.appendChild(el('div','font:500 12px/1.3 Manrope,sans-serif;color:#C4B596',x.donde));
    f.appendChild(t);pop.appendChild(f);
  });
  if(ADM){var a=el('a','display:block;margin-top:8px;padding-top:9px;border-top:1px solid rgba(196,167,125,.25);font:600 12.5px/1 Manrope,sans-serif;color:#C4A77D;text-decoration:none','Ver usuarios');a.href='/presupuestos/usuarios.php';pop.appendChild(a);}
}
function abrir(si){
  if(!si){if(pop){pop.remove();pop=null;}if(btn)btn.setAttribute('aria-expanded','false');return;}
  var r=btn.getBoundingClientRect();
  pop=el('div','position:fixed;z-index:9999;top:'+Math.round(r.bottom+8)+'px;right:'+Math.max(8,Math.round(window.innerWidth-r.right-40))+'px;width:250px;max-width:calc(100vw - 16px);max-height:60vh;overflow:auto;background:#1A1816;border:1px solid rgba(196,167,125,.45);border-radius:10px;box-shadow:0 12px 32px rgba(0,0,0,.45);padding:14px 15px 12px;text-align:left');
  pop.id='bsLineaPop';pop.setAttribute('role','dialog');pop.addEventListener('click',function(e){e.stopPropagation();});
  document.body.appendChild(pop);btn.setAttribute('aria-expanded','true');lista();senal(false);
}
document.addEventListener('click',function(){if(pop)abrir(false);});
document.addEventListener('keydown',function(e){if(e.key==='Escape'&&pop)abrir(false);});
function senal(f){
  try{
    var b=JSON.stringify({pagina:P,fuera:!!f});
    if(f){if(navigator.sendBeacon)navigator.sendBeacon(U,b);return;}
    fetch(U,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:b,keepalive:true})
      .then(function(r){return r.ok?r.json():null;}).then(function(j){if(j&&j.linea){L=j.linea;pintar();}}).catch(function(){});
  }catch(e){}
}
function vivo(){if(document.visibilityState==='visible')senal(false);}
vivo();setInterval(vivo,20000);document.addEventListener('visibilitychange',vivo);
// La letra del título carga después y lo ensancha; y el celular se puede girar: se vuelve a acomodar.
if(document.fonts&&document.fonts.ready)document.fonts.ready.then(function(){if(btn)pintar();});
window.addEventListener('resize',function(){if(btn)pintar();});
window.addEventListener('pagehide',function(){senal(true);});
})();
JS;
    $f = JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;
    $js = str_replace(['__PAG__', '__YO__', '__VE__', '__ADM__'],
        [json_encode($pagina, $f), json_encode((string)$u['id'], $f), auth_puede('presencia') ? 'true' : 'false', auth_puede('usuarios') ? 'true' : 'false'], $js);
    return "\n<script>" . $js . "</script>";
}

/** Quién está adentro, para el encabezado (solo se ve en pantallas anchas). */
function auth_quien_html(): string {
    $u = auth_usuario();
    if (!$u || !empty($u['viejo'])) return '<span>blackstones.com.ar</span>';
    return '<span>' . htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8') . ' · ' . AUTH_ROLES[$u['rol']] . '</span>';
}

/** La primera pantalla de cada rol. */
function auth_inicio(): string {
    if (auth_puede('calculadora')) return '/calculadora/';
    if (auth_puede('tablero')) return '/presupuestos/tablero.php';
    return '/calculadora/logout.php';
}

/** Para páginas: sin sesión va al login; con sesión pero sin permiso, a su primera pantalla. */
function auth_exigir_pagina(string $permiso): void {
    if (!auth_is_valid()) { header('Location: /calculadora/login.php'); exit; }
    if (!auth_puede($permiso)) { header('Location: ' . auth_inicio()); exit; }
}

/**
 * Setea la cookie de autenticación. Sin usuario, la del modo viejo.
 */
function auth_login(?string $id = null): void {
    $expiresAt = time() + AUTH_COOKIE_LIFETIME;
    if ($id === null) {
        $value = auth_make_cookie_value($expiresAt);
    } else {
        $db = auth_db();
        $value = auth_cookie_usuario($id, (int)($db['usuarios'][$id]['ver'] ?? 1), $expiresAt);
    }

    setcookie(AUTH_COOKIE_NAME, $value, [
        'expires'  => $expiresAt,
        'path'     => '/',
        'domain'   => '',           // dominio actual
        'secure'   => true,         // solo HTTPS
        'httponly' => true,         // no accesible desde JS
        'samesite' => 'Lax',        // protección CSRF razonable
    ]);
}

/**
 * Borra la cookie (logout).
 * Borra cookies de path=/ (nueva) y path=/calculadora/ (legacy) por compat.
 */
function auth_logout(): void {
    foreach (['/', '/calculadora/'] as $p) {
        setcookie(AUTH_COOKIE_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => $p,
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

/**
 * Contraseña compartida. Solo sirve mientras no haya usuarios.
 */
function auth_check_password(string $password): bool {
    if (auth_db() !== null) return false;
    return password_verify($password, AUTH_PASSWORD_HASH);
}

function auth_usuario_llano(string $s): string {
    return strtolower(trim($s));
}

/** Busca un usuario por su nombre de entrada. Devuelve su id o ''. */
function auth_buscar(string $usuario, ?array $db = null): string {
    $db = $db ?? auth_db();
    $q = auth_usuario_llano($usuario);
    if ($q === '' || !$db) return '';
    foreach ($db['usuarios'] as $id => $r) {
        if (auth_usuario_llano((string)($r['usuario'] ?? '')) === $q) return (string)$id;
    }
    return '';
}

/**
 * Usuario y contraseña. Devuelve el id si entran, '' si no.
 * Tarda lo mismo exista o no el usuario.
 */
function auth_entrar(string $usuario, string $password): string {
    $db = auth_db();
    if (!$db || !empty($db['roto'])) return '';
    $id = auth_buscar($usuario, $db);
    $hash = $id !== '' ? (string)($db['usuarios'][$id]['hash'] ?? '') : AUTH_PASSWORD_HASH;
    $ok = password_verify($password, $hash);
    if ($id === '' || !$ok || empty($db['usuarios'][$id]['activo'])) return '';
    return $id;
}

/** Reglas de una contraseña nueva. Devuelve el error o ''. */
function auth_clave_error(string $clave): string {
    if (strlen($clave) < 8) return 'La contraseña tiene que tener al menos 8 caracteres.';
    if (strlen($clave) > 200) return 'La contraseña es demasiado larga.';
    return '';
}

/**
 * Registro de quién hizo qué: una línea por cada cambio, en bs-data/usuarios/registro-AAAA-MM.log
 * (fecha, usuario, archivo, permiso, datos mínimos). Nunca guarda contraseñas ni nombres de clientes.
 */
function auth_registrar(string $permiso, array $datos = []): void {
    $u = auth_usuario();
    $dir = dirname(auth_usuarios_archivo());
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return;
    $quien = $u ? ($u['usuario'] !== '' ? $u['usuario'] : 'equipo') : '-';
    $partes = [];
    foreach ($datos as $k => $v) {
        if (is_scalar($v) && $v !== '') $partes[] = $k . '=' . preg_replace('/[^\w\-\.:]/u', '_', substr((string)$v, 0, 60));
    }
    // La hora, siempre la de Argentina (18/11/2026): esto corre antes de que cada archivo fije su zona
    // horaria, y con la del servidor las acciones quedaban anotadas con horas de diferencia.
    $ahora = new DateTime('now', new DateTimeZone('America/Argentina/Buenos_Aires'));
    $linea = $ahora->format('Y-m-d H:i:s') . "\t" . $quien . "\t" . basename((string)($_SERVER['SCRIPT_NAME'] ?? '')) . "\t" . $permiso . "\t" . implode(' ', $partes) . "\n";
    @file_put_contents($dir . '/registro-' . $ahora->format('Y-m') . '.log', $linea, FILE_APPEND | LOCK_EX);
}

// ============================================================
// RATE LIMITING — controla intentos fallidos por IP
// ============================================================

function auth_rate_file(): string {
    return __DIR__ . '/.auth_attempts.json';
}

function auth_get_attempts(): array {
    $file = auth_rate_file();
    if (!file_exists($file)) return [];
    $raw = @file_get_contents($file);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function auth_save_attempts(array $data): void {
    @file_put_contents(auth_rate_file(), json_encode($data));
}

function auth_get_ip(): string {
    // Solo REMOTE_ADDR — X-Forwarded-For es spoofeable por el cliente.
    // En Hostinger shared hosting el cliente llega directo sin proxy nuestro,
    // así que confiar en headers HTTP de la request rompe el rate limiting.
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * A quién se le cuentan los intentos fallidos. Con usuarios, a ese usuario desde esa conexión:
 * en el taller todos salen por la misma IP, y que uno se equivoque no puede dejar afuera al resto.
 * Sin usuario (contraseña compartida), a la conexión entera, como siempre.
 */
function auth_rate_key(string $usuario = ''): string {
    $u = substr((string)preg_replace('/[^a-z0-9._-]/', '', auth_usuario_llano($usuario)), 0, 30);
    return auth_get_ip() . ($u !== '' ? '|' . $u : '');
}

/** Tope para la conexión entera cuando hay usuarios: frena a quien prueba muchos usuarios distintos. */
function auth_rate_key_ip(): string {
    return auth_get_ip() . '|*';
}

function auth_rate_restante(array $data, string $key, int $max): int {
    if (!isset($data[$key])) return 0;
    $entry = $data[$key];
    if (($entry['count'] ?? 0) < $max) return 0;
    $remaining = ($entry['last'] ?? 0) + AUTH_LOCKOUT_TIME - time();
    return $remaining > 0 ? $remaining : 0;
}

/** Segundos que le quedan al bloqueo de la conexión entera (con usuarios), o 0. */
function auth_lockout_conexion(): int {
    return auth_rate_restante(auth_get_attempts(), auth_rate_key_ip(), AUTH_MAX_ATTEMPTS * 6);
}

/**
 * Devuelve segundos restantes de bloqueo, o 0 si no está bloqueado.
 */
function auth_check_lockout(string $usuario = ''): int {
    $data = auth_get_attempts();
    $r = auth_rate_restante($data, auth_rate_key($usuario), AUTH_MAX_ATTEMPTS);
    return max($r, auth_rate_restante($data, auth_rate_key_ip(), AUTH_MAX_ATTEMPTS * 6));
}

function auth_record_failure(string $usuario = ''): void {
    $data = auth_get_attempts();
    $keys = [auth_rate_key($usuario)];
    if ($usuario !== '') $keys[] = auth_rate_key_ip();
    foreach ($keys as $key) {
        $entry = $data[$key] ?? ['count' => 0, 'last' => 0];
        // Reset si ya pasó la ventana de bloqueo
        if (time() - $entry['last'] > AUTH_LOCKOUT_TIME) $entry = ['count' => 0, 'last' => 0];
        $entry['count']++;
        $entry['last'] = time();
        $data[$key] = $entry;
    }

    // Limpieza: borrar entradas viejas (>1 día) para que el archivo no crezca
    foreach ($data as $k => $v) {
        if (time() - ($v['last'] ?? 0) > 86400) unset($data[$k]);
    }

    auth_save_attempts($data);
}

/** Entró bien: se le borran sus intentos (los de la conexión entera siguen su curso). */
function auth_reset_attempts(string $usuario = ''): void {
    $key = auth_rate_key($usuario);
    $data = auth_get_attempts();
    if (isset($data[$key])) {
        unset($data[$key]);
        auth_save_attempts($data);
    }
}
