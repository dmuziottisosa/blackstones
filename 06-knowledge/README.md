# 06-knowledge/

El conocimiento del proyecto, organizado en 4 capas. **Esta es la columna vertebral del sistema.**

> Detalle conceptual de las capas en `01-strategy/operating-principles.md` § 2.

---

## Las 4 capas

### `layer-0-raw/` — Data virgen
**Nunca se edita.** Solo se agrega.

Qué va acá:
- Briefings originales tal cual nos llegaron.
- Transcripts de conversaciones (WhatsApp, llamadas, reuniones).
- Compendios y dumps históricos sin procesar.
- Capturas de pantalla con timestamp.
- Resultados crudos de campañas exportados.

Convención de nombres: `YYYY-MM-tema.md` o `YYYY-MM-DD-tema.md`.

**Si la realidad cambió → no edites el dump viejo.** Agregá uno nuevo más reciente. La layer-0 es historia, no presente.

---

### `layer-1-synthesis/` — Procesamiento estructurado
"Lo que el dump dice, ordenado." Sin opinión, sin filtro estratégico — solo estructura.

Qué va acá:
- Resúmenes ordenados de un dump específico.
- Tablas / matrices que organizan info dispersa de la layer-0.
- Listas de hallazgos enumerados.

**Cuándo se reescribe:** cuando entra nueva data en layer-0 que cambia lo sintetizado.

---

### `layer-2-landscape/` — Investigación externa
Lo que sabe el mundo fuera de BlackStones que es relevante para nosotros.

Subcarpetas:
- `libros/` — síntesis de libros canónicos del rubro (copy, marketing, behavioral econ)
- `competencia/` (pendiente) — perfiles de competidores en CABA
- `benchmarks/` (pendiente) — casos de estudio de ganadores en rubros adyacentes
- `industry/` (pendiente) — datos del rubro marmolería en Argentina

**Cuándo se actualiza:** cuando descubrimos algo nuevo del mercado / industria / disciplina.

---

### `layer-3-active-beliefs/` — La brújula viva
**Lo que creemos hoy que es verdad.** Síntesis de las 3 capas anteriores filtrada por nuestra realidad operativa.

Qué va acá:
- Conclusiones operativas que guían decisiones.
- Beliefs revisables con evidencia.
- Hipótesis testeables marcadas con `status: hypothesis`.

**Cuándo se actualiza:** cuando una decisión nueva nos enseña algo, cuando layer-1 o layer-2 agregan data que cambia una conclusión.

> Nota: muchos docs en `00-brand/`, `01-strategy/`, `02-marketing/`, `03-product/` ya son layer-3 active beliefs, escritos directamente en su carpeta funcional. Esta capa de `06-knowledge/layer-3-active-beliefs/` es para beliefs que **no caben en una sola carpeta funcional** o que son meta-beliefs sobre cómo opera el negocio.

---

## Cómo navegar el conocimiento

**Si dudás de algo, bajá una capa:**

```
"¿Es verdad que X?"
   ↓
Layer-3 dice: "Sí, X es verdad."
   ↓
"¿Cómo lo sabe?"
   ↓
Layer-2 / Layer-1 lo respaldan con dato.
   ↓
"¿De dónde salió ese dato?"
   ↓
Layer-0 tiene el origen crudo.
```

Si al bajar una capa el respaldo no aparece → marcar la afirmación como `status: hypothesis` en la capa superior y armar plan de validación.

---

## Reglas inviolables

1. **No editar layer-0.** Si la data cambió, agregá un nuevo dump fechado.
2. **Toda afirmación en layer-3 debe poder rastrearse a layer-1, layer-2 o layer-0.** Si no, es opinión sin base.
3. **Lo que está en layer-3 hoy puede estar mal mañana.** Cuando aparece evidencia que contradice → actualizar con fecha y razón. La regla Brújula-no-Mapa es no negociable.
4. **Una sola fuente de verdad por tema.** El resto cross-linkea con paths relativos.

---

## Cuándo se actualiza la estructura

- Sumamos una capa nueva (raro).
- Una capa actual deja de tener uso.
- Default: revisión cada año.
