<?php
// ============================================================
// /presupuestos/index.php — Hub del registry
// ============================================================
// 2 vistas en tabs:
//   - Activos: cotizaciones vivas (todos los estados)
//   - Reporte:  dashboard histórico de entregados
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

$tab = $_GET['tab'] ?? 'activos';
if (!in_array($tab, ['activos', 'reporte'], true)) $tab = 'activos';
$tab_label = $tab === 'activos' ? 'Presupuestos · Activos' : 'Presupuestos · Reporte';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>BlackStones · <?= htmlspecialchars($tab_label) ?></title>

<!-- Favicons -->
<link rel="icon" type="image/png" sizes="96x96" href="../favicon/favicon-96x96.png" />
<link rel="icon" type="image/svg+xml" href="../favicon/favicon.svg" />
<link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png" />
<link rel="manifest" href="../favicon/site.webmanifest" />
<link rel="shortcut icon" href="../favicon/favicon.ico" />
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;1,9..144,400&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}

/* === DESIGN TOKENS — refinados con jerarquía y dark mode profundo === */
:root{
  --bg:#FAF8F4;--card:#FFFFFF;--card-alt:#F5F0E5;
  --text:#1A1816;--text2:#5A4A35;--text3:#8B7860;
  --border:#E5DECF;--border-soft:#F0EAD8;
  --hover:#FDFBF6;--row-alt:#FCFAF5;--row-border:#F2EDE3;
  --dk:#1A1816;--dk2:#2A2522;--gd:#C4A77D;--gdd:#7A6649;
  --gr:#1F8F47;--ars:#0782B8;--cr:#FAF8F4;
  --gd-soft:rgba(196,167,125,.08);--gd-glow:rgba(196,167,125,.18);
  --shadow-sm:0 1px 2px rgba(26,24,22,.04);
  --shadow:0 2px 10px rgba(26,24,22,.06);
  --shadow-lg:0 12px 32px rgba(26,24,22,.10);
  --radius:10px;--radius-sm:6px;
}
[data-theme="dark"]{
  --bg:#0A0907;--card:#16140F;--card-alt:#1F1B14;
  --text:#F5F0E5;--text2:#C4B596;--text3:#8B7860;
  --border:#2D2820;--border-soft:#221E18;
  --hover:#1F1B14;--row-alt:#191611;--row-border:#26221C;
  --gd-soft:rgba(196,167,125,.10);--gd-glow:rgba(196,167,125,.25);
  --gr:#34D670;--ars:#3CB8E8;
  --shadow-sm:0 1px 2px rgba(0,0,0,.4);
  --shadow:0 2px 10px rgba(0,0,0,.5);
  --shadow-lg:0 12px 32px rgba(0,0,0,.6);
}

body{font-family:'Manrope',sans-serif;background:var(--bg);color:var(--text);font-size:14px;line-height:1.5;transition:background .3s,color .3s;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}
html{overflow-x:hidden}

/* === HEADER (idéntico a calc.html) === */
.header{background:var(--dk);padding:16px 24px;display:flex;justify-content:space-between;align-items:center}
.header h1{font-family:'Fraunces',serif;color:var(--cr);font-size:1.4rem;font-weight:600}
.header span{color:var(--gd);font-size:.65rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase}
.header .h-right{display:flex;align-items:center;gap:16px}
.gold-line{height:3px;background:var(--gd)}

/* === THEME TOGGLE === */
.toggle{width:50px;height:28px;background:var(--dk2);border-radius:14px;cursor:pointer;position:relative;border:1px solid var(--gd);transition:background .3s,box-shadow .25s;box-shadow:0 1px 3px rgba(0,0,0,.08)}
.toggle:hover{box-shadow:0 2px 8px rgba(196,167,125,.25)}
.toggle::after{content:'☀️';position:absolute;left:3px;top:2px;width:22px;height:22px;font-size:13px;display:flex;align-items:center;justify-content:center;transition:transform .3s;background:var(--card);border-radius:50%;box-shadow:0 1px 2px rgba(0,0,0,.12)}
[data-theme="dark"] .toggle::after{content:'🌙';transform:translateX(22px)}
.logout-link{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;color:var(--gd);opacity:.5;transition:opacity .2s,color .2s;text-decoration:none;border-radius:50%}
.logout-link svg{width:14px;height:14px}
.logout-link:hover{opacity:1;color:var(--cr);background:rgba(196,167,125,.1)}

/* === TAB NAV === */
.h-tabs{background:var(--dk);display:flex;align-items:center;padding:0 24px;border-top:1px solid rgba(196,167,125,.12);overflow-x:auto;scrollbar-width:thin;scrollbar-color:var(--gd) transparent}
.h-tabs::-webkit-scrollbar{height:3px}
.h-tabs::-webkit-scrollbar-thumb{background:var(--gd)}
.h-tabs a{color:var(--gd);opacity:.55;text-decoration:none;font-family:'Manrope',sans-serif;font-size:.7rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;padding:11px 18px;transition:opacity .15s,color .15s,background .15s;white-space:nowrap;border-bottom:2px solid transparent}
.h-tabs a:hover{opacity:1;color:var(--cr);background:rgba(196,167,125,.05)}
.h-tabs a.active{opacity:1;color:var(--gd);border-bottom-color:var(--gd)}
@media(max-width:768px){.h-tabs{padding:0 8px}.h-tabs a{padding:9px 12px;font-size:.62rem;letter-spacing:.06em}}

/* === CONTAINER === */
.container{max-width:1320px;margin:0 auto;padding:28px 24px}
h1{font-family:'Fraunces',serif;font-size:26px;font-weight:600;margin-bottom:6px;color:var(--text);letter-spacing:-.005em}
.sub{color:var(--text2);font-size:13.5px;margin-bottom:22px;line-height:1.55;max-width:720px}

/* === FILTERS — más limpio, search icon, focus dorado === */
.filters{display:flex;gap:10px;align-items:center;flex-wrap:wrap;background:var(--card);padding:14px 16px;border-radius:var(--radius);border:1px solid var(--border);margin-bottom:18px;box-shadow:var(--shadow-sm)}
.filters .search-wrap{position:relative;flex:1;min-width:240px;max-width:380px}
.filters .search-wrap svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:var(--text3);pointer-events:none;transition:color .15s}
.filters .search-wrap:focus-within svg{color:var(--gd)}
.filters input,.filters select{padding:9px 13px;border:1px solid var(--border);border-radius:var(--radius-sm);font-family:inherit;font-size:13.5px;background:var(--card);color:var(--text);transition:all .15s;outline:none}
.filters .search-wrap input{padding-left:34px;width:100%}
.filters select{min-width:180px;cursor:pointer}
.filters input:hover,.filters select:hover{border-color:var(--gd)}
.filters input:focus,.filters select:focus{border-color:var(--gd);box-shadow:0 0 0 3px var(--gd-soft)}
.filters input::placeholder{color:var(--text3)}
.btn-search{padding:9px 18px;background:var(--dk);color:var(--cr);border:none;border-radius:var(--radius-sm);font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s;letter-spacing:.01em}
.btn-search:hover{background:var(--dk2);transform:translateY(-1px);box-shadow:var(--shadow-sm)}

/* === TABLE — moderna, zebra sutil, hover de fila completa === */
table{width:100%;border-collapse:separate;border-spacing:0;background:var(--card);border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow);border:1px solid var(--border)}
thead{background:var(--dk)}
th{background:var(--dk);color:var(--cr);text-align:left;padding:13px 14px;font-size:10.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;border-bottom:1px solid var(--dk2)}
th:first-child{padding-left:18px}
th:last-child{padding-right:18px}
td{padding:14px;border-bottom:1px solid var(--row-border);font-size:13.5px;vertical-align:middle;color:var(--text);transition:background .12s}
td:first-child{padding-left:18px}
td:last-child{padding-right:18px}
tbody tr:nth-child(even) td{background:var(--row-alt)}
tbody tr:hover td{background:var(--hover)}
tbody tr:last-child td{border-bottom:none}
td b{font-weight:700;color:var(--text)}
td small{color:var(--text3);font-size:11.5px;display:block;margin-top:2px}

/* === ESTADO BADGES — más prominentes con dot === */
.estado{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:12px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;line-height:1}
.estado::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor;flex-shrink:0}
.est-borrador{background:#F2EDE3;color:#7A6649}
.est-enviado{background:#E1EEF8;color:#0782B8}
.est-aprobado{background:#DDF2DD;color:#1F8F47}
.est-entregado{background:#1A1816;color:#C4A77D}
.est-perdido{background:#FDE6E6;color:#A53C3C}
[data-theme="dark"] .est-borrador{background:#2A241D;color:#C4A77D}
[data-theme="dark"] .est-enviado{background:#0A2533;color:#3CB8E8}
[data-theme="dark"] .est-aprobado{background:#0E2A18;color:#34D670}
[data-theme="dark"] .est-entregado{background:#2A2522;color:#D4B07A}
[data-theme="dark"] .est-perdido{background:#2A1414;color:#E26666}

/* === ACTION BUTTONS — agrupados por jerarquía === */
.actions-cell{display:flex;gap:5px;flex-wrap:wrap;align-items:center;min-width:340px}
.actions-cell button,.actions-cell a{font-size:11.5px;padding:6px 11px;border-radius:5px;cursor:pointer;text-decoration:none;font-family:inherit;font-weight:600;transition:all .15s;white-space:nowrap;border:1px solid transparent;display:inline-flex;align-items:center;gap:4px;line-height:1.2;letter-spacing:.005em}
/* Primary: + Nueva (filled gold, prominente) */
.actions-cell .act-primary{background:var(--gd);color:#1A1816;border-color:var(--gd)}
.actions-cell .act-primary:hover{background:var(--gdd);border-color:var(--gdd);color:var(--cr);transform:translateY(-1px);box-shadow:0 2px 8px var(--gd-glow)}
/* Utility: Ver PDF, Cargar (outline subtle) */
.actions-cell .act-util{background:transparent;color:var(--text2);border-color:var(--border)}
.actions-cell .act-util:hover{background:var(--gd-soft);color:var(--gdd);border-color:var(--gd)}
/* Transition: → Estado (outline, hover muestra color del target) */
.actions-cell .act-trans{background:transparent;color:var(--text2);border-color:var(--border)}
.actions-cell .act-trans:hover{background:var(--gd-soft);color:var(--gdd);border-color:var(--gd)}
.actions-cell .act-trans-enviado:hover{background:#E1EEF8;color:#0782B8;border-color:#0782B8}
.actions-cell .act-trans-aprobado:hover{background:#DDF2DD;color:#1F8F47;border-color:#1F8F47}
.actions-cell .act-trans-entregado:hover{background:#1A1816;color:#C4A77D;border-color:#1A1816}
.actions-cell .act-trans-perdido:hover{background:#FDE6E6;color:#A53C3C;border-color:#A53C3C}
[data-theme="dark"] .actions-cell .act-trans-enviado:hover{background:#0A2533;color:#3CB8E8;border-color:#3CB8E8}
[data-theme="dark"] .actions-cell .act-trans-aprobado:hover{background:#0E2A18;color:#34D670;border-color:#34D670}
[data-theme="dark"] .actions-cell .act-trans-entregado:hover{background:#2A2522;color:#C4A77D;border-color:#C4A77D}
[data-theme="dark"] .actions-cell .act-trans-perdido:hover{background:#2A1414;color:#E26666;border-color:#E26666}
/* ZIP: distinct */
.actions-cell .act-zip{background:transparent;color:#1F8F47;border-color:#9DD9B0;font-weight:700}
.actions-cell .act-zip:hover{background:#DDF2DD;border-color:#1F8F47;transform:translateY(-1px)}
[data-theme="dark"] .actions-cell .act-zip{color:#34D670;border-color:#1A4525}
[data-theme="dark"] .actions-cell .act-zip:hover{background:#0E2A18;border-color:#34D670}
/* Danger: Eliminar */
.actions-cell .act-danger{background:transparent;color:#A53C3C;border-color:#F0C8C8;margin-left:auto}
.actions-cell .act-danger:hover{background:#A53C3C;color:#fff;border-color:#A53C3C}
[data-theme="dark"] .actions-cell .act-danger{color:#E26666;border-color:#4A1E1E}
[data-theme="dark"] .actions-cell .act-danger:hover{background:#A53C3C;color:#fff;border-color:#A53C3C}
/* Separador visual entre grupos */
.actions-cell .sep{display:inline-block;width:1px;height:18px;background:var(--border-soft);margin:0 2px}

/* === EMPTY / LOADING === */
.empty{text-align:center;padding:80px 20px;color:var(--text3);font-style:italic;font-size:13.5px}
.loading{text-align:center;padding:60px 20px;color:var(--text3);font-style:italic;font-size:13.5px}

/* === STAT CARDS — modernos, hover lift === */
.stat-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:24px}
.stat-card{background:var(--card);padding:20px 22px;border-radius:var(--radius);border:1px solid var(--border);position:relative;overflow:hidden;box-shadow:var(--shadow-sm);transition:transform .18s ease,box-shadow .18s ease,border-color .18s}
.stat-card:hover{transform:translateY(-2px);box-shadow:var(--shadow);border-color:var(--gd)}
.stat-card::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:linear-gradient(180deg,var(--gd) 0%,var(--gdd) 100%);opacity:.7}
.stat-label{font-size:10.5px;color:var(--text2);text-transform:uppercase;letter-spacing:.08em;font-weight:700}
.stat-value{font-family:'Fraunces',serif;font-size:28px;font-weight:600;color:var(--text);margin-top:6px;letter-spacing:-.01em;line-height:1.1}

/* === BANNER === */
.banner{background:var(--gd-soft);border-left:3px solid var(--gd);padding:12px 16px;border-radius:var(--radius-sm);font-size:13px;color:var(--gdd);margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;gap:12px}
[data-theme="dark"] .banner{color:var(--text2)}
.banner button{background:var(--gd);color:var(--dk);border:none;padding:7px 16px;border-radius:var(--radius-sm);font-size:11.5px;font-weight:700;cursor:pointer;transition:all .15s;letter-spacing:.02em}
.banner button:hover{background:var(--gdd);color:var(--cr);transform:translateY(-1px)}

/* === PAGINATION === */
.pagination{margin-top:18px;text-align:center;font-size:12.5px;color:var(--text2);display:flex;align-items:center;justify-content:center;gap:8px}
.pagination button{padding:6px 13px;border:1px solid var(--border);background:var(--card);color:var(--gdd);border-radius:var(--radius-sm);cursor:pointer;font-size:12px;font-weight:600;transition:all .15s}
.pagination button:hover:not(:disabled){background:var(--gd-soft);border-color:var(--gd)}
.pagination button:disabled{opacity:.35;cursor:not-allowed}

/* === MISC === */
.num{text-align:right;font-variant-numeric:tabular-nums;font-weight:600}

/* === MODAL === */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:100;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
.modal-overlay.show{display:flex}
.modal{background:var(--card);border-radius:var(--radius);padding:26px;max-width:480px;width:90%;box-shadow:var(--shadow-lg);border:1px solid var(--border)}
.modal h3{font-family:'Fraunces',serif;font-size:19px;margin-bottom:10px;color:var(--text)}
.modal p{font-size:13.5px;color:var(--text);margin-bottom:18px;line-height:1.55}
.modal .modal-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:18px}
.modal button{padding:9px 18px;border-radius:var(--radius-sm);font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;border:1px solid transparent;transition:all .15s}
.modal .btn-confirm{background:var(--dk);color:var(--cr)}
.modal .btn-confirm:hover{background:var(--dk2);transform:translateY(-1px)}
.modal .btn-cancel{background:var(--card);border-color:var(--border);color:var(--text)}
.modal .btn-cancel:hover{background:var(--card-alt)}
.modal .btn-danger{background:#A53C3C;color:#fff}
.modal .btn-danger:hover{background:#8C2E2E;transform:translateY(-1px)}

/* === TOP MATERIALES === */
.top-mat-list{background:var(--card);padding:18px 20px;border-radius:var(--radius);border:1px solid var(--border);box-shadow:var(--shadow-sm)}
.top-mat-item{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--row-border);font-size:13px;color:var(--text);gap:12px}
.top-mat-item:last-child{border-bottom:none}
.top-mat-item .count{color:var(--text3);font-size:11.5px;margin-left:auto;padding:0 12px;font-style:italic}

/* === CONCEPTO INLINE EDIT === */
.concepto-cell{display:inline-block;min-width:90px;padding:4px 8px;border-radius:5px;border:1px dashed transparent;cursor:text;transition:all .15s;font-size:13px;color:var(--text);outline:none}
.concepto-cell:hover{border-color:var(--gd);background:var(--gd-soft)}
.concepto-cell:focus{border-color:var(--gd);border-style:solid;background:var(--card-alt);box-shadow:0 0 0 3px var(--gd-soft)}
.concepto-cell.saving{opacity:.5}
.concepto-cell.saved{border-color:#1F8F47;background:rgba(31,143,71,.08)}
.concepto-cell.error{border-color:#A53C3C;background:rgba(165,60,60,.08)}

/* === SECTION HEADERS for stats === */
.sec-h{margin:28px 0 12px;font-family:'Fraunces',serif;font-weight:600;color:var(--text);font-size:18px;letter-spacing:-.005em;display:flex;align-items:center;gap:10px}
.sec-h::before{content:'';width:4px;height:18px;background:var(--gd);border-radius:2px}

@media(max-width:1100px){
  .actions-cell{min-width:auto}
  .actions-cell .act-danger{margin-left:0}
}
@media(max-width:768px){
  .header{padding:10px 14px}
  .header h1{font-size:1.1rem}
  .header span{display:none}
  .header img{height:30px!important}
  .header .h-right{gap:8px}
  .toggle{width:42px;height:24px}
  .toggle::after{width:18px;height:18px;font-size:11px;left:2px;top:2px}
  [data-theme="dark"] .toggle::after{transform:translateX(20px)}
  .container{padding:16px 12px}
  h1{font-size:22px}
  .sub{font-size:12.5px}
  .filters{padding:10px;gap:8px}
  .filters .search-wrap{min-width:100%;max-width:100%}
  .filters select{min-width:140px;font-size:12.5px}
  .btn-search{padding:9px 16px;font-size:12.5px}
  .stat-cards{grid-template-columns:repeat(2,1fr);gap:10px}
  .stat-card{padding:14px 16px}
  .stat-value{font-size:22px}
  table{font-size:12.5px;display:block;overflow-x:auto;-webkit-overflow-scrolling:touch}
  th,td{padding:10px 8px;white-space:nowrap}
  th:first-child,td:first-child{padding-left:12px}
  .actions-cell{min-width:280px}
  .actions-cell button,.actions-cell a{font-size:11px;padding:5px 9px}
}
</style>
</head>
<body>

<div class="header">
  <div style="display:flex;align-items:center;gap:12px">
    <img src="../logo/logo_isotipo.png" alt="BlackStones" style="height:36px;width:auto">
    <div><h1>BlackStones</h1><span><?= htmlspecialchars($tab_label) ?></span></div>
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
  <a href="/presupuestos/?tab=activos" class="<?= $tab==='activos' ? 'active' : '' ?>">Presupuestos · Activos</a>
  <a href="/presupuestos/?tab=reporte" class="<?= $tab==='reporte' ? 'active' : '' ?>">Presupuestos · Reporte</a>
</nav>
<div class="gold-line"></div>

<div class="container">
<?php if ($tab === 'activos'): ?>
  <h1>Presupuestos activos</h1>
  <div class="sub">Cotizaciones en proceso. Se eliminan automáticamente a los 60 días si no llegan a entregado.</div>

  <div class="filters">
    <div class="search-wrap">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="filter-q" placeholder="Buscar por nombre, celular, DNI, N°...">
    </div>
    <select id="filter-estado">
      <option value="">Todos los estados</option>
      <option value="borrador">Borrador</option>
      <option value="enviado">Enviado</option>
      <option value="aprobado">Aprobado</option>
      <option value="entregado">Entregado</option>
      <option value="perdido">Perdido</option>
    </select>
    <button class="btn-search" onclick="cargarActivos()">Buscar</button>
  </div>

  <table id="activos-tabla">
    <thead>
      <tr>
        <th>N°</th>
        <th>Cliente</th>
        <th>Celular</th>
        <th>Concepto</th>
        <th>Estado</th>
        <th class="num">USD</th>
        <th class="num">ARS</th>
        <th>Fecha</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody id="activos-body">
      <tr><td colspan="9" class="loading">Cargando...</td></tr>
    </tbody>
  </table>

  <div class="pagination" id="activos-pagination"></div>

<?php else: ?>
  <h1>Reporte — Histórico de entregados</h1>
  <div class="sub">Dashboard de cotizaciones entregadas. Solo cuentan los presupuestos que llegaron a estado "entregado".</div>

  <div id="reporte-content">
    <div class="loading">Cargando...</div>
  </div>
<?php endif; ?>
</div>

<!-- Modal de confirmación genérico -->
<div class="modal-overlay" id="modal" onclick="if(event.target===this)cerrarModal()">
  <div class="modal">
    <h3 id="modal-title">Confirmación</h3>
    <p id="modal-body"></p>
    <div class="modal-actions">
      <button class="btn-cancel" onclick="cerrarModal()">Cancelar</button>
      <button class="btn-confirm" id="modal-confirm-btn">Confirmar</button>
    </div>
  </div>
</div>

<script>
// === THEME TOGGLE (mismo que calc) ===
function toggleTheme(){
  const html=document.documentElement;
  const isDark=html.getAttribute('data-theme')==='dark';
  html.setAttribute('data-theme',isDark?'light':'dark');
  localStorage.setItem('bs-theme',isDark?'light':'dark');
}
(function(){
  const saved=localStorage.getItem('bs-theme');
  if(saved==='dark')document.documentElement.setAttribute('data-theme','dark');
})();

// ============================================================
// Tab Activos: listing + filtros + acciones
// ============================================================
let _activosPage = 1;
const _activosPerPage = 50;

const ESTADOS_LABELS = {
  borrador: 'Borrador',
  enviado: 'Enviado',
  aprobado: 'Aprobado',
  entregado: 'Entregado',
  perdido: 'Perdido'
};

const TRANSICIONES = {
  borrador: ['enviado', 'perdido'],
  enviado: ['aprobado', 'perdido'],
  aprobado: ['entregado'],
  entregado: [],
  perdido: []
};

function fmtUSD(n) { return n > 0 ? 'USD ' + n.toLocaleString('es-AR', {minimumFractionDigits: 2}) : '—'; }
function fmtARS(n) { return n > 0 ? '$ ' + n.toLocaleString('es-AR') : '—'; }

async function cargarActivos() {
  const q = document.getElementById('filter-q').value.trim();
  const estado = document.getElementById('filter-estado').value;
  const url = `/calculadora/api/list.php?page=${_activosPage}&per_page=${_activosPerPage}` +
              (q ? '&q=' + encodeURIComponent(q) : '') +
              (estado ? '&estado=' + estado : '');
  try {
    const r = await fetch(url, { credentials: 'same-origin' });
    const d = await r.json();
    if (!d.ok) {
      document.getElementById('activos-body').innerHTML = `<tr><td colspan="9" class="empty">Error: ${d.error}</td></tr>`;
      return;
    }
    if (d.results.length === 0) {
      document.getElementById('activos-body').innerHTML = `<tr><td colspan="9" class="empty">Sin resultados</td></tr>`;
      document.getElementById('activos-pagination').innerHTML = '';
      return;
    }
    const rows = d.results.map(r => {
      const transitions = (TRANSICIONES[r.estado] || []).map(t =>
        `<button class="act-trans act-trans-${t}" onclick="cambiarEstado('${r.cliente_nro}', ${r.sub}, '${t}')">→ ${ESTADOS_LABELS[t]}</button>`
      ).join('');
      const verPdf = `<a class="act-util" href="/calculadora/api/render-pdf.php?nro=${r.cliente_nro}&sub=${r.sub}" target="_blank">Ver PDF</a>`;
      const cargar = `<a class="act-util" href="/calculadora/?load=${r.cliente_nro}-${r.sub}" target="_blank">Cargar</a>`;
      const nueva = `<a class="act-primary" href="/calculadora/?load=${r.cliente_nro}-${r.sub}&blank=1" target="_blank" title="Cotizar otra cosa para este mismo cliente">+ Nueva cotización</a>`;
      const zip = r.estado === 'entregado'
        ? `<a class="act-zip" href="/calculadora/api/download-zip.php?nro=${r.cliente_nro}&sub=${r.sub}">⬇ ZIP</a>`
        : '';
      const del = `<button class="act-danger" onclick="confirmarBorrar('${r.cliente_nro}', ${r.sub}, '${(r.cliente_nombre || '').replace(/'/g, "\\'")}')">Eliminar</button>`;
      return `
        <tr>
          <td><b>${r.cliente_nro}-${r.sub}</b></td>
          <td>${escapeHtml(r.cliente_nombre || '—')}<br><small style="color:var(--text2)">${escapeHtml(r.cliente_dni || '')}</small></td>
          <td>${escapeHtml(r.cliente_celular || '—')}</td>
          <td><span class="concepto-cell" contenteditable="true" data-nro="${r.cliente_nro}" data-sub="${r.sub}" data-orig="${escapeHtml(r.concepto || '')}" onblur="onConceptoBlur(this)" onkeydown="if(event.key==='Enter'){event.preventDefault();this.blur();}" title="Click para editar el concepto">${escapeHtml(r.concepto || '—')}</span></td>
          <td><span class="estado est-${r.estado}">${ESTADOS_LABELS[r.estado] || r.estado}</span></td>
          <td class="num">${fmtUSD(r.monto_usd)}</td>
          <td class="num">${fmtARS(r.monto_ars)}</td>
          <td>${escapeHtml((r.fecha || '').substring(0, 10))}</td>
          <td class="actions-cell">${nueva} ${verPdf} ${cargar} ${zip} ${transitions} ${del}</td>
        </tr>
      `;
    }).join('');
    document.getElementById('activos-body').innerHTML = rows;

    const totalPages = Math.ceil(d.total / d.per_page);
    document.getElementById('activos-pagination').innerHTML = totalPages > 1
      ? `<button onclick="_activosPage=Math.max(1,_activosPage-1);cargarActivos()" ${_activosPage<=1?'disabled':''}>← Anterior</button> Página ${d.page} de ${totalPages} · ${d.total} resultados <button onclick="_activosPage=Math.min(${totalPages},_activosPage+1);cargarActivos()" ${_activosPage>=totalPages?'disabled':''}>Siguiente →</button>`
      : `${d.total} resultados`;
  } catch (e) {
    document.getElementById('activos-body').innerHTML = `<tr><td colspan="9" class="empty">No se pudo cargar (${e.message})</td></tr>`;
  }
}

function escapeHtml(s) {
  return String(s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

async function cambiarEstado(nro, sub, nuevo) {
  if (nuevo === 'entregado') {
    abrirModal(
      'Confirmar entrega',
      `Al marcar como ENTREGADO:<br>
       • Las otras sub-versiones del cliente (borradores, enviados, aprobados, perdidos) se eliminarán inmediatamente.<br>
       • Esta cotización se conserva 60 días — después se borra automáticamente.<br>
       • Se generará automáticamente un ZIP descargable durante esos 60 días.<br>
       <br>
       ¿Confirmar entrega?`,
      async () => {
        cerrarModal();
        await ejecutarCambioEstado(nro, sub, nuevo);
      },
      'btn-confirm'
    );
  } else {
    await ejecutarCambioEstado(nro, sub, nuevo);
  }
}

async function ejecutarCambioEstado(nro, sub, nuevo) {
  try {
    const r = await fetch('/calculadora/api/state.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ cliente_nro: nro, sub: sub, nuevo_estado: nuevo })
    });
    const d = await r.json();
    if (!d.ok) {
      alert('Error: ' + d.error);
      return;
    }
    let msg = `Estado: ${d.estado_anterior} → ${d.estado_nuevo}`;
    if (d.siblings_eliminadas > 0) msg += `\n${d.siblings_eliminadas} hermana(s) eliminada(s).`;
    if (d.zip_path) msg += `\nZIP generado y disponible para descarga.`;
    alert(msg);
    cargarActivos();
  } catch (e) {
    alert('Error: ' + e.message);
  }
}

function confirmarBorrar(nro, sub, nombre) {
  abrirModal(
    'Eliminar presupuesto',
    `¿Eliminar el presupuesto <b>${nro}-${sub}</b> de <b>${nombre}</b>?<br><br>Esta acción no se puede deshacer.`,
    async () => {
      cerrarModal();
      try {
        const r = await fetch('/calculadora/api/delete.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ cliente_nro: nro, sub: sub })
        });
        const d = await r.json();
        if (!d.ok) { alert('Error: ' + d.error); return; }
        cargarActivos();
      } catch (e) {
        alert('Error: ' + e.message);
      }
    },
    'btn-danger'
  );
}

async function onConceptoBlur(el) {
  const nro = el.dataset.nro;
  const sub = parseInt(el.dataset.sub);
  const orig = el.dataset.orig || '';
  let nuevo = el.innerText.trim();
  if (nuevo === '—') nuevo = '';
  if (nuevo === orig) {
    el.innerText = nuevo || '—';
    return;
  }
  el.classList.add('saving');
  try {
    const r = await fetch('/calculadora/api/edit-concepto.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ cliente_nro: nro, sub: sub, concepto: nuevo })
    });
    const d = await r.json();
    el.classList.remove('saving');
    if (!d.ok) {
      el.classList.add('error');
      el.innerText = orig || '—';
      setTimeout(() => el.classList.remove('error'), 2000);
      alert('Error guardando concepto: ' + d.error);
      return;
    }
    el.dataset.orig = nuevo;
    el.innerText = nuevo || '—';
    el.classList.add('saved');
    setTimeout(() => el.classList.remove('saved'), 1500);
  } catch (e) {
    el.classList.remove('saving');
    el.classList.add('error');
    el.innerText = orig || '—';
    setTimeout(() => el.classList.remove('error'), 2000);
    alert('Error: ' + e.message);
  }
}

function abrirModal(title, body, onConfirm, btnClass) {
  document.getElementById('modal-title').innerText = title;
  document.getElementById('modal-body').innerHTML = body;
  const btn = document.getElementById('modal-confirm-btn');
  btn.className = btnClass || 'btn-confirm';
  btn.onclick = onConfirm;
  document.getElementById('modal').classList.add('show');
}
function cerrarModal() {
  document.getElementById('modal').classList.remove('show');
}

// ============================================================
// Tab Reporte: dashboard
// ============================================================
async function cargarReporte() {
  const cont = document.getElementById('reporte-content');
  try {
    const r = await fetch('/calculadora/api/report-summary.php', { credentials: 'same-origin' });
    const d = await r.json();
    if (!d.ok) { cont.innerHTML = `<div class="empty">Error: ${d.error}</div>`; return; }

    const totals = d.totales || {};
    const tops = d.top_materiales || [];
    const meses = d.meses || [];
    const banner = d.banner_mes_pendiente
      ? `<div class="banner"><span>📊 Reporte de <b>${d.banner_mes_pendiente}</b> generado. ¿Lo viste?</span><button onclick="marcarVisto('${d.banner_mes_pendiente}')">Marcar como visto</button></div>`
      : '';
    const statCards = `
      <div class="stat-cards">
        <div class="stat-card"><div class="stat-label">Entregados</div><div class="stat-value">${totals.entregados_count || 0}</div></div>
        <div class="stat-card"><div class="stat-label">Total USD</div><div class="stat-value">${fmtUSD(totals.monto_usd || 0)}</div></div>
        <div class="stat-card"><div class="stat-label">Total ARS</div><div class="stat-value">${fmtARS(totals.monto_ars || 0)}</div></div>
        <div class="stat-card"><div class="stat-label">Clientes únicos</div><div class="stat-value">${totals.clientes_unicos || 0}</div></div>
      </div>
    `;
    const topMat = tops.length ? `
      <h3 class="sec-h">Lo más entregado</h3>
      <div class="top-mat-list">
        ${tops.map((m, i) => `
          <div class="top-mat-item">
            <span><b>${i+1}.</b> ${escapeHtml(m.material_color || '')}</span>
            <span class="count">${m.count} entregas</span>
            <span><b>${fmtUSD(m.monto_usd || 0)}</b></span>
          </div>`).join('')}
      </div>
    ` : '';
    const mesesTable = meses.length ? `
      <h3 class="sec-h">Por mes</h3>
      <table>
        <thead><tr><th>Mes</th><th class="num">Entregados</th><th class="num">USD</th><th class="num">ARS</th><th>Fuente</th></tr></thead>
        <tbody>
          ${meses.map(m => `
            <tr>
              <td><b>${m.mes}</b></td>
              <td class="num">${m.entregados_count || 0}</td>
              <td class="num">${fmtUSD(m.monto_usd || 0)}</td>
              <td class="num">${fmtARS(m.monto_ars || 0)}</td>
              <td><small style="color:var(--text2)">${m.source === 'archivo' ? '📁 archivo' : '🔄 live'}</small></td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    ` : '<div class="empty">Sin entregados todavía.</div>';

    cont.innerHTML = banner + statCards + topMat + mesesTable;
  } catch (e) {
    cont.innerHTML = `<div class="empty">No se pudo cargar el reporte (${e.message})</div>`;
  }
}

async function marcarVisto(mes) {
  try {
    await fetch('/calculadora/api/mark-viewed.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ mes })
    });
    cargarReporte();
  } catch(e) {}
}

// Init según tab
const _tab = '<?= $tab ?>';
if (_tab === 'activos') {
  cargarActivos();
  document.getElementById('filter-q').addEventListener('keypress', e => { if (e.key === 'Enter') cargarActivos(); });
  document.getElementById('filter-estado').addEventListener('change', cargarActivos);
} else {
  cargarReporte();
}
</script>
</body>
</html>
