// Mide el contraste de cada texto del hero de la landing contra lo que tiene detrás (la foto más el sombreado),
// en varios anchos de computadora. Si se cambia la foto o el sombreado del hero, se corre antes de entregar.
//
//     node 04-operations/tools/contraste-hero.js [url]          (por defecto, https://blackstones.com.ar/)
//
// Necesita Playwright (en las sesiones en la nube ya viene instalado). No toca nada: abre la página y mide.
// De cada texto da dos números: contra el promedio del fondo, y contra "lo más claro" (el 2 % más claro de lo
// que tiene detrás, que es donde peor se lee). El texto se vuelve transparente pero conserva su sombra, así
// se mide el fondo tal cual queda detrás de las letras. Se miden los renglones de texto, no la caja entera.
// Qué se le pide: 4,5 a 1 al texto chico y 3 a 1 al título (letra grande), también en lo más claro.
const { chromium } = require('playwright');
const URL = process.argv[2] || 'https://blackstones.com.ar/';
const ANCHOS = [[900, 800], [1024, 768], [1199, 800], [1200, 800], [1280, 800], [1366, 650], [1440, 790], [1536, 730], [1905, 955], [2560, 1300]];
const lin = v => { v /= 255; return v <= 0.04045 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
const L = c => 0.2126 * lin(c[0]) + 0.7152 * lin(c[1]) + 0.0722 * lin(c[2]);
(async () => {
  const b = await chromium.launch(); let flojo = 0;
  for (const [w, h] of ANCHOS) {
    const p = await (await b.newContext({ viewport: { width: w, height: h } })).newPage();
    await p.goto(URL, { waitUntil: 'networkidle' }); await p.waitForTimeout(1200);
    const cajas = await p.evaluate(() => {
      const renglones = (sel, chico) => { const e = document.querySelector(sel), r = document.createRange(); r.selectNodeContents(e); const c = getComputedStyle(e).color.match(/[\d.]+/g).map(Number);
        return [...r.getClientRects()].map(x => ({ x: Math.floor(x.left), y: Math.floor(x.top), w: Math.ceil(x.width), h: Math.ceil(x.height), color: c, chico })); };
      const res = { 'rótulo': renglones('.hero-eyebrow', true), 'título': renglones('.hero h1', false), 'título, dorado': renglones('.hero h1 em', false), 'bajada': renglones('.hero-sub', true), 'ver catálogo': renglones('.hero-ctas .btn-ghost', true) };
      const st = document.createElement('style'); st.textContent = '.hero-content, .hero-content *{-webkit-text-fill-color:transparent !important} .hero-ctas .btn-ghost{border-color:transparent !important}'; document.head.appendChild(st);
      return res; });
    const png = await p.screenshot(), p2 = await b.newPage();
    const datos = await p2.evaluate(async ({ b64, cajas }) => {
      const im = new Image(); im.src = 'data:image/png;base64,' + b64; await im.decode();
      const c = document.createElement('canvas'); c.width = im.width; c.height = im.height; const x = c.getContext('2d'); x.drawImage(im, 0, 0);
      const out = {};
      for (const [k, rs] of Object.entries(cajas)) out[k] = rs.filter(r => r.w > 0 && r.h > 0).map(r => { const d = x.getImageData(r.x, r.y, r.w, r.h).data, px = []; let n = 0; const s = [0, 0, 0];
        for (let i = 0; i < d.length; i += 4) { n++; s[0] += d[i]; s[1] += d[i + 1]; s[2] += d[i + 2]; px.push([d[i] * .2126 + d[i + 1] * .7152 + d[i + 2] * .0722, d[i], d[i + 1], d[i + 2]]); }
        px.sort((a, b) => a[0] - b[0]); const q = px[Math.floor(px.length * 0.98)];
        return { prom: s.map(v => v / n), claro: [q[1], q[2], q[3]], color: r.color, chico: r.chico }; });
      return out; }, { b64: png.toString('base64'), cajas });
    const fila = [];
    for (const [k, rs] of Object.entries(datos)) { let prom = 99, claro = 99, chico = false;
      for (const r of rs) { const a = r.color.length > 3 ? r.color[3] : 1, cr = f => { const t = r.color.slice(0, 3).map((v, i) => v * a + f[i] * (1 - a)), lt = L(t), lf = L(f); return (Math.max(lt, lf) + 0.05) / (Math.min(lt, lf) + 0.05); };
        prom = Math.min(prom, cr(r.prom)); claro = Math.min(claro, cr(r.claro)); chico = r.chico; }
      const pide = chico ? 4.5 : 3, bien = claro >= pide; if (!bien) flojo++;
      fila.push(k + ' ' + prom.toFixed(1) + ' (' + claro.toFixed(1) + (bien ? '' : ' FLOJO, pide ' + pide) + ')'); }
    console.log(String(w).padStart(4) + ' px  ' + fila.join(' | ')); await p.close(); await p2.close();
  }
  await b.close();
  console.log(flojo ? '\n' + flojo + ' texto(s) por debajo de lo que se pide.' : '\nTodos los textos llegan a lo que se pide, también en lo más claro del fondo.');
  process.exit(flojo ? 1 : 0);
})().catch(e => { console.error(e); process.exit(2); });
