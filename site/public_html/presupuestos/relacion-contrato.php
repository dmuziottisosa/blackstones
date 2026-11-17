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

.wrap{max-width:1640px;margin:0 auto;padding:20px 20px 60px}

/* === ARRIBA: el mes, el buscador y el alta === */
.barra{display:flex;align-items:center;flex-wrap:wrap;gap:12px 16px;margin-bottom:16px}
.mes{display:flex;align-items:center;gap:6px}
.mes h2{font-family:'Fraunces',serif;font-size:24px;font-weight:600;min-width:214px;text-align:center}
.mes button{height:36px;min-width:36px;padding:0 10px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--card);font-size:16px;font-weight:700;color:var(--text2);cursor:pointer}
.mes button:disabled{opacity:.35;cursor:default}
.mes .hoy-b{font-size:13px;margin-left:4px}
.acc{margin-left:auto;display:flex;align-items:center;justify-content:flex-end;gap:10px;flex:1 1 380px}
.buscar{flex:1 1 220px;max-width:340px}
.buscar input{width:100%;padding:10px 16px;border:1px solid var(--border);border-radius:999px;background:var(--card);color:var(--text);font-family:inherit;font-size:14px;font-weight:500;outline:none}
.buscar input:focus{border-color:var(--gd);box-shadow:0 0 0 3px var(--gd-soft)}
.btn{padding:10px 16px;border:1px solid var(--border);border-radius:999px;background:var(--card);font-size:13.5px;font-weight:700;color:var(--text);cursor:pointer;white-space:nowrap}
.btn.pri{background:var(--dk);border-color:var(--dk);color:var(--cr)}
[data-theme="dark"] .btn.pri{background:var(--gd);border-color:var(--gd);color:#1A1816}
.btn.chico{padding:7px 12px;font-size:12.5px}
.btn:disabled{opacity:.6;cursor:default}

/* === RESUMEN DEL MES: una sola franja, para leer de corrido === */
.res{display:flex;flex-wrap:wrap;margin-bottom:12px;border:1px solid var(--border);border-radius:var(--radius);background:var(--card);overflow:hidden}
.res[hidden]{display:none}
.res>div{flex:1 1 150px;min-width:0;padding:10px 18px 11px;border-left:1px solid var(--border-soft)}
.res>div:first-child{flex:0 1 150px;border-left:none}
.res .t{font-size:10px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--text3)}
.res .v{margin-top:3px;font-size:15.5px;font-weight:700;color:var(--text);font-variant-numeric:tabular-nums;line-height:1.35;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.res .v.nada{color:var(--text3);font-weight:500}
.res .cobr .v{color:var(--gr)}
.res .debe .v{color:var(--red)}
.res .debe.cero .v{color:var(--gr)}
.res .tod{flex:1.2 1 190px;background:var(--card-alt)}
.res .tod .v{font-size:13.5px;font-weight:600;color:var(--text2)}

/* === FILTROS: qué mirar. Apagados, todos iguales; prendido, relleno. === */
.filtros{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px}
.filtros[hidden]{display:none}
.filtros button{display:inline-flex;align-items:center;gap:7px;padding:6px 12px;border:1px solid var(--border);border-radius:999px;background:var(--card);font-size:12.5px;font-weight:600;color:var(--text2);cursor:pointer;white-space:nowrap}
.filtros button i{font-style:normal;font-weight:800;color:var(--text3);font-variant-numeric:tabular-nums}
.filtros button.on{background:var(--dk);border-color:var(--dk);color:var(--cr)}
.filtros button.on i{color:var(--gd)}
[data-theme="dark"] .filtros button.on{background:var(--gd);border-color:var(--gd);color:#1A1816}
[data-theme="dark"] .filtros button.on i{color:#1A1816}

.aviso-p{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px;padding:8px 14px;border:1px dashed var(--amb);border-radius:var(--radius);background:var(--amb-soft);color:var(--amb);font-size:13px;font-weight:600}
.aviso-p[hidden]{display:none}
.aviso-s{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px;padding:8px 14px;border:1px solid var(--border);border-radius:var(--radius);background:var(--card);color:var(--text2);font-size:13px}
.aviso-s b{color:var(--text)}
.aviso-s .btn{margin-left:auto}
.aviso-s[hidden]{display:none}
.m-chk{display:flex;flex-wrap:wrap;gap:4px 14px;margin-top:6px;font-size:12px;color:var(--text3)}
.m-chk span::before{content:'';display:inline-block;width:7px;height:7px;margin-right:6px;border-radius:50%;border:1.5px solid var(--text3);vertical-align:1px}
.m-chk span.si{color:var(--gr)}
.m-chk span.si::before{background:var(--gr);border-color:var(--gr)}
.res .v small{display:block;margin-top:1px;font-size:11.5px;font-weight:500;color:var(--text3)}
.aviso-p .btn{margin-left:auto;border-color:var(--amb);color:var(--amb);background:transparent}

/* === PLANILLA: un renglón por contrato, columnas fijas, números alineados === */
.lista{--cols:74px 72px minmax(0,1.7fr) minmax(0,1.5fr) 108px 108px 114px 42px 42px 64px 172px minmax(0,1.25fr) 30px;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow)}
.fila,.cab,.pie{display:grid;grid-template-columns:var(--cols);gap:0 14px;align-items:center;padding:9px 16px}
.cab{position:sticky;top:0;z-index:5;padding-top:9px;padding-bottom:9px;background:var(--card-alt);border-bottom:1px solid var(--border);border-radius:var(--radius) var(--radius) 0 0;font-size:10px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--text3)}
.cab>div{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cab button{padding:0;border:none;background:none;font:inherit;letter-spacing:inherit;text-transform:inherit;color:inherit;cursor:pointer}
.cab button.on{color:var(--text)}
.cab .der,.fila .der,.pie .der{text-align:right}
.cab .cen,.fila .cen{text-align:center}
.fila{min-height:54px;border-top:1px solid var(--border-soft);cursor:pointer;min-width:0;font-size:13.5px;color:var(--text2);transition:background .12s}
.cab+.fila{border-top:none}
.fila>div{min-width:0}
.c-fec{font-weight:600;color:var(--text);font-variant-numeric:tabular-nums;white-space:nowrap}
.c-fec small{display:block;font-size:11px;font-weight:500;color:var(--text3);text-transform:capitalize}
.c-nro{font-weight:600;color:var(--text2);font-variant-numeric:tabular-nums;white-space:nowrap}
.c-nro .falta{font-size:11.5px;color:var(--amb)}
.c-nro small{display:block;font-size:11px;font-weight:500;color:var(--text3)}
.f-nom{font-size:14px;font-weight:700;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.f-sub{font-size:11.5px;color:var(--text3);margin-top:1px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.f-sub .tel{color:var(--text2);font-weight:600;text-decoration:none;font-variant-numeric:tabular-nums}
.f-mat{color:var(--text2);font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.plata{font-weight:600;color:var(--text);font-variant-numeric:tabular-nums;line-height:1.35}
.plata span{display:block;white-space:nowrap}
.plata.nada{color:var(--text3);font-weight:500}
.plata.debe{color:var(--red);font-weight:700}
.plata.ok{color:var(--gr);font-weight:700}
/* sí / no / sin dato: se lee de un vistazo, sin color fuerte */
.sn{font-size:12px;font-weight:700;color:var(--text3)}
.sn.si{color:var(--gr)}
.sn.no{color:var(--text2)}
.c-not{font-size:12px;color:var(--amb);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.nota-i{display:none}
.solo-t{display:none}
.rot{display:none}
.tab{display:flex;flex-direction:column;align-items:flex-start;gap:3px}
.etapa{display:inline-flex;align-items:center;gap:7px;max-width:100%;padding:4px 11px;border-radius:999px;background:var(--et-soft);color:var(--et);font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap}
.etapa::before{content:'';width:8px;height:8px;border-radius:50%;background:var(--et);flex-shrink:0}
.etapa.fuera{--et:var(--text3);--et-soft:var(--card-alt)}
.etapa.rota{--et:var(--red);--et-soft:var(--red-soft,rgba(176,58,58,.1))}
.tab [data-desenlazar]{margin-top:6px}
.tab small.sena-d{color:var(--amb);font-weight:700;cursor:help}
.nota-t.sena-d{background:var(--amb-soft);color:var(--amb)}
.nota-t.sena-d b{font-weight:800}
.tab small{font-size:11px;color:var(--text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%}
.tab small.rojo{color:var(--red);font-weight:700}
.tab .btn.chico{padding:5px 11px;font-size:12px}
.btn.enl-b{display:inline-flex;align-items:center;gap:6px;border-color:var(--gr);color:var(--gr)}
.btn.enl-b svg{width:13px;height:13px}
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
/* Los puntitos de cada renglón: copiar el N°, el teléfono o la dirección, y borrar. */
.mas{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;padding:0;border:none;border-radius:50%;background:transparent;color:var(--text3);cursor:pointer}
.mas svg{width:18px;height:18px}
.mas[aria-expanded="true"]{background:var(--card-alt);color:var(--text)}
.menu{position:fixed;z-index:800;min-width:232px;max-width:calc(100vw - 16px);padding:6px;border:1px solid var(--border);border-radius:var(--radius);background:var(--card);box-shadow:var(--shadow-lg)}
.menu[hidden]{display:none}
.menu button{display:flex;align-items:baseline;justify-content:space-between;gap:14px;width:100%;padding:9px 11px;border:none;border-radius:var(--radius-sm);background:none;font-size:13.5px;font-weight:600;color:var(--text);text-align:left;cursor:pointer}
.menu button small{font-size:12px;font-weight:500;color:var(--text3);font-variant-numeric:tabular-nums;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:150px}
.menu button:disabled{color:var(--text3);cursor:default}
.menu .sep{height:1px;margin:5px 4px;background:var(--border-soft)}
.menu .pel{color:var(--red)}
/* Papelera: lista de contratos borrados, cada uno con su botón para recuperarlo */
.pap{margin-top:4px;border:1px solid var(--border);border-radius:var(--radius-sm);max-height:52vh;overflow-y:auto}
.pap-f{display:flex;align-items:center;gap:12px;padding:10px 12px;border-top:1px solid var(--border-soft)}
.pap-f:first-child{border-top:none}
.pap-f>div{flex:1;min-width:0}
.pap-f b{display:block;font-size:14px;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pap-f small{display:block;margin-top:2px;font-size:12px;color:var(--text3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pap-f .btn{flex-shrink:0;border-color:var(--gr);color:var(--gr)}
/* Pie de la planilla: la suma de lo que se está viendo, debajo de cada columna */
.pie{border-top:2px solid var(--border);border-radius:0 0 var(--radius) var(--radius);background:var(--card-alt);font-size:13px;color:var(--text2)}
.pie .q{grid-column:1/5;font-weight:700;color:var(--text)}
.pie .plata{font-weight:700}
/* Sello de VERIFICADO: el contrato tiene adjunto el PDF o Excel del presupuesto. Dorado, como un sello. */
.ver{display:inline-flex;align-items:center;gap:5px;color:var(--gdd);font-size:10.5px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;white-space:nowrap;vertical-align:middle}
[data-theme="dark"] .ver{color:var(--gd)}
.ver svg{width:16px;height:16px;flex-shrink:0}
.ver .tilde{fill:none;stroke:var(--card);stroke-width:2.3;stroke-linecap:round;stroke-linejoin:round}
.ver[hidden]{display:none}
.f-nom .ver{margin-left:6px;vertical-align:-3px}
.m-cab h3 .ver{margin-left:8px;font-family:'Manrope',sans-serif;vertical-align:2px}
.m-cab h3 .ver svg{width:19px;height:19px}
.c-nro .duda{margin-left:4px;color:var(--amb);font-weight:800;cursor:help}
/* El presupuesto del contrato: el N° lo enlaza con Presupuestos; el archivo lo verifica. */
.pres{margin-top:14px;padding:12px 14px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--card-alt);font-size:13px;color:var(--text2);line-height:1.5}
.pres>div+div{margin-top:10px;padding-top:10px;border-top:1px solid var(--border)}
.pres .t{display:block;margin-bottom:3px;font-size:10px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--text3)}
.pres b{color:var(--text);font-weight:700;overflow-wrap:anywhere}
.pres a{color:var(--text);font-weight:700}
.pres .gris{color:var(--text3)}
.pres .amb{color:var(--amb);font-weight:600}
.pres .lin{display:flex;align-items:center;flex-wrap:wrap;gap:6px 10px}
.pres .lnk{padding:0;border:none;background:none;font:inherit;font-weight:700;color:var(--red);cursor:pointer}
.pres .btn.chico{background:var(--card)}
/* Distintivo: este contrato ya está enlazado con el tablero (y por eso también con el calendario). */
.enl{display:inline-flex;align-items:center;gap:5px;font-size:10px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:var(--gr);white-space:nowrap}
.enl svg{width:12px;height:12px;flex-shrink:0}
.tab small .enl{vertical-align:-1px}
.enl.roto{color:var(--text3)}
.nota-t.rota .enl.roto{color:var(--red)}
.nota-t .lnk{border:none;background:none;padding:0;font:inherit;font-weight:700;color:var(--red);text-decoration:underline;cursor:pointer}
.nota-t .enl{white-space:normal;margin-bottom:3px}
.tab .fila-e{display:flex;align-items:center;flex-wrap:wrap;gap:6px 8px;max-width:100%}
.fila.enlazada{box-shadow:inset 3px 0 0 var(--gr)}
/* La nota en las tarjetas (tablet y celular): mismo ámbar que las notas del tablero */
.f-nota{display:none}
.prueba-t{display:inline-block;margin-left:6px;padding:1px 7px;border-radius:999px;background:var(--amb-soft);color:var(--amb);font-size:10px;font-weight:800;letter-spacing:.06em;vertical-align:middle}
.vacio{padding:48px 20px;text-align:center;color:var(--text2);font-size:14.5px;line-height:1.6}
.vacio h2{font-family:'Fraunces',serif;font-size:24px;font-weight:600;color:var(--text);margin-bottom:6px}
.vacio .btns-v{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:18px}
.error{padding:14px 16px;border-radius:var(--radius);background:var(--red-soft);color:var(--red);font-weight:600;margin-bottom:12px}
.error[hidden]{display:none}

/* === VENTANA === */
.modal-bg{position:fixed;inset:0;background:rgba(10,9,7,.55);display:none;align-items:center;justify-content:center;padding:16px;z-index:900}
.modal-bg.show{display:flex}
.modal{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-lg);--mp:26px;padding:var(--mp);max-width:660px;width:100%;max-height:calc(100vh - 32px);overflow-y:auto}
.modal.chica{max-width:440px}
.modal h3{font-family:'Fraunces',serif;font-size:22px;font-weight:600;line-height:1.25;margin-bottom:6px;overflow-wrap:anywhere}
.m-cab{display:flex;align-items:flex-start;gap:12px;margin-bottom:14px}
.m-cab>div{flex:1;min-width:0}
.m-cab h3{margin-bottom:2px}
.m-sub{font-size:12.5px;color:var(--text3)}
.m-cab .etapa{margin-top:3px;flex-shrink:0}
/* Total, cobrado y saldo: siempre arriba, se recalculan mientras se escribe */
.m-res{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1px;border:1px solid var(--border);border-radius:var(--radius);background:var(--border);overflow:hidden}
.m-res>div{min-width:0;padding:11px 14px 12px;background:var(--card-alt)}
.m-res .t{font-size:10px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--text3)}
.m-res b{display:block;margin-top:5px;font-family:'Fraunces',serif;font-size:18px;font-weight:600;line-height:1.3;color:var(--text);font-variant-numeric:lining-nums tabular-nums;overflow-wrap:anywhere}
.m-res b.nada{color:var(--text3)}
.m-res b.debe{color:var(--red)}
.m-res b.ok{color:var(--gr)}
/* Notas: acá van los detalles del contrato. Mismo ámbar que las notas del tablero. */
.campo.notas textarea{min-height:104px;padding:12px 14px;border-color:color-mix(in srgb,var(--amb) 30%,var(--border));background:var(--amb-soft);font-size:14.5px;line-height:1.55;resize:vertical}
.campo.notas textarea::placeholder{color:var(--amb);opacity:.65}
.modal .bajada{font-size:13px;color:var(--text2);line-height:1.5;margin-bottom:14px}
.modal h4{margin:24px 0 0;padding-top:18px;border-top:1px solid var(--border-soft);font-family:'Fraunces',serif;font-size:16px;font-weight:600;color:var(--text)}
.campo{display:block;margin-top:14px;font-size:12px;font-weight:700;color:var(--text2)}
.campo i{font-style:normal;font-weight:500;color:var(--text3)}
.campo i a{color:var(--gr);font-weight:700;text-decoration:none}
.campo i[hidden]{display:none}
.campo input,.campo select,.campo textarea{display:block;width:100%;margin-top:6px;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--card);color:var(--text);font-family:inherit;font-size:15px;font-weight:500;outline:none}
.campo textarea{resize:vertical;min-height:64px;line-height:1.45}
.campo input:focus,.campo select:focus,.campo textarea:focus{border-color:var(--gd);box-shadow:0 0 0 3px var(--gd-soft)}
.campo input[readonly]{background:var(--card-alt);color:var(--text2)}
.campo[hidden]{display:none}
.campo.cot input{max-width:220px}
.pista{margin-top:8px;font-size:12.5px;color:var(--text3)}
.pista.baja{color:var(--amb);font-weight:600}
.pista[hidden]{display:none}
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
.modal .btns .izq-n{margin-right:auto}
.modal .btns button:disabled{opacity:.6;cursor:default}
.m-err{margin-top:12px;padding:9px 12px;border-radius:var(--radius-sm);background:var(--red-soft);color:var(--red);font-size:13px;font-weight:600}
.m-err[hidden]{display:none}
.toast{position:fixed;left:50%;top:calc(14px + env(safe-area-inset-top));transform:translateX(-50%);z-index:950;max-width:calc(100vw - 32px);padding:11px 18px;border-radius:999px;background:var(--dk);color:var(--cr);font-size:13.5px;font-weight:600;box-shadow:var(--shadow-lg);opacity:0;pointer-events:none;transition:opacity .2s}
.toast.show{opacity:1}
[data-theme="dark"] .toast{background:var(--gd);color:#1A1816}
@media(hover:hover){
  .fila:hover{background:var(--gd-soft)}
  .mas:hover{background:var(--card-alt);color:var(--text)}
  .menu button:not(:disabled):hover{background:var(--card-alt)}
  .menu .pel:hover{background:var(--red-soft)}
  .btn:hover,.mes button:not(:disabled):hover,.filtros button:not(.on):hover{border-color:var(--gd)}
  .cab button:hover{color:var(--text)}
  .f-sub .tel:hover{color:var(--gr);text-decoration:underline}
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
  .mes button{height:42px;min-width:42px}
  .opc button{padding:12px 6px}
  .pago button{width:40px;height:40px}
}
/* Pantallas medianas: primero se va la columna de notas (queda un punto al lado del nombre),
   después factura / muebles / pileta. Todo sigue estando en la ficha. */
@media(max-width:1519px){
  .lista{--cols:74px 72px minmax(0,1.7fr) minmax(0,1.5fr) 108px 108px 114px 42px 42px 64px 172px 30px}
  .c-not,.cab .h-not{display:none}
  .nota-i{display:inline-block;width:7px;height:7px;margin-left:7px;border-radius:50%;background:var(--amb);vertical-align:middle}
}
@media(max-width:1199px){
  .lista{--cols:70px 68px minmax(0,1.6fr) minmax(0,1.3fr) 100px 100px 106px 172px 30px}
  .c-sn,.cab .h-sn{display:none}
  .fila,.cab,.pie{gap:0 12px;padding-left:14px;padding-right:14px}
}
/* Tablet y celular: cada contrato es una tarjeta, con el rótulo al lado de cada dato. */
@media(max-width:1099px){
  .acc{flex:1 1 100%;margin-left:0}
  .buscar{max-width:none}
  .res>div{flex:1 1 40%;border-top:1px solid var(--border-soft)}
  .res>div:first-child{flex:1 1 40%}
  .res>div:nth-child(-n+2){border-top:none}
  .res>div:nth-child(odd){border-left:none}
  .res .tod{flex:1 1 100%;border-left:none}
  .res .v{white-space:normal;overflow-wrap:anywhere}
  .cab,.pie{display:none}
  .lista{background:none;border:none;box-shadow:none;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;align-items:start}
  .lista>.vacio{grid-column:1/-1;background:var(--card);border:1px solid var(--border);border-radius:var(--radius)}
  .fila{grid-template-columns:repeat(3,minmax(0,1fr));gap:10px 12px;align-items:start;min-height:0;padding:16px;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow)}
  .fila>.c-fec,.fila>.c-nro,.fila>.c-sn,.fila>.c-not{display:none}
  .fila{position:relative}
  .fila>.c-mas{position:absolute;top:8px;right:8px}
  .mas{width:40px;height:40px}
  .fila>.c-cli{padding-right:36px}
  .menu button{padding:12px 12px;font-size:14.5px}
  .solo-t{display:inline}
  .nota-i{display:none}
  .fila>.c-cli,.fila>.c-mat,.fila>.c-tab,.f-nota,.fila>.c-obra{grid-column:1/-1}
  .f-nom{font-size:15.5px}
  .f-sub{font-size:12.5px;margin-top:3px}
  .f-mat{font-size:14px}
  .plata{font-size:14.5px}
  .plata span{white-space:normal}
  .fila .der{text-align:left;min-width:0}
  .rot{display:block;margin-bottom:2px;font-size:10px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:var(--text3)}
  .c-obra{font-size:12.5px;color:var(--text3)}
  .c-obra b{font-weight:700;color:var(--text2)}
  .f-nota{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;padding:7px 11px;border-radius:var(--radius-sm);background:var(--amb-soft);color:var(--amb);font-size:12.5px;font-weight:600;line-height:1.45}
  .c-tab{padding-top:12px;border-top:1px solid var(--border-soft)}
  .tab{flex-direction:row;flex-wrap:wrap;align-items:center;gap:6px 10px}
  .tab .btn.chico{padding:10px 14px;font-size:13px}
  .etapa{padding:6px 12px;font-size:12.5px}
  .fila.enlazada{box-shadow:var(--shadow);border-left:3px solid var(--gr)}
  .filtros{flex-wrap:nowrap;overflow-x:auto;margin-left:-12px;margin-right:-12px;padding:0 12px;scrollbar-width:none}
  .filtros::-webkit-scrollbar{display:none}
  .filtros button{flex-shrink:0;padding:9px 14px;font-size:13px}
}
@media(min-width:1100px){.c-obra{display:none}}
@media(max-width:639px){
  .mes{flex:1 1 100%}
  .mes h2{flex:1;min-width:0;font-size:21px}
  .lista{grid-template-columns:minmax(0,1fr)}
  .acc{flex-wrap:wrap}
  .acc .btn.pri{flex:1 1 100%}
  .buscar{flex-basis:100%}
  .res>div{padding:9px 14px 10px}
  .res .v{font-size:14.5px}
  .dos,.tres{grid-template-columns:minmax(0,1fr)}
  .m-res b{font-size:15.5px}
  .m-res>div{padding:10px 10px 11px}
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
  <a href="/presupuestos/">Presupuestos</a>
  <a href="/presupuestos/tablero.php">Tablero</a>
  <a href="/presupuestos/calendario.php">Calendario</a>
  <a href="/presupuestos/relacion-contrato.php" class="active">Relación-Contrato</a>
  <a href="/presupuestos/stock.php">Stock</a>
</nav>
<div class="gold-line"></div>

<div class="wrap">
  <div class="error" id="err" hidden></div>
  <div class="barra">
    <div class="mes">
      <button type="button" id="mesAnt" aria-label="Mes anterior">‹</button>
      <h2 id="mesTxt"></h2>
      <button type="button" id="mesSig" aria-label="Mes siguiente">›</button>
      <button type="button" class="hoy-b" id="mesHoy">Este mes</button>
    </div>
    <div class="acc">
      <div class="buscar"><input type="search" id="buscar" placeholder="Buscar en todos los meses" autocomplete="off" aria-label="Buscar cliente, N° o material en todos los meses"></div>
      <button type="button" class="btn" id="papB" data-papelera title="Contratos borrados: se pueden recuperar durante 30 días" hidden></button>
      <button type="button" class="btn pri" data-nuevo>+ Nuevo contrato</button>
    </div>
  </div>
  <div class="res" id="res" hidden></div>
  <div class="filtros" id="filtros" hidden></div>
  <div class="aviso-p" id="avisoP" hidden></div>
  <div class="aviso-s" id="avisoS" hidden></div>
  <div class="lista" id="lista"><div class="vacio">Cargando…</div></div>
</div>

<div class="modal-bg" id="modalBg"><div class="modal" id="modal" role="dialog" aria-modal="true"></div></div>
<div class="menu" id="menu" role="menu" hidden></div>
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
const API='/calculadora/api/contratos.php',API_T='/calculadora/api/tablero.php',API_CARGAR='/calculadora/api/tablero-cargar.php',API_DEL='/calculadora/api/delete.php',API_COLORES='/calculadora/api/colors-db.php',API_ADJ='/calculadora/api/contratos-adjunto.php',API_PAP_T='/calculadora/api/tablero-papelera.php';
let CONTRATOS=[],CARDS={},ETAPAS={},HOY='',TABLERO_OK=false,q='';
let VENDEDORES=[],PROCEDENCIAS=[];
let EN_PAPELERA=0; // contratos borrados que todavía se pueden recuperar (30 días) // listas cerradas: las manda el servidor (se editan en contratos.php)
let mes=''; // mes a la vista, 'YYYY-MM' (por la fecha del contrato)
let filtro='todos',orden='fecha',ordenAsc=false; // qué se mira y cómo se ordena la planilla
const MESES=['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
const mesTxt=m=>MESES[+m.slice(5,7)-1].replace(/^./,x=>x.toUpperCase())+' '+m.slice(0,4);
// Sello de verificado: una roseta con tilde.
const ICO_VER='<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 1.6l2.5 1.9 3.1-.4 1.2 2.9 2.9 1.2-.4 3.1 1.9 2.5-1.9 2.5.4 3.1-2.9 1.2-1.2 2.9-3.1-.4-2.5 1.9-2.5-1.9-3.1.4-1.2-2.9-2.9-1.2.4-3.1L.8 12.8l1.9-2.5-.4-3.1 2.9-1.2 1.2-2.9 3.1.4z"/><path class="tilde" d="M7.9 12.6l2.8 2.8 5.5-5.9"/></svg>';
const kb=n=>n>=1048576?(n/1048576).toFixed(1).replace('.',',')+' MB':Math.max(1,Math.round(n/1024))+' KB';
const ICO_MAS='<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.9"/><circle cx="12" cy="12" r="1.9"/><circle cx="19" cy="12" r="1.9"/></svg>';
const ICO_ENL='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 007.07 0l3-3a5 5 0 00-7.07-7.07l-1.5 1.5"/><path d="M14 11a5 5 0 00-7.07 0l-3 3a5 5 0 007.07 7.07l1.5-1.5"/></svg>';
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

// --- teléfonos ---
// Valen los dos formatos: "+54 9 11 5555-1234" (como se copia de WhatsApp, a veces con caracteres
// invisibles) y "1155551234". Los dos son el mismo número: se guarda y se muestra "11 5555-1234".
function telDigitos(s){
  let d=String(s||'').replace(/\D/g,'');
  if(d.length===13&&d.indexOf('549')===0)d=d.slice(3);       // +54 9 11 5555-1234
  else if(d.length===12&&d.indexOf('54')===0)d=d.slice(2);   // +54 11 5555-1234
  else if(d.length===11&&d[0]==='0')d=d.slice(1);            // 011 5555-1234
  if(d.length===12&&d.indexOf('1115')===0)d='11'+d.slice(4); // 11 15 5555-1234
  return d;
}
function telLindo(s){
  const d=telDigitos(s);
  if(d.length===10)return d.indexOf('11')===0?'11 '+d.slice(2,6)+'-'+d.slice(6):d.slice(0,3)+' '+d.slice(3,6)+'-'+d.slice(6);
  // no se reconoce (interno, de otro país, incompleto): queda como se escribió, sin caracteres raros
  return String(s||'').replace(/[^\d+()\-\s]/g,'').replace(/\s+/g,' ').trim();
}
function waLink(s){
  const d=telDigitos(s);
  if(d.length===10)return 'https://wa.me/549'+d;
  const t=String(s||'').replace(/\D/g,'');
  return /^\s*\+/.test(String(s||''))&&t.length>=10?'https://wa.me/'+t:'';
}

// --- enlace con el tablero ---
const keyDe=c=>c.enlace?c.enlace.cliente_nro+'-'+c.enlace.sub:'';
const cardDe=c=>c.enlace?(CARDS[keyDe(c)]||null):null;
// ¿Ese N° de presupuesto ya está cargado en el tablero? Devuelve su ficha si hay una sola
// y no está enlazada con otro contrato. Con eso el renglón ofrece "Enlazar con el tablero".
// Las fichas cargadas a mano no tienen N° de presupuesto: por número no se buscan.
const conContrato=(key,exceptoId)=>CONTRATOS.some(c=>c.id!==exceptoId&&c.enlace&&keyDe(c)===key);
function cardDeNro(nro,exceptoId){
  const m=/^(\d{1,6})(?:-(\d{1,3}))?$/.exec(String(nro||'').trim());
  if(!m)return null;
  const n=String(+m[1]).padStart(4,'0'),s=m[2]?+m[2]:0;
  const l=Object.keys(CARDS).map(k=>CARDS[k]).filter(k=>!k.manual&&k.cliente_nro===n&&(!s||k.sub===s));
  if(l.length!==1)return null;
  return conContrato(l[0].key,exceptoId)?null:l[0];
}
// La ficha de un contrato suelto: por su N° de presupuesto o, si no, por el teléfono (los diez
// dígitos iguales y una sola ficha sin contrato con ese teléfono). Devuelve {k, por} o null.
function fichaPara(c){
  if(!c||c.enlace)return null;
  const kn=c.nro?cardDeNro(c.nro,c.id):null;
  if(kn)return {k:kn,por:'nro'};
  const t=telDigitos(c.telefono);
  if(t.length!==10)return null;
  const l=Object.keys(CARDS).map(k=>CARDS[k]).filter(k=>telDigitos(k.celular)===t&&!conContrato(k.key,c.id));
  return l.length===1?{k:l[0],por:'tel'}:null;
}
// Cómo se nombra una obra del tablero: por su N° de presupuesto, o "cargada a mano" si no tiene.
const obraTxt=k=>k.manual?'cargada a mano, sin N° de presupuesto':k.key;
// La ficha con la que estaba enlazado un contrato y que ya no está. Los números de 90001 en adelante son internos (fichas a mano).
const fichaIda=c=>+c.enlace.cliente_nro>=90000?'una ficha cargada a mano':'la ficha '+keyDe(c);
// Cómo se ve un contrato: enlazado y con la ficha en el tablero, lo operativo sale de la ficha.
function ver(c){
  const k=cardDe(c);
  if(!k)return c;
  return Object.assign({},c,{
    cliente:k.nombre||c.cliente,telefono:telLindo(k.celular)||c.telefono,direccion:k.direccion||c.direccion,material:k.material||c.material,
    bacha:k.bacha||c.bacha,bacha_modelo:k.bacha?k.bacha_modelo:c.bacha_modelo
    // la seña NO: la del contrato es la del contrato. Si la del tablero es otra, se avisa (senaDif).
  });
}
// Un cobro se descuenta del total de su misma moneda, salvo que tenga cotización: ahí se
// convierte y se descuenta del total en la otra (lo habitual: contrato en pesos, pago en dólares).
function aplicar(p,x){
  if(x.aplica&&x.aplica!==x.moneda&&x.cotizacion>0)p[x.aplica]+=x.moneda==='USD'?x.monto*x.cotizacion:x.monto/x.cotizacion;
  else p[x.moneda]+=x.monto;
}
// tot: el contrato · pag: lo que entró de verdad, en cada moneda · sal: lo que falta, ya convertido
function cuentas(c,v){
  v=v||ver(c);
  const p={USD:0,ARS:0},a={USD:0,ARS:0},r=x=>Math.round(x*100)/100;
  (v.sena?[v.sena]:[]).concat(c.pagos||[]).forEach(x=>{p[x.moneda]+=x.monto;aplicar(a,x);});
  return {tot:{USD:v.total_usd,ARS:v.total_ars},pag:{USD:r(p.USD),ARS:r(p.ARS)},sal:{USD:r(v.total_usd-a.USD),ARS:r(v.total_ars-a.ARS)}};
}
// De qué total se descuenta un cobro en esa moneda: del suyo si todavía hay saldo ahí;
// si no, del otro (y entonces hace falta la cotización).
function destino(moneda,tot,sal){
  const otra=moneda==='USD'?'ARS':'USD';
  if(tot[moneda]>0&&(sal[moneda]>0.004||!(tot[otra]>0)))return moneda;
  return tot[otra]>0?otra:moneda;
}
// El dólar del día, el mismo que usa la calculadora: es solo el valor que se propone; se puede cambiar.
let DOLAR=0;
fetch('/calculadora/dolar.php',{cache:'no-store'}).then(r=>r.json()).then(j=>{DOLAR=Math.round(+j.venta||0);if(F)pintarPagos();}).catch(()=>{});
const cambioTxt=x=>x.aplica&&x.aplica!==x.moneda&&x.cotizacion>0?' · a $ '+NF.format(x.cotizacion)+' = '+plata(x.moneda==='USD'?x.monto*x.cotizacion:x.monto/x.cotizacion,x.aplica):'';
const partes=o=>['USD','ARS'].filter(m=>Math.abs(o[m])>0.004).map(m=>plata(o[m],m));
// Saldo: lo que falta cobrar en cada moneda. Si se cobró de más en una, queda "a favor".
function saldoTxt(n){
  if(!n.tot.USD&&!n.tot.ARS)return {cls:'nada',l:['Sin total']};
  const l=['USD','ARS'].filter(m=>Math.abs(n.sal[m])>0.004).map(m=>n.sal[m]>0?plata(n.sal[m],m):plata(-n.sal[m],m)+' a favor');
  if(!l.length)return {cls:'ok',l:['Pagado']};
  return {cls:['USD','ARS'].some(m=>n.sal[m]>0.004)?'debe':'ok',l:l};
}
// Enlazado, pero la ficha no está entre las obras vivas del tablero. Se dice por qué.
// Archivada es lo normal al terminar la obra; "no existe" es un enlace roto y se puede desenlazar.
const FUERA={archivada:['Archivada','Obra entregada. El contrato queda.'],papelera:['En la papelera','Su ficha está en la papelera del tablero.']};
function sinFichaHtml(c){
  if(!TABLERO_OK)return '<div class="fila-e"><span class="etapa fuera">Sin datos del tablero</span></div><small>No se pudo leer el tablero</small>';
  const f=FUERA[c.ficha];
  if(f)return '<div class="fila-e"><span class="etapa fuera">'+f[0]+'</span></div><small>'+f[1]+'</small>';
  return '<div class="fila-e"><span class="etapa rota">No existe en el tablero</span></div><small class="rojo">'+(+c.enlace.cliente_nro>=90000?'Era una ficha cargada a mano':'La ficha '+esc(keyDe(c))+' ya no está')+'</small><button type="button" class="btn chico" data-desenlazar="'+esc(c.id)+'">Desenlazar</button>';
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
    CONTRATOS=a.contratos;HOY=a.hoy;VENDEDORES=a.vendedores||[];PROCEDENCIAS=a.procedencias||[];EN_PAPELERA=a.papelera||0;
    $('papB').hidden=!EN_PAPELERA;$('papB').textContent='Papelera · '+EN_PAPELERA;
    if(!mes)mes=HOY.slice(0,7);
    TABLERO_OK=!!t;CARDS={};ETAPAS={};
    if(t){t.cards.forEach(k=>{CARDS[k.key]=k;});t.etapas.forEach(e=>{ETAPAS[e.id]=e.titulo;});}
    $('err').hidden=true;
    dibujar();
  }catch(e){$('err').textContent=e.message;$('err').hidden=false;$('lista').innerHTML='<div class="vacio">No se pudo cargar la lista.</div>';}
}
// La seña del tablero y la del contrato no se pisan nunca (dueño, 17/11/2026): si difieren, se avisa.
// No es diferencia que sea la misma plata dicha de otra forma: la seña en dólares con su cotización
// contra la del tablero en pesos, o el tablero con todo lo cobrado anotado como seña.
// Devuelve null si coinciden, o {t: la del tablero, s: la del contrato, txt}.
function senaDif(c){
  const k=cardDe(c);
  if(!k)return null;
  const t=k.sena||null,s=c.sena||null,igual=(a,b)=>Math.abs(a-b)<1;
  if(!t&&!s)return null;
  if(t&&s){
    if(t.moneda===s.moneda&&igual(t.monto,s.monto))return null;
    const a={USD:0,ARS:0};aplicar(a,s);
    if(igual(a[t.moneda],t.monto))return null;
  }
  if(t){
    const a={USD:0,ARS:0};(s?[s]:[]).concat(c.pagos||[]).forEach(x=>aplicar(a,x));
    if(a[t.moneda]>0&&igual(a[t.moneda],t.monto))return null;
  }
  const txt=t&&s?'En el tablero la seña figura '+plata(t.monto,t.moneda)+'; acá, '+plata(s.monto,s.moneda)+'.'
    :(t?'En el tablero figura una seña de '+plata(t.monto,t.moneda)+' y acá no hay ninguna cargada.':'Acá hay una seña de '+plata(s.monto,s.moneda)+' y en el tablero no está tildada.');
  return {t:t,s:s,txt:txt};
}
// Obras vivas en el tablero que no tienen contrato acá. Con el tablero sin leer, no se sabe: ninguna.
const sinContrato=()=>{const con={};CONTRATOS.forEach(c=>{if(c.enlace)con[keyDe(c)]=1;});return Object.keys(CARDS).map(k=>CARDS[k]).filter(k=>!con[k.key]).sort((a,b)=>llano(a.nombre)<llano(b.nombre)?-1:1);};
// Un contrato cierra limpio con las tres cosas: N°, presupuesto adjunto y enlazado con el tablero.
const completo=c=>!!(c.nro&&c.archivo&&c.enlace);
const sumar=(l)=>{const s={tot:{USD:0,ARS:0},pag:{USD:0,ARS:0},deb:{USD:0,ARS:0},conSaldo:0};l.forEach(c=>{const n=cuentas(c);let debe=false;['USD','ARS'].forEach(m=>{s.tot[m]+=n.tot[m];s.pag[m]+=n.pag[m];if(n.sal[m]>0.004){s.deb[m]+=n.sal[m];debe=true;}});if(debe)s.conSaldo++;});return s;};
const debe=c=>{const n=cuentas(c);return n.sal.USD>0.004||n.sal.ARS>0.004;};
const pagado=c=>{const n=cuentas(c);return (n.tot.USD>0||n.tot.ARS>0)&&!debe(c);};
// Filtros de control: lo que hay que perseguir.
const FILTROS=[['todos','Todos',()=>true],['saldo','Con saldo',debe],['pagados','Pagados',pagado],['sueltos','Sin cargar al tablero',c=>!c.enlace],['sinver','Sin verificar',c=>!c.archivo],['sinnro','Sin N°',c=>!c.nro],['completos','Completos',completo],['sena','Seña distinta',c=>!!senaDif(c),true]];
const SN=v=>v==='si'?'<span class="sn si">Sí</span>':(v==='no'?'<span class="sn no">No</span>':(v==='cf'?'<span class="sn si" title="Factura a consumidor final">CF</span>':'<span class="sn">—</span>'));
const PIL={nuestra:'Nuestra',cliente:'Cliente',no:'No'};

function dibujar(){
  const pr=CONTRATOS.filter(c=>c.prueba).length;
  $('avisoP').hidden=!pr;
  if(pr)$('avisoP').innerHTML='<span>'+(pr===1?'Hay 1 renglón de prueba.':'Hay '+pr+' renglones de prueba.')+' No son contratos reales y cuentan en los números hasta que los borres.</span><button type="button" class="btn chico" data-borrar-prueba>Borrar los de prueba</button>';
  const sc=sinContrato();
  $('avisoS').hidden=!sc.length;
  if(sc.length)$('avisoS').innerHTML='<span>'+(sc.length===1?'Hay <b>1 obra</b> en el tablero sin contrato.':'Hay <b>'+sc.length+' obras</b> en el tablero sin contrato.')+'</span><button type="button" class="btn chico" data-sin-contrato>Verlas</button>';
  const t=llano(q).trim(),buscando=!!t;
  const qTel=/^[\d\s+()\-.\u2010-\u2015\u200e\u200f\u202a-\u202e\u00a0]+$/.test(q.trim())&&telDigitos(q).length>=6?telDigitos(q):'';
  $('mesTxt').textContent=buscando?'Todos los meses':mesTxt(mes);
  $('mesAnt').disabled=$('mesSig').disabled=buscando;
  $('mesHoy').hidden=buscando||mes===HOY.slice(0,7);
  if(!CONTRATOS.length){
    $('res').hidden=$('filtros').hidden=true;
    $('lista').innerHTML='<div class="vacio"><h2>Todavía no hay contratos</h2>Acá va un renglón por cada cliente que contrata: el total, la seña y lo que falta cobrar.<br>Desde cada renglón se puede cargar la obra en Por medir del tablero.<div class="btns-v"><button type="button" class="btn pri" data-nuevo>+ Nuevo contrato</button><button type="button" class="btn" data-prueba>Cargar 3 de prueba</button></div></div>';
    return;
  }
  // El mes (por la fecha del contrato). Buscando, todos los meses.
  const delMes=CONTRATOS.filter(c=>{
    if(!buscando)return (c.fecha||'').slice(0,7)===mes;
    const v=ver(c);
    if(qTel&&telDigitos(v.telefono).indexOf(qTel)>=0)return true; // el teléfono, pegado como venga
    return llano([v.cliente,c.nro,keyDe(c),v.material,c.vendedor,c.procedencia,v.telefono,v.direccion,c.contrato,c.contrato_paralelo,c.nota].join(' ')).indexOf(t)>=0;
  });
  // Lo que se ve es lo que se suma: arriba, en los filtros y en el pie. Los de prueba cuentan
  // mientras existan (el cartel lo avisa), así ningún número contradice a otro.
  const base=delMes,s=sumar(base),todo=sumar(CONTRATOS),hayDeuda=partes(s.deb).length>0;
  const linea=o=>partes(o).join(' + ');
  $('res').hidden=false;
  $('res').innerHTML='<div><div class="t">Contratos</div><div class="v">'+base.length+(base.length?'<small title="Completo: con N°, presupuesto adjunto y enlazado con el tablero">'+base.filter(completo).length+' de '+base.length+' completos</small>':'')+'</div></div>'
    +'<div><div class="t">Contratado</div><div class="v'+(linea(s.tot)?'':' nada')+'">'+(linea(s.tot)||'—')+'</div></div>'
    +'<div class="cobr"><div class="t">Cobrado</div><div class="v'+(linea(s.pag)?'':' nada')+'">'+(linea(s.pag)||'—')+'</div></div>'
    +'<div class="debe'+(hayDeuda?'':' cero')+'"><div class="t">Por cobrar</div><div class="v'+(hayDeuda||base.length?'':' nada')+'">'+(linea(s.deb)||(base.length?'Nada':'—'))+'</div></div>'
    +(buscando?'':'<div class="tod"><div class="t">Por cobrar, todos los meses</div><div class="v">'+(linea(todo.deb)||'Nada')+'</div></div>');
  if(!FILTROS.some(f=>f[0]===filtro))filtro='todos';
  $('filtros').hidden=!delMes.length;
  $('filtros').innerHTML=FILTROS.filter(f=>!f[3]||delMes.some(f[2])||filtro===f[0]).map(f=>'<button type="button" data-filtro="'+f[0]+'" class="'+(filtro===f[0]?'on':'')+'" aria-pressed="'+(filtro===f[0])+'">'+f[1]+' <i>'+delMes.filter(f[2]).length+'</i></button>').join('');

  const fx=FILTROS.find(f=>f[0]===filtro)[2];
  const dir=ordenAsc?1:-1;
  const lista=delMes.filter(fx).sort((a,b)=>{
    if(orden==='cliente'){const x=llano(ver(a).cliente),y=llano(ver(b).cliente);if(x!==y)return (x<y?-1:1)*dir;}
    else if(a.fecha!==b.fecha)return (a.fecha<b.fecha?-1:1)*dir;
    return a.creado<b.creado?1:-1;
  });
  if(!delMes.length){
    // mes sin contratos: se ofrece ir al último mes que tiene
    const otros=[...new Set(CONTRATOS.map(c=>(c.fecha||'').slice(0,7)).filter(Boolean))].sort(),ult=otros.filter(m=>m<mes).pop()||otros[0];
    $('lista').innerHTML=buscando?'<div class="vacio">Ningún contrato coincide con la búsqueda.</div>'
      :'<div class="vacio"><h2>Sin contratos en '+esc(MESES[+mes.slice(5,7)-1])+'</h2>Los contratos se ordenan por mes según su fecha.<div class="btns-v"><button type="button" class="btn pri" data-nuevo>+ Nuevo contrato</button>'+(ult?'<button type="button" class="btn" data-ir-mes="'+esc(ult)+'">Ir a '+esc(mesTxt(ult).toLowerCase())+'</button>':'')+'</div></div>';
    return;
  }
  const fl=k=>orden===k?(ordenAsc?' ↑':' ↓'):'';
  let h='<div class="cab"><div><button type="button" data-orden="fecha" class="'+(orden==='fecha'?'on':'')+'" title="Ordenar por fecha">Fecha'+fl('fecha')+'</button></div><div title="Contrato: N° de la calculadora. Debajo, el contrato paralelo.">Contrato</div><div><button type="button" data-orden="cliente" class="'+(orden==='cliente'?'on':'')+'" title="Ordenar por cliente">Cliente'+fl('cliente')+'</button></div><div>Material</div><div class="der">Total</div><div class="der">Cobrado</div><div class="der">Saldo</div><div class="cen h-sn" title="Factura">Fact.</div><div class="cen h-sn" title="Muebles colocados">Mueb.</div><div class="h-sn">Pileta</div><div>Tablero</div><div class="h-not">Notas</div><div></div></div>';
  h+=lista.map(c=>{
    const v=ver(c),n=cuentas(c,v),k=cardDe(c),s=saldoTxt(n);
    const p=(c.fecha||'').split('-').map(Number),dt=p.length===3?new Date(p[0],p[1]-1,p[2]):null;
    const fecha=dt?String(p[2]).padStart(2,'0')+'/'+String(p[1]).padStart(2,'0')+(p[0]!==+HOY.slice(0,4)?'/'+String(p[0]).slice(2):''):'';
    // debajo del nombre: en la planilla, vendedor y procedencia; en tarjeta se suman N° y fecha
    const sub='<span class="solo-t">'+(c.nro?'N° '+esc(c.nro):'<span class="falta">Sin N°</span>')+(c.contrato_paralelo?' · ctto '+esc(c.contrato_paralelo):'')+(fecha?' · '+esc(fch(c.fecha)):'')+([v.telefono,c.vendedor,c.procedencia].some(Boolean)?' · ':'')+'</span>'
      +[v.telefono?(waLink(v.telefono)?'<a class="tel" href="'+esc(waLink(v.telefono))+'" target="_blank" rel="noopener" title="Abrir WhatsApp">'+esc(telLindo(v.telefono))+'</a>':esc(telLindo(v.telefono))):'',esc(c.vendedor),esc(c.procedencia)].filter(Boolean).join(' · ');
    const tot=partes(n.tot),pag=partes(n.pag);
    let tab;
    const sd=senaDif(c);
    const fp=fichaPara(c),kn=fp?fp.k:null; // su obra ya está en el tablero (mismo N° o mismo teléfono): se enlaza, no se carga de nuevo
    if(kn)tab='<button type="button" class="btn chico enl-b" data-enlazar="'+esc(c.id)+'">'+ICO_ENL+'Enlazar con el tablero</button><small>'+(fp.por==='tel'?'Mismo teléfono que una obra del tablero: ':'Ya está en el tablero: ')+esc(ETAPAS[kn.etapa]||kn.etapa)+'</small>';
    else if(!c.enlace)tab='<button type="button" class="btn chico" data-enviar="'+esc(c.id)+'">Cargar en Por medir</button><small>'+(c.medicion?'Medición: '+esc(fch(c.medicion)):'Sin medición coordinada')+'</small>';
    else if(k){const d=etapaDet(k);tab='<a class="etapa et-'+esc(k.etapa)+'" href="/presupuestos/tablero.php?q='+encodeURIComponent(k.key)+'" title="Ver la ficha en el tablero">'+esc(ETAPAS[k.etapa]||k.etapa)+'</a><small'+(d.rojo?' class="rojo"':'')+'><span class="enl" title="Enlazado con el tablero y el calendario">'+ICO_ENL+'Enlazado</span>'+(d.t?' · '+esc(d.t):'')+'</small>'+(sd?'<small class="sena-d" title="'+esc(sd.txt)+'">Seña distinta en el tablero</small>':'');}
    else tab=sinFichaHtml(c);
    const nota=(c.nota||'').replace(/\s+/g,' ').trim();
    const obra=[c.factura?'Factura <b>'+(c.factura==='si'?'sí':(c.factura==='cf'?'cons. final':'no'))+'</b>':'',c.muebles?'Muebles <b>'+(c.muebles==='si'?'sí':'no')+'</b>':'',v.bacha?'Pileta <b>'+esc(PIL[v.bacha].toLowerCase())+'</b>':''].filter(Boolean).join(' · ');
    return '<div class="fila'+(k?' enlazada':'')+'" data-id="'+esc(c.id)+'" tabindex="0" role="button" aria-label="Abrir el contrato de '+esc(v.cliente)+'">'
      +'<div class="c-fec">'+esc(fecha)+(dt?'<small>'+DIAS[dt.getDay()]+'</small>':'')+'</div>'
      +'<div class="c-nro">'+(c.nro?esc(c.nro)+(c.presup&&!c.presup.existe?'<span class="duda" title="Ese N° no está en Presupuestos: o está mal escrito, o ya se borró (los presupuestos duran 180 días).'+(c.archivo?' El contrato conserva el presupuesto adjunto.':'')+'">?</span>':''):'<span class="falta">Sin N°</span>')+(c.contrato_paralelo?'<small title="Contrato paralelo">ctto '+esc(c.contrato_paralelo)+'</small>':'')+'</div>'
      +'<div class="c-cli"><div class="f-nom">'+esc(v.cliente)+(c.archivo?'<span class="ver" title="Verificado: tiene adjunto el presupuesto ('+esc(c.archivo.ext.toUpperCase())+')">'+ICO_VER+'</span>':'')+(c.prueba?'<span class="prueba-t">PRUEBA</span>':'')+(nota?'<span class="nota-i" title="'+esc(nota)+'"></span>':'')+'</div>'+(sub.replace(/<[^>]+>/g,'').trim()?'<div class="f-sub">'+sub+'</div>':'')+'</div>'
      +'<div class="c-mat"><div class="f-mat">'+(esc(v.material)||'<span style="color:var(--text3)">Sin material</span>')+'</div>'+(c.medidas?'<div class="f-sub">'+esc(c.medidas)+'</div>':'')+'</div>'
      +'<div class="der"><span class="rot">Total</span><div class="plata'+(tot.length?'':' nada')+'">'+(tot.length?tot.map(x=>'<span>'+x+'</span>').join(''):'—')+'</div></div>'
      +'<div class="der"><span class="rot">Cobrado</span><div class="plata'+(pag.length?'':' nada')+'">'+(pag.length?pag.map(x=>'<span>'+x+'</span>').join(''):'—')+'</div></div>'
      +'<div class="der"><span class="rot">Saldo</span><div class="plata '+s.cls+'">'+s.l.map(x=>'<span>'+esc(x)+'</span>').join('')+'</div></div>'
      +'<div class="cen c-sn">'+SN(c.factura)+'</div><div class="cen c-sn">'+SN(c.muebles)+'</div><div class="c-sn">'+(v.bacha?'<span class="sn no" title="'+esc(bachaTxt(v))+'">'+PIL[v.bacha]+'</span>':'<span class="sn">—</span>')+'</div>'
      +(obra?'<div class="c-obra">'+obra+'</div>':'')
      +'<div class="c-tab"><div class="tab">'+tab+'</div></div>'
      +'<div class="c-not" title="'+esc(nota)+'">'+esc(nota)+'</div>'
      +'<div class="c-mas"><button type="button" class="mas" data-menu="'+esc(c.id)+'" aria-haspopup="menu" aria-expanded="false" aria-label="Más acciones de '+esc(v.cliente)+'" title="Más acciones">'+ICO_MAS+'</button></div>'
      +(nota?'<div class="f-nota">'+esc(nota)+'</div>':'')+'</div>';
  }).join('');
  if(!lista.length)h+='<div class="vacio">Ningún contrato de '+(buscando?'la búsqueda':'este mes')+' entra en ese filtro.</div>';
  else{
    // pie: la suma de lo que se ve, debajo de cada columna
    const z=sumar(lista),col=(o,cls)=>{const l=partes(o);return '<div class="der"><div class="plata'+(l.length?cls:' nada')+'">'+(l.length?l.map(x=>'<span>'+x+'</span>').join(''):'—')+'</div></div>';};
    h+='<div class="pie"><div class="q">'+lista.length+(lista.length===1?' contrato':' contratos')+(filtro!=='todos'?' · '+esc(FILTROS.find(f=>f[0]===filtro)[1].toLowerCase()):'')+'</div>'+col(z.tot,'')+col(z.pag,'')+col(z.deb,' debe')+'</div>';
  }
  $('lista').innerHTML=h;
}
function moverMes(n){const d=new Date(+mes.slice(0,4),+mes.slice(5,7)-1+n,1);mes=d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0');dibujar();}

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
// Lista cerrada. Si el contrato tiene un valor que ya no está en la lista, se conserva como una opción más.
const selHtml=(id,ops,val)=>'<select id="'+id+'"><option value="">Sin asignar</option>'+ops.concat(val&&ops.indexOf(val)<0?[val]:[]).map(o=>'<option value="'+esc(o)+'"'+(o===val?' selected':'')+'>'+esc(o)+'</option>').join('')+'</select>';

function abrirFicha(id){
  const c=id?CONTRATOS.find(x=>x.id===id):null;
  if(id&&!c)return;
  const v=c?ver(c):{},k=c?cardDe(c):null;
  // Enlazado y con la ficha a la vista: lo operativo se edita en el tablero (un solo lugar por dato).
  const fijo=!!k,ro=fijo?' readonly':'';
  const dif=c?senaDif(c):null;
  F={id:c?c.id:'',factura:c?c.factura:'',muebles:c?c.muebles:'',bacha:c?v.bacha:'',pagos:c?(c.pagos||[]).slice():[],fijo:fijo,
     empujarSena:!!(k&&!k.sena&&k.etapa==='medir'),key:k?k.key:'',
     contrato:c?c.contrato:'',enlazado:!!(c&&c.enlace),archivo:c?c.archivo:null,archivoNuevo:null,quitarArchivo:false,presup:c?c.presup:null,nroMal:'',buscando:false};
  const val=x=>esc(x==null?'':x);
  let h='<div class="m-cab"><div><h3>'+(c?esc(v.cliente):'Nuevo contrato')+'<span class="ver" id="hVer" hidden title="Tiene adjunto el presupuesto">'+ICO_VER+'Verificado</span></h3>'+(c?'<div class="m-sub">'+[c.nro?'N° '+esc(c.nro):'Sin N° de contrato',esc(fch(c.fecha)),c.prueba?'de prueba':''].filter(Boolean).join(' · ')+'</div>':'')+'<div class="m-chk" id="mChk"></div></div>'
    +(k?'<a class="etapa et-'+esc(k.etapa)+'" href="/presupuestos/tablero.php?q='+encodeURIComponent(k.key)+'" title="Ver la ficha en el tablero">'+esc(ETAPAS[k.etapa]||k.etapa)+'</a>':'')+'</div>';
  h+='<div class="m-res"><div><div class="t">Total</div><b id="rTot"></b></div><div><div class="t">Cobrado</div><b id="rCob"></b></div><div><div class="t">Saldo</div><b id="rSal"></b></div></div>';
  if(k)h+='<div class="nota-t"><span class="enl">'+ICO_ENL+'Enlazado con el tablero y el calendario</span><br>'+(etapaDet(k).t?esc(etapaDet(k).t)+'. ':'')+'Cliente, teléfono, dirección, material y pileta se cambian desde el tablero.</div>';
  else if(c&&c.enlace&&TABLERO_OK){
    if(FUERA[c.ficha])h+='<div class="nota-t"><span class="enl roto">'+ICO_ENL+FUERA[c.ficha][0]+'</span><br>'+FUERA[c.ficha][1]+'</div>';
    else h+='<div class="nota-t rota"><span class="enl roto">'+ICO_ENL+'Esta ficha no existe en el tablero</span><br>El contrato está enlazado con '+esc(fichaIda(c))+' que ya no está: se borró o le cambiaron el N° de presupuesto. <button type="button" class="lnk" data-desenlazar="'+esc(c.id)+'">Desenlazar</button></div>';
  }
  h+='<label class="campo notas">Notas<textarea id="fNota" rows="4" maxlength="4000" placeholder="Acá van los detalles: qué se acordó, aclaraciones de medidas, forma de pago, lo que haya que acordarse.">'+val(c&&c.nota)+'</textarea></label>';
  h+='<h4>Cliente</h4>'
    +'<label class="campo">Nombre<input id="fCliente" value="'+val(v.cliente)+'" autocomplete="off"'+ro+(c?'':' data-foco')+'></label>'
    +'<div class="dos"><label class="campo">Teléfono <i id="fWaI" hidden>· <a id="fWa" href="#" target="_blank" rel="noopener">abrir WhatsApp</a></i><input id="fTel" value="'+val(telLindo(v.telefono))+'" inputmode="tel" autocomplete="off" placeholder="11 5555-1234"'+ro+'></label>'
    +'<label class="campo">Dirección<input id="fDir" value="'+val(v.direccion)+'" autocomplete="off"'+ro+'></label></div>';
  h+='<h4>Contrato</h4>'
    +'<div class="dos"><label class="campo">Contrato <i>· N° de la calculadora</i><input id="fNro" value="'+val(c&&c.nro)+'" placeholder="0042" inputmode="numeric" autocomplete="off"></label>'
    +'<label class="campo">Ctto paralelo<input id="fCttoP" value="'+val(c&&c.contrato_paralelo)+'" placeholder="0815" autocomplete="off"></label></div>'
    +'<div class="pres"><div><span class="t">Enlace con Presupuestos</span><div class="lin" id="pEst"></div></div><div><span class="t">Presupuesto adjunto</span><div class="lin" id="pArch"></div></div></div>'
    +'<input type="file" id="fArchivo" accept=".pdf,.xlsx,.xls,application/pdf,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" hidden>'
    +'<div class="dos"><label class="campo">Fecha del contrato<input id="fFecha" type="date" value="'+val(c?c.fecha:(mes===HOY.slice(0,7)?HOY:mes+'-01'))+'"></label>'
    +'<label class="campo">Vendedor'+selHtml('fVend',VENDEDORES,c?c.vendedor:'')+'</label></div>'
    +'<div class="dos"><label class="campo">Procedencia'+selHtml('fProc',PROCEDENCIAS,c?c.procedencia:'')+'</label>'
    +'<label class="campo">Material<input id="fMat" value="'+val(v.material)+'" list="catalogo" autocomplete="off"'+ro+'></label></div>'
    +'<label class="campo">Medidas<input id="fMed" value="'+val(c&&c.medidas)+'" placeholder="2,40 x 0,62 + isla 1,80 x 0,90" autocomplete="off"></label>'
    ;
  h+='<h4>Plata</h4>'
    +'<div class="dos"><label class="campo">Total en dólares<input id="fTotU" value="'+enCampo(c&&c.total_usd)+'" inputmode="decimal" placeholder="0" autocomplete="off"></label>'
    +'<label class="campo">Total en pesos<input id="fTotA" value="'+enCampo(c&&c.total_ars)+'" inputmode="decimal" placeholder="0" autocomplete="off"></label></div>'
    +'<div class="tres"><label class="campo">Seña recibida<input id="fSen" value="'+enCampo(v.sena&&v.sena.monto)+'" inputmode="decimal" placeholder="0" autocomplete="off"></label>'
    +'<label class="campo">Moneda<select id="fSenM"><option value="ARS"'+(v.sena&&v.sena.moneda==='USD'?'':' selected')+'>Pesos</option><option value="USD"'+(v.sena&&v.sena.moneda==='USD'?' selected':'')+'>Dólares</option></select></label>'
    +'<label class="campo">Fecha de la seña<input id="fSenF" type="date" value="'+val((v.sena&&v.sena.fecha)||'')+'"></label></div>'
    +'<label class="campo cot" id="cSenC" hidden>Cotización del dólar para la seña <i id="fSenCI"></i><input id="fSenC" value="'+enCampo(v.sena&&v.sena.cotizacion)+'" inputmode="decimal" placeholder="1.575" autocomplete="off"></label>'
    +'<div class="pista" id="fSenP" hidden></div>'
    +(dif?'<div class="nota-t sena-d"><b>La seña no coincide con el tablero.</b> '+esc(dif.txt)+' No se cambia sola en ninguno de los dos lados: corregí la que esté mal.</div>':'')
    +(F.empujarSena?'<div class="nota-t">Al guardar, la seña queda tildada también en el tablero ("Trajo la seña"), que hoy no la tiene.</div>':'');
  if(c)h+='<div id="fPagos"></div>'
    +'<div class="tres"><label class="campo">Nuevo pago<input id="pMonto" inputmode="decimal" placeholder="Monto" autocomplete="off"></label>'
    +'<label class="campo">Cómo<select id="pComo"><option value="efectivo|ARS">Efectivo $</option><option value="efectivo|USD">Efectivo USD</option><option value="transferencia|ARS">Transferencia</option></select></label>'
    +'<label class="campo">Fecha<input id="pFecha" type="date" value="'+val(HOY)+'"></label></div>'
    +'<label class="campo cot" id="cPagC" hidden>Cotización del dólar para este pago <i id="pCotI"></i><input id="pCot" inputmode="decimal" placeholder="1.575" autocomplete="off"></label>'
    +'<div style="margin-top:10px"><button type="button" class="btn chico" data-pago>Agregar pago</button></div>';
  h+='<h4>Obra</h4>'
    +'<div class="dos"><div class="campo">Factura'+opcHtml('factura',[['si','Sí'],['no','No'],['cf','Cons. final']],F.factura)+'</div>'
    +'<div class="campo">Muebles colocados'+opcHtml('muebles',[['si','Sí'],['no','No']],F.muebles)+'</div></div>'
    +'<div class="campo">Pileta'+opcHtml('bacha',[['nuestra','Nuestra'],['cliente','Del cliente'],['no','No lleva']],F.bacha,fijo)+'</div>'
    +'<label class="campo" id="cModelo"'+(F.bacha==='nuestra'?'':' hidden')+'>Modelo de pileta<input id="fModelo" value="'+val(v.bacha_modelo)+'" autocomplete="off"'+ro+'></label>'
    +(c&&c.enlace?'':'<label class="campo">Medición coordinada <i>· si ya tiene día</i><input id="fMedicion" type="date" value="'+val(c&&c.medicion)+'"></label>');
  h+='<div class="m-err" id="mErr" hidden></div><div class="btns">'+(c?'<button type="button" class="izq" data-borrar>Borrar contrato</button>':'')+'<button type="button" data-cerrar>Cancelar</button><button type="button" class="ok" data-guardar>Guardar</button></div>';
  abrirModal(h,false,true);
  pintarPagos();
  crecerNota();
  pintarTel(false);
  pintarPres();
}
function datosForm(){
  const g=id=>$(id)?$(id).value.trim():'';
  return {nro:g('fNro'),fecha:g('fFecha'),cliente:g('fCliente'),telefono:telLindo(g('fTel')),direccion:g('fDir'),vendedor:g('fVend'),procedencia:g('fProc'),
    contrato:F.contrato||'',contrato_paralelo:g('fCttoP'),material:g('fMat'),medidas:g('fMed'),total_usd:num(g('fTotU')),total_ars:num(g('fTotA')),
    sena_monto:num(g('fSen')),sena_moneda:g('fSenM')||'ARS',sena_fecha:g('fSenF'),
    sena_aplica:destino(g('fSenM')||'ARS',{USD:num(g('fTotU')),ARS:num(g('fTotA'))},{USD:num(g('fTotU')),ARS:num(g('fTotA'))}),sena_cotizacion:num(g('fSenC'))||'',factura:F.factura,muebles:F.muebles,bacha:F.bacha,
    bacha_modelo:F.bacha==='nuestra'?g('fModelo'):'',medicion:g('fMedicion'),nota:g('fNota')};
}
// El presupuesto del contrato. Tres casos: vacío (no pasa nada), con N° (queda enlazado con
// Presupuestos) y con el PDF o Excel adjunto. Solo el archivo adjunto lo deja VERIFICADO.
function pintarPres(){
  if(!F||!$('pEst'))return;
  const nro=$('fNro').value.trim(),p=F.presup;
  // las tres cosas con las que el contrato cierra limpio
  const hayArch=!!F.archivoNuevo||(!!F.archivo&&!F.quitarArchivo);
  $('mChk').innerHTML=[[!!nro,'N° de contrato'],[hayArch,'Presupuesto adjunto'],[F.enlazado,'Enlazado con el tablero']].map(x=>'<span class="'+(x[0]?'si':'')+'">'+x[1]+'</span>').join('');
  let e;
  if(!nro)e='<span class="gris">Sin N°. Si lo ponés, el contrato queda enlazado con ese presupuesto.</span>';
  else if(F.nroMal)e='<span class="amb">'+esc(F.nroMal)+'</span>';
  else if(F.buscando||!p)e='<span class="gris">Buscando el N° en Presupuestos…</span>';
  else if(p.existe){
    const tot=partes({USD:p.total_usd,ARS:p.total_ars}).join(' + ');
    e='<span class="enl">'+ICO_ENL+'Enlazado</span><span>N° <b>'+esc(p.cliente_nro+'-'+p.sub)+'</b>'+(p.cliente?' · '+esc(p.cliente):'')+(tot?' · '+tot:'')+'</span>'
      +'<a href="'+(p.detalle?'/calculadora/api/render-pdf.php?nro='+encodeURIComponent(p.cliente_nro)+'&sub='+p.sub:'/presupuestos/?tab=activos')+'" target="_blank" rel="noopener">'+(p.detalle?'Ver presupuesto':'Ir a Presupuestos')+'</a>';
  }else e='<span class="amb">No hay ningún presupuesto N° '+esc(p.cliente_nro+(p.sub?'-'+p.sub:''))+' en Presupuestos.</span><span class="gris">O está mal escrito, o ya se borró: los presupuestos duran 180 días. Lo que queda para siempre es el archivo adjunto.</span>';
  $('pEst').innerHTML=e;
  const a=F.archivoNuevo?{nombre:F.archivoNuevo.name,bytes:F.archivoNuevo.size,nuevo:true}:(F.quitarArchivo?null:F.archivo);
  $('pArch').innerHTML=a
    ?'<span class="ver">'+ICO_VER+'Verificado</span><span><b>'+esc(a.nombre)+'</b> · '+kb(a.bytes)+(a.nuevo?' · se sube al guardar':'')+'</span>'
      +(a.nuevo?'':'<a href="'+API_ADJ+'?id='+encodeURIComponent(F.id)+'" target="_blank" rel="noopener">Abrir</a>')
      +'<button type="button" class="lnk" data-quitar-archivo>Quitar</button>'
    :'<button type="button" class="btn chico" data-elegir-archivo>Subir PDF o Excel</button><span class="gris">'+(F.quitarArchivo?'Se quita al guardar: el contrato deja de estar verificado.':'Con el presupuesto adjunto, el contrato queda verificado.')+'</span>';
  $('hVer').hidden=!a;
}
// Busca el N° en Presupuestos mientras se escribe (con una pausa, para no preguntar en cada tecla).
let tNro=null;
function buscarNro(){
  if(!F)return;
  clearTimeout(tNro);
  const nro=$('fNro').value.trim();
  F.presup=null;F.nroMal='';F.buscando=false;
  if(nro&&!/^#?\s*\d{1,6}(\s*-\s*\d{1,3})?$/.test(nro))F.nroMal='El N° va como 0042, o 0042-2 si tiene versiones.';
  else if(nro){
    F.buscando=true;
    tNro=setTimeout(async()=>{
      let r=null;try{r=await pedir(API+'?verificar='+encodeURIComponent(nro));}catch(e){}
      if(!F||$('fNro').value.trim()!==nro)return; // ya se escribió otra cosa
      F.buscando=false;F.presup=r?r.presup:{existe:false,cliente_nro:nro,sub:0};
      pintarPres();
    },350);
  }
  pintarPres();
}
async function subirArchivo(id,file){
  const fd=new FormData();fd.append('id',id);fd.append('archivo',file);
  const r=await fetch(API_ADJ,{method:'POST',body:fd,credentials:'same-origin'});
  let j=null;try{j=await r.json();}catch(e){}
  if(!j||!j.ok)throw new Error((j&&j.error)||'No se pudo subir el archivo.');
  return j;
}
// El teléfono se acomoda al salir del campo (o al pegar) y muestra el link a WhatsApp si es un número completo.
function pintarTel(acomodar){
  const i=$('fTel'),a=$('fWa');if(!i)return;
  if(acomodar&&!i.readOnly&&i.value.trim())i.value=telLindo(i.value);
  const w=waLink(i.value);
  if(a){$('fWaI').hidden=!w;if(w)a.href=w;}
}
// La caja de notas crece con lo que se escribe (hasta un tope, después se desplaza por dentro).
function crecerNota(){const t=$('fNota');if(!t)return;t.style.height='auto';t.style.height=Math.min(320,Math.max(104,t.scrollHeight+2))+'px';}
// Pagos y saldo de la ventana: se recalculan con lo que está escrito, sin guardar.
function pintarPagos(){
  if(!F)return;
  const d=datosForm(),p={USD:0,ARS:0},a={USD:0,ARS:0},r=x=>Math.round(x*100)/100;
  const tot={USD:d.total_usd,ARS:d.total_ars};
  // la seña: si se cobró en una moneda y el contrato está en la otra, pide la cotización
  const sm=d.sena_moneda==='USD'?'USD':'ARS',conCambio=d.sena_monto>0&&d.sena_aplica!==sm;
  $('cSenC').hidden=!conCambio;
  if(conCambio&&!$('fSenC').value&&DOLAR&&document.activeElement!==$('fSenC'))$('fSenC').value=enCampo(DOLAR);
  const sena=d.sena_monto?{monto:d.sena_monto,moneda:sm,aplica:d.sena_aplica,cotizacion:conCambio?num($('fSenC').value):0}:null;
  $('fSenCI').textContent=sena&&conCambio&&sena.cotizacion?'· '+plata(sena.monto,sm)+' = '+plata(sm==='USD'?sena.monto*sena.cotizacion:sena.monto/sena.cotizacion,sena.aplica):'· '+(sm==='USD'?'se cobró en dólares y el contrato está en pesos':'se cobró en pesos y el contrato está en dólares');
  (sena?[sena]:[]).concat(F.pagos).forEach(x=>{p[x.moneda]+=x.monto;aplicar(a,x);});
  const sal={USD:r(tot.USD-a.USD),ARS:r(tot.ARS-a.ARS)},s=saldoTxt({tot:tot,sal:sal});
  const pon=(id,l,cls)=>{$(id).className=l.length?cls:'nada';$(id).innerHTML=l.length?l.map(esc).join('<br>'):'—';};
  pon('rTot',partes(tot),'');pon('rCob',partes({USD:r(p.USD),ARS:r(p.ARS)}),'');
  $('rSal').className=s.cls;$('rSal').innerHTML=s.l.map(esc).join('<br>');
  // Qué parte del total es la seña. Lo habitual es 50%, o 75% si no hay muebles; es un dato para mirar, no una regla.
  const ps=$('fSenP');
  if(sena){
    const ap={USD:0,ARS:0};aplicar(ap,sena);
    const m=ap.USD>0?'USD':'ARS',pct=tot[m]>0?ap[m]/tot[m]*100:null,esp=F.muebles==='no'?75:50;
    ps.hidden=pct===null;
    if(pct!==null){ps.textContent='La seña es el '+NF.format(Math.round(pct))+'% del total. Lo habitual: 50%, o 75% si no hay muebles.';ps.classList.toggle('baja',pct<esp-0.5);}
  }else ps.hidden=true;
  // el pago nuevo: ¿hace falta cotización?
  if($('pComo')){
    const pm=$('pComo').value.split('|')[1],dst=destino(pm,tot,sal),cc=dst!==pm;
    $('cPagC').hidden=!cc;$('cPagC').dataset.aplica=dst;
    if(cc&&!$('pCot').value&&DOLAR&&document.activeElement!==$('pCot'))$('pCot').value=enCampo(DOLAR);
    const pmon=num($('pMonto').value),pc=num($('pCot').value);
    $('pCotI').textContent=cc&&pmon&&pc?'· '+plata(pmon,pm)+' = '+plata(pm==='USD'?pmon*pc:pmon/pc,dst):'· '+(pm==='USD'?'entra en dólares y se descuenta de los pesos':'entra en pesos y se descuenta de los dólares');
  }
  if($('fPagos'))$('fPagos').innerHTML='<div class="pagos">'+(F.pagos.length?F.pagos.map(x=>'<div class="pago"><span class="q"><b>'+esc(fch(x.fecha))+'</b> · '+(x.medio==='transferencia'?'Transferencia':(x.medio==='efectivo'?'Efectivo':'Cobro'))+esc(cambioTxt(x))+(x.nota?' · '+esc(x.nota):'')+'</span><span class="m">'+plata(x.monto,x.moneda)+'</span><button type="button" data-borrar-pago="'+esc(x.id)+'" title="Borrar este pago" aria-label="Borrar este pago">×</button></div>').join(''):'<div class="nada">Sin pagos cargados, aparte de la seña.</div>')+'</div>';
}
async function guardar(){
  const d=datosForm(),id=F.id,empujar=F.empujarSena&&d.sena_monto>0,key=F.key;
  const nuevo=F.archivoNuevo,quitar=F.quitarArchivo&&!!F.archivo&&!nuevo;
  if(!d.cliente)throw new Error('Falta el nombre del cliente.');
  const r=await pedir(API,Object.assign({accion:id?'editar':'crear'},id?{id:id}:{},d));
  let aviso='';
  // El presupuesto adjunto va después de guardar: un contrato nuevo recién ahí tiene dónde colgarlo.
  try{
    if(nuevo)await subirArchivo(r.id,nuevo);
    else if(quitar)await pedir(API_ADJ,{accion:'borrar',id:r.id});
  }catch(e){aviso=' El archivo no se pudo '+(nuevo?'subir':'quitar')+': '+e.message;}
  if(empujar){
    const p=key.split('-');
    try{await pedir(API_T,{cliente_nro:p[0],sub:+p[1],accion:'check',clave:'sena',valor:true,monto:d.sena_monto,moneda:d.sena_moneda});}
    catch(e){aviso=' La seña no se pudo tildar en el tablero: '+e.message;}
  }
  cerrarModal();
  if(/^\d{4}-\d{2}/.test(d.fecha||'')&&!llano(q).trim())mes=d.fecha.slice(0,7);
  toast((id?'Contrato guardado.':'Contrato cargado.')+(nuevo&&!aviso?' Quedó verificado.':'')+aviso);
  await cargar();
  const cg=CONTRATOS.find(x=>x.id===r.id);
  if(cg&&fichaPara(cg))pedirEnlazar(cg.id);
  return r;
}

function pedirEnviar(id){
  const c=CONTRATOS.find(x=>x.id===id);if(!c)return;
  const li=(a,b)=>b?'<li><span>'+a+'</span><b>'+esc(b)+'</b></li>':'';
  let h='<h3>Cargar en Por medir</h3>';
  if(c.nro)h+='<div class="bajada">Se aprueba el presupuesto <b>N° '+esc(c.nro)+'</b> y la obra aparece en Por medir del tablero, con los datos de ese presupuesto. Si ya está en el tablero, solo se enlaza.</div><ul class="lista-c">'+li('Pileta',bachaTxt(c))+li('Medición',fch(c.medicion))+li('Seña',c.sena?plata(c.sena.monto,c.sena.moneda):'')+'</ul>';
  else h+='<div class="bajada">Se crea la ficha en Por medir del tablero con estos datos. El total, los pagos y lo demás del contrato quedan acá.</div><ul class="lista-c">'+li('Cliente',c.cliente)+li('Teléfono',telLindo(c.telefono))+li('Dirección',c.direccion)+li('Material',c.material)+li('Pileta',bachaTxt(c))+li('Medición',fch(c.medicion))+li('Seña',c.sena?plata(c.sena.monto,c.sena.moneda):'')+'</ul>';
  h+='<div class="m-err" id="mErr" hidden></div><div class="btns"><button type="button" data-cerrar>Cancelar</button><button type="button" class="ok" data-foco data-confirmar-enviar="'+esc(c.id)+'">Cargar en Por medir</button></div>';
  abrirModal(h,true,false);
}
async function enviar(id){
  const c=CONTRATOS.find(x=>x.id===id);if(!c)return;
  let nro,sub,yaEstaba=false;
  try{
    const r=await pedir(API_CARGAR,c.nro?{nro:c.nro}:{nombre:c.cliente,celular:telLindo(c.telefono),direccion:c.direccion,material:c.material});
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
  toast(yaEstaba?'Ya estaba en el tablero: quedó enlazada con el tablero y el calendario.':('Cargada en Por medir: quedó enlazada con el tablero y el calendario.'+(falto.length?' Faltó copiar '+falto.join(', ')+': cargalo en el tablero.':'')));
  await cargar();
}
// La obra de este contrato ya está en el tablero (mismo N° de presupuesto o mismo teléfono):
// se enlaza con esa ficha. En el tablero no se crea ni se cambia nada.
function pedirEnlazar(id){
  const c=CONTRATOS.find(x=>x.id===id),fp=fichaPara(c);
  if(!c||!fp)return;
  const k=fp.k,li=(a,b)=>b?'<li><span>'+a+'</span><b>'+esc(b)+'</b></li>':'',d=etapaDet(k);
  abrirModal('<h3>Enlazar con el tablero</h3><div class="bajada">'+(fp.por==='tel'?'En el tablero hay una obra con el <b>mismo teléfono</b> que este contrato. Fijate que sea la misma.':'El presupuesto <b>N° '+esc(c.nro)+'</b> ya está cargado en el tablero.')+' Al enlazarlo, este contrato muestra la etapa de esa obra y queda conectado con el tablero y el calendario. En el tablero no se crea ni se cambia nada.</div>'
    +'<ul class="lista-c">'+li('Obra',obraTxt(k))+li('Cliente',k.nombre)+li('Teléfono',telLindo(k.celular))+li('Dirección',k.direccion)+li('Etapa',(ETAPAS[k.etapa]||k.etapa)+(d.t?' · '+d.t:''))+li('Material',k.material)+'</ul>'
    +'<div class="m-err" id="mErr" hidden></div><div class="btns"><button type="button" data-cerrar>Ahora no</button><button type="button" class="ok" data-foco data-confirmar-enlazar="'+esc(c.id)+'">Enlazar con el tablero</button></div>',true,false);
}
async function enlazar(id){
  const c=CONTRATOS.find(x=>x.id===id),fp=fichaPara(c);
  if(!c||!fp)throw new Error('Esa obra ya no está en el tablero. Actualizá la página.');
  await pedir(API,{accion:'enlazar',id:c.id,cliente_nro:fp.k.cliente_nro,sub:fp.k.sub});
  cerrarModal();
  toast('Enlazado con el tablero y el calendario.');
  await cargar();
}
// Desenlazar: el contrato deja de estar unido a su ficha. No se borra nada en ningún lado.
function pedirDesenlazar(id){
  const c=CONTRATOS.find(x=>x.id===id);
  if(!c||!c.enlace)return;
  const k=cardDe(c),v=ver(c);
  abrirModal('<h3>Desenlazar del tablero</h3><div class="bajada">El contrato de <b>'+esc(v.cliente)+'</b> deja de estar unido a '+(k?'su ficha del tablero ('+esc(obraTxt(k))+', en '+esc(ETAPAS[k.etapa]||k.etapa)+')':esc(fichaIda(c))+' que ya no está en el tablero')+'.<br><br><b>No se borra nada:</b> el contrato queda suelto, con su total y sus pagos'+(k?', y la ficha sigue en el tablero y en el calendario':'')+'. Después se puede volver a enlazar.</div>'
    +'<div class="m-err" id="mErr" hidden></div><div class="btns"><button type="button" data-cerrar data-foco>Cancelar</button><button type="button" class="ok" data-confirmar-desenlazar="'+esc(c.id)+'">Desenlazar</button></div>',true,false);
}
async function desenlazar(id){
  // lo operativo que venía de la ficha (cliente, teléfono, dirección, material) queda copiado en el contrato
  const c=CONTRATOS.find(x=>x.id===id),k=c?cardDe(c):null;
  if(c&&k){
    const v=ver(c);
    await pedir(API,{accion:'editar',id:c.id,nro:c.nro,fecha:c.fecha,cliente:v.cliente,telefono:telLindo(v.telefono),direccion:v.direccion,vendedor:c.vendedor,procedencia:c.procedencia,
      contrato:c.contrato,contrato_paralelo:c.contrato_paralelo,material:v.material,medidas:c.medidas,total_usd:c.total_usd,total_ars:c.total_ars,
      sena_monto:v.sena?v.sena.monto:0,sena_moneda:v.sena?v.sena.moneda:'ARS',sena_fecha:v.sena?v.sena.fecha:'',sena_cotizacion:(v.sena&&v.sena.cotizacion)||'',sena_aplica:(v.sena&&v.sena.aplica)||'',
      factura:c.factura,muebles:c.muebles,bacha:v.bacha,bacha_modelo:v.bacha==='nuestra'?v.bacha_modelo:'',medicion:c.medicion,nota:c.nota}).catch(()=>{});
  }
  await pedir(API,{accion:'desenlazar',id:id});
  cerrarModal();
  toast('Desenlazado. El contrato quedó suelto; no se borró nada.');
  await cargar();
}
// --- los puntitos de cada renglón ---
let menuDe='',menuAt=0;
function cerrarMenu(){
  if(!menuDe)return;
  menuDe='';$('menu').hidden=true;
  document.querySelectorAll('.mas[aria-expanded="true"]').forEach(b=>b.setAttribute('aria-expanded','false'));
}
function abrirMenu(btn){
  const id=btn.dataset.menu,c=CONTRATOS.find(x=>x.id===id);
  if(!c||menuDe===id){cerrarMenu();return;}
  cerrarMenu();
  const v=ver(c),tel=telDigitos(v.telefono)||String(v.telefono||'').trim();
  const it=(que,txt,valor)=>'<button type="button" role="menuitem" data-copiar="'+que+'"'+(valor?' data-valor="'+esc(valor)+'"':' disabled')+'>'+txt+'<small>'+esc(valor||'no tiene')+'</small></button>';
  const m=$('menu');
  m.innerHTML=it('nro','Copiar N° de presupuesto',c.nro)+it('tel','Copiar teléfono',tel)+it('dir','Copiar dirección',v.direccion)
    +'<div class="sep"></div><button type="button" role="menuitem" data-menu-desenlazar="'+esc(id)+'"'+(c.enlace?'':' disabled')+'>Desenlazar del tablero<small>'+(c.enlace?'no borra nada':'no está enlazado')+'</small></button>'
    +'<button type="button" role="menuitem" class="pel" data-menu-borrar="'+esc(id)+'">Borrar contrato</button>';
  m.hidden=false;menuDe=id;btn.setAttribute('aria-expanded','true');
  // debajo del botón, pegado a su borde derecho; si no entra abajo, arriba
  const r=btn.getBoundingClientRect(),w=m.offsetWidth,h=m.offsetHeight;
  m.style.left=Math.max(8,Math.min(r.right-w,window.innerWidth-w-8))+'px';
  m.style.top=(r.bottom+h+8>window.innerHeight&&r.top-h-6>0?r.top-h-6:r.bottom+6)+'px';
  menuAt=Date.now();
  const f=m.querySelector('button:not(:disabled)');if(f)f.focus({preventScroll:true});
}
const COPIADO={nro:'N° de presupuesto copiado',tel:'Teléfono copiado',dir:'Dirección copiada'};
async function copiar(txt,que){
  try{await navigator.clipboard.writeText(txt);}
  catch(e){ // navegadores viejos o sin permiso: el camino de antes
    const ta=document.createElement('textarea');ta.value=txt;ta.setAttribute('readonly','');ta.style.cssText='position:fixed;top:0;left:0;opacity:0';
    document.body.appendChild(ta);ta.select();
    let ok=false;try{ok=document.execCommand('copy');}catch(x){}
    ta.remove();
    if(!ok){toast('No se pudo copiar. El dato es: '+txt);return;}
  }
  toast(COPIADO[que]+': '+txt);
}

// Borrar un contrato: va a la papelera y se puede recuperar durante 30 días. Si está enlazado con
// una ficha que sigue en el tablero, la ficha va a la papelera del tablero (regla del dueño,
// 16/11/2026: enlazados, se borra en los dos lados). Restaurar trae de vuelta a los dos.
function pedirBorrar(id){
  const c=CONTRATOS.find(x=>x.id===(id||(F&&F.id)));if(!c)return;
  id=c.id;
  const k=cardDe(c),nAdj=k&&k.adjuntos?k.adjuntos.length:0;
  abrirModal('<h3>Borrar contrato</h3><div class="bajada">El contrato de <b>'+esc(ver(c).cliente)+'</b> va a la papelera, con sus pagos'+(c.archivo?' y su presupuesto adjunto':'')+'.'
    +(k?'<br><br><b>También sale del tablero su ficha</b> ('+esc(obraTxt(k))+', en '+esc(ETAPAS[k.etapa]||k.etapa)+'), con sus tildes, fechas y datos'+(nAdj?', y '+(nAdj===1?'su archivo adjunto':'sus '+nAdj+' archivos adjuntos'):'')+'.'
      :(c.enlace?'<br>La ficha del tablero ya no está (archivada o borrada): no se toca nada más.':''))
    +'<br><br>'+(c.prueba?'Es un renglón de prueba: se borra de verdad, sin papelera.':'Se puede recuperar durante 30 días desde "Papelera". Después se borra de verdad.')+'</div><div class="m-err" id="mErr" hidden></div><div class="btns"><button type="button" data-cerrar data-foco>Cancelar</button><button type="button" class="ok peligro" data-confirmar-borrar="'+esc(id)+'">'+(k?'Borrar contrato y ficha':'Borrar')+'</button></div>',true,false);
}
async function borrarContrato(id){
  const c=CONTRATOS.find(x=>x.id===id);if(!c)return;
  const k=cardDe(c);
  // primero la ficha del tablero: si eso falla, el contrato no se toca
  if(k){
    const base={cliente_nro:k.cliente_nro,sub:k.sub};
    if(c.prueba){ // los de prueba no pasan por la papelera
      await pedir(API_T,Object.assign({accion:'borrar'},base));
      if(k.manual)await pedir(API_DEL,Object.assign({desde_tablero:true},base));
      else await pedir('/calculadora/api/state.php',Object.assign({nuevo_estado:'enviado',desde_tablero:true},base));
    }else await pedir(API_PAP_T,Object.assign({accion:'tirar'},base));
  }
  await pedir(API,{accion:'borrar',id:id});
  cerrarModal();
  toast(c.prueba?'Renglón de prueba borrado.':(k?'Contrato y ficha en la papelera. Se pueden recuperar 30 días.':'Contrato en la papelera. Se puede recuperar 30 días.'));
  await cargar();
}
// Papelera de contratos: los borrados en los últimos 30 días, con el botón para recuperarlos.
// --- obras en el tablero sin contrato ---
function pedirSinContrato(){
  const l=sinContrato();
  const filas=l.map(k=>'<div class="pap-f"><div><b>'+esc(k.nombre||'Sin nombre')+(k.manual?'':' · '+esc(k.key))+'</b><small>'+[esc(ETAPAS[k.etapa]||k.etapa),esc(k.material)].filter(Boolean).join(' · ')+'</small></div><button type="button" class="btn chico" data-crear-de="'+esc(k.key)+'">Crear contrato</button></div>').join('');
  abrirModal('<h3>Obras en el tablero sin contrato</h3><div class="bajada">'+(filas?'Cada obra del tablero tendría que tener su contrato acá. Al crearlo queda enlazado, con el cliente, el teléfono, la dirección, el material y la seña que ya están en la ficha. Después se le cargan el total y los pagos.':'Todas las obras del tablero tienen su contrato.')+'</div>'+(filas?'<div class="pap">'+filas+'</div>':'')
    +'<div class="m-err" id="mErr" hidden></div><div class="btns">'+(l.length>1?'<button type="button" class="izq-n" data-crear-todos>Crear los '+l.length+'</button>':'')+'<button type="button" data-cerrar data-foco>Cerrar</button></div>',false,false);
}
// El contrato de una ficha: nace enlazado. Lo operativo lo sigue mandando el tablero.
function crearDeFicha(k){
  return pedir(API,{accion:'crear',cliente:k.nombre||'Sin nombre',telefono:telLindo(k.celular),direccion:k.direccion,material:k.material,
    nro:k.manual?'':k.cliente_nro+(k.sub>1?'-'+k.sub:''),fecha:HOY,bacha:k.bacha,bacha_modelo:k.bacha==='nuestra'?k.bacha_modelo:'',
    sena_monto:k.sena?k.sena.monto:0,sena_moneda:k.sena?k.sena.moneda:'ARS',enlace:{cliente_nro:k.cliente_nro,sub:k.sub}});
}
async function crearUno(key){
  const k=CARDS[key];
  if(!k)throw new Error('Esa obra ya no está en el tablero. Actualizá la página.');
  const r=await crearDeFicha(k);
  if(!llano(q).trim())mes=HOY.slice(0,7);
  await cargar();
  toast('Contrato creado y enlazado. Falta cargarle el total.');
  abrirFicha(r.id);
}
async function crearTodos(){
  const l=sinContrato();let ok=0,mal='';
  for(const k of l){try{await crearDeFicha(k);ok++;}catch(e){mal=e.message;}}
  cerrarModal();
  if(!llano(q).trim())mes=HOY.slice(0,7);
  await cargar();
  toast((ok===1?'Se creó 1 contrato.':'Se crearon '+ok+' contratos.')+(mal?' Alguno no se pudo: '+mal:'')+' Quedaron con fecha de hoy.');
}
async function pedirPapelera(){
  let j;
  try{j=await pedir(API+'?papelera=1');}catch(e){toast(e.message);return;}
  const filas=j.contratos.map(c=>{
    const n=cuentas(c,c),tot=partes(n.tot).join(' + ');
    return '<div class="pap-f"><div><b>'+esc(c.cliente)+(c.nro?' · N° '+esc(c.nro):'')+'</b><small>'+[tot?'Total '+tot:'',(c.pagos.length+(c.sena?1:0))?((c.pagos.length+(c.sena?1:0))===1?'1 cobro':(c.pagos.length+(c.sena?1:0))+' cobros'):'',c.archivo?'presupuesto adjunto':'',c.enlace?'enlazado con '+esc(c.enlace.cliente_nro+'-'+c.enlace.sub):''].filter(Boolean).join(' · ')+'</small><small>Borrado el '+esc(fch(c.borrado))+' · '+(c.quedan<=0?'se borra hoy':(c.quedan===1?'queda 1 día':'quedan '+c.quedan+' días'))+'</small></div><button type="button" class="btn chico" data-restaurar="'+esc(c.id)+'">Restaurar</button></div>';
  }).join('');
  abrirModal('<h3>Papelera</h3><div class="bajada">'+(filas?'Contratos borrados. Se pueden recuperar durante '+j.dias+' días; después se borran de verdad. Si el contrato estaba enlazado con una ficha que también está en la papelera del tablero, vuelven los dos.':'La papelera está vacía.')+'</div>'+(filas?'<div class="pap">'+filas+'</div>':'')
    +'<div class="m-err" id="mErr" hidden></div><div class="btns"><button type="button" data-cerrar data-foco>Cerrar</button></div>',false,false);
}
async function restaurar(id){
  const r=await pedir(API,{accion:'restaurar',id:id});
  // su ficha del tablero, si se había ido con él
  let conFicha=false;
  if(r.enlace){
    try{await pedir(API_PAP_T,{accion:'restaurar',cliente_nro:r.enlace.cliente_nro,sub:r.enlace.sub});conFicha=true;}catch(e){}
  }
  toast('Contrato restaurado'+(conFicha?', con su ficha del tablero.':'.'));
  await cargar();
  if(EN_PAPELERA)await pedirPapelera();else cerrarModal();
}
async function borrarPrueba(){
  const r=await pedir(API,{accion:'borrar_prueba'});
  // las fichas que se habían cargado en el tablero desde esos renglones también se van
  for(const e of (r.enlaces||[])){
    try{await pedir(API_T,{cliente_nro:e.cliente_nro,sub:e.sub,accion:'borrar'});await pedir(API_DEL,{cliente_nro:e.cliente_nro,sub:e.sub,desde_tablero:true});}catch(x){}
  }
  cerrarModal();toast('Renglones de prueba borrados.');await cargar();
}

document.addEventListener('click',ev=>{
  const t=ev.target;
  // los puntitos: abrir, elegir una acción, o cerrar al tocar afuera
  const bm=t.closest('[data-menu]');
  if(bm){abrirMenu(bm);return;}
  const bc=t.closest('#menu [data-copiar]');
  if(bc){const v=bc.dataset.valor,q2=bc.dataset.copiar;cerrarMenu();if(v)copiar(v,q2);return;}
  const bd=t.closest('#menu [data-menu-desenlazar]');
  if(bd){const idd=bd.dataset.menuDesenlazar;cerrarMenu();pedirDesenlazar(idd);return;}
  const bb=t.closest('#menu [data-menu-borrar]');
  if(bb){const idb=bb.dataset.menuBorrar;cerrarMenu();pedirBorrar(idb);return;}
  if(menuDe){cerrarMenu();if(!t.closest('.modal'))return;}
  if(t.closest('[data-nuevo]')){abrirFicha('');return;}
  if(t.closest('[data-ir-mes]')){mes=t.closest('[data-ir-mes]').dataset.irMes;dibujar();return;}
  if(t.closest('[data-filtro]')){filtro=t.closest('[data-filtro]').dataset.filtro;dibujar();return;}
  if(t.closest('[data-orden]')){const k=t.closest('[data-orden]').dataset.orden;if(orden===k)ordenAsc=!ordenAsc;else{orden=k;ordenAsc=k==='cliente';}dibujar();return;}
  if(t.closest('[data-prueba]')){const b=t.closest('[data-prueba]');conBoton(b,async()=>{try{await pedir(API,{accion:'prueba'});toast('Se cargaron 3 renglones de prueba.');await cargar();}catch(e){toast(e.message);throw e;}});return;}
  if(t.closest('[data-borrar-prueba]')){
    abrirModal('<h3>Borrar los de prueba</h3><div class="bajada">Se borran los renglones marcados PRUEBA. Si alguno se cargó en el tablero, esa ficha de prueba también se borra.</div><div class="m-err" id="mErr" hidden></div><div class="btns"><button type="button" data-cerrar>Cancelar</button><button type="button" class="ok peligro" data-confirmar-prueba>Borrar</button></div>',true,false);
    return;
  }
  let b;
  if((b=t.closest('[data-confirmar-prueba]'))){conBoton(b,borrarPrueba);return;}
  if(t.closest('[data-papelera]')){pedirPapelera();return;}
  if(t.closest('[data-sin-contrato]')){pedirSinContrato();return;}
  if((b=t.closest('[data-crear-de]'))){conBoton(b,()=>crearUno(b.dataset.crearDe));return;}
  if((b=t.closest('[data-crear-todos]'))){conBoton(b,crearTodos);return;}
  if((b=t.closest('[data-restaurar]'))){conBoton(b,()=>restaurar(b.dataset.restaurar));return;}
  if((b=t.closest('[data-enviar]'))){pedirEnviar(b.dataset.enviar);return;}
  if((b=t.closest('[data-enlazar]'))){pedirEnlazar(b.dataset.enlazar);return;}
  if((b=t.closest('[data-confirmar-enlazar]'))){conBoton(b,()=>enlazar(b.dataset.confirmarEnlazar));return;}
  if((b=t.closest('[data-desenlazar]'))){pedirDesenlazar(b.dataset.desenlazar);return;}
  if((b=t.closest('[data-confirmar-desenlazar]'))){conBoton(b,()=>desenlazar(b.dataset.confirmarDesenlazar));return;}
  if((b=t.closest('[data-confirmar-enviar]'))){conBoton(b,()=>enviar(b.dataset.confirmarEnviar));return;}
  if((b=t.closest('[data-confirmar-borrar]'))){conBoton(b,()=>borrarContrato(b.dataset.confirmarBorrar));return;}
  if(t.closest('[data-cerrar]')){cerrarModal();return;}
  if((b=t.closest('[data-guardar]'))){conBoton(b,guardar);return;}
  if(t.closest('[data-borrar]')){pedirBorrar('');return;}
  if(t.closest('[data-elegir-archivo]')){$('fArchivo').click();return;}
  if(t.closest('[data-quitar-archivo]')&&F){if(F.archivoNuevo)F.archivoNuevo=null;else F.quitarArchivo=true;$('fArchivo').value='';pintarPres();return;}
  if((b=t.closest('[data-pago]'))){
    conBoton(b,async()=>{
      const m=num($('pMonto').value),como=$('pComo').value.split('|');
      if(!m)throw new Error('Cargá el monto del pago.');
      const conCambio=!$('cPagC').hidden,cot=conCambio?num($('pCot').value):0;
      if(conCambio&&!cot)throw new Error('Poné la cotización del dólar de ese pago.');
      errModal('');
      await pedir(API,Object.assign({accion:'pago',id:F.id,monto:m,moneda:como[1],medio:como[0],fecha:$('pFecha').value},conCambio?{cotizacion:cot,aplica:$('cPagC').dataset.aplica}:{}));
      const a=await pedir(API);CONTRATOS=a.contratos;
      const c=CONTRATOS.find(x=>x.id===F.id);if(c)F.pagos=(c.pagos||[]).slice();
      $('pMonto').value='';$('pCot').value='';pintarPagos();dibujar();
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
    if(campo==='muebles')pintarPagos(); // el porcentaje habitual de seña depende de si hay muebles
    return;
  }
  if(t===$('modalBg')&&!protegida){cerrarModal();return;}
  if(t.closest('a'))return;
  const f=t.closest('.fila');
  if(f)abrirFicha(f.dataset.id);
});
// Recién abierto no lo cierra el scroll: en el celular la página puede venir todavía en movimiento.
window.addEventListener('scroll',()=>{if(Date.now()-menuAt>350)cerrarMenu();},true);
window.addEventListener('resize',cerrarMenu);
document.addEventListener('keydown',ev=>{
  if(ev.key==='Escape'&&menuDe){const b=document.querySelector('.mas[aria-expanded="true"]');cerrarMenu();if(b)b.focus();return;}
  if(ev.key==='Escape'&&$('modalBg').classList.contains('show')&&!protegida){cerrarModal();return;}
  if((ev.key==='Enter'||ev.key===' ')&&ev.target.classList&&ev.target.classList.contains('fila')){ev.preventDefault();abrirFicha(ev.target.dataset.id);}
});
$('modal').addEventListener('input',ev=>{if(ev.target.id==='fNota'){crecerNota();return;}if(ev.target.id==='fNro'){buscarNro();return;}if(F&&['fTotU','fTotA','fSen','fSenM','fSenC','pMonto','pCot'].indexOf(ev.target.id)>=0)pintarPagos();});
$('modal').addEventListener('change',ev=>{
  if(F&&(ev.target.id==='fSenM'||ev.target.id==='pComo'))pintarPagos();
  if(ev.target.id==='fTel')pintarTel(true);
  if(F&&ev.target.id==='fArchivo'){
    const f=ev.target.files[0];
    if(!f)return;
    errModal('');
    if(!/\.(pdf|xlsx|xls)$/i.test(f.name)){errModal('Tiene que ser el presupuesto en PDF o en Excel (.xlsx o .xls).');ev.target.value='';return;}
    if(f.size>10*1024*1024){errModal('El archivo es demasiado grande (máximo 10 MB).');ev.target.value='';return;}
    F.archivoNuevo=f;F.quitarArchivo=false;pintarPres();
  }
});
$('modal').addEventListener('paste',ev=>{if(ev.target.id==='fTel')setTimeout(()=>pintarTel(true),0);});
$('buscar').addEventListener('input',()=>{q=$('buscar').value;if(mes)dibujar();});
$('mesAnt').addEventListener('click',()=>moverMes(-1));
$('mesSig').addEventListener('click',()=>moverMes(1));
$('mesHoy').addEventListener('click',()=>{mes=HOY.slice(0,7);dibujar();});

// Catálogo de materiales de la calculadora, para escribir el material siempre igual ("Marca · Color").
pedir(API_COLORES).then(r=>{$('catalogo').innerHTML=(r.colors||[]).map(c=>'<option value="'+esc(c.full)+'">').join('');}).catch(()=>{});
// Llegando desde el tablero ("Con contrato"): ?contrato=<id> abre ese contrato, en su mes.
(async function(){
  await cargar();
  const id=new URLSearchParams(location.search).get('contrato');
  if(!id)return;
  history.replaceState(null,'',location.pathname); // que al recargar no se vuelva a abrir solo
  const c=CONTRATOS.find(x=>x.id===id);
  if(!c){toast('Ese contrato ya no está. Puede estar en la papelera.');return;}
  if(/^\d{4}-\d{2}/.test(c.fecha||'')){mes=c.fecha.slice(0,7);dibujar();}
  abrirFicha(id);
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
