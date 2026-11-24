#!/usr/bin/env python3
"""Arma la vista previa de /trabajos/ en un solo archivo, para verla sin subirla al sitio.

Uso:  python3 04-operations/trabajos/vista.py <salida.html>
      (para probar: vista.py <salida.html> <otra carpeta public_html>)

Primero se corre armar.py. Esto lee las páginas que armar.py deja hechas (la galería y la de
cada material) y las junta en un archivo que se abre con doble clic, en la computadora o en el
celular. Las fotos, las texturas, el logo y las letras van adentro: no pide nada a internet.

Por qué existe: el dueño, 24/11/2026: "antes de subirlo prefiero tenerlo local, hasta tener
suficientes imágenes". Mientras la página no esté publicada, cada vez que se suma un trabajo
se le manda este archivo. No se guarda en el repo: se arma y se entrega.

Cómo está hecho, para que muestre lo mismo que se va a publicar:
  - No vuelve a escribir las páginas: toma el HTML y el CSS tal cual salen de armar.py.
  - Cada página queda en un bloque, y se pasa de una a otra con el link de siempre (el bloque
    que se muestra es el que nombra la dirección, con :target). Anda sin JavaScript.
  - Cada foto va una sola vez en el archivo, como fondo de su recuadro, aunque se use en la
    galería y en la página de su material. Las miniaturas de una colección sí usan JavaScript.
  - Lo que apunta a la landing o a WhatsApp abre el sitio de verdad.
"""
import base64
import datetime
import os
import re
import sys
import urllib.request

ACA = os.path.dirname(os.path.abspath(__file__))
if len(sys.argv) < 2:
    sys.exit('uso: vista.py <salida.html> [otra carpeta public_html]')
SALIDA = sys.argv[1]
WEB = os.path.normpath(sys.argv[2] if len(sys.argv) > 2 else os.path.join(ACA, '..', '..', 'site', 'public_html'))
SITIO = 'https://blackstones.com.ar'
PIXEL = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'   # 1 x 1, transparente
MIME = {'.webp': 'image/webp', '.png': 'image/png', '.jpg': 'image/jpeg'}
NAVEGADOR = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36'


def fallar(msg):
    sys.exit('NO SE ARMÓ LA VISTA PREVIA: ' + msg)


def leer(rel):
    try:
        return open(os.path.join(WEB, rel), encoding='utf-8').read()
    except FileNotFoundError:
        fallar('falta %s. Primero se corre armar.py.' % rel)


def adentro(ruta):
    """Un archivo del sitio, como dirección que lleva el contenido adentro."""
    f = os.path.join(WEB, ruta.lstrip('/'))
    if not os.path.isfile(f):
        fallar('falta el archivo ' + ruta)
    return 'data:%s;base64,%s' % (MIME[os.path.splitext(f)[1]], base64.b64encode(open(f, 'rb').read()).decode())


def entre(s, a, b):
    i = s.index(a)
    return s[i + len(a):s.index(b, i + len(a))]


def letras(link):
    """Las letras de la página (Fraunces y Manrope), adentro del archivo. Si no hay internet al
    armar, queda el link de siempre y las letras se ven solo con conexión."""
    try:
        href = re.search(r'href="([^"]+)"', link).group(1).replace('&amp;', '&')
        css = urllib.request.urlopen(urllib.request.Request(href, headers={'User-Agent': NAVEGADOR}), timeout=25).read().decode()
        grupos = {}
        for b in re.findall(r'/\* latin \*/\s*@font-face\s*\{([^}]*)\}', css):
            p = dict((k.strip(), v.strip()) for k, v in (x.split(':', 1) for x in b.split(';') if ':' in x))
            url = re.search(r'url\((https://fonts\.gstatic\.com/[^)]+\.woff2)\)', p['src']).group(1)
            g = grupos.setdefault((p['font-family'], p['font-style'], url), {'pesos': [], 'rango': p['unicode-range']})
            g['pesos'].append(int(p['font-weight']))
        if not grupos:
            raise ValueError('no vinieron las letras')
        out = ''
        for (familia, estilo, url), g in grupos.items():
            dato = base64.b64encode(urllib.request.urlopen(url, timeout=25).read()).decode()
            # el archivo trae todos los grosores (es variable): una sola copia sirve para todos
            out += "@font-face{font-family:%s;font-style:%s;font-weight:%d %d;font-display:swap;src:url(data:font/woff2;base64,%s) format('woff2');unicode-range:%s}\n" % (
                familia, estilo, min(g['pesos']), max(g['pesos']), dato, g['rango'])
        return out, ''
    except Exception as ex:   # sin internet, o Google cambió el formato: la vista previa se arma igual
        print('  OJO: no se pudieron guardar las letras adentro (%s). Se van a ver solo con internet.' % ex)
        return '', '<link rel="preconnect" href="https://fonts.googleapis.com">\n<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>\n' + link + '\n'


# ------------------------------------------------------------------ las páginas ya armadas
gal = leer('trabajos/index.html')
slugs = list(dict.fromkeys(re.findall(r'<a class="t-card" href="/trabajos/([a-z0-9-]+)/', gal)))
if not slugs:
    fallar('la galería no tiene trabajos')
css = css_galeria = entre(gal, '<style>', '</style>')
nav = '<nav class="nav">' + entre(gal, '<nav class="nav">', '</nav>') + '</nav>'
pie = '<footer class="site-footer">' + entre(gal, '<footer class="site-footer">', '</body>')
bloques = ''
for slug in slugs:
    pag = leer('trabajos/%s/index.html' % slug)
    if slug == slugs[0]:
        # Las dos hojas de estilo empiezan y terminan igual (lo común de armar.py). Se junta sin
        # repetir esa parte: repetida, lo común pisaría lo propio de la galería que va antes.
        css_material = entre(pag, '<style>', '</style>')
        a = 0
        while a < min(len(css_galeria), len(css_material)) and css_galeria[a] == css_material[a]:
            a += 1
        a = css_galeria.rfind('\n', 0, a) + 1
        z = 0
        while z < min(len(css_galeria), len(css_material)) - a and css_galeria[-1 - z] == css_material[-1 - z]:
            z += 1
        z = len(css_galeria) - css_galeria.find('\n', len(css_galeria) - z)
        if a < 2000 or z < 500:
            fallar('las hojas de estilo de la galería y del material ya no comparten el principio y el final')
        css = css_galeria[:len(css_galeria) - z] + css_material[a:len(css_material) - z] + css_galeria[len(css_galeria) - z:]
    bloques += '\n<div class="vp" id="m-%s">%s</div>\n' % (slug, entre(pag, '</nav>', '<footer class="site-footer">'))
# la galería va última: es la que se ve cuando la dirección no nombra ningún bloque
cuerpo = nav + bloques + '\n<div class="vp vp-inicio" id="galeria">' + entre(gal, '</nav>', '<footer class="site-footer">') + '</div>\n' + pie

# ------------------------------------------------------------------ todo lo que apuntaba al sitio
fotos, texturas = [], {}


def foto(m):
    if m.group(2) not in fotos:
        fotos.append(m.group(2))
    return '<img class="ft ft-%s%s" src="%s"' % (m.group(2), ' m-grande' if m.group(1) else '', PIXEL)


def textura(m):
    clave = m.group(2).strip('/').replace('/t/', '-').replace('.webp', '').replace('/', '-').replace('_', '-')
    texturas[clave] = m.group(2)
    return '<img class="%stx tx-%s" src="%s"' % ('muestra ' if m.group(1) else '', clave, PIXEL)


cuerpo = re.sub(r'<img (id="mFoto" )?src="/trabajos/f/([a-z0-9-]+)-720\.webp" srcset="[^"]*" sizes="[^"]*"', foto, cuerpo)
cuerpo = re.sub(r'<img (class="muestra" )?src="(/[a-z0-9_-]+/t/[a-z0-9-]+\.webp)"', textura, cuerpo)
cuerpo = re.sub(r' data-s="[^"]*" data-ss="[^"]*"', '', cuerpo)
cuerpo = re.sub(r'src="(/trabajos/f/[a-z0-9-]+-160\.webp)"', lambda m: 'src="%s"' % adentro(m.group(1)), cuerpo)
logo = adentro('/logo/logo_isotipo.png')
cuerpo = cuerpo.replace('src="/logo/logo_isotipo.png"', 'src="%s"' % logo)
cuerpo = re.sub(r'href="/trabajos/([a-z0-9-]+)/#([a-z0-9-]+)"', r'href="#m-\1" data-foto="\2"', cuerpo)
cuerpo = re.sub(r'href="/trabajos/([a-z0-9-]+)/"', r'href="#m-\1"', cuerpo)
cuerpo = cuerpo.replace('href="/trabajos/"', 'href="#galeria"')
cuerpo = re.sub(r'href="/(#[a-z0-9-]+)?"', lambda m: 'href="%s/%s" target="_blank" rel="noopener"' % (SITIO, m.group(1) or ''), cuerpo)
queda = re.findall(r'(?:src|href|srcset|data-s|data-ss)="/[^"]*"|url\(/[^)]*\)', cuerpo + css)
if queda:
    fallar('quedó algo que apunta al sitio y no se supo traer adentro: ' + ', '.join(sorted(set(queda))[:6]))

# ------------------------------------------------------------------ el archivo
link = re.search(r'<link href="https://fonts\.googleapis\.com/css2[^>]*>', gal)
css_letras, links_letras = letras(link.group(0)) if link else ('', '')
css_vista = '''
/* ===== SOLO DE LA VISTA PREVIA ===== */
.vp{display:none;scroll-margin-top:6rem}
.vp:target,.vp-inicio{display:block}
.vp:target~.vp-inicio{display:none}
.ft,.tx{background-size:cover;background-position:center;background-repeat:no-repeat}
.vp-aviso{background:#2A2520;color:#F3EDE3;font-size:.72rem;font-weight:600;letter-spacing:.04em;line-height:1.4;text-align:center;padding:.5rem 1rem}
''' + ''.join('.ft-%s{background-image:url(%s)}\n' % (n, adentro('/trabajos/f/%s-1080.webp' % n)) for n in fotos) \
    + ''.join('.tx-%s{background-image:url(%s)}\n' % (k, adentro(r)) for k, r in texturas.items())
JS = '''
<script>
// Solo de la vista previa: las miniaturas cambian la foto grande, y una foto de la galería abre la suya.
document.addEventListener('click', function(ev){
  if(!ev.target.closest) return;
  var el = ev.target.closest('.m-mini, a[href^="#m-"]'); if(!el) return;
  var mini = el;
  if(!el.classList.contains('m-mini')){
    var bloque = document.getElementById(el.getAttribute('href').slice(1)); if(!bloque) return;
    mini = el.hasAttribute('data-foto') ? bloque.querySelector('.m-mini[data-f="' + el.getAttribute('data-foto') + '"]') : bloque.querySelector('.m-mini');
  }
  if(!mini) return;
  var sec = mini.closest('.vp'), grande = sec.querySelector('.m-grande');
  grande.className = 'ft m-grande ft-' + mini.getAttribute('data-f'); grande.alt = mini.getAttribute('data-alt');
  [].forEach.call(sec.querySelectorAll('.m-mini'), function(x){ x.setAttribute('aria-pressed', x === mini ? 'true' : 'false'); });
});
</script>
'''
n_fotos = len(re.findall(r'<a class="t-card"', gal))
hoy = datetime.date.today()
aviso = 'Vista previa · todavía no está publicada · %d %s en %d %s · %02d/%02d/%d' % (
    n_fotos, 'foto' if n_fotos == 1 else 'fotos', len(slugs), 'material' if len(slugs) == 1 else 'materiales', hoy.day, hoy.month, hoy.year)
doc = '''<!DOCTYPE html>
<html lang="es-AR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="robots" content="noindex" />
<title>Trabajos · vista previa (sin publicar)</title>
%s<!-- Vista previa de /trabajos/. La arma 04-operations/trabajos/vista.py con lo que deja armar.py. No se sube al sitio. -->
<style>%s%s%s</style>
</head>
<body>
<div class="vp-aviso">%s</div>
%s%s</body>
</html>
''' % (links_letras, css_letras, css, css_vista, aviso, cuerpo, JS)
os.makedirs(os.path.dirname(os.path.abspath(SALIDA)), exist_ok=True)
open(SALIDA, 'w', encoding='utf-8', newline='\n').write(doc)
print('  %s: %d fotos, %d materiales, %.1f MB' % (SALIDA, n_fotos, len(slugs), len(doc.encode()) / 1048576))
