<?php
// ============================================================
// /presupuestos/calendario.php — Calendario de obras
// ============================================================
// La vista de halcón del tablero: las mismas obras, puestas en el mes.
// Muestra mediciones, instalaciones, retiros y el día en que vence el
// plazo de cada obra. Desde acá se agenda y se reagenda (tocando o
// arrastrando) y se cambia el plazo de entrega. Todo lo que se cambia
// acá es el mismo dato del tablero: no hay nada duplicado.
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
<title>BlackStones · Calendario de obras</title>
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

.wrap{max-width:1480px;margin:0 auto;padding:20px 20px 60px}
.aviso{color:var(--text2);font-size:13.5px;padding:40px 16px;text-align:center;line-height:1.6}

/* === COLOR POR TIPO (los mismos de las columnas del tablero) === */
.et-medir{--et:#2F6DB5;--et-soft:rgba(47,109,181,.11)}
.et-instalar{--et:#7A52B3;--et-soft:rgba(122,82,179,.11)}
.et-retiro{--et:#14808A;--et-soft:rgba(20,128,138,.11)}
[data-theme="dark"] .et-medir{--et:#6AA5E8;--et-soft:rgba(106,165,232,.14)}
[data-theme="dark"] .et-instalar{--et:#B594E6;--et-soft:rgba(181,148,230,.14)}
[data-theme="dark"] .et-retiro{--et:#4FC4C4;--et-soft:rgba(79,196,196,.13)}

/* === BARRA: mes, qué se ve, fecha de hoy === */
.barra{display:flex;align-items:center;flex-wrap:wrap;gap:10px 16px;margin-bottom:14px}
.mes{display:flex;align-items:center;gap:6px}
.mes h2{font-family:'Fraunces',serif;font-size:22px;font-weight:600;min-width:200px;text-align:center}
.mes button{height:34px;min-width:34px;padding:0 10px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--card);font-size:16px;font-weight:700;color:var(--text2);cursor:pointer}
.mes button:hover{border-color:var(--gd);color:var(--text)}
.mes .hoy-b{font-size:13px;margin-left:4px}
.ley{display:flex;flex-wrap:wrap;gap:6px}
.ley button{display:inline-flex;align-items:center;gap:6px;padding:6px 11px;border:1px solid var(--border);border-radius:999px;background:var(--card);font-size:12.5px;font-weight:700;color:var(--text3);cursor:pointer}
.ley button::before{content:'';width:10px;height:10px;border-radius:3px;background:var(--border)}
.ley button b{font-variant-numeric:tabular-nums;opacity:.75}
.ley button.on{color:var(--text)}
.ley button.on::before{background:var(--et,var(--red))}
.ley button.on[data-ver="vence"]::before{background:none;border:1.5px dashed var(--text3)}
.ley button:not(.on){text-decoration:line-through}
.hoy{margin-left:auto;font-size:14px;color:var(--text2);white-space:nowrap}
.hoy b{font-weight:700;color:var(--text)}

/* === PLANO: el mes + lo que falta agendar === */
.plano{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:16px;align-items:start}
.cal{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow)}
.cal .dh{padding:8px 8px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text3);background:var(--card-alt);border-bottom:1px solid var(--border)}
.dia{min-height:124px;padding:6px 6px 8px;border-right:1px solid var(--border-soft);border-bottom:1px solid var(--border-soft);min-width:0}
.dia:nth-child(7n){border-right:none}
.dia .n{font-size:13px;font-weight:700;color:var(--text2);display:inline-flex;align-items:center;justify-content:center;min-width:24px;height:24px;border-radius:999px;padding:0 6px}
.dia.finde{background:var(--card-alt)}
.dia.pasado .n{color:var(--text3);font-weight:600}
.dia.otro{opacity:.4}
.dia.es-hoy{box-shadow:inset 0 0 0 2px var(--gd)}
.dia.es-hoy .n{background:var(--dk);color:var(--cr)}
[data-theme="dark"] .dia.es-hoy .n{background:var(--gd);color:#1A1816}
.dia.drop{background:var(--gd-soft);box-shadow:inset 0 0 0 2px var(--gd)}

/* === EVENTO === */
.ev{display:block;width:100%;margin-top:4px;padding:5px 7px;border:1px solid transparent;border-left:3px solid var(--et);border-radius:var(--radius-sm);background:var(--et-soft);color:var(--text);font-size:12px;font-weight:600;text-align:left;cursor:pointer;overflow-wrap:anywhere;line-height:1.35}
.ev:hover{border-color:var(--et);border-left-color:var(--et)}
.ev i{font-style:normal;font-weight:700;font-variant-numeric:tabular-nums;color:var(--et)}
.ev span{color:var(--text3);font-weight:500}
.ev em{font-style:normal;display:inline-flex;align-items:center;justify-content:center;width:15px;height:15px;margin-right:4px;border-radius:50%;background:var(--red);color:var(--card);font-size:10.5px;font-weight:800;vertical-align:-2px}
.ev.atras{background:var(--red-soft);border-left-color:var(--red)}
.ev.atras i{color:var(--red)}
.ev.arr{opacity:.4}
/* Vencimiento del plazo: es un límite, no un turno. Va sin relleno y con borde punteado. */
.ev.vence{--et:var(--text3);background:none;border:1px dashed var(--border);border-left:1px dashed var(--border);color:var(--text2);font-weight:600}
.ev.vence:hover{border-color:var(--text3)}
.ev.vence.cerca{border-color:var(--amb);color:var(--amb);background:var(--amb-soft)}
.ev.vence.vencida{border-color:var(--red);color:var(--red);background:var(--red-soft);font-weight:700}

/* === PENDIENTES === */
.pend{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);padding:14px;position:sticky;top:12px;max-height:calc(100vh - 24px);overflow-y:auto}
.pend h3{font-family:'Fraunces',serif;font-size:17px;font-weight:600;margin-bottom:2px}
.pend .ayuda{font-size:12px;color:var(--text3);margin-bottom:6px}
.pend h4{font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--text2);margin:14px 0 2px;display:flex;align-items:center;gap:6px}
.pend h4.rojo{color:var(--red)}
.pend h4 b{font-variant-numeric:tabular-nums;opacity:.7}
.pend .ok{font-size:13px;color:var(--gr);font-weight:600;margin-top:10px}

/* === AGENDA (celular): el mismo mes, en lista === */
.agenda{display:none}
.ag-dia{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:10px 12px 12px;margin-bottom:10px}
.ag-dia .n{font-size:13px;font-weight:700;color:var(--text2)}
.ag-dia.es-hoy{border-color:var(--gd);box-shadow:inset 0 0 0 1px var(--gd)}
.ag-dia.es-hoy .n{color:var(--text)}
.ag-dia.pasado{opacity:.75}
.ag-dia .nada{font-size:12.5px;color:var(--text3);margin-top:4px}
.agenda .ev,.pend .ev{padding:8px 10px;font-size:13px;margin-top:6px}

/* === MODAL + TOAST (como el tablero) === */
.modal-bg{position:fixed;inset:0;background:rgba(10,9,7,.55);display:none;align-items:center;justify-content:center;padding:16px;z-index:900}
.modal-bg.show{display:flex}
.modal{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-lg);padding:20px;max-width:420px;width:100%;max-height:calc(100vh - 32px);overflow-y:auto}
.modal h3{font-family:'Fraunces',serif;font-size:19px;font-weight:600;margin-bottom:4px}
.modal .sub{color:var(--text2);font-size:13px;line-height:1.5}
.modal .sub.estado{font-weight:700;margin-bottom:2px}
.modal .campo{display:block;margin-top:16px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--text2)}
.modal .fila{display:flex;gap:8px;margin-top:5px}
.modal input{flex:1 1 130px;min-width:0;width:100%;padding:11px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--card);color:var(--text);font-family:inherit;font-size:16px;font-weight:600;outline:none}
.modal input:focus{border-color:var(--gd);box-shadow:0 0 0 3px var(--gd-soft)}
.modal .nota{font-size:12.5px;color:var(--text3);margin-top:6px;line-height:1.5}
.modal .nota.rojo{color:var(--red);font-weight:700}
.modal .lnk{background:none;border:none;padding:0;font-size:12.5px;font-weight:700;color:var(--text2);text-decoration:underline;cursor:pointer}
.modal .ir{display:inline-block;margin-top:16px;font-size:13px;font-weight:700;color:var(--text2)}
.modal .btns{display:flex;gap:10px;justify-content:flex-end;margin-top:16px}
.modal .btns button{padding:9px 16px;border-radius:var(--radius-sm);font-size:13.5px;font-weight:700;cursor:pointer;border:1px solid var(--border);background:var(--card)}
.modal .btns .ok{background:var(--dk);color:var(--cr);border-color:var(--dk)}
[data-theme="dark"] .modal .btns .ok{background:var(--gd);color:#1A1816;border-color:var(--gd)}
.toast{position:fixed;left:50%;bottom:24px;transform:translate(-50%,20px);background:var(--dk);color:var(--cr);padding:11px 16px;border-radius:var(--radius-sm);border-left:3px solid var(--red);box-shadow:var(--shadow-lg);font-size:13px;font-weight:600;opacity:0;pointer-events:none;transition:opacity .2s,transform .2s;z-index:1000;max-width:calc(100vw - 32px)}
.toast.show{opacity:1;transform:translate(-50%,0)}
.toast.ok{border-left-color:var(--gr)}

@media(max-width:1100px){
  .plano{grid-template-columns:minmax(0,1fr)}
  .pend{order:-1;position:static;max-height:none}
}
@media(max-width:900px){
  .header{padding:14px 16px}
  .header .h-right>span{display:none}
  .h-tabs{padding:0 8px}
  .wrap{padding:12px 12px 60px}
  .barra{gap:10px}
  .hoy{order:-1;width:100%;margin-left:0}
  .mes{width:100%}
  .mes h2{flex:1;min-width:0;font-size:20px}
  .mes button{height:40px;min-width:40px}
  .cal{display:none}
  .agenda{display:block}
}
</style>
</head>
<body>

<div class="header">
  <div style="display:flex;align-items:center;gap:12px">
    <img src="../logo/logo_isotipo.png" alt="BlackStones" style="height:36px;width:auto">
    <div><h1>BlackStones</h1><span>Calendario de obras</span></div>
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
  <a href="/presupuestos/tablero.php">Tablero</a>
  <a href="/presupuestos/calendario.php" class="active">Calendario</a>
</nav>
<div class="gold-line"></div>

<div class="wrap">
  <div class="barra">
    <div class="mes">
      <button type="button" id="mesAnt" aria-label="Mes anterior">‹</button>
      <h2 id="mesTxt"></h2>
      <button type="button" id="mesSig" aria-label="Mes siguiente">›</button>
      <button type="button" class="hoy-b" id="mesHoy">Hoy</button>
    </div>
    <div class="ley" id="ley"></div>
    <div class="hoy" id="hoyTxt"></div>
  </div>
  <div class="plano">
    <div>
      <div class="cal" id="cal"><div class="aviso" style="grid-column:1/-1">Cargando…</div></div>
      <div class="agenda" id="agenda"></div>
    </div>
    <aside class="pend" id="pend"></aside>
  </div>
</div>

<div class="modal-bg" id="modalBg">
  <div class="modal" role="dialog" aria-modal="true">
    <h3 id="mTitulo"></h3>
    <div id="mCuerpo"></div>
    <div class="btns">
      <button type="button" id="mCancelar">Cancelar</button>
      <button type="button" class="ok" id="mOk">Guardar</button>
    </div>
  </div>
</div>
<div class="toast" id="toast"></div>

<script>
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

// ============================================================
// Calendario de obras
// ============================================================
const API='/calculadora/api/tablero.php';

let ETAPAS=[];   // definición de etapas (viene del server)
let CARDS=[];    // las obras del tablero
let HOY='';      // YYYY-MM-DD según el server
let mes='';      // mes a la vista, 'YYYY-MM'
let ocupado=false;
let arrastre=null; // {key,tipo} del evento que se está arrastrando
let ver={};      // tipos apagados en la leyenda: {vence:false}
try{ver=JSON.parse(localStorage.getItem('bs-cal-ver')||'{}')||{};}catch(e){ver={};}

const $=id=>document.getElementById(id);
const esc=s=>String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const DIAS=['dom','lun','mar','mié','jue','vie','sáb'];
const DIAS_L=['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
const MESES=['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
const PLURAL={'Medición':'Mediciones','Instalación':'Instalaciones','Retiro':'Retiros'};
function partes(iso){const p=iso.split('-').map(Number);return new Date(p[0],p[1]-1,p[2]);}
function isoDe(d){return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');}
function masDias(iso,n){const d=partes(iso);d.setDate(d.getDate()+n);return isoDe(d);}
function ddmm(iso){const p=iso.split('-');return p[2]+'/'+p[1];}
function fechaCorta(iso){return DIAS[partes(iso).getDay()]+' '+ddmm(iso);}
function fechaLarga(iso){const d=partes(iso);return DIAS_L[d.getDay()]+' '+d.getDate()+' de '+MESES[d.getMonth()];}
function nombreCorto(n){
  const t=String(n||'').trim().split(/\s+/).filter(Boolean);
  if(t.length<2||!/[a-záéíóúñ]/i.test(t[0]))return t.join(' ');
  return t[0]+' '+t[t.length-1].charAt(0).toUpperCase()+'.';
}
function toast(msg,ok){
  const t=$('toast');t.textContent=msg;t.classList.toggle('ok',!!ok);t.classList.add('show');
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

const etapaDe=id=>ETAPAS.find(e=>e.id===id);
const cardDe=key=>CARDS.find(c=>c.key===key);
const quien=c=>c.cliente_nro+' '+(nombreCorto(c.nombre)||'Sin nombre');

// Mismas reglas que el tablero.
function cumplida(c){const e=etapaDe(c.etapa);return !!(e&&e.cierra&&c.checks[e.cierra]);} // ya se midió / ya se retiró
function atrasada(c){const e=etapaDe(c.etapa);return !!(e&&e.fecha&&c.fecha&&c.fecha<HOY&&!cumplida(c));}
function estadoPlazo(c){ // '' | 'cerca' (vence hoy o en los próximos 6 días) | 'vencida'
  if(!c.limite||c.etapa==='archivar')return '';
  return c.limite<HOY?'vencida':(c.limite<=masDias(HOY,6)?'cerca':'');
}
// Instalaciones que se pisan: mismo día, un instalador en común y menos de 2 horas entre una y otra.
const CHOQUE_MIN=120;
let CHOQUES={};
function minutos(h){const m=/^(\d\d):(\d\d)$/.exec(h||'');return m?m[1]*60+(+m[2]):null;}
function calcularChoques(){
  CHOQUES={};
  const l=CARDS.filter(c=>etapaDe(c.etapa).exige_fecha&&c.fecha&&c.fecha>=HOY&&c.hora&&c.equipo.length);
  const anotar=(a,b,q)=>{(CHOQUES[a.key]=CHOQUES[a.key]||[]).push(q.join(', ')+(q.length>1?' también instalan ':' también instala ')+b.hora+' hs · '+quien(b));};
  for(let i=0;i<l.length;i++)for(let j=i+1;j<l.length;j++){
    const a=l[i],b=l[j];
    if(a.fecha!==b.fecha||Math.abs(minutos(a.hora)-minutos(b.hora))>=CHOQUE_MIN)continue;
    const q=a.equipo.filter(x=>b.equipo.indexOf(x)>=0);
    if(q.length){anotar(a,b,q);anotar(b,a,q);}
  }
}
// Lo que hay que mirar en un turno: está atrasado, le falta la hora, quedó después del plazo, o se pisa con otro.
function avisosDe(c){
  const e=etapaDe(c.etapa),out=[];
  if(!e.fecha||!c.fecha||cumplida(c))return out;
  if(atrasada(c))out.push('Atrasada: era el '+fechaCorta(c.fecha));
  if(e.exige_fecha&&e.pide_hora&&!c.hora)out.push('Falta la hora');
  if(e.exige_fecha&&c.limite&&c.fecha>c.limite)out.push('Queda después del plazo (vence el '+fechaCorta(c.limite)+')');
  (CHOQUES[c.key]||[]).forEach(t=>out.push(t));
  return out;
}

async function cargar(){
  try{
    const j=await pedir(API);
    if(arrastre)return;
    ETAPAS=j.etapas;CARDS=j.cards;HOY=j.hoy;
    if(!mes)mes=HOY.slice(0,7);
    dibujar();
  }catch(e){
    if(!CARDS.length)$('cal').innerHTML='<div class="aviso" style="grid-column:1/-1">'+esc(e.message)+'</div>';
    else toast(e.message);
  }
}

// Todos los eventos, por día: {iso:[{tipo,c}]}. tipo = id de etapa (turno) o 'vence' (plazo).
function armar(){
  const dias={};
  const sumar=(d,ev)=>{(dias[d]=dias[d]||[]).push(ev);};
  CARDS.forEach(c=>{
    const e=etapaDe(c.etapa);
    if(!e)return;
    if(e.fecha&&c.fecha&&!cumplida(c)&&ver[c.etapa]!==false)sumar(c.fecha,{tipo:c.etapa,c:c});
    if(c.limite&&c.etapa!=='archivar'&&ver.vence!==false)sumar(c.limite,{tipo:'vence',c:c});
  });
  Object.keys(dias).forEach(d=>dias[d].sort((a,b)=>{
    if((a.tipo==='vence')!==(b.tipo==='vence'))return a.tipo==='vence'?1:-1; // los turnos arriba, los vencimientos abajo
    const ha=a.tipo==='vence'?'':a.c.hora,hb=b.tipo==='vence'?'':b.c.hora;
    if(ha!==hb){if(!ha)return 1;if(!hb)return -1;return ha<hb?-1:1;}
    return a.c.key<b.c.key?-1:1;
  }));
  return dias;
}

// largo=true: dice también qué es ("Medición · …"). En la grilla alcanza con el color.
function chip(ev,largo){
  const c=ev.c;
  const nom='<b>'+esc(c.cliente_nro)+'</b> '+esc(nombreCorto(c.nombre)||'Sin nombre');
  if(ev.tipo==='vence'){
    const p=estadoPlazo(c);
    const t='Plazo de entrega: '+fechaCorta(c.limite)+(c.plazo_manual?' (modificado)':'')+' · '+etapaDe(c.etapa).titulo;
    return '<button type="button" class="ev vence'+(p?' '+p:'')+'" draggable="true" data-key="'+esc(c.key)+'" data-tipo="vence" title="'+esc(t)+'">'+(p==='vencida'?'Venció':'Vence')+' · '+nom+(largo?' <span>· '+esc(etapaDe(c.etapa).titulo)+'</span>':'')+'</button>';
  }
  const e=etapaDe(c.etapa),av=avisosDe(c);
  const t=e.fecha+(c.fecha?' '+fechaCorta(c.fecha):'')+(c.hora?' '+c.hora+' hs':'')+(av.length?' · '+av.join(' · '):'');
  return '<button type="button" class="ev et-'+esc(c.etapa)+(atrasada(c)?' atras':'')+'" draggable="true" data-key="'+esc(c.key)+'" data-tipo="'+esc(c.etapa)+'" title="'+esc(t)+'">'
    +(av.length?'<em>!</em>':'')+(largo?esc(e.fecha)+' · ':'')+(c.hora?'<i>'+esc(c.hora)+'</i> ':'')+nom+(c.zona?' <span>· '+esc(c.zona)+'</span>':'')
    +(largo&&av.length?'<br><span>'+esc(av.join(' · '))+'</span>':'')+'</button>';
}

function dibujar(){
  if(!ETAPAS.length)return;
  calcularChoques();
  $('hoyTxt').innerHTML='Hoy · <b>'+esc(fechaLarga(HOY))+'</b>';
  const y=+mes.slice(0,4),m=+mes.slice(5,7);
  $('mesTxt').textContent=MESES[m-1].replace(/^./,x=>x.toUpperCase())+' '+y;

  // Leyenda: qué es cada color, cuántos hay este mes, y tocarla lo oculta o lo muestra.
  const tipos=ETAPAS.filter(e=>e.fecha).map(e=>({id:e.id,txt:PLURAL[e.fecha]||e.fecha,n:CARDS.filter(c=>c.etapa===e.id&&c.fecha&&c.fecha.slice(0,7)===mes&&!cumplida(c)).length}));
  tipos.push({id:'vence',txt:'Vencimientos',n:CARDS.filter(c=>c.limite&&c.etapa!=='archivar'&&c.limite.slice(0,7)===mes).length});
  $('ley').innerHTML=tipos.map(t=>'<button type="button" class="'+(t.id==='vence'?'':'et-'+esc(t.id))+(ver[t.id]!==false?' on':'')+'" data-ver="'+esc(t.id)+'" aria-pressed="'+(ver[t.id]!==false)+'">'+esc(t.txt)+' <b>'+t.n+'</b></button>').join('');

  const dias=armar();

  // Grilla del mes: semanas de lunes a domingo.
  const primero=new Date(y,m-1,1),ultimo=new Date(y,m,0);
  const d=new Date(primero);d.setDate(1-((primero.getDay()+6)%7));
  let g=['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'].map(x=>'<div class="dh">'+x+'</div>').join('');
  while(d<=ultimo||d.getDay()!==1){
    const iso=isoDe(d),fuera=d.getMonth()!==m-1;
    g+='<div class="dia'+(fuera?' otro':'')+(iso===HOY?' es-hoy':'')+(iso<HOY?' pasado':'')+(d.getDay()===0||d.getDay()===6?' finde':'')+'" data-dia="'+iso+'"><div class="n">'+d.getDate()+'</div>'
      +(dias[iso]||[]).map(ev=>chip(ev,false)).join('')+'</div>';
    d.setDate(d.getDate()+1);
  }
  $('cal').innerHTML=g;

  // Agenda (celular): los días del mes que tienen algo, más hoy.
  let a='';
  for(let n=1;n<=ultimo.getDate();n++){
    const iso=mes+'-'+String(n).padStart(2,'0'),l=dias[iso]||[];
    if(!l.length&&iso!==HOY)continue;
    a+='<div class="ag-dia'+(iso===HOY?' es-hoy':'')+(iso<HOY?' pasado':'')+'" data-dia="'+iso+'"><div class="n">'+esc(fechaLarga(iso))+(iso===HOY?' · hoy':'')+'</div>'
      +(l.length?l.map(ev=>chip(ev,true)).join(''):'<div class="nada">Nada agendado.</div>')+'</div>';
  }
  $('agenda').innerHTML=a||'<div class="aviso">Nada agendado este mes.</div>';

  // Pendientes: plazo vencido, turnos que se pasaron de fecha y lo que todavía no tiene día.
  let p='<h3>Pendientes</h3><div class="ayuda">Tocá una obra para ponerle día, o arrastrala al calendario.</div>',hay=false;
  // Lo urgente primero, y siempre a la vista aunque caiga en otro mes.
  const ven=CARDS.filter(c=>estadoPlazo(c)==='vencida').sort((x,z)=>x.limite<z.limite?-1:1);
  if(ven.length){hay=true;p+='<h4 class="rojo">Plazo vencido <b>'+ven.length+'</b></h4>'+ven.map(c=>chip({tipo:'vence',c:c},true)).join('');}
  const atr=CARDS.filter(atrasada).sort((x,z)=>x.fecha<z.fecha?-1:1);
  if(atr.length){hay=true;p+='<h4 class="rojo">Atrasadas <b>'+atr.length+'</b></h4>'+atr.map(c=>chip({tipo:c.etapa,c:c},true)).join('');}
  ETAPAS.filter(e=>e.fecha).forEach(e=>{
    const l=CARDS.filter(c=>c.etapa===e.id&&!c.fecha&&!cumplida(c)).sort((x,z)=>x.desde<z.desde?-1:(x.desde>z.desde?1:0));
    if(!l.length)return;
    hay=true;
    p+='<h4>'+esc('Sin '+(e.exige_fecha?'turno':'fecha')+' de '+e.fecha.toLowerCase())+' <b>'+l.length+'</b></h4>'+l.map(c=>chip({tipo:c.etapa,c:c},false)).join('');
  });
  if(!hay)p+='<div class="ok">Nada pendiente: todo tiene su fecha y está en plazo.</div>';
  $('pend').innerHTML=p;
}

// Guarda uno o más cambios de una obra: primero en pantalla, después el server.
async function guardar(c,cambios,aviso){
  if(ocupado||!cambios.length)return;
  ocupado=true;
  cambios.forEach(x=>x[1]());
  dibujar();
  try{
    for(const x of cambios)await pedir(API,Object.assign({cliente_nro:c.cliente_nro,sub:c.sub},x[0]));
    if(aviso)toast(aviso,true);
  }catch(e){toast(e.message);}
  ocupado=false;
  cargar();
}
const cambioFecha=(c,dia,hora)=>[{accion:'fecha',fecha:dia,hora:hora},()=>{c.fecha=dia;c.hora=dia?hora:'';}];
// El plazo "a mano" solo se guarda si es distinto del calculado; si coincide (o se borra), vuelve al normal.
function cambioPlazo(c,dia){
  const manual=dia&&dia!==c.limite_auto?dia:'';
  return [{accion:'plazo',fecha:manual},()=>{c.plazo_manual=!!manual;c.limite=manual||c.limite_auto;}];
}

// ---------- Ficha de la obra: su turno y su plazo, en un solo lugar ----------
let modalOk=null;
function cerrarModal(){$('modalBg').classList.remove('show');modalOk=null;}
function abrir(c,dia,foco){ // dia: fecha sugerida (viene de soltar la obra en un día)
  const e=etapaDe(c.etapa);
  const conTurno=!!e.fecha&&!cumplida(c);
  let h='<p class="sub estado">'+esc(e.titulo)+'</p>';
  const mat=[c.material,c.concepto].filter(Boolean).join(' · ');
  if(mat)h+='<p class="sub">'+esc(mat)+'</p>';
  const lugar=[c.zona,c.direccion].filter(Boolean).join(' · ');
  if(lugar)h+='<p class="sub">'+esc(lugar)+'</p>';
  if(c.equipo.length&&e.exige_fecha)h+='<p class="sub">Instalan: '+esc(c.equipo.join(', '))+'</p>';
  if(conTurno){
    h+='<label class="campo" for="fDia">'+esc(e.fecha)+'</label><div class="fila"><input type="date" id="fDia" value="'+esc(dia||c.fecha)+'">'+(e.pide_hora?'<input type="time" id="fHora" step="900" value="'+esc(c.hora)+'" aria-label="Hora">':'')+'</div>'
      +'<p class="nota">'+(e.exige_fecha?'Día y hora.':(e.pide_hora?'La hora es opcional.':''))+' Para sacarle la fecha, dejá el día vacío.</p><p class="nota rojo" id="fAviso"></p>';
  }else if(e.fecha&&c.fecha){
    h+='<p class="nota">'+esc(e.fecha)+' hecha: '+esc(fechaCorta(c.medido_at||c.fecha))+'.</p>';
  }
  h+='<label class="campo" for="fPlazo">Plazo de entrega</label><div class="fila"><input type="date" id="fPlazo" value="'+esc(c.limite)+'"></div>'
    +'<p class="nota">'+(c.limite_auto?'Plazo normal: '+esc(fechaCorta(c.limite_auto))+' ('+c.plazo_dias+' días corridos desde la medición).':'Todavía no se midió: el plazo normal se calcula al medir ('+c.plazo_dias+' días corridos).')
    +' <button type="button" class="lnk" id="fNormal"'+(c.plazo_manual?'':' hidden')+'>Volver al plazo normal</button></p>';
  h+='<a class="ir" href="/presupuestos/tablero.php?q='+encodeURIComponent(c.cliente_nro+'-'+c.sub)+'">Ver la ficha en el tablero →</a>';
  $('mTitulo').textContent=c.cliente_nro+'-'+c.sub+' · '+(nombreCorto(c.nombre)||'Sin nombre');
  $('mCuerpo').innerHTML=h;
  $('modalBg').classList.add('show');

  // Aviso en vivo: el turno de instalación queda después del plazo, o se pisa con otro.
  const revisar=()=>{
    if(!$('fAviso'))return;
    const dd=$('fDia').value,pz=$('fPlazo').value,out=[];
    if(e.exige_fecha&&dd&&pz&&dd>pz)out.push('Queda después del plazo de entrega.');
    if(dd===c.fecha)(CHOQUES[c.key]||[]).forEach(t=>out.push(t));
    $('fAviso').textContent=out.join(' ');
  };
  if($('fDia'))$('fDia').addEventListener('input',revisar);
  $('fPlazo').addEventListener('input',()=>{$('fNormal').hidden=!$('fPlazo').value||$('fPlazo').value===c.limite_auto;revisar();});
  $('fNormal').addEventListener('click',()=>{$('fPlazo').value=c.limite_auto;$('fNormal').hidden=true;revisar();});
  revisar();

  modalOk=()=>{
    const cambios=[];
    if(conTurno){
      const dd=$('fDia').value,hh=dd&&$('fHora')?$('fHora').value.slice(0,5):'';
      if(dd&&!hh&&e.exige_fecha){$('fHora').focus();toast('Falta la hora.');return;}
      if(dd!==c.fecha||hh!==c.hora)cambios.push(cambioFecha(c,dd,hh));
    }
    const pz=$('fPlazo').value;
    if(pz!==c.limite)cambios.push(cambioPlazo(c,pz));
    cerrarModal();
    guardar(c,cambios,cambios.length?'Guardado: '+quien(c):'');
  };
  const f=foco==='plazo'?$('fPlazo'):(conTurno?(($('fDia').value&&$('fHora'))?$('fHora'):$('fDia')):$('fPlazo'));
  if(f&&matchMedia('(hover:hover)').matches)f.focus();
}
$('mCancelar').addEventListener('click',cerrarModal);
$('mOk').addEventListener('click',()=>{if(modalOk)modalOk();});
$('modalBg').addEventListener('click',ev=>{if(ev.target===$('modalBg'))cerrarModal();});
document.addEventListener('keydown',ev=>{
  if(!$('modalBg').classList.contains('show'))return;
  if(ev.key==='Escape')cerrarModal();
  else if(ev.key==='Enter'&&modalOk){ev.preventDefault();$('mOk').click();}
});

// ---------- Tocar ----------
document.addEventListener('click',ev=>{
  const b=ev.target.closest('.ev[data-key]');
  if(b){const c=cardDe(b.dataset.key);if(c)abrir(c,'',b.dataset.tipo==='vence'?'plazo':'');return;}
  const v=ev.target.closest('[data-ver]');
  if(v){
    ver[v.dataset.ver]=ver[v.dataset.ver]===false;
    try{localStorage.setItem('bs-cal-ver',JSON.stringify(ver));}catch(e){}
    dibujar();
  }
});
function moverMes(n){const d=new Date(+mes.slice(0,4),+mes.slice(5,7)-1+n,1);mes=isoDe(d).slice(0,7);dibujar();}
$('mesAnt').addEventListener('click',()=>moverMes(-1));
$('mesSig').addEventListener('click',()=>moverMes(1));
$('mesHoy').addEventListener('click',()=>{mes=HOY.slice(0,7);dibujar();});

// ---------- Arrastrar a un día (computadora) ----------
document.addEventListener('dragstart',ev=>{
  const b=ev.target.closest&&ev.target.closest('.ev[data-key]');
  if(!b)return;
  arrastre={key:b.dataset.key,tipo:b.dataset.tipo};
  ev.dataTransfer.effectAllowed='move';
  ev.dataTransfer.setData('text/plain',b.dataset.key);
  setTimeout(()=>b.classList.add('arr'),0);
});
document.addEventListener('dragend',()=>{
  arrastre=null;
  document.querySelectorAll('.dia.drop').forEach(x=>x.classList.remove('drop'));
  document.querySelectorAll('.ev.arr').forEach(x=>x.classList.remove('arr'));
});
$('cal').addEventListener('dragover',ev=>{
  const dia=ev.target.closest('.dia');
  if(!arrastre||!dia)return;
  ev.preventDefault();
  ev.dataTransfer.dropEffect='move';
  if(!dia.classList.contains('drop')){
    document.querySelectorAll('.dia.drop').forEach(x=>x.classList.remove('drop'));
    dia.classList.add('drop');
  }
});
$('cal').addEventListener('drop',ev=>{
  const celda=ev.target.closest('.dia');
  if(!arrastre||!celda)return;
  ev.preventDefault();
  const c=cardDe(arrastre.key),tipo=arrastre.tipo,dia=celda.dataset.dia;
  arrastre=null;
  celda.classList.remove('drop');
  if(!c)return;
  if(tipo==='vence'){
    if(dia!==c.limite)guardar(c,[cambioPlazo(c,dia)],'Plazo de '+quien(c)+' → '+fechaCorta(dia));
    return;
  }
  const e=etapaDe(c.etapa);
  if(dia===c.fecha)return;
  // La instalación necesita hora: si todavía no la tiene, se pide ahí mismo con el día ya puesto.
  if(e.exige_fecha&&e.pide_hora&&!c.hora){abrir(c,dia);return;}
  guardar(c,[cambioFecha(c,dia,c.hora)],e.fecha+' de '+quien(c)+' → '+fechaCorta(dia));
});

const libre=()=>!ocupado&&!arrastre&&!$('modalBg').classList.contains('show');
document.addEventListener('visibilitychange',()=>{if(!document.hidden&&libre())cargar();});
setInterval(()=>{if(!document.hidden&&libre())cargar();},60000);

cargar();
</script>
</body>
</html>
