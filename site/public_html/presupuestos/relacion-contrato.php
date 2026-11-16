<?php
// ============================================================
// /presupuestos/relacion-contrato.php — Relación-Contrato
// ============================================================
// Lo administrativo de cada obra: el contrato, su total y lo cobrado.
// Datos propios (api/contratos.php); se enlaza con el tablero con un botón.
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
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>BlackStones · Relación-Contrato</title>
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

/* === ARRIBA: buscador, resumen y alta === */
.tope{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px}
.buscar{flex:1 1 260px;max-width:420px}
.buscar input{width:100%;padding:11px 16px;border:1px solid var(--border);border-radius:999px;background:var(--card);color:var(--text);font-family:inherit;font-size:14px;font-weight:500;outline:none}
.buscar input:focus{border-color:var(--gd);box-shadow:0 0 0 3px var(--gd-soft)}
.resumen{margin-left:auto;font-size:13px;color:var(--text2);text-align:right;line-height:1.35}
.resumen b{color:var(--text);font-weight:700;font-variant-numeric:tabular-nums}
.btn{padding:10px 16px;border:1px solid var(--border);border-radius:999px;background:var(--card);font-size:13.5px;font-weight:700;color:var(--text);cursor:pointer;white-space:nowrap}
.btn.pri{background:var(--dk);border-color:var(--dk);color:var(--cr)}
[data-theme="dark"] .btn.pri{background:var(--gd);border-color:var(--gd);color:#1A1816}
.btn.chico{padding:7px 12px;font-size:12.5px}
.btn:disabled{opacity:.6;cursor:default}
.aviso-p{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px;padding:9px 14px;border:1px dashed var(--amb);border-radius:var(--radius);background:var(--amb-soft);color:var(--amb);font-size:13px;font-weight:600}
.aviso-p[hidden]{display:none}
.aviso-p .btn{margin-left:auto;border-color:var(--amb);color:var(--amb);background:transparent}

/* === LISTA: un renglón por contrato === */
.lista{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
.fila,.cab{display:grid;grid-template-columns:minmax(0,2.1fr) minmax(0,1.6fr) minmax(0,1.1fr) minmax(0,1.1fr) minmax(0,1.1fr) minmax(0,1.9fr);gap:14px;align-items:center;padding:12px 16px}
.cab{padding-top:10px;padding-bottom:10px;background:var(--card-alt);font-size:10.5px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:var(--text3)}
.cab .der,.fila .der{text-align:right}
.fila{border-top:1px solid var(--border-soft);cursor:pointer;min-width:0}
.fila>div{min-width:0}
.f-nom{font-size:14.5px;font-weight:700;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.f-sub{font-size:12px;color:var(--text3);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.f-sub .falta{color:var(--amb);font-weight:600}
.f-mat{font-size:13.5px;color:var(--text2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.plata{font-size:13.5px;font-weight:600;color:var(--text);font-variant-numeric:tabular-nums;line-height:1.35}
.plata span{display:block;white-space:nowrap}
.plata.nada{color:var(--text3);font-weight:500}
.plata.debe{color:var(--red);font-weight:700}
.plata.ok{color:var(--gr);font-weight:700}
.rot{display:none}
.tab{display:flex;flex-direction:column;align-items:flex-start;gap:4px}
.etapa{display:inline-flex;align-items:center;gap:7px;max-width:100%;padding:5px 11px;border-radius:999px;background:var(--et-soft);color:var(--et);font-size:12.5px;font-weight:700;text-decoration:none;white-space:nowrap}
.etapa::before{content:'';width:8px;height:8px;border-radius:50%;background:var(--et);flex-shrink:0}
.etapa.fuera{--et:var(--text3);--et-soft:var(--card-alt)}
.tab small{font-size:11.5px;color:var(--text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%}
.tab small.rojo{color:var(--red);font-weight:700}
.et-medir{--et:#2F6DB5;--et-soft:rgba(47,109,181,.11)}
.et-cortar{--et:#4B5563;--et-soft:rgba(75,85,99,.12)}
.et-fabricar{--et:#B5680A;--et-soft:rgba(181,104,10,.12)}
.et-instalar{--et:#7A52B3;--et-soft:rgba(122,82,179,.11)}
.et-archivar{--et:#1F8F47;--et-soft:rgba(31,143,71,.11)}
.et-retiro{--et:#14808A;--et-soft:rgba(20,128,138,.11)}
[data-theme="dark"] .et-medir{--et:#6AA5E8;--et-soft:rgba(106,165,232,.14)}
[data-theme="dark"] .et-cortar{--et:#A8B3C4;--et-soft:rgba(168,179,196,.14)}
[data-theme="dark"] .et-fabricar{--et:#E8A548;--et-soft:rgba(232,165,72,.14)}
[data-theme="dark"] .et-instalar{--et:#B594E6;--et-soft:rgba(181,148,230,.14)}
[data-theme="dark"] .et-archivar{--et:#34D670;--et-soft:rgba(52,214,112,.13)}
[data-theme="dark"] .et-retiro{--et:#4FC4C4;--et-soft:rgba(79,196,196,.13)}
.prueba-t{display:inline-block;margin-left:6px;padding:1px 7px;border-radius:999px;background:var(--amb-soft);color:var(--amb);font-size:10px;font-weight:800;letter-spacing:.06em;vertical-align:middle}
.vacio{padding:44px 20px;text-align:center;color:var(--text2);font-size:14.5px;line-height:1.6}
.vacio h2{font-family:'Fraunces',serif;font-size:24px;font-weight:600;color:var(--text);margin-bottom:6px}
.vacio .btns-v{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:18px}
.error{padding:14px 16px;border-radius:var(--radius);background:var(--red-soft);color:var(--red);font-weight:600;margin-bottom:12px}
.error[hidden]{display:none}

/* === VENTANA === */
.modal-bg{position:fixed;inset:0;background:rgba(10,9,7,.55);display:none;align-items:center;justify-content:center;padding:16px;z-index:900}
.modal-bg.show{display:flex}
.modal{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-lg);--mp:24px;padding:var(--mp);max-width:560px;width:100%;max-height:calc(100vh - 32px);overflow-y:auto}
.modal.chica{max-width:440px}
.modal h3{font-family:'Fraunces',serif;font-size:20px;font-weight:600;margin-bottom:6px}
.modal .bajada{font-size:13px;color:var(--text2);line-height:1.5;margin-bottom:14px}
.modal h4{margin:22px 0 2px;padding-top:16px;border-top:1px solid var(--border-soft);font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--text3)}
.campo{display:block;margin-top:14px;font-size:12px;font-weight:700;color:var(--text2)}
.campo i{font-style:normal;font-weight:500;color:var(--text3)}
.campo input,.campo select,.campo textarea{display:block;width:100%;margin-top:6px;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--card);color:var(--text);font-family:inherit;font-size:15px;font-weight:500;outline:none}
.campo textarea{resize:vertical;min-height:64px;line-height:1.45}
.campo input:focus,.campo select:focus,.campo textarea:focus{border-color:var(--gd);box-shadow:0 0 0 3px var(--gd-soft)}
.campo input[readonly]{background:var(--card-alt);color:var(--text2)}
.campo[hidden]{display:none}
.dos{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:0 12px}
.tres{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(0,.9fr) minmax(0,1.2fr);gap:0 12px}
.opc{display:flex;gap:8px;margin-top:6px}
.opc button{flex:1;padding:10px 6px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--card);font-size:13.5px;font-weight:600;color:var(--text2);cursor:pointer}
.opc button.on{background:var(--dk);border-color:var(--dk);color:var(--cr)}
[data-theme="dark"] .opc button.on{background:var(--gd);border-color:var(--gd);color:#1A1816}
.opc button:disabled{cursor:default;opacity:.75}
.nota-t{margin-top:14px;padding:10px 12px;border-radius:var(--radius-sm);background:var(--card-alt);font-size:12.5px;color:var(--text2);line-height:1.5}
.nota-t a{color:var(--text);font-weight:700}
.nota-t[hidden]{display:none}
.saldo-c{display:flex;justify-content:space-between;gap:12px;margin-top:14px;padding:10px 12px;border-radius:var(--radius-sm);background:var(--card-alt);font-size:13px;color:var(--text2)}
.saldo-c b{color:var(--text);font-variant-numeric:tabular-nums;text-align:right}
.saldo-c b.debe{color:var(--red)}
.saldo-c b.ok{color:var(--gr)}
.pagos{margin-top:10px}
.pago{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--border-soft);font-size:13.5px}
.pago .q{flex:1;min-width:0;color:var(--text2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pago .q b{color:var(--text);font-weight:700}
.pago .m{font-weight:700;font-variant-numeric:tabular-nums;white-space:nowrap}
.pago button{width:30px;height:30px;border:none;border-radius:50%;background:transparent;color:var(--text3);font-size:18px;line-height:1;cursor:pointer;flex-shrink:0}
.pagos .nada{padding:8px 0;font-size:13px;color:var(--text3)}
.lista-c{margin:4px 0 0;padding:0;list-style:none;font-size:13.5px;color:var(--text2);line-height:1.5}
.lista-c li{display:flex;gap:10px;padding:7px 0;border-bottom:1px solid var(--border-soft)}
.lista-c li span{flex:0 0 118px;color:var(--text3);font-size:12px;font-weight:700}
.lista-c li b{color:var(--text);font-weight:600;min-width:0;overflow-wrap:anywhere}
.modal .btns{display:flex;gap:10px;justify-content:flex-end;position:sticky;bottom:calc(-1*var(--mp));z-index:1;background:var(--card);margin:16px calc(-1*var(--mp)) calc(-1*var(--mp));padding:14px var(--mp) var(--mp);border-radius:0 0 var(--radius) var(--radius)}
.modal .btns button{padding:9px 16px;border-radius:var(--radius-sm);font-size:13.5px;font-weight:700;cursor:pointer;border:1px solid var(--border);background:var(--card)}
.modal .btns .ok{background:var(--dk);color:var(--cr);border-color:var(--dk)}
[data-theme="dark"] .modal .btns .ok{background:var(--gd);color:#1A1816;border-color:var(--gd)}
.modal .btns .ok.peligro,[data-theme="dark"] .modal .btns .ok.peligro{background:var(--red);border-color:var(--red);color:#fff}
.modal .btns .izq{margin-right:auto;border-color:transparent;color:var(--red);padding-left:4px;padding-right:4px}
.modal .btns button:disabled{opacity:.6;cursor:default}
.m-err{margin-top:12px;padding:9px 12px;border-radius:var(--radius-sm);background:var(--red-soft);color:var(--red);font-size:13px;font-weight:600}
.m-err[hidden]{display:none}
.toast{position:fixed;left:50%;top:calc(14px + env(safe-area-inset-top));transform:translateX(-50%);z-index:950;max-width:calc(100vw - 32px);padding:11px 18px;border-radius:999px;background:var(--dk);color:var(--cr);font-size:13.5px;font-weight:600;box-shadow:var(--shadow-lg);opacity:0;pointer-events:none;transition:opacity .2s}
.toast.show{opacity:1}
[data-theme="dark"] .toast{background:var(--gd);color:#1A1816}
@media(hover:hover){
  .fila:hover{background:var(--gd-soft)}
  .btn:hover{border-color:var(--gd)}
  .pago button:hover{background:var(--red-soft);color:var(--red)}
}
a,button,.toggle{-webkit-tap-highlight-color:transparent;touch-action:manipulation}
a:focus-visible,.toggle:focus-visible,.btn:focus-visible,.fila:focus-visible,.opc button:focus-visible{outline:2px solid var(--gd);outline-offset:2px}
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
  .wrap{padding:12px max(12px,env(safe-area-inset-right)) calc(60px + env(safe-area-inset-bottom)) max(12px,env(safe-area-inset-left))}
  .modal{--mp:20px}
  .campo input,.campo select,.campo textarea,.buscar input{font-size:16px}
  .modal .btns button{padding:11px 18px;font-size:14px}
  .btn{padding:12px 18px}
  .btn.chico{padding:10px 14px;font-size:13px}
  .opc button{padding:12px 6px}
  .pago button{width:40px;height:40px}
}
/* Tablet parada y celular: cada contrato es una tarjeta, con el rótulo al lado de cada dato. */
@media(max-width:899px){
  .cab{display:none}
  .lista{background:none;border:none;box-shadow:none;overflow:visible;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
  .lista>.vacio{grid-column:1/-1;background:var(--card);border:1px solid var(--border);border-radius:var(--radius)}
  .fila{grid-template-columns:repeat(3,minmax(0,1fr));gap:10px 12px;align-items:start;padding:14px;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow)}
  .fila>.c-cli,.fila>.c-mat,.fila>.c-tab{grid-column:1/-1}
  .fila .der{text-align:left}
  .rot{display:block;margin-bottom:2px;font-size:10px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:var(--text3)}
  .c-tab{padding-top:10px;border-top:1px solid var(--border-soft)}
  .tab{flex-direction:row;flex-wrap:wrap;align-items:center;gap:6px 10px}
  .resumen{flex:1 1 100%;margin-left:0;text-align:left}
}
@media(max-width:639px){
  .lista{grid-template-columns:minmax(0,1fr)}
  .tope .btn.pri{flex:1 1 100%}
  .buscar{max-width:none;flex-basis:100%}
  .dos,.tres{grid-template-columns:minmax(0,1fr)}
}
</style>
</head>
<body>

<div class="header">
  <div style="display:flex;align-items:center;gap:12px">
    <img src="../logo/logo_isotipo.png" alt="BlackStones" style="height:36px;width:auto">
    <div><h1>BlackStones</h1><span>Relación-Contrato</span></div>
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
  <a href="/presupuestos/calendario.php">Calendario</a>
  <a href="/presupuestos/relacion-contrato.php" class="active">Relación-Contrato</a>
  <a href="/presupuestos/stock.php">Stock</a>
</nav>
<div class="gold-line"></div>

<div class="wrap">
  <div class="error" id="err" hidden></div>
  <div class="tope">
    <div class="buscar"><input type="search" id="buscar" placeholder="Buscar cliente, N° o material" autocomplete="off" aria-label="Buscar"></div>
    <button type="button" class="btn pri" data-nuevo>+ Nuevo contrato</button>
    <div class="resumen" id="resumen"></div>
  </div>
  <div class="aviso-p" id="avisoP" hidden></div>
  <div class="lista" id="lista"><div class="vacio">Cargando…</div></div>
</div>

<div class="modal-bg" id="modalBg"><div class="modal" id="modal" role="dialog" aria-modal="true"></div></div>
<div class="toast" id="toast" role="status"></div>
<datalist id="catalogo"></datalist>

<script>
// En pantallas angostas la barra de páginas se desliza de costado: la página actual queda a la vista.
(function(){
  const a=document.querySelector('.h-tabs a.active'),n=a&&a.parentElement;
  if(n)n.scrollLeft=Math.max(0,a.offsetLeft-(n.clientWidth-a.offsetWidth)/2);
})();
// === RELACIÓN-CONTRATO ===
// Un renglón por contrato. Lo operativo (cliente, dirección, material, pileta, seña) lo manda el
// tablero cuando el contrato está enlazado; la plata (total y pagos) vive acá.
const $=id=>document.getElementById(id);
const API='/calculadora/api/contratos.php',API_T='/calculadora/api/tablero.php',API_CARGAR='/calculadora/api/tablero-cargar.php',API_DEL='/calculadora/api/delete.php',API_COLORES='/calculadora/api/colors-db.php';
let CONTRATOS=[],CARDS={},ETAPAS={},HOY='',TABLERO_OK=false,q='';
const esc=s=>String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const llano=s=>String(s||'').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'');
async function pedir(url,body){
  const r=await fetch(url,body?{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body),credentials:'same-origin'}:{credentials:'same-origin',cache:'no-store'});
  if(r.status===401){location.href='/calculadora/login.php';throw new Error('Se venció la sesión.');}
  let j=null;try{j=await r.json();}catch(e){}
  if(!j||!j.ok)throw new Error((j&&j.error)||'No se pudo conectar ('+r.status+').');
  return j;
}
let tT=null;
function toast(t){const e=$('toast');e.textContent=t;e.classList.add('show');clearTimeout(tT);tT=setTimeout(()=>e.classList.remove('show'),3200);}

// --- plata y fechas ---
const NF=new Intl.NumberFormat('es-AR',{maximumFractionDigits:2});
const plata=(n,mon)=>(mon==='USD'?'USD ':'$ ')+NF.format(n);
// "1.250" son mil doscientos cincuenta; "1250,50" lleva coma decimal.
function num(s){
  s=String(s==null?'':s).replace(/[^\d.,]/g,'');
  if(!s)return 0;
  if(s.indexOf(',')>=0)s=s.replace(/\./g,'').replace(',','.');
  else if(/^\d{1,3}(\.\d{3})+$/.test(s))s=s.replace(/\./g,'');
  const n=parseFloat(s);
  return isFinite(n)&&n>0?Math.round(n*100)/100:0;
}
const enCampo=n=>n?NF.format(n):'';
const DIAS=['dom','lun','mar','mié','jue','vie','sáb'];
function fch(iso){
  if(!/^\d{4}-\d{2}-\d{2}/.test(iso||''))return '';
  const p=iso.slice(0,10).split('-').map(Number),d=new Date(p[0],p[1]-1,p[2]);
  return DIAS[d.getDay()]+' '+String(p[2]).padStart(2,'0')+'/'+String(p[1]).padStart(2,'0')+(HOY&&p[0]!==+HOY.slice(0,4)?'/'+String(p[0]).slice(2):'');
}

// --- enlace con el tablero ---
const keyDe=c=>c.enlace?c.enlace.cliente_nro+'-'+c.enlace.sub:'';
const cardDe=c=>c.enlace?(CARDS[keyDe(c)]||null):null;
// Cómo se ve un contrato: enlazado y con la ficha en el tablero, lo operativo sale de la ficha.
function ver(c){
  const k=cardDe(c);
  if(!k)return c;
  return Object.assign({},c,{
    cliente:k.nombre||c.cliente,telefono:k.celular||c.telefono,direccion:k.direccion||c.direccion,material:k.material||c.material,
    bacha:k.bacha||c.bacha,bacha_modelo:k.bacha?k.bacha_modelo:c.bacha_modelo,
    sena:k.sena?{monto:k.sena.monto,moneda:k.sena.moneda,fecha:(c.sena&&c.sena.fecha)||HOY}:c.sena
  });
}
function cuentas(c,v){
  v=v||ver(c);
  const p={USD:0,ARS:0},r=x=>Math.round(x*100)/100;
  if(v.sena)p[v.sena.moneda]+=v.sena.monto;
  (c.pagos||[]).forEach(x=>{p[x.moneda]+=x.monto;});
  return {tot:{USD:v.total_usd,ARS:v.total_ars},pag:{USD:r(p.USD),ARS:r(p.ARS)},sal:{USD:r(v.total_usd-p.USD),ARS:r(v.total_ars-p.ARS)}};
}
const partes=o=>['USD','ARS'].filter(m=>Math.abs(o[m])>0.004).map(m=>plata(o[m],m));
// Saldo: lo que falta cobrar en cada moneda. Si se cobró de más en una, queda "a favor".
function saldoTxt(n){
  if(!n.tot.USD&&!n.tot.ARS)return {cls:'nada',l:['Sin total']};
  const l=['USD','ARS'].filter(m=>Math.abs(n.sal[m])>0.004).map(m=>n.sal[m]>0?plata(n.sal[m],m):plata(-n.sal[m],m)+' a favor');
  if(!l.length)return {cls:'ok',l:['Pagado']};
  return {cls:['USD','ARS'].some(m=>n.sal[m]>0.004)?'debe':'ok',l:l};
}
function etapaDet(k){
  let t='';
  if(k.etapa==='medir')t=k.medido_at?'Medida el '+fch(k.medido_at):(k.fecha?'Medición '+fch(k.fecha):'Sin fecha de medición');
  else if(k.etapa==='instalar')t=k.fecha?'Instalación '+fch(k.fecha)+(k.hora?' · '+k.hora:''):'Sin turno de instalación';
  const venc=!!k.limite&&k.limite<HOY&&k.etapa!=='archivar';
  if(k.limite&&k.etapa!=='archivar')t+=(t?' · ':'')+(venc?'Plazo vencido':'Vence '+fch(k.limite));
  return {t:t,rojo:venc};
}
const BACHA={nuestra:'Nuestra',cliente:'Del cliente',no:'No lleva'};
const bachaTxt=v=>v.bacha?BACHA[v.bacha]+(v.bacha==='nuestra'&&v.bacha_modelo?' · modelo '+v.bacha_modelo:''):'';

async function cargar(){
  try{
    const [a,t]=await Promise.all([pedir(API),pedir(API_T).catch(()=>null)]);
    CONTRATOS=a.contratos;HOY=a.hoy;
    TABLERO_OK=!!t;CARDS={};ETAPAS={};
    if(t){t.cards.forEach(k=>{CARDS[k.key]=k;});t.etapas.forEach(e=>{ETAPAS[e.id]=e.titulo;});}
    $('err').hidden=true;
    dibujar();
    espejarSenas();
  }catch(e){$('err').textContent=e.message;$('err').hidden=false;$('lista').innerHTML='<div class="vacio">No se pudo cargar la lista.</div>';}
}
// La seña cargada en el tablero queda copiada en el contrato: así el saldo sigue bien
// cuando la obra se archiva y la ficha ya no está.
function espejarSenas(){
  CONTRATOS.forEach(c=>{
    const k=cardDe(c);
    if(!k||!k.sena)return;
    if(c.sena&&c.sena.monto===k.sena.monto&&c.sena.moneda===k.sena.moneda)return;
    const f=(c.sena&&c.sena.fecha)||HOY;
    c.sena={monto:k.sena.monto,moneda:k.sena.moneda,fecha:f};
    pedir(API,{accion:'sena',id:c.id,monto:k.sena.monto,moneda:k.sena.moneda,fecha:f}).catch(()=>{});
  });
}

function dibujar(){
  const pr=CONTRATOS.filter(c=>c.prueba).length;
  $('avisoP').hidden=!pr;
  if(pr)$('avisoP').innerHTML='<span>'+(pr===1?'Hay 1 renglón de prueba.':'Hay '+pr+' renglones de prueba.')+' No son contratos reales.</span><button type="button" class="btn chico" data-borrar-prueba>Borrar los de prueba</button>';
  // por cobrar: la suma de lo que falta en cada moneda (los de prueba no cuentan)
  const pc={USD:0,ARS:0};
  CONTRATOS.filter(c=>!c.prueba).forEach(c=>{const n=cuentas(c);['USD','ARS'].forEach(m=>{if(n.sal[m]>0)pc[m]+=n.sal[m];});});
  const reales=CONTRATOS.length-pr;
  $('resumen').innerHTML=CONTRATOS.length?'<b>'+reales+'</b> '+(reales===1?'contrato':'contratos')+' · Por cobrar: <b>'+(partes(pc).join(' + ')||'nada')+'</b>':'';
  if(!CONTRATOS.length){
    $('lista').innerHTML='<div class="vacio"><h2>Todavía no hay contratos</h2>Acá va un renglón por cada cliente que contrata: el total, la seña y lo que falta cobrar.<br>Desde cada renglón se puede cargar la obra en Por medir del tablero.<div class="btns-v"><button type="button" class="btn pri" data-nuevo>+ Nuevo contrato</button><button type="button" class="btn" data-prueba>Cargar 3 de prueba</button></div></div>';
    return;
  }
  const t=llano(q).trim();
  const lista=CONTRATOS.filter(c=>{
    if(!t)return true;
    const v=ver(c);
    return llano([v.cliente,c.nro,keyDe(c),v.material,c.vendedor,v.telefono,v.direccion,c.contrato,c.contrato_paralelo].join(' ')).indexOf(t)>=0;
  });
  let h='<div class="cab"><div>Cliente</div><div>Material</div><div class="der">Total</div><div class="der">Cobrado</div><div class="der">Saldo</div><div>Tablero</div></div>';
  h+=lista.map(c=>{
    const v=ver(c),n=cuentas(c,v),k=cardDe(c),s=saldoTxt(n);
    const sub=[c.nro?'N° '+esc(c.nro):'<span class="falta">Sin N°</span>',esc(fch(c.fecha)),esc(c.vendedor)].filter(Boolean).join(' · ');
    const tot=partes(n.tot),pag=partes(n.pag);
    let tab;
    if(!c.enlace)tab='<button type="button" class="btn chico" data-enviar="'+esc(c.id)+'">Cargar en Por medir</button><small>'+(c.medicion?'Medición coordinada: '+esc(fch(c.medicion)):'Sin medición coordinada')+'</small>';
    else if(k){const d=etapaDet(k);tab='<a class="etapa et-'+esc(k.etapa)+'" href="/presupuestos/tablero.php?q='+encodeURIComponent(k.key)+'" title="Ver la ficha en el tablero">'+esc(ETAPAS[k.etapa]||k.etapa)+'</a>'+(d.t?'<small'+(d.rojo?' class="rojo"':'')+'>'+esc(d.t)+'</small>':'');}
    else tab='<span class="etapa fuera">'+(TABLERO_OK?'Fuera del tablero':'Sin datos del tablero')+'</span><small>'+(TABLERO_OK?'Archivada o borrada':'No se pudo leer el tablero')+'</small>';
    return '<div class="fila" data-id="'+esc(c.id)+'" tabindex="0" role="button" aria-label="Abrir el contrato de '+esc(v.cliente)+'">'
      +'<div class="c-cli"><div class="f-nom">'+esc(v.cliente)+(c.prueba?'<span class="prueba-t">PRUEBA</span>':'')+'</div><div class="f-sub">'+sub+'</div></div>'
      +'<div class="c-mat"><div class="f-mat">'+(esc(v.material)||'<span style="color:var(--text3)">Sin material</span>')+'</div>'+(c.medidas?'<div class="f-sub">'+esc(c.medidas)+'</div>':'')+'</div>'
      +'<div class="der"><span class="rot">Total</span><div class="plata'+(tot.length?'':' nada')+'">'+(tot.length?tot.map(x=>'<span>'+x+'</span>').join(''):'—')+'</div></div>'
      +'<div class="der"><span class="rot">Cobrado</span><div class="plata'+(pag.length?'':' nada')+'">'+(pag.length?pag.map(x=>'<span>'+x+'</span>').join(''):'—')+'</div></div>'
      +'<div class="der"><span class="rot">Saldo</span><div class="plata '+s.cls+'">'+s.l.map(x=>'<span>'+esc(x)+'</span>').join('')+'</div></div>'
      +'<div class="c-tab"><div class="tab">'+tab+'</div></div></div>';
  }).join('');
  if(!lista.length)h+='<div class="vacio">Ningún contrato coincide con la búsqueda.</div>';
  $('lista').innerHTML=h;
}

// --- ventanas ---
let protegida=false,F=null; // F: el contrato que se está editando (opciones sí/no y pagos)
function abrirModal(html,chica,prot){
  const m=$('modal');m.className='modal'+(chica?' chica':'');m.innerHTML=html;protegida=!!prot;
  $('modalBg').classList.add('show');
  const f=m.querySelector('[data-foco]');if(f)setTimeout(()=>f.focus(),30);
}
function cerrarModal(){$('modalBg').classList.remove('show');$('modal').innerHTML='';F=null;protegida=false;}
function errModal(t){const e=$('mErr');if(e){e.textContent=t;e.hidden=!t;if(t)e.scrollIntoView({block:'nearest'});}}
async function conBoton(b,fn){
  if(b.disabled)return;
  const t=b.textContent;b.disabled=true;b.textContent='Un momento…';
  try{await fn();}catch(e){errModal(e.message);if(document.body.contains(b)){b.disabled=false;b.textContent=t;}}
}

const opcHtml=(campo,ops,val,fijo)=>'<div class="opc" data-opc="'+campo+'">'+ops.map(o=>'<button type="button" data-v="'+o[0]+'" class="'+(val===o[0]?'on':'')+'" aria-pressed="'+(val===o[0])+'"'+(fijo?' disabled':'')+'>'+o[1]+'</button>').join('')+'</div>';
const usados=campo=>[...new Set(CONTRATOS.map(c=>c[campo]).filter(Boolean))].sort().map(x=>'<option value="'+esc(x)+'">').join('');

function abrirFicha(id){
  const c=id?CONTRATOS.find(x=>x.id===id):null;
  if(id&&!c)return;
  const v=c?ver(c):{},k=c?cardDe(c):null;
  // Enlazado y con la ficha a la vista: lo operativo se edita en el tablero (un solo lugar por dato).
  const fijo=!!k,ro=fijo?' readonly':'';
  const senaFija=!!(k&&k.sena);
  F={id:c?c.id:'',factura:c?c.factura:'',muebles:c?c.muebles:'',bacha:c?v.bacha:'',pagos:c?(c.pagos||[]).slice():[],fijo:fijo,senaFija:senaFija,
     empujarSena:!!(k&&!k.sena&&k.etapa==='medir'),key:k?k.key:''};
  const val=x=>esc(x==null?'':x);
  let h='<h3>'+(c?'Contrato · '+esc(v.cliente):'Nuevo contrato')+'</h3>';
  if(k)h+='<div class="nota-t">Está en el tablero: <a href="/presupuestos/tablero.php?q='+encodeURIComponent(k.key)+'">'+esc(ETAPAS[k.etapa]||k.etapa)+'</a>'+(etapaDet(k).t?' · '+esc(etapaDet(k).t):'')+'.<br>Cliente, teléfono, dirección, material y pileta se cambian desde el tablero.</div>';
  else if(c&&c.enlace)h+='<div class="nota-t">Se cargó en el tablero como '+esc(keyDe(c))+', pero esa ficha ya no está ahí (archivada o borrada).</div>';
  h+='<div class="dos"><label class="campo">N° de presupuesto <i>· si lo tiene</i><input id="fNro" value="'+val(c&&c.nro)+'" placeholder="0042" inputmode="numeric" autocomplete="off"></label>'
    +'<label class="campo">Fecha del contrato<input id="fFecha" type="date" value="'+val(c?c.fecha:HOY)+'"></label></div>'
    +'<label class="campo">Cliente<input id="fCliente" value="'+val(v.cliente)+'" autocomplete="off"'+ro+(c?'':' data-foco')+'></label>'
    +'<div class="dos"><label class="campo">Teléfono<input id="fTel" value="'+val(v.telefono)+'" inputmode="tel" autocomplete="off"'+ro+'></label>'
    +'<label class="campo">Dirección<input id="fDir" value="'+val(v.direccion)+'" autocomplete="off"'+ro+'></label></div>'
    +'<div class="dos"><label class="campo">Vendedor<input id="fVend" value="'+val(c&&c.vendedor)+'" list="lVend" autocomplete="off"></label>'
    +'<label class="campo">Procedencia<input id="fProc" value="'+val(c&&c.procedencia)+'" list="lProc" placeholder="Instagram, recomendación…" autocomplete="off"></label></div>'
    +'<div class="dos"><label class="campo">Contrato<input id="fCtto" value="'+val(c&&c.contrato)+'" autocomplete="off"></label>'
    +'<label class="campo">Contrato paralelo<input id="fCttoP" value="'+val(c&&c.contrato_paralelo)+'" autocomplete="off"></label></div>'
    +'<label class="campo">Material<input id="fMat" value="'+val(v.material)+'" list="catalogo" autocomplete="off"'+ro+'></label>'
    +'<label class="campo">Medidas<input id="fMed" value="'+val(c&&c.medidas)+'" placeholder="2,40 x 0,62 + isla 1,80 x 0,90" autocomplete="off"></label>'
    +'<datalist id="lVend">'+usados('vendedor')+'</datalist><datalist id="lProc">'+usados('procedencia')+'</datalist>';
  h+='<h4>Plata</h4>'
    +'<div class="dos"><label class="campo">Total en dólares<input id="fTotU" value="'+enCampo(c&&c.total_usd)+'" inputmode="decimal" placeholder="0" autocomplete="off"></label>'
    +'<label class="campo">Total en pesos<input id="fTotA" value="'+enCampo(c&&c.total_ars)+'" inputmode="decimal" placeholder="0" autocomplete="off"></label></div>'
    +'<div class="tres"><label class="campo">Seña recibida<input id="fSen" value="'+enCampo(v.sena&&v.sena.monto)+'" inputmode="decimal" placeholder="0" autocomplete="off"'+(senaFija?' readonly':'')+'></label>'
    +'<label class="campo">Moneda<select id="fSenM"'+(senaFija?' disabled':'')+'><option value="ARS"'+(v.sena&&v.sena.moneda==='USD'?'':' selected')+'>Pesos</option><option value="USD"'+(v.sena&&v.sena.moneda==='USD'?' selected':'')+'>Dólares</option></select></label>'
    +'<label class="campo">Fecha de la seña<input id="fSenF" type="date" value="'+val((v.sena&&v.sena.fecha)||'')+'"></label></div>'
    +(senaFija?'<div class="nota-t">La seña se cargó en el tablero ("Trajo la seña"). Para cambiar el monto, se cambia ahí.</div>':(F.empujarSena?'<div class="nota-t">Al guardar, la seña queda tildada también en el tablero ("Trajo la seña").</div>':''));
  if(c)h+='<div id="fPagos"></div>'
    +'<div class="tres"><label class="campo">Nuevo pago<input id="pMonto" inputmode="decimal" placeholder="Monto" autocomplete="off"></label>'
    +'<label class="campo">Cómo<select id="pComo"><option value="efectivo|ARS">Efectivo $</option><option value="efectivo|USD">Efectivo USD</option><option value="transferencia|ARS">Transferencia</option></select></label>'
    +'<label class="campo">Fecha<input id="pFecha" type="date" value="'+val(HOY)+'"></label></div>'
    +'<div style="margin-top:10px"><button type="button" class="btn chico" data-pago>Agregar pago</button></div>';
  h+='<div class="saldo-c"><span>Saldo pendiente</span><b id="fSaldo"></b></div>';
  h+='<h4>Obra</h4>'
    +'<div class="dos"><div class="campo">Factura'+opcHtml('factura',[['si','Sí'],['no','No']],F.factura)+'</div>'
    +'<div class="campo">Muebles colocados'+opcHtml('muebles',[['si','Sí'],['no','No']],F.muebles)+'</div></div>'
    +'<div class="campo">Pileta'+opcHtml('bacha',[['nuestra','Nuestra'],['cliente','Del cliente'],['no','No lleva']],F.bacha,fijo)+'</div>'
    +'<label class="campo" id="cModelo"'+(F.bacha==='nuestra'?'':' hidden')+'>Modelo de pileta<input id="fModelo" value="'+val(v.bacha_modelo)+'" autocomplete="off"'+ro+'></label>'
    +(c&&c.enlace?'':'<label class="campo">Medición coordinada <i>· si ya tiene día</i><input id="fMedicion" type="date" value="'+val(c&&c.medicion)+'"></label>')
    +'<label class="campo">Nota<textarea id="fNota" rows="2">'+val(c&&c.nota)+'</textarea></label>';
  h+='<div class="m-err" id="mErr" hidden></div><div class="btns">'+(c?'<button type="button" class="izq" data-borrar>Borrar contrato</button>':'')+'<button type="button" data-cerrar>Cancelar</button><button type="button" class="ok" data-guardar>Guardar</button></div>';
  abrirModal(h,false,true);
  pintarPagos();
}
function datosForm(){
  const g=id=>$(id)?$(id).value.trim():'';
  return {nro:g('fNro'),fecha:g('fFecha'),cliente:g('fCliente'),telefono:g('fTel'),direccion:g('fDir'),vendedor:g('fVend'),procedencia:g('fProc'),
    contrato:g('fCtto'),contrato_paralelo:g('fCttoP'),material:g('fMat'),medidas:g('fMed'),total_usd:num(g('fTotU')),total_ars:num(g('fTotA')),
    sena_monto:num(g('fSen')),sena_moneda:g('fSenM')||'ARS',sena_fecha:g('fSenF'),factura:F.factura,muebles:F.muebles,bacha:F.bacha,
    bacha_modelo:F.bacha==='nuestra'?g('fModelo'):'',medicion:g('fMedicion'),nota:g('fNota')};
}
// Pagos y saldo de la ventana: se recalculan con lo que está escrito, sin guardar.
function pintarPagos(){
  if(!F)return;
  const d=datosForm(),p={USD:0,ARS:0},r=x=>Math.round(x*100)/100;
  if(d.sena_monto)p[d.sena_moneda==='USD'?'USD':'ARS']+=d.sena_monto;
  F.pagos.forEach(x=>{p[x.moneda]+=x.monto;});
  const s=saldoTxt({tot:{USD:d.total_usd,ARS:d.total_ars},sal:{USD:r(d.total_usd-p.USD),ARS:r(d.total_ars-p.ARS)}});
  $('fSaldo').className=s.cls;$('fSaldo').innerHTML=s.l.map(esc).join('<br>');
  if($('fPagos'))$('fPagos').innerHTML='<div class="pagos">'+(F.pagos.length?F.pagos.map(x=>'<div class="pago"><span class="q"><b>'+esc(fch(x.fecha))+'</b> · '+(x.medio==='transferencia'?'Transferencia':'Efectivo')+(x.nota?' · '+esc(x.nota):'')+'</span><span class="m">'+plata(x.monto,x.moneda)+'</span><button type="button" data-borrar-pago="'+esc(x.id)+'" title="Borrar este pago" aria-label="Borrar este pago">×</button></div>').join(''):'<div class="nada">Sin pagos cargados, aparte de la seña.</div>')+'</div>';
}
async function guardar(){
  const d=datosForm(),id=F.id,empujar=F.empujarSena&&d.sena_monto>0,key=F.key;
  if(!d.cliente)throw new Error('Falta el nombre del cliente.');
  const r=await pedir(API,Object.assign({accion:id?'editar':'crear'},id?{id:id}:{},d));
  let aviso='';
  if(empujar){
    const p=key.split('-');
    try{await pedir(API_T,{cliente_nro:p[0],sub:+p[1],accion:'check',clave:'sena',valor:true,monto:d.sena_monto,moneda:d.sena_moneda});}
    catch(e){aviso=' La seña no se pudo tildar en el tablero: '+e.message;}
  }
  cerrarModal();
  toast((id?'Contrato guardado.':'Contrato cargado.')+aviso);
  await cargar();
  return r;
}

function pedirEnviar(id){
  const c=CONTRATOS.find(x=>x.id===id);if(!c)return;
  const li=(a,b)=>b?'<li><span>'+a+'</span><b>'+esc(b)+'</b></li>':'';
  let h='<h3>Cargar en Por medir</h3>';
  if(c.nro)h+='<div class="bajada">Se aprueba el presupuesto <b>N° '+esc(c.nro)+'</b> y la obra aparece en Por medir del tablero, con los datos de ese presupuesto. Si ya está en el tablero, solo se enlaza.</div><ul class="lista-c">'+li('Pileta',bachaTxt(c))+li('Medición',fch(c.medicion))+li('Seña',c.sena?plata(c.sena.monto,c.sena.moneda):'')+'</ul>';
  else h+='<div class="bajada">Se crea la ficha en Por medir del tablero con estos datos. El total, los pagos y lo demás del contrato quedan acá.</div><ul class="lista-c">'+li('Cliente',c.cliente)+li('Teléfono',c.telefono)+li('Dirección',c.direccion)+li('Material',c.material)+li('Pileta',bachaTxt(c))+li('Medición',fch(c.medicion))+li('Seña',c.sena?plata(c.sena.monto,c.sena.moneda):'')+'</ul>';
  h+='<div class="m-err" id="mErr" hidden></div><div class="btns"><button type="button" data-cerrar>Cancelar</button><button type="button" class="ok" data-foco data-confirmar-enviar="'+esc(c.id)+'">Cargar en Por medir</button></div>';
  abrirModal(h,true,false);
}
async function enviar(id){
  const c=CONTRATOS.find(x=>x.id===id);if(!c)return;
  let nro,sub,yaEstaba=false;
  try{
    const r=await pedir(API_CARGAR,c.nro?{nro:c.nro}:{nombre:c.cliente,celular:c.telefono,direccion:c.direccion,material:c.material});
    nro=r.cliente_nro;sub=r.sub;
  }catch(e){
    // El presupuesto ya estaba aprobado: la ficha existe, solo falta enlazarla.
    if(!c.nro||!/ya está en el tablero/.test(e.message))throw e;
    const t=await pedir(API_T),p=c.nro.split('-'),l=t.cards.filter(k=>k.cliente_nro===p[0]&&(!p[1]||k.sub===+p[1]));
    if(l.length!==1)throw e;
    nro=l[0].cliente_nro;sub=l[0].sub;yaEstaba=true;
  }
  await pedir(API,{accion:'enlazar',id:c.id,cliente_nro:nro,sub:sub});
  // Lo que sigue completa la ficha nueva. Si algo falla, el enlace queda y se avisa qué faltó.
  const falto=[];
  if(!yaEstaba){
    const base={cliente_nro:nro,sub:sub};
    if(c.bacha)await pedir(API_T,Object.assign({accion:'bacha',origen:c.bacha,modelo:c.bacha_modelo},base)).catch(()=>falto.push('la pileta'));
    if(c.medicion)await pedir(API_T,Object.assign({accion:'fecha',fecha:c.medicion},base)).catch(()=>falto.push('la fecha de medición'));
    if(c.sena)await pedir(API_T,Object.assign({accion:'check',clave:'sena',valor:true,monto:c.sena.monto,moneda:c.sena.moneda},base)).catch(()=>falto.push('la seña'));
  }
  cerrarModal();
  toast(yaEstaba?'Ya estaba en el tablero: quedó enlazada.':('Cargada en Por medir.'+(falto.length?' Faltó copiar '+falto.join(', ')+': cargalo en el tablero.':'')));
  await cargar();
}
function pedirBorrar(){
  const c=CONTRATOS.find(x=>x.id===F.id);if(!c)return;
  const id=c.id;
  abrirModal('<h3>Borrar contrato</h3><div class="bajada">Se borra el contrato de <b>'+esc(ver(c).cliente)+'</b> con sus pagos. No se puede deshacer.'+(c.enlace?'<br>La ficha del tablero no se toca.':'')+'</div><div class="m-err" id="mErr" hidden></div><div class="btns"><button type="button" data-cerrar>Cancelar</button><button type="button" class="ok peligro" data-confirmar-borrar="'+esc(id)+'">Borrar</button></div>',true,false);
}
async function borrarPrueba(){
  const r=await pedir(API,{accion:'borrar_prueba'});
  // las fichas que se habían cargado en el tablero desde esos renglones también se van
  for(const e of (r.enlaces||[])){
    try{await pedir(API_T,{cliente_nro:e.cliente_nro,sub:e.sub,accion:'borrar'});await pedir(API_DEL,{cliente_nro:e.cliente_nro,sub:e.sub});}catch(x){}
  }
  cerrarModal();toast('Renglones de prueba borrados.');await cargar();
}

document.addEventListener('click',ev=>{
  const t=ev.target;
  if(t.closest('[data-nuevo]')){abrirFicha('');return;}
  if(t.closest('[data-prueba]')){const b=t.closest('[data-prueba]');conBoton(b,async()=>{try{await pedir(API,{accion:'prueba'});toast('Se cargaron 3 renglones de prueba.');await cargar();}catch(e){toast(e.message);throw e;}});return;}
  if(t.closest('[data-borrar-prueba]')){
    abrirModal('<h3>Borrar los de prueba</h3><div class="bajada">Se borran los renglones marcados PRUEBA. Si alguno se cargó en el tablero, esa ficha de prueba también se borra.</div><div class="m-err" id="mErr" hidden></div><div class="btns"><button type="button" data-cerrar>Cancelar</button><button type="button" class="ok peligro" data-confirmar-prueba>Borrar</button></div>',true,false);
    return;
  }
  let b;
  if((b=t.closest('[data-confirmar-prueba]'))){conBoton(b,borrarPrueba);return;}
  if((b=t.closest('[data-enviar]'))){pedirEnviar(b.dataset.enviar);return;}
  if((b=t.closest('[data-confirmar-enviar]'))){conBoton(b,()=>enviar(b.dataset.confirmarEnviar));return;}
  if((b=t.closest('[data-confirmar-borrar]'))){conBoton(b,async()=>{await pedir(API,{accion:'borrar',id:b.dataset.confirmarBorrar});cerrarModal();toast('Contrato borrado.');await cargar();});return;}
  if(t.closest('[data-cerrar]')){cerrarModal();return;}
  if((b=t.closest('[data-guardar]'))){conBoton(b,guardar);return;}
  if(t.closest('[data-borrar]')){pedirBorrar();return;}
  if((b=t.closest('[data-pago]'))){
    conBoton(b,async()=>{
      const m=num($('pMonto').value),como=$('pComo').value.split('|');
      if(!m)throw new Error('Cargá el monto del pago.');
      errModal('');
      await pedir(API,{accion:'pago',id:F.id,monto:m,moneda:como[1],medio:como[0],fecha:$('pFecha').value});
      const a=await pedir(API);CONTRATOS=a.contratos;
      const c=CONTRATOS.find(x=>x.id===F.id);if(c)F.pagos=(c.pagos||[]).slice();
      $('pMonto').value='';pintarPagos();dibujar();
      b.disabled=false;b.textContent='Agregar pago';
    });
    return;
  }
  if((b=t.closest('[data-borrar-pago]'))){
    const pid=b.dataset.borrarPago;
    conBoton(b,async()=>{
      await pedir(API,{accion:'borrar_pago',id:F.id,pago_id:pid});
      F.pagos=F.pagos.filter(x=>x.id!==pid);
      const c=CONTRATOS.find(x=>x.id===F.id);if(c)c.pagos=F.pagos.slice();
      pintarPagos();dibujar();
    });
    return;
  }
  if((b=t.closest('.opc button'))&&F){
    const campo=b.parentElement.dataset.opc,v=b.dataset.v;
    F[campo]=F[campo]===v?'':v; // tocar de nuevo lo deja sin marcar
    b.parentElement.querySelectorAll('button').forEach(x=>{const on=x.dataset.v===F[campo];x.classList.toggle('on',on);x.setAttribute('aria-pressed',on);});
    if(campo==='bacha')$('cModelo').hidden=F.bacha!=='nuestra';
    return;
  }
  if(t===$('modalBg')&&!protegida){cerrarModal();return;}
  if(t.closest('a'))return;
  const f=t.closest('.fila');
  if(f)abrirFicha(f.dataset.id);
});
document.addEventListener('keydown',ev=>{
  if(ev.key==='Escape'&&$('modalBg').classList.contains('show')&&!protegida){cerrarModal();return;}
  if((ev.key==='Enter'||ev.key===' ')&&ev.target.classList&&ev.target.classList.contains('fila')){ev.preventDefault();abrirFicha(ev.target.dataset.id);}
});
$('modal').addEventListener('input',ev=>{if(F&&['fTotU','fTotA','fSen','fSenM'].indexOf(ev.target.id)>=0)pintarPagos();});
$('modal').addEventListener('change',ev=>{if(F&&ev.target.id==='fSenM')pintarPagos();});
$('buscar').addEventListener('input',()=>{q=$('buscar').value;if(CONTRATOS.length)dibujar();});

// Catálogo de materiales de la calculadora, para escribir el material siempre igual ("Marca · Color").
pedir(API_COLORES).then(r=>{$('catalogo').innerHTML=(r.colors||[]).map(c=>'<option value="'+esc(c.full)+'">').join('');}).catch(()=>{});
cargar();

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
