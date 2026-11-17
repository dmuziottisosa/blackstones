<?php
// ============================================================
// usuarios.php — Usuarios del sistema interno
// ============================================================
// GET  /api/usuarios.php
//   → { modo: 'viejo' }                       todavía no hay usuarios (contraseña compartida)
//   → { modo: 'usuarios', yo, roles, usuarios }   'usuarios' solo para administradores
//
// POST /api/usuarios.php  (JSON)
//   primer_admin  { usuario, nombre, clave }          solo mientras no haya usuarios
//   crear         { usuario, nombre, rol, clave }     administradores
//   editar        { id, [nombre], [rol], [activo] }   administradores (solo cambia lo que se manda)
//   clave         { id, clave }                       administradores (le cambia la contraseña a otro)
//   borrar        { id }                              administradores
//   mi_clave      { actual, nueva }                   cualquiera, la propia
//
// Los usuarios viven en bs-data/usuarios/usuarios.json (fuera de la web y del repo).
// Las contraseñas se guardan con password_hash; nunca salen del servidor ni se anotan.
// Reglas que no se pueden saltar: siempre queda al menos un administrador activo, y
// nadie se saca a sí mismo el rol ni se da de baja a sí mismo.
// ============================================================

require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_auth.php';

if (!auth_is_valid()) {
    http_response_code(401);
    bs_json_response(['ok' => false, 'error' => 'No autenticado'], 401);
}
bs_check_csrf();
date_default_timezone_set('America/Argentina/Buenos_Aires');

$yo = auth_usuario();
$db = auth_db();

function bs_usuario_salida($id, $r) {
    return [
        'id' => (string)$id, 'usuario' => (string)$r['usuario'], 'nombre' => (string)$r['nombre'], 'rol' => (string)$r['rol'],
        'activo' => !empty($r['activo']), 'creado' => substr((string)($r['creado'] ?? ''), 0, 10), 'ultimo' => substr((string)($r['ultimo'] ?? ''), 0, 10),
    ];
}

function bs_usuario_nombre_ok($usuario) {
    $u = auth_usuario_llano((string)$usuario);
    if (!preg_match('/^[a-z0-9][a-z0-9._-]{2,29}$/', $u)) bs_error('El usuario va en minúsculas, sin espacios, de 3 a 30 letras o números (por ejemplo: carolina o c.demo).');
    return $u;
}

function bs_usuario_texto($s, $max) {
    $s = trim(preg_replace('/\s+/u', ' ', (string)$s));
    return function_exists('mb_substr') ? mb_substr($s, 0, $max) : substr($s, 0, $max);
}

function bs_usuario_admins_activos($db, $sin = '') {
    $n = 0;
    foreach ($db['usuarios'] as $id => $r) {
        if ((string)$id !== (string)$sin && ($r['rol'] ?? '') === 'admin' && !empty($r['activo'])) $n++;
    }
    return $n;
}

// ============================================================
// GET
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $roles = [];
    foreach (AUTH_ROLES as $k => $t) $roles[] = ['id' => $k, 'nombre' => $t];
    if ($db === null) bs_ok(['modo' => 'viejo', 'roles' => $roles]);
    $out = ['modo' => 'usuarios', 'roles' => $roles, 'yo' => ['id' => $yo['id'], 'usuario' => $yo['usuario'], 'nombre' => $yo['nombre'], 'rol' => $yo['rol']]];
    if (auth_puede('usuarios')) {
        $lista = [];
        foreach ($db['usuarios'] as $id => $r) $lista[] = bs_usuario_salida($id, $r);
        usort($lista, function ($a, $b) { return strcmp($a['nombre'], $b['nombre']); });
        $out['usuarios'] = $lista;
    }
    bs_ok($out);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') bs_error('Solo GET o POST', 405);

$body = json_decode(@file_get_contents('php://input'), true);
if (!is_array($body)) bs_error('Body JSON inválido');
$accion = (string)($body['accion'] ?? '');
if (!in_array($accion, ['primer_admin', 'crear', 'editar', 'clave', 'borrar', 'mi_clave'], true)) bs_error('accion inválida');

$fp = auth_db_candado();
if (!$fp) bs_error('No se pudo adquirir lock', 500);
$db = auth_db(true);

function bs_usuarios_salir($fp, $msg, $code = 400) {
    flock($fp, LOCK_UN); fclose($fp);
    bs_error($msg, $code);
}

// ---- El primer administrador: cierra la contraseña compartida ----
if ($accion === 'primer_admin') {
    if ($db !== null) bs_usuarios_salir($fp, 'Ya hay usuarios creados. Entrá con el tuyo.', 409);
    $usuario = bs_usuario_nombre_ok($body['usuario'] ?? '');
    $nombre = bs_usuario_texto($body['nombre'] ?? '', 40);
    $clave = (string)($body['clave'] ?? '');
    if ($nombre === '') bs_usuarios_salir($fp, 'Falta el nombre.');
    if (($e = auth_clave_error($clave)) !== '') bs_usuarios_salir($fp, $e);
    $id = bin2hex(random_bytes(6));
    $nueva = ['version' => 1, 'secreto' => bin2hex(random_bytes(32)), 'usuarios' => [$id => [
        'usuario' => $usuario, 'nombre' => $nombre, 'rol' => 'admin', 'hash' => password_hash($clave, PASSWORD_DEFAULT),
        'activo' => true, 'ver' => 1, 'creado' => date('c'), 'ultimo' => date('c'),
    ]]];
    if (!auth_db_guardar($nueva)) bs_usuarios_salir($fp, 'No se pudo guardar el usuario', 500);
    flock($fp, LOCK_UN); fclose($fp);
    auth_login($id); // la sesión de la contraseña compartida ya no sirve: entra con la nueva
    auth_registrar('usuarios', ['accion' => 'primer_admin', 'usuario' => $usuario]);
    bs_ok(['id' => $id]);
}

if ($db === null || !empty($db['roto'])) bs_usuarios_salir($fp, 'Todavía no hay usuarios creados.', 409);

// ---- La contraseña propia ----
if ($accion === 'mi_clave') {
    $id = $yo['id'];
    if (!isset($db['usuarios'][$id])) bs_usuarios_salir($fp, 'Tu usuario ya no existe.', 404);
    if (!password_verify((string)($body['actual'] ?? ''), (string)$db['usuarios'][$id]['hash'])) bs_usuarios_salir($fp, 'La contraseña actual no es esa.', 403);
    $nueva = (string)($body['nueva'] ?? '');
    if (($e = auth_clave_error($nueva)) !== '') bs_usuarios_salir($fp, $e);
    $db['usuarios'][$id]['hash'] = password_hash($nueva, PASSWORD_DEFAULT);
    $db['usuarios'][$id]['ver'] = intval($db['usuarios'][$id]['ver'] ?? 1) + 1; // cierra las otras sesiones
    if (!auth_db_guardar($db)) bs_usuarios_salir($fp, 'No se pudo guardar', 500);
    flock($fp, LOCK_UN); fclose($fp);
    auth_login($id); // esta sesión sigue abierta
    auth_registrar('mi_clave', ['accion' => 'mi_clave']);
    bs_ok([]);
}

// ---- Lo demás: solo administradores ----
if (!auth_puede('usuarios')) bs_usuarios_salir($fp, 'Tu usuario no tiene permiso para esto.', 403);

if ($accion === 'crear') {
    $usuario = bs_usuario_nombre_ok($body['usuario'] ?? '');
    $nombre = bs_usuario_texto($body['nombre'] ?? '', 40);
    $rol = (string)($body['rol'] ?? '');
    $clave = (string)($body['clave'] ?? '');
    if ($nombre === '') bs_usuarios_salir($fp, 'Falta el nombre.');
    if (!isset(AUTH_ROLES[$rol])) bs_usuarios_salir($fp, 'Elegí un rol.');
    if (auth_buscar($usuario, $db) !== '') bs_usuarios_salir($fp, 'Ya hay un usuario "' . $usuario . '".', 409);
    if (($e = auth_clave_error($clave)) !== '') bs_usuarios_salir($fp, $e);
    if (count($db['usuarios']) >= 50) bs_usuarios_salir($fp, 'Hay demasiados usuarios. Borrá alguno que no se use.');
    $id = bin2hex(random_bytes(6));
    $db['usuarios'][$id] = ['usuario' => $usuario, 'nombre' => $nombre, 'rol' => $rol, 'hash' => password_hash($clave, PASSWORD_DEFAULT),
        'activo' => true, 'ver' => 1, 'creado' => date('c'), 'ultimo' => ''];
    $reg = ['accion' => 'crear', 'usuario' => $usuario, 'rol' => $rol];

} else {
    $id = (string)($body['id'] ?? '');
    if (!isset($db['usuarios'][$id])) bs_usuarios_salir($fp, 'Ese usuario ya no existe. Actualizá la página.', 404);
    $r = $db['usuarios'][$id];
    $soy_yo = $id === $yo['id'];
    $reg = ['accion' => $accion, 'usuario' => (string)$r['usuario']];

    if ($accion === 'editar') {
        $nombre = bs_usuario_texto($body['nombre'] ?? $r['nombre'], 40);
        $rol = (string)($body['rol'] ?? $r['rol']);
        $activo = array_key_exists('activo', $body) ? !empty($body['activo']) : !empty($r['activo']);
        if ($nombre === '') bs_usuarios_salir($fp, 'Falta el nombre.');
        if (!isset(AUTH_ROLES[$rol])) bs_usuarios_salir($fp, 'Elegí un rol.');
        if ($soy_yo && ($rol !== 'admin' || !$activo)) bs_usuarios_salir($fp, 'No te podés sacar el rol ni darte de baja a vos mismo. Pedíselo a otro administrador.');
        if (($rol !== 'admin' || !$activo) && bs_usuario_admins_activos($db, $id) === 0) bs_usuarios_salir($fp, 'Tiene que quedar al menos un administrador activo.');
        $db['usuarios'][$id]['nombre'] = $nombre;
        $db['usuarios'][$id]['rol'] = $rol;
        // Dar de baja cierra sus sesiones para siempre: si después se lo reactiva, tiene que volver a entrar.
        if (!$activo && !empty($r['activo'])) $db['usuarios'][$id]['ver'] = intval($r['ver'] ?? 1) + 1;
        $db['usuarios'][$id]['activo'] = $activo;
        $reg['rol'] = $rol; $reg['activo'] = $activo ? 'si' : 'no';

    } elseif ($accion === 'clave') {
        $clave = (string)($body['clave'] ?? '');
        if (($e = auth_clave_error($clave)) !== '') bs_usuarios_salir($fp, $e);
        $db['usuarios'][$id]['hash'] = password_hash($clave, PASSWORD_DEFAULT);
        $db['usuarios'][$id]['ver'] = intval($r['ver'] ?? 1) + 1; // cierra sus sesiones abiertas

    } else { // borrar
        if ($soy_yo) bs_usuarios_salir($fp, 'No te podés borrar a vos mismo.');
        if (bs_usuario_admins_activos($db, $id) === 0) bs_usuarios_salir($fp, 'Tiene que quedar al menos un administrador activo.');
        unset($db['usuarios'][$id]);
    }
}

if (!auth_db_guardar($db)) bs_usuarios_salir($fp, 'No se pudo guardar', 500);
flock($fp, LOCK_UN); fclose($fp);
if ($accion === 'clave' && $id === $yo['id']) auth_login($id);
auth_registrar('usuarios', $reg);
bs_ok(['id' => $id]);
