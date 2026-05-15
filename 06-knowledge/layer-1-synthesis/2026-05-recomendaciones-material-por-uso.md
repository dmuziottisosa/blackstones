# Matriz de recomendación de material por uso

> **Status:** `layer-1-synthesis` — síntesis operativa de criterios técnicos × catálogo BlackStones.
> **Última actualización:** 2026-05.
> **Validez:** alta confianza en specs físicas · media en preferencias estéticas argentinas (validar con WhatsApp transcripts).

---

## Por qué existe este doc

Cuando un cliente nos pregunta "qué me recomendás", Orlando (y cualquier asesor del equipo) necesita una respuesta **basada en su situación específica**, no en una preferencia personal. Este doc consolida cómo decidir según 5 variables del cliente.

---

## Las 5 variables que importan para recomendar

| Variable | Por qué pesa |
|---|---|
| **Uso real** (cocción / prep+desayuno / baño / isla / fondo) | Determina qué resistencia necesita el material |
| **Calidad del agua** (potable / pozo / dura) | Afecta visibilidad del sarro en mesadas oscuras |
| **Ya tiene otras mesadas** (qué color, qué material) | Quiere contraste o coherencia |
| **Presupuesto mental en USD** | Ancla la franja de precio |
| **Disposición a mantenimiento** (alta / baja) | Determina si tolera sellado periódico, vinagre, etc. |

---

## Matriz uso × material recomendado

### Uso: COCCIÓN (cocina principal completa con anafe y/o cocción intensa)

| Recomendación 1 | Recomendación 2 | Evitar |
|---|---|---|
| Sinterizado (Xtone, Neolith, Dekton, Suprastone) | Cuarzo (Silestone, Technistone) | Mármol (se opaca con cítricos), granito leather sin sellar |

**Por qué**: termoresistencia + resistencia a ácidos + porosidad ~0%.

### Uso: PREP + DESAYUNO (isla auxiliar, no cocción)

| Recomendación 1 | Recomendación 2 | Recomendación 3 |
|---|---|---|
| Sinterizado tipo Calacatta | Cuarzo blanco veteado | Mármol Calacatta real (con honestidad técnica) |

**Por qué**: bajo riesgo permite considerar mármol real (que sí se opaca con ácidos pero el riesgo en este uso es bajo). Cliente que valora "alma natural" puede ir por ahí.

### Uso: BAÑO (mesada principal del baño)

| Recomendación 1 | Recomendación 2 | Evitar |
|---|---|---|
| Mármol (Carrara, Statuario, Calacatta) | Granito de color claro | Sinterizado en baño chico (sobre-engineered) |

**Por qué**: en baño no hay cocción ni ácidos. El mármol acá viene a brillar. Ticket también más bajo (ver pricing).

### Uso: FONDO / REVESTIMIENTO (pared, no superficie de uso)

| Recomendación 1 | Recomendación 2 |
|---|---|
| Piedra traslúcida con backlight (Celestial Blue Guidoni) | Cuarcita / mármol vetado (sin restricciones de uso) |

**Por qué**: cero contacto con líquidos/calor → todas las opciones premium en juego.

---

## Matriz calidad de agua × color

### Agua de pozo / agua dura (zona GBA, mucha cal)

| Color | Recomendado | Por qué |
|---|---|---|
| Negro pulido | ⚠️ Visualmente desafiante | Sarro blanco sobre negro brilla → cada gota visible |
| Negro leather / brushed | ✅ Mucho mejor | Textura rompe el reflejo, disimula deposits |
| Gris medio / antracita texturado | ✅ Ideal | Camufla deposits |
| Blanco / claro | ✅ Sin problema | Deposits del mismo tono no se ven |
| Negro con vetas (Black Cosmic) | ✅ Buena alternativa | Vetas naturales rompen uniformidad |

### Agua potable normal

Todas las opciones igual de viables. La decisión es estética.

---

## Matriz contraste con mesada existente

### Cliente tiene Negro Brasil y quiere isla CLARA

Las 3 opciones canónicas:

1. **Sinterizado tipo Calacatta** (USD 540-700/m²) — Suprastone Calacatta Gold, Xtone Calacatta Antico
2. **Cuarzo blanco veteado** (USD 900-1030/m²) — Silestone Calacatta Classic/Gold
3. **Mármol Calacatta real** (USD 700-1100/m²) — Pura Calacatta Versalles/Borghini, Prima Calacatta Antico

**Recomendación default**: sinterizado tipo Calacatta. Ticket razonable, contraste perfecto, performance total.

### Cliente tiene mesada clara y quiere isla OSCURA

Mismo patrón inverso:
1. **Negro Boreal Leather** (ARS 308.210/m²) — textura disimula sarro si hay agua dura
2. **Black Cosmic** (ARS 320.000/m²) — vetas plateadas naturales
3. **Negro Brasil pulido** (USD 330/m²) — más económico, requiere mantenimiento si hay agua dura

---

## Honestidad técnica obligatoria por material

Cada material tiene UNA debilidad que mencionamos **proactivamente** antes que el cliente la descubra. Esto construye confianza vs. competencia que oculta:

| Material | La verdad que decimos antes |
|---|---|
| **Granito** | "No tiene garantía de fábrica. Hay que sellarlo cada 1-2 años para que no manche" |
| **Mármol** | "Se opaca con cítricos, vinagre y descalcificadores directos. No es para cocción intensa" |
| **Cuarzo** | "No tolera calor extremo directo (>150°C). Usar siempre apoyaplato" |
| **Sinterizado** | "Resistencia total pero estéticamente más 'frío' que el mármol natural" |
| **Negro pulido + agua dura** | "Vas a ver cada gota mal secada. No daño, solo contraste visual" |

---

## Pricing — rangos referenciales por categoría (USD/m²)

> **Última verificación:** 2026-05 desde `COLORS_DB` en `site/public_html/calculadora/calc.html`.
> **🗓️ Pendiente:** validación oficial con BlackStones el 15-may (ver `01-strategy/backlog.md`).

| Categoría | Rango USD/m² | Ejemplos |
|---|---|---|
| Granito nacional | ARS 280k-320k (≈USD 280-320) | Negro Boreal, Black Cosmic |
| Granito importado básico | USD 330-350 | Negro Brasil, Brushed |
| Granito importado premium | USD 900-1200 | Blanco Alpinus, Negro Absoluto |
| Cuarzo (Silestone) | USD 700-1030 | Calacatta Gold, Negro Tebas |
| Sinterizado básico | USD 540 | Suprastone (línea base) |
| Sinterizado Xtone | USD 600 | Calacatta Antico, Glem White, Alpinus White |
| Sinterizado Prima | USD 700-960 | Calacatta Antico/Vagli |
| Sinterizado Neolith | USD 830-1330 | Calacatta luxe, Estatuario |
| Mármol clásico | USD 700-935 | Negro Marquina, Carrara |
| Mármol Calacatta real | USD 1020-3366 | Calacatta Vagli, Calacatta Extra |
| Piedra translúcida premium | USD 1150 | Celestial Blue (Guidoni) |

---

## Plantilla de respuesta WhatsApp (cuando el cliente pregunta)

Ver `02-marketing/whatsapp/recommendation-patterns.md` (creado en sesión 2026-05).

Estructura mínima:

1. **Validar la intuición del cliente** (1 línea, sin paternalismo)
2. **2-3 caminos según prioridad** (precio / estética / durabilidad)
3. **Honestidad técnica del que tiene debilidad** (1 línea)
4. **Recomendación clara** (no "depende")
5. **CTA: medidas + zona → cotización 24 hs por escrito**
6. **Bonus: oferta de fotos / showroom**

---

## Trigger de revisión

- Cambia el catálogo (`COLORS_DB`)
- Lista oficial de precios del 15-may modifica significativamente los rangos
- Nuevo material recurrente que merezca matriz propia
- Default: revisión cada 90 días
