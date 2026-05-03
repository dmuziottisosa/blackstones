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
      "transiciones": [
        { "estado": "borrador", "at": "2026-03-15T10:00:00-03:00" },
        { "estado": "enviado", "at": "2026-03-15T11:30:00-03:00" },
        { "estado": "aprobado", "at": "2026-03-18T09:00:00-03:00" },
        { "estado": "entregado", "at": "2026-04-10T16:00:00-03:00" }
      ],
      "presupuesto": null,
      "summary": {
        "monto_usd": 2100,
        "monto_ars": 0,
        "m2_total": 5.6,
        "materiales": ["Granito Importado · Blanco Dallas"],
        "secciones_count": 1,
        "archivado_at": "2026-04-17T03:00:00-03:00"
      }
    },
    {
      "sub": 2,
      "fecha": "2026-04-15T15:20:00-03:00",
      "concepto": "cocina depto hijo",
      "estado": "perdido",
      "transiciones": [
        { "estado": "borrador", "at": "2026-04-15T15:20:00-03:00" },
        { "estado": "enviado", "at": "2026-04-15T17:00:00-03:00" },
        { "estado": "perdido", "at": "2026-04-22T10:00:00-03:00" }
      ],
      "presupuesto": { "...": "full data ver § 3" },
      "summary": null
    },
    {
      "sub": 3,
      "fecha": "2026-05-01T11:00:00-03:00",
      "concepto": "baño",
      "estado": "enviado",
      "transiciones": [
        { "estado": "borrador", "at": "2026-05-01T11:00:00-03:00" },
        { "estado": "enviado", "at": "2026-05-01T13:45:00-03:00" }
      ],
      "presupuesto": { "...": "full data" },
      "summary": null
    },
    {
      "sub": 4,
      "fecha": "2026-05-02T14:30:00-03:00",
      "concepto": "baño v2 (cambió bacha)",
      "estado": "borrador",
      "transiciones": [
        { "estado": "borrador", "at": "2026-05-02T14:30:00-03:00" }
      ],
      "presupuesto": { "...": "full data" },
      "summary": null
    }
  ]
}
```

---

## 3. Estructura del campo `presupuesto`

Cuando `estado != "entregado-archivado"`, este campo contiene **todo lo necesario para rehidratar el estado completo de la calc**:

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
     │ entregado  │ → +10 días → archivado (presupuesto = null + summary)
     └────────────┘
```

### 4.2 Transiciones permitidas

| Desde | A | Trigger |
|---|---|---|
| borrador | enviado | **Manual** desde página de presupuestos. |
| borrador | perdido | **Manual**. |
| enviado | aprobado | **Manual**. |
| enviado | perdido | **Manual**. |
| aprobado | entregado | **Manual**. Dispara aviso UI (ver § 4.4). |
| entregado | (archivado) | Automático por cron, +10 días después de `entregado_at`. |

**Todas las transiciones de estado son manuales.** Sin auto-transiciones por exportación ni por otros disparadores. El equipo decide explícitamente cada cambio desde la página de presupuestos.

**Sin transiciones inversas.** Si el cliente "vuelve" después de perder, se crea sub-versión nueva con estado `borrador`, no se reabre la perdida.

### 4.3 Campo `transiciones[]`

Array append-only que registra cada cambio de estado con timestamp. Permite analytics futuros (ej: tiempo medio enviado→aprobado).

### 4.4 Aviso UI al pasar a APROBADO

Cuando el equipo cambia el estado de una cotización a `aprobado` desde la página de presupuestos, el frontend muestra un modal/toast informativo:

> **"Si querés guardar este presupuesto para tus records, descargalo ahora.**
> **Cuando pase a ENTREGADO + 10 días, el detalle se archiva (queda solo el resumen)."**
>
> `[⬇ Descargar Excel]` `[⬇ Descargar PDF]` `[Cerrar]`

**Comportamiento:**
- El aviso es **informativo, no bloqueante**. El estado YA pasó a aprobado al momento del aviso.
- Los botones de descarga llaman a las mismas funciones que la calc (`generarExcel`/`generarPDF`) usando el JSON cargado.
- El aviso aparece **una sola vez por transición** — si el equipo vuelve a editar y re-aprobar, no spamea.
- **Cero acción server-side**: no se genera ni guarda Excel automático en server. La descarga es opcional y manual.

**Por qué este aviso existe:** anticipar el archive del detalle en ENTREGADO+7. El equipo tiene el momento APROBADO como punto natural para "asegurar el archivo si lo necesitan" — antes el ciclo de entrega + retención lo borraría sin aviso.

**No se gestiona contabilidad en v1.** El equipo decide qué hacer con el Excel descargado (mandarlo a la contadora, guardarlo en Drive, archivarlo local). La carpeta `bs-data/contabilidad/` **no existe** en este modelo.

---

## 5. Light-archive (post-entregado +10 días)

### 5.1 Qué hace el cron

Script `archive-delivered.php` corre nightly (cron de Hostinger). Para cada cliente.json:

```
Para cada cotización en cotizaciones:
  Si cotización.estado == "entregado":
    fecha_entregado = ultima transicion a "entregado" (.at)
    Si (now - fecha_entregado) > 10 días:
      cotización.summary = generarSummary(cotización.presupuesto)
      cotización.presupuesto = null
      cotización.summary.archivado_at = now
```

### 5.2 Estructura del summary

Contiene lo mínimo para responder "qué hizo este cliente con nosotros":

```json
{
  "monto_usd": 2100,
  "monto_ars": 0,
  "m2_total": 5.6,
  "materiales": ["Granito Importado · Blanco Dallas", "Silestone · Calacatta Gold"],
  "secciones_count": 2,
  "archivado_at": "2026-04-17T03:00:00-03:00"
}
```

Campos:
- `monto_usd` y `monto_ars`: totales antes de IVA.
- `m2_total`: suma de m² de todas las secciones + zócalos.
- `materiales`: lista deduplicada de "Material · Color" usados.
- `secciones_count`: cuántas secciones tuvieron items (mesada, alzada, etc.).
- `archivado_at`: ISO timestamp del momento del archivado.

### 5.3 Recuperabilidad

**No hay recuperación automática del detalle archivado.** Si el equipo necesita el detalle dentro de la ventana de 10 días, tiene que descargar el Excel/PDF antes. Después de archivar = solo summary.

Mitigación opcional: si dolió → ampliar la ventana a 14 o 30 días con cambio en `archive-delivered.php`.

---

## 6. Generación del N° de cliente

### 6.1 Algoritmo `next-nro.php`

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

### 6.2 Modos de save

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

### 6.3 Resolución de modos en el server

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

### 6.4 Cómo la calc determina el `mode`

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

## 7. Endpoints

### 7.1 `GET /api/next-nro.php`

**Auth:** HMAC cookie de la calc.
**Response:**
```json
{ "next": "0044" }
```

### 7.2 `POST /api/save.php`

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

### 7.3 `GET /api/load.php?nro={X}[&sub={Y}]`

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

### 7.4 `GET /api/list.php?[query=string]&[estado=X]&[fecha_desde=YYYY-MM-DD]&[fecha_hasta=...]&[page=1]&[per_page=50]`

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

### 7.5 `POST /api/state.php`

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

## 8. Versionado del contract

`version: "1.0"` está hardcoded en cada archivo. Si en el futuro se cambian campos:

- **Adición de campos opcionales:** mantiene `1.0`. La calc nueva los lee, la vieja los ignora.
- **Cambio de campo existente o nuevo campo obligatorio:** bump a `1.1`. Migración script obligatoria.
- **Cambio breaking de estructura:** bump a `2.0`. Migración + período de doble lectura (calc lee 1.x y 2.0 mientras dura la migración).

Política: cada cambio de versión documentado en `01-strategy/decision-log/` con razón + plan de migración.

---

## 9. Edge cases documentados

### 9.1 DNI faltante
Cliente sin DNI se acepta. El campo queda `""` en el JSON. La búsqueda en `list.php` por DNI lo ignora.

### 9.2 Cliente con un solo presupuesto perdido y desaparece
El archivo del cliente queda en disco con su único presupuesto perdido. No se borra. La página de presupuestos lo muestra normalmente.

### 9.3 Borrar manualmente un presupuesto
**No implementado en MVP.** Si hay que borrar, el equipo edita el JSON a mano (o usa una utility CLI futura). Razón: forzar trazabilidad, no perder data por accidente.

### 9.4 Calc carga `?load=0042-99` (sub que no existe)
Server `load.php` devuelve error. Calc fallback: ignora el query param, arranca limpia con next nro auto-asignado.

### 9.5 Concurrencia en append_to
Dos equipos agregan sub-versiones al mismo cliente al mismo segundo. Flock serializa. Ambos quedan registrados, sub crecientes consecutivos.

### 9.6 El cron de archive-delivered falla
Idempotente: corre nightly, si una corrida falla, la siguiente toma los pendientes. Sin pérdida de datos.

### 9.7 El registry está abajo (endpoints no responden)
La calc detecta y muestra mensaje "Registry no disponible, podés seguir trabajando localmente y guardar después". El estado en la calc se sigue persistiendo en localStorage como hoy.

---

## 10. Compatibilidad con la calc actual

### 10.1 Lo que se agrega a la calc

Solo dos cosas, sin tocar nada más:

1. **Botón "Guardar presupuesto"** junto a Excel + PDF. Wrapper try/catch + fallback toast.
2. **Lógica de hidratación** al cargar:
   - Si query param `?cliente=X` o `?load=X-Y` → fetch + autocompletar inputs.
   - Si no hay query param → fetch `next-nro.php` y autocompletar input N° (try/catch fallback: input vacío como hoy).

### 10.2 Lo que NO se toca

- El layout de la calc.
- El flow de exports (Excel/PDF) más allá del refactor a `exports.js`.
- La auth.
- Los inputs existentes (cliente, dni, celular, dirección, fecha, N°).
- El cálculo de m² y totales.
- Las variantes y adicionales.

### 10.3 Reverse-compatibility

Si mañana el dueño dice "saquemos el registry":
1. Borrar carpeta `calculadora/api/`.
2. Borrar página `presupuestos/`.
3. Borrar carpeta `bs-data/` (después de exportar a un zip si querés data).
4. La calc sigue funcionando idéntica al baseline v1.3.

El botón "Guardar presupuesto" en la calc queda inerte (siempre falla) — se borra cuando se quiera con un patch single-line.

---

## 11. Test cases para validar el contract

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

## 12. Cuándo se actualiza este doc

- Cambia algún campo del JSON (bump version).
- Cambia el algoritmo de generación de N°.
- Cambian las transiciones de estados.
- Cambia la política de retención (ej: 10 días → 30 días).
- Default: revisión cada 90 días.

---

*Este es el contrato lockeado. Toda implementación debe respetarlo. Toda modificación debe pasar por decision-log.*
