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

Todos los hallazgos del review fueron resueltos en commit `<commit-hash>` del 2026-05-06.
Ver sección Done abajo. Pendiente solo:

- [ ] **🟡 N+1 en `list.php`** (mitigado parcialmente con file-sort DESC, falta el index real) — pre-indexar entradas activas en `bs-data/registro/activos-index.json` sincronizado en `save.php` + cron. Esfuerzo medio, postpone hasta tener >100 cotizaciones activas reales

---

## Done

- 2026-05-06 ~~🔴 Fix IP spoofing rate limiting~~ — `auth_get_ip()` solo usa `REMOTE_ADDR`
- 2026-05-06 ~~🟡 CSRF protection via Origin/Referer check~~ — `bs_check_csrf()` agregado a save / state / delete / edit-concepto / mark-viewed
- 2026-05-06 ~~🟡 JSON corruption protection~~ — `bs_write_json_atomic` ahora hace `.bak` antes de pisar; `bs_read_json` falla con auto-restore desde `.bak` si JSON corrupto
- 2026-05-06 ~~🟡 Integer overflow guard~~ — `next-nro.php` aborta con 507 si `> 9999`
- 2026-05-06 ~~🟢 Content-Disposition UTF-8 (RFC 5987)~~ — `download-zip.php` con `filename*=UTF-8''...` preserva tildes/ñ
- 2026-05-06 ~~🟢 TODOs obsoletos limpiados~~ — `state.php` (ZIP ya implementado) y `monthly-report.php` (Excel descartado)
- 2026-05-06 ~~Health check endpoint~~ — `api/health.php` público (sin auth) para UptimeRobot. Verifica fs writable, cron reciente, espacio disco
- 2026-05-06 ~~list.php sort optimization~~ — files DESC para FS cache locality (full N+1 fix postpuesto, ver pendientes arriba)
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
