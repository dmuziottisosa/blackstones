# Decision Log — Sesión mayo 2026: mejoras múltiples calc + hub + RAG

> **Fecha de cierre:** 2026-05-15
> **Tipo:** sesión larga de iteración (~30 commits)
> **Trigger:** múltiples bugs, hardening, features y captions surgieron durante operación.

---

## Knowns (alta confianza, con evidencia)

### Sobre el sistema (calc + hub + API)

- **Hardening de seguridad necesario antes de operar real:** detectamos 7 vulnerabilidades reales (IP spoofing en rate limiting, CSRF faltante, JSON corruption sin backup, integer overflow, N+1 query, Content-Disposition con tildes, TODOs obsoletos). 6 fixes implementados, 1 (N+1 real) postpuesto a backlog.
- **Health endpoint funciona en producción.** Hostinger soporta el patrón `/api/health.php` público sin auth. Apto para UptimeRobot.
- **Cron de Hostinger está activo:** `health.php` reportó `cron_recent: true` con 16.5h. Falta verificar manualmente cuál de los dos crons corre (cleanup-retention vs monthly-report) — ambos escriben al mismo log.
- **Deploy via repo público temporal + `Invoke-WebRequest` desde PowerShell** es 30x más rápido que paste de b64 chunked (~20 segundos vs ~30 minutos). Validado en múltiples ciclos.
- **Reset de contador funciona:** `reset-once.php` con `?scope=light` borra `clientes-index.json` y deja el next-nro en 0001. Endpoint debe nombrarse SIN underscore inicial (htaccess bloquea `^_`).

### Sobre copywriting (captions + WhatsApp + scripts)

- **El framework de 3 tonos + recomendación funciona.** Cuando se generan 3 variantes (editorial / directo / observacional) + recomendación clara, el cliente elige rápido y queda satisfecho. Repetido 6+ veces en la sesión.
- **Anti-genericness es la diferencia real vs. competencia argentina.** Una pieza editorial de BlackStones se reconoce por lo que NO dice ("amplia gama", "calidad premium", "para todos los gustos").
- **Patrones de IG de cuentas premium globales son aplicables al rioplatense:** em-dashes, punto interpunct para specs, lowercase deliberado (con cuidado), credits-style al pie, cero hashtags en caption. Investigación documentada.
- **La frase ancla "tatuable" hace memorable el caption.** 6 patrones validados (categoría reframed, contraste sin/con, decir lo que no es, insight invisible, división de responsabilidades, reframe temporal).
- **Sintered stone es el tradeoff óptimo** para clientes que dudan entre estética y mantenimiento. Imita mármol/madera sin sus debilidades.

### Sobre operación de cliente

- **Cliente Solution Aware con consulta específica gana más con respuesta directa que con catálogo.** Validado con consulta real "isla 170x90 para prep + desayuno". Recomendación clara (1 material) + alternativas (2) + honestidad técnica (1) supera ofrecer 5+ opciones.
- **El modal de edición rápida en hub completa el flow.** Cliente nombre, concepto, montos, fecha — todo editable sin abrir la calc. Para cambios mayores (items/m²) abre calc en otra tab.
- **La persistencia de draft en localStorage es necesaria para UX:** usuario navega entre tabs sin perder estado. Implementado con auto-save cada 2s + restore con toast.

---

## Known unknowns (lo que sabemos que no sabemos)

- **¿Ambos crons corren?** El log es escrito por ambos pero no distinguimos cuál de los dos sin abrir `_cron-log.txt`. Pendiente: validación manual.
- **¿Cuántos transcripts reales de WhatsApp tenemos para validar el patrón de respuesta?** Hipótesis: 0-5. Necesitamos 30+ para confirmar lenguaje real de Carolina.
- **¿La performance del guion ganador en Meta Ads cómo es?** Status en `02-marketing/creatives/video-scripts/guion-ganador-2026-05.md` dice "pendiente loggear". Hay que medir.
- **¿Cuándo la competencia argentina adopta los patrones premium que usamos?** Si pasa, hay que rotar. Sin sistema de monitoreo competitivo todavía.
- **¿La lista oficial de precios de BlackStones del 15-may matchea los rangos del catálogo `COLORS_DB`?** Variancias entre cálculo automático y precio real son riesgo de "extras al colocar" (dolor #4 Carolina).
- **¿El "ajuste manual" en `edit-cotizacion.php` afecta los reportes mensuales?** Si una cot es editada manualmente, el monto en `by-month/*.json` será el ajustado, no el calculado. Es lo correcto, pero documentar.

---

## Unknown unknowns (territorio que no vemos todavía)

- ¿Cómo se comporta el sistema con 500+ cotizaciones activas? Solo testeamos con <10.
- ¿Hay edge cases en `bsApplyState` (calc.html) que rompen al cargar un draft de hace 24 horas?
- ¿La pestaña "Cronograma" (en backlog) cambia cómo entendemos la operación, una vez implementada?
- ¿El switch publicidad/local (en backlog) revela que el 80/20 del avatar Carolina está mal calibrado?
- ¿La fotografía de proyectos terminados sigue siendo el ROI más alto vs. video, o se invirtió en 2026?

---

## Decisiones tomadas

| Decisión | Razón |
|---|---|
| Aplicar todos los fixes de hardening del deep review | Riesgo > costo, son cambios chicos y críticos antes de producción real |
| Adoptar `Invoke-WebRequest` desde repo público como patrón canónico de deploy | 30x más rápido que paste, prueba empírica de 3 sesiones |
| Crear modal de edición rápida en hub en vez de extender la calc | UX más liviano para cambios pequeños, no requiere recalcular items |
| Persistir draft en localStorage (auto-save 2s) en vez de server-side | Más simple, no expone draft incompleto a otras sesiones de servidor |
| Documentar formato CapCut como archivo canónico en repo | No volver a regenerarlo de cero en cada sesión |
| Documentar patrones de captions + WhatsApp + IG research en RAG | Próximas sesiones (con cualquier IA o persona) arrancan con base |
| `reset-once.php` con guard `?confirm=YES` | Endpoint admin destructivo necesita doble candado |

---

## Decisiones que NO se tomaron (pero se evaluaron)

| Opción descartada | Por qué |
|---|---|
| Reescribir N+1 en `list.php` con index file | Postergada — postergar hasta tener >100 cotizaciones activas reales |
| Hacer público el repo permanentemente | Riesgo > beneficio. Estrategia/avatars/decisiones son competitivamente sensibles |
| Generar audio automático con cues `[excited]` en cada caption nuevo | No relevante — los captions son texto, no audio |
| Subir todos los captions a un CMS para gestionarlos | Por ahora `02-marketing/copywriting/captions/` en git es suficiente |
| Migrar de filesystem JSON a SQLite | No hay dolor real todavía. Filesystem aguanta 5k+ cotizaciones |

---

## Output persistido en el repo

### Código deployado
- `auth_check.php`, `_auth.php`, `_config.php` (hardening)
- `save.php`, `state.php`, `delete.php`, `edit-concepto.php`, `mark-viewed.php` (CSRF)
- `next-nro.php` (overflow guard)
- `list.php` (sort optimization + retorno de email/dirección/ajuste_manual)
- `download-zip.php` (RFC 5987)
- `health.php` (nuevo — UptimeRobot)
- `monthly-report.php` (TODO obsoleto limpiado + materiales en index)
- `edit-cliente.php` (nuevo — cliente fields editables)
- `edit-cotizacion.php` (nuevo — modal de edición rápida)
- `reset-once.php` (nuevo — reset counter one-shot)
- `index.html` (landing — fix typos catálogo)
- `calculadora/calc.html` (Alpinus White, Glem White, Classic Bulgari, autoxlsx, draft persistence)
- `presupuestos/index.php` (cliente nombre editable + modal edit + tabla visual)

### Docs persistidos
- `01-strategy/backlog.md` (priorizado con recordatorio 15-may)
- `01-strategy/decision-log/2026-05-15-sesion-mayo-mejoras-multiples.md` (este doc)
- `02-marketing/creatives/video-scripts/capcut-output-format.md` (formato canónico)
- `02-marketing/creatives/video-scripts/README.md` (pipeline de video)
- `02-marketing/copywriting/frameworks/captions-blackstones-framework.md` (framework operativo)
- `02-marketing/whatsapp/recommendation-patterns.md` (plantilla respuesta)
- `06-knowledge/layer-1-synthesis/2026-05-recomendaciones-material-por-uso.md` (matriz)
- `06-knowledge/layer-2-landscape/ig-luxury-design-caption-patterns.md` (research)
- `06-knowledge/layer-3-active-beliefs/anti-genericness.md` (creencia)

---

## Próximas acciones (de aquí salen items para backlog)

🔴 **Crítico**:
- 15-may: pedir lista oficial de precios a BlackStones (ver `backlog.md`)
- Validar manualmente ambos crons en `_cron-log.txt`
- Configurar backup automático nocturno de `bs-data/`
- Conectar UptimeRobot a `health.php`

🟡 **Medio**:
- Implementar switch publicidad/local en estado de presupuesto
- Implementar pestaña Cronograma
- WhatsApp directo desde hub (1 click → wa.me)
- Cliente recurrente / VIP detection en calc

🟢 **Quick wins**:
- Concepto auto-suggest con datalist
- Reportería con gráficos Chart.js
- Search por rango de fechas en activos
- Audit log liviano

---

## Trigger de revisión

Este doc no se revisa — es un punto en el tiempo. Las nuevas decisiones generan **nuevas** entradas en `01-strategy/decision-log/` con su propia fecha.
