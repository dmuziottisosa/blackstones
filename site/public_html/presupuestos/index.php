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

/* === THEME VARIABLES (espejo de calc.html) === */
:root{
  --bg:#FAF8F4;--card:#FFFFFF;--card-alt:#F2EDE3;
  --text:#1A1816;--text2:#6B6560;--border:#E5DECF;
  --hover:#FAF8F4;--row-border:#F2EDE3;
  --dk:#1A1816;--dk2:#2A2522;--gd:#C4A77D;--gdd:#7A6649;
  --gr:#25D366;--ars:#0EA5E9;--cr:#FAF8F4;
}
[data-theme="dark"]{
  --bg:#0E0D0C;--card:#1A1816;--card-alt:#2A2522;
  --text:#FAF8F4;--text2:#A89580;--border:#3A3530;
  --hover:#2A2522;--row-border:#3A3530;
}

body{font-family:'Manrope',sans-serif;background:var(--bg);color:var(--text);font-size:14px;transition:background .3s,color .3s}
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

/* === TAB NAV (cross-surface) === */
.h-tabs{background:var(--dk);display:flex;align-items:center;padding:0 24px;border-top:1px solid rgba(196,167,125,.12);overflow-x:auto;scrollbar-width:thin;scrollbar-color:var(--gd) transparent}
.h-tabs::-webkit-scrollbar{height:3px}
.h-tabs::-webkit-scrollbar-thumb{background:var(--gd)}
.h-tabs a{color:var(--gd);opacity:.55;text-decoration:none;font-family:'Manrope',sans-serif;font-size:.7rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;padding:11px 18px;transition:opacity .15s,color .15s,background .15s;white-space:nowrap;border-bottom:2px solid transparent}
.h-tabs a:hover{opacity:1;color:var(--cr);background:rgba(196,167,125,.05)}
.h-tabs a.active{opacity:1;color:var(--gd);border-bottom-color:var(--gd)}
@media(max-width:768px){.h-tabs{padding:0 8px}.h-tabs a{padding:9px 12px;font-size:.62rem;letter-spacing:.06em}}

/* === CONTENT === */
.container{max-width:1280px;margin:0 auto;padding:24px}
h1{font-family:'Fraunces',serif;font-size:24px;font-weight:600;margin-bottom:8px;color:var(--text)}
.sub{color:var(--text2);font-size:13px;margin-bottom:20px}
.filters{display:flex;gap:12px;align-items:center;flex-wrap:wrap;background:var(--card);padding:12px 16px;border-radius:8px;border:1px solid var(--border);margin-bottom:16px}
.filters input,.filters select{padding:8px 12px;border:1px solid var(--border);border-radius:5px;font-family:inherit;font-size:13px;background:var(--card);color:var(--text);min-width:160px}
.filters input:focus,.filters select:focus{outline:none;border-color:var(--gd);box-shadow:0 0 0 2px rgba(196,167,125,.2)}
.filters button.btn-primary{padding:8px 16px;background:var(--dk);color:var(--cr);border:none;border-radius:5px;font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;transition:background .15s}
.filters button.btn-primary:hover{background:var(--dk2)}
table{width:100%;border-collapse:collapse;background:var(--card);border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04)}
th{background:var(--dk);color:var(--cr);text-align:left;padding:10px 12px;font-size:11px;font-weight:600;letter-spacing:.04em;text-transform:uppercase}
td{padding:10px 12px;border-bottom:1px solid var(--row-border);font-size:13px;vertical-align:top;color:var(--text)}
tr:hover td{background:var(--hover)}
.estado{display:inline-block;padding:2px 8px;border-radius:3px;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.04em}
.est-borrador{background:#F2EDE3;color:#7A6649}
.est-enviado{background:#E5F0F8;color:#0EA5E9}
.est-aprobado{background:#E5F8E5;color:#25A036}
.est-entregado{background:#1A1816;color:#C4A77D}
.est-perdido{background:#FDF1F1;color:#C44747}
.actions-cell{white-space:nowrap}
.actions-cell button,.actions-cell a{font-size:11px;padding:4px 10px;border-radius:4px;border:1px solid var(--gd);background:var(--card);color:var(--gdd);cursor:pointer;text-decoration:none;font-family:inherit;font-weight:500;margin-right:4px;transition:all .15s}
.actions-cell button:hover,.actions-cell a:hover{background:var(--gd);color:var(--dk)}
.actions-cell .danger{border-color:#C44747;color:#C44747}
.actions-cell .danger:hover{background:#C44747;color:#fff}
.empty{text-align:center;padding:60px 20px;color:var(--text2)}
.stat-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:20px}
.stat-card{background:var(--card);padding:16px 20px;border-radius:8px;border:1px solid var(--border);position:relative;overflow:hidden}
.stat-card::before{content:'';position:absolute;top:0;left:0;width:3px;height:100%;background:var(--gd);opacity:.6}
.stat-label{font-size:10px;color:var(--text2);text-transform:uppercase;letter-spacing:.06em;font-weight:600}
.stat-value{font-family:'Fraunces',serif;font-size:24px;font-weight:600;color:var(--text);margin-top:4px}
.banner{background:rgba(196,167,125,.08);border-left:3px solid var(--gd);padding:10px 14px;border-radius:4px;font-size:12px;color:var(--gdd);margin-bottom:16px;display:flex;justify-content:space-between;align-items:center}
.banner button{background:var(--gd);color:var(--dk);border:none;padding:6px 14px;border-radius:4px;font-size:11px;font-weight:600;cursor:pointer}
.pagination{margin-top:16px;text-align:center;font-size:12px;color:var(--text2)}
.pagination button{margin:0 4px;padding:4px 10px;border:1px solid var(--gd);background:var(--card);color:var(--gdd);border-radius:4px;cursor:pointer;font-size:11px;font-weight:500}
.pagination button:disabled{opacity:.4;cursor:not-allowed}
.num{text-align:right;font-variant-numeric:tabular-nums}
.loading{text-align:center;padding:40px;color:var(--text2);font-style:italic}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:100;align-items:center;justify-content:center}
.modal-overlay.show{display:flex}
.modal{background:var(--card);border-radius:8px;padding:24px;max-width:480px;width:90%;box-shadow:0 12px 40px rgba(0,0,0,.2);border:1px solid var(--border)}
.modal h3{font-family:'Fraunces',serif;font-size:18px;margin-bottom:8px;color:var(--text)}
.modal p{font-size:13px;color:var(--text);margin-bottom:16px;line-height:1.5}
.modal .modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:16px}
.modal button{padding:8px 16px;border-radius:5px;font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;border:1px solid transparent}
.modal .btn-confirm{background:var(--dk);color:var(--cr)}
.modal .btn-cancel{background:var(--card);border-color:var(--border);color:var(--text)}
.modal .btn-danger{background:#C44747;color:#fff}
.top-mat-list{background:var(--card);padding:16px;border-radius:8px;border:1px solid var(--border)}
.top-mat-item{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--row-border);font-size:13px;color:var(--text)}
.top-mat-item:last-child{border-bottom:none}
.top-mat-item .count{color:var(--text2);font-size:11px;margin-left:auto;padding:0 12px}

@media(max-width:768px){
  .header{padding:10px 14px}
  .header h1{font-size:1.1rem}
  .header span{display:none}
  .header img{height:30px!important}
  .header .h-right{gap:8px}
  .toggle{width:42px;height:24px}
  .toggle::after{width:18px;height:18px;font-size:11px;left:2px;top:2px}
  [data-theme="dark"] .toggle::after{transform:translateX(20px)}
  .container{padding:14px 10px}
  .filters{padding:10px}
  .filters input,.filters select{min-width:140px;font-size:12px}
  table{font-size:12px;display:block;overflow-x:auto;-webkit-overflow-scrolling:touch}
  th,td{padding:8px;white-space:nowrap}
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
    <input type="text" id="filter-q" placeholder="Buscar por nombre, celular, DNI, N°...">
    <select id="filter-estado">
      <option value="">Todos los estados</option>
      <option value="borrador">Borrador</option>
      <option value="enviado">Enviado</option>
      <option value="aprobado">Aprobado</option>
      <option value="entregado">Entregado</option>
      <option value="perdido">Perdido</option>
    </select>
    <button class="btn-primary" onclick="cargarActivos()">Buscar</button>
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
        `<button onclick="cambiarEstado('${r.cliente_nro}', ${r.sub}, '${t}')">→ ${ESTADOS_LABELS[t]}</button>`
      ).join('');
      const verPdf = `<a href="/calculadora/api/render-pdf.php?nro=${r.cliente_nro}&sub=${r.sub}" target="_blank">Ver PDF</a>`;
      const cargar = `<a href="/calculadora/?load=${r.cliente_nro}-${r.sub}" target="_blank">Cargar</a>`;
      const nueva = `<a href="/calculadora/?load=${r.cliente_nro}-${r.sub}&blank=1" target="_blank" title="Cotizar otra cosa para este mismo cliente">+ Nueva cotización</a>`;
      const zip = r.estado === 'entregado'
        ? `<a href="/calculadora/api/download-zip.php?nro=${r.cliente_nro}&sub=${r.sub}">⬇ ZIP</a>`
        : '';
      const del = `<button class="danger" onclick="confirmarBorrar('${r.cliente_nro}', ${r.sub}, '${(r.cliente_nombre || '').replace(/'/g, "\\'")}')">Eliminar</button>`;
      return `
        <tr>
          <td><b>${r.cliente_nro}-${r.sub}</b></td>
          <td>${escapeHtml(r.cliente_nombre || '—')}<br><small style="color:var(--text2)">${escapeHtml(r.cliente_dni || '')}</small></td>
          <td>${escapeHtml(r.cliente_celular || '—')}</td>
          <td>${escapeHtml(r.concepto || '—')}</td>
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
      <h3 style="margin:24px 0 10px;font-family:'Fraunces',serif;color:var(--text)">Lo más entregado</h3>
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
      <h3 style="margin:24px 0 10px;font-family:'Fraunces',serif;color:var(--text)">Por mes</h3>
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
