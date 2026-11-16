<?php
// ============================================================
// /presupuestos/calendario.php — Calendario de obras
// ============================================================
// La vista de halcón del tablero: las mismas obras, puestas en el mes.
// Muestra mediciones, instalaciones, retiros y el día en que vence el
// plazo de cada obra (y, para lo que todavía no se midió, la entrega
// estimada según el día de medición agendado). Desde acá se agenda y se reagenda (tocando o
// arrastrando), se cambia el plazo de entrega y se edita la ficha
// completa de la obra (la del tablero, embebida en modo "una ficha"). Todo lo que se cambia
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
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
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
@media(hover:hover){.mes button:hover{border-color:var(--gd);color:var(--text)}}
.mes .hoy-b{font-size:13px;margin-left:4px}
/* === FILTROS: casilleros a la izquierda del mes ===
   Arriba, qué ver (mediciones, instalaciones, entregas, vencimientos). Abajo, los bloques de medición
   que existen: marcar uno o más deja en pantalla solo lo de esos bloques, para seguirlos de cerca. */
.filtros{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);padding:14px 12px;position:sticky;top:12px;max-height:calc(100vh - 24px);overflow-y:auto}
.filtros h4{font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--text2);margin:14px 2px 4px}
.filtros h4:first-child{margin-top:0}
.f-tit{display:none}
.ley{display:flex;flex-direction:column}
.ley button{display:flex;align-items:center;gap:9px;width:100%;padding:6px 2px;border:none;background:none;font-size:13px;font-weight:600;color:var(--text2);text-align:left;cursor:pointer;border-radius:var(--radius-sm)}
.ley button::before{content:'';flex-shrink:0;width:16px;height:16px;border-radius:4px;border:1.5px solid var(--text3);background:var(--card);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;line-height:1;color:#fff}
.ley button.on{color:var(--text)}
.ley button.on::before{content:'✓';background:var(--fc,var(--dk));border-color:var(--fc,var(--dk))}
[data-theme="dark"] .ley button.on::before{color:#14110D;background:var(--fc,var(--gd));border-color:var(--fc,var(--gd))}
.ley button b{margin-left:auto;font-weight:700;font-variant-numeric:tabular-nums;color:var(--text3)}
/* Referencias de colores (no se tocan) */
.refs{display:flex;flex-direction:column}
.ref{display:flex;align-items:center;gap:9px;padding:5px 2px;font-size:12.5px;font-weight:600;color:var(--text2)}
.ref::before{content:'';flex-shrink:0;width:14px;height:14px;border-radius:3px;background:var(--et);margin:0 1px}
.ref.entrega::before{height:8px;border-radius:2px;background:color-mix(in srgb,var(--gd) 40%,var(--card));border:1px solid var(--gd)}
.ref.vence::before{background:none;border:1.5px dashed var(--text3)}
.ref b{margin-left:auto;font-weight:700;font-variant-numeric:tabular-nums;color:var(--text3)}
.ley button.vencida b{color:var(--red)}
.ley button.cerca b{color:var(--amb)}
.ley .nada{font-size:12.5px;color:var(--text3);padding:4px 2px}
.f-todo{display:block;width:100%;margin-top:12px;padding:8px;border:1px solid var(--dk);border-radius:var(--radius-sm);background:var(--dk);color:var(--cr);font-family:inherit;font-size:12.5px;font-weight:700;cursor:pointer}
[data-theme="dark"] .f-todo{background:var(--gd);border-color:var(--gd);color:#1A1816}
.f-todo[hidden],.foco-b[hidden]{display:none}
/* Con un bloque en foco se ve su tramo completo, no un mes: las flechas de mes no aplican. */
.barra.foco .mes button{display:none}
.barra.foco .mes h2{min-width:0;text-align:left}
/* Con un bloque en foco: su medición y su entrega, en un renglón, con el salto al mes de cada una. */
.foco-b{display:flex;flex-wrap:wrap;align-items:center;gap:6px 14px;margin-bottom:10px;padding:10px 14px;border:1px solid var(--border);border-left:4px solid var(--dk);border-radius:var(--radius);background:var(--card);font-size:13px;color:var(--text2)}
[data-theme="dark"] .foco-b{border-left-color:var(--gd)}
.foco-b b{color:var(--text);font-weight:700}
.foco-b .rojo{color:var(--red);font-weight:700}
.foco-b .amb{color:var(--amb);font-weight:700}
.foco-b button{padding:6px 12px;border:1px solid var(--border);border-radius:999px;background:var(--card);font-family:inherit;font-size:12.5px;font-weight:700;color:var(--text2);cursor:pointer}
.foco-b .fin{margin-left:auto;display:flex;gap:6px}
@media(hover:hover){.ley button:hover{background:var(--card-alt)}}
.hoy{margin-left:auto;font-size:14px;color:var(--text2);white-space:nowrap}
.hoy b{font-weight:700;color:var(--text)}

/* === PLANO: el mes + lo que falta agendar === */
.plano{display:grid;grid-template-columns:190px minmax(0,1fr) 280px;gap:16px;align-items:start}
/* Pantallas medianas: los filtros van arriba del mes, plegados detrás de un botón. */
@media(max-width:1359px){
  .plano{grid-template-columns:minmax(0,1fr) 280px}
  .filtros{grid-column:1/-1;position:static;max-height:none;padding:0;border:none;background:none;box-shadow:none}
  .f-tit{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border:1px solid var(--border);border-radius:999px;background:var(--card);font-family:inherit;font-size:13px;font-weight:700;color:var(--text2);cursor:pointer}
  .f-tit::after{content:'';width:6px;height:6px;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:translateY(-2px) rotate(45deg)}
  .f-tit[aria-expanded="true"]::after{transform:translateY(2px) rotate(-135deg)}
  .f-tit.activo{border-color:var(--dk);color:var(--text)}
  .f-cuerpo{display:none;margin-top:10px;padding:14px;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow)}
  .filtros.abierto .f-cuerpo{display:block}
  .ley,.refs{flex-direction:row;flex-wrap:wrap;gap:2px 18px}
  .ref b{margin-left:2px}
  .ley button{width:auto;padding:7px 4px}
  .ley button b{margin-left:2px}
  .f-todo{width:auto;padding:8px 16px}
}
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
@media(hover:hover){.ev:hover{border-color:var(--et);border-left-color:var(--et)}}
.ev i{font-style:normal;font-weight:700;font-variant-numeric:tabular-nums;color:var(--et)}
.ev span{color:var(--text3);font-weight:500}
.ev em{font-style:normal;display:inline-flex;align-items:center;justify-content:center;width:15px;height:15px;margin-right:4px;border-radius:50%;background:var(--red);color:var(--card);font-size:10.5px;font-weight:800;vertical-align:-2px}
.ev.atras{background:var(--red-soft);border-left-color:var(--red)}
.ev.atras i{color:var(--red)}
.ev.arr{opacity:.4}
/* Medición ya hecha: queda en su día, apagada. No es un pendiente. */
.ev.hecha{background:none;border-color:var(--border-soft);border-left-color:var(--et);color:var(--text3);font-weight:500}
.ev.hecha b{color:var(--text2)}
.ev em.ok{background:var(--gr)}
[data-theme="dark"] .ev em.ok{color:#04220F}
/* Vencimiento del plazo: es un límite, no un turno. Va sin relleno y con borde punteado. */
.ev.vence{--et:var(--text3);background:none;border:1px dashed var(--border);border-left:1px dashed var(--border);color:var(--text2);font-weight:600}
@media(hover:hover){.ev.vence:hover{border-color:var(--text3)}}
/* Entrega estimada de lo que todavía no se midió: más tenue que un vencimiento real. */
.ev.vence.prev{color:var(--text3);font-style:italic;font-weight:500;opacity:.85}
.ev.vence.cerca{border-color:var(--amb);color:var(--amb);background:var(--amb-soft)}
.ev.vence.vencida{border-color:var(--red);color:var(--red);background:var(--red-soft);font-weight:700}

/* === BLOQUE DE MEDICIÓN ===
   Todas las obras medidas un mismo día forman un bloque. Su entrega se espera entre el día 15 y
   el 20 desde la medición: esa ventana es una franja que cruza los días, y el último vence. */
.dia{--dp:6px}
.blq{--bq:var(--gd);--bq-soft:color-mix(in srgb,var(--gd) 26%,var(--card));--bq-t:var(--text);
  display:flex;align-items:center;gap:6px;box-sizing:border-box;width:calc(100% + 2*var(--dp) + 1px);height:22px;margin:4px calc(-1*var(--dp) - 1px) 0 calc(-1*var(--dp));padding:0 7px;
  border-top:1px solid var(--bq);border-bottom:1px solid var(--bq);background:var(--bq-soft);color:var(--bq-t);font-size:11.5px;font-weight:700;line-height:1;white-space:nowrap;cursor:pointer;user-select:none;-webkit-user-select:none}
.blq.hueco{visibility:hidden;cursor:default}
.blq.ini{width:calc(100% + var(--dp) + 1px);margin-left:0;border-left:3px solid var(--bq);border-radius:5px 0 0 5px}
.blq.fin{width:calc(100% + var(--dp));margin-right:0;border-right:1px solid var(--bq);border-radius:0 5px 5px 0}
.blq.ini.fin{width:100%;border-radius:5px}
/* el rótulo puede seguir de largo sobre los días siguientes de la misma franja */
.blq.rot{position:relative;z-index:2}
.blq.ult,.blq.fin{overflow:hidden}
.blq span{overflow:hidden;text-overflow:ellipsis;min-width:0}
.blq.rot:not(.ult):not(.fin) span{overflow:visible}
.blq i{font-style:normal;margin-left:auto;flex-shrink:0;padding:2px 6px;border-radius:999px;background:var(--bq);color:#1A1816;font-size:10px;font-weight:800;letter-spacing:.02em}
/* Bloque de MEDICIÓN: un solo día, en el color de Por medir. No se confunde con la entrega. */
.blq.med{--bq:var(--et);--bq-soft:var(--et-soft);--bq-t:var(--et);border:1px solid var(--bq);border-left:3px solid var(--bq)}
.blq.med i{color:#fff;padding:1px 5px}
.blq s,.blq u{text-decoration:none}
.blq s{display:none}
/* Celdas angostas (tablet, notebook chica): el rótulo de medición se acorta para que entre entero. */
@media(max-width:1599px){
  .blq.med{padding:0 5px}
  .blq.med u,.blq.med i{display:none}
  .blq.med s{display:inline}
}
[data-theme="dark"] .blq.med i{color:#14110D}
.ev.blq-ag.med{background:var(--et-soft);color:var(--et);font-weight:700}
.blq.est{border-top-style:dashed;border-bottom-style:dashed;--bq-soft:color-mix(in srgb,var(--gd) 11%,var(--card));font-style:italic;font-weight:600;color:var(--text2)}
.blq.cerca{--bq:var(--amb);--bq-soft:var(--amb-soft);--bq-t:var(--amb)}
.blq.vencida{--bq:var(--red);--bq-soft:var(--red-soft);--bq-t:var(--red)}
.blq.cerca i,.blq.vencida i{color:var(--card)}
@media(hover:hover){.blq:not(.hueco):hover{filter:brightness(.96)}}
.blq:focus-visible{outline:2px solid var(--gd);outline-offset:1px}
/* en la lista del celular el bloque va como un renglón más del día */
.ev.blq-ag{--et:var(--gd);background:color-mix(in srgb,var(--gd) 20%,var(--card))}
.ev.blq-ag.est{font-style:italic;background:var(--gd-soft)}
.ev.blq-ag.cerca{--et:var(--amb);background:var(--amb-soft)}
.ev.blq-ag.vencida{--et:var(--red);background:var(--red-soft)}
.modal.angosta{max-width:480px}
.modal .lista .ev{padding:10px 12px;font-size:13.5px;margin-top:8px}

/* === PENDIENTES === */
.pend{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);padding:14px;position:sticky;top:12px;max-height:calc(100vh - 24px);overflow-y:auto}
.pend h3{font-family:'Fraunces',serif;font-size:17px;font-weight:600;margin-bottom:2px}
.pend .ayuda{font-size:12px;color:var(--text3);margin-bottom:6px}
.pend .ayuda.foco{color:var(--text);font-weight:700;padding:6px 9px;border-radius:var(--radius-sm);background:var(--card-alt)}
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
.modal{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-lg);padding:24px;max-width:920px;width:100%;max-height:calc(100vh - 32px);overflow-y:auto}
/* Dos partes: a la izquierda lo propio del calendario (día, hora, plazo); a la derecha la ficha del tablero, entera. */
.modal .dos{display:grid;grid-template-columns:minmax(0,340px) minmax(0,1fr);gap:22px;align-items:start}
.modal .ficha-t{font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--text2);margin-bottom:6px}
.modal .ficha{display:block;width:100%;height:520px;border:none;border-radius:var(--radius);background:var(--card)}
@media(max-width:820px){.modal .dos{grid-template-columns:minmax(0,1fr);gap:18px}}
.modal h3{font-family:'Fraunces',serif;font-size:20px;font-weight:600;margin-bottom:6px}
.modal .sub{color:var(--text2);font-size:13px;line-height:1.5}
.modal .sub.estado{font-weight:700;margin-bottom:2px}
.modal .campo{display:block;margin-top:20px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--text2)}
.modal .fila{display:flex;gap:8px;margin-top:5px}
.modal input{flex:1 1 130px;min-width:0;width:100%;padding:11px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--card);color:var(--text);font-family:inherit;font-size:16px;font-weight:600;outline:none}
.modal input:focus{border-color:var(--gd);box-shadow:0 0 0 3px var(--gd-soft)}
.modal .nota{font-size:12.5px;color:var(--text3);margin-top:6px;line-height:1.5}
.modal .nota.rojo{color:var(--red);font-weight:700}
.modal .lnk{background:none;border:none;padding:0;font-size:12.5px;font-weight:700;color:var(--text2);text-decoration:underline;cursor:pointer}
.modal .ir{display:inline-block;margin-top:20px;font-size:13px;font-weight:700;color:var(--text2)}
.modal .btns{display:flex;gap:10px;justify-content:flex-end;margin-top:26px}
.modal .btns button{padding:9px 16px;border-radius:var(--radius-sm);font-size:13.5px;font-weight:700;cursor:pointer;border:1px solid var(--border);background:var(--card)}
.modal .btns .ok{background:var(--dk);color:var(--cr);border-color:var(--dk)}
[data-theme="dark"] .modal .btns .ok{background:var(--gd);color:#1A1816;border-color:var(--gd)}
.toast{position:fixed;left:50%;bottom:24px;transform:translate(-50%,20px);background:var(--dk);color:var(--cr);padding:11px 16px;border-radius:var(--radius-sm);border-left:3px solid var(--red);box-shadow:var(--shadow-lg);font-size:13px;font-weight:600;opacity:0;pointer-events:none;transition:opacity .2s,transform .2s;z-index:1000;max-width:calc(100vw - 32px)}
.toast.show{opacity:1;transform:translate(-50%,0)}
.toast.ok{border-left-color:var(--gr)}

/* Al tocar: sin el destello gris del navegador ni la espera del doble toque. */
a,button,input,.toggle{-webkit-tap-highlight-color:transparent;touch-action:manipulation}
button:focus-visible,a:focus-visible,.toggle:focus-visible{outline:2px solid var(--gd);outline-offset:2px}
.pend-mas{display:none}

/* === PANTALLAS TÁCTILES: lo que se toca mide lo que mide un dedo === */
@media(max-width:1100px),(pointer:coarse){
  .mes button{height:42px;min-width:42px}
  .ley button{padding:9px 4px;font-size:13.5px}
  .ley button::before{width:18px;height:18px}
  .f-tit{padding:10px 16px}
  .ev{padding:7px 8px}
  .agenda .ev,.pend .ev{padding:11px 12px;font-size:13.5px}
  .modal .btns button{padding:11px 18px;font-size:14px}
  .modal .lnk{padding:8px 0;display:inline-block}
  .toggle{width:54px;height:30px}
  .toggle::after{top:3px}
  .logout-link{width:36px;height:36px;opacity:.7}
  .logout-link svg{width:16px;height:16px}
}
/* === CELULAR Y TABLET PARADA: los pendientes arriba, el mes abajo === */
@media(max-width:1100px){
  .header{padding:12px max(14px,env(safe-area-inset-right)) 12px max(14px,env(safe-area-inset-left))}
  .header .h-right>span{display:none}
  .h-tabs{padding:0 8px;scrollbar-width:none}
  .h-tabs::-webkit-scrollbar{display:none}
  .h-tabs a{padding:12px 12px}
  .wrap{padding:14px max(20px,env(safe-area-inset-right)) calc(60px + env(safe-area-inset-bottom)) max(20px,env(safe-area-inset-left))}
  .plano{grid-template-columns:minmax(0,1fr)}
  .filtros{order:-2}
  .pend{order:-1;position:static;max-height:none;margin-bottom:10px}
  /* Con muchos pendientes el mes quedaba muy abajo: se muestran los primeros y un botón para ver todos. */
  .pend.corto{max-height:272px;overflow:hidden;position:relative}
  .pend-mas{display:block;width:100%;margin-top:12px;padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--card);font-family:inherit;font-size:13.5px;font-weight:700;color:var(--text2);cursor:pointer}
  .pend.corto .pend-mas{position:absolute;left:0;right:0;bottom:0;margin:0;border:none;border-top:1px solid var(--border);border-radius:0 0 var(--radius) var(--radius);box-shadow:0 -20px 18px -8px var(--card)}
  .toast{bottom:calc(20px + env(safe-area-inset-bottom))}
}
/* Tablet parada: el mes entra en grilla; los días son más angostos. */
@media(min-width:700px) and (max-width:1100px){
  .dia{min-height:108px;padding:5px 4px 7px;--dp:4px}
  .blq{font-size:11px;padding:0 5px}
  .cal .dh{padding:8px 6px}
  .cal .ev{font-size:11.5px;padding:6px 6px;margin-top:3px}
}
/* Celular: el mes va en lista. */
@media(max-width:699px){
  .wrap{padding:12px max(12px,env(safe-area-inset-right)) calc(60px + env(safe-area-inset-bottom)) max(12px,env(safe-area-inset-left))}
  .h-tabs a{font-size:.62rem;letter-spacing:.06em}
  .barra{gap:10px}
  .hoy{order:-1;width:100%;margin-left:0}
  .mes{width:100%}
  .mes h2{flex:1;min-width:0;font-size:20px}
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
  <a href="/presupuestos/stock.php">Stock</a>
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
    <div class="hoy" id="hoyTxt"></div>
  </div>
  <div class="plano">
    <aside class="filtros" id="filtros">
      <button type="button" class="f-tit" id="fTit" aria-expanded="false">Filtros</button>
      <div class="f-cuerpo">
        <h4>Bloque de medición</h4>
        <div class="ley" id="fBloques"></div>
        <button type="button" class="f-todo" id="fTodo" hidden>Ver todo</button>
        <h4>Referencias</h4>
        <div class="refs" id="ley"></div>
      </div>
    </aside>
    <div>
      <div class="foco-b" id="focoB" hidden></div>
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
      <button type="button" id="mCancelar">Cerrar</button>
      <button type="button" class="ok" id="mOk">Guardar fecha y plazo</button>
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
// Ya no se apaga nada por tipo: el único filtro son los bloques de medición. Se limpia lo que
// hubiera quedado guardado de la versión anterior, para que nada siga escondido.
try{localStorage.removeItem('bs-cal-ver');}catch(e){}

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
// Entrega estimada de una obra que todavía no se midió: si se mide el día agendado, el plazo
// vencería ese día + 20 corridos. No es un compromiso todavía (por eso va "fantasma"): se
// vuelve vencimiento real cuando se marca Medido. Si ya tiene plazo (real o puesto a mano), no aplica.
function previsto(c){
  const e=etapaDe(c.etapa);
  return (!c.limite&&e&&e.boton==='medido'&&c.fecha&&!cumplida(c))?masDias(c.fecha,c.plazo_dias):'';
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
    if(j.ventana)VENTANA=+j.ventana;
    MEDIR=(ETAPAS.find(e=>e.boton==='medido')||{}).id||'';
    if(!mes)mes=HOY.slice(0,7);
    dibujar();
  }catch(e){
    if(!CARDS.length)$('cal').innerHTML='<div class="aviso" style="grid-column:1/-1">'+esc(e.message)+'</div>';
    else toast(e.message);
  }
}

// ---------- Bloques de medición ----------
// Las mediciones se hacen en tandas. Todas las obras medidas un mismo día forman un bloque, sean
// ocho o una sola (no depende de qué día de la semana sea). A cada bloque le toca una ventana de
// entrega: del día VENTANA (15) al día del plazo (20) desde la medición. El último día vence.
// Las obras siguen teniendo su cartelito "Vence" el día 20: la franja da la idea de la semana.
let VENTANA=15;
let MEDIR=''; // id de la etapa donde se mide ("medir")
// Foco por bloque: los días de medición marcados en el panel de filtros. Vacío = se ve todo.
// No se guarda entre visitas: es para seguir un bloque en el momento, no para dejarlo escondido.
const selB=new Set();
// A qué bloque pertenece una obra: el día en que se midió o, si falta medir, el día agendado.
const bloqueDe=c=>c.medido_at||(c.etapa===MEDIR&&c.fecha&&!cumplida(c)?c.fecha:'');
// Con TODOS los bloques marcados se ve todo, también las obras que todavía no tienen fecha de medición.
let todosMarcados=false;
const enFoco=c=>!selB.size||selB.has(bloqueDe(c))||(todosMarcados&&!bloqueDe(c));
const diaMes=iso=>(+iso.slice(8,10))+'-'+MESES[+iso.slice(5,7)-1].slice(0,3);
function bloques(todos){
  const m={};
  CARDS.forEach(c=>{
    if(!todos&&!enFoco(c))return;
    if(c.etapa==='archivar'||c.plazo_manual)return; // ya instalada, o con un plazo propio puesto a mano
    // medida: cuenta el día en que se midió; todavía no: el día agendado (bloque estimado)
    const dia=c.medido_at||(previsto(c)?c.fecha:'');
    if(!dia)return;
    const b=m[dia]=m[dia]||{dia:dia,obras:[],est:true,dias:c.plazo_dias};
    b.obras.push(c);
    if(c.medido_at)b.est=false;
  });
  const l=Object.keys(m).sort().map(k=>{
    const b=m[k];
    b.desde=masDias(b.dia,Math.min(VENTANA,b.dias));
    b.hasta=masDias(b.dia,b.dias);
    b.estado=b.hasta<HOY?'vencida':(b.desde<=HOY?'cerca':'');
    const n=b.obras.length;
    // "Entrega": así se distingue del bloque de MEDICIÓN, que va marcado en azul en el día en que se mide.
    b.de='medidas el '+diaMes(b.dia)+' · '+(n===1?(nombreCorto(b.obras[0].nombre)||b.obras[0].cliente_nro):n+' obras');
    b.txt=(b.est?'Entrega estimada · ':'Entrega · ')+b.de;
    b.tit=(b.est?'Entrega estimada':'Entrega')+' del bloque medido el '+fechaCorta(b.dia)+' · '+n+(n===1?' obra':' obras')+' · del '+fechaCorta(b.desde)+' al '+fechaCorta(b.hasta)+' (vence)';
    return b;
  });
  // Carriles: dos bloques cuyas ventanas se pisan van uno debajo del otro, siempre en el mismo renglón.
  const fin=[];
  l.forEach(b=>{let i=0;while(fin[i]&&fin[i]>=b.desde)i++;fin[i]=b.hasta;b.carril=i;});
  return l;
}
// Las franjas de un día de la grilla. col: 0 = lunes … 6 = domingo.
function franjas(iso,L,col){
  const hoy=L.filter(b=>b.desde<=iso&&iso<=b.hasta);
  if(!hoy.length)return '';
  const max=Math.max.apply(null,hoy.map(b=>b.carril));
  let h='';
  for(let i=0;i<=max;i++){
    const b=hoy.find(x=>x.carril===i);
    if(!b){h+='<div class="blq hueco"></div>';continue;}
    const ini=iso===b.desde,fin=iso===b.hasta,rot=ini||col===0; // el rótulo va al empezar y al arrancar cada semana
    h+='<div class="blq'+(ini?' ini':'')+(fin?' fin':'')+(rot?' rot':'')+(col===6?' ult':'')+(b.est?' est':'')+(b.estado?' '+b.estado:'')+'" role="button" tabindex="0" data-bloque="'+b.dia+'" title="'+esc(b.tit)+'">'
      +(rot?'<span>'+esc(col===6?(b.est?'Estimada · ':'Entrega · ')+diaMes(b.dia):b.txt)+'</span>':'')+(fin?'<i>'+(b.estado==='vencida'?'Venció':'Vence')+'</i>':'')+'</div>';
  }
  return h;
}
// Bloque de MEDICIÓN: las obras que se miden (o se midieron) un mismo día. No es la entrega:
// es el día de la tanda, y va en el color de Por medir.
function obrasMedicion(dia){
  return CARDS.filter(c=>bloqueDe(c)===dia);
}
function tiraMedicion(iso,largo){
  const l=selB.size&&!selB.has(iso)?[]:obrasMedicion(iso);
  if(!l.length||!MEDIR)return '';
  const n=l.length,h=l.filter(c=>c.medido_at).length;
  const estado=h===n?(n===1?'hecha':'hechas'):(h?h+' de '+n+' hechas':'');
  const t='Bloque de medición del '+fechaCorta(iso)+' · '+n+(n===1?' obra':' obras')+(h===n?' · todas medidas':(h?' · '+h+' medidas':' · sin medir todavía'));
  if(largo)return '<button type="button" class="ev blq-ag med et-'+esc(MEDIR)+'" data-bloque="'+iso+'" data-origen="med">Bloque de medición · '+n+(n===1?' obra':' obras')+(estado?' <span>· '+estado+'</span>':'')+'</button>';
  return '<div class="blq med ini fin et-'+esc(MEDIR)+(h===n?' ok':'')+'" role="button" tabindex="0" data-bloque="'+iso+'" data-origen="med" title="'+esc(t)+'"><span>'+(h===n?'<s>✓ </s>':'')+'Medición · '+n+'<u>'+(n===1?' obra':' obras')+'</u></span>'+(h===n?'<i>✓</i>':'')+'</div>';
}
// La ventana del bloque: se abre desde el bloque de medición (azul) o desde su entrega (franja).
// Dice las dos cosas por separado, y lista las obras para abrir cada ficha.
function abrirBloque(dia,origen){
  const obras=obrasMedicion(dia),b=bloques().find(x=>x.dia===dia);
  if(!obras.length&&!b)return;
  const lista=obras.length?obras:b.obras,n=lista.length,hechas=lista.filter(c=>c.medido_at).length,orden=ETAPAS.map(e=>e.id);
  let h='<p class="sub estado">Medición: '+esc(fechaCorta(dia))+' · '+n+(n===1?' obra':' obras')+(hechas===n?' · todas medidas':(hechas?' · '+hechas+' medidas, '+(n-hechas)+' sin medir':' · todavía sin medir'))+'</p>';
  if(b){
    const m=b.obras.length;
    h+='<p class="sub estado">'+(b.est?'Entrega estimada':'Entrega')+': del '+esc(fechaCorta(b.desde))+' al '+esc(fechaCorta(b.hasta))+'</p>'
      +'<p class="sub">'+(b.estado==='vencida'?'El plazo venció el '+esc(fechaCorta(b.hasta))+' y '+(m===1?'queda 1 obra':'quedan '+m+' obras')+' sin entregar.'
        :'La entrega va del día '+Math.min(VENTANA,b.dias)+' al '+b.dias+' desde la medición; el '+esc(fechaCorta(b.hasta))+' vence.'+(b.est?' Es estimada hasta que se marquen medidas.':''))+'</p>';
  }else h+='<p class="sub">Sin franja de entrega: estas obras tienen el plazo puesto a mano o ya están instaladas.</p>';
  h+='<div class="lista">';
  lista.slice().sort((x,z)=>(orden.indexOf(x.etapa)-orden.indexOf(z.etapa))||(x.key<z.key?-1:1)).forEach(c=>{
    h+='<button type="button" class="ev et-'+esc(c.etapa)+'" data-key="'+esc(c.key)+'" data-tipo="'+(origen==='med'?'medida':'vence')+'"><b>'+esc(c.cliente_nro)+'</b> '+esc(nombreCorto(c.nombre)||'Sin nombre')
      +' <span>· '+esc(etapaDe(c.etapa).titulo)+(c.medido_at?'':' · todavía sin medir')+(c.plazo_manual?' · plazo propio':'')+'</span></button>';
  });
  h+='</div>';
  $('mTitulo').textContent=(origen==='med'?'Bloque de medición del ':'Entrega del bloque medido el ')+fechaCorta(dia);
  $('mCuerpo').innerHTML=h;
  document.querySelector('.modal').classList.add('angosta');
  $('mOk').hidden=true;
  modalOk=null;
  $('modalBg').classList.add('show');
}

// Todos los eventos, por día: {iso:[{tipo,c}]}. tipo = id de etapa (turno) o 'vence' (plazo).
function armar(){
  const dias={};
  const sumar=(d,ev)=>{(dias[d]=dias[d]||[]).push(ev);};
  CARDS.forEach(c=>{
    const e=etapaDe(c.etapa);
    if(!e||!enFoco(c))return;
    if(e.fecha&&c.fecha&&!cumplida(c)&&ver[c.etapa]!==false)sumar(c.fecha,{tipo:c.etapa,c:c});
    // La medición ya hecha queda marcada en su día, esté la obra en la etapa que esté: es el día
    // que le da nombre a su bloque y desde el que corre el plazo.
    if(c.medido_at&&MEDIR&&ver[MEDIR]!==false)sumar(c.medido_at,{tipo:'medida',c:c});
    if(c.limite&&c.etapa!=='archivar'&&ver.vence!==false)sumar(c.limite,{tipo:'vence',c:c});
    if(previsto(c)&&ver.vence!==false)sumar(previsto(c),{tipo:'vence',c:c,prev:true});
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
  if(ev.prev&&previsto(c)<HOY){
    // La entrega estimada ya pasó: los días corrieron igual desde la medición agendada, esté o no
    // tildado "Medido". Se muestra como vencida, igual que las demás.
    const t='Venció el '+fechaCorta(previsto(c))+': la medición estaba agendada para el '+fechaCorta(c.fecha)+' ('+c.plazo_dias+' días corridos). Todavía no figura como medida.';
    return '<button type="button" class="ev vence vencida" draggable="true" data-key="'+esc(c.key)+'" data-tipo="vence" title="'+esc(t)+'">Venció · '+nom+(largo?' <span>· '+esc(etapaDe(c.etapa).titulo)+' · sin marcar medida</span>':'')+'</button>';
  }
  if(ev.prev){
    const t='Si se mide el '+fechaCorta(c.fecha)+', el plazo vence el '+fechaCorta(previsto(c))+' ('+c.plazo_dias+' días corridos)';
    return '<button type="button" class="ev vence prev" draggable="true" data-key="'+esc(c.key)+'" data-tipo="vence" title="'+esc(t)+'">Entrega estimada · '+nom+(largo?' <span>· todavía sin medir</span>':'')+'</button>';
  }
  if(ev.tipo==='medida'){
    const t='Medición hecha el '+fechaCorta(c.medido_at)+' · ahora en '+etapaDe(c.etapa).titulo;
    return '<button type="button" class="ev hecha et-'+esc(MEDIR)+'" data-key="'+esc(c.key)+'" data-tipo="medida" title="'+esc(t)+'"><em class="ok">✓</em>'+(largo?'Medición hecha · ':'')+nom+(largo?' <span>· '+esc(etapaDe(c.etapa).titulo)+'</span>':'')+'</button>';
  }
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
  // Lo que se ve: normalmente un mes. Con bloques en foco, la HISTORIA COMPLETA de esos bloques:
  // desde el día de la medición hasta el día en que vence la entrega, aunque cruce de un mes a otro.
  [...selB].forEach(d=>{if(!CARDS.some(c=>bloqueDe(c)===d))selB.delete(d);}); // un bloque que ya no existe deja de filtrar
  todosMarcados=selB.size>0&&selB.size>=new Set(CARDS.map(bloqueDe).filter(Boolean)).size;
  const foco=selB.size>0;
  let vDesde=isoDe(new Date(y,m-1,1)),vHasta=isoDe(new Date(y,m,0));
  if(foco){
    const sel=[...selB].sort(),tb=bloques(true);
    vDesde=sel[0];
    vHasta=sel.map(d=>{const b=tb.find(x=>x.dia===d);return b?b.hasta:masDias(d,20);}).sort().pop();
    // y todo lo que ya tienen agendado esas obras (por ejemplo una instalación más adelante)
    CARDS.filter(enFoco).forEach(c=>{[c.fecha,c.limite].forEach(f=>{if(f&&f>vHasta)vHasta=f;});});
  }
  const enVista=iso=>!!iso&&iso>=vDesde&&iso<=vHasta;
  const diaTxt=iso=>(+iso.slice(8,10))+' '+MESES[+iso.slice(5,7)-1].slice(0,3);
  $('mesTxt').textContent=foco?diaTxt(vDesde)+' – '+diaTxt(vHasta):MESES[m-1].replace(/^./,x=>x.toUpperCase())+' '+y;
  document.querySelector('.barra').classList.toggle('foco',foco);

  // Referencias: qué es cada color y cuántos hay a la vista. No son interruptores.
  const F=CARDS.filter(enFoco);
  const tipos=ETAPAS.filter(e=>e.fecha).map(e=>({id:e.id,txt:PLURAL[e.fecha]||e.fecha,n:F.filter(c=>c.etapa===e.id&&enVista(c.fecha)&&!cumplida(c)).length}));
  tipos.push({id:'entrega',txt:'Entregas',n:bloques().filter(b=>b.desde<=vHasta&&b.hasta>=vDesde).length});
  tipos.push({id:'vence',txt:'Vencimientos',n:F.filter(c=>(c.limite&&c.etapa!=='archivar'&&enVista(c.limite))||enVista(previsto(c))).length});
  $('ley').innerHTML=tipos.map(t=>'<div class="ref '+(t.id==='vence'||t.id==='entrega'?t.id:'et-'+esc(t.id))+'">'+esc(t.txt)+' <b>'+t.n+'</b></div>').join('');
  // El filtro: los bloques de medición que existen hoy en el tablero (solo datos reales). Marcar uno
  // muestra su par completo: la medición y su entrega con el vencimiento.
  const todosB=bloques(true),dB={};
  CARDS.forEach(c=>{const d=bloqueDe(c);if(d)dB[d]=(dB[d]||0)+1;});
  const diasB=Object.keys(dB).sort();
  $('fBloques').innerHTML=diasB.length?diasB.map(d=>{
    const b=todosB.find(x=>x.dia===d),est=b?b.estado:'';
    return '<button type="button" class="'+(selB.has(d)?'on ':'')+est+'" data-fbloque="'+d+'" role="checkbox" aria-checked="'+selB.has(d)+'" title="'+esc('Medición del '+fechaCorta(d)+(b?' · entrega del '+fechaCorta(b.desde)+' al '+fechaCorta(b.hasta):''))+'">'+esc(diaMes(d))+' <b>'+dB[d]+'</b></button>';
  }).join(''):'<div class="nada">Todavía no hay mediciones.</div>';
  const nFil=selB.size;
  $('fTodo').hidden=!nFil;
  // Resumen del foco, arriba de la grilla: qué se está siguiendo y cómo salir.
  let fo='';
  if(selB.size===1){
    const d=[...selB][0],b=todosB.find(x=>x.dia===d),l=obrasMedicion(d),hm=l.filter(c=>c.medido_at).length;
    fo='<span><b>Bloque '+esc(diaMes(d))+'</b></span><span>Medición: <b>'+esc(fechaCorta(d))+'</b> · '+l.length+(l.length===1?' obra':' obras')+(hm===l.length?' · todas medidas':' · '+hm+(hm===1?' medida, ':' medidas, ')+(l.length-hm)+' sin medir')+'</span>'
      +(b?'<span>'+(b.est?'Entrega estimada':'Entrega')+': <b>'+esc(fechaCorta(b.desde))+' al '+esc(fechaCorta(b.hasta))+'</b>'+(b.estado==='vencida'?' <span class="rojo">· vencida</span>':(b.estado==='cerca'?' <span class="amb">· en curso</span>':''))+'</span>':'');
  }else if(selB.size>1)fo='<span><b>'+selB.size+' bloques en foco:</b> '+[...selB].sort().map(d=>esc(diaMes(d))).join(', ')+'</span>';
  if(fo)fo+='<span class="fin"><button type="button" data-salir>Volver al mes</button></span>';
  $('focoB').innerHTML=fo;$('focoB').hidden=!fo;
  $('fTit').textContent=nFil?'Filtros · '+nFil:'Filtros';
  $('fTit').classList.toggle('activo',nFil>0);

  const dias=armar();
  const L=ver.entrega!==false?bloques():[]; // franjas de entrega: tienen su propio botón en la leyenda
  const conMed=ver[MEDIR]!==false;       // el bloque de medición se apaga junto con las mediciones

  // Grilla: semanas de lunes a domingo, del primer al último día a la vista (un mes, o el tramo en foco).
  const primero=partes(vDesde),ultimo=partes(vHasta);
  const d=new Date(primero);d.setDate(d.getDate()-((primero.getDay()+6)%7));
  let g=['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'].map(x=>'<div class="dh">'+x+'</div>').join('');
  let va=0;
  while(d<=ultimo||d.getDay()!==1){
    const iso=isoDe(d),fuera=!enVista(iso);
    // en foco el tramo cruza meses: el número lleva el mes al empezar y cada día 1
    const num=d.getDate()+(foco&&(va===0||d.getDate()===1)?' '+MESES[d.getMonth()].slice(0,3):'');va++;
    g+='<div class="dia'+(fuera?' otro':'')+(iso===HOY?' es-hoy':'')+(iso<HOY?' pasado':'')+(d.getDay()===0||d.getDay()===6?' finde':'')+'" data-dia="'+iso+'"><div class="n">'+num+'</div>'
      +franjas(iso,L,(d.getDay()+6)%7)+(conMed?tiraMedicion(iso,false):'')+(dias[iso]||[]).map(ev=>chip(ev,false)).join('')+'</div>';
    d.setDate(d.getDate()+1);
  }
  $('cal').innerHTML=g;

  // Agenda (celular): los días del mes que tienen algo, más hoy.
  let a='';
  for(let iso=vDesde;iso<=vHasta;iso=masDias(iso,1)){
    const l=dias[iso]||[];
    // En la lista, cada bloque aparece dos veces: el día que empieza su ventana y el día que vence.
    const bq=L.filter(b=>b.desde===iso||b.hasta===iso).map(b=>'<button type="button" class="ev blq-ag'+(b.est?' est':'')+(b.estado?' '+b.estado:'')+'" data-bloque="'+b.dia+'">'
      +(b.hasta===iso?(b.estado==='vencida'?'Venció la entrega':'Vence la entrega')+' · '+esc(b.de):(b.est?'Empieza la entrega estimada':'Empieza la entrega')+' · '+esc(b.de)+'<br><span>Hasta el '+esc(fechaCorta(b.hasta))+'</span>')+'</button>').join('')
      +(conMed?tiraMedicion(iso,true):'');
    if(!l.length&&!bq&&iso!==HOY)continue;
    a+='<div class="ag-dia'+(iso===HOY?' es-hoy':'')+(iso<HOY?' pasado':'')+'" data-dia="'+iso+'"><div class="n">'+esc(fechaLarga(iso))+(iso===HOY?' · hoy':'')+'</div>'
      +bq+(l.length?l.map(ev=>chip(ev,true)).join(''):(bq?'':'<div class="nada">Nada agendado.</div>'))+'</div>';
  }
  $('agenda').innerHTML=a||'<div class="aviso">Nada agendado este mes.</div>';

  // Pendientes: plazo vencido, turnos que se pasaron de fecha y lo que todavía no tiene día.
  let p='<h3>Pendientes</h3><div class="ayuda">Tocá una obra para ponerle día, o arrastrala al calendario.</div>',hay=false;
  // Lo urgente primero, y siempre a la vista aunque caiga en otro mes.
  // Cuenta también la entrega estimada que ya pasó (medición agendada, sin tildar "Medido").
  const venDia=c=>c.limite||previsto(c);
  if(selB.size)p+='<div class="ayuda foco">Solo '+(selB.size===1?'el bloque '+esc(diaMes([...selB][0])):'los '+selB.size+' bloques marcados')+'.</div>';
  const ven=F.filter(c=>estadoPlazo(c)==='vencida'||(previsto(c)&&previsto(c)<HOY)).sort((x,z)=>venDia(x)<venDia(z)?-1:1);
  if(ven.length){hay=true;p+='<h4 class="rojo">Plazo vencido <b>'+ven.length+'</b></h4>'+ven.map(c=>chip({tipo:'vence',c:c,prev:!c.limite},true)).join('');}
  const atr=F.filter(atrasada).sort((x,z)=>x.fecha<z.fecha?-1:1);
  if(atr.length){hay=true;p+='<h4 class="rojo">Atrasadas <b>'+atr.length+'</b></h4>'+atr.map(c=>chip({tipo:c.etapa,c:c},true)).join('');}
  ETAPAS.filter(e=>e.fecha).forEach(e=>{
    const l=F.filter(c=>c.etapa===e.id&&!c.fecha&&!cumplida(c)).sort((x,z)=>x.desde<z.desde?-1:(x.desde>z.desde?1:0));
    if(!l.length)return;
    hay=true;
    p+='<h4>'+esc('Sin '+(e.exige_fecha?'turno':'fecha')+' de '+e.fecha.toLowerCase())+' <b>'+l.length+'</b></h4>'+l.map(c=>chip({tipo:c.etapa,c:c},false)).join('');
  });
  if(!hay)p+='<div class="ok">Nada pendiente: todo tiene su fecha y está en plazo.</div>';
  pendTotal=(p.match(/class="ev[ "]/g)||[]).length;
  if(pendTotal>3)p+='<button type="button" class="pend-mas" id="pendMas"></button>';
  $('pend').innerHTML=p;
  pintarPend();
}
// En celular y tablet parada el panel va arriba del mes: muestra los primeros y un botón para verlos todos.
let pendTotal=0,pendAbierto=false;
function pintarPend(){
  const b=$('pendMas');
  $('pend').classList.toggle('corto',!!b&&!pendAbierto);
  if(b)b.textContent=pendAbierto?'Mostrar menos':'Ver los '+pendTotal+' pendientes';
}
$('pend').addEventListener('click',ev=>{
  if(!ev.target.closest('#pendMas'))return;
  pendAbierto=!pendAbierto;
  pintarPend();
  if(!pendAbierto)$('pend').scrollIntoView({block:'nearest'});
});

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
// Al cerrar se vuelve a leer el tablero: en la ficha se pudo haber cambiado cualquier cosa.
function cerrarModal(recargar){
  $('modalBg').classList.remove('show');modalOk=null;
  $('mCuerpo').innerHTML='';
  if(recargar!==false)cargar();
}
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
    +'<p class="nota">'+(c.limite_auto?'Plazo normal: '+esc(fechaCorta(c.limite_auto))+' ('+c.plazo_dias+' días corridos desde la medición).':'Todavía no se midió: el plazo normal se calcula al medir ('+c.plazo_dias+' días corridos).'+(previsto(c)?' Si se mide el '+esc(fechaCorta(c.fecha))+', vence el '+esc(fechaCorta(previsto(c)))+'.':''))
    +' <button type="button" class="lnk" id="fNormal"'+(c.plazo_manual?'':' hidden')+'>Volver al plazo normal</button></p>';
  h+='<a class="ir" href="/presupuestos/tablero.php?q='+encodeURIComponent(c.cliente_nro+'-'+c.sub)+'">Ver en el tablero →</a>';
  // La ficha del tablero, entera y editable (tildes, seña, bacha, fotos, nota): es la misma página en modo "una ficha".
  h='<div class="dos"><div>'+h+'</div><div><div class="ficha-t">Ficha</div><iframe class="ficha" id="fFicha" title="Ficha de la obra" src="/presupuestos/tablero.php?solo='+encodeURIComponent(c.key)+'"></iframe></div></div>';
  $('mTitulo').textContent=c.cliente_nro+'-'+c.sub+' · '+(nombreCorto(c.nombre)||'Sin nombre');
  document.querySelector('.modal').classList.remove('angosta');
  $('mOk').hidden=false;
  $('mCuerpo').innerHTML=h;
  $('modalBg').classList.add('show');
  // El alto del marco sigue al de la ficha (crece al abrir "Más"), con un mínimo para que entren sus ventanitas.
  const marco=$('fFicha');
  marco.addEventListener('load',()=>{
    try{
      const doc=marco.contentDocument,ajustar=()=>{if(marco.isConnected)marco.style.height=Math.max(520,doc.documentElement.scrollHeight+2)+'px';};
      new ResizeObserver(ajustar).observe(doc.body);
      ajustar();
    }catch(e){}
  });

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
    cerrarModal(!cambios.length); // si hay cambios, guardar() recarga al terminar
    guardar(c,cambios,cambios.length?'Guardado: '+quien(c):'');
  };
  const f=foco==='plazo'?$('fPlazo'):(conTurno?(($('fDia').value&&$('fHora'))?$('fHora'):$('fDia')):$('fPlazo'));
  if(f&&matchMedia('(hover:hover)').matches)f.focus();
}
$('mCancelar').addEventListener('click',()=>cerrarModal());
$('mOk').addEventListener('click',()=>{if(modalOk)modalOk();});
$('modalBg').addEventListener('click',ev=>{if(ev.target===$('modalBg'))cerrarModal();});
document.addEventListener('keydown',ev=>{
  if(!$('modalBg').classList.contains('show'))return;
  if(ev.key==='Escape')cerrarModal();
  else if(ev.key==='Enter'&&modalOk){ev.preventDefault();$('mOk').click();}
});

// ---------- Tocar ----------
document.addEventListener('keydown',ev=>{
  const q=ev.target.closest&&ev.target.closest('.blq[data-bloque]');
  if(q&&(ev.key==='Enter'||ev.key===' ')){ev.preventDefault();abrirBloque(q.dataset.bloque,q.dataset.origen||'');}
});
document.addEventListener('click',ev=>{
  const q=ev.target.closest('[data-bloque]');
  if(q){abrirBloque(q.dataset.bloque,q.dataset.origen||'');return;}
  const b=ev.target.closest('.ev[data-key]');
  if(b){const c=cardDe(b.dataset.key);if(c)abrir(c,'',b.dataset.tipo==='vence'?'plazo':'');return;}
  const fb=ev.target.closest('[data-fbloque]');
  if(fb){
    const d=fb.dataset.fbloque;
    if(selB.has(d))selB.delete(d);else selB.add(d);
    dibujar();
    return;
  }
  if(ev.target.closest('[data-salir]')){selB.clear();dibujar();return;}
  if(ev.target.closest('#fTodo')){selB.clear();dibujar();return;}
  const ft=ev.target.closest('#fTit');
  if(ft){const ab=$('filtros').classList.toggle('abierto');ft.setAttribute('aria-expanded',ab);return;}
});
function moverMes(n){const d=new Date(+mes.slice(0,4),+mes.slice(5,7)-1+n,1);mes=isoDe(d).slice(0,7);dibujar();}
// En pantallas angostas la barra de páginas se desliza de costado: la página actual queda a la vista.
(function(){
  const a=document.querySelector('.h-tabs a.active'),n=a&&a.parentElement;
  if(n)n.scrollLeft=Math.max(0,a.offsetLeft-(n.clientWidth-a.offsetWidth)/2);
})();
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
