<?php
// ============================================================
// /presupuestos/tablero.php — Tablero de obras
// ============================================================
// Las obras entran solas: una tarjeta por cada presupuesto "aprobado".
// Cinco columnas + cuadro de retiro en local. La tarjeta se arrastra de
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

/* === BUSCADOR === */
.buscar{display:flex;align-items:center;gap:10px;margin-bottom:14px;position:relative}
.buscar input{width:100%;max-width:380px;padding:10px 38px 10px 14px;border:1px solid var(--border);border-radius:999px;background:var(--card);color:var(--text);font-family:inherit;font-size:14px;outline:none;-webkit-appearance:none;appearance:none}
.buscar input::-webkit-search-cancel-button{display:none}
.buscar input:focus{border-color:var(--gd);box-shadow:0 0 0 3px var(--gd-soft)}
.buscar input.activo{border-color:var(--gd);background:var(--gd-soft);font-weight:600}
.buscar button{margin-left:-46px;width:30px;height:30px;border:none;border-radius:50%;background:none;font-size:20px;line-height:1;color:var(--text3);cursor:pointer;flex-shrink:0}
.buscar button:hover{color:var(--text)}
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
.board{display:grid;grid-template-columns:repeat(var(--ncols,5),minmax(236px,1fr));gap:12px;align-items:start;overflow-x:auto;padding-bottom:4px}
.col{background:var(--card-alt);border:1px solid var(--border);border-top:4px solid var(--et);border-radius:var(--radius);padding:10px;min-height:120px;transition:background .15s,opacity .15s}
.col-h{display:flex;align-items:center;justify-content:space-between;padding:4px 6px 10px}
.col-h h2{font-family:'Fraunces',serif;font-size:17px;font-weight:600;display:flex;align-items:center;gap:8px}
.col-h h2::before{content:'';width:10px;height:10px;border-radius:50%;background:var(--et);flex-shrink:0}
.col-n{font-size:12px;font-weight:700;color:var(--et-on);background:var(--et);border-radius:999px;min-width:26px;padding:2px 8px;text-align:center;font-variant-numeric:tabular-nums}
.col-sub{font-size:11.5px;color:var(--text3);padding:0 6px 8px;margin-top:-6px}
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
.card{background:var(--card);border:1px solid var(--border);border-left:4px solid var(--et);border-radius:var(--radius);padding:12px;margin-bottom:10px;box-shadow:var(--shadow-sm);cursor:grab;user-select:none;-webkit-user-select:none;-webkit-touch-callout:none}
.card:hover{border-color:var(--et)}
.card.alerta{box-shadow:0 0 0 1.5px var(--red)}
/* el hueco que deja la tarjeta mientras viaja */
.card.arrastrando{background:var(--et-soft);border:2px dashed var(--et);box-shadow:none}
.card.arrastrando>*{visibility:hidden}
/* la tarjeta en el aire: toma el color de la etapa sobre la que está */
.fantasma{position:fixed;left:0;top:0;z-index:800;pointer-events:none;margin:0;border:2px solid var(--et);box-shadow:var(--shadow-lg);cursor:grabbing;transition:border-color .12s}
.fantasma .f-aviso{display:none;margin:-12px -12px 10px -12px;padding:7px 12px;border-radius:7px 7px 0 0;background:var(--et);color:var(--et-on);font-size:12.5px;font-weight:700}
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
.card.primera{outline:2px solid var(--et);outline-offset:1px}
.card.primera .c-pos{background:var(--et);color:var(--et-on)}
.c-prox{margin:-12px -12px 10px -12px;padding:5px 12px;border-radius:9px 9px 0 0;background:var(--et-soft);color:var(--et);font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
.fantasma .c-prox{display:none}
.c-id b{font-weight:700;font-variant-numeric:tabular-nums}
.c-id .zona{color:var(--text3);font-weight:500}
.c-mat{color:var(--text2);font-size:13px;margin-top:2px;overflow-wrap:anywhere}

.c-fecha{display:inline-flex;align-items:center;gap:6px;margin-top:9px;padding:6px 10px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--card);font-size:12.5px;font-weight:600;color:var(--text2);cursor:pointer}
.c-fecha:hover{border-color:var(--gd)}
.c-fecha.vacia{border-style:dashed;color:var(--gdd);background:var(--gd-soft)}
[data-theme="dark"] .c-fecha.vacia{color:var(--gd)}
.c-fecha.hoy{background:var(--gd);border-color:var(--gd);color:#1A1816}
.c-fecha.atrasada{background:var(--red-soft);border-color:var(--red);color:var(--red)}
/* MEDIDO: botón para marcar la medición hecha, y cómo queda después */
.c-turno{display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin-top:9px}
.c-turno .c-fecha{margin-top:0}
.c-hecho{display:block;width:100%;margin-top:10px;padding:12px;border-radius:var(--radius-sm);border:2px solid var(--et);background:var(--et);color:var(--et-on);font-size:15px;font-weight:800;letter-spacing:.01em;text-align:center;cursor:pointer;box-shadow:var(--shadow-sm)}
.c-hecho:hover{filter:brightness(1.08)}
.c-hecho.listo{background:var(--gr);border-color:var(--gr);color:#fff}
[data-theme="dark"] .c-hecho.listo{color:#04220F}
.c-hecho.listo::before{content:'✓  '}
.col-sep{margin:14px 2px 10px;padding-top:10px;border-top:2px dashed var(--border);font-size:11.5px;font-weight:700;color:var(--text3);letter-spacing:.02em}
/* AVISO ámbar: a la obra le falta el turno que su etapa exige (Por instalar sin fecha u hora) */
.c-fecha.aviso{background:var(--amb-soft);border:1.5px solid var(--amb);color:var(--amb);font-weight:700}
.c-fecha.aviso::before,.col-aviso::before{content:'!';display:inline-flex;align-items:center;justify-content:center;width:17px;height:17px;border-radius:50%;background:var(--amb);color:var(--card);font-size:12px;font-weight:800;line-height:1;flex-shrink:0}
.card.sin-turno{box-shadow:0 0 0 1.5px var(--amb)}
.col-aviso{display:inline-flex;align-items:center;gap:5px;margin-left:auto;margin-right:8px;padding:2px 9px 2px 3px;border-radius:999px;background:var(--amb-soft);color:var(--amb);font-size:11.5px;font-weight:700;white-space:nowrap}

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

/* el botón lleva el color de la etapa a la que manda */
.c-avanzar{display:block;width:100%;margin-top:10px;padding:10px 12px;border:1px solid var(--et);border-radius:var(--radius-sm);background:var(--et);color:var(--et-on);font-size:13.5px;font-weight:700;cursor:pointer}
.c-avanzar:hover{filter:brightness(1.08)}
.c-avanzar.falta{background:none;color:var(--text3);border:1px dashed var(--border);font-weight:600}
.c-avanzar.falta:hover{filter:none;color:var(--et);border-color:var(--et)}

.c-mas{display:block;margin:8px auto 0;padding:4px 10px;border:none;background:none;font-size:12px;font-weight:600;color:var(--text3);cursor:pointer}
.c-mas:hover{color:var(--text)}
.c-det{margin-top:8px;padding-top:10px;border-top:1px solid var(--border-soft);font-size:13px;display:grid;gap:7px;cursor:auto;user-select:text;-webkit-user-select:text}
.c-det .fila{display:flex;gap:8px}
.c-det .fila span{color:var(--text3);flex:0 0 74px}
/* Hitos: lo ya hecho en etapas anteriores, con su fecha, a la vista en la ficha */
.c-hitos{display:flex;flex-wrap:wrap;gap:4px 6px;margin-top:7px}
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
.modal{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-lg);padding:20px;max-width:420px;width:100%}
.modal h3{font-family:'Fraunces',serif;font-size:19px;font-weight:600;margin-bottom:8px}
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
.modal .btns{display:flex;gap:10px;justify-content:flex-end;margin-top:14px}
.modal .btns button{padding:9px 16px;border-radius:var(--radius-sm);font-size:13.5px;font-weight:700;cursor:pointer;border:1px solid var(--border);background:var(--card)}
.modal .btns .ok{background:var(--dk);color:var(--cr);border-color:var(--dk)}
[data-theme="dark"] .modal .btns .ok{background:var(--gd);color:#1A1816;border-color:var(--gd)}
.modal .btns .ok.peligro,[data-theme="dark"] .modal .btns .ok.peligro{background:var(--red);border-color:var(--red);color:#fff}
.c-det .lnk.rojo{color:var(--red)}
.toast{position:fixed;left:50%;bottom:24px;transform:translate(-50%,20px);background:var(--dk);color:var(--cr);padding:11px 16px;border-radius:var(--radius-sm);border-left:3px solid var(--red);box-shadow:var(--shadow-lg);font-size:13px;font-weight:600;opacity:0;pointer-events:none;transition:opacity .2s,transform .2s;z-index:1000;max-width:calc(100vw - 32px)}
.toast.show{opacity:1;transform:translate(-50%,0)}
.toast.ok{border-left-color:var(--gr)}

/* === CARGAR OBRA A MANO (botón de la columna Por medir) === */
.col-cargar{display:block;width:100%;margin-bottom:10px;padding:10px;border:1.5px dashed var(--et);border-radius:var(--radius);background:var(--card);color:var(--et);font-size:13.5px;font-weight:700;cursor:pointer}
.col-cargar:hover{background:var(--et-soft)}
.modal .campo{display:block;margin-bottom:10px;font-size:12px;font-weight:700;color:var(--text2)}
.modal .campo input{display:block;width:100%;margin-top:4px;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--card);color:var(--text);font-family:inherit;font-size:15px;font-weight:500;outline:none}
.modal .campo input:focus{border-color:var(--gd);box-shadow:0 0 0 3px var(--gd-soft)}

/* Filtros de Por medir: fichas sin plano / sin foto de bacha */
.col-filtros{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px}
.col-filtros button{padding:6px 10px;border:1px solid var(--border);border-radius:999px;background:var(--card);font-size:12px;font-weight:700;color:var(--text2);cursor:pointer}
.col-filtros button i{font-style:normal;opacity:.7;font-variant-numeric:tabular-nums}
.col-filtros button.on{background:var(--et);border-color:var(--et);color:var(--et-on)}

/* === ADJUNTOS: foto de bacha y plano === */
.c-adj{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px}
.adj{position:relative;width:60px;height:60px;border-radius:8px;border:1px solid var(--border);background:var(--card-alt);padding:0;overflow:hidden;cursor:pointer;flex-shrink:0}
.adj:hover{border-color:var(--et)}
.adj img{width:100%;height:100%;object-fit:cover;display:block}
.adj span{position:absolute;left:0;right:0;bottom:0;background:rgba(20,17,13,.74);color:#fff;font-size:9.5px;font-weight:700;line-height:1.6;text-align:center;letter-spacing:.02em}
.adj.pdf{font-size:13px;font-weight:800;color:var(--text2);padding-bottom:14px}
.adj.mas{border:1.5px dashed var(--et);background:var(--et-soft);color:var(--et);font-size:10.5px;font-weight:700;line-height:1.25;padding:3px}
.visor img{display:block;max-width:100%;max-height:60vh;margin:0 auto 12px;border-radius:6px;background:var(--card-alt)}
.visor .acciones{display:flex;justify-content:space-between;align-items:center;gap:12px;font-size:13.5px}
.visor a{color:var(--gdd);font-weight:700}
[data-theme="dark"] .visor a{color:var(--gd)}
.visor .borrar{border:none;background:none;padding:0;font-size:13.5px;font-weight:700;color:var(--red);cursor:pointer;text-decoration:underline}
.modal .adjuntar{display:block;width:100%;margin-top:12px;padding:11px;border:1.5px dashed var(--border);border-radius:var(--radius-sm);background:none;font-size:13.5px;font-weight:700;color:var(--text2);cursor:pointer}
.modal .adjuntar:hover{border-color:var(--gd);color:var(--text)}

/* === MÓVIL: una columna a la vez, con pestañas === */
@media(max-width:900px){
  .header{padding:12px 14px}
  .header .h-right > span{display:none}
  .h-tabs{padding:0 8px}
  .h-tabs a{padding:9px 12px;font-size:.62rem;letter-spacing:.06em}
  .wrap{padding:0 12px 60px}
  .buscar{margin:10px 0 0}
  .buscar input{max-width:none;font-size:16px}
  .col-tabs{display:flex;gap:4px;position:sticky;top:0;z-index:50;background:var(--bg);padding:10px 0;margin-bottom:2px}
  .col-tabs button{flex:1 1 0;min-width:0;padding:7px 2px 6px;border-radius:var(--radius-sm);border:1px solid var(--border);background:var(--card);font-size:10.5px;letter-spacing:-.01em;font-weight:700;color:var(--text2);cursor:pointer;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.25}
  .col-tabs button{border-bottom:3px solid var(--et)}
  .col-tabs button.on{background:var(--et);color:var(--et-on);border-color:var(--et)}
  .col-tabs button.con-aviso::after{content:'!';position:absolute;top:-5px;right:-3px;width:16px;height:16px;border-radius:50%;background:var(--amb);color:var(--card);font-size:11px;font-weight:800;line-height:16px;text-align:center}
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
  .board{grid-template-columns:1fr;overflow-x:visible;padding-bottom:0}
  .col{display:none;background:none;border:none;padding:0}
  .col.on{display:block}
  .col-h{display:none}
  .chk{padding:10px 13px 10px 10px;font-size:13px}
  .c-hecho{padding:14px 12px;font-size:16px}
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
  <div class="buscar">
    <input type="search" id="buscar" placeholder="Buscar cliente, N° o material" autocomplete="off" enterkeyhint="search" aria-label="Buscar obra">
    <button type="button" id="buscarX" aria-label="Borrar búsqueda" hidden>×</button>
    <span id="buscarN"></span>
  </div>
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
const API_ADJ='/calculadora/api/tablero-adjunto.php';
const API_CARGAR='/calculadora/api/tablero-cargar.php';
const API_BORRAR='/calculadora/api/delete.php';
const ADJ={bacha:'Bacha',plano:'Plano'}; // tipos de adjunto

let ETAPAS=[];      // definición de etapas (viene del server)
let INSTALADORES=[];// quiénes instalan (viene del server)
let FABRICADORES=[];// quiénes fabrican (viene del server)
let CORTADORES=[];  // quiénes cortan (viene del server)
let MEDIDORES=[];   // quiénes miden (viene del server)
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
let colMovil=localStorage.getItem('bs-tablero-col')||'medir';
let ocupado=false;  // hay un guardado en curso
let fechaKey='';    // tarjeta a la que se le está eligiendo fecha
let drag=null;      // arrastre en curso
let buscado=[];     // palabras del buscador, ya sin mayúsculas ni acentos
const falta={}; // filtros "le falta…" encendidos: plano, foto de bacha y fecha (Por medir), turno (Por instalar)
let recien=null;    // última tarjeta movida (o rebotada), para animarla: {key,tipo,t}
const slot=document.createElement('div'); // "Soltar acá", viaja de columna en columna

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
    ETAPAS=j.etapas;CARDS=j.cards;HOY=j.hoy;INSTALADORES=j.instaladores||[];FABRICADORES=j.fabricadores||[];CORTADORES=j.cortadores||[];MEDIDORES=j.medidores||[];
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
function atrasada(c){ // la fecha pasó y no se cumplió (o la tarjeta sigue en la columna)
  const e=etapaDe(c.etapa);
  return !!(e.fecha&&c.fecha&&c.fecha<HOY&&!(e.cierra&&c.checks[e.cierra]));
}
// Qué falta para llevar la tarjeta a esa etapa (misma regla que el server):
// hacia atrás o hacia Retiro, nada; hacia adelante, los tildes de todo lo de atrás.
function faltaPara(c,destino){
  const cols=columnas();
  const i=cols.findIndex(e=>e.id===c.etapa),j=cols.findIndex(e=>e.id===destino);
  if(j<0||(i>=0&&j<i))return [];
  let f=[];
  // desde la etapa en la que está (o desde el principio, si viene de Retiro) hasta la anterior al destino
  for(let n=Math.max(i,0);n<j;n++)f=f.concat(faltanDe(c,cols[n]));
  return f;
}

const urlAdj=(c,a)=>API_ADJ+'?nro='+encodeURIComponent(c.cliente_nro)+'&sub='+c.sub+'&id='+a.id;
function htmlAdjuntos(c,puedeSumar){
  let h='';
  c.adjuntos.forEach(a=>{
    h+='<button type="button" class="adj'+(a.pdf?' pdf':'')+'" data-act="ver" data-id="'+esc(a.id)+'" title="'+ADJ[a.tipo]+'">'+
      (a.pdf?'PDF':'<img loading="lazy" alt="" src="'+esc(urlAdj(c,a))+'&t=1">')+'<span>'+ADJ[a.tipo]+'</span></button>';
  });
  if(puedeSumar){
    h+='<button type="button" class="adj mas" data-act="adjuntar" data-tipo="bacha">+ Foto bacha</button>';
    h+='<button type="button" class="adj mas" data-act="adjuntar" data-tipo="plano">+ Plano</button>';
  }
  return h?'<div class="c-adj">'+h+'</div>':'';
}

// Buscador: sin mayúsculas ni acentos, y tienen que estar todas las palabras.
const plano=t=>String(t==null?'':t).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
function coincide(c){
  if(!buscado.length)return true;
  if(!c._txt)c._txt=plano([c.cliente_nro+'-'+c.sub,c.nombre,c.material,c.concepto,c.zona,c.direccion,c.celular,String(c.celular||'').replace(/\D/g,''),c.nota,c.equipo.join(' '),c.fabrica.join(' '),c.medidor.join(' '),c.cortador.join(' ')].join(' '));
  return buscado.every(w=>c._txt.indexOf(w)>=0);
}

// ¿Le falta ese adjunto? La foto de bacha no se pide si la obra no lleva bacha.
function sinAdjunto(c,tipo){
  if(tipo==='bacha'&&c.bacha==='no')return false;
  return !c.adjuntos.some(a=>a.tipo===tipo);
}

// Orden de la cola dentro de una etapa.
function ordenCola(a,b){
  const ha=hecho(a),hb=hecho(b);
  if(ha!==hb)return ha?1:-1;                             // lo que falta hacer, arriba
  if(ha&&a.boton_at!==b.boton_at)return a.boton_at<b.boton_at?-1:1; // ya hechas: la más vieja primero
  if(!!a.fecha!==!!b.fecha)return a.fecha?-1:1;          // con turno primero
  if(a.fecha!==b.fecha)return a.fecha<b.fecha?-1:1;      // el turno más próximo arriba
  if(a.hora!==b.hora){                                   // mismo día: el más temprano; sin hora, al final
    if(!a.hora)return 1;
    if(!b.hora)return -1;
    return a.hora<b.hora?-1:1;
  }
  if(a.desde!==b.desde)return a.desde<b.desde?-1:1;      // después, la cola: el que entró antes
  return a.key<b.key?-1:1;
}
// Lugar de cada ficha en la cola de su etapa (1 = la próxima a atender). Se cuenta
// sobre TODAS las fichas de la etapa, así el número no cambia al buscar o filtrar.
// Las ya hechas (ya medidas, ya cortadas) no llevan número: esperan el control.
let POS={};
function numerarColas(){
  POS={};
  ETAPAS.forEach(e=>{
    CARDS.filter(c=>c.etapa===e.id&&!hecho(c)).sort(ordenCola).forEach((c,i)=>{POS[c.key]=i+1;});
  });
}

function htmlCard(c){
  const e=etapaDe(c.etapa);
  const cols=columnas();
  const idx=cols.indexOf(e);
  const sig=idx>=0?cols[idx+1]:null;
  const pos=POS[c.key]||0;
  let cls='card'+(atrasada(c)?' alerta':(sinTurno(c)?' sin-turno':''))+(pos===1?' primera':''),estilo='';
  if(recien&&recien.key===c.key){
    const dt=Math.max(0,Date.now()-recien.t); // si se redibuja en el medio, la animación sigue donde iba
    if(dt<1000){cls+=' '+recien.tipo;estilo=' style="animation-delay:-'+dt+'ms"';}
  }
  let h='<article class="'+cls+'"'+estilo+' data-key="'+esc(c.key)+'">';

  if(pos===1)h+='<div class="c-prox">Próxima</div>';
  h+='<div class="c-id">'+(pos?'<span class="c-pos" title="Lugar en la cola">'+pos+'</span>':'')+'<b>'+esc(c.cliente_nro)+'-'+c.sub+'</b> · '+esc(nombreCorto(c.nombre)||'Sin nombre');
  if(c.zona)h+=' <span class="zona">· '+esc(c.zona)+'</span>';
  h+='</div>';
  const mat=[c.material,c.concepto].filter(Boolean).join(' · ');
  if(mat)h+='<div class="c-mat">'+esc(mat)+'</div>';

  // El botón de la etapa (Medido / Cortado) es lo que más se ve en la tarjeta:
  // dice de un vistazo si ese trabajo ya se hizo o no. Tocarlo otra vez lo deshace.
  if(e.boton){
    h+=hecho(c)
      ?'<button type="button" class="c-hecho listo" data-act="check" data-clave="'+esc(e.boton)+'" title="Tocar para desmarcar">'+esc(e.boton_listo)+(c.boton_at?' · '+esc(fechaCorta(c.boton_at)):'')+'</button>'
      :'<button type="button" class="c-hecho" data-act="check" data-clave="'+esc(e.boton)+'">'+esc(e.boton_txt)+'</button>';
  }
  if(e.fecha&&!hecho(c)){
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
  // Semana estimada de entrega: guía, no alarma.
  if(c.semana&&c.etapa!=='archivar'){
    const esta=lunesDe(HOY);
    const cls=c.semana<esta?' vencida':(c.semana===esta?' cerca':'');
    const txt=c.semana===esta?'esta semana':'semana del '+ddmm(c.semana)+(c.semana<esta?' · ya pasó':'');
    h+='<div class="c-promesa'+cls+'">Entrega estimada: '+esc(txt)+'</div>';
  }
  // Datos que se cargan al medir y quedan a la vista para el taller y el que instala.
  if(c.etapa!=='medir'&&c.etapa!=='archivar'){
    const datos=[];
    if(c.bacha)datos.push(c.bacha==='no'?'Sin bacha':'Bacha '+BACHA[c.bacha]);
    if(c.acceso)datos.push('Sube '+textoAcceso(c.acceso));
    if(c.medidor.length)datos.push('Midió: '+c.medidor.join(', '));
    if(c.cortador.length&&!e.checks.some(k=>k.id==='cortador'))datos.push('Cortó: '+c.cortador.join(', '));
    if(c.fabrica.length&&!e.checks.some(k=>k.id==='fabrica'))datos.push('Fabricó: '+c.fabrica.join(', '));
    if(c.equipo.length&&!e.checks.some(k=>k.id==='equipo'))datos.push('Instalan: '+c.equipo.join(', '));
    if(datos.length)h+='<div class="c-promesa">'+esc(datos.join(' · '))+'</div>';
    // Lo que ya pasó, con su fecha: se ve acá sin tener que volver la ficha atrás.
    if(c.hitos.length)h+='<div class="c-hitos">'+c.hitos.map(x=>'<span>'+esc(x.txt)+(x.fecha?' <b>'+esc(ddmm(x.fecha))+'</b>':'')+'</span>').join('')+'</div>';
  }
  if(c.nota)h+='<div class="c-nota">'+esc(c.nota)+'</div>';
  // Foto de bacha y plano: se suman en Por medir (o desde "Más") y quedan a la vista después.
  h+=htmlAdjuntos(c,c.etapa==='medir'||abierta===c.key);

  h+='<div class="c-checks">';
  e.checks.forEach(k=>{
    if(k.id===e.boton)return; // ese tilde es el botón de arriba
    const on=!!c.checks[k.id];
    let label=k.label;
    if(k.id==='sena'&&on&&c.sena)label+=' · '+plataSena(c.sena);
    if(k.id==='acceso'&&on&&c.acceso)label+=' · '+textoAcceso(c.acceso);
    if(k.id==='bacha'&&on&&c.bacha)label=c.bacha==='no'?'No lleva bacha':'Tenemos la bacha · '+BACHA[c.bacha];
    if(PERSONAS[k.id]&&on&&c[PERSONAS[k.id].campo].length)label=PERSONAS[k.id].rotulo+c[PERSONAS[k.id].campo].join(', ');
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
      h+='<button type="button" class="lnk" data-act="retiro">Retira en el local</button>';
    }
    h+='<button type="button" class="lnk rojo" data-act="borrar">Borrar ficha</button>';
    h+='</div></div>';
  }
  return h+'</article>';
}

function dibujar(){
  if(!ETAPAS.length)return;
  if(!etapaDe(colMovil))colMovil=ETAPAS[0].id;

  numerarColas();
  const visibles=CARDS.filter(coincide);
  // Buscando en celular: si la pestaña abierta no tiene resultados, salta a la primera que sí.
  if(buscado.length&&visibles.length&&!visibles.some(c=>c.etapa===colMovil))colMovil=ETAPAS.find(e=>visibles.some(c=>c.etapa===e.id)).id;
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
    if(e.boton)filtros.push(['pend_'+e.id,e.pendientes,c=>!hecho(c)],['cont_'+e.id,'Necesitan continuar',hecho]);
    if(esPrimera)filtros.push(['plano','Sin plano',c=>sinAdjunto(c,'plano')],['bacha','Sin foto de bacha',c=>sinAdjunto(c,'bacha')]);
    // cada columna tiene su propio filtro de fecha: Por medir "Sin fecha", Por instalar "Sin turno"
    if(e.exige_fecha||e.avisa_fecha)filtros.push(['turno_'+e.id,e.exige_fecha?'Sin turno':'Sin fecha',sinTurno]);
    const lista=deEtapa.filter(c=>filtros.every(f=>!falta[f[0]]||f[2](c))).sort(ordenCola);
    const on=e.id===colMovil;
    const corto=e.aparte?'Retiro':e.titulo.replace('Por ','').replace(/^./,x=>x.toUpperCase());
    const llega=recien&&recien.tipo==='llego'&&Date.now()-recien.t<300&&lista.some(c=>c.key===recien.key);
    const sinT=lista.filter(sinTurno).length;
    tabs+='<button type="button" class="et-'+esc(e.id)+(on?' on':'')+(llega?' llego':'')+(sinT?' con-aviso':'')+'" data-col="'+esc(e.id)+'">'+esc(corto)+'<i>'+lista.length+'</i></button>';
    let h='<section class="col et-'+esc(e.id)+(on?' on':'')+'" data-col="'+esc(e.id)+'">';
    h+='<div class="col-h"><h2>'+esc(e.titulo)+'</h2>'+(sinT?'<span class="col-aviso">'+sinT+(e.exige_fecha?' sin turno':' sin fecha')+'</span>':'')+'<span class="col-n">'+lista.length+'</span></div>';
    if(!e.aparte&&!e.fecha&&columnas().indexOf(e)<ultima)h+='<div class="col-sub">En orden de llegada</div>';
    if(esPrimera)h+='<button type="button" class="col-cargar" data-cargar>+ Cargar obra</button>';
    if(filtros.length)h+='<div class="col-filtros">'+filtros.map(f=>'<button type="button" class="'+(falta[f[0]]?'on':'')+'" data-falta="'+f[0]+'" aria-pressed="'+!!falta[f[0]]+'">'+f[1]+' <i>'+deEtapa.filter(f[2]).length+'</i></button>').join('')+'</div>';
    // Separador entre lo que falta hacer y lo ya hecho (Por medir: "Ya medidas").
    // El separador va antes del bloque final de fichas ya hechas.
    let corte=lista.length;
    while(e.boton&&corte>0&&hecho(lista[corte-1]))corte--;
    const nHechas=lista.length-corte;
    const cards=lista.map((c,i)=>(nHechas&&i===corte?'<div class="col-sep">'+esc(e.hecho)+' ('+nHechas+')</div>':'')+htmlCard(c)).join('');
    if(e.aparte)h+=lista.length?'<div class="cards">'+cards+'</div>':'<div class="col-vacia">'+(buscado.length?'Sin resultados':'Nadie retira por el local')+'</div>';
    else h+=lista.length?cards:'<div class="col-vacia">'+(buscado.length?'Sin resultados':CARDS.length?'Nada acá':(columnas().indexOf(e)===0?'Las obras aparecen solas acá cuando marcás un presupuesto como Aprobado.':''))+'</div>';
    h+='</section>';
    if(e.aparte)aparte+=h;else cols+=h;
  });
  $('board').style.setProperty('--ncols',columnas().length);
  $('colTabs').innerHTML=tabs;
  $('board').innerHTML=cols;
  $('aparte').innerHTML=aparte;
}

// Guarda un cambio: primero lo aplica en pantalla, después lo confirma el server.
async function guardar(c,body,aplicar){
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

$('colTabs').addEventListener('click',ev=>{
  const b=ev.target.closest('button[data-col]');
  if(!b)return;
  colMovil=b.dataset.col;
  localStorage.setItem('bs-tablero-col',colMovil);
  dibujar();
  window.scrollTo({top:0});
});

wrap.addEventListener('click',ev=>{
  if(ev.target.closest('[data-cargar]')){pedirCargar();return;}
  const bf=ev.target.closest('[data-falta]');
  if(bf){
    const k=bf.dataset.falta,par=k.indexOf('pend_')===0?k.replace('pend_','cont_'):(k.indexOf('cont_')===0?k.replace('cont_','pend_'):'');
    falta[k]=!falta[k];
    if(falta[k]&&par)falta[par]=false;
    dibujar();
    return;
  }
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
    if(clave==='acceso'&&valor){pedirAcceso(c);return;}
    if(clave==='bacha'&&valor){pedirBacha(c);return;}
    if(PERSONAS[clave]){pedirPersonas(c,clave);return;} // tildado o no, abre la lista para elegir o cambiar
    guardar(c,{accion:'check',clave:clave,valor:valor},()=>{
      if(valor)c.checks[clave]=true;else delete c.checks[clave];
      if(clave==='sena')c.sena=null;
      if(clave==='acceso')c.acceso=null;
      if(clave==='bacha')c.bacha='';
      // Medido / Cortado: los botones de etapa guardan el día. No dependen de ningún otro tilde.
      if(clave==='medido'){c.medido_at=valor?((c.fecha&&c.fecha<=HOY)?c.fecha:HOY):'';c.boton_at=c.medido_at;}
      if(clave==='cortado'||clave==='fabricado')c.boton_at=valor?HOY:'';
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
  }else if(act==='borrar'){
    pedirBorrar(c);
  }else if(act==='adjuntar'){
    elegirArchivo(c,b.dataset.tipo);
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
  d.fantasma.remove();
  slot.remove();
  d.art.classList.remove('arrastrando');
  document.body.classList.remove('arrastre');
  document.querySelectorAll('.col[data-col],#colTabs button[data-col]').forEach(z=>z.classList.remove('puede','bloq','origen','drop'));
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
  $('mCancelar').style.display='';
  $('mOk').classList.remove('peligro');
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
  else if(ev.key==='Enter'&&modalOk&&$('modalBg').classList.contains('show')&&!$('mOk').classList.contains('peligro')){ev.preventDefault();$('mOk').click();} // (borrar nunca se confirma con Enter)
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

// Piso y ascensor: un toque para decir cómo sube la pieza.
function pedirAcceso(c){
  let modo='';
  abrirModal('Piso y ascensor · '+c.cliente_nro+'-'+c.sub,
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
  abrirModal(e.fecha+' · '+c.cliente_nro+'-'+c.sub,
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

// Cargar una obra a mano: para las que no pasaron por la calculadora.
function pedirCargar(){
  const campo=(id,rotulo,extra)=>'<label class="campo">'+rotulo+'<input type="text" id="'+id+'" autocomplete="off" '+(extra||'')+'></label>';
  abrirModal('Cargar obra',
    campo('cgNro','N° de presupuesto (si ya está hecho en la calculadora)','maxlength="12" inputmode="numeric" placeholder="Ej: 0042 o 0042-2"')+
    '<p style="margin:-4px 0 12px;font-size:12px">Con el número alcanza: la ficha toma los datos de ese presupuesto. Sin número, completá lo de abajo.</p>'+
    campo('cgNombre','Cliente','maxlength="120" placeholder="Nombre y apellido"')+
    campo('cgCel','Teléfono','maxlength="40" inputmode="tel"')+
    campo('cgDir','Dirección','maxlength="200"')+
    campo('cgMat','Material y color','maxlength="200" placeholder="Ej: Blanco Suggar"')+
    campo('cgCon','Trabajo','maxlength="200" placeholder="Ej: Cocina en L"'),
    'Cargar obra',
    async()=>{
      const nombre=$('cgNombre').value.trim(),nro=$('cgNro').value.trim();
      if(!nro&&!nombre){$('cgNombre').focus();toast('Poné el N° de presupuesto o el nombre del cliente.');return;}
      if(ocupado)return;
      ocupado=true;
      const datos={nro:nro,nombre:nombre,celular:$('cgCel').value,direccion:$('cgDir').value,material:$('cgMat').value,concepto:$('cgCon').value};
      cerrarModal();
      try{
        const j=await pedir(API_CARGAR,datos);
        recien={key:j.cliente_nro+'-'+j.sub,tipo:'llego',t:Date.now()}; // late al aparecer
        toast('Obra '+j.cliente_nro+'-'+j.sub+' cargada en Por medir',true);
      }catch(e){
        toast(e.message);
      }
      ocupado=false;
      await cargar();
    });
  $('cgNro').focus();
}

// Bacha: de quién es.
function pedirBacha(c){
  let origen='';
  abrirModal('Bacha · '+c.cliente_nro+'-'+c.sub,
    '<p>¿De quién es la bacha?</p><div class="sena"><button type="button" data-op="nuestra">Nuestra</button><button type="button" data-op="cliente">Del cliente</button><button type="button" data-op="no">No lleva</button></div>'+
    '<button type="button" class="adjuntar" id="bachaFoto">+ Adjuntar foto de la bacha</button>',
    'Guardar',
    ()=>{
      if(!origen){toast('Elegí de quién es la bacha.');return;}
      cerrarModal();
      guardar(c,{accion:'check',clave:'bacha',valor:true,origen:origen},()=>{c.checks.bacha=true;c.bacha=origen;});
    });
  $('mCuerpo').querySelectorAll('[data-op]').forEach(b=>b.addEventListener('click',()=>{
    origen=b.dataset.op;
    $('mCuerpo').querySelectorAll('[data-op]').forEach(x=>x.classList.toggle('on',x===b));
    $('mOk').focus();
  }));
  $('bachaFoto').addEventListener('click',()=>elegirArchivo(c,'bacha'));
}

// ============================================================
// Adjuntos: foto de bacha y plano
// ============================================================
const fileAdj=document.createElement('input');
fileAdj.type='file';fileAdj.style.display='none';
document.body.appendChild(fileAdj);
let adjPara=null,subiendo=false;

function elegirArchivo(c,tipo){
  if(subiendo)return;
  adjPara={nro:c.cliente_nro,sub:c.sub,tipo:tipo};
  fileAdj.accept=tipo==='bacha'?'image/*':'image/*,application/pdf'; // el plano también puede ser PDF
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
  toast(para.tipo==='bacha'?'Subiendo la foto de la bacha…':'Subiendo el plano…',true);
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
    toast(para.tipo==='bacha'?'Foto de bacha guardada':'Plano guardado',true);
  }catch(e){
    toast(e.message);
  }
  subiendo=false;
  await cargar();
}

function verAdjunto(c,id){
  const a=c.adjuntos.find(x=>x.id===id);
  if(!a)return;
  const url=urlAdj(c,a);
  abrirModal(ADJ[a.tipo]+' · '+c.cliente_nro+'-'+c.sub,
    '<div class="visor">'+(a.pdf?'':'<img src="'+esc(url)+'" alt="">')+
    '<div class="acciones"><a href="'+esc(url)+'" target="_blank" rel="noopener">'+(a.pdf?'Abrir el PDF':'Abrir en grande')+'</a>'+
    '<button type="button" class="borrar" id="adjBorrar">Borrar</button></div></div>',
    'Cerrar',cerrarModal);
  $('mCancelar').style.display='none';
  let seguro=false;
  $('adjBorrar').addEventListener('click',async()=>{
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
  abrirModal(P.titulo+' · '+c.cliente_nro+'-'+c.sub,
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

// Borrar ficha: siempre pide confirmación y dice exactamente qué se pierde.
function pedirBorrar(c){
  const quien=c.cliente_nro+'-'+c.sub+' · '+(nombreCorto(c.nombre)||'Sin nombre');
  const n=c.adjuntos.length;
  abrirModal('¿Borrar la ficha '+c.cliente_nro+'-'+c.sub+'?',
    '<p><b>'+esc(quien)+'</b></p>'+
    '<p>Se borran sus tildes, fechas y datos'+(n?', y '+(n===1?'el archivo adjunto':'los '+n+' archivos adjuntos'):'')+'.</p>'+
    (c.manual
      ?'<p>Es una obra cargada a mano: se borra completa.</p>'
      :'<p>El presupuesto no se borra: vuelve a <b>Enviado</b> en Presupuestos.</p>')+
    '<p class="ojo">No se puede deshacer.</p>',
    'Borrar ficha',
    async()=>{
      if(ocupado)return;
      ocupado=true;
      cerrarModal();
      try{
        // 1) la ficha del tablero (tildes, datos, adjuntos)  2) el presupuesto
        await pedir(API,{cliente_nro:c.cliente_nro,sub:c.sub,accion:'borrar'});
        if(c.manual)await pedir(API_BORRAR,{cliente_nro:c.cliente_nro,sub:c.sub});
        else await pedir(API_ESTADO,{cliente_nro:c.cliente_nro,sub:c.sub,nuevo_estado:'enviado'});
        if(abierta===c.key)abierta='';
        toast('Ficha '+c.cliente_nro+'-'+c.sub+' borrada',true);
      }catch(e){
        toast(e.message);
      }
      ocupado=false;
      await cargar();
    });
  $('mOk').classList.add('peligro');
  $('mCancelar').focus(); // el foco queda en Cancelar: Enter no borra por accidente
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
  return !ocupado&&!subiendo&&!drag&&!$('modalBg').classList.contains('show')&&!(a&&a.matches&&a.matches('input[data-nota]'));
}
document.addEventListener('visibilitychange',()=>{if(!document.hidden&&libre())cargar();});
setInterval(()=>{if(!document.hidden&&libre())cargar();},60000);

cargar();
</script>
</body>
</html>
