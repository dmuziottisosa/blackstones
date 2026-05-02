# Principios operativos — BlackStones

> Estado: **layer-3 active belief.** Última actualización: mayo 2026.
>
> Este es el doc más importante del repo después de `CLAUDE.md`. Define **cómo decidimos**, no qué decidimos.

---

## 1. La filosofía de fondo

> **Decidimos rápido, pero solo después de haber investigado lento — cruzando datos propios, mercado, benchmarks de ganadores y validación científica — para operar siempre desde la probabilidad más alta posible, sabiendo que no hay certezas, solo sistemas que se retroalimentan.**

Tres ideas que merecen desempaquetarse:

### "Investigar lento, decidir rápido"
La velocidad de la decisión final es **inversamente proporcional** al rigor de la investigación previa. Si investigaste bien, la decisión es obvia y rápida. Si la decisión te cuesta, no investigaste lo suficiente.

### "Cruzando 4 fuentes"
- **Datos propios:** lo que pasa en nuestro WhatsApp, en nuestra calc, en nuestra cuenta de Meta.
- **Mercado:** qué hace la competencia, qué precios circulan, qué materiales mueve el rubro.
- **Benchmarks de ganadores:** qué hacen los que están ganando en rubros adyacentes (no solo marmolería — ecommerce, servicios premium, B2C de ticket alto).
- **Validación científica:** copywriting clásico, psicología del consumidor, behavioral economics. Los libros que sintetizamos en `06-knowledge/layer-2-landscape/libros/`.

Una sola fuente sesga. Cuatro fuentes triangulan.

### "No hay certezas, solo sistemas que se retroalimentan"
Toda decisión es una hipótesis. El sistema bueno es el que **mide el resultado de la decisión y lo retroalimenta** al próximo ciclo. Por eso el `decision-log/` no es burocracia — es el ciclo de feedback.

---

## 2. Las cinco reglas operativas

### Regla 1 — Brújula, no mapa

Los documentos de síntesis del repo son **dirección, no verdad absoluta**. Una brújula excelentemente calibrada sigue siendo una brújula: orienta, no reemplaza el caminar.

**Operacionalmente:**
- Cuando un documento dice algo, asumimos **alta probabilidad de cierto, no certeza**.
- Antes de tomar una decisión grande basada en un documento, **validamos contra realidad actual** (lenguaje crudo del avatar, métricas vivas, mercado en tiempo real).
- Si la realidad contradice el documento, **gana la realidad** y el documento se actualiza con fecha y razón.

### Regla 2 — Cuatro capas del conocimiento

Todo lo que sabemos vive en una de cuatro capas (`06-knowledge/`):

| Capa | Qué es | Quién la edita |
|---|---|---|
| **layer-0-raw** | Data virgen tal cual llegó (transcripts, dumps, briefings originales). | **Nunca se edita.** Solo se agrega. |
| **layer-1-synthesis** | Procesamiento estructurado de la layer-0. "Lo que el dump dice, ordenado." | Se reescribe cuando cambia layer-0. |
| **layer-2-landscape** | Investigación externa: libros, benchmarks de competencia, frameworks. | Se actualiza cuando descubrimos algo nuevo del mercado. |
| **layer-3-active-beliefs** | **Lo que creemos hoy que es verdad.** La brújula viva. | Se actualiza cuando una decisión nueva nos enseña algo. |

**Cuando dudes, bajá una capa.** Si layer-3 dice X y layer-1 dice Y, andá a layer-0 y resolvelo.

### Regla 3 — Knowns / Known unknowns / Unknown unknowns

Toda decisión grande se documenta en `01-strategy/decision-log/` con tres listas explícitas.

| Categoría | Pregunta |
|---|---|
| **Knowns** | ¿Qué sabemos con alta confianza y evidencia? |
| **Known unknowns** | ¿Qué sabemos que no sabemos? (lista explícita — esto orienta qué investigar después) |
| **Unknown unknowns** | ¿Qué territorio puede tener trampas que ni siquiera vemos? (humildad institucional) |

El objetivo de los **known unknowns** es convertirlos en knowns con investigación. El objetivo de los **unknown unknowns** es bajar la probabilidad de sorpresas catastróficas.

### Regla 4 — Datos > opiniones

Antes de afirmar algo en un doc:

- ¿Tenemos **datos propios**? (métricas Meta, WhatsApp, presupuestos cerrados/perdidos, tasa de cierre)
- ¿Tenemos **benchmark externo**? (libros canónicos, estudios, ganadores comparables)
- ¿Tenemos **validación reciente**? (testeo en mercado, no asunción)

Si la respuesta a las tres es no → marcar como hipótesis (`status: hypothesis`) y armar plan para validar. **Una hipótesis honesta es mejor que una afirmación sin base.**

### Regla 5 — Anti-genericness

Cualquier párrafo que pueda aparecer en el blog de cualquier marketing agency genérica **no va**. Si un doc tiene "calidad premium", "servicio integral", "empresa familiar con tradición", o cualquier frase que ya leíste 1.000 veces, **no aporta**. Borralo o reemplazalo con dato específico.

**Test:** si un competidor copy-pegara el párrafo en su web, ¿se notaría? Si no, sobra.

---

## 3. El ciclo de decisión estándar

Para una decisión grande (presupuesto, ángulo de campaña, cambio de mecanismo, contratación):

```
1. DEFINIR    → Una frase. ¿Qué estamos decidiendo?
2. INVESTIGAR → 4 fuentes (propias, mercado, benchmarks, libros). Lento.
3. CRUZAR     → ¿Las 4 apuntan al mismo lado o se contradicen?
4. KNOWNS     → Listar los 3 grupos (knowns / known unknowns / unknown unknowns).
5. DECIDIR    → Rápido. La decisión cae sola si los pasos 1-4 se hicieron bien.
6. DOCUMENTAR → Decision-log con fecha, autor, hipótesis testeable.
7. EJECUTAR   → Con métrica de éxito definida ANTES de empezar.
8. RETRO      → A los 7-14-30 días según la decisión. Actualizar layer-3.
```

Si el paso 5 te cuesta → repetir paso 2.

---

## 4. Cuándo se viola este proceso (y está bien)

- **Emergencias operativas** (cliente pide algo a las 2am, se rompió el sitio, etc.). Decisión por instinto, post-mortem después.
- **Decisiones reversibles de bajo costo** (cambiar copy de un caption, probar un hook nuevo). No requieren decision-log formal.
- **Tests de campaña** (cambiar una variable a la vez en Meta). Tienen su propio sistema en `02-marketing/meta-ads/tests-log.md`.

**El proceso es para decisiones grandes con consecuencias largas.** No es burocracia para todo.

---

## 5. Sesgo a vigilar

### Sesgo de confirmación
Buscamos data que confirma lo que ya creemos. **Antídoto:** antes de decidir, listar 2 razones por las que la decisión podría salir mal. Si no se te ocurren 2, no investigaste suficiente.

### Sesgo de novedad
La idea nueva nos parece mejor que la vieja por ser nueva. **Antídoto:** ¿qué dato concreto cambió desde la última vez que decidimos esto?

### Sesgo de autoridad
Un libro famoso lo dijo, entonces es verdad. **Antídoto:** los libros son layer-2-landscape, no layer-3. Aplican a nuestro contexto solo si los validamos en él.

### Sesgo del último cliente
"El último cliente dijo X, hagamos Y." **Antídoto:** una conversación es anécdota. Tres conversaciones es patrón. Diez es señal.

---

## 6. Cuándo se actualiza este doc

- Cambia la filosofía de fondo (raro).
- Encontramos un sesgo nuevo recurrente que no estaba listado.
- Una regla operativa demuestra ser inviable o falsa.
- Default: revisión cada 6 meses.

---

## 7. Lectura recomendada antes de tomar decisiones grandes

- `01-strategy/avatars/carolina.md` — quién es nuestro cliente.
- `00-brand/positioning/positioning.md` — qué prometemos y a quién.
- El doc específico de la decisión en `06-knowledge/layer-3-active-beliefs/`.
- Los relevantes de `06-knowledge/layer-2-landscape/libros/` (Schwartz, Sugarman, Hopkins, Whitman, Kahneman, Blair).
