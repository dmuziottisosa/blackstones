# CLAUDE.md — Entry point del repositorio BlackStones

> **Si estás leyendo esto por primera vez (humano o IA): este es el primer doc que tenés que leer. Te orienta en 60 segundos. No avances sin entenderlo.**

---

## 1. Qué es este repositorio

Este NO es solo el código de la web de BlackStones Marmolería. Es el **centro de operaciones estratégicas** del negocio: marca, copywriting, ads, creativos, prompts de IA, flow de WhatsApp, investigación de avatares, decisiones, libros y benchmarks.

Lo único que **no** vive acá es la contabilidad.

El sitio web (landing + calculadora) es **una carpeta entre nueve**. Vive en `site/public_html/`.

---

## 2. Filosofía operativa (no negociable)

> **"Decidimos rápido, pero solo después de haber investigado lento — cruzando datos propios, mercado, benchmarks de ganadores y validación científica — para operar siempre desde la probabilidad más alta posible, sabiendo que no hay certezas, solo sistemas que se retroalimentan."**

### Regla 1 — Brújula, no mapa

Los documentos sintetizados en este repo son **dirección, no verdad absoluta**. Una brújula excelentemente calibrada sigue siendo una brújula: orienta, no reemplaza el caminar.

Operacionalmente:
- Cuando un doc dice algo, asumimos **alta probabilidad de cierto, no certeza**.
- Antes de una decisión grande basada en un doc, **validamos contra realidad actual** (lenguaje crudo del avatar, métricas vivas, mercado en tiempo real).
- Si la realidad contradice el doc, **gana la realidad** y el doc se actualiza con fecha y razón.

### Regla 2 — Las 4 capas del conocimiento

Todo lo que sabemos vive en una de 4 capas (en `06-knowledge/`):

| Capa | Qué es | Quién la edita |
|---|---|---|
| **layer-0-raw** | Data virgen tal cual llegó (transcripts, dumps, briefings originales). **Nunca se edita.** | Solo se agrega, nunca se modifica. |
| **layer-1-synthesis** | Procesamiento estructurado de la layer-0. "Lo que el dump dice, ordenado." | Se reescribe cuando cambia layer-0. |
| **layer-2-landscape** | Investigación externa: libros, benchmarks de competencia, frameworks de la industria. | Se actualiza cuando descubrimos algo nuevo del mercado. |
| **layer-3-active-beliefs** | **Lo que creemos hoy que es verdad.** La brújula viva. Síntesis de las 3 capas anteriores filtrada por nuestra realidad operativa. | Se actualiza cuando una decisión nueva nos enseña algo. |

**Cuando dudes de algo, bajá una capa. Si layer-3 dice X y layer-1 dice Y, andá a layer-0 y resolvelo.**

### Regla 3 — Knowns, Known unknowns, Unknown unknowns

Toda decisión grande se documenta en `01-strategy/decision-log/` con tres listas explícitas:

- **Knowns** — qué sabemos con alta confianza y evidencia
- **Known unknowns** — qué sabemos que no sabemos (lista explícita, esto orienta qué investigar después)
- **Unknown unknowns** — qué territorio puede tener trampas que ni siquiera vemos (humildad institucional)

### Regla 4 — Datos > opiniones, siempre

Antes de afirmar algo en un doc:
- ¿Tenemos datos propios? (métricas de Meta, conversaciones reales de WhatsApp, presupuestos cerrados/perdidos)
- ¿Tenemos benchmark externo? (libros canónicos, estudios, ganadores comparables)
- ¿Tenemos validación reciente? (testeo en mercado, no asunción)

Si la respuesta a las tres es no, marcalo como hipótesis (`status: hypothesis`) y armá el plan para validar.

### Regla 5 — Anti-genericness

Cualquier párrafo que pueda aparecer en el blog de cualquier marketing agency genérica **no va**. Si un doc tiene "calidad premium", "servicio integral", "empresa familiar con tradición", o cualquier frase que ya leíste 1.000 veces, **no aporta**. Borralo o reemplazalo con dato específico.

---

## 3. Mapa del repositorio

```
00-brand/         identidad de marca (visual, voz, posicionamiento)
01-strategy/      avatares, mecanismo, principios operativos, decision log
02-marketing/     meta ads, copy, creatives, whatsapp
03-product/       materiales, servicios, lógica de pricing
04-operations/    procesos, ruteo de mediciones, deploy
05-ai-systems/    matriz de modelos de generación + prompt library
06-knowledge/     las 4 capas del saber
site/             la web pública (deploy 1:1 a Hostinger)
```

Cada carpeta tiene su `README.md` con detalle. Empezá por el README de la carpeta que te toque.

---

## 4. Cómo trabajar en este repo

### Si vas a tomar una decisión grande
1. Leé el `01-strategy/operating-principles.md` completo.
2. Leé el doc de `layer-3-active-beliefs/` que toque tu decisión.
3. Bajá a `layer-1-synthesis/` y `layer-0-raw/` para verificar la base.
4. Crea entry en `01-strategy/decision-log/YYYY-MM-DD-tema.md` con knowns / known-unknowns / unknown-unknowns.
5. Validá en realidad (test, conversación, dato).
6. Decidí. Documentá la decisión y por qué.

### Si vas a editar un doc existente
- Si es `layer-0-raw` → **no lo edites**. Si la data cambió, agregá un nuevo dump fechado.
- Si es `layer-1` → mirá si el cambio viene de `layer-0` actualizado. Sí: reescribí. No: paso a layer-3.
- Si es `layer-3` → actualizá con fecha y razón. Si el cambio es grande, hacelo decision-log.

### Si vas a sumar conocimiento nuevo
- Externo (libro, benchmark, estudio) → `06-knowledge/layer-2-landscape/`.
- Propio (dato, conversación, métrica) → `06-knowledge/layer-0-raw/` con fecha en el nombre.
- Síntesis tuya → `06-knowledge/layer-1-synthesis/`.

### Si vas a desplegar un cambio al sitio (Hostinger)

> **TL;DR:** editás local en `site/public_html/`, copy-paste un bloque de PowerShell, listo.

Tres docs canónicos en `04-operations/`, leelos en este orden:

1. **`ftp-map.md`** — modelo mental obligatorio. La regla que evita el error #1: el FTP user **NO** aterriza en `public_html/`. Aterriza en `/home/u144473384/`. El web root real está en `domains/blackstones.com.ar/public_html/`. Toda ruta de upload tiene que empezar con eso.
2. **`deploy-snippets.md`** ⭐ — 6 recetas listas para copy-paste en PowerShell. Cada bloque es autocontenido (carga `.env`, sube, abre el browser para verificar). Las más usadas:
   - Receta 1: cambié `index.html`, deploy.
   - Receta 2: cambié `calc.html`, deploy.
   - Receta 3: cambié otro archivo, deploy.
   - Receta 4: sync de carpeta entera (vía WinSCP).
3. **`deploy-notes.md`** — referencia detallada (camino manual, `deploy.ps1` script completo, checklists pre/post deploy, rollback).

**Setup mínimo (1 sola vez):** crear `.env` en raíz del repo con `FTP_HOST` / `FTP_USER` / `FTP_PASS` / `FTP_REMOTE_BASE=domains/blackstones.com.ar/public_html` / `FTP_LOCAL_BASE=site/public_html`. El `.env` está gitignorado.

### Si dudás dónde poner algo
Preguntá antes de adivinar. Una sola fuente de verdad por tema. El resto cross-linkea con paths relativos.

---

## 5. Reglas duras de este negocio (memorizar)

- **No mencionar VenarStones** en ninguna comunicación pública. (Rebrand 2026, separación de socios — interno.)
- **Plazos reales:** 15-20 días corridos desde medición. Sinterizado: 20. **Nunca decir "10 días".**
- **No coordinamos gremios** (carpinteros, plomeros). El cliente ya los tiene.
- **Medimos cuando los muebles ya están puestos.** Es un filtro, no una limitación.
- **Presupuesto cerrado por escrito en 24 hs por WhatsApp.** Es el mecanismo diferenciador.
- **Granito sin garantía** se dice explícito. Suma confianza, no resta.
- **Avatar principal: Carolina** (38-55, ABC1, en obra). 80% del presupuesto va a ella.
- **Tono:** rioplatense, "vos", sin jerga, conversacional. Nada de marketing de manual.
- **Test de Living:** ¿podría aparecer en La Nación Living sin desentonar?

---

## 6. Estado actual del negocio (snapshot mayo 2026)

- **+500 mesadas colocadas** en CABA y GBA.
- **Zona de operación:** CABA + GBA, sale de Lanús para mediciones.
- **Meta Ads:** $1.000 USD/mes (2 campañas de $14/día — 1 video + 1 static, ambas 1-1-1).
- **Leads de hoy:** ~25 buenos por WhatsApp.
- **Funnel actual:** ad → click WhatsApp → cotización 24 hs → cierre.
- **Sitio web:** `blackstones.com.ar` en Hostinger, dominio en donweb.
- **Calculadora interna:** `blackstones.com.ar/calculadora/` (auth con HMAC, password compartido con equipo).

---

## 7. Qué NO hacer en este repo

- No subir credenciales (FTP, DB, secrets) a archivos versionados. Usar `.env` (gitignorado).
- No commitear el `auth_config.php` de la calc rotado a producción sin avisar.
- No reescribir `06-knowledge/layer-0-raw/` — solo agregar.
- No inventar avatares, métricas o frameworks. Si no existe data, marcar `status: hypothesis`.
- No usar emojis salvo donde el sistema visual los autorice. ◾ y 📐 sí. 🔥 🚨 🙌 no.
- No documentar lo obvio. Si "se entiende del nombre del archivo", no escribas un README de 200 líneas.

---

## 8. Cuándo se actualiza este doc

- Cambia la filosofía operativa.
- Cambia la estructura de carpetas (ej: sumamos `07-finance/` algún día).
- Cambian las reglas duras del negocio (ej: ya garantizamos granito, ya coordinamos plomería).
- Default: revisión cada 90 días aunque no haya cambio aparente.

---

*Última actualización: mayo 2026. Próxima revisión obligatoria: agosto 2026.*
