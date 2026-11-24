#!/usr/bin/env python3
"""Arma las páginas de /trabajos/ a partir de datos.json.

Uso:  python3 04-operations/trabajos/armar.py
      (para probar: armar.py <otro datos.json> <otra carpeta public_html>)

Escribe en site/public_html/:
  trabajos/index.html               la galería: cada foto con la textura de su material
  trabajos/<material>/index.html    una página por material, con toda su colección de fotos
  sitemap.xml                       el bloque de /trabajos/

El menú, los colores, el botón de WhatsApp y el pie se copian de la landing (index.html) cada
vez que se arma: si la landing cambia, se vuelve a correr esto y quedan iguales.

Reglas del dueño (24/11/2026) que este archivo hace cumplir:
  - "Toda descripción tiene prohibido ser un testamento": la descripción de un material no pasa
    de 30 palabras, y las características son 2 o 4: un ícono y hasta 3 palabras cada una.
  - "Que el cliente entienda en menos de 10 segundos."
Y una que no se puede comprobar con un programa: las características son ventajas. Lo que no
ayuda a vender no va en estas páginas ("no tiene ninguna ventaja táctica").
"""
import html, json, os, re, sys, urllib.parse

ACA = os.path.dirname(os.path.abspath(__file__))
WEB = os.path.normpath(sys.argv[2] if len(sys.argv) > 2 else os.path.join(ACA, '..', '..', 'site', 'public_html'))
SITIO = 'https://blackstones.com.ar'
MAX_PALABRAS_DESCRIPCION = 30
MAX_CARACTERISTICAS = 4
MAX_PALABRAS_TITULO = 3

D = json.load(open(sys.argv[1] if len(sys.argv) > 1 else os.path.join(ACA, 'datos.json'), encoding='utf-8'))
LANDING = open(os.path.join(WEB, 'index.html'), encoding='utf-8').read()
e = html.escape


def fallar(msg):
    sys.exit('NO SE ARMÓ NADA: ' + msg)


# ------------------------------------------------------------------ lo que se copia de la landing
def entre(a, b):
    i = LANDING.index(a)
    return LANDING[i:LANDING.index(b, i)]


def reglas(patron):
    r = [l for l in LANDING.split('\n') if re.match(patron, l)]
    if not r:
        fallar('la landing ya no tiene el estilo ' + patron)
    return '\n'.join(r)


NAV_CSS = entre('/* ===== NAV', '/* ===== HERO').rstrip()
BTN_CSS = reglas(r'^\.btn-wa')
FINAL_CSS = reglas(r'^\.final-v3|^\.final-center')
PIE_CSS = reglas(r'^\.site-footer|^\.foot-')
FLOTANTE_CSS = reglas(r'^\.sticky-wa\{|^\.sticky-wa:hover|^\.sticky-wa svg|^@media\(min-width:900px\)\{\.sticky-wa')
m = re.search(r'<a class="nav-cta"[^>]*>\s*(<svg viewBox="0 0 24 24" fill="currentColor"><path d="([^"]+)"/></svg>)', LANDING)
if not m:
    fallar('no encuentro el ícono de WhatsApp en la landing')
SVG_WA, PATH_WA = m.group(1), m.group(2)
m = re.search(r'<link href="https://fonts\.googleapis\.com/css2\?[^"]+" rel="stylesheet"[^>]*>', LANDING)
if not m:
    fallar('no encuentro las tipografías en la landing')
FUENTES = m.group(0)
m = re.search(r'https://wa\.me/(\d+)\?', LANDING)
TELEFONO = m.group(1)


def wa(texto):
    return 'https://wa.me/' + TELEFONO + '?text=' + urllib.parse.quote(texto, safe='')


WA_GENERAL = wa('Hola, vi los trabajos en la página y me gustaría cotizar una mesada')

# Los íconos de las características: línea fina, 24 x 24. Se eligen por nombre en datos.json.
# Los trazos son de Lucide (lucide.dev), licencia ISC: Copyright (c) for portions of Lucide are
# held by Cole Bemis 2013-2022 as part of Feather (MIT). All other copyright (c) for Lucide are
# held by Lucide Contributors 2022. "parejo" es propio.
ICONOS = {
    'gema':     '<path d="M6 3h12l4 6-10 13L2 9Z"/><path d="M11 3 8 9l4 13 4-13-3-6"/><path d="M2 9h20"/>',
    'llama':    '<path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>',
    'infinito': '<path d="M6 16c5 0 7-8 12-8a4 4 0 0 1 0 8c-5 0-7-8-12-8a4 4 0 1 0 0 8"/>',
    'cantera':  '<path d="m8 3 4 8 5-5 5 15H2L8 3z"/>',
    'hexagono': '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>',
    'gota':     '<path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/>',
    'frasco':   '<path d="M10 2v7.527a2 2 0 0 1-.211.896L4.72 20.55a1 1 0 0 0 .9 1.45h12.76a1 1 0 0 0 .9-1.45l-5.069-10.127A2 2 0 0 1 14 9.527V2"/><path d="M8.5 2h7"/><path d="M7 16h10"/>',
    'parejo':   '<rect x="3" y="3" width="18" height="18" rx="2.5"/><rect x="8" y="8" width="8" height="8" rx="1.2"/>',
}

# ------------------------------------------------------------------ los datos, revisados
TIPOS = D['tipos']
MATERIALES = {m_['slug']: m_ for m_ in D['materiales']}
TRABAJOS = D['trabajos']
SLUG = re.compile(r'^[a-z0-9]+(-[a-z0-9]+)*$')

for s, mat in MATERIALES.items():
    if not SLUG.match(s) or s == 'f':
        fallar('nombre de material no válido: ' + s)
    if mat['tipo'] not in TIPOS:
        fallar(s + ': no existe el tipo ' + mat['tipo'])
    n = len(mat['descripcion'].split())
    if n > MAX_PALABRAS_DESCRIPCION:
        fallar('%s: la descripción tiene %d palabras y el tope es %d. Acortala.' % (s, n, MAX_PALABRAS_DESCRIPCION))
    if not os.path.isfile(os.path.join(WEB, mat['textura'].lstrip('/'))):
        fallar(s + ': no existe la textura ' + mat['textura'])
for t, tipo in TIPOS.items():
    car = tipo['caracteristicas']
    if len(car) > MAX_CARACTERISTICAS or len(car) % 2:
        fallar('%s: son %d características. Van 2 o 4 (se muestran de a pares).' % (t, len(car)))
    for c in car:
        if not isinstance(c, list) or len(c) != 2:
            fallar('%s: cada característica es ["ícono", "dos o tres palabras"]: %s' % (t, c))
        if c[0] not in ICONOS:
            fallar('%s: no existe el ícono "%s". Hay: %s' % (t, c[0], ', '.join(sorted(ICONOS))))
        if len(c[1].split()) > MAX_PALABRAS_TITULO:
            fallar('%s: característica larga (hasta %d palabras): %s' % (t, MAX_PALABRAS_TITULO, c[1]))
vistos = set()
for t in TRABAJOS:
    if t['material'] not in MATERIALES:
        fallar('trabajo %s: no existe el material %s' % (t['foto'], t['material']))
    if not SLUG.match(t['foto']) or t['foto'] in vistos:
        fallar('nombre de foto repetido o no válido: ' + t['foto'])
    vistos.add(t['foto'])
    # el nombre del archivo es público: no lleva el N° de presupuesto
    if re.match(r'^\d{3,}', t['foto']):
        fallar('la foto %s empieza con un número: el nombre es público y no lleva el N° de presupuesto' % t['foto'])
    for cola in ('160.webp', '720.webp', '1080.webp', '1440.webp', 'og.jpg'):
        if not os.path.isfile(os.path.join(WEB, 'trabajos', 'f', '%s-%s' % (t['foto'], cola))):
            fallar('falta la foto trabajos/f/%s-%s (se arma con foto.php)' % (t['foto'], cola))


def de(mat):
    """Los trabajos de un material, en el orden de datos.json (el más nuevo arriba)."""
    return [t for t in TRABAJOS if t['material'] == mat['slug']]


def titulo(mat):
    return TIPOS[mat['tipo']]['nombre'] + ' ' + mat['nombre']


def como_se_nombra(mat):
    marca = (' ' + mat['dato'][1]) if mat.get('dato', [''])[0] == 'Marca' else ''
    return TIPOS[mat['tipo']]['nombre'].lower() + marca + ' ' + mat['nombre']


def fuente(foto):
    return '/trabajos/f/%s-720.webp' % foto, '/trabajos/f/%s-720.webp 720w, /trabajos/f/%s-1080.webp 1080w, /trabajos/f/%s-1440.webp 1440w' % (foto, foto, foto)


# ------------------------------------------------------------------ las piezas comunes
CSS_BASE = '''
:root{
  --night:#15130F;
  --cream:#F3EDE3;
  --cream-dim:rgba(243,237,227,.62);
  --cream-faint:rgba(243,237,227,.38);
  --gold:#C4A77D;
  --gold-deep:#8A7560;
  --line:rgba(196,167,125,.18);
  --wa:#25D366;
  --wa-dark:#1DA851;
  --radius:14px;
  --font-display:'Fraunces',serif;
  --font-body:'Manrope',sans-serif;
  --section-y:clamp(3.5rem,8vw,6.5rem);
  /* El crema de estas páginas es más claro que el de la landing (#FAF8F4, #F2EDE3, #E8E2D6).
     Dueño, 24/11/2026: "aclararlo un poco a algo más premium; no queremos la sensación de
     libro viejo o pergamino". */
  --bg:#FCFBF9;
  --bg-alt:#F4F1EB;
  --text:#1A1816;
  --text-soft:#4A453E;
  --accent:#A89580;
  --accent-dark:#7A6649;
  --border:#EBE7E0;
}
*{margin:0;padding:0;box-sizing:border-box}
html,body{overflow-x:clip}
body{font-family:var(--font-body);background:var(--bg);color:var(--text);font-size:16px;line-height:1.6;-webkit-font-smoothing:antialiased}
img{max-width:100%;display:block}
a{color:inherit}
.container{max-width:1120px;margin:0 auto;padding:0 clamp(1.25rem,4vw,2.5rem)}
.eyebrow{font-size:.72rem;font-weight:800;letter-spacing:.18em;text-transform:uppercase;color:var(--accent-dark);margin-bottom:1.1rem}
h1,h2{font-family:var(--font-display);font-weight:500;font-size:clamp(1.75rem,4.2vw,2.7rem);line-height:1.1;letter-spacing:-.01em;margin-bottom:1rem;color:var(--text)}
h1 em,h2 em{font-style:italic;color:var(--accent-dark)}
.lead{color:var(--text-soft);max-width:560px;font-size:1.02rem}

/* ===== MENÚ (el de la landing) ===== */
@@NAV_CSS@@
.nav-links a[aria-current]{color:var(--gold)}

/* La textura del material: un cuadrito con borde claro y sombra, montado sobre la esquina
   de abajo a la izquierda de la foto. Abajo a la derecha las fotos traen el logo: no se tapa. */
.muestra{flex-shrink:0;position:relative;border-radius:10px;border:3px solid var(--bg);object-fit:cover;background:var(--bg-alt);box-shadow:0 8px 20px rgba(26,24,22,.2)}
'''.replace('@@NAV_CSS@@', NAV_CSS)

CSS_PIE = '\n/* ===== BOTÓN, CIERRE Y PIE (los de la landing) ===== */\n' + '\n'.join([BTN_CSS, FINAL_CSS, '.final-v3{padding:var(--section-y) 0}', PIE_CSS, FLOTANTE_CSS]) + '\n'


def cabeza(tit, descripcion, ruta, css, foto):
    return '''<!DOCTYPE html>
<html lang="es-AR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="%(d)s" />
  <title>%(t)s</title>
  <link rel="icon" href="/favicon/favicon.ico" />
  <link rel="canonical" href="%(u)s" />
  <meta property="og:type" content="website" />
  <meta property="og:locale" content="es_AR" />
  <meta property="og:site_name" content="BlackStones Marmolería" />
  <meta property="og:title" content="%(t)s" />
  <meta property="og:description" content="%(d)s" />
  <meta property="og:url" content="%(u)s" />
  <meta property="og:image" content="%(s)s/trabajos/f/%(foto)s-og.jpg" />
  <meta property="og:image:width" content="1200" />
  <meta property="og:image:height" content="630" />
  <meta name="twitter:card" content="summary_large_image" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  %(f)s
<!-- Esta página la arma 04-operations/trabajos/armar.py con datos.json. No se edita a mano. -->
<style>%(c)s</style>
</head>
<body>

<nav class="nav">
  <div class="nav-inner">
    <a href="/" class="nav-logo">
      <img src="/logo/logo_isotipo.png" alt="" />
      <span><b>Black</b>Stones</span>
    </a>
    <ul class="nav-links">
      <li><a href="/#resenas">Reseñas</a></li>
      <li><a href="/#proceso">Cómo trabajamos</a></li>
      <li><a href="/#materiales">Materiales</a></li>
      <li><a href="/trabajos/" aria-current="page">Trabajos</a></li>
      <li><a href="/#faq">Preguntas</a></li>
    </ul>
    <div class="nav-actions">
      <a class="nav-catalog" href="/#colores">Catálogo</a>
      <a class="nav-cta" href="%(w)s" target="_blank" rel="noopener">
        %(svg)s
        Cotizar
      </a>
    </div>
  </div>
</nav>
''' % {'t': e(tit), 'd': e(descripcion), 'u': SITIO + ruta, 's': SITIO, 'f': FUENTES, 'c': css, 'w': WA_GENERAL, 'svg': SVG_WA, 'foto': foto}


def pie(wa_link):
    return '''
<footer class="site-footer">
  <div class="container">
    <div class="foot-grid">
      <div>
        <strong style="color:rgba(243,237,227,.85)">BlackStones Marmolería</strong><br>
        Av. Juan Bautista Alberdi 3575 · CABA · Lun–Vie 9–17 hs · Sáb 9–13 hs
      </div>
      <div class="foot-links">
        <a href="https://wa.me/%(tel)s" target="_blank" rel="noopener">WhatsApp</a>
        <a href="https://www.instagram.com/blackstones.ar/" target="_blank" rel="noopener">Instagram</a>
        <a href="https://share.google/674RKVdI1dnTyTZBY" target="_blank" rel="noopener">Google</a>
      </div>
    </div>
  </div>
</footer>

<a class="sticky-wa" href="%(w)s" target="_blank" rel="noopener" aria-label="Cotizar por WhatsApp">
  <svg viewBox="0 0 24 24"><path d="%(p)s"/></svg>
</a>
''' % {'tel': TELEFONO, 'w': wa_link, 'p': PATH_WA}


# ------------------------------------------------------------------ la galería
CSS_GALERIA = CSS_BASE + '''
/* ===== CABECERA ===== */
.t-head{padding-top:clamp(2.4rem,6vw,4.2rem);padding-bottom:clamp(1.8rem,4vw,2.8rem)}

/* ===== GALERÍA: una columna en el celular, dos en tablet, tres en computadora ===== */
.t-grid{display:grid;grid-template-columns:1fr;gap:2.4rem 1.6rem;padding-bottom:var(--section-y)}
@media(min-width:640px){.t-grid{grid-template-columns:repeat(2,1fr)}}
@media(min-width:1000px){.t-grid{grid-template-columns:repeat(3,1fr);gap:3rem 1.8rem}}
.t-card{display:block;min-width:0;text-decoration:none;color:inherit}
.t-foto{aspect-ratio:1;border-radius:var(--radius);overflow:hidden;background:var(--bg-alt);box-shadow:0 6px 18px rgba(26,24,22,.08)}
.t-foto img{width:100%;height:100%;object-fit:cover;transition:transform .5s cubic-bezier(.22,1,.36,1)}
.t-card:hover .t-foto img{transform:scale(1.03)}
.t-pie{display:flex;align-items:flex-start;gap:.95rem;padding:0 .2rem 0 .9rem}
.t-pie .muestra{width:84px;height:84px;margin-top:-42px}
.t-txt{padding-top:.7rem;min-width:0}
.t-txt strong{display:block;font-family:var(--font-display);font-weight:500;font-size:1.15rem;line-height:1.2}
.t-txt span{display:block;font-size:.7rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:var(--accent-dark);margin-top:.25rem}
''' + CSS_PIE


def galeria():
    cards = []
    for i, t in enumerate(TRABAJOS):
        mat = MATERIALES[t['material']]
        src, srcset = fuente(t['foto'])
        carga = 'fetchpriority="high"' if i == 0 else 'loading="lazy" decoding="async"'
        ancla = ('#' + t['foto']) if len(de(mat)) > 1 else ''
        cards.append('''    <a class="t-card" href="/trabajos/%(slug)s/%(ancla)s">
      <div class="t-foto"><img src="%(src)s" srcset="%(srcset)s" sizes="(min-width:1000px) 360px, (min-width:640px) 46vw, 92vw" width="1440" height="1440" alt="%(alt)s" %(carga)s></div>
      <div class="t-pie">
        <img class="muestra" src="%(tex)s" width="84" height="84" alt="" loading="lazy" decoding="async">
        <div class="t-txt"><strong>%(nombre)s</strong><span>%(tipo)s</span></div>
      </div>
    </a>''' % {'slug': mat['slug'], 'ancla': ancla, 'src': src, 'srcset': srcset, 'alt': e(t['alt']), 'carga': carga,
               'tex': mat['textura'], 'nombre': e(mat['nombre']), 'tipo': e(TIPOS[mat['tipo']]['nombre'])})
    return cabeza('Trabajos — BlackStones Marmolería',
                  'Mesadas de granito, cuarzo y sinterizado ya colocadas en CABA y GBA. Cada foto, con el material con el que se hizo.',
                  '/trabajos/', CSS_GALERIA, TRABAJOS[0]['foto']) + '''
<main>
  <header class="container t-head">
    <div class="eyebrow">Trabajos</div>
    <h1>Cada mesada, <em>con su piedra.</em></h1>
    <p class="lead">Obras nuestras, ya colocadas. Tocá una foto para ver el material.</p>
  </header>

  <div class="container t-grid">
%(cards)s
  </div>
</main>

<section class="final-v3">
  <div class="container final-center">
    <img class="final-logo" src="/logo/logo_isotipo.png" alt="" aria-hidden="true" />
    <h2>¿Te gustó alguna? <em>Mandanos las medidas.</em></h2>
    <p>Decinos el material y el largo por ancho. Te respondemos con el presupuesto por escrito.</p>
    <a class="btn-wa" href="%(w)s" target="_blank" rel="noopener">
      %(svg)s
      Cotizá por WhatsApp
    </a>
    <span class="final-place">O vení a verlo en persona: <a href="https://share.google/674RKVdI1dnTyTZBY" target="_blank" rel="noopener">Av. Juan B. Alberdi 3575, CABA</a> · Lun–Vie 9–17 hs · Sáb 9–13 hs</span>
  </div>
</section>
''' % {'cards': '\n'.join(cards), 'w': WA_GENERAL, 'svg': SVG_WA} + pie(WA_GENERAL) + '\n</body>\n</html>\n'


# ------------------------------------------------------------------ la página de un material
CSS_MATERIAL = CSS_BASE + '''
/* ===== PÁGINA DE UN MATERIAL =====
   Celular: título, foto, datos. Computadora: la foto a la izquierda y todo lo demás a la derecha. */
.m-wrap{display:grid;grid-template-columns:minmax(0,1fr);grid-template-areas:"head" "fotos" "info";row-gap:1.3rem;padding-top:clamp(1.8rem,4vw,3.2rem);padding-bottom:clamp(2.8rem,6vw,4.5rem)}
@media(min-width:900px){.m-wrap{grid-template-columns:minmax(0,1.05fr) minmax(0,1fr);grid-template-areas:"fotos head" "fotos info";grid-template-rows:auto 1fr;column-gap:clamp(2.5rem,5vw,4.5rem);row-gap:0;align-items:start}}
.m-head{grid-area:head}
.m-fotos{grid-area:fotos;min-width:0}
.m-info{grid-area:info;min-width:0}
.m-miga{font-size:.72rem;font-weight:800;letter-spacing:.18em;text-transform:uppercase;color:var(--accent-dark);margin-bottom:.9rem}
.m-miga a{text-decoration:none;border-bottom:1px solid var(--accent)}
.m-miga a:hover{color:var(--text)}
.m-head h1{margin-bottom:0}
.m-foto{aspect-ratio:1;border-radius:var(--radius);overflow:hidden;background:var(--bg-alt);box-shadow:0 6px 18px rgba(26,24,22,.08)}
.m-foto img{width:100%;height:100%;object-fit:cover}
/* debajo de la foto: la textura y, si hay más de un trabajo, las miniaturas de la colección */
.m-banda{display:flex;align-items:flex-start;gap:.6rem;padding-left:.9rem}
.m-banda .muestra{width:96px;height:96px;margin-top:-48px}
/* la tira de miniaturas no baja de renglón: si la colección es larga, se desliza de costado */
.m-minis{display:flex;gap:.6rem;flex:1;min-width:0;overflow-x:auto;padding:.7rem 4px 8px;scrollbar-width:thin;scrollbar-color:var(--accent) transparent}
.m-minis::-webkit-scrollbar{height:4px}
.m-minis::-webkit-scrollbar-thumb{background:var(--accent);border-radius:2px}
.m-mini{appearance:none;-webkit-appearance:none;border:0;padding:0;background:var(--bg-alt);flex:0 0 72px;height:72px;border-radius:10px;overflow:hidden;cursor:pointer;opacity:.7;outline:2px solid transparent;outline-offset:2px;transition:opacity .15s}
.m-mini:hover{opacity:1}
.m-mini[aria-pressed="true"]{opacity:1;outline-color:var(--gold-deep)}
.m-mini img{width:100%;height:100%;object-fit:cover}
.m-desc{font-size:1.08rem;line-height:1.55;color:var(--text-soft);max-width:44ch}
@media(min-width:900px){.m-desc{margin-top:1.2rem}}
.m-datos{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin:1.6rem 0 0;padding:1.1rem 0;border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.m-rotulo,.m-datos dt{font-size:.7rem;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:var(--accent-dark)}
.m-datos dd{font-family:var(--font-display);font-size:1.3rem;line-height:1.3;margin-top:.2rem}
/* Las características: una tira liviana de íconos de línea fina, cada uno con dos o tres
   palabras. Sigue a la fila de datos y cierra con la misma raya: se lee de un vistazo. */
.m-ficha{padding:1.25rem 0 1.5rem;margin-bottom:1.9rem;border-bottom:1px solid var(--border)}
.m-car{list-style:none;display:grid;grid-template-columns:repeat(4,1fr);gap:.5rem;margin-top:1.15rem}
.m-car.de-2{grid-template-columns:repeat(2,1fr)}
.m-car li{display:flex;flex-direction:column;align-items:center;text-align:center;gap:.7rem;min-width:0}
.m-ico{width:58px;height:58px;border-radius:50%;border:1px solid rgba(138,117,96,.34);display:flex;align-items:center;justify-content:center;background:#fff}
.m-ico svg{width:25px;height:25px;fill:none;stroke:var(--gold-deep);stroke-width:1.35;stroke-linecap:round;stroke-linejoin:round}
.m-car strong{font-family:var(--font-display);font-weight:500;font-size:.98rem;line-height:1.22;max-width:8.5em}
@media(max-width:400px){.m-ico{width:52px;height:52px}.m-car strong{font-size:.92rem}}
.m-mas{display:inline-block;margin-left:1.2rem;color:var(--accent-dark);font-weight:700;font-size:.88rem;text-decoration:none;border-bottom:1px solid var(--accent);white-space:nowrap}
.m-mas:hover{color:var(--text)}
@media(max-width:480px){.m-mas{display:table;margin:1.1rem 0 0}}

/* ===== OTROS MATERIALES ===== */
.m-otros{border-top:1px solid var(--border);padding-top:clamp(2rem,5vw,3rem);margin-bottom:var(--section-y)}
.m-fila{display:flex;flex-wrap:wrap;gap:.8rem}
.m-chip{display:flex;align-items:center;gap:.7rem;padding:.45rem 1.1rem .45rem .45rem;border:1px solid var(--border);border-radius:999px;background:#fff;text-decoration:none;transition:border-color .15s}
.m-chip:hover{border-color:var(--accent)}
.m-chip img{width:44px;height:44px;border-radius:50%;object-fit:cover}
.m-chip strong{display:block;font-family:var(--font-display);font-weight:500;font-size:1rem;line-height:1.2}
.m-chip small{display:block;font-size:.66rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:var(--accent-dark)}
''' + CSS_PIE

JS_MINIS = '''
<script>
// Las miniaturas cambian la foto grande. Si se llega desde la galería con #foto, arranca en esa.
(function(){
  var foto = document.getElementById('mFoto'), minis = [].slice.call(document.querySelectorAll('.m-mini'));
  if(!foto || !minis.length) return;
  function elegir(b){
    foto.srcset = b.dataset.ss; foto.src = b.dataset.s; foto.alt = b.dataset.alt;
    minis.forEach(function(x){ x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
  }
  minis.forEach(function(b){ b.addEventListener('click', function(){ elegir(b); }); });
  var pedida = location.hash.slice(1);
  minis.forEach(function(b){ if(b.dataset.f === pedida) elegir(b); });
})();
</script>
'''


def pagina_material(mat):
    tipo = TIPOS[mat['tipo']]
    obras = de(mat)
    src, srcset = fuente(obras[0]['foto'])
    minis = ''
    if len(obras) > 1:
        for i, t in enumerate(obras):
            s, ss = fuente(t['foto'])
            minis += '\n        <button class="m-mini" type="button" aria-pressed="%s" aria-label="Ver el trabajo %d de %d en %s" data-f="%s" data-s="%s" data-ss="%s" data-alt="%s"><img src="/trabajos/f/%s-160.webp" width="72" height="72" alt="" decoding="async"></button>' % (
                'true' if i == 0 else 'false', i + 1, len(obras), e(mat['nombre']), t['foto'], s, ss, e(t['alt']), t['foto'])
        minis = '\n      <div class="m-minis">' + minis + '\n      </div>'
    car = tipo['caracteristicas']
    datos = '<div><dt>Espesor</dt><dd>%d mm</dd></div>' % tipo['espesor_mm']
    if mat.get('dato'):
        datos += '<div><dt>%s</dt><dd>%s</dd></div>' % (e(mat['dato'][0]), e(mat['dato'][1]))
    otros = [o for o in MATERIALES.values() if o['slug'] != mat['slug'] and de(o)]
    chips = '\n'.join('      <a class="m-chip" href="/trabajos/%s/"><img src="%s" width="44" height="44" alt="" loading="lazy" decoding="async"><span><strong>%s</strong><small>%s</small></span></a>' % (
        o['slug'], o['textura'], e(o['nombre']), e(TIPOS[o['tipo']]['nombre'])) for o in otros)
    bloque_otros = '''
<section class="container">
  <div class="m-otros">
    <div class="eyebrow">Otros materiales</div>
    <div class="m-fila">
%s
    </div>
  </div>
</section>
''' % chips if otros else ''
    w = wa('Hola, vi en la página los trabajos en ' + como_se_nombra(mat) + ' y me gustaría cotizar una mesada')
    return cabeza(titulo(mat) + ' — Trabajos | BlackStones Marmolería', mat['descripcion'], '/trabajos/%s/' % mat['slug'], CSS_MATERIAL, obras[0]['foto']) + '''
<main class="container m-wrap">
  <div class="m-head">
    <div class="m-miga"><a href="/trabajos/">Trabajos</a> / %(tipo)s</div>
    <h1>%(tipo)s <em>%(nombre)s</em></h1>
  </div>

  <div class="m-fotos">
    <div class="m-foto"><img id="mFoto" src="%(src)s" srcset="%(srcset)s" sizes="(min-width:900px) 560px, 92vw" width="1440" height="1440" alt="%(alt)s" fetchpriority="high"></div>
    <div class="m-banda">
      <img class="muestra" src="%(tex)s" width="96" height="96" alt="Textura del %(tipo_min)s %(nombre)s">%(minis)s
    </div>
  </div>

  <div class="m-info">
    <p class="m-desc">%(desc)s</p>
    <dl class="m-datos">%(datos)s</dl>
    <div class="m-ficha">
      <div class="m-rotulo">Cómo es el %(tipo_min)s</div>
      <ul class="m-car%(de)s">
%(car)s
      </ul>
    </div>
    <a class="btn-wa" href="%(w)s" target="_blank" rel="noopener">
      %(svg)s
      Cotizar en %(nombre)s
    </a>
    <a class="m-mas" href="/trabajos/">← Todos los trabajos</a>
  </div>
</main>
%(otros)s''' % {'tipo': e(tipo['nombre']), 'tipo_min': e(tipo['nombre'].lower()), 'nombre': e(mat['nombre']), 'src': src, 'srcset': srcset,
                'alt': e(obras[0]['alt']), 'tex': mat['textura'], 'minis': minis, 'desc': e(mat['descripcion']), 'datos': datos,
                'car': '\n'.join('        <li><span class="m-ico"><svg viewBox="0 0 24 24" aria-hidden="true">%s</svg></span><strong>%s</strong></li>' % (ICONOS[c[0]], e(c[1])) for c in car),
                'de': ' de-2' if len(car) == 2 else '', 'w': w, 'svg': SVG_WA, 'otros': bloque_otros} + pie(w) + (JS_MINIS if len(obras) > 1 else '') + '\n</body>\n</html>\n'


# ------------------------------------------------------------------ escribir
def escribir(rel, contenido):
    f = os.path.join(WEB, rel)
    os.makedirs(os.path.dirname(f), exist_ok=True)
    open(f, 'w', encoding='utf-8', newline='\n').write(contenido)
    print('  %-38s %6.1f KB' % (rel, len(contenido.encode()) / 1024))


print('Armando /trabajos/ con %d trabajos en %d materiales:' % (len(TRABAJOS), len([m_ for m_ in MATERIALES.values() if de(m_)])))
escribir('trabajos/index.html', galeria())
rutas = ['/trabajos/']
for mat in MATERIALES.values():
    if de(mat):
        escribir('trabajos/%s/index.html' % mat['slug'], pagina_material(mat))
        rutas.append('/trabajos/%s/' % mat['slug'])

# el sitemap: el bloque de /trabajos/ se reescribe entero entre sus dos marcas
sm_f = os.path.join(WEB, 'sitemap.xml')
sm = open(sm_f, encoding='utf-8').read()
bloque = '  <!-- trabajos: lo escribe 04-operations/trabajos/armar.py -->\n' + ''.join(
    '  <url>\n    <loc>%s%s</loc>\n    <changefreq>weekly</changefreq>\n    <priority>%s</priority>\n  </url>\n' % (SITIO, r, '0.8' if r == '/trabajos/' else '0.7') for r in rutas) + '  <!-- /trabajos -->\n'
if '<!-- trabajos:' in sm:
    sm = re.sub(r'  <!-- trabajos:.*?<!-- /trabajos -->\n', lambda _: bloque, sm, flags=re.S)
else:
    sm = sm.replace('</urlset>', bloque + '\n</urlset>')
open(sm_f, 'w', encoding='utf-8', newline='\n').write(sm)
print('  sitemap.xml: %d direcciones de /trabajos/' % len(rutas))

# páginas de materiales que quedaron de antes y ya no tienen trabajos: se avisa, no se borra nada
for d in sorted(os.listdir(os.path.join(WEB, 'trabajos'))):
    if d != 'f' and os.path.isdir(os.path.join(WEB, 'trabajos', d)) and ('/trabajos/%s/' % d) not in rutas:
        print('  OJO: trabajos/%s/ ya no tiene trabajos en datos.json. La carpeta sigue ahí.' % d)
