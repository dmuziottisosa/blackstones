<?php
// ============================================================
// /presupuestos/tablero.php — Tablero de obras
// ============================================================
// Las obras entran solas: una tarjeta por cada presupuesto "aprobado".
// Cuatro columnas + cuadro de retiro en local. La tarjeta se arrastra de
// una columna a otra, o se avanza con el botón Siguiente.
// Datos y reglas: /calculadora/api/tablero.php
//
// Auth: reusa HMAC cookie de la calc (auth_check.php).
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>BlackStones · Tablero de obras</title>

<link rel="icon" type="image/png" sizes="96x96" href="../favicon/favicon-96x96.png" />
<link rel="icon" type="image/svg+xml" href="../favicon/favicon.svg" />
<link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png" />
<link rel="shortcut icon" href="../favicon/favicon.ico" />
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;1,9..144,400&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}

/* === DESIGN TOKENS (los mismos del hub de presupuestos) === */
:root{
  --bg:#FAF8F4;--card:#FFFFFF;--card-alt:#F5F0E5;
  --text:#1A1816;--text2:#5A4A35;--text3:#8B7860;
  --border:#E5DECF;--border-soft:#F0EAD8;
  --dk:#1A1816;--dk2:#2A2522;--gd:#C4A77D;--gdd:#7A6649;
  --gr:#1F8F47;--gr-soft:#E4F4E8;--cr:#FAF8F4;
  --red:#B03A3A;--red-soft:#FBEAEA;--amb:#8A5A0B;--amb-soft:#FBF1DC;
  --gd-soft:rgba(196,167,125,.10);
  --shadow-sm:0 1px 2px rgba(26,24,22,.04);
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
  --shadow-sm:0 1px 2px rgba(0,0,0,.4);
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

/* === TABLERO === */
.wrap{max-width:1480px;margin:0 auto;padding:20px 20px 60px}
.aviso{color:var(--text2);font-size:13.5px;padding:40px 16px;text-align:center;line-height:1.6}
.aviso b{color:var(--text)}

.col-tabs{display:none}
.board{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;align-items:start}
.col{background:var(--card-alt);border:1px solid var(--border);border-radius:var(--radius);padding:10px;min-height:120px}
.col-h{display:flex;align-items:center;justify-content:space-between;padding:4px 6px 10px}
.col-h h2{font-family:'Fraunces',serif;font-size:17px;font-weight:600}
.col-n{font-size:12px;font-weight:700;color:var(--text2);background:var(--card);border:1px solid var(--border);border-radius:999px;min-width:26px;padding:1px 8px;text-align:center;font-variant-numeric:tabular-nums}
.col-sub{font-size:11.5px;color:var(--text3);padding:0 6px 8px;margin-top:-6px}
.col-vacia{color:var(--text3);font-size:13px;text-align:center;padding:22px 8px}
.col.drop{border-color:var(--gd);box-shadow:0 0 0 2px var(--gd) inset;background:var(--gd-soft)}

/* Retiro en local: cuadro aparte, debajo de las 4 columnas */
#aparte{margin-top:14px}
#aparte .col{min-height:0}
#aparte .cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:10px;align-items:start}
#aparte .card{margin-bottom:0}

/* === TARJETA === */
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:12px;margin-bottom:10px;box-shadow:var(--shadow-sm);cursor:grab;user-select:none;-webkit-user-select:none;-webkit-touch-callout:none}
.card.arrastrando{opacity:.3}
.fantasma{position:fixed;left:0;top:0;z-index:800;pointer-events:none;margin:0;box-shadow:var(--shadow-lg);border-color:var(--gd);cursor:grabbing}
body.arrastre,body.arrastre *{cursor:grabbing!important}
.card.alerta{border-left:4px solid var(--red)}
.c-id{font-size:15px;font-weight:600;line-height:1.3;overflow-wrap:anywhere}
.c-id b{font-weight:700;font-variant-numeric:tabular-nums}
.c-id .zona{color:var(--text3);font-weight:500}
.c-mat{color:var(--text2);font-size:13px;margin-top:2px;overflow-wrap:anywhere}

.c-fecha{display:inline-flex;align-items:center;gap:6px;margin-top:9px;padding:6px 10px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--card);font-size:12.5px;font-weight:600;color:var(--text2);cursor:pointer}
.c-fecha:hover{border-color:var(--gd)}
.c-fecha.vacia{border-style:dashed;color:var(--gdd);background:var(--gd-soft)}
[data-theme="dark"] .c-fecha.vacia{color:var(--gd)}
.c-fecha.hoy{background:var(--gd);border-color:var(--gd);color:#1A1816}
.c-fecha.atrasada{background:var(--red-soft);border-color:var(--red);color:var(--red)}

.c-promesa{font-size:12.5px;color:var(--text3);margin-top:7px}
.c-promesa.cerca{color:var(--amb);font-weight:600}
.c-promesa.vencida{color:var(--red);font-weight:700}
.c-nota{margin-top:8px;padding:7px 10px;border-radius:var(--radius-sm);background:var(--amb-soft);color:var(--amb);font-size:12.5px;font-weight:600;overflow-wrap:anywhere}

.c-checks{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px}
.chk{display:inline-flex;align-items:center;gap:7px;padding:7px 11px 7px 8px;border-radius:999px;border:1px solid var(--border);background:var(--card);font-size:12.5px;font-weight:600;color:var(--text2);cursor:pointer;text-align:left}
.chk::before{content:'';flex-shrink:0;width:16px;height:16px;border-radius:5px;border:1.5px solid var(--text3);display:flex;align-items:center;justify-content:center;font-size:11px;line-height:1;color:#fff}
.chk:hover{border-color:var(--gd)}
.chk.on{background:var(--gr-soft);border-color:var(--gr);color:var(--gr)}
.chk.on::before{content:'✓';background:var(--gr);border-color:var(--gr)}
[data-theme="dark"] .chk.on::before{color:#04220F}

.c-avanzar{display:block;width:100%;margin-top:10px;padding:10px 12px;border:none;border-radius:var(--radius-sm);background:var(--dk);color:var(--cr);font-size:13.5px;font-weight:700;cursor:pointer}
.c-avanzar:hover{background:var(--dk2)}
[data-theme="dark"] .c-avanzar{background:var(--gd);color:#1A1816}
.c-avanzar.falta,[data-theme="dark"] .c-avanzar.falta{background:none;color:var(--text3);border:1px dashed var(--border);font-weight:600}

.c-mas{display:block;margin:8px auto 0;padding:4px 10px;border:none;background:none;font-size:12px;font-weight:600;color:var(--text3);cursor:pointer}
.c-mas:hover{color:var(--text)}
.c-det{margin-top:8px;padding-top:10px;border-top:1px solid var(--border-soft);font-size:13px;display:grid;gap:7px;cursor:auto;user-select:text;-webkit-user-select:text}
.c-det .fila{display:flex;gap:8px}
.c-det .fila span{color:var(--text3);flex:0 0 74px}
.c-det .fila div{flex:1;min-width:0;overflow-wrap:anywhere}
.c-det a{color:var(--gdd);font-weight:600}
[data-theme="dark"] .c-det a{color:var(--gd)}
.c-det input{width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--card);color:var(--text);font-family:inherit;font-size:13px;outline:none}
.c-det input:focus{border-color:var(--gd);box-shadow:0 0 0 3px var(--gd-soft)}
.c-det .acciones{display:flex;flex-wrap:wrap;gap:14px;margin-top:2px}
.c-det .lnk{border:none;background:none;padding:0;font-size:12.5px;font-weight:600;color:var(--text2);cursor:pointer;text-decoration:underline}

#dp{position:absolute;opacity:0;pointer-events:none;width:1px;height:1px;border:0;padding:0}

/* === MODAL + TOAST === */
.modal-bg{position:fixed;inset:0;background:rgba(10,9,7,.55);display:none;align-items:center;justify-content:center;padding:16px;z-index:900}
.modal-bg.show{display:flex}
.modal{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-lg);padding:20px;max-width:420px;width:100%}
.modal h3{font-family:'Fraunces',serif;font-size:19px;font-weight:600;margin-bottom:8px}
.modal p{color:var(--text2);font-size:13.5px;line-height:1.55;margin-bottom:8px}
.modal .sena{display:flex;gap:8px;margin-top:4px}
.modal .sena input{flex:1;min-width:0;padding:11px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--card);color:var(--text);font-family:inherit;font-size:17px;font-weight:700;outline:none;font-variant-numeric:tabular-nums}
.modal .sena input:focus{border-color:var(--gd);box-shadow:0 0 0 3px var(--gd-soft)}
.modal .sena button{padding:0 14px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--card);font-size:14px;font-weight:700;color:var(--text2);cursor:pointer}
.modal .sena button.on{background:var(--dk);color:var(--cr);border-color:var(--dk)}
[data-theme="dark"] .modal .sena button.on{background:var(--gd);color:#1A1816;border-color:var(--gd)}
.modal .btns{display:flex;gap:10px;justify-content:flex-end;margin-top:14px}
.modal .btns button{padding:9px 16px;border-radius:var(--radius-sm);font-size:13.5px;font-weight:700;cursor:pointer;border:1px solid var(--border);background:var(--card)}
.modal .btns .ok{background:var(--dk);color:var(--cr);border-color:var(--dk)}
[data-theme="dark"] .modal .btns .ok{background:var(--gd);color:#1A1816;border-color:var(--gd)}
.toast{position:fixed;left:50%;bottom:24px;transform:translate(-50%,20px);background:var(--dk);color:var(--cr);padding:11px 16px;border-radius:var(--radius-sm);border-left:3px solid var(--red);box-shadow:var(--shadow-lg);font-size:13px;font-weight:600;opacity:0;pointer-events:none;transition:opacity .2s,transform .2s;z-index:1000;max-width:calc(100vw - 32px)}
.toast.show{opacity:1;transform:translate(-50%,0)}

/* === MÓVIL: una columna a la vez, con pestañas === */
@media(max-width:900px){
  .header{padding:12px 14px}
  .header .h-right > span{display:none}
  .h-tabs{padding:0 8px}
  .h-tabs a{padding:9px 12px;font-size:.62rem;letter-spacing:.06em}
  .wrap{padding:0 12px 60px}
  .col-tabs{display:flex;gap:5px;position:sticky;top:0;z-index:50;background:var(--bg);padding:10px 0;margin-bottom:2px}
  .col-tabs button{flex:1 1 0;min-width:0;padding:7px 2px 6px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--card);font-size:11.5px;font-weight:700;color:var(--text2);cursor:pointer;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.25}
  .col-tabs button.on{background:var(--dk);color:var(--cr);border-color:var(--dk)}
  [data-theme="dark"] .col-tabs button.on{background:var(--gd);color:#1A1816;border-color:var(--gd)}
  .col-tabs button i{display:block;font-style:normal;font-size:13px;opacity:.75;font-variant-numeric:tabular-nums}
  .col-tabs button.drop{border-color:var(--gd);box-shadow:0 0 0 2px var(--gd) inset}
  #aparte{margin-top:0}
  #aparte .cards{grid-template-columns:1fr}
  .col-sub{display:none}
  .board{grid-template-columns:1fr}
  .col{display:none;background:none;border:none;padding:0}
  .col.on{display:block}
  .col-h{display:none}
  .chk{padding:10px 13px 10px 10px;font-size:13px}
  .c-fecha{padding:9px 12px;font-size:13px}
  .c-avanzar{padding:13px 12px;font-size:14px}
}
</style>
</head>
<body>

<div class="header">
  <div style="display:flex;align-items:center;gap:12px">
    <img src="../logo/logo_isotipo.png" alt="BlackStones" style="height:36px;width:auto">
    <div><h1>BlackStones</h1><span>Tablero de obras</span></div>
  </div>
  <div class="h-right">
    <span>blackstones.com.ar</span>
    <div class="toggle" onclick="toggleTheme()" title="Modo claro / oscuro"></div>
    <a href="/calculadora/logout.php" class="logout-link" title="Cerrar sesión" aria-label="Cerrar sesión">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    </a>
  </div>
</div>
<nav class="h-tabs">
  <a href="/calculadora/">Calculadora</a>
  <a href="/presupuestos/?tab=activos">Presupuestos · Activos</a>
  <a href="/presupuestos/?tab=reporte">Presupuestos · Reporte</a>
  <a href="/presupuestos/tablero.php" class="active">Tablero</a>
</nav>
<div class="gold-line"></div>

<div class="wrap">
  <div class="col-tabs" id="colTabs"></div>
  <div class="board" id="board"><div class="aviso">Cargando…</div></div>
  <div id="aparte"></div>
</div>

<input type="date" id="dp" tabindex="-1" aria-hidden="true">

<div class="modal-bg" id="modalBg">
  <div class="modal" role="dialog" aria-modal="true">
    <h3 id="mTitulo"></h3>
    <div id="mCuerpo"></div>
    <div class="btns">
      <button type="button" id="mCancelar">Cancelar</button>
      <button type="button" class="ok" id="mOk">Archivar</button>
    </div>
  </div>
</div>
<div class="toast" id="toast"></div>

<script>
// === THEME TOGGLE (mismo que calc y hub) ===
function toggleTheme(){
  const html=document.documentElement;
  const isDark=html.getAttribute('data-theme')==='dark';
  html.setAttribute('data-theme',isDark?'light':'dark');
  localStorage.setItem('bs-theme',isDark?'light':'dark');
}
(function(){
  if(localStorage.getItem('bs-theme')==='dark')document.documentElement.setAttribute('data-theme','dark');
})();

// ============================================================
// Tablero de obras
// ============================================================
const API='/calculadora/api/tablero.php';
const API_ESTADO='/calculadora/api/state.php';

let ETAPAS=[];      // definición de etapas (viene del server)
let CARDS=[];       // tarjetas
let HOY='';         // YYYY-MM-DD según el server
let abierta='';     // key de la tarjeta con el detalle abierto
let colMovil=localStorage.getItem('bs-tablero-col')||'medir';
let ocupado=false;  // hay un guardado en curso
let fechaKey='';    // tarjeta a la que se le está eligiendo fecha
let drag=null;      // arrastre en curso

const $=id=>document.getElementById(id);
const esc=s=>String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const DIAS=['dom','lun','mar','mié','jue','vie','sáb'];

function partes(iso){const p=iso.split('-').map(Number);return new Date(p[0],p[1]-1,p[2]);}
function ddmm(iso){const p=iso.split('-');return p[2]+'/'+p[1];}
function fechaCorta(iso){return DIAS[partes(iso).getDay()]+' '+ddmm(iso);}
function lunesDe(iso){ // lunes de la semana de esa fecha, YYYY-MM-DD
  const d=partes(iso);
  d.setDate(d.getDate()-((d.getDay()+6)%7));
  return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
}
function nombreCorto(n){
  const t=String(n||'').trim().split(/\s+/).filter(Boolean);
  if(t.length<2||!/[a-záéíóúñ]/i.test(t[0]))return t.join(' ');
  return t[0]+' '+t[t.length-1].charAt(0).toUpperCase()+'.';
}
function plataSena(sn){
  return (sn.moneda==='USD'?'USD ':'$ ')+Number(sn.monto).toLocaleString('es-AR',{maximumFractionDigits:2});
}
function leerMonto(txt){ // "250.000" → 250000 · "1.250,50" → 1250.5 · "1500.5" → 1500.5
  let t=String(txt).replace(/[^\d.,]/g,'');
  if(t.indexOf(',')>=0)t=t.replace(/\./g,'').replace(',','.');
  else if(/^\d{1,3}(\.\d{3})+$/.test(t))t=t.replace(/\./g,'');
  const n=parseFloat(t);
  return isFinite(n)?n:0;
}
function waLink(cel){
  let d=String(cel||'').replace(/\D/g,'');
  if(d.length<8)return '';
  if(d.indexOf('54')!==0)d='549'+d.replace(/^0/,'');
  return 'https://wa.me/'+d;
}
function toast(msg){
  const t=$('toast');t.textContent=msg;t.classList.add('show');
  clearTimeout(toast._t);toast._t=setTimeout(()=>t.classList.remove('show'),4500);
}

async function pedir(url,body){
  const opt=body?{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)}:{};
  opt.credentials='same-origin';
  const r=await fetch(url,opt);
  if(r.status===401){location.href='/calculadora/login.php';throw new Error('Sesión vencida');}
  let j=null;
  try{j=await r.json();}catch(e){}
  if(!j||!j.ok)throw new Error((j&&j.error)||'No se pudo conectar con el servidor');
  return j;
}

async function cargar(){
  try{
    const j=await pedir(API);
    if(drag)return; // no redibujar con una tarjeta en el aire
    ETAPAS=j.etapas;CARDS=j.cards;HOY=j.hoy;
    dibujar();
  }catch(e){
    if(!CARDS.length)$('board').innerHTML='<div class="aviso" style="grid-column:1/-1">'+esc(e.message)+'</div>';
    else toast(e.message);
  }
}

const etapaDe=id=>ETAPAS.find(e=>e.id===id);
const cardDe=key=>CARDS.find(c=>c.key===key);
const columnas=()=>ETAPAS.filter(e=>!e.aparte); // las 4, en orden
function faltanDe(c,e){return e.checks.filter(k=>!c.checks[k.id]).map(k=>k.label);}
function atrasada(c){
  const e=etapaDe(c.etapa);
  return !!(c.fecha&&c.fecha<HOY&&e.cierra&&!c.checks[e.cierra]);
}
// Qué falta para llevar la tarjeta a esa etapa (misma regla que el server):
// hacia atrás o hacia Retiro, nada; hacia adelante, los tildes de todo lo de atrás.
function faltaPara(c,destino){
  const cols=columnas();
  const i=cols.findIndex(e=>e.id===c.etapa),j=cols.findIndex(e=>e.id===destino);
  if(j<0||(i>=0&&j<i))return [];
  let f=[];
  for(let n=0;n<j;n++)f=f.concat(faltanDe(c,cols[n]));
  return f;
}

function htmlCard(c){
  const e=etapaDe(c.etapa);
  const cols=columnas();
  const idx=cols.indexOf(e);
  const sig=idx>=0?cols[idx+1]:null;
  let h='<article class="card'+(atrasada(c)?' alerta':'')+'" data-key="'+esc(c.key)+'">';

  h+='<div class="c-id"><b>'+esc(c.cliente_nro)+'-'+c.sub+'</b> · '+esc(nombreCorto(c.nombre)||'Sin nombre');
  if(c.zona)h+=' <span class="zona">· '+esc(c.zona)+'</span>';
  h+='</div>';
  const mat=[c.material,c.concepto].filter(Boolean).join(' · ');
  if(mat)h+='<div class="c-mat">'+esc(mat)+'</div>';

  if(e.fecha){
    let cls='',txt;
    if(!c.fecha){cls=' vacia';txt='+ Fecha de '+e.fecha.toLowerCase();}
    else if(atrasada(c)){cls=' atrasada';txt=e.fecha+' '+fechaCorta(c.fecha)+' · atrasada';}
    else if(c.fecha===HOY){cls=' hoy';txt=e.fecha+' · hoy';}
    else txt=e.fecha+' · '+fechaCorta(c.fecha);
    h+='<button type="button" class="c-fecha'+cls+'" data-act="fecha">'+esc(txt)+'</button>';
  }
  // Semana estimada de entrega: guía, no alarma.
  if(c.semana&&c.etapa!=='archivar'){
    const esta=lunesDe(HOY);
    const cls=c.semana<esta?' vencida':(c.semana===esta?' cerca':'');
    const txt=c.semana===esta?'esta semana':'semana del '+ddmm(c.semana)+(c.semana<esta?' · ya pasó':'');
    h+='<div class="c-promesa'+cls+'">Entrega estimada: '+esc(txt)+'</div>';
  }
  if(c.nota)h+='<div class="c-nota">'+esc(c.nota)+'</div>';

  h+='<div class="c-checks">';
  e.checks.forEach(k=>{
    const on=!!c.checks[k.id];
    let label=k.label;
    if(k.id==='sena'&&on&&c.sena)label+=' · '+plataSena(c.sena);
    h+='<button type="button" class="chk'+(on?' on':'')+'" data-act="check" data-clave="'+esc(k.id)+'" aria-pressed="'+on+'">'+esc(label)+'</button>';
  });
  h+='</div>';

  const falta=faltanDe(c,e).length>0;
  h+='<button type="button" class="c-avanzar'+(falta?' falta':'')+'" data-act="'+(sig?'avanzar':'archivar')+'">'+(sig?'Siguiente: '+esc(sig.titulo)+' →':'Archivar obra')+'</button>';

  const abre=abierta===c.key;
  h+='<button type="button" class="c-mas" data-act="mas" aria-expanded="'+abre+'">'+(abre?'Menos':'Más')+'</button>';
  if(abre){
    const wa=waLink(c.celular);
    h+='<div class="c-det">';
    if(c.celular)h+='<div class="fila"><span>Teléfono</span><div>'+(wa?'<a href="'+esc(wa)+'" target="_blank" rel="noopener">'+esc(c.celular)+'</a>':esc(c.celular))+'</div></div>';
    if(c.direccion)h+='<div class="fila"><span>Dirección</span><div><a href="https://www.google.com/maps/search/?api=1&query='+encodeURIComponent(c.direccion)+'" target="_blank" rel="noopener">'+esc(c.direccion)+'</a></div></div>';
    h+='<input type="text" maxlength="140" data-nota placeholder="Nota (ej: falta la bacha)" value="'+esc(c.nota)+'">';
    h+='<div class="acciones">';
    if(c.tiene_detalle)h+='<a href="/calculadora/api/render-pdf.php?nro='+encodeURIComponent(c.cliente_nro)+'&sub='+c.sub+'" target="_blank" rel="noopener">Ver presupuesto</a>';
    if(e.aparte)h+='<button type="button" class="lnk" data-act="atras">Volver a las columnas</button>';
    else{
      if(idx>0)h+='<button type="button" class="lnk" data-act="atras">Volver a '+esc(cols[idx-1].titulo)+'</button>';
      h+='<button type="button" class="lnk" data-act="retiro">Retira en el local</button>';
    }
    h+='</div></div>';
  }
  return h+'</article>';
}

function dibujar(){
  if(!ETAPAS.length)return;
  if(!etapaDe(colMovil))colMovil=ETAPAS[0].id;

  let tabs='',cols='',aparte='';
  const ultima=columnas().length-1;
  ETAPAS.forEach(e=>{
    const lista=CARDS.filter(c=>c.etapa===e.id).sort((a,b)=>{
      if(!!a.fecha!==!!b.fecha)return a.fecha?-1:1;          // con turno primero
      if(a.fecha!==b.fecha)return a.fecha<b.fecha?-1:1;      // el turno más próximo arriba
      if(a.desde!==b.desde)return a.desde<b.desde?-1:1;      // después, la cola: el que entró antes
      return a.key<b.key?-1:1;
    });
    const on=e.id===colMovil;
    const corto=e.aparte?'Retiro':e.titulo.replace('Por ','').replace(/^./,x=>x.toUpperCase());
    tabs+='<button type="button" class="'+(on?'on':'')+'" data-col="'+esc(e.id)+'">'+esc(corto)+'<i>'+lista.length+'</i></button>';
    let h='<section class="col'+(on?' on':'')+'" data-col="'+esc(e.id)+'">';
    h+='<div class="col-h"><h2>'+esc(e.titulo)+'</h2><span class="col-n">'+lista.length+'</span></div>';
    if(!e.aparte&&!e.fecha&&columnas().indexOf(e)<ultima)h+='<div class="col-sub">En orden de llegada</div>';
    const cards=lista.map(htmlCard).join('');
    if(e.aparte)h+=lista.length?'<div class="cards">'+cards+'</div>':'<div class="col-vacia">Nadie retira por el local</div>';
    else h+=lista.length?cards:'<div class="col-vacia">'+(CARDS.length?'Nada acá':(columnas().indexOf(e)===0?'Las obras aparecen solas acá cuando marcás un presupuesto como Aprobado.':''))+'</div>';
    h+='</section>';
    if(e.aparte)aparte+=h;else cols+=h;
  });
  $('colTabs').innerHTML=tabs;
  $('board').innerHTML=cols;
  $('aparte').innerHTML=aparte;
}

// Guarda un cambio: primero lo aplica en pantalla, después lo confirma el server.
async function guardar(c,body,aplicar){
  if(ocupado)return;
  ocupado=true;
  aplicar();
  dibujar();
  try{
    await pedir(API,Object.assign({cliente_nro:c.cliente_nro,sub:c.sub},body));
  }catch(e){
    toast(e.message);
  }
  ocupado=false;
  await cargar();
}

// Mover de etapa: lo usan el botón Siguiente, los links del detalle y el arrastre.
function mover(c,destino){
  if(!etapaDe(destino)||destino===c.etapa)return;
  const f=faltaPara(c,destino);
  if(f.length){toast('Falta: '+f.join(', '));return;}
  guardar(c,{accion:'mover',a:destino},()=>{
    c.etapa=destino;c.fecha='';c.desde=new Date().toISOString();
  });
}

// === Eventos ===
const wrap=document.querySelector('.wrap');

$('colTabs').addEventListener('click',ev=>{
  const b=ev.target.closest('button[data-col]');
  if(!b)return;
  colMovil=b.dataset.col;
  localStorage.setItem('bs-tablero-col',colMovil);
  dibujar();
  window.scrollTo({top:0});
});

wrap.addEventListener('click',ev=>{
  const b=ev.target.closest('[data-act]');
  if(!b)return;
  const art=b.closest('.card');
  const c=art&&cardDe(art.dataset.key);
  if(!c)return;
  const act=b.dataset.act;
  const cols=columnas();
  const idx=cols.findIndex(e=>e.id===c.etapa);

  if(act==='mas'){
    abierta=abierta===c.key?'':c.key;
    dibujar();
  }else if(act==='check'){
    const clave=b.dataset.clave,valor=!c.checks[clave];
    if(clave==='sena'&&valor){pedirSena(c);return;}
    guardar(c,{accion:'check',clave:clave,valor:valor},()=>{
      if(valor)c.checks[clave]=true;else delete c.checks[clave];
      if(clave==='sena')c.sena=null;
    });
  }else if(act==='avanzar'){
    const f=faltanDe(c,etapaDe(c.etapa));
    if(f.length){toast('Falta: '+f.join(', '));return;}
    mover(c,cols[idx+1].id);
  }else if(act==='atras'){
    if(idx>0)mover(c,cols[idx-1].id);
    else guardar(c,{accion:'mover',hacia:'atras'},()=>{}); // desde Retiro: el server sabe de dónde vino
  }else if(act==='retiro'){
    mover(c,'retiro');
  }else if(act==='fecha'){
    const dp=$('dp');
    const r=b.getBoundingClientRect();
    dp.style.left=(r.left+window.scrollX)+'px';
    dp.style.top=(r.bottom+window.scrollY)+'px';
    fechaKey=c.key;
    dp.value=c.fecha||'';
    try{dp.showPicker();}catch(e){dp.style.pointerEvents='auto';dp.focus();dp.click();}
  }else if(act==='archivar'){
    const f=faltanDe(c,etapaDe(c.etapa));
    if(f.length){toast('Falta: '+f.join(', '));return;}
    pedirArchivar(c);
  }
});

$('dp').addEventListener('change',()=>{
  const c=cardDe(fechaKey);
  if(!c)return;
  const f=$('dp').value;
  if(f===c.fecha)return;
  guardar(c,{accion:'fecha',fecha:f},()=>{c.fecha=f;});
});

// La nota se guarda al salir del campo o con Enter.
wrap.addEventListener('change',ev=>{
  const inp=ev.target.closest('input[data-nota]');
  if(!inp)return;
  const c=cardDe(inp.closest('.card').dataset.key);
  if(!c)return;
  const nota=inp.value.trim();
  if(nota===c.nota)return;
  guardar(c,{accion:'nota',nota:nota},()=>{c.nota=nota;});
});
wrap.addEventListener('keydown',ev=>{
  if(ev.key==='Enter'&&ev.target.matches('input[data-nota]'))ev.target.blur();
});

// ============================================================
// Arrastrar tarjetas
//   Mouse: se arrastra directo. Dedo: mantener apretado un instante
//   (así no se confunde con deslizar la pantalla). En el celular se
//   suelta sobre la pestaña de la etapa.
// ============================================================
function dragZona(x,y){
  const el=document.elementFromPoint(x,y);
  return el?el.closest('.col[data-col],#colTabs button[data-col]'):null;
}
function dragMarcar(zona){
  if(drag.zona===zona)return;
  if(drag.zona)drag.zona.classList.remove('drop');
  drag.zona=zona;
  if(zona&&zona.dataset.col!==drag.c.etapa)zona.classList.add('drop');
}
function dragEmpezar(){
  const r=drag.art.getBoundingClientRect();
  drag.dx=drag.x-r.left;drag.dy=drag.y-r.top;
  const g=drag.art.cloneNode(true);
  g.classList.add('fantasma');
  g.style.width=r.width+'px';
  document.body.appendChild(g);
  drag.fantasma=g;
  drag.activo=true;
  drag.art.classList.add('arrastrando');
  document.body.classList.add('arrastre');
  dragSeguir(drag.x,drag.y);
  requestAnimationFrame(dragBorde);
}
function dragSeguir(x,y){
  drag.fantasma.style.transform='translate('+(x-drag.dx)+'px,'+(y-drag.dy)+'px) rotate(1.5deg)';
  dragMarcar(dragZona(x,y));
}
// Con la tarjeta quieta cerca del borde de la pantalla, la página acompaña
// (así se llega al cuadro de Retiro aunque quede abajo).
function dragBorde(){
  if(!drag||!drag.activo)return;
  const paso=drag.y<70?-12:(drag.y>window.innerHeight-70?12:0);
  if(paso){window.scrollBy(0,paso);dragMarcar(dragZona(drag.x,drag.y));}
  requestAnimationFrame(dragBorde);
}
function dragSoltar(cancelar){
  const d=drag;
  if(!d)return;
  drag=null;
  clearTimeout(d.espera);
  if(!d.activo)return;
  d.fantasma.remove();
  d.art.classList.remove('arrastrando');
  document.body.classList.remove('arrastre');
  if(d.zona)d.zona.classList.remove('drop');
  if(!cancelar&&d.zona)mover(d.c,d.zona.dataset.col);
}

wrap.addEventListener('pointerdown',ev=>{
  if(drag||ocupado||(ev.pointerType==='mouse'&&ev.button!==0))return;
  const art=ev.target.closest('.card');
  if(!art||ev.target.closest('button,a,input,.c-det'))return;
  const c=cardDe(art.dataset.key);
  if(!c)return;
  drag={c:c,art:art,id:ev.pointerId,x:ev.clientX,y:ev.clientY,activo:false,zona:null,dedo:ev.pointerType!=='mouse'};
  if(drag.dedo)drag.espera=setTimeout(()=>{if(drag&&!drag.activo){dragEmpezar();if(navigator.vibrate)navigator.vibrate(12);}},320);
});
document.addEventListener('pointermove',ev=>{
  if(!drag||ev.pointerId!==drag.id)return;
  const lejos=Math.abs(ev.clientX-drag.x)+Math.abs(ev.clientY-drag.y)>8;
  if(!drag.activo){
    if(drag.dedo){if(lejos)dragSoltar(true);return;} // se movió antes de tiempo: era un deslizamiento
    if(!lejos)return;
    dragEmpezar();
  }
  drag.x=ev.clientX;drag.y=ev.clientY;
  dragSeguir(ev.clientX,ev.clientY);
});
document.addEventListener('pointerup',ev=>{if(drag&&ev.pointerId===drag.id)dragSoltar(false);});
document.addEventListener('pointercancel',ev=>{if(drag&&ev.pointerId===drag.id)dragSoltar(true);});
// Con una tarjeta en el aire, el dedo no desliza la página ni abre el menú del navegador.
document.addEventListener('touchmove',ev=>{if(drag&&drag.activo)ev.preventDefault();},{passive:false});
document.addEventListener('contextmenu',ev=>{if(drag)ev.preventDefault();});

// ============================================================
// Modal (seña y archivar)
// ============================================================
let modalOk=null;
function abrirModal(titulo,cuerpo,boton,alAceptar){
  $('mTitulo').textContent=titulo;
  $('mCuerpo').innerHTML=cuerpo;
  $('mOk').textContent=boton;
  modalOk=alAceptar;
  $('modalBg').classList.add('show');
}
function cerrarModal(){$('modalBg').classList.remove('show');modalOk=null;}
$('mCancelar').addEventListener('click',cerrarModal);
$('modalBg').addEventListener('click',ev=>{if(ev.target===$('modalBg'))cerrarModal();});
document.addEventListener('keydown',ev=>{
  if(ev.key==='Escape'){if(drag)dragSoltar(true);cerrarModal();}
  else if(ev.key==='Enter'&&modalOk&&$('modalBg').classList.contains('show')){ev.preventDefault();$('mOk').click();}
});
$('mOk').addEventListener('click',()=>{if(modalOk)modalOk();});

// Seña: no alcanza con tildar, hay que cargar cuánto se cobró.
function pedirSena(c){
  let moneda='ARS';
  abrirModal('Seña de '+c.cliente_nro+'-'+c.sub+' · '+(nombreCorto(c.nombre)||'Sin nombre'),
    '<p>¿Cuánto se cobró?</p><div class="sena"><input type="text" id="senaMonto" inputmode="decimal" autocomplete="off" placeholder="Monto"><button type="button" data-mon="ARS" class="on">$</button><button type="button" data-mon="USD">USD</button></div>',
    'Guardar seña',
    ()=>{
      const monto=leerMonto($('senaMonto').value);
      if(!(monto>0)){$('senaMonto').focus();toast('Cargá el monto de la seña.');return;}
      cerrarModal();
      guardar(c,{accion:'check',clave:'sena',valor:true,monto:monto,moneda:moneda},()=>{
        c.checks.sena=true;c.sena={monto:monto,moneda:moneda};
      });
    });
  $('mCuerpo').querySelectorAll('[data-mon]').forEach(b=>b.addEventListener('click',()=>{
    moneda=b.dataset.mon;
    $('mCuerpo').querySelectorAll('[data-mon]').forEach(x=>x.classList.toggle('on',x===b));
    $('senaMonto').focus();
  }));
  $('senaMonto').focus();
}

// Archivar: el presupuesto pasa a "Entregado" en el hub. No borra nada.
function pedirArchivar(c){
  abrirModal('Archivar '+c.cliente_nro+'-'+c.sub+' · '+(nombreCorto(c.nombre)||'Sin nombre'),
    '<p>La obra sale del tablero y el presupuesto pasa a <b>Entregado</b>.</p><p>No se borra nada: queda guardado 6 meses.</p>',
    'Archivar',
    async()=>{
      if(ocupado)return;
      ocupado=true;
      cerrarModal();
      try{
        await pedir(API_ESTADO,{cliente_nro:c.cliente_nro,sub:c.sub,nuevo_estado:'entregado',conservar_hermanas:true});
      }catch(e){
        toast(e.message);
      }
      ocupado=false;
      await cargar();
    });
  $('mOk').focus();
}

// === Refresco: al volver a la pestaña y cada minuto, si nadie está trabajando ===
function libre(){
  const a=document.activeElement;
  return !ocupado&&!drag&&!$('modalBg').classList.contains('show')&&!(a&&a.matches&&a.matches('input[data-nota]'));
}
document.addEventListener('visibilitychange',()=>{if(!document.hidden&&libre())cargar();});
setInterval(()=>{if(!document.hidden&&libre())cargar();},60000);

cargar();
</script>
</body>
</html>
