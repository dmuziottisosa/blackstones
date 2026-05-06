# Backlog · BlackStones

> Items pendientes priorizados por leverage. Se agregan acá apenas se identifican.
> Cuando un item se completa: mover al final con tag `~~done~~` y fecha, o eliminar si ya no aplica.

---

## 🔴 Crítico — chequear antes de producción real

- [ ] **Cron jobs activos en panel Hostinger**
  - `cleanup-retention.php` → 03:00 ART diario
  - `monthly-report.php` → 02:00 ART diario
  - Sin esto: nada se limpia y los reportes mensuales nunca se generan
- [ ] **Backup automático nocturno** de `bs-data/` → Google Drive / Dropbox / S3. Sin esto, un problema de Hostinger = todo perdido
- [ ] **Health check + uptime monitoring** (`api/health.php` + UptimeRobot)

## 🟡 Mejoras de operación / conversión

- [ ] **Origen del presupuesto: switch publicidad / local** *(2026-05-06)*
  - Campo nuevo en estado del presupuesto que distinga si el lead vino de Meta Ads / orgánico vs walk-in / referido
  - UI: switch o dropdown en la calc al crear, persistido en `clientes/{nro}.json`
  - Reportería: separa conversion rate por origen → sabés qué canal cierra más
  - Considerar valores: `publicidad` (Meta), `local` (walk-in), `referido`, `recurrente` (cliente que volvió)
- [ ] **Pestaña Cronograma** *(2026-05-06)*
  - 4ta tab cross-surface: Calculadora · Activos · Reporte · **Cronograma**
  - Vista calendar/timeline de fechas comprometidas de entrega
  - Cada presupuesto aprobado/entregado tiene fecha de instalación → mostrarlas en calendario
  - Detectar saturación de semana (ej: 5 entregas en 1 semana = warning)
  - Posible sync con Google Calendar
- [ ] **WhatsApp directo desde el hub** — botón "Enviar por WhatsApp" → `wa.me/${cel}?text=...`. Reduce 5 clicks a 1
- [ ] **Cliente recurrente / VIP detection** — banner cuando DNI/cel ya existe en `clientes-index.json` con resumen de entregas previas
- [ ] **Pipeline Kanban view** — alternativa a la tabla activos: columnas por estado, drag entre columnas = transición

## 🟢 Mejoras menores / quick wins

- [ ] **Concepto auto-suggest** — datalist con conceptos históricos comunes
- [ ] **Reportería con gráficos** (Chart.js): conversion rate por mes, tiempo borrador→entregado, top materiales en barras, distribución montos
- [ ] **Mobile-first hub** — vista cards en lugar de tabla con scroll horizontal
- [ ] **Search por rango de fechas** en activos
- [ ] **Comparador de sub-versiones** lado a lado
- [ ] **Audit log liviano** — quién cambió qué cuándo
- [ ] **Backfill `materiales[]` en `clientes-index.json`** — actualmente solo nuevas entregas lo tienen

---

## 🛡️ Hardening / Code review (deep review 2026-05-06)

### Seguridad
- [ ] **🔴 IP spoofing en rate limiting** — `auth_check.php:100-103` confía ciegamente en `X-Forwarded-For`. Atacante puede enviar header diferente cada intento → bypass del bloqueo después de N fallidos. Verificar si Hostinger setea proxy confiable; si no → usar solo `REMOTE_ADDR`. **Severidad alta** (afecta el único mecanismo de defensa contra brute-force del password)
- [ ] **🟡 CSRF tokens en POST endpoints** — `save.php`, `state.php`, `delete.php`, `edit-concepto.php` aceptan POSTs sin validar token. SameSite=Lax mitiga 90% pero no es defense-in-depth. Generar token al login, validar en cada mutación
- [ ] **🟡 Timestamp malleability en transiciones** — `state.php:73,86,90` usa `date('c')` server-side ✓ (verificado OK), pero asegurarse que ningún endpoint acepta `at` del body para historial
- [ ] **🟢 Content-Disposition con filenames acentuados** — `download-zip.php:34` trunca nombres latinos (regex `[a-zA-Z0-9_-]`). Usar RFC 5987 `filename*=UTF-8''...` para preservar tildes/ñ

### Data integrity
- [ ] **🟡 JSON corrupto = pérdida silenciosa** — `_config.php:44` `bs_read_json()` retorna `null` tanto si el archivo no existe como si está corrupto. Si el server falla mid-write y se trunca el JSON, la próxima lectura retorna null y el siguiente write lo sobreescribe con datos parciales → pérdida total del cliente. **Fix**: distinguir error vs not-found, escribir `.bak` antes de cada write atómico
- [ ] **🟡 Integer overflow al cliente 9999** — `next-nro.php:37` no tiene cap. Cliente 10000 rompe el regex `^\d{4}\.json$` y la lógica de listado. Probable que tarde años en pasar pero un guard de 1 línea lo previene: `if ($next > 9999) bs_error('Límite alcanzado')`

### Performance
- [ ] **🟡 N+1 en `list.php`** — recorre TODOS los clientes y TODAS sus cotizaciones cada request. Con 500 clientes × 100 cot c/u = 50k items en RAM por request del hub. Pre-indexar entradas activas en `bs-data/registro/activos-index.json` (sincronizado en `save.php` y cron) para search lineal rápido

### Code smell / mantenimiento
- [ ] **🟢 TODO: ZIP automático en `state.php:21`** — comentario dice "TODO Fase 2" pero `_zip-gen.php` ya existe y se invoca. Limpiar el TODO obsoleto
- [ ] **🟢 TODO: Excel descargable en `monthly-report.php:18`** — Fase 2 mencionada nunca implementada. Decidir: ¿se necesita? Si no, eliminar el comment

---

## Done

- 2026-05-04 ~~Reset de contador de presupuestos a 0001~~ (`reset-once.php` one-shot)
- 2026-05-04 ~~Saneamiento visual hub nivel excelencia: fechas `4 - may - 26`, currency tabular, capitalize nombres, muted empties~~
- 2026-05-04 ~~Botón Excel per-presupuesto + autoxlsx en calc~~
- 2026-05-04 ~~Toasts modernos reemplazan alert()~~
- 2026-05-04 ~~CSV resumen de tabla activos~~
- 2026-05-04 ~~Concepto editable inline + endpoint `edit-concepto.php`~~
- 2026-05-04 ~~Materiales agregados a `clientes-index.json`~~
- 2026-05-04 ~~Cross-tabs unificadas calc + hub con header idéntico~~
- 2026-05-04 ~~Save validations (nro + nombre + celular obligatorios)~~
- 2026-05-04 ~~Botón "+ Nueva cotización" mismo cliente~~
- 2026-05-04 ~~Color DB cleanup (Black Cosmic, Negro Boreal split, Negro Absoluto, etc.)~~
