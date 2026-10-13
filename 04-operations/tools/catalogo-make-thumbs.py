"""Genera miniaturas del catálogo: <carpeta>/t/<nombre>.webp
Lado corto = 560 px (cubre un tile de ~220 px CSS a DPR 2-3), WebP q72.
Los originales NO se tocan (el visor/lightbox los sigue usando)."""
import re, os, sys
from PIL import Image
root = sys.argv[1]
html = open(os.path.join(root, 'index.html'), encoding='utf-8').read()
pat = re.compile(r"<span class=\"color-tile-(portada|muestra)\" style=\"background-image: url\('([^']+)'\);\"></span>")
srcs = sorted({m.group(2) for m in pat.finditer(html)})
SHORT = 560
before = after = 0; made = 0
for rel in srcs:
    src = os.path.join(root, rel)
    d, fn = os.path.split(rel)
    out_rel = f"{d}/t/{os.path.splitext(fn)[0]}.webp"
    out = os.path.join(root, out_rel)
    os.makedirs(os.path.dirname(out), exist_ok=True)
    im = Image.open(src)
    im = im.convert('RGBA' if im.mode in ('RGBA', 'LA', 'P') else 'RGB')
    w, h = im.size
    s = min(w, h)
    if s > SHORT:
        k = SHORT / s
        im = im.resize((round(w * k), round(h * k)), Image.LANCZOS)
    im.save(out, 'WEBP', quality=72, method=6)
    before += os.path.getsize(src); after += os.path.getsize(out); made += 1
print(f"miniaturas: {made}  originales {before/1e6:.2f} MB -> miniaturas {after/1e6:.2f} MB ({100*after/before:.0f}%)")
