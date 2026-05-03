# JSON Contract — Registry de Presupuestos v1

> **Status:** spec lockeada · 2026-05-02
>
> Define la estructura de datos canónica de cada cliente en el registry. Cualquier cambio breaking requiere bump de `version` y plan de migración.
>
> **Principio rector:** el registry es **100% aditivo** sobre la calculadora. **Si mañana se desconectan los endpoints PHP del registry, la calc sigue funcionando idéntico al día de hoy.** Esa es ley de diseño no negociable.

---

## 1. Filosofía de diseño

### 1.1 Decoupling absoluto entre calc y registry

La calc **no depende** del registry para funcionar. Si los endpoints `/api/save.php`, `/api/next-nro.php`, `/api/load.php`, `/api/list.php` no responden o no existen:

- ❌ El botón "Guardar presupuesto" muestra error toast pero NO rompe nada del estado de la calc.
- ❌ El auto-incremento del N° al cargar **falla en silencio** y deja el input vacío (comportamiento actual).
- ❌ La detección de cliente existente onBlur **falla en silencio**, equipo tipea manualmente.
- ❌ Los query params `?cliente=X` y `?load=X-Y` se ignoran si los endpoints no responden — la calc arranca limpia.

Implementación: cada fetch del registry envuelto en `try/catch` con fallback al comportamiento legacy. Cero excepciones que rompan UX.

### 1.2 Edición = nueva sub-versión, nunca pisar

Toda modificación de un presupuesto guardado crea una sub-versión nueva bajo el mismo cliente. Preserva historia. Sin "deshacer cambios" mágico.

### 1.3 Server tiene la última palabra en N°

La calc **propone** un N° (auto-incremento). El server, con flock, **asigna** el definitivo al guardar. Si dos clientes intentan el mismo N° simultáneamente, el segundo recibe el siguiente libre y el frontend lo informa.

---

## 2. Estructura del archivo cliente

**Path:** `bs-data/clientes/{cliente_nro}.json` (fuera de `public_html/`).

**Ejemplo completo:**

```json
{
  "version": "1.0",
  "cliente_nro": "0042",
  "cliente": {
    "nombre": "Carolina Perez",
    "dni": "12345678",
    "celular": "+5491123456789",
    "direccion": "Av. Cabildo 1234, CABA",
    "email": "carolina@ejemplo.com"
  },
  "primera_cotizacion": "2026-03-15T10:00:00-03:00",
  "ultima_actualizacion": "2026-05-02T14:30:00-03:00",
  "cotizaciones": [
    {
      "sub": 1,
      "fecha": "2026-03-15T10:00:00-03:00",
      "concepto": "cocina principal",
      "estado": "entregado",
      "entregado_at": "2026-04-10T16:00:00-03:00",
      "transiciones": [
        { "estado": "borrador", "at": "2026-03-15T10:00:00-03:00" },
        { "estado": "enviado", "at": "2026-03-15T11:30:00-03:00" },
        { "estado": "aprobado", "at": "2026-03-18T09:00:00-03:00" },
        { "estado": "entregado", "at": "2026-04-10T16:00:00-03:00" }
      ],
      "presupuesto": { "...": "full data — persiste 60 días desde entregado_at" },
      "zip_path": "bs-data/zips/0042-1.zip"
    },
    {
      "sub": 5,
      "fecha": "2026-05-02T14:30:00-03:00",
      "concepto": "baño nuevo",
      "estado": "borrador",
      "transiciones": [
        { "estado": "borrador", "at": "2026-05-02T14:30:00-03:00" }
      ],
      "presupuesto": { "...": "full data" },
      "zip_path": null
    }
  ]
}
```

---

## 3. Estructura del campo `presupuesto`

Mientras la cotización vive (no fue borrada por retención), este campo contiene **todo lo necesario para rehidratar el estado completo de la calc**:

```json
{
  "secciones": {
    "m": { "qty": 1, "items": [ /* ver § 3.1 */ ] },
    "a": { "qty": 1, "items": [ ... ] },
    "l": { "qty": 1, "items": [ ... ] },
    "i": { "qty": 1, "items": [ ... ] },
    "b": { "qty": 1, "items": [ ... ] }
  },
  "zocalos": {
    "qty": 1,
    "items": [ { "color": "...", "mat": "...", "d1": 0, "d2": 0, "price": 0, "mon": "USD" } ]
  },
  "extras": [ { "name": "...", "amount": 0, "mon": "USD" } ],
  "variantes": [ { "color": "...", "mat": "...", "price": 0, "mon": "USD", "selection": {}, "regMode": "L+A", "regManualVal": 5 } ],
  "bachas": [ { "code": "...", "desc": "...", "cat": "...", "qty": 1, "price": 0 } ],
  "adicionales": {
    "flete": { "zona": "GBA Norte", "monto": 0 },
    "escalera": { "cant": 0, "monto": 0, "mon": "USD" },
    "angulos": { "cant": 0, "monto": 0, "mon": "USD" }
  },
  "factura": false,
  "dolar_venta": 1415,
  "totales": {
    "usd": 800.00,
    "ars": 0,
    "iva_usd": 0,
    "iva_ars": 0,
    "total_en_ars": 0
  },
  "exports_options": {
    "incTotal": true,
    "incSena": false,
    "incSaldo": false,
    "incTotalARS": false
  }
}
```

### 3.1 Item de sección (Mesada / Alzada / L / Isla / Baño)

Match exacto con la estructura interna `D[secKey].items[i]` de la calc:

```json
{
  "mat": "Granito_i",
  "color": "Blanco Dallas",
  "d1": 2.7,
  "d2": 0.65,
  "d3": 0,
  "d4": 0,
  "price": 450,
  "mon": "USD",
  "reg": "L+A",
  "rv": 5,
  "ag": "Ambos",
  "agPrice": 0,
  "nLat": 1,
  "caraInt": "No",
  "altoLat": 0.90,
  "tipo": "Bacha armada",
  "tag": "Cocina"
}
```

Campos obligatorios para todos los tipos de sección: `mat`, `color`, `d1`, `d2`, `price`, `mon`, `reg`, `rv`, `ag`.

Campos específicos:
- **Isla (i):** `nLat`, `caraInt`, `altoLat`, `d4`.
- **L (l):** `d3`, `d4` (si 2 piezas).
- **Baño (b):** `tipo`, `d3`, `d4` (si Bacha armada).
- **Alzada (a):** `agPrice` (precio manual de agujeros).

---

## 4. Estados — máquina de estados

### 4.1 Diagrama

```
                    ┌─────────────┐
                    │  borrador   │  (default al crear)
                    └──────┬──────┘
                           │
                    ┌──────┴──────┐
                    ▼             ▼
              ┌──────────┐  ┌──────────┐
              │ enviado  │  │ perdido  │
              └────┬─────┘  └──────────┘
                   │
            ┌──────┴──────┐
            ▼             ▼
      ┌──────────┐  ┌──────────┐
      │ aprobado │  │ perdido  │
      └────┬─────┘  └──────────┘
           │
           ▼
     ┌────────────┐
     │ entregado  │ ─► dispara borrado de hermanas (no entregadas) +
     └────────────┘    persiste 60 días desde entregado_at +
                       a los 60 días → BORRADO COMPLETO del archivo
```

### 4.2 Transiciones permitidas

| Desde | A | Trigger |
|---|---|---|
| borrador | enviado | **Manual** desde página de presupuestos. |
| borrador | perdido | **Manual**. |
| enviado | aprobado | **Manual**. |
| enviado | perdido | **Manual**. |
| aprobado | entregado | **Manual**. Dispara aviso UI + **borrado inmediato de hermanas no entregadas** (ver § 4.4). |
| entregado | (borrado) | Automático por cron, +60 días después de `entregado_at`. NO hay light-archive intermedio — eliminación completa. |

**Todas las transiciones de estado son manuales.** Sin auto-transiciones por exportación ni por otros disparadores. El equipo decide explícitamente cada cambio desde la página de presupuestos.

**Sin transiciones inversas.** Si el cliente "vuelve" después de perder, se crea sub-versión nueva con estado `borrador`, no se reabre la perdida.

### 4.3 Campo `transiciones[]`

Array append-only que registra cada cambio de estado con timestamp. Permite analytics futuros (ej: tiempo medio enviado→aprobado).

### 4.4 Aviso UI al pasar a ENTREGADO

Cuando el equipo cambia el estado de una cotización a `entregado` desde la página de presupuestos, el frontend muestra un modal/toast informativo:

> **"Si marcás como entregado:**
> **• Las otras sub-versiones del cliente (borradores, enviados, aprobados, perdidos) se eliminan ahora.**
> **• Esta cotización entregada se va a guardar 60 días y después se borra automáticamente.**
> **• Si querés guardarla más tiempo, descargá el ZIP ahora."**
>
> `[⬇ Descargar ZIP]` `[Confirmar entrega]` `[Cancelar]`

**Comportamiento:**
- El aviso es **bloqueante** (modal, requiere confirmación) porque el cleanup de hermanas es destructivo.
- Si confirma → server marca como entregado + borra hermanas + genera ZIP + arranca contador de retención.
- Si descarga ZIP antes → el blob se construye en cliente con `generarExcel`/`generarPDF` + datos del cliente y se baja directo. No requiere ir al server.

**No se gestiona contabilidad en v1.** El equipo decide qué hacer con el Excel descargado (mandarlo a la contadora, guardarlo en Drive, archivarlo local). La carpeta `bs-data/contabilidad/` **no existe** en este modelo.

---

## 5. Política de retención y cleanup

### 5.1 Estructura del filesystem

```
bs-data/
├── clientes/
│   └── {nro}.json                ← ephemeral · 60 días
├── zips/
│   └── {nro}-{sub}.zip           ← ephemeral · 60 días desde creación
└── reportes/
    ├── 2026-05.json              ← permanente · data estructurada
    ├── 2026-05.xlsx              ← permanente · Excel descargable
    ├── 2026-05.viewed            ← flag (touch file, vacío)
    └── ...
```

### 5.2 Reglas de cleanup

**Trigger inmediato — al marcar ENTREGADO sobre una cotización:**

- Todas las otras sub-versiones del mismo cliente.json en estados `borrador`, `enviado`, `aprobado`, `perdido` → **borradas inmediatamente del array**.
- La cotización marcada como entregado:
  - Persiste con full detail durante 60 días desde `entregado_at`.
  - Server genera automáticamente el ZIP (`bs-data/zips/{nro}-{sub}.zip`) con Excel + PDF + datos del cliente.
- **Múltiples entregados conviven**: marcar un nuevo entregado NO borra entregados previos del mismo cliente.

**Trigger periódico — cron `cleanup-retention.php` (nightly, 03:00 ART):**

```
Para cada bs-data/clientes/{nro}.json:
  Para cada cotización del array:
    Si estado == "entregado" Y (now - entregado_at) > 60 días:
      Borrar la cotización del array
    Si estado != "entregado" Y NO existe ningún entregado activo en este cliente
       Y (now - última_modificación) > 60 días:
      Borrar la cotización del array
  Si quedaron 0 cotizaciones en el array:
    Borrar el cliente.json entero del filesystem

Para cada bs-data/zips/{archivo}.zip:
  Si (now - file_mtime) > 60 días:
    Borrar el archivo
```

**Importante:** sin un entregado activo, los borradores/enviados/aprobados/perdidos se cuentan desde su última modificación, no desde el momento del primer entregado. Es decir: un cliente que solo tuvo perdidos se borra completamente a los 60 días desde la última actividad.

**Manual — botón "Eliminar presupuesto":**

- Disponible en la página de presupuestos por cada cotización individual.
- Borra esa cotización del array sin esperar retención.
- Si queda el cliente.json vacío, también se borra el archivo entero.
- Acción confirmada con modal.

### 5.3 Storage proyectado

| Componente | Crecimiento real |
|---|---|
| `clientes/` (rolling, ~50 cotizaciones activas en pico) | ~3 MB constante |
| `zips/` (rolling, ~50 zips activos) | ~2.5 MB constante |
| `reportes/` (5 años acumulados) | ~2 MB total |
| **Total disco usado en cualquier momento** | **<10 MB** |

Hostinger Premium da 100+ GB. Storage **matemáticamente nunca es un problema**.

---

## 6. Registro: dashboard histórico de entregados

> **Tab dentro de `/presupuestos/`. NO es una página separada.** La página `/presupuestos/` tiene 2 vistas:
> - **Activos:** cotizaciones vivas (borrador / enviado / aprobado / perdido pendiente).
> - **Registro:** dashboard histórico de entregados.

### 6.1 Filosofía: archivo canónico cuando existe, live scan cuando no

```
Para cada mes que el dashboard solicita:
  Si bs-data/registro/by-month/{YYYY-MM}.json existe:
    Usar ese archivo (canónico, generado por el cron al cierre del mes).
  Sino:
    Scan en vivo de bs-data/clientes/*.json
    Filtrar cotizaciones con estado=entregado y entregado_at en el mes objetivo
    Calcular totales sobre la marcha.
```

**Casos posibles:**

| Caso | Comportamiento |
|---|---|
| Mes actual (en curso) | Siempre live scan (cron aún no corrió, archivo no existe) |
| Mes pasado, cron OK | Usa archivo (lectura instantánea) |
| Mes pasado, cron falló pero entregados aún en retención (<60 días) | Live scan funciona. Botón manual "Regenerar reporte" persiste. |
| Mes pasado >60 días + cron falló + entregados borrados | **Data perdida.** Único caso problemático.

### 6.2 Cron idempotente del registro mensual

`monthly-report.php` corre **diariamente a las 02:00 ART** (no solo día 1):

```
1. Para cada mes en los últimos 3 meses cerrados:
   Si bs-data/registro/by-month/{YYYY-MM}.json NO existe:
     Detectar entregados con entregado_at en ese mes desde bs-data/clientes/
     Si hay datos:
       - Sumar totales (USD, ARS, m²)
       - Top materiales del mes
       - Lista de entregados con concepto + cliente
       - Guardar by-month/{YYYY-MM}.json
       - Generar by-month/{YYYY-MM}.xlsx
     Si no hay datos (entregados ya fueron borrados por retención):
       - Loggear en _cron-log.txt: "mes {YYYY-MM}: no hay datos, no se generó reporte"
2. Append a _cron-log.txt con timestamp + resumen de la corrida.
```

**Idempotencia:** la corrida diaria se autocorrige. Si el día 1 falla por algo, el día 2 detecta el reporte faltante y lo genera.

**Margen de seguridad:** ~25 días (60 días de retención menos los 5 días ya pasados desde fin de mes en el peor caso) para que el cron logre auto-corregirse antes de perder datos.

### 6.2 Estructura del JSON del reporte

`bs-data/registro/2026-05.json`:

```json
{
  "version": "1.0",
  "mes": "2026-05",
  "generado_at": "2026-06-01T02:00:00-03:00",
  "generado_por": "cron-auto",
  "totales": {
    "entregados_count": 23,
    "clientes_unicos": 19,
    "monto_usd": 47300.00,
    "monto_ars": 0,
    "m2_total": 145.6,
    "ticket_promedio_usd": 2056.52,
    "ticket_promedio_m2": 6.33
  },
  "materiales": [
    { "material": "Granito Importado", "color": "Blanco Dallas", "count": 4, "m2": 22.3, "monto_usd": 9800 },
    { "material": "Silestone", "color": "Calacatta Gold", "count": 3, "m2": 18.5, "monto_usd": 12200 },
    ...
  ],
  "entregados": [
    {
      "cliente_nro": "0042",
      "sub": 1,
      "cliente_nombre": "Carolina Perez",
      "fecha_entregado": "2026-05-15",
      "concepto": "cocina principal",
      "monto_usd": 2100,
      "m2": 5.6,
      "materiales": ["Granito Importado · Blanco Dallas"]
    },
    ...
  ]
}
```

### 6.3 UI: Página `/presupuestos/reportes/`

**Vista principal:**

- Banner discreto arriba si hay reporte sin "ver" (`{mes}.viewed` no existe): *"📊 Reporte de {mes} listo · [Ver] [Descargar Excel]"*
- Al click en Ver / Descargar: server crea `{mes}.viewed` (touch file vacío). Banner desaparece para siempre en ese mes.
- Tabla de meses con totales (USD, ARS, m², count entregados). Click en una fila → expande inline detalle:
  - Lista de entregados del mes
  - Materiales únicos con subtotales
  - Stats agregados (ticket promedio, top materiales)
- Cada fila tiene botón `[↓ Excel]` para descargar el `.xlsx` permanente.
- Botón "Marcar como no visto" → borra `{mes}.viewed`, banner vuelve a aparecer.

**Sección manual:**

- Selector de mes + botón "Generar reporte manual".
- Sobreescribe el JSON+XLSX existente (con confirm si ya había uno).
- Útil para: regenerar tras corrección manual, recuperar tras fallo del cron, generar meses históricos antes de la implementación.

### 6.4 Permanencia

**Los reportes mensuales no tienen retención.** Se mantienen permanentes. Cada uno es ~35 KB (JSON + Excel). 5 años = ~2 MB total.

Son la **memoria contable del negocio** independiente del cleanup de cotizaciones individuales.

---

## 7. Generación del N° de cliente

### 7.1 Algoritmo `next-nro.php`

```
locked_directory_scan(bs-data/clientes/):
  numbers = []
  for archivo in dir:
    si archivo coincide con /^(\d{4,})\.json$/:
      numbers.push(int(match))
  si numbers.empty:
    return "0001"
  return zero_pad(max(numbers) + 1, 4)
```

### 7.2 Modos de save

El payload del POST a `save.php` incluye:

```json
{
  "mode": "new_client" | "append_to",
  "cliente_nro_proposed": "0044",        // si mode=new_client, es propuesta
  "cliente_nro_target": "0042",           // si mode=append_to, es ID definitivo
  "cliente": { ... },
  "presupuesto": { ... }
}
```

### 7.3 Resolución de modos en el server

**Si `mode = "new_client"`:**
```
locked_acquire(bs-data/clientes/)
  si existe clientes/{cliente_nro_proposed}.json:
    asignar nuevo nro = next-nro
  sino:
    asignar nuevo nro = cliente_nro_proposed
  crear archivo clientes/{nro}.json con cotizacion sub=1
locked_release()
return { cliente_nro: nro_asignado, sub: 1 }
```

**Si `mode = "append_to"`:**
```
locked_acquire(bs-data/clientes/{cliente_nro_target}.json)
  si NO existe:
    crear archivo nuevo con ese nro y cotizacion sub=1
  sino:
    leer archivo
    sub = (max sub existente) + 1
    appendar nueva cotizacion
    escribir archivo
locked_release()
return { cliente_nro: cliente_nro_target, sub: sub }
```

### 7.4 Cómo la calc determina el `mode`

| Situación frontend | Mode enviado |
|---|---|
| Calc cargó fresh sin query params, equipo no tocó el N° auto | `new_client` |
| Calc cargó con `?cliente=X` | `append_to` (target = X) |
| Calc cargó con `?load=X-Y` | `append_to` (target = X) |
| Equipo borró el N° auto y tipeó otro número (onBlur fetch confirmó que existe) | `append_to` (target = nro tipeado) |
| Equipo borró el N° auto y tipeó otro (onBlur fetch confirmó que NO existe) | `new_client` (proposed = nro tipeado) |

Tracking interno en la calc:
- Variable `_clienteNroSource = "auto" | "loaded" | "typed_existing" | "typed_new"`.
- Mode derivado de eso al construir el payload.

---

## 8. Endpoints

### 8.1 `GET /api/next-nro.php`

**Auth:** HMAC cookie de la calc.
**Response:**
```json
{ "next": "0044" }
```

### 8.2 `POST /api/save.php`

**Auth:** HMAC cookie.
**Body:** estructura de § 6.2.
**Response (success):**
```json
{
  "ok": true,
  "cliente_nro": "0044",
  "sub": 1,
  "estado": "borrador",
  "warning": null
}
```

Si hubo conflict de N°:
```json
{
  "ok": true,
  "cliente_nro": "0045",
  "sub": 1,
  "estado": "borrador",
  "warning": "El N° 0044 fue tomado por otro miembro del equipo. Tu presupuesto se guardó como 0045."
}
```

**Response (error):**
```json
{ "ok": false, "error": "mensaje legible" }
```

### 8.3 `GET /api/load.php?nro={X}[&sub={Y}]`

**Auth:** HMAC cookie.
**Comportamiento:**
- Si solo `nro`: devuelve el cliente entero (todas las sub-versiones).
- Si `nro` y `sub`: devuelve solo esa sub-versión + los datos del cliente.

**Response:**
```json
{
  "ok": true,
  "data": { ...cliente.json o sub-versión seleccionada... }
}
```

### 8.4 `GET /api/list.php?[query=string]&[estado=X]&[fecha_desde=YYYY-MM-DD]&[fecha_hasta=...]&[page=1]&[per_page=50]`

**Auth:** HMAC cookie.
**Comportamiento:** lista de clientes + cotizaciones con filtros.

**Response:**
```json
{
  "ok": true,
  "total": 187,
  "page": 1,
  "per_page": 50,
  "results": [
    {
      "cliente_nro": "0042",
      "cliente_nombre": "Carolina Perez",
      "cliente_celular": "+5491123456789",
      "cotizaciones_count": 4,
      "ultima_cotizacion": "2026-05-02T14:30:00-03:00",
      "estados_resumen": { "entregado": 1, "perdido": 1, "enviado": 1, "borrador": 1 }
    }
  ]
}
```

### 8.5 `POST /api/state.php`

**Auth:** HMAC cookie.
**Body:**
```json
{
  "cliente_nro": "0042",
  "sub": 3,
  "nuevo_estado": "aprobado"
}
```
**Response:** `{ "ok": true }` o error.

Server:
- Valida transición permitida (§ 4.2). Si inválida, rechaza.
- Appendea a `transiciones[]`.
- Actualiza `cotizacion.estado`.
- Si `nuevo_estado == "entregado"`, NO archiva inmediatamente. El cron lo hace +10 días.

---

## 9. Versionado del contract

`version: "1.0"` está hardcoded en cada archivo. Si en el futuro se cambian campos:

- **Adición de campos opcionales:** mantiene `1.0`. La calc nueva los lee, la vieja los ignora.
- **Cambio de campo existente o nuevo campo obligatorio:** bump a `1.1`. Migración script obligatoria.
- **Cambio breaking de estructura:** bump a `2.0`. Migración + período de doble lectura (calc lee 1.x y 2.0 mientras dura la migración).

Política: cada cambio de versión documentado en `01-strategy/decision-log/` con razón + plan de migración.

---

## 10. Edge cases documentados

### 10.1 DNI faltante
Cliente sin DNI se acepta. El campo queda `""` en el JSON. La búsqueda en `list.php` por DNI lo ignora.

### 10.2 Cliente con un solo presupuesto perdido y desaparece
El archivo del cliente queda en disco con su único presupuesto perdido. No se borra. La página de presupuestos lo muestra normalmente.

### 10.3 Borrar manualmente un presupuesto
**No implementado en MVP.** Si hay que borrar, el equipo edita el JSON a mano (o usa una utility CLI futura). Razón: forzar trazabilidad, no perder data por accidente.

### 10.4 Calc carga `?load=0042-99` (sub que no existe)
Server `load.php` devuelve error. Calc fallback: ignora el query param, arranca limpia con next nro auto-asignado.

### 10.5 Concurrencia en append_to
Dos equipos agregan sub-versiones al mismo cliente al mismo segundo. Flock serializa. Ambos quedan registrados, sub crecientes consecutivos.

### 10.6 El cron de archive-delivered falla
Idempotente: corre nightly, si una corrida falla, la siguiente toma los pendientes. Sin pérdida de datos.

### 10.7 El registry está abajo (endpoints no responden)
La calc detecta y muestra mensaje "Registry no disponible, podés seguir trabajando localmente y guardar después". El estado en la calc se sigue persistiendo en localStorage como hoy.

---

## 11. Compatibilidad con la calc actual

### 11.1 Lo que se agrega a la calc

Solo dos cosas, sin tocar nada más:

1. **Botón "Guardar presupuesto"** junto a Excel + PDF. Wrapper try/catch + fallback toast.
2. **Lógica de hidratación** al cargar:
   - Si query param `?cliente=X` o `?load=X-Y` → fetch + autocompletar inputs.
   - Si no hay query param → fetch `next-nro.php` y autocompletar input N° (try/catch fallback: input vacío como hoy).

### 11.2 Lo que NO se toca

- El layout de la calc.
- El flow de exports (Excel/PDF) más allá del refactor a `exports.js`.
- La auth.
- Los inputs existentes (cliente, dni, celular, dirección, fecha, N°).
- El cálculo de m² y totales.
- Las variantes y adicionales.

### 11.3 Reverse-compatibility

Si mañana el dueño dice "saquemos el registry":
1. Borrar carpeta `calculadora/api/`.
2. Borrar página `presupuestos/`.
3. Borrar carpeta `bs-data/` (después de exportar a un zip si querés data).
4. La calc sigue funcionando idéntica al baseline v1.3.

El botón "Guardar presupuesto" en la calc queda inerte (siempre falla) — se borra cuando se quiera con un patch single-line.

---

## 12. Test cases para validar el contract

Casos que el sistema tiene que manejar correctamente:

1. **Cliente nuevo con todos los campos.** Save → recibe `0044-1`.
2. **Cliente nuevo sin DNI.** Save acepta, DNI queda `""`.
3. **Cliente que vuelve.** Page → click → calc abre con datos pre-cargados → save → recibe sub-version nueva.
4. **Tipear N° existente.** onBlur fetch → autocompletar → save → append.
5. **Tipear N° NO existente.** onBlur fetch → no encuentra → mode pasa a `new_client`.
6. **Concurrencia "new_client" simultánea.** A y B con same proposed. A wins, B baja a next.
7. **Concurrencia "append_to" simultánea.** A agrega sub 5, B sub 6.
8. **Estado: borrador → enviado → aprobado → entregado.** Cron archiva +10 días.
9. **Estado: borrador → perdido.** Sin archivado.
10. **Estado: invalid transition** (ej: entregado → borrador). Server rechaza.
11. **Endpoint down al cargar calc.** Calc arranca limpia, input N° vacío.
12. **`?load=X-Y` con Y inválido.** Calc ignora, arranca limpia.

---

## 13. Cuándo se actualiza este doc

- Cambia algún campo del JSON (bump version).
- Cambia el algoritmo de generación de N°.
- Cambian las transiciones de estados.
- Cambia la política de retención (ej: 10 días → 30 días).
- Default: revisión cada 90 días.

---

*Este es el contrato lockeado. Toda implementación debe respetarlo. Toda modificación debe pasar por decision-log.*
