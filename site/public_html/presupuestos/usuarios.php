<?php
// ============================================================
// /presupuestos/usuarios.php — Usuarios y roles
// ============================================================
// Cualquiera que entró ve "Mi usuario" (cambiar su contraseña).
// Los administradores ven además la lista: crear, cambiar rol, dar de baja,
// cambiar contraseña y borrar. Mientras no haya usuarios (contraseña
// compartida), la página ofrece crear el primer administrador.
// Datos: /calculadora/api/usuarios.php. Permisos: AUTH_PERMISOS en auth_check.php.
// ============================================================

require_once __DIR__ . '/../calculadora/auth_check.php';

if (!auth_is_valid()) {
    header('Location: /calculadora/login.php');
    exit;
}

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>BlackStones · Usuarios</title>
<link rel="icon" type="image/png" sizes="96x96" href="../favicon/favicon-96x96.png" />
<link rel="icon" type="image/svg+xml" href="../favicon/favicon.svg" />
<link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png" />
<link rel="shortcut icon" href="../favicon/favicon.ico" />
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;1,9..144,400&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}

/* === DESIGN TOKENS (los mismos del hub y del tablero) === */
:root{
  --bg:#FAF8F4;--card:#FFFFFF;--card-alt:#F5F0E5;
  --text:#1A1816;--text2:#5A4A35;--text3:#8B7860;
  --border:#E5DECF;--border-soft:#F0EAD8;
  --dk:#1A1816;--dk2:#2A2522;--gd:#C4A77D;--gdd:#7A6649;
  --gr:#1F8F47;--gr-soft:#E4F4E8;--cr:#FAF8F4;
  --red:#B03A3A;--red-soft:#FBEAEA;--amb:#8A5A0B;--amb-soft:#FBF1DC;
  --gd-soft:rgba(196,167,125,.10);
  --shadow:0 2px 10px rgba(26,24,22,.06);
  --shadow-lg:0 12px 32px rgba(26,24,22,.14);
  --radius:10px;--radius-sm:6px;
}
[data-theme="dark"]{
  --bg:#0A0907;--card:#16140F;--card-alt:#1F1B14;
  --text:#F5F0E5;--text2:#C4B596;--text3:#8B7860;
  --border:#2D2820;--border-soft:#221E18;
  --gr:#34D670;--gr-soft:#0E2A18;
  --red:#E26666;--red-soft:#2A1414;--amb:#E8B95C;--amb-soft:#3A2A14;
  --gd-soft:rgba(196,167,125,.12);
  --shadow:0 2px 10px rgba(0,0,0,.5);
  --shadow-lg:0 12px 32px rgba(0,0,0,.6);
}

body{font-family:'Manrope',sans-serif;background:var(--bg);color:var(--text);font-size:14px;line-height:1.45;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}
html{overflow-x:hidden}
button{font-family:inherit;color:inherit}

/* === HEADER + TABS (idénticos al hub) === */
.header{background:var(--dk);padding:16px 24px;display:flex;justify-content:space-between;align-items:center}
.header h1{font-family:'Fraunces',serif;color:var(--cr);font-size:1.4rem;font-weight:600}
.header span{color:var(--gd);font-size:.65rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase}
.header .h-right{display:flex;align-items:center;gap:16px}
.gold-line{height:3px;background:var(--gd)}
.toggle{width:50px;height:28px;background:var(--dk2);border-radius:14px;cursor:pointer;position:relative;border:1px solid var(--gd)}
.toggle::after{content:'☀️';position:absolute;left:3px;top:2px;width:22px;height:22px;font-size:13px;display:flex;align-items:center;justify-content:center;transition:transform .3s;background:var(--card);border-radius:50%}
[data-theme="dark"] .toggle::after{content:'🌙';transform:translateX(22px)}
.logout-link{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;color:var(--gd);opacity:.5;text-decoration:none;border-radius:50%}
.logout-link svg{width:14px;height:14px}
.logout-link:hover{opacity:1;color:var(--cr)}
.h-tabs{background:var(--dk);display:flex;align-items:center;padding:0 24px;border-top:1px solid rgba(196,167,125,.12);overflow-x:auto;scrollbar-width:thin;scrollbar-color:var(--gd) transparent}
.h-tabs a{color:var(--gd);opacity:.55;text-decoration:none;font-size:.7rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;padding:11px 18px;white-space:nowrap;border-bottom:2px solid transparent}
.h-tabs a:hover{opacity:1;color:var(--cr)}
.h-tabs a.active{opacity:1;color:var(--gd);border-bottom-color:var(--gd)}
/* Encabezado en celulares angostos: el nombre no empuja a los botones fuera de la pantalla. */
.header>div{min-width:0}
.header h1{white-space:nowrap}
@media(max-width:380px){
  .header h1{font-size:1.15rem}
  .header img{height:30px!important}
  .header .h-right{gap:8px}
}

.wrap{max-width:860px;margin:0 auto;padding:24px 20px 80px}
.caja{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);padding:22px 22px 20px;margin-bottom:18px}
.caja h2{font-family:'Fraunces',serif;font-size:21px;font-weight:600;margin-bottom:4px}
.caja .sub{color:var(--text2);font-size:13.5px;margin-bottom:16px;line-height:1.55}
.campos{display:grid;grid-template-columns:1fr 1fr;gap:12px 14px}
.campo label{display:block;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text3);margin-bottom:5px}
.campo input,.campo select,.fila select{width:100%;font-family:inherit;font-size:15px;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--bg);color:var(--text);outline:none}
.campo input:focus,.campo select:focus,.fila select:focus{border-color:var(--gd);box-shadow:0 0 0 3px var(--gd-soft)}
.campo .ayuda{font-size:12px;color:var(--text3);margin-top:4px}
.acc{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:16px}
.btn{font-family:inherit;font-size:13.5px;font-weight:700;padding:10px 16px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--text);cursor:pointer}
.btn.pri{background:var(--dk);border-color:var(--dk);color:var(--cr)}
[data-theme="dark"] .btn.pri{background:var(--gd);border-color:var(--gd);color:#1A1816}
.btn.chico{font-size:12.5px;padding:7px 11px;font-weight:600}
.btn.rojo{color:var(--red)}
.btn:disabled{opacity:.5;cursor:default}
.msg{font-size:13.5px;font-weight:600}
.msg.ok{color:var(--gr)}.msg.mal{color:var(--red)}
.fila{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(0,1.1fr) auto;gap:10px 14px;align-items:center;padding:13px 0;border-top:1px solid var(--border-soft)}
.fila:first-of-type{border-top:none}
.fila .quien b{display:block;font-size:15px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.fila .quien span{font-size:12.5px;color:var(--text3)}
.fila .quien [data-estado]{margin-top:1px}
.fila .bts{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}
.fila.baja .quien b{color:var(--text3);text-decoration:line-through}
.et{display:inline-block;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.04em;background:var(--amb-soft);color:var(--amb);margin-left:6px;vertical-align:1px}
.et.vos{background:var(--gr-soft);color:var(--gr)}
.pt{display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--border);margin-right:6px;vertical-align:1px}
.pt.on{background:var(--gr);box-shadow:0 0 0 3px var(--gr-soft)}
.fila .quien span.on{color:var(--gr);font-weight:700}
.ahora{display:flex;flex-wrap:wrap;gap:6px 8px;align-items:center;margin:-4px 0 14px;font-size:13.5px;color:var(--text2)}
.ahora b{color:var(--text)}
.ahora .chip{display:inline-flex;align-items:center;padding:4px 11px;border-radius:999px;background:var(--gr-soft);color:var(--gr);font-weight:700;font-size:12.5px}
.ahora .chip small{font-weight:600;opacity:.8;margin-left:5px}
.roles{width:100%;border-collapse:collapse;font-size:13.5px}
.roles th,.roles td{padding:8px 8px;border-top:1px solid var(--border-soft);text-align:center}
.roles th{font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--text3);border-top:none}
.roles td:first-child,.roles th:first-child{text-align:left;color:var(--text2)}
.roles .si{color:var(--gr);font-weight:700}.roles .no{color:var(--text3)}
.velo{position:fixed;inset:0;background:rgba(10,9,7,.55);display:none;align-items:center;justify-content:center;padding:16px;z-index:50}
.velo.on{display:flex}
.velo .caja{width:100%;max-width:420px;margin:0;box-shadow:var(--shadow-lg)}
.oculto{display:none!important}
a,button,.toggle{-webkit-tap-highlight-color:transparent;touch-action:manipulation}
a:focus-visible,.toggle:focus-visible{outline:2px solid var(--gd);outline-offset:2px}
@media(max-width:1099px),(pointer:coarse){
  .toggle{width:54px;height:30px}
  .toggle::after{top:3px}
  .logout-link{width:36px;height:36px;opacity:.7}
  .logout-link svg{width:16px;height:16px}
}
@media(max-width:1099px){
  .header{padding:12px max(14px,env(safe-area-inset-right)) 12px max(14px,env(safe-area-inset-left))}
  .header .h-right>span{display:none}
  .h-tabs{padding:0 8px;scrollbar-width:none}
  .h-tabs::-webkit-scrollbar{display:none}
  .h-tabs a{padding:12px 12px}
  .wrap{padding:14px 12px 70px}
  .caja{padding:18px 16px}
}
@media(max-width:640px){
  .campos{grid-template-columns:1fr}
  .fila{grid-template-columns:1fr;gap:8px}
  .fila .bts{justify-content:flex-start}
  .campo input,.campo select,.fila select{font-size:16px}
  .btn{padding:12px 16px}
  .btn.chico{padding:10px 12px}
  .roles{font-size:12.5px}
  .roles th,.roles td{padding:7px 4px}
}
</style>
</head>
<body>

<div class="header">
  <div style="display:flex;align-items:center;gap:12px">
    <img src="../logo/logo_isotipo.png" alt="BlackStones" style="height:36px;width:auto">
    <div><h1>BlackStones</h1><span><?= auth_puede('usuarios') ? 'Usuarios' : 'Mi usuario' ?></span></div>
  </div>
  <div class="h-right">
    <?= auth_quien_html() ?>
    <div class="toggle" onclick="toggleTheme()" title="Modo claro / oscuro"></div>
    <a href="/calculadora/logout.php" class="logout-link" title="Cerrar sesión" aria-label="Cerrar sesión">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    </a>
  </div>
</div>
<?= auth_nav_html('usuarios') ?>
<div class="gold-line"></div>

<div class="wrap">

  <!-- Todavía no hay usuarios: crear el primer administrador -->
  <div class="caja oculto" id="cajaPrimero">
    <h2>Creá tu usuario administrador</h2>
    <p class="sub">Hoy todo el equipo entra con la misma contraseña. Cuando crees tu usuario, esa contraseña deja de servir: cada persona entra con el suyo y solo ve lo que le toca. Después creás acá mismo los usuarios de los demás.</p>
    <form id="fPrimero" autocomplete="off">
      <div class="campos">
        <div class="campo"><label for="pNombre">Tu nombre</label><input id="pNombre" maxlength="40" required></div>
        <div class="campo"><label for="pUsuario">Usuario</label><input id="pUsuario" maxlength="30" required autocapitalize="none" autocorrect="off" spellcheck="false" autocomplete="username"><div class="ayuda">En minúsculas, sin espacios.</div></div>
        <div class="campo"><label for="pClave">Contraseña</label><input id="pClave" type="password" required autocomplete="new-password"><div class="ayuda">8 caracteres o más.</div></div>
        <div class="campo"><label for="pClave2">Repetila</label><input id="pClave2" type="password" required autocomplete="new-password"></div>
      </div>
      <div class="acc"><button class="btn pri" type="submit">Crear mi usuario</button><span class="msg" id="pMsg"></span></div>
    </form>
  </div>

  <!-- Mi usuario -->
  <div class="caja oculto" id="cajaYo">
    <h2 id="yoNombre"></h2>
    <p class="sub" id="yoSub"></p>
    <form id="fClave" autocomplete="off">
      <div class="campos">
        <div class="campo"><label for="cActual">Contraseña actual</label><input id="cActual" type="password" required autocomplete="current-password"></div>
        <div class="campo"></div>
        <div class="campo"><label for="cNueva">Contraseña nueva</label><input id="cNueva" type="password" required autocomplete="new-password"><div class="ayuda">8 caracteres o más.</div></div>
        <div class="campo"><label for="cNueva2">Repetila</label><input id="cNueva2" type="password" required autocomplete="new-password"></div>
      </div>
      <div class="acc"><button class="btn" type="submit">Cambiar mi contraseña</button><span class="msg" id="cMsg"></span></div>
    </form>
  </div>

  <!-- Lista (solo administradores) -->
  <div class="caja oculto" id="cajaLista">
    <h2>Usuarios</h2>
    <p class="sub">El cambio de rol y la baja valen al instante: la persona pierde el acceso en el momento.</p>
    <div class="ahora" id="ahora" aria-live="polite"></div>
    <div id="lista"></div>
    <div class="acc"><button class="btn pri" type="button" id="bNuevo">+ Nuevo usuario</button><span class="msg" id="lMsg"></span></div>
  </div>

  <div class="caja oculto" id="cajaRoles">
    <h2>Qué puede cada rol</h2>
    <p class="sub">Lo que un rol no puede, tampoco lo ve: el servidor no se lo manda.</p>
    <table class="roles">
      <tr><th></th><th>Administrador</th><th>Operador</th><th>Planos y bacha</th></tr>
      <tr><td>Ver el tablero</td><td class="si">Sí</td><td class="si">Sí</td><td class="si">Sí</td></tr>
      <tr><td>Adjuntar y borrar fotos de bacha, plano y entrega</td><td class="si">Sí</td><td class="si">Sí</td><td class="si">Sí</td></tr>
      <tr><td>Anotar de quién es la bacha y su modelo</td><td class="si">Sí</td><td class="si">Sí</td><td class="si">Sí</td></tr>
      <tr><td>Dar aprobaciones (medición, corte, fabricación, instalación)</td><td class="si">Sí</td><td class="si">Sí</td><td class="no">No</td></tr>
      <tr><td>Cargar, mover, editar, archivar y borrar obras</td><td class="si">Sí</td><td class="si">Sí</td><td class="no">No</td></tr>
      <tr><td>Ver la seña de la ficha</td><td class="si">Sí</td><td class="si">Sí</td><td class="no">No</td></tr>
      <tr><td>Calculadora, Presupuestos y Calendario</td><td class="si">Sí</td><td class="si">Sí</td><td class="no">No</td></tr>
      <tr><td>Ver Relación-Contrato, con la plata de cada contrato</td><td class="si">Sí</td><td class="si">Sí</td><td class="no">No</td></tr>
      <tr><td>Ver el resumen de plata del mes</td><td class="si">Sí</td><td class="no">No</td><td class="no">No</td></tr>
      <tr><td>Cargar y cambiar contratos, cobros y pagos</td><td class="si">Sí</td><td class="no">No</td><td class="no">No</td></tr>
      <tr><td>Ver quién está trabajando ahora y dónde</td><td class="si">Sí</td><td class="si">Sí</td><td class="si">Sí</td></tr>
      <tr><td>Usuarios</td><td class="si">Sí</td><td class="no">No</td><td class="no">No</td></tr>
    </table>
  </div>
</div>

<!-- Ventana: nuevo usuario / cambiar contraseña de otro -->
<div class="velo" id="velo">
  <div class="caja">
    <h2 id="vTitulo"></h2>
    <p class="sub" id="vSub"></p>
    <form id="fVelo" autocomplete="off">
      <div class="campos" style="grid-template-columns:1fr">
        <div class="campo" data-solo="nuevo"><label for="vNombre">Nombre</label><input id="vNombre" maxlength="40"></div>
        <div class="campo" data-solo="nuevo"><label for="vUsuario">Usuario</label><input id="vUsuario" maxlength="30" autocapitalize="none" autocorrect="off" spellcheck="false" autocomplete="off"><div class="ayuda">En minúsculas, sin espacios. Con esto entra.</div></div>
        <div class="campo" data-solo="nuevo"><label for="vRol">Rol</label><select id="vRol"></select></div>
        <div class="campo"><label for="vClave">Contraseña</label><input id="vClave" type="password" autocomplete="new-password"><div class="ayuda">8 caracteres o más. Después la puede cambiar desde "Mi usuario".</div></div>
      </div>
      <div class="acc"><button class="btn pri" type="submit" id="vOk">Guardar</button><button class="btn" type="button" id="vNo">Cancelar</button><span class="msg mal" id="vMsg"></span></div>
    </form>
  </div>
</div>

<script>
const API='/calculadora/api/usuarios.php';
const $=id=>document.getElementById(id);
const esc=s=>String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let D=null,VELO=null;
// Quién está conectado ahora y en qué pantalla (api/presencia.php). Se refresca solo.
const API_PRES='/calculadora/api/presencia.php';
const PAGINAS={calculadora:'Calculadora',presupuestos:'Presupuestos',tablero:'Tablero',calendario:'Calendario',contratos:'Relación-Contrato',stock:'Stock',usuarios:'Usuarios'};
let PRES={};
function hace(s){
  if(s<90)return 'hace un momento';
  if(s<3600)return 'hace '+Math.round(s/60)+' min';
  if(s<86400){const h=Math.round(s/3600);return 'hace '+h+(h===1?' hora':' horas');}
  const d=Math.round(s/86400);return 'hace '+d+(d===1?' día':' días');
}
function dondeTxt(l){const n=l.map(p=>PAGINAS[p]||p);return n.length>1?n.slice(0,-1).join(', ')+' y '+n[n.length-1]:n[0];}
function estadoHtml(u){
  const p=PRES[u.id];
  if(p&&p.en_linea.length)return '<span class="on"><i class="pt on"></i>En línea · '+esc(dondeTxt(p.en_linea))+'</span>';
  if(p)return '<span><i class="pt"></i>'+esc(u.usuario)+' · visto '+hace(p.visto)+' en '+esc(PAGINAS[p.pagina]||p.pagina)+'</span>';
  return '<span><i class="pt"></i>'+esc(u.usuario)+' · último ingreso: '+fecha(u.ultimo)+'</span>';
}
function pintarAhora(){
  if(!D||!Array.isArray(D.usuarios))return;
  const on=D.usuarios.filter(u=>PRES[u.id]&&PRES[u.id].en_linea.length);
  $('ahora').innerHTML=on.length
    ?'<b>En línea ahora:</b>'+on.map(u=>'<span class="chip">'+esc(u.nombre)+'<small>'+esc(dondeTxt(PRES[u.id].en_linea))+'</small></span>').join('')
    :'<b>En línea ahora:</b> nadie más.';
  // el estado de cada renglón, sin redibujar la lista (no se pierde un desplegable abierto)
  document.querySelectorAll('#lista .fila').forEach(f=>{const u=D.usuarios.find(x=>x.id===f.dataset.id),e=f.querySelector('[data-estado]');if(u&&e)e.innerHTML=estadoHtml(u);});
}
async function cargarPresencia(){
  if(!D||!Array.isArray(D.usuarios)||document.visibilityState!=='visible')return;
  try{
    const r=await fetch(API_PRES,{credentials:'same-origin',cache:'no-store'});const j=r.ok?await r.json():null;
    if(j&&j.ok){PRES=j.usuarios||{};pintarAhora();}
  }catch(e){}
}

async function pedir(body){
  const r=await fetch(API,{method:body?'POST':'GET',credentials:'same-origin',cache:'no-store',headers:body?{'Content-Type':'application/json'}:{},body:body?JSON.stringify(body):undefined});
  if(r.status===401){location.href='/calculadora/login.php';throw new Error('Se cerró la sesión.');}
  let j=null;try{j=await r.json();}catch(e){}
  if(!j||!j.ok)throw new Error((j&&j.error)||'No se pudo guardar. Probá de nuevo.');
  return j;
}
function msg(el,txt,ok){el.textContent=txt||'';el.className='msg '+(ok?'ok':'mal');}
function fecha(s){if(!s)return'nunca';const p=s.split('-');return p[2]+'/'+p[1]+'/'+p[0];}

async function cargar(){
  D=await pedir();
  $('cajaPrimero').classList.toggle('oculto',D.modo!=='viejo');
  const con=D.modo==='usuarios';
  $('cajaYo').classList.toggle('oculto',!con);
  const admin=con&&Array.isArray(D.usuarios);
  $('cajaLista').classList.toggle('oculto',!admin);
  $('cajaRoles').classList.toggle('oculto',!admin);
  if(!con)return;
  const rol=(D.roles.find(r=>r.id===D.yo.rol)||{}).nombre||'';
  $('yoNombre').textContent=D.yo.nombre;
  $('yoSub').textContent='Entrás como "'+D.yo.usuario+'" · '+rol;
  if(admin){pintar();pintarAhora();cargarPresencia();}
}
function pintar(){
  $('lista').innerHTML=D.usuarios.map(u=>{
    const yo=u.id===D.yo.id;
    const ops=D.roles.map(r=>'<option value="'+r.id+'"'+(r.id===u.rol?' selected':'')+'>'+esc(r.nombre)+'</option>').join('');
    return '<div class="fila'+(u.activo?'':' baja')+'" data-id="'+u.id+'">'+
      '<div class="quien"><b>'+esc(u.nombre)+(yo?'<span class="et vos">Vos</span>':'')+(u.activo?'':'<span class="et">De baja</span>')+'</b>'+
      '<div data-estado>'+estadoHtml(u)+'</div></div>'+
      '<div><select data-rol aria-label="Rol de '+esc(u.nombre)+'"'+(yo?' disabled':'')+'>'+ops+'</select></div>'+
      '<div class="bts"><button class="btn chico" data-clave>Contraseña</button>'+
      (yo?'':'<button class="btn chico" data-baja>'+(u.activo?'Dar de baja':'Reactivar')+'</button><button class="btn chico rojo" data-borrar>Borrar</button>')+
      '</div></div>';
  }).join('');
}
function usuarioDe(el){const f=el.closest('.fila');return D.usuarios.find(u=>u.id===f.dataset.id);}
async function hacer(body,ok){
  try{await pedir(body);await cargar();msg($('lMsg'),ok,true);}
  catch(e){await cargar().catch(()=>{});msg($('lMsg'),e.message,false);}
}
$('lista').addEventListener('change',e=>{
  if(!e.target.matches('[data-rol]'))return;
  const u=usuarioDe(e.target);
  hacer({accion:'editar',id:u.id,rol:e.target.value},'Rol cambiado.'); // solo el rol: no pisa una baja hecha desde otra pantalla
});
$('lista').addEventListener('click',e=>{
  const b=e.target.closest('button');if(!b)return;
  const u=usuarioDe(b);
  if(b.hasAttribute('data-clave'))abrirVelo('clave',u);
  else if(b.hasAttribute('data-baja')){
    if(u.activo&&!confirm('¿Dar de baja a '+u.nombre+'? Pierde el acceso en el momento. Se puede reactivar.'))return;
    hacer({accion:'editar',id:u.id,activo:!u.activo},u.activo?'Dado de baja.':'Reactivado.');
  }else if(b.hasAttribute('data-borrar')){
    if(!confirm('¿Borrar el usuario de '+u.nombre+'? No se puede deshacer. Lo que cargó en el sistema no se toca.'))return;
    hacer({accion:'borrar',id:u.id},'Usuario borrado.');
  }
});

function abrirVelo(modo,u){
  VELO={modo,u};
  $('fVelo').reset();msg($('vMsg'),'');
  document.querySelectorAll('[data-solo="nuevo"]').forEach(x=>x.classList.toggle('oculto',modo!=='nuevo'));
  if(modo==='nuevo'){
    $('vTitulo').textContent='Nuevo usuario';
    $('vSub').textContent='Pasale el usuario y la contraseña en persona o por teléfono, no por escrito.';
    $('vRol').innerHTML='<option value="">Elegí…</option>'+D.roles.map(r=>'<option value="'+r.id+'">'+esc(r.nombre)+'</option>').join('');
  }else{
    $('vTitulo').textContent='Contraseña de '+u.nombre;
    $('vSub').textContent='Se le cierran las sesiones abiertas y entra con la nueva.';
  }
  $('velo').classList.add('on');
  setTimeout(()=>(modo==='nuevo'?$('vNombre'):$('vClave')).focus(),50);
}
function cerrarVelo(){$('velo').classList.remove('on');VELO=null;}
$('bNuevo').addEventListener('click',()=>abrirVelo('nuevo'));
$('vNo').addEventListener('click',cerrarVelo);
$('velo').addEventListener('click',e=>{if(e.target===$('velo'))cerrarVelo();});
document.addEventListener('keydown',e=>{if(e.key==='Escape'&&VELO)cerrarVelo();});
$('fVelo').addEventListener('submit',async e=>{
  e.preventDefault();
  const body=VELO.modo==='nuevo'
    ?{accion:'crear',nombre:$('vNombre').value,usuario:$('vUsuario').value,rol:$('vRol').value,clave:$('vClave').value}
    :{accion:'clave',id:VELO.u.id,clave:$('vClave').value};
  $('vOk').disabled=true;
  try{await pedir(body);const ok=VELO.modo==='nuevo'?'Usuario creado.':'Contraseña cambiada.';cerrarVelo();await cargar();msg($('lMsg'),ok,true);}
  catch(err){msg($('vMsg'),err.message,false);}
  $('vOk').disabled=false;
});

$('fPrimero').addEventListener('submit',async e=>{
  e.preventDefault();
  if($('pClave').value!==$('pClave2').value){msg($('pMsg'),'Las dos contraseñas no son iguales.',false);return;}
  if(!confirm('Desde ahora la contraseña compartida deja de servir y cada persona entra con su usuario. ¿Seguimos?'))return;
  try{await pedir({accion:'primer_admin',nombre:$('pNombre').value,usuario:$('pUsuario').value,clave:$('pClave').value});location.reload();}
  catch(err){msg($('pMsg'),err.message,false);}
});
$('fClave').addEventListener('submit',async e=>{
  e.preventDefault();
  if($('cNueva').value!==$('cNueva2').value){msg($('cMsg'),'Las dos contraseñas nuevas no son iguales.',false);return;}
  try{await pedir({accion:'mi_clave',actual:$('cActual').value,nueva:$('cNueva').value});$('fClave').reset();msg($('cMsg'),'Contraseña cambiada.',true);}
  catch(err){msg($('cMsg'),err.message,false);}
});

setInterval(cargarPresencia,10000);
document.addEventListener('visibilitychange',cargarPresencia);
cargar().catch(e=>{document.querySelector('.wrap').insertAdjacentHTML('afterbegin','<div class="caja"><p class="msg mal">'+esc(e.message)+'</p></div>');});

// En pantallas angostas la barra de páginas se desliza de costado: la página actual queda a la vista.
(function(){
  const a=document.querySelector('.h-tabs a.active'),n=a&&a.parentElement;
  if(n)n.scrollLeft=Math.max(0,a.offsetLeft-(n.clientWidth-a.offsetWidth)/2);
})();
// === THEME TOGGLE (mismo que calc, hub y tablero) ===
function toggleTheme(){
  const html=document.documentElement;
  const isDark=html.getAttribute('data-theme')==='dark';
  html.setAttribute('data-theme',isDark?'light':'dark');
  localStorage.setItem('bs-theme',isDark?'light':'dark');
}
(function(){
  if(localStorage.getItem('bs-theme')==='dark')document.documentElement.setAttribute('data-theme','dark');
})();
</script>
</body>
</html>
