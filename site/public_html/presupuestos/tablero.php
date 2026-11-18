<?php
// ============================================================
// /presupuestos/tablero.php — Tablero de obras
// ============================================================
// Las obras entran solas: una tarjeta por cada presupuesto "aprobado".
// Cinco columnas + cuadro de retiro en local. La tarjeta se arrastra de
// una columna a otra, o se avanza con el botón Siguiente.
// Datos y reglas: /calculadora/api/tablero.php
//
// Acceso: sesión de auth_check.php + permiso 'tablero' (ver AUTH_PERMISOS).
// ============================================================

require_once __DIR__ . '/../calculadora/auth_check.php';

// Sin sesión va al login; con sesión pero sin permiso, a la primera pantalla de su rol.
auth_exigir_pagina('tablero');

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
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
/* Al tocar: sin el destello gris del navegador ni la espera del doble toque. */
a,button,input,.toggle{-webkit-tap-highlight-color:transparent;touch-action:manipulation}
/* Con teclado, se ve dónde está el foco. */
button:focus-visible,a:focus-visible,.toggle:focus-visible{outline:2px solid var(--gd);outline-offset:2px}

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
/* Los efectos al pasar el mouse van solo donde hay mouse: en pantallas táctiles quedaban "pegados" después de tocar. */
@media(hover:hover){.logout-link:hover{opacity:1;color:var(--cr)}}
.h-tabs{background:var(--dk);display:flex;align-items:center;padding:0 24px;border-top:1px solid rgba(196,167,125,.12);overflow-x:auto;scrollbar-width:thin;scrollbar-color:var(--gd) transparent}
.h-tabs a{color:var(--gd);opacity:.55;text-decoration:none;font-size:.7rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;padding:11px 18px;white-space:nowrap;border-bottom:2px solid transparent}
@media(hover:hover){.h-tabs a:hover{opacity:1;color:var(--cr)}}
.h-tabs a.active{opacity:1;color:var(--gd);border-bottom-color:var(--gd)}
/* Encabezado en celulares angostos: el nombre no empuja a los botones fuera de la pantalla. */
.header>div{min-width:0}
.header h1{white-space:nowrap}
@media(max-width:380px){
  .header h1{font-size:1.15rem}
  .header img{height:30px!important}
  .header .h-right{gap:8px}
}

/* === TABLERO === */
.wrap{max-width:1480px;margin:0 auto;padding:20px 20px 60px}
.aviso{color:var(--text2);font-size:13.5px;padding:40px 16px;text-align:center;line-height:1.6}
.aviso b{color:var(--text)}

/* === MODO UNA FICHA (embebida en el calendario): sin encabezado, sin columnas, solo la ficha === */
.solo body{background:var(--card)}
.solo .header,.solo .h-tabs,.solo .gold-line,.solo .tope,.solo .avisos-g,.solo .col-tabs,
.solo .col-h,.solo .col-avisos,.solo .col-sub,.solo .col-acc,.solo .col-filtros,.solo .col-sep,.solo .col-sem,.solo .c-prox{display:none!important}
.solo .board .col{max-height:none;overflow:visible}
.solo .wrap{padding:0;max-width:none}
.solo .board{display:block;overflow:visible;padding:0}
.solo .col{display:block!important;background:none;border:none;padding:0;min-height:0}
.solo .card{margin:0;box-shadow:none;cursor:default}
.solo .aviso{padding:30px 10px}

/* === BUSCADOR === */
.tope{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px}
.tope-d{display:flex;align-items:center;gap:16px}
.tope-b{display:flex;align-items:center;gap:8px}
.vista-b{padding:7px 12px;border:1px solid var(--border);border-radius:999px;background:var(--card);font-size:12.5px;font-weight:700;color:var(--text2);cursor:pointer;white-space:nowrap}
@media(hover:hover){.vista-b:hover{border-color:var(--gd);color:var(--text)}}
.hoy{font-size:14px;color:var(--text2);white-space:nowrap}
.hoy b{font-weight:700;color:var(--text)}
/* Fecha de hoy y semana en curso: versión larga en pantallas anchas, corta en celular. */
.hoy .f-c,.hoy .s-c{display:none}
.hoy .sem{color:var(--text2);font-weight:600}
@media(max-width:699px){
  .hoy .f-l,.hoy .s-l{display:none}
  .hoy .f-c,.hoy .s-c{display:inline}
}
.buscar{display:flex;align-items:center;gap:10px;flex:1;min-width:0;position:relative}
.buscar input{width:100%;max-width:380px;padding:10px 38px 10px 14px;border:1px solid var(--border);border-radius:999px;background:var(--card);color:var(--text);font-family:inherit;font-size:14px;outline:none;-webkit-appearance:none;appearance:none}
.buscar input::-webkit-search-cancel-button{display:none}
.buscar input:focus{border-color:var(--gd);box-shadow:0 0 0 3px var(--gd-soft)}
.buscar input.activo{border-color:var(--gd);background:var(--gd-soft);font-weight:600}
.buscar button{margin-left:-46px;width:30px;height:30px;border:none;border-radius:50%;background:none;font-size:20px;line-height:1;color:var(--text3);cursor:pointer;flex-shrink:0}
@media(hover:hover){.buscar button:hover{color:var(--text)}}
.buscar span{font-size:12.5px;color:var(--text3);font-weight:600;white-space:nowrap}

/* === COLOR DE CADA ETAPA ===
   El mismo color en la columna, la tarjeta, la pestaña del celular y el
   botón que lleva a esa etapa. Se aprende una vez y se reconoce de lejos. */
:root{--et:var(--gd);--et-soft:var(--gd-soft);--et-on:#fff}
[data-theme="dark"]{--et-on:#14110D}
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

.col-tabs{display:none}
.board{display:grid;grid-template-columns:repeat(var(--ncols,5),minmax(200px,1fr));gap:14px;align-items:start;overflow-x:auto;padding-bottom:4px}
.col{background:var(--card-alt);border:1px solid var(--border);border-top:4px solid var(--et);border-radius:var(--radius);padding:12px;min-height:120px;transition:background .15s,opacity .15s}
.col-h{display:flex;align-items:center;justify-content:space-between;padding:2px 4px 12px}
.col .card:last-child{margin-bottom:0}
/* En computadora la columna tiene alto fijo y se recorre por dentro; su título queda siempre a la vista. */
@media(min-width:1100px){
  .board .col{max-height:var(--col-max,none);overflow-y:auto;scrollbar-width:thin;scrollbar-color:var(--border) transparent}
  .board .col-h{position:sticky;top:-12px;z-index:3;background:inherit;margin:-12px -12px 0;padding:14px 16px 10px;border-radius:var(--radius) var(--radius) 0 0}
  .board .col::-webkit-scrollbar{width:8px}
  .board .col::-webkit-scrollbar-thumb{background:var(--border);border-radius:8px}
}
/* Papelera: lista de fichas borradas, cada una con su botón para recuperarla */
.pap{margin-top:12px;border:1px solid var(--border);border-radius:var(--radius-sm);max-height:52vh;overflow-y:auto}
.pap-f{display:flex;align-items:center;gap:12px;padding:10px 12px;border-top:1px solid var(--border-soft)}
.pap-f:first-child{border-top:none}
.pap-f>div{flex:1;min-width:0}
.pap-f b{display:block;font-size:14px;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pap-f small{display:block;margin-top:2px;font-size:12px;color:var(--text3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pap-f button{flex-shrink:0;padding:8px 14px;border:1px solid var(--gr);border-radius:999px;background:var(--card);font-family:inherit;font-size:13px;font-weight:700;color:var(--gr);cursor:pointer}
.pap-f button:disabled{opacity:.6;cursor:default}
/* Plegar una etapa: se cierra hacia arriba y queda solo su título, con el contador (y un "!" si
   tiene algo por avanzar). No cambia de ancho ni mueve a las demás. Solo en computadora: en tablet
   y celular ya se ve de a una etapa por vez. */
.col-pl,.col-al{display:none}
@media(min-width:1100px){
  .col-h{gap:8px}
  .col-h h2{flex:1;min-width:0}
  .col-pl{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;margin-right:-4px;border:none;border-radius:6px;background:transparent;color:var(--text3);cursor:pointer;flex-shrink:0}
  .col-pl svg{width:16px;height:16px}
  .col-pl:focus-visible{outline:2px solid var(--gd);outline-offset:1px}
  .board .col.cerrada{min-height:0;padding-bottom:0;overflow:hidden;cursor:pointer}
  .board .col.cerrada>:not(.col-h){display:none}
  .board .col.cerrada .col-h{position:static;padding-bottom:14px}
  .board .col.cerrada .col-al{display:flex;align-items:center;justify-content:center;width:18px;height:18px;flex-shrink:0;border-radius:50%;background:var(--red);color:#fff;font-style:normal;font-size:11px;font-weight:800}
}
@media(min-width:1100px) and (hover:hover){
  .col-pl:hover{background:var(--card);color:var(--text)}
  .board .col.cerrada:hover{background:var(--et-soft)}
}
@media(min-width:1100px) and (pointer:coarse){
  .col-pl{width:40px;height:40px}
}
/* Tablet acostada y notebooks chicas: columnas más angostas, así que se ajustan los márgenes. */
@media(min-width:1100px) and (max-width:1279px){
  .wrap{padding:16px 16px 48px}
  .board{gap:10px}
  .col{padding:8px}
  .board .col-h{top:-8px;margin:-8px -8px 0;padding:12px 12px 10px}
  .col-h h2{font-size:16px}
  .card{padding:12px 11px}
  .card.plegada{padding:10px 11px 11px}
  .card.plegada .c-prox{margin:-10px -11px 9px -11px}
  .c-prox{margin:-12px -11px 11px -11px;padding:5px 11px}
}
.col-h h2{font-family:'Fraunces',serif;font-size:17px;font-weight:600;display:flex;align-items:center;gap:8px}
.col-h h2::before{content:'';width:10px;height:10px;border-radius:50%;background:var(--et);flex-shrink:0}
.col-n{font-size:12px;font-weight:700;color:var(--et-on);background:var(--et);border-radius:999px;min-width:26px;padding:2px 8px;text-align:center;font-variant-numeric:tabular-nums}
.col-sub{font-size:11.5px;color:var(--text3);padding:0 4px 10px}
.col-vacia{color:var(--text3);font-size:13px;text-align:center;padding:22px 8px}

/* === ARRASTRE: qué se ve mientras hay una tarjeta en el aire ===
   puede  → columna a la que se puede soltar: se enciende en su color
   bloq   → falta algún tilde para llegar ahí: se apaga, y en rojo si se pasa por encima
   origen → de dónde salió */
.col.puede{background:var(--et-soft);outline:2px dashed var(--et);outline-offset:2px}
.col.puede.drop{outline:3px solid var(--et);background:color-mix(in srgb,var(--et) 20%,var(--card-alt))}
.col.bloq{opacity:.5}
.col.bloq.drop{opacity:1;outline:3px solid var(--red);outline-offset:2px;background:var(--red-soft)}
.col.origen{opacity:.8}
.slot{border:2px dashed var(--et);border-radius:var(--radius);padding:14px 10px;margin-bottom:10px;text-align:center;font-size:13px;font-weight:700;color:var(--et);background:var(--card)}
.slot.bloq{border-color:var(--red);color:var(--red);background:var(--card)}

/* Retiro en local: cuadro aparte, debajo de las columnas */
#aparte{margin-top:14px}
#aparte .col{min-height:0}
#aparte .cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:10px;align-items:start}
#aparte .card{margin-bottom:0}

/* === TARJETA === */
.card{position:relative;background:var(--card);border:1px solid var(--border);border-left:4px solid var(--et);border-radius:var(--radius);padding:14px;margin-bottom:10px;box-shadow:var(--shadow-sm);cursor:grab;user-select:none;-webkit-user-select:none;-webkit-touch-callout:none}
/* FICHA PLEGADA: dos o tres renglones. Se abre al tocarla; la flechita de la abierta la vuelve a plegar. */
.card.plegada{padding:11px 14px 12px;cursor:pointer}
.card.plegada .c-id{font-size:14px}
.card.plegada .c-prox{margin:-11px -14px 10px -14px}
.card.plegada .c-nota{margin-top:8px;padding:4px 10px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.c-res{display:flex;flex-wrap:wrap;gap:5px 6px;margin-top:9px}
.c-res .r{max-width:100%;overflow:hidden;text-overflow:ellipsis;padding:2px 9px;border-radius:999px;background:var(--card-alt);color:var(--text2);font-size:11.5px;font-weight:700;white-space:nowrap;font-variant-numeric:tabular-nums}
.c-res .r.rojo{background:var(--red-soft);color:var(--red)}
.c-res .r.amb{background:var(--amb-soft);color:var(--amb)}
.c-res .r.ok{background:var(--gr-soft);color:var(--gr)}
.c-res .r.hoy{background:var(--gd);color:#1A1816}
.c-plegar{position:absolute;top:8px;right:8px;width:28px;height:28px;border:none;border-radius:50%;background:none;cursor:pointer;z-index:1}
.c-plegar::before{content:'';position:absolute;left:10px;top:12px;width:7px;height:7px;border-left:2px solid var(--text3);border-top:2px solid var(--text3);transform:rotate(45deg)}
@media(hover:hover){.c-plegar:hover{background:var(--card-alt)}}
.card.primera .c-plegar{top:1px}
.card:not(.plegada):not(.primera) .c-id{padding-right:28px}
.fantasma .c-plegar{display:none}
@media(hover:hover){.card:hover{border-color:var(--et)}}
/* Algo la frena (atrasada, sin turno, se pisa): marco rojo. Un solo marco, sin doble borde. */
.card.alerta,.card.sin-turno,.card.choque{border-color:var(--red);border-left-color:var(--et)}
/* el hueco que deja la tarjeta mientras viaja */
.card.arrastrando{background:var(--et-soft);border:2px dashed var(--et);box-shadow:none}
.card.arrastrando>*{visibility:hidden}
/* la tarjeta en el aire: toma el color de la etapa sobre la que está */
.fantasma{position:fixed;left:0;top:0;z-index:800;pointer-events:none;margin:0;border:2px solid var(--et);box-shadow:var(--shadow-lg);cursor:grabbing;transition:border-color .12s}
.fantasma .f-aviso{display:none;margin:-14px -14px 12px -14px;padding:7px 12px;border-radius:7px 7px 0 0;background:var(--et);color:var(--et-on);font-size:12.5px;font-weight:700}
.fantasma.va .f-aviso,.fantasma.bloq .f-aviso{display:block}
.fantasma.bloq{border-color:var(--red)}
.fantasma.bloq .f-aviso{background:var(--red);color:#fff}
/* con el dedo viaja una versión chica, debajo del dedo, para no tapar las pestañas */
.fantasma.mini>*:not(.f-aviso){display:none}
.fantasma.mini>.c-id,.fantasma.mini>.c-mat{display:block}
body.arrastre,body.arrastre *{cursor:grabbing!important}
/* al llegar a la etapa nueva, late una vez en el color de esa etapa; si no pudo pasar, sacude */
@keyframes llego{0%{transform:scale(1.04);box-shadow:0 0 0 0 var(--et)}55%{transform:none;box-shadow:0 0 0 9px transparent}100%{box-shadow:var(--shadow-sm)}}
@keyframes nope{0%,100%{transform:none}20%,60%{transform:translateX(-7px)}40%,80%{transform:translateX(7px)}}
.card.llego{animation:llego 1s ease-out;background:var(--et-soft)}
.card.nope{animation:nope .45s;border-color:var(--red)}
@media(prefers-reduced-motion:reduce){.card.llego,.card.nope{animation:none}}
.c-id{font-size:15px;font-weight:600;line-height:1.3;overflow-wrap:anywhere}
/* Lugar en la cola: 1, 2, 3… La 1 es la próxima a atender y va enmarcada en el color de la etapa. */
.c-pos{display:inline-flex;align-items:center;justify-content:center;min-width:24px;height:24px;padding:0 6px;margin-right:8px;border-radius:999px;border:1.5px solid var(--et);color:var(--et);font-size:13px;font-weight:800;font-variant-numeric:tabular-nums;vertical-align:1px}
/* La próxima: marco entero en el color de la etapa (pisa al rojo; el motivo sigue en las pastillas). */
.card.primera,.card.primera.alerta,.card.primera.sin-turno,.card.primera.choque{border-color:var(--et);outline:2px solid var(--et);outline-offset:-1px}
.card.primera .c-pos{background:var(--et);color:var(--et-on)}
.c-prox{margin:-14px -14px 12px -14px;padding:5px 14px;border-radius:9px 9px 0 0;background:var(--et-soft);color:var(--et);font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
.fantasma .c-prox{display:none}
.c-id b{font-weight:700;font-variant-numeric:tabular-nums}
.c-id .zona{color:var(--text3);font-weight:500}
.c-id b.sin-nro{color:var(--text3);font-weight:700}
.c-mat{color:var(--text2);font-size:13px;margin-top:3px;overflow-wrap:anywhere}
.c-contrato{display:inline-flex;align-items:center;gap:5px;margin-top:7px;padding:3px 0;font-size:10.5px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:var(--gr);text-decoration:none;white-space:nowrap}
.c-contrato svg{width:12px;height:12px;flex-shrink:0}
.c-contrato:hover,.c-contrato:focus-visible{text-decoration:underline}

.c-fecha{display:inline-flex;align-items:center;gap:6px;margin-top:9px;padding:6px 10px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--card);font-size:12.5px;font-weight:600;color:var(--text2);cursor:pointer}
@media(hover:hover){.c-fecha:hover{border-color:var(--gd)}}
.c-fecha.vacia{border-style:dashed;color:var(--gdd);background:var(--gd-soft)}
[data-theme="dark"] .c-fecha.vacia{color:var(--gd)}
.c-fecha.hoy{background:var(--gd);border-color:var(--gd);color:#1A1816}
.c-fecha.atrasada{background:var(--red-soft);border-color:var(--red);color:var(--red)}
/* MEDIDO: botón para marcar la medición hecha, y cómo queda después */
.c-turno{display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin-top:10px}
.c-turno .c-fecha{margin-top:0}
.c-hecho{display:block;width:100%;margin-top:12px;padding:12px;border-radius:var(--radius-sm);border:2px solid var(--et);background:var(--et);color:var(--et-on);font-size:15px;font-weight:800;letter-spacing:.01em;text-align:center;cursor:pointer;box-shadow:var(--shadow-sm)}
@media(hover:hover){.c-hecho:hover{filter:brightness(1.08)}}
.c-hecho.listo{background:var(--gr);border-color:var(--gr);color:#fff}
[data-theme="dark"] .c-hecho.listo{color:#04220F}
.c-hecho.listo::before{content:'✓  '}
/* Títulos de sección dentro de una columna.
   Lo que falta hacer: texto gris con una línea punteada arriba (separa sin gritar). */
.col-sep{margin:16px 2px 10px;padding-top:12px;border-top:2px dashed var(--border);font-size:11.5px;font-weight:700;color:var(--text3);letter-spacing:.02em}
/* Lo ya hecho que espera control: banda verde con tilde y contador. Va primero en la columna. */
.col-sep.arriba{display:flex;align-items:center;gap:9px;margin:2px 0 10px;padding:8px 10px;border:1px solid color-mix(in srgb,var(--gr) 35%,var(--card));border-radius:var(--radius-sm);background:var(--gr-soft);font-size:12.5px;color:var(--gr);letter-spacing:0;line-height:1.3}
.col-sep.arriba::before{content:'✓';flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:var(--gr);color:#fff;font-size:12px;font-weight:800}
[data-theme="dark"] .col-sep.arriba::before{color:#04220F}
.col-sep.arriba .t{flex:1;min-width:0}
.col-sep.arriba small{display:block;margin-top:1px;font-size:11px;font-weight:600;color:var(--gr);opacity:.8}
.col-sep.arriba b{flex-shrink:0;min-width:24px;padding:2px 8px;border-radius:999px;background:var(--gr);color:#fff;font-size:12px;font-weight:800;text-align:center;font-variant-numeric:tabular-nums}
[data-theme="dark"] .col-sep.arriba b{color:#04220F}
/* AVISO "!" en rojo: la obra no tiene fecha (Por medir) o turno (Por instalar). Sin fecha no hay
   plazo, y lo que no tiene plazo se queda olvidado: se marca igual de fuerte que lo atrasado. */
.c-fecha.aviso{background:var(--red-soft);border:1.5px solid var(--red);color:var(--red);font-weight:700}
.c-fecha.aviso::before,.col-aviso::before,.aviso-g::before,.c-choque::before{content:'!';display:inline-flex;align-items:center;justify-content:center;width:17px;height:17px;border-radius:50%;background:var(--red);color:var(--card);font-size:12px;font-weight:800;line-height:1;flex-shrink:0}
.col-avisos{display:flex;flex-wrap:wrap;align-items:center;gap:6px;padding:0 2px 12px}
/* Avisos de todo el tablero (plazo prometido al cliente). Tocarlos deja a la vista solo esas fichas. */
.avisos-g{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;scrollbar-width:none}
.avisos-g::-webkit-scrollbar{display:none}
.avisos-g:empty{display:none}
.aviso-g{display:inline-flex;align-items:center;gap:7px;border:1.5px solid var(--red);font-family:inherit;cursor:pointer;padding:6px 14px 6px 6px;border-radius:999px;background:var(--red-soft);color:var(--red);font-size:13px;font-weight:700;white-space:nowrap}
.aviso-g.on{background:var(--red);color:var(--card)}
/* Limpiar filtros: oscuro, para que no se confunda con un aviso rojo ni con un filtro. */
.limpiar-f{display:inline-flex;align-items:center;gap:7px;flex-shrink:0;border:1.5px solid var(--dk);font-family:inherit;cursor:pointer;padding:6px 14px 6px 10px;border-radius:999px;background:var(--dk);color:var(--cr);font-size:13px;font-weight:700;white-space:nowrap}
.limpiar-f::before{content:'×';font-size:18px;line-height:.8;font-weight:600}
[data-theme="dark"] .limpiar-f{background:var(--gd);border-color:var(--gd);color:#1A1816}
.aviso-g.on::before{background:var(--card);color:var(--red)}
.aviso-g.cerca{border-color:var(--amb);background:var(--amb-soft);color:var(--amb)}
.aviso-g.cerca::before{background:var(--amb)}
.aviso-g.cerca.on{background:var(--amb);color:var(--card)}
.aviso-g.cerca.on::before{background:var(--card);color:var(--amb)}
/* Dos instalaciones que comparten instalador el mismo día, casi a la misma hora. */
.c-choque{display:flex;align-items:flex-start;gap:6px;margin-top:8px;font-size:12.5px;font-weight:700;color:var(--red);overflow-wrap:anywhere}
.col-aviso-det{font-size:11.5px;font-weight:600;color:var(--red)}
.col-aviso{display:inline-flex;align-items:center;gap:5px;border:none;font-family:inherit;cursor:pointer;padding:3px 10px 3px 3px;border-radius:999px;background:var(--red-soft);color:var(--red);font-size:11.5px;font-weight:700;white-space:nowrap}

.c-promesa{font-size:12.5px;color:var(--text3);margin-top:8px;line-height:1.5}
.c-promesa.cerca{color:var(--amb);font-weight:600}
.c-promesa.vencida{color:var(--red);font-weight:700}
.c-nota{margin-top:10px;padding:7px 10px;border-radius:var(--radius-sm);background:var(--amb-soft);color:var(--amb);font-size:12.5px;font-weight:600;overflow-wrap:anywhere}

.c-checks{display:flex;flex-wrap:wrap;gap:7px;margin-top:12px}
.chk{display:inline-flex;align-items:center;gap:7px;padding:7px 11px 7px 8px;border-radius:999px;border:1px solid var(--border);background:var(--card);font-size:12.5px;font-weight:600;color:var(--text2);cursor:pointer;text-align:left}
.chk::before{content:'';flex-shrink:0;width:16px;height:16px;border-radius:5px;border:1.5px solid var(--text3);display:flex;align-items:center;justify-content:center;font-size:11px;line-height:1;color:#fff}
@media(hover:hover){.chk:hover{border-color:var(--gd)}}
.chk.on{background:var(--gr-soft);border-color:var(--gr);color:var(--gr)}
.chk.on::before{content:'✓';background:var(--gr);border-color:var(--gr)}
[data-theme="dark"] .chk.on::before{color:#04220F}

/* el botón lleva el color de la etapa a la que manda */
.c-avanzar{display:block;width:100%;margin-top:14px;padding:10px 12px;border:1px solid var(--et);border-radius:var(--radius-sm);background:var(--et);color:var(--et-on);font-size:13.5px;font-weight:700;cursor:pointer}
@media(hover:hover){.c-avanzar:hover{filter:brightness(1.08)}}
.c-avanzar.falta{background:none;color:var(--text3);border:1px dashed var(--border);font-weight:600}
@media(hover:hover){.c-avanzar.falta:hover{filter:none;color:var(--et);border-color:var(--et)}}

.c-mas{display:block;margin:10px auto -2px;padding:4px 10px;border:none;background:none;font-size:12px;font-weight:600;color:var(--text3);cursor:pointer}
@media(hover:hover){.c-mas:hover{color:var(--text)}}
.c-det{margin-top:10px;padding-top:12px;border-top:1px solid var(--border-soft);font-size:13px;display:grid;gap:7px;cursor:auto;user-select:text;-webkit-user-select:text}
.c-det .fila{display:flex;gap:8px}
.c-det .fila span{color:var(--text3);flex:0 0 74px}
/* Hitos: lo ya hecho en etapas anteriores, con su fecha, a la vista en la ficha */
.c-hitos{display:flex;flex-wrap:wrap;gap:5px 6px;margin-top:9px}
.c-hitos span{padding:2px 8px;border-radius:999px;background:var(--gr-soft);color:var(--gr);font-size:11.5px;font-weight:600;white-space:nowrap}
.c-hitos b{font-weight:800;font-variant-numeric:tabular-nums}
/* Historial completo, en "Más" */
.c-hist{font-size:12.5px;border:1px solid var(--border-soft);border-radius:var(--radius-sm);padding:8px 10px}
.c-hist .t{font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--text3);margin-bottom:2px}
.c-hist .e{margin-top:7px;color:var(--text)}
.c-hist .e span{color:var(--text3);font-weight:500}
.c-hist .i{display:flex;justify-content:space-between;gap:10px;padding:2px 0 2px 10px;color:var(--text2)}
.c-hist .i span:last-child{color:var(--text3);white-space:nowrap;font-variant-numeric:tabular-nums}
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
.modal{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-lg);--mp:24px;padding:var(--mp);max-width:440px;width:100%;max-height:calc(100vh - 32px);overflow-y:auto}
.modal h3{font-family:'Fraunces',serif;font-size:20px;font-weight:600;margin-bottom:16px}
.modal p{color:var(--text2);font-size:13.5px;line-height:1.55;margin-bottom:8px}
.modal .sena{display:flex;gap:8px;margin-top:4px}
.modal .sena input{flex:1;min-width:0;padding:11px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--card);color:var(--text);font-family:inherit;font-size:17px;font-weight:700;outline:none;font-variant-numeric:tabular-nums}
.modal .sena input:focus{border-color:var(--gd);box-shadow:0 0 0 3px var(--gd-soft)}
.modal .sena button{padding:11px 14px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--card);font-size:14px;font-weight:700;color:var(--text2);cursor:pointer}
.modal .sena button.on{background:var(--dk);color:var(--cr);border-color:var(--dk)}
.modal .sena{flex-wrap:wrap}
.modal .sena input[type=date],.modal .sena input[type=time]{flex:1 1 130px;font-size:16px;font-weight:600}
.modal .sena [data-op]{flex:1 1 28%;white-space:nowrap}
[data-theme="dark"] .modal .sena button.on{background:var(--gd);color:#1A1816;border-color:var(--gd)}
.modal .lnk{background:none;border:none;padding:0;font-size:12.5px;font-weight:700;color:var(--red);text-decoration:underline;cursor:pointer}
.modal .m-error{margin:16px 0 0;padding:10px 12px;border-radius:var(--radius-sm);background:var(--red-soft);color:var(--red);font-weight:700}
.modal .btns button:disabled{opacity:.6;cursor:default}
.modal .btns{display:flex;gap:10px;justify-content:flex-end;position:sticky;bottom:calc(-1*var(--mp));z-index:1;background:var(--card);margin:12px calc(-1*var(--mp)) calc(-1*var(--mp));padding:14px var(--mp) var(--mp);border-radius:0 0 var(--radius) var(--radius)}
.modal .btns button{padding:9px 16px;border-radius:var(--radius-sm);font-size:13.5px;font-weight:700;cursor:pointer;border:1px solid var(--border);background:var(--card)}
.modal .btns .ok{background:var(--dk);color:var(--cr);border-color:var(--dk)}
[data-theme="dark"] .modal .btns .ok{background:var(--gd);color:#1A1816;border-color:var(--gd)}
.modal .btns .ok.peligro,[data-theme="dark"] .modal .btns .ok.peligro{background:var(--red);border-color:var(--red);color:#fff}
.c-det .lnk.rojo{color:var(--red)}
.toast{position:fixed;left:50%;bottom:24px;transform:translate(-50%,20px);background:var(--dk);color:var(--cr);padding:11px 16px;border-radius:var(--radius-sm);border-left:3px solid var(--red);box-shadow:var(--shadow-lg);font-size:13px;font-weight:600;opacity:0;pointer-events:none;transition:opacity .2s,transform .2s;z-index:1000;max-width:calc(100vw - 32px)}
.toast.show{opacity:1;transform:translate(-50%,0)}
.toast.ok{border-left-color:var(--gr)}

/* === CARGAR OBRA A MANO (botón de la columna Por medir) === */
.col-acc{display:flex;gap:8px;align-items:stretch;margin-bottom:12px}
.col-fb{flex:0 0 auto;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);background:var(--card);font-size:12.5px;font-weight:700;color:var(--text2);cursor:pointer;white-space:nowrap}
.col-fb::after{content:'';display:inline-block;margin-left:7px;width:6px;height:6px;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:translateY(-3px) rotate(45deg)}
.col-fb.on::after{transform:translateY(1px) rotate(-135deg)}
.col-fb.on{border-color:var(--et);color:var(--et)}
@media(hover:hover){.col-fb:hover{border-color:var(--et);color:var(--et)}}
.modal .campo{display:block;margin-bottom:16px;font-size:12px;font-weight:700;color:var(--text2)}
.modal .campo+.sena{margin:-10px 0 0}
.modal .campo[hidden]{display:none}
.modal .sena+.campo{margin-top:16px}
.modal .campo.aire{margin-top:24px}
.modal .campo+p{margin:-10px 0 16px;font-size:12px}
.modal .campo input{display:block;width:100%;margin-top:6px;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--card);color:var(--text);font-family:inherit;font-size:15px;font-weight:500;outline:none}
.modal .campo input:focus{border-color:var(--gd);box-shadow:0 0 0 3px var(--gd-soft)}

/* Sugerencias de material (catálogo de la calculadora) */
.modal .mat-wrap{position:relative}
.mat-caja{display:none;position:absolute;left:0;right:0;top:100%;z-index:60;margin-top:4px;max-height:232px;overflow-y:auto;background:var(--card);border:1px solid var(--border);border-radius:var(--radius-sm);box-shadow:var(--shadow-lg)}
.mat-caja.open{display:block}
.mat-item{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:9px 12px;font-size:13.5px;font-weight:600;color:var(--text);cursor:pointer;border-bottom:1px solid var(--border-soft)}
.mat-item:last-child{border-bottom:none}
.mat-item.active{background:var(--gd-soft)}
@media(hover:hover){.mat-item:hover{background:var(--gd-soft)}}
.mat-item i{font-style:normal;font-size:11px;font-weight:700;color:var(--text2);background:var(--card-alt);padding:2px 8px;border-radius:999px;white-space:nowrap}
.mat-nada{padding:10px 12px;font-size:12.5px;font-weight:500;color:var(--text3)}
/* Dónde empieza cada semana de entrega, en las colas sin turno */
.col-sem{margin:14px 2px 8px;font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--text3)}
.col-sem.rojo{color:var(--red)}

/* Filtros de Por medir: fichas sin plano / sin foto de bacha */
.col-filtros{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px}
.col-filtros button{padding:6px 10px;border:1px solid var(--border);border-radius:999px;background:var(--card);font-size:12px;font-weight:700;color:var(--text2);cursor:pointer}
.col-filtros button i{font-style:normal;opacity:.7;font-variant-numeric:tabular-nums}
.col-filtros button.on{background:var(--et);border-color:var(--et);color:var(--et-on)}
/* Filtros con algo urgente adentro ("Necesitan continuar", "Sin fecha", "Sin turno", "Se pisan"):
   sin prender se ven igual que los demás, y lo urgente lo marca solo el número en rojo. Antes iban
   pintados de rojo y parecían ya seleccionados. Prendido hay un solo aspecto para todos: relleno
   en el color de la etapa. */
.col-filtros button.urg i,.col-filtros button.avi i{opacity:1;color:var(--red);font-weight:800}
.col-filtros button.on i,.col-filtros button.urg.on i,.col-filtros button.avi.on i{color:inherit;opacity:.85}

/* === ADJUNTOS: foto de bacha y plano === */
.c-adj{display:flex;flex-wrap:wrap;gap:7px;margin-top:12px}
.adj{position:relative;width:60px;height:60px;border-radius:8px;border:1px solid var(--border);background:var(--card-alt);padding:0;overflow:hidden;cursor:pointer;flex-shrink:0}
@media(hover:hover){.adj:hover{border-color:var(--et)}}
.adj img{width:100%;height:100%;object-fit:cover;display:block}
.adj span{position:absolute;left:0;right:0;bottom:0;background:rgba(20,17,13,.74);color:#fff;font-size:9.5px;font-weight:700;line-height:1.6;text-align:center;letter-spacing:.02em}
.adj.pdf,.adj.link{font-size:13px;font-weight:800;color:var(--text2);padding-bottom:14px}
.adj.link{font-size:12px;color:var(--gdd)}
[data-theme="dark"] .adj.link{color:var(--gd)}
.visor-link{margin:0 0 14px;padding:12px;border-radius:var(--radius-sm);background:var(--card-alt);font-size:13px;color:var(--text2);word-break:break-all}
.modal .adjuntar.subir{margin-top:0;border-style:solid;color:var(--text)}
.modal .adj-o{margin:14px 0;text-align:center;font-size:12px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.08em}
.adj.mas{border:1.5px dashed var(--et);background:var(--et-soft);color:var(--et);font-size:10.5px;font-weight:700;line-height:1.25;padding:3px}
.visor img,.visor canvas{display:block;max-width:100%;max-height:60vh;margin:0 auto 12px;border-radius:6px;background:var(--card-alt)}
.visor .girar{border:1px solid var(--border);border-radius:999px;background:var(--card);padding:6px 14px;font-family:inherit;font-size:13px;font-weight:700;color:var(--text2);cursor:pointer}
.visor .girar::before{content:'↻';margin-right:6px;font-size:14px}
.visor .acciones{display:flex;justify-content:space-between;align-items:center;gap:12px;font-size:13.5px}
.visor a{color:var(--gdd);font-weight:700}
[data-theme="dark"] .visor a{color:var(--gd)}
.visor .borrar{border:none;background:none;padding:0;font-size:13.5px;font-weight:700;color:var(--red);cursor:pointer;text-decoration:underline}
.modal .adjuntar{display:block;width:100%;margin-top:12px;padding:11px;border:1.5px dashed var(--border);border-radius:var(--radius-sm);background:none;font-size:13.5px;font-weight:700;color:var(--text2);cursor:pointer}
@media(hover:hover){.modal .adjuntar:hover{border-color:var(--gd);color:var(--text)}}

/* === PANTALLAS TÁCTILES: todo lo que se toca mide lo que mide un dedo ===
   Vale para celular y tablet, y también para una tablet acostada que muestra las 5 columnas. */
@media(max-width:1099px),(pointer:coarse){
  .chk{padding:10px 13px 10px 10px;font-size:13px}
  .chk::before{width:18px;height:18px}
  .c-hecho{padding:14px 12px;font-size:16px}
  .c-fecha{padding:9px 12px;font-size:13px}
  .c-avanzar{padding:13px 12px;font-size:14px}
  .c-mas{padding:10px 16px;font-size:13px}
  .c-plegar{width:36px;height:36px;top:4px;right:4px}
  .c-plegar::before{left:14px;top:16px}
  .card.primera .c-plegar{top:-2px}
  .card:not(.plegada):not(.primera) .c-id{padding-right:34px}
  .c-det .lnk{padding:8px 0;font-size:13.5px}
  .c-det .acciones{gap:6px 20px}
  .vista-b{padding:9px 14px;font-size:13px}
  .col-fb{padding:10px 14px}
  .col-filtros button{padding:8px 13px;font-size:12.5px}
  .col-aviso{padding:6px 12px 6px 5px;font-size:12px}
  .aviso-g{padding:8px 15px 8px 7px}
  .limpiar-f{padding:8px 15px 8px 11px}
  .modal .btns button{padding:11px 18px;font-size:14px}
  .modal .lnk,.visor .borrar,.visor a{padding:8px 0;display:inline-block}
  /* Con menos de 16 px, el iPhone y el iPad hacen zoom solos al tocar un campo. */
  .modal .campo input,.c-det input,.buscar input{font-size:16px}
  .toggle{width:54px;height:30px}
  .toggle::after{top:3px}
  .logout-link{width:36px;height:36px;opacity:.7}
  .logout-link svg{width:16px;height:16px}
}

/* === CELULAR Y TABLET PARADA: una etapa a la vez, con pestañas === */
@media(max-width:1099px){
  .header{padding:12px max(14px,env(safe-area-inset-right)) 12px max(14px,env(safe-area-inset-left))}
  .header .h-right > span{display:none}
  .h-tabs{padding:0 8px;scrollbar-width:none}
  .h-tabs::-webkit-scrollbar{display:none}
  .h-tabs a{padding:12px 12px;font-size:.62rem;letter-spacing:.06em}
  .wrap{padding:0 max(12px,env(safe-area-inset-right)) calc(60px + env(safe-area-inset-bottom)) max(12px,env(safe-area-inset-left))}
  /* Arriba: la fecha y los botones en un renglón, el buscador en el siguiente. */
  .tope{flex-direction:column-reverse;align-items:stretch;gap:10px;margin:12px 0 0}
  .tope-d{justify-content:space-between;flex-wrap:wrap;gap:8px}
  .card{padding:14px}
  .modal{--mp:20px}
  /* Los avisos van en un solo renglón que se desliza de costado: no le comen alto al tablero. */
  .avisos-g{margin:10px -12px 0;padding:0 12px;flex-wrap:nowrap;overflow-x:auto}
  .aviso-g{flex-shrink:0}
  .buscar input{max-width:none}
  .toast{bottom:calc(20px + env(safe-area-inset-bottom))}
  .col-tabs{display:flex;gap:4px;position:sticky;top:0;z-index:50;background:var(--bg);padding:10px 0;margin-bottom:2px}
  .col-tabs button{min-height:44px}
  .col-tabs button{flex:1 1 0;min-width:0;padding:7px 2px 6px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--card);font-size:10.5px;letter-spacing:-.01em;font-weight:700;color:var(--text2);cursor:pointer;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.25}
  .col-tabs button{border-bottom:3px solid var(--et)}
  .col-tabs button.on{background:var(--et);color:var(--et-on);border-color:var(--et)}
  .col-tabs button.con-aviso::after{content:'!';position:absolute;top:-5px;right:-3px;width:16px;height:16px;border-radius:50%;background:var(--red);color:var(--card);font-size:11px;font-weight:800;line-height:16px;text-align:center}
  .col-tabs button{position:relative;overflow:visible}
  .col-tabs button i{display:block;font-style:normal;font-size:13px;opacity:.75;font-variant-numeric:tabular-nums}
  .col-tabs button.puede{background:var(--et-soft);color:var(--et);border-color:var(--et);border-style:dashed dashed solid}
  .col-tabs button.puede.drop{background:var(--et);color:var(--et-on);border-style:solid;transform:scale(1.07)}
  .col-tabs button.bloq{opacity:.45}
  .col-tabs button.bloq.drop{opacity:1;background:var(--red-soft);color:var(--red);border-color:var(--red)}
  @keyframes tabllego{0%{transform:scale(1.14);box-shadow:0 0 0 0 var(--et)}100%{transform:none;box-shadow:0 0 0 9px transparent}}
  .col-tabs button.llego{animation:tabllego .9s ease-out}
  .col,.col.puede,.col.bloq,.col.origen{border-top:none;outline:none;opacity:1;background:none}
  #aparte{margin-top:0}
  #aparte .cards{grid-template-columns:1fr}
  .col-sub{display:none}
  .board{grid-template-columns:minmax(0,1fr);overflow-x:visible;padding-bottom:0}
  .col{display:none;background:none;border:none;padding:0;min-width:0}
  .col.on{display:block}
  .col-h{display:none}
  .col-avisos{padding:0 0 10px;margin-top:0}
}
/* Celular: los textos de la barra se acortan para que entren en un renglón. */
@media(max-width:699px){
  .sob{display:none}
  .hoy{font-size:13px}
  .vista-b{padding:8px 12px}
}
/* === TABLET (parada, o acostada chica): las fichas de la etapa van en 2 o 3 columnas ===
   Se leen de izquierda a derecha y de arriba abajo, en el orden de la cola. */
@media(min-width:700px) and (max-width:1099px){
  .wrap{padding:0 max(20px,env(safe-area-inset-right)) calc(60px + env(safe-area-inset-bottom)) max(20px,env(safe-area-inset-left))}
  .header{padding:14px 20px}
  .h-tabs{padding:0 12px}
  .h-tabs a{font-size:.68rem;padding:12px 14px}
  /* como en celular: botones y fecha arriba, el buscador en su propio renglón (con la semana ya no entran en uno) */
  .tope{margin:16px 0 0}
  .buscar input{max-width:420px}
  .hoy .sob{display:none}
  .avisos-g{margin:12px -20px 0;padding:0 20px}
  .col-tabs{gap:8px;padding:12px 0}
  .col-tabs button{padding:9px 4px 8px;font-size:12.5px;letter-spacing:0}
  .col-tabs button i{font-size:15px}
  .col.on{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;align-items:start}
  .col.on>*{grid-column:1/-1;margin-bottom:0}
  .col.on>.card{grid-column:auto}
  .col.on>.col-sep{margin:8px 2px 0}
  .col.on>.col-sep.arriba{margin:8px 0 0}
  .col-acc{max-width:420px}
}
@media(min-width:960px) and (max-width:1099px){
  .col.on{grid-template-columns:repeat(3,minmax(0,1fr))}
}
/* Rol que solo adjunta fotos: la ficha se ve entera pero no se cambia. */
.solo-fotos .c-hecho,.solo-fotos .c-fecha,.solo-fotos .chk{pointer-events:none}
.solo-fotos .c-avanzar,.solo-fotos input[data-nota],.solo-fotos .c-det .acciones,.solo-fotos #exportB,.solo-fotos .c-fecha.vacia{display:none}
.solo-fotos .c-hecho:not(.listo){display:none} /* el botón grande de la etapa no se le muestra a quien no puede usarlo */
.solo-fotos .chk{opacity:.8}
.solo-fotos .card{cursor:default}
.solo-fotos .card.plegada{cursor:pointer}
.adj.bacha-b{width:auto;max-width:100%;min-width:0;flex-shrink:1;padding:0 12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
</style>
</head>
<body<?= auth_puede('tablero_operar') ? '' : ' class="solo-fotos"' ?>>

<div class="header">
  <div style="display:flex;align-items:center;gap:12px">
    <img src="../logo/logo_isotipo.png" alt="BlackStones" style="height:36px;width:auto">
    <div><h1>BlackStones</h1><span>Tablero de obras</span></div>
  </div>
  <div class="h-right">
    <?= auth_quien_html() ?>
    <div class="toggle" onclick="toggleTheme()" title="Modo claro / oscuro"></div>
    <a href="/calculadora/logout.php" class="logout-link" title="Cerrar sesión" aria-label="Cerrar sesión">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    </a>
  </div>
</div>
<?= auth_nav_html('tablero') ?>
<div class="gold-line"></div>

<div class="wrap">
  <div class="tope">
    <div class="buscar">
      <input type="search" id="buscar" placeholder="Buscar cliente, N° o material" autocomplete="off" enterkeyhint="search" aria-label="Buscar obra">
      <button type="button" id="buscarX" aria-label="Borrar búsqueda" hidden>×</button>
      <span id="buscarN"></span>
    </div>
    <div class="tope-d">
      <div class="tope-b">
        <button type="button" class="vista-b" id="vistaB"></button>
        <button type="button" class="vista-b" id="exportB" title="Baja todas las obras del tablero en un archivo CSV (se abre con Excel)">Exportar<span class="sob"> tablero</span></button>
        <button type="button" class="vista-b" id="archB" title="Obras archivadas: se pueden devolver al tablero" hidden></button>
        <button type="button" class="vista-b" id="papB" title="Fichas borradas: se pueden recuperar durante 30 días" hidden></button>
      </div>
      <div class="hoy" id="hoyTxt"></div>
    </div>
  </div>
  <div class="avisos-g" id="avisosG"></div>
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
// Quién entró y qué puede (el servidor igual rechaza lo que el rol no puede).
const YO=<?= auth_permisos_js() ?>;
const OPERA=!!YO.puede.tablero_operar; // sin esto la ficha solo se mira y se le adjuntan fotos
const SOLO_BACHA=!OPERA&&!!YO.puede.tablero_bacha; // no opera, pero anota de quién es la bacha y su modelo
const API='/calculadora/api/tablero.php';
const API_ESTADO='/calculadora/api/state.php';
const API_ADJ='/calculadora/api/tablero-adjunto.php';
const API_CARGAR='/calculadora/api/tablero-cargar.php';
const API_EDITAR='/calculadora/api/tablero-editar.php';
const API_BORRAR='/calculadora/api/delete.php';
const API_PAPELERA='/calculadora/api/tablero-papelera.php';
const ADJ={bacha:'Bacha',plano:'Plano',entrega:'Entrega'}; // tipos de adjunto
const ADJ_L={bacha:'Foto de la bacha',plano:'Plano',entrega:'Foto de entrega'}; // cómo se nombran en ventanas y avisos
const ADJ_OK={bacha:'Foto de bacha guardada',plano:'Plano guardado',entrega:'Foto de entrega guardada'};
// Links que se aceptan en vez de subir el archivo (misma lista que el server).
const LINK_HOSTS=['drive.google.com','docs.google.com','photos.app.goo.gl','photos.google.com'];
function linkValido(t){
  t=String(t||'').trim();
  if(!t||t.length>600||/[\s"'<>\\]/.test(t))return '';
  let u=null;
  try{u=new URL(t);}catch(e){return '';}
  if(u.protocol!=='https:'||u.username||u.password||u.port||LINK_HOSTS.indexOf(u.hostname.toLowerCase())<0)return '';
  return t;
}

let ETAPAS=[];      // definición de etapas (viene del server)
let INSTALADORES=[];// quiénes instalan (viene del server)
let FABRICADORES=[];// quiénes fabrican (viene del server)
let CORTADORES=[];  // quiénes cortan (viene del server)
let MEDIDORES=[];   // quiénes miden (viene del server)
let APROB=[];       // tildes que son aprobaciones: muestran quién las dio (viene del server)
let PUEDE_APROBAR=false; // si el que mira puede dar aprobaciones
// Tildes que piden elegir personas: uno o varios nombres de una lista.
const PERSONAS={
  medidor:{campo:'medidor',lista:()=>MEDIDORES,titulo:'Medidor',pregunta:'¿Quién va a medir?',rotulo:'Mide: '},
  equipo:{campo:'equipo',lista:()=>INSTALADORES,titulo:'Instaladores',pregunta:'¿Quiénes instalan?',rotulo:'Instalan: '},
  cortador:{campo:'cortador',lista:()=>CORTADORES,titulo:'Cortador',pregunta:'¿Quién corta?',rotulo:'Corta: '},
  fabrica:{campo:'fabrica',lista:()=>FABRICADORES,titulo:'Fabricadores',pregunta:'¿Quiénes la fabrican?',rotulo:'Fabrican: '}
};
let CARDS=[];       // tarjetas
let HOY='';         // YYYY-MM-DD según el server
let abierta='';     // key de la tarjeta con el detalle abierto
// El N° que se muestra es el del presupuesto. Una ficha cargada a mano no tiene presupuesto:
// dice "Sin N°" (su número interno no se muestra) hasta que se le ponga uno desde "Editar obra".
// Si está enlazada con un contrato que tiene N°, muestra ese (lo escribe Relación-Contrato).
const nroB=c=>!c.manual?'<b>'+esc(c.cliente_nro)+'-'+c.sub+'</b>'
  :(c.nro_contrato?'<b title="N° de su contrato en Relación-Contrato. La ficha se cargó a mano.">'+esc(c.nro_contrato)+'</b>'
  :'<b class="sin-nro" title="Cargada a mano: todavía no tiene presupuesto ni contrato con N°.">Sin N°</b>');
// Lo mismo, en texto, para títulos de ventanas y avisos (barrido del 18/11/2026: ahí se veía el
// número interno de la ficha a mano, y alguien lo podía copiar como N° en el contrato).
const nroTxt=c=>!c.manual?c.cliente_nro+'-'+c.sub:(c.nro_contrato||'Sin N°');
// Qué fichas tienen contrato en Relación-Contrato (ficha → contrato). Lo dice Relación-Contrato;
// si no existe o no contesta, queda vacío y la ficha simplemente no muestra la marca.
let ENLACES={},ENLACES_OK=false,soloSinCto=false; // soloSinCto: filtro "Sin contrato" (dueño, 18/11/2026)
const ICO_ENL='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 007.07 0l3-3a5 5 0 00-7.07-7.07l-1.5 1.5"/><path d="M14 11a5 5 0 00-7.07 0l-3 3a5 5 0 007.07 7.07l1.5-1.5"/></svg>';
async function cargarEnlaces(){
  if(!YO.puede.contratos)return; // sin acceso a Relación-Contrato no hay marca que mostrar
  let n={},ok=false;
  try{
    const r=await fetch('/calculadora/api/contratos.php?enlaces=1',{credentials:'same-origin',cache:'no-store'});
    const j=r.ok?await r.json():null;
    if(j&&j.ok&&j.enlaces&&typeof j.enlaces==='object'){n=j.enlaces;ok=true;}
  }catch(e){}
  if(JSON.stringify(n)===JSON.stringify(ENLACES)&&ok===ENLACES_OK)return;
  ENLACES=n;ENLACES_OK=ok;
  if(!drag&&libre())dibujar();
}
let colMovil=localStorage.getItem('bs-tablero-col')||'medir';
let ocupado=false;  // hay un guardado en curso
let fechaKey='';    // tarjeta a la que se le está eligiendo fecha
let drag=null;      // arrastre en curso
let buscado=[];     // palabras del buscador, ya sin mayúsculas ni acentos
// FICHAS PLEGADAS: para que una columna no sea infinita hacia abajo, cada ficha se ve en dos o tres
// renglones (quién, qué, y en qué está) y se abre al tocarla. De entrada vienen todas plegadas: así
// se ve la cola entera de un vistazo. El botón de arriba ("Abrir todas") las muestra completas.
let vistaTodas=false;
try{vistaTodas=localStorage.getItem('bs-tablero-vista')==='completas';}catch(e){}
let VISTA={};        // lo que se abrió o plegó a mano: key → true / false
let UNICA='';        // key de la única ficha que deja el buscador, si es una sola
let ultimoArrastre=0;// para que soltar una ficha arrastrada no cuente como "tocarla"
// Modo "una sola ficha" (?solo=0042-1): lo usa el calendario para mostrar la ficha completa adentro
// de su ventana. Es esta misma página sin encabezado ni columnas: una única ficha, con todo lo suyo.
const SOLO=(()=>{const k=new URLSearchParams(location.search).get('solo')||'';return /^\d{4,}-\d+$/.test(k)?k:'';})();
if(SOLO)document.documentElement.classList.add('solo');
// Los filtros de cada columna vienen guardados detrás del botón "Filtrar", para que el encabezado no
// se coma la columna. Lo que se abre queda recordado; un filtro prendido siempre se ve.
// Etapas plegadas hacia arriba (solo en computadora). Se recuerda en este navegador.
let colCerr={};
try{(JSON.parse(localStorage.getItem('bs-tablero-cerradas')||'[]')||[]).forEach(k=>{colCerr[k]=true;});}catch(e){}
const ESCRITORIO=window.matchMedia('(min-width:1100px)');
const ICO_PLEGAR='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 15 12 9 18 15"/></svg>';
const ICO_DESPLEGAR='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>';
let filtrosAb={};
try{(JSON.parse(localStorage.getItem('bs-tablero-filtros')||'[]')||[]).forEach(k=>{filtrosAb[k]=true;});}catch(e){}
const falta={}; // filtros "le falta…" encendidos: plano, foto de bacha y fecha (Por medir), turno (Por instalar)
let recien=null;    // última tarjeta movida (o rebotada), para animarla: {key,tipo,t}
const slot=document.createElement('div'); // "Soltar acá", viaja de columna en columna

const $=id=>document.getElementById(id);
const esc=s=>String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const DIAS=['dom','lun','mar','mié','jue','vie','sáb'];

function partes(iso){const p=iso.split('-').map(Number);return new Date(p[0],p[1]-1,p[2]);}
const MESES=['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
const DIAS_L=['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
// La semana en curso, de lunes a viernes: "Semana 16-20 de noviembre" (o "Semana 30 nov - 4 dic" si cruza de mes).
function semanaTxt(iso,corto){
  const l=partes(iso);l.setDate(l.getDate()-((l.getDay()+6)%7));
  const v=new Date(l);v.setDate(l.getDate()+4);
  const m=d=>MESES[d.getMonth()],mismo=l.getMonth()===v.getMonth();
  if(corto)return 'Sem '+l.getDate()+(mismo?'':' '+m(l).slice(0,3))+'-'+v.getDate()+(mismo?'':' '+m(v).slice(0,3));
  return 'Semana '+(mismo?l.getDate()+'-'+v.getDate()+' de '+m(l):l.getDate()+' '+m(l).slice(0,3)+' - '+v.getDate()+' '+m(v).slice(0,3));
}
function fechaLarga(iso){const d=partes(iso);return DIAS_L[d.getDay()]+' '+d.getDate()+' de '+MESES[d.getMonth()];}
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
const BACHA={nuestra:'nuestra',cliente:'del cliente',no:'no lleva'};
function textoAcceso(a){
  if(a.modo==='pb')return 'planta baja';
  if(a.modo==='ascensor')return 'por ascensor';
  return 'por escalera, '+a.pisos+(a.pisos===1?' piso':' pisos');
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
function toast(msg,ok){ // ok=true: aviso bueno (verde); si no, algo falta o falló (rojo)
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

async function cargar(){
  try{
    const j=await pedir(API);
    if(drag)return; // no redibujar con una tarjeta en el aire
    ETAPAS=j.etapas;CARDS=j.cards;HOY=j.hoy;
    cargarEnlaces(); // aparte y sin esperar: el tablero no depende de esto
    $('papB').hidden=!j.papelera;$('papB').textContent='Papelera · '+(j.papelera||0);
    $('archB').hidden=!j.archivadas;$('archB').textContent='Archivadas · '+(j.archivadas||0);INSTALADORES=j.instaladores||[];FABRICADORES=j.fabricadores||[];CORTADORES=j.cortadores||[];APROB=j.aprobaciones||[];PUEDE_APROBAR=!!j.puede_aprobar;MEDIDORES=j.medidores||[];
    dibujar();
  }catch(e){
    if(!CARDS.length)$('board').innerHTML='<div class="aviso" style="grid-column:1/-1">'+esc(e.message)+'</div>';
    else toast(e.message);
  }
}

const etapaDe=id=>ETAPAS.find(e=>e.id===id);
const cardDe=key=>CARDS.find(c=>c.key===key);
const columnas=()=>ETAPAS.filter(e=>!e.aparte); // las columnas, en orden
// Qué le falta a la tarjeta para salir de su etapa: la fecha (si la etapa la exige) y los tildes.
function faltanDe(c,e){
  const f=e.checks.filter(k=>!c.checks[k.id]).map(k=>k.label);
  if(e.exige_fecha&&e.id===c.etapa){
    if(!c.fecha)f.unshift((e.pide_hora?'Fecha y hora de ':'Fecha de ')+e.fecha.toLowerCase());
    else if(e.pide_hora&&!c.hora)f.unshift('Hora de '+e.fecha.toLowerCase());
  }
  return f;
}
// A la obra le falta el turno que su etapa exige para salir (Por instalar: día y hora).
function sinTurno(c){
  const e=etapaDe(c.etapa);
  if(e.exige_fecha)return !c.fecha||!!(e.pide_hora&&!c.hora); // Por instalar: falta el día o la hora
  if(e.avisa_fecha)return !c.fecha&&!hecho(c);                // Por medir: sin fecha acordada (si ya se midió, no aplica)
  return false;
}
// La obra ya cumplió el paso principal de su etapa (en Por medir: ya se midió).
function hecho(c){
  const e=etapaDe(c.etapa);
  return !!(e.boton&&c.checks[e.boton]);
}
// "Por avanzar": la ficha necesita que alguien haga algo YA para no quedarse parada.
//   - ya está hecha (medida, cortada, fabricada) y sigue en la columna
//   - tenía fecha y se le pasó sin hacerse
//   - le falta un paso que la etapa marca como urgente (Por cortar: entregarla al cortador)
function urgente(c){
  const e=etapaDe(c.etapa);
  if(!e.boton)return false;
  return hecho(c)||atrasada(c)||(e.urge||[]).some(k=>!c.checks[k]);
}
// Detalle del aviso "N por avanzar": cuántas por cada motivo. Cada ficha cuenta una sola vez,
// por su motivo más avanzado (hecha > atrasada > le falta el tilde de "urge"), así la suma da N.
function motivosAvanzar(e,cards){
  let h=0,a=0,u=0;
  cards.filter(urgente).forEach(c=>{if(hecho(c))h++;else if(atrasada(c))a++;else u++;});
  const t=e.hecha_txt||[],p=[];
  if(u)p.push(u+' '+(e.urge_txt||'sin empezar'));
  if(a)p.push(a+(a===1?' atrasada':' atrasadas'));
  if(h)p.push(h+' '+(h===1?(t[0]||'hecha'):(t[1]||'hechas')));
  return p.join(' · ');
}
// Plazo prometido al cliente (semana estimada de entrega): '' | 'cerca' (vence hoy o en los próximos 6 días) | 'vencida'.
// Son días corridos desde la medición: vence el día exacto (c.limite), no al terminar la semana.
function plazo(c){
  if(!c.limite||c.etapa==='archivar')return '';
  return c.limite<HOY?'vencida':(c.limite<=masDias(HOY,6)?'cerca':'');
}
function masDias(iso,n){const d=partes(iso);d.setDate(d.getDate()+n);return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');}
let plazoF=''; // aviso general prendido ('atrasada' | 'vencida' | 'cerca'): muestra solo esas fichas
// Instalaciones que se pisan: mismo día, mismo instalador y menos de CHOQUE_MIN minutos entre una y otra.
const CHOQUE_MIN=120;
let CHOQUES={}; // key → ["David también instala 10:00 hs · 0041 Carolina P.", …]
function minutos(h){const m=/^(\d\d):(\d\d)$/.exec(h||'');return m?m[1]*60+(+m[2]):null;}
function calcularChoques(){
  CHOQUES={};
  const l=CARDS.filter(c=>etapaDe(c.etapa).exige_fecha&&c.fecha&&c.fecha>=HOY&&c.hora&&c.equipo.length);
  const anotar=(a,b,quien)=>{(CHOQUES[a.key]=CHOQUES[a.key]||[]).push(quien.join(', ')+(quien.length>1?' también instalan ':' también instala ')+b.hora+' hs · '+(b.manual?(b.nro_contrato?b.nro_contrato+' ':''):b.cliente_nro+' ')+(nombreCorto(b.nombre)||''));};
  for(let i=0;i<l.length;i++)for(let j=i+1;j<l.length;j++){
    const a=l[i],b=l[j];
    if(a.fecha!==b.fecha||Math.abs(minutos(a.hora)-minutos(b.hora))>=CHOQUE_MIN)continue;
    const quien=a.equipo.filter(x=>b.equipo.indexOf(x)>=0);
    if(quien.length){anotar(a,b,quien);anotar(b,a,quien);}
  }
}
const sePisa=c=>!!CHOQUES[c.key];
function atrasada(c){ // la fecha pasó y no se cumplió (o la tarjeta sigue en la columna)
  const e=etapaDe(c.etapa);
  return !!(e.fecha&&c.fecha&&c.fecha<HOY&&!(e.cierra&&c.checks[e.cierra]));
}
// Qué falta para llevar la tarjeta a esa etapa (misma regla que el server):
// hacia atrás o hacia Retiro, nada; hacia adelante, los tildes de todo lo de atrás.
function faltaPara(c,destino){
  const d=etapaDe(destino);
  if(d&&d.oculta&&c.etapa!==destino)return ['"'+d.titulo+'" está desactivado por ahora'];
  const cols=columnas();
  const i=cols.findIndex(e=>e.id===c.etapa),j=cols.findIndex(e=>e.id===destino);
  if(j<0||(i>=0&&j<i))return [];
  let f=[];
  // desde la etapa en la que está (o desde el principio, si viene de Retiro) hasta la anterior al destino
  for(let n=Math.max(i,0);n<j;n++)f=f.concat(faltanDe(c,cols[n]));
  return f;
}

const urlAdj=(c,a)=>API_ADJ+'?nro='+encodeURIComponent(c.cliente_nro)+'&sub='+c.sub+'&id='+a.id;
function htmlAdjuntos(c,desplegada){
  let h='';
  c.adjuntos.forEach(a=>{
    h+='<button type="button" class="adj'+(a.pdf?' pdf':'')+(a.link?' link':'')+'" data-act="ver" data-id="'+esc(a.id)+'" title="'+ADJ[a.tipo]+(a.link?' (link de Drive)':'')+'">'+
      (a.link?'Drive':(a.pdf?'PDF':'<img loading="lazy" alt="" src="'+esc(urlAdj(c,a))+'&t=1">'))+'<span>'+ADJ[a.tipo]+'</span></button>';
  });
  // Bacha y plano se suman en Por medir (o desde "Más"); la foto de entrega, cuando se instala.
  if(c.etapa==='medir'||desplegada||!OPERA){ // quien solo adjunta los tiene siempre a mano
    h+='<button type="button" class="adj mas" data-act="adjuntar" data-tipo="bacha">+ Foto bacha</button>';
    h+='<button type="button" class="adj mas" data-act="adjuntar" data-tipo="plano">+ Plano</button>';
  }
  if(c.etapa==='instalar'||c.etapa==='archivar'){
    h+='<button type="button" class="adj mas" data-act="adjuntar" data-tipo="entrega">+ Foto entrega</button>';
  }
  // Quien no opera el tablero carga o corrige desde acá de quién es la bacha y su modelo.
  if(SOLO_BACHA)h+='<button type="button" class="adj mas bacha-b" data-act="bacha">'+(c.bacha?'Bacha: '+esc(c.bacha==='no'?'no lleva':BACHA[c.bacha]+(c.bacha_modelo?' · '+c.bacha_modelo:' · sin modelo')):'+ Modelo de bacha')+'</button>';
  return h?'<div class="c-adj">'+h+'</div>':'';
}

// Buscador: sin mayúsculas ni acentos, y tienen que estar todas las palabras.
const plano=t=>String(t==null?'':t).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
function coincide(c){
  if(!buscado.length)return true;
  if(!c._txt)c._txt=plano([c.cliente_nro+'-'+c.sub,c.nro_contrato||'',c.nombre,c.material,c.concepto,c.zona,c.direccion,c.celular,String(c.celular||'').replace(/\D/g,''),c.nota,c.bacha_modelo,c.equipo.join(' '),c.fabrica.join(' '),c.medidor.join(' '),c.cortador.join(' ')].join(' '));
  return buscado.every(w=>c._txt.indexOf(w)>=0);
}

// ¿Le falta ese adjunto? La foto de bacha no se pide si la obra no lleva bacha.
function sinAdjunto(c,tipo){
  if(tipo==='bacha'&&c.bacha==='no')return false;
  return !c.adjuntos.some(a=>a.tipo===tipo);
}

// ============================================================
// Material y color: el catálogo de la calculadora
// ============================================================
// calc.html es la única fuente de los colores; /api/colors-db.php los sirve (lo mismo que usa
// Presupuestos). Acá sirve para dos cosas: sugerir el material al cargar o editar una obra, con el
// formato "Marca · Color", y reconocer el color de cada ficha aunque esté escrito a mano
// ("Negro Boreal Pulido", "gris mara granito"), para poder juntar en la cola las del mismo color.
let CATALOGO=[];
const suelto=t=>String(t==null?'':t).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,' ').trim();
async function cargarCatalogo(){
  try{
    const r=await fetch('/calculadora/api/colors-db.php',{credentials:'same-origin'});
    const d=await r.json();
    if(!d.ok)throw new Error(d.error||'colors-db');
    d.colors.forEach(c=>{
      c._h=suelto(c.full);
      c._n=suelto(String(c.n).replace(/\([^)]*\)/g,' ').replace(/\*/g,' ')); // "Blanco Cana 24 (N y J)" → "blanco cana 24"
      c._m=suelto(c.marca_label);
    });
    CATALOGO=d.colors.filter(c=>c._n);
    MATCACHE={};
    if(CARDS.length)dibujar(); // con el catálogo ya se puede agrupar por color
  }catch(e){CATALOGO=[];} // sin catálogo no hay sugerencias; el texto se guarda igual
}
// El color de catálogo que corresponde a un texto de material: {c: entrada del catálogo, marca: true
// si la marca es segura}, o null si no se reconoce. Es una lectura del texto, no cambia la ficha.
// La marca importa: "Taj Mahal" existe en cuarcita y en Suprastone, y no son la misma placa.
let MATCACHE={};
function colorDe(texto){
  const t=suelto(texto);
  if(!t)return null;
  if(t in MATCACHE)return MATCACHE[t];
  const p=' '+t+' ';
  const nombra=x=>p.indexOf(' '+x._m+' ')>=0||(x._m==='suprastone'&&p.indexOf(' supra ')>=0); // el texto dice la marca
  let l=CATALOGO.filter(c=>c._h===t),exacto=l.length>0;          // 1) elegido de la lista: "Marca · Color"
  if(!l.length){
    // 2) el texto nombra un color del catálogo ("negro boreal pulido" → Negro Boreal): gana el nombre más largo
    const c=CATALOGO.filter(x=>p.indexOf(' '+x._n+' ')>=0).sort((x,y)=>y._n.length-x._n.length);
    if(c.length)l=c.filter(x=>x._n===c[0]._n);
  }
  if(!l.length&&t.length>=4){
    // 3) el texto es parte de un solo color ("suggar" → Blanco Suggar)
    const c=CATALOGO.filter(x=>(' '+x._n+' ').indexOf(' '+t+' ')>=0);
    if(c.length&&c.every(x=>x._n===c[0]._n))l=c;
  }
  let r=null;
  if(l.length){
    const dicha=l.filter(nombra);
    // la marca es segura si se eligió de la lista, si el color existe en una sola marca, o si el texto la nombra
    r=exacto||l.length===1?{c:l[0],marca:true}:(dicha.length===1?{c:dicha[0],marca:true}:{c:l[0],marca:false});
    // si el texto nombra OTRA marca que la del catálogo, la marca no es segura
    if(!exacto&&r.marca&&!nombra(r.c)&&CATALOGO.some(nombra))r.marca=false;
  }
  return MATCACHE[t]=r;
}
// Clave para juntar fichas del mismo color. Con marca segura, color + marca; si no, solo el color
// (se junta con las otras del mismo nombre que tampoco dicen la marca). Sin reconocer, el texto tal cual.
const claveColor=c=>{const k=colorDe(c.material);return k?k.c._n+'|'+(k.marca?k.c._m:'?'):suelto(c.material);};

const MAT_POR_DEFINIR='Por definir';
// Sugerencias de material en un campo de texto (Cargar obra, Editar obra).
function conCatalogo(idInput,idCaja){
  const input=$(idInput),caja=$(idCaja);
  if(!input||!caja)return;
  const pintar=()=>{
    const q=suelto(input.value);
    // "Por definir" va siempre primero (dueño, 18/11/2026): la obra se puede cargar aunque el cliente todavía no haya elegido el color.
    const pd=!q||suelto(MAT_POR_DEFINIR).indexOf(q)>=0?[{full:MAT_POR_DEFINIR,n:MAT_POR_DEFINIR,marca_label:'el cliente todavía no eligió'}]:[];
    const l=pd.concat(q?CATALOGO.filter(c=>c._h.indexOf(q)>=0):CATALOGO).slice(0,12);
    caja.innerHTML=l.length?l.map((c,i)=>'<div class="mat-item'+(i===0?' active':'')+'" data-full="'+esc(c.full)+'"><span>'+esc(c.n)+'</span><i>'+esc(c.marca_label)+'</i></div>').join('')
      :(CATALOGO.length?'<div class="mat-nada">No está en el catálogo de la calculadora. Se guarda igual, como lo escribiste.</div>':'');
    if(!l.length&&!CATALOGO.length){caja.classList.remove('open');return;}
    caja.classList.add('open');
  };
  const elegir=el=>{input.value=el.dataset.full;caja.classList.remove('open');};
  input.addEventListener('focus',pintar);
  input.addEventListener('input',pintar);
  input.addEventListener('keydown',ev=>{
    if(!caja.classList.contains('open'))return;
    const items=[...caja.querySelectorAll('.mat-item')];
    if(!items.length)return;
    let i=items.findIndex(x=>x.classList.contains('active'));
    if(ev.key==='ArrowDown'||ev.key==='ArrowUp'){
      ev.preventDefault();
      if(i>=0)items[i].classList.remove('active');
      i=ev.key==='ArrowDown'?(i+1)%items.length:(i<=0?items.length-1:i-1);
      items[i].classList.add('active');items[i].scrollIntoView({block:'nearest'});
    }else if(ev.key==='Enter'&&i>=0&&suelto(input.value)){ev.preventDefault();ev.stopPropagation();elegir(items[i]);}
    else if(ev.key==='Escape'){ev.stopPropagation();caja.classList.remove('open');}
  });
  // pointerdown: sirve igual con mouse y con dedo, y llega antes de que el campo pierda el foco
  caja.addEventListener('pointerdown',ev=>{const it=ev.target.closest('.mat-item');if(it){ev.preventDefault();elegir(it);}});
  input.addEventListener('blur',()=>setTimeout(()=>caja.classList.remove('open'),180));
}
// Lo escrito, llevado al formato del catálogo cuando se reconoce sin dudas (mismo texto que una opción).
function materialNormal(texto){
  const t=suelto(texto);
  if(!t)return '';
  const c=CATALOGO.find(x=>x._h===t)||(()=>{const l=CATALOGO.filter(x=>x._n===t);return l.length===1?l[0]:null;})();
  return c?c.full:String(texto).trim().replace(/\s+/g,' ');
}

// Orden de la cola dentro de una etapa.
// En las etapas con turno (medir, instalar) manda la fecha. En las que no tienen turno (cortar,
// fabricar) manda la semana en que hay que entregar, y dentro de la semana van juntas las del mismo
// color: así dos Negro Boreal de la misma semana se cortan una atrás de la otra.
let GRUPO={}; // key de ficha → {lim, des}: el vencimiento y la llegada más tempranos de su grupo (semana + color)
function prepararColas(){
  GRUPO={};
  const g={};
  CARDS.forEach(c=>{
    const e=etapaDe(c.etapa);
    if(!e||e.fecha||hecho(c))return;
    const k=c.etapa+'|'+(c.semana||'')+'|'+claveColor(c);
    const x=g[k]=g[k]||{lim:'9999',des:'9999'};
    if(c.limite&&c.limite<x.lim)x.lim=c.limite;
    if(c.desde&&c.desde<x.des)x.des=c.desde;
    GRUPO[c.key]=x;
  });
}
function ordenCola(a,b){
  const ha=hecho(a),hb=hecho(b);
  // En las colas sin turno (cortar, fabricar) manda el calendario: primero la semana de entrega,
  // esté hecha o no. Una obra NO sube por estar cortada: queda en su semana.
  if(!etapaDe(a.etapa).fecha){
    const sa=a.semana||'9999',sb=b.semana||'9999';
    if(sa!==sb)return sa<sb?-1:1;
  }
  // Dentro de lo mismo, lo ya hecho (medida, cortada, fabricada) que espera el control del gerente
  // va primero: es lo que frena el paso a la etapa siguiente. Entre ellas, la que vence antes.
  if(ha!==hb)return ha?-1:1;
  if(ha){
    const la=a.limite||'9999',lb=b.limite||'9999';
    if(la!==lb)return la<lb?-1:1;
    if(a.boton_at!==b.boton_at)return a.boton_at<b.boton_at?-1:1;
  }
  if(!!a.fecha!==!!b.fecha)return a.fecha?-1:1;          // con turno primero
  if(a.fecha!==b.fecha)return a.fecha<b.fecha?-1:1;      // el turno más próximo arriba
  if(a.hora!==b.hora){                                   // mismo día: el más temprano; sin hora, al final
    if(!a.hora)return 1;
    if(!b.hora)return -1;
    return a.hora<b.hora?-1:1;
  }
  if(!ha&&!etapaDe(a.etapa).fecha){                      // sin turno: semana de entrega → color → vencimiento
    const sa=a.semana||'9999',sb=b.semana||'9999';
    if(sa!==sb)return sa<sb?-1:1;                        // la semana que vence antes, arriba
    const ga=GRUPO[a.key],gb=GRUPO[b.key];
    if(ga&&gb&&ga!==gb){                                 // otro color: primero el grupo que vence antes
      if(ga.lim!==gb.lim)return ga.lim<gb.lim?-1:1;
      if(ga.des!==gb.des)return ga.des<gb.des?-1:1;
      const ka=claveColor(a),kb=claveColor(b);
      if(ka!==kb)return ka<kb?-1:1;
    }
    const la=a.limite||'9999',lb=b.limite||'9999';
    if(la!==lb)return la<lb?-1:1;                        // mismo color: la que vence antes
  }
  if(a.desde!==b.desde)return a.desde<b.desde?-1:1;      // después, la cola: el que entró antes
  return a.key<b.key?-1:1;
}
// Lugar de cada ficha en la cola de su etapa (1 = la próxima a atender). Se cuenta
// sobre TODAS las fichas de la etapa, así el número no cambia al buscar o filtrar.
// Las ya hechas (ya medidas, ya cortadas) no llevan número: esperan el control.
let POS={};
function numerarColas(){
  prepararColas();
  POS={};
  ETAPAS.forEach(e=>{
    CARDS.filter(c=>c.etapa===e.id&&!hecho(c)).sort(ordenCola).forEach((c,i)=>{POS[c.key]=i+1;});
  });
}

function desplegada(c){
  if(SOLO)return true;
  if(c.key in VISTA)return VISTA[c.key];
  return vistaTodas||abierta===c.key||UNICA===c.key; // buscar y que quede una sola: se muestra abierta
}
// La ficha plegada: N°, cliente, material y una fila de etiquetas con lo que hay que saber de un vistazo.
function htmlPlegada(c,e,cls,estilo,pos){
  let h='<article class="'+cls+' plegada"'+estilo+' data-key="'+esc(c.key)+'" data-act="abrir" title="Tocar para abrir la ficha">';
  if(pos===1)h+='<div class="c-prox">Próxima</div>';
  h+='<div class="c-id">'+(pos?'<span class="c-pos" title="Lugar en la cola">'+pos+'</span>':'')+nroB(c)+' · '+esc(nombreCorto(c.nombre)||'Sin nombre');
  if(c.zona)h+=' <span class="zona">· '+esc(c.zona)+'</span>';
  h+='</div>';
  const mat=[c.material,c.concepto].filter(Boolean).join(' · ');
  if(mat)h+='<div class="c-mat">'+esc(mat)+'</div>';
  const r=[],et=(txt,tipo)=>r.push('<span class="r'+(tipo?' '+tipo:'')+'">'+esc(txt)+'</span>');
  if(hecho(c)){
    et(e.boton_listo+(c.boton_at?' · '+fechaCorta(c.boton_at):''),'ok');
    if(e.exige_fecha&&sinTurno(c))et(!c.fecha?'Sin turno':'Falta la hora','rojo'); // instalada sin el turno completo: se ve sin abrirla
  }else if(e.fecha){
    if(!c.fecha){if(e.exige_fecha||e.avisa_fecha)et(e.exige_fecha?'Sin turno':'Sin fecha','rojo');}
    else{
      et((c.fecha===HOY?'Hoy':fechaCorta(c.fecha))+(c.hora?' · '+c.hora:'')+(atrasada(c)?' · atrasada':''),atrasada(c)?'rojo':(c.fecha===HOY?'hoy':''));
      if(e.exige_fecha&&e.pide_hora&&!c.hora&&!atrasada(c))et('Falta la hora','rojo');
    }
  }
  // El paso urgente de la etapa (Por cortar / Por fabricar: entregarle la obra a quien la hace) se ve
  // sin abrir la ficha, esté o no: "Sin entregar" en rojo o "Entregado" en verde.
  if(!hecho(c)&&(e.urge||[]).some(k=>!c.checks[k]))et((e.urge_txt||'sin empezar').replace(/^./,x=>x.toUpperCase()),'rojo');
  else if(!hecho(c)&&(e.urge||[]).length&&e.urge_ok_txt)et(e.urge_ok_txt,'ok');
  if(sePisa(c))et('Se pisa','rojo');
  const pl=plazo(c);
  if(pl==='vencida')et('Plazo vencido','rojo');
  else if(pl==='cerca')et('Vence '+(c.limite===HOY?'hoy':fechaCorta(c.limite)),'amb');
  // cuánto le falta para poder pasar de etapa
  if(faltanDe(c,e).length)et(e.checks.filter(k=>c.checks[k.id]).length+' de '+e.checks.length,'tildes');
  else et('Lista para pasar','ok');
  h+='<div class="c-res">'+r.join('')+'</div>';
  if(c.nota)h+='<div class="c-nota">'+esc(c.nota)+'</div>';
  return h+'</article>';
}
function htmlCard(c){
  const e=etapaDe(c.etapa);
  const cols=columnas();
  const idx=cols.indexOf(e);
  const sig=idx>=0?cols[idx+1]:null;
  const pos=POS[c.key]||0;
  let cls='card'+(atrasada(c)?' alerta':(sinTurno(c)?' sin-turno':(sePisa(c)?' choque':'')))+(pos===1?' primera':''),estilo='';
  if(recien&&recien.key===c.key){
    const dt=Math.max(0,Date.now()-recien.t); // si se redibuja en el medio, la animación sigue donde iba
    if(dt<1000){cls+=' '+recien.tipo;estilo=' style="animation-delay:-'+dt+'ms"';}
  }
  if(!desplegada(c))return htmlPlegada(c,e,cls,estilo,pos);
  let h='<article class="'+cls+'"'+estilo+' data-key="'+esc(c.key)+'">';
  if(!SOLO)h+='<button type="button" class="c-plegar" data-act="plegar" aria-label="Plegar la ficha" title="Plegar"></button>';

  if(pos===1)h+='<div class="c-prox">Próxima</div>';
  h+='<div class="c-id">'+(pos?'<span class="c-pos" title="Lugar en la cola">'+pos+'</span>':'')+nroB(c)+' · '+esc(nombreCorto(c.nombre)||'Sin nombre');
  if(c.zona)h+=' <span class="zona">· '+esc(c.zona)+'</span>';
  h+='</div>';
  const mat=[c.material,c.concepto].filter(Boolean).join(' · ');
  if(mat)h+='<div class="c-mat">'+esc(mat)+'</div>';
  if(ENLACES[c.key])h+='<a class="c-contrato" href="/presupuestos/relacion-contrato.php?contrato='+encodeURIComponent(ENLACES[c.key])+'" target="_top" title="Tiene contrato en Relación-Contrato: tocá para abrirlo">'+ICO_ENL+'Con contrato</a>';

  // El botón de la etapa (Medido / Cortado) es lo que más se ve en la tarjeta:
  // dice de un vistazo si ese trabajo ya se hizo o no. Tocarlo otra vez lo deshace.
  if(e.boton){
    h+=hecho(c)
      ?'<button type="button" class="c-hecho listo" data-act="check" data-clave="'+esc(e.boton)+'" title="Tocar para desmarcar">'+esc(e.boton_listo)+(c.boton_at?' · '+esc(fechaCorta(c.boton_at)):'')+'</button>'
      :'<button type="button" class="c-hecho" data-act="check" data-clave="'+esc(e.boton)+'">'+esc(e.boton_txt)+'</button>';
  }
  // Ya hecha, la fecha no se muestra; salvo que la etapa exija el turno y esté incompleto (Por instalar:
  // instalada pero sin día u hora cargados): sin eso no puede pasar de etapa, así que tiene que poder cargarse.
  if(e.fecha&&(!hecho(c)||(e.exige_fecha&&sinTurno(c)))){
    let cls='',txt;
    const hora=c.hora?' · '+c.hora+' hs':(e.pide_hora&&e.exige_fecha?' · falta la hora':'');
    if(!c.fecha&&(e.exige_fecha||e.avisa_fecha)){cls=' aviso';txt='Falta fecha '+(e.exige_fecha&&e.pide_hora?'y hora ':'')+'de '+e.fecha.toLowerCase();}
    else if(!c.fecha){cls=' vacia';txt='+ Fecha de '+e.fecha.toLowerCase();}
    else if(atrasada(c)){cls=' atrasada';txt=e.fecha+' '+fechaCorta(c.fecha)+(c.hora?' · '+c.hora+' hs':'')+' · atrasada';}
    else if(c.fecha===HOY){cls=' hoy';txt=e.fecha+' · hoy'+hora;}
    else txt=e.fecha+' · '+fechaCorta(c.fecha)+hora;
    if(c.fecha&&e.pide_hora&&e.exige_fecha&&!c.hora&&!atrasada(c))cls=' aviso';
    h+='<div class="c-turno"><button type="button" class="c-fecha'+cls+'" data-act="fecha">'+esc(txt)+'</button></div>';
  }
  if(sePisa(c))h+=CHOQUES[c.key].map(t=>'<div class="c-choque">'+esc(t)+'</div>').join('');
  // Semana estimada de entrega. Las vencidas y las que vencen en 7 días se cuentan además arriba del tablero.
  if(c.semana&&c.etapa!=='archivar'){
    // Lejos del plazo alcanza con la semana; cerca o vencido, se dice el día.
    const pl=plazo(c);
    const txt=pl==='vencida'?'Plazo vencido el '+fechaCorta(c.limite):(pl==='cerca'?'Vence '+(c.limite===HOY?'hoy':'el '+fechaCorta(c.limite)):'Entrega estimada: semana del '+ddmm(c.semana))+(c.plazo_manual?' · plazo modificado':'');
    h+='<div class="c-promesa'+(pl?' '+pl:'')+'">'+esc(txt)+'</div>';
  }
  // Datos que se cargan al medir y quedan a la vista para el taller y el que instala.
  if(c.etapa!=='medir'&&c.etapa!=='archivar'){
    const datos=[];
    if(c.bacha)datos.push(c.bacha==='no'?'Sin bacha':'Bacha '+BACHA[c.bacha]+(c.bacha_modelo?' ('+c.bacha_modelo+')':''));
    if(c.acceso)datos.push('Sube '+textoAcceso(c.acceso));
    if(c.medidor.length)datos.push('Midió: '+c.medidor.join(', '));
    if(c.cortador.length&&!e.checks.some(k=>k.id==='cortador'))datos.push('Cortó: '+c.cortador.join(', '));
    if(c.fabrica.length&&!e.checks.some(k=>k.id==='fabrica'))datos.push('Fabricó: '+c.fabrica.join(', '));
    if(c.equipo.length&&!e.checks.some(k=>k.id==='equipo'))datos.push('Instalan: '+c.equipo.join(', '));
    if(datos.length)h+='<div class="c-promesa">'+esc(datos.join(' · '))+'</div>';
    // Lo que ya pasó, con su fecha: se ve acá sin tener que volver la ficha atrás.
    if(c.hitos.length)h+='<div class="c-hitos">'+c.hitos.map(x=>'<span'+(x.por?' title="Aprobó: '+esc(x.por)+'"':'')+'>'+esc(x.txt)+(x.fecha?' <b>'+esc(ddmm(x.fecha))+'</b>':'')+'</span>').join('')+'</div>';
  }
  if(c.nota)h+='<div class="c-nota">'+esc(c.nota)+'</div>';
  // Foto de bacha y plano: se suman en Por medir (o desde "Más") y quedan a la vista después.
  h+=htmlAdjuntos(c,abierta===c.key);

  h+='<div class="c-checks">';
  e.checks.forEach(k=>{
    if(k.id===e.boton)return; // ese tilde es el botón de arriba
    const on=!!c.checks[k.id];
    let label=k.label;
    if(k.id==='sena'&&on&&c.sena)label+=' · '+plataSena(c.sena);
    if(k.id==='acceso'&&on&&c.acceso)label+=' · '+textoAcceso(c.acceso);
    // de quién es se ve aunque falte el tilde (se anota al cargar la obra)
    // sin tilde dice solo de quién es ("Bacha del cliente"); con tilde, "Tenemos la bacha · …"
    if(k.id==='bacha'&&c.bacha&&(on||c.bacha!=='no'))label=c.bacha==='no'?'No lleva bacha':(on?'Tenemos la bacha · ':'Bacha ')+BACHA[c.bacha]+(c.bacha_modelo?' · '+c.bacha_modelo:(c.bacha==='nuestra'?' · falta el modelo':''));
    if(PERSONAS[k.id]&&on&&c[PERSONAS[k.id].campo].length)label=PERSONAS[k.id].rotulo+c[PERSONAS[k.id].campo].join(', ');
    if(on&&c.por&&c.por[k.id])label+=' · '+c.por[k.id]; // las aprobaciones dicen quién las dio
    h+='<button type="button" class="chk'+(on?' on':'')+'" data-act="check" data-clave="'+esc(k.id)+'" aria-pressed="'+on+'">'+esc(label)+'</button>';
  });
  h+='</div>';

  const falta=faltanDe(c,e).length>0;
  h+='<button type="button" class="c-avanzar et-'+(sig?esc(sig.id):'archivar')+(falta?' falta':'')+'" data-act="'+(sig?'avanzar':'archivar')+'">'+(sig?'Siguiente: '+esc(sig.titulo)+' →':'Archivar obra')+'</button>';

  const abre=abierta===c.key;
  h+='<button type="button" class="c-mas" data-act="mas" aria-expanded="'+abre+'">'+(abre?'Menos':'Más')+'</button>';
  if(abre){
    const wa=waLink(c.celular);
    h+='<div class="c-det">';
    if(c.celular)h+='<div class="fila"><span>Teléfono</span><div>'+(wa?'<a href="'+esc(wa)+'" target="_blank" rel="noopener">'+esc(c.celular)+'</a>':esc(c.celular))+'</div></div>';
    if(c.direccion)h+='<div class="fila"><span>Dirección</span><div><a href="https://www.google.com/maps/search/?api=1&query='+encodeURIComponent(c.direccion)+'" target="_blank" rel="noopener">'+esc(c.direccion)+'</a></div></div>';
    if(c.historia.length){
      h+='<div class="c-hist"><div class="t">Historial</div>';
      c.historia.forEach(et=>{
        h+='<div class="e"><b>'+esc(et.etapa)+'</b>'+(et.salio?' <span>· salió el '+esc(fechaCorta(et.salio))+'</span>':'')+'</div>';
        h+=et.items.length?et.items.map(x=>'<div class="i"><span>'+esc(x.txt)+'</span><span>'+(x.fecha?esc(fechaCorta(x.fecha)):'—')+'</span></div>').join(''):'<div class="i"><span>Sin tildes cargados</span><span></span></div>';
      });
      h+='</div>';
    }
    h+='<input type="text" maxlength="300" data-nota placeholder="Nota (ej: falta la bacha)" value="'+esc(c.nota)+'">';
    h+='<div class="acciones">';
    if(c.tiene_detalle)h+='<a href="/calculadora/api/render-pdf.php?nro='+encodeURIComponent(c.cliente_nro)+'&sub='+c.sub+'" target="_blank" rel="noopener">Ver presupuesto</a>';
    if(e.aparte)h+='<button type="button" class="lnk" data-act="atras">Volver a las columnas</button>';
    else{
      if(idx>0)h+='<button type="button" class="lnk" data-act="atras">Volver a '+esc(cols[idx-1].titulo)+'</button>';
      // "Retiro en local" puede estar desactivado: entonces no se ofrece mandar la obra ahí.
      if(ETAPAS.some(x=>x.aparte&&!x.oculta))h+='<button type="button" class="lnk" data-act="retiro">Retira en el local</button>';
    }
    h+='<button type="button" class="lnk" data-act="editar">Editar obra</button>';
    h+='<button type="button" class="lnk rojo" data-act="borrar">Borrar ficha</button>';
    h+='</div></div>';
  }
  return h+'</article>';
}

function dibujar(){
  if(!ETAPAS.length)return;
  if(!etapaDe(colMovil))colMovil=ETAPAS[0].id;

  numerarColas();
  calcularChoques();
  // Hoy y la semana en curso (lunes a viernes). En celular, la versión corta: "Lun 16 nov · Sem 16-20".
  $('hoyTxt').innerHTML=HOY?'<span class="sob">Hoy · </span><b class="f-l">'+esc(fechaLarga(HOY))+'</b><b class="f-c">'+esc(DIAS_L[partes(HOY).getDay()].slice(0,3)+' '+partes(HOY).getDate()+' '+MESES[partes(HOY).getMonth()].slice(0,3))+'</b>'
    +'<span class="sem s-l"> · '+esc(semanaTxt(HOY,false))+'</span><span class="sem s-c"> · '+esc(semanaTxt(HOY,true))+'</span>':'';
  // Avisos de todo el tablero: el plazo prometido. Se cuentan sobre todas las fichas, en cualquier columna.
  const nVen=CARDS.filter(c=>plazo(c)==='vencida').length,nCer=CARDS.filter(c=>plazo(c)==='cerca').length;
  // Atrasadas: tenían fecha (medición, instalación o retiro), la fecha pasó y siguen sin hacerse.
  // Es lo más urgente del tablero: va primero, y cuenta todas las columnas.
  const nAtr=CARDS.filter(atrasada).length;
  if((plazoF==='vencida'&&!nVen)||(plazoF==='cerca'&&!nCer)||(plazoF==='atrasada'&&!nAtr))plazoF='';
  // "Limpiar filtros": aparece solo cuando algo está filtrando (buscador, un aviso de arriba o un
  // filtro de columna) y va primero en la fila, para que siempre quede a la vista.
  // PROTECCIÓN (dueño, 18/11/2026): toda obra nace de su contrato, así que una ficha sin contrato es
  // rara y se avisa arriba. Tocar el aviso las deja solas a la vista. Solo si Relación-Contrato contestó.
  const nSinCto=ENLACES_OK?CARDS.filter(c=>!ENLACES[c.key]).length:0;
  if(!nSinCto)soloSinCto=false;
  const nFil=(buscado.length?1:0)+(plazoF?1:0)+(soloSinCto?1:0)+Object.keys(falta).filter(k=>falta[k]).length;
  $('avisosG').innerHTML=(nFil&&!SOLO?'<button type="button" class="limpiar-f" data-limpiar title="Saca el buscador y todos los filtros">Limpiar filtros'+(nFil>1?' · '+nFil:'')+'</button>':'')+(nSinCto?'<button type="button" class="aviso-g'+(soloSinCto?' on':'')+'" data-sincto aria-pressed="'+soloSinCto+'" title="Fichas que no tienen contrato en Relación-Contrato. No debería haber: toda obra nace de su contrato.">'+nSinCto+' sin contrato</button>':'')+(nAtr?'<button type="button" class="aviso-g'+(plazoF==='atrasada'?' on':'')+'" data-plazo="atrasada" aria-pressed="'+(plazoF==='atrasada')+'">'+nAtr+(nAtr===1?' atrasada':' atrasadas')+'</button>':'')
    +(nVen?'<button type="button" class="aviso-g'+(plazoF==='vencida'?' on':'')+'" data-plazo="vencida" aria-pressed="'+(plazoF==='vencida')+'">'+nVen+' con plazo vencido</button>':'')
    +(nCer?'<button type="button" class="aviso-g cerca'+(plazoF==='cerca'?' on':'')+'" data-plazo="cerca" aria-pressed="'+(plazoF==='cerca')+'">'+nCer+(nCer===1?' vence en 7 días':' vencen en 7 días')+'</button>':'');
  const filtrando=buscado.length>0||!!plazoF||!!SOLO;
  const visibles=SOLO?CARDS.filter(c=>c.key===SOLO):CARDS.filter(coincide).filter(c=>!soloSinCto||!ENLACES[c.key]).filter(c=>!plazoF||(plazoF==='atrasada'?atrasada(c):plazo(c)===plazoF));
  UNICA=buscado.length&&visibles.length===1?visibles[0].key:'';
  // Buscando en celular: si la pestaña abierta no tiene resultados, salta a la primera que sí.
  if(filtrando&&visibles.length&&!visibles.some(c=>c.etapa===colMovil))colMovil=ETAPAS.find(e=>visibles.some(c=>c.etapa===e.id)).id;
  $('buscarN').textContent=buscado.length?(visibles.length?visibles.length+(visibles.length===1?' obra':' obras'):'Sin resultados'):'';

  let tabs='',cols='',aparte='';
  const ultima=columnas().length-1;
  ETAPAS.forEach(e=>{
    const esPrimera=columnas().indexOf(e)===0;
    const deEtapa=visibles.filter(c=>c.etapa===e.id);
    // Filtros de la columna: [clave, texto, a quién le falta]
    const filtros=[];
    // Las dos preguntas de todos los días en las etapas con botón: ¿qué falta ejecutar? ¿qué está
    // hecho y sigue acá esperando que la pasen? Son excluyentes: prender una apaga la otra.
    // "Necesitan continuar" es el pendiente urgente: lo que ya está hecho y sigue acá, y también
    // lo que tenía fecha y se le pasó sin hacerse (había que medir el 14, es 15 y sigue sin medir).
    if(e.boton)filtros.push(['pend_'+e.id,e.pendientes,c=>!hecho(c)],['cont_'+e.id,'Necesitan continuar',urgente]);
    // "Con el cortador": ya se le entregó y todavía no lo cortó. Es su lista de trabajo de hoy.
    if(e.en_manos&&e.en_manos.length===2)filtros.push(['mano_'+e.id,e.en_manos[1],c=>!!c.checks[e.en_manos[0]]&&!hecho(c)]);
    if(esPrimera)filtros.push(['plano','Sin plano',c=>sinAdjunto(c,'plano')],['bacha','Sin foto de bacha',c=>sinAdjunto(c,'bacha')]);
    // cada columna tiene su propio filtro de fecha: Por medir "Sin fecha", Por instalar "Sin turno"
    if(e.exige_fecha||e.avisa_fecha)filtros.push(['turno_'+e.id,e.exige_fecha?'Sin turno':'Sin fecha',sinTurno]);
    // "Se pisan": solo aparece cuando hay alguna (o si quedó prendido).
    const nPisan=e.exige_fecha?deEtapa.filter(sePisa).length:0;
    if(nPisan||falta['pisa_'+e.id])filtros.push(['pisa_'+e.id,'Se pisan',sePisa]);
    const lista=deEtapa.filter(c=>filtros.every(f=>!falta[f[0]]||f[2](c))).sort(ordenCola);
    if(SOLO&&!lista.length)return; // en modo una ficha solo se dibuja la columna donde está
    const on=e.id===colMovil;
    const corto=e.aparte?'Retiro':e.titulo.replace('Por ','').replace(/^./,x=>x.toUpperCase());
    const llega=recien&&recien.tipo==='llego'&&Date.now()-recien.t<300&&lista.some(c=>c.key===recien.key);
    const sinT=lista.filter(sinTurno).length+lista.filter(sePisa).length;
    tabs+='<button type="button" class="et-'+esc(e.id)+(on?' on':'')+(llega?' llego':'')+(sinT?' con-aviso':'')+'" data-col="'+esc(e.id)+'">'+esc(corto)+'<i>'+lista.length+'</i></button>';
    // Avisos de la columna, siempre sobre TODAS sus fichas (no cambian al filtrar). Tocarlos prende su filtro.
    const nSinF=deEtapa.filter(sinTurno).length,nUrg=e.boton?deEtapa.filter(urgente).length:0;
    const cerr=!SOLO&&!e.aparte&&!!colCerr[e.id];
    let h='<section class="col et-'+esc(e.id)+(on?' on':'')+(cerr?' cerrada':'')+'" data-col="'+esc(e.id)+'">';
    h+='<div class="col-h"><h2>'+esc(e.titulo)+'</h2><span class="col-n">'+lista.length+'</span>'
      +(nSinF||nUrg||nPisan?'<i class="col-al" title="Tiene obras por avanzar o sin fecha">!</i>':'')
      +(e.aparte||SOLO?'':'<button type="button" class="col-pl" data-plegar="'+esc(e.id)+'" aria-expanded="'+!cerr+'" title="'+(cerr?'Desplegar ':'Plegar ')+esc(e.titulo)+'" aria-label="'+(cerr?'Desplegar ':'Plegar ')+esc(e.titulo)+'">'+(cerr?ICO_DESPLEGAR:ICO_PLEGAR)+'</button>')+'</div>';
    if(nSinF||nUrg||nPisan){
      h+='<div class="col-avisos">';
      if(nUrg)h+='<button type="button" class="col-aviso" data-falta="cont_'+esc(e.id)+'">'+nUrg+' por avanzar</button>';
      // El porqué, al lado: sin esto el número puede quedar igual aunque la ficha haya cambiado de caso
      // (dejó de estar "ya cortada" pero ahora está "sin entregar") y parece que no se actualizó.
      if(nUrg)h+='<span class="col-aviso-det">'+esc(motivosAvanzar(e,deEtapa))+'</span>';
      if(nSinF)h+='<button type="button" class="col-aviso" data-falta="turno_'+esc(e.id)+'">'+nSinF+(e.exige_fecha?' sin turno':' sin fecha')+'</button>';
      if(nPisan)h+='<button type="button" class="col-aviso" data-falta="pisa_'+esc(e.id)+'">'+nPisan+' se pisan</button>';
      h+='</div>';
    }
    if(!e.aparte&&!e.fecha&&columnas().indexOf(e)<ultima)h+='<div class="col-sub">Por semana de entrega · mismo color junto</div>';
    // Fila de acciones: el botón que muestra u oculta los filtros. (Acá estaba "+ Cargar obra": las obras
    // se cargan desde su contrato, en Relación-Contrato, y el dueño pidió que el tablero no tenga ni el link.)
    const nAct=filtros.filter(f=>falta[f[0]]).length,verF=filtros.length>0&&(!!filtrosAb[e.id]||nAct>0);
    if(filtros.length){
      h+='<div class="col-acc">';
      h+='<button type="button" class="col-fb'+(verF?' on':'')+'" data-filtros="'+esc(e.id)+'" aria-expanded="'+verF+'">Filtrar'+(nAct?' · '+nAct:'')+'</button>';
      h+='</div>';
    }
    if(verF)h+='<div class="col-filtros">'+filtros.map(f=>'<button type="button" class="'+(falta[f[0]]?'on':'')+(f[0].indexOf('cont_')===0&&deEtapa.some(f[2])?' urg':'')+((f[0].indexOf('turno_')===0||f[0].indexOf('pisa_')===0)&&deEtapa.some(f[2])?' avi':'')+'" data-falta="'+f[0]+'" aria-pressed="'+!!falta[f[0]]+'">'+f[1]+' <i>'+deEtapa.filter(f[2]).length+'</i></button>').join('')+'</div>';
    // Separador entre lo que falta hacer y lo ya hecho (Por medir: "Ya medidas").
    // Las ya hechas van primero, con su título; después la cola de lo que falta hacer.
    let nHechas=0;
    while(e.boton&&nHechas<lista.length&&hecho(lista[nHechas]))nHechas++;
    // En las etapas sin turno, un renglón marca dónde empieza cada semana de entrega: se entiende el orden.
    const conSemanas=!e.fecha&&!e.aparte&&columnas().indexOf(e)<ultima;
    const sem=c=>{
      if(!c.semana)return 'Sin plazo todavía';
      const fin=masDias(c.semana,6);
      return 'Entrega: semana del '+ddmm(c.semana)+(fin<HOY?' · vencida':(c.semana<=HOY?' · esta semana':''));
    };
    // título de lo ya hecho: "Ya cortadas" + lo que esperan, y el contador a la derecha
    const seccion=(txt,n,cls)=>{const p=String(txt).split(' · ');return '<div class="col-sep'+cls+'"><span class="t">'+esc(p[0])+(p.length>1?'<small>'+esc(p.slice(1).join(' · ').replace(/^./,x=>x.toUpperCase()))+'</small>':'')+'</span><b>'+n+'</b></div>';};
    const cards=lista.map((c,i)=>(nHechas&&i===0?seccion(e.hecho,nHechas,' arriba'):'')
      +(nHechas&&i===nHechas?'<div class="col-sep">'+esc(e.pendientes||'En cola')+' ('+lista.filter(x=>!hecho(x)).length+')</div>':'')
      +(conSemanas&&i>=nHechas&&(i===nHechas||(lista[i-1].semana||'')!==(c.semana||''))?'<div class="col-sem'+(c.semana&&masDias(c.semana,6)<HOY?' rojo':'')+'">'+esc(sem(c))+'</div>':'')+htmlCard(c)).join('');
    if(e.aparte)h+=lista.length?'<div class="cards">'+cards+'</div>':'<div class="col-vacia">'+(filtrando?'Sin resultados':'Nadie retira por el local')+'</div>';
    else h+=lista.length?cards:'<div class="col-vacia">'+(filtrando?'Sin resultados':CARDS.length?'Nada acá':(columnas().indexOf(e)===0?'Las obras aparecen solas acá cuando marcás un presupuesto como Aprobado.':''))+'</div>';
    h+='</section>';
    if(e.aparte)aparte+=h;else cols+=h;
  });
  $('board').style.setProperty('--ncols',columnas().length);
  $('colTabs').innerHTML=tabs;
  // cada columna se desplaza por dentro: al redibujar, queda donde estaba
  const sc={};
  document.querySelectorAll('#board .col[data-col]').forEach(x=>{sc[x.dataset.col]=x.scrollTop;});
  $('board').innerHTML=cols;
  document.querySelectorAll('#board .col[data-col]').forEach(x=>{if(sc[x.dataset.col])x.scrollTop=sc[x.dataset.col];});
  $('aparte').innerHTML=aparte;
  $('vistaB').innerHTML=(vistaTodas?'Plegar':'Abrir todas')+'<span class="sob"> las fichas</span>';
  ajustarAlto();
  if(SOLO&&!visibles.length)$('board').innerHTML='<div class="aviso">Esta obra ya no está en el tablero.</div>';
}

// Alto de las columnas (computadora): lo que queda de pantalla debajo del encabezado. Así el tablero
// entra en una pantalla y cada columna se recorre por dentro, en vez de crecer sin fin hacia abajo.
function ajustarAlto(){
  const t=$('board').getBoundingClientRect().top+window.scrollY;
  document.documentElement.style.setProperty('--col-max',Math.max(380,window.innerHeight-t-16)+'px');
}
window.addEventListener('resize',ajustarAlto);

// Guarda un cambio: primero lo aplica en pantalla, después lo confirma el server.
async function guardar(c,body,aplicar){
  if(!OPERA&&!(SOLO_BACHA&&body.accion==='bacha')){toast('Tu usuario no puede cambiar las fichas.');return;}
  if(ocupado)return;
  ocupado=true;
  // Si el cambio le mueve el lugar en la cola (fecha, hora, Medido, Cortado), la pantalla
  // la acompaña y la marca un instante, así no se pierde de vista.
  const sel='.card[data-key="'+c.key+'"]';
  const a0=document.querySelector(sel);
  const y0=a0?a0.getBoundingClientRect().top:null;
  aplicar();
  dibujar();
  const a1=document.querySelector(sel);
  if(a1&&y0!==null&&body.accion!=='mover'&&Math.abs(a1.getBoundingClientRect().top-y0)>30){
    recien={key:c.key,tipo:'llego',t:Date.now()};
    a1.classList.add('llego');
    a1.scrollIntoView({block:'center',behavior:'smooth'});
  }
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
  if(f.length){
    recien={key:c.key,tipo:'nope',t:Date.now()};
    dibujar();
    toast('Falta: '+f.join(', '));
    return;
  }
  guardar(c,{accion:'mover',a:destino},()=>{
    c.etapa=destino;c.fecha='';c.hora='';c.desde=new Date().toISOString();
    recien={key:c.key,tipo:'llego',t:Date.now()};
  });
}

// === Eventos ===
$('buscar').addEventListener('input',()=>{
  const v=$('buscar').value;
  buscado=plano(v).split(/\s+/).filter(Boolean);
  $('buscar').classList.toggle('activo',buscado.length>0);
  $('buscarX').hidden=!v;
  dibujar();
});
$('buscarX').addEventListener('click',()=>{
  $('buscar').value='';
  $('buscar').dispatchEvent(new Event('input'));
  $('buscar').focus();
});
$('buscar').addEventListener('keydown',ev=>{
  if(ev.key==='Escape'&&$('buscar').value){ev.stopPropagation();$('buscarX').click();}
  else if(ev.key==='Enter')$('buscar').blur(); // en celular, cierra el teclado
});
const wrap=document.querySelector('.wrap');

// Vuelve el tablero a lo normal: sin búsqueda, sin aviso prendido y sin filtros de columna.
// Los paneles "Filtrar" quedan como estaban: abrirlos no es filtrar.
function limpiarFiltros(){
  buscado=[];
  $('buscar').value='';
  $('buscar').classList.remove('activo');
  $('buscarX').hidden=true;
  plazoF='';soloSinCto=false;
  Object.keys(falta).forEach(k=>{falta[k]=false;});
  dibujar();
  toast('Filtros limpios: se ven todas las obras',true);
}

$('colTabs').addEventListener('click',ev=>{
  const b=ev.target.closest('button[data-col]');
  if(!b)return;
  colMovil=b.dataset.col;
  if(!SOLO)localStorage.setItem('bs-tablero-col',colMovil);
  dibujar();
  window.scrollTo({top:0});
});

wrap.addEventListener('click',ev=>{
  // Plegar / desplegar una etapa: con su botón, o tocando el título de la etapa plegada.
  const bpl=ev.target.closest('[data-plegar]'),tira=!bpl&&ESCRITORIO.matches?ev.target.closest('#board .col.cerrada'):null;
  if(bpl||tira){
    const k=bpl?bpl.dataset.plegar:tira.dataset.col;
    colCerr[k]=!colCerr[k];
    try{localStorage.setItem('bs-tablero-cerradas',JSON.stringify(Object.keys(colCerr).filter(x=>colCerr[x])));}catch(e){}
    dibujar();
    return;
  }
  if(ev.target.closest('[data-limpiar]')){limpiarFiltros();return;}
  if(ev.target.closest('[data-sincto]')){soloSinCto=!soloSinCto;dibujar();return;}
  const bp=ev.target.closest('[data-plazo]');
  if(bp){plazoF=plazoF===bp.dataset.plazo?'':bp.dataset.plazo;dibujar();return;}
  const bfi=ev.target.closest('[data-filtros]');
  if(bfi){
    const k=bfi.dataset.filtros;
    filtrosAb[k]=!bfi.classList.contains('on');
    // cerrar el panel apaga los filtros de esa columna: no quedan filtros prendidos a escondidas
    if(!filtrosAb[k])Object.keys(falta).forEach(f=>{if(f.slice(-k.length-1)==='_'+k||(k===ETAPAS[0].id&&(f==='plano'||f==='bacha')))falta[f]=false;});
    try{localStorage.setItem('bs-tablero-filtros',JSON.stringify(Object.keys(filtrosAb).filter(x=>filtrosAb[x])));}catch(e){}
    dibujar();
    return;
  }
  const bf=ev.target.closest('[data-falta]');
  if(bf){
    // Los filtros de estado de una columna (ejecutar / continuar / con el cortador) son
    // excluyentes: prender uno apaga los otros dos. Los demás se combinan libremente.
    const k=bf.dataset.falta,grupo=['pend_','cont_','mano_'],mio=grupo.find(g=>k.indexOf(g)===0);
    falta[k]=!falta[k];
    if(falta[k]&&mio)grupo.filter(g=>g!==mio).forEach(g=>{falta[k.replace(mio,g)]=false;});
    dibujar();
    return;
  }
  const b=ev.target.closest('[data-act]');
  if(!b)return;
  const art=b.closest('.card');
  const c=art&&cardDe(art.dataset.key);
  if(!c)return;
  const act=b.dataset.act;
  if(!OPERA&&!['abrir','plegar','mas','adjuntar','ver'].concat(SOLO_BACHA?['bacha']:[]).includes(act))return; // solo mira, adjunta y anota la bacha
  if(act==='bacha'){pedirBacha(c);return;}
  if(act==='abrir'||act==='plegar'){
    if(Date.now()-ultimoArrastre<400)return;
    VISTA[c.key]=act==='abrir';
    if(act==='plegar'&&abierta===c.key)abierta='';
    dibujar();
    const a=document.querySelector('.card[data-key="'+c.key+'"]');
    if(a)a.scrollIntoView({block:'nearest'});
    return;
  }
  const cols=columnas();
  const idx=cols.findIndex(e=>e.id===c.etapa);

  if(act==='mas'){
    abierta=abierta===c.key?'':c.key;
    dibujar();
  }else if(act==='check'){
    const clave=b.dataset.clave,valor=!c.checks[clave];
    if(clave==='sena'&&valor){pedirSena(c);return;}
    if(clave==='acceso'&&valor){pedirAcceso(c);return;}
    if(clave==='bacha'){pedirBacha(c);return;} // tildada o no: abre la ventana para cargar o corregir
    if(PERSONAS[clave]){pedirPersonas(c,clave);return;} // tildado o no, abre la lista para elegir o cambiar
    if(APROB.indexOf(clave)>=0&&!PUEDE_APROBAR){toast('Tu usuario no puede dar aprobaciones.');return;}
    guardar(c,{accion:'check',clave:clave,valor:valor},()=>{
      if(valor)c.checks[clave]=true;else delete c.checks[clave];
      // la aprobación queda a nombre de quien la da (el servidor lo confirma al recargar)
      if(APROB.indexOf(clave)>=0){c.por=c.por||{};if(valor&&YO.nombre&&!YO.viejo)c.por[clave]=YO.nombre;else delete c.por[clave];}
      if(clave==='sena')c.sena=null;
      if(clave==='acceso')c.acceso=null;
      if(clave==='bacha')c.bacha='';
      // Medido / Cortado: los botones de etapa guardan el día. No dependen de ningún otro tilde.
      if(clave==='medido'){c.medido_at=valor?((c.fecha&&c.fecha<=HOY)?c.fecha:HOY):'';c.boton_at=c.medido_at;}
      if(clave==='cortado'||clave==='fabricado'||clave==='instalado')c.boton_at=valor?HOY:'';
    });
  }else if(act==='avanzar'){
    mover(c,cols[idx+1].id);
  }else if(act==='atras'){
    if(idx>0)mover(c,cols[idx-1].id);
    else guardar(c,{accion:'mover',hacia:'atras'},()=>{}); // desde Retiro: el server sabe de dónde vino
  }else if(act==='retiro'){
    mover(c,'retiro');
  }else if(act==='fecha'&&etapaDe(c.etapa).pide_hora){
    pedirTurno(c);
  }else if(act==='fecha'){
    const dp=$('dp');
    const r=b.getBoundingClientRect();
    dp.style.left=(r.left+window.scrollX)+'px';
    dp.style.top=(r.bottom+window.scrollY)+'px';
    fechaKey=c.key;
    dp.value=c.fecha||'';
    try{dp.showPicker();}catch(e){dp.style.pointerEvents='auto';dp.focus();dp.click();}
  }else if(act==='editar'){
    pedirEditar(c);
  }else if(act==='borrar'){
    pedirBorrar(c);
  }else if(act==='adjuntar'){
    pedirAdjunto(c,b.dataset.tipo);
  }else if(act==='ver'){
    verAdjunto(c,b.dataset.id);
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
  const g=drag.fantasma,aviso=g.querySelector('.f-aviso');
  slot.remove();
  g.className=drag.base+' et-'+drag.c.etapa;
  if(!zona||zona.dataset.col===drag.c.etapa)return;
  zona.classList.add('drop');
  const destino=zona.dataset.col,f=drag.falta[destino]||[];
  const txt=f.length?'Falta: '+f.join(', '):'Soltar en '+etapaDe(destino).titulo;
  // la tarjeta en el aire se pinta del color de la etapa de destino, o de rojo si no puede pasar
  g.className=drag.base+' '+(f.length?'bloq et-'+drag.c.etapa:'va et-'+destino);
  aviso.textContent=txt;
  if(zona.classList.contains('col')){
    slot.className='slot'+(f.length?' bloq':'');
    slot.textContent=txt;
    zona.insertBefore(slot,zona.querySelector('.card,.cards,.col-vacia'));
    if(zona.scrollTop)zona.scrollTop=0; // la columna tiene su propio desplazamiento: que el cartel se vea
  }
}
function dragEmpezar(){
  const r=drag.art.getBoundingClientRect();
  drag.dx=drag.x-r.left;drag.dy=drag.y-r.top;
  const g=drag.art.cloneNode(true);
  drag.base='card fantasma'+(drag.dedo?' mini':'');
  g.className=drag.base+' et-'+drag.c.etapa;
  g.removeAttribute('style');
  if(drag.dedo){const w=Math.min(250,r.width);g.style.width=w+'px';drag.dx=w/2;drag.dy=-26;}
  else g.style.width=r.width+'px';
  const aviso=document.createElement('div');
  aviso.className='f-aviso';
  g.insertBefore(aviso,g.firstChild);
  document.body.appendChild(g);
  drag.fantasma=g;
  drag.activo=true;
  drag.art.classList.add('arrastrando');
  document.body.classList.add('arrastre');
  // todas las etapas muestran de entrada si reciben o no esta tarjeta
  drag.falta={};
  document.querySelectorAll('.col[data-col],#colTabs button[data-col]').forEach(z=>{
    const id=z.dataset.col;
    if(id===drag.c.etapa){z.classList.add('origen');return;}
    if(!drag.falta[id])drag.falta[id]=faltaPara(drag.c,id);
    z.classList.add(drag.falta[id].length?'bloq':'puede');
  });
  dragSeguir(drag.x,drag.y);
  requestAnimationFrame(dragBorde);
}
function dragSeguir(x,y){
  let gx=x-drag.dx;
  if(drag.dedo)gx=Math.max(6,Math.min(gx,window.innerWidth-drag.fantasma.offsetWidth-6)); // en celular no se sale de la pantalla
  drag.fantasma.style.transform='translate('+gx+'px,'+(y-drag.dy)+'px) rotate(1.5deg)';
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
  ultimoArrastre=Date.now();
  d.fantasma.remove();
  slot.remove();
  d.art.classList.remove('arrastrando');
  document.body.classList.remove('arrastre');
  document.querySelectorAll('.col[data-col],#colTabs button[data-col]').forEach(z=>z.classList.remove('puede','bloq','origen','drop'));
  if(!cancelar&&d.zona)mover(d.c,d.zona.dataset.col);
}

wrap.addEventListener('pointerdown',ev=>{
  if(!OPERA)return; // sin permiso no se arrastra
  if(SOLO||drag||ocupado||(ev.pointerType==='mouse'&&ev.button!==0))return;
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
let modalProtegido=null; // si devuelve true, tocar afuera no cierra la ventana (hay datos sin guardar)
function abrirModal(titulo,cuerpo,boton,alAceptar){
  $('mCancelar').style.display='';
  $('mOk').classList.remove('peligro');
  $('mTitulo').textContent=titulo;
  $('mCuerpo').innerHTML=cuerpo;
  $('mOk').textContent=boton;
  modalOk=alAceptar;
  $('modalBg').classList.add('show');
}
function cerrarModal(){$('modalBg').classList.remove('show');modalOk=null;modalProtegido=null;$('mOk').disabled=false;}
$('mCancelar').addEventListener('click',cerrarModal);
$('modalBg').addEventListener('click',ev=>{if(ev.target===$('modalBg')&&!(modalProtegido&&modalProtegido()))cerrarModal();});
document.addEventListener('keydown',ev=>{
  if(ev.key==='Escape'){if(drag)dragSoltar(true);cerrarModal();}
  else if(ev.key==='Enter'&&modalOk&&$('modalBg').classList.contains('show')&&!$('mOk').classList.contains('peligro')){
    if(ev.target&&ev.target.closest&&ev.target.closest('button,a'))return; // el foco está en otro botón (USD, Cancelar): Enter aprieta ESE botón
    ev.preventDefault();$('mOk').click();} // (borrar nunca se confirma con Enter)
});
$('mOk').addEventListener('click',()=>{if(modalOk)modalOk();});

// Seña: no alcanza con tildar, hay que cargar cuánto se cobró.
function pedirSena(c){
  let moneda='ARS';
  abrirModal('Seña de '+nroTxt(c)+' · '+(nombreCorto(c.nombre)||'Sin nombre'),
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
  // Mientras se escribe, el monto se va separando en miles: 1.000 · 10.000 · 100.000 · 1.000.000
  $('senaMonto').addEventListener('input',()=>{
    const el=$('senaMonto');
    const t=el.value.replace(/[^\d,]/g,'');
    const i=t.indexOf(',');
    const entero=(i>=0?t.slice(0,i):t).replace(/^0+(?=\d)/,'').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
    el.value=entero+(i>=0?','+t.slice(i+1).replace(/,/g,'').slice(0,2):''); // la coma abre los centavos
  });
  $('senaMonto').focus();
}

// Piso y ascensor: un toque para decir cómo sube la pieza.
function pedirAcceso(c){
  let modo='';
  abrirModal('Piso y ascensor · '+nroTxt(c),
    '<p>¿Cómo sube la pieza?</p><div class="sena"><button type="button" data-op="pb">Planta baja</button><button type="button" data-op="ascensor">Por ascensor</button><button type="button" data-op="escalera">Por escalera</button></div>'+
    '<div class="sena" id="accPisos" style="display:none;margin-top:10px"><input type="text" id="accN" inputmode="numeric" autocomplete="off" placeholder="¿Cuántos pisos?"></div>',
    'Guardar',
    ()=>{
      if(!modo){toast('Elegí cómo sube la pieza.');return;}
      const pisos=modo==='escalera'?parseInt($('accN').value,10):0;
      if(modo==='escalera'&&!(pisos>=1&&pisos<=60)){$('accN').focus();toast('Cargá cuántos pisos por escalera.');return;}
      cerrarModal();
      guardar(c,{accion:'check',clave:'acceso',valor:true,modo:modo,pisos:pisos},()=>{
        c.checks.acceso=true;c.acceso={modo:modo,pisos:pisos};
      });
    });
  $('mCuerpo').querySelectorAll('[data-op]').forEach(b=>b.addEventListener('click',()=>{
    modo=b.dataset.op;
    $('mCuerpo').querySelectorAll('[data-op]').forEach(x=>x.classList.toggle('on',x===b));
    $('accPisos').style.display=modo==='escalera'?'flex':'none';
    if(modo==='escalera')$('accN').focus();else $('mOk').focus();
  }));
}

// Turno con día y hora (Por instalar).
function pedirTurno(c){
  const e=etapaDe(c.etapa);
  abrirModal(e.fecha+' · '+nroTxt(c),
    '<p>'+(e.exige_fecha?'¿Qué día y a qué hora?':'¿Qué día? La hora es opcional.')+'</p><div class="sena"><input type="date" id="tuDia" value="'+esc(c.fecha)+'"><input type="time" id="tuHora" step="900" value="'+esc(c.hora)+'"></div>',
    'Guardar',
    ()=>{
      const dia=$('tuDia').value,hora=dia?$('tuHora').value.slice(0,5):'';
      if(dia&&!hora&&e.exige_fecha){$('tuHora').focus();toast('Falta la hora.');return;}
      cerrarModal();
      if(dia===c.fecha&&hora===c.hora)return;
      guardar(c,{accion:'fecha',fecha:dia,hora:hora},()=>{c.fecha=dia;c.hora=hora;});
    });
  (c.fecha?$('tuHora'):$('tuDia')).focus();
}

// Editar una obra ya cargada: corregir sus datos o cambiarle el N° de presupuesto, en cualquier etapa.
// Al cambiar el N°, la ficha se muda a ese presupuesto con todo lo hecho (tildes, fechas, fotos).
// ¿Es un nombre de verdad? Un teléfono puesto en el lugar del nombre no tiene letras.
const tieneLetras=s=>/[a-záéíóúüñ]/i.test(String(s||''));
function pedirEditar(c){
  const campo=(id,rotulo,valor,extra)=>'<label class="campo">'+rotulo+'<input type="text" id="'+id+'" autocomplete="off" value="'+esc(valor)+'" '+(extra||'')+'></label>';
  const propio=c.cliente_nro+'-'+c.sub;
  // Una obra cargada a mano no tiene presupuesto: el campo arranca vacío, para poner el número cuando exista.
  const nro0=c.manual?'':propio;
  const conBacha=c.bacha==='nuestra'||c.bacha==='cliente';
  // Un presupuesto hecho sin saber el nombre trae el teléfono en su lugar: el campo arranca vacío para escribirlo.
  const sinNombre=!tieneLetras(c.nombre);
  abrirModal('Editar obra '+propio,
    campo('edNro','N° de presupuesto',nro0,'maxlength="12" inputmode="numeric" placeholder="'+(c.manual?'Todavía sin presupuesto':'Ej: 0042 o 0042-2')+'"')+
    '<p>'+(c.manual?'Esta obra se cargó a mano. Cuando el presupuesto esté hecho, poné acá su número.':'Cambialo para pasar la ficha a otro presupuesto.')+' La ficha conserva sus tildes, fechas y fotos.</p>'+
    campo('edNombre','Nombre y apellido',sinNombre?'':c.nombre,'maxlength="120" placeholder="'+(sinNombre&&c.nombre?'Falta: hoy figura '+esc(c.nombre):'Nombre y apellido del cliente')+'"')+
    campo('edCel','Teléfono',c.celular,'maxlength="40" inputmode="tel"')+
    campo('edDir','Dirección',c.direccion,'maxlength="200"')+
    '<label class="campo mat-wrap">Material y color (del catálogo de la calculadora)<input type="text" id="edMat" autocomplete="off" maxlength="200" value="'+esc(c.material)+'"><div class="mat-caja" id="edMatCaja"></div></label>'+
    // El modelo de la bacha se puede cargar o corregir en cualquier etapa (el tilde solo vive en Por medir).
    (conBacha?campo('edBacha','Modelo de la bacha ('+BACHA[c.bacha]+')',c.bacha_modelo||'','maxlength="80" placeholder="Ej: Johnson E37, 37 × 34"'):'')+
    // La nota de la ficha (lo que la frena, lo que hay que saber) va última.
    campo('edNota','Nota',c.nota,'maxlength="300" placeholder="Ej: falta la bacha, llamar antes de ir"')+
    '<p class="m-error" id="edError" hidden></p>',
    'Guardar',
    async()=>{
      const v=id=>$(id).value.trim();
      const nro=v('edNro'),cambiaNro=!!nro&&nro.replace(/^0+(?=\d)/,'')!==nro0.replace(/^0+(?=\d)/,'');
      const body={cliente_nro:c.cliente_nro,sub:c.sub};
      if(cambiaNro)body.nro=nro; // con N° nuevo los datos los pone ese presupuesto
      else{
        // solo viaja lo que cambió
        [['nombre','edNombre',c.nombre],['celular','edCel',c.celular],['direccion','edDir',c.direccion],['material','edMat',c.material]]
          .forEach(x=>{
            if(v(x[1])===String(x[2]||'').trim())return;          // no se tocó: queda exactamente como estaba
            if(x[0]==='nombre'&&sinNombre&&!v(x[1]))return;       // no tenía nombre y no se le puso: queda como estaba
            body[x[0]]=x[0]==='material'?materialNormal(v(x[1])):v(x[1]);
          });
        if(body.nombre===''){$('edNombre').focus();toast('El nombre no puede quedar vacío.');return;}
      }
      const modelo=conBacha?v('edBacha').replace(/\s+/g,' '):'';
      const cambiaBacha=conBacha&&modelo!==(c.bacha_modelo||'');
      const cambiaDatos=cambiaNro||Object.keys(body).length>2;
      const nota=v('edNota').replace(/\s+/g,' '),cambiaNota=nota!==String(c.nota||'').trim();
      if(!cambiaDatos&&!cambiaBacha&&!cambiaNota){cerrarModal();return;} // no cambió nada
      if(cambiaBacha&&!modelo&&c.bacha==='nuestra'&&c.checks.bacha){$('edBacha').focus();toast('Poné el modelo de la bacha.');return;}
      if(ocupado)return;
      ocupado=true;
      // Igual que al cargar: la ventana no se cierra hasta que el server confirma.
      const b=$('mOk'),rotulo=b.textContent;
      b.disabled=true;b.textContent='Guardando…';
      $('edError').hidden=true;
      let j={cliente_nro:c.cliente_nro,sub:c.sub};
      try{
        // Primero la bacha (va con la ficha aunque después cambie de número); si algo falla,
        // lo que ya se guardó no se vuelve a mandar al reintentar.
        if(cambiaBacha&&modelo!==(c.bacha_modelo||'')){
          await pedir(API,{cliente_nro:c.cliente_nro,sub:c.sub,accion:'bacha',origen:c.bacha,modelo:modelo});
          c.bacha_modelo=modelo;c._txt='';
        }
        if(cambiaNota&&nota!==String(c.nota||'').trim()){
          await pedir(API,{cliente_nro:c.cliente_nro,sub:c.sub,accion:'nota',nota:nota});
          c.nota=nota;c._txt='';
        }
        if(cambiaDatos)j=await pedir(API_EDITAR,body);
      }catch(e){
        ocupado=false;
        b.disabled=false;b.textContent=rotulo;
        $('edError').textContent=e.message;$('edError').hidden=false;
        if(cambiaNro){$('edNro').focus();$('edNro').select();}
        return;
      }
      b.disabled=false;
      cerrarModal();
      ocupado=false;
      const key=j.cliente_nro+'-'+j.sub;
      if(j.mudada){
        // Enlazados, el contrato comparte el N° con la ficha: la sigue a su número nuevo. Relación-Contrato
        // es opcional: si no existe o no contesta, la ficha se mudó igual y no pasa nada.
        try{await fetch(API_CONTRATOS,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({accion:'mudar_enlazado',de:{cliente_nro:c.cliente_nro,sub:c.sub},a:{cliente_nro:j.cliente_nro,sub:j.sub}})});}catch(e){}
        // en modo "una ficha" (calendario) la página sigue a la obra a su número nuevo
        if(SOLO){location.replace('?solo='+encodeURIComponent(key));return;}
        if(abierta===c.key)abierta=key;
        recien={key:key,tipo:'llego',t:Date.now()};
        toast('La ficha ahora es la '+key,true);
      }else toast('Obra '+key+' guardada',true);
      await cargar();
    });
  // con algo modificado, tocar afuera no cierra la ventana
  const ids=['edNro','edNombre','edCel','edDir','edMat','edNota'].concat(conBacha?['edBacha']:[]);
  const ini=ids.map(id=>$(id).value);
  modalProtegido=()=>ids.some((id,i)=>$(id)&&$(id).value!==ini[i]);
  conCatalogo('edMat','edMatCaja');
  $(c.manual?'edNro':'edNombre').focus();
}

// "Cargar obra" ya no existe en el tablero (dueño, 18/11/2026, decisión final): toda obra nace de su
// contrato, en Relación-Contrato ("Cargar en Por medir"). Así el contrato y la ficha van siempre enlazados.

// Bacha: de quién es.
function pedirBacha(c){
  // Se abre tanto para tildar como para corregir: de quién es la bacha y qué modelo es.
  let origen=c.bacha||''; // si ya se anotó al cargar la obra, viene elegida
  const op=(v,t)=>'<button type="button" data-op="'+v+'" class="'+(origen===v?'on':'')+'">'+t+'</button>';
  abrirModal('Bacha · '+nroTxt(c),
    '<p>¿De quién es la bacha?</p><div class="sena">'+op('nuestra','Nuestra')+op('cliente','Del cliente')+op('no','No lleva')+'</div>'+
    '<label class="campo" id="bachaModL" style="margin-top:16px"><span id="bachaModT">Modelo de la bacha</span><input type="text" id="bachaMod" maxlength="80" autocomplete="off" placeholder="Ej: Johnson E37, 37 × 34" value="'+esc(c.bacha_modelo||'')+'"></label>'+
    '<button type="button" class="adjuntar" id="bachaFoto">+ Adjuntar foto de la bacha</button>'+
    (OPERA&&(c.checks.bacha||c.bacha)?'<p style="margin:14px 0 0"><button type="button" class="lnk" id="bachaQuitar">'+(c.checks.bacha?'Quitar el tilde':'Borrar el dato')+'</button></p>':''),
    'Guardar',
    ()=>{
      if(!origen){toast('Elegí de quién es la bacha.');return;}
      const modelo=origen==='no'?'':$('bachaMod').value.trim().replace(/\s+/g,' ');
      // el modelo obliga solo si la bacha es nuestra; si es del cliente, se pone si se sabe
      if(origen==='nuestra'&&!modelo){$('bachaMod').focus();toast('Poné el modelo de la bacha.');return;}
      cerrarModal();
      if((c.checks.bacha||!OPERA)&&origen===c.bacha&&modelo===(c.bacha_modelo||''))return;
      // Sin operar el tablero se guarda solo el dato (de quién es y el modelo): el tilde no se toca.
      if(!OPERA){guardar(c,{accion:'bacha',origen:origen,modelo:modelo},()=>{c.bacha=origen;c.bacha_modelo=modelo;c._txt='';});return;}
      guardar(c,{accion:'check',clave:'bacha',valor:true,origen:origen,modelo:modelo},()=>{c.checks.bacha=true;c.bacha=origen;c.bacha_modelo=modelo;c._txt='';});
    });
  const ver=()=>{
    $('bachaModL').style.display=origen==='no'?'none':'';
    $('bachaModT').textContent=origen==='cliente'?'Modelo de la bacha (si se sabe)':'Modelo de la bacha';
  };
  ver();
  $('mCuerpo').querySelectorAll('[data-op]').forEach(b=>b.addEventListener('click',()=>{
    origen=b.dataset.op;
    $('mCuerpo').querySelectorAll('[data-op]').forEach(x=>x.classList.toggle('on',x===b));
    ver();
    if(origen==='no')$('mOk').focus();else $('bachaMod').focus();
  }));
  $('bachaFoto').addEventListener('click',()=>elegirArchivo(c,'bacha'));
  if($('bachaQuitar'))$('bachaQuitar').addEventListener('click',()=>{
    cerrarModal();
    guardar(c,{accion:'check',clave:'bacha',valor:false},()=>{delete c.checks.bacha;c.bacha='';c.bacha_modelo='';c._txt='';});
  });
}
// ============================================================
// Adjuntos: foto de bacha y plano
// ============================================================
const fileAdj=document.createElement('input');
fileAdj.type='file';fileAdj.style.display='none';
document.body.appendChild(fileAdj);
let adjPara=null,subiendo=false;

// Sumar una foto o un plano: se sube desde el dispositivo, o se pega el link de Drive donde ya está.
function pedirAdjunto(c,tipo){
  abrirModal(ADJ_L[tipo]+' · '+nroTxt(c),
    '<button type="button" class="adjuntar subir" id="adjSubir">Subir desde este dispositivo</button>'+
    '<div class="adj-o">o</div>'+
    '<label class="campo">Pegar el link de Drive<input type="url" id="adjLink" autocomplete="off" inputmode="url" placeholder="https://drive.google.com/…"></label>'+
    '<p>El archivo queda en Drive y acá se guarda solo el link. Compartilo con quienes tengan que verlo.</p>'+
    '<p class="m-error" id="adjError" hidden></p>',
    'Guardar link',
    async()=>{
      const escrito=$('adjLink').value.trim();
      if(!escrito){$('adjLink').focus();toast('Pegá el link de Drive, o tocá "Subir desde este dispositivo".');return;}
      const url=linkValido(escrito);
      if(!url){$('adjLink').focus();$('adjError').textContent='Ese link no sirve. Tiene que ser un link de Google Drive (empieza con https://drive.google.com/).';$('adjError').hidden=false;return;}
      if(ocupado)return;
      ocupado=true;
      const b=$('mOk'),rotulo=b.textContent;
      b.disabled=true;b.textContent='Guardando…';
      $('adjError').hidden=true;
      try{
        await pedir(API_ADJ,{cliente_nro:c.cliente_nro,sub:c.sub,accion:'link',tipo:tipo,url:url});
      }catch(e){
        ocupado=false;
        b.disabled=false;b.textContent=rotulo;
        $('adjError').textContent=e.message;$('adjError').hidden=false;
        return;
      }
      b.disabled=false;
      cerrarModal();
      ocupado=false;
      toast(ADJ_OK[tipo]+' (link de Drive)',true);
      await cargar();
    });
  modalProtegido=()=>!!($('adjLink')&&$('adjLink').value.trim());
  $('adjSubir').addEventListener('click',()=>{cerrarModal();elegirArchivo(c,tipo);});
}

function elegirArchivo(c,tipo){
  if(subiendo)return;
  adjPara={nro:c.cliente_nro,sub:c.sub,tipo:tipo};
  fileAdj.accept=tipo==='plano'?'image/*,application/pdf':'image/*'; // el plano también puede ser PDF
  fileAdj.value='';
  fileAdj.click();
}
fileAdj.addEventListener('change',()=>{
  const f=fileAdj.files&&fileAdj.files[0];
  if(f&&adjPara)subir(adjPara,f);
});

// Achica la foto antes de mandarla: sube rápido desde el celular y no llena el servidor.
function achicar(file,lado,calidad){
  return new Promise(res=>{
    const img=new Image(),url=URL.createObjectURL(file);
    img.onload=()=>{
      const k=Math.min(1,lado/Math.max(img.naturalWidth,img.naturalHeight));
      const cv=document.createElement('canvas');
      cv.width=Math.max(1,Math.round(img.naturalWidth*k));
      cv.height=Math.max(1,Math.round(img.naturalHeight*k));
      const g=cv.getContext('2d');
      g.fillStyle='#fff';g.fillRect(0,0,cv.width,cv.height); // por si la imagen tiene transparencia
      g.drawImage(img,0,0,cv.width,cv.height);
      URL.revokeObjectURL(url);
      cv.toBlob(b=>res(b),'image/jpeg',calidad);
    };
    img.onerror=()=>{URL.revokeObjectURL(url);res(null);};
    img.src=url;
  });
}

async function subir(para,file){
  if(subiendo)return;
  subiendo=true;
  toast('Subiendo: '+ADJ_L[para.tipo].toLowerCase()+'…',true);
  try{
    const fd=new FormData();
    fd.append('cliente_nro',para.nro);fd.append('sub',para.sub);fd.append('tipo',para.tipo);
    if(file.type==='application/pdf'){
      if(file.size>10*1024*1024)throw new Error('El PDF es demasiado pesado (máximo 10 MB).');
      fd.append('archivo',file,'plano.pdf');
    }else{
      // el plano se guarda más grande que la bacha: tiene que leerse la letra chica
      const grande=await achicar(file,para.tipo==='plano'?2400:1600,.86);
      if(!grande)throw new Error('No se pudo leer la imagen. Probá con otra foto.');
      const mini=await achicar(file,260,.72);
      fd.append('archivo',grande,'foto.jpg');
      if(mini)fd.append('mini',mini,'mini.jpg');
    }
    const r=await fetch(API_ADJ,{method:'POST',body:fd,credentials:'same-origin'});
    if(r.status===401){location.href='/calculadora/login.php';return;}
    let j=null;
    try{j=await r.json();}catch(e){}
    if(!j||!j.ok)throw new Error((j&&j.error)||'No se pudo subir el archivo');
    toast(ADJ_OK[para.tipo],true);
  }catch(e){
    toast(e.message);
  }
  subiendo=false;
  await cargar();
}

function verAdjunto(c,id){
  const a=c.adjuntos.find(x=>x.id===id);
  if(!a)return;
  const url=a.link||urlAdj(c,a),foto=!a.link&&!a.pdf;
  abrirModal(ADJ[a.tipo]+' · '+nroTxt(c),
    '<div class="visor">'+(a.link?'<p class="visor-link">Está en Drive: '+esc(a.link.replace(/^https:\/\//,'').slice(0,60))+(a.link.length>68?'…':'')+'</p>':(a.pdf?'':'<div class="lienzo" id="adjLienzo"><img src="'+esc(url)+'" alt="" id="adjImg"></div>'))+
    '<div class="acciones"><a href="'+esc(url)+'" target="_blank" rel="noopener noreferrer">'+(a.link?'Abrir en Drive':(a.pdf?'Abrir el PDF':'Abrir en grande'))+'</a>'+
    (foto&&a.mio!==false?'<button type="button" class="girar" id="adjGirar" title="Gira la foto un cuarto de vuelta">Girar</button>':'')+
    (a.mio!==false?'<button type="button" class="borrar" id="adjBorrar">Borrar</button>':'')+'</div></div>',
    'Cerrar',cerrarModal);
  $('mCancelar').style.display='none';
  // Girar: cada toque es un cuarto de vuelta, y se ve al instante. Recién al tocar "Guardar así" la
  // foto girada reemplaza a la original, para todos (también la miniatura de la ficha).
  let giro=0,fuente=null;
  const dibujarGirada=(lado,calidad)=>new Promise(res=>{
    const w=fuente.naturalWidth,h=fuente.naturalHeight,k=Math.min(1,lado/Math.max(w,h));
    const cw=Math.max(1,Math.round(w*k)),ch=Math.max(1,Math.round(h*k)),cv=document.createElement('canvas');
    cv.width=giro%180?ch:cw;cv.height=giro%180?cw:ch;
    const g=cv.getContext('2d');
    g.fillStyle='#fff';g.fillRect(0,0,cv.width,cv.height);
    g.translate(cv.width/2,cv.height/2);g.rotate(giro*Math.PI/180);g.drawImage(fuente,-cw/2,-ch/2,cw,ch);
    if(calidad)cv.toBlob(b=>res(b),'image/jpeg',calidad);else res(cv);
  });
  if(foto&&$('adjGirar'))$('adjGirar').addEventListener('click',async()=>{
    if(ocupado)return;
    if(!fuente){
      const img=$('adjImg');
      if(!img||!img.complete||!img.naturalWidth){toast('La foto todavía se está cargando.');return;}
      fuente=img;
    }
    giro=(giro+90)%360;
    const cv=await dibujarGirada(1400,0);
    cv.id='adjVista';
    const l=$('adjLienzo');
    [...l.children].forEach(x=>{if(x!==fuente)x.remove();});
    fuente.style.display=giro?'none':'';
    if(giro)l.appendChild(cv);
    // el botón principal pasa a guardar el giro; sin giro vuelve a ser "Cerrar"
    $('mOk').textContent=giro?'Guardar así':'Cerrar';
    $('mCancelar').style.display=giro?'':'none';
    modalOk=giro?guardarGiro:cerrarModal;
    modalProtegido=()=>giro>0; // con un giro sin guardar, tocar afuera no cierra la ventana
  });
  const guardarGiro=async()=>{
    if(ocupado||!giro)return;
    ocupado=true;
    const b=$('mOk');
    b.disabled=true;b.textContent='Guardando…';
    try{
      const grande=await dibujarGirada(a.tipo==='plano'?2400:1600,.9),mini=await dibujarGirada(260,.72);
      if(!grande)throw new Error('No se pudo girar la foto.');
      const fd=new FormData();
      fd.append('cliente_nro',c.cliente_nro);fd.append('sub',c.sub);fd.append('tipo',a.tipo);fd.append('reemplaza',a.id);
      fd.append('archivo',grande,'foto.jpg');
      if(mini)fd.append('mini',mini,'mini.jpg');
      const r=await fetch(API_ADJ,{method:'POST',body:fd,credentials:'same-origin'});
      if(r.status===401){location.href='/calculadora/login.php';return;}
      let j=null;
      try{j=await r.json();}catch(e){}
      if(!j||!j.ok)throw new Error((j&&j.error)||'No se pudo guardar la foto girada');
      b.disabled=false;
      cerrarModal();
      toast('Foto girada y guardada',true);
    }catch(e){
      b.disabled=false;b.textContent='Guardar así';
      toast(e.message);
    }
    ocupado=false;
    await cargar();
  };
  let seguro=false;
  if($('adjBorrar'))$('adjBorrar').addEventListener('click',async()=>{
    if(!seguro){seguro=true;$('adjBorrar').textContent='¿Seguro? Tocá de nuevo para borrar';return;}
    if(ocupado)return;
    ocupado=true;
    cerrarModal();
    try{
      await pedir(API_ADJ,{cliente_nro:c.cliente_nro,sub:c.sub,accion:'borrar',id:id});
    }catch(e){
      toast(e.message);
    }
    ocupado=false;
    await cargar();
  });
}

// Instaladores y fabricadores: uno o varios. Se puede volver a abrir para cambiarlos.
function pedirPersonas(c,clave){
  const P=PERSONAS[clave],lista=P.lista();
  const elegidos=new Set(c[P.campo]);
  abrirModal(P.titulo+' · '+nroTxt(c),
    '<p>'+P.pregunta+' Podés marcar más de uno.</p><div class="sena">'+lista.map(n=>'<button type="button" data-op="'+esc(n)+'" class="'+(elegidos.has(n)?'on':'')+'">'+esc(n)+'</button>').join('')+'</div>',
    'Guardar',
    ()=>{
      const personas=lista.filter(n=>elegidos.has(n));
      cerrarModal();
      if(!personas.length&&!c.checks[clave])return;
      guardar(c,{accion:'check',clave:clave,valor:personas.length>0,personas:personas},()=>{
        if(personas.length)c.checks[clave]=true;else delete c.checks[clave];
        c[P.campo]=personas;
      });
    });
  $('mCuerpo').querySelectorAll('[data-op]').forEach(b=>b.addEventListener('click',()=>{
    const n=b.dataset.op;
    if(elegidos.has(n))elegidos.delete(n);else elegidos.add(n);
    b.classList.toggle('on',elegidos.has(n));
  }));
}

// Relación-Contrato es opcional: si no existe (se puede borrar entera), estas dos llamadas
// fallan en silencio y el tablero sigue igual. Es la ÚNICA vez que el tablero le habla a contratos.
const API_CONTRATOS='/calculadora/api/contratos.php';
async function contratoDe(c){ // el contrato enlazado con esta ficha, o null
  try{
    const r=await fetch(API_CONTRATOS+'?enlace='+encodeURIComponent(c.cliente_nro+'-'+c.sub),{credentials:'same-origin',cache:'no-store'});
    const j=await r.json();
    return j&&j.ok?j.contrato:null;
  }catch(e){return null;}
}
async function borrarContratoDe(c){
  try{
    const r=await fetch(API_CONTRATOS,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({accion:'borrar_enlazado',cliente_nro:c.cliente_nro,sub:c.sub})});
    const j=await r.json();
    return !!(j&&j.ok&&j.borrado);
  }catch(e){return false;}
}

// Borrar ficha: siempre pide confirmación y dice exactamente qué se pierde.
// Si la ficha está enlazada con un contrato, se borran los dos (regla del dueño, 16/11/2026).
async function pedirBorrar(c){
  const quien=nroTxt(c)+' · '+(nombreCorto(c.nombre)||'Sin nombre');
  const n=c.adjuntos.length;
  const ct=await contratoDe(c);
  abrirModal('¿Borrar la ficha '+nroTxt(c)+'?',
    '<p><b>'+esc(quien)+'</b></p>'+
    '<p>Sale del tablero y del calendario, con sus tildes, fechas y datos'+(n?', y '+(n===1?'su archivo adjunto':'sus '+n+' archivos adjuntos'):'')+'.</p>'+
    (ct?'<p class="ojo">Está enlazada con un contrato de Relación-Contrato: <b>también se borra el contrato</b>'+(ct.pagos||ct.archivo?', con '+[ct.pagos?(ct.pagos===1?'su pago':'sus '+ct.pagos+' pagos'):'',ct.archivo?'su presupuesto adjunto':''].filter(Boolean).join(' y '):'')+'.</p>':'')+
    '<p>Va a la <b>papelera</b>: se puede recuperar durante 30 días'+(ct?', junto con su contrato':'')+'. Después se borra de verdad.</p>',
    'Borrar ficha',
    async()=>{
      if(ocupado)return;
      ocupado=true;
      cerrarModal();
      try{
        // 1) la ficha va a la papelera (no se destruye nada: 30 días para recuperarla)
        await pedir(API_PAPELERA,{accion:'tirar',cliente_nro:c.cliente_nro,sub:c.sub});
        // 2) su contrato enlazado, si lo tiene, va a su propia papelera
        const conContrato=ct?await borrarContratoDe(c):false;
        if(abierta===c.key)abierta='';
        toast('Ficha '+nroTxt(c)+' en la papelera'+(conContrato?', con su contrato':'')+'. Se puede recuperar 30 días.',true);
      }catch(e){
        toast(e.message);
      }
      ocupado=false;
      await cargar();
    });
  $('mOk').classList.add('peligro');
  $('mCancelar').focus(); // el foco queda en Cancelar: Enter no borra por accidente
}

// Papelera: las fichas borradas en los últimos 30 días, con el botón para recuperarlas.
// Restaurar deja la ficha como estaba (etapa, tildes, fechas, fotos) y, si tenía un contrato
// enlazado que se fue con ella, también lo trae de vuelta.
// Archivadas: las obras que ya se entregaron. Desde acá se devuelve al tablero una que se archivó por error.
// (Antes eso se hacía desde la pestaña "Presupuestos · Reporte", que se eliminó el 17/11/2026.)
async function pedirArchivadas(){
  let j;
  try{j=await pedir(API+'?archivadas=1');}catch(e){toast(e.message);return;}
  const filas=j.obras.map(f=>'<div class="pap-f"><div><b>'+(f.manual?'':esc(f.key)+' · ')+esc(nombreCorto(f.nombre)||'Sin nombre')+'</b><small>'+(f.material?esc(f.material)+' · ':'')+'Archivada el '+esc(fechaCorta(f.archivada))+'</small><small>'+(f.quedan<=0?'Se borra hoy':'Se guarda '+f.quedan+(f.quedan===1?' día más':' días más'))+'</small></div><button type="button" data-devolver="'+esc(f.key)+'">Devolver al tablero</button></div>').join('');
  abrirModal('Archivadas',
    (filas?'<p>Obras entregadas. Se guardan '+(j.dias===365?'1 año':j.dias+' días')+' desde que se archivan, con sus fotos y planos. Si una se archivó por error, "Devolver al tablero" la vuelve a la etapa en la que estaba, con todo lo que tenía.</p><div class="pap">'+filas+'</div>':'<p>No hay obras archivadas.</p>'),
    'Cerrar',cerrarModal);
  $('mCancelar').style.display='none';
}
async function devolverArchivada(key,btn){
  if(ocupado)return;
  ocupado=true;btn.disabled=true;btn.textContent='Un momento…';
  const p=key.split('-');
  try{
    await pedir(API_ESTADO,{cliente_nro:p[0],sub:+p[1],nuevo_estado:'aprobado',desde_tablero:true});
    toast('La obra volvió al tablero, a la etapa en la que estaba.');
    ocupado=false;
    await cargar();
    if($('archB').hidden)cerrarModal();else pedirArchivadas();
  }catch(e){toast(e.message);btn.disabled=false;btn.textContent='Devolver al tablero';}
  ocupado=false;
}
async function pedirPapelera(){
  let j;
  try{j=await pedir(API_PAPELERA);}catch(e){toast(e.message);return;}
  const filas=j.fichas.map(f=>'<div class="pap-f"><div><b>'+(f.manual?'':esc(f.key)+' · ')+esc(nombreCorto(f.nombre)||'Sin nombre')+'</b><small>'+esc(f.etapa_titulo)+(f.material?' · '+esc(f.material):'')+(f.adjuntos?' · '+f.adjuntos+(f.adjuntos===1?' adjunto':' adjuntos'):'')+'</small><small>Borrada el '+esc(fechaCorta(f.borrada))+' · '+(f.quedan<=0?'se borra hoy':(f.quedan===1?'queda 1 día':'quedan '+f.quedan+' días'))+'</small></div><button type="button" data-restaurar="'+esc(f.key)+'">Restaurar</button></div>').join('');
  abrirModal('Papelera',
    (filas?'<p>Fichas borradas. Se pueden recuperar durante '+j.dias+' días; después se borran de verdad.</p><div class="pap">'+filas+'</div>':'<p>La papelera está vacía.</p>'),
    'Cerrar',cerrarModal);
  $('mCancelar').style.display='none';
}
async function restaurarFicha(key,btn){
  if(ocupado)return;
  ocupado=true;btn.disabled=true;btn.textContent='Un momento…';
  const p=key.split('-'),c={cliente_nro:p[0],sub:+p[1]};
  try{
    await pedir(API_PAPELERA,{accion:'restaurar',cliente_nro:c.cliente_nro,sub:c.sub});
    // su contrato, si se había ido con ella (Relación-Contrato es opcional: si no existe, no pasa nada)
    let conContrato=false;
    try{
      const r=await fetch(API_CONTRATOS,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify({accion:'restaurar_enlazado',cliente_nro:c.cliente_nro,sub:c.sub})});
      const jj=await r.json();conContrato=!!(jj&&jj.ok&&jj.restaurado);
    }catch(e){}
    toast('Ficha '+(+p[0]>=90001?'':key+' ')+'restaurada'+(conContrato?', con su contrato':''),true);
  }catch(e){toast(e.message);}
  ocupado=false;
  await cargar();
  if($('papB').hidden)cerrarModal();else pedirPapelera();
}
$('papB').addEventListener('click',pedirPapelera);
$('archB').addEventListener('click',pedirArchivadas);
$('mCuerpo').addEventListener('click',ev=>{const b=ev.target.closest('[data-restaurar]');if(b)restaurarFicha(b.dataset.restaurar,b);});
$('mCuerpo').addEventListener('click',ev=>{const b=ev.target.closest('[data-devolver]');if(b)devolverArchivada(b.dataset.devolver,b);});

// Archivar: el presupuesto pasa a "Entregado" en el hub. No borra nada.
function pedirArchivar(c){
  abrirModal('Archivar '+nroTxt(c)+' · '+(nombreCorto(c.nombre)||'Sin nombre'),
    '<p>La obra sale del tablero y el presupuesto pasa a <b>Entregado</b>.</p><p>No se borra nada: queda guardado 1 año en "Archivadas", con sus fotos y planos.</p>',
    'Archivar',
    async()=>{
      if(ocupado)return;
      ocupado=true;
      cerrarModal();
      try{
        await pedir(API_ESTADO,{cliente_nro:c.cliente_nro,sub:c.sub,nuevo_estado:'entregado',conservar_hermanas:true,desde_tablero:true});
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
  return !ocupado&&!subiendo&&!drag&&!$('modalBg').classList.contains('show')&&!(a&&a.matches&&a.matches('input[data-nota]'));
}
document.addEventListener('visibilitychange',()=>{if(!document.hidden&&libre())cargar();});
setInterval(()=>{if(!document.hidden&&libre())cargar();},60000);

// ============================================================
// Exportar tablero: todas las obras a un CSV (se abre con Excel)
// ============================================================
// Sale del mismo dato que dibuja el tablero, así que no pide nada nuevo al server.
// Separador ";" y BOM: es lo que Excel en español abre bien, con acentos y columnas.
function exportarCSV(){
  if(!CARDS.length){toast('No hay obras para exportar.');return;}
  const dia=iso=>iso?iso.slice(8,10)+'/'+iso.slice(5,7)+'/'+iso.slice(0,4):'';
  const orden=ETAPAS.map(e=>e.id);
  const filas=CARDS.slice().sort((a,b)=>(orden.indexOf(a.etapa)-orden.indexOf(b.etapa))||a.key.localeCompare(b.key,undefined,{numeric:true}));
  const cols=[
    ['N°',c=>c.manual?(c.nro_contrato||''):c.cliente_nro+'-'+c.sub], // N° de presupuesto; la ficha a mano, el de su contrato si tiene
    ['Cliente',c=>c.nombre],
    ['Teléfono',c=>c.celular],
    ['Dirección',c=>c.direccion],
    ['Zona',c=>c.zona],
    ['Material',c=>c.material],
    ['Color reconocido',c=>{const k=colorDe(c.material);return k?k.c.n+' ('+(k.marca?k.c.marca_label:'marca sin definir')+')':'';}],
    ['Trabajo',c=>c.concepto],
    ['Etapa',c=>etapaDe(c.etapa).titulo],
    ['Turno (qué)',c=>etapaDe(c.etapa).fecha||''],
    ['Turno (día)',c=>dia(c.fecha)],
    ['Turno (hora)',c=>c.hora],
    ['Atrasada',c=>atrasada(c)?'SI':''],
    ['Medido el',c=>dia(c.medido_at)],
    ['Plazo de entrega',c=>dia(c.limite)],
    ['Estado del plazo',c=>({vencida:'VENCIDO',cerca:'vence en 6 días o menos'})[plazo(c)]||''],
    ['Seña',c=>c.sena?plataSena(c.sena):''],
    ['Bacha',c=>c.bacha?BACHA[c.bacha]:''],
    ['Modelo de bacha',c=>c.bacha_modelo],
    ['Tenemos la bacha',c=>c.bacha==='no'?'no lleva':(c.checks.bacha?'SI':'')],
    ['Cómo sube',c=>c.acceso?textoAcceso(c.acceso):''],
    ['Mide',c=>c.medidor.join(', ')],
    ['Corta',c=>c.cortador.join(', ')],
    ['Fabrica',c=>c.fabrica.join(', ')],
    ['Instala',c=>c.equipo.join(', ')],
    ['Falta en esta etapa',c=>faltanDe(c,etapaDe(c.etapa)).join(', ')],
    ['Fotos de bacha',c=>c.adjuntos.filter(a=>a.tipo==='bacha').length||''],
    ['Planos',c=>c.adjuntos.filter(a=>a.tipo==='plano').length||''],
    ['Fotos de entrega',c=>c.adjuntos.filter(a=>a.tipo==='entrega').length||''],
    ['Links de Drive',c=>c.adjuntos.filter(a=>a.link).map(a=>ADJ[a.tipo]+': '+a.link).join(' | ')],
    ['Nota',c=>c.nota],
    ['Ficha (número interno)',c=>c.key],
  ];
  const celda=v=>{
    let t=String(v==null?'':v).replace(/\r?\n/g,' ').trim();
    if(/^[=+\-@]/.test(t))t="'"+t; // que Excel no lo tome como fórmula
    return /[";]/.test(t)?'"'+t.replace(/"/g,'""')+'"':t;
  };
  const csv='\uFEFF'+[cols.map(k=>celda(k[0])).join(';')].concat(filas.map(c=>cols.map(k=>celda(k[1](c))).join(';'))).join('\r\n')+'\r\n';
  const a=document.createElement('a');
  a.href=URL.createObjectURL(new Blob([csv],{type:'text/csv;charset=utf-8'}));
  a.download='tablero-blackstones-'+HOY+'.csv';
  document.body.appendChild(a);a.click();a.remove();
  setTimeout(()=>URL.revokeObjectURL(a.href),4000);
  toast('Exportadas '+filas.length+(filas.length===1?' obra':' obras')+' · '+a.download,true);
}
$('exportB').addEventListener('click',exportarCSV);

// En pantallas angostas la barra de páginas se desliza de costado: la página actual queda a la vista.
(function(){
  const a=document.querySelector('.h-tabs a.active'),n=a&&a.parentElement;
  if(n)n.scrollLeft=Math.max(0,a.offsetLeft-(n.clientWidth-a.offsetWidth)/2);
})();

$('vistaB').addEventListener('click',()=>{
  vistaTodas=!vistaTodas;
  VISTA={};
  try{localStorage.setItem('bs-tablero-vista',vistaTodas?'completas':'plegadas');}catch(e){}
  dibujar();
});

// Llegando desde el calendario ("Ver la ficha en el tablero"): ?q=0042-1 abre con esa obra buscada.
(function(){
  const q=new URLSearchParams(location.search).get('q');
  if(q){$('buscar').value=q;$('buscar').dispatchEvent(new Event('input'));}
})();

cargar();
cargarCatalogo();
</script>
</body>
</html>
