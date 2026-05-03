# Implementar registry de presupuestos (PHP + JSON files en Hostinger)

**Fecha:** 2026-05-02
**Autor:** Owner + síntesis del diseño iterado en chat
**Status:** propuesta → aprobada (en implementación)

---

## 1. Qué estamos decidiendo

Construir un sistema de registro de presupuestos que viva paralelo a la calculadora, tal que:

- La calc sigue funcionando idéntica al baseline v1.3 si el registry se desconecta.
- Cada presupuesto se persiste como JSON file en filesystem de Hostinger.
- Una página `/presupuestos/` permite listar, buscar, abrir y descargar exports.
- Los clientes recurrentes se agrupan bajo un mismo N° (1 N° = 1 cliente).

El JSON contract canónico está en `03-product/calculadora/json-contract-v1.md`.

---

## 2. Por qué ahora

- Volumen actual: 15-20 presupuestos/mes. Proyección: 30-50/mes.
- Recompra estimada: ~30%. Sin sistema de tracking, esa data se pierde.
- Operativo: equipo necesita encontrar presupuestos viejos rápido, sin scrollear conversaciones de WhatsApp.
- Owner pidió explícitamente "agilización de cotizaciones, encontrar presupuestos por clientes rápido, ver qué se cotizó".
- Stack actual (PHP + Hostinger + auth HMAC) ya soporta el patrón sin agregar infraestructura.

---

## 3. Investigación previa (4 fuentes)

### Datos propios

- Stack actual de la calc: PHP 8.x, HMAC cookie auth, filesystem writes en `dolar_cache.json` y `.auth_attempts.json` ya funcionan.
- `flock()` disponible para serialización de escrituras concurrentes.
- Hostinger plan permite cron jobs y storage >100GB.
- Auth gate ya validado en `auth_check.php` — reutilizable.

### Mercado

- Marmolerías referentes (Baremes, De Stefano) tienen sistemas internos no expuestos públicamente — no podemos copiar pero confirma que el patrón es estándar.
- Tools de presupuesto SaaS (HoneyBook, Quotient): mismas capacidades pero con lock-in y cost mensual. Reinventarlo en propia infra es razonable a este volumen.

### Benchmarks de ganadores

- Rubros adyacentes (estudios de arquitectura, dentistas con planes de tratamiento, talleres mecánicos): file-based JSON registries son ubicuos en pequeñas operaciones por simplicidad y debuggeabilidad.
- Patrón "store the JSON, search later" supera a DB cuando el volumen es <2000 records y el equipo es <5 personas.

### Validación científica

- DDD/CQRS principles: separation of concerns entre la "command tool" (calc) y el "query/management tool" (registry page) reduce complejidad.
- Single Responsibility: la calc cotiza, el registry persiste. Decoupling absoluto evita acoplamiento dañino.

---

## 4. Knowns / Known unknowns / Unknown unknowns

### Knowns

- File-based PHP escala perfecto a <500 records (proyección 5+ años).
- `flock()` en Hostinger shared funciona para concurrencia low-volume.
- Auth HMAC reusable.
- El JSON contract está lockeado y cubre todos los campos necesarios.
- Calc baseline v1.3 funciona end-to-end.
- Equipo ya entrenado en deploy via PowerShell + Receta 2.

### Known unknowns

- ¿Cuánto pesa concretamente cada cliente.json con 4 sub-versiones? Estimado ~50-100 KB. Confirmar con primer caso real.
- ¿La página de presupuestos performance OK con 500+ clientes? Estimado <1s por scan dir; si crece >2000, hay que pre-computar índice.
- ¿El cron de Hostinger funciona como esperado? Nunca lo testeamos. Plan B: trigger lazy (en cada request al server).
- ¿La extracción de exports a `exports.js` rompe algo invisible? Tests manuales post-refactor obligatorios.

### Unknown unknowns

- ¿Hostinger cambia política de storage o cron en el futuro?
- ¿PHP version upgrade rompe `flock()` u otra primitiva?
- ¿El equipo cambia de proceso comercial y necesita campos no contemplados en el JSON contract?
- ¿La realidad operativa pide estados nuevos no previstos?

---

## 5. Decisión

> **Implementar el registry según la spec de `json-contract-v1.md`. Stack: PHP + filesystem JSON files. Hostinger shared. Sin DB. Cron para light-archive post-instalado +7 días.**

### Stack tecnológico

| Componente | Tecnología | Justificación |
|---|---|---|
| Storage | Filesystem JSON files en `data-blackstones/` (fuera de `public_html/`) | Simplicidad, no-DB, debuggeable, backup = `cp -r` |
| API | PHP 8.x con endpoints en `calculadora/api/` | Reusa stack existente |
| Auth | HMAC cookie (mismo patrón que `auth_check.php`) | Cero cambios de UX para el equipo |
| Concurrencia | `flock()` per-archivo | Suficiente para <5 escrituras simultáneas |
| Cron | Hostinger native cron (nightly) | Sin polling, sin cargas |
| Página de presupuestos | PHP server-side rendering + vanilla JS | Coherente con la calc, sin frameworks |
| Exports compartidos | `calculadora/exports.js` (extraído de calc.html) | Re-uso entre calc y página de presupuestos |

### Reverse-compatibility

Si mañana el dueño quiere remover el registry:
1. Borrar `calculadora/api/`.
2. Borrar `presupuestos/`.
3. Borrar `data-blackstones/` (con backup previo si querés).
4. **La calc sigue idéntica al baseline v1.3.**

Esto es ley de diseño no negociable.

---

## 6. Hipótesis testeable

> **"El registry de presupuestos como JSON files en Hostinger soporta el volumen proyectado (30-50/mes, 30% recompra) sin degradar performance de la calc, sin acoplar lógica, y reduce el tiempo de búsqueda de un presupuesto histórico de minutos (scrollear WhatsApp) a segundos (filtrar tabla)."**

### Métrica de éxito

- **Tiempo medio de búsqueda** de un presupuesto histórico: <30 segundos desde "necesito el de Carolina del baño" hasta "lo tengo abierto en pantalla".
- **% de presupuestos que se guardan al registry** después de hacer la cotización: >70% (target real, los borradores muy crudos pueden quedar fuera).
- **Tasa de error 5xx** del save endpoint: <0.5%.
- **Performance de la página `/presupuestos/`** con primeros 100 clientes: load time <1.5 segundos.
- **Cero degradación de la calc** medida como tiempo de carga + tiempo de export.

### Métrica de "abortar"

- Si el cron de retención falla 3 veces consecutivas y empieza a inflar el storage.
- Si `flock()` no resuelve concurrencia y aparecen sub-versiones duplicadas o saltadas.
- Si la página de presupuestos toma >10 segundos en cargar con 200 clientes (señal de que necesitamos índice o migrar a SQLite antes de lo previsto).
- Si el equipo deja de usar el registry porque "es más rápido buscar en WhatsApp" — significa que no resolvió el dolor.

---

## 7. Plan de ejecución (fases)

### Fase 0 — Documentación lockeada ✅
- [x] JSON contract v1 (`03-product/calculadora/json-contract-v1.md`).
- [x] Decision-log (este archivo).
- [x] Backup pre-registry (commit `cdcab12`, tag `baseline-pre-registry-2026-05-02`).

### Fase 1 — Backend PHP
- [ ] `calculadora/api/auth-shared.php` — wrapper que reusa `auth_check.php` y rechaza si no autenticado.
- [ ] `calculadora/api/next-nro.php` — devuelve siguiente N° libre.
- [ ] `calculadora/api/save.php` — POST con flock. Maneja `mode: new_client` y `mode: append_to`.
- [ ] `calculadora/api/load.php` — GET por `nro` o `nro+sub`.
- [ ] `calculadora/api/list.php` — GET paginado/filtrable.
- [ ] `calculadora/api/state.php` — POST cambio de estado, valida transición.
- [ ] Crear directorio `data-blackstones/clientes/` en Hostinger via FTP MKD.
- [ ] `.htaccess` que bloquee acceso público a `api/` excepto via auth.
- [ ] Test cases (§ 11 del contract): smoke manual con 12 escenarios.

### Fase 2 — Refactor exports
- [ ] Extraer `generarExcel` y `generarPDF` de `calc.html` a `calculadora/exports.js`.
- [ ] Refactorizar para que reciban `data` parameter (no leer DOM directo).
- [ ] Actualizar calc.html para invocar nuevo signature: `generarExcel(getCurrentState())`.
- [ ] Test: regression smoke completo, exports byte-equivalentes a baseline v1.3.

### Fase 3 — Calc gana botón + lógica
- [ ] Botón "Guardar presupuesto" junto a Excel/PDF.
- [ ] Auto-incremento del N° al cargar (call a `next-nro.php` con try/catch fallback).
- [ ] onBlur del input N°: si existe → autocompletar cliente.
- [ ] Soporte para `?cliente=X` y `?load=X-Y` query params al cargar.
- [ ] Tracking interno de `mode` (new_client vs append).
- [ ] Toast post-guardado con N° asignado real (incluye warning si hubo conflict).
- [ ] Bake-in de "registry-down → calc funciona normal" — test manual con `api/` deshabilitado.

### Fase 4 — Página `/presupuestos/`
- [ ] `presupuestos/index.php` con auth gate.
- [ ] `presupuestos/.htaccess` (mismo patrón que calc).
- [ ] UI: tabla principal con búsqueda (cliente nombre/cel/N°/fecha).
- [ ] Vista por cliente: sub-versiones expandibles con `[↻ Cargar]`, `[⬇ Excel]`, `[⬇ PDF]`.
- [ ] Cambio de estado inline (dropdown por sub-versión que llama `state.php`).
- [ ] Filtros por estado, fecha desde/hasta, material.

### Fase 5 — Cron + Test + Deploy
- [ ] `cron/archive-installed.php` con lógica del § 5.1 del contract.
- [ ] Configurar cron en Hostinger panel (nightly 03:00).
- [ ] Test cases completos (§ 11 del contract: 12 escenarios).
- [ ] Deploy via Receta 2 (multi-patch en calc.html) + Receta 1 (Base64 inline para nuevos archivos PHP/JS).
- [ ] Bump baseline a v2.0.
- [ ] Update `04-operations/deploy-snippets.md` con cualquier patrón nuevo aprendido.

---

## 8. Riesgos y mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|
| Refactor de exports rompe la calc | Media | Alto | Backup pre-registry (ya creado). Regression smoke obligatorio antes de deploy. Si rompe, restore desde backup. |
| `flock()` race condition deja sub-versiones duplicadas | Baja | Medio | Test concurrencia con 2 navegadores simultáneos. Si aparece, agregar lockfile externo. |
| Cron de Hostinger no corre como esperado | Media | Bajo | Plan B: trigger lazy archiving en `load.php` cuando un cliente está siendo abierto. |
| Página de presupuestos carga lento con muchos clientes | Baja-Media (a futuro) | Medio | Pre-computar `_index.json` con metadata cuando crezcamos a >500 clientes. |
| El equipo no adopta el registry y vuelve a WhatsApp | Media | Alto | UX simple, sin fricciones. Botón "Guardar" único click. Onboarding de 5 min al equipo. |
| Datos sensibles (DNI, presupuestos) expuestos por bug de auth | Baja | Crítico | `data-blackstones/` fuera de `public_html/`. `.htaccess` defensivo. Auth check en cada endpoint. Pen test manual antes de "ir vivo". |
| Hostinger cambia política de storage/cron | Baja | Medio | Migración a otro hosting documentada (no urgente, plan futuro). |

---

## 9. Estimación de esfuerzo

| Fase | Horas |
|---|---|
| Fase 0 - Docs | 4 (✅ done) |
| Fase 1 - Backend | 7-9 |
| Fase 2 - Refactor exports | 3-4 |
| Fase 3 - Calc UI | 4-5 |
| Fase 4 - Página presupuestos | 6-8 |
| Fase 5 - Cron + Test + Deploy | 4-5 |
| **Total** | **~28-35 horas** |

Spread real estimado: 4-6 días calendario laburando concentrado, o 2-3 semanas en sesiones cortas.

---

## 10. Retro (a completar después de la ventana de evaluación)

> Completar 30 días después del go-live: 2026-XX-XX.

### Qué pasó
[pendiente]

### Métrica vs hipótesis
- Tiempo medio de búsqueda histórico: hipótesis <30s. Realidad: ?
- % presupuestos guardados al registry: hipótesis >70%. Realidad: ?
- Performance página de presupuestos: hipótesis <1.5s con 100 clientes. Realidad: ?
- Cero degradación calc: ?

### Qué aprendimos
[pendiente]

### Acción siguiente
- [ ] Si la hipótesis se sostiene → documentar éxito + proceder a Fase 6 (analytics de conversión).
- [ ] Si fallan métricas operativas → optimizar (índice precomputado, cache, etc.).
- [ ] Si fallan métricas de adopción → revisar UX del registry, conversación con equipo.
- [ ] Si fallan métricas de seguridad → freeze, fix, post-mortem.

---

## 11. Decisiones derivadas (pendientes futuras)

Una vez que el registry esté vivo, abren puertas para:

- **Analytics de conversión**: tasa borrador → enviado → aprobado → instalado. Tiempo medio entre estados. Cuáles materiales convierten más.
- **Cliente recurrente flag**: si un cliente tiene >=2 instalados, mostrar `★` en la página y priorizar respuestas.
- **Predicción de recompra**: clientes instalados hace 6-12 meses son target de "¿qué tal el baño?".
- **Pre-cotización con datos del cliente histórico**: la calc autocompleta el material/precio "que el cliente prefirió la última vez".
- **Versión pública (read-only) del registry para arquitectos partners**: que vean sus propias obras.

Ninguna de estas es alcance de v1. Quedan en pipeline post-launch.
