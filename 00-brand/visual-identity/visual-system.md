# Sistema visual — BlackStones

> Estado: **layer-3 active belief.** Última actualización: mayo 2026.
>
> Fuente: dump original en `06-knowledge/layer-0-raw/2026-05-brief-tecnico-original.md`.

---

## 1. Paleta

### Colores núcleo

| Hex | Nombre | Uso aproximado | Función |
|---|---|---|---|
| `#FAF8F4` | crema | ~70% de la superficie | fondo principal |
| `#1A1816` | dark | ~20% | headers, totales, marca, tipografía sobre crema |
| `#C4A77D` | dorado | ~10% | acentos, CTAs, highlights, bordes finos |

### Variantes derivadas (en uso en la calc y la landing)

| Hex | Nombre | Cuándo usar |
|---|---|---|
| `#F2EDE3` | crema secundario | sub-bloques sobre crema |
| `#D4B07A` | dorado claro | hovers, highlights más suaves |
| `#7A6649` | dorado oscuro / texto-acento | sub-líneas italic en exports, tags discretos |
| `#A89580` | dorado muted | texto m² embebido, captions sutiles |
| `#2A2522` | dark secundario | bloques dark con leve variación |
| `#EDE5D0` | "ST" — subtotal de sección | crema dorado, fondo de subtotales por sección |
| `#F5EFE2` | "ST2" — subtotal de grupo | crema intermedio, fondo de subtotales por grupo (tags) |

### Variante para el logo en fondo oscuro

- `#1A1514` — fondo dark cálido charcoal específico del backplate del logo en assets de IG.

---

## 2. Tipografías

| Familia | Rol | Cuándo usar |
|---|---|---|
| **Fraunces** (serif) | Headers editoriales | títulos de sección en landing, hero |
| **Montserrat** (sans display) | Display | subtítulos, eyebrows con `letter-spacing` |
| **Manrope** (sans UI) | UI | cuerpo de la calc, labels, inputs, tablas |

**Sentence case en todo.** Nunca ALL CAPS salvo eyebrows muy chicos con `letter-spacing` deliberado.

---

## 3. Reglas duras (anti-clínicas, anti-snobby)

- **Flat siempre.** Nada de gradientes, sombras pesadas, neón, brillos.
- **Borde dorado fino** como acento (1.5px top en subtotales, 3px lateral en cards).
- **Esquinas redondeadas suaves** (`border-radius` 4-12px).
- Nada de pills con border parcial — rompe el ojo.
- Sentence case siempre.
- Si una decisión visual no aporta a la **confianza del cliente final**, no va.

### Test interno antes de publicar cualquier visual

1. ¿Lo leería Carolina sin cerrar la pestaña?
2. ¿Una arquitecta lo respetaría?
3. ¿Podría salir en una nota de La Nación Living?

Si las tres son sí, está al nivel.

### Frase de calibración

> "Estilo Rolex, no Patek." → Preferimos lo legible y sólido sobre lo recargado-snobby.

---

## 4. Logo

- **Siempre exacto** como se provee. No recolorear, no redibujar, no simplificar, no agregar efectos.
- **En statics:** bottom-right corner, ~8-10% del ancho del frame. Integrado estéticamente — no pegado como sticker.
- **Sobre fondo claro:** versión dark del logo.
- **Sobre fondo oscuro:** versión crema del logo.
- **Margen mínimo en assets cuadrados de IG:** 20% libre desde cada borde (la imagen se muestra como círculo y los bordes se cortan).

Assets actuales en `site/public_html/logo/`:
- `logo_fondo_crema.png`
- `logo_fondo_tierra.png`
- `logo_isotipo.png`
- `logo_isotipo_crema.png`
- `og-image.png`

---

## 5. Subtítulos en video

- Base: blanco con outline/sombra negra para contraste universal.
- Highlight de palabra activa: **amarillo `#FFDD00`** (no el dorado de marca — el amarillo puro tiene mejor retención visual en video).

---

## 6. Cómo se valida un visual generado por IA

Antes de publicar (filtro de 4 pasos):
1. ¿Cumple la paleta núcleo?
2. ¿No tiene gradientes / brillos / sombras pesadas?
3. ¿Pasa el test de Living?
4. ¿El logo está usado exactamente como se provee?

Si alguna falla → regenerar. Sin excepciones.

---

## 7. Trigger de revisión de este doc

- Cambia la paleta oficial.
- Sumamos una tipografía o jubilamos una.
- Aparece un caso recurrente que las reglas no cubren.
- Default: revisión cada 90 días.
