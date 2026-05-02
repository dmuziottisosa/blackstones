# Meta Ads — Estrategia y estado

> Estado: **layer-3 active belief.** Última actualización: mayo 2026.
>
> Snapshot vivo del estado de Meta Ads + framework de decisión sobre escalamiento.

---

## 1. Estado actual (mayo 2026)

### Presupuesto
**USD 1.000 / mes total.**
Distribución: 2 campañas de USD 14/día = ~USD 840/mes en campañas + buffer.

### Estructura
```
Campaña 1 — Mensajes - Carolina - Video         → USD 14/día (1-1-1)
Campaña 2 — Mensajes - Carolina - Static        → USD 14/día (1-1-1)
                                       Total: USD 28/día → ~USD 840/mes
```

**1-1-1** = 1 campaña / 1 ad set / 1 anuncio. Estructura ultra-limpia para no contaminar aprendizaje del algoritmo.

### Funnel
```
Anuncio (video o static)
   ↓ click
WhatsApp click-to-chat (mensaje pre-llenado)
   ↓ respuesta nuestra < 2 hs
Conversación de calificación (medidas + material + zona)
   ↓ < 24 hs
Cotización cerrada por escrito
   ↓ ~3-7 días
Medición técnica
   ↓ 15-20 días corridos
Colocación
```

### Mensaje pre-llenado WhatsApp (click-to-chat)
```
Hola, buenas. Vi el anuncio y me interesa cotizar una mesada de cocina.
```

### Resultado de hoy (snapshot ejemplar — mayo 2026)
- ~25 leads buenos (escribieron con intención real).
- Costo bruto por lead: ~USD 16 (presupuesto / leads).
- Status del cierre: pendiente de medir tasa real.

> **Known unknown:** tasa de cierre lead → presupuesto cerrado → mesada vendida. Sin atribución, no podemos calcular CAC real. **Acción:** loggear cada lead con outcome.

---

## 2. Creativo ganador actual

### Video
Guion en `02-marketing/creatives/video-scripts/guion-ganador-2026-05.md`. Performance: bueno desde día 1 (estrenado mayo 2026, sigue corriendo).

### Static
[pendiente — agregar referencia al static actual + screenshot]

---

## 3. CTA único

> **"Mandanos largo x ancho por WhatsApp."**

Es el único CTA. Cualquier creativo que no termine ahí, no es un creativo de Meta — es content orgánico.

---

## 4. Mecanismo prometido en ads (no se rompe)

- Cotización cerrada por escrito en 24 hs por WhatsApp.
- Plazo en días corridos (no semanas).
- Todo adentro: medición + fabricación + flete + colocación.
- +500 mesadas colocadas en CABA y GBA.

Estos son los 4 puntos que **siempre aparecen** en algún lugar del creativo o del copy. Detalle: `01-strategy/mechanism.md`.

---

## 5. Plan de escalamiento

### Por qué escalar
Hoy gastamos USD 1k/mes y traemos 25 leads buenos hoy (extrapolable a ~700/mes si el ritmo se sostiene). El cuello no es leads, es **aprendizaje** — con un solo creativo ganador no podemos atacar 5 ángulos del avatar.

### Plan a USD 2k/mes
Cuando se apruebe duplicar:
```
Campaña 1 — Mensajes - Carolina - Video         → $14/día (mantener ganador)
Campaña 2 — Mensajes - Carolina - Static        → $14/día (mantener ganador)
Campaña 3 — Mensajes - Carolina - Static V2     → $14/día (testear nuevo ángulo)
Campaña 4 — Mensajes - Arquitecta - Mix         → $14/día (NUEVO avatar)
                                       Total: $56/día → $1.680/mes
```

### Plan a USD 3-4k/mes
Cuando validemos que arquitecta convierte:
- Sumar campaña Baño (avatar secundario subdesarrollado).
- Sumar campaña Reemplazo simple (avatar identificado como gap).
- Sumar campaña Sector comercial (gap, hipótesis).

### Reglas de escalamiento
- **No escalar un ad set ganador en > 30% por semana.** Mete al algoritmo en learning otra vez.
- **No matar un test antes de 7 días** salvo que quemes presupuesto sin un solo mensaje (CTR < 0.5% + CPM > USD 12 + 3 días).
- **No prender 5 campañas el mismo día.** Una nueva por semana max.
- **Cada campaña nueva = decision-log** (`01-strategy/decision-log/`).

---

## 6. Pool de creativos a probar (próximo trimestre)

> Status: pipeline. **Cada test = una variable, nunca dos.**

### Statics — ángulos prioritarios
1. "El que responde primero gana" (mecanismo 24hs)
2. "Días corridos, no semanas" (mecanismo plazo)
3. "El presupuesto que sí cierra" (mecanismo cerrado por escrito)
4. "+500 mesadas" (social proof)
5. "El que hizo lo de tu vecina" (UGC / proof local)
6. "No vendemos piedra, vendemos certeza" (manifiesto)

### Videos — ángulos prioritarios
1. Variantes del guion ganador (cambio de hook inicial, no del cuerpo).
2. UGC del founder explicando el proceso en 30 segundos.
3. Time-lapse de medición → fabricación → colocación.
4. Testimonio cliente real (con permiso) en su cocina nueva.

### Avatares secundarios a abrir
1. **Arquitecta** — usar `ARQ #51 / #65 "Tu proveedor te dejó colgado"` o `ARQ #66 "Para tu próximo proyecto"`.
2. **Baño** — usar `BAÑO #1 hero` o `BAÑO #4 en obra`.
3. **Reemplazo simple** — desarrollar (no tenemos creativo aún).

Ver pool completo en `02-marketing/creatives/static-ads/index.md`.

---

## 7. Lo que NO vamos a hacer

- **No campaña de awareness puro.** Carolina ya nos puede encontrar; el problema es captura, no awareness.
- **No campañas de tráfico web.** El sitio es brochure, no convierte directo. La conversión vive en WhatsApp.
- **No catálogo de productos en Meta.** Vendemos servicio + asesoramiento, no SKU.
- **No comprar reseñas.** Dolor #5 de Carolina es justamente la desconfianza — un fake la prende como alarma.

---

## 8. Tests-log

Cada test corrido se documenta en `tests-log.md` con: hipótesis, variable, resultado, aprendizaje. Si todavía no existe → crear apenas se corra el primer test estructurado.

---

## 9. Trigger de revisión de este doc

- Cambio de presupuesto > 30%.
- Apertura de avatar nuevo en ads.
- Validación de un creativo ganador nuevo.
- Cambio en plataforma Meta que afecte estructura (nueva interfaz, nuevo objetivo, retiro de feature).
- Default: revisión cada 30 días (este doc se mueve más rápido que el resto del repo).
