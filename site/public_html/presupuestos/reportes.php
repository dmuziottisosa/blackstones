<?php
// ============================================================
// /presupuestos/reportes.php — Control (solo administradores). Hasta el 18/11/2026 se llamó "Reportes":
// el archivo y el permiso conservan ese nombre para no romper links; lo que se ve dice "Control".
// ============================================================
// Lo que se baja del sistema para guardar afuera. Hoy: el cierre del mes (dueño, 18/11/2026).
// Acceso: sesión de auth_check.php + permiso 'reportes' (solo administradores; ver AUTH_PERMISOS).
// El archivo lo arma calculadora/api/cierre-mes.php.
// ============================================================

require_once __DIR__ . '/../calculadora/auth_check.php';

// Sin sesión va al login; con sesión pero sin permiso, a la primera pantalla de su rol.
auth_exigir_pagina('reportes');

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>BlackStones · Control</title>
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

.wrap{max-width:1480px;margin:0 auto;padding:20px 20px 60px}
.wrap{max-width:1240px}
/* Control (dueño, 18/11/2026): a la izquierda, el 70 %, los recordatorios; a la derecha, el 30 %, el cierre del mes. */
.ctl{display:grid;grid-template-columns:minmax(0,7fr) minmax(0,3fr);gap:18px;align-items:start}
@media(max-width:900px){.ctl{grid-template-columns:minmax(0,1fr)}}
.ctl .caja h2{font-size:20px}
.ctl .ci-ops label{flex-wrap:wrap}
.ctl .ci-ops small{flex-basis:100%;padding-left:27px;margin-top:-6px}
.rec-vacio{padding:18px 0 6px;color:var(--text2);font-size:14px}
.rec{display:grid;grid-template-columns:30px minmax(0,1fr) auto;gap:4px 14px;align-items:start;padding:15px 0;border-top:1px solid var(--border-soft)}
.rec:first-child{border-top:0;padding-top:6px}
.rec input{width:24px;height:24px;margin-top:1px;accent-color:var(--gr);cursor:pointer}
.rec .t{font-size:15.5px;font-weight:700;line-height:1.35}
.rec .d{grid-column:2;font-size:13.5px;color:var(--text2);line-height:1.5}
.rec .d a{color:var(--text);font-weight:700;white-space:nowrap}
.rec .d .ir{white-space:nowrap}
/* Abajo: qué fichas son (dueño, 18/11/2026). Cada nombre abre su ficha en el tablero. */
.rec .f{grid-column:2;font-size:13.5px;line-height:1.6;color:var(--text2)}
.rec .f a{color:var(--text);font-weight:600;text-decoration:none;border-bottom:1px solid var(--border)}
.rec .f a:hover{border-bottom-color:var(--text)}
.rec.hecho .f{display:none}
.rec .c{font-size:11.5px;font-weight:800;letter-spacing:.05em;text-transform:uppercase;white-space:nowrap;padding:3px 9px;border-radius:999px;background:var(--card-alt);color:var(--text2)}
.rec.urge .c{background:var(--amb-soft);color:var(--amb)}
.rec.hecho .t{font-weight:600;color:var(--text3);text-decoration:line-through}
.rec.hecho .c{background:var(--gr-soft);color:var(--gr)}
.rec-sep{margin-top:10px;padding:14px 0 2px;border-top:1px solid var(--border);font-size:11.5px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--text3)}
.rec-sep+.rec{border-top:0}
/* Los pendientes van por grupo (Turnos, Taller y plazo, Plata y contratos, Fin de mes): un rótulo chico y nada más */
.rec-grupo{padding:16px 0 0;font-size:11.5px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--text3)}
.rec-grupo:first-child{padding-top:4px}
.rec-grupo+.rec{border-top:0;padding-top:8px}
@media(max-width:520px){.rec{grid-template-columns:30px minmax(0,1fr)}.rec .c{grid-column:2;justify-self:start}}
.caja{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);padding:22px 22px 20px;margin-bottom:18px}
.caja h2{font-family:'Fraunces',serif;font-size:21px;font-weight:600;margin-bottom:4px}
.caja .sub{color:var(--text2);font-size:13.5px;margin-bottom:16px;line-height:1.55}
.acc{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:16px}
.btn{font-family:inherit;font-size:13.5px;font-weight:700;padding:10px 16px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--text);cursor:pointer}
.btn.pri{background:var(--dk);border-color:var(--dk);color:var(--cr)}
.btn:disabled{opacity:.45;cursor:not-allowed}
.rg-filtros{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px}
.rg-filtros select,.rg-filtros input{font-family:inherit;font-size:14px;padding:9px 11px;border:1px solid var(--border);border-radius:8px;background:var(--card);color:var(--text);min-width:0}
.rg-filtros input{flex:1 1 200px}
.rg-cuenta{font-size:12.5px;color:var(--text3);margin-bottom:6px}
.rg-dia{margin-top:14px;padding:6px 0;font-size:12px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--text2);border-bottom:1px solid var(--border)}
.rg-f{display:grid;grid-template-columns:52px 150px minmax(0,1fr);gap:10px;padding:7px 0;border-bottom:1px solid var(--border-soft);font-size:13.5px;line-height:1.4}
.rg-f .h{font-variant-numeric:tabular-nums;color:var(--text2);font-weight:600}
.rg-f .q{font-weight:600;overflow-wrap:anywhere}
.rg-f .o{color:var(--text3);font-size:12.5px}
.rg-vacio{padding:14px 0;color:var(--text2);font-size:13.5px}
@media(max-width:560px){.rg-f{grid-template-columns:46px minmax(0,1fr)}.rg-f .q{grid-column:2}.rg-f .d{grid-column:2}}
.msg{font-size:13.5px;font-weight:600}.msg.ok{color:var(--gr)}
.ci-estado{font-size:14px;font-weight:600;margin-bottom:12px}
.ci-estado.si{color:var(--gr)}
.ci-ops{display:flex;flex-direction:column;gap:9px}
.ci-ops label{display:flex;align-items:center;gap:9px;font-size:14px;cursor:pointer}
.ci-ops input{width:18px;height:18px;flex-shrink:0}
.ci-ops small{color:var(--text3);font-size:12.5px}
a,button,.toggle{-webkit-tap-highlight-color:transparent;touch-action:manipulation}
a:focus-visible,.toggle:focus-visible{outline:2px solid var(--gd);outline-offset:2px}
@media(max-width:520px) and (pointer:coarse){.rec{grid-template-columns:38px minmax(0,1fr)}}
@media(min-width:521px) and (pointer:coarse){.rec{grid-template-columns:38px minmax(0,1fr) auto}}
@media(max-width:1099px),(pointer:coarse){
  .rec input{width:32px;height:32px} /* el tilde "Hecho", para el dedo (auditoría de tablet, 18/11/2026) */
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
  .wrap{padding:12px 12px 60px}
}
</style>
</head>
<body>

<div class="header">
  <div style="display:flex;align-items:center;gap:12px">
    <img src="../logo/logo_isotipo.png" alt="BlackStones" style="height:36px;width:auto">
    <div><h1>BlackStones</h1><span>Control</span></div>
  </div>
  <div class="h-right">
    <?= auth_quien_html() ?>
    <div class="toggle" onclick="toggleTheme()" title="Modo claro / oscuro"></div>
    <a href="/calculadora/logout.php" class="logout-link" title="Cerrar sesión" aria-label="Cerrar sesión">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    </a>
  </div>
</div>
<?= auth_nav_html('reportes') ?>
<div class="gold-line"></div>

<div class="wrap">
  <div class="ctl">
    <div class="caja" id="cajaRec">
      <h2>Recordatorios</h2>
      <p class="sub">Lo que hay que hacer hoy para que no se pase. Aparecen solos y se van cuando se hacen.</p>
      <div id="recLista"><div class="rec-vacio">Cargando…</div></div>
    </div>
    <div class="caja" id="cajaCierre">
      <h2>Cierre del mes</h2>
      <p class="sub">El último día del mes se baja todo en un archivo, para imprimir y guardar en la carpeta física: una <b>carpeta por cliente</b> y la <b>foto completa del sistema</b>.</p>
      <div id="ciEstado" class="ci-estado">Cargando…</div>
      <div class="ci-ops" id="ciOps">
        <label><input type="checkbox" checked disabled> Fichas, contratos y presupuestos adjuntos <small id="ciBase"></small></label>
        <label><input type="checkbox" id="ciPlanos" checked> Planos <small id="ciPlanosN"></small></label>
        <label><input type="checkbox" id="ciEntregas" checked> Fotos de entrega <small id="ciEntregasN"></small></label>
        <label><input type="checkbox" id="ciBachas" checked> Fotos de bacha <small id="ciBachasN"></small></label>
      </div>
      <div class="acc"><button class="btn pri" type="button" id="ciBajar" disabled>Descargar</button><span class="msg" id="ciMsg"></span></div>
    </div>
  </div>

  <div class="caja" id="cajaReg">
    <h2>Registro de acciones</h2>
    <p class="sub">Lo que se hizo hoy, quién y a qué hora. Se actualiza solo. Se anota cada cambio que se guarda en el sistema; mirar una pantalla no deja registro.</p>
    <div class="rg-filtros">
      <input type="date" id="rgDia" aria-label="Día">
      <select id="rgUsuario" aria-label="Usuario"><option value="">Todos los usuarios</option></select>
      <input type="search" id="rgQ" placeholder="Buscar: una obra, una acción…" autocomplete="off">
    </div>
    <div class="rg-cuenta" id="rgCuenta"></div>
    <div class="rg-tabla" id="rgTabla"></div>
    <div class="acc"><button class="btn" type="button" id="rgMas" hidden>Ver más</button></div>
  </div>
</div>

<script>
const $=id=>document.getElementById(id);
// ---------- Cierre del mes: bajar todo, solo el último día del mes (dueño, 18/11/2026) ----------
const API_CIERRE='/calculadora/api/cierre-mes.php';
const ddmm=iso=>iso.slice(8,10)+'/'+iso.slice(5,7)+'/'+iso.slice(0,4);
async function cargarCierre(){
  try{
    const r=await fetch(API_CIERRE,{credentials:'same-origin'}),j=await r.json();
    if(!j||!j.ok)throw new Error((j&&j.error)||'No se pudo consultar');
    const cu=(n,una,varias)=>n+' '+(n===1?una:varias);
    $('ciEstado').className='ci-estado'+(j.habilitado?' si':'');
    $('ciEstado').textContent=j.habilitado?'Hoy es el último día de '+j.mes+': se puede descargar.':'Se habilita el '+ddmm(j.cierre)+', último día de '+j.mes+'.';
    $('ciBase').textContent='· '+cu(j.fichas,'obra','obras')+' en el tablero'+(j.archivadas_mes?', '+cu(j.archivadas_mes,'archivada','archivadas')+' este mes':'')+', '+cu(j.contratos,'contrato','contratos')+', '+cu(j.presupuestos,'presupuesto adjunto','presupuestos adjuntos')+(j.mb.base?' ('+j.mb.base+' MB)':'');
    [['ciPlanosN',j.planos,j.mb.planos],['ciEntregasN',j.entregas,j.mb.entregas],['ciBachasN',j.bachas,j.mb.bachas]].forEach(x=>{$(x[0]).textContent='· '+cu(x[1],'archivo','archivos')+(x[2]?' ('+x[2]+' MB)':'');});
    $('ciBajar').disabled=!j.habilitado;
    $('ciBajar').textContent=j.habilitado?'Descargar el cierre de '+j.mes:'Descargar';
  }catch(e){$('ciEstado').textContent=e.message;}
}
$('ciBajar').addEventListener('click',()=>{
  const q=['bajar=1'].concat([['planos','ciPlanos'],['entregas','ciEntregas'],['bachas','ciBachas']].filter(x=>$(x[1]).checked).map(x=>x[0]+'=1'));
  $('ciMsg').className='msg ok';$('ciMsg').textContent='Armando el archivo… puede tardar si hay muchas fotos.';
  location.href=API_CIERRE+'?'+q.join('&');
  setTimeout(()=>{$('ciMsg').textContent='';},15000);
});
cargarCierre();

// ---------- Recordatorios (dueño, 18/11/2026): el recordatorio se hace adentro del sistema, y sin ruido ----------
// Se calculan en el servidor leyendo el tablero; acá solo se muestran y se tilda "Hecho".
const API_CTL='/calculadora/api/control.php',escR=s=>String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let recYendo=false;
function recFila(r,listo){
  const quien=r.hecho?('Hecho'+(r.hecho.por?' por '+escR(r.hecho.por):'')+(r.hecho.at?' · '+escR(r.hecho.at.slice(11,16))+' hs':'')):'';
  return '<div class="rec'+(listo?' hecho':(r.falta<=1?' urge':''))+'">'
    +'<input type="checkbox" data-rec="'+escR(r.id)+'"'+(listo?' checked':'')+(r.completo&&!r.hecho?' disabled':'')+' aria-label="Hecho: '+escR(r.titulo)+'">'
    +'<div class="t">'+escR(r.titulo)+'</div><span class="c">'+(listo?'Hecho':escR(r.cuando))+'</span>'
    +'<div class="d">'+escR(r.detalle)+(quien?' · '+quien:'')+' <span class="ir">· <a href="'+escR(r.link)+'">'+escR(r.link_txt||'Ver →')+'</a></span></div>'
    +((r.fichas||[]).length?'<div class="f">'+escR(r.fichas_txt||'Fichas')+': '+r.fichas.map(x=>'<a href="'+escR(x.link)+'">'+escR(x.txt)+'</a>').join(' · ')+(r.fichas_mas?' y '+r.fichas_mas+' más':'')+'</div>':'')+'</div>';
}
async function cargarRec(){
  if(recYendo)return;recYendo=true;
  try{
    const r=await fetch(API_CTL,{credentials:'same-origin',cache:'no-store'}),j=await r.json();
    if(!j||!j.ok)throw new Error((j&&j.error)||'No se pudieron leer los recordatorios');
    const listo=x=>!!x.hecho||x.completo,pend=j.recordatorios.filter(x=>!listo(x)),hechos=j.recordatorios.filter(listo);
    // un rótulo cada vez que cambia el grupo (vienen ordenados del servidor)
    // (con un solo grupo, el rótulo sobra)
    const varios=new Set(pend.map(x=>x.grupo)).size>1;
    let g='';const porGrupo=pend.map(x=>{const cab=(varios&&x.grupo&&x.grupo!==g)?'<div class="rec-grupo">'+escR(x.grupo)+'</div>':'';g=x.grupo||g;return cab+recFila(x,false);}).join('');
    $('recLista').innerHTML=(pend.length?porGrupo:'<div class="rec-vacio">'+(hechos.length?'Todo hecho. No queda nada pendiente.':'No hay nada para recordar hoy.')+'</div>')
      +(hechos.length?'<div class="rec-sep">Hechos</div>'+hechos.map(x=>recFila(x,true)).join(''):'');
  }catch(e){$('recLista').innerHTML='<div class="rec-vacio">'+escR(e.message)+'</div>';}
  recYendo=false;
}
$('recLista').addEventListener('change',async ev=>{
  const t=ev.target;if(!t.dataset||t.dataset.rec===undefined)return;
  t.disabled=true;
  try{
    const r=await fetch(API_CTL,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({accion:t.checked?'hecho':'deshacer',id:t.dataset.rec})}),j=await r.json();
    if(!j||!j.ok)throw new Error((j&&j.error)||'No se pudo guardar');
  }catch(e){alert(e.message);}
  cargarRec();
});
cargarRec();
setInterval(()=>{if(!document.hidden)cargarRec();},60000);

// ---------- Registro de acciones (dueño, 18/11/2026): solo lectura ----------
const API_REG='/calculadora/api/registro.php',esc=s=>String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const MESES_R=['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'],DIAS_R=['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
let rgFilas=[],rgTotal=0,rgHoy='',rgYendo=false;
// Se ve un día por vez, y de entrada el de hoy (dueño, 18/11/2026). Mientras se mira hoy, se actualiza solo.
async function cargarRegistro(mas,solo){
  if(rgYendo)return;rgYendo=true;
  const q=new URLSearchParams({dia:$('rgDia').value||'',usuario:$('rgUsuario').value||'',q:$('rgQ').value.trim(),desde:mas?rgFilas.length:0});
  try{
    const r=await fetch(API_REG+'?'+q,{credentials:'same-origin'}),j=await r.json();
    if(!j||!j.ok)throw new Error((j&&j.error)||'No se pudo leer el registro');
    if(!mas){
      rgFilas=[];
      if(rgHoy&&j.hoy!==rgHoy&&$('rgDia').value===rgHoy){rgHoy=j.hoy;$('rgDia').max=j.hoy;$('rgDia').value=j.hoy;rgYendo=false;return cargarRegistro();} // pasó la medianoche: se sigue viendo "hoy"
      rgHoy=j.hoy;$('rgDia').max=j.hoy;$('rgDia').value=j.dia;
      const u=$('rgUsuario').value;
      $('rgUsuario').innerHTML='<option value="">Todos los usuarios</option>'+j.usuarios.map(x=>'<option value="'+esc(x.usuario)+'"'+(x.usuario===u?' selected':'')+'>'+esc(x.nombre)+'</option>').join('');
    }
    rgFilas=rgFilas.concat(j.filas);rgTotal=j.total;
    const esHoy=j.dia===j.hoy,p=j.dia.split('-').map(Number);
    let h='<div class="rg-dia">'+(esHoy?'Hoy · ':'')+DIAS_R[new Date(p[0],p[1]-1,p[2]).getDay()]+' '+p[2]+' de '+MESES_R[p[1]-1]+'</div>';
    rgFilas.forEach(f=>{
      h+='<div class="rg-f"><span class="h">'+esc(f.at.slice(11,16))+'</span><span class="d">'+esc(f.nombre)+'</span><span class="q">'+esc(f.que)+(f.obra?' <span class="o">· '+esc(f.obra)+'</span>':'')+'</span></div>';
    });
    $('rgTabla').innerHTML=rgFilas.length?h:'<div class="rg-vacio">'+(esHoy?'Hoy todavía no se anotó ninguna acción':'Ese día no hay acciones anotadas')+($('rgUsuario').value||$('rgQ').value.trim()?' con ese filtro':'')+'.</div>';
    $('rgCuenta').textContent=rgTotal?(rgTotal===1?'1 acción':rgTotal+' acciones')+(rgFilas.length<rgTotal?' · se ven las últimas '+rgFilas.length:''):'';
    $('rgMas').hidden=!j.hay_mas;
  }catch(e){if(!solo)$('rgTabla').innerHTML='<div class="rg-vacio">'+esc(e.message)+'</div>';}
  rgYendo=false;
}
$('rgDia').addEventListener('change',()=>cargarRegistro());
$('rgUsuario').addEventListener('change',()=>cargarRegistro());
let rgT=null;$('rgQ').addEventListener('input',()=>{clearTimeout(rgT);rgT=setTimeout(()=>cargarRegistro(),300);});
$('rgMas').addEventListener('click',()=>cargarRegistro(true));
cargarRegistro();
// En vivo: cada 15 segundos, solo si se mira hoy, la pestaña está a la vista y no se abrió "Ver más".
setInterval(()=>{if(!document.hidden&&rgHoy&&$('rgDia').value===rgHoy&&rgFilas.length<=20&&document.activeElement!==$('rgQ'))cargarRegistro(false,true);},15000);
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
